<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #3: Связывание моделей ");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания было выполнены следующие цели.</p>

<h5 class="mb-3">1. Созданы списки и настройка связи врачей с процедурами:</h5>
<ul>
    <li>Создан и заполнен список врачей
            <ul>
                <li><a href="/services/lists/16/view/0/?list_section_id=">o	Список на портале</a></li>
                <li><a href="/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=16&type=lists&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Список в административной части</a></li>
                <li><a href="/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=16&type=lists&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Настройки списка в административной части</a></li>
             </ul>
    </li>
</ul>
<ul>
    <li>Создан и заполнен процедур
            <ul>
                <li><a href="/services/lists/16/view/0/?list_section_id=">Список на портале</a></li>
                <li><a href="/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=17&type=lists&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Список в административной части</a></li>
                <li><a href="/bitrix/admin/iblock_edit.php?type=lists&lang=ru&ID=17&admin=Y">Настройки списка в административной части</a></li>
             </ul>
    </li>
</ul>
<ul>
    <li>В списке врачей настроено свойство "Процедуры" Код поля "PROCED_ID" для связи с процедурами
    </li>
</ul>

<h5 class="mb-3">2. Создана <a href="/homeworks/homework3//doktors.php">страница</a> для отображения списка врачей в ней реализовано:</h5>
<ul>
    <li>Для получения данных из списка врачей используется класс <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FLists%2FDoktorsPropertyValuesTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">DoktorsPropertyValuesTable</a>
    <li>Для получения данных из связанного списка процедур используется класс <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FLists%2FProceduresPropertyValuesTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">ProceduresPropertyValuesTable</a> 
    <li>Оба данных класса наследуются от абстрактного класса <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FModels%2FAbstractIblockPropertyValuesTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">AbstractIblockPropertyValuesTable</a>
    <li>В свою очередь <b>AbstractIblockPropertyValuesTable</b> наследуется из системного класса <b>DataManager</b> </li>
</ul>
<h5 class="mb-3">3. Был реализован функционал по добавлению записей в списки процедур и врачей, а также редактированию элемента списка врачи. Данный функционал также использует ранее представленные классы для получения данных.</h5>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>