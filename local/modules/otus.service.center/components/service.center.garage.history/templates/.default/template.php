<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arResult
 */

if ($arResult['ERROR'] !== '') {
    echo '<div style="padding:10px;color:#a94442;">' . htmlspecialcharsbx($arResult['ERROR']) . '</div>';

    return;
}

$rows = $arResult['ROWS'];

if (empty($rows)) {
    echo '<div style="padding:10px;">По этому автомобилю пока нет обращений в сервис.</div>';

    return;
}

$semanticLabels = [
    'S' => 'Выполнена',
    'F' => 'Не выполнена',
    'P' => 'В работе',
];

$statusStyles = [
    'В работе' => 'background:#fff4e5;color:#b97d10;border:1px solid #f0d9b0;',
    'Выполнена' => 'background:#e8f7ee;color:#2a7a30;border:1px solid #bfe6cc;',
    'Не выполнена' => 'background:#fdecec;color:#a94442;border:1px solid #f2c2c2;',
    'Закрыта' => 'background:#eef1f4;color:#535c69;border:1px solid #d7dde5;',
];

/**
 * Сумма без HTML-сущностей (число + символ валюты).
 */
function otusScFormatMoney(float $amount, string $currencyId): string
{
    $symbol = $currencyId === 'RUB' ? '₽' : $currencyId;
    $decimals = floor($amount) == $amount ? 0 : 2;

    return number_format($amount, $decimals, ',', ' ') . ' ' . $symbol;
}

$productsMap = [];

foreach ($rows as $row) {
    $productsMap[(int) $row['ID']] = $row['PRODUCTS'] ?? [];
}
?>
<table style="width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;">
    <colgroup>
        <col style="width:26%;">
        <col style="width:15%;">
        <col style="width:13%;">
        <col style="width:16%;">
        <col style="width:13%;">
        <col style="width:17%;">
    </colgroup>
    <thead>
    <tr style="border-bottom:2px solid #dfe4ea;text-align:left;color:#535c69;">
        <th style="padding:8px;">Сделка</th>
        <th style="padding:8px;">Дата обращения</th>
        <th style="padding:8px;">Статус</th>
        <th style="padding:8px;">Ответственный</th>
        <th style="padding:8px;text-align:right;">Сумма</th>
        <th style="padding:8px;">Запчасти</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <?php
        $status = $semanticLabels[$row['STAGE_SEMANTIC_ID'] ?? ''] ?? 'В работе';

        if ($status === 'В работе' && ($row['CLOSED'] ?? '') === 'Y') {
            $status = 'Закрыта';
        }

        $dateValue = $row['DATE_CREATE'] ?? null;

        if ($dateValue instanceof \Bitrix\Main\Type\DateTime) {
            $date = $dateValue->format('d.m.Y H:i');
        } elseif (is_string($dateValue) && $dateValue !== '') {
            $ts = strtotime($dateValue);
            $date = $ts !== false ? date('d.m.Y H:i', $ts) : $dateValue;
        } else {
            $date = '';
        }

        $productsCount = count($row['PRODUCTS'] ?? []);
        $style = $statusStyles[$status] ?? $statusStyles['В работе'];
        ?>
        <tr style="border-bottom:1px solid #eef1f4;vertical-align:middle;">
            <td style="padding:8px;word-break:break-word;">
                <span style="color:#80868d;">#<?= (int) $row['ID'] ?></span>
                <?= htmlspecialcharsbx((string) ($row['TITLE'] ?? '')) ?>
            </td>
            <td style="padding:8px;color:#535c69;"><?= htmlspecialcharsbx((string) $date) ?></td>
            <td style="padding:8px;">
                <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;<?= $style ?>">
                    <?= htmlspecialcharsbx($status) ?>
                </span>
            </td>
            <td style="padding:8px;">
                <?php if (($row['ASSIGNED_BY_ID'] ?? 0) > 0): ?>
                    <a href="/company/personal/user/<?= (int) $row['ASSIGNED_BY_ID'] ?>/"
                       target="_blank" style="color:#006cc0;text-decoration:none;">
                        <?= htmlspecialcharsbx((string) ($row['ASSIGNED_NAME'] ?? '#' . $row['ASSIGNED_BY_ID'])) ?>
                    </a>
                <?php else: ?>
                    <span style="color:#80868d;">—</span>
                <?php endif; ?>
            </td>
            <td style="padding:8px;text-align:right;white-space:nowrap;font-weight:600;">
                <?= htmlspecialcharsbx(otusScFormatMoney((float) ($row['OPPORTUNITY'] ?? 0), (string) ($row['CURRENCY_ID'] ?? 'RUB'))) ?>
            </td>
            <td style="padding:8px;">
                <?php if ($productsCount > 0): ?>
                    <a href="javascript:void(0)"
                       onclick="otusScDealProducts(<?= (int) $row['ID'] ?>)"
                       style="color:#006cc0;text-decoration:none;border-bottom:1px dashed #006cc0;">
                        <?= $productsCount ?> поз.
                    </a>
                <?php else: ?>
                    <span style="color:#80868d;">нет</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
    (function () {
        'use strict';

        window.OTUS_SC_DEAL_PRODUCTS = <?= json_encode($productsMap, JSON_UNESCAPED_UNICODE) ?>;

        function money(n) {
            var v = Math.round(n * 100) / 100;
            var s = v.toLocaleString('ru-RU', {maximumFractionDigits: 2});

            return s + ' ₽';
        }

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);

            return d.innerHTML;
        }

        window.otusScDealProducts = function (dealId) {
            var items = window.OTUS_SC_DEAL_PRODUCTS[dealId] || [];
            var rows = '';

            items.forEach(function (p) {
                rows += '<tr style="border-bottom:1px solid #eef1f4;">'
                    + '<td style="padding:6px 10px;">' + esc(p.NAME) + '</td>'
                    + '<td style="padding:6px 10px;text-align:center;">' + esc(p.QTY) + (p.MEASURE ? ' ' + esc(p.MEASURE) : '') + '</td>'
                    + '<td style="padding:6px 10px;text-align:right;">' + esc(money(p.PRICE)) + '</td>'
                    + '<td style="padding:6px 10px;text-align:right;font-weight:600;">' + esc(money(p.SUM)) + '</td>'
                    + '</tr>';
            });

            var popup = new BX.PopupWindow('otus_sc_deal_products_' + dealId, null, {
                titleBar: 'Запчасти по сделке #' + dealId,
                content: BX.create('DIV', {
                    style: {padding: '12px', minWidth: '420px'},
                    html: '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                        + '<thead><tr style="border-bottom:2px solid #dfe4ea;text-align:left;color:#535c69;">'
                        + '<th style="padding:6px 10px;">Запчасть</th>'
                        + '<th style="padding:6px 10px;text-align:center;">Кол-во</th>'
                        + '<th style="padding:6px 10px;text-align:right;">Цена</th>'
                        + '<th style="padding:6px 10px;text-align:right;">Сумма</th>'
                        + '</tr></thead><tbody>'
                        + (rows || '<tr><td colspan="4" style="padding:8px;">Нет позиций.</td></tr>')
                        + '</tbody></table>'
                }),
                autoHide: false,
                closeByEsc: true,
                zIndex: 3000,
                buttons: [
                    new BX.PopupWindowButton({
                        text: 'Закрыть',
                        className: 'ui-btn',
                        events: {click: function () { popup.close(); }}
                    })
                ]
            });

            popup.show();
        };
    })();
</script>