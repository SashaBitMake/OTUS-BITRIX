<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

header('Content-Type: text/html; charset=' . LANG_CHARSET);

global $USER;

if (!$USER->IsAuthorized() || !check_bitrix_sessid()) {
    echo '<div style="padding:10px;color:#a94442;">Нет доступа.</div>';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    die();
}

$carId = (int) ($_REQUEST['car_id'] ?? 0);

try {
    if (!Loader::includeModule('otus.service.center')) {
        throw new \RuntimeException('Модуль otus.service.center не установлен');
    }

    global $APPLICATION;

    ob_start();
    $APPLICATION->IncludeComponent(
        'otus:service.center.garage.history',
        '',
        ['CAR_ID' => $carId]
    );
    echo (string) ob_get_clean();
} catch (\Throwable $e) {
    echo '<div style="padding:10px;color:#a94442;">Ошибка: ' . htmlspecialcharsbx($e->getMessage()) . '</div>';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
die();
