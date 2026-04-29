<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency\CurrencyTable;

if (!Loader::includeModule('currency')) {
    return;
}

$arCurrencyList = [];
$dbRes = CurrencyTable::getList([
    'select' => ['CURRENCY'],
    'order' => ['SORT' => 'ASC']
]);

while ($currency = $dbRes->fetch()) {
    $arCurrencyList[$currency['CURRENCY']] = $currency['CURRENCY'];
}

$arComponentParameters = array(
    "GROUPS" => array(
		"LIST"=>array(
			"NAME"=>"Источник данных",
			"SORT"=>"300"
		)
    ),
    "PARAMETERS" => array(
		"CURRENCY_ID" =>  array(
			"PARENT" => "DATA_SOURCE",
            "NAME" => "Валюта",
            "TYPE" => "LIST",
            "VALUES" => $arCurrencyList,
            "MULTIPLE" => "N",
            "DEFAULT"=>"N"
		)
	)
);
