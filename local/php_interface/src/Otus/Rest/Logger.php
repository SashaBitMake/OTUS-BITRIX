<?php

namespace Otus\Rest;

class Logger
{
    private const LOG_DIR = '/local/logs/otus_rest/';

    public static function log(string $method, array $payload): void
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . self::LOG_DIR;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            file_put_contents($dir . '.htaccess', "Deny from all\n");
        }

        $file = $dir . date('Y-m-d') . '.log';

        $line = sprintf(
            "[%s] %s | %s\n",
            date('Y-m-d H:i:s'),
            $method,
            json_encode(self::sanitize($payload), JSON_UNESCAPED_UNICODE)
        );

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Убираем потенциально чувствительные поля перед записью в лог
     * (токены, пароли и т.п.), если они вдруг попадут в параметры.
     */
    private static function sanitize(array $payload): array
    {
        $blacklist = ['auth', 'access_token', 'refresh_token', 'password'];

        array_walk_recursive($payload, function (&$value, $key) use ($blacklist) {
            if (in_array(strtolower((string)$key), $blacklist, true)) {
                $value = '***';
            }
        });

        return $payload;
    }
}
