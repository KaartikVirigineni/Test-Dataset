<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__));
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('APP', ROOT . DS . 'src' . DS);
define('APP_DIR', 'src');
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('CONFIG', ROOT . DS . 'config' . DS);
define('CACHE', TMP . 'cache' . DS);
define('LOGS', ROOT . DS . 'logs' . DS);
define('RESOURCES', ROOT . DS . 'resources' . DS);

require ROOT . DS . 'vendor' . DS . 'autoload.php';

require CORE_PATH . 'config' . DS . 'bootstrap.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

Configure::write(require CONFIG . 'app.php');

Cache::setConfig([
    '_cake_core_' => [
        'className' => 'File',
        'prefix' => 'myapp_cake_core_',
        'path' => CACHE . 'persistent/',
        'serialize' => true,
        'duration' => '+1 years',
    ],
    '_cake_model_' => [
        'className' => 'File',
        'prefix' => 'myapp_cake_model_',
        'path' => CACHE . 'models/',
        'serialize' => true,
        'duration' => '+1 years',
    ],
]);

Log::setConfig(Configure::consume('Log'));

ConnectionManager::setConfig(Configure::consume('Datasources'));