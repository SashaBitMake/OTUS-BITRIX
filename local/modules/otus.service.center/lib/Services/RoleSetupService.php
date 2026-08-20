<?php

declare(strict_types=1);

namespace Otus\Service\Center\Services;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Otus\Service\Center\Helpers\CrmHelper;

/**
 * Структура компании по ТЗ (idempotent): группы + демо-пользователи.
 *
 * CRM-роли программно НЕ создаются: модель ролей этого ядра
 * (PermissionLevel / AttrPreset) несовместима с сырыми вставками
 * в b_crm_role_perms (ArgumentOutOfRangeException при входе под ролью).
 * Роли настраиваются вручную через интерфейс:
 * CRM → Настройки → Разрешения → Роли (см. docs/USER.md):
 *   - Механик  — уровень доступа "только свои" (сделки/контакты);
 *   - Менеджер — "все" сделки/контакты;
 *   - Директор — полный доступ;
 *   привязка — к группам, созданным этим сервисом.
 */
final class RoleSetupService
{
    private const DEMO_PASSWORD = 'otus2026!';

    private const GROUPS = [
        [
            'code' => 'otus_sc_mechanics',
            'name' => 'Механики',
            'desc' => 'Видят только свои сделки и своих клиентов.',
            'option' => CrmHelper::OPTION_GROUP_MECHANICS_ID,
        ],
        [
            'code' => 'otus_sc_managers',
            'name' => 'Менеджеры',
            'desc' => 'Все сделки + доступ к "Гаражу" клиентов.',
            'option' => CrmHelper::OPTION_GROUP_MANAGERS_ID,
        ],
        [
            'code' => 'otus_sc_directors',
            'name' => 'Директор',
            'desc' => 'Полный доступ.',
            'option' => CrmHelper::OPTION_GROUP_DIRECTOR_ID,
        ],
        [
            'code' => 'otus_sc_purchase',
            'name' => 'Отдел закупок',
            'desc' => 'Обработка заявок на закупку.',
            'option' => CrmHelper::OPTION_PURCHASE_GROUP_ID,
        ],
        [
            'code' => 'otus_sc_purchase_head',
            'name' => 'Начальник отдела закупок',
            'desc' => 'Получает копии уведомлений по заявкам (fallback по ТЗ п.5.2).',
            'option' => CrmHelper::OPTION_PURCHASE_HEAD_GROUP_ID,
        ],
    ];

    private const DEMO_USERS = [
        ['login' => 'mechanic1', 'name' => 'Иван', 'last' => 'Механиков', 'group' => 'otus_sc_mechanics'],
        ['login' => 'mechanic2', 'name' => 'Сергей', 'last' => 'Гаев', 'group' => 'otus_sc_mechanics'],
        ['login' => 'manager1', 'name' => 'Пётр', 'last' => 'Менеджеров', 'group' => 'otus_sc_managers'],
        ['login' => 'director1', 'name' => 'Игорь', 'last' => 'Директоров', 'group' => 'otus_sc_directors'],
        ['login' => 'purchaser', 'name' => 'Павел', 'last' => 'Закупщиков', 'group' => 'otus_sc_purchase'],
        ['login' => 'purchase_head', 'name' => 'Андрей', 'last' => 'Начальников', 'group' => 'otus_sc_purchase_head'],
    ];

    /**
     * Idempotent-настройка структуры компании: группы + демо-пользователи.
     *
     * @return Result Успех или ошибки создания групп/пользователей
     */
    public function setup(): Result
    {
        $result = new Result();

        if (!Loader::includeModule('main')) {
            $result->addError(new Error('Модуль main недоступен.'));

            return $result;
        }

        $groupIds = $this->ensureGroups($result);
        $this->ensureUsers($groupIds, $result);

        return $result;
    }

    /**
     * Создание групп пользователей.
     *
     * @param Result $result Result для добавления ошибок создания групп
     *
     * @return array<string, int> Карта code => ID группы
     */
    private function ensureGroups(Result $result): array
    {
        $ids = [];

        foreach (self::GROUPS as $def) {
            $id = (int) Option::get(CrmHelper::MODULE_ID, $def['option'], '0');

            if ($id > 0 && \CGroup::GetByID($id)->Fetch() !== false) {
                $ids[$def['code']] = $id;
                continue;
            }

            $byCode = \CGroup::GetList('', '', ['STRING_ID' => $def['code']])->Fetch();

            if ($byCode !== false) {
                Option::set(CrmHelper::MODULE_ID, $def['option'], (string) $byCode['ID']);
                $ids[$def['code']] = (int) $byCode['ID'];
                continue;
            }

            $group = new \CGroup();
            $newId = $group->Add([
                'NAME' => $def['name'],
                'STRING_ID' => $def['code'],
                'DESCRIPTION' => $def['desc'],
                'ACTIVE' => 'Y',
            ]);

            if (!$newId) {
                $result->addError(new Error(
                    'Не удалось создать группу "' . $def['name'] . '"'
                    . ($group->LAST_ERROR !== '' ? ': ' . $group->LAST_ERROR : '')
                ));
                continue;
            }

            Option::set(CrmHelper::MODULE_ID, $def['option'], (string) $newId);
            $ids[$def['code']] = (int) $newId;
        }

        return $ids;
    }

    /**
     * Создание демо-пользователей с привязкой к группам (идемпотентно).
     *
     * @param array<string, int> $groupIds Карта code => ID группы из ensureGroups()
     * @param Result             $result   Result для добавления ошибок создания пользователей
     *
     * @return void
     */
    private function ensureUsers(array $groupIds, Result $result): void
    {
        foreach (self::DEMO_USERS as $def) {
            $existing = \CUser::GetByLogin($def['login'])->Fetch();

            if ($existing !== false) {
                continue;
            }

            $groupId = $groupIds[$def['group']] ?? 0;
            $user = new \CUser();
            $newId = $user->Add([
                'LOGIN' => $def['login'],
                'PASSWORD' => self::DEMO_PASSWORD,
                'CONFIRM_PASSWORD' => self::DEMO_PASSWORD,
                'NAME' => $def['name'],
                'LAST_NAME' => $def['last'],
                'EMAIL' => $def['login'] . '@otus-sc.example',
                'ACTIVE' => 'Y',
                'GROUP_ID' => $groupId > 0 ? [$groupId] : [],
            ]);

            if (!$newId) {
                $result->addError(new Error(
                    'Не удалось создать пользователя "' . $def['login'] . '"'
                    . ($user->LAST_ERROR !== '' ? ': ' . $user->LAST_ERROR : '')
                ));
            }
        }
    }
}