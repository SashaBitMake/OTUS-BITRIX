<?php

declare(strict_types=1);

namespace Otus\Service\Center\Services;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Otus\Service\Center\ORM\GarageTable;
use Throwable;

final class GarageService
{
    /**
     * Список автомобилей контакта с сортировкой.
     *
     * @param int   $contactId ID контакта CRM (должен быть > 0)
     * @param array $sort      Сортировка в формате ORM: ['FIELD' => 'ASC'|'DESC']
     *
     * @return Result data: array<array{
     *   ID: int,
     *   CONTACT_ID: int,
     *   BRAND: string,
     *   MODEL: string,
     *   NUMBER: string,
     *   YEAR: int,
     *   COLOR: string,
     *   MILEAGE: int
     * }>
     */
    public function getCarsByContact(int $contactId, array $sort = []): Result
    {
        $result = new Result();

        if ($contactId <= 0) {
            $result->addError(new Error('Некорректный идентификатор контакта.'));

            return $result;
        }

        if (empty($sort)) {
            $sort = ['ID' => 'ASC'];
        }

        try {
            $rows = GarageTable::getList([
                'filter' => ['=CONTACT_ID' => $contactId],
                'select' => ['ID', 'CONTACT_ID', 'BRAND', 'MODEL', 'NUMBER', 'YEAR', 'COLOR', 'MILEAGE'],
                'order' => $sort,
            ])->fetchAll();

            $result->setData($rows);
        } catch (Throwable $e) {
            $result->addError(new Error('Не удалось загрузить список автомобилей.'));
        }

        return $result;
    }

    /**
     * Получение одного автомобиля по ID.
     *
     * @param int $carId ID автомобиля из таблицы otus_sc_garage (должен быть > 0)
     *
     * @return Result data: array{
     *   ID: int,
     *   CONTACT_ID: int,
     *   BRAND: string,
     *   MODEL: string,
     *   NUMBER: string,
     *   YEAR: int,
     *   COLOR: string,
     *   MILEAGE: int
     * } или ошибка "Автомобиль не найден." при отсутствии записи
     */
    public function getCar(int $carId): Result
    {
        $result = new Result();

        if ($carId <= 0) {
            $result->addError(new Error('Некорректный ID автомобиля.'));

            return $result;
        }

        try {
            $row = GarageTable::getList([
                'filter' => ['=ID' => $carId],
                'select' => ['ID', 'CONTACT_ID', 'BRAND', 'MODEL', 'NUMBER', 'YEAR', 'COLOR', 'MILEAGE'],
            ])->fetch();

            if ($row === false) {
                $result->addError(new Error('Автомобиль не найден.'));

                return $result;
            }

            $result->setData($row);
        } catch (Throwable $e) {
            $result->addError(new Error('Не удалось загрузить автомобиль.'));
        }

        return $result;
    }

    /**
     * Добавляет автомобиль в гараж контакта с валидацией обязательных полей.
     *
     * @param array $fields Поля автомобиля:
     *                      - CONTACT_ID: int — ID контакта CRM (обязательный, > 0)
     *                      - BRAND: string — марка (обязательный, 1–100 символов)
     *                      - MODEL: string — модель (обязательный, 1–100 символов)
     *                      - NUMBER: string — гос. номер (опционально, до 20 символов)
     *                      - YEAR: int — год выпуска (опционально)
     *                      - COLOR: string — цвет (опционально, до 50 символов)
     *                      - MILEAGE: int — пробег в км (опционально)
     *
     * @return Result data: ['ID' => int] — ID созданной записи
     */
    public function addCar(array $fields): Result
    {
        $result = new Result();

        $contactId = (int) ($fields['CONTACT_ID'] ?? 0);
        $brand = trim((string) ($fields['BRAND'] ?? ''));
        $model = trim((string) ($fields['MODEL'] ?? ''));

        if ($contactId <= 0) {
            $result->addError(new Error('Не указан контакт.'));

            return $result;
        }

        if ($brand === '' || $model === '') {
            $result->addError(new Error('Марка и модель обязательны.'));

            return $result;
        }

        try {
            $add = GarageTable::add([
                'CONTACT_ID' => $contactId,
                'BRAND' => $brand,
                'MODEL' => $model,
                'NUMBER' => trim((string) ($fields['NUMBER'] ?? '')),
                'YEAR' => (int) ($fields['YEAR'] ?? 0),
                'COLOR' => trim((string) ($fields['COLOR'] ?? '')),
                'MILEAGE' => (int) ($fields['MILEAGE'] ?? 0),
            ]);

            if (!$add->isSuccess()) {
                foreach ($add->getErrors() as $error) {
                    $result->addError($error);
                }

                return $result;
            }

            $result->setData(['ID' => (int) $add->getId()]);
        } catch (Throwable $e) {
            $result->addError(new Error('Не удалось добавить автомобиль: ' . $e->getMessage()));
        }

        return $result;
    }

    /**
     * Обновление автомобиля с валидацией обязательных полей.
     *
     * @param int   $carId  ID автомобиля из таблицы otus_sc_garage (должен быть > 0)
     * @param array $fields Обновляемые поля (те же ключи, что у addCar, кроме CONTACT_ID):
     *                      - BRAND: string — марка (обязательный)
     *                      - MODEL: string — модель (обязательный)
     *                      - NUMBER, YEAR, COLOR, MILEAGE — опциональные
     *
     * @return Result data: ['ID' => int] — ID обновлённой записи
     */
    public function updateCar(int $carId, array $fields): Result
    {
        $result = new Result();

        if ($carId <= 0) {
            $result->addError(new Error('Некорректный ID автомобиля.'));

            return $result;
        }

        $brand = trim((string) ($fields['BRAND'] ?? ''));
        $model = trim((string) ($fields['MODEL'] ?? ''));

        if ($brand === '' || $model === '') {
            $result->addError(new Error('Марка и модель обязательны.'));

            return $result;
        }

        try {
            $update = GarageTable::update($carId, [
                'BRAND' => $brand,
                'MODEL' => $model,
                'NUMBER' => trim((string) ($fields['NUMBER'] ?? '')),
                'YEAR' => (int) ($fields['YEAR'] ?? 0),
                'COLOR' => trim((string) ($fields['COLOR'] ?? '')),
                'MILEAGE' => (int) ($fields['MILEAGE'] ?? 0),
            ]);

            if (!$update->isSuccess()) {
                foreach ($update->getErrors() as $error) {
                    $result->addError($error);
                }

                return $result;
            }

            $result->setData(['ID' => $carId]);
        } catch (Throwable $e) {
            $result->addError(new Error('Не удалось обновить автомобиль: ' . $e->getMessage()));
        }

        return $result;
    }

    /**
     * Удаление автомобиля из гаража контакта.
     *
     * @param int $carId ID автомобиля из таблицы otus_sc_garage (должен быть > 0)
     *
     * @return Result data: ['ID' => int] — ID удалённой записи
     */
    public function deleteCar(int $carId): Result
    {
        $result = new Result();

        if ($carId <= 0) {
            $result->addError(new Error('Некорректный ID автомобиля.'));

            return $result;
        }

        try {
            $delete = GarageTable::delete($carId);

            if (!$delete->isSuccess()) {
                foreach ($delete->getErrors() as $error) {
                    $result->addError($error);
                }

                return $result;
            }

            $result->setData(['ID' => $carId]);
        } catch (Throwable $e) {
            $result->addError(new Error('Не удалось удалить автомобиль: ' . $e->getMessage()));
        }

        return $result;
    }
}