<?php

declare(strict_types=1);

namespace Otus\Service\Center\Services;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Otus\Service\Center\Helpers\CrmHelper;

/**
 * Товары-запчасти демо-стенда + очистка устаревших UF-полей.
 *
 * Idempotent создаёт 5 товаров CRM через CCrmProduct (без вариаций),
 * чтобы весь контур остатков — агент, авто-заявки, +10 при одобрении,
 * отображение "Доступного остатка" в CRM — проверялся на товарах,
 * которые модуль контролирует сам (как демо-пользователи в RoleSetupService).
 *
 * Товары, созданные вручную через UI с вариациями (SKU), остаются
 * краевым случаем: их остаток живёт в офферах (см. docs/TECHNICAL.md).
 */
final class PartsSetupService
{
    private const PARTS = [
        ['xml' => 'otus_sc_part_brake_pads', 'name' => 'Колодки тормозные', 'price' => 2500],
        ['xml' => 'otus_sc_part_oil', 'name' => 'Масло моторное 5W-40', 'price' => 3200],
        ['xml' => 'otus_sc_part_oil_filter', 'name' => 'Фильтр масляный', 'price' => 700],
        ['xml' => 'otus_sc_part_spark_plug', 'name' => 'Свеча зажигания', 'price' => 450],
        ['xml' => 'otus_sc_part_muffler', 'name' => 'Глушитель спортивный', 'price' => 12000],
    ];

    /**
     * Idempotent-настройка: удаление старых UF + создание демо-товаров.
     *
     * @return Result Успех или ошибки создания товаров
     */
    public function setup(): Result
    {
        $result = new Result();

        if (!Loader::includeModule('crm')) {
            $result->addError(new Error('Модуль CRM недоступен.'));

            return $result;
        }

        $this->removeProductUf(CrmHelper::UF_PRODUCT_STOCK);
        $this->removeProductUf(CrmHelper::UF_PRODUCT_MIN_STOCK);
        $this->ensureParts($result);

        return $result;
    }

    /**
     * Создаёт 5 товаров-запчастей, если их ещё нет (поиск по XML_ID).
     *
     * @param Result $result Result для добавления ошибок создания
     *
     * @return void
     */
    private function ensureParts(Result $result): void
    {
        foreach (self::PARTS as $part) {
            $existing = \CCrmProduct::GetList([], ['=XML_ID' => $part['xml']], ['ID'])->Fetch();

            if ($existing !== false) {
                continue;
            }

            $newId = \CCrmProduct::Add([
                'NAME' => $part['name'],
                'XML_ID' => $part['xml'],
                'ACTIVE' => 'Y',
                'PRICE' => $part['price'],
                'CURRENCY_ID' => 'RUB',
            ]);

            if ((int) $newId <= 0) {
                $result->addError(new Error('Не удалось создать товар "' . $part['name'] . '"'));
            }
        }
    }

    /**
     * Удаляет UF-поле товара вместе с колонкой, если оно существует.
     *
     * @param string $name Имя UF-поля (например, 'UF_CRM_OTUS_SC_STOCK')
     *
     * @return void
     */
    private function removeProductUf(string $name): void
    {
        $userType = new \CUserTypeEntity();

        $db = $userType->GetList([], ['ENTITY_ID' => 'CRM_PRODUCT', 'FIELD_NAME' => $name]);
        $row = $db->Fetch();

        if ($row) {
            $userType->Delete((int) $row['ID']);
        }
    }
}