<?php

spl_autoload_register(function ($className) {
    // $classPath = str_replace('\\', '/', $className);
    // $file      = __DIR__ . "/$classPath.php";
    // if (file_exists($file))
    // {
    //     include_once $file;
    // }



    if (!str_contains($className, 'App'))
    {
        return;
    }


    $className = str_replace('App', '', $className);
    $className = str_replace('\\', '/', $className);

    $filePath = __DIR__ .  $className . '.php';

    if (file_exists($filePath))
    {
        require_once $filePath;
    }

});
