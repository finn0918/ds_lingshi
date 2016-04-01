<?php
error_reporting(0);
//配置信息
$config = include_once ("../../config.inc.php");
$c = array(
    'DB_HOST' => $config['DB_HOST'] ,
	'DB_NAME' => $config['DB_NAME'] ,
	'DB_TABLE' => 'ch_lhj', //只有一张表,设置连接的数据表
	'DB_USER' => $config['DB_USER'] ,
	'DB_PWD' => $config['DB_PWD'] ,
	'DB_PORT' => '3306' ,
);

// $c = array(
    // 'DB_HOST' => 'localhost' ,
	// 'DB_NAME' => 'timebox' ,
	// 'DB_TABLE' => 'yx_lhj_info', //只有一张表,设置连接的数据表
	// 'DB_USER' => 'root' ,
	// 'DB_PWD' => '' ,
	// 'DB_PORT' => '3306' ,
// );