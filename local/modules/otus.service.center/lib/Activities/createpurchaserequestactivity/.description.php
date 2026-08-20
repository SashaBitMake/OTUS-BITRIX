<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return [
    'NAME' => Loc::getMessage('OTUS_SC_BP_ACTIVITY_NAME'),
    'DESCRIPTION' => Loc::getMessage('OTUS_SC_BP_ACTIVITY_DESC'),
    'SORT' => 100,
    'PROPERTIES' => [
        'RequestId' => [
            'Name' => Loc::getMessage('OTUS_SC_BP_PROPERTY_REQUEST'),
            'Desc' => '',
            'Type' => 'int',
            'Default' => '0',
            'Required' => true,
        ],
        'AutoApprove' => [
            'Name' => Loc::getMessage('OTUS_SC_BP_PROPERTY_AUTO'),
            'Desc' => '',
            'Type' => 'bool',
            'Default' => 'N',
            'Required' => false,
        ],
    ],
];