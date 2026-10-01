<?php

defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');
defined('CI_DEBUG') || define('CI_DEBUG', true);
defined('ENVIRONMENT') || define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT'] ?? 'production');

if (! defined('ROOTPATH')) {
    define('ROOTPATH', realpath(FCPATH . '../') . DIRECTORY_SEPARATOR);
}

if (! defined('APPPATH')) {
    define('APPPATH', realpath(ROOTPATH . 'app') . DIRECTORY_SEPARATOR);
}

if (! defined('WRITEPATH')) {
    define('WRITEPATH', realpath(ROOTPATH . 'writable') . DIRECTORY_SEPARATOR);
}

if (! defined('SYSTEMPATH')) {
    define('SYSTEMPATH', realpath(ROOTPATH . 'vendor/codeigniter4/framework/system') . DIRECTORY_SEPARATOR);
}

if (! defined('COMPOSER_PATH')) {
    define('COMPOSER_PATH', realpath(ROOTPATH . 'vendor/autoload.php'));
}

defined('EXIT_SUCCESS') || define('EXIT_SUCCESS', 0);
defined('EXIT_ERROR') || define('EXIT_ERROR', 1);
defined('EXIT_CONFIG') || define('EXIT_CONFIG', 3);
defined('EXIT_UNKNOWN_FILE') || define('EXIT_UNKNOWN_FILE', 4);
defined('EXIT_UNKNOWN_CLASS') || define('EXIT_UNKNOWN_CLASS', 5);
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6);
defined('EXIT_USER_INPUT') || define('EXIT_USER_INPUT', 7);
defined('EXIT_DATABASE') || define('EXIT_DATABASE', 8);
defined('EXIT__AUTO_MIN') || define('EXIT__AUTO_MIN', 9);
defined('EXIT__AUTO_MAX') || define('EXIT__AUTO_MAX', 125);