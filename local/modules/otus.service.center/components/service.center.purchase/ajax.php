<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Otus\Service\Center\Services\PurchaseService;

header('Content-Type: application/json; charset=utf-8');

global $USER;

if (!$USER->IsAuthorized() || !check_bitrix_sessid()) {
    echo json_encode(['ok' => false, 'error' => 'Access denied'], JSON_UNESCAPED_UNICODE);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    die();
}

Loader::includeModule('otus.service.center');

$action = (string) ($_REQUEST['action'] ?? '');
$requestId = (int) ($_REQUEST['request_id'] ?? 0);
$userId = (int) $USER->GetID();

$service = new PurchaseService();
$result = null;

if ($action === 'approve') {
    $result = $service->approve($requestId, $userId);
} elseif ($action === 'reject') {
    $result = $service->reject($requestId, $userId, (string) ($_REQUEST['reason'] ?? ''));
}

if ($result === null) {
    echo json_encode(['ok' => false, 'error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
} elseif ($result->isSuccess()) {
    echo json_encode(['ok' => true, 'message' => 'Операция выполнена.'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(
        ['ok' => false, 'error' => implode(' ', $result->getErrorMessages())],
        JSON_UNESCAPED_UNICODE
    );
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
die();
