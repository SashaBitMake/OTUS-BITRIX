<?php
// /local/activities/custom/dadatafindcompany/dadatafindcompany.php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!class_exists('TooManyRequests')) {
    /**
     * Исключение, выбрасываемое при превышении лимита запросов к API DaData.
     */
    class TooManyRequests extends \Exception
    {
    }
}


/**
 * Действие бизнес-процесса для поиска компании по ИНН через сервис DaData.
 *
 * @property string $Title Название действия
 * @property string $DadataToken API-ключ DaData
 * @property string $DadataSecret Секретный ключ DaData
 * @property string $CompanyInn ИНН компании для поиска
 * @property string $CompanyName Название найденной компании (исходящий параметр)
 * @property string $CompanyAddress Адрес найденной компании (исходящий параметр)
 */
class CBPDadatafindcompanyActivity extends BaseActivity
{
    /**
     * Конструктор действия.
     * Инициализирует свойства по умолчанию.
     *
     * @param string $name Уникальное имя действия в шаблоне БП.
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = [
            "Title" => "",
            "DadataToken" => "",
            "DadataSecret" => "",
            "CompanyInn" => "",
            "CompanyName" => "",
            "CompanyAddress" => "",
        ];
    }

    /**
     * Возвращает путь к текущему файлу действия.
     * Используется ядром BizProc для локализации и поиска сопутствующих файлов.
     *
     * @return string Полный путь к файлу.
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [
            'DadataToken' => [
                'Name' => Loc::getMessage('DADATA_FIND_COMPANY_TOKEN'),
                'FieldName' => 'dadata_token',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
            'DadataSecret' => [
                'Name' => Loc::getMessage('DADATA_FIND_COMPANY_SECRET'),
                'FieldName' => 'dadata_secret',
                'Type' => FieldType::STRING,
                'Required' => false,
            ],
            'CompanyInn' => [
                'Name' => Loc::getMessage('DADATA_FIND_COMPANY_INN'),
                'FieldName' => 'company_inn',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
        ];
    }

    /**
     * Основной метод выполнения действия.
     * Выполняет запрос к API DaData, обрабатывает результат и записывает данные в переменные БП.
     *
     * @return ErrorCollection Коллекция ошибок, возникших при выполнении.
     */
    protected function internalExecute(): ErrorCollection
    {
        $errorCollection = parent::internalExecute();

        $token = trim((string)$this->DadataToken);
        $secret = trim((string)$this->DadataSecret);
        $inn = trim((string)$this->CompanyInn);

        if (empty($token)) {
            $errorCollection->setError(new Error(Loc::getMessage('DADATA_FIND_COMPANY_ERROR_EMPTY_TOKEN')));
            return $errorCollection;
        }

        if (empty($inn)) {
            $errorCollection->setError(new Error(Loc::getMessage('DADATA_FIND_COMPANY_ERROR_EMPTY_INN')));
            return $errorCollection;
        }

        try {
            $dadata = new Dadata($token, $secret);
            $dadata->init();
            
            $result = $dadata->findById("party", ["query" => $inn]);
            $dadata->close();

            if (!empty($result['suggestions'])) {
                $company = $result['suggestions'][0];
                
                $companyName = $company['value'] ?? '';
                $companyAddress = $company['data']['address']['value'] ?? '';
                $companyInnResult = $company['data']['inn'] ?? '';

                $this->CompanyName = $companyName;
                $this->CompanyAddress = $companyAddress;

                $rootActivity = $this->GetRootActivity();
                if ($rootActivity) {
                    $rootActivity->SetVariable("CompanyName", $companyName);
                    $rootActivity->SetVariable("CompanyAdress", $companyAddress);
                    $rootActivity->SetVariable("CompanyINN", $companyInnResult);
                }

                $this->log('Найдена компания: ' . $companyName . '.');
                
            } else {
                $errorCollection->setError(new Error(Loc::getMessage('DADATA_FIND_COMPANY_ERROR_NOT_FOUND', ['#INN#' => $inn])));
            }
        } catch (\TooManyRequests $e) {
            $errorCollection->setError(new Error(Loc::getMessage('DADATA_FIND_COMPANY_ERROR_TOO_MANY_REQUESTS')));
        } catch (\Exception $e) {
            $errorCollection->setError(new Error($e->getMessage()));
        }

        return $errorCollection;
    }
}