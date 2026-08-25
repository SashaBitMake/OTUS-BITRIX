<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #11: Локальное REST приложение дата последней коммуникации");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><?= $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания были выполнены следующие.</p>

<h5 class="mb-3">1. Создан <a href="/devops/edit/out-hook/2/">исходящий вебхук</a> и настроен на обработчик, вызывается при добавление дела в контакте. </h5>

<h5 class="mb-3">2. Создан <a href="/devops/edit/in-hook/3/">входящий вебхук</a> отвечает за обновление даты, а также получения данных для обновления.</h5>

<h5 class="mb-3">3. Создано <a href="/bitrix/admin/userfield_edit.php?ID=115&lang=ru">пользовательское поле</a> для контакта.</h5>

<h5 class="mb-3">4. Создан <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Ftest%2Fhendler.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">обработчик</a> в котором собрана все логика обработки хуков</h5>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>