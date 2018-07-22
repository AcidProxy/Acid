@echo off

title AcidProxy - starting

if not exist bin\php\php.exe (
    echo Could not start proxy: PHP not found
    sleep
    exit
)

if not exist composer.json (
    bin\php\php.exe install\composer_install.php
)

if not exist vendor\autoload.php (
    echo Installing composer, after installation run again start.cmd
    bin\composer.bat install
    exit
)

bin\php\php.exe src\proxy\Acid.php
