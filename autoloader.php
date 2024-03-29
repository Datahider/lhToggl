<?php

//define('__LIB_ROOT__', '/Users/drweb_000/Desktop/MyData/phplib');
define('__LIB_ROOT__', 'D:/MyData/phplib');

spl_autoload_register(function ($class) {
    $suggested = [
        __LIB_ROOT__ . "/lhTestingSuite/classes/$class.php",
        __DIR__ . "/lhToggl/abstract/$class.php",
        __DIR__ . "/lhToggl/classes/$class.php"
    ];
    
    foreach ($suggested as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

