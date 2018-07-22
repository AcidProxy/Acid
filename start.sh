#!/usr/bin/env bash

if [ ! -f ./bin/ ]; then
    echo "PHP Binary not found, installing php..."
    wget https://jenkins.pmmp.io/job/PHP-7.2-Linux-x86_64/lastSuccessfulBuild/artifact/PHP_Linux-x86_64.tar.gz;
    echo "Extracting php binary..."
    tar -xzf PHP_Linux-x86_64.tar.gz
    if [ -f ./bin/ ]; then
        echo "PHP successfully installed!";
    fi
fi

if [ ! -f ./bin/php7/bin/php ]; then
    echo "Could not start server: PHP not found."
    exit 1
fi

if [ ! -f ./composer.json ]; then
    ./bin/php7/bin/php ./install/composer_install.php
fi

if [ ! -f ./vendor/autoload.php ]; then
    echo "Installing composer, after installation run again ./start.sh"
    ./bin/composer install
    exit 1
fi

./bin/php7/bin/php ./src/proxy/Acid.php