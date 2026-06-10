<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    "NAME" => Loc::getMessage("DADATA_FIND_COMPANY_DESCR_NAME") ?? "Поиск компании в DaData",
    "DESCRIPTION" => Loc::getMessage("DADATA_FIND_COMPANY_DESCR_DESCR") ?? "Поиск данных компании по ИНН",
    "TYPE" => ["activity", "robot_activity"],
    "CLASS" => "Dadatafindcompany",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => [
        "ID" => "other",
    ],
];