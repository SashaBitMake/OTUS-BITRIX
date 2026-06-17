<?php

use Bitrix\Main\EventManager;
use Local\Events\SynchronizationHandler;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementUpdate',
    [SynchronizationHandler::class, 'onApplicationUpdate']
);

$eventManager->addEventHandler(
    'crm',
    'OnAfterCrmDealUpdate',
    [SynchronizationHandler::class, 'onDealUpdate']
);
