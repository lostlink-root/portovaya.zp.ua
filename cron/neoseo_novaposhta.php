<?php
define('VERSION', '3.0.3.1');

$sapi = php_sapi_name();

// Configuration
require_once(dirname(__FILE__) . "/../admin/config.php");

write_to_log("Начинаем формирование импорта по расписанию");

write_to_log("Подключаем движок опенкарт");
// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response);

// Config
$config = new Config();
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

//settings
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");

foreach ($query->rows as $setting)
{
	if (!$setting['serialized'])
	{
		$config->set($setting['key'], $setting['value']);
	}
	else
	{
		$config->set($setting['key'], json_decode($setting['value'], true));
	}
}


// Session
if ($config->get('session_autostart')) {
	$session = new Session();
	$session->start();
	$registry->set('session', $session);
}

// Url
$registry->set('url', new Url($config->get('site_ssl')));


// Language Detection
$languages = array();

$query = $db->query("SELECT * FROM " . DB_PREFIX . "language WHERE status = '1'");
foreach ($query->rows as $result) {
	$languages[$result['code']] = $result;
}

$code = $config->get('config_language');
$config->set('config_language_id', $languages[$code]['language_id']);
$config->set('config_language', $languages[$code]['code']);

// Language
$language = new Language($languages[$code]['directory']);
$language->load('default');
$registry->set('language', $language);

// Document
$registry->set('document', new Document());

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Event Register
if ($config->has('action_event')) {
	foreach ($config->get('action_event') as $key => $value) {
		$event->register($key, new Action($value));
	}
}

// Config Autoload
if ($config->has('config_autoload')) {
	foreach ($config->get('config_autoload') as $value) {
		$loader->config($value);
	}
}

// Language Autoload
if ($config->has('language_autoload')) {
	foreach ($config->get('language_autoload') as $value) {
		$loader->language($value);
	}
}

// Library Autoload
if ($config->has('library_autoload')) {
	foreach ($config->get('library_autoload') as $value) {
		$loader->library($value);
	}
}

// Model Autoload
if ($config->has('model_autoload')) {
	foreach ($config->get('model_autoload') as $value) {
		$loader->model($value);
	}
}

function error_handler($errno, $errstr, $errfile, $errline)
{
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			break;
		default:
			$error = 'Unknown';
			break;
	}

	write_to_log('Произошла ошибка PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	return true;
}

// Error Handler
set_error_handler('error_handler');

write_to_log('Запускаем ипорт справочников ');

if($config->get('neoseo_novaposhta_status')){
	$loader->model('tool/neoseo_novaposhta');
	$model_tool_neoseo_novaposhta = $registry->get('model_tool_neoseo_novaposhta');
	$model_tool_neoseo_novaposhta->updateHandbook();
	write_to_log("Обновление справочников по CRON завершено");
	$model_tool_neoseo_novaposhta->trackAll();
	write_to_log("Обновление состояний отправлений по CRON завершено");

}

function write_to_log( $message ){
	$logile = DIR_LOGS . "neoseo_novaposhta.log";
	if (file_exists($logile) && filesize($logile) >= 10 * 1024 * 1024 /* Erase after 10 mb */) {
		unlink($logile);
		file_put_contents( $logile, date("Y-m-d H:i:s - ") . " Файл логов очищен и начат заново" . "\r\n", FILE_APPEND );
	}

	file_put_contents( $logile, date("Y-m-d H:i:s - ") . $message . "\r\n", FILE_APPEND );
}