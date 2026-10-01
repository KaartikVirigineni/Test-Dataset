<?php

use CodeIgniter\Boot\BootProduction;

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

chdir(__DIR__);

require realpath(ROOTPATH = dirname(__DIR__)) . '/vendor/autoload.php';

$app = new BootProduction();
$app->run();