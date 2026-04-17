<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #4: Создание своих таблиц БД и написание модели данных к ним");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания было выполнены следующие цели.</p>

<h5 class="mb-3">1. Создата таблица <a href=/bitrix/admin/perfmon_table.php?lang=ru&table_name=schedule()>Расписание</a>, используется для связи с инфоблоками:</h5>
<ul>
    <li>Заполнена 
            <ul>
                <li>ID студента</li>
                <li>ID аудитори</li>
                <li>Время занятия</a></li>
             </ul>
    </li>
</ul>

<h5 class="mb-3">2. Созданы инфоблоки:</h5>
<ul>
    <li>Создан и заполнен список <a href=/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=18&type=lists&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y>Учителя</a>.</li>
    <li>Создан и заполнен список <a href=/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=19&type=lists&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y>Ученики</a>.</li>
    <li>Создан и заполнен список <a href=/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=20&type=lists&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y>Аудитории</a>.</li>
</ul>
<h5 class="mb-3">2. Создана <a href=/homeworks/homework4/table.php>страница</a> с выводом информации с инфоблоков через связи OneToOne, OneToMany, ManyToMany:</h5>
<ul>
    <li>Для получения данных используются следующике класы:
    <ul> 
        <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FClassroomsValuesTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Класс для аудиторий</a> нанследуется от <b>AbstractIblockPropertyValuesTable</b>.</li>
        <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FTeachersValuesTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Класс для учителей</a> нанследуется от <b>AbstractIblockPropertyValuesTable</b>.</li>
        <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FStudentsValuesTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Класс для учеников</a> нанследуется от <b>AbstractIblockPropertyValuesTable</b>.</li>
        <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FScheduleTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Класс для расписания</a> нанследуется от <b>DataManager</b>.</li>
    </ul>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>