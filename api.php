<?php
header('Content-Type: application/json; charset=utf-8');
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
define('THINK_PATH', './includes/thinkphp/');
define('APP_NAME', 'api');
define('APP_PATH', './api/');
define('APP_DEBUG',true);
require( THINK_PATH."ThinkPHP.php");
