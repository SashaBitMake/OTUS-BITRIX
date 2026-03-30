<?php

return [
    'exception_handling' => [
        'value'    => [
            'debug'                      => true,
            'handled_errors_types'       => E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
            'exception_errors_types'     => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
            'ignore_silence'             => false,
            'assertion_throws_exception' => true,
            'assertion_error_type'       => 256,
            'log'                        => [
                'class_name'    => \App\Debug\Log::class, //  пользовательский класс, наследуемый от \ExceptionHandlerLog. Может быть не указан. В этом случае будет использоваться \Bitrix\Main\Diag\FileExceptionHandlerLog.
                'settings'      => [
                    //настройки для класса, указанного в class_name
                    'file'     => 'local/logs/exceptions.log',
                    'log_size' => 1000000,
                ],
            ],
        ],
        'readonly' => false,
    ],
];