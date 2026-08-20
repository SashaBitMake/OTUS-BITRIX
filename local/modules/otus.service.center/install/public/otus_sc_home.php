<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

use Bitrix\Main\Loader;
use Otus\Service\Center\Agents\StockUpdateAgent;
use Otus\Service\Center\Helpers\CrmHelper;
use Otus\Service\Center\ORM\GarageTable;
use Otus\Service\Center\ORM\PurchaseRequestTable;

$APPLICATION->SetTitle('Сервисный центр');

Loader::includeModule('otus.service.center');
\CJSCore::Init(['core', 'ui.buttons']);

global $USER;
$isAdmin = $USER->IsAdmin();

$report = '';
$reportOk = true;
if ($isAdmin
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['otus_sc_run_agent'] ?? '') === 'Y'
    && check_bitrix_sessid()
) {
    $result = StockUpdateAgent::execute();
    if ($result->isSuccess()) {
        $data = $result->getData();
        $report = sprintf(
            'Проверено товаров: %d. Ниже порога (%d): %d. Создано авто-заявок: %d. Источник: %s.',
            $data['checked'] ?? 0,
            CrmHelper::LOW_STOCK_THRESHOLD,
            count($data['low'] ?? []),
            $data['autoCreated'] ?? 0,
            $data['source'] ?? '—'
        );
    } else {
        $reportOk = false;
        $report = 'Ошибка: ' . implode(', ', $result->getErrorMessages());
    }
}

$newRequests = 0;
$carsCount = 0;
try {
    $newRequests = count(PurchaseRequestTable::getList([
        'filter' => ['=STATUS' => CrmHelper::REQUEST_STAGE_NEW],
        'select' => ['ID'],
    ])->fetchAll());
    $carsCount = count(GarageTable::getList(['select' => ['ID']])->fetchAll());
} catch (\Throwable $e) {
}
?>
<style>
.otus-sc-home{max-width:960px;margin:0 auto;padding:24px 16px;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;}
.otus-sc-hero{background:linear-gradient(135deg,#0b69c6 0%,#2fc0ee 100%);color:#fff;border-radius:12px;padding:28px 32px;margin-bottom:24px;}
.otus-sc-hero h1{margin:0 0 8px;font-size:26px;}
.otus-sc-hero p{margin:0;opacity:.9;font-size:14px;}
.otus-sc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;}
.otus-sc-card{background:#fff;border:1px solid #dfe4ea;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.otus-sc-card h2{margin:0 0 6px;font-size:16px;color:#333;}
.otus-sc-card .hint{color:#80868d;font-size:13px;margin-bottom:14px;}
.otus-sc-card .stat{font-size:13px;color:#535c69;margin-bottom:14px;}
.otus-sc-card .stat b{color:#0b69c6;}
.otus-sc-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.otus-sc-report{margin-top:14px;padding:10px 12px;border-radius:8px;font-size:13px;}
.otus-sc-report.ok{background:#e8f7ee;color:#2a7a30;border:1px solid #bfe6cc;}
.otus-sc-report.err{background:#fdecec;color:#a94442;border:1px solid #f2c2c2;}
</style>

<div class="otus-sc-home">
    <div class="otus-sc-hero">
        <h1>Сервисный центр</h1>
        <p>Автоматизация сервисного центра: гаражи клиентов, сделки по автомобилям,
           заявки на закупку и контроль остатков запчастей.</p>
    </div>

    <div class="otus-sc-grid">
        <div class="otus-sc-card">
            <h2>Заявки на закупку</h2>
            <div class="hint">Ручное создание заявки и история обработанных.</div>
            <div class="stat">Новых заявок: <b><?= $newRequests ?></b></div>
            <div class="otus-sc-actions">
                <a class="ui-btn ui-btn-primary" href="/purchase_create.php">Создать заявку</a>
                <a class="ui-btn ui-btn-light-border" href="/purchase.php">История заявок</a>
            </div>
        </div>

        <div class="otus-sc-card">
            <h2>Гаражи клиентов</h2>
            <div class="hint">Автомобили — в карточке контакта, вкладка «Гараж».</div>
            <div class="stat">Автомобилей в базе: <b><?= $carsCount ?></b></div>
            <div class="otus-sc-actions">
                <a class="ui-btn ui-btn-light-border" href="/crm/contact/">Открыть контакты</a>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="otus-sc-card">
            <h2>Остатки запчастей</h2>
            <div class="hint">Проверка: числа из random.org; при нуле — авто-заявка на +10.
                Агент по расписанию: раз в сутки.</div>
            <div class="otus-sc-actions">
                <form method="post" style="margin:0;">
                    <?= bitrix_sessid_post() ?>
                    <input type="hidden" name="otus_sc_run_agent" value="Y">
                    <button type="submit" class="ui-btn ui-btn-success">Запустить проверку остатков</button>
                </form>
            </div>
            <?php if ($report !== ''): ?>
                <div class="otus-sc-report <?= $reportOk ? 'ok' : 'err' ?>">
                    <?= htmlspecialcharsbx($report) ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';