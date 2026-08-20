<?php

declare(strict_types=1);

namespace Otus\Service\Center\Services;

use Bitrix\Crm\DealTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Otus\Service\Center\Helpers\CrmHelper;
use Throwable;

final class DealHistoryService
{
    /**
     * История обращений по автомобилю.
     *
     * @param int $carId ID автомобиля из таблицы otus_sc_garage (должен быть > 0)
     *
     * @return Result data: array<array{
     *   ID: int,
     *   TITLE: string,
     *   CLOSED: string,
     *   STAGE_SEMANTIC_ID: string,
     *   DATE_CREATE: DateTime|string|null,
     *   ASSIGNED_BY_ID: int,
     *   ASSIGNED_NAME: string,
     *   OPPORTUNITY: float,
     *   CURRENCY_ID: string,
     *   PRODUCTS: array<array{NAME: string, QTY: float, PRICE: float, SUM: float, MEASURE: string}>
     * }>
     *
     * @throws \Bitrix\Main\LoaderException При недоступности модуля CRM (ловится внутри, добавляется в ошибки Result)
     */
    public function getHistoryByCar(int $carId): Result
    {
        $result = new Result();

        if ($carId <= 0) {
            $result->addError(new Error('Не указан автомобиль.'));

            return $result;
        }

        $serviceCategoryId = (int) Option::get(
            CrmHelper::MODULE_ID,
            CrmHelper::OPTION_SERVICE_CATEGORY_ID,
            '0'
        );

        if ($serviceCategoryId <= 0) {
            $result->addError(new Error('Сервисная воронка не настроена.'));

            return $result;
        }

        try {
            if (!Loader::includeModule('crm')) {
                $result->addError(new Error('Модуль CRM недоступен.'));

                return $result;
            }

            $rows = DealTable::getList([
                'filter' => [
                    '=CATEGORY_ID' => $serviceCategoryId,
                    '=' . CrmHelper::CAR_UF_FIELD => $carId,
                ],
                'select' => [
                    'ID', 'TITLE', 'CLOSED', 'STAGE_SEMANTIC_ID', 'DATE_CREATE',
                    'ASSIGNED_BY_ID', 'OPPORTUNITY', 'CURRENCY_ID',
                ],
                'order' => ['DATE_CREATE' => 'DESC'],
            ])->fetchAll();

            $dealIds = array_map('intval', array_column($rows, 'ID'));
            $productsMap = $this->loadProductsForDeals($dealIds);

            $userIds = array_values(array_unique(array_map(
                'intval',
                array_column($rows, 'ASSIGNED_BY_ID')
            )));
            $usersMap = $this->loadUsers($userIds);

            $data = [];

            foreach ($rows as $row) {
                $dealId = (int) $row['ID'];
                $assignedId = (int) ($row['ASSIGNED_BY_ID'] ?? 0);

                $data[] = [
                    'ID' => $dealId,
                    'TITLE' => (string) ($row['TITLE'] ?? ''),
                    'CLOSED' => (string) ($row['CLOSED'] ?? ''),
                    'STAGE_SEMANTIC_ID' => (string) ($row['STAGE_SEMANTIC_ID'] ?? ''),
                    'DATE_CREATE' => $row['DATE_CREATE'] ?? null,
                    'ASSIGNED_BY_ID' => $assignedId,
                    'ASSIGNED_NAME' => $usersMap[$assignedId] ?? '',
                    'OPPORTUNITY' => (float) ($row['OPPORTUNITY'] ?? 0),
                    'CURRENCY_ID' => (string) ($row['CURRENCY_ID'] ?? 'RUB'),
                    'PRODUCTS' => $productsMap[$dealId] ?? [],
                ];
            }

            $result->setData($data);
        } catch (Throwable $e) {
            @file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/local/logs/otus_sc_debug.log',
                date('Y-m-d H:i:s') . ' DealHistoryService: ' . get_class($e) . ': ' . $e->getMessage() . "\n",
                FILE_APPEND | LOCK_EX
            );

            $result->addError(new Error('Не удалось загрузить историю обслуживания.'));
        }

        return $result;
    }

    /**
     * Позиции товаров по списку сделок (массовый запрос через CCrmProductRow).
     *
     * @param int[] $dealIds Массив ID сделок (из DealTable)
     *
     * @return array<int, array<array{NAME: string, QTY: float, PRICE: float, SUM: float, MEASURE: string}>>
     *         Карта dealId => массив позиций; для сделки без позиций ключ отсутствует
     */
    private function loadProductsForDeals(array $dealIds): array
    {
        $map = [];

        if (empty($dealIds)) {
            return $map;
        }

        $filter = [
            '@OWNER_ID' => $dealIds,
            '=OWNER_TYPE' => 'D',
        ];

        $db = \CCrmProductRow::GetList(
            ['ID' => 'ASC'],
            $filter,
            false,
            false,
            ['ID', 'OWNER_ID', 'PRODUCT_ID', 'PRODUCT_NAME', 'QUANTITY', 'PRICE', 'MEASURE_NAME']
        );

        while ($row = $db->Fetch()) {
            $dealId = (int) $row['OWNER_ID'];
            $qty = (float) $row['QUANTITY'];
            $price = (float) $row['PRICE'];

            $map[$dealId][] = [
                'NAME' => (string) ($row['PRODUCT_NAME'] ?? ('#' . (int) $row['PRODUCT_ID'])),
                'QTY' => $qty,
                'PRICE' => $price,
                'SUM' => $qty * $price,
                'MEASURE' => (string) ($row['MEASURE_NAME'] ?? ''),
            ];
        }

        return $map;
    }

    /**
     * Массовая загрузка пользователей по ID для отображения ответственных.
     *
     * @param int[] $userIds Массив ID пользователей (из ASSIGNED_BY_ID сделок)
     *
     * @return array<int, string> Карта userId => человекочитаемое имя ("Фамилия Имя" или LOGIN)
     */
    private function loadUsers(array $userIds): array
    {
        $map = [];

        if (empty($userIds)) {
            return $map;
        }

        $db = \CUser::GetList(
            ($by = 'ID'),
            ($order = 'ASC'),
            ['ID' => implode(' | ', $userIds)],
            ['FIELDS' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN']]
        );

        while ($u = $db->Fetch()) {
            $full = trim(($u['LAST_NAME'] ?? '') . ' ' . ($u['NAME'] ?? ''));
            $map[(int) $u['ID']] = $full !== '' ? $full : (string) ($u['LOGIN'] ?? '');
        }

        return $map;
    }
}