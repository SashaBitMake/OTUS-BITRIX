<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$MESS['DADATA_FIND_COMPANY_TOKEN'] = 'API-ключ DaData (Token)';
$MESS['DADATA_FIND_COMPANY_SECRET'] = 'Секретный ключ DaData (Secret)';
$MESS['DADATA_FIND_COMPANY_INN'] = 'ИНН для поиска';
$MESS['DADATA_FIND_COMPANY_ERROR_EMPTY_TOKEN'] = 'API-ключ DaData не указан в настройках действия.';
$MESS['DADATA_FIND_COMPANY_ERROR_EMPTY_INN'] = 'ИНН не передан для проверки.';
$MESS['DADATA_FIND_COMPANY_ERROR_NOT_FOUND'] = 'Организация с ИНН "#INN#" не найдена в DaData.';
$MESS['DADATA_FIND_COMPANY_ERROR_TOO_MANY_REQUESTS'] = 'Лимит запросов к DaData исчерпан (ошибка 429).';