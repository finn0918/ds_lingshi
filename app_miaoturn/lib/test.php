<?php
include('config.php');
$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
$db->exec("set names utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//$sql = "select count(*) as tmp_count from " . $c['DB_TABLE'] . " where hit=1" ;  //统计当前几个人中奖
//$sql = "truncate `ch_lhj`" ;  //统计当前几个人中奖
//$res = $db->query($sql);
//var_dump($res);
//中奖信息插入数据库
$c['DB_TABLE']= 'ch_lhj';
//$sql = "INSERT INTO `".$c['DB_TABLE']."` (`name` ,`tel`, `hit`, `time`)VALUES (:name, :tel, :hit, :time)";
//$stmt = $db->prepare($sql);
//$stmt->execute(array(':name'=>'',':tel'=>'15036588657' , 'hit'=>3, 'time'=>time()));
echo "ok";
?>