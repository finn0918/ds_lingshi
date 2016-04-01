<?php
//配置文件请勿支持覆盖线上文件

return array(
		//内网数据库测试地址
		'DB_HOST' => '127.0.0.1',
		'DB_NAME' => 'ds_lingshi',
		'DB_USER' => 'ds_lingshi',
		'DB_PWD' => 'ds_lingshi383fj',
		/*
		 * 发布版本地址配置
		 * 'DB_HOST' => '',
		 * 'DB_NAME' => '',
		 * 'DB_USER' => '',
		 * 'DB_PWD' => '',
		 */
		// 数据库端口与前缀	
		'DB_PORT' => '3306',
		'DB_PREFIX' => 'ls_' ,

		// 默认主题
		'DEFAULT_THEME' => 'default' ,

		'TMPL_ACTION_SUCCESS' => 'Public:success' ,
		'TMPL_ACTION_ERROR' => 'Public:error' ,

		// 样式版本控制
		'VERSION' => '1.2' ,

		// 默认模块	
		'DEFAULT_MODULE'     => 'Index', //默认模块
		'URL_MODEL'          => 3,
		// 系统类型 
		'OS_TYPE' =>array(
				'0'=>'全部',
				'1'=>'IOS版',
				'3'=>'安卓版',
				),

		'XD_STATUS' => array(
				'0' => '全部',
				'edit'=>'正在编辑',
				'scheduled'=>'已定时',
				'published'=>'已发布',
				),

		//推送消息状态
		'PUSH_STATUS' =>array(
				'0' => '未启用',
				'1' => '等待定时',
				'2' => '已定时',
				'3' => '已推送'
				),

		// Redis 服务
		'REDIS_CONF'     => array(
				'host'       => '127.0.0.1',
				'port'       => '6379',
				'auth'       => 'feibo667',
				'persistent' => false,
				'rw_separate' => false
				),
		'SMS_CONF'     =>array(
				'account'  => 'xmwl@xmwl',
				'password' => 'xmwl@1105uI',
				'msg'      => '您的短信验证码是:'
				),
		//又拍云 配置
		'UPYUN_CONF' => array(
				'bucketname' => 'lingshi' ,
				'username' => 'lingshi' ,
				'password' => 'lingshi498j' ,
				'path_prefix' => '/lingshi/',
				'is_local_copy' => TRUE,
				'local_copy_path' => ROOT_PATH.'/upload'
				),
		//图片地址
		'LS_IMG_URL' => 'http://img.lingshi.cccwei.com',//图片云地址
		/*************** 缓存键名 ************************/
		'LS_DAILY_ON_NEW'       => 'ls_daily_on_new',
		'LS_BRAND_SPU_LIST'     => 'ls_brand_spu_list_',// 某品牌团列表缓存
		"LS_SPECIAL_SALE_LIST"  => 'ls_special_sale_list',// 特卖列表
		"LS_FOUND_AD"           => 'ls_found_ad',// 零食发现页缓存
		"LS_TOPIC_LIST"         => 'ls_topic_list',//专题列表
		"LS_SPU_DETAIL"         => 'ls_spu_detail_',// 商品详情页缓存
		'LS_APP_CONFIG'         => 'ls_app_config',// app的配置表
                'LS_POP'                => 'ls_pop',// pop弹框
                'LS_POP_USER'           => 'ls_pop_user',// 已领取了大礼包的用户
		/*************** 特定专题及专题集合 ************************/
		"SPECIAL_TOPIC_ID"      => 6,// 特卖专题id 6
		'BRAND_TEAM_ID'         => 1,// 品牌团专题合集id
		'DAILY_TOPIC_ID'        => 5,// 每日上新id 5
		'ORDER_DESC'            => '零食小喵商城订单',
		// 快递公司配置文件。供我打
		"SHIPPING_COM"          => array(
				"顺丰"      => "sf",
				"申通"      => "sto",
				"圆通"      => "yt",
				"韵达"      => "yd",
				"天天"      => 'tt',
				"EMS"       => 'ems',
				"中通"      => 'zto',
				"汇通"      => 'ht',
				"全峰"      => 'qf'
				),
		//    "NOTIFY_URL"            => "http://58.23.56.98:45380/alipay_return/notify_url.php",
		"SELLER_EMAIL"          => "feibobomail@126.com",
		'SHIPPING_INFO' => array(

				'appkey' => '7d0dafd7fa980897ae5bc3a32c5f7bcc',
				'url' => 'http://v.juhe.cn/exp/index',
				'logo' => array(
					'sf'   => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_sf.png',
					'sto'  => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_sto.png',
					'yt'   => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_yt.png',
					'yd'   => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_yd.png',
					'tt'   => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_tt.png',
					'ems'  => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_ems.png',
					'zto'  => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_zt.png',
					'ht'   => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_bsht.png',
					'qf'   => 'http://ds.lingshi.cccwei.com/statics/images/express/icon_qf.png',		
					'yzxb' => 'http://ds.lingshi.cccwei.com/statics/images/express/yzxb.png'
					),
				),
		/********************************验证码********************************/
		'CODE_STR'                      => '23456789abcdefghjkmnpqrstuvwsyz', //验证码种子
		'CODE_FONT'                     => ROOT_PATH . '/Font/droidsansfallback.ttf', //字体
		'CODE_WIDTH'                    => 60,         //宽度
		'CODE_HEIGHT'                   => 25,          //高度
		'CODE_BG_COLOR'                 => '#ffffff',   //背景颜色
		'CODE_LEN'                      => 4,           //文字数量
		'CODE_FONT_SIZE'                => 18,          //字体大小
		'CODE_FONT_COLOR'               => 'black',   //字体颜色
		'CODE_OUTPUT_FILE'              => 1,// 1 生成文件，0 直接输出文件
		/********************************站点配置********************************/
		'XUN_SEARCH'                    => "ds_lingshi",
		'SITE_URL'                      => 'http://ds.lingshi.cccwei.com',
		//默认用户头像
		'DEFAULT_ICON' => 'http://ds.lingshi.cccwei.com/statics/default.png',
		'CODE_CACHE'                    => 'code_',
		'DEFAULT_SHIPPING_PRICE'        => 1200,
		'ALIPAY_NOTIFY_URL'             => 'http://api.ds.lingshi.cccwei.com/Pay/notifyUrlAliPay',
		'WXPAY_NOTIFY_URL'              => 'http://api.ds.lingshi.cccwei.com/Pay/notifyUrlWXPay', 
		'STARTPAGE_SIZE'=>array(  //启动页尺寸
				'1'=>array('3'=>'480x800','1'=>'640x960'),
				'2'=>array('3'=>'540x960','1'=>'640x1136'),
				'3'=>array('3'=>'720x1280','1'=>'750x1334'),//默认选择的分辨率
				'4'=>array('3'=>'1080x1920','1'=>'1242x2208'),
				),

		'START_PAGE_REDIS' => 'start_page_lingshi',

		'MAP_URL' => 'http://file.v.lengxiaohua.cn',//文件云地址

		'UPYUN_CONF_VIDEO' =>array(
				'bucketname' => 'file-v-lxh' ,
				'username' => 'videolxh' ,
				'password' => 'videolxh0427ji' ,
				),
		'LOG_RECORD' => true, // 开启日志记录
		'LOG_LEVEL'  => 'WARN, ERR, INFO, DEBUG', //记录的日志级别
		'LOG_FILE_SIZE' => 104857600, // 日志文件大小限制(100M)
		'LOG_TYPE'      => 3,// 文件方式记录
		'AUTH_KEY' => 'FEiafda^#&09342sdfjsdfKJFfiajfako345254F435',
		// 供应商接口对接密钥
		'SECRET_KEY' =>array(
        "yalidisi" => array(
            "secret_key"    =>"ylds1018",
            "sellername"    => "零食小喵",
            "wsdl"           => "http://www.relyexpress.com/tdms/service/MallService?wsdl"

        ),
        'edb'   => array(
            "secret_key"    =>"edb2015xiaomiao",
            "sellername"    => "零食小喵",
            "url"           => "http://101.200.79.57/api.lingshimall.com.cn/lingshiAPI/api/api.php?srvid=4"
				)
		),
		 'LS_SECOND_KILL' => array(
       				 7718,7719,7720,7721,7722,7723,7373,7400
   				 ),
			 'LS_FREE_SHIPPING'=> array(
  			      7948,7949,8008,8047,8050,8203,8209,7863
				 )		
		);
