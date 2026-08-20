<?php

declare(strict_types=1);

namespace Otus\Service\Center\Services;

use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Otus\Service\Center\Helpers\CrmHelper;

final class StockService
{
    private const SOURCE_URL = 'https://www.random.org/integers/?num=%d&min=0&max=20&col=1&base=10&format=plain&rnd=new';
    private const SOURCE_TIMEOUT = 5;

    /**
     * Конструктор: загружает модули CRM и catalog для работы с товарами.
     *
     * @return void
     *
     * @throws \Bitrix\Main\LoaderException Если один из модулей недоступен
     */
    public function __construct()
    {
        Loader::includeModule('crm');
        Loader::includeModule('catalog');
    }

    /**
     * Получение массива случайных целых чисел для обновления остатков.
     *
     * @param int $count Требуемое количество случайных чисел (> 0)
     *
     * @return array{values: array<int>, source: string} Массив значений и источник:
     *         - 'random.org' — успешный запрос к внешнему сервису;
     *         - 'fallback' — локальная генерация при недоступности random.org;
     *         - 'empty' — count <= 0
     */
    public function fetchStocks(int $count): array
    {
        if ($count <= 0) {
            return ['values' => [], 'source' => 'empty'];
        }

        $url = sprintf(self::SOURCE_URL, $count);
        $ctx = stream_context_create([
            'http' => ['timeout' => self::SOURCE_TIMEOUT, 'ignore_errors' => true],
        ]);

        $body = @file_get_contents($url, false, $ctx);

        if (is_string($body) && trim($body) !== '') {
            $lines = preg_split('/[\r\n]+/', trim($body));
            $values = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line !== '' && ctype_digit($line)) {
                    $values[] = (int) $line;
                }
            }

            if (count($values) === $count) {
                return ['values' => $values, 'source' => 'random.org'];
            }
        }

        $values = [];

        for ($i = 0; $i < $count; $i++) {
            $values[] = random_int(0, 20);
        }

        return ['values' => $values, 'source' => 'fallback'];
    }

    /**
     * Обновление штатных остатков всех активных товаров CRM.
     *
     * @return Result data: [
     *   'checked' => int,        — количество проверенных товаров
     *   'low' => array<array{ID:int, NAME:string, STOCK:int}>, — товары ниже порога
     *   'source' => string,      — источник чисел (random.org/fallback/none)
     * ]
     */
    public function updateStocks(): Result
    {
        $result = new Result();

        $products = $this->getProducts();

        if (empty($products)) {
            $result->setData(['checked' => 0, 'low' => [], 'source' => 'none']);

            return $result;
        }

        $fetched = $this->fetchStocks(count($products));
        $values = $fetched['values'];

        $low = [];

        foreach ($products as $i => $product) {
            $value = $values[$i] ?? 0;

            self::setStock((int) $product['ID'], (float) $value);

            if ($value <= CrmHelper::LOW_STOCK_THRESHOLD) {
                $low[] = [
                    'ID' => (int) $product['ID'],
                    'NAME' => (string) $product['NAME'],
                    'STOCK' => $value,
                ];
            }
        }

        $result->setData([
            'checked' => count($products),
            'low' => $low,
            'source' => $fetched['source'],
        ]);

        return $result;
    }

    /**
     * Текущий штатный остаток товара (QUANTITY из таблицы каталога).
     *
     * @param int $productId ID товара CRM
     *
     * @return float Текущий остаток (0.0 если товар не найден в каталоге)
     */
    public static function getStock(int $productId): float
    {
        Loader::includeModule('catalog');

        $row = \CCatalogProduct::GetByID($productId);

        return is_array($row) ? (float) $row['QUANTITY'] : 0.0;
    }

    /**
     * Увеличение штатного остатка товара на заданное количество.
     *
     * @param int $productId ID товара CRM
     * @param int $delta     Количество для добавления (> 0)
     *
     * @return bool true если остаток обновлён успешно
     */
    public static function increaseProductStock(int $productId, int $delta): bool
    {
        Loader::includeModule('catalog');

        $row = \CCatalogProduct::GetByID($productId);

        if (!is_array($row)) {
            return false;
        }

        return self::setStock($productId, (float) $row['QUANTITY'] + $delta);
    }

    /**
     * Запись значения QUANTITY в таблицу каталога.
     *
     * @param int   $productId ID товара CRM
     * @param float $value     Новое значение остатка
     *
     * @return bool true если операция успешна
     */
    private static function setStock(int $productId, float $value): bool
    {
        Loader::includeModule('catalog');

        if (\CCatalogProduct::Update($productId, ['QUANTITY' => $value])) {
            return true;
        }

        return (bool) \CCatalogProduct::Add(['ID' => $productId, 'QUANTITY' => $value]);
    }

    /**
     * Получение всех активных товаров CRM.
     *
     * @return array<int, array{ID: int, NAME: string}> Массив товаров (без позиций — только ID и NAME)
     */
    private function getProducts(): array
    {
        $db = \CCrmProduct::GetList(['ID' => 'ASC'], ['ACTIVE' => 'Y'], ['ID', 'NAME'], false);

        $rows = [];

        while ($row = $db->Fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }
}