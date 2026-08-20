<?php

declare(strict_types=1);

namespace Otus\Service\Center\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

class PurchaseRequestItemTable extends DataManager
{
    public const FIELD_ID = 'ID';
    public const FIELD_REQUEST_ID = 'REQUEST_ID';
    public const FIELD_PRODUCT_ID = 'PRODUCT_ID';
    public const FIELD_QUANTITY = 'QUANTITY';

    /**
     * Имя таблицы в БД.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'otus_sc_purchase_request_items';
    }

    /**
     * Карта полей сущности: ID, REQUEST_ID (шапка заявки),
     * PRODUCT_ID (товар CRM), QUANTITY (количество).
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
            new IntegerField(self::FIELD_REQUEST_ID, [
                'required' => true
            ]),
            new IntegerField(self::FIELD_PRODUCT_ID, [
                'required' => true
            ]),
            new IntegerField(self::FIELD_QUANTITY, [
                'required' => true
            ]),
        ];
    }
}