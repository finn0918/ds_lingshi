<?php
//收集用户信息
include('config.php');

$id = isset($_POST['hit_id']) ? intval($_POST['hit_id']) :'';
$auth_code = isset($_POST['auth_code']) ? trim($_POST['auth_code']) :'';

if($auth_code != md5($id . 'IDKFDKower#L13EF') || empty($id)) {
	die(json_encode(array('flag'=>0, 'msg'=>'你在逗我吗？')));
}

$coupon5 = "110466231675550550";  //后台的填写的要发放的5元优惠券优惠码
$coupon10 = "110466231976022308"; //后台的填写的要发放的10元优惠券优惠码
$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
$db->exec("set names utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tel  = isset($_POST['tel']) ? htmlspecialchars(trim($_POST['tel']),ENT_QUOTES) : '';
$name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']),ENT_QUOTES) : '';
$address = isset($_POST['address'])? htmlspecialchars(trim($_POST['address']),ENT_QUOTES):'';
$hit = isset($_POST['cate'])? intval($_POST['cate']):0;
if($tel&&$name&&$address)
{
	$sql = "UPDATE `".$c['DB_TABLE']."` SET `tel`=:tel,`name`=:name,`address`=:address WHERE `id`=:id";
	$stmt = $db->prepare($sql);
	$stmt->execute(array(':tel'=>$tel, ':name'=>$name,':address'=>$address, 'id' => $id));
	if($stmt->rowCount()) { //用户数据添加成功
        if($hit==1||$hit==6||$hit==10){//五元优惠券发放给用户
            $sql = "select user_id from ls_client where mobile='$tel'" ;  //根据用户手机号码查找用户id
            $rs  = $db->query($sql);
            if(!$rs->rowCount()){
                die(json_encode(array('flag'=>1,'cate'=>0 , 'msg'=>'用户数据添加成功')));
            }
            $row = $rs->fetchAll();
            if($row[0]['user_id']){
                $sql = "INSERT INTO `ls_coupon_user` (`user_id` ,`coupon_num`)VALUES (:user_id, :coupon_num)";
                $stmt = $db->prepare($sql);
                $stmt->execute(array(':user_id'=>$row[0]['user_id'],':coupon_num'=>$coupon5));
            }
        }else if($hit==3||$hit==7||$hit==11){//十元优惠券发放给用户
            $sql = "select user_id from ls_client where mobile='$tel'" ;  //根据用户手机号码查找用户id
            $rs  = $db->query($sql);
            if(!$rs->rowCount()){
                die(json_encode(array('flag'=>1,'cate'=>0 , 'msg'=>'用户数据添加成功')));
            }
            $row = $rs->fetchAll();
            if($row[0]['user_id']) {
                $sql = "INSERT INTO `ls_coupon_user` (`user_id` ,`coupon_num`)VALUES (:user_id, :coupon_num)";
                $stmt = $db->prepare($sql);
                $stmt->execute(array(':user_id' => $row[0]['user_id'], ':coupon_num' => $coupon10));
            }
        }
		die(json_encode(array('flag'=>1,'cate'=>0 , 'msg'=>'用户数据添加成功')));
	} else {//用户数据添加失败
		die(json_encode(array('flag'=>1,'cate'=>0 , 'msg'=>'用户数据添加失败')));
	}
} else {
	die(json_encode(array('flag'=>0, 'msg'=>'请填写完整数据')));
}
