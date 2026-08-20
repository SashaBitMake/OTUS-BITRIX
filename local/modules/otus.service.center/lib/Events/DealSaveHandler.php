<?php

declare(strict_types=1);

namespace Otus\Service\Center\Events;

use Bitrix\Crm\DealTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Otus\Service\Center\Helpers\CrmHelper;
use Otus\Service\Center\ORM\GarageTable;
use Throwable;

Loc::loadMessages(__FILE__);

final class DealSaveHandler
{
    /**
     * OnBeforeCrmDealAdd. Ссылка &$arg — чтобы править поля легаси-события.
     *
     * @param Event|array|null $arg
     *
     * @return EventResult|bool|null
     */
    public static function onBeforeDealAdd(&$arg = null)
    {
        return self::validate($arg);
    }

    /**
     * OnBeforeCrmDealUpdate. Ссылка &$arg — чтобы править поля легаси-события.
     *
     * @param Event|array|null $arg
     *
     * @return EventResult|bool|null
     */
    public static function onBeforeDealUpdate(&$arg = null)
    {
        return self::validate($arg);
    }

    /**
     * OnAfterCrmDealAdd: авто-название сделки.
     *
     * @param Event|array|null $arg
     *
     * @return void
     */
    public static function onAfterDealAdd($arg = null): void
    {
        self::applyAutoTitle($arg);
    }

    /**
     * OnAfterCrmDealUpdate: авто-название сделки.
     *
     * @param Event|array|null $arg
     *
     * @return void
     */
    public static function onAfterDealUpdate($arg = null): void
    {
        self::applyAutoTitle($arg);
    }

    /**
     * Поиск открытой сделки-дубля по автомобилю.
     *
     * @param int $carId         ID автомобиля
     * @param int $excludeDealId ID исключаемой сделки (для Update)
     *
     * @return array{id: int, title: string}|null
     */
    public static function findDuplicate(int $carId, int $excludeDealId = 0): ?array
    {
        $serviceCategoryId = (int) Option::get(
            CrmHelper::MODULE_ID,
            CrmHelper::OPTION_SERVICE_CATEGORY_ID,
            '0'
        );

        if ($serviceCategoryId <= 0 || $carId <= 0) {
            return null;
        }

        $filter = [
            '=CATEGORY_ID' => $serviceCategoryId,
            '=' . CrmHelper::CAR_UF_FIELD => $carId,
            '=CLOSED' => 'N',
        ];

        if ($excludeDealId > 0) {
            $filter['!=ID'] = $excludeDealId;
        }

        $duplicate = DealTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'TITLE'],
        ])->fetch();

        if ($duplicate === false) {
            return null;
        }

        return ['id' => (int) $duplicate['ID'], 'title' => (string) $duplicate['TITLE']];
    }

    /**
     * Валидация: обязательные поля + запрет дубля + дозапись из presave.
     *
     * @param Event|array|null $arg
     *
     * @return EventResult|bool|null
     */
    private static function validate(&$arg)
    {
        try {
            if ($arg instanceof Event) {
                return self::validateViaEvent($arg);
            }

            $fields = is_array($arg) ? $arg : [];
            self::applyPresave($fields);

            if (is_array($arg)) {
                $arg = $fields;
            }

            return self::validateViaLegacy($fields);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Легаси-путь: отмена + ThrowException.
     *
     * @param array $fields Поля сделки
     *
     * @return bool true если валидация пройдена
     */
    private static function validateViaLegacy(array $fields): bool
    {
        $error = self::computeError($fields, (int) ($fields['ID'] ?? 0));

        if ($error === '') {
            return true;
        }

        global $APPLICATION;
        $APPLICATION->ThrowException(new \Exception($error));

        return false;
    }

    /**
     * Новый путь: ошибка в EventResult.
     *
     * @param Event $event Событие CRM
     *
     * @return EventResult|null null если ошибок нет
     */
    private static function validateViaEvent(Event $event): ?EventResult
    {
        $parameters = $event->getParameters();
        $fields = [];

        if (is_array($parameters) && isset($parameters['arFields']) && is_array($parameters['arFields'])) {
            $fields = $parameters['arFields'];
        } elseif (is_array($parameters) && isset($parameters[0]) && is_array($parameters[0])) {
            $fields = $parameters[0];
        }

        $error = self::computeError($fields, (int) ($fields['ID'] ?? 0));

        if ($error === '') {
            return null;
        }

        $result = new EventResult(EventResult::ERROR, null, CrmHelper::MODULE_ID);
        $result->addError(new Error($error));

        return $result;
    }

    /**
     * Общая бизнес-логика: текст ошибки или пустая строка.
     *
     * @param array $fields Поля сделки
     * @param int   $dealId ID сделки (0 для новой)
     *
     * @return string Текст ошибки или ''
     */
    private static function computeError(array $fields, int $dealId): string
    {
        $serviceCategoryId = (int) Option::get(
            CrmHelper::MODULE_ID,
            CrmHelper::OPTION_SERVICE_CATEGORY_ID,
            '0'
        );

        if ($serviceCategoryId <= 0) {
            return '';
        }

        $existing = self::loadExisting($dealId);
        $categoryId = (int) ($fields['CATEGORY_ID'] ?? ($existing['CATEGORY_ID'] ?? 0));

        if ($categoryId !== $serviceCategoryId) {
            return '';
        }

        $closed = (string) ($fields['CLOSED'] ?? ($existing['CLOSED'] ?? 'N'));

        if ($closed === 'Y') {
            return '';
        }

        $contactId = (int) ($fields['CONTACT_ID'] ?? ($existing['CONTACT_ID'] ?? 0));
        $carId = (int) ($fields[CrmHelper::CAR_UF_FIELD] ?? ($existing[CrmHelper::CAR_UF_FIELD] ?? 0));

        if ($contactId <= 0 || $carId <= 0) {
            return Loc::getMessage('OTUS_SC_DEAL_REQUIRED');
        }

        $duplicate = self::findDuplicate($carId, $dealId);

        if ($duplicate !== null) {
            return Loc::getMessage('OTUS_SC_DEAL_DUPLICATE', [
                '#DEAL_ID#' => (string) $duplicate['id'],
                '#TITLE#' => $duplicate['title'],
            ]);
        }

        return '';
    }

    /**
     * Дозапись контакта/авто из сессии (deal_presave от JS).
     *
     * CRM-форма не передаёт программно установленный UF-инпут,
     * поэтому JS шлёт presave перед сабмитом, а сервер дозаписывает
     * значения в $arFields до валидации.
     *
     * @param array &$fields Поля сделки (ссылка для модификации)
     *
     * @return void
     */
    private static function applyPresave(array &$fields): void
    {
        if (!isset($_SESSION['OTUS_SC_PRESAVE']) || !is_array($_SESSION['OTUS_SC_PRESAVE'])) {
            return;
        }

        $ps = $_SESSION['OTUS_SC_PRESAVE'];
        unset($_SESSION['OTUS_SC_PRESAVE']);

        if (time() - (int) ($ps['ts'] ?? 0) > 120) {
            return;
        }

        $serviceCategoryId = (int) Option::get(
            CrmHelper::MODULE_ID,
            CrmHelper::OPTION_SERVICE_CATEGORY_ID,
            '0'
        );

        if ($serviceCategoryId <= 0) {
            return;
        }

        $categoryId = (int) ($fields['CATEGORY_ID'] ?? 0);

        if ($categoryId <= 0 && (int) ($fields['ID'] ?? 0) > 0) {
            $existing = self::loadExisting((int) $fields['ID']);
            $categoryId = (int) ($existing['CATEGORY_ID'] ?? 0);
        }

        if ($categoryId !== $serviceCategoryId) {
            return;
        }

        if ((int) ($ps['car_id'] ?? 0) > 0) {
            $fields[CrmHelper::CAR_UF_FIELD] = (int) $ps['car_id'];
        }

        if ((int) ($ps['contact_id'] ?? 0) > 0) {
            $fields['CONTACT_ID'] = (int) $ps['contact_id'];
        }
    }

    /**
     * Авто-название сделки после сохранения: "Марка Модель, Гос. номер".
     *
     * @param Event|array|null $arg
     *
     * @return void
     */
    private static function applyAutoTitle($arg): void
    {
        try {
            $fields = is_array($arg) ? $arg : [];
            $dealId = (int) ($fields['ID'] ?? 0);

            if ($dealId <= 0) {
                return;
            }

            $existing = self::loadExisting($dealId);

            if ($existing === null) {
                return;
            }

            $serviceCategoryId = (int) Option::get(
                CrmHelper::MODULE_ID,
                CrmHelper::OPTION_SERVICE_CATEGORY_ID,
                '0'
            );
            $categoryId = (int) ($fields['CATEGORY_ID'] ?? ($existing['CATEGORY_ID'] ?? 0));

            if ($serviceCategoryId <= 0 || $categoryId !== $serviceCategoryId) {
                return;
            }

            $carId = (int) ($fields[CrmHelper::CAR_UF_FIELD] ?? ($existing[CrmHelper::CAR_UF_FIELD] ?? 0));

            if ($carId <= 0) {
                return;
            }

            $label = self::getCarLabel($carId);

            if ($label === null) {
                return;
            }

            $current = DealTable::getList([
                'filter' => ['=ID' => $dealId],
                'select' => ['TITLE'],
            ])->fetch();

            if ($current === false || (string) $current['TITLE'] === $label) {
                return;
            }

            DealTable::update($dealId, ['TITLE' => $label]);
        } catch (Throwable $e) {
        }
    }

    /**
     * Текущая запись сделки из БД.
     *
     * @param int $dealId ID сделки
     *
     * @return array|null Запись сделки или null если не найдена
     */
    private static function loadExisting(int $dealId): ?array
    {
        if ($dealId <= 0) {
            return null;
        }

        $existing = DealTable::getList([
            'filter' => ['=ID' => $dealId],
            'select' => ['ID', 'CATEGORY_ID', 'CLOSED', 'CONTACT_ID', CrmHelper::CAR_UF_FIELD],
        ])->fetch();

        return $existing === false ? null : $existing;
    }

    /**
     * Человекочитаемая метка автомобиля (без хвоста ", " при пустом номере).
     *
     * @param int $carId ID автомобиля
     *
     * @return string|null Метка вида "Марка Модель, Номер" или null
     */
    private static function getCarLabel(int $carId): ?string
    {
        if ($carId <= 0) {
            return null;
        }

        $car = GarageTable::getList([
            'filter' => ['=ID' => $carId],
            'select' => ['BRAND', 'MODEL', 'NUMBER'],
        ])->fetch();

        if ($car === false) {
            return null;
        }

        $label = trim($car['BRAND'] . ' ' . $car['MODEL']);

        if (trim((string) $car['NUMBER']) !== '') {
            $label .= ', ' . trim((string) $car['NUMBER']);
        }

        return $label;
    }
}