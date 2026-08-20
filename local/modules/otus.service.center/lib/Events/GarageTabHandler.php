<?php

declare(strict_types=1);

namespace Otus\Service\Center\Events;

use Bitrix\Crm\DealTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use CCrmOwnerType;
use Otus\Service\Center\Helpers\CrmHelper;
use Throwable;

Loc::loadMessages(__FILE__);

final class GarageTabHandler
{
    private const GRID_ID = 'otus_sc_garage_grid_v3';
    private const TAB_ID = 'otus_sc_garage_tab';
    private const LAZYLOAD_URL = '/local/components/otus/service.center.garage/lazyload.ajax.php';
    private const CONTACT_URI_PATTERN = '#^/crm/contact/#';
    private const DEAL_URI_PATTERN = '#^/crm/deal/#';
    private const DEAL_DETAILS_PATTERN = '#/crm/deal/details/(\d+)/#';

    /**
     * Ранний обработчик: ассеты для страниц контактов и сделок.
     */
    public static function onPageStart(): void
    {
        $uri = (string) Context::getCurrent()->getRequest()->getRequestedPage();

        if (preg_match(self::CONTACT_URI_PATTERN, $uri) === 1) {
            try {
                Extension::load('main.ui.grid');
            } catch (Throwable $e) {
                // Страховка не критична: вкладка отработает через контракт lazyload.
            }
        }

        if (preg_match(self::DEAL_URI_PATTERN, $uri) === 1) {
            try {
                Extension::load('otus.servicecenter');
                self::injectDealContext($uri);
            } catch (Throwable $e) {
                // Без контекста UX-секция просто не появится, CRM работает штатно.
            }
        }
    }

    /**
     * Инжект контекста для клиентского JS:
     * service — ID сервисной воронки (опция модуля),
     * category — категория текущей сделки (из URL создания или из БД).
     */
    private static function injectDealContext(string $uri): void
    {
        $serviceCategoryId = (int) Option::get(
            CrmHelper::MODULE_ID,
            CrmHelper::OPTION_SERVICE_CATEGORY_ID,
            '0'
        );

        $categoryId = (int) (Context::getCurrent()->getRequest()->get('category_id') ?? 0);

        if ($categoryId <= 0 && preg_match(self::DEAL_DETAILS_PATTERN, $uri, $matches) === 1) {
            if (Loader::includeModule('crm')) {
                $deal = DealTable::getList([
                    'filter' => ['=ID' => (int) $matches[1]],
                    'select' => ['CATEGORY_ID'],
                ])->fetch();

                $categoryId = (int) ($deal['CATEGORY_ID'] ?? 0);
            }
        }

        Asset::getInstance()->addString(
            '<script>window.OTUS_SC_CTX={service:' . $serviceCategoryId . ',category:' . $categoryId . '};</script>'
        );
    }

    /**
     * Основной обработчик события onEntityDetailsTabsInitialized.
     */
    public static function onEntityDetailsTabsInitialized(Event $event): EventResult
    {
        $tabs = $event->getParameter('tabs') ?: [];

        try {
            $entityId = (int) $event->getParameter('entityID');

            $request = Context::getCurrent()->getRequest();

            // 1. Перехват AJAX-запросов грида внутри вкладки.
            if ($request->isAjaxRequest() && (string) $request->get('grid_id') === self::GRID_ID) {
                self::renderComponent($entityId);
            }

            // 2. Вкладка — только для контакта.
            if ($entityId <= 0 || !self::isContactContext($event)) {
                return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs], 'crm');
            }

            // 3. Добавляем вкладку с ленивой загрузкой.
            $tabs[] = [
                'id' => self::TAB_ID,
                'name' => Loc::getMessage('OTUS_SC_TAB_GARAGE_NAME'),
                'loader' => [
                    'serviceUrl' => self::LAZYLOAD_URL
                        . '?CONTACT_ID=' . $entityId
                        . '&sessid=' . bitrix_sessid(),
                    'componentData' => [
                        'template' => '',
                        'params' => ['CONTACT_ID' => $entityId],
                    ],
                ],
            ];

            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs], 'crm');
        } catch (Throwable $e) {
            // Обработчик не должен ронять карточку CRM ни при каких условиях.
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs], 'crm');
        }
    }

    /**
     * Определяет, что текущая сущность — контакт.
     */
    private static function isContactContext(Event $event): bool
    {
        $raw = $event->getParameter('entityTypeID');

        if ($raw !== null) {
            if (is_numeric($raw)) {
                return (int) $raw === CCrmOwnerType::Contact;
            }

            return CCrmOwnerType::ResolveID((string) $raw) === CCrmOwnerType::Contact;
        }

        $uri = (string) Context::getCurrent()->getRequest()->getRequestedPage();

        return preg_match(self::CONTACT_URI_PATTERN, $uri) === 1;
    }

    /**
     * Рендерит компонент гаража в текущий поток и завершает скрипт.
     */
    private static function renderComponent(int $contactId): void
    {
        global $APPLICATION;

        $APPLICATION->RestartBuffer();

        $APPLICATION->IncludeComponent(
            'otus:service.center.garage',
            '',
            ['CONTACT_ID' => $contactId]
        );

        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
        die();
    }
}