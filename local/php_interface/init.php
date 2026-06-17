<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Loader;

//класс для DaData
include_once __DIR__ .'/src/Otus/Dadata.php';

// composer
if (file_exists(__DIR__ . '/../../vendor/autoload.php'))
{
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

// App
if (file_exists(__DIR__ . '/../App/autoload.php'))
{
    require_once(__DIR__ . '/../App/autoload.php');
}

// Регистрация кастомного типа свойства для инфоблока
if (\Bitrix\Main\Loader::includeModule('iblock')) {
    \Bitrix\Main\EventManager::getInstance()->addEventHandler(
        'iblock',
        'OnIBlockPropertyBuildList',
        ['\App\Iblock\Properties\DoctorBookingProperty', 'GetUserTypeDescription']
    );
}

// Подключение кастомных событий
if (file_exists(__DIR__ . '/src/Otus/timeWindow.php')) {
    require_once(__DIR__ . '/src/Otus/timeWindow.php');
}

// Автозагрузка для обработчиков событий из local/events/
Loader::registerAutoLoadClasses(null, [
    'Local\Events\SynchronizationHandler' => '/local/events/SynchronizationHandler.php',
]);

// Подключение кастомных событий ДЗ
if (file_exists(__DIR__ . '/src/Otus/events_extra.php')) {
    require_once(__DIR__ . '/src/Otus/events_extra.php');
}
