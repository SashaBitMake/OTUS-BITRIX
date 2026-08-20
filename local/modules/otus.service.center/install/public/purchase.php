<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Заявки на закупку');
$APPLICATION->IncludeComponent('otus:service.center.purchase', '', []);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
