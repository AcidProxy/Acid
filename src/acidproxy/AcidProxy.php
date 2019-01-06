<?php

declare(strict_types=1);

namespace acidproxy;

use acidproxy\utils\Logger;
use Composer\Autoload\ClassLoader;

define("acidproxy\START_TIME", microtime(true));
define("acidproxy\DATA", getcwd() . DIRECTORY_SEPARATOR);
define("acidproxy\PLUGIN_PATH", getcwd() . DIRECTORY_SEPARATOR . "plugins");
define("acidproxy\VERSION", "1.0.0");

define("COMPOSER", "vendor/autoload.php");

/**
 * @var ClassLoader $loader
 *
 * @noinspection PhpIncludeInspection
 */
$loader = require COMPOSER;

$logger = new Logger();

$extensions = [
    "pthreads",
    "sockets",
    "yaml"
];

foreach ($extensions as $extension) {
    if (!extension_loaded($extension)) {
        echo "Could not start server: extension not found.";
        exit;
    }
}

if (!is_file(COMPOSER)) {
    echo "Composer autoloader not found, install composer first." . PHP_EOL;
    exit;
}

new ProxyServer($logger);