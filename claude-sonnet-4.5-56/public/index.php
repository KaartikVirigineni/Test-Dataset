<?php

use CodeIgniter\CodeIgniter;

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once dirname(__DIR__) . '/app/Config/Paths.php';

$paths = new Config\Paths();

$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

$app->run();