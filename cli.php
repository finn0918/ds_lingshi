<?php
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
define('MODE_NAME','cli');
define('THINK_PATH', ROOT_PATH.'/includes/thinkphp/');
define('APP_NAME', 'cli');
define('APP_PATH', ROOT_PATH.'/cli/');
//define('APP_DEBUG',TRUE);
require( THINK_PATH."ThinkPHP.php");
