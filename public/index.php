<?php

require __DIR__.'/../vendor/autoload.php';
define('APP_ROOT_DIR', __DIR__.'/../');

require_once(APP_ROOT_DIR.'Controller.php');
$config = require_once(APP_ROOT_DIR.'config.php');

$f3 = \Base::instance();
$f3->config = $config;
$f3->TEMP = APP_ROOT_DIR.'tmp/';
$f3->LOGS = APP_ROOT_DIR.'logs/';
$f3->DEBUG = 0;
$f3->UI = APP_ROOT_DIR.'ui/';

$f3->set('db', new DB\SQL('mysql:host='.$config['db_connection']['host'].';port='.$config['db_connection']['port'].';dbname='.$config['db_connection']['database'].';charset=UTF8', $config['db_connection']['username'], $config['db_connection']['password'], [
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_STRINGIFY_FETCHES => false
]));

$f3->route('GET|POST /api/getConversationHistoryByEmailAddress', 'Controller->getConversationHistoryByEmailAddressApi');
$f3->route('GET|POST /getConversationHistoryByEmailAddress', 'Controller->getConversationHistoryByEmailAddressHtml');
$f3->route('GET|POST /downloadFile', 'Controller->downloadFile');
$f3->route('GET|POST /checkEmail', 'Controller->checkEmail');


$f3->run();