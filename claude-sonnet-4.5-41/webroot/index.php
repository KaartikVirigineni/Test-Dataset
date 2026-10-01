<?php

require dirname(__DIR__) . '/config/bootstrap.php';

use Cake\Http\Server;

$server = new Server(new \App\Application(dirname(__DIR__) . '/config'));
$server->run();