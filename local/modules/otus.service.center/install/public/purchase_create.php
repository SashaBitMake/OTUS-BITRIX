<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Создание заявки на закупку');

use Otus\Service\Center\Services\PurchaseService;
use Otus\Service\Center\Services\StockService;

if (!\Bitrix\Main\Loader::includeModule('otus.service.center') || !\Bitrix\Main\Loader::includeModule('crm')) {
    echo 'Модули недоступны.';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    die();
}

global $USER;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    $items = [];
    $pids = is_array($_POST['product_id'] ?? null) ? $_POST['product_id'] : [];
    $qtys = is_array($_POST['quantity'] ?? null) ? $_POST['quantity'] : [];

    foreach ($pids as $i => $pid) {
        $pid = (int) $pid;
        $qty = (int) ($qtys[$i] ?? 0);

        if ($pid > 0 && $qty > 0) {
            $items[] = ['product_id' => $pid, 'quantity' => $qty];
        }
    }

    if (!empty($items)) {
        $service = new PurchaseService();
        $res = $service->createRequest((int) $USER->GetID(), $items);

        if ($res->isSuccess()) {
            $id = (int) $res->getData()['id'];

            if (!$service->startPurchaseWorkflow($id, false)) {
                $service->notifyProcurementDept($id);
            }

            $message = 'Создана заявка #' . $id
                . '. <a href="/purchase.php?request_id=' . $id . '">Открыть</a>';
        } else {
            $message = 'Ошибка: ' . htmlspecialcharsbx(implode(', ', $res->getErrorMessages()));
        }
    } else {
        $message = 'Добавьте хотя бы одну позицию с количеством больше 0.';
    }
}

$db = \CCrmProduct::GetList(['ID' => 'ASC'], ['ACTIVE' => 'Y'], ['ID', 'NAME'], false);
$products = [];

while ($p = $db->Fetch()) {
    $products[] = $p;
}
?>
<h2>Создание заявки на закупку</h2>
<?= $message !== '' ? '<div style="margin:10px 0;padding:10px;background:#eef6ff;border:1px solid #bcd7ff;">' . $message . '</div>' : '' ?>

<form method="post" id="otus_sc_purchase_form">
    <?= bitrix_sessid_post() ?>

    <h3>Состав заявки</h3>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:12px;">
        <thead>
        <tr style="border-bottom:2px solid #dfe4ea;text-align:left;">
            <th style="padding:6px 8px;">Товар</th>
            <th style="padding:6px 8px;">Количество</th>
            <th style="padding:6px 8px;"></th>
        </tr>
        </thead>
        <tbody id="otus_sc_rows">
        <tr id="otus_sc_empty_row">
            <td colspan="3" style="padding:10px 8px;color:#80868d;">
                Позиции ещё не добавлены. Выберите товар ниже и нажмите «Добавить».
            </td>
        </tr>
        </tbody>
    </table>

    <div style="margin-bottom:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <select id="otus_sc_product" style="min-width:320px;padding:6px;">
            <?php foreach ($products as $p): ?>
                <option value="<?= (int) $p['ID'] ?>">
                    <?= htmlspecialcharsbx($p['NAME']) ?>
                    (остаток: <?= (int) StockService::getStock((int) $p['ID']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" id="otus_sc_qty" min="1" value="1" style="width:80px;padding:6px;">
        <button type="button" class="ui-btn ui-btn-primary" onclick="otusScAddRow()">Добавить</button>
    </div>

    <button type="submit" class="adm-btn-save">Создать заявку</button>
</form>

<script>
    (function () {
        'use strict';

        var selected = {};

        window.otusScAddRow = function () {
            var select = document.getElementById('otus_sc_product');
            var qtyInput = document.getElementById('otus_sc_qty');

            var productId = parseInt(select.value, 10);
            var qty = parseInt(qtyInput.value, 10);

            if (!productId || !qty || qty <= 0) {
                return;
            }

            if (selected[productId]) {
                selected[productId].qty.value = qty;
                return;
            }

            var empty = document.getElementById('otus_sc_empty_row');

            if (empty) {
                empty.remove();
            }

            var label = select.options[select.selectedIndex].text;

            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #eef1f4';

            var tdName = document.createElement('td');
            tdName.style.padding = '6px 8px';
            tdName.textContent = label;

            var tdQty = document.createElement('td');
            tdQty.style.padding = '6px 8px';

            var qty = qty;
            var hiddenId = document.createElement('input');
            hiddenId.type = 'hidden';
            hiddenId.name = 'product_id[]';
            hiddenId.value = productId;

            var hiddenQty = document.createElement('input');
            hiddenQty.type = 'number';
            hiddenQty.name = 'quantity[]';
            hiddenQty.min = '1';
            hiddenQty.value = qty;
            hiddenQty.style.width = '80px';

            tdQty.appendChild(hiddenId);
            tdQty.appendChild(hiddenQty);

            var tdDel = document.createElement('td');
            tdDel.style.padding = '6px 8px';

            var del = document.createElement('a');
            del.href = 'javascript:void(0)';
            del.textContent = 'Убрать';
            del.onclick = function () {
                delete selected[productId];
                tr.remove();

                if (Object.keys(selected).length === 0) {
                    var tbody = document.getElementById('otus_sc_rows');
                    var emptyRow = document.createElement('tr');
                    emptyRow.id = 'otus_sc_empty_row';
                    emptyRow.innerHTML = '<td colspan="3" style="padding:10px 8px;color:#80868d;">'
                        + 'Позиции ещё не добавлены. Выберите товар ниже и нажмите «Добавить».</td>';
                    tbody.appendChild(emptyRow);
                }
            };

            tdDel.appendChild(del);

            tr.appendChild(tdName);
            tr.appendChild(tdQty);
            tr.appendChild(tdDel);

            document.getElementById('otus_sc_rows').appendChild(tr);

            selected[productId] = {qty: hiddenQty};
        };
    })();
</script>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
