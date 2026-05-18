<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>
<div class="otus-custom-tab-wrapper">
    <?php
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.grid',
        '',
        [
            'GRID_ID' => $arResult['GRID_ID'],
            'COLUMNS' => $arResult['COLUMNS'],
            'ROWS' => $arResult['ROWS'],
            'SORT' => $arResult['SORT'],
            'SORT_VARS' => $arResult['SORT_VARS'],
        ]
    );
    ?>
</div>