<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Grid\Options;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Otus\Service\Center\ORM\GarageTable;
use Otus\Service\Center\Services\GarageService;

Loc::loadMessages(__FILE__);

class OtusServiceCenterGarageComponent extends CBitrixComponent
{
    /**
     * ID грида main.ui.grid (используется для сохранения пользовательских
     * настроек: сортировки, видимых колонок, фильтров).
     *
     * @var string
     */
    protected $gridId = 'otus_sc_garage_grid_v3';

    /**
     * Основной метод компонента: подготовка данных для шаблона.
     *
     * @return void Выводит ошибку через ShowError при отсутствии модулей
     *              или ошибке выборки; затем вызывает includeComponentTemplate()
     */
    public function executeComponent()
    {
        if (!Loader::includeModule('crm') || !Loader::includeModule('otus.service.center')) {
            ShowError(Loc::getMessage('OTUS_SC_GARAGE_CMP_MODULES_MISSING'));

            return;
        }

        $contactId = (int) ($this->arParams['CONTACT_ID'] ?? 0);

        $gridOptions = new Options($this->gridId);
        $sortData = $gridOptions->GetSorting([
            'sort' => [GarageTable::FIELD_ID => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);

        $service = new GarageService();
        $result = $service->getCarsByContact($contactId, $sortData['sort']);

        $this->arResult['GRID_ID'] = $this->gridId;
        $this->arResult['CONTACT_ID'] = $contactId;
        $this->arResult['SORT'] = $sortData['sort'];
        $this->arResult['SORT_VARS'] = $sortData['vars'];
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['ROWS'] = [];
        $this->arResult['ERRORS'] = [];
        $this->arResult['CARS_DATA'] = []; // Для редактирования.

        if ($result->isSuccess()) {
            $this->arResult['ROWS'] = $this->buildRows($result->getData(), $this->getContactName($contactId));
        } else {
            $this->arResult['ERRORS'] = $result->getErrorMessages();
        }

        $this->includeComponentTemplate();
    }

    /**
     * Получение имени контакта для формирования заголовка попапа истории.
     *
     * @param int $contactId ID контакта CRM (CONTACT_ID из таблицы otus_sc_garage)
     *
     * @return string Имя контакта ("Фамилия Имя") или пустая строка
     */
    protected function getContactName(int $contactId): string
    {
        if ($contactId <= 0) {
            return '';
        }

        $contact = \CCrmContact::GetByID($contactId);

        if (!is_array($contact)) {
            return '';
        }

        return trim(($contact['LAST_NAME'] ?? '') . ' ' . ($contact['NAME'] ?? ''));
    }

    /**
     * Формирование массива колонок для main.ui.grid.
     *
     * @return array<int, array<string, mixed>> Массив описаний колонок грида
     *
     * @see \Bitrix\Main\Grid\Options::GetColumns()
     */
    protected function getColumns(): array
    {
        return [
            ['id' => GarageTable::FIELD_ID, 'name' => Loc::getMessage('OTUS_SC_GARAGE_COL_ID'), 'sort' => GarageTable::FIELD_ID, 'default' => true],
            ['id' => 'CAR', 'name' => Loc::getMessage('OTUS_SC_GARAGE_COL_CAR'), 'default' => true],
            ['id' => GarageTable::FIELD_NUMBER, 'name' => Loc::getMessage('OTUS_SC_GARAGE_COL_NUMBER'), 'sort' => GarageTable::FIELD_NUMBER, 'default' => true],
            ['id' => GarageTable::FIELD_YEAR, 'name' => Loc::getMessage('OTUS_SC_GARAGE_COL_YEAR'), 'sort' => GarageTable::FIELD_YEAR, 'default' => true],
            ['id' => GarageTable::FIELD_COLOR, 'name' => Loc::getMessage('OTUS_SC_GARAGE_COL_COLOR'), 'sort' => GarageTable::FIELD_COLOR, 'default' => true],
            ['id' => GarageTable::FIELD_MILEAGE, 'name' => Loc::getMessage('OTUS_SC_GARAGE_COL_MILEAGE'), 'sort' => GarageTable::FIELD_MILEAGE, 'default' => true],
            ['id' => 'ACTIONS', 'name' => '', 'default' => true, 'width' => 60],
        ];
    }

    /**
     * Формирование массива строк грида из списка автомобилей.
     *
     * @param array<int, array<string, mixed>> $cars        Массив автомобилей из GarageService::getCarsByContact()
     * @param string                           $contactName Имя контакта для заголовка попапа истории
     *
     * @return array<int, array<string, mixed>> Массив строк для main.ui.grid (ключи: id, columns)
     *
     * @see \Bitrix\Main\Grid\Options::GetRows()
     */
    protected function buildRows(array $cars, string $contactName): array
    {
        $rows = [];

        foreach ($cars as $car) {
            $carId = (int) $car['ID'];
            $carTitle = htmlspecialcharsbx($car['BRAND'] . ' ' . $car['MODEL']);

            $popupTitle = $carTitle . ' - ' . htmlspecialcharsbx((string) $car['NUMBER']);

            if ($contactName !== '') {
                $popupTitle .= ' (' . htmlspecialcharsbx($contactName) . ')';
            }

            $this->arResult['CARS_DATA'][$carId] = [
                'ID' => $carId,
                'BRAND' => $car['BRAND'],
                'MODEL' => $car['MODEL'],
                'NUMBER' => $car['NUMBER'],
                'YEAR' => $car['YEAR'],
                'COLOR' => $car['COLOR'] ?? '',
                'MILEAGE' => $car['MILEAGE'] ?? 0,
            ];

            $rows[] = [
                'id' => $carId,
                'columns' => [
                    'ID' => $carId,
                    'CAR' => '<a href="#" class="otus-sc-history-link" '
                        . 'data-car-id="' . $carId . '" '
                        . 'data-car-title="' . $popupTitle . '">'
                        . $carTitle
                        . '</a>',
                    'NUMBER' => htmlspecialcharsbx((string) $car['NUMBER']),
                    'YEAR' => (int) $car['YEAR'],
                    'COLOR' => htmlspecialcharsbx((string) ($car['COLOR'] ?? '')),
                    'MILEAGE' => (int) ($car['MILEAGE'] ?? 0),
                    'ACTIONS' => '<span style="white-space:nowrap;">'
                        . '<a href="javascript:void(0)" onclick="otusScEditCar(' . $carId . ')" '
                        . 'style="color:#006cc0;text-decoration:none;">'
                        . Loc::getMessage('OTUS_SC_GARAGE_ACTION_EDIT') . '</a>'
                        . '<span style="color:#ccd2d9;">&nbsp;|&nbsp;</span>'
                        . '<a href="javascript:void(0)" onclick="otusScDeleteCar(' . $carId . ')" '
                        . 'style="color:#a94442;text-decoration:none;">'
                        . Loc::getMessage('OTUS_SC_GARAGE_ACTION_DELETE') . '</a>'
                        . '</span>',
                ],
            ];
        }

        return $rows;
    }
}