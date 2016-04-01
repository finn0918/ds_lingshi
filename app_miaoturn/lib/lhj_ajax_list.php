<?php
include('config.php');
$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
$db->exec("set names utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$action = isset($_GET['action']) ? htmlspecialchars($_GET['action']) : 'get';
if($action == 'get') {
	$sql="SELECT * FROM " . $c['DB_TABLE'] . " where name<>''  and tel<>'' order by time desc";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list=$stmt->fetchAll(PDO::FETCH_ASSOC);
    /*
	// 设置时区
	date_default_timezone_set("PRC");
	// 获取天
	$day = date("d",time());

	// 加入虚拟中奖者
	$firstday = array(
		0=>array(
		"name" =>"许晴",
		"tel"  =>"18523932667",
		"hit"  => 2,
		"time" => strtotime("2015-04-01")+54456
		),
		1=>array(
		"name" =>"王琳琳",
		"tel"  =>"13255067219",
		"hit"  => 2,
		"time" => strtotime("2015-04-01")+34560
		)
	);
	$secondday = array(
		0=>array(
		"name" =>"梁子亮",
		"tel"  =>"13559802150",
		"hit"  => 1,
		"time" => strtotime("2015-04-02")+33120
		),
		1=>array(
		"name" =>"曾逸俊",
		"tel"  =>"15060767790",
		"hit"  => 2,
		"time" => strtotime("2015-04-02")+44280
		),
		2=>array(
		"name" =>"陈姗",
		"tel"  =>"13666910410",
		"hit"  => 2,
		"time" => strtotime("2015-04-02")+51480
		),
		3=>array(
		"name" =>"施钦",
		"tel"  =>"15600206810",
		"hit"  => 3,
		"time" => strtotime("2015-04-02")+51180
		),
		4=>array(
		"name" =>"小明",
		"tel"  =>"18020756630",
		"hit"  => 3,
		"time" => strtotime("2015-04-02")+55800
		),
		5=>array(
		"name" =>"小麦",
		"tel"  =>"15259602353",
		"hit"  => 3,
		"time" => strtotime("2015-04-02")+65520
		),
		6=>array(
		"name" =>"徐超",
		"tel"  =>"15860005339",
		"hit"  => 3,
		"time" => strtotime("2015-04-02")+76356
		)
	);

	$thirdday = array(
		0=>array(
		"name" =>"罗家伟",
		"tel"  =>"18059931229",
		"hit"  => 2,
		"time" => strtotime("2015-07-16")+65520
		),
		1=>array(
		"name" =>"马亮",
		"tel"  =>"15510628116",
		"hit"  => 3,
		"time" => strtotime("2015-07-16")+73356
		),
		2=>array(
		"name" =>"苏少雄",
		"tel"  =>"18039090278",
		"hit"  => 4,
		"time" => strtotime("2015-07-16")+51280
		),
		3=>array(
		"name" =>"何天宝",
		"tel"  =>"18030389176",
		"hit"  => 4,
		"time" => strtotime("2015-07-16")+42380
		),
		4=>array(
		"name" =>"肖贵芳",
		"tel"  =>"18039090292",
		"hit"  => 4,
		"time" => strtotime("2015-07-16")+432
		),
		5=>array(
		"name" =>"黎明",
		"tel"  =>"13489994225",
		"hit"  => 5,
		"time" => strtotime("2015-07-16")+80028
		),
		6=>array(
		"name" =>"余萍萍",
		"tel"  =>"15260986951",
		"hit"  => 5,
		"time" => strtotime("2015-07-16")+50280
		),
		7=>array(
		"name" =>"郑超雄",
		"tel"  =>"13559206775",
		"hit"  => 5,
		"time" => strtotime("2015-07-16")+71356
		),
		8=>array(
		"name" =>"马丁",
		"tel"  =>"13860936910",
		"hit"  => 5,
		"time" => strtotime("2015-07-16")+55231
		),
		9=>array(
		"name" =>"徐超",
		"tel"  =>"15860005339",
		"hit"  => 3,
		"time" => strtotime("2015-07-16")+76356
		)
	);
	// 判断当天是几号
	switch ($day) {
		case '030':
			foreach ($thirdday as $key => $value) {
				// 如果 当前时间大于 奖品出现实现则压入中奖者信息
				if($value['time']<time()){
						$list[] = $value;
				}
			}
		
		case '020':
			foreach ($secondday as $key => $value) {
				// 如果 当前时间大于 奖品出现实现则压入中奖者信息
				if($value['time']<time()){
						$list[] = $value;
				}
			}
		
		case '010':
			foreach ($firstday as $key => $value) {
				// 如果 当前时间大于 奖品出现实现则压入中奖者信息
				if($value['time']<time()){
						$list[] = $value;
				}
			}
			break;
		default :
			foreach ($thirdday as $key => $value) {
						$list[] = $value;
			}
			break;
	}
	*/
    foreach($list as $key=>$val){
        switch($val['hit']){
            case 1:
                $list[$key]['hit'] = "获得5元优惠券";
                break;
            case 2:
                $list[$key]['hit'] = "获得20M流量";
                break;
            case 3:
                $list[$key]['hit'] = "获得10元优惠券";
                break;
            case 4:
                $list[$key]['hit'] = "获得卡乐比薯条";
                break;
            case 5:
                $list[$key]['hit'] = "获得100M流量";
                break;
            case 6:
                $list[$key]['hit'] = "获得5元优惠券";
                break;
            case 7:
                $list[$key]['hit'] = "获得10元优惠券";
                break;
            case 8:
                $list[$key]['hit'] = "获得价值11元零食礼包";
                break;
            case 12:
                $list[$key]['hit'] = "获得价值111元零食礼包";
                break;
            case 10:
                $list[$key]['hit'] = "获得5元优惠券";
                break;
            case 11:
                $list[$key]['hit'] = "获得10元优惠券";
                break;
        }
    }
	die(json_encode($list));
} else if($action == 'del') {
    /*
	$id = isset($_GET['id']) ? intval($_GET['id']) : '0';
	if($id) {
		$sql = "DELETE FROM ".$c['DB_TABLE']." WHERE id=$id"; //kevin%
		$stmt = $dbh->prepare($sql);
		$stmt->execute();
		if($stmt->rowCount()) {
			die(json_encode(array('flag'=>1, 'msg'=>'删除成功')));
		} else {
			die(json_encode(array('flag'=>0, 'msg'=>'删除失败')));
		}

	} else {
		die(json_encode(array('flag'=>0, 'msg'=>'你在逗我吗？')));
	}
    */
}


?>