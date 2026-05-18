<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

$postParams = $request->getPost('PARAMS');
$componentParams = $postParams['params'] ?? [];

if (empty($componentParams['ENTITY_ID'])) {
    $componentParams['ENTITY_ID'] = (int)$request->get('ENTITY_ID');
}

global $APPLICATION;
$APPLICATION->IncludeComponent(
    'otus:crm.custom.tab',
    '',
    $componentParams
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');