<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Otus\Service\Center\Services\DealHistoryService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class ServiceCenterGarageHistory extends CBitrixComponent
{
    /**
     * Основной метод компонента: подготовка данных для шаблона истории обслуживания.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        $this->arResult['ROWS'] = [];
        $this->arResult['ERROR'] = '';

        $carId = (int) ($this->arParams['CAR_ID'] ?? 0);

        if ($carId <= 0) {
            $this->arResult['ERROR'] = 'Не указан автомобиль.';
            $this->includeComponentTemplate();

            return;
        }

        if (!Loader::includeModule('otus.service.center')) {
            $this->arResult['ERROR'] = 'Модуль сервисного центра не установлен.';
            $this->includeComponentTemplate();

            return;
        }

        $result = (new DealHistoryService())->getHistoryByCar($carId);

        if (!$result->isSuccess()) {
            $this->arResult['ERROR'] = implode(' ', $result->getErrorMessages());
        } else {
            $this->arResult['ROWS'] = $result->getData();
        }

        $this->includeComponentTemplate();
    }
}