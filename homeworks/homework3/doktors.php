<?
use Bitrix\Main\Page\Asset;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>

<?
$APPLICATION->SetPageProperty("title", "Врачи");
//подключение костомных классов
use App\Models\Lists\DoktorsPropertyValuesTable as DoktorTable;
use App\Models\Lists\ProceduresPropertyValuesTable as ProcedurTable;

//настройка маршутиования для POST
$doktor_ID = isset($_GET['doktor_ID']) ? (int)$_GET['doktor_ID'] : 0;
$action = $_GET['action'] ?? '';

$DetailView = $doktor_ID > 0;
$NewProcForm = $action === 'newProcedur';
$DoctorForm = in_array($action, ['new', 'edit']);

// код для добавления процедуры
if ($action == 'newProcedur' && !$DetailView) {
    if (isset($_POST['proc-submit'])) {
        unset($_POST['proc-submit']);
        if (ProcedurTable::add($_POST)) {
            header("Location: /homeworks/homework3/doktors.php");
            exit();
        } else {
            $error = "Произошла ошибка при добавлении процедуры";
        }
    }
}

// код для добовления редактирования врачей
if ($action == 'new' || $action == 'edit') {
    if (isset($_POST['doktor-submit'])) {
        unset($_POST['doktor-submit']);
        
        if ($action == 'edit' && !empty($_POST['ID'])) {
            $ID = $_POST['ID'];
            unset($_POST['ID']);
            $_POST['IBLOCK_ELEMENT_ID'] = $ID;

            if (!empty($_POST['PROCED_ID'])) {
                $procs = $_POST['PROCED_ID'];
                unset($_POST['PROCED_ID']);
                CIBlockElement::SetPropertyValues($ID, DoktorTable::IBLOCK_ID, $procs, "PROCED_ID");
            }

            if (DoktorTable::update($ID, $_POST)) {
                header("Location: /homeworks/homework3/doktors.php");
                exit();
            } else {
                $error = "Произошла ошибка при обновлении врача";
            }
        }
        elseif ($action == 'new' && DoktorTable::add($_POST)) {
            header("Location: /homeworks/homework3/doktors.php");
            exit();
        } else {
            $error = "Произошла ошибка";
        }
    }

    // код для получения списка процедур при редактировании
    $proc_options = ProcedurTable::query()
        ->setSelect(['ID' => 'ELEMENT.ID', 
        'NAME' => 'ELEMENT.NAME',
        ])
        ->fetchAll();
    
    // код для получения данных врача при редактировании
    $data = null;
    if (!empty($doktor_ID) && $action == 'edit') {
        $data = DoktorTable::query()
            ->setSelect(['*', 'ID' => 'ELEMENT.ID', 
            'NAME' => 'ELEMENT.NAME', 
            'PROCED_ID'])
            ->where("ID", $doktor_ID)
            ->fetch();
    }
}


$doctors = [];
$doktor = null;
$procd = [];

//  код для получаем список врачей
if (!$DetailView && !$NewProcForm && !$DoctorForm) {
    $doctors = DoktorTable::query()
        ->setSelect([
            '*',
            'ID' => 'ELEMENT.ID',
            'NAME' => 'ELEMENT.NAME',
        ])  
        ->fetchAll();
}

// код для получение данных врача при выборе
if ($DetailView && !empty($doktor_ID) && !$DoctorForm) {
    $doktor = DoktorTable::query()
        ->setSelect([
            '*',
            'ID' => 'ELEMENT.ID',
            'NAME' => 'ELEMENT.NAME',
            'PROCED_ID',
        ])
        ->where("ID", $doktor_ID)
        ->fetch();
    
    if (is_array($doktor) && !empty($doktor['PROCED_ID'])) {
        $procd = ProcedurTable::query()
            ->setSelect([
                'NAME' => 'ELEMENT.NAME',
            ])
            ->where("ELEMENT.ID", "in", $doktor['PROCED_ID'])
            ->fetchAll();
    }
}
?>

<div class="doctors-page" style="max-width: 1000px; margin: 30px auto; padding: 0 20px; font-family: 'Helvetica Neue', Arial, sans-serif;">
    
    <?php if (!empty($error)): ?>
        <div class="ui-alert ui-alert-danger" style="margin-bottom: 20px; padding: 12px;">
            <?= htmlspecialcharsbx($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- ==================== СПИСОК ВРАЧЕЙ ==================== -->
    <?php if (!$DetailView && !$NewProcForm && !$DoctorForm): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
            <h1 style="margin: 0; font-size: 24px;">Врачи</h1>
            <div style="display: flex; gap: 10px;">
                <a href="?action=newProcedur" class="ui-btn ui-btn-secondary"> Новая процедура</a>
                <a href="?action=new" class="ui-btn ui-btn-primary"> Создать врача</a>
            </div>
        </div>
        
        <?php if (!empty($doctors)): ?>
            <div class="ui-list" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 6px;">
                <?php foreach ($doctors as $doc): ?>
                    <?php
                    $fullName = trim(
                        ($doc['LAST_NAME'] ?? '') . ' ' . 
                        ($doc['FIRST_NAME'] ?? '') . ' ' . 
                        ($doc['MIDDLE_NAME'] ?? $doc['NAME'] ?? '')
                    );
                    $spec = !empty($doc['SPEC_ID']) ? htmlspecialcharsbx($doc['SPEC_ID']) : '';
                    ?>
                    <div class="ui-list-item" style="padding: 15px 20px; border-bottom: 1px solid #f0f0f0;">
                        <a href="?doktor_ID=<?= $doc['ID'] ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;">
                                <?= htmlspecialcharsbx($fullName ?: $doc['NAME']) ?>
                            </div>
                            <?php if ($spec): ?>
                                <div style="color: #666; font-size: 14px;">
                                    <?= $spec ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ui-alert ui-alert-info" style="padding: 20px; background: #e8f4fd; border: 1px solid #b6d4fe; border-radius: 4px;">
                Врачи не найдены
            </div>
        <?php endif; ?>
    
    <!-- ==================== ДЕТАЛЬНАЯ СТРАНИЦА ВРАЧА ==================== -->
    <?php elseif ($DetailView && $doktor && is_array($doktor) && !$DoctorForm): ?>
        <div style="margin-bottom: 25px;">
            <a href="?" class="ui-btn ui-btn-light ui-btn-sm">← Назад к списку</a>
        </div>
        
        <!-- Карточка врача -->
        <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 25px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                <h1 style="margin: 0; font-size: 24px;">
                    <?= htmlspecialcharsbx(
                        trim($doktor['LAST_NAME'] ?? '') . ' ' . 
                        trim($doktor['FIRST_NAME'] ?? '') . ' ' . 
                        trim($doktor['MIDDLE_NAME'] ?? '')
                    ) ?: htmlspecialcharsbx($doktor['NAME']) ?>
                </h1>
                <a href="?action=edit&doktor_ID=<?= $doktor['ID'] ?>" class="ui-btn ui-btn-primary ui-btn-sm">
                    Редактировать
                </a>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <div style="color: #888; font-size: 13px; margin-bottom: 5px; text-transform: uppercase;">Фамилия</div>
                    <div style="font-size: 16px;"><?= htmlspecialcharsbx($doktor['LAST_NAME'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="color: #888; font-size: 13px; margin-bottom: 5px; text-transform: uppercase;">Имя</div>
                    <div style="font-size: 16px;"><?= htmlspecialcharsbx($doktor['FIRST_NAME'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="color: #888; font-size: 13px; margin-bottom: 5px; text-transform: uppercase;">Отчество</div>
                    <div style="font-size: 16px;"><?= htmlspecialcharsbx($doktor['MIDDLE_NAME'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="color: #888; font-size: 13px; margin-bottom: 5px; text-transform: uppercase;">Специальность</div>
                    <div style="font-size: 16px;">
                        <?= !empty($doktor['SPEC_ID']) ? htmlspecialcharsbx($doktor['SPEC_ID']) : '—' ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Список процедур -->
        <h2 style="margin-bottom: 15px; font-size: 20px;">Выполняемые процедуры</h2>
        
        <?php if (!empty($procd)): ?>
            <div class="ui-list" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 6px;">
                <?php foreach ($procd as $proc): ?>
                    <div class="ui-list-item" style="padding: 12px 20px; border-bottom: 1px solid #f0f0f0;">
                        <?= htmlspecialcharsbx($proc['NAME']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ui-alert ui-alert-info" style="padding: 20px; background: #e8f4fd; border: 1px solid #b6d4fe; border-radius: 4px;">
                У врача пока нет назначенных процедур
            </div>
        <?php endif; ?>
    
    <!-- ==================== ФОРМА ДОБАВЛЕНИЯ/РЕДАКТИРОВАНИЯ ВРАЧА ==================== -->
    <?php elseif ($DoctorForm): ?>
        <div style="margin-bottom: 25px;">
            <a href="<?= $action == 'edit' && !empty($doktor_ID) ? "?doktor_ID=$doktor_ID" : "?" ?>" class="ui-btn ui-btn-light ui-btn-sm">
                ← Назад
            </a>
        </div>
        
        <h1 style="margin-bottom: 25px; font-size: 24px;">
            <?= $action == 'new' ? 'Создать врача' : 'Редактировать врача' ?>
        </h1>
        
        <form method="POST" style="background: #fff; padding: 25px; border: 1px solid #e0e0e0; border-radius: 6px; max-width: 700px;">
            
            <?php if ($action == 'edit' && !empty($data['ID'])): ?>
                <input type="hidden" name="ID" value="<?= $data['ID'] ?>">
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Название (обязательно) *</label>
                <input type="text" 
                    name="NAME" 
                    value="<?= htmlspecialcharsbx($data['NAME'] ?? '') ?>" 
                    required
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Фамилия *</label>
                <input type="text" 
                       name="LAST_NAME" 
                       value="<?= htmlspecialcharsbx($data['LAST_NAME'] ?? '') ?>" 
                       required
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Имя *</label>
                <input type="text" 
                       name="FIRST_NAME" 
                       value="<?= htmlspecialcharsbx($data['FIRST_NAME'] ?? '') ?>" 
                       required
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Отчество</label>
                <input type="text" 
                       name="MIDDLE_NAME" 
                       value="<?= htmlspecialcharsbx($data['MIDDLE_NAME'] ?? '') ?>" 
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Специальность (SPEC_ID)</label>
                <input type="text" 
                       name="SPEC_ID" 
                       value="<?= htmlspecialcharsbx($data['SPEC_ID'] ?? '') ?>" 
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Процедуры (PROCED_ID)</label>
                <select name="PROCED_ID[]" 
                        multiple 
                        style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; min-height: 150px;">
                    <?php 
                    // Текущие процедуры врача (для edit)
                    $currentProcs = [];
                    if ($action == 'edit' && !empty($data['PROCED_ID'])) {
                        $currentProcs = is_array($data['PROCED_ID']) ? $data['PROCED_ID'] : [$data['PROCED_ID']];
                    }
                    
                    foreach ($proc_options as $proc): 
                        $selected = in_array($proc['ID'], $currentProcs) ? 'selected' : '';
                    ?>
                        <option value="<?= $proc['ID'] ?>" <?= $selected ?>>
                            <?= htmlspecialcharsbx($proc['NAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="doktor-submit" class="ui-btn ui-btn-primary">
                    Сохранить
                </button>
                <a href="<?= $action == 'edit' && !empty($doktor_ID) ? "?doktor_ID=$doktor_ID" : "?" ?>" class="ui-btn ui-btn-light">
                    Отмена
                </a>
            </div>
        </form>
    
    <!-- ==================== ФОРМА ДОБАВЛЕНИЯ ПРОЦЕДУРЫ (только на списке) ==================== -->
    <?php elseif ($NewProcForm && !$DetailView): ?>
        <div style="margin-bottom: 25px;">
            <a href="?" class="ui-btn ui-btn-light ui-btn-sm">← Назад к списку</a>
        </div>
        
        <h1 style="margin-bottom: 25px; font-size: 24px;">Новая процедура</h1>
        
        <form method="POST" style="background: #fff; padding: 25px; border: 1px solid #e0e0e0; border-radius: 6px; max-width: 600px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Название процедуры *</label>
                <input type="text" 
                       name="NAME" 
                       required
                       placeholder="Введите название процедуры"
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
      
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="proc-submit" class="ui-btn ui-btn-primary">
                    Сохранить процедуру
                </button>
                <a href="?" class="ui-btn ui-btn-light">
                    Отмена
                </a>
            </div>
        </form>
    
    <!-- ==================== ВРАЧ НЕ НАЙДЕН ==================== -->
    <?php else: ?>
        <div class="ui-alert ui-alert-danger" style="padding: 20px; background: #ffebee; border: 1px solid #ef9a9a; border-radius: 4px;">
            Врач не найден. <a href="?">Вернуться к списку</a>.
        </div>
    <?php endif; ?>
    
</div>

<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>