<?php

declare(strict_types=1);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$documentRoot = rtrim(
    (string)($_SERVER['DOCUMENT_ROOT'] ?? ''),
    '/'
);

$prologPath = $documentRoot . '/bitrix/modules/main/include/prolog_before.php';

if (!is_file($prologPath)) {
    http_response_code(500);
    exit('Bitrix prolog not found');
}

require_once $prologPath;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Otus\Rest\Logger;

header('Content-Type: application/json; charset=utf-8');

/**
 * Настройки лучше хранить вне исходного кода (.settings.php / env),
 * здесь оставлены как константы для учебного проекта.
 */
const OUTGOING_APPLICATION_TOKEN = 'efh2i6o46mgycf2t5s73z7iywolnn79u';
const INCOMING_WEBHOOK_URL = 'https://ch871087.tw1.ru/rest/1/rlw682anu7xxph4l/';
const CONTACT_LAST_ACTIVITY_FIELD = 'ufCrmLastCont';
const EXPECTED_EVENT = 'ONCRMACTIVITYADD';

$startTime = microtime(true);

/**
 * Вспомогательная функция для получения текста ошибки HttpClient
 */
function getHttpClientErrorText(HttpClient $client): string
{
    $errors = $client->getError();
    
    if (empty($errors)) {
        return '';
    }
    
    $messages = [];
    foreach ($errors as $error) {
        if (is_array($error)) {
            $messages[] = $error['message'] ?? 'Unknown error';
        } else {
            $messages[] = (string)$error;
        }
    }
    
    return implode('; ', $messages);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new RuntimeException(
            'Only POST requests are allowed',
            405
        );
    }

    /**
     * Bitrix24 outgoing webhook обычно передаёт
     * параметры как POST-поля.
     */
    $requestData = $_POST;

    Logger::log('crm_activity_webhook.incoming', [
        'event' => $requestData['event'] ?? null,
        'activity_id' => $requestData['data']['FIELDS']['ID'] ?? null,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    /**
     * Проверяем application_token.
     */
    $applicationToken = (string)(
        $requestData['auth']['application_token'] ?? ''
    );

    if (
        $applicationToken === '' ||
        !hash_equals(
            OUTGOING_APPLICATION_TOKEN,
            $applicationToken
        )
    ) {
        Logger::log('crm_activity_webhook.invalid_token', [
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        throw new RuntimeException(
            'Invalid application token',
            403
        );
    }

    /**
     * Проверяем, что это именно то событие, которое мы ожидаем.
     */
    $event = strtoupper((string)($requestData['event'] ?? ''));

    if ($event !== EXPECTED_EVENT) {
        Logger::log('crm_activity_webhook.unexpected_event', ['event' => $event]);

        echo Json::encode([
            'status' => 'skipped',
            'reason' => 'unexpected event',
            'event' => $event,
        ]);

        exit;
    }

    /**
     * Получаем ID созданного дела.
     */
    $activityId = (int)(
        $requestData['data']['FIELDS']['ID'] ?? 0
    );

    if ($activityId <= 0) {
        throw new RuntimeException(
            'Activity ID not found in webhook payload',
            400
        );
    }

    $httpClient = new HttpClient();
    $httpClient->setHeader('Content-Type', 'application/json');

    /**
     * ---------------------------------------------------------
     * 1. Получаем активность
     * ---------------------------------------------------------
     */

    $activityRequestData = [
        'id' => $activityId,
    ];

    Logger::log('crm_activity_webhook.api_request', [
        'method' => 'crm.activity.get',
        'params' => $activityRequestData,
    ]);

    $activityResponse = $httpClient->post(
        INCOMING_WEBHOOK_URL . 'crm.activity.get.json',
        Json::encode($activityRequestData)
    );

    $errorText = getHttpClientErrorText($httpClient);
    if ($errorText !== '') {
        throw new RuntimeException(
            'crm.activity.get HTTP request failed: ' . $errorText,
            502
        );
    }

    $activityResult = Json::decode($activityResponse);

    Logger::log('crm_activity_webhook.api_response', [
        'method' => 'crm.activity.get',
        'has_error' => isset($activityResult['error']),
        'result_keys' => array_keys($activityResult['result'] ?? []),
    ]);

    if (isset($activityResult['error'])) {
        Logger::log('crm_activity_webhook.api_error', [
            'method' => 'crm.activity.get',
            'error' => $activityResult['error'],
            'error_description' => $activityResult['error_description'] ?? null,
        ]);

        throw new RuntimeException(
            'crm.activity.get error: ' .
            ($activityResult['error_description'] ?? 'Unknown error'),
            500
        );
    }

    $activity = $activityResult['result'] ?? null;

    if (!is_array($activity)) {
        throw new RuntimeException(
            'Activity data is empty',
            500
        );
    }

    /**
     * ---------------------------------------------------------
     * 2. Определяем владельца активности
     * ---------------------------------------------------------
     */

    $ownerTypeId = (int)(
        $activity['OWNER_TYPE_ID'] ?? 0
    );

    $ownerId = (int)(
        $activity['OWNER_ID'] ?? 0
    );

    Logger::log('crm_activity_webhook.activity_owner', [
        'activity_id' => $activityId,
        'owner_type_id' => $ownerTypeId,
        'owner_id' => $ownerId,
    ]);

    /**
     * Если активность принадлежит сделке, компании,
     * лиду и т.д. — ничего не делаем.
     */
    if ($ownerTypeId !== 3) {
        Logger::log('crm_activity_webhook.skipped_not_contact', [
            'activity_id' => $activityId,
            'owner_type_id' => $ownerTypeId,
        ]);

        echo Json::encode([
            'status' => 'skipped',
            'reason' => 'Activity does not belong to contact',
            'activity_id' => $activityId,
            'owner_type_id' => $ownerTypeId,
            'owner_id' => $ownerId,
        ]);

        exit;
    }

    if ($ownerId <= 0) {
        throw new RuntimeException(
            'Contact ID is empty',
            500
        );
    }

    /**
     * ---------------------------------------------------------
     * 3. Обновляем контакт
     * ---------------------------------------------------------
     */
    $currentDateTime = (new DateTimeImmutable())
        ->format(DateTimeInterface::ATOM);

    $updateRequestData = [
        'entityTypeId' => 3,
        'id' => $ownerId,
        'fields' => [
            CONTACT_LAST_ACTIVITY_FIELD => $currentDateTime,
        ],
    ];

    Logger::log('crm_activity_webhook.api_request', [
        'method' => 'crm.item.update',
        'params' => $updateRequestData,
    ]);

    $updateResponse = $httpClient->post(
        INCOMING_WEBHOOK_URL . 'crm.item.update.json',
        Json::encode($updateRequestData)
    );

    $errorText = getHttpClientErrorText($httpClient);
    if ($errorText !== '') {
        throw new RuntimeException(
            'crm.item.update HTTP request failed: ' . $errorText,
            502
        );
    }

    $updateResult = Json::decode($updateResponse);

    Logger::log('crm_activity_webhook.api_response', [
        'method' => 'crm.item.update',
        'has_error' => isset($updateResult['error']),
    ]);

    if (isset($updateResult['error'])) {
        Logger::log('crm_activity_webhook.api_error', [
            'method' => 'crm.item.update',
            'error' => $updateResult['error'],
            'error_description' => $updateResult['error_description'] ?? null,
        ]);

        throw new RuntimeException(
            'crm.item.update error: ' .
            ($updateResult['error_description'] ?? 'Unknown error'),
            500
        );
    }

    /**
     * ---------------------------------------------------------
     * 4. Успешный ответ
     * ---------------------------------------------------------
     */

    $executionTime = round(microtime(true) - $startTime, 4);

    Logger::log('crm_activity_webhook.success', [
        'activity_id' => $activityId,
        'contact_id' => $ownerId,
        'execution_time' => $executionTime . 's',
    ]);

    echo Json::encode([
        'status' => 'success',
        'activity_id' => $activityId,
        'contact_id' => $ownerId,
        'field' => CONTACT_LAST_ACTIVITY_FIELD,
        'datetime' => $currentDateTime,
    ]);

} catch (Throwable $exception) {

    $statusCode = $exception->getCode();

    if ($statusCode < 400 || $statusCode > 599) {
        $statusCode = 500;
    }

    $executionTime = round(microtime(true) - $startTime, 4);

    Logger::log('crm_activity_webhook.error', [
        'message' => $exception->getMessage(),
        'code' => $statusCode,
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'execution_time' => $executionTime . 's',
    ]);

    http_response_code($statusCode);

    echo Json::encode([
        'status' => 'error',
        'message' => $exception->getMessage(),
    ]);
}