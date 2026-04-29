<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<form method="get" action="<?= htmlspecialcharsbx($arResult['CURRENT_URL']) ?>">
    <label for="currency_select">Выберите валюту:</label>
    <select name="currency" id="currency_select" onchange="this.form.submit()">
        <?php foreach ($arResult['CURRENCY_LIST'] as $code => $title): ?>
            <option value="<?= htmlspecialcharsbx($code) ?>" <?= ($code == $arResult['SELECTED_CURRENCY']) ? 'selected' : '' ?>>
                <?= htmlspecialcharsbx($title) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>


<p>
    <strong>Текущий курс:</strong> 1 <?= htmlspecialcharsbx($arResult['SELECTED_CURRENCY']) ?>
    = <?= number_format($arResult['CURRENT_RATE'], 4, '.', ' ') ?>
    (к базовой валюте)
</p>
