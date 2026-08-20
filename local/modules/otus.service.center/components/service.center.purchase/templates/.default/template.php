<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

$labels = $arResult['STATUS_LABELS'];

function otusScFmtDate($value): string
{
    if (empty($value)) {
        return '—';
    }

    if ($value instanceof \Bitrix\Main\Type\DateTime) {
        return $value->format('d.m.Y H:i');
    }

    return (string) $value;
}
?>
<div style="padding:12px;">

<?php if (isset($arResult['REQUEST']) && $arResult['REQUEST'] !== null): ?>
    <?php $r = $arResult['REQUEST']; ?>
    <a href="/purchase.php">&larr; К списку заявок</a>
    <h2><?= htmlspecialcharsbx($r['TITLE']) ?> (#<?= (int) $r['ID'] ?>)</h2>

    <table style="font-size:13px;border-collapse:collapse;margin-bottom:16px;">
        <tr>
            <td style="padding:4px 12px 4px 0;color:#80868d;">Статус:</td>
            <td style="padding:4px 0;"><?= htmlspecialcharsbx($labels[$r['STAGE_ID']] ?? $r['STAGE_ID']) ?></td>
        </tr>
        <tr>
            <td style="padding:4px 12px 4px 0;color:#80868d;">Автор:</td>
            <td style="padding:4px 0;"><?= htmlspecialcharsbx($r['AUTHOR_NAME']) ?></td>
        </tr>
        <tr>
            <td style="padding:4px 12px 4px 0;color:#80868d;">Создана:</td>
            <td style="padding:4px 0;"><?= htmlspecialcharsbx(otusScFmtDate($r['CREATED_AT'])) ?></td>
        </tr>
        <?php if ($r['PROCESSED_BY_ID'] > 0): ?>
            <tr>
                <td style="padding:4px 12px 4px 0;color:#80868d;">Обработал:</td>
                <td style="padding:4px 0;"><?= htmlspecialcharsbx($r['PROCESSED_NAME']) ?></td>
            </tr>
            <tr>
                <td style="padding:4px 12px 4px 0;color:#80868d;">Дата обработки:</td>
                <td style="padding:4px 0;"><?= htmlspecialcharsbx(otusScFmtDate($r['PROCESSED_AT'])) ?></td>
            </tr>
        <?php endif; ?>
        <?php if ($r['STAGE_ID'] === 'REJECTED' && $r['REJECT_REASON'] !== ''): ?>
            <tr>
                <td style="padding:4px 12px 4px 0;color:#80868d;">Причина отказа:</td>
                <td style="padding:4px 0;"><?= htmlspecialcharsbx($r['REJECT_REASON']) ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <h3>Состав заявки</h3>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:16px;">
        <thead>
        <tr style="border-bottom:2px solid #dfe4ea;text-align:left;">
            <th style="padding:6px 8px;">Товар</th>
            <th style="padding:6px 8px;">Количество</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($arResult['ITEMS'] as $item): ?>
            <tr style="border-bottom:1px solid #eef1f4;">
                <td style="padding:6px 8px;"><?= htmlspecialcharsbx($item['PRODUCT_NAME']) ?> (#<?= (int) $item['PRODUCT_ID'] ?>)</td>
                <td style="padding:6px 8px;"><?= (int) $item['QUANTITY'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($r['STAGE_ID'] === 'NEW' && $arResult['IS_STAFF']): ?>
        <a href="javascript:void(0)" class="ui-btn ui-btn-success"
           onclick="otusScApprove(<?= (int) $r['ID'] ?>)">Одобрить</a>
        <a href="javascript:void(0)" class="ui-btn ui-btn-danger"
           onclick="otusScReject(<?= (int) $r['ID'] ?>)">Отклонить</a>
    <?php endif; ?>

<?php else: ?>
    <h2>История заявок на закупку</h2>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
        <tr style="border-bottom:2px solid #dfe4ea;text-align:left;">
            <th style="padding:6px 8px;">ID</th>
            <th style="padding:6px 8px;">Название</th>
            <th style="padding:6px 8px;">Статус</th>
            <th style="padding:6px 8px;">Автор</th>
            <th style="padding:6px 8px;">Создана</th>
            <th style="padding:6px 8px;">Обработал</th>
            <th style="padding:6px 8px;">Дата обработки</th>
            <th style="padding:6px 8px;"></th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($arResult['REQUESTS'])): ?>
            <tr><td colspan="8" style="padding:12px;">Заявок пока нет.</td></tr>
        <?php else: ?>
            <?php foreach ($arResult['REQUESTS'] as $r): ?>
                <tr style="border-bottom:1px solid #eef1f4;">
                    <td style="padding:6px 8px;"><?= (int) $r['ID'] ?></td>
                    <td style="padding:6px 8px;"><?= htmlspecialcharsbx($r['TITLE']) ?></td>
                    <td style="padding:6px 8px;"><?= htmlspecialcharsbx($labels[$r['STAGE_ID']] ?? $r['STAGE_ID']) ?></td>
                    <td style="padding:6px 8px;"><?= htmlspecialcharsbx($r['AUTHOR_NAME']) ?></td>
                    <td style="padding:6px 8px;"><?= htmlspecialcharsbx(otusScFmtDate($r['CREATED_AT'])) ?></td>
                    <td style="padding:6px 8px;"><?= htmlspecialcharsbx($r['PROCESSED_NAME'] ?: '—') ?></td>
                    <td style="padding:6px 8px;"><?= htmlspecialcharsbx(otusScFmtDate($r['PROCESSED_AT'])) ?></td>
                    <td style="padding:6px 8px;white-space:nowrap;">
                        <a href="javascript:void(0)" onclick="otusScItems(<?= (int) $r['ID'] ?>)">Позиции</a>
                        |
                        <a href="/purchase.php?request_id=<?= (int) $r['ID'] ?>">Открыть</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

</div>

<script>
    (function () {
        'use strict';

        window.OTUS_SC_ITEMS = <?= json_encode($arResult['ITEMS_MAP'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
        window.OTUS_SC_SESSID = '<?= bitrix_sessid() ?>';

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function showMessage(text, isError) {
            var el = document.createElement('div');
            el.style.cssText = 'position:fixed;top:20px;right:20px;z-index:3000;padding:12px 18px;'
                + 'border-radius:6px;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.25);'
                + (isError ? 'background:#fde2e2;color:#a94442;' : 'background:#e6f7e8;color:#2a7a30;');
            el.textContent = text;
            document.body.appendChild(el);
            setTimeout(function () { el.remove(); }, 4000);
        }

        function ajax(action, extra, onOk) {
            var data = 'sessid=' + window.OTUS_SC_SESSID + '&action=' + action;
            for (var k in extra) {
                if (Object.prototype.hasOwnProperty.call(extra, k)) {
                    data += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(extra[k]);
                }
            }
            BX.ajax({
                url: '/local/components/otus/service.center.purchase/ajax.php',
                method: 'POST',
                dataType: 'json',
                data: data,
                onsuccess: function (r) {
                    if (r && r.ok) {
                        showMessage(r.message || 'Операция выполнена.', false);
                        setTimeout(function () { location.reload(); }, 900);
                    } else {
                        showMessage((r && r.error) || 'Ошибка операции.', true);
                    }
                },
                onfailure: function () { showMessage('Ошибка сети.', true); }
            });
        }

        window.otusScItems = function (requestId) {
            var items = window.OTUS_SC_ITEMS[requestId] || [];
            var rows = '';

            items.forEach(function (it) {
                rows += '<tr style="border-bottom:1px solid #eef1f4;">'
                    + '<td style="padding:6px 8px;">' + esc(it.PRODUCT_NAME) + ' (#' + it.PRODUCT_ID + ')</td>'
                    + '<td style="padding:6px 8px;">' + it.QUANTITY + '</td>'
                    + '</tr>';
            });

            var popup = new BX.PopupWindow('otus_sc_items_' + requestId, null, {
                titleBar: 'Позиции заявки #' + requestId,
                content: BX.create('DIV', {
                    style: {padding: '12px', minWidth: '360px'},
                    html: '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                        + '<tr style="border-bottom:2px solid #dfe4ea;text-align:left;">'
                        + '<th style="padding:6px 8px;">Товар</th><th style="padding:6px 8px;">Количество</th></tr>'
                        + (rows || '<tr><td colspan="2" style="padding:8px;">Нет позиций.</td></tr>')
                        + '</table>'
                }),
                autoHide: false,
                closeByEsc: true,
                zIndex: 2500,
                buttons: [
                    new BX.PopupWindowButton({
                        text: 'Закрыть',
                        className: 'ui-btn ui-btn-link',
                        events: {click: function () { popup.close(); }}
                    })
                ]
            });

            popup.show();
        };

        window.otusScApprove = function (requestId) {
            var popup = new BX.PopupWindow('otus_sc_approve_' + requestId, null, {
                titleBar: 'Подтверждение',
                content: BX.create('DIV', {style: {padding: '15px'}, text: 'Одобрить заявку на закупку?'}),
                autoHide: false,
                closeByEsc: true,
                zIndex: 2500,
                buttons: [
                    new BX.PopupWindowButton({
                        text: 'Одобрить',
                        className: 'ui-btn ui-btn-success',
                        events: {click: function () { popup.close(); ajax('approve', {request_id: requestId}); }}
                    }),
                    new BX.PopupWindowButton({
                        text: 'Отмена',
                        className: 'ui-btn ui-btn-link',
                        events: {click: function () { popup.close(); }}
                    })
                ]
            });
            popup.show();
        };

        window.otusScReject = function (requestId) {
            var reasonInput = BX.create('TEXTAREA', {
                attrs: {rows: 3, placeholder: 'Укажите причину отказа…'},
                style: {width: '100%', marginTop: '8px'}
            });

            var rejectBtn = new BX.PopupWindowButton({
                text: 'Отклонить',
                className: 'ui-btn ui-btn-danger',
                events: {
                    click: function () {
                        var reason = (reasonInput.value || '').trim();
                        if (reason === '') {
                            reasonInput.style.borderColor = '#a94442';
                            return;
                        }
                        popup.close();
                        ajax('reject', {request_id: requestId, reason: reason});
                    }
                }
            });

            var popup = new BX.PopupWindow('otus_sc_reject_' + requestId, null, {
                titleBar: 'Отклонить заявку',
                content: BX.create('DIV', {
                    style: {padding: '15px', width: '380px'},
                    children: [BX.create('DIV', {text: 'Причина отказа:'}), reasonInput]
                }),
                autoHide: false,
                closeByEsc: true,
                zIndex: 2500,
                buttons: [
                    rejectBtn,
                    new BX.PopupWindowButton({
                        text: 'Отмена',
                        className: 'ui-btn ui-btn-link',
                        events: {click: function () { popup.close(); }}
                    })
                ]
            });

            BX.bind(reasonInput, 'input', function () {
                var btn = rejectBtn.buttonContainer;
                if (btn) {
                    btn.style.opacity = (reasonInput.value || '').trim() === '' ? '0.5' : '1';
                }
                reasonInput.style.borderColor = '';
            });

            popup.show();
        };
    })();
</script>
