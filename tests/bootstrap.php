<?php

require dirname(__DIR__) . '/vendor/autoload.php';

Kirby\Cms\App::$enableWhoops = false;

require dirname(__DIR__) . '/index.php';
