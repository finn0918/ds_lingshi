<?php

class wodaAction extends Action{

    function index(){
        $this->display();
    }

    //oAuth2.0
    function oauthRegister() {
        // 当前登录用户
        $user_id = 1;

        // 来自用户表单
        $consumer = array(
            // 下面两项必填
            'requester_name'         => 'lingshi',
            'requester_email'        => 'lingshi@qq.com',

            // 以下均为可选
            /*
            'callback_uri'           => 'http://www.demo.com/oauth_callback',
            'application_uri'        => 'http://www.demo.com/',
            'application_title'      => 'Online Printer',
            'application_descr'      => 'Online Print Your Photoes',
            'application_notes'      => 'Online Printer',
            'application_type'       => 'website',
            'application_commercial' => 0
            */
        );
        $dbOptions = array(
            'server'   => C('DB_HOST'),
            'username' => C('DB_USER'),
            'password' => C('DB_PWD'),
            'database' => C('DB_NAME')
        );
        include_once THINK_PATH."Extend/Vendor/oauth/library/OAuthStore.php";


        // 注册消费方
        $store = OAuthStore::instance('MySQL', $dbOptions);
        $key   = $store->updateConsumer($consumer, $user_id);

        // 获取消费方信息
        $consumer = $store->getConsumer($key, $user_id);

        // 消费方注册后得到的 App Key 和 App Secret
        $consumer_id     = $consumer['id'];
        $consumer_key    = $consumer['consumer_key'];
        $consumer_secret = $consumer['consumer_secret'];

        // 输出给消费方
        echo 'Your App Key: ' . $consumer_key;
        echo '<br />';
        echo 'Your App Secret: ' . $consumer_secret;
    }

    function oauthRequestToken() {
        include_once THINK_PATH."Extend/Vendor/oauth/library/OAuthStore.php";
        include_once THINK_PATH."Extend/Vendor/oauth/library/OAuthServer.php";
        $dbOptions = array(
            'server'   => C('DB_HOST'),
            'username' => C('DB_USER'),
            'password' => C('DB_PWD'),
            'database' => C('DB_NAME')
        );
        $store = OAuthStore::instance('MySQL', $dbOptions);

        $server = new OAuthServer();
        $server->requestToken();
        exit();
    }

    //批量订单查询
    function orderQueryMuti() {
        $vcode       = isset($_GET['vcode'])       ? trim($_GET['vcode'])            : 0;    //商户密钥
        $status      = $_GET['status'];     //订单状态	0所有 1等待付款 2等待发货 3等待确认收货，4交易成功，5交易取消
        $ctime_start = isset($_GET['ctime_start']) ? strtotime($_GET['ctime_start']) : 0;    //订单创建时间起始
        $ctime_end   = isset($_GET['ctime_end'])   ? strtotime($_GET['ctime_end'])   : 0;    //订单创建时间截止
        $page        = isset($_GET['page'])        ? intval($_GET['page'])           : 0;
        $page_size   = isset($_GET['page_size'])   ? intval($_GET['page_size'])      : 100;

        $spuImageDB = M('spu_image');

        $code = 0;
        $msg = '无错误信息';
        //状态格式转换
        switch($status) {
            case '0' :
                //所有
                $statusCheck = '';
                break;
            case '1' :
                //待付款
                $statusCheck = '0';
                break;
            case '2' :
                //待发货
                $statusCheck = '1';
                break;
            case '3' :
                //等待确认收货
                $statusCheck = '2';
                break;
            case '4' :
                //交易成功
                $statusCheck = '5';
                break;
            case '5' :
                //交易取消(订单关闭)
                $statusCheck = '11';
                break;
        }
        //验证供应商
        $supp = M('suppliers');
        $suppWhere['key'] = $vcode;
        $suppDat = $supp->where($suppWhere)->find();
        if($suppDat) {
            //供应商ID，作为关联条件
            $suppName = $suppDat['suppliers_name'];
            //子单
            $subDb = M('sub_order');
            if(!empty($status)) {
                $subWhere['status'] = $statusCheck;
            }
            $subWhere['add_time'] = array(array('egt',$ctime_start),array('elt',$ctime_end));
            $subWhere['suppliers_name'] = $suppName;
            $subWhere['_logic'] = 'AND';

            $subOrderData = $subDb->where($subWhere)->limit($page,$page_size)->select();
            $orderCount = $subDb->where($subWhere)->count();

            if($subOrderData) {
                $main_order = array();
                foreach($subOrderData as $val) {
                    $propArr = array();
                    $goodsArr = array();
                    //避免主单相同重复查询
                    if(!isset($main_order["{$val['order_sn']}"])){
                        $db = M('order');
                        $orderWhere['order_sn'] = $val['order_sn'];
                        $orderData = $db->where($orderWhere)->find();
                        $main_order["{$val['order_sn']}"] = $orderData;
                    }
                    $newOrderData = $main_order["{$val['order_sn']}"];
                    //主单地址信息
                    $orderAddress = array(
                        'postcode'            => '',
                        'nickname'            => $newOrderData['consignee'],
                        'phone'               => $newOrderData['mobile'],
                        'address'             => $newOrderData['address'],
                        'province'            => $newOrderData['province'],
                        'city'                => $newOrderData['city'],
                        'district'            => $newOrderData['proper'],
                        'street'              => $newOrderData['street']
                    );
                    //属性
                    $spu_attrs = unserialize($val['spu_attr']);
                    foreach($spu_attrs as $d) {
                        $propArr[] = array(
                            'name'            => $d['attr_name'],
                            'value'           => $d['attr_value'],
                            'is_show'         => '1'
                        );
                    }

                    //商品图
                    $spuImageData = $spuImageDB->where('spu_id='.$val['spu_id'])->find();
                    $spuImageSer = unserialize($spuImageData['images_src']);
                    $spuImage = C('LS_IMG_URL').$spuImageSer['img_url'];

                    //状态说明文字
                    switch($val['status']) {
                        case '0' :
                            $val['status'] = '等待付款';
                            break;
                        case '1' :
                            $val['status'] = '等待发货';
                            break;
                        case '2' :
                            $val['status'] = '等待确认收货';
                            break;
                        case '3' :
                            $val['status'] = '交易成功';
                            break;
                        case '4' :
                            $val['status'] = '交易取消';
                            break;
                        case '5' :
                            $val['status'] = '交易成功';
                            break;
                        case '6' :
                            $val['status'] = '申请退货';
                            break;
                        case '7' :
                            $val['status'] = '退货中';
                            break;
                        case '8' :
                            $val['status'] = '退款中';
                            break;
                        case '9' :
                            $val['status'] = '退货完成(退款完成）';
                            break;
                        case '10' :
                            $val['status'] = '结算完成（与供应商）';
                            break;
                        case '11' :
                            $val['status'] = '交易关闭';
                            break;
                    }
                    $goodsArr[] = array(
                        'price'                   => $val['price']/100,
                        'goods_title'             => $val['spu_name'],
                        'short_title'             => $val['spu_name'],
                        'goods_img'               => $spuImage,
                        'amount'                  => $val['nums'],
                        'goods_no'                => $val['sku_id'],
                        'outer_id'                => '',
                        'prop'                    => $propArr,
                        'refund_status_text'      => ''                                           //商品退货/退款状态
                    );
                    //info
                    $orderArr = array(
                        'order_id'                => $val['sub_order_sn'],
                        'status_text'             => $val['status'],
                        'url'                     => '',                                          //web订单页地址
                        'total_price'             => $val['sum_price']/100,                       //总价
                        'ctime'                   => date('Y-m-d h:m', $val['add_time']),         //下单时间
                        'comment'                 => $val['postscript'],                          //留言
                        'express_price'           => $val['shipping_fee']/100,                    //运费
                        'express_id'              => empty($val['shipping_id']) ? '' : $val['shipping_id'],                         //运货单号
                        'express_company'         => empty($val['suppliers_com']) ? '' : $val['suppliers_com'],                     //快递公司
                        'pay_time'                => empty($subOrderData['pay_time']) ? '' : date('Y-m-d h:m',$subOrderData['pay_time']),
                        'send_time'               => empty($subOrderData['send_time']) ? '' : date('Y-m-d h:m',$subOrderData['send_time']),
                        'last_status_time'        => '',                                          //最后状态变更时间
                        'pay_time_out'            => '',
                        'receive_time_out'        => '',
                        'buyer_nickname'          => $newOrderData['consignee'],                  //买家名
                        'service_time_out'        => '',
                        'seller_note'             => ''                                           //卖家备注
                    );
                    $infoArr[] = array(
                        'order'       => $orderArr,
                        'goods'       => $goodsArr,
                        'address'     => $orderAddress
                    );
                }
            } else {
                $code = 1;
                $infoArr = '';
                $msg = '没有符合条件的订单';
            }
        } else {
            $code = 1;
            $infoArr = '';
            $msg = '无此供应商信息';
        }
        $data = array(
            'code'          => $code,
            'info'          => $infoArr,
            'total_num'     => $orderCount,
            'message'       => $msg
        );
        $this->ajaxReturn($data,'json');
    }

    //单个订单查询
    function orderQuerySingle() {
        $vcode = $_GET['vcode'];    //商户密钥
        $oid = $_GET['oid'];	    //订单编号

        $supp = M('suppliers');
        $mainDB = M('order');
        $subDb = M('sub_order');
        $spuImage = M('spu_image');

        $code = 0;
        $msg = '无错误信息';
        //验证供应商

        $suppWhere['key'] = $vcode;
        $suppDat = $supp->where($suppWhere)->find();
        if($suppDat) {
            //供应商ID，作为查询条件
            $suppName = $suppDat['suppliers_name'];
            //子单 用vcode过滤
            $subWhere['sub_order_sn'] = $oid;
            $subWhere['suppliers_name'] = $suppName;
            $subWhere['_logic'] = 'AND';
            $subOrderData = $subDb->where($subWhere)->find();
            if($subOrderData) {
                $mainOrderWhere['order_sn'] = $subOrderData['order_sn'];
                $mainOrderData = $mainDB->where($mainOrderWhere)->find();
                //收货信息
                $addressArr = array(
                    'postcode'            => '',
                    'nickname'            => $mainOrderData['consignee'],
                    'phone'               => $mainOrderData['mobile'],
                    'address'             => $mainOrderData['address'],
                    'province'            => $mainOrderData['province'],
                    'city'                => $mainOrderData['city'],
                    'district'            => $mainOrderData['proper'],
                    'street'              => $mainOrderData['street']
                );
                //属性
                $spu_attrs = unserialize($subOrderData['spu_attr']);
                //商品图
                $spuImageData = $spuImage->where('spu_id='.$subOrderData['spu_id'])->find();
                $spuImageSer = unserialize($spuImageData['images_src']);
                $spuImage = C('LS_IMG_URL').$spuImageSer['img_url'];

                $propArr = array();
                foreach($spu_attrs as $val) {
                    $propArr[] = array(
                        'name'            => $val['attr_name'],
                        'value'           => $val['attr_value'],
                        'is_show'         => '1'
                    );
                };
                //商品
                $goodsArr[] = array(
                    'price'                   => $subOrderData['price']/100,
                    'goods_title'             => $subOrderData['spu_name'],
                    'goods_img'               => $spuImage,
                    'amount'                  => $subOrderData['nums'],
                    'goods_no'                => $subOrderData['sku_id'],
                    'prop'                    => $propArr,
                    'refund_status_text'      => ''                         //商品退货/退款状态
                );
                //订单状态描述文字
                switch($subOrderData['status']) {
                    case '0' :
                        $statusText = '等待付款';
                        break;
                    case '1' :
                        $statusText = '等待发货';
                        break;
                    case '2' :
                        $statusText = '等待确认收货';
                        break;
                    case '3' :
                        $statusText = '交易成功';
                        break;
                    case '4' :
                        $statusText = '交易取消';
                        break;
                    case '5' :
                        $statusText = '交易成功';
                        break;
                    case '6' :
                        $statusText = '申请退货';
                        break;
                    case '7' :
                        $statusText = '退货中';
                        break;
                    case '8' :
                        $statusText = '退款中';
                        break;
                    case '9' :
                        $statusText = '退货完成(退款完成）';
                        break;
                    case '10' :
                        $statusText = '结算完成（与供应商）';
                        break;
                    case '11' :
                        $statusText = '交易关闭';
                        break;
                }
                $comment        = $subOrderData['postscript'];
                $expressPrice   = $subOrderData['shipping_fee']/100;
                $expressId      = $subOrderData['shipping_id'];
                $expressCompany = $subOrderData['suppliers_com'];
                $totalPrice     = $subOrderData['sum_price']/100;
                $orderArr = array(
                    'order_id'            => $subOrderData['sub_order_sn'],
                    'status_text'         => $statusText,
                    'total_price'         => $totalPrice,                                    //总价（分）
                    'comment'             => $comment,                                       //留言
                    'express_price'       => $expressPrice,                                  //运费
                    'express_id'          => empty($expressId) ? '' : $expressId,            //运货单号
                    'express_company'     => empty($expressCompany) ? '' : $expressCompany,  //快递公司
                    'ctime'               => date('Y-m-d h:m',$subOrderData['add_time']),    //下单时间
                    'last_status_time'    => '',
                    'pay_time_out'        => '',
                    'receive_time_out'    => '',
                    'service_time_out'    => '',
                );
                if(!empty($subOrderData['pay_time'])) {
                    $orderArr['pay_time'] = date('Y-m-d h:m',$subOrderData['pay_time']);
                }
                if(!empty($subOrderData['send_time'])) {
                    $orderArr['send_time'] = date('Y-m-d h:m',$subOrderData['send_time']);
                }
                $infoArr = array(
                    'order'     => $orderArr,
                    'goods'     => $goodsArr,
                    'address'   => $addressArr
                );
            } else {
                //订单不属于该vcode的供应商
                $code = 1;
                $infoArr = '';
                $msg = '无权查看此订单信息';
            }
        } else {
            $code = 1;
            $infoArr = '';
            $msg = '无此供应商信息';
        }

        $data = array(
            'code'          => $code,
            'info'          => $infoArr,
            'message'       => $msg
        );
        $this->ajaxReturn($data,'json');
    }

    function orderSend() {
        $vcode     = $_GET['vcode'];                        //打印密钥
        $oid       = $_GET['oid'];	                        //订单编号
        $express   = $_GET['express_company'];	            //快递公司
        $expressNo = $_GET['express_no'];                   //运单号
        //快递公司名字与"聚合数据"的编号对应
        if(strstr($express, '顺丰')) {
            $expressCode = 'sf';
        } else if(strstr($express, '申通')) {
            $expressCode = 'sto';
        } else if(strstr($express, '圆通')) {
            $expressCode = 'yt';
        } else if(strstr($express, '韵达')) {
            $expressCode = 'yd';
        } else if(strstr($express, '天天')) {
            $expressCode = 'tt';
        } else if(strstr($express, 'EMS')) {
            $expressCode = 'ems';
        } else if(strstr($express, '中通')) {
            $expressCode = 'zto';
        } else if(strstr($express, '汇通')) {
            $expressCode = 'ht';
        } else if(strstr($express, '全峰')) {
            $expressCode = 'qf';
        } else {
            //无法识别的公司
            $expressCode = 'unknown';
            $res['code'] = 0;
            $res['message'] = "无法识别的快递公司";
        }
        //验证供应商
        $supp = M('suppliers');
        $suppWhere['key'] = $vcode;
        $suppDat = $supp->where($suppWhere)->find();
        if($suppDat) {
            //供应商ID，作为查询条件
            $suppName = $suppDat['suppliers_name'];
            //子单 查询条件加入vcode过滤
            $subDb = M('sub_order');
            $subWhere['sub_order_sn'] = $oid;
            $subWhere['suppliers_name'] = $suppName;
            $subWhere['_logic'] = 'AND';
            $subOrderData = $subDb->where($subWhere)->find();
            if($subOrderData && $subOrderData['status'] == '1') {
                $save['shipping_id']  = $expressNo;     //运单号
                $save['shipping_com'] = $expressCode;   //快递公司
                $save['status'] = 2;                    //修改订单状态
                $save['send_time'] = time();            //修改发货时间
                $subOrderSaveData = $subDb->where($subWhere)->save($save);
                if($subOrderSaveData) {
                    $res['code'] = 0;
                    $res['message'] = "发货成功";
                } else {
                    $res['code'] = 1;
                    $res['message'] = "发货失败";
                }
            } else {
                //订单不存在 或 未付款
                $res['code'] = 1;
                $res['message'] = "无可发货的订单";
            }
        } else {
            $res['code'] = 1;
            $res['message'] = "无此供应商信息";
        }

        $this->ajaxReturn($res,'json');
    }

}
?>