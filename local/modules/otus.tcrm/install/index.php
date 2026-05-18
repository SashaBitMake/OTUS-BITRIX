<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

class otus_tcrm extends CModule
{
    public $MODULE_ID = 'otus.tcrm';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    /**
     * Инициализация данных модуля:
     * - версия
     * - название
     * - описание
     * - информация о партнере
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('OTUS_TCRM_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('OTUS_TCRM_MODULE_DESC');
        $this->PARTNER_NAME = 'OTUS';
        $this->PARTNER_URI = 'https://otus.ru';
    }

    /**
     * Установка модуля:
     * - регистрация модуля
     * - установка БД
     * - копирование файлов
     * - регистрация обработчиков событий
     *
     * @return void
     */
    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();
    }

    /**
     * Удаление модуля:
     * - удаление обработчиков событий
     * - удаление файлов
     * - удаление таблиц БД
     * - удаление регистрации модуля
     *
     * @return void
     */
    public function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Устанавливает структуру БД модуля.
     * Выполняет SQL-скрипт install.sql.
     *
     * @return void
     */ 
    public function InstallDB()
    {
        global $DB;
        $DB->RunSQLBatch(__DIR__ . '/db/mysql/install.sql');
    }

    /**
     * Удаляет структуру БД модуля.
     * Выполняет SQL-скрипт uninstall.sql.
     *
     * @return void
     */
    public function UnInstallDB()
    {
        global $DB;
        $DB->RunSQLBatch(__DIR__ . '/db/mysql/uninstall.sql');
    }

    /**
     * Копирует файлы компонентов модуля
     * в директорию local/components.
     *
     * @return void
     */    
    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            Application::getDocumentRoot() . '/local/components',
            true,
            true
        );
    }

    /**
     * Удаляет файлы компонентов модуля.
     *
     * @return void
     */    
    public function UnInstallFiles()
    {
        Directory::deleteDirectory(Application::getDocumentRoot() . '/local/components/otus/crm.custom.tab');
    }

    /**
     * Регистрирует обработчики событий модуля.
     *
     * Регистрируется обработчик события:
     * crm:onEntityDetailsTabsInitialized
     *
     * @return void
     */    
    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Otus\\TCrm\\Handler',
            'onEntityDetailsTabsInitialized'
        );
    }

    /**
     * Удаляет зарегистрированные обработчики событий модуля.
     *
     * @return void
     */
    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Otus\\TCrm\\Handler',
            'onEntityDetailsTabsInitialized'
        );
    }
}
