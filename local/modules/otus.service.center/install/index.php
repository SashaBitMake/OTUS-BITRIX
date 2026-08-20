<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Otus\Service\Center\Helpers\CrmHelper;
use Otus\Service\Center\Services\CrmSetupService;
use Otus\Service\Center\Services\PartsSetupService;
use Otus\Service\Center\Services\RoleSetupService;

Loc::loadMessages(__FILE__);

require_once dirname(__DIR__) . '/lib/Helpers/CrmHelper.php';
require_once dirname(__DIR__) . '/lib/Services/CrmSetupService.php';
require_once dirname(__DIR__) . '/lib/Services/PartsSetupService.php';
require_once dirname(__DIR__) . '/lib/Services/RoleSetupService.php';

class otus_service_center extends CModule
{
    public $MODULE_ID = 'otus.service.center';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    private const COMPONENT_NAMESPACE = 'otus';
    private const GARAGE_TABLE = 'otus_sc_garage';
    private const PURCHASE_ITEMS_TABLE = 'otus_sc_purchase_request_items';
    private const PURCHASE_REQUEST_TABLE = 'otus_sc_purchase_request';

    /**
     * Карта регистрируемых событий модуля. Единая точка правды.
     */
    private const EVENTS = [
        [
            'moduleId' => 'crm',
            'eventType' => 'onEntityDetailsTabsInitialized',
            'handlerClass' => '\\Otus\\Service\\Center\\Events\\GarageTabHandler',
            'handlerMethod' => 'onEntityDetailsTabsInitialized',
        ],
        [
            'moduleId' => 'main',
            'eventType' => 'OnPageStart',
            'handlerClass' => '\\Otus\\Service\\Center\\Events\\GarageTabHandler',
            'handlerMethod' => 'onPageStart',
        ],
        [
            'moduleId' => 'crm',
            'eventType' => 'OnBeforeCrmDealAdd',
            'handlerClass' => '\\Otus\\Service\\Center\\Events\\DealSaveHandler',
            'handlerMethod' => 'onBeforeDealAdd',
        ],
        [
            'moduleId' => 'crm',
            'eventType' => 'OnBeforeCrmDealUpdate',
            'handlerClass' => '\\Otus\\Service\\Center\\Events\\DealSaveHandler',
            'handlerMethod' => 'onBeforeDealUpdate',
        ],
        [
            'moduleId' => 'crm',
            'eventType' => 'OnAfterCrmDealAdd',
            'handlerClass' => '\\Otus\\Service\\Center\\Events\\DealSaveHandler',
            'handlerMethod' => 'onAfterDealAdd',
        ],
        [
            'moduleId' => 'crm',
            'eventType' => 'OnAfterCrmDealUpdate',
            'handlerClass' => '\\Otus\\Service\\Center\\Events\\DealSaveHandler',
            'handlerMethod' => 'onAfterDealUpdate',
        ],
    ];

    /**
     * Конструктор: загружает версию модуля и локализацию из version.php / lang-файлов.
     *
     * @return void
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '2026-08-04';

        $this->MODULE_NAME = Loc::getMessage('OTUS_SC_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('OTUS_SC_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('OTUS_SC_PARTNER_NAME');
        $this->PARTNER_URI = 'https://otus.ru';
    }

    /**
     * Установка модуля: БД → файлы → CRM-инфраструктура → регистрация → события.
     *
     * @return void
     */
    public function DoInstall(): void
    {
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallCrm();

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallEvents();
    }

    /**
     * Удаление модуля: события → дерегистрация → файлы → БД.
     * CRM-данные (воронка, стадии, UF) сознательно не удаляем.
     *
     * @return void
     */
    public function DoUninstall(): void
    {
        $this->UnInstallEvents();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $this->UnInstallFiles();
        $this->UnInstallDB();
    }

    /**
     * Настройка CRM-инфраструктуры: UF-поле у сделок, очистка UF товаров,
     * группы/пользователи/роли, highload-блок заявок для бизнес-процессов.
     *
     * @return void
     *
     * @throws SystemException При недоступности CRM или ошибке любого setup-сервиса
     */
    public function InstallCrm(): void
    {
        if (!Loader::includeModule('crm')) {
            throw new SystemException('CRM module is not available');
        }

        $services = [
            new CrmSetupService(),
            new PartsSetupService(),
            new RoleSetupService(),
        ];

        foreach ($services as $service) {
            $result = $service->setup();

            if (!$result->isSuccess()) {
                throw new SystemException(implode('; ', $result->getErrorMessages()));
            }
        }

        if (Loader::includeModule('highloadblock')) {
            $exists = \Bitrix\HighloadBlock\HighloadBlockTable::getList([
                'filter' => ['=TABLE_NAME' => self::PURCHASE_REQUEST_TABLE],
            ])->fetch();

            if ($exists === false) {
                $add = \Bitrix\HighloadBlock\HighloadBlockTable::add([
                    'NAME' => 'OtusScPurchaseRequests',
                    'TABLE_NAME' => self::PURCHASE_REQUEST_TABLE,
                ]);

                if ($add->isSuccess()) {
                    Option::set(
                        $this->MODULE_ID,
                        CrmHelper::OPTION_PURCHASE_HL_ID,
                        (string) $add->getId()
                    );
                }
            }
        }
    }

    /**
     * Создание таблиц БД из install.sql + пост-проверка их существования.
     *
     * @return bool true при успешном создании всех таблиц
     *
     * @throws SystemException Если хотя бы одна таблица не создалась
     */
    public function InstallDB(): bool
    {
        global $DB;

        $DB->RunSQLBatch($this->getSqlPath('install.sql'));

        foreach ([
            self::GARAGE_TABLE,
            self::PURCHASE_ITEMS_TABLE,
            self::PURCHASE_REQUEST_TABLE,
        ] as $table) {
            if (!$this->isTableExists($table)) {
                throw new SystemException(
                    'Table ' . $table . ' was not created. Check install/db/mysql/install.sql.'
                );
            }
        }

        return true;
    }

    /**
     * Удаление таблиц БД из uninstall.sql + удаление highload-блока заявок.
     *
     * @return bool true при успешном удалении
     */
    public function UnInstallDB(): bool
    {
        if (Loader::includeModule('highloadblock')) {
            $hl = \Bitrix\HighloadBlock\HighloadBlockTable::getList([
                'filter' => ['=TABLE_NAME' => self::PURCHASE_REQUEST_TABLE],
            ])->fetch();

            if ($hl !== false) {
                \Bitrix\HighloadBlock\HighloadBlockTable::delete((int) $hl['ID']);
            }
        }

        global $DB;

        $DB->RunSQLBatch($this->getSqlPath('uninstall.sql'));

        return true;
    }

    /**
     * Копирование компонентов, JS-расширения, activity, рабочих страниц
     * и создание папки логов. Файлы портала (меню и т.п.) НЕ изменяются.
     *
     * @return bool true при успешном копировании
     *
     * @throws SystemException Если исходная папка компонентов не найдена
     */
    public function InstallFiles(): bool
    {
        $documentRoot = Application::getDocumentRoot();
        $moduleRoot = dirname(__DIR__);

        $logsDir = $documentRoot . '/local/logs';

        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $source = $moduleRoot . '/components';
        $destination = $documentRoot . '/local/components/' . self::COMPONENT_NAMESPACE;

        if (!is_dir($source)) {
            throw new SystemException('Components source not found: ' . $source);
        }

        CopyDirFiles($source, $destination, true, true);

        $jsExtSource = $moduleRoot . '/install/public/js/otus.servicecenter';
        $jsExtDest = $documentRoot . '/local/js/otus/servicecenter';

        if (is_dir($jsExtSource)) {
            CopyDirFiles($jsExtSource, $jsExtDest, true, true);
        }

        $activitiesSource = $moduleRoot . '/lib/Activities';
        $activitiesDest = $documentRoot . '/local/activities/otus';

        if (is_dir($activitiesSource)) {
            CopyDirFiles($activitiesSource, $activitiesDest, true, true);
        }

        $pages = [
            'otus_sc_home.php',
            'otus_sc_stock.php',
            'purchase.php',
            'purchase_create.php',
        ];

        foreach ($pages as $page) {
            $pageSource = __DIR__ . '/public/' . $page;

            if (is_file($pageSource)) {
                copy($pageSource, $documentRoot . '/' . $page);
            }
        }

        return true;
    }

    /**
     * Удаление файлов модуля: компоненты, JS-расширение, activity, страницы.
     * Удаляем только своё — файлы портала не трогаем.
     *
     * @return bool true
     */
    public function UnInstallFiles(): bool
    {
        $documentRoot = Application::getDocumentRoot();
        $componentsRoot = $documentRoot . '/local/components/' . self::COMPONENT_NAMESPACE;

        foreach (['service.center.garage', 'service.center.garage.history', 'service.center.purchase'] as $componentName) {
            $path = $componentsRoot . '/' . $componentName;

            if (is_dir($path)) {
                Directory::deleteDirectory($path);
            }
        }

        $jsExtPath = $documentRoot . '/local/js/otus/servicecenter';

        if (is_dir($jsExtPath)) {
            Directory::deleteDirectory($jsExtPath);
        }

        $activitiesPath = $documentRoot . '/local/activities/otus';

        if (is_dir($activitiesPath)) {
            Directory::deleteDirectory($activitiesPath);
        }

        foreach (['otus_sc_home.php', 'otus_sc_test.php', 'otus_sc_stock.php', 'purchase.php', 'purchase_create.php'] as $page) {
            @unlink($documentRoot . '/' . $page);
        }

        return true;
    }

    /**
     * Регистрация обработчиков событий модуля и агента остатков (раз в сутки).
     * Предварительно снимает старые агенты модуля (идемпотентно).
     *
     * @return bool true
     */
    public function InstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        foreach (self::EVENTS as $event) {
            $eventManager->registerEventHandler(
                $event['moduleId'],
                $event['eventType'],
                $this->MODULE_ID,
                $event['handlerClass'],
                $event['handlerMethod']
            );
        }

        \CAgent::RemoveModuleAgents($this->MODULE_ID);
        \CAgent::AddAgent(
            '\\Otus\\Service\\Center\\Agents\\StockUpdateAgent::run();',
            $this->MODULE_ID,
            'N',
            86400,
            '',
            'Y',
            \ConvertTimeStamp(time() + 60, 'FULL')
        );

        return true;
    }

    /**
     * Снятие всех обработчиков событий и удаление агентов модуля.
     *
     * @return bool true
     */
    public function UnInstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        foreach (self::EVENTS as $event) {
            $eventManager->unRegisterEventHandler(
                $event['moduleId'],
                $event['eventType'],
                $this->MODULE_ID,
                $event['handlerClass'],
                $event['handlerMethod']
            );
        }

        \CAgent::RemoveModuleAgents($this->MODULE_ID);

        return true;
    }

    /**
     * Проверка существования таблицы в текущей схеме БД через information_schema.
     *
     * @param string $tableName Имя таблицы
     *
     * @return bool true если таблица существует
     */
    private function isTableExists(string $tableName): bool
    {
        $connection = Application::getConnection();
        $safeName = $connection->getSqlHelper()->forSql($tableName);

        $count = (int) $connection->queryScalar(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = '{$safeName}'"
        );

        return $count > 0;
    }

    /**
     * Абсолютный путь до SQL-файла установочного батча.
     *
     * @param string $fileName Имя файла (install.sql / uninstall.sql)
     *
     * @return string Абсолютный путь
     *
     * @throws SystemException Если файл не найден
     */
    private function getSqlPath(string $fileName): string
    {
        $path = __DIR__ . '/db/mysql/' . $fileName;

        if (!is_file($path)) {
            throw new SystemException('SQL batch file not found: ' . $path);
        }

        return $path;
    }
}