<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'CAR_ID' => [
            'NAME' => Loc::getMessage('OTUS_SC_HISTORY_PARAM_CAR_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
            'PARENT' => 'BASE',
        ],
    ],
];