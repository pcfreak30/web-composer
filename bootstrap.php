<?php

if (!class_exists('Aura\Autoload\Loader')) {
    require_once __DIR__ . '/Loader.php';

    $web_composer_autoloader = new Aura\Autoload\Loader();
    $web_composer_autoloader->addPrefix('Unirest', __DIR__ . '/vendors/Unirest');
    $web_composer_autoloader->addPrefix('pcfreak30', __DIR__ . '/src');
    $web_composer_autoloader->register();
}
