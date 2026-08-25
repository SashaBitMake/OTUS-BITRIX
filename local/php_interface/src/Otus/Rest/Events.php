<?php

namespace Otus\Rest;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Rest\RestException;
use Otus\Service\BookService;

Loc::loadMessages(__FILE__);

class Events
{
    /**
     * Регистрация REST-методов.
     * После изменения состава методов очистить кеш scope:
     * \Bitrix\Main\Data\Cache::clearCache(true, '/rest/scope/');
     *
     * @return array[]
     */
    public static function OnRestServiceBuildDescriptionHandler(): array
    {
        return [
            'otus.book' => [
                'otus.book.add' => [__CLASS__, 'add'],
                'otus.book.update' => [__CLASS__, 'update'],
                'otus.book.delete' => [__CLASS__, 'delete'],
                'otus.book.get' => [__CLASS__, 'get'],
                'otus.book.list' => [__CLASS__, 'getList'],

                \CRestUtil::EVENTS => [
                    'onAfterOtusBookAdd' => [
                        'main',
                        'onAfterOtusBookAdd',
                        [__CLASS__, 'prepareEventData'],
                    ],
                    'onBeforeOtusBookAdd' => [
                        'main',
                        'onBeforeOtusBookAdd',
                        [__CLASS__, 'prepareEventData'],
                    ],
                ],
            ],
            'otus' => [
                'otus.getHttpInfo' => [__CLASS__, 'getHttpInfo'],
            ],
        ];
    }

    /**
     * otus.book.add
     *
     * Параметры:
     *   BOOK.TITLE  string  обязательный
     *   BOOK.AUTHOR string  опциональный
     *   BOOK.YEAR   int     опциональный
     *   BOOK.ISBN   string  опциональный
     *
     * Ответ:
     *   { "id": 123 }
     *
     * @throws RestException
     */
    public static function add($arParams, $navStart, \CRestServer $server)
    {
        Logger::log('otus.book.add', ['params' => $arParams]);

        $userId = self::getUserId($server);
        $fields = is_array($arParams['BOOK'] ?? null) ? $arParams['BOOK'] : [];

        $eventBefore = new Event('main', 'onBeforeOtusBookAdd', ['FIELDS' => $fields, 'USER_ID' => $userId]);
        $eventBefore->send();

        $service = new BookService();
        $result = $service->add($fields, $userId);

        if (!$result->isSuccess()) {
            self::throwError($result, 'otus.book.add');
        }

        $id = $result->getData()['ID'];

        $eventAfter = new Event('main', 'onAfterOtusBookAdd', ['ID' => $id, 'FIELDS' => $fields]);
        $eventAfter->send();

        $response = ['id' => $id];
        Logger::log('otus.book.add.result', $response);

        return $response;
    }

    /**
     * otus.book.update
     *
     * Параметры:
     *   id           int     обязательный
     *   FIELDS.TITLE  string  опциональный
     *   FIELDS.AUTHOR string  опциональный
     *   FIELDS.YEAR   int     опциональный
     *   FIELDS.ISBN   string  опциональный
     *
     * Ответ:
     *   { "id": 123 }
     *
     * @throws RestException
     */
    public static function update($arParams, $navStart, \CRestServer $server)
    {
        Logger::log('otus.book.update', ['params' => $arParams]);

        $userId = self::getUserId($server);
        $id = self::requirePositiveInt($arParams, 'id');
        $fields = is_array($arParams['FIELDS'] ?? null) ? $arParams['FIELDS'] : [];

        $service = new BookService();
        $result = $service->update($id, $fields, $userId);

        if (!$result->isSuccess()) {
            self::throwError($result, 'otus.book.update');
        }

        $response = ['id' => $id];
        Logger::log('otus.book.update.result', $response);

        return $response;
    }

    /**
     * otus.book.delete
     *
     * Параметры:
     *   id int обязательный
     *
     * Ответ:
     *   { "id": 123 }
     *
     * Запись удаляется физически (в ORM нет поля ACTIVE для soft delete).
     *
     * @throws RestException
     */
    public static function delete($arParams, $navStart, \CRestServer $server)
    {
        Logger::log('otus.book.delete', ['params' => $arParams]);

        $userId = self::getUserId($server);
        $id = self::requirePositiveInt($arParams, 'id');

        $service = new BookService();
        $result = $service->delete($id, $userId);

        if (!$result->isSuccess()) {
            self::throwError($result, 'otus.book.delete');
        }

        $response = ['id' => $id];
        Logger::log('otus.book.delete.result', $response);

        return $response;
    }

    /**
     * otus.book.get
     *
     * Параметры:
     *   id int обязательный
     *
     * Ответ:
     *   { "item": { "ID": 1, "TITLE": "...", "AUTHOR": "...", "YEAR": 1967, "ISBN": "..." } }
     *
     * @throws RestException
     */
    public static function get($arParams, $navStart, \CRestServer $server)
    {
        Logger::log('otus.book.get', ['params' => $arParams]);

        $userId = self::getUserId($server);
        $id = self::requirePositiveInt($arParams, 'id');

        $service = new BookService();
        $book = $service->get($id, $userId);

        if (!$book) {
            // Намеренно не различаем "не найдено" и "нет прав" в тексте ответа,
            // чтобы не давать возможность перебором ID проверять существование чужих записей.
            Logger::log('otus.book.get.error', ['id' => $id]);

            throw new RestException(
                Loc::getMessage('OTUS_BOOK_NOT_FOUND') ?: 'Book not found',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        $response = ['item' => $book];
        Logger::log('otus.book.get.result', ['id' => $id]);

        return $response;
    }

    /**
     * otus.book.list
     *
     * Параметры:
     *   filter object опциональный
     *   order  object опциональный
     *   select array  опциональный
     *   limit  int    опциональный (по умолчанию/максимум 50)
     *   offset int    опциональный (либо стандартный параметр "start" REST-сервера)
     *
     * Ответ:
     *   { "items": [...], "count": 12 }
     */
    public static function getList($arParams, $navStart, \CRestServer $server)
    {
        Logger::log('otus.book.list', ['params' => $arParams]);

        $userId = self::getUserId($server);

        $filter = is_array($arParams['filter'] ?? null) ? $arParams['filter'] : [];
        $order = is_array($arParams['order'] ?? null) ? $arParams['order'] : [];
        $select = is_array($arParams['select'] ?? null) ? $arParams['select'] : [];
        $limit = (int)($arParams['limit'] ?? 50);
        $offset = $navStart !== null && $navStart !== false ? (int)$navStart : (int)($arParams['offset'] ?? 0);

        $service = new BookService();
        $listResult = $service->getList($filter, $order, $select, $limit, $offset, $userId);

        Logger::log('otus.book.list.result', ['count' => $listResult['count']]);

        return $listResult;
    }

    public static function getHttpInfo($arParams, $navStart, \CRestServer $server)
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        return [
            'arParams' => $arParams,
            'onlyQuery' => $request->getQueryList()->toArray(),
            'onlyBody' => $request->getPostList()->toArray(),
            'navStart' => $navStart,
            'server' => [
                'methodName' => $server->getMethod(),
            ],
            'httpMethod' => $request->isPost() ? 'POST' : 'GET',
        ];
    }

    /**
     * Обработчик событий onBefore/onAfterOtusBookAdd - для логирования/расширения.
     */
    public static function prepareEventData($arguments, $handler)
    {
        /** @var Event $event */
        $event = reset($arguments);
        $response = $event->getParameters();

        Logger::log('event:' . $event->getEventType(), $response);

        return $response;
    }

    private static function getUserId(\CRestServer $server): int
    {
        $authData = $server->getAuthData();

        return (int)($authData['user_id'] ?? 0);
    }

    /**
     * @throws RestException
     */
    private static function requirePositiveInt(array $params, string $key): int
    {
        $value = (int)($params[$key] ?? 0);

        if ($value <= 0) {
            throw new RestException(
                sprintf('Parameter "%s" is required and must be a positive integer', $key),
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_WRONG_REQUEST
            );
        }

        return $value;
    }

    /**
     * @throws RestException
     */
    private static function throwError(Result $result, string $method): void
    {
        $errors = $result->getErrors();
        $messages = array_map(static fn (Error $e) => $e->getMessage(), $errors);
        $firstCode = $errors ? $errors[0]->getCode() : 'INTERNAL_ERROR';

        Logger::log($method . '.error', ['code' => $firstCode, 'errors' => $messages]);

        $status = $firstCode === 'ACCESS_DENIED'
            ? \CRestServer::STATUS_FORBIDDEN
            : \CRestServer::STATUS_WRONG_REQUEST;

        throw new RestException(
            implode('; ', $messages),
            RestException::ERROR_ARGUMENT,
            $status
        );
    }
}
