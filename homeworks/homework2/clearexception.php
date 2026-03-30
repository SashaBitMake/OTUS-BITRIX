<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
// ТУТ ДОБАВИТЬ СВОЮ ФУНКЦИЮ ОЧИСТКИ ЛОГА
\App\Debug\Log::cleanLog('exceptions');
LocalRedirect('');
