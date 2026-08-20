<?php

declare(strict_types=1);

namespace Otus\Service\Center\Services;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Otus\Service\Center\Helpers\CrmHelper;
use Throwable;

Loc::loadMessages(__FILE__);

final class CrmSetupService
{
    /**
     * Полный набор настройки CRM-инфраструктуры.
     *
     * Идемпотентно: повторный вызов не создаёт дубликатов.
     * Ошибки создания UF-поля не бросаются, а собираются в Result.
     *
     * @return Result Успех или список ошибок настройки
     */
    public function setup(): Result
    {
        $result = new Result();

        try {
            $this->ensureCarUserField();
        } catch (Throwable $e) {
            $result->addError(new Error($e->getMessage()));
        }

        return $result;
    }

    /**
     * Создаёт UF-поле "Автомобиль" (UF_CRM_OTUS_SC_CAR, тип integer)
     * у сделок, если оно ещё не существует.
     *
     * @return void
     *
     * @throws \Bitrix\Main\SystemException Если создать поле не удалось
     */
    private function ensureCarUserField(): void
    {
        $existing = \CUserTypeEntity::GetList(
            [],
            [
                'ENTITY_ID' => CrmHelper::CRM_DEAL_ENTITY,
                'FIELD_NAME' => CrmHelper::CAR_UF_FIELD,
            ]
        )->Fetch();

        if ($existing !== false) {
            return;
        }

        $userTypeEntity = new \CUserTypeEntity();

        $addField = $userTypeEntity->Add([
            'ENTITY_ID' => CrmHelper::CRM_DEAL_ENTITY,
            'FIELD_NAME' => CrmHelper::CAR_UF_FIELD,
            'USER_TYPE_ID' => 'integer',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'Y',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL' => ['ru' => Loc::getMessage('OTUS_SC_SETUP_UF_LABEL'), 'en' => 'Car'],
            'LIST_COLUMN_LABEL' => ['ru' => Loc::getMessage('OTUS_SC_SETUP_UF_LABEL'), 'en' => 'Car'],
            'LIST_FILTER_LABEL' => ['ru' => Loc::getMessage('OTUS_SC_SETUP_UF_LABEL'), 'en' => 'Car'],
        ]);

        if ($addField === false) {
            global $APPLICATION;
            $exception = $APPLICATION->GetException();

            throw new \Bitrix\Main\SystemException(
                'UF field create failed: ' . ($exception !== false ? $exception->GetString() : 'unknown error')
            );
        }
    }
}