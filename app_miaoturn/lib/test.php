<?php
include('config.php');
$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
$db->exec("set names utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//$sql = "select count(*) as tmp_count from " . $c['DB_TABLE'] . " where hit=1" ;  //ͳ�Ƶ�ǰ�������н�
//$sql = "truncate `ch_lhj`" ;  //ͳ�Ƶ�ǰ�������н�
//$res = $db->query($sql);
//var_dump($res);
//�н���Ϣ�������ݿ�
$c['DB_TABLE']= 'ch_lhj';
//$sql = "INSERT INTO `".$c['DB_TABLE']."` (`name` ,`tel`, `hit`, `time`)VALUES (:name, :tel, :hit, :time)";
//$stmt = $db->prepare($sql);
//$stmt->execute(array(':name'=>'',':tel'=>'15036588657' , 'hit'=>3, 'time'=>time()));
echo "ok";
?>