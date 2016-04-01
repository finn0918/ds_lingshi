<?php
/**
 * 一、二维数组 转字符串 , 号拼接
 * @param void $var 
 */
function arrayToStr($array,$key=''){
    if($key==''){
       return  implode(",",$array);
    }
    $id ='';
    if($array) {
        foreach ($array as $val) {
            $id .= $val[$key] . ',';
        }
        $id = rtrim($id, ',');
    }
    return $id;
}

/**
 * 二维数组转一维 id 做建名
 * @param void $var
 */
function secToOne($var,$id,$value){
    $tmp = array();
    foreach ($var as $v) {
        $tmp["{$v[$id]}"] = $v[$value];
    }
    return $tmp;
}
/**
 * 处理订单查询返回的库存信息
 * @param void $var
 */
function stockList($var,$id,$value){
    $tmp = array();
    foreach ($var as $v) {
        $tmp["{$v[$id]}"] = $v[$value]<0?0:$v[$value];//库存小于0就是0
    }
    return $tmp;
}
/**
 * 二维数组转一维 id 做建名 其余做键值
 * @param void $var
 */
function arrayFormat($var,$id){
    $tmp = array();
    foreach ($var as $v) {
        $id_d = $v[$id];
        unset($v[$id]);
        $tmp[$id_d] = $v;
    }
    return $tmp;
}
/**
 * 打印输出数据 print_data 别名
 * @param void $var
 */
function p($var){
    print_data($var);
}
/**
 * 打印输出数据
 * @param void $var
 */
function print_data($var)
{
    if (is_bool($var)) {
        var_dump($var);
    } else if (is_null($var)) {
        var_dump(NULL);
    } else {
        echo "<pre style='position:relative;z-index:1000;padding:10px;border-radius:5px;background:#F5F5F5;border:1px solid #aaa;font-size:14px;line-height:18px;opacity:0.9;'>" . print_r($var, true) . "</pre>";
    }
}
//获取短码
function short_code($input) {
  $base32 = array (
    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
    'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
    'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
    'y', 'z', '0', '1', '2', '3', '4', '5'
    );

  $hex = md5($input);
  $hexLen = strlen($hex);
  $subHexLen = $hexLen / 8;
  $output = array();

  for ($i = 0; $i < $subHexLen; $i++) {
    $subHex = substr ($hex, $i * 8, 8);
    $int = 0x3FFFFFFF & (1 * ('0x'.$subHex));
    $out = '';

    for ($j = 0; $j < 6; $j++) {
      $val = 0x0000001F & $int;
      $out .= $base32[$val];
      $int = $int >> 5;
    }

    $output[] = $out;
  }
 //输出三条，获取第一条
  return $output[0];
}
//格式化时间
function beautime($t)
{
	$time = time();
	$z_time = $time - $t;
	if ($z_time < 3600)
	{
		return floor($z_time / 60) . "分钟前";
	}

	if ($z_time > 3600 && $z_time < 86400)
	{
		return floor($z_time / 3600) . "小时前";
	}

	return date("m-d",$t);
}
//获取列表缩略图
function get_upyun_tmp($dimg){
	$upyun_tmp = C('UPYUN_TMP');
	foreach($dimg as $k=>$v){
		$dimg[$k] = array(
				'img' =>$v['img'].$upyun_tmp['name'],
				'img_w' => 300,
				'img_h' => 200,
		);
	}
	return $dimg;
}
//获取详细页缩略图
function get_upyun_detail_tmp($dimg){
	$upyun_tmp = C('UPYUN_DETAIL_TMP');
	foreach($dimg as $k=>$v){
		$dimg[$k] = array(
				'img' =>$v['img'].$upyun_tmp['name'],
				'img_w' => 640,
				'img_h' => 490,
		);
	}
	return $dimg;
}
//获取列表页单图缩略图
function get_upyun_one_tmp($dimg){
	$upyun_tmp = C('UPYUN_ONE_TMP');
	foreach($dimg as $k=>$v){
		$dimg[$k] = array(
				'img' =>$v['img'].$upyun_tmp['name'],
				'img_w' => 640,
				'img_h' => 360,
		);
	}
	return $dimg;
}
//获取列表页单图缩略图
function get_upyun_double_tmp($dimg){
	$upyun_tmp = C('UPYUN_DOUBLE_TMP');
	foreach($dimg as $k=>$v){
		$dimg[$k] = array(
				'img' =>$v['img'].$upyun_tmp['name'],
				'img_w' => 290,
				'img_h' => 240,
		);
	}
	return $dimg;
}
//获取相关新闻缩略图
function get_upyun_recommend_tmp($dimg){
    foreach($dimg as $k=>$v){
        $dimg[$k] = array(
            'img' =>$v['img'].'!180x120',
            'img_w' => 180,
            'img_h' => 120,
        );
    }
    return $dimg;
}

//替换图片地址的域名
function img_addr($addr){
	//$old_addr = "img.lingshi.cccwei.com";
	$is_tencent = C('is_tencent');
	if(!$is_tencent){
		$new_addr = "http://lingshi.b0.upaiyun.com";
		$addr = substr($addr,29);
		$addr = $new_addr.$addr;
	}
	return $addr;
}

//图片格式
function image($img,$type=1){
    $img = unserialize($img);
    $img_arr = array();
    if($type==2){
        foreach ($img as $k=>$v) {
            $img_arr[$k]['img_url'] = isset($v['img_url']) ? C('LS_IMG_URL').urldecode($v['img_url']):'';
            $img_arr[$k]['img_w'] = intval($v['img_w']);
            $img_arr[$k]['img_h'] = intval($v['img_h']);
        }
    }else{
        $img_arr['img_url'] =  isset($img['img_url']) ? C('LS_IMG_URL').urldecode($img['img_url']):'';
        $img_arr['img_w'] = intval($img['img_w']);
        $img_arr['img_h'] = intval($img['img_h']);
    }

    return $img_arr;
}

/*
 * 判断 购物车里面商品的有效性
 */
function cart_spu_status($spu_id=0,$sku_id=0,$price=-1){
    if($spu_id==0||$sku_id==0||$price==-1){
        return array(
            "state"       => 2,
            "sku_stocks"  => 0,
            "sku_price"   => 0
        );
    }
    $spu_mod = M("spu");
    $status = $spu_mod->where("spu_id = {$spu_id}")->getField("status");
    if($status>=2){
        return array(
            "state"       => 3,
            "sku_stocks"  => 0,
            "sku_price"   => 0,
            "status"      => 2
        );
    }

    // 主要看当时的那个sku的 商品是否有价格变动和 库存是否还有
    $extend_special_mod = M("extend_special");
    $now = time();

    $topic_relation_mod = M("topic_relation");
    $spu_active = $topic_relation_mod->where("spu_id = {$spu_id} and status='published' and publish_time< {$now} and end_time > {$now} and type=2")->count();

    if(empty($spu_active)){
        $sku_list_mod = M("sku_list");
        $sku_list = $sku_list_mod->where("spu_id = {$spu_id} and sku_id={$sku_id}")->field("price,sku_stocks")->find();
        // 判断库存量
        if($sku_list['sku_stocks']<=0){
            return array(
                "state"       => 2,
                "sku_stocks"  => $sku_list['sku_stocks'],
                "sku_price"   => $sku_list['price'],
                "active"      => 0
            );
        }
        if($sku_list['price']!=$price){
            return array(
                "state"       => 1,
                "sku_stocks"  => $sku_list['sku_stocks'],
                "sku_price"   => $sku_list['price'],
                "active"      => 0
            );
        }

        return array(
            "state"       => 0,
            "sku_stocks"  => $sku_list['sku_stocks'],
            "sku_price"   => $sku_list['price'],
            "active"      => 0
        );
  }else{
        $sku_extend = $extend_special_mod->where(" sku_id = {$sku_id} and start_time< {$now} and end_time > {$now}")->find();
        if($sku_extend['sku_stocks']<=0){
            return array(
                "state"       => 2,
                "sku_stocks"  => $sku_extend['sku_stocks'],
                "sku_price"   => $sku_extend['price'],
                "active"      => 1
            );
        }
        if($sku_extend['price']!=$price){
            return array(
                "state"       => 1,
                "sku_stocks"  => $sku_extend['sku_stocks'],
                "sku_price"   => $sku_extend['price'],
                "active"      => 1
            );
        }
        return array(
            "state"       => 0,
            "sku_stocks"  => $sku_extend['sku_stocks'],
            "sku_price"   => $sku_extend['price'],
            "active"      => 1
        );
    }
}


/**
 * 计算商品的折扣信息
 * return string
 */
function agio_format($price_now,$price_old){
    return number_format(($price_now/$price_old)*10,1).'折';
}


/**
 * 不包括导购商品
 * 根据特卖商品，或者品牌团商品获取   库存 价格 销量 type
 * $param void $spu_id 商品的spu_id
 * spu_real_info
 */
function spu_format($spu_id = 0){

    // 初始化变量
    $min_price  = 0;
    $stocks_sum = 0;
    $sale_sum   = 0;
    $price_arr  = array();
    // 特卖商品需要特卖价格
    $extend_special_mod = M("extend_special");
    $sku_list_mod = M("sku_list");
    $sku_list = $sku_list_mod->where("spu_id = {$spu_id}")->field("sku_id")->select();

    if(empty($sku_list)){
        $spu_mod = M("spu");
        $spu_info = $spu_mod->where("spu_id={$spu_id}")->field("price_now,price_old,stocks,sales")->find();
        return array(
            "price"    => price_format($spu_info['price_now']),
            "stocks"   => $spu_info['stocks'],
            "sale"     => $spu_info['sales'],
            "type"     => 0,
            "end_time" => 0
        );
    }

    $now = time();
    $sku_ids = arrayToStr($sku_list,'sku_id');
    $topic_relation_mod = M("topic_relation");
    $spu_active = $topic_relation_mod->where("spu_id = {$spu_id} and status='published' and publish_time< {$now} and end_time > {$now} and type=2")->select();
    $type = 0;
    $end_time = 0;
    if(empty($spu_active)){
        // 查询库存表最低价
        $sku_list_mod = M("sku_list");
        $sku_list = $sku_list_mod->where("spu_id = {$spu_id} ")->field("price,sku_sale,sku_id,sku_stocks")->select();
        // 如果有库存就显示sku 表里该spu的最低价
        foreach ($sku_list as $v) {
            $price_arr[] = $v['price'];
            $stocks_sum += $v['sku_stocks'];
            $sale_sum   += $v['sku_sale'];
            $sku_stocks[]       = array(
                "sku_id"    => $v['sku_id'],
                "sku_stocks"=> $v['sku_stocks']
            );
            $sku_price[] = array(
                "sku_id"    => $v['sku_id'],
                "price"     => $v['price']
            );
        }
        $topic_id = 0;
        $min_price = min($price_arr);

    }else{
        $spu_extend = $extend_special_mod->where("sku_id in ({$sku_ids}) and start_time< {$now} and end_time > {$now}")->select();
        // 代表的活动类型 1 限时 2 限时限量
        $type = $spu_extend[0]['type'];

        foreach ($spu_extend as $v) {
            $price_arr[] = $v['price'];
            $stocks_sum += $v['sku_stocks'];
            $sale_sum   += $v['sku_sale'];
            $sku_stocks[]       = array(
                "sku_id"    => $v['sku_id'],
                "sku_stocks"=> $v['sku_stocks']
            );
            $sku_price[] = array(
                "sku_id"    => $v['sku_id'],
                "price"     => $v['price']
            );
        }
        $topic_id = $spu_active['topic_id'];
        $min_price = min($price_arr);

        $end_time  = $spu_extend[0]['end_time'];
    }

    return array(
        "price"     => price_format($min_price),
        "stocks"    => $stocks_sum,
        "sale"      => $sale_sum,
        "type"      => $type,
        "end_time"  => $end_time,
        "sku_stocks"=> $sku_stocks,
        "sku_price" => $sku_price,
        "topic_id"  => $topic_id
    );
}

/**
 * 自定义的格式化价格
 * $param void $price 以分为单位
 * return double
 */
function price_format($price)
{
    return floatval(sprintf("%0f",$price / 100));
}

/**
 * 自定义的优惠金额
 * $param void $price 以分为单位
 * return double
 */
function free_format($price){
    return intval($price/100);
}
/**
 * 用户登录设备时，将设备中的购物车商品同步到 用户购物车表
 * $param void cid
 * $param void uid
 */
function mergerCart($cid,$uid){
    if($cid<=0||$uid<=0){
        return false;
    }
    $cart_driver_mod = M("cart_driver");
    $cart_mod = M("cart");
    $driver_cart_list = $cart_driver_mod->where("cid = {$cid}")->select();

    if(!empty($driver_cart_list)){
        // 设备的购物车表里面有数据 这需要合并购物车
        foreach($driver_cart_list as $v){

            $id = $v['id'];
            $result = $cart_mod->where("user_id = {$uid} and sku_id={$v['sku_id']} ")->setInc("num",$v['num']);
            if($result===0){
                // 说明没有更新成功 也就是这个商品与原商品不同步
                unset($v['id']);
                unset($v['cid']);
                $v['user_id'] = $uid;

                if($cart_mod->add($v)){
                    $cart_driver_mod->where("id = {$id}")->delete();
                }
            }elseif($result){
                // 更新成功，说明可以合并，于是删除 设备购物车表数据
                $cart_driver_mod->where("id = {$id}")->delete();
            }
        }
    }else{
        return false;
    }

}

/**
 * 对象转数组
 * $param void obj
 * return array
 */
function object_to_array($obj){
    $_arr = is_object($obj) ? get_object_vars($obj) :$obj;
    foreach ($_arr as $key=>$val){
        $val = (is_array($val) || is_object($val)) ? object_to_array($val):$val;
        $arr[$key] = $val;
    }
    return $arr;
}

/**
 * 字符串截取
 * $param void obj
 * return string
 */
function cut_str($str,$len,$dot='...'){

    if(mb_strlen($str,'utf-8')>$len){

        return mb_substr($str,0,$len,'utf-8').$dot;
    }else{
        return $str;
    }
}
/**
 * 标签显示判断函数
 * $param void type 0普通商品 1  限时限量要显示库存 2 限时的不需要显示库存
 * $param void shipping_free 是否包邮 1 包邮 0 不包邮
 * return string
 */
function tag_format($type,$shipping_free=0,$price_now,$price_old){
    if($type>0){
        return array(
            "title" => agio_format($price_now,$price_old),
            "color" => 0
        );
    }else{
        return array(
            "title"  => $shipping_free?"包邮":"",
            "color"  => 0
        );
    }
}

/**
 * 针对自营商品获取该商品的原价
 * $param void $spu_id 商品的spu_id
 * spu_real_info
 */
function getOriginalPrice($spu_id){
    $sku_list_mod = M("sku_list");
    $price_list = $sku_list_mod->where("spu_id = {$spu_id}")->field("price")->select();
    if(count($price_list) > 1) {
        $price_arr = array();
        foreach ($price_list as $key => $value) {
            $price_arr[] = $value['price'];
        }
        $price = min($price_arr);
    }else{
        $price = $price_list[0]['price'];
    }
    return  price_format($price);
}

function customError($errno, $errstr, $errfile, $errline)
{
    Log::write("Custom error: [$errno] ，Error on line $errline in $errfile",Log::WARN);
}
