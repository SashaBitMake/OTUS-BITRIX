<?php

declare(strict_types=1);

namespace Otus\Service\Center\Agents;

use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Otus\Service\Center\Helpers\CrmHelper;
use Otus\Service\Center\Services\PurchaseService;
use Otus\Service\Center\Services\StockService;

final class StockUpdateAgent
{
    public static function run(): string
    {
        self::execute();

        return '\\Otus\\Service\\Center\\Agents\\StockUpdateAgent::run();';
    }

    /**
     * @return Result data: [checked, low, autoCreated, source]
     */
    public static function execute(): Result
    {
        Loader::includeModule('crm');
        Loader::includeModule('otus.service.center');

        $stock = new StockService();
        $update = $stock->updateStocks();

        if (!$update->isSuccess()) {
            return $update;
        }

        $data = $update->getData();

        $purchase = new PurchaseService();
        $autoCreated = 0;

        foreach ($data['low'] as $row) {
            $ar = $purchase->createAutomaticRequest(
                $row['ID'],
                CrmHelper::AGENT_REPLENISH_QUANTITY
            );

            if ($ar->isSuccess()) {
                $autoCreated++;
            }
        }

        $data['autoCreated'] = $autoCreated;
        $update->setData($data);

        return $update;
    }
}