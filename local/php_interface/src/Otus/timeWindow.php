<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

AddEventHandler(
    'main',
    'onEpilog',
    'injectOtusTimemanExtension'
);

/**
 * Функция-обработчик.
 */
function injectOtusTimemanExtension()
{
    if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
        return;
    }
    
    if (class_exists('\Bitrix\Main\UI\Extension')) {
        try {
            \Bitrix\Main\UI\Extension::load('otus.timeman');
        } catch (\Exception $e) {
            AddMessage2Log("Ошибка загрузки расширения otus.timeman: " . $e->getMessage());
        }
    }
}