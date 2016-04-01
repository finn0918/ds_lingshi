<?php
/**
 * 后台专属配置文件
 * @author feibo
 *
 */

if (! defined('THINK_PATH')) {
	exit();
}

// 引入公共配置
$config = require ("config.inc.php");

$array = array(


		//URL模型
		'URL_MODEL' => 0 ,
		'PER_PAGE_COUNT' => '10' ,
		'DEFAULT_LANG' => 'zh-cn' ,

		// 敏感词类型
		'SENSITIVE_WORD_TYPE' => array(
				'1' => 'forbidden' ,
				'2' => 'replace',
				'3' => 'filter'
		) ,

		//后台控制节点状态
		'NODE_SATAUS' => array(
				'0' => 'forbidden' ,
				'1' => 'normal'
		) ,
		'NODE_SHOW' => array(
				'0' => 'normal' ,
				'1' => 'forbidden'
		) ,

		//可忽略的权限检查
		'IGNORE_PRIV_LIST' => array(
				array(
						'module_name' => 'Admin' ,
						'action_list' => array(
								'ajax_check_username'
						)
				) ,
				array(
						'module_name' => 'Article' ,
						'action_list' => array(
								'delete_attatch'
						)
				) ,
				array(
						'module_name' => 'Cache' ,
						'action_list' => array(
								'clearCache'
						)
				) ,
				array(
						'module_name' => 'Index' ,
						'action_list' => array()
				) ,
				array(
						'module_name' => 'Public' ,
						'action_list' => array()
				)
		) ,

		//失败与成功跳转页面
		'TMPL_ACTION_ERROR' => 'Public:error' ,
		'TMPL_ACTION_SUCCESS' => 'Public:success' ,
		'APP_TYPE' => array(
				'1' => '微信'
		) ,

		//排版说明
		'POSTION_TYPE' => array(
				'1' => '横排' ,
				'2' => '竖排' ,
				'4' => '头部'
		) ,
		//商品状态
		'SPU_STATUS' => array(
				'0' => '出售中' ,
				'3' => '未上架' ,
				'2' => '已下架' ,
				'5' => '待编辑' ,
                '1' => '售空' ,
		) ,
		//商品类型
		'SPU_TYPE' => array(
				'0' => '商城商品' ,
				'2' => '天猫商品' ,
				'1' => '淘宝商品'
		) ,
		//银行类型
		'BANK_TYPE' => array(
				'1' => '中国银行' ,
				'2' => '建设银行' ,
				'3' => '招商银行' ,
				'4' => '工商银行' ,
				'5' => '农业银行' ,
				'6' => '华夏银行' ,
				'7' => '浦发银行' ,
		) ,
        //推送类型
		'MSG_TYPE' =>array(
            '1'=>'专题详情',
            '2'=>'专题列表',
            '3'=>'商品详情',
            '4'=>'隐形专题的商品列表',
            '5'=>'跳转指定地址',
            '0'=>'打开应用',
		),
        //订单状态
        'ORDER_TYPE' =>array(
            '0'=>'待付款',
            '1'=>'待发货',
            '2'=>'待收货',
            '3'=>'交易成功-待评价',
            '4'=>'交易成功-已评价',
            '7'=>'交易关闭',
        ),
    'CATE_INDEX_NUM' => 6,//首页显示
    'CATE_LIST_NUM' => 15,//列表顺序
    'ALIPAY_REFUND_NOTIFY_URL' => 'http://ds.lingshi.cccwei.com/admin.php/Notify/notify',

);
return array_merge($config, $array);
?>
