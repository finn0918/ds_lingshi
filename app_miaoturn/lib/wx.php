<?php
	header("Content-type:application/json;charset=utf-8");
	$url = isset($_REQUEST['url']) ?  trim($_REQUEST['url']) : '';
	require_once "jssdk.php";
	//$jssdk = new JSSDK("wxfa49d5a2c0e0d7ac","e602a6601ed2c728c5cca89f03ccee5e");//千万反思
   	// $jssdk = new JSSDK("wxc98bbd7d8484e946","63e083b505dfa59ebdadd5450e334b63");//今晚玩啥
	//$jssdk = new JSSDK("wxcd116d70a368e076","300c3c77b37d522a401dfd9ebc88f3fb");//牵梦小助手
	//$jssdk = new JSSDK("wx46be632718311a93","37415ff36936a0feba5ad60a1c5ce4ba");//试试大派送
	$signPackage = $jssdk->GetSignPackage($url);
	die(json_encode($signPackage));
?>
