<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    'GROUPS' => [
    ],
    'PARAMETERS' => [
        'ENTITY_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'ID сущности CRM (Сделка/Лид и т.д.)',
            'TYPE' => 'STRING',
            'DEFAULT' => '={$_REQUEST["ENTITY_ID"]}',
        ],
    ],
];