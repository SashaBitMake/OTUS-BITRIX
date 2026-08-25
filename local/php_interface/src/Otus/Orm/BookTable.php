<?php

namespace Otus\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

class BookTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'otus_book';
    }

    /**
     * @return string
     */
    public static function getUfId(): string
    {
        return 'OTUS_BOOK';
    }

    /**
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID',
            ]),

            new StringField('TITLE', [
                'required' => true,
                'validation' => [__CLASS__, 'validateTitle'],
                'title' => 'Название книги',
            ]),

            new StringField('AUTHOR', [
                'required' => false,
                'validation' => [__CLASS__, 'validateAuthor'],
                'title' => 'Автор',
            ]),

            new IntegerField('YEAR', [
                'required' => false,
                'title' => 'Год издания',
            ]),

            new IntegerField('CREATED_BY', [
                'required' => true,
                'title' => 'Кем создано',
            ]),

            new DatetimeField('DATE_CREATE', [
                'default_value' => new DateTime(),
                'title' => 'Дата создания',
            ]),

            new DatetimeField('DATE_UPDATE', [
                'default_value' => new DateTime(),
                'title' => 'Дата обновления',
            ]),

            new StringField('ISBN', [
                'required' => false,
                'validation' => [__CLASS__, 'validateIsbn'],
                'title' => 'ISBN',
            ]),
        ];
    }

    /**
     * @return array
     */
    public static function validateTitle(): array
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * @return array
     */
    public static function validateAuthor(): array
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * @return array
     */
    public static function validateIsbn(): array
    {
        return [
            new LengthValidator(null, 20),
        ];
    }
}