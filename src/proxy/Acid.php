<?php

declare(strict_types=1);

namespace proxy;

use pocketmine\utils\Config;

define("COMPOSER", "vendor/autoload.php");

$extensions = ["pthreads", "sockets", "yaml"];

foreach ($extensions as $extension) {
    if(!extension_loaded($extension)) {
        echo "Could not start server: extension not found.";
        exit;
    }
}

if(!is_file(COMPOSER)){
    echo "Composer autoloader not found, install composer first." . PHP_EOL;
    exit;
}

// class loader
require_once COMPOSER;

$settings = [
    'server-ip' => 'pe.gameteam.cz', # sorry Honzo :D
    'server-port' => 19132,
    'bind-port' => 19132
];

$config = new Config("config.yml", Config::YAML, $settings);
$all = $config->getAll();

try {
    new ProxyServer((string)$all['server-ip'], (int)$all['server-port'], (int)$all['bind-port']);
}
catch (\Exception $exception){
    echo "Could not start server:" . $exception->getMessage();
}

