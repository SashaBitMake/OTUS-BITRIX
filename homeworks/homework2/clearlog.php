<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
// ТУТ ДОБАВИТЬ СВОЮ ФУНКЦИЮ ОЧИСТКИ ЛОГА
$logFileName = "custom_" . date('d.m.Y');
App\Debug\Log::cleanLog($logFileName);
LocalRedirect('');