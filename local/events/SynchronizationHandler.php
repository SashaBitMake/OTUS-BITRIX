<?php

namespace Local\Events;

use Bitrix\Main\Loader;
use Bitrix\Crm\DealTable;


class SynchronizationHandler
{
    private const IBLOCK_ID = 23;
    private static bool $isProcessing = false;

    /**
     * Синхронизация: Из Заявки (инфоблок) в Сделку CRM.
     *
     * @param array $fields Поля изменяемого элемента.
     * @return void
     */
    public static function onApplicationUpdate(array &$fields): void
    {
        if (self::$isProcessing || (int)$fields['IBLOCK_ID'] !== self::IBLOCK_ID) {
            return;
        }

        if (isset($fields['RESULT']) && $fields['RESULT'] === false) {
            return;
        }

        $elementId = (int)$fields['ID'];
        if ($elementId <= 0 || !Loader::includeModule('crm') || !Loader::includeModule('iblock')) {
            return;
        }

        $dealId = 0;
        $price = null;

        $dbProps = \CIBlockElement::GetProperty(self::IBLOCK_ID, $elementId, [], []);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['CODE'] === 'DEALONCRM') {
                $rawDealValue = (string)$prop['VALUE'];
                if (str_starts_with($rawDealValue, 'D_')) {
                    $dealId = (int)substr($rawDealValue, 2);
                } else {
                    $dealId = (int)$rawDealValue;
                }
            }
            if ($prop['CODE'] === 'DEALSUMM') {
                $price = $prop['VALUE'];
            }
        }

        if ($dealId > 0 && !is_null($price)) {
            $currentDeal = DealTable::query()
                ->setSelect(['OPPORTUNITY'])
                ->setFilter(['=ID' => $dealId])
                ->exec()
                ->fetch();

            if ($currentDeal && (float)$currentDeal['OPPORTUNITY'] === (float)$price) {
                return;
            }

            self::$isProcessing = true;

            $dealFields = [
                'OPPORTUNITY' => $price,
            ];

            $deal = new \CCrmDeal(false);
            $deal->Update($dealId, $dealFields, true, true, ['DISABLE_USER_FIELD_CHECK' => true]);

            self::$isProcessing = false;
        }
    }

    /**
     * Синхронизация: Из Сделки CRM в Заявку (инфоблок).
     *
     * @param mixed $eventДанные события (array или \Bitrix\Main\Event).
     * @return void
     */
    public static function onDealUpdate($event): void
    {
        if (self::$isProcessing) {
            return;
        }

        $dealId = 0;

        if (is_array($event)) {
            $dealId = isset($event['ID']) ? (int)$event['ID'] : 0;
        } elseif ($event instanceof \Bitrix\Main\Event) {
            $parameters = $event->getParameters();
            $dealId = isset($parameters['id']) ? (int)$parameters['id'] : 0;
        }

        if ($dealId <= 0) {
            return;
        }

        if (!Loader::includeModule('crm') || !Loader::includeModule('iblock')) {
            return;
        }

        $dealData = DealTable::query()
            ->setSelect(['ID', 'OPPORTUNITY'])
            ->setFilter(['=ID' => $dealId])
            ->exec()
            ->fetch();

        if (!$dealData) {
            return;
        }

        $currentOpportunity = $dealData['OPPORTUNITY'];

        $elementRes = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                [
                    'LOGIC' => 'OR',
                    ['=PROPERTY_DEALONCRM' => $dealId],
                    ['=PROPERTY_DEALONCRM' => 'D_' . $dealId],
                ]
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        );

        if ($element = $elementRes->Fetch()) {
            $elementId = (int)$element['ID'];

            $dbPropPrice = \CIBlockElement::GetProperty(self::IBLOCK_ID, $elementId, [], ['CODE' => 'DEALSUMM']);
            if ($propPrice = $dbPropPrice->Fetch()) {
                if (!is_null($propPrice['VALUE']) && (float)$propPrice['VALUE'] === (float)$currentOpportunity) {
                    return;
                }
            }

            self::$isProcessing = true;

            \CIBlockElement::SetPropertyValuesEx($elementId, self::IBLOCK_ID, [
                'DEALSUMM' => $currentOpportunity
            ]);

            \CIBlock::clearIblockTagCache(self::IBLOCK_ID);

            self::$isProcessing = false;
        }
    }
}