<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
    'otus.tcrm',
    [
        '\\Otus\\TCrm\\Handler' => 'lib/handler.php',
        '\\Otus\\TCrm\\CrmDataTable' => 'lib/CrmDataTable.php',
    ]
);
