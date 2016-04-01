<?php
/**
 * 
 * @author: zengnanlin
 * @since: 2015-08-21
 */

class CouponAction extends  BaseAction{
    /**
    +----------------------------------------------------------
     * 我的优惠券 : 2908
    +----------------------------------------------------------
     */
    public function myCoupon(){
        // 未登录拒绝操作
        if($this->uid<=0){
            $this->errorView($this->ws_code['login_out_error'],$this->msg['login_out_error']);
        }
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;
        // 计算出用户所拥有的有效优惠券
        $coupon_user_mod = M("coupon_user");// 优惠券与用户关系表
        $coupon_arr = $coupon_user_mod->where("user_id = {$this->uid} and is_use=0 and is_show = 1")->getField("coupon_num",true);// 获取用户的所有优惠券

        $coupon_count_value = array_count_values($coupon_arr);// 重复的优惠券记录
        $coupon_strs = "'".implode('\',\'',$coupon_arr)."'";
        $coupon_mod = M("coupon");// 优惠券表
        $now = time();
        $coupon_list = $coupon_mod->where("coupon_num in($coupon_strs) and use_end_time>='{$now}' ")->field("id,name,get_start_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->order("rule_free desc,rule_money asc")->select();// 搜索出对应的优惠券

        // 获取供应商信息
        $coupon_suppliers = "";
        $tmp_coupon_list = $coupon_list;

        foreach($tmp_coupon_list as $k=> $v){
            if($v['suppliers_id']>0){
                $coupon_suppliers .=$v['suppliers_id'].",";
            }

//            // 优惠券码 重复的处理
            if(array_key_exists($v['coupon_num'],$coupon_count_value)){
                for($i=1;$i<$coupon_count_value["{$v['coupon_num']}"];$i++){
                    array_splice($coupon_list,$k,0,array($v));
                }
            }
        }

        $suppliers_list = array();
        if($coupon_suppliers!=""){
            $coupon_suppliers = rtrim($coupon_suppliers,",");
            $suppliers_mod = M("suppliers");
            $suppliers_list = $suppliers_mod->where("suppliers_id in($coupon_suppliers)")->field("suppliers_id,shop_name")->select();
            $suppliers_list = secToOne($suppliers_list,"suppliers_id","shop_name");
        }



        $user_coupon_arr = array();
        foreach ($coupon_list as $v) {

            if($v['type'] == 2 && $v['get_start_time'] > $now){
                continue ;// 按用户发放的优惠券，如果还未到发放时间就不显示
            }
            if($v['suppliers_id']==0){
                $msg = "全平台使用";
            }else{
                $msg = $suppliers_list["{$v['suppliers_id']}"] ? $suppliers_list["{$v['suppliers_id']}"]."专用":"指定供应商使用";
            }

            $user_coupon_arr[] = array(
                "id"                => intval($v['id']),
                "title"             => $v['name'],
                "is_valid"          => $v["use_end_time"] >= $now? 1:0,
                "status"            => 1,
                "start_time"        => intval($v['use_start_time']),
                "end_time"          => intval($v['use_end_time']),
                "validation_amount" =>  free_format($v['rule_free']),
                "using_range"       => "订单满". price_format($v['rule_money'])."元可用",
                "msg"               => $msg,
            );
        }
        if(empty($user_coupon_arr)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }
        $this->successView($user_coupon_arr);
    }
    /**
    +----------------------------------------------------------
     * 获取优惠券红点提醒 2903
    +----------------------------------------------------------
     */
    public function couponRedHot(){
        // 未登录用户拒绝调用
        if($this->uid<=0){
            $this->errorView($this->ws_code['no_privilege'],$this->msg['no_privilege']);
        }
        // 实例化
        $coupon_user_mod = M("coupon_user");

        $coupon_arr = $coupon_user_mod->where("user_id = {$this->uid} and is_use=0 and is_show = 1")->getField("coupon_num",true);// 获取用户的所有优惠券

        $coupon_strs = "'".implode('\',\'',$coupon_arr)."'";
        $coupon_mod = M("coupon");// 优惠券表

        $coupon_list = $coupon_mod->where("coupon_num in($coupon_strs)")->field("id,name,get_start_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->order("rule_free desc,rule_money asc")->select();// 搜索出对应的优惠券
        $now = time();
        $user_coupon_arr = array();
        foreach ($coupon_list as $v) {

            if ($v['type'] == 2 && $v['get_start_time'] > $now) {
                continue;// 按用户发放的优惠券，如果还未到发放时间就不显示
            }
            // 如果使用结束时间小于当前时间的则剔除有效性
            if($v['use_end_time']<$now){
                continue;
            }
            $user_coupon_arr[] = $v['id'];
        }

        $data = array(
            "status"    => 0
        );
        if(!empty($user_coupon_arr)){
            $data['status'] = 1;
        }
        $this->successView('',$data);

    }
    /**
    +----------------------------------------------------------
     * 获取优惠券使用规则 : 2910
    +----------------------------------------------------------
     */
    public function about(){
        $this->display("Discount/ruler");
    }

    /**
    +----------------------------------------------------------
     * 根据供应商ID获取商品促销与特殊优惠H5页面 : 2901
     * // 0：未领取 1：已领取, 2：优惠券被抢光了 3：发放时间结束了 4: 优惠券还没开始发放
     * type
     * 0：商品id
     * 1：供应商id
     * 2：优惠券集合id（首页、专题页的轮播图使用）
    +----------------------------------------------------------
     */
    public function activeList(){
        $id = htmlspecialchars($this->getField('id','',true));// 商品id 或 供应商id 或优惠券集合id
        $type = intval($this->getFieldDefault('type',0));// 0 1 2
        $user_id  = intval($this->getField("uid"));// 用户的uid
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;
        switch($type){
            case 0:
                $goods_id = $id;
                $supplier_id = 0;
                break;
            case 1:
                $goods_id = 0;
                $supplier_id = $id;
                break;
            case 2:

                // 直接跳转到另一个方法处理
                $this->bannerCouponList($id,$user_id);
                break;
            default:
                break;
        }

        // 获取当前的包邮信息
        $free_fee_template_mod = M("free_fee_template");
        $now = time();
        $plat_form_free = $free_fee_template_mod->where("type = 2 and status='published' and start_time<={$now} and end_time>={$now}")->order("price asc")->field("template_name")->find();
        $free_title = '';
        if(!empty($plat_form_free)) {
            $free_title = $plat_form_free['template_name'];
        }else{
            // 重新获取 suppliers_id 丢失
            $spu_mod = M("spu");
            if($goods_id !=0) {
                $supplier_id = $spu_mod->where("spu_id = $goods_id")->getField("suppliers_id");
            }

            if(empty($supplier_id)){
                $this->errorView($this->ws_code['supplier_id_not_exist'],$this->msg['supplier_id_not_exist']);
            }

            $suppliers_free_fee = $free_fee_template_mod->where("suppliers_id = {$supplier_id} and status='published' and start_time<={$now} and end_time>={$now} ")->order("price asc")->field("template_name")->find();

            $free_title = $suppliers_free_fee['template_name'] ? $suppliers_free_fee['template_name']: '';
        }
        if(!empty($free_title)) {
            $os_version = $this->version;
            $active_arr['activity'] = array(
                "icon_title" => "满包邮",
                "title" => $free_title,
                "action" => array(
                    "type" => $plat_form_free ? 1:0 ,
                    "info" => $supplier_id
                )
            );
        }else{
            $active_arr['activity']  = "";
        }
        $now = time();// 当前时间
        // 获取这个商品的所有相关的优惠券
        $coupon_spu_mod = M("coupon_spu");
        $coupon_mod = M('coupon');
        if($goods_id == 0){
            // suppliers_id 等于0也是表示全平台通用
            $coupon_num_arr = $coupon_mod->where("suppliers_id = $supplier_id or suppliers_id = 0")->getField("coupon_num",true);

        }else{
            // spu_id＝1 表示全平台的商品通用
            $coupon_num_arr = $coupon_spu_mod->where("spu_id = {$goods_id} or spu_id=1")->getField("coupon_num",true);
        }

        if(!empty($coupon_num_arr)){
            $coupon_num_str = "'".implode('\',\'',$coupon_num_arr)."'";
            $coupon_user_mod = M("coupon_user");
            if($user_id<=0){
                //  未登录 展示优惠券不含系统推送的
                $coupon_arr = $coupon_mod->where("coupon_num in($coupon_num_str) and status='published' and use_end_time>={$now} and type=1")->order("rule_free desc,rule_money asc")->field("id,name,get_start_time,get_end_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->select();
                $user_coupon_arr = array();// 未登录
            }else{
                $coupon_arr = $coupon_mod->where("coupon_num in($coupon_num_str) and status='published' and use_end_time>={$now} ")->order("rule_free desc,rule_money asc")->field("id,name,get_start_time,get_end_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->select();
                $user_coupon_arr = $coupon_user_mod->where(" user_id =$user_id and coupon_num in($coupon_num_str) ")->getField("coupon_num",true);

            }
            // 确保有数据
            $active_arr['items'] = array();// 初始化优惠券列表数据结构
            if(!empty($coupon_arr)){
                // 获取供应商信息
                $coupon_suppliers = "";
                foreach($coupon_arr as $v){
                    if($v['suppliers_id']>0){
                        $coupon_suppliers .=$v['suppliers_id'].",";
                    }
                }
                $suppliers_list = array();
                if($coupon_suppliers!=""){
                    $coupon_suppliers = rtrim($coupon_suppliers,",");
                    $suppliers_mod = M("suppliers");
                    $suppliers_list = $suppliers_mod->where("suppliers_id in($coupon_suppliers)")->field("suppliers_id,shop_name")->select();
                    $suppliers_list = secToOne($suppliers_list,"suppliers_id","shop_name");

                }

                foreach($coupon_arr as $v){
                    $status = 0;// 默认是可领取状态

                    // 如果领取结束时间已到的表示领取时间结束
                    if($v['get_end_time']<$now){
                        $status = 3;
                    }
                    if($v['type']==1&&$v['surplus_num'] <= 0){
                        $status = 2;// 被抢光的优惠券
                    }
                    //0：未领取 1：已领取, 2：优惠券被抢光了 3：发放时间结束了 4: 优惠券还没开始发放
                    // 判断 优惠券的状态 status 0:未领取，1：已领取，2:优惠券还没开始发放
                    if(in_array($v['coupon_num'],$user_coupon_arr)&&$v['type']==2&&$v['get_start_time']>$now){
                        continue ; // 跳过发放时间大于当前时间且发放给用户的优惠券
                    }elseif(in_array($v['coupon_num'],$user_coupon_arr)){
                        $status = 1 ;// 已领取
                    }elseif($v['type'] == 2){
                        // 说明这个优惠券不属于该用户
                        continue;//跳过那些 以用户发放，但不属于该用户的优惠券
                    }elseif($v['get_start_time']>$now){
                        // 优惠券还没开始发放
                        $status = 4 ;
                    }
                    // msg 描述判断 指定供应商 OR 全平台可用
                    if($v['suppliers_id']==0){
                        $msg = "全平台使用";
                    }else{
                        $msg = $suppliers_list["{$v['suppliers_id']}"] ? $suppliers_list["{$v['suppliers_id']}"]."专用":"指定供应商使用";
                    }
                    $active_arr['items'][] = array(
                        "id"                => intval($v['id']),
                        "title"             => $v['name'],
                        "status"            => $status,// 需计算
                        "start_time"        => intval($v['use_start_time']),
                        "end_time"          => intval($v['use_end_time']),
                        "validation_amount" =>  free_format($v['rule_free']),
                        "using_range"        => "订单满". price_format($v['rule_money'])."元可用",
                        "msg"               => $msg,
                    );
                }
            }
        }

        // 计算列表个数
        $temp['count'] = count($active_arr['items']);
        $active_arr = array_merge($temp,$active_arr);
        $this->successView('',$active_arr);
    }
    /**
    +----------------------------------------------------------
     * 根据优惠券集合id获取优惠券列表
     * @param $id 集合id
    +----------------------------------------------------------
     */
    public function bannerCouponList($id,$user_id){
        $group_topic_mod = M("group_topic");
        $coupon_id_arr = $group_topic_mod->where("group_id = $id")->order("sort desc")->getField("topic_id",true);

        $coupon_id_strs = implode(",",$coupon_id_arr);
        $coupon_mod = M("coupon");
        $now = time();

        // 确保有数据
        $active_arr['items'] = array();// 初始化优惠券列表数据结构

        $coupon_user_mod = M("coupon_user");

//        $user_id = $this->uid;
        if($user_id<=0){
            //  未登录
            $coupon_arr = $coupon_mod->where("id in($coupon_id_strs) and status='published' and use_end_time>={$now} ")->order("rule_free desc,rule_money asc")->field("id,name,get_start_time,get_end_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num")->select();
            $user_coupon_arr = array();// 未登录
        }else{
            $coupon_arr = $coupon_mod->where("id in($coupon_id_strs) and status='published' and use_end_time>={$now} ")->order("rule_free desc,rule_money asc")->field("id,name,get_start_time,get_end_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num")->select();
            $coupon_arr_new = arrayFormat($coupon_arr,"coupon_num");
            $coupon_arr_nums = array_keys($coupon_arr_new);
            $coupon_strs = "'".implode('\',\'',$coupon_arr_nums)."'";
            $user_coupon_arr = $coupon_user_mod->where("user_id =$user_id and coupon_num in($coupon_strs)")->getField("coupon_num",true);
        }


        // 如果为空，则说明没有数据
        if(empty($coupon_arr)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }

        // 获取供应商信息
        $coupon_suppliers = "";
        foreach($coupon_arr as $v){
            if($v['suppliers_id']>0){
                $coupon_suppliers .=$v['suppliers_id'].",";
            }
        }
        $suppliers_list = array();
        if($coupon_suppliers!=""){
            $coupon_suppliers = rtrim($coupon_suppliers,",");
            $suppliers_mod = M("suppliers");
            $suppliers_list = $suppliers_mod->where("suppliers_id in($coupon_suppliers)")->field("suppliers_id,shop_name")->select();
            $suppliers_list = secToOne($suppliers_list,"suppliers_id","shop_name");
        }

        foreach($coupon_arr as $v){
            $status = 0;// 默认状态 未领取
            if($v['get_end_time']<$now){
                $status = 3;
            }

            if($v['surplus_num'] <= 0){
                $status = 2;//被抢光的优惠券
            }
            //0：未领取 1：已领取, 2：优惠券被抢光了 3：发放时间结束了 4: 优惠券还没开始发放
            // 判断 优惠券的状态 status 0:未领取，1：已领取，2:优惠券还没开始发放
            if(in_array($v['coupon_num'],$user_coupon_arr)&&$v['type']==2&&$v['get_start_time']>$now){
                continue ; // 跳过发放时间大于当前时间且发放给用户的优惠券
            }elseif(in_array($v['coupon_num'],$user_coupon_arr)){
                $status = 1 ;// 已领取
            }elseif($v['get_start_time']>$now){
                // 优惠券还没开始发放
                $status = 4 ;
            }
            // msg 描述判断 指定供应商 OR 全平台可用
            if($v['suppliers_id']==0){
                $msg = "全平台使用";
            }else{
                $msg = $suppliers_list["{$v['suppliers_id']}"] ? $suppliers_list["{$v['suppliers_id']}"]."专用":"指定供应商使用";
            }
            $active_arr['items'][] = array(
                "id"                => intval($v['id']),
                "title"             => $v['name'],
                "status"            => $status,// 需计算
                "start_time"        => intval($v['use_start_time']),
                "end_time"          => intval($v['use_end_time']),
                "validation_amount" =>  free_format($v['rule_free']),
                "using_range"        => "订单满". price_format($v['rule_money'])."元可用",
                "msg"               => $msg,
            );
        }

        // 计算列表个数
        $temp['count'] = count($active_arr['items']);

        $active_arr = array_merge($temp,$active_arr);
        $this->successView('',$active_arr);
    }
    /**
    +----------------------------------------------------------
     * 根据供应商ID获取商品促销与特殊优惠 客户端 : 2902
     * @param id 商品id或供应商id或优惠券集合的id
     * @param type
     * 0：商品id
     * 1：供应商id
     * 2：优惠券集合id（首页、专题页的轮播图使用）
    +----------------------------------------------------------
     */
    public function activeListApp(){
        $id   = intval($this->getField("id",'',true));// 必须传的id
        $type = intval($this->getFieldDefault("type",0));// 类型id
        $site_url = C("SITE_URL");// 站点url
        $data['url'] = $site_url."/api.php?apptype=0&srv=2915&type=".$type."&id=".$id."&cid=10002&uid=". $this->uid."&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$this->os_type."&version=12";
        $this->successView("",$data);
    }
    /**
    +----------------------------------------------------------
     * 领取优惠券 : 2907
     * 0:领取成功,1：已领取, 2：优惠券被抢光了 3：发放时间结束了 4:优惠券还没开始发放
    +----------------------------------------------------------
     */
    public function get(){
        $discoupon_id = intval($this->getField('discoupon_id','',true));// 优惠券id    	
        $type = intval($this->getFieldDefault("type",0));   // 约束上面参数的意义。0 表示优惠券id 1 表示优惠券集合id
        if($type == 1){
			if($discoupon_id == 18){
					//再次判断用户是否购买过东西，如果买过则提示优惠券已经被领完了
					$o = M();
					$sql = "select order_id from ls_order where user_id = '{$this->uid}' and pay_sn <> '' and pay_time <> 0 limit 1 ";
					$r = $o->query($sql);
					if($r){
						$data['status'] = 2;
						$this->successView("", $data);
						 die;
					}
					$this->gets();
					die;
			}
			else{
	           // $this->getGift($discoupon_id);//旧代码
                $this->getcoups($discoupon_id);
	            die;
			}
        }

        $coupon_mod = M("coupon");
        $now  = time();
        if($this->uid<=0){
            $this->errorView($this->ws_code['login_out_error'],$this->msg['login_out_error']);
        }
        $coupon_data = $coupon_mod->where("id = $discoupon_id")->field('coupon_num,online_time,get_start_time,get_end_time,use_start_time,status,surplus_num,use_end_time')->find();// 优惠券数据
        if(empty($coupon_data)){
            $this->errorView($this->ws_code['coupon_id_not_exist'],$this->msg['coupon_id_not_exist']);
        }
        // 优惠券还没开始发放 4
        if($coupon_data['get_start_time']>$now){
            $data['status'] = 4;
            $this->successView("",$data);
        }
        // 发放时间结束了 3
        if($coupon_data['get_end_time']<$now){
            $data['status'] = 3;
            $this->successView("",$data);
        }
        // 优惠券被抢光了 2
        if($coupon_data['surplus_num']<=0){
            $data['status'] = 2;
            $this->successView("",$data);
        }
        // 已领取 1
        $coupon_user_mod = M("coupon_user");
        $check_result = $coupon_user_mod->where("user_id = {$this->uid} and coupon_num='{$coupon_data['coupon_num']}'")->find();

        if(!empty($check_result)){
            $data['status'] = 1;
            $this->successView("",$data);
        }
        // 剩下的就是领取操作。考虑临界值 情况 。是否还有库存
        $coupon_mod->startTrans();// 开启事务
        // 插入关系
        $coupon_user_data = array(
            "user_id"       => $this->uid,
            "coupon_num"    => $coupon_data['coupon_num'],
            "send_time"     => $now
        );
        // 添加成功
        $result = $coupon_mod->execute("update ls_coupon set surplus_num=surplus_num-1,send_num = send_num+1 where id = {$discoupon_id} and surplus_num>0");
        if(!$result){
                $coupon_mod->rollback();
                $this->errorView($this->ws_code['coupon_get_error'],$this->msg['coupon_get_error']);
        }else{
           // 添加成功
            if(!$coupon_user_mod->add($coupon_user_data)){
                $coupon_mod->rollback();
                $this->errorView($this->ws_code['coupon_get_error'],$this->msg['coupon_get_error']);
            }
        }
        // 执行成功 提交事务
        $coupon_mod->commit();// 提交事务
        $data['status'] = 0;
        $this->successView("",$data);
    }
    /**
    +----------------------------------------------------------
     * //领取优惠券大礼包
     * 0:领取成功,1：已领取, 2：优惠券被抢光了 3：发放时间结束了 4:优惠券还没开始发放
    +----------------------------------------------------------
     */

    public function getGift($id){
        $coupon_user_mod = M("coupon_user");
        $cou_gift_rel_mod = M("cou_gift_relation");
        $gift_user_mod = M("gift_user");

        $coupon_list = $cou_gift_rel_mod->where("gift_id = $id")->field("num,coupon_num")->select();
        if(empty($coupon_list)){
            $this->errorView($this->ws_code["gifts_not_exist"],$this->msg['gifts_not_exist']);
        }
        $is_get = $gift_user_mod->where("gift_id=$id and user_id = $this->uid")->find();
//        p($gift_user_mod->getLastSql());
        if(!empty($is_get)){
            // 有记录说明已领取
            $data['status'] = 1;
            $this->successView("",$data);
        }

        $model = M();
        $model->startTrans();//开启事务
        $sql = "INSERT INTO ls_coupon_user (user_id,coupon_num) VALUES ";
        foreach ($coupon_list as $v) {
            $tmp = str_repeat("($this->uid,'{$v['coupon_num']}'),",$v['num']);
            $sql .= $tmp;
        }
        $sql = rtrim($sql,",");
        if($model->execute($sql)){

            // 下一步插入关系表
            $gift_data = array(
                "user_id"  => $this->uid,
                "gift_id"  => $id
            );
            if($gift_user_mod->add($gift_data)){
                $data['status'] = 0;
                $model->commit();// 事务提交
                $this->successView("",$data);
            }else{
                $model->rollback();
                $this->errorView($this->ws_code['gifts_not_exist'],$this->msg['gifts_not_exist']);
            }
        }else{
            $model->rollback();
            $this->errorView($this->ws_code["gifts_not_exist"],$this->msg['gifts_not_exist']);
        }

    }
    /**
    +----------------------------------------------------------
     * 根据供应商ID获取商品促销与特殊优惠 H5 页面载入 : 2915
    +----------------------------------------------------------
     */
    public function couponWap(){
        $id = intval($this->getField("id"));
        $type = intval($this->getField("type"));
        $this->assign("id",$id);
        $this->assign("type",$type);
        $uid  = intval($this->getField("uid"));
        $this->assign("uid",$uid);
        $site_url = C("SITE_URL");
        $this->assign("site_url",$site_url);
        if($this->os_type==1){
            $tpl = "Discount/disCountListIos";
        }else{
            $tpl = "Discount/disCountList";
        }
        $this->display($tpl);
    }
    /**
    +----------------------------------------------------------
     *  获取优惠券详情（H5使用）2905
    +----------------------------------------------------------
     */
    public function detail(){
        $discoupon_id = intval($this->getField('discoupon_id','',true));// 优惠券id
        $uid          = intval($this->getFieldDefault("uid",0));
        $coupon_mod = M("coupon");
        $now = time();
        $coupon_data = $coupon_mod->where("id = $discoupon_id ")->field("id,name,status,type,get_start_time,use_start_time,get_end_time,use_end_time,rule_free,rule_money,rule_desc,suppliers_id,surplus_num,send_num,coupon_num")->find();
        if(empty($coupon_data)){
            $this->errorView($this->ws_code['coupon_id_not_exist'],$this->msg['coupon_id_not_exist']);
        }
        $status = 0;// 默认是未领取状态
        if($coupon_data['type'] ==1 && $coupon_data['surplus_num']==0){
            $status = 2;// 被抢光了
        }
        // 发放时间还未开始
        if($coupon_data['get_start_time']>$now){
            $status = 4;
        }
        if($coupon_data['get_end_time']<$now){
            $status = 3;// 发放时间结束
        }
        // 判断该优惠券用户是否已经领取了
        if($uid > 0){
            $coupon_user_mod = M("coupon_user");
            // 如果是全平台的优惠券 则在排除了以上情况下，即是已领取状态
            $check_result = $coupon_user_mod->where("user_id = {$uid} and coupon_num='{$coupon_data['coupon_num']}'")->find();
            if(!empty($check_result)){
                $status = 1; // 领取
            }
        }
        // 获取优惠券指定供应商的名称
        $shop_name = "指定供应商使用";// 初始值

        if($coupon_data['suppliers_id']>0){
            $suppliers_mod = M("suppliers");
            $shop_name  = $suppliers_mod->where("suppliers_id = {$coupon_data['suppliers_id']}")->getField("shop_name");
            $shop_name  = $shop_name ? $shop_name."专用":"指定供应商使用";// 避免出现空值
        }

        $discountCoupon = array(
            "id"                => intval($coupon_data['id']),
            "title"             => $coupon_data['name'],
            "is_show"           => $coupon_data['type'] == 1? 1:0,
            "desc"              => nl2br($coupon_data['rule_desc']),
            "remaining_count"   => intval($coupon_data['surplus_num']),
            "total_count"       => intval($coupon_data['surplus_num']+$coupon_data['send_num']),
            "status"            => $status,//
            "start_time"        => intval($coupon_data['use_start_time']),
            "end_time"          => intval($coupon_data['use_end_time']),
            "validation_amount"  => free_format($coupon_data['rule_free']),
            "using_range"        => "订单满".price_format($coupon_data['rule_money'])."元可用",
            "msg"               => $coupon_data['suppliers_id']? $shop_name:"全平台使用",
            "action"            => array(
                "type"          => $coupon_data['suppliers_id'] ? 0:1,
                "info"          => $coupon_data['suppliers_id']
            )
        );
        $this->successView("",$discountCoupon);
    }

    /**
    +----------------------------------------------------------
     *  获取优惠券大礼包（H5使用）2925
    +----------------------------------------------------------
     */
    public function detailGifts(){
        $gifts_id = intval($this->getField('gifts_id','',true));// 优惠券id
        $uid      = intval($this->getFieldDefault("uid",0));
        $gifts_mod = M("coupon_gift");// 优惠券大礼包
        $gift_data = $gifts_mod->find("$gifts_id");
        // 如果这个大礼包
        if(empty($gift_data)){
            $this->errorView($this->ws_code['gifts_not_exist'],$this->msg['gifts_not_exist']);
        }
        // 判断用户是否已领取
        $gift_user_mod = M("gift_user");
        if($uid>0){
            $is_get = $gift_user_mod->where("gift_id = $gifts_id and user_id=$uid")->find();
        }else{
            $is_get = 0;
        }

        //===========大礼包内的优惠券页面======================

        // 计算出用户所拥有的有效优惠券
        $cou_gift_mod = M("cou_gift_relation");// 优惠券与用户关系表
        $gift_coupon_arr = $cou_gift_mod->where("gift_id = $gifts_id ")->getField("coupon_num,num");// 获取用户的所有优惠券

        $coupon_arr = array_keys($gift_coupon_arr);// 重复的优惠券记录
        $coupon_strs = "'".implode('\',\'',$coupon_arr)."'";
        $coupon_mod = M("coupon");// 优惠券表

        $coupon_list = $coupon_mod->where("coupon_num in($coupon_strs)")->field("id,name,get_start_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->order("rule_free desc,rule_money asc")->select();// 搜索出对应的优惠券

        $now = time();
        // 获取供应商信息
        $coupon_suppliers = "";
        $tmp_coupon_list = $coupon_list;

        foreach($tmp_coupon_list as $k=> $v){
            if($v['suppliers_id']>0){
                $coupon_suppliers .=$v['suppliers_id'].",";
            }

//            // 优惠券码 重复的处理
            for($i=1;$i< $gift_coupon_arr["{$v['coupon_num']}"];$i++){
                array_splice($coupon_list,$k,0,array($v));
            }
        }

        $suppliers_list = array();
        if($coupon_suppliers!=""){
            $coupon_suppliers = rtrim($coupon_suppliers,",");
            $suppliers_mod = M("suppliers");
            $suppliers_list = $suppliers_mod->where("suppliers_id in($coupon_suppliers)")->field("suppliers_id,shop_name")->select();
            $suppliers_list = secToOne($suppliers_list,"suppliers_id","shop_name");
        }



        $user_coupon_arr = array();
        foreach ($coupon_list as $v) {

            if($v['suppliers_id']==0){
                $msg = "全平台使用";
            }else{
                $msg = $suppliers_list["{$v['suppliers_id']}"] ? $suppliers_list["{$v['suppliers_id']}"]."专用":"指定供应商使用";
            }

            $user_coupon_arr[] = array(
                "id"                => intval($v['id']),
                "title"             => $v['name'],
                "is_valid"          => $v["use_end_time"] >= $now? 1:0,
                "status"            => 1,
                "start_time"        => intval($v['use_start_time']),
                "end_time"          => intval($v['use_end_time']),
                "validation_amount" =>  free_format($v['rule_free']),
                "using_range"       => "订单满". price_format($v['rule_money'])."元可用",
                "msg"               => $msg,
            );
        }
        //=====================大礼包内的优惠券页面========================
        $share_url = C("SITE_URL")."/web.php?m=Share&a=gift_share&gifts_id=$gifts_id";

        $gift_data = array(
            "id"        => intval($gift_data['id']),
            "title"     => $gift_data['title'],
            "desc"      => nl2br($gift_data['detail']),
            "image"     => image($gift_data['image_src']),
            "status"    => $is_get? 1:0,
            "contentUrl" => $share_url,
            "items"     => $user_coupon_arr,
            "action"    => array(
                "type"  => 1,
                "info"  => "1"
            )
        );
        $this->successView("",$gift_data);
    }

    /**
    +----------------------------------------------------------
     *  获取优惠券详情（客户端使用）2906
    +----------------------------------------------------------
     */
    public function detailApp(){
        $discoupon_id = intval($this->getField('discoupon_id','',true));// 优惠券id
        $type = intval($this->getFieldDefault('type',0));// 优惠券id
        $site_url = C("SITE_URL");// 站点url
        //新用户id为8时的判断
//        if( $type == 8 ){
//        	//新人优惠券礼包去3002接口  8暂时定为固定值，如果后台新人礼包变成别的id则 8也需要跟着改变
//        	if($discoupon_id == 8){
//        		$os_type = I('get.os_type','','trim,intval');
//        		$version = I('get.version','','trim,intval');
//        		$uid = I('get.uid','','trim,intval');
//        		$tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
//        		$sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
//        		$wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
//        		$cid = $this->cid;
//        		$data['url'] = $site_url."/api.php?apptype=0&srv=3002&cid=$cid&uid=$uid&tms=$tms&sig=$sig&wssig=$wwsig&os_type=$os_type&version=$version&type=1";
//        	}else {
//        		$data['url'] = $site_url . "/api.php?apptype=0&srv=2926&gifts_id=" . $discoupon_id . "&cid=10002&uid=" . $this->uid . "&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=" . $this->os_type . "&version=$this->version";
//        	}


        if( $type == 8 ){
            //新人优惠券礼包去3002接口  8暂时定为固定值，如果后台新人礼包变成别的id则 8也需要跟着改变
            if($discoupon_id == 18){
                $os_type = I('get.os_type','','trim,intval');
                $version = I('get.version','','trim,intval');
                $uid = I('get.uid','','trim,intval');
                $tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
                $sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
                $wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
                $cid = $this->cid;
                $data['url'] = $site_url."/api.php?apptype=0&srv=3002&cid=$cid&uid=$uid&tms=$tms&sig=$sig&wssig=$wwsig&os_type=$os_type&version=$version&type=1";
            }else {
                $data['url'] = $site_url . "/api.php?apptype=0&srv=2926&gifts_id=" . $discoupon_id . "&cid=10002&uid=" . $this->uid . "&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=" . $this->os_type . "&version=$this->version";
            }
        }elseif($type ==9){
            $tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
            $sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
            $wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
            $cid = $this->cid;
            $data['url'] = $site_url."/api.php?apptype=0&srv=2921&cid=$cid&uid=".$this->uid."&tms=$tms&sig=$sig&wssig=$wwsig&os_type=".$this->os_type."&version=$this->version";
        }else{
            //$data['url'] = $site_url."/api.php?apptype=0&srv=2916&discoupon_id=".$discoupon_id."&cid=10002&uid=".$this->uid."&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$this->os_type."&version=$this->version";
            $data['url'] = $site_url . "/api.php?apptype=0&srv=2926&gifts_id=" . $discoupon_id . "&cid=10002&uid=" . $this->uid . "&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=" . $this->os_type . "&version=$this->version";
        }
        $this->successView("",$data);
    }
    /**
    +----------------------------------------------------------
     *  获取大礼包详情（H5使用）2926
    +----------------------------------------------------------
     */
    public function detailGifsWap(){
        $gifts_id = intval($this->getField("gifts_id"));
        $uid = intval($this->getField("uid",0));
        $cid = $_SESSION['cid'];;
        $os_type = intval($this->getFieldDefault("os_type",1));
        $site_url = C("SITE_URL");
        $this->assign("os_type",$os_type);
        $this->assign("site_url",$site_url);
        $this->assign("gifts_id",$gifts_id);
        $this->assign("uid",$uid);
        $this->assign("cid",$cid);
        //老代码静态页面
        if($os_type == 1){
            $this->display("Discount/giftsIos");
        }else{
            $this->display("Discount/gifts");
        }

//        if($os_type == 1){
//            $this->display("Discount/giftsNewIos");
//        }else{
//            $this->display("Discount/giftsNew");
//        }
    }
    /**
    +----------------------------------------------------------
     *  获取优惠券详情（H5使用）2916
    +----------------------------------------------------------
     */
    public function detailWap(){
        $discoupon_id = intval($this->getField("discoupon_id"));
        $uid = intval($this->getField("uid",0));
        $site_url = C("SITE_URL");
        $this->assign("site_url",$site_url);
        $this->assign("discoupon_id",$discoupon_id);
        $this->assign("uid",$uid);
        if($this->os_type == 1){
            $this->display("Discount/disCountDetailIos");
        }else{
            $this->display("Discount/disCountDetail");
        }

    }
    /**
    +----------------------------------------------------------
     *  优惠券通用使用规则 （客户端使用）2917
    +----------------------------------------------------------
     */
    public function ruleApp(){
        $site_url = C("SITE_URL");
        $data['url'] = $site_url."/api.php?apptype=0&srv=2910&&cid=10002&uid=0&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$this->os_type."&version=12";
        $this->successView("",$data);

    }
    
    /**
     +----------------------------------------------------------
     *  优惠券（H5使用）3002
     +----------------------------------------------------------
     */
    public function giftdetails(){
    	$site_url = C("SITE_URL");// 站点url
    	$os_type = I('get.os_type','','trim,intval');
    	$version = I('get.version','','trim,intval');

        $u = I('get.u','','trim,intval');
    	//$id = isset(I('get.id','','trim,intval'))?I('get.id','','trim,intval'):1;
    	$uid = I('get.uid','','trim,intval');
    	$gifts_id =I('get.gifts_id','','trim,intval');
    	//$gifts_id = 12;//之前新用户的礼包id
        $gifts_id = 18;
        if($u == 1){
            $gifts_id = 19;
        }
    	$uid= isset($uid)?$uid:0;
    	//$gifts_id= isset($gifts_id)?$gifts_id:8;//
        $gifts_id= isset($gifts_id)?$gifts_id:18;
    	
    	$tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
    	$sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
    	$wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
    	$cid = $this->cid;


    	$url = $site_url."/api.php?apptype=0&srv=3003&cid=$cid&uid=$uid&tms=$tms&sig=$sig&wssig=$wwsig&os_type=$os_type&version=$version&gifts_id=$gifts_id";
    	 //print_r($url);
    	$this->assign("url",$url);
    	 
    	if($os_type == 1){
    		$this->display("Discount/giftsNewIos");
    	}else{
    		$this->display("Discount/giftsNew");
    	}
    }



    /**
    +----------------------------------------------------------
     *  优惠券（H5使用）3003
    +----------------------------------------------------------
     */
    public function gf(){
        $site_url = C("SITE_URL");// 站点url
        $os_type = I('get.os_type','','trim,intval');
        $version = I('get.version','','trim,intval');
        //$id = isset(I('get.id','','trim,intval'))?I('get.id','','trim,intval'):1;
       // $uid = I('get.uid','','trim,intval');
       // $gifts_id =I('get.gifts_id','','trim,intval');
       // $gifts_id = 8;
       // $uid= isset($uid)?$uid:0;
        //$gifts_id= isset($gifts_id)?$gifts_id:8;

        $tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
        $sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
        $wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
        $cid = $this->cid;
        $gifts_id = intval($this->getField('gifts_id','',true));// 优惠券id
        $uid      = intval($this->getFieldDefault("uid",0));
        $gifts_mod = M("coupon_gift");// 优惠券大礼包
        $gift_data = $gifts_mod->find("$gifts_id");
        // 如果这个大礼包
        if(empty($gift_data)){
            $this->errorView($this->ws_code['gifts_not_exist'],$this->msg['gifts_not_exist']);
        }
        // 判断用户是否已领取
        $gift_user_mod = M("gift_user");
        if($uid>0){
            $is_get = $gift_user_mod->where("gift_id = $gifts_id and user_id=$uid")->find();
        }else{
            $is_get = 0;
        }

        //===========大礼包内的优惠券页面======================

        // 计算出用户所拥有的有效优惠券
        $cou_gift_mod = M("cou_gift_relation");// 优惠券与用户关系表
        $gift_coupon_arr = $cou_gift_mod->where("gift_id = $gifts_id ")->getField("coupon_num,num");// 获取用户的所有优惠券

        $coupon_arr = array_keys($gift_coupon_arr);// 重复的优惠券记录
        $coupon_strs = "'".implode('\',\'',$coupon_arr)."'";
        $coupon_mod = M("coupon");// 优惠券表

        $coupon_list = $coupon_mod->where("coupon_num in($coupon_strs)")->field("id,name,get_start_time,use_start_time,use_end_time,rule_free,rule_money,suppliers_id,surplus_num,coupon_num,type")->order("rule_free desc,rule_money asc")->select();// 搜索出对应的优惠券

        $now = time();
        // 获取供应商信息
        $coupon_suppliers = "";
        $tmp_coupon_list = $coupon_list;

        foreach($tmp_coupon_list as $k=> $v){
            if($v['suppliers_id']>0){
                $coupon_suppliers .=$v['suppliers_id'].",";
            }

            //            // 优惠券码 重复的处理
            for($i=1;$i< $gift_coupon_arr["{$v['coupon_num']}"];$i++){
                array_splice($coupon_list,$k,0,array($v));
            }
        }

        $suppliers_list = array();
        if($coupon_suppliers!=""){
            $coupon_suppliers = rtrim($coupon_suppliers,",");
            $suppliers_mod = M("suppliers");
            $suppliers_list = $suppliers_mod->where("suppliers_id in($coupon_suppliers)")->field("suppliers_id,shop_name")->select();
            $suppliers_list = secToOne($suppliers_list,"suppliers_id","shop_name");
        }



        $user_coupon_arr = array();
        foreach ($coupon_list as $v) {

            if($v['suppliers_id']==0){
                $msg = "全平台使用";
            }else{
                $msg = $suppliers_list["{$v['suppliers_id']}"] ? $suppliers_list["{$v['suppliers_id']}"]."专用":"指定供应商使用";
            }

            $user_coupon_arr[] = array(
                "id"                => intval($v['id']),
                "title"             => $v['name'],
                "is_valid"          => $v["use_end_time"] >= $now? 1:0,
                "status"            => 1,
                "start_time"        => intval($v['use_start_time']),
                "end_time"          => intval($v['use_end_time']),
                "validation_amount" =>  free_format($v['rule_free']),
                "using_range"       => "订单满". price_format($v['rule_money'])."元可用",
                "msg"               => $msg,
            );
        }
        //=====================大礼包内的优惠券页面========================
        $share_url = C("SITE_URL")."/web.php?m=Share&a=gift_share&gifts_id=$gifts_id";

        $gift_data1 = array(
            "id"        => intval($gift_data['id']),
            "title"     => $gift_data['title'],
            "desc"      => nl2br($gift_data['detail']),
            "image"     => image($gift_data['image_src']),
            "status"    => $is_get? 1:0,
            "contentUrl" => $share_url,
            "items"     => $user_coupon_arr,
            "action"    => array(
                "type"  => 1,
                "info"  => "1"
            )
        );
        $this->successView("",$gift_data1);
    }
    
 /**
     +----------------------------------------------------------
     * 领取优惠券 : 3004修改自[[[2907]]]
     * 0:领取成功,1：已领取, 2：优惠券被抢光了 3：发放时间结束了 4:优惠券还没开始发放
     +----------------------------------------------------------
     */
    public function gets()
    {
        // 	$discoupon_id = intval($this->getField('discoupon_id','',true));// 优惠券id
        $type = isset($_REQUEST['type']) ? intval($this->getFieldDefault("type", 1)) : 1;   // 约束上面参数的意义。0 表示优惠券id 1 表示优惠券集合id
        //$type = isset($type)?$type:1;
        //$gifts_id =isset($_REQUEST['gifts_id'])?I('get.gifts_id','','trim,intval'):14;//之前优惠券礼包Id为14
        $gifts_id = isset($_REQUEST['gifts_id']) ? I('get.gifts_id', '', 'trim,intval') : 18;
        //$gifts_id= isset($gifts_id)?$gifts_id:8;
        if ($this->uid <= 0) {
            $this->errorView($this->ws_code['login_out_error'], $this->msg['login_out_error']);
        }
//        //再次判断用户是否购买过东西，如果买过则提示优惠券已经被领完了
        if ($gifts_id == 18) {
            $o = M();
            $sql = "select order_id from ls_order where user_id = '{$this->uid}' and pay_sn <> '' and pay_time <> 0 limit 1 ";
            $r = $o->query($sql);
            if ($r) {
                $data['status'] = 2;
                $this->successView("", $data);
                die;
            }
     }
    	if($type == 1){
    		$this->getcoups($gifts_id);
    		die;
    	}

    }

    /**
    +----------------------------------------------------------
     * //领取优惠券大礼包
     * 0:领取成功,1：已领取, 2：优惠券被抢光了 3：发放时间结束了 4:优惠券还没开始发放
    +----------------------------------------------------------
     */

    public function getcoups($id){
        $coupon_user_mod = M("coupon_user");
        $cou_gift_rel_mod = M("cou_gift_relation");
        $gift_user_mod = M("gift_user");
        $now = time();
        $coupon_list = $cou_gift_rel_mod->where("gift_id = $id")->field("num,coupon_num")->select();
        if(empty($coupon_list)){
            $this->errorView($this->ws_code["gifts_not_exist"],$this->msg['gifts_not_exist']);
        }
        $is_get = $gift_user_mod->where("gift_id=$id and user_id = $this->uid")->find();
//        p($gift_user_mod->getLastSql());
        if(!empty($is_get)){
            // 有记录说明已领取
            $data['status'] = 1;
            $this->successView("",$data);
        }
//开始判断领取礼包的状态

        foreach ($coupon_list as$k => $v) {
            $coupon_mod = M("coupon");
            $coupon_data = $coupon_mod->where("coupon_num = '$v[coupon_num]'")->field('id,coupon_num,online_time,get_start_time,get_end_time,use_start_time,status,surplus_num,send_num,use_end_time,type')->find();// 优惠券数据
            if (empty($coupon_data)) {
                $this->errorView($this->ws_code['coupon_id_not_exist'], $this->msg['coupon_id_not_exist']);
            }
            // 优惠券还没开始发放 4
            if ($coupon_data['get_start_time'] > $now) {
                $data['status'] = 4;
                $this->successView("", $data);
            }
            // 发放时间结束了 3
            if ($coupon_data['type'] != 2 && $coupon_data['get_end_time'] < $now) {
                $data['status'] = 3;
                $this->successView("", $data);
            }
            // 优惠券被抢光了 2
            if ($coupon_data['surplus_num'] <= 0) {
                $data['status'] = 2;
                $this->successView("", $data);
            }
            //更新ls_coupon表中的surplus_num(剩余数量)和send_num(被领取的数量)
            $coupon_list[$k]['surplus_num'] = $coupon_data['surplus_num'] - $v['num'];
            $coupon_list[$k]['surplus_num'] = $coupon_list[$k]['surplus_num']<=0?0:$coupon_list[$k]['surplus_num'];//如果优惠券表中(ls_coupon)剩余数量小于0则等于0，否则等于正常减去后的值
            $coupon_list[$k]['send_num']    = $coupon_data['send_num']    + $v['num'];
            $coupon_list[$k]['id']          = $coupon_data['id'];

        }
        $model = M();
        $model->startTrans();//开启事务
        $sql = "INSERT INTO ls_coupon_user (user_id,coupon_num,send_time) VALUES ";
        foreach ($coupon_list as $v) {
            $tmp = str_repeat("($this->uid,'{$v['coupon_num']}',$now),",$v['num']);
            $sql .= $tmp;

        }
        //更新多条记录的多个字段
        $update_sql = "UPDATE  ls_coupon SET surplus_num= CASE id";
        foreach ($coupon_list as $k=>$v) {
            $update_sql .= sprintf(" WHEN %s THEN %d", $v['id'],$v['surplus_num']);
        }
        $update_sql .= " END,";
        $update_sql .= "send_num = CASE id";
        foreach ($coupon_list as $k=>$v){
            $update_sql .= sprintf(" WHEN %s THEN %d",$v['id'],$v['send_num']);
        }
        $update_sql .= " END WHERE id in (";
        foreach ($coupon_list as $k=>$v){
            $update_sql .= $v['id'].",";
        }
        $update_sql = substr_replace($update_sql,'',-1);
        $update_sql .= ")";

        //更新多条记录的多个字段end

        $sql = rtrim($sql,",");
        if($model->execute($sql) && $model->execute($update_sql)){

            // 下一步插入关系表
            $gift_data = array(
                "user_id"  => $this->uid,
                "gift_id"  => $id
            );
            if($gift_user_mod->add($gift_data)){
                $data['status'] = 0;
                $model->commit();// 事务提交
                $this->successView("",$data);
            }else{
                $model->rollback();
                $this->errorView($this->ws_code['gifts_not_exist'],$this->msg['gifts_not_exist']);
            }
        }else{
            $model->rollback();
            $this->errorView($this->ws_code["gifts_not_exist"],$this->msg['gifts_not_exist']);
        }

    }

    /**
     *用户输入优惠券码领取优惠券接口
     * @Param uid用户Id
     * @Param $coupnum优惠券编码
     * @return array  领取结果
     */
    public function getCouponnum(){
        $uid = $this->uid;
        $coupnum =  substr(htmlspecialchars($this->getField("coupnum"),ENT_QUOTES),0);
        $now = time();
//        print_r($coupnum);
//        echo '<hr />';
//        print_r($uid);
//        echo '<hr />';die;
        $coup_user = M("coupon_user");
        $coup = M("coupon");

        //先判断用户传入的优惠码是否在coupon_user中 如果存在则表示已经被人领过了 这里只允许 一人一个码   如果出现则返回 （此优惠码已被领过）
        $r_u = $coup_user->where("coupon_num=$coupnum and user_id = $uid")->find();

        //print_r($r_u);
        //不存在则接着查询 type是否为3 surplus_num 是否大于0
        if(empty($r_u)){
            $r_c = $coup->where("coupon_num=$coupnum and surplus_num > 0 and type = 3")->find();
            //print_r($coup->getLastSql());print_r($r_c);
            if(!empty($r_c)) {
                $coup_user->startTrans();//开启事务
                $data['user_id'] = $uid;
                $data['coupon_num'] = $coupnum;
                $data['send_time'] = $now;
                // 剩下的就是领取操作。考虑临界值 情况 。是否还有库存
                $coup->startTrans();// 开启事务
                // 插入关系
                $coupon_user_data = array(
                    "user_id" => $uid,
                    "coupon_num" => $coupnum,
                    "send_time" => $now
                );
                $d['status'] = 1;
                // 修改成功
                $result = $coup->execute("update ls_coupon set surplus_num=surplus_num-1,send_num = send_num+1 where coupon_num = {$coupnum} ");
                if (!$result) {
                    $coup->rollback();
                    $this->errorView($this->ws_code['coupon_get_error'], $this->msg['coupon_get_error']);
                } else {
                    // 添加成功
                    if (!$coup_user->add($coupon_user_data)) {
                        $coup->rollback();
                        $this->errorView($this->ws_code['coupon_get_error'], $this->msg['coupon_get_error']);
                    }
                }
                // 执行成功 提交事务
                $coup->commit();// 提交事务
                $this->successView("", $d);
            }else{
                //数量不够
                $this->errorView($this->ws_code['coupon_get_error'],$this->msg['coupon_get_error']);
            }
        }else{
            //出现过至少一次返回错误提示（此优惠码已被领过）
            $this->errorView($this->ws_code['coupon_get_error'],$this->msg['coupon_get_error']);
        }
    }

}