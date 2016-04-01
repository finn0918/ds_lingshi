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
function cart_spu_status($spu_id=0,$sku_id=0,$price=0){
    if($spu_id==0||$sku_id==0||$price==0){
        return 0;
    }
    // 主要看当时的那个sku的 商品是否有价格变动和 库存是否还有
    $extend_special_mod = M("extend_special");
    $now = time();
    $sku_extend = $extend_special_mod->where("spu_id = {$spu_id} and sku_id = {$sku_id} and start_time< {$now} and end_time > {$now}")->find();
    $type = 0;
    if(empty($sku_extend)){
        $sku_list_mod = M("sku_list");
        $sku_list = $sku_list_mod->where("spu_id = {$spu_id} and sku_id={$sku_id}")->field("price,sku_stocks")->find();
        // 判断库存量
        if($sku_list['sku_stocks']<=0){
            return 0;
        }
        if($sku_list['price']!=$price){
            return 0;
        }
        return 1;
  }else{

        if($sku_extend['sku_stocks']<=0){
            return 0;
        }
        return 1;
    }
    return 0;
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
        return false;
    }
    $now = time();
    $sku_ids = arrayToStr($sku_list,'sku_id');
    $spu_extend = $extend_special_mod->where("sku_id in ({$sku_ids}) and start_time< {$now} and end_time > {$now}")->select();
    $type = 0;
    if(empty($spu_extend)){
        // 查询库存表最低价
        $sku_list_mod = M("sku_list");
        $sku_list = $sku_list_mod->where("spu_id = {$spu_id} ")->field("price,sku_sale,sku_stocks")->select();
        // 如果有库存就显示sku 表里该spu的最低价
        foreach ($sku_list as $v) {
            $price_arr[] = $v['price'];
            $stocks_sum += $v['sku_stocks'];
            $sale_sum   += $v['sku_sale'];
        }
        $min_price = min($price_arr);
    }else{
        $type = $spu_extend[0]['type'];
        foreach ($spu_extend as $v) {
            $price_arr[] = $v['price'];
            $stocks_sum += $v['sku_stocks'];
            $sale_sum   += $v['sku_sale'];
        }
        $min_price = min($price_arr);
    }

    return array(
        "price"  => price_format($min_price),
        "stocks" => $stocks_sum,
        "sale"   => $sale_sum,
        "type"   => $type
    );
}

/**
 * 不包括导购商品
 * 根据特卖商品，或者品牌团商品获取   库存 价格 销量
 * $param void $type 0 普通商品 1 特卖商品 2 特卖不显示库存
 * $param void $spu_id 商品的spu_id
 * $param void $topic_id 专题id
 * $param void $extend_table 扩展表
*/
function spu_format_1($type = 0,$spu_id = 0, $topic_id = 0){
    // 初始化变量
    $min_price  = 0;
    $stocks_sum = 0;
    $sale_sum   = 0;
    $price_arr  = array();
    // 特卖商品需要特卖价格
    if ($type != 0) {

        // 查询 商品的 特卖价格
        $topic_relation_mod = M("topic_relation");
        $extend_id_arr = $topic_relation_mod->where("topic_id = {$topic_id} and spu_id = {$spu_id}")->field("table_name,extend_id")->select();

        $extend_table = $extend_id_arr[0]['table_name'];

        $extend_table_mod = M($extend_table);

        $extend_ids =  arrayToStr($extend_id_arr,"extend_id");

        $special_sku_list = $extend_table_mod->where("id in ($extend_ids) ")->field("price,sku_sale,sku_stocks")->select();

        foreach ($special_sku_list as $v) {
            $price_arr[] = $v['price'];
            $stocks_sum += $v['sale_num'];
            $sale_sum   += $v['stock_num'];
        }
        $min_price = min($price_arr);

    } else {
        // 查询库存表最低价
        $sku_list_mod = M("sku_list");
        $sku_list = $sku_list_mod->where("spu_id = $spu_id")->field("price,sku_sale,sku_stocks")->select();
        // 如果有库存就显示sku 表里该spu的最低价
        foreach ($sku_list as $v) {
            $price_arr[] = $v['price'];
            $stocks_sum += $v['sale_num'];
            $sale_sum   += $v['stock_num'];
        }
        $min_price = min($price_arr);
    }
    return array(
        "price"  => price_format($min_price),
        "stocks" => $stocks_sum,
        "sale"   => $sale_sum
    );

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
 * 自定义的格式化价格
 * $param void $price 以分为单位
 * return double
 */
function price_format($price)
{

    return floatval(number_format($price / 100, 2));
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