<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Otus\Service\Center\Helpers\CrmHelper;

/**
 * Страница настроек модуля (Настройки → Настройки продукта → Настройки модулей).
 *
 * Современный контракт options.php (как у b24connector): модуль сам рисует
 * CAdminTabControl, форму и кнопку сохранения.
 *
 * Администратор выбирает воронку сервисного центра;
 * её ID сохраняется в опцию service_category_id, которую читают сервисы.
 * Воронки сделок в этом ядре хранятся в b_crm_deal_category.
 */
$module_id = CrmHelper::MODULE_ID;

global $APPLICATION;

if ($APPLICATION->GetGroupRight($module_id) >= 'W') {
    IncludeModuleLangFile(__FILE__);

    $request = Application::getInstance()->getContext()->getRequest();

    $aTabs = [
        [
            'DIV' => 'edit1',
            'TAB' => Loc::getMessage('OTUS_SC_OPT_TAB_CRM'),
            'ICON' => '',
            'TITLE' => Loc::getMessage('OTUS_SC_OPT_TAB_CRM'),
        ],
    ];

    $tabControl = new CAdminTabControl('tabControl', $aTabs);

    // Сохранение настроек
    if ($request->isPost() && $request->get('Update') !== null && check_bitrix_sessid()) {
        Option::set(
            $module_id,
            CrmHelper::OPTION_SERVICE_CATEGORY_ID,
            (string) (int) $request->get('otus_sc_category_id')
        );

        LocalRedirect(
            $APPLICATION->GetCurPage()
            . '?mid=' . urlencode($module_id)
            . '&lang=' . urlencode(LANGUAGE_ID)
            . '&' . $tabControl->ActiveTabParam()
        );
    }

    $currentCategoryId = (int) Option::get(
        $module_id,
        CrmHelper::OPTION_SERVICE_CATEGORY_ID,
        '0'
    );

    // Список воронок сделок (b_crm_deal_category — таблица воронок в этом ядре).
    $categories = [];

    try {
        $connection = Application::getConnection();

        $rows = $connection->query(
            'SELECT ID, NAME FROM b_crm_deal_category ORDER BY ID ASC'
        )->fetchAll();

        foreach ($rows as $row) {
            $categories[(int) $row['ID']] = (string) $row['NAME'];
        }
    } catch (\Throwable $e) {
        $categories = [];
    }

    $tabControl->Begin();
    ?>
    <form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?php
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td style="width: 40%;">
            <?= Loc::getMessage('OTUS_SC_OPT_CATEGORY') ?>:
        </td>
        <td style="width: 60%;">
            <select name="otus_sc_category_id">
                <option value="0"><?= Loc::getMessage('OTUS_SC_OPT_CATEGORY_NOT_SET') ?></option>
                <?php foreach ($categories as $id => $name): ?>
                    <option value="<?= $id ?>"<?= $id === $currentCategoryId ? ' selected' : '' ?>>
                        <?= htmlspecialcharsbx($name) ?> (ID <?= $id ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
            <small><?= Loc::getMessage('OTUS_SC_OPT_CATEGORY_HINT') ?></small>
        </td>
    </tr>
    <?php
    $tabControl->Buttons();
    ?>
        <input type="submit" name="Update" value="<?= GetMessage('MAIN_SAVE') ?>" class="adm-btn-save">
        <?= bitrix_sessid_post(); ?>
    <?php
    $tabControl->End();
    ?>
    </form>
    <?php
}