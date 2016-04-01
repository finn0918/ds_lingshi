<?php
require_once(ROOT_PATH.'/admin/Lib/Api/upaiyun/upyun.class.php');
function up($val,$path,$type='img'){
	$conf = C('UPYUN_CONF');
	//$conf['bucketname'] = 'lingshi';
	//$conf['username'] = 'lingshi';
	//$conf['password'] = 'lingshi498j';
    if($type=='file'){
        $conf = C('UPYUN_CONF_VIDEO');
    }
    $upyun = new UpYun($conf['bucketname'], $conf['username'], $conf['password']);

try {
	$fh = fopen($val, 'rb');
    $rsp = $upyun->writeFile($path, $fh, True);
    fclose($fh);
	return $rsp;

}
catch(Exception $e) {
    //echo $e->getCode();
    //echo $e->getMessage();
}
}
function del_yun($path){
	$conf = C('UPYUN_CONF');
	$upyun = new UpYun($conf['bucketname'], $conf['username'], $conf['password']);
	$rsp = $upyun->delete($path);
	return $rsp;
}
