<?php

namespace Otus\TCrm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class CrmDataTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENTITY_ID int mandatory
 * <li> NAME string(255) mandatory
 * <li> VALUE text optional
 * </ul>
 *
 * @package Bitrix\Tab
 **/

class CrmDataTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'otus_tab_crm_data';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => (new IntegerField('ID',
					[]
				))->configureTitle(Loc::getMessage('CRM_DATA_ENTITY_ID_FIELD'))
						->configurePrimary(true)
						->configureAutocomplete(true)
			,
			'ENTITY_ID' => (new IntegerField('ENTITY_ID',
					[]
				))->configureTitle(Loc::getMessage('CRM_DATA_ENTITY_ENTITY_ID_FIELD'))
						->configureRequired(true)
			,
			'NAME' => (new StringField('NAME',
					[
						'validation' => [__CLASS__, 'validateName']
					]
				))->configureTitle(Loc::getMessage('CRM_DATA_ENTITY_NAME_FIELD'))
						->configureRequired(true)
			,
			'VALUE' => (new TextField('VALUE',
					[]
				))->configureTitle(Loc::getMessage('CRM_DATA_ENTITY_VALUE_FIELD'))
			,
		];
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName(): array
	{
		return [
			new LengthValidator(null, 255),
		];
	}
}
