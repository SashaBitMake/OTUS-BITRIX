<?php

namespace App\Debug;

/*
 * \App\Debug\Log::addLog('OnBeforeHLEAdd');
 */

use Bitrix\Main\Diag\ExceptionHandlerFormatter;
use Bitrix\Main\Diag\FileExceptionHandlerLog;

class Log extends FileExceptionHandlerLog
{

    private $level;

    /**
     * Запись в лог
     *
     * @param           $message
     * @param   false   $clear
     * @param   string  $fileName
     *
     * @return void
     */
    public static function addLog($message, bool $clear = false, string $fileName = 'custom', $timeVersion = true): void
    {
        $logFile = $_SERVER["DOCUMENT_ROOT"] . '/local/logs/' . $fileName;

        if ($timeVersion) {
            $logFile .= '_' . date("d.m.Y");
        }
        $logFile .= '.log';

        $_message = date("d.m.Y H:i:s");
        $_message .= "\n";
        $_message .= print_r($message, true);
        $_message .= "\n";
        $_message .= "---";
        $_message .= "\n";

        if ($clear)
        {
            file_put_contents($logFile, $_message);
        }
        else
        {
            file_put_contents($logFile, $_message, FILE_APPEND);
        }
    }

    public static function cleanLog(string $fileName = 'custom') {
        $logFile = $_SERVER["DOCUMENT_ROOT"] . '/local/logs/' . $fileName;
        $logFile .= '.log';
        file_put_contents($logFile, '');
    }

    /**
     * Запись в лог
     *
     * @param $exception
     * @param $logType
     *
     * @return void
     */

	public function write($exception, $logType)
	{
		$text = ExceptionHandlerFormatter::format($exception, false, $this->level);

		$context = [
			'type' => static::logTypeToString($logType),
		];

		$logLevel = static::logTypeToLevel($logType);

		$message = "OTUS {date} - Host: {host} - {type} - {$text}\n";

		$this->logger->log($logLevel, $message, $context);
	}
}


