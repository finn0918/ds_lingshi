<?php
//老虎机抽奖概率
include('config.php');
date_default_timezone_set("Asia/Shanghai");
/*
// 获取天
$day = date("d",time());
// 设置中奖时段
switch ($day) {
	case "260":
		$arr = array(
			//'几等奖' => array('中奖数字' ,'概率',数量)
			'1' => array(      //一等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 1,    //概率
				'num' => 1,      //数量
			),
			'2' => array(     //二等奖
				'hit' => 1,	    //'中奖数字'
				'rate' => 500000000000000000,    //概率
				'num' => 1,      //数量
			),
			'3' => array(      //三等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 500000000000000000,       //概率
				'num' => 3,           //数量
			),
           '4' => array(     //二等奖
               'hit' => 1,	    //'中奖数字'
               'rate' => 1,    //概率
               'num' => 1,      //数量
           ),
           '5' => array(      //三等奖
               'hit' => 1,		   //'中奖数字'
               'rate' => 1,       //概率
               'num' => 3,           //数量
           ),
		);
		break;
	case "140":
		$arr = array(
			//'几等奖' => array('中奖数字' ,'概率',数量)
			'1' => array(      //一等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 500000000000000000,    //概率
				'num' => 1,      //数量
			),
			'2' => array(     //二等奖
				'hit' => 1,	    //'中奖数字'
				'rate' => 5000000000,    //概率
				'num' => 1,      //数量
			),
			'3' => array(      //三等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 50000,       //概率
				'num' => 3,           //数量
			),
		);
		break;
	case "170":
		$arr = array(
			//'几等奖' => array('中奖数字' ,'概率',数量)
			'1' => array(      //一等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 500000000000000000,    //概率
				'num' => 1,      //数量
			),
			'2' => array(     //二等奖
				'hit' => 1,	    //'中奖数字'
				'rate' => 5000000000,    //概率
				'num' => 1,      //数量
			),
			'3' => array(      //三等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 50000000000,       //概率
				'num' => 21,           //数量
			),
		);
		break;		
	default:
		$arr = array(
			//'几等奖' => array('中奖数字' ,'概率',数量)
			'1' => array(      //一等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 100000,    //概率
				'num' => 1,      //数量
			),
			'2' => array(     //二等奖
				'hit' => 1,	    //'中奖数字'
				'rate' => 10000,    //概率
				'num' => 1,      //数量
			),
			'3' => array(      //三等奖
				'hit' => 1,		   //'中奖数字'
				'rate' => 3000,       //概率
				'num' => 3,           //数量
			),
           '4' => array(     //四等奖
               'hit' => 1,	    //'中奖数字'
               'rate' => 1000,    //概率
               'num' => 1,      //数量
           ),
            '5' => array(      //五等奖
                'hit' => 1,		   //'中奖数字'
                'rate' => 300,       //概率
                'num' => 3,           //数量
            ),
		);
		break;
}
*/
    $arr = array(
        //'几等奖' => array('中奖数字' ,'概率',数量)
        '2' => array(      //20M流量
            'hit' => 5,		   //'中奖数字'
            'rate' => 50,    //概率 50
            'num' => 20,      //数量 20
        ),
        '5' => array(     //100M流量
            'hit' => 9,	    //'中奖数字'
            'rate' => 100,    //概率 100
            'num' => 10,      //数量 10
        ),
        '8' => array(      //价值11元零食礼包
            'hit' => 5,		   //'中奖数字'
            'rate' => 200,       //概率 200
            'num' => 10,           //数量 10
        ),
        '12' => array(      //价值111元零食礼包
            'hit' => 7,		   //'中奖数字'
            'rate' => 300,       //概率 300
            'num' => 1,           //数量 1
        ),
        '1' => array(      //5元优惠券（满59元）
            'hit' => 1,		   //'中奖数字'
            'rate' => 3,       //概率 3
            'num' => 3333,           //数量 3333
        ),
        '6' => array(      //5元优惠券（满59元）
            'hit' => 2,		   //'中奖数字'
            'rate' => 3,       //概率 3
            'num' => 3333,           //数量 3333
        ),
        '10' => array(      //5元优惠券（满59元）
            'hit' => 3,		   //'中奖数字'
            'rate' => 3,       //概率 3
            'num' => 3333,           //数量 3333
        ),
        '3' => array(      //10元优惠券（满99元）
            'hit' => 2,		   //'中奖数字'
            'rate' => 5,       //概率 5
            'num' => 3333,           //数量 3333
        ),
        '7' => array(      //10元优惠券（满99元）
            'hit' => 4,		   //'中奖数字'
            'rate' => 5,       //概率 5
            'num' => 3333,           //数量 3333
        ),
        '11' => array(      //10元优惠券（满99元）
            'hit' => 3,		   //'中奖数字'
            'rate' => 5,       //概率 5
            'num' => 3333,           //数量 3333
        ),
        '4' => array(      //卡乐比薯条
            'hit' => 1,		   //'中奖数字'
            'rate' => 500,       //概率 500
            'num' => 1,           //数量 1
        ),
    );

//数据库查询

$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
$db->exec("set names utf8");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$keys = array_keys($arr);//以下代码打乱奖品顺序
shuffle($keys);
foreach($keys as $key) {
    $new[$key] = $arr[$key];
}
$arr = $new;


// 循环中奖的 key 未奖项级别
foreach($arr as $key => $value) {
	$sql = "select count(*) as tmp_count from " . $c['DB_TABLE'] . " where hit=$key" ;  //统计当前几个人中奖
	$rs  = $db->query($sql);
	$row = $rs->fetchAll();
    /*
        switch($key){
            case '1':
                $base = 0;
                // $base = 10;
                break;
            case '2':
                $base = 0;
                break;
            case '3':
                $base = 20;
                break;
            case '4':
                $base = 50;
            // $base = 10;
                break;
            case '5':
                $base = 100;
            // $base = 10;
                break;
            default:
                $base = 20;
                break;
        }

        $sum = 0;
        // 累计数量
        switch($day){
            case "17":
                $sum = $base;
                break;
            case "18":
                $sum = $base*2;
                break;
            case "19":
                $sum = $base*3;
                break;
            default:
                $sum = $base;
        }
        */
	if($row[0]['tmp_count'] >= $value['num']) { //如果中奖数已经达到则退出

		continue;
	}

//	$id = isset($_POST['id_d']) ? intval($_POST['id_d']) :'';
//	//如果id 不为空则 说明已经中过奖。则continue
//	if(!empty($id)){
//		continue;
//	}
	$rand_hit = mt_rand(1, $value['rate']);
	
	if($value['hit'] == $rand_hit) {//中奖
		//中奖信息插入数据库
		$sql = "INSERT INTO `".$c['DB_TABLE']."` (`name` ,`tel`, `hit`, `time`)VALUES (:name, :tel, :hit, :time)";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(':name'=>'',':tel'=>'' , 'hit'=>$key, 'time'=>time()));
		$hit_id = $db->lastinsertid();
		if($hit_id) {
			$auth_code = md5($hit_id . 'IDKFDKower#L13EF');
			die(json_encode(array('flag'=>1,'cate'=>$key, 'hit_id' => $hit_id, 'auth_code' => $auth_code,'msg'=>'中奖')));
		} else {
			die(json_encode(array('flag'=>0,'cate'=>0,'msg'=>'数据错误')));
		}
	} else {//未中奖
		continue;
	}
}
//未中奖
die(json_encode(array('flag'=>0,'cate'=>0 , 'msg'=>'未中奖')));
