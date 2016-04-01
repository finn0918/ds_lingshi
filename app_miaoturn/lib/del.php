<?php
include('config.php');
$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
$db->exec("set names utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo $c['DB_TABLE'];

$sql = "delete from {$c['DB_TABLE']}" ;  //统计当前几个人中奖
$rs  = $db->query($sql);
?>