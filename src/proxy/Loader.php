<?php namespace proxy;

use pocketmine\utils\Config;

CONST COMPOSER = "vendor/autoload.php";

if(!is_file(COMPOSER)){
    echo "Composer autoloader not found" . PHP_EOL;
    exit(127);
}
/** @noinspection PhpIncludeInspection */
require_once (COMPOSER);

$settings = [
    'server-ip' => 'pe.gameteam.cz',
    'server-port' => 19132,
    'bind-port' => 19132
];
$config = new Config("config.yml", Config::YAML, $settings);
$all = $config->getAll();
try{
    new ProxyServer($all['server-ip'], $all['server-port'], $all['bind-port']);
}catch (\Exception $e){

}


