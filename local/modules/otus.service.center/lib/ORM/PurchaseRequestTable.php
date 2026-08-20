<?php

declare(strict_types=1);

namespace Otus\Service\Center\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

class PurchaseRequestTable extends DataManager
{
    public const FIELD_ID = 'ID';
    public const FIELD_TITLE = 'TITLE';
    public const FIELD_STATUS = 'STATUS';
    public const FIELD_AUTHOR_ID = 'AUTHOR_ID';
    public const FIELD_PROCESSED_BY_ID = 'PROCESSED_BY_ID';
    public const FIELD_PROCESSED_AT = 'PROCESSED_AT';
    public const FIELD_REJECT_REASON = 'REJECT_REASON';
    public const FIELD_IS_AUTOMATIC = 'IS_AUTOMATIC';
    public const FIELD_CREATED_AT = 'CREATED_AT';

    /**
     * Имя таблицы в БД.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'otus_sc_purchase_request';
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
                'autocomplete' => true
            ]),
            new StringField(self::FIELD_TITLE, [
                'required' => true
            ]),
            new StringField(self::FIELD_STATUS, [
                'required' => true,
                'default_value' => 'NEW'
            ]),
            new IntegerField(self::FIELD_AUTHOR_ID, [
                'default_value' => 0
            ]),
            new IntegerField(self::FIELD_PROCESSED_BY_ID, [
                'default_value' => 0
            ]),
            new DatetimeField(self::FIELD_PROCESSED_AT, [
                'default_value' => null
            ]),
            new TextField(self::FIELD_REJECT_REASON, [
                'default_value' => null
            ]),
            new StringField(self::FIELD_IS_AUTOMATIC, [
                'default_value' => 'N'
            ]),
            new DatetimeField(self::FIELD_CREATED_AT, [
                'default_value' => null
            ]),
        ];
    }
}