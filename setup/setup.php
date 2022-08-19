<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/SetupCommand.php';

use Symfony\Component\Console\Application;

$app = new Application();
$app->add(new SetupCommand());
$app->run();