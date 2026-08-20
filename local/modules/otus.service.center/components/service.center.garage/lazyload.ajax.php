<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

global $USER;

if (!$USER->IsAuthorized() || !check_bitrix_sessid()) {
    die();
}

if (!Loader::includeModule('otus.service.center')) {
    die();
}

$request = Application::getInstance()->getContext()->getRequest();

$postParams = $request->getPost('PARAMS');
$componentParams = is_array($postParams) ? ($postParams['params'] ?? []) : [];

if (empty($componentParams['CONTACT_ID'])) {
    $componentParams['CONTACT_ID'] = (int) $request->get('CONTACT_ID');
}

global $APPLICATION;

Header('Content-Type: text/html; charset=' . LANG_CHARSET);
$APPLICATION->ShowAjaxHead();

$APPLICATION->IncludeComponent(
    'otus:service.center.garage',
    '',
    $componentParams
);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
die();