<?php
namespace Microcms;

error_reporting(E_ALL);
ini_set('display_errors', true);

define('DOC_ROOT', realpath(__DIR__ . '/../..'));
define("DOC_PATH", (substr(DOC_ROOT, strlen($_SERVER['DOCUMENT_ROOT'])) ?: '/'));

require_once 'classes/Error.php';
require_once 'classes/Tools.php';
require_once 'classes/Registry.php';
require_once 'classes/Init.php';


$conf_file = DOC_ROOT . "/conf.ini";
if ( ! file_exists($conf_file)) {
    throw new \Exception("Missing configuration file '{$conf_file}'.");
}

$config_inline = [
    'system' => [
        'name'     => '',
        'cache'    => 'cache',
        'host'     => ! empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '',
        'timezone' => '',
        'debug'    => [
            'on' => false
        ],
        'tmp'      => sys_get_temp_dir() ?: "/tmp"
    ]
];

if ( ! empty($_SERVER['SERVER_NAME'])) {
    $config_ini = Tools::getConfig($conf_file);
    $config_ini = isset($config_ini[$_SERVER['SERVER_NAME']])
        ? $config_ini[$_SERVER['SERVER_NAME']]
        : $config_ini['production'];
} else {
    $config_ini = Tools::getConfig($conf_file, 'production');
}


$config = array_replace_recursive($config_inline, $config_ini);



//определяем имя секции для cli режима
if (PHP_SAPI === 'cli') {
    $options = getopt('m:a:s:', [
        'method:',
        'argument:',
        'section:'
    ]);
    if (( ! empty($options['section']) && is_string($options['section'])) || ( ! empty($options['s']) && is_string($options['s']))) {
        $_SERVER['SERVER_NAME'] = ! empty($options['section']) ? $options['section'] : $options['s'];
    }
}


// отладка приложения
if ($config['system'] &&
    $config['system']['debug'] &&
    $config['system']['debug']['on']
) {
    ini_set('display_errors', true);
} else {
    ini_set('display_errors', false);
}


// определяем путь к папке кеша
if ($config['system'] &&
    $config['system']['cache']
) {
    if (strpos($config['system']['cache'], '/') !== 0) {
        $config['system']['cache'] = DOC_ROOT . '/' . trim($config['system']['cache'], "/");
    }
}


//сохраняем конфиг
Registry::set('config', json_decode(json_encode($config)));



$conf_file = __DIR__ . "/../vendor/autoload.php";
if ( ! file_exists($conf_file)) {
    throw new \Exception("Composer autoload is missing.");
}

require_once $conf_file;