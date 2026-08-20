<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Проверка остатков запчастей');

global $USER;

if (!$USER->IsAdmin()) {
    ShowError('Доступ только для администратора.');
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    die();
}

if (!\Bitrix\Main\Loader::includeModule('otus.service.center')) {
    ShowError('Модуль otus.service.center не установлен.');
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    die();
}

$report = '';
$low = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    $result = \Otus\Service\Center\Agents\StockUpdateAgent::execute();

    if ($result->isSuccess()) {
        $data = $result->getData();
        $low = $data['low'] ?? [];
        $report = sprintf(
            'Проверено товаров: %d. Ниже порога (%d): %d. Создано авто-заявок: %d. Источник: %s.',
            $data['checked'] ?? 0,
            \Otus\Service\Center\Helpers\CrmHelper::LOW_STOCK_THRESHOLD,
            count($low),
            $data['autoCreated'] ?? 0,
            $data['source'] ?? '—'
        );
    } else {
        $report = 'Ошибка: ' . implode(', ', $result->getErrorMessages());
    }
}
?>

<?= $report !== '' ? '<div class="adm-info-message">' . htmlspecialcharsbx($report) . '</div>' : '' ?>

<h2>Проверка остатков запчастей</h2>
<p>Кнопка запускает тот же код, что и агент по расписанию: числа из random.org, порог — константа <?= \Otus\Service\Center\Helpers\CrmHelper::LOW_STOCK_THRESHOLD ?>. Остаток товара = полученное значение; если значение &lt; порога, создаётся авто-заявка на +<?= \Otus\Service\Center\Helpers\CrmHelper::AGENT_REPLENISH_QUANTITY ?>.</p>

<form method="post">
    <?= bitrix_sessid_post() ?>
    <button type="submit" class="adm-btn-save">Запустить проверку остатков</button>
</form>

<?php if (!empty($low)): ?>
    <h3>Товары ниже порога (по ним созданы заявки)</h3>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
        <tr style="border-bottom:2px solid #dfe4ea;text-align:left;">
            <th style="padding:6px 8px;">ID</th>
            <th style="padding:6px 8px;">Название</th>
            <th style="padding:6px 8px;">Получено значение</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($low as $p): ?>
            <tr style="border-bottom:1px solid #eef1f4;">
                <td style="padding:6px 8px;"><?= (int) $p['ID'] ?></td>
                <td style="padding:6px 8px;"><?= htmlspecialcharsbx($p['NAME']) ?></td>
                <td style="padding:6px 8px;"><?= (int) $p['STOCK'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
