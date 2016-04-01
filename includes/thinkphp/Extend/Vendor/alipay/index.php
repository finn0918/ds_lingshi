<?php
require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");
/**
+----------------------------------------------------------
 * 获得定单号
 * @return string
+----------------------------------------------------------
 */
function getOrderId()
{
    $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    $i = intval(date('Y')) - 2010-1;
    return $year_code[$i] . date('md').
    substr(time(), -5) . substr(microtime(), 2, 5) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
}

// 
$order_id = getOrderId();
// $alipayNotify = new AlipayNotify($alipay_config);
$data = array(
	"partner"		=> $alipay_config['partner'],
	"seller_id"		=> "feibobomail@126.com",
	"out_trade_no"  => $order_id,
	"suject"		=> 1,
	"body"			=> "零食小喵商城订单",
	"total_fee"		=> 12,
	"notify_url"	=> "http://www.xiaomi.com",
	"service"		=> "mobile.secureitypay.pay",
	"payment_type"	=> 1,
	"_input_charset"=> $alipay_config['input_charset'],
	"it_b_pay"		=> "30m",
	"show_url"		=> "m.alipay.com"
	);

//除去待签名参数数组中的空值和签名参数
$para_filter = paraFilter($data);

//对待签名参数数组排序
$para_sort = argSort($para_filter);
$str = createLinkstring($para_sort);



echo rsaSign($str,trim($alipay_config['private_key_path']));
?>
