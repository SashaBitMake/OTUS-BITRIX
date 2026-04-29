<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency\CurrencyTable;

/**
 * Компонент для выбора валюты и отображения её курса
 */
class CurrencyRateComponent extends CBitrixComponent
{
    /**
     * Подготавливает параметры компонента перед вызовом executeComponent().
     *
     * @param array $arParams Входящие параметры компонента
     * @return array Подготовленные параметры
     */
    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    /**
     * Возвращает список доступных валют в формате [код => код].
     *
     * @return array<string, string> Ассоциативный массив кодов валют
     */
    private function getCurrencyList()
    {
        $currencies = [];
        $dbRes = CurrencyTable::getList([
            'select' => ['CURRENCY', 'BASE'],
            'order' => ['SORT' => 'ASC']
        ]);

        while ($currency = $dbRes->fetch()) {
            $currencies[$currency['CURRENCY']] = $currency['CURRENCY'];
        }

        return $currencies;
    }

    /**
     * Получает курс указанной валюты относительно базовой.
     *
     * @param string $currencyCode Код валюты (например, USD)
     * @return float|null Значение курса или null, если валюта не найдена
     */
    private function getCurrencyRate($currencyCode)
    {
        $currencyData = CurrencyTable::getList([
            'select' => ['AMOUNT', 'AMOUNT_CNT'],
            'filter' => ['CURRENCY' => $currencyCode]
        ])->fetch();

        return $currencyData ? (float) $currencyData['AMOUNT'] : null;
    }

    /**
     * Основной метод компонента: определяет выбранную валюту, получает курс
     * и подготавливает данные для шаблона.
     *
     * @return void
     */
    public function executeComponent()
    {
        $this->arResult['CURRENCY_LIST'] = $this->getCurrencyList();

        $selectedCurrency = $_GET['currency'] ?? $this->arParams['CURRENCY_ID'] ?? array_key_first($this->arResult['CURRENCY_LIST']);

        $this->arResult['SELECTED_CURRENCY'] = $selectedCurrency;
        $this->arResult['CURRENT_RATE'] = $this->getCurrencyRate($selectedCurrency);
        $this->arResult['CURRENT_URL'] = Bitrix\Main\Context::getCurrent()->getServer()->getRequestUri();

        $this->includeComponentTemplate();
    }
}