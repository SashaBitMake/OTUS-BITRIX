<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime as BitrixDateTime;
use App\Models\Lists\BookingPropertyValuesTable;

header('Content-Type: application/json');

if (!check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo Json::encode(['status' => 'error', 'message' => 'Доступ запрещен.']);
    die();
}

if (!Loader::includeModule('iblock')) {
    echo Json::encode(['status' => 'error', 'message' => 'Не подключен модуль инфоблоков.']);
    die();
}

$request = Context::getCurrent()->getRequest();

$doctorId = (int)$request->getPost('doctor_id');
$procedure = trim((string)$request->getPost('procedure'));
$patientFio = trim((string)$request->getPost('patient_fio'));
$bookingTimeRaw = trim((string)$request->getPost('booking_time'));

if (!$doctorId || empty($procedure) || empty($patientFio) || empty($bookingTimeRaw)) {
    echo Json::encode(['status' => 'error', 'message' => 'Все поля обязательны к заполнению.']);
    die();
}

try {
    $phpDateTime = new \DateTime($bookingTimeRaw);
    $bookingDateTime = BitrixDateTime::createFromPhp($phpDateTime);
} catch (\Exception $e) {
    echo Json::encode(['status' => 'error', 'message' => 'Некорректный формат времени записи.']);
    die();
}

/**
 * Проверка времени на занятость
 */
try {
    $existBooking = BookingPropertyValuesTable::getList([
        'select' => ['IBLOCK_ELEMENT_ID'],
        'filter' => [
            '=DOCTOR' => $doctorId,
            '=BOOKING_TIME' => $bookingDateTime->toString()
        ],
        'limit' => 1
    ])->fetch();

    if ($existBooking) {
        echo Json::encode([
            'status' => 'error',
            'message' => 'Это время у выбранного специалиста уже занято. Укажите другое время.'
        ]);
        die();
    }
} catch (\Exception $e) {
    echo Json::encode([
        'status' => 'error',
        'message' => 'Ошибка при обращении к базе данных: ' . $e->getMessage()
    ]);
    die();
}

/**
 * Создание записи через наследуемый метод add()
 */
$fields = [
    'NAME' => "Запись: {$patientFio} - {$procedure}",
    'PATIENT_FIO' => $patientFio,
    'BOOKING_TIME' => $bookingDateTime->toString(),
    'PROCEDURE' => $procedure,
    'DOCTOR' => $doctorId
];

if (BookingPropertyValuesTable::add($fields)) {
    echo Json::encode([
        'status' => 'success',
        'message' => 'Вы успешно записаны на процедуру!'
    ]);
} else {
    echo Json::encode([
        'status' => 'error',
        'message' => 'Не удалось создать бронирование. Проверьте заполнение полей.'
    ]);
}
die();