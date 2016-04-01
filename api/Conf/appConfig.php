<?php
/**
 * API 接口 配置文件
 * @author feibo
 * 接口号配置信息
 *
 */
$config = require_once("config.inc.php");
$array= array(
    'API_NO'=>array(//接口配置
    
        //APP系统类 
	    '1001' => array('m'=>'App' , 'a'=>'verUpd') , //版本更新
	    '1002' => array('m'=>'App' , 'a'=>'getDeviceId') , //获取设备id
        '1003' => array('m'=>'App' , 'a'=>'getConfig') , //获取配置项
        '1004' => array('m'=>'App' , 'a'=>'startPage'), //获取启动页图片
        '1005' => array('m'=>'App' , 'a'=>'iosUserStatus'), //iOS 用户开关
        '1006' => array('m'=>'App' , 'a'=>'clearRed'), //iOS红点数量清零
        '1007' => array('m'=>'App' , 'a'=>'checkCode'), //获取app 推送码
        '1008' => array('m'=>'App' , 'a'=>'shareSwitch'), //分享有礼开关
        '1009' => array('m'=>'App' , 'a'=>'pop'), //获取 首页弹窗信息
        '2104' => array('m'=>'App' , 'a'=>'registerUrl'),// 注册协议
        '2108' => array('m'=>'App' , 'a'=>'register'),// 注册协议载入模板
        '2808' => array('m'=>'App' , 'a'=>'updateAddress'),// 获取最新的收货地址
        //其他类
        '1501' => array('m'=>'Ad' , 'a'=>'banner'), //获取广告位
        '1502' => array('m'=>'Stat' , 'a'=>'operatCount'),//统计数据（推送数，到达数，点击确认按钮数）
        '1102' => array('m'=>'User' , 'a'=>'submitSuggest'), //公共板反馈
        '1107' => array('m'=>'Topic', 'a'=>'shareStatistics'), //公共板反馈
        '1504' => array('m'=>'Push' , 'a'=>'addPushDev'), //添加推送设备
        '2001' => array('m'=>'App','a'=>'service'), //获取服务信息
        '2002' => array('m'=>'App','a'=>'about'),  //获取关于零食小喵信息
        // 登录/注册/忘记密码接口
        '2102' => array('m'=>'User','a'=>'smslogin'),// 用户登录
        '1101' => array('m'=>'User','a'=>'userLogin'),// 用户登录/注册（第三方平台）
        '2103' => array('m'=>'User','a'=>'smsverify'),//注册获取短信验证码
        '2105' => array('m'=>'User','a'=>'isUserRegist'),//判断用户是否已经注册


        //首页接口

        '2201' => array('m'=>'Home','a'=>'home'),//通过分类id获取商品列表
        '2204' => array('m'=>'Search','a'=>'get'),//通过关键词获取搜索结果
        '2206' => array('m'=>'Home','a'=>'dailyOnNew'),// 首页的每日上新

        //专题 相关接口
        '2205' => array('m'=>'Topic','a'=>'brandSpuList'),// 品牌团详情页
        '2216' => array('m'=>'Activity','a'=>'seqActivity'),// 专场活动 H5 页面
        '2217' => array('m'=>'Activity','a'=>'nYuanActivity'),// N元任意购活动 H5 页面
        '2218' => array('m'=>'Activity','a'=>'mainActivity'),// 双十一主会场
        '2301' => array('m'=>'Topic','a'=>'specialSaleList'),// 特卖商品列表
        '2401' => array('m'=>'Topic','a'=>'foundAd'),//  获取发现首页 大专题
        '2402' => array('m'=>'Cate','a'=>'getSpuByCid'),// 通过分类获取商品
        '2403' => array('m'=>'Topic','a'=>'subjectList'),//通过大专题id获取小专题列表
        '2404' => array('m'=>'Topic','a'=>'detail'),  //  通过专题id获取专题详情商品列表
        '2414' => array('m'=>'Topic','a'=>'detailWap'),//  通过专题id 获取专题详情
        '2405' => array('m'=>'Topic','a'=>'topicList'),//获取发现首页小专题列表
        '2406' => array('m'=>'Cate','a'=>'getSpuBySubCid'),// 通过子分类id获取商品列表
        '2407' => array('m'=>'Topic','a'=>'spuList'),//  通过专题id获取商品列表列表
        '2408' => array('m'=>'Topic','a'=>'detailApp'),//  通过专题id获取专题详情 供客户端使用
        // 获取商品详情
        '2502' => array('m'=>'Spu','a'=>'detail'),// 通过spu_id 获取商品详情 供h5页面使用
        '2503' => array('m'=>'Spu','a'=>'commentList'),//通过商品id获取商品评价列表
        '2505' => array('m'=>'Spu','a'=>'detailApp'),//  通过spu_id 获取商品详情 供客户端使用
        '2506' => array('m'=>'Spu','a'=>'template'),//  调取html5 商品详情模板
        '2507' => array('m'=>'Spu','a'=>'templateComment'),//  调取html5 商品评论模板
        // 购物车
        '2604' => array('m'=>'Cart','a'=>'index'),  // 获取购物车列表
        '2602' => array('m'=>'Cart','a'=>'edit'),   // 修改购物车
        "2603" => array('m'=>'Cart','a'=>'del'),    // 单个或批量购物车中的商品
        '2601' => array('m'=>'Cart','a'=>'add'),    // 加入购物车
        '2609' => array('m'=>'Cart','a'=>'addMany'),    // 批量加入购物车
    	'2607' => array('m'=>'Person','a'=>'getRedDotInfo'),    // 获取红点信息
        '2608' => array('m'=>'Cart','a'=>'invalidIndex'),    // 无效的购物车商品
        // 支付
        '2605' => array('m'=>'Order','a'=>'index'), //提交购物车到支付页面
    	'2701' => array('m'=>'Person','a'=>'detail'), // 订单详情
        '2702' => array('m'=>'Order','a'=>'pay'), // 根据选择的支付方式，提交并获取签名信息
        '2712' => array('m'=>'Pay','a'=>'notifyUrlAliPay'),// 支付宝异步通知
        '2703' => array('m'=>'Order','a'=>'notifyResult'),// 获取支付结果
        '2708' => array('m'=>'Order','a'=>'payOrder'),// 获取支付结果
    	'2704' => array('m'=>'Person','a'=>'get'), // 个人中心订单分类接口
    	'2705' => array('m'=>'Person','a'=>'put'), // 取消/删除/确认收货
    	'2706' => array('m'=>'Person','a'=>'comment'), //评论接口
    	'2707' => array('m' =>'Express','a'=>'get'),  //获取物流详情
    	'2709' => array('m' =>'Person','a'=>'getOrderSpu'),  //获取订单商品
        '2710' => array('m' =>'Person','a'=>'modifyNickname'),// 修改用户昵称
        '2711' => array('m' =>'Person','a'=>'modifyAvatar'),// 修改个人头像
        '2713' => array('m'=>'Person','a'=>'refund'),// 用户申请售后服务
        // 收货地址管理
        '2801' => array('m'=>'Address','a'=>'get'), // 获取收货地址
    	'2802' => array('m'=>'Address','a'=>'put'), // 新增或修改收货地址
    	'2803' => array('m'=>'Address','a'=>'del'), // 删除收货地址、设置默认收货地址
    	'2804' => array('m'=>'Collect','a'=>'get'), // 获取收藏夹商品列表
    	'2805' => array('m'=>'Collect','a'=>'put'), // 添加/移除收藏夹商品/专题
    	'2806' => array('m'=>'Collect','a'=>'get'), // 获取收藏夹专题列表

        '2901' => array('m'=>'Coupon','a'=>'activeList'), // 根据供应商ID获取商品促销与特殊优惠 H5
        '2902' => array('m'=>'Coupon','a'=>'activeListApp'), //  根据供应商ID获取商品促销与特殊优惠 客户端使用
        '2903' => array('m'=>'Coupon','a'=>'couponRedHot'), //  优惠券红点提醒
        '2904' => array('m'=>'Order','a'=>'couponSelect'),// 使用优惠券，有、无效优惠券（确认订单页）
        '2905' => array('m'=>'Coupon','a'=>'detail'),// 优惠券详情页面 H5 使用
        '2906' => array('m'=>'Coupon','a'=>'detailApp'),// 优惠券详情页面 客户端使用
        '2907' => array('m'=>'Coupon','a'=>'get'),// 领取优惠券接口
        '2908' => array('m'=>'Coupon','a'=>'myCoupon'),// 我的优惠券
        '2909' => array('m'=>'FreeFee','a'=>'index'), // 返回某供应商下的所有商品
        '2917' => array('m'=>'Coupon','a'=>'ruleApp'),// 优惠券通用使用规则
        '2910' => array('m'=>'Coupon', 'a'=>'about'),//获取优惠券通用使用规则
        '2915' => array('m'=>'Coupon','a'=>'couponWap'), // 载入优惠券列表模板页
        '2916' => array('m'=>'Coupon','a'=>'detailWap'), // 载入优惠券详情页
        '2925' => array('m'=>'Coupon','a'=>'detailGifts'), // 获取优惠券大礼包数据（H5使用）
        '2926' => array('m'=>'Coupon','a'=>'detailGifsWap'), //  大礼包页面载入（H5使用）
        '2936' => array('m'=>'Coupon','a'=>'detailGiftApp'), // 获取大礼包详情（客户端使用）
        '2921' => array('m'=>'ShareActive','a'=>'index'),// 分享有礼H5载入页面
        '2922' => array('m'=>'ShareActive','a'=>'shareSuccess'),// 分享有礼分享成功后调取的页面

		'3000' => array('m'=>'Pop','a'=>'pop'), // 获取优惠券大礼包数据老用户（H5使用）
    	'3001' => array('m'=>'Pop','a'=>'popNew'), // 获取优惠券大礼包数据新用户（H5使用）
    	'3002' => array('m'=>'Coupon','a'=>'giftdetails'), // 获取优惠券大礼包数据ios和and分发（H5使用）
        '3003' => array('m'=>'Coupon','a'=>'gf'), // 获取优惠券大礼包数据ios和and分发（H5使用）
        '3004' => array('m'=>'Coupon','a'=>'gets'), // 领取优惠券大礼包数据（H5使用）
    ),
    'WS_CODE'=>array(//请求返回信息配置
        'success'               => '1000',//成功
        'internal_error'        => '1001',//内部错误 (异常统一显示)
        'empty_error'           => '1002',//空数据错误
    	'sensitive_word'        => '1003',//敏感词
        'returnsnull_error'     => '1004',//返回数据为空 （没有更多了）
        'parameter_error'       => '1006',//传参错误
        'validate_error'        => '1007',//验证错误
        'no_privilege'          => '1008',//无权限
        'bind_mobile_error'     => '1009',//此手机号码已被占用，请使用其他号码绑定
        'repeat_mobile_error'   => '1010',//手机号已经被注册
        'mobile_format_error'   => '1011',//手机号码格式错误
        'forget_wrong_num'      => '1012',//号码尚未注册，请先注册
        'pwd_validate_error'    => '1013',//密码需由6-16位字母或数字组成
        'login_out_error'       => '1014',//未登录不能进行操作
        'mobile_miss'           => '1015',//手机号码未填写
        'pwd_error'             => '1017',//密码有误，请重新输入
        'img_code_error'        => '1018',//图形验证码错误
        'ad_word_exists'        => '2002',//太过广告
        'sensitive_word_exists' => '2003',//敏感词
        'repeat_click'          => '2004',//重复点击
        'none_object'           => '2005',//错误操作对象
        'up_repeated_text'      => '2006',//重复评论
        'weibo_oauth_timeout'   => '2018',//微博授权过期
    	'collect_repeat'        => '2019',//收藏重复
        'order_address_loss'    => '3002',//地址信息丢失
    	'get_express_error'     => '3003',//物流信息获取失败
        'topic_id_error'        => '4001',//专题id不存在
        'spu_id_error'          => '4002',//spu_id不存在
        'sku_id_error'          => '4003',// 不存在的sku_id
        'pay_not_exist'         => '5001',// 支付方式不存在（订单与支付相关错误 5开头）
        'order_not_exist'       => '5002',// 该用户的订单不存在
        'cart_goods_not_exist'  => '5003',// 您的购物车空空的
        'sku_stocks_not_enough' => '5004',// 库存量为0
        'sku_price_change'      => '5005',// 商品价格变更了
        'order_create_fail'     => '5006',// 订单创建失败
        'spu_not_sale'          => '5007',// 零食已下架
        'spu_stock_empty'       => '5008',// 零食已售空
        'spu_not_exist'         => '5009',// 该零食不存在
        'order_common_error'    => '5010',// 支付订单后出错的统一回复
        'brand_topic_not_exist' => '5011',// 该品牌团已下架
        'supplier_id_not_exist' => '6001',// 供应商不存在
        'coupon_id_not_exist'   => '6002',// 优惠券id 不存在
        'coupon_get_error'      => '6003',// 领取失败，请稍后尝试
        'coupon_is_offline'     => '6004',// 优惠券已下架
        'coupon_can_not_use'    => '6005',// 该优惠券无法使用
        'coupon_invalidate'     => '6006',// 订单内促销信息发生变化，请重新支付
        'cart_num_max'          => '6007',// 购物车的最大商品数不能超过99。
        'gifts_not_exist'       => '6008',// 优惠券大礼包不存在
        'auth_code_not_match'   => '6009',// 优惠券的授权码不正确
        'order_refund_exist'    => '6010' // 订单已处于售后处理状态
    ),
    'MSG'=>array(//错误提示信息
        'internal_error'       => '内部错误',
        'empty_error'          => 'null data',
        'repeat_mobile_error'  => '手机号已注册过',
        'mobile_format_error'  => '手机格式或密码有误，请重新输入',
        'forget_wrong_num'     => '号码尚未注册，请先注册',
        'bind_mobile_error'    => '此手机号码已被占用，请使用其他号码绑定',
        'pwd_validate_error'   => '密码需由6-16位字母或数字组成',
        'parameter_error'      => '传参错误',
        'validate_error'       => '验证码错误，请重新输入',
        'no_privilege'         => '无权限',
	    'sensitive_word'       => '包含敏感词',
	    'collect_repeat'       => '已收藏',
        'login_out_error'      => '未登录不能进行操作',
        'order_address_out'    => '不在配送范围内',
        'order_address_loss'   => '地址信息丢失',
        'topic_id_error'       => '专题id错误',
        'pwd_error'            => '密码有误，请重新输入',
        'img_code_error'       => '图片验证码错误',
        'pay_not_exist'        => '支付方式不存在',
        'order_not_exist'      => '该用户的订单不存在',
        'cart_goods_not_exist' => '您的购物车空空的',
        "pay_not_exist"        => '支付方式不存在',
    	'get_express_error'    => '物流信息获取失败',
        'mobile_miss'          => '手机号码未填写',
        'spu_id_error'         => '商品已下架或不存在',
        'sku_id_error'         => '未找到该规格的商品',
        'returnsnull_error'    => '没有更多了。',
        'sku_stocks_not_enough'=> '库存量不足',
        'sku_price_change'     => '商品价格变更了',
        'order_create_fail'    => '订单创建失败',
        'spu_not_sale'         => '零食已下架',
        'spu_stock_empty'      => '零食已售空',
        'spu_not_exist'        => '该零食不存在',
        'order_common_error'   => '您的订单内有商品信息失效，请重新购买',
        'brand_topic_not_exist'=> '该品牌团已下架',
        'supplier_id_not_exist'=> '供应商不存在',
        'coupon_id_not_exist'  => '优惠券不存在',
        'coupon_get_error'     => '领取失败，请稍后尝试',
        'coupon_is_offline'    => '优惠券已下架',
        'coupon_can_not_use'   => '该优惠券无法使用',
        'coupon_invalidate'    =>'6006',//订单内促销信息发生变化，请重新支付 客户端需要使用6006做判断
        'cart_num_max'         =>'购物车的最大商品数不能超过99',
        'gifts_not_exist'      => '大礼包领取失败,或无该礼包',
        'auth_code_not_match'  => '优惠券的授权码不正确',
        'order_refund_exist'   => '订单已处于售后处理状态'
    ),
    'PLATFORM'=>array(//手机平台
        'ios'=>1,
        'android'=>3
    ),
    //用户密匙设定

    'DEFAULT_ICON' => 'http://ds.lingshi.cccwei.com/statics/default.png',
    'NICK_NAME'    => '小喵小伙伴',
    'FEED_BACK_CONTENT'    => '欢迎反馈',// 反馈信息欢迎语
    'DEFAULT_MIAO_AUTHOR'  => array(
        "uid"       => 0,
        "nickname"  => "零食小喵",
        "icon"      => ''
    ),// 默认头像配置信息
    'AGIO_LINE'     => 8 // 折扣线 低于这个线的折扣率才会被显示
);
return  array_merge($array,$config);