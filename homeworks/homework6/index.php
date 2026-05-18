<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle(""); ?><?php
$APPLICATION->SetTitle("ДЗ #6: Написание своего модуля");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>
<h4 class="mb-3">Пояснительная записка</h4>
<div>
	<p>
		 При реализации домашнего задания было выполнены следующее. Установку модуля можно произвести из панели администрирования, <a href="/bitrix/admin/partner_modules.php?lang=ru">Marketplace</a> , название модуля OTUS: Вкладка в CRM.
	</p>
	<h5 class="mb-3">Создана собственный <a href="/bitrix/admin/fileman_admin.php?PAGEN_1=1&SIZEN_1=20&lang=ru&site=s1&path=%2Flocal%2Fmodules%2Fotus.tcrm&show_perms_for=0&fu_action=">модуль</a>, добавляющий вкладку в CRM, выводя GRID таблицу из таблицы БД.</h5>

    <pre>
        /local/modules/otus.tabcrm/
        │
        ├── install/                           <-- Всё, что связано с установкой модуля
        │   ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Findex.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">index.php</a>                      <-- Главный класс установки
        │   ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fversion.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">version.php</a>                    <-- Массив с версией и датой
        │   │
        │   ├── db/                            <-- SQL-скрипты для базы данных
        │   │   └── mysql/
        │   │       ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fdb%2Fmysql%2Finstall.sql&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">install.sql</a>            <-- скрип для создании таблицы в базе данных, и заполнение тестовой информацией
        │   │       └── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fdb%2Fmysql%2Funinstall.sql&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">uninstall.sql</a>          <-- скрипт для удаления таблицы с БД
        │   │
        │   └── components/                    <-- Исходники компонентов 
        │       └── otus/
        │           └── crm.custom.tab/        <-- Компонент
        │               ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fcomponents%2Fotus%2Fcrm.custom.tab%2F.description.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">.description.php</a>   <-- Описание для визуального редактора
        │               ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fcomponents%2Fotus%2Fcrm.custom.tab%2F.parameters.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">.parameters.php</a>    <-- Настройки параметров
        │               ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fcomponents%2Fotus%2Fcrm.custom.tab%2Fclass.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">class.php </a>         <-- Логика грида и запрос к БД
        │               ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fcomponents%2Fotus%2Fcrm.custom.tab%2Flazyload.ajax.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">lazyload.ajax.php</a>  <-- Точка входа для ленивой загрузки
        │               ├──  templates/
        │                   └── .default/
        │                       └── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fcomponents%2Fotus%2Fcrm.custom.tab%2Ftemplates%2F.default%2Ftemplate.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">template.php</a> <-- Вызов bitrix:main.ui.grid
        │               └── lang/
        │                   └── ru/
        │                       └── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finstall%2Fcomponents%2Fotus%2Fcrm.custom.tab%2Flang%2Fru%2Fclass.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">class.php</a> <-- Языковой файл компонета
        │
        ├── lib/                               <-- Классы модуля.
        │   ├── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Flib%2Fhandler.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">handler.php</a>                    <-- Класс Otus\TabCrm\Handler (перехватчик и вкладка)
        │   └── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Flib%2FCrmDataTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">crmdatatable.php</a>               <-- ORM-класс Otus\TabCrm\CrmDataTable (запросы к БД)
        │
        ├── lang/                              <-- Языковые файлы (переводы Loc::getMessage)
        │   └── ru/
        │       ├── install/
        │       │   └── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Flang%2Fru%2Finstall%2Findex.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">index.php</a>              <-- Перевод названия модуля и описания
        │       └── lib/
        │           └── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Flang%2Fru%2Flib%2Fhandler.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">handler.php</a>           <-- Перевод названия вкладки
        │
        └── <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fmodules%2Fotus.tcrm%2Finclude.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">include.php</a>                        <-- Точка входа

    </pre>
	<h5 class="mb-3"><a href="/crm/deal/details/13/">Пример</a></h5>
</div>
<br><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>