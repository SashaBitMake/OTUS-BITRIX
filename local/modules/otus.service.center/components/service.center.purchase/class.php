<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Otus\Service\Center\Helpers\CrmHelper;
use Otus\Service\Center\Services\PurchaseService;

class ServiceCenterPurchaseComponent extends CBitrixComponent
{
    /**
     * Подготовка данных для шаблона: список заявок или карточка одной.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule('otus.service.center')) {
            ShowError('Модуль otus.service.center не установлен.');

            return;
        }

        global $USER;

        $userId = (int) $USER->GetID();
        $service = new PurchaseService();

        $this->arResult['USER_ID'] = $userId;
        $this->arResult['IS_STAFF'] = $this->isStaff($userId);
        $this->arResult['REQUEST_ID'] = (int) ($_REQUEST['request_id'] ?? 0);
        $this->arResult['STATUS_LABELS'] = [
            CrmHelper::REQUEST_STAGE_NEW => 'Новая',
            CrmHelper::REQUEST_STAGE_APPROVED => 'Выполнена',
            CrmHelper::REQUEST_STAGE_REJECTED => 'Отклонена',
        ];

        if ($this->arResult['REQUEST_ID'] > 0) {
            $request = $service->getRequest($this->arResult['REQUEST_ID']);

            $this->arResult['REQUEST'] = $request !== null ? $this->decorate($request) : null;
            $items = $service->getItems($this->arResult['REQUEST_ID']);
            $this->arResult['ITEMS'] = $items;
            $this->arResult['ITEMS_MAP'] = [$this->arResult['REQUEST_ID'] => $items];
        } else {
            $requests = [];
            $map = [];

            foreach ($service->getRequests() as $request) {
                $requests[] = $this->decorate($request);
                $map[$request['ID']] = $service->getItems($request['ID']);
            }

            $this->arResult['REQUESTS'] = $requests;
            $this->arResult['ITEMS_MAP'] = $map;
        }

        $this->includeComponentTemplate();
    }

    /**
     * Обогащение данных заявки для шаблона: автор и обработчик — ФИО.
     *
     * @param array $request Сырые данные из PurchaseService::getRequest()
     * @return array Заявка с полями AUTHOR_NAME и PROCESSED_NAME
     */
    private function decorate(array $request): array
    {
        $request['AUTHOR_NAME'] = $request['AUTHOR_ID'] > 0
            ? $this->userName($request['AUTHOR_ID'])
            : 'Система';

        $request['PROCESSED_NAME'] = $request['PROCESSED_BY_ID'] > 0
            ? $this->userName($request['PROCESSED_BY_ID'])
            : '';

        return $request;
    }

    /**
     * Имя пользователя в формате «Фамилия Имя» (или логин, если ФИО нет).
     *
     * @param int $userId ID пользователя
     * @return string Человекочитаемое имя или «#ID» при отсутствии записи
     */
    private function userName(int $userId): string
    {
        $user = \CUser::GetByID($userId)->Fetch();

        if (!$user) {
            return '#' . $userId;
        }

        $name = trim(($user['LAST_NAME'] ?? '') . ' ' . ($user['NAME'] ?? ''));

        return $name !== '' ? $name : (string) $user['LOGIN'];
    }

    /**
     * Проверка членства пользователя в группе «Отдел закупок».
     *
     * @param int $userId ID пользователя (0 — гость, возвращает false)
     * @return bool true — пользователь состоит в группе закупок
     */
    private function isStaff(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $groupId = (int) Option::get(
            CrmHelper::MODULE_ID,
            CrmHelper::OPTION_PURCHASE_GROUP_ID,
            '0'
        );

        if ($groupId <= 0) {
            return false;
        }

        return in_array($groupId, array_map('intval', \CUser::GetUserGroup($userId)), true);
    }
}