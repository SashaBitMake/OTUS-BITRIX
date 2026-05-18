<?php
namespace Otus\TCrm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class Handler
{
    /**
     * Обработчик события инициализации вкладок в карточке CRM (onEntityDetailsTabsInitialized).
     *
     * @param Event $event Объект события ядра D7. Содержит параметры:
     *                     - 'entityID' (int): ID текущей сущности (например, ID сделки)
     *                     - 'tabs' (array): Массив уже зарегистрированных вкладок
     *
     * @return EventResult Возвращает результат обработки события с дополненным массивом 'tabs'
     */

    public static function onEntityDetailsTabsInitialized(Event $event)
    {
        $tabs = $event->getParameter('tabs') ?: [];
        $entityID = (int)$event->getParameter('entityID');
        $gridId = 'otus_custom_data_grid'; // Должен совпадать с ID в компоненте!

        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        if ($request->isAjaxRequest() && $request->get('grid_id') === $gridId) {
            global $APPLICATION;
            $APPLICATION->RestartBuffer();
            $APPLICATION->IncludeComponent(
                'otus:crm.custom.tab', 
                '', 
                ['ENTITY_ID' => $entityID]
            );
            require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
            die();
        }

        if ($entityID > 0) {
            $tabs[] = [
                'id' => 'otus_custom_grid_tab',
                'name' => 'Данные OTUS',
                'loader' => [
                    'serviceUrl' => '/local/components/otus/crm.custom.tab/lazyload.ajax.php?ENTITY_ID=' . $entityID,
                    'componentData' => [
                        'template' => '',
                        'params' => ['ENTITY_ID' => $entityID],
                    ],
                ],
            ];
        }

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs], 'crm');
    }
}