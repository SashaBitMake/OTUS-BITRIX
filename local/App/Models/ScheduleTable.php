<?php

namespace App\Models;

use App\Models\StudentsValuesTable as Students;
use App\Models\ClassroomsValuesTable as Rooms;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator,
    Bitrix\Main\ORM\Fields\Validators\RegExpValidator,
    Bitrix\Main\ORM\Fields\Relations\Reference,
    Bitrix\Main\ORM\Fields\Relations\OneToMany,
    Bitrix\Main\ORM\Fields\Relations\ManyToMany,
	Bitrix\Main\ORM\Fields\FloatField,
    Bitrix\Main\Entity\Query\Join;

/**
 * Class Table
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> student_id int optional
 * <li> classroom_id int optional
 * <li> time_class string(50) optional
 * </ul>
 *
 * @package Bitrix\
 **/

class ScheduleTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'schedule';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'id' => (new IntegerField('id',
					[]
				))->configureTitle(Loc::getMessage('_ENTITY_ID_FIELD'))
						->configurePrimary(true)
						->configureAutocomplete(true)
			,
			'student_id' => (new IntegerField('student_id',
					[]
				))->configureTitle(Loc::getMessage('_ENTITY_STUDENT_ID_FIELD'))
			,
			'classroom_id' => (new IntegerField('classroom_id',
					[]
				))->configureTitle(Loc::getMessage('_ENTITY_CLASSROOM_ID_FIELD'))
			,
			'time_class' => (new StringField('time_class',
					[
						'validation' => [__CLASS__, 'validateTimeClass']
					]
				))->configureTitle(Loc::getMessage('_ENTITY_TIME_CLASS_FIELD'))
			,
            (new Reference(
                'STUDENT',
                Students::class, 
                Join::on('this.student_id', 'ref.IBLOCK_ELEMENT_ID') 
            ))->configureJoinType('inner'),
            
            (new Reference(
                'CLASSROOM',
                Rooms::class,
                Join::on('this.classroom_id', 'ref.IBLOCK_ELEMENT_ID')
            ))->configureJoinType('inner'),
		];
	}

	/**
	 * Returns validators for time_class field.
	 *
	 * @return array
	 */
	public static function validateTimeClass(): array
	{
		return [
			new LengthValidator(null, 50),
		];
	}
}