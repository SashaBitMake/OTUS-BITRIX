<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #12: Собственные обработчики REST");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><?= $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания были выполнены следующие.</p>

<h5 class="mb-3">1. Создана таблица <a href="/bitrix/admin/perfmon_table.php?lang=ru&table_name=otus_book">книг</a> а также <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2FOrm%2FBookTable.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">ORM</a> к таблице. </h5>

<h5 class="mb-3">2. Создан <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2FService%2FBookService.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">вспомогательный сервис</a>, отвечает за валидацию, права внутри.</h5>

<h5 class="mb-3">3. Создан <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2FRest%2FEvents.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Events.php</a> отвечающий за регистрацию рест методов.</h5>

<h5 class="mb-3">4. Создан <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2FRest%2FLogger.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">логер</a>, отдельный для логирования своего реста, также используется в дз 11</h5>

<h5 class="mb-3">5. Создана тестовая <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2FRest%2FLogger.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">страница</a>, для проверки работы методов.</h5>

<h5 class="mb-3">5. Создана входящий <a href="/devops/edit/in-hook/7/">вебхук</a>.</h5>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>