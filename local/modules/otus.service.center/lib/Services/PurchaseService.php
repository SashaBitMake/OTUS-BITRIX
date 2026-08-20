<?php

declare(strict_types=1);

namespace Otus\Service\Center\Services;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Otus\Service\Center\Helpers\CrmHelper;
use Otus\Service\Center\ORM\PurchaseRequestItemTable;
use Otus\Service\Center\ORM\PurchaseRequestTable;
use Throwable;

Loc::loadMessages(__FILE__);

final class PurchaseService
{
    private const SYSTEM_USER_ID = 0;

    /**
     * Конструктор: загружает модули CRM и otus.service.center.
     *
     * @return void
     *
     * @throws \Bitrix\Main\LoaderException Если один из модулей недоступен
     */
    public function __construct()
    {
        Loader::includeModule('crm');
        Loader::includeModule('otus.service.center');
    }

    /**
     * Создание заявки (NEW) + позиции в отдельной таблице.
     *
     * @param int   $authorId  ID автора заявки (>0 — пользователь, 0 — "Система")
     * @param array $items     Массив позиций [['product_id' => int, 'quantity' => int], ...]
     * @param bool  $automatic true для авто-заявок агента (IS_AUTOMATIC='Y')
     *
     * @return Result data: ['id' => int] — ID созданной заявки
     */
    public function createRequest(int $authorId, array $items, bool $automatic = false): Result
    {
        $result = new Result();

        if ($authorId < 0) {
            $result->addError(new Error('Некорректный автор заявки.'));

            return $result;
        }

        if (empty($items)) {
            $result->addError(new Error('Заявка должна содержать хотя бы одну позицию.'));

            return $result;
        }

        foreach ($items as $index => $item) {
            if ((int) ($item['product_id'] ?? 0) <= 0 || (int) ($item['quantity'] ?? 0) <= 0) {
                $result->addError(new Error('Позиция №' . ($index + 1) . ': некорректные данные.'));

                return $result;
            }
        }

        try {
            $names = [];

            foreach ($items as $row) {
                $product = \CCrmProduct::GetByID((int) $row['product_id']);
                $names[] = is_array($product) ? (string) ($product['NAME'] ?? '') : '#' . $row['product_id'];
            }

            $title = ($automatic ? 'Авто-заявка: ' : 'Заявка: ') . implode(', ', $names);

            $add = PurchaseRequestTable::add([
                'TITLE' => mb_substr($title, 0, 250),
                'STATUS' => CrmHelper::REQUEST_STAGE_NEW,
                'AUTHOR_ID' => $authorId,
                'IS_AUTOMATIC' => $automatic ? 'Y' : 'N',
                'CREATED_AT' => DateTime::createFromTimestamp(time()),
            ]);

            if (!$add->isSuccess()) {
                foreach ($add->getErrors() as $error) {
                    $result->addError($error);
                }

                return $result;
            }

            $requestId = (int) $add->getId();

            foreach ($items as $row) {
                $itemAdd = PurchaseRequestItemTable::add([
                    'REQUEST_ID' => $requestId,
                    'PRODUCT_ID' => (int) $row['product_id'],
                    'QUANTITY' => (int) $row['quantity'],
                ]);

                if (!$itemAdd->isSuccess()) {
                    foreach ($itemAdd->getErrors() as $error) {
                        $result->addError($error);
                    }

                    return $result;
                }
            }

            $result->setData(['id' => $requestId]);
        } catch (Throwable $e) {
            $result->addError(new Error('Не удалось создать заявку: ' . $e->getMessage()));
        }

        return $result;
    }

    /**
     * Авто-заявка агента: создаёт и сразу одобряет (остаток +10).
     *
     * @param int $productId ID товара CRM
     * @param int $quantity  Количество для пополнения (обычно AGENT_REPLENISH_QUANTITY = 10)
     *
     * @return Result data: ['id' => int] — ID созданной заявки
     */
    public function createAutomaticRequest(int $productId, int $quantity): Result
    {
        $create = $this->createRequest(self::SYSTEM_USER_ID, [
            ['product_id' => $productId, 'quantity' => $quantity],
        ], true);

        if (!$create->isSuccess()) {
            return $create;
        }

        $requestId = (int) $create->getData()['id'];

        if (!$this->startPurchaseWorkflow($requestId, true)) {
            return $this->approveAutomatic($requestId);
        }

        return $create;
    }

    /**
     * Запуск БП заявки на HL-блоке.
     *
     * @param int  $requestId ID заявки
     * @param bool $auto      true для авто-одобрения (AutoApprove='Y')
     *
     * @return bool true — БП запущен; false — шаблон не настроен или ошибка
     */
    public function startPurchaseWorkflow(int $requestId, bool $auto): bool
    {
        $templateId = (int) Option::get(
            CrmHelper::MODULE_ID,
            CrmHelper::OPTION_PURCHASE_BP_TEMPLATE_ID,
            '0'
        );
        $hlId = (int) Option::get(CrmHelper::MODULE_ID, CrmHelper::OPTION_PURCHASE_HL_ID, '0');

        if ($templateId <= 0 || $hlId <= 0) {
            return false;
        }

        try {
            Loader::includeModule('bizproc');
            Loader::includeModule('highloadblock');

            \CBPDocument::StartWorkflow(
                $templateId,
                [
                    'highloadblock',
                    'Bitrix\HighloadBlock\HighloadBlockTable',
                    'HLBLOCK_' . $hlId . '_' . $requestId,
                ],
                true,
                ['RequestId' => $requestId, 'AutoApprove' => $auto ? 'Y' : 'N']
            );

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Обработка заявки активностью БП (публичный вход для CreatePurchaseRequestActivity).
     *
     * AutoApprove=Y — сразу одобряет (остатки +10, уведомление отделу).
     * AutoApprove=N — только уведомляет отдел закупок (заявка ждёт ручного решения).
     *
     * @param int  $requestId ID заявки
     * @param bool $auto      true для авто-одобрения
     *
     * @return Result Успех или ошибки обработки
     */
    public function processByActivity(int $requestId, bool $auto): Result
    {
        if ($auto) {
            return $this->approveAutomatic($requestId);
        }

        $this->notifyProcurementDept($requestId);

        return new Result();
    }

    /**
     * Одобрение заявки вручную сотрудником отдела закупок (атомарно в транзакции).
     *
     * @param int $requestId ID заявки
     * @param int $userId    ID пользователя, который одобряет
     *
     * @return Result Успех или ошибки (недостаточно прав, не тот статус, и т.д.)
     */
    public function approve(int $requestId, int $userId): Result
    {
        $result = new Result();

        $this->validateStatus($requestId, CrmHelper::REQUEST_STAGE_NEW, $result);
        $this->validateRights($userId, $requestId, $result);

        if (!$result->isSuccess()) {
            return $result;
        }

        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            $items = $this->getItems($requestId);

            if (empty($items)) {
                $result->addError(new Error(Loc::getMessage('OTUS_SC_PURCHASE_EMPTY_ITEMS')));
                $connection->rollbackTransaction();

                return $result;
            }

            $this->increaseStock($items, $result);

            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();

                return $result;
            }

            $this->markProcessed($requestId, CrmHelper::REQUEST_STAGE_APPROVED, $userId, null, $result);

            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();

                return $result;
            }

            $connection->commitTransaction();

            $this->notifyAuthor($requestId, Loc::getMessage('OTUS_SC_PURCHASE_NOTIFY_APPROVED'));
        } catch (Throwable $e) {
            $connection->rollbackTransaction();
            $result->addError(new Error('Ошибка одобрения: ' . $e->getMessage()));
        }

        return $result;
    }

    /**
     * Авто-одобрение заявки (без проверки прав, для агента остатков).
     *
     * @param int $requestId ID заявки
     *
     * @return Result Успех или ошибки (не удалось обновить остаток, и т.д.)
     */
    private function approveAutomatic(int $requestId): Result
    {
        $result = new Result();
        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            $items = $this->getItems($requestId);

            $this->increaseStock($items, $result);

            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();

                return $result;
            }

            $this->markProcessed($requestId, CrmHelper::REQUEST_STAGE_APPROVED, self::SYSTEM_USER_ID, null, $result);

            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();

                return $result;
            }

            $connection->commitTransaction();

            $this->notifyProcurementDept(
                $requestId,
                'Запчасть закончилась — автоматически закуплено '
                . CrmHelper::AGENT_REPLENISH_QUANTITY . ' шт.'
            );
        } catch (Throwable $e) {
            $connection->rollbackTransaction();
            $result->addError(new Error('Ошибка авто-одобрения: ' . $e->getMessage()));
        }

        return $result;
    }

    /**
     * Отклонение заявки с обязательной причиной (атомарно в транзакции).
     *
     * @param int    $requestId ID заявки
     * @param int    $userId    ID пользователя, который отклоняет
     * @param string $reason    Причина отказа (обязательна, не пустая)
     *
     * @return Result Успех или ошибки (недостаточно прав, пустая причина, и т.д.)
     */
    public function reject(int $requestId, int $userId, string $reason): Result
    {
        $result = new Result();

        $this->validateStatus($requestId, CrmHelper::REQUEST_STAGE_NEW, $result);
        $this->validateRights($userId, $requestId, $result);

        if (trim($reason) === '') {
            $result->addError(new Error(Loc::getMessage('OTUS_SC_PURCHASE_REASON_REQUIRED')));
        }

        if (!$result->isSuccess()) {
            return $result;
        }

        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            $this->markProcessed($requestId, CrmHelper::REQUEST_STAGE_REJECTED, $userId, trim($reason), $result);

            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();

                return $result;
            }

            $connection->commitTransaction();

            $this->notifyAuthor(
                $requestId,
                Loc::getMessage('OTUS_SC_PURCHASE_NOTIFY_REJECTED', ['#REASON#' => trim($reason)])
            );
        } catch (Throwable $e) {
            $connection->rollbackTransaction();
            $result->addError(new Error('Ошибка отклонения: ' . $e->getMessage()));
        }

        return $result;
    }

    /**
     * Чтение шапки заявки из БД.
     *
     * @param int $requestId ID заявки
     *
     * @return array{
     *   ID: int,
     *   TITLE: string,
     *   STAGE_ID: string,
     *   AUTHOR_ID: int,
     *   PROCESSED_BY_ID: int,
     *   PROCESSED_AT: DateTime|null,
     *   REJECT_REASON: string,
     *   IS_AUTOMATIC: bool,
     *   CREATED_AT: DateTime|null
     * }|null null если заявка не найдена
     */
    public function getRequest(int $requestId): ?array
    {
        $row = PurchaseRequestTable::getList([
            'filter' => ['=ID' => $requestId],
        ])->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'ID' => (int) $row['ID'],
            'TITLE' => (string) $row['TITLE'],
            'STAGE_ID' => (string) $row['STATUS'],
            'AUTHOR_ID' => (int) $row['AUTHOR_ID'],
            'PROCESSED_BY_ID' => (int) $row['PROCESSED_BY_ID'],
            'PROCESSED_AT' => $row['PROCESSED_AT'],
            'REJECT_REASON' => (string) ($row['REJECT_REASON'] ?? ''),
            'IS_AUTOMATIC' => $row['IS_AUTOMATIC'] === 'Y',
            'CREATED_AT' => $row['CREATED_AT'],
        ];
    }

    /**
     * Список заявок (история), новые сверху.
     *
     * @param int $limit Максимум записей (по умолчанию 100)
     *
     * @return array<int, array> Массив заявок в формате getRequest()
     */
    public function getRequests(int $limit = 100): array
    {
        $rows = PurchaseRequestTable::getList([
            'order' => ['ID' => 'DESC'],
            'limit' => $limit,
        ])->fetchAll();

        $requests = [];

        foreach ($rows as $row) {
            $requests[] = $this->getRequest((int) $row['ID']);
        }

        return $requests;
    }

    /**
     * Позиции заявки с названиями товаров из CRM.
     *
     * @param int $requestId ID заявки
     *
     * @return array<int, array{
     *   ID: int,
     *   PRODUCT_ID: int,
     *   QUANTITY: int,
     *   PRODUCT_NAME: string
     * }>
     */
    public function getItems(int $requestId): array
    {
        $rows = PurchaseRequestItemTable::getList([
            'filter' => ['=REQUEST_ID' => $requestId],
            'select' => ['ID', 'PRODUCT_ID', 'QUANTITY'],
            'order' => ['ID' => 'ASC'],
        ])->fetchAll();

        $items = [];

        foreach ($rows as $row) {
            $product = \CCrmProduct::GetByID((int) $row['PRODUCT_ID']);
            $items[] = [
                'ID' => (int) $row['ID'],
                'PRODUCT_ID' => (int) $row['PRODUCT_ID'],
                'QUANTITY' => (int) $row['QUANTITY'],
                'PRODUCT_NAME' => is_array($product) ? (string) ($product['NAME'] ?? '') : '',
            ];
        }

        return $items;
    }

    /**
     * Проверка статуса заявки (добавляет ошибку в Result при несоответствии).
     *
     * @param int    $requestId     ID заявки
     * @param string $expectedStage Ожидаемый статус (NEW/APPROVED/REJECTED)
     * @param Result $result        Result для добавления ошибок
     *
     * @return void
     */
    public function validateStatus(int $requestId, string $expectedStage, Result $result): void
    {
        $request = $this->getRequest($requestId);

        if ($request === null) {
            $result->addError(new Error(Loc::getMessage('OTUS_SC_PURCHASE_NOT_FOUND')));

            return;
        }

        if ($request['STAGE_ID'] !== $expectedStage) {
            $result->addError(new Error(Loc::getMessage('OTUS_SC_PURCHASE_WRONG_STATUS')));
        }
    }

    /**
     * Проверка прав пользователя на обработку заявки.
     *
     * @param int    $userId    ID пользователя
     * @param int    $requestId ID заявки (для проверки авторства)
     * @param Result $result    Result для добавления ошибок
     *
     * @return void
     */
    private function validateRights(int $userId, int $requestId, Result $result): void
    {
        if ($userId <= 0) {
            $result->addError(new Error('Не определён пользователь.'));

            return;
        }

        $groupId = (int) Option::get(CrmHelper::MODULE_ID, CrmHelper::OPTION_PURCHASE_GROUP_ID, '0');

        if ($groupId <= 0) {
            $result->addError(new Error('Группа "Отдел закупок" не настроена.'));

            return;
        }

        $rs = \CUser::GetUserGroup($userId);

        if (!in_array($groupId, array_map('intval', $rs), true)) {
            $result->addError(new Error(Loc::getMessage('OTUS_SC_PURCHASE_NO_RIGHTS')));

            return;
        }

        $request = $this->getRequest($requestId);

        if ($request !== null && $request['AUTHOR_ID'] === $userId) {
            $result->addError(new Error(Loc::getMessage('OTUS_SC_PURCHASE_SELF_APPROVAL')));
        }
    }

    /**
     * Увеличение штатных остатков товаров (QUANTITY) на количество из заявки.
     *
     * @param array  $items  Массив позиций из getItems()
     * @param Result $result Result для добавления ошибок
     *
     * @return void
     */
    private function increaseStock(array $items, Result $result): void
    {
        foreach ($items as $row) {
            $ok = StockService::increaseProductStock((int) $row['PRODUCT_ID'], (int) $row['QUANTITY']);

            if (!$ok) {
                $result->addError(new Error('Не удалось обновить остаток товара #' . $row['PRODUCT_ID'] . '.'));

                return;
            }
        }
    }

    /**
     * Финальный статус + метаданные обработки (кто/когда/причина отказа).
     *
     * @param int         $requestId    ID заявки
     * @param string      $status       Новый статус (APPROVED/REJECTED)
     * @param int         $userId       ID обработчика (0 для "Система")
     * @param string|null $rejectReason Причина отказа (только для REJECTED)
     * @param Result      $result       Result для добавления ошибок
     *
     * @return void
     */
    private function markProcessed(
        int $requestId,
        string $status,
        int $userId,
        ?string $rejectReason,
        Result $result
    ): void {
        $fields = [
            'STATUS' => $status,
            'PROCESSED_BY_ID' => $userId > 0 ? $userId : 0,
            'PROCESSED_AT' => DateTime::createFromTimestamp(time()),
        ];

        if ($rejectReason !== null) {
            $fields['REJECT_REASON'] = $rejectReason;
        }

        $update = PurchaseRequestTable::update($requestId, $fields);

        if (!$update->isSuccess()) {
            foreach ($update->getErrors() as $error) {
                $result->addError($error);
            }
        }
    }

    /**
     * Уведомление автору заявки (со ссылкой на карточку).
     *
     * @param int    $requestId ID заявки
     * @param string $message   Текст уведомления (без ссылки — добавляется автоматически)
     *
     * @return void
     */
    private function notifyAuthor(int $requestId, string $message): void
    {
        $request = $this->getRequest($requestId);

        if ($request === null || $request['AUTHOR_ID'] <= 0) {
            return;
        }

        $this->imNotify($request['AUTHOR_ID'], $message, $requestId);
    }

    /**
     * Уведомление о заявке: отдел закупок + начальник отдела (fallback ТЗ п.5.2).
     *
     * @param int    $requestId     ID заявки
     * @param string $customMessage Произвольный префикс сообщения:
     *                              - пусто → "Новая заявка на закупку #N: …"
     *                              - не пусто → "<prefix> Заявка #N: …" (для автозакупки)
     *
     * @return void
     */
    public function notifyProcurementDept(int $requestId, string $customMessage = ''): void
    {
        $request = $this->getRequest($requestId);
        if ($request === null) {
            return;
        }
        $base = $customMessage !== ''
            ? $customMessage . ' Заявка #' . $requestId . ': ' . $request['TITLE']
            : 'Новая заявка на закупку #' . $requestId . ': ' . $request['TITLE'];

        $this->notifyGroup(
            (int) Option::get(CrmHelper::MODULE_ID, CrmHelper::OPTION_PURCHASE_GROUP_ID, '0'),
            $request['AUTHOR_ID'],
            $base,
            $requestId,
        );
        $this->notifyGroup(
            (int) Option::get(CrmHelper::MODULE_ID, CrmHelper::OPTION_PURCHASE_HEAD_GROUP_ID, '0'),
            $request['AUTHOR_ID'],
            '[Копия начальнику] ' . $base,
            $requestId,
        );
    }

    /**
     * Рассылка по группе (до 10 получателей), автор заявки пропускается.
     *
     * @param int    $groupId   ID группы пользователей
     * @param int    $authorId  ID автора заявки (исключается из рассылки)
     * @param string $message   Текст уведомления
     * @param int    $requestId ID заявки (для ссылки в уведомлении)
     *
     * @return void
     */
    private function notifyGroup(int $groupId, int $authorId, string $message, int $requestId): void
    {
        if ($groupId <= 0) {
            return;
        }
        $connection = Application::getConnection();
        $rows = $connection->query(
            'SELECT ug.USER_ID FROM b_user_group ug '
            . 'INNER JOIN b_user u ON u.ID = ug.USER_ID '
            . 'WHERE ug.GROUP_ID = ' . $groupId . " AND u.ACTIVE = 'Y' "
            . 'ORDER BY ug.USER_ID ASC'
        )->fetchAll();

        $sent = 0;
        foreach ($rows as $row) {
            $userId = (int) $row['USER_ID'];
            if ($userId === $authorId) {
                continue;
            }
            $this->imNotify($userId, $message, $requestId);
            if (++$sent >= 10) {
                break;
            }
        }
    }

    /**
     * IM-уведомление пользователю (устойчиво к версии ядра) + ссылка на заявку.
     *
     * @param int    $userId    ID получателя
     * @param string $message   Текст уведомления (ссылка добавляется автоматически)
     * @param int    $requestId ID заявки (0 — без ссылки)
     *
     * @return void
     */
    private function imNotify(int $userId, string $message, int $requestId = 0): void
    {
        if ($userId <= 0) {
            return;
        }

        if (!Loader::includeModule('im')) {
            return;
        }

        $url = $requestId > 0 ? $this->requestLink($requestId) : '';

        if ($url !== '') {
            $message .= ' <a href="' . htmlspecialcharsbx($url) . '">Открыть заявку</a>';
        }

        $fields = [
            'TO_USER_ID' => $userId,
            'FROM_USER_ID' => 0,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'otus.service.center',
            'NOTIFY_EVENT' => 'purchase_request',
            'NOTIFY_MESSAGE' => $message,
            'NOTIFY_MESSAGE_OUT' => $url !== '' ? trim(strip_tags($message)) . "\n" . $url : $message,
        ];

        try {
            if (class_exists('\\CIMNotify') && method_exists('\\CIMNotify', 'Add')) {
                \CIMNotify::Add($fields);
            }
        } catch (Throwable $e) {
            @file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/local/logs/otus_sc_notify.log',
                date('Y-m-d H:i:s') . ' notify failed: ' . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
    }

    /**
     * Абсолютная ссылка на карточку заявки (с протоколом и хостом).
     *
     * @param int $requestId ID заявки
     *
     * @return string URL вида "https://example.com/purchase.php?request_id=123"
     */
    private function requestLink(int $requestId): string
    {
        $path = '/purchase.php?request_id=' . $requestId;

        try {
            $server = Application::getInstance()->getContext()->getServer();

            return ($server->isHttps() ? 'https://' : 'http://') . $server->getHttpHost() . $path;
        } catch (Throwable $e) {
            return $path;
        }
    }
}