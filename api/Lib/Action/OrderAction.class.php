<?php
/**
 * Created by PhpStorm.
 * User: nangua
 * Date: 2015/7/10
 * Time: 16:31
 */

class OrderAction extends BaseAction{
    /**
    +----------------------------------------------------------
     * 提交购物车到支付页面 2605
    +----------------------------------------------------------
     */
    public function index(){
        // 判断是否登录
        if($this->uid<=0){
            $this->errorView($this->ws_code['login_out_error'],$this->msg['login_out_error']);
        }
        $ids = htmlspecialchars($this->getField('ids'), ENT_QUOTES);// 获取购物车条目id
        $address_id = intval($this->getField('address_id'));// 传地址
        $coupon_id = intval($this->getFieldDefault("coupon_id",0));// 优惠券id

        $address_mod = M("address");

        if($address_id<=0){
            // 查询用户地址
            $default_addr = $address_mod->where("user_id = {$this->uid} and type=1")->find();
        }else{
            $default_addr = $address_mod->where("add_id = {$address_id} and user_id = {$this->uid}")->find();
        }

        if(empty($default_addr)){
            $address = array();
        }else{
            $address = array(
                "id"         => intval($default_addr['add_id']),
                "name"       => $default_addr['consignee'],
                "phone"      => $default_addr['phone'],
                "province"   => $default_addr['province'],
                "city"       => $default_addr['city'],
                "proper"     => $default_addr['proper'],
                "full_add"   => $default_addr['province']." ".$default_addr['city']." ".$default_addr['proper']." ".$default_addr['street'],
                "type"       => intval($default_addr['type'])
            );
        }
        //---------------勾选的购物车商品----------------
        $cart_mod = M("cart");
        $cart_list = $cart_mod->where("id in ($ids) and  user_id = {$this->uid}  ")->order("id asc")->select();
        if(empty($cart_list)){
            $this->errorView($this->ws_code['cart_goods_not_exist'],$this->msg['cart_goods_not_exist']);
        }

        //计算在满99减50活动里的商品价格
        $sum_price_in_list = $this->getSumPriceInList($cart_list);

            //---------------物流费用计算start----------------
        $user_area_code = '';
        if(!empty($address)){
            $shipping_template_area_mod = M("shipping_template_area");//
            $shipping_area_code_mod = M("shipping_area_code");// 地区编码模板
            $area_code = '';
            //----------------对直辖市特殊处理-----------------------
            switch($address['province']){
                case "北京市":
                case "天津市":
                case "上海市":
                case "重庆市":
                    $area_code = $shipping_area_code_mod->where("province = '{$default_addr['province']}' and city = '{$default_addr['proper']}'")->getField("code");
                    break;
                default :
                    $area_code = $shipping_area_code_mod->where("province = '{$default_addr['province']}' and city = '{$default_addr['city']}'")->getField("code");
                    break;
            }
            $user_area_code = $area_code;// 用户使用地址的地区代码
            //----------------对直辖市特殊处理-----------------------

            // 获取 运费模板
            $spu_mod = M("spu");
            $shipping_template_mod = M("shipping_template");
        }
        // 新用户包邮活动
        // 第一步判断用户是否资格享受活动。写成方法好修改
        //$client_free_arr = $this->clientFree($this->uid);

        //---------------物流费用计算end-----------------



        $cart_item = array();
        // 获取商品图片
        $spu_img_mod = M('spu_image');
        $spu_ids = arrayToStr($cart_list,"spu_id");
        $spu_img_list = $spu_img_mod->where("spu_id in ($spu_ids) and type = 1")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");// 使用spu_id 做键名
        //=======记录所选购物车中的商品===
        $coupon_spu_id_arr = array();
        $spu_id_sum_price  = array();// 用来记录同一个spu的商品
        $cart_spu_arr = array();// 记录下用户所购买的商品spu_id
        foreach ($cart_list as $v) {

            $supplier_id = $v['suppliers_id'];
            $spu_attrs =  unserialize($v['spu_attr']);// 兼容后续多规格 因此存序列化结构
            $kinds = '';
            foreach($spu_attrs as $value ){
                $kinds    =  $value['attr_name']." : ".$value['attr_value'];
            }
            if(!empty($default_addr)){
                $template_id = $spu_mod->where("spu_id = {$v['spu_id']}")->getField("shipping_template");
                // 万一获取不到运费模板就默认以12块钱计算  注意数据库中的记录以分为单位
                if(empty($template_id)){
                    $shipping_fee = C("DEFAULT_SHIPPING_PRICE")? C("DEFAULT_SHIPPING_PRICE"):1200;
                    $shipping_fee = $shipping_fee;
                }else{
                    $default_set = $shipping_template_mod->where("id = {$template_id}")->field("type,default_price")->find();
                    if($default_set['type']==1){// 买家承担运费
                        if(empty($area_code)){
                            $area_code = -1;// 给个负值 表示没有这个地区
                        }
                        $shipping_fee = $shipping_template_area_mod->where("template_id = {$template_id} and code = {$area_code} ")->getField("price");
                        $shipping_fee = $shipping_fee? $shipping_fee: $default_set['default_price'];
                    }else{
                        $shipping_fee = 0;
                    }
                }
            }else{
                $shipping_fee = 0;
            }

            $cart_state = cart_spu_status($v['spu_id'],$v['sku_id'],$v['price_now']);// 商品的 spu_id
            $state = $cart_state['state'];
            $cart_item[$supplier_id][] = array(
                "id"            => intval($v['id']),
                "goods_id"      => intval($v['spu_id']),
                "goods_title"   => $v['spu_name'],
                "state"         => intval($state),
                "num"           => intval($v['num']),
                "kinds"         => $kinds,
                "price"         => array(
                    "current"   => price_format($v['price_now']),
                    "prime"     => price_format($v['price_old'])
                ),
                "img"           => image($spu_img_list["{$v['spu_id']}"])
            );
            $cart_item[$supplier_id]['suppliers_name'] = $v['suppliers_name'];


            // 如果状态为0 才累计运费
            if($state==0){
                $cart_item[$supplier_id]['sum_price']     +=$v['price_now']*$v['num'];
                if(isset($cart_item[$supplier_id]['shipping_fee'])){
                    $cart_item[$supplier_id]['shipping_fee']  =  $cart_item[$supplier_id]['shipping_fee']>$shipping_fee ? $cart_item[$supplier_id]['shipping_fee']:$shipping_fee;
                }else{
                    $cart_item[$supplier_id]['shipping_fee'] = $shipping_fee;
                }

                if(!isset($cart_item[$supplier_id]['valid_num'])){
                    $cart_item[$supplier_id]['valid_num'] = 0;
                }
                $cart_item[$supplier_id]['valid_num'] +=$v['num'];

                $coupon_spu_id_arr[] = $v['spu_id'];//  只记录有效的商品
                // 累计同一个spu_id的金额 必须是有效的
                if(isset($spu_id_sum_price["{$v['spu_id']}"])){
                    $spu_id_sum_price["{$v['spu_id']}"] += $v['price_now']*$v['num'];
                }else{
                    $spu_id_sum_price["{$v['spu_id']}"] = $v['price_now']*$v['num'];
                }

                $cart_spu_arr["{$v['spu_id']}"] = array(
                    "sku_id"    => $v['sku_id'],
                    "price"     => $v['price_now']
                );

            }

        }

        //-------平台包邮与商家包邮判断start v2.1-------
        /*
         *  以商家或者以平台为单位包邮
         */
        $supplier_id_arr = array_keys($cart_item);// 获取所有的供应商id
        $supplier_id_str = implode(',',$supplier_id_arr);
        $free_fee_template_mod = M("free_fee_template");
        $now = time();
        $poster_ids = "";// 包邮信息的id
        // 先查询下有没有平台包邮的活动
        $plat_form_fee = $free_fee_template_mod->where("type = 2 and status='published' and start_time<={$now} and end_time>={$now}")->order("price asc")->find();

        $free_template_result = array();// 记录 供应商的包邮政策
        if(empty($plat_form_fee)){


            $free_template_result = $free_fee_template_mod->where("suppliers_id in($supplier_id_str) and status='published' and start_time<={$now} and end_time>={$now} ")->field("suppliers_id,price,id")->select();
            $temp_free_result = array();
            foreach ($free_template_result as $free_v) {
                if(isset($temp_free_result["{$free_v['suppliers_id']}"])){
                    // 如果下一个模板价格更低 则采用该模板
                    if($temp_free_result["{$free_v['suppliers_id']}"]['price']>$free_v['price']){
                        $temp_free_result["{$free_v['suppliers_id']}"]['price'] = $free_v['price'];
                        $temp_free_result["{$free_v['suppliers_id']}"]['id'] = $free_v['id'];
                    }
                }else{
                    $temp_free_result["{$free_v['suppliers_id']}"]['price'] = $free_v['price'];
                    $temp_free_result["{$free_v['suppliers_id']}"]['id'] = $free_v['id'];
                }
            }
        }

        //-------平台包邮与商家包邮判断end v2.1-------
        // 归类到大条目
        $cart_suppliers = array();
        $total_num = 0;
        $total_shipping_fee = 0;
        $spu_sum_price = 0; // 商品总价
        $plat_shipping_fee = 0;// 是否免邮费
        $free_fee_template_area_mod = M("free_fee_template_area");
        // 计算用户购买的总价格
        foreach($cart_item as $k=>$v){
            $sum_price = $v['sum_price'];
            $spu_sum_price += $sum_price;
        }
        // 如果存在平台包邮
        if(!empty($plat_form_fee)){

            if($plat_form_fee['price']<=$spu_sum_price){

                // 满足了价格之后，需要判断是否满足地区
                if(empty($user_area_code)){// 还没有地址
                    $plat_shipping_fee = 1;
                }else{
                    $free_area = $free_fee_template_area_mod->where("template_id = {$plat_form_fee['id']} and code = {$user_area_code}")->find();

                    if($free_area){
                        $poster_ids = $plat_form_fee['id'].",";
                        $plat_shipping_fee = 1;
                    }
                }
            }
        }



        foreach ($cart_item as $k => $v) {


            // 只有在平台包邮生效，且该用户所选地址符合平台包邮。
            if(!empty($plat_form_fee)&&$plat_shipping_fee==1) {
                $shipping_fee = 0;
            }else{
                // 需要支付的邮费。
                $shipping_fee = $v['shipping_fee'];
                // 如果该供应商有包邮活动
                if(isset($temp_free_result[$k])){
                    // 再判断包邮的条件，是否满足该供应商的总价
                    if($temp_free_result[$k]['price']<=$v['sum_price']){
                        if($user_area_code!=''){

                            // 下一步判断是否 是在指定的地区
                            $template_id = $temp_free_result[$k]['id'];
                            $is_free_area = $free_fee_template_area_mod->where("template_id = $template_id and code=$user_area_code")->count();
                            if(!empty($is_free_area)){
                                $poster_ids .= $temp_free_result[$k]['id'].",";
                                $shipping_fee = 0;
                            }
                        }else{
                            $shipping_fee = 0;
                        }
                    }
                }
            }

            $suppliers_name = $v['suppliers_name'];
            $sum_price = $v['sum_price'];

            $valid_num = $v['valid_num'];
            unset($v['shipping_fee']);
            unset($v['suppliers_name']);
            unset($v['sum_price']);
         // 新用户包邮活动
        // 第一步判断用户是否资格享受活动。写成方法好修改
        $client_free_arr = $this->clientFree($this->uid);unset($v['valid_num']);
            
            // 在处理运费的节点做判断,看看该不该给他免邮
            $shipping_fee = $this->clientShipping($k,$shipping_fee,$client_free_arr,$spu_sum_price);

            $cart_suppliers[] = array(
                "id"        => intval($k),
                "name"      => $suppliers_name,
                "items"     => $v,
                "num"       => intval($valid_num),
                "sum_price" => price_format($sum_price+$shipping_fee),
                "freight"   => price_format($shipping_fee),// 需计算
                "note"      => ''
            );


            $total_shipping_fee += $shipping_fee;
            $total_num += $sum_price + $shipping_fee;

        }




        //---------------勾选的购物车商品---------------

        // 没有记录不返回，有记录返回？
        $order = array(
            "order_sn"          => '',
            "type"              => 0,// 未付款
            "cart_suppliers"    => $cart_suppliers,
//            "infos"             => array(), !! 不需要了
//            "logistics"         => new stdClass(), !! 不需要了
            "poster_ids"         => trim($poster_ids,","),
            "sum_freight"       => price_format($total_shipping_fee)
        );
        // 如果是 version ==12 的版本才返回 可用优惠券数量
        if($this->version >= 12){
            // N元任意购
            $arbitrary_buy_mod = M("arbitrary_buy");
            $arbitrary = $arbitrary_buy_mod->where("status = 'published' ")->field("money,num,spu_list,start_time,end_time")->find();
            $spu_arr_unique = array_keys($cart_spu_arr);
            $any_buy_free = 0; // 任意购的优惠金额
            $any_buy_spu = array();// 计算到任意购的商品
            if(!empty($arbitrary)){
                if($arbitrary["start_time"]<=$now && $now<=$arbitrary['end_time']){

                    $buy_num = count($spu_arr_unique);
                    // 只有在用户购买的商品达到这个数量，我们才要去考虑他的商品里面有没有满足任意购活动商品。减少不必要的查询
                    if($buy_num >= $arbitrary['num']){
                        // 查询此次任意购的商品有哪些
                        $topic_relation_mod = M("topic_relation");
                        $active_spu_arr= $topic_relation_mod->where("topic_id = {$arbitrary['spu_list']}")->getField("spu_id",true);
                        // 与用户购买的商品取交集
                        $buy_active_arr = array_intersect($spu_arr_unique,$active_spu_arr);
                        // 活动到用户购买的任意购的商品 再次判断购买数量是否符合要求
                        $buy_num = count($buy_active_arr);
                        if($buy_num >= $arbitrary['num']){// 购买的不同商品大于设定的值
                            // 算出优惠价 1、先计算理应支付费用 2、再扣去定额费用，= 优惠金额
                            $real_money = 0;
                            // 下一步判断
                            $share_log_mod = M("share_log");
                            $share_log = $share_log_mod->where("user_id = {$this->uid} and type = 2")->find();
//                            p($share_log_mod->getLastSql());
                            $inc_num = 0;// 奖励的额外购买商品数
                            if(!empty($share_log)){
                                $inc_num = 1;
                            }
                            $max_free_num = $arbitrary['num'] + $inc_num ;// -1 因为$k是从0 开始
                            $i = 1;

                            foreach($buy_active_arr as $spu_id_v){

                                if($i>$max_free_num){
                                    break;
                                }
                                $any_buy_spu[] = $spu_id_v;

                                $real_money += $cart_spu_arr[$spu_id_v]['price'];
                                $i++ ;
                            }
                            $any_buy_free = $real_money -  $arbitrary['money'];

                            $total_num -= $any_buy_free;
                        }
                    }
                }
            }
            $order['discount_amount'] = price_format($any_buy_free);
            $now = time();
            $discount_num = 0;
            $any_buy_spu_count = count($any_buy_spu);// 任意购的商品总计
            // 计算出用户所拥有的有效优惠券
            $coupon_user_mod = M("coupon_user");
            $coupon_arr = $coupon_user_mod->where("user_id = {$this->uid} and is_use=0 and is_show = 1")->getField("coupon_num",true);// 获取用户的所有优惠券
            // 没有 优惠券 信息 直接判定为 0
            if(empty($coupon_arr)){
                $discount_num = 0;
            }else{
                // 有优惠券信息 判定是否有有效的优惠券
                $coupon_strs = "'".implode('\',\'',$coupon_arr)."'";
                // 判定 哪些优惠券有效。
                $coupon_mod = M("coupon");
                $coupon_arr = $coupon_mod->where("coupon_num in($coupon_strs)  and use_start_time<={$now} and use_end_time>={$now}")->getField("coupon_num",true);

                if(empty($coupon_arr)){
                    $discount_num = 0;
                }else{

                    // 在有效期内的优惠券。
                    $coupon_strs = "'".implode('\',\'',$coupon_arr)."'";
                    $coupon_spu_mod = M("coupon_spu");
                    $coupon_spu_id_arr = array_unique($coupon_spu_id_arr);// 所选购物车的商品去重。可能存在多个sku
                    $coupon_spu_id_strs = implode(",",$coupon_spu_id_arr).",1";// 连上表示全部商品的优惠券

                    // 表示即在用户所拥有的有优惠券，也在 所购买商品可以使用的优惠券
                    $coupon_num_arr = $coupon_spu_mod->where("coupon_num in($coupon_strs) and spu_id in($coupon_spu_id_strs) ")->select();

                    if(empty($coupon_num_arr)){
                        $discount_num = 0;
                    }else{
                        $coupon_num_arr_format = arrayFormat($coupon_num_arr,"coupon_num");
                        $coupon_num_arr_keys   = array_keys($coupon_num_arr_format);
//                        $coupon_num_arr_keys   = array_unique($coupon_num_arr_keys);// 去除重复的优惠券
                        // 判断是否达到优惠券的门槛。
                        $coupon_mod = M("coupon");
                        $coupon_num_arr_keys_strs = "'".implode('\',\'',$coupon_num_arr_keys)."'";

                        // 记录 每个优惠券所对应的商品的
                        $coupon_spu_relation = array();
                        foreach ($coupon_num_arr as $coupon_v) {
                            $coupon_spu_relation["{$coupon_v['coupon_num']}"][] = $coupon_v['spu_id'];
                        }
                        $coupon_valid_list = $coupon_mod->where("coupon_num in($coupon_num_arr_keys_strs)")->field("suppliers_id,coupon_num,rule_money,rule_free")->select();
                        // 逐个判断 优惠券的有效性
                        $coupon_valid_nums = array();
                        $tmp_cart_suppliers = arrayFormat($cart_suppliers,"id");
                        foreach($coupon_valid_list as $valid_v){
                            // 判断这个优惠券的所属商品是否达到要求
                            $tmp_spu_id_sum_price = 0;
                            $tmp_spu_id_any = 0;
                            foreach ( $coupon_spu_relation[$valid_v['coupon_num']] as $relation_spu_id) {
                                $tmp_spu_id_sum_price += $spu_id_sum_price[$relation_spu_id];
                                if(in_array($relation_spu_id,$any_buy_spu)){
                                    $tmp_spu_id_any++;
                                }
                            }
                            // 先判断是不是平台的优惠券 平台优惠券计算的是总价
                            /*
                             *  mark目前对于多供应商的金额分配还为定论。对于全平台的优惠券使用
                             */
                            if($valid_v['suppliers_id'] == 0){
                                if(($sum_price_in_list<9900)&&($spu_sum_price >= ($valid_v['rule_money'] - $total_shipping_fee + $any_buy_free ))){ //如果不满足满99减50的条件
                                    $coupon_valid_nums[]=$valid_v['coupon_num'];
                                    $discount_num++;// 累计上可用优惠券
                                }else if(($sum_price_in_list>=9900)&&(($spu_sum_price-$sum_price_in_list) >= ($valid_v['rule_money'] - $total_shipping_fee + $any_buy_free ))){//满足满99减50的条件
                                    $coupon_valid_nums[]=$valid_v['coupon_num'];
                                    $discount_num++;// 累计上可用优惠券
                                }
                            }else{
                            /*
                             *  mark目前对于多供应商的金额分配还为定论。对于多供应商
                             */
                                $shipping_fee = 0;
                                $tmp_any_buy  = 0;
                                if(isset($tmp_cart_suppliers["{$valid_v['suppliers_id']}"])){
                                    $shipping_fee = $tmp_cart_suppliers["{$valid_v['suppliers_id']}"]['freight'];
                                }

                                if(!empty($any_buy_spu)){
                                    $tmp_any_buy = ceil($any_buy_free*$tmp_spu_id_any/$any_buy_spu_count);
                                }
                                $tmp_shipping_fee = intval(strval($shipping_fee*100));// 避免浮点数计算产生问题
                                if(($sum_price_in_list<9900)&&(($tmp_spu_id_sum_price)>= ($valid_v['rule_money'] - $tmp_shipping_fee + $tmp_any_buy))){ //如果不满足满99减50的条件
                                    $coupon_valid_nums[] = $valid_v['coupon_num'];
                                    $discount_num++;// 累计上可用优惠券
                                }else if(($sum_price_in_list>=9900)&&(($tmp_spu_id_sum_price-$sum_price_in_list)>= ($valid_v['rule_money'] - $tmp_shipping_fee + $tmp_any_buy))){//满足满99减50的条件
                                    $coupon_valid_nums[] = $valid_v['coupon_num'];
                                    $discount_num++;// 累计上可用优惠券
                                }
                            }
                        }
                    }

                }
            }
//            p($coupon_valid_nums);
            // 在有优惠券的情况下。判断
            $free_money = 0;

            if($coupon_id>0){

                $coupon_select = $coupon_mod->where("id = $coupon_id")->getField("coupon_num");
                if(empty($coupon_select)){
                    $this->errorView($this->ws_code['coupon_id_not_exist'],$this->msg['coupon_id_not_exist']);
                }

                if(!in_array($coupon_select,$coupon_valid_nums)){
                    $this->errorView($this->ws_code['coupon_can_not_use'],$this->msg['coupon_can_not_use']);
                }

                // 看看能扣多少钱
                $coupon_valid_list = arrayFormat($coupon_valid_list,'coupon_num');
                $free_money = $coupon_valid_list[$coupon_select]['rule_free'];
                $total_num -= $free_money;
            }
            if($sum_price_in_list >=9900){   //如果满足列表里的商品金额大于99元并且计算的是小喵自营商品的运费
                $order['discount_amount']  += 50; //优惠金额加上50
                $total_num -= 5000; //总价减掉50
            }
            // 经过一番计算得出了优惠券的可用数量
            $order['discoupon_num'] = intval($discount_num);
            $order['validation_amount'] = free_format($free_money);
            $order['sum_goods'] = price_format($spu_sum_price);

        }

        $order['final_sum'] = price_format($total_num);
        // 不为空则有记录
        if(!empty($address)){
            $order['address'] = $address;
        }
        // 记录下这个页面的信息
        Log::write("用户的购买信息".var_export($order,true),LOG::INFO);

        $this->successView("",$order);
    }

    /**
    +----------------------------------------------------------
     * 未付款订单结算，提交并获取签名信息 2708
    +----------------------------------------------------------
     */
    public function payOrder(){
        $order_sn_old = htmlspecialchars($this->getField("order_sn"),ENT_QUOTES);// 订单号
        $order_sn = substr($order_sn_old,1);
        $version   = intval($this->getField("version"));//版本号
        $order_mod = M("order");


        $order = $order_mod->where("user_id = {$this->uid} and order_sn = '{$order_sn}' ")->find();
        if(empty($order)){
            $this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);//
        }
        // 计算总费用
        $sub_order_mod = M('sub_order');
        $sub_order_list = $sub_order_mod->where("order_sn = '{$order_sn}' ")->select();
        $total_price = 0;
        $suppliers = array();
        

        foreach ($sub_order_list as $v) {

            $suppliers["{$v['suppliers_id']}"]['shipping_fee'] = $suppliers["{$v['suppliers_id']}"]['shipping_fee']>$v['shipping_fee']?$suppliers["{$v['suppliers_id']}"]['shipping_fee']:$v['shipping_fee'];
            $total_price +=$v['sum_price'];
        }
        // 合计运费跟商品总价
        foreach ($suppliers as $v) {
            $total_price  += $v['shipping_fee'] ;
        }
        // 查询优惠券与订单的关系表
        $sub_order_coupon_mod = M("sub_order_coupon");
        $rule_free = $sub_order_coupon_mod->where("order_sn ='$order_sn'")->getField("rule_free");
        if(!empty($rule_free)){
            $total_price -=$rule_free;
        }

        $suborder_active_mod = M("sub_order_active");
        $active_amount = 0;
        $free_amount   = $suborder_active_mod->where("order_sn = '{$order_sn}' ")->getField("free_amount");
        // mark 目前是只有一个供应商可以这样直接计算
        if(!empty($free_amount)){
            $active_amount = $free_amount;
        }

        $total_price = $total_price - $active_amount;
// 查询 库存情况

        $result = $this->_checkStock($sub_order_list,$order,true);

        // 如果判断为库存缺失，这可能是有的库存为0了或者用户购买的数量库存提供不了
        if($result == false){
            $spu_mod = M('spu');
            foreach ($sub_order_list as $order_v) {
                $spu_extend = spu_format($order_v['spu_id']);
                if($spu_extend['stocks'] <= 0){
                    $spu_mod->where("spu_id = {$order_v['spu_id']}")->setField("status",1);// 商品失效 售空
                }
            }
            $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);//
        }

        $sum_price_in_list = $this->getSumPriceInListByOrder($order_sn);  //获取订单中的商品在活动列表里的价格
        if($sum_price_in_list>=9900){
            $total_price -= 5000;
        }

        // 支付方式
        if($version<18){  //支付宝支付18 之前的版本，只支持支付宝支付，版本18开始，从传入的参数中找支付方式,微信支付
            require_once(THINK_PATH."Extend/Vendor/alipay/alipay.config.php");
            require_once(THINK_PATH."Extend/Vendor/alipay/lib/alipay_notify.class.php");
            $data = array(
                "partner"		=> $alipay_config['partner'],
                "seller_id"		=> C("SELLER_EMAIL"),
                "out_trade_no"  => $order_sn,
                "subject"		=> '小喵的订单',
                "body"			=> C("ORDER_DESC"),
                "total_fee"		=> price_format($total_price),
                "notify_url"	=> C('ALIPAY_NOTIFY_URL'),
                "service"		=> "mobile.securitypay.pay",
                "payment_type"	=> 1,
                "_input_charset"=> $alipay_config['input_charset'],
                "it_b_pay"		=> "30m",
                "show_url"		=> "m.alipay.com"
            );

            //除去待签名参数数组中的空值和签名参数
            $str = "partner=\"{$data['partner']}\"&seller_id=\"{$data['seller_id']}\"&out_trade_no=\"{$data['out_trade_no']}\"&".
                "subject=\"{$data['subject']}\"&body=\"{$data['body']}\"&total_fee=\"{$data['total_fee']}\"&notify_url=\"{$data['notify_url']}\"&".
                "service=\"{$data['service']}\"&payment_type=\"{$data['payment_type']}\"&_input_charset=\"{$data['_input_charset']}\"&it_b_pay=\"{$data['it_b_pay']}\"&show_url=\"{$data['show_url']}\"";
            $rsa = rsaSign($str,THINK_PATH."Extend/Vendor/alipay/".trim($alipay_config['private_key_path']));
            $rsa = urlencode($rsa);
            $str = $str.'&sign="'.$rsa.'"&sign_type="RSA"';
            $data = array(
                "order_sn"      => $order_sn_old,
                "type"          => intval($order['pay_type']),
                "pay_info"      => $str
            );
        }else if((($version>=18)))  ////版本18开始，从传入的参数中找支付方式
        {
            $type   = intval($this->getField("type"));//非必须传type 支付类型：0，支付宝；1，微信；3，银联
            if($type==0){  //支付宝支付
                require_once(THINK_PATH."Extend/Vendor/alipay/alipay.config.php");
                require_once(THINK_PATH."Extend/Vendor/alipay/lib/alipay_notify.class.php");
                $data = array(
                    "partner"		=> $alipay_config['partner'],
                    "seller_id"		=> C("SELLER_EMAIL"),
                    "out_trade_no"  => $order_sn,
                    "subject"		=> '小喵的订单',
                    "body"			=> C("ORDER_DESC"),
                    "total_fee"		=> price_format($total_price),
                    "notify_url"	=> C('ALIPAY_NOTIFY_URL'),
                    "service"		=> "mobile.securitypay.pay",
                    "payment_type"	=> 1,
                    "_input_charset"=> $alipay_config['input_charset'],
                    "it_b_pay"		=> "30m",
                    "show_url"		=> "m.alipay.com"
                );

                //除去待签名参数数组中的空值和签名参数
                $str = "partner=\"{$data['partner']}\"&seller_id=\"{$data['seller_id']}\"&out_trade_no=\"{$data['out_trade_no']}\"&".
                    "subject=\"{$data['subject']}\"&body=\"{$data['body']}\"&total_fee=\"{$data['total_fee']}\"&notify_url=\"{$data['notify_url']}\"&".
                    "service=\"{$data['service']}\"&payment_type=\"{$data['payment_type']}\"&_input_charset=\"{$data['_input_charset']}\"&it_b_pay=\"{$data['it_b_pay']}\"&show_url=\"{$data['show_url']}\"";
                $rsa = rsaSign($str,THINK_PATH."Extend/Vendor/alipay/".trim($alipay_config['private_key_path']));
                $rsa = urlencode($rsa);
                $str = $str.'&sign="'.$rsa.'"&sign_type="RSA"';
                $data = array(
                    "order_sn"      => $order_sn_old,
                    "type"          => intval($order['pay_type']),
                    "pay_info"      => $str
                );
            }else if($type==1){  //微信支付
                require_once(THINK_PATH."Extend/Vendor/wxpay/WxPay.Api.php");
                require_once(THINK_PATH."Extend/Vendor/wxpay/WxPay.JsApiPay.php");

                //①、获取用户openid
//            $tools = new JsApiPay();
//                $openId = $tools->GetOpenid();
//                echo "OPID是".$openId;exit;

                //②、统一下单
                $input = new WxPayUnifiedOrder();
                $input->SetBody(C("ORDER_DESC"));
                $input->SetOut_trade_no($order_sn);
                $input->SetTotal_fee(intval($total_price));
                $input->SetNotify_url(C("WXPAY_NOTIFY_URL"));
                $input->SetTrade_type("APP");

                $order = WxPayApi::unifiedOrder($input);
//            echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
//                $jsApiParameters = $tools->GetJsApiParameters($order);
//                print_r($jsApiParameters);

                $data = array(
                    "order_sn"      => $order_sn_old,
                    "type"          => 1,
                    "pay_info"      => json_encode($order)
                );
            }
        }
        $this->successView('',$data);
    }


    /**
    +----------------------------------------------------------
     *  确认订单，提交并获取签名信息 2702
    +----------------------------------------------------------
     */
    public function pay(){
        require_once(THINK_PATH."Extend/Vendor/alipay/alipay.config.php");
        require_once(THINK_PATH."Extend/Vendor/alipay/lib/alipay_notify.class.php");
        require_once(THINK_PATH."Extend/Vendor/wxpay/WxPay.Api.php");
        require_once(THINK_PATH."Extend/Vendor/wxpay/WxPay.JsApiPay.php");
        $add_id = intval($this->getField("add_id"));
        $ids = htmlspecialchars($this->getField('ids'), ENT_QUOTES);// 获取购物车条目id
        $type   = intval($this->getField("type"));//必须传type 支付类型：0，支付宝；1，微信；3，银联
        $coupon_id = intval($this->getFieldDefault("coupon_id",0));// 优惠券的id
        $poster_ids = htmlspecialchars($this->getFieldDefault('poster_ids',''), ENT_QUOTES);// 获取包邮id
        $notes  = json_decode($this->getFieldDefault("notes",''));//备注的json串，见备注
        $notes  = object_to_array($notes);// json 对象转数组

        // 格式化转换
        $suppliers = array();
        foreach ($notes as $v) {
            $suppliers["{$v['id']}"] = $v['note'];
        }

        if($this->uid<=0){
            $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//            $this->errorView($this->ws_code['login_out_error'],$this->msg['login_out_error']);
        }


        $address_mod = M("address");
        $address = $address_mod->where("add_id = {$add_id} and user_id = {$this->uid} ")->find();

        if(empty($address)){

            // order_common_error
            $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//            $this->errorView($this->ws_code['order_address_loss'],$this->msg['order_address_loss']);
        }

        $order_sn = $this::getOrderId();
        if($type==0||$type==1){   //支付宝支付或微信支付
            //----------------生成订单 start--------------------
            $cart_mod = M("cart");
            //----------------生成订单 end  --------------------

            $cart_list = $cart_mod->where("user_id = {$this->uid} and id in($ids)")->order("id asc")->select();
            $sum_price_in_list = $this->getSumPriceInList($cart_list);  //获取在活动列表里的商品总价
            // 与供应商下单对接判断
            $result_judge = $this->_checkStock($cart_list,$address);

            // 如果判断为库存缺失，这可能是有的库存为0了或者用户购买的数量库存提供不了
            if($result_judge == false){
                $spu_mod = M('spu');
                $tmp_spu_arr = array();
                foreach ($cart_list as $judge_v) {
                    $tmp_spu_arr[] = $judge_v['spu_id'];
                }
                foreach ($tmp_spu_arr as $spu_v) {
                    $spu_extend = spu_format($spu_v);
                    if($spu_extend['stocks'] <= 0){
                        $spu_mod->where("spu_id = {$spu_v['spu_id']}")->setField("status",1);// 商品失效 售空
                    }
                }
                $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);//
            }

            // 与供应商下单对接判断


            if(empty($cart_list)){

                $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//                $this->errorView($this->ws_code['cart_goods_not_exist'],$this->msg['cart_goods_not_exist']);
            }

            $time = time();
            // 订单数据整合
            $order_data = array(
                "user_id"       => intval($this->uid),
                "consignee"     => $address['consignee'],
                "mobile"        => $address['phone'],
                "status"        => 0,
                "is_show"       => 1,
                "province"      => $address['province'],
                "city"          => $address['city'],
                "proper"        => $address['proper'],
                "street"        => $address['street'],
                "address"       => $address['province'].$address['city'].$address['proper'].$address['street'],
                "add_time"      => $time,
                "pay_type"      => $type,
                "order_sn"      => $order_sn
            );
            Log::write("用户下单的主体信息".var_export($order_data,true),Log::INFO);

            $shipping_template_area_mod = M("shipping_template_area");//
            $shipping_area_code_mod = M("shipping_area_code");// 地区编码模板
            $user_area_code = '';
            $area_code = '';
            //----------------对直辖市特殊处理-----------------------
            switch($address['province']){
                case "北京市":
                case "天津市":
                case "上海市":
                case "重庆市":
                    $area_code = $shipping_area_code_mod->where("province = '{$address['province']}' and city = '{$address['proper']}'")->getField("code");
                    break;
                default :
                    $area_code = $shipping_area_code_mod->where("province = '{$address['province']}' and city = '{$address['city']}'")->getField("code");
                    break;
            }
            $user_area_code = $area_code;// 用户使用地址的地区代码

            //----------------对直辖市特殊处理-----------------------

            // 获取 运费模板
            $spu_mod = M("spu");
            $shipping_template_mod = M("shipping_template");

            $model = M();
            $model->startTrans();
            // 订单实例化
            $order_mod = M("order");

            $order_id  = $order_mod->add($order_data);
            $total_fee = 0;
            if($order_id){
                // 新用户包邮活动


                $sub_order_mod = M("sub_order");
                $extend_special_mod = M("extend_special");
                $sku_list_mod = M("sku_list");
                $now = time();
                $suppliers_fee = array();
                $spu_id_num_arr= array();// 记录下所选购物车内的所有商品spu_id
                //-------平台包邮与商家包邮判断start v2.1-------
                $suppliers_sum_info = array();
                $total_suppliers_price = 0;
                $suppliers_id_str = "";
                //======================限购活动 starts==========================
                foreach($cart_list as $v){
                    if(!isset($spu_id_num_arr[$v['spu_id']])){
                        $spu_id_num_arr[$v['spu_id']] = $v['num'];
                    }else{
                        $spu_id_num_arr[$v['spu_id']] += $v['num'];
                    }
                    $suppliers_id_str .= $v['suppliers_id'].",";
                    $suppliers_sum_info["{$v['suppliers_id']}"]['price'] += $v['price_now']*$v['num'];
                    $total_suppliers_price += $v['price_now']*$v['num'];
                }

                $limit_buy_mod = M("limit_buy");

                foreach ($spu_id_num_arr as $spu_k => $spu_v) {
                    $limit = $limit_buy_mod->where("spu_id = $spu_k and start_time<={$time} and end_time>={$time} and is_use = 1 ")->field("limit_num,start_time")->find();
                    if(empty($limit)){
                        continue;// 没有限购跳过记录
                    }
                    if($limit['limit_num']<$spu_v){
                        $model->rollback();
                        $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
                    }else{
                        // 再次判断 该商品是否限购
                        $spu_num_arr = $sub_order_mod->where("user_id ={$this->uid} and spu_id = $spu_k and status not in(7) and add_time>{$limit['start_time']} ")->getField("nums",true);
                        $limit_sub_order_num = array_sum($spu_num_arr);
                        $tmp_total = $limit_sub_order_num+$spu_v;
                        if($limit['limit_num']<($tmp_total)){
                            $model->rollback();
                            $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
                        }
                    }
                }
                //======================限购活动 end==========================

                $suppliers_id_str = rtrim($suppliers_id_str,',');
                $free_fee_template_mod = M("free_fee_template");
                $plat_form_fee = $free_fee_template_mod->where("type = 2 and status='published' and start_time<={$now} and end_time>={$now}")->order("price asc")->find();
                // 在没有平台包邮的情况下 计算各个供应商的包邮活动信息
                if(empty($plat_form_fee)){
                    $free_template_result = $free_fee_template_mod->where("suppliers_id in($suppliers_id_str) and status='published' and start_time<={$now} and end_time>={$now} ")->field("suppliers_id,price,id")->select();
                    $temp_free_result = array();
                    foreach ($free_template_result as $free_v) {
                        if(isset($temp_free_result["{$free_v['suppliers_id']}"])){
                            // 如果下一个满包邮价格更低 则采用该活动规则
                            if($temp_free_result["{$free_v['suppliers_id']}"]['price']>$free_v['price']){
                                $temp_free_result["{$free_v['suppliers_id']}"]['price'] = $free_v['price'];
                                $temp_free_result["{$free_v['suppliers_id']}"]['id'] = $free_v['id'];
                            }
                        }else{
                            $temp_free_result["{$free_v['suppliers_id']}"]['price'] = $free_v['price'];
                            $temp_free_result["{$free_v['suppliers_id']}"]['id'] = $free_v['id'];
                        }
                    }
                }
                // 在没有平台包邮的情况下 计算各个供应商的包邮活动信息

                $plat_shipping_fee = 0;// 是否免邮费 0 是不免邮
                $free_fee_template_area_mod = M("free_fee_template_area");

                //========优化包邮信息判断========
                if(trim($poster_ids,",") != "" ){ // 包邮信息判断
                    $poster_list = $free_fee_template_mod->where("id in($poster_ids) and status='published' and start_time<={$now} and end_time>={$now}")->select();
                    if(empty($poster_list)){

                        $model->rollback();
                        $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                    }
                    // 平台包邮判断
                    $poster_list_count = count($poster_list);
                    $tmp_poster_list = $poster_list;
                    $plat_poster_flag= 0;
                    if($poster_list_count==1 && $poster_list[0]['type'] == 2){
                        $plat_poster_flag = 1;
                        if($poster_list[0]['price'] > $total_suppliers_price){
                            // 满足了价格之后，需要判断是否满足地区
                            $model->rollback();
                            $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);

                        }else{
                            $free_area = $free_fee_template_area_mod->where("template_id = {$plat_form_fee['id']} and code = {$user_area_code}")->find();
                            if(empty($free_area)){
                                $model->rollback();
                                $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);

                            }
                        }
                    }else{
                        // 计算传来的参数个数

                        $poster_ids_count = substr_count($poster_ids,',')+1;

                        if($poster_ids_count!=$poster_list_count){
                            $model->rollback();
                            $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                        }

                        // 供应商包邮判断
                        $poster_list = arrayFormat($poster_list,"suppliers_id");
                        foreach($poster_list as $poster_k => $poster_v){
                                if($suppliers_sum_info[$poster_k]['price'] >= $poster_list[$poster_k]['price']){
                                    // 下一步要判断 这个用户购买的商品是否在该区域
                                    $template_id = $poster_list[$poster_k]['id'];
                                    $free_area = $free_fee_template_area_mod->where("template_id = {$template_id} and code = {$user_area_code}")->find();
                                    if(empty($free_area)){
                                        $model->rollback();
                                        $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                                    }
                                }else{
                                    $model->rollback();
                                    $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                                }
                        }
                    }
                }

                //========优化包邮信息判断========

                // 如果存在平台包邮
                if(!empty($plat_form_fee)){
                    // 总价格符合了 满 N 元的条件
                    if($plat_form_fee['price'] <= $total_suppliers_price){
                        // 满足了价格之后，需要判断是否满足地区
                        $free_area = $free_fee_template_area_mod->where("template_id = {$plat_form_fee['id']} and code = {$user_area_code}")->find();
                        if($free_area){
                            $plat_shipping_fee = 1;
                        }
                    }

                }
                // 计算各个供应商该采取的邮费。
                foreach($suppliers_sum_info as $k=>$v){
                    // 判断 该供应商的价格是否满足包邮活动
                    if(isset($temp_free_result[$k])){
                        // 购买的数量满足了 满 N元的规则
                        if($v['price']>=$temp_free_result[$k]['price']){
                            // 下一步要判断 这个用户购买的商品是否在该区域
                            $template_id = $temp_free_result[$k]['id'];
                            $free_area = $free_fee_template_area_mod->where("template_id = {$template_id} and code = {$user_area_code}")->find();
                            if($free_area){
                                $suppliers_sum_info[$k]['shipping_fee'] = 0;
                            }
                        }
                    }
                }
                //-------平台包邮与商家包邮判断end v2.1---------

                $coupon_sku_arr = array(); // 用来存放所购买商品的所有spu_id
                $spu_id_sum_price  = array();// 用来记录同一个spu的有效商品总价
                $cart_spu_arr = array();// 记录N元任意购的不同商品
                $plat_spu_arr = array();// 记录所有的购物车商品
                foreach ($cart_list as $v) {
                    $plat_spu_arr[] = $v["spu_id"];//记录所有的购物车商品
                    $cart_spu_arr["{$v['spu_id']}"] = array(
                        "sku_id"    => $v['sku_id'],
                        "price"     => $v['price_now']
                    );

                    // 优惠券需要用到的信息
                    $coupon_sku_arr["{$v['spu_id']}"][] = $v['sku_id'];//
                    if(isset($spu_id_sum_price["{$v['spu_id']}"])){
                        $spu_id_sum_price["{$v['spu_id']}"] += $v['price_now']*$v['num'];
                    }else{
                        $spu_id_sum_price["{$v['spu_id']}"] = $v['price_now']*$v['num'];
                    }
                    // 优惠券需要用到的信息

                    //=========================商品下架判断 =================
                    $cart_state = cart_spu_status($v['spu_id'],$v['sku_id'],$v['price_now']);// 商品的 spu_id
                    if(isset($cart_state['status'])&&$cart_state['status']==2){
                        $model->rollback();

                        Log::write(date("Y-m-d H:i:s")."商品的状态发生了变动",Log::ERR);
                        $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
                    }
                    //=========================商品下架判断 =================
                    // 扣库存
                    try{
                        if($cart_state['active']==0) {
                            $sku_info = $sku_list_mod->where("sku_id = {$v['sku_id']}")->field("sku_id,price")->find();
                            $cart_checkout = $sku_list_mod->where("sku_id = {$v['sku_id']} and sku_stocks >={$v['num']}")->setDec('sku_stocks',$v['num']);

                            $sale_mod = $sku_list_mod;

                        }else{

                            $sku_info['price'] = $cart_state['sku_price'];
                            $cart_checkout = $extend_special_mod->where("sku_id = {$v['sku_id']} and sku_stocks >={$v['num']}")->setDec('sku_stocks',$v['num']);
                            $sale_mod = $extend_special_mod;
                        }
                        if($cart_checkout === 0){

                            throw new Exception('库存不足');
                        }else{
                            if(!$sale_mod->where("sku_id = {$v['sku_id']}")->setInc('sku_sale',$v['num'])){
                                Log::write(date("Y-m-d H:i:s")."销量增加执行失败",Log::ERR);
                                $model->rollback();
                                $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//                                $this->errorView($this->ws_code['order_create_fail'],$this->msg['order_create_fail']);
                            }
                        }
                    }catch(Exception $e){

                        // 某商品的库存不足
                        Log::write(date("Y-m-d H:i:s")."某商品的库存不足");
                        $model->rollback();
// 如果库存不足看看整体库存是否售空
                        $spu_extend = spu_format($v['spu_id']);
                        if($spu_extend['stocks'] <= 0){
                            $spu_mod->where("spu_id = {$v['spu_id']}")->setField("status",1);// 商品失效 售空
                        }
                        $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//                        $this->errorView($this->ws_code['sku_stocks_not_enough'],$this->msg['sku_stocks_not_enough']);
                    }
                    $spu_extend = spu_format($v['spu_id']);
                    if($spu_extend['stocks'] <= 0){
                        $spu_mod->where("spu_id = {$v['spu_id']}")->setField("status",1);// 商品失效 售空
                    }

                    //===============再次验证商品的有效性(已通过库存判定)===============
                    if($v['price_now']!=$sku_info['price']){

                        $model->rollback();
                        Log::write(date("Y-m-d H:i:s")."购物车的价格跟商品的价格不一致");
                        $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//                         $this->errorView($this->ws_code['sku_price_change'],$this->msg['sku_price_change']);
                    }
                    //===============再次验证商品的有效性===============
                    // 加入 包邮信息判断
                    if($plat_shipping_fee==1){
                        $shipping_fee = 0;
                    }else{
                        // 下一步 判断是否是供应商包邮
                        if(isset($suppliers_sum_info["{$v['suppliers_id']}"]['shipping_fee'])&&$suppliers_sum_info["{$v['suppliers_id']}"]['shipping_fee']==0){
                            $shipping_fee = 0;
                        }else{
                            $template_id = $spu_mod->where("spu_id = {$v['spu_id']}")->getField("shipping_template");
                            // 万一获取不到运费模板就默认以12块钱计算
                            if(empty($template_id)){
                                $shipping_fee = C("DEFAULT_SHIPPING_PRICE")? C("DEFAULT_SHIPPING_PRICE"):1200;
                            }else{
                                $default_set = $shipping_template_mod->where("id = {$template_id}")->field("type,default_price")->find();
                                if($default_set['type']==1){// 买家承担运费
                                    if(empty($area_code)){
                                        $area_code = -1;// 给个负值 表示没有这个地区
                                    }
                                    $shipping_fee = $shipping_template_area_mod->where("template_id = {$template_id} and code = {$area_code} ")->getField("price");
                                    $shipping_fee = $shipping_fee? $shipping_fee: $default_set['default_price'];
                                }else{
                                    $shipping_fee = 0;
                                }
                            }
                        }
                    }



                    $sum_price = $v['price_now']*$v['num'];
                    
                    
                    // 第一步判断用户是否资格享受活动。写成方法好修改
                    $client_free_arr = $this->clientFree($this->uid);
                    // 在处理运费的节点做判断,看看该不该给他免邮
                    $shipping_fee = $this->clientShipping($v['suppliers_id'],$shipping_fee,$client_free_arr,$sum_price);
                    
                    
                    $sub_order = array(
                        "order_sn"      => $order_sn,
                        "user_id"       => intval($this->uid),
                        "sub_order_sn"  => $this::getOrderId(),
                        "spu_id"        => intval($v['spu_id']),
                        "sku_id"        => intval($v['sku_id']),
                        "spu_name"      => $v['spu_name'],
                        "price"         => intval($v['price_now']),
                        "sum_price"     => intval($sum_price),
                        "nums"          => intval($v['num']),
                        "spu_attr"      => $v['spu_attr'],
                        "shipping_fee"  => $shipping_fee,// 运费价格 存入以分为单位
                        "is_show"       => 1,
                        "status"        => 0,
                        "suppliers_id"  => intval($v['suppliers_id']),
                        "suppliers_name"=> $v['suppliers_name'],
                        "add_time"      => $time,
                        "postscript"    => $suppliers["{$v['suppliers_id']}"]?$suppliers["{$v['suppliers_id']}"]:'',
                        "topic_id"      => intval($spu_extend['topic_id'])
                    );
                    Log::write("用户下单的子单信息".var_export($sub_order,true),Log::INFO);

                    // 如果 已有运费
                    if(isset($suppliers_fee["{$v['suppliers_id']}"])){
                        $suppliers_fee["{$v['suppliers_id']}"] = $suppliers_fee["{$v['suppliers_id']}"] > $shipping_fee ? $suppliers_fee["{$v['suppliers_id']}"]:$shipping_fee;
                    }else{
                        $suppliers_fee["{$v['suppliers_id']}"] = $shipping_fee;
                    }
                    // 累加总价
                    $total_fee += $sum_price;

                    // 尝试生成子订单
                    try{
                        $sub_order_id = $sub_order_mod->add($sub_order);
                        if(!$sub_order_id){

                            throw new Exception('添加失败');
                        }else{
                            $cart_mod->where("id = {$v['id']}")->delete();
                        }
                    }catch (Exception $e){
                        Log::write(date("Y-m-d H:i:s")."子单创建失败",Log::ERR);
                        $model->rollback();
                        $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//                        $this->successView($this->ws_code['order_create_fail'],$this->msg['order_create_fail']);
                    }
                }

                //==========判断优惠券的有效性以及记录扣去的优惠券金额==============

                // N元任意购 start
                $arbitrary_buy_mod = M("arbitrary_buy");
                $arbitrary = $arbitrary_buy_mod->where("status = 'published'")->field("money,num,spu_list,start_time,end_time")->find();
                $spu_arr_unique = array_keys($cart_spu_arr);
                $any_buy_free = 0; // 任意购的优惠金额
                $any_buy_spu_count = 0;
                $any_buy_spu = array();
                if(!empty($arbitrary)){
                    if($arbitrary["start_time"]<=$now && $now<=$arbitrary['end_time']){
                        $buy_num = count($spu_arr_unique);
                        // 只有在用户购买的商品达到这个数量，我们才要去考虑他的商品里面有没有满足任意购活动商品。减少不必要的查询
                        if($buy_num >= $arbitrary['num']){
                            // 查询此次任意购的商品有哪些
                            $topic_relation_mod = M("topic_relation");
                            $active_spu_arr= $topic_relation_mod->where("topic_id = {$arbitrary['spu_list']}")->getField("spu_id",true);
                            // 与用户购买的商品取交集
                            $buy_active_arr = array_intersect($spu_arr_unique,$active_spu_arr);
                            // 活动到用户购买的任意购的商品 再次判断购买数量是否符合要求
                            $buy_num = count($buy_active_arr);
                            if($buy_num >= $arbitrary['num']){// 购买的不同商品大于设定的值
                                // 算出优惠价 1、先计算理应支付费用 2、再扣去定额费用，= 优惠金额
                                $real_money = 0;
                                // 下一步判断
                                $share_log_mod = M("share_log");
                                $share_log = $share_log_mod->where("user_id = {$this->uid} and type = 2")->find();

                                $inc_num = 0;// 奖励的额外购买商品数
                                if(!empty($share_log)){
                                    $inc_num = 1;
                                }
                                $max_free_num = $arbitrary['num'] + $inc_num ;// -1 因为$k是从0 开始
                                $i = 1;

                                foreach($buy_active_arr as $spu_id_v){

                                    if($i>$max_free_num){
                                        break;
                                    }
                                    $any_buy_spu[] = $spu_id_v;
                                    $real_money += $cart_spu_arr[$spu_id_v]['price'];
                                    $i++ ;
                                }
                                $any_buy_free = $real_money -  $arbitrary['money'];

                                $total_fee -= $any_buy_free;
                                $suborder_active_mod = M("sub_order_active");
                                $suppliers_keys = array_keys($suppliers_fee);
                                // mark 居于当前只有亚历蒂斯供应以及金额分配方案未定的情况下的处理方案。
                                $active_data = array(
                                    "order_sn"     => $order_sn,
                                    "active_id"    => $arbitrary['spu_list'],
                                    "free_amount"  => $any_buy_free,
                                    "suppliers_id" => current($suppliers_keys),// 目前只有一个供应商，所以只记录一次
                                    "create_time"  => time()
                                );

                                if(!$suborder_active_mod->add($active_data)){
                                    $model->rollback();
                                    $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
                                }
                            }
                        }
                    }
                }
                // N元任意购 end

                if($coupon_id>0){

                    // 有传入优惠券。接下来判断其有效性
                    $coupon_mod  = M("coupon");
                    $coupon_data = $coupon_mod->where("id = $coupon_id and use_start_time<={$now} and use_end_time>={$now}")->field("rule_money,rule_free,coupon_num,suppliers_id")->find();
                    // 判断优惠券是否是有效的
                    if(empty($coupon_data)){
                        Log::write("优惠券失效",Log::INFO);
                        $model->rollback();
                        $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                    }
                    // 更新优惠券状态为 is_use =1;
                    $coupon_user_mod = M("coupon_user");
                    $coupon_update = array('is_use'=>1,"is_show"=>0);
                    // 判断优惠券是否被使用过
                    $check_coupon = $coupon_user_mod->where("user_id = {$this->uid} and coupon_num = '{$coupon_data['coupon_num']}'")->getField("is_use",true);
                    if(empty($check_coupon)){
                        $model->rollback();
                        $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                    }
                    // 防止优惠券被使用过
                    $min_coupon = min($check_coupon);
                    if($min_coupon > 0){
                        $model->rollback();
                        $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                    }
                    //只更新一条
                    $coupon_user_mod->where("user_id = {$this->uid} and coupon_num = '{$coupon_data['coupon_num']}' and is_use = 0")->limit(1)->save($coupon_update);// 优惠券已使用



                    // 如果是平台的优惠券则直接看总金额
                    if($coupon_data['suppliers_id'] == 0){
                        $coupon_spu_arr = array_unique($plat_spu_arr);
                        $total_shipping_fee = 0;
                        // 同一供应商的商品以最贵的运费计算
                        foreach ($suppliers_fee as $fee_v) {
                            $total_shipping_fee += $fee_v;
                        }
                        if($total_suppliers_price< ($coupon_data['rule_money'] - $total_shipping_fee + $any_buy_free)){
                            $model->rollback();

                            $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                        }
                    }else{
                        //如果是商家的优惠券，又或者是圈定商品范围的优惠券
                        $coupon_spu_mod = M("coupon_spu");
                        $coupon_spu_id_arr = array_keys($coupon_sku_arr);// 所选购物车的商品去重。可能存在多个sku
                        $coupon_spu_id_strs = implode(",",$coupon_spu_id_arr);
                        $coupon_spu_arr = $coupon_spu_mod->where("coupon_num ='{$coupon_data['coupon_num']}' and spu_id in($coupon_spu_id_strs) ")->getField("spu_id",true);
                        if(empty($coupon_spu_arr)){
                            $model->rollback();

                            $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                        }
                        $tmp_spu_sum_price = 0; // 记录该优惠下所购买商品的总金额，以便计算是否达到该金额门槛
                        $tmp_spu_id_any  = 0;
                        foreach ($coupon_spu_arr as $spu_v) {

                            $tmp_spu_sum_price += $spu_id_sum_price[$spu_v];
                            if(in_array($spu_v,$any_buy_spu)){
                                $tmp_spu_id_any++;
                            }
                        }

                        $tmp_any_buy = 0;
                        $shipping_fee = 0;
                        if(isset($suppliers_fee["{$v['suppliers_id']}"])){
                            $shipping_fee = $suppliers_fee["{$v['suppliers_id']}"];
                        }
                        // 确保有任意购商品材进行计算
                        if(!empty($any_buy_spu)){
                            $tmp_any_buy = ceil($any_buy_free*$tmp_spu_id_any/$any_buy_spu_count);
                        }

                        if($tmp_spu_sum_price < ($coupon_data['rule_money']  - $shipping_fee + $tmp_any_buy)){

                            $model->rollback();// 回滚订单
                            $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                        }
                    }
                   // 判断完都通过了，说明就可以去使用优惠券了
                    // 扣去优惠的金额
                    $total_fee -= $coupon_data['rule_free'];
                    // 记录优惠券的信息到 ls_sub_order_coupon 表
                    $sub_order_coupon_mod = M('sub_order_coupon');// 子单
                    $coupon_trace = array(
                        "coupon_num"   => $coupon_data['coupon_num'],
                        "suppliers_id" => $coupon_data['suppliers_id'],
                        "order_sn"     => $order_sn,
                        "rule_money"   => $coupon_data['rule_money'],
                        "rule_free"    => $coupon_data['rule_free'],
                        "create_time"  => $time
                    );

                    if($sub_order_coupon_mod->add($coupon_trace)){
                        // 插入数据到关系表
                        $order_sku_coupon_mod = M("order_sku_coupon");
                        foreach ($coupon_spu_arr as $spu_v) {
                            foreach ($coupon_sku_arr[$spu_v] as $sku_v) {

                                $sku_trace = array(
                                    "spu_id"        => intval($spu_v),
                                    "sku_id"        => intval($sku_v),
                                    "coupon_num"    => $coupon_data['coupon_num']
                                );

                                // 插入失败回滚记录
                                if(!$order_sku_coupon_mod->add($sku_trace)){

                                    $model->rollback();
                                    $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                                };
                            }

                        }

                    }else{
                        $model->rollback();
                        $this->errorView($this->ws_code['coupon_invalidate'],$this->msg['coupon_invalidate']);
                    }

                }
                //==========判断优惠券的有效性以及记录扣去的优惠券金额==============


                $model->commit();

            }else{
                $model->rollback();
                $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//                $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
            }

            $sum_price = $total_fee;//初始的总价不包含运费
            // 同一供应商的商品以最贵的运费计算
//            foreach ($suppliers_fee as $fee_v) {
//                $total_fee += $fee_v;
//            }

            $shipping_fee_new = 0;
            foreach ($suppliers_fee as $fee_v) {
                $shipping_fee_new += $fee_v;
            }


            // 第一步判断用户是否资格享受活动。写成方法好修改
                    $client_free_arr = $this->clientFree($this->uid);
                    // 在处理运费的节点做判断,看看该不该给他免邮

            if($sum_price_in_list>=9900){
                $total_fee -= 5000;  //总金额减去50元
            }


                    $shipping_fee = $this->clientShipping($v['suppliers_id'],$shipping_fee_new,$client_free_arr,$sum_price);
            if($shipping_fee==0){
                $d = array('shipping_fee'=>'0');
                $sub_order_mod->where("order_sn = '{$order_sn}'")->save($d);
            }

            $total_fee = $total_fee+$shipping_fee;
            $total_fee_wx = $total_fee;   //微信支付所用，为分
            $total_fee = price_format($total_fee);// 对外输出 格式化成元

            if($type==0) {  //支付宝支付
                $data = array(
                    "partner" => $alipay_config['partner'],
                    "seller_id" => "feibobomail@126.com",
                    "out_trade_no" => $order_sn,
                    "subject" => '小喵的订单',
                    "body" => C("ORDER_DESC"),
                    "total_fee" => $total_fee,
                    "notify_url" => C("ALIPAY_NOTIFY_URL"),
                    "service" => "mobile.securitypay.pay",
                    "payment_type" => 1,
                    "_input_charset" => $alipay_config['input_charset'],
                    "it_b_pay" => "30m",
                    "show_url" => "m.alipay.com"
                );

                //除去待签名参数数组中的空值和签名参数
                $str = "partner=\"{$data['partner']}\"&seller_id=\"{$data['seller_id']}\"&out_trade_no=\"{$data['out_trade_no']}\"&" .
                    "subject=\"{$data['subject']}\"&body=\"{$data['body']}\"&total_fee=\"{$data['total_fee']}\"&notify_url=\"{$data['notify_url']}\"&" .
                    "service=\"{$data['service']}\"&payment_type=\"{$data['payment_type']}\"&_input_charset=\"{$data['_input_charset']}\"&it_b_pay=\"{$data['it_b_pay']}\"&show_url=\"{$data['show_url']}\"";

                $rsa = rsaSign($str, THINK_PATH . "Extend/Vendor/alipay/" . trim($alipay_config['private_key_path']));
                $rsa = urlencode($rsa);
                $str = $str . '&sign="' . $rsa . '"&sign_type="RSA"';
                $data = array(
                    "order_sn" => 'a' . $order_sn,// 需要加前缀
                    "type" => 0,
                    "pay_info" => $str
                );
                $this->successView('', $data);
            }else if($type==1){ //微信支付
//                echo "微信支付";
                //①、获取用户openid
//                $tools = new JsApiPay();
//                $openId = $tools->GetOpenid();
//                echo "OPID是".$openId;exit;

                //②、统一下单
                $input = new WxPayUnifiedOrder();
                $input->SetBody(C("ORDER_DESC"));
                $input->SetOut_trade_no($order_sn);
                $input->SetTotal_fee($total_fee_wx);
                $input->SetNotify_url(C("WXPAY_NOTIFY_URL"));
                $input->SetTrade_type("APP");


                $order = WxPayApi::unifiedOrder($input);
//                echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
//                print_r($order);
                $data = array(
                    "order_sn"      => 'a'.$order_sn,// 需要加前缀
                    "type"          => 1,
                    "pay_info"      => json_encode($order)
                );
                $this->successView('',$data);
            }
        }else{
            $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
//            $this->errorView($this->ws_code['pay_not_exist'],$this->msg['pay_not_exist']);
        }
    }


    /**
    +----------------------------------------------------------
     * 查询供应商的库存情况
     *
    +----------------------------------------------------------
     */
    public function _checkStock($cart_list,$address,$is_order=false){
        // 发生异步请求
        $yalidisi = C("SECRET_KEY");
        $url = $yalidisi['yalidisi']['wsdl'];

        $cart_mod = M("cart");
        $sellername = $yalidisi['yalidisi']['sellername'];
        // 找出亚历蒂斯的商品
        $sku_str = "";
        $now_time = date("Y-m-d H:i:s");
        $code = $yalidisi['yalidisi']['secret_key'];
        $sku_num_arr = array();
        $cart_id_arr = array();
        foreach($cart_list as $v){
            // 过滤掉非亚历蒂斯
            if($v['suppliers_id']!=8){
                continue;
            }
            $sku_str .= $v['sku_id'].",";
            if($is_order){
                $sku_num_arr[$v['sku_id']] = $v['nums'];// 字段定义出入，修正
            }else{
                $sku_num_arr[$v['sku_id']] = $v['num'];
                $cart_id_arr[$v['sku_id']] = $v['id'];
            }

        }
        // 说明这个单子并没有购买亚历蒂斯的商品
        if(empty($sku_num_arr)){
            return true;
        }

        // 在购买了该供应商的商品的情况下
        $flag = true;// 默认库存充足
        if($sku_str!=""){
            $sku_str = trim($sku_str,',');
            $sku_mod = M("sku_list");
            $result = $sku_mod->where("sku_id in($sku_str)")->field("library_number,sku_id,spu_id")->select();

            foreach($result as $k=> $v){
                // mark 暂行办法 将library_number 拆分 格式形如 20*A010-30*B010
                $lib_arr = array();
                $lib_arr = explode("-", $v['library_number']);

                // 说明是 20*A010 或者 A010
                if(count($lib_arr) == 1){
                   $start_v = explode('*',current($lib_arr));

                   if(count($start_v) ==1){
                       // 是A010
                       $productlist[] = array(
                           'prosn' => "{$v['library_number']}",//库位号
                           "num"   => "{$sku_num_arr[$v['sku_id']]}"//购买数量
                       );
                       $result[$k]['level'] = 1;
                       $result[$k]['list']  = $v['library_number'];
                   }else{
                       // 是 20*A010
                       $productlist[] = array(
                           'prosn' => "{$start_v[1]}",
                           "num"   => strval($sku_num_arr[$v['sku_id']]*$start_v[0])
                       );

                       $result[$k]['level'] = 2;// 定义是 20*A010
                       $result[$k]['list'] = $start_v;
                   }
                }else{
                    foreach ($lib_arr as $lib_v) {
                        $unit_v = explode("*",$lib_v);
                        if(count($unit_v)==1){
                            $unit_v[0] = 1;
                            $unit_v[1] = $lib_v;
                        }
                        $productlist[] = array(
                            'prosn' => "{$unit_v[1]}",
                            "num"   => strval($sku_num_arr[$v['sku_id']]*$unit_v[0])
                        );
                        $result[$k]['level'] = 3;
                        $result[$k]['list'][]  = $unit_v;
                    }
                }
            }

            $data = array(
                "encrypt"        => md5($code.$now_time),
                "time"           => $now_time,
                "sellername"     => $sellername,
                "province"       => $address['province'],
                "city"           => $address['city'],
                "district"       => $address['proper'],
                "address"        => $address['street'],
                "productlist"    => $productlist
            );

            // 根据亚丽蒂斯提供的文档 需要组成该数组
            $data = array("in0"=>json_encode($data));
            $result_info = $this->_soapCall($url,$data,"findAreaAndStock");
            Log::write("回调消息:".var_export($result_info,true),Log::INFO);//


            if($result_info['success'] == 'false'){// 亚历蒂斯需要使用字符串匹配才能相等！！！
                // 这种情况下要不到库存信息
                Log::write("亚历蒂斯库存地址查询接口请求错误或所购商品不存在:".$result_info['msg'],Log::WARN);//
                $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
            }
            // 1表示可以匹配到网点可以发货、0表示没有匹配到网点不能发货
            if($result_info['candeliver'] == 0){
                // 这种情况下也要不到库存信息
                Log::write("用户填写的收货地址亚历蒂斯查不到:".$result_info['msg'],Log::WARN);//
                $this->errorView($this->ws_code['order_common_error'],$this->msg['order_common_error']);
            }
            Log::write("亚历蒂斯库存查询结果:".$result_info['msg'],Log::INFO);//

            /*
             *  接下来的情况包括，所购买的数量超过储备库存 特殊情况是某些商品库存为0
             */
            // 更新对应的库存信息：
            $flag = true;// 默认库存充足、
            // 废弃库存更新策略，先别删，心疼！
            if(!empty($result_info)){
                $productlist = stockList($result_info['stocklist'],"prosn","stock");
                $extend_special_mod = M("extend_special");
                $sku_id_d = 0;
                $group_flag = true;
                // 循环的遍历
                foreach ($result as $result_v) {
                    $sku_stock = 0;
                    $save_flag = false;
                    switch($result_v['level']){
                        case 1:// A010
                            if(isset($productlist["{$result_v['list']}"])){
                                $sku_stock = $productlist["{$result_v['list']}"];
                                $save_flag = true;
                            }
                            break;
                        case 2://20*A010
                            if(isset($productlist["{$result_v['list'][1]}"])){

                                $sku_stock = floor($productlist["{$result_v['list'][1]}"]/$result_v['list'][0]);

                                $save_flag = true;
                            }
                            break;
                        case 3://20*A010-10*B020 组合中取最小倍数的作为库存信息
                            $min = 0;
                            foreach ($result_v['list'] as $list_v) {
                                if(isset($productlist["{$list_v[1]}"])){
                                    $tmp = floor($productlist["{$list_v[1]}"]/$list_v[0]);
                                    if($min == 0){//第一次赋值
                                        $min = $tmp;
                                    }
                                    $min = $tmp < $min? $tmp:$min;

                                    $save_flag = true;
                                }else{
                                    // 一旦有发现获取不到的则解释掉这层循环
                                    $save_flag = false;

                                    break;
                                }
                            }
                            $sku_stock = $min;
                            break;
                    }
                    if($save_flag == false){//如果查询的库位号不存在，库存强制改为0
                        $sku_stock = 0;
                    }

                    $data = array(
                        "sku_stocks" => $sku_stock
                    );
                    if($sku_stock == 0){
                        $extend_special_mod->where("sku_id = {$result_v['sku_id']}")->setField("sku_stocks",0);
                        $flag = false;// 说明有商品库存为0
                    }else{
                        $cart_status = cart_spu_status($result_v['spu_id'],$result_v['sku_id'],0);
                        if(isset($cart_status['active']) && $cart_status['active'] ==1 && $cart_status['sku_stocks']>$sku_stock){
                            $extend_special_mod->where("sku_id = {$result_v['sku_id']} ")->setField("sku_stocks",$sku_stock);
                        }
                    }
                    $sku_id_d = $result_v['sku_id'];
                    Log::write("记录库存信息".$result_v['sku_id'].var_export($data,true),Log::INFO);
                    // 只有在购物车内的商品大于 现有库存才需要更新购物车
                    if($is_order == false && $sku_num_arr[$sku_id_d] > $sku_stock){
                        if($sku_stock>0){ // 避免将购物车库存设置成0
                            $cart_mod->where("id = {$cart_id_arr[$sku_id_d]}")->setField("num",$sku_stock);
                        }
                    }
                    $sku_mod->where("sku_id ={$result_v['sku_id']} ")->save($data);
                }

            }else{
                $flag = false;
            }
            if($result_info['hasstock'] == 0){
                // 这种情况属于库存不足，比如用户想买的量，亚历蒂斯不能满足
                $flag = false;
            }
            //如果有不足的库存 需要返回false
            return $flag;
        }else{
            return false;// 没有商品直接返回false
        }
    }
    /**
    +----------------------------------------------------------
     * Soap 版发起请求会话调用webServer 服务
    +----------------------------------------------------------
     */
    private function _soapCall($url,$data,$method){
        $client = new SoapClient($url);
        $data = $client->$method($data);
        $respon = json_decode($data->out,true);// 转成array 非object 根据wsdl 文档访问值包在out属性内
        return $respon;
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
     * 获取支付结果 2703
     * @return double
    +----------------------------------------------------------
     */
    public function notifyResult(){
        //
        $order_sn = substr(htmlspecialchars($this->getField("order_sn"),ENT_QUOTES),1);
        $order_mod = M("order");
        $order = $order_mod->where("user_id = {$this->uid} and order_sn = '{$order_sn}'")->find();

        if(empty($order)){
            //　输出错误信息
            $this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);
        }
        $sub_order_mod = M("sub_order");
        $price_all = $sub_order_mod->where("order_sn = '{$order_sn}'")->field("sum_price,shipping_fee,suppliers_id")->select();
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
        $rule_free = $sub_order_coupon_mod->where("order_sn ='$order_sn'")->getField("rule_free");
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

        $data = array(
            "type"      => $order['status']?1:0,
            "real_pay"  => price_format($total_price)
        );
        Log::write("返回的订单信息".var_export($data,true),Log::INFO);
        $this->successView('',$data);
    }
    /**
    +----------------------------------------------------------
     * 使用优惠券，有、无效优惠券（确认订单页） 2904
     * @return double
    +----------------------------------------------------------
     */
    public function couponSelect(){
        // 判断是否登录
        if($this->uid<=0){
            $this->errorView($this->ws_code['login_out_error'],$this->msg['login_out_error']);
        }
        // 根据type 显示有效或者无效的优惠券
        $type = intval($this->getField('type'));// 0：表示获取有效优惠券 1：表示获取无效的优惠券
        $ids = htmlspecialchars($this->getField('ids'), ENT_QUOTES);// 获取购物车条目id
        $address_id = intval($this->getFieldDefault("address_id",0));
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数

        $start = ($pg_cur - 1)*$pg_size;
        // 列出有效的优惠券
        $now = time();
        //---------------勾选的购物车商品----------------
        $cart_mod = M("cart");
        $cart_list = $cart_mod->where("id in ($ids) and  user_id = {$this->uid}  ")->order("id asc")->select();
        if(empty($cart_list)){
            $this->errorView($this->ws_code['cart_goods_not_exist'],$this->msg['cart_goods_not_exist']);
        }
        //计算在满99减50活动里的商品价格
        $sum_price_in_list = $this->getSumPriceInList($cart_list);
        $discount_num = 0;
        // 计算出用户所拥有的有效优惠券
        $coupon_user_mod = M("coupon_user");
        $coupon_arr = $coupon_user_mod->where("user_id = {$this->uid}  and is_use=0 and is_show = 1")->getField("coupon_num",true);// 获取用户的所有优惠券
        $compute_result = array();// 计算结果
        if($address_id>0){

            $compute_result = $this->computeFee($address_id,$cart_list);

        }
        // 没有 优惠券 信息 直接判定为 0
        if(empty($coupon_arr)){
            $discount_num = 0;
        }else{
            // 购物车列表
            $spu_sum_price = 0;
            $spu_id_sum_price = array();
            $cart_spu_arr = array();// 记录下用户所购买的商品spu_id
            foreach ($cart_list as $v) {
                // 判断出有效的购物车商品
                $cart_state = cart_spu_status($v['spu_id'],$v['sku_id'],$v['price_now']);// 商品的 spu_id
                $state = $cart_state['state'];
                if($state == 0){
                    $coupon_spu_id_arr[] = $v['spu_id'];
                    $spu_sum_price += $v['price_now']*$v['num'];
                    if(isset($spu_id_sum_price["{$v['spu_id']}"])){
                        $spu_id_sum_price["{$v['spu_id']}"] += $v['price_now']*$v['num'];
                    }else{
                        $spu_id_sum_price["{$v['spu_id']}"] = $v['price_now']*$v['num'];
                    }
                    $cart_spu_arr["{$v['spu_id']}"] = array(
                        "sku_id"    => $v['sku_id'],
                        "price"     => $v['price_now']
                    );
                }
            }

            //加入任意购优惠
            // N元任意购
            $arbitrary_buy_mod = M("arbitrary_buy");
            $arbitrary = $arbitrary_buy_mod->where("status = 'published'")->field("money,num,spu_list,start_time,end_time")->find();
            $spu_arr_unique = array_keys($cart_spu_arr);
            $any_buy_free = 0; // 任意购的优惠金额
            $any_buy_spu_count = 0;
            $any_buy_spu = array();
            if(!empty($arbitrary)){
                if($arbitrary["start_time"]<=$now && $now<=$arbitrary['end_time']){
                    $buy_num = count($spu_arr_unique);
                    // 只有在用户购买的商品达到这个数量，我们才要去考虑他的商品里面有没有满足任意购活动商品。减少不必要的查询
                    if($buy_num >= $arbitrary['num']){
                        // 查询此次任意购的商品有哪些
                        $topic_relation_mod = M("topic_relation");
                        $active_spu_arr= $topic_relation_mod->where("topic_id = {$arbitrary['spu_list']}")->getField("spu_id",true);
                        // 与用户购买的商品取交集
                        $buy_active_arr = array_intersect($spu_arr_unique,$active_spu_arr);
                        // 活动到用户购买的任意购的商品 再次判断购买数量是否符合要求
                        $buy_num = count($buy_active_arr);
                        if($buy_num >= $arbitrary['num']){// 购买的不同商品大于设定的值
                            // 算出优惠价 1、先计算理应支付费用 2、再扣去定额费用，= 优惠金额
                            $real_money = 0;
                            // 下一步判断
                            $share_log_mod = M("share_log");
                            $share_log = $share_log_mod->where("user_id = {$this->uid} and type = 2")->find();

                            $inc_num = 0;// 奖励的额外购买商品数
                            if(!empty($share_log)){
                                $inc_num = 1;
                            }
                            $max_free_num = $arbitrary['num'] + $inc_num ;// -1 因为$k是从0 开始
                            $i = 1;

                            foreach($buy_active_arr as $spu_id_v){

                                if($i>$max_free_num){
                                    break;
                                }
                                $any_buy_spu[] = $spu_id_v;
                                $real_money += $cart_spu_arr[$spu_id_v]['price'];
                                $i++ ;
                            }
                            $any_buy_free = $real_money -  $arbitrary['money'];
                        }
                    }
                }
            }

            $any_buy_spu_count = count($any_buy_spu);// 任意购的商品总计

            // 有优惠券信息 判定是否有有效的优惠券
            $coupon_strs = "'".implode('\',\'',$coupon_arr)."'";

            // 判定 哪些优惠券有效。
            $coupon_mod = M("coupon");
            $coupon_disable_arr = array();// 结构 num,msg
            $coupon_all_arr = $coupon_mod->where("coupon_num in($coupon_strs) ")->field("coupon_num,use_start_time,use_end_time,suppliers_id")->select();
            $coupon_arr = array();// 重置coupon_arr
            $coupon_suppliers = "";// 优惠券的供应商
            foreach ($coupon_all_arr as $all_v) {
                if($all_v['use_end_time']>$now && $all_v['use_start_time']<=$now){
                    $coupon_arr[] = $all_v['coupon_num'];
                }else{
                    $coupon_disable_arr[] = array(
                        "coupon_num" => $all_v['coupon_num'],
                        "msg"        => $all_v['use_end_time']<$now?"该优惠券使用有效期已结束":"该优惠券未到使用有效期"
                    );
                }
                // 获取优惠券的所属供应商id
                if($all_v['suppliers_id']>0){
                    $coupon_suppliers .=$all_v['suppliers_id'].",";
                }
            }

            $suppliers_list = array();
            if($coupon_suppliers != ""){
                $coupon_suppliers = rtrim($coupon_suppliers,",");
                $suppliers_mod = M("suppliers");
                $suppliers_list = $suppliers_mod->where("suppliers_id in($coupon_suppliers)")->field("suppliers_id,shop_name")->select();
                $suppliers_list = secToOne($suppliers_list,"suppliers_id","shop_name");
            }

            if(empty($coupon_arr)){
                $discount_num = 0;
            }else{
                // 在有效期内的优惠券。
                $coupon_strs = "'".implode('\',\'',$coupon_arr)."'"; // 需要拼接‘’  因为 优惠券是字符串类型，用in 需要加引号。
                $coupon_spu_mod = M("coupon_spu");
                $coupon_spu_id_arr = array_unique($coupon_spu_id_arr);// 所选购物车的商品去重。可能存在多个sku
                $coupon_spu_id_strs = implode(",",$coupon_spu_id_arr).",1";
                // 表示即在用户所拥有的有优惠券，也在 所购买商品可以使用的优惠券
                $coupon_num_arr = $coupon_spu_mod->where("coupon_num in($coupon_strs) and spu_id in($coupon_spu_id_strs) ")->select();

                $coupon_num_arr_format = arrayFormat($coupon_num_arr,"coupon_num");
                $coupon_num_arr_keys   = array_keys($coupon_num_arr_format);

                // 与原始的优惠券做差集
                $coupon_not_match_spu_arr = array_diff($coupon_arr,$coupon_num_arr_keys);// 找到没有匹配到的优惠券

                foreach ( $coupon_not_match_spu_arr as $coupon_not_match_spu) {
                    $coupon_disable_arr[] = array(
                        "coupon_num" => $coupon_not_match_spu,
                        "msg"        => "所有结算商品中，没有符合该活动的商品"
                    );
                }
                if(empty($coupon_num_arr)){
                    $discount_num = 0;
                }else{
                    $discount_num = count($coupon_num_arr);
                }
            }
        }

        // 经过一番计算得出了优惠券的可用数量
        if($discount_num ==0 && $type==0){

            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }else{

            // 查询可用优惠券信息 判断是否达到优惠券的门槛。
            $coupon_mod = M("coupon");
            $coupon_num_strs = "'".implode('\',\'',$coupon_num_arr_keys)."'";
            $coupon_list = $coupon_mod->where("coupon_num in($coupon_num_strs)")->order("rule_free desc,rule_money asc")->field("id,name,get_start_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->select();

            $total_shipping = 0;// 总运费

            if(!empty($compute_result)) {
                $tmp_cart_suppliers = $compute_result['suppliers'];
                $total_shipping     = $compute_result['total_shipping_fee'];
            }

            foreach($coupon_list as $v){
                // msg 描述判断 指定供应商 OR 全平台可用
                if($v['suppliers_id']==0){
                    $msg = "全平台使用";
                }else{
                    $msg = $suppliers_list["{$v['suppliers_id']}"] ? $suppliers_list["{$v['suppliers_id']}"]."专用":"指定供应商使用";
                }
                // 记录 每个优惠券所对应的商品的
                $coupon_spu_relation = array();
                foreach ($coupon_num_arr as $coupon_v) {
                    $coupon_spu_relation["{$coupon_v['coupon_num']}"][] = $coupon_v['spu_id'];
                }

                $tmp_spu_id_any = 0;
                $tmp_spu_id_sum_price = 0;
                foreach ( $coupon_spu_relation[$v['coupon_num']] as $relation_spu_id) {
                    $tmp_spu_id_sum_price += $spu_id_sum_price[$relation_spu_id];// 累计某个优惠券下商品总金额
                    if(in_array($relation_spu_id,$any_buy_spu)){
                        $tmp_spu_id_any++;
                    }
                }

                // 先判断是不是平台的优惠券 平台优惠券计算的是总价
                if($v['suppliers_id'] == 0){
                    if(($sum_price_in_list<9900)&&(($spu_sum_price < ($v['rule_money'] - $total_shipping + $any_buy_free)))){  //如果满足满99减50的条件
                        // 记下失效的优惠券
                        $coupon_disable_arr[] = array(
                            "coupon_num" => $v['coupon_num'],
                            "msg"        => "所结算商品金额不满足优惠券启动限额"
                        );
                        continue ;
                    }else if(($sum_price_in_list>=9900)&&((($spu_sum_price-$sum_price_in_list) < ($v['rule_money'] - $total_shipping + $any_buy_free)))){
                        // 记下失效的优惠券
                        $coupon_disable_arr[] = array(
                            "coupon_num" => $v['coupon_num'],
                            "msg"        => "所结算商品金额不满足优惠券启动限额"
                        );
                        continue ;
                    }
                }else{

                    $tmp_any_buy  = 0;
                    $shipping_fee = 0;
                    if(!empty($compute_result)){
                        // 确保有包邮信息
                        if(isset($tmp_cart_suppliers["{$v['suppliers_id']}"])){
                            $shipping_fee = $tmp_cart_suppliers["{$v['suppliers_id']}"]['freight'];
                        }
                        // 确保有任意购商品材进行计算
                        if(!empty($any_buy_spu)){
                            $tmp_any_buy = ceil($any_buy_free*$tmp_spu_id_any/$any_buy_spu_count);
                        }
                    }
                    if((($sum_price_in_list<9900)&&($tmp_spu_id_sum_price < ($v['rule_money']  - $shipping_fee + $tmp_any_buy)))||(($sum_price_in_list<9900)&&(($tmp_spu_id_sum_price-$sum_price_in_list) < ($v['rule_money']  - $shipping_fee + $tmp_any_buy)))){
                        // 记下失效的优惠券
                        $coupon_disable_arr[] = array(
                            "coupon_num" => $v['coupon_num'],
                            "msg"        => "所结算商品金额不满足优惠券启动限额"
                        );
                        continue;
                    }
                }

                if($type==0){
                    $user_coupon_arr[] = array(
                        "id"                => intval($v['id']),
                        "title"             => $v['name'],
                        "is_valid"          => 1,
                        "status"            => 1,
                        "start_time"        => intval($v['use_start_time']),
                        "end_time"          => intval($v['use_end_time']),
                        "validation_amount" =>  free_format($v['rule_free']),
                        "using_range"       => "满". price_format($v['rule_money'])."元可用",
                        "msg"               => $msg,
                        "invalid_desc"      => ""
                    );
                }
            }
            if($type>0){
                // 如果是要获取失效优惠券。则需要判断那些失效优惠券
                $coupon_disable_arr = secToOne($coupon_disable_arr,'coupon_num',"msg");
                $coupon_disable_keys_arr = array_keys($coupon_disable_arr);
                $coupon_disable_keys_strs = "'".implode('\',\'',$coupon_disable_keys_arr)."'";
                $coupon_list = $coupon_mod->where("coupon_num in($coupon_disable_keys_strs)")->order("rule_money desc,rule_money asc")->field("id,name,get_start_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->select();

                foreach($coupon_list as $v) {
                    // msg 描述判断 指定供应商 OR 全平台可用
                    if($v['suppliers_id']==0){
                        $msg = "全平台使用";
                    }else{
                        $msg = $suppliers_list["{$v['suppliers_id']}"] ? $suppliers_list["{$v['suppliers_id']}"]."专用":"指定供应商使用";
                    }
                    $user_coupon_arr[] = array(
                        "id"                => intval($v['id']),
                        "title"             => $v['name'],
                        "is_valid"          => 0,
                        "status"            => 1,
                        "start_time"        => intval($v['use_start_time']),
                        "end_time"          => intval($v['use_end_time']),
                        "validation_amount" =>  free_format($v['rule_free']),
                        "using_range"       => "满". price_format($v['rule_money'])."元可用",
                        "msg"               => $msg,
                        "invalid_desc"      => $coupon_disable_arr["{$v['coupon_num']}"],
                    );
                }
            }
            if(empty($user_coupon_arr)){
                $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
            }
            $this->successView($user_coupon_arr);
        }
    }
    // 计算邮费
    public function computeFee($address_id,$cart_list){
        $default_addr = array();
        // 获取 运费模板
        $spu_mod = M("spu");
        $shipping_template_mod = M("shipping_template");
        $address_mod = M("address");
        // 如果有收货地址则判断邮费

        $default_addr = $address_mod->where("add_id = {$address_id} and user_id = {$this->uid}")->find();

        if(empty($default_addr)){
            // 无地址是错误的
            $this->errorView($this->ws_code['coupon_id_not_exist'],$this->msg['coupon_id_not_exist']);
            $address = array();
        }else{
            $address = array(
                "id"         => intval($default_addr['add_id']),
                "name"       => $default_addr['consignee'],
                "phone"      => $default_addr['phone'],
                "province"   => $default_addr['province'],
                "city"       => $default_addr['city'],
                "proper"     => $default_addr['proper'],
                "full_add"   => $default_addr['province']." ".$default_addr['city']." ".$default_addr['proper']." ".$default_addr['street'],
                "type"       => intval($default_addr['type'])
            );
        }
        //---------------物流费用计算start----------------
        $user_area_code = '';

        if(!empty($address)){
            $shipping_template_area_mod = M("shipping_template_area");//
            $shipping_area_code_mod = M("shipping_area_code");// 地区编码模板
            $area_code = '';
            //----------------对直辖市特殊处理-----------------------
            switch($address['province']){
                case "北京市":
                case "天津市":
                case "上海市":
                case "重庆市":
                    $area_code = $shipping_area_code_mod->where("province = '{$default_addr['province']}' and city = '{$default_addr['proper']}'")->getField("code");
                    break;
                default :
                    $area_code = $shipping_area_code_mod->where("province = '{$default_addr['province']}' and city = '{$default_addr['city']}'")->getField("code");
                    break;
            }
            $user_area_code = $area_code;// 用户使用地址的地区代码
            //----------------对直辖市特殊处理-----------------------
        }

        $cart_item = array();
        //---------------物流费用计算end-----------------
        foreach ($cart_list as $v) {

            if(!empty($default_addr)){
                $template_id = $spu_mod->where("spu_id = {$v['spu_id']}")->getField("shipping_template");
                // 万一获取不到运费模板就默认以12块钱计算  注意数据库中的记录以分为单位
                if(empty($template_id)){
                    $shipping_fee = C("DEFAULT_SHIPPING_PRICE")? C("DEFAULT_SHIPPING_PRICE"):1200;
                }else{
                    $default_set = $shipping_template_mod->where("id = {$template_id}")->field("type,default_price")->find();
                    if($default_set['type']==1){// 买家承担运费
                        if(empty($area_code)){
                            $area_code = -1;// 给个负值 表示没有这个地区
                        }
                        $shipping_fee = $shipping_template_area_mod->where("template_id = {$template_id} and code = {$area_code} ")->getField("price");
                        $shipping_fee = $shipping_fee? $shipping_fee: $default_set['default_price'];
                    }else{
                        $shipping_fee = 0;
                    }
                }
            }else{
                $shipping_fee = 0;
            }
            $cart_state = cart_spu_status($v['spu_id'],$v['sku_id'],$v['price_now']);// 商品的 spu_id
            $state = $cart_state['state'];
            $supplier_id = $v['suppliers_id'];

            // 如果状态为0 才累计运费
            if($state==0){
                $cart_item[$supplier_id]['sum_price']     +=$v['price_now']*$v['num'];
                if(isset($cart_item[$supplier_id]['shipping_fee'])){
                    $cart_item[$supplier_id]['shipping_fee']  =  $cart_item[$supplier_id]['shipping_fee']>$shipping_fee ? $cart_item[$supplier_id]['shipping_fee']:$shipping_fee;
                }else{
                    $cart_item[$supplier_id]['shipping_fee'] = $shipping_fee;
                }
            }
        }

        //-------平台包邮与商家包邮判断end v2.1-------
        // 归类到大条目
        $cart_suppliers = array();
        $total_shipping_fee = 0;
        $spu_sum_price = 0; // 商品总价
        $plat_shipping_fee = 0;// 是否免邮费
        $free_fee_template_area_mod = M("free_fee_template_area");
        // 计算用户购买的总价格
        foreach($cart_item as $k=>$v){
            $sum_price = $v['sum_price'];
            $spu_sum_price += $sum_price;
        }

        // 如果存在平台包邮
        if(!empty($plat_form_fee)){

            if($plat_form_fee['price'] <= $spu_sum_price){

                // 满足了价格之后，需要判断是否满足地区
                if(empty($user_area_code)){// 还没有地址
                    $plat_shipping_fee = 1;
                }else{
                    $free_area = $free_fee_template_area_mod->where("template_id = {$plat_form_fee['id']} and code = {$user_area_code}")->find();

                    if($free_area){

                        $plat_shipping_fee = 1;
                    }
                }
            }
        }
        // 新用户包邮活动
        // 第一步判断用户是否资格享受活动。写成方法好修改
        $client_free_arr = $this->clientFree($this->uid);
        foreach ($cart_item as $k => $v) {
            // 只有在平台包邮生效，且该用户所选地址符合平台包邮。
            if(!empty($plat_form_fee)&&$plat_shipping_fee==1) {
                $shipping_fee = 0;
            }else{
                // 需要支付的邮费。
                $shipping_fee = $v['shipping_fee'];
                // 如果该供应商有包邮活动
                if(isset($temp_free_result[$k])){
                    // 再判断包邮的条件，是否满足该供应商的总价
                    if($temp_free_result[$k]['price']<=$v['sum_price']){
                        if($user_area_code!=''){
                            // 下一步判断是否 是在指定的地区
                            $template_id = $temp_free_result[$k]['id'];
                            $is_free_area = $free_fee_template_area_mod->where("template_id = $template_id and code=$user_area_code")->count();
                            if(!empty($is_free_area)){

                                $shipping_fee = 0;
                            }
                        }else{
                            $shipping_fee = 0;
                        }
                    }
                }
            }

            $shipping_fee = $this->clientShipping($k,$shipping_fee,$client_free_arr,$spu_sum_price);

            $cart_suppliers[] = array(
                "id"        => intval($k),
                "freight"   => $shipping_fee,// 需计算
            );
            $total_shipping_fee += $shipping_fee;
        }
        $tmp_cart_suppliers['suppliers'] = arrayFormat($cart_suppliers,"id");
        $tmp_cart_suppliers['total_shipping_fee'] = $total_shipping_fee;
        return $tmp_cart_suppliers;
    }
    /**
    +----------------------------------------------------------
     * 判断用户是否免费享有活动
    +----------------------------------------------------------
     */
    private function clientFree($uid){
        $order_mod = M("order");
        // 只要查到该用户有创建过订单就不享有优惠活动
        // 因为如果先计算优惠活动，就算有活动，你还是得看看这个用户是否满足要求。
        // 那还不如先看看这个用户有没有资格，没有资格，有没有活动也就无所谓了。
        if($order_mod->where("user_id = $uid and status>=1 and pay_sn <> '' " )->find()){
            return array(
                "is_free"   => 0
            );
        }
        $time = time();
        $client_free_plat = 0;
        $client_free_suppliers = array();
        $client_free_mod = M("client_free_shipping");
        $client_free_list = $client_free_mod->where("status = 'published' and start_time<= $time and end_time >=$time ")->select();
        // 判断出当前的包邮情况
        foreach($client_free_list as $free_v){
            // 判断是否是平台类型的包邮
            if($free_v['suppliers_id'] == 0 ){
                $client_free_plat = 1;
                break;
            }
            // 其他情况则是供应商的新用户包邮活动
            $client_free_suppliers["{$free_v['suppliers_id']}"] = 1;
        }
        // 至此已计算出平台
        return array(
            "is_free"               => 1,//有优惠资格
            "free_plat"             => $client_free_plat,// 平台优惠
            "free_suppliers"        => $client_free_suppliers//供应商优惠
        );
    }
//添加了$total_price，swith改为if....else $total_price >= 2900
    private function clientShipping($suppliers_id,$shipping_fee,$client_free_arr,$total_price){
        if($client_free_arr['is_free']!=0){
//     		            switch($client_free_arr['free_plat']){
//     		                case 1:
//     		                    // 平台包邮活动生效
//     		                    	$shipping_fee = 0;
//     		                    break;
//     		               case 0:
//     		                    	// 判断是否有这个供应商的包邮活动
//     		                    	$shipping_fee = array_key_exists($suppliers_id,$client_free_arr['free_suppliers'])?0:$shipping_fee;
//     		                    	break;
    	
//     		            }
    		if($client_free_arr['free_plat'] == 1){
    			return	$shipping_fee = 0;
    		}
    		if($client_free_arr['free_plat'] == 0){
    			if(array_key_exists($suppliers_id,$client_free_arr['free_suppliers'])){
    				return	$shipping_fee = 0;
    			}
    			
    			if($total_price >= 2900){
    				return	$shipping_fee = 0;
    			}
    			else{
    				return $shipping_fee;
    			}
    		}
    		
    	}
    	if($total_price >= 6800 ){
    		return $shipping_fee = 0;
    	}
    	return $shipping_fee;
    	
    }


    /**
     * 获取在购物车里的商品中在活动列表里的商品总价，单位为分
     */
    private function  getSumPriceInList($cart_list){
        $sum_price_in_list = 0;
        $relationMod = M('topic_relation');  //商品与活动的关系
        $appMod = M("app_config");
        $topic = $appMod->where("config_name = '味蕾旅行'")->find();
        $spu_list = $relationMod->where("topic_id = '{$topic[config_value]}'")->getField('spu_id',true);
        foreach ($cart_list as $v) {
            if (in_array($v['spu_id'], $spu_list)) {  //如果商品在活动商品列表里并且都是小喵自营商品
                $sum_price_in_list += $v['price_now'] * $v['num'];  //将在列表里的商品价格做累计计算
            }
        }
        return $sum_price_in_list;
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