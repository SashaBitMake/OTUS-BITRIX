<?php

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