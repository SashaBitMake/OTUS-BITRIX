<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Добавление в лог");
$logFileName = "custom_" . date('d.m.Y') . ".log";

?>
    <ul class="list-group">
        <li class="list-group-item">
            <a href="/local/logs/<?= $logFileName ?>">Файл лога</a>,
            в лог добавленно 'Открыта страница writelog.php'
        </li>
    </ul>
<?
// ТУТ ДОБАВИТЬ СВОЮ ФУНКЦИЮ ДОБАВЛЕНИЯ В ЛОГ

\App\Debug\Log::addLog(message:'Test');

?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>