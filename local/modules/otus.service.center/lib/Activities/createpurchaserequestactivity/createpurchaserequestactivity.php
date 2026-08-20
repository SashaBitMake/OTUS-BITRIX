<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CreatePurchaseRequestActivity extends CBPActivity
{
    /** ID заявки. */
    public $RequestId = 0;

    /** Авто-одобрение (Y/N). */
    public $AutoApprove = 'N';

    /**
     * Инициализация activity: код, название и описание из lang-файлов.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->Code = 'otus_create_purchase_request';
        $this->Name = Loc::getMessage('OTUS_SC_BP_ACTIVITY_NAME');
        $this->Description = Loc::getMessage('OTUS_SC_BP_ACTIVITY_DESC');
    }

    /**
     * Выполнение activity в бизнес-процессе.
     *
     * @return int Статус завершения activity (CBPActivityExecutionStatus::Closed)
     */
    public function Execute()
    {
        Loader::includeModule('otus.service.center');

        $requestId = (int) $this->RequestId;

        if ($requestId > 0) {
            $service = new \Otus\Service\Center\Services\PurchaseService();
            $result = $service->processByActivity($requestId, $this->AutoApprove === 'Y');

            $this->WriteToTrackingService(
                $result->isSuccess()
                    ? 'Заявка #' . $requestId . ' обработана activity.'
                    : 'Заявка #' . $requestId . ': ' . implode(', ', $result->getErrorMessages())
            );
        }

        return CBPActivityExecutionStatus::Closed;
    }
}

if (!class_exists('Createpurchaserequestactivity', false)) {
    class_alias('CreatePurchaseRequestActivity', 'Createpurchaserequestactivity');
}