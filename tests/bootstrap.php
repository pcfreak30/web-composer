<?php
function error_handler($errno, $errstr, $errfile, $errline)
{
    throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
}

set_error_handler('error_handler');
require_once __DIR__ . '/../bootstrap.php';

if (file_exists(getenv('COMPOSER_HOME') . '/vendor/autoload.php')) {
    require_once getenv('COMPOSER_HOME') . '/vendor/autoload.php';
}

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
