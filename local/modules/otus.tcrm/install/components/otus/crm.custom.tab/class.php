<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Otus\TCrm\CrmDataTable;
use Bitrix\Main\Grid\Options;

class OtusCrmCustomTabComponent extends CBitrixComponent
{
    protected $gridId = 'otus_custom_data_grid';

    /**
     * Основной исполняемый метод компонента.
     *
     * Точка входа, вызываемая ядром при подключении компонента.
     * Выполняет проверку зависимостей, формирование колонок, получение 
     * параметров сортировки и выборку данных (ROWS) для передачи в шаблон.
     *
     * @return void
     */
    
    public function executeComponent()
    {
        if (!Loader::includeModule('otus.tcrm')) {
            return;
        }

        $entityId = (int)$this->arParams['ENTITY_ID'];

        if ($entityId <= 0) {
            echo '<div style="color:red; padding: 20px;">Ошибка: не передан ENTITY_ID</div>';
            return;
        }

        $this->arResult['GRID_ID'] = $this->gridId;

        $this->arResult['COLUMNS'] = [
            ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true],
            ['id' => 'NAME', 'name' => 'Имя', 'sort' => 'NAME', 'default' => true],
            ['id' => 'VALUE', 'name' => 'Значение', 'sort' => 'VALUE', 'default' => true],
        ];

        $gridOptions = new Options($this->gridId);
        $sortData = $gridOptions->GetSorting([
            'sort' => ['ID' => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order']
        ]);
        
        $this->arResult['SORT'] = $sortData['sort'];
        $this->arResult['SORT_VARS'] = $sortData['vars'];

        $this->arResult['ROWS'] = [];
        
        if (class_exists('Otus\TCrm\CrmDataTable')) {
            $iterator = CrmDataTable::getList([
                'filter' => ['ENTITY_ID' => $entityId],
                'select' => ['ID', 'NAME', 'VALUE'],
                'order'  => $this->arResult['SORT']
            ]);

            while ($row = $iterator->fetch()) {
                $this->arResult['ROWS'][] = [
                    'id' => $row['ID'],
                    'data' => $row,
                    'columns' => $row,
                ];
            }
        }

        $this->includeComponentTemplate();
    }
}