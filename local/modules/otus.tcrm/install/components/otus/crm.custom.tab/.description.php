<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    'NAME' => 'Вкладка с данными OTUS', 
    'DESCRIPTION' => 'Выводит кастомный грид с данными для карточки CRM (Сделки, Лиды и т.д.)',
    'PATH' => [
        'ID' => 'otus',
        'CHILD' => [
            'ID' => 'crm_custom',
            'NAME' => 'Доработки CRM',
            'SORT' => 10,
        ]
    ],
];