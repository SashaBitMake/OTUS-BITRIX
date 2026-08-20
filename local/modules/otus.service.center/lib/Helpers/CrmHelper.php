<?php

declare(strict_types=1);

namespace Otus\Service\Center\Helpers;

use Bitrix\Main\Config\Option;

/**
 * Единый реестр констант модуля (смарт-процесс удалён из архитектуры).
 */
final class CrmHelper
{
    /** ID модуля. */
    public const MODULE_ID = 'otus.service.center';

    /** Имя опции модуля: ID сервисной воронки сделок (CATEGORY_ID). */
    public const OPTION_SERVICE_CATEGORY_ID = 'service_category_id';

    /** Имя UF-поля сделки: привязка к автомобилю из гаража. */
    public const CAR_UF_FIELD = 'UF_CRM_OTUS_SC_CAR';

    /** ID сущности CRM для UF-полей сделок (CUserTypeEntity ENTITY_ID). */
    public const CRM_DEAL_ENTITY = 'CRM_DEAL';

    /** Устаревшее UF-поле остатка (для удаления). */
    public const UF_PRODUCT_STOCK = 'UF_CRM_OTUS_SC_STOCK';

    /** Устаревшее UF-поле порога (для удаления). */
    public const UF_PRODUCT_MIN_STOCK = 'UF_CRM_OTUS_SC_MIN_STOCK';

    /**
     * Порог авто-заявки по ТЗ: запчасть кончилась (количество = 0).
     */
    public const LOW_STOCK_THRESHOLD = 0;

    /** Статусы заявок. */
    public const REQUEST_STAGE_NEW = 'NEW';
    public const REQUEST_STAGE_APPROVED = 'APPROVED';
    public const REQUEST_STAGE_REJECTED = 'REJECTED';

    /** Пополнение при авто-закупке (ТЗ: 10 единиц). */
    public const AGENT_REPLENISH_QUANTITY = 10;

    /** Опция: ID группы "Отдел закупок". */
    public const OPTION_PURCHASE_GROUP_ID = 'purchase_group_id';

    /** Опция: ID highload-блока заявок (носитель БП). */
    public const OPTION_PURCHASE_HL_ID = 'purchase_hl_id';

    /** Опция: ID шаблона БП закупок (настраивается в дизайнере). */
    public const OPTION_PURCHASE_BP_TEMPLATE_ID = 'purchase_bp_template_id';

    /** Опция: ID группы "Механики". */
    public const OPTION_GROUP_MECHANICS_ID = 'group_mechanics_id';

    /** Опция: ID группы "Менеджеры". */
    public const OPTION_GROUP_MANAGERS_ID = 'group_managers_id';

    /** Опция: ID группы "Директор". */
    public const OPTION_GROUP_DIRECTOR_ID = 'group_director_id';

    /** Опция: ID группы "Начальник отдела закупок" (fallback по ТЗ п.5.2). */
    public const OPTION_PURCHASE_HEAD_GROUP_ID = 'purchase_head_group_id';

    private function __construct()
    {
    }
}