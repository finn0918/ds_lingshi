<?php
/**
 * Created by PhpStorm.
 * User: nangua
 * Date: 2015/7/22
 * Time: 15:31
 */

class PayAction {

    /**
    +----------------------------------------------------------
     * 支付后的异步通知
     *
    +----------------------------------------------------------
     */
    public function notifyUrlAliPay(){
        require_once(THINK_PATH."Extend/Vendor/alipay/alipay.config.php");
        require_once(THINK_PATH."Extend/Vendor/alipay/lib/alipay_notify.class.php");
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);// 配置文件中存在该变量
        $verify_result = $alipayNotify->verifyNotify();
        //商户订单号
        $out_trade_no = htmlspecialchars($_POST['out_trade_no'],ENT_QUOTES);
        //支付宝交易号
        $trade_no = htmlspecialchars($_POST['trade_no'],ENT_QUOTES);
        $total_fee = $_POST['total_fee'];

        Log::write("支付的交易记录".var_export($_POST,true),Log::INFO);

        $time = time();
        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//            file_put_contents(ROOT_PATH.'/alipay_order_log.txt',var_export($_POST,true)."\n",FILE_APPEND);
            //商户订单号
            $out_trade_no = htmlspecialchars($_POST['out_trade_no'],ENT_QUOTES);

            // 判断返回的价格是否是正确的价格
            $order_mod = M("order");
            $sub_order_mod = M("sub_order");

            $real_money = $this->_finalSum($out_trade_no,$_POST);

            if($total_fee != $real_money){
                echo "fail";
                Log::write("这个订单有异常！返回信息".var_export($_POST,true),Log::WARN);
                die;
            }
            Log::write("我方计算出来的金额".$real_money,Log::INFO);
            //支付宝交易号

            //交易状态
            $trade_status = $_POST['trade_status'];

            $flag = true;// 默认是要发给亚历蒂斯
            $buyer_mobile = $order_mod->where("order_sn = '{$out_trade_no}'")->getField("mobile");
            // 查询配置表中是否存在该号码
            $app_config_mod = M("app_config");
            $test_mobiles = $app_config_mod->where("config_name = 'test_mobile' ")->getField("config_value");
            if(!empty($test_mobiles)){
                $mobiles_arr = explode(",",$test_mobiles);
                if(in_array($buyer_mobile,$mobiles_arr)){
                    $flag = false;
                }
            }


            $model = M();
            $model->startTrans();// 开始事务
            if($_POST['trade_status'] == 'TRADE_FINISHED') {
                // 判断该订单是否存在

                $data = array(
                    "status"    => 1,
                    "pay_sn"    => $trade_no,
                    "pay_time"  => $time,
                    "new_order" => 0,
                );
                //判断是否是首单 加入首单状态
                $user_new_order = $this->getIsNewOrder($out_trade_no);
                if(!$user_new_order){
                	$data['new_order'] = 1;
                }
                if( $order_mod->where("order_sn = '{$out_trade_no}' and status = 0 ")->save($data)){

                    // 更新订单状态。

                    $update_data = array(
                        'status'    =>  1,
                        'pay_time'  => $time
                    );
                   
                    if($sub_order_mod->where("order_sn = '{$out_trade_no}' and status = 0 ")->save($update_data)){
                        // 更新用户累计购买的金额
                        $client_mod = M("client");
                        // 接收支付宝通知金额
                        $user_id = $order_mod->where("order_sn = '{$out_trade_no}'")->getField("user_id");
                        // 需要存入 以分为单位的数据
                        $inc_total_fee = $_POST['total_fee']*100;
/********************************************是否发放优惠券判断开始**********start*******************************************************/
// 需要存入 以分为单位的数据
                        //$totle_price = $inc_total_fee  * 100;
						$totle_price = $inc_total_fee ;
                        $order_mod = M("order");
                        $order = $order_mod->where("order_sn = '{$out_trade_no}'")->field("pay_time,order_id")->find();
                        $pay_time = $order['pay_time'];
                        $order_id = $order['order_id'];
                        if(!empty($order_id) ){
                            $o = 1;
                        }
                        //判断是否大于99元
                        $startime = '1450368000';	//开始日期 2015-12-18
                        $endtime  = '1451836799';	//结束日期 2016-01-04
                        $k = 0;
                        if($pay_time>=$startime && $pay_time <=$endtime){
                            $k = 1;
                        }
						
                        if ($totle_price >= 9900 && $k && $o ) {
                            $coupon = M('coupon');
                            $coupon_num = $coupon->where("id in(134,135)")->field("coupon_num")->select();
                            $coupon_user_mod = M("coupon_user");
                            foreach($coupon_num as $k=>$v) {
                                $data = array(
                                    "user_id" => $user_id,
                                    "coupon_num" => $v['coupon_num'],
                                    "order_id" => $order_id,
                                    "send_time" => $pay_time,
                                    "is_show" => 1,
                                    "is_use" => 0
                                );
                                if (!$coupon_user_mod->add($data)) {
                                    Log::write("用户uid=>".$user_id."的单号为".$order_id."的优惠券没发放成功", Log::ERR);
                                }
                            }
                        }
						
/*******************************************是否发放优惠券判断结束*************end*******************************************************/                       
                        // 如果自增失败 则记录下
                        if(!$client_mod->where("user_id = $user_id")->setInc("money_pay",$inc_total_fee)){
                            $model->rollback();
                            echo "fail";
                            Log::write("订单号:".$out_trade_no."用户id:".$user_id,Log::WARN);
                        }else{
                           

                            if($flag == true){
//                                $this->_sendOrder($out_trade_no);// 亚历蒂斯商品发货
                                $this->_sendOrderedb($out_trade_no);// e店宝商品发货
                            }
                            $model->commit();
                            echo "success";
                        }
                    }else{
                        $model->rollback();
                        echo "fail";
                    }

                }else{
                    $model->rollback();
                    echo "fail";
                }
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                // 判断该订单是否存在
                $order_mod = M("order");
                $data = array(
                    "status"    => 1,
                    "pay_sn"    => $trade_no,
                    "pay_time"  => $time
                );
                //判断是否是首单 加入首单状态
                $user_new_order = $this->getIsNewOrder($out_trade_no);
                if(!$user_new_order){
                	$data['new_order'] = 1;
                }
                
                $is_ok = $order_mod->where("order_sn = '{$out_trade_no}'  and status = 0  ")->save($data);

                if( $is_ok){
                    // 更新订单状态。
                    $sub_order_mod = M("sub_order");
                    $update_data = array(
                        'status'    =>  1,
                        'pay_time'  => $time
                    );
                    if($sub_order_mod->where("order_sn = '{$out_trade_no}' and status = 0  ")->save($update_data)){
                        // 更新用户累计购买的金额
                        $client_mod = M("client");
                        // 接收支付宝通知金额
                        $user_id = $order_mod->where("order_sn = '{$out_trade_no}'")->getField("user_id");
                        // 需要存入 以分为单位的数据
                        $inc_total_fee = $_POST['total_fee']*100;
                        
                        
/********************************************是否发放优惠券判断开始**********start*******************************************************/
// 需要存入 以分为单位的数据
                        //$totle_price = $inc_total_fee  * 100;
						$totle_price = $inc_total_fee ;
                        $order_mod = M("order");
                        $order = $order_mod->where("order_sn = '{$out_trade_no}'")->field("pay_time,order_id")->find();
                        $pay_time = $order['pay_time'];
                        $order_id = $order['order_id'];
                        if(!empty($order_id) ){
                            $o = 1;
                        }
                        //判断是否大于99元
                        $startime = '1450368000';	//开始日期 2015-12-18
                        $endtime  = '1451836799';	//结束日期 2016-01-04
                        $k = 0;
                        if($pay_time>=$startime && $pay_time <=$endtime){
                            $k = 1;
                        }
                        if ($totle_price >= 9900 && $k && $o ) {
                            $coupon = M('coupon');
                            $coupon_num = $coupon->where("id in(134,135)")->field("coupon_num")->select();
                            $coupon_user_mod = M("coupon_user");
                            foreach($coupon_num as $k=>$v) {
                                $data = array(
                                    "user_id" => $user_id,
                                    "coupon_num" => $v['coupon_num'],
                                    "order_id" => $order_id,
                                    "send_time" => $pay_time,
                                    "is_show" => 1,
                                    "is_use" => 0
                                );
                                if (!$coupon_user_mod->add($data)) {
                                    Log::write("用户uid=>".$user_id."的单号为".$order_id."的优惠券没发放成功", Log::ERR);
                                }
                            }
                        }
/*******************************************是否发放优惠券判断结束*************end*******************************************************/                        
                        
                        // 如果自增失败 则记录下
                        if(!$client_mod->where("user_id = $user_id")->setInc("money_pay",$inc_total_fee)){
                            $model->rollback();
                            echo "fail";
                            Log::write("订单号:".$out_trade_no."用户id:".$user_id,Log::WARN);
                        }else{
                       
                            if($flag == true){

//                                $this->_sendOrder($out_trade_no);// 亚历蒂斯商品发货
                                $this->_sendOrderedb($out_trade_no);// e店宝商品发货
                            }
                            $model->commit();
                            echo "success";
                        }
                    }else{
                        $model->rollback();
                        echo "fail";
                    }
                }else{
                    $model->rollback();
                    echo "fail";
                }
            }
        }
        else {
            //验证失败
            echo "fail";
            Log::write("商户订单号：".$_POST['out_trade_no']."==>".$_POST['trade_status'],Log::WARN);
        }
    }

    /**
    +----------------------------------------------------------
     * 微信支付的异步通知
     *
    +----------------------------------------------------------
     */
    public function notifyUrlWXPay(){
        $xmlData = $GLOBALS["HTTP_RAW_POST_DATA"];
        Log::write("收到微信的异步通知内容".$xmlData,Log::INFO);
        if($xmlData){//如果返回的不为空
            $postObj = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA); //将XML载入对象
            if (! is_object($postObj)) {
                return false;
            }
            $array = json_decode(json_encode($postObj), true); // xml对象转数组
            $array = array_change_key_case($array, CASE_LOWER); // 所有键小写
            require_once (THINK_PATH."Extend/Vendor/wxpay/WxPay.Api.php");
            $input = new WxPayUnifiedOrder();
            if($array['return_code']=='SUCCESS'){  //如果返回的为成功的话
                //商户订单号
                $out_trade_no = htmlspecialchars($array['out_trade_no'],ENT_QUOTES);
                //微信交易号
                $trade_no = htmlspecialchars($array['transaction_id'],ENT_QUOTES);
                $total_fee = $array['total_fee'];

                Log::write("微信的交易记录".var_export($array,true),Log::INFO);
                $verify_result = $this->verifyWXSign($array); //验证微信返回结果的签名正确性
                $time = time();
                if($verify_result) {//验证成功
                    $order_mod = M("order");
                    $sub_order_mod = M("sub_order");
                    $model = M();
                    $model->startTrans();// 开始事务
                    // 判断该订单是否存在
                    $data = array(
                        "status"    => 1,
                        "pay_sn"    => $trade_no,
                        "pay_time"  => $time
                    );
                    if( $order_mod->where("order_sn = '{$out_trade_no}' and status = 0 ")->save($data)){
                        $update_data = array(
                            'status'    =>  1,
                            'pay_time'  => $time
                        );

                        if($sub_order_mod->where("order_sn = '{$out_trade_no}' and status = 0 ")->save($update_data)){
                            // 更新用户累计购买的金额
                            $client_mod = M("client");
                            // 接收微信通知金额
                            $user_id = $order_mod->where("order_sn = '{$out_trade_no}'")->getField("user_id");
                            // 需要存入 以分为单位的数据
                            $inc_total_fee = $array['total_fee'];
                            // 如果自增失败 则记录下
                            if(!$client_mod->where("user_id = $user_id")->setInc("money_pay",$inc_total_fee)){
                                $model->rollback();
                                Log::write("订单号:".$out_trade_no."用户id:".$user_id,Log::WARN);
                                echo "<xml><return_code><![CDATA[FAIL]]></return_code></xml>";
                            }else{
                                $this->_sendOrderedb($out_trade_no);// e店宝商品发货
                                $model->commit();
                                echo "<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>";
//                                $this->_sendOrderedb($out_trade_no);// e店宝商品发货
                            }
                        }else{
                            $model->rollback();
                            echo "<xml><return_code><![CDATA[FAIL]]></return_code></xml>";
                        }
                    }else{
                        $model->rollback();
                        echo "<xml><return_code><![CDATA[FAIL]]></return_code></xml>";
                    }
                }
                else {
                    //验证失败
                    echo "<xml><return_code><![CDATA[FAIL]]></return_code></xml>";
                    Log::write("商户订单号：".$out_trade_no."==>签名验证失败",Log::WARN);
                }
            }else{
                echo "<xml><return_code><![CDATA[FAIL]]></return_code></xml>";
            }
        }
    }
    /**
    +----------------------------------------------------------
     * 验证微信的签名正确性
     * @return boolean true/false
    +----------------------------------------------------------
     */
    public function verifyWXSign($sign_data)
    {
        $sign_wx = $sign_data['sign'];  //从微信获得的签名
        if (isset($sign_data['sign'])) unset($sign_data['sign']);
        ksort($sign_data);
        $sign_str = urldecode(http_build_query($sign_data));
        $sign_local = strtoupper(md5($sign_str.'&key='.WxPayConfig::KEY));
        return $sign_wx==$sign_local?ture:false;
    }

    /**
    +----------------------------------------------------------
     * 获得定单号
     * @return string
    +----------------------------------------------------------
     */
    public  static function getOrderId()
    {
        $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $i = intval(date('Y')) - 2010-1;
        return $year_code[$i] . date('md').
        substr(time(), -5) . substr(microtime(), 2, 5) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
    }

    /**
    +----------------------------------------------------------
     * 支付后发送订单信息给 供应商
     * 需要知道哪个供应商要与亚丽蒂斯对应
    +----------------------------------------------------------
     */

    private function _sendOrder($ordersn){
        $yalidisi = C("SECRET_KEY");
        $url = $yalidisi['yalidisi']['wsdl'];
        $order_mod = M("order");
        $sub_order_mod = M("sub_order");
        $order_info = $order_mod->where("order_sn = '$ordersn'")->find();
        // 获取亚丽蒂斯的订单金额 目前的办法是 所有商品都认为是亚丽蒂斯的商品
        $sub_order_data = $sub_order_mod->where("order_sn='$ordersn'")->select();
        $money = 0;
        $postmoney = 0;
        $sku_list_mod = M("sku_list");
        $suppliers_arr = array();
        $suppliers_arr['total_money'] = 0;
        $suppliers_arr['shipping_fee'] = 0;
        $suppliers_arr['suppliers_id'] = 0;
        // 记录拆单信息
        $sub_order_split_mod = M("sub_order_split");
        foreach($sub_order_data as $data_v){
            if($data_v['suppliers_id']!=8){
                continue;// 不是8的过滤掉
            }
            // 暂时的办法，只记录一个供应商id mark
            $suppliers_arr['suppliers_id'] = $data_v['suppliers_id'];
            $suppliers_arr['total_money'] += $data_v['sum_price'];
            $library_number = $sku_list_mod->where("sku_id = {$data_v['sku_id']}")->getField("library_number");
            $spu_attrs =  unserialize($data_v['spu_attr']);// 兼容后续多规格 因此存序列号结构
            $kinds = '';
            // 如果是单层则直接读取
            if(isset($spu_attrs['attr_name'])){
                $kinds = $spu_attrs['attr_name']." : ".$spu_attrs['attr_value'];
            }else{
                foreach($spu_attrs as $value ){
                    $kinds    =  $value['attr_name']." : ".$value['attr_value'];
                }
            }
            // 拆单
            $lib_arr = array();
            $lib_arr = explode("-", $library_number);

            // 说明是 20*A010 或者 A010
            if(count($lib_arr) == 1){
                $start_v = explode('*',current($lib_arr));
                if(count($start_v) ==1){
                    // 是A010
                    $productlist[] = array(
                        "oid"       => $data_v['sub_order_sn'],
                        "proname"  => $data_v['spu_name'],
                        "prosn"    => "{$library_number}",
                        "pronum"   => $data_v['nums'],
                        "proprice" => strval(price_format($data_v['price'])),
                        "proattributes"=> $kinds
                    );

                }else{
                    // 是 20*A010
                    $productlist[] = array(
                        "oid"       => $data_v['sub_order_sn'],
                        "proname"   => $data_v['spu_name'],
                        "prosn"     => "{$start_v[1]}",
                        "pronum"    => strval($data_v['nums']*$start_v[0]),
                        "proprice"  => strval(price_format($data_v['price'])),
                        "proattributes"=> $kinds
                    );

                }
            }else{
                $tmp_sn_arr = array();
                foreach ($lib_arr as $lib_v) {
                    $unit_v = explode("*",$lib_v);
                    // 防止出现 A010的情况
                    if(count($unit_v)==1){
                        $unit_v[0] = 1;
                        $unit_v[1] = $lib_v;
                    }
                    $tmp_order_sn = $this::getOrderId();
                    $tmp_sn_arr[$tmp_order_sn] =  array(
                        "prosn" => "{$unit_v[1]}",
                        "pronum"    => strval($data_v['nums']*$unit_v[0]),
                    );

                    $productlist[] = array(
                        "oid"       => $tmp_order_sn,
                        "proname"   => $data_v['spu_name'],
                        "prosn"     => "{$unit_v[1]}",
                        "pronum"    => strval($data_v['nums']*$unit_v[0]),
                        "proprice"  => strval(price_format($data_v['price'])),
                        "proattributes"=> $kinds
                    );
                }

                // 记录拆单的信息
                foreach ($tmp_sn_arr as $sn_k=>$sn_v) {
                    $tmp_data[] = array(
                        "order_sn"      => $data_v['order_sn'],
                        "sub_order_sn"  => $data_v['sub_order_sn'],
                        "split_order_sn" => $sn_k,
                        "prosn"         => $sn_v['prosn'],
                        "pronum"        => $sn_v['pronum']
                    );
                }

                // 记录拆分信息
                if(!empty($tmp_data)){
                    $sub_order_split_mod->addAll($tmp_data);
                }

            }
            // 拆单


            $suppliers_arr['shipping_fee'] = $suppliers_arr['shipping_fee']>$data_v['shipping_fee']?$suppliers_arr['shipping_fee']:$data_v['shipping_fee'];
        }
        // 说明这个单子里面没有亚历蒂斯的商品，就不发单子了。
        if($suppliers_arr['suppliers_id'] == 0){
            return false;
        }
        // 扣去优惠金额
        // 计算优惠券
        $suborder_coupon_mod = M("sub_order_coupon");
        $rule_free = $suborder_coupon_mod->where("order_sn = '$ordersn' ")->getField("rule_free");
        if(!empty($rule_free)){
            $suppliers_arr['total_money'] -= $rule_free;
        }
        // 计算N元选M件商品
        $suborder_active_mod = M("sub_order_active");
        $free_amount = $suborder_active_mod->where("order_sn ='$ordersn'")->getField("free_amount");
        if(!empty($free_amount)){
            $suppliers_arr['total_money'] -= $free_amount;
        }
        // 扣去优惠金额
        // 总金额需加上运费 mark，目前是单个供应商。
        $suppliers_arr['total_money'] += $suppliers_arr['shipping_fee'];
        $money = price_format($suppliers_arr['total_money']);
        $suppliers_arr['shipping_fee'] = price_format($suppliers_arr['shipping_fee']);
        $now_time = date("Y-m-d H:i:s");
        $yalidisi = C("SECRET_KEY");
        $code = $yalidisi['yalidisi']['secret_key'];
        $sellername = $yalidisi['yalidisi']['sellername'];

        switch($order_info['province']){
            case "北京市":
                $order_info['province'] = "北京"; break;
            case "天津市":
                $order_info['province'] = "天津"; break;
            case "上海市":
                $order_info['province'] = "上海"; break;
            case "重庆市":
                $order_info['province'] = "重庆"; break;
        }



        $data = array(
            "encrypt"       => md5($code.$now_time),
            "time"          => $now_time,
            "province"      => $order_info['province'],
            "city"          => $order_info['city'],
            "district"      => $order_info['proper'],
            "address"       => $order_info['street'],
            "sellername"   => $sellername,
            "ordersn"      => $ordersn,
            "addtime"      => date("Y-m-d H:i:s",$order_info["pay_time"]),
            "username"     => $order_info['consignee'],
            "money"         => strval($money),
            "postmoney"    => strval($suppliers_arr['shipping_fee']),
            "zip"           => "",
            "telphone"     => $order_info['mobile'],
            "mobile"       => $order_info['mobile'],
            "consignee"    => $order_info['consignee'],
            "paymethod"    => "alipay",
            "delivermethod"=> "express",
            "memo"          => $sub_order_data[0]['postscript'],
//            "username" => $username,
            "productlist" => $productlist
        );

        Log::write("上传给亚历蒂斯的商品:".var_export($data,true),Log::INFO);
        $data = array("in0"=>json_encode($data));
        $result_info = self::_soapCall($url,$data,"OrderDeliver");

        $order_sned_log_mod = M("order_send_log");
        $log_data = array(
            "order_sn"          => $ordersn,
            "suppliers_id"      => $suppliers_arr['suppliers_id'],
            "create_time"       => time()
        );

        Log::write("订单接口上传后亚历蒂斯返回的信息:".var_export($result_info,true),Log::INFO);

        if($result_info['success'] == "false"){
            Log::write("订单号:".$ordersn."未能同步到亚丽蒂斯",Log::WARN);
            $log_data['type']   = 0;
        }else{
            $log_data['type']   = 1;
        }
        $order_sned_log_mod->add($log_data);
        return $result_info;
    }


    /**
    +----------------------------------------------------------
     * Soap 版发起请求会话调用webServer 服务
    +----------------------------------------------------------
     */
    private function _soapCall($url,$data,$method){
        $client = new SoapClient($url);
        $data = $client->$method($data);
        $respon = json_decode($data->out,true);
        return $respon;
    }
    /**
    +----------------------------------------------------------
     * 计算订单实付金额
    +----------------------------------------------------------
     */
    private function _finalSum($order_sn,$post){
        $order_mod = M("order");
        $order = $order_mod->where("order_sn = '{$order_sn}'")->find();

        if(empty($order)){
            //　输出错误信息
            $alipay_time = strtotime($post['gmt_create']);
            $sep_month = strtotime("2015-09-01 00:00:00");
            if($alipay_time>$sep_month){
                echo "fail";
                Log::write("这个订单不存在，返回信息".var_export($_POST,true),Log::WARN);
            }else{
                echo "success";
                Log::write("这个订单不存在，返回信息,可能是测试账号的订单".var_export($_POST,true),Log::ERR);
            }
            die;
        }
        $sub_order_mod = M("sub_order");
        $price_all = $sub_order_mod->where("order_sn = '{$order_sn}' ")->field("sum_price,shipping_fee,suppliers_id")->select();
        $total_price = 0;
        $suppliers_fee_arr = array();

        foreach ($price_all as $v) {
            $total_price +=$v['sum_price'];
            if(isset($suppliers_fee_arr["{$v['suppliers_id']}"])){
                $suppliers_fee_arr["{$v['suppliers_id']}"] = $suppliers_fee_arr["{$v['suppliers_id']}"]>$v['shipping_fee']? $suppliers_fee_arr["{$v['suppliers_id']}"]:$v['shipping_fee'];
            }else{
                $suppliers_fee_arr["{$v['suppliers_id']}"] = $v['shipping_fee'];
            }
        }

        foreach ($suppliers_fee_arr as $v) {
            $total_price +=$v;
        }
        // 查询优惠券与订单的关系表
        $sub_order_coupon_mod = M("sub_order_coupon");
        $rule_free = $sub_order_coupon_mod->where("order_sn ='$order_sn' ")->getField("rule_free");
        if(!empty($rule_free)){
            $total_price -= $rule_free;
        }

        // 加入N元任意购的条件
        $suborder_active_mod = M("sub_order_active");
        $active_amount = 0;
        $free_amount   = $suborder_active_mod->where("order_sn = '{$order_sn}' ")->getField("free_amount");
        // mark 目前是只有一个供应商可以这样直接计算
        if(!empty($free_amount)){
            $active_amount = $free_amount;
        }

        $total_price = $total_price - $active_amount;
        $sum_price_in_list = $this->getSumPriceInListByOrder($order_sn);//获取订单的商品在满99减50的活动的列表里价格总和
        if($sum_price_in_list>=9900){
            $total_price -= 5000;
        }
        return price_format($total_price);
    }
    /**
    +----------------------------------------------------------
     * 支付后发送订单信息给 Edb E店宝
    +----------------------------------------------------------
     */

    private function _sendOrderedb($ordersn){


        $edb = C("SECRET_KEY");
        $url = $edb['edb']['url'];
        $order_mod = M("order");
        $sub_order_mod = M("sub_order");
        $order_info = $order_mod->where("order_sn = '$ordersn'")->find();
        // 获取要提交到E店宝的订单
        $sub_order_data = $sub_order_mod->where("order_sn='$ordersn'")->select();
        $money = 0;
        $postmoney = 0;
        $sku_list_mod = M("sku_list");
        $suppliers_arr = array();
        $suppliers_arr['total_money'] = 0;
        $suppliers_arr['shipping_fee'] = 0;
        $suppliers_arr['suppliers_id'] = 0;
        // 记录拆单信息
        $sub_order_split_mod = M("sub_order_split");
        foreach($sub_order_data as $data_v){
              // 将所有小喵自营单子都发给E店宝
            if($data_v['suppliers_id']!=4){
                continue;// 不是4的过滤掉
            }
            // 暂时的办法，只记录一个供应商id mark
            $suppliers_arr['suppliers_id'] = $data_v['suppliers_id'];
            $suppliers_arr['total_money'] += $data_v['sum_price'];
            $sku_number = $sku_list_mod->where("sku_id = {$data_v['sku_id']}")->getField("sku_number");
            $spu_attrs =  unserialize($data_v['spu_attr']);// 兼容后续多规格 因此存序列号结构
            $kinds = '';
            // 如果是单层则直接读取
            if(isset($spu_attrs['attr_name'])){
                $kinds = $spu_attrs['attr_name']." : ".$spu_attrs['attr_value'];
            }else{
                foreach($spu_attrs as $value ){
                    $kinds    =  $value['attr_name']." : ".$value['attr_value'];
                }
            }
            // 拆单
            $lib_arr = array();
            $lib_arr = explode("-", $sku_number);

            // 说明是 20*A010 或者 A010
            if(count($lib_arr) == 1){
                $start_v = explode('*',current($lib_arr));
                if(count($start_v) ==1){
                    // 是A010
                    $productlist[] = array(
                        "oid"       => $data_v['sub_order_sn'],
                        "proname"  => $data_v['spu_name'],
                        "prosn"    => "{$sku_number}",
                        "pronum"   => $data_v['nums'],
                        "proprice" => strval(price_format($data_v['price'])),
                        "proattributes"=> $kinds
                    );

                }else{
                    // 是 20*A010
                    $productlist[] = array(
                        "oid"       => $data_v['sub_order_sn'],
                        "proname"   => $data_v['spu_name'],
                        "prosn"     => "{$start_v[1]}",
                        "pronum"    => strval($data_v['nums']*$start_v[0]),
                        "proprice"  => strval(price_format($data_v['price'])),
                        "proattributes"=> $kinds
                    );

                }
            }else{
                $tmp_sn_arr = array();
                foreach ($lib_arr as $lib_v) {
                    $unit_v = explode("*",$lib_v);
                    // 防止出现 A010的情况
                    if(count($unit_v)==1){
                        $unit_v[0] = 1;
                        $unit_v[1] = $lib_v;
                    }
                    $tmp_order_sn = $this::getOrderId();
                    $tmp_sn_arr[$tmp_order_sn] =  array(
                        "prosn" => "{$unit_v[1]}",
                        "pronum"    => strval($data_v['nums']*$unit_v[0]),
                    );

                    $productlist[] = array(
                        "oid"       => $tmp_order_sn,
                        "proname"   => $data_v['spu_name'],
                        "prosn"     => "{$unit_v[1]}",
                        "pronum"    => strval($data_v['nums']*$unit_v[0]),
                        "proprice"  => strval(price_format($data_v['price'])),
                        "proattributes"=> $kinds
                    );
                }

                // 记录拆单的信息
                foreach ($tmp_sn_arr as $sn_k=>$sn_v) {
                    $tmp_data[] = array(
                        "order_sn"      => $data_v['order_sn'],
                        "sub_order_sn"  => $data_v['sub_order_sn'],
                        "split_order_sn" => $sn_k,
                        "prosn"         => $sn_v['prosn'],
                        "pronum"        => $sn_v['pronum']
                    );
                }

                // 记录拆分信息
                if(!empty($tmp_data)){
                    $sub_order_split_mod->addAll($tmp_data);
                }

            }
            // 拆单


            $suppliers_arr['shipping_fee'] = $suppliers_arr['shipping_fee']>$data_v['shipping_fee']?$suppliers_arr['shipping_fee']:$data_v['shipping_fee'];
        }
        // 说明这个单子里面没有亚历蒂斯的商品，就不发单子了。
        if($suppliers_arr['suppliers_id'] == 0){
            return false;
        }
        // 扣去优惠金额
        // 计算优惠券
        $suborder_coupon_mod = M("sub_order_coupon");
        $rule_free = $suborder_coupon_mod->where("order_sn = '$ordersn' ")->getField("rule_free");
        if(!empty($rule_free)){
            $suppliers_arr['total_money'] -= $rule_free;
        }
        // 计算N元选M件商品
        $suborder_active_mod = M("sub_order_active");
        $free_amount = $suborder_active_mod->where("order_sn ='$ordersn'")->getField("free_amount");
        if(!empty($free_amount)){
            $suppliers_arr['total_money'] -= $free_amount;
        }
        // 扣去优惠金额
        // 总金额需加上运费 mark，目前是单个供应商。
        $suppliers_arr['total_money'] += $suppliers_arr['shipping_fee'];
        $money = price_format($suppliers_arr['total_money']);
        $suppliers_arr['shipping_fee'] = price_format($suppliers_arr['shipping_fee']);
        $now_time = date("Y-m-d H:i:s");

        $code = $edb['edb']['secret_key'];
        $sellername = $edb['edb']['sellername'];

        switch($order_info['province']){
            case "北京市":
                $order_info['province'] = "北京"; break;
            case "天津市":
                $order_info['province'] = "天津"; break;
            case "上海市":
                $order_info['province'] = "上海"; break;
            case "重庆市":
                $order_info['province'] = "重庆"; break;
        }



        $data = array(
            "encrypt"       => md5($code.$now_time),
            "time"          => $now_time,
            "province"      => $order_info['province'],
            "city"          => $order_info['city'],
            "district"      => $order_info['proper'],
            "address"       => $order_info['street'],
            "sellername"   => $sellername,
            "ordersn"      => $ordersn,
            "createtime"   => date("Y-m-d H:i:s",$order_info["add_time"]),
            "addtime"      => date("Y-m-d H:i:s",$order_info["pay_time"]),
            "userid"       => $order_info['user_id'],
            "username"     => $order_info['consignee'],
            "money"        => strval($money),
            "postmoney"    => strval($suppliers_arr['shipping_fee']),
            "zip"          => "",
            "telphone"     => $order_info['mobile'],
            "mobile"       => $order_info['mobile'],
            "consignee"    => $order_info['consignee'],
            "paymethod"    => "alipay",
            "delivermethod"=> "express",
            "memo"          => $sub_order_data[0]['postscript'],
//            "username" => $username,
            "productlist" => $productlist
        );
        $sum_price_in_list = $this->getSumPriceInListByOrder($data['ordersn']);
        if($sum_price_in_list >= 9900){  //满99减50，需要把传给EDB的费用也减去50
            $data['money'] -= 50;
        }

        Log::write("上传给E店宝的商品:".var_export($data,true),Log::INFO);
        $data = json_encode($data);
//        p($data);

        $result_info = $this->curlrequest($url,$data);
    
        $order_sned_log_mod = M("order_send_log");
        $log_data = array(
            "order_sn"          => $ordersn,
            "suppliers_id"      => $suppliers_arr['suppliers_id'],
            "create_time"       => time()
        );

        Log::write("订单接口上传后E店宝返回的信息:".var_export($result_info,true),Log::INFO);

        if($result_info['success'] == "false"){
            Log::write("订单号:".$ordersn."未能同步到E店宝",Log::WARN);
            $log_data['type']   = 0;
        }else{
            $log_data['type']   = 1;
        }
        $order_sned_log_mod->add($log_data);
        return $result_info;
    }

    /**
    +----------------------------------------------------------
     * curl方式发送数据
    +----------------------------------------------------------
     */
    private function curlrequest($url,$data,$method='POST'){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $document = curl_exec($ch);

        if(!curl_errno($ch)){
            $info = curl_getinfo($ch);
        } else {
            //

            Log::write("请求错误".curl_error($ch),Log::WARN);
            curl_close($ch);
            // 这部分是你的处理代码
        }
        curl_close($ch);

        return $document;
    }
    
    //判断是否是首单（一次钱都没付过） 付过一次钱的不算
    private function getIsNewOrder($out_trade_no){
    	$order_mod = M("order");
		$user_id = $order_mod->where("order_sn = '{$out_trade_no}' " )->field('user_id')->find();

    	$o = $order_mod->where("user_id = '{$user_id[user_id]}' and status>=1 and pay_sn <> '' " )->select();
		return $o;
    }


    /**
     * 获取订单里的商品中在活动列表里的商品总价，单位为分
     */
    private function  getSumPriceInListByOrder($ordersn){
        $sum_price_in_list = 0;
        $relationMod = M('topic_relation');  //商品与活动的关系
        $subOrderModel = M('sub_order');
        $appMod = M("app_config");
        $topic = $appMod->where("config_name = '味蕾旅行'")->find();
        $spu_list = $relationMod->where("topic_id = '{$topic[config_value]}'")->getField('spu_id',true);
        $sub_data = $subOrderModel->field("sum_price,nums,price,spu_id")->where("order_sn='{$ordersn}'")->select();
//		print_r($spu_list);
//		print_r($sub_data);
        foreach ($sub_data as $v) {
            if (in_array($v['spu_id'], $spu_list)) {  //如果商品在活动商品列表里并且都是小喵自营商品
                $sum_price_in_list += $v['sum_price'];  //将在列表里的商品价格做累计计算
            }
        }
        return $sum_price_in_list;
    }
}
