<?php

defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');
defined('CI_DEBUG') || define('CI_DEBUG', true);
defined('ENVIRONMENT') || define('ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'production');

if (! defined('ROOTPATH')) {
    define('ROOTPATH', realpath(FCPATH . '../') . DIRECTORY_SEPARATOR);
}

if (! defined('APPPATH')) {
    define('APPPATH', realpath(FCPATH . '../app/') . DIRECTORY_SEPARATOR);
}

if (! defined('WRITEPATH')) {
    define('WRITEPATH', realpath(FCPATH . '../writable/') . DIRECTORY_SEPARATOR);
}

if (! defined('SYSTEMPATH')) {
    define('SYSTEMPATH', realpath(FCPATH . '../vendor/codeigniter4/framework/system/') . DIRECTORY_SEPARATOR);
}

if (! defined('EXIT_SUCCESS')) {
    define('EXIT_SUCCESS', 0);
}

if (! defined('EXIT_ERROR')) {
    define('EXIT_ERROR', 1);
}

if (! defined('EXIT_CONFIG')) {
    define('EXIT_CONFIG', 3);
}

if (! defined('EXIT_UNKNOWN_FILE')) {
    define('EXIT_UNKNOWN_FILE', 4);
}

if (! defined('EXIT_UNKNOWN_CLASS')) {
    define('EXIT_UNKNOWN_CLASS', 5);
}

if (! defined('EXIT_UNKNOWN_METHOD')) {
    define('EXIT_UNKNOWN_METHOD', 6);
}

if (! defined('EXIT_USER_INPUT')) {
    define('EXIT_USER_INPUT', 7);
}

if (! defined('EXIT_DATABASE')) {
    define('EXIT_DATABASE', 8);
}