<?php
/*
 * Created on 2014-8-27
 * 有型配置文件
 *
 */
$config = require_once(ROOT_PATH."/config.inc.php");
$array =  array(
 	 /* 模板设置 */
    'TMPL_CACHE_ON'         =>  false,       // 默认开启模板编译缓存 false 的话每次都重新编译模板
    'URL_MODEL' => 1,                       //URL普通模式

 	/* 数据库配置信息 */

    /* 请求成功与失败返回信息 */
 	/* 模块和操作设置 */
    'DEFAULT_MODULE'            =>  'index', // 默认模块名称
    'DEFAULT_ACTION'            =>  'index', // 默认操作名称
  	/* 数据格式设置 */
    'AJAX_RETURN_TYPE'      =>  'JSON', //AJAX 数据返回格式 JSON
    /* 语言时区设置 */
    'TIME_ZONE'                 =>  'PRC',       // 默认时区
    'LANG_SWITCH_ON'            =>  false,   // 默认关闭多语言包功能
    'DEFAULT_LANGUAGE'      =>  'zh-cn',     // 默认语言
    'AUTO_DETECT_LANG'      =>   false,     // 自动侦测语言
    'DEFAULT_TIMEZONE'      => 'PRC', // 默认时区
	/*日志路径*/
 	'LOG_PATH' => 'logs/',
    // 个推配置信息
    'APPKEY' => 'Qj0l6ukcMp9OPn4XCsU1E1',
    'APPID' => 'ErJLCqAsll9sl9RglmBUm2',
    'MASTERSECRET' => 'OTLq2PvhYJ9uurHjgkM38',
    'HOST' =>  'http://sdk.open.api.igexin.com/apiex.htm',
);
return  array_merge($config,$array);
?>