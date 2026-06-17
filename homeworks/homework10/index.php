<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #10: Обработка событий");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><?= $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания были выполнены следующие.</p>

<h5 class="mb-3">1. Создан инфоблок <a href="/services/lists/23/view/0/?list_section_id=">Заявки</a>:</h5>
    <ul>
        <li>Произведена настройка <a href="/bitrix/admin/iblock_edit.php?type=lists&lang=ru&ID=23&admin=Y">свойств элементов</a>, с привязкой к элементам.</li>
    </ul>

<h5 class="mb-3">2. Создан обработчик событий реагирующий на изменения суммы в Инфоблоке Заявки и CRM Заказы, включает в себя:</h5>

   <ul>
        <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fevents%2FSynchronizationHandler.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">SynchronizationHandler.php</a>, основной класс содержащий логику.</li>
        <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2Fevents_extra.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">events_extra.php</a>, файл содержит EventHandler.</li>
    </ul>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>