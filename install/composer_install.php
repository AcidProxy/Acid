<?php

declare(strict_types=1);

$path = str_replace("\\", DIRECTORY_SEPARATOR, getcwd() . DIRECTORY_SEPARATOR);

if(!is_file($path . "composer.json")) {
    echo "-> Composer settings not found, downloading composer.json" . PHP_EOL;

    try {
        file_put_contents($path. "composer.json", fopen("https://raw.githubusercontent.com/GamakCZ/Acid/master/composer.json", "r"));
    }
    catch (Exception $exception) {
        echo "-> Error while downloading composer.json: " . $exception->getMessage() . PHP_EOL;
        echo $exception->getTraceAsString();
        exit;
    }
}
