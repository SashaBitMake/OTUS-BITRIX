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

// === REST otus.book.* ===
// Все классы лежат в /local/php_interface/src/Otus/... (namespace Otus\...)

Loader::registerAutoLoadClasses(null, [
    'Otus\Orm\BookTable' => '/local/php_interface/src/Otus/Orm/BookTable.php',
    'Otus\Service\BookService' => '/local/php_interface/src/Otus/Service/BookService.php',
    'Otus\Rest\Events' => '/local/php_interface/src/Otus/Rest/Events.php',
    'Otus\Rest\Logger' => '/local/php_interface/src/Otus/Rest/Logger.php',
]);

// Регистрация кастомных REST-методов otus.book.* и otus.getHttpInfo.
// init.php выполняется на каждом хите, поэтому обработчик регистрируется
// постоянно, а не только при открытии отдельной admin-страницы.
EventManager::getInstance()->addEventHandlerCompatible(
    'rest',
    'OnRestServiceBuildDescription',
    ['Otus\Rest\Events', 'OnRestServiceBuildDescriptionHandler']
);