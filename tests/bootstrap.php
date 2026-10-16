<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

$app = new Container();
$configData = require __DIR__ . '/../config/mfs-sms.php';
$config = new Repository(['mfs-sms' => $configData]);

$app->instance('config', $config);
$app->instance('app', $app);

Container::setInstance($app);
Facade::setFacadeApplication($app);
