<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Crm\ContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Otus\Service\Center\Events\DealSaveHandler;
use Otus\Service\Center\Helpers\CrmHelper;
use Otus\Service\Center\ORM\GarageTable;
use Otus\Service\Center\Services\GarageService;
use Throwable;

header('Content-Type: application/json; charset=utf-8');

/**
 * Единый AJAX-эндпоинт компонента "Гараж".
 *
 * Действия (параметр action):
 * - car_add / car_update / car_delete — CRUD автомобилей контакта;
 * - cars_list    — список автомобилей контакта;
 * - contacts_list — контакты (механикам — только свои по ASSIGNED_BY_ID);
 * - deal_info    — контакт и автомобиль сделки (режим просмотра);
 * - deal_check   — поиск открытой сделки-дубля по автомобилю;
 * - deal_presave — сохранить контакт/авто в сессию перед сабмитом формы.
 *
 * Ответ всегда JSON: {ok: bool, ...data, errors?: string[]}.
 */

/**
 * Отправка JSON-ответа и завершение.
 *
 * @param array $payload Данные ответа
 *
 * @return void
 */
function otus_sc_ajax_respond(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    die();
}

/**
 * Логирование фатальных ошибок эндпоинта (некритично для клиента).
 *
 * @param string    $context Действие, на котором упало
 * @param Throwable $e       Исключение
 *
 * @return void
 */
function otus_sc_ajax_log(string $context, Throwable $e): void
{
    @file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/local/logs/otus_sc_debug.log',
        date('Y-m-d H:i:s') . ' garage.ajax[' . $context . ']: '
        . get_class($e) . ': ' . $e->getMessage() . "\n",
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Является ли текущий пользователь механиком (группа "Механики").
 *
 * Механикам контакты фильтруются по ASSIGNED_BY_ID ("только свои"),
 * менеджерам/директору/админу — все контакты.
 *
 * @return bool true — пользователь в группе механиков и не админ
 */
function otus_sc_is_mechanic(): bool
{
    global $USER;

    if (!is_object($USER) || $USER->IsAdmin()) {
        return false;
    }

    $groupId = (int) Option::get(
        CrmHelper::MODULE_ID,
        CrmHelper::OPTION_GROUP_MECHANICS_ID,
        '0'
    );

    if ($groupId <= 0) {
        return false;
    }

    return in_array($groupId, array_map('intval', \CUser::GetUserGroup($USER->GetID())), true);
}

global $USER;

if (!$USER->IsAuthorized() || !check_bitrix_sessid()) {
    otus_sc_ajax_respond(['ok' => false, 'errors' => ['Access denied']]);
}

if (!Loader::includeModule('otus.service.center')) {
    otus_sc_ajax_respond(['ok' => false, 'errors' => ['Module otus.service.center is not available']]);
}

$action = (string) ($_REQUEST['action'] ?? '');
$service = new GarageService();

try {
    switch ($action) {
        case 'car_add':
        case 'car_update':
            $fields = $_POST;

            if (empty($fields['CONTACT_ID']) && !empty($fields['CLIENT_ID'])) {
                $fields['CONTACT_ID'] = (int) $fields['CLIENT_ID'];
            }

            $carId = (int) ($fields['ID'] ?? 0);

            $result = $action === 'car_add'
                ? $service->addCar($fields)
                : $service->updateCar($carId, $fields);

            if (!$result->isSuccess()) {
                otus_sc_ajax_respond(['ok' => false, 'errors' => $result->getErrorMessages()]);
            }

            $newId = (int) $result->getData()['ID'];
            $payload = ['ok' => true, 'data' => ['ID' => $newId]];

            if ($action === 'car_add') {
                $car = GarageTable::getList([
                    'filter' => ['=ID' => $newId],
                    'select' => ['ID', 'BRAND', 'MODEL', 'NUMBER'],
                ])->fetch();

                if ($car !== false) {
                    $payload['car'] = [
                        'id' => $newId,
                        'label' => $car['BRAND'] . ' ' . $car['MODEL'] . ', ' . $car['NUMBER'],
                    ];
                }
            }

            otus_sc_ajax_respond($payload);
            break;

        case 'car_delete':
            $result = $service->deleteCar((int) ($_REQUEST['ID'] ?? 0));

            if (!$result->isSuccess()) {
                otus_sc_ajax_respond(['ok' => false, 'errors' => $result->getErrorMessages()]);
            }

            otus_sc_ajax_respond(['ok' => true, 'data' => ['ID' => (int) ($_REQUEST['ID'] ?? 0)]]);
            break;

        case 'cars_list':
            $contactId = (int) ($_REQUEST['contact_id'] ?? $_REQUEST['CLIENT_ID'] ?? 0);
            $cars = [];

            if ($contactId > 0) {
                $result = $service->getCarsByContact($contactId);

                if ($result->isSuccess()) {
                    foreach ($result->getData() as $car) {
                        $cars[] = [
                            'id' => (int) $car['ID'],
                            'label' => $car['BRAND'] . ' ' . $car['MODEL'] . ', ' . $car['NUMBER'],
                        ];
                    }
                }
            }

            otus_sc_ajax_respond(['ok' => true, 'cars' => $cars]);
            break;

        case 'contacts_list':
            Loader::includeModule('crm');

            $filter = [];

            if (otus_sc_is_mechanic()) {
                $filter['=ASSIGNED_BY_ID'] = (int) $USER->GetID();
            }

            $rows = ContactTable::getList([
                'filter' => $filter,
                'select' => ['ID', 'NAME', 'LAST_NAME'],
                'order' => ['ID' => 'ASC'],
                'limit' => 50,
            ])->fetchAll();

            $contacts = [];

            foreach ($rows as $row) {
                $contacts[] = [
                    'id' => (int) $row['ID'],
                    'name' => '#' . (int) $row['ID'] . ' ' . trim($row['LAST_NAME'] . ' ' . $row['NAME']),
                ];
            }

            otus_sc_ajax_respond(['ok' => true, 'contacts' => $contacts]);
            break;

        case 'deal_info':
            Loader::includeModule('crm');

            $dealId = (int) ($_REQUEST['deal_id'] ?? 0);
            $contactId = 0;
            $carId = 0;
            $cars = [];

            if ($dealId > 0) {
                $deal = DealTable::getList([
                    'filter' => ['=ID' => $dealId],
                    'select' => ['ID', 'CONTACT_ID', CrmHelper::CAR_UF_FIELD],
                ])->fetch();

                if ($deal !== false) {
                    $contactId = (int) ($deal['CONTACT_ID'] ?? 0);
                    $carId = (int) ($deal[CrmHelper::CAR_UF_FIELD] ?? 0);

                    if ($contactId > 0) {
                        $result = $service->getCarsByContact($contactId);

                        if ($result->isSuccess()) {
                            foreach ($result->getData() as $car) {
                                $cars[] = [
                                    'id' => (int) $car['ID'],
                                    'label' => $car['BRAND'] . ' ' . $car['MODEL'] . ', ' . $car['NUMBER'],
                                ];
                            }
                        }
                    }
                }
            }

            otus_sc_ajax_respond([
                'ok' => true,
                'contactId' => $contactId,
                'carId' => $carId,
                'cars' => $cars,
            ]);
            break;

        case 'deal_check':
            Loader::includeModule('crm');

            $carId = (int) ($_REQUEST['car_id'] ?? 0);
            $dealId = (int) ($_REQUEST['deal_id'] ?? 0);
            $duplicate = null;

            if ($carId > 0) {
                $duplicate = DealSaveHandler::findDuplicate($carId, $dealId);
            }

            otus_sc_ajax_respond(['ok' => true, 'duplicate' => $duplicate]);
            break;

        case 'deal_presave':
            $_SESSION['OTUS_SC_PRESAVE'] = [
                'contact_id' => (int) ($_REQUEST['contact_id'] ?? 0),
                'car_id' => (int) ($_REQUEST['car_id'] ?? 0),
                'ts' => time(),
            ];
            otus_sc_ajax_respond(['ok' => true]);
            break;

        default:
            otus_sc_ajax_respond(['ok' => false, 'errors' => ['Unknown action: ' . $action]]);
    }
} catch (Throwable $e) {
    otus_sc_ajax_log($action, $e);
    otus_sc_ajax_respond(['ok' => false, 'errors' => ['FATAL: ' . $e->getMessage()]]);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
die();