<?php

declare(strict_types=1);

namespace Otus\Service\Center\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * ORM-таблица гаража: автомобили клиентов.
 *
 * Схема соответствует install/db/mysql/install.sql:
 * otus_sc_garage(ID, CONTACT_ID, BRAND, MODEL, NUMBER, YEAR, COLOR, MILEAGE).
 *
 * Константы FIELD_* — публичный контракт для компонента, шаблона и сервисов
 * (используются вместо строковых литералов).
 *
 * Валидация — статическими методами, возвращающими массив валидаторов
 * (штатный паттерн D7).
 */
class GarageTable extends DataManager
{
    public const FIELD_ID = 'ID';
    public const FIELD_CONTACT_ID = 'CONTACT_ID';
    public const FIELD_BRAND = 'BRAND';
    public const FIELD_MODEL = 'MODEL';
    public const FIELD_NUMBER = 'NUMBER';
    public const FIELD_YEAR = 'YEAR';
    public const FIELD_COLOR = 'COLOR';
    public const FIELD_MILEAGE = 'MILEAGE';

    /**
     * Имя таблицы в БД.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'otus_sc_garage';
    }

    /**
     * Карта полей сущности.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            new IntegerField(self::FIELD_ID, [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new IntegerField(self::FIELD_CONTACT_ID, [
                'required' => true,
            ]),
            new StringField(self::FIELD_BRAND, [
                'required' => true,
                'validation' => [self::class, 'validateBrand'],
            ]),
            new StringField(self::FIELD_MODEL, [
                'required' => true,
                'validation' => [self::class, 'validateModel'],
            ]),
            new StringField(self::FIELD_NUMBER, [
                'validation' => [self::class, 'validateNumber'],
            ]),
            new IntegerField(self::FIELD_YEAR),
            new StringField(self::FIELD_COLOR, [
                'validation' => [self::class, 'validateColor'],
            ]),
            new IntegerField(self::FIELD_MILEAGE),
        ];
    }

    /**
     * @return array<LengthValidator>
     */
    public static function validateBrand(): array
    {
        return [new LengthValidator(1, 100)];
    }

    /**
     * @return array<LengthValidator>
     */
    public static function validateModel(): array
    {
        return [new LengthValidator(1, 100)];
    }

    /**
     * Необязательное поле: ограничиваем только максимальную длину.
     *
     * @return array<LengthValidator>
     */
    public static function validateNumber(): array
    {
        return [new LengthValidator(null, 20)];
    }

    /**
     * @return array<LengthValidator>
     */
    public static function validateColor(): array
    {
        return [new LengthValidator(null, 50)];
    }
}