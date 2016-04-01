<?php
/**
 * API 接口 配置文件
 * @author feibo
 *
 * 常用配置
 */

$config = require_once("appConfig.php");

$array= array(
	'DATA_CACHE_TYPE' => 'Memcache',                //默认是file方式进行缓存的，修改为memcache
	'MEMCACHE_HOST'   =>  'tcp://127.0.0.1:11211',  //memcache服务器地址和端口，这里为本机。
	'DATA_CACHE_TIME' => '3600',                     //过期的秒数。
	'DEFAULT_MODULE'     => 'index', //默认模块
	'WS_CONFIG'=>array(//设备签名配置
	    'wskey_length' => 16,
	    'ignore_wsauth' => false, // 测试时候设置为true，忽略验证；上线时候设置为false，进行验证
	    'tms_max_span' => 0,//验证的时间间隔（暂没用）
	    'init_wskeys' => array(//默认设备id、秘钥，第一次注册设备使用
	        '10001' => 'GNab1tdOMTPCCMG8' ,  // ios 
	        '10002' => 'uYz1ZS6AXNQGNlV8' // android
	    ),
    ),

 	'US_CONFIG'=>array(//用户签名配置
		'wskey_length' => 16,
		'ignore_usauth' => false,// 测试时候设置为true，忽略验证；上线时候设置为false，进行验证
		'tms_max_span' => 0,
		'default_token' => array(//默认用户验证时间、秘钥、签名， 第一次注册用户使用
			'key' => 'ooxxooxxooxx',
			'tms' => '20130401000000',
			'wssig' => 'be5cea403c952b29',
		),
	),


);

return  array_merge($array,$config);
?>