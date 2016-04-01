<?php
$config = require ("config.inc.php");
$array = array(
    'URL_ROUTER_ON'   => true, //开启路由
    'URL_ROUTE_RULES' => array( //定义路由规则
        ':spu_id\d'               => 'Share/pro_detail',
        'subject/:subject_id'               => 'Share/themes_detail',
    ),
);
return array_merge($config, $array);
?>