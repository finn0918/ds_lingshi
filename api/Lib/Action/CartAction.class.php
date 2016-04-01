<?php
/**
 * 购物车列表
 * @author nangua(410966126)
 *
 */

class CartAction extends BaseAction{
    /**
    +----------------------------------------------------------
     * 获取购物车列表  2604
    +----------------------------------------------------------
     */
    public function index(){
        $since_id = intval($this->getFieldDefault('since_id',0));// 备用
        $pg_cur = intval($this->getFieldDefault('pg_cur',1));//当前页数
        $pg_size = isset($_GET['pg_size'])?intval($_GET['pg_size']):100;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;
        // 未登录
        // 判断是否有登录
        if($this->uid<=0){
            $where = " cid = {$this->cid} ";
            $cart_mod = M("cart_driver");
        }else{
            $where = " user_id = {$this->uid} ";
            $cart_mod = M("cart");
        }
        $cart_list = $cart_mod->where($where)->order("id asc")->limit($start,$pg_size)->select();

        if(empty($cart_list)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }
        $ids = arrayToStr($cart_list,"spu_id");
        // 获取商品图片
        $spu_img_mod = M('spu_image');
        $spu_img_list = $spu_img_mod->where("spu_id in ($ids) and type=1")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");// 使用spu_id 做键名

        $cart_item = array();
        $spu_mod = M("spu");
        $off_spu_id_arr =$spu_mod->where("spu_id in($ids) and status >=2")->getField("spu_id",true);

        foreach ($cart_list as $v) {
            // 判断
            if(in_array($v['spu_id'],$off_spu_id_arr)){

                // 2.1 版本以前仍采用删除下架商品
                if($this->version<12){
                    $cart_mod->where("id = {$v['id']}")->delete();//
                }
                continue;//跳过下架商品
            }

            $supplier_id = $v['suppliers_id'];
            $spu_attrs =  unserialize($v['spu_attr']);// 兼容后续多规格 因此存序列号结构
            $kinds = '';
            // 如果是单层则直接读取
            if(isset($spu_attrs['attr_name'])){
                $kinds = $spu_attrs['attr_name']." : ".$spu_attrs['attr_value'];
            }else{
                foreach($spu_attrs as $value ){
                    $kinds    =  $value['attr_name']." : ".$value['attr_value'];
                }
            }
            //-------------判断商品是否失效----------------------

            $cart_state = cart_spu_status($v['spu_id'],$v['sku_id'],$v['price_now']);// 商品的 spu_id
//            p($cart_state);
            //2.1版本 2 就是 售空状态。 1 价格变动。
            if(isset($cart_state['state'])&&$cart_state['state']==2 && $this->version>11){
                continue;// 跳过该条继续循环 售空的商品跳过
            }
            // 如果是价格不一致 更新购物车中的商品价格
            if($cart_state['state'] == 1){
                $cart_mod->where("id = {$v['id']}")->setField("price_now",$cart_state['sku_price']);
                $v['price_now'] = $cart_state['sku_price'] ;
                $state = 0;
            }else{
                $state = $cart_state['state'];
            }

            $price_now  = price_format($v['price_now']);
            //-------------判断商品是否失效----------------------
            $cart_item[$supplier_id][] = array(
                    "id"            => intval($v['id']),
                    "goods_id"      => intval($v['spu_id']),
                    "goods_title"   => $v['spu_name'],
                    "state"         => intval($state),// 需要判断
                    "num"           => intval($v['num']),
                    "kinds"         => $kinds,
                    "price"         => array(
                        "current"   => $price_now,
                        "prime"     => price_format($v['price_old'])
                    ),
                    "img"           => image($spu_img_list["{$v['spu_id']}"]),
                    "surplus_num"   => intval($cart_state['sku_stocks']) // 剩余库存
            );
            $cart_item[$supplier_id]['suppliers_name'] = $v['suppliers_name'];

            if($state==0){
                $cart_item[$supplier_id]['sum_price'] += $v['price_now']*$v['num'];
                if(!isset($cart_item[$supplier_id]['valid_num'])){
                    $cart_item[$supplier_id]['valid_num'] = 0;
                }
                $cart_item[$supplier_id]['valid_num'] +=$v['num'];
            }

        }

        // 归类到大条目

        $cart_suppliers = array();
        $suppliers_id_arr = array_keys($cart_item);
        $supplier_id_str = implode(',',$suppliers_id_arr);
        $free_fee_template_mod = M("free_fee_template");
        $free_template_result = array();// 记录 供应商的包邮政策
        $now = time();
        $free_title = "";
        $temp_free_result = array();
        // 先检查是否有平台包邮
        $plat_form_free = $free_fee_template_mod->where("type = 2 and status='published' and start_time<={$now} and end_time>={$now}")->order("price asc")->field("template_name")->find();

        if(!empty($plat_form_free)) {

            $free_title = $plat_form_free['template_name'];
        }else{
            $free_template_result = $free_fee_template_mod->where("suppliers_id in($supplier_id_str) and status='published' and start_time<={$now} and end_time>={$now} ")->field("suppliers_id,price,template_name")->select();

            foreach ($free_template_result as $free_v) {
                if(isset($temp_free_result["{$free_v['suppliers_id']}"])){
                    // 如果下一个模板价格更低 则采用该模板
                    if($temp_free_result["{$free_v['suppliers_id']}"]['price']>$free_v['price']){
                        $temp_free_result["{$free_v['suppliers_id']}"]['price'] = $free_v['price'];
                        $temp_free_result["{$free_v['suppliers_id']}"]['template_name'] = $free_v['template_name'];
                    }
                }else{
                    $temp_free_result["{$free_v['suppliers_id']}"]['price'] = $free_v['price'];
                    $temp_free_result["{$free_v['suppliers_id']}"]['template_name'] = $free_v['template_name'];
                }
            }
        }
        $action_type = $plat_form_free ? 1:0;
        foreach ($cart_item as $k => $v) {
            $suppliers_name = $v['suppliers_name'];
            $sum_price = $v['sum_price'];
            $valid_num = $v['valid_num'];
            unset($v['suppliers_name']);
            unset($v['sum_price']);
            unset($v['valid_num']);
            $temp_cart_suppliers = array();
            $temp_cart_suppliers = array(
                "id"        => intval($k),
                "name"      => $suppliers_name,
                "items"     => $v,
                "num"       => intval($valid_num),
                "sum_price" => price_format($sum_price),
                "freight"   => 0,
                "note"      => ""
            );
            if($this->version >= 11){
                // 平台包邮
                if(!empty($plat_form_free)) {

                    $temp_cart_suppliers['activity'] = array(
                        "icon_title" => $this->version ==11? "满包邮":"优惠",
                        "title"      => $free_title,
                        "action"     => array(
                            "type"   => 1,
                            "info"   => strval($k)
                        )
                    );
                }elseif(isset($temp_free_result[$k])){
                    $temp_cart_suppliers['activity'] = array(
                        "icon_title" => $this->version ==11? "满包邮":"优惠",
                        "title"     => $temp_free_result[$k]['template_name'],
                        "action"    => array(
                            "type"  => $this->version == 11 ? 0:1,// 2.0.1是跳供应商商品列表 约定是type 0
                            "info"  => strval($k)
                        )
                    );
                }else{
                    if($this->version >= 12){
                        // 说明获取供应商对应的优惠券
                        $coupon_mod = M("coupon");

                        $coupon_data = $coupon_mod->where("(suppliers_id = $k or suppliers_id = 0) and status='published'  and use_end_time>={$now} and type = 1")->order("rule_free desc,rule_money asc")->find();

                        if(!empty($coupon_data)){
                            $temp_cart_suppliers['activity'] = array(
                                "icon_title" => "优惠",
                                "title"     => $coupon_data['name'],
                                "action"    => array(
                                    "type"  => 1,
                                    "info"  => strval($k)
                                )
                            );

                        }else{
                            $temp_cart_suppliers['activity'] = new stdClass();
                        }
                    }else{
                        $temp_cart_suppliers['activity'] = new stdClass();
                    }


                }

            }

            $cart_suppliers[] = $temp_cart_suppliers;
        }
        // 返回购物车列表
        $this->successView($cart_suppliers);
    }

    /**
    +----------------------------------------------------------
     * 获取无效购物车列表  2608
    +----------------------------------------------------------
     */
    public function invalidIndex(){
        $since_id = intval($this->getFieldDefault('since_id',0));// 备用
        // 以下代码暂时不用
//        $pg_cur = intval($this->getFieldDefault('pg_cur',1));//当前页数
//        $pg_size = isset($_GET['pg_size'])?intval($_GET['pg_size']):100;//每页显示的条数
//        $start = ($pg_cur - 1)*$pg_size;
        // 判断是否有登录
        if($this->uid<=0){
            $where = " cid = {$this->cid} ";
            $cart_mod = M("cart_driver");
        }else{
            $where = " user_id = {$this->uid} ";
            $cart_mod = M("cart");
        }
        $cart_list = $cart_mod->where($where)->order("id asc")->select();
        if(empty($cart_list)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }
        $ids = arrayToStr($cart_list,"spu_id");

        // 获取商品图片
        $spu_img_mod = M('spu_image');
        $spu_img_list = $spu_img_mod->where("spu_id in ($ids) and type=1")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");// 使用spu_id 做键名

        $cart_item = array();
        $spu_mod = M("spu");
        $off_spu_id_arr =$spu_mod->where("spu_id in($ids) and status >=2")->getField("spu_id",true);
        // 购物车无效商品循环判断
        foreach ($cart_list as $v) {
            $supplier_id = $v['suppliers_id'];
            $spu_attrs = unserialize($v['spu_attr']);// 兼容后续多规格 因此存序列号结构
            $kinds = '';
            // 如果是单层则直接读取
            if (isset($spu_attrs['attr_name'])) {
                $kinds = $spu_attrs['attr_name'] . " : " . $spu_attrs['attr_value'];
            } else {
                foreach ($spu_attrs as $value) {
                    $kinds = $value['attr_name'] . " : " . $value['attr_value'];
                }
            }
            //-------------判断商品是否失效----------------------
            // 判断
            $cart_state = cart_spu_status($v['spu_id'], $v['sku_id'], $v['price_now']);// 商品的 spu_id


            //            p($cart_state);
            if (isset($cart_state['state']) && in_array($cart_state['state'],array(0,1))) {

                continue;// 跳过该条继续循环 只记录失效商品
            }



            // 将下架的跟售空的记录下来
            if(in_array($v['spu_id'],$off_spu_id_arr)){
                $state =  3;// 下架商品
            }else{
                $state = 2; // 售空商品
            }


            $price_now  = price_format($v['price_now']);
            $cart_item[] = array(
                "id"            => intval($v['id']),
                "goods_id"      => intval($v['spu_id']),
                "goods_title"   => $v['spu_name'],
                "state"         => intval($state),// 需要判断
                "num"           => intval($v['num']),
                "kinds"         => $kinds,
                "price"         => array(
                    "current"   => $price_now,
                    "prime"     => price_format($v['price_old'])
                ),
                "img"           => image($spu_img_list["{$v['spu_id']}"]),
                "surplus_num"   => intval($cart_state['sku_stocks']) // 剩余库存
            );
        }
        $this->successView($cart_item);
    }

    /**
    +----------------------------------------------------------
     * 加入购物车 2601
    +----------------------------------------------------------
     */
    public function add(){

        $goods_id   = intval($this->getField('goods_id'));// spu_id
        $kind_id    = intval($this->getField('kind_id'));
        $subkind_id = intval($this->getField('subkind_id'));//子种类id
        $num        = intval($this->getField('num','',true));// 不能传0

        $spu_attr_mod  = M("spu_attr");
        $attr_value = $spu_attr_mod->where("spu_attr_id = $subkind_id ")->getField("attr_value");
        // 2.0  只有  口味
        $spu_attr = serialize(array(
            array(
                "attr_name"     => "口味",
                "attr_value"    => $attr_value
            )
        ));
        // 判断是否有登录
        if($this->uid<=0){
            $uid = 0;
            $cid = $this->cid;
            $where = " cid = {$this->cid} ";
            $cart_mod = M("cart_driver");
            $merge_data = array("cid"=>$cid);
            $car_log_mod = M("cart_driver_log");// 设备行为日志
        }else{
            $uid = $this->uid;
            $cid = 0;
            $where = " user_id = {$this->uid} ";
            $cart_mod = M("cart");
            $merge_data = array("user_id"=>$uid);
            $car_log_mod = M("cart_log");// 用户行为日志
        }
        // 获取该商品的sku_id
        $sku_list_mod = M("sku_list");

        $sku_id = $sku_list_mod->where("spu_id = {$goods_id} and attr_combination ={$subkind_id} ")->getField("sku_id");

        if(empty($sku_id)){
            $this->errorView($this->ws_code['sku_id_error'],$this->msg['sku_id_error']);
        }
        $check = $cart_mod->where($where." and spu_id = {$goods_id} and sku_id = $sku_id ")->find();

        $total_cart_num = $cart_mod->where($where)->count();
        if($total_cart_num>=99){
            $this->errorView($this->ws_code['cart_num_max'],$this->msg['cart_num_max']);
        }

        if($check['id']){
            // 修复加入购物车商品数量 超过实际库存，以最大库存存入 nangua 2015-08-27
            $spu_extend =  spu_format($goods_id);
            $sku_stock_arr = $spu_extend['sku_stocks'];
            $sku_stock_arr = secToOne($sku_stock_arr,'sku_id','sku_stocks');
            $after_num = $check['num'] + $num;
            // 如果超过了 库存直接插入最大库存

            if($sku_stock_arr[$sku_id]<$after_num){
                $num = $sku_stock_arr[$sku_id];
                $cart_mod->where(" id = {$check['id']} ")->setField("num",$num);
                unset($check['id']);
                $car_log_mod->add($check);// 记录加入购物车的行为日志
                $this->successView();
            }
            // 修复加入购物车商品数量 超过实际库存，以最大库存存入 end
            if($cart_mod->where(" id = {$check['id']} ")->setInc("num",$num)){
                unset($check['id']);
                $car_log_mod->add($check);// 记录加入购物车的行为日志
                $this->successView();
            }else{
//                echo 1;
                $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
            }
        }else{
            // 查询商品的 信息
            $spu_mod = M("spu");
            // 查询 在卖的商品信息
            $spu_info = $spu_mod->where("spu_id = {$goods_id} and status=0 and guide_type=0")->field("guide_type,spu_name,price_old,price_now,suppliers_id")->find();

            if(empty($spu_info)){
                $this->errorView($this->ws_code['spu_id_error'],$this->msg['spu_id_error']);
            }
            $suppliers_mod = M("suppliers");
            $suppliers_name = $suppliers_mod->where("suppliers_id = {$spu_info['suppliers_id']}")->getField("suppliers_name");
            $price_old  = price_format($spu_info['price_old']);
            // 获取商品的sku_id
            $sku_list_mod = M("sku_list");
            $sku_info = $sku_list_mod->where("spu_id = {$goods_id} and attr_combination={$subkind_id}")->field("sku_id")->find();
            if(empty($sku_info)){
                $this->errorView($this->ws_code['sku_id_error'],$this->msg['sku_id_error']);
            }
            $spu_extend =  spu_format($goods_id);
            $price_now  =  $spu_extend['price'];
            $time = time();
            $agio = agio_format($price_now,$price_old);
            $sku_price = $spu_extend['sku_price'];
            $sku_price = secToOne($sku_price,'sku_id','price');
            $data = array(
                "sku_id"         => intval($sku_info['sku_id']),
                "spu_id"         => intval($goods_id),
                "spu_name"       => $spu_info['spu_name'],
                "num"            => intval($num),
                "price_now"      => $sku_price["{$sku_info['sku_id']}"],
                "price_old"      => $spu_info['price_old'],
                "discount_info"  => $agio,
                "spu_attr"       => $spu_attr,
                "rec_type"       => $spu_extend['type'],
                "create_time"    => $time,
                "update_time"    => $time,
                "suppliers_id"   => $spu_info['suppliers_id'],
                "suppliers_name" => $suppliers_name
            );
            $data = array_merge($data,$merge_data);

            if($cart_mod->add($data)){
                $car_log_mod->add($data);// 记录加入购物车的行为日志
                $this->successView();
            }else{
                $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 批量加入购物车(暂时用于任意购H5) 2609
    +----------------------------------------------------------
     */
    public function addMany(){
        $sku_list_mod = M("sku_list");
        $spu_mod = M("spu");
        $suppliers_mod = M("suppliers");
        $spu_attr_mod  = M("spu_attr");
        $appMod = M("app_config");
        // 判断是否有登录
        if($this->uid<=0){
            $uid = 0;
            $cid = $this->cid;
            $where = " cid = {$this->cid} ";
            $cart_mod = M("cart_driver");
            $merge_data = array("cid"=>$cid);
            $car_log_mod = M("cart_driver_log");// 设备行为日志
        }else{
            $uid = $this->uid;
            $cid = 0;
            $where = " user_id = {$this->uid} ";
            $cart_mod = M("cart");
            $merge_data = array("user_id"=>$uid);
            $car_log_mod = M("cart_log");// 用户行为日志
        }
        $addition = $this->postField('addition');
        $manySpu = json_decode($addition,true);
        if(!$manySpu){
            $this->errorView($this->ws_code['parameter_error'],$this->msg['parameter_error']);
        }
        //统计多少人点击了批量加入购物车
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //读取是false
        $Cache = Cache::getInstance('Redis',$options);
        $cachekey = "addManySpu";
        if(($cacheNum = $Cache->incr($cachekey))>10){
            if(!$appMod->where("config_name = 'car_num'")->setInc("config_value",$cacheNum)){
                $carData = array(
                    "config_name"=>"car_num",
                    "config_value"=>$cacheNum
                );
                $appMod->add($carData);
            }
            $Cache->set($cachekey,0);
        }
        //结束统计
        foreach($manySpu as $key=>$val){
            $goods_id = $val['goods_id'];//商品id
            $subkind_id = $val['subkind_id'];//子种类id
            $num = $val['num']>0?$val['num']:1;//数量
            $sku_id = $sku_list_mod->where("spu_id = {$goods_id} and attr_combination ={$subkind_id} ")->getField("sku_id");
            $check = $cart_mod->where($where." and spu_id = {$goods_id} and sku_id = $sku_id ")->find();
            if($check){//如果购物车已经有了这个商品
                // 修复加入购物车商品数量 超过实际库存，以最大库存存入
                $spu_extend =  spu_format($goods_id);
                $sku_stock_arr = $spu_extend['sku_stocks'];
                $sku_stock_arr = secToOne($sku_stock_arr,'sku_id','sku_stocks');
                $after_num = $check['num'] + $num;
                // 如果超过了库存直接插入最大库存
                if($sku_stock_arr[$sku_id]<$after_num){
                    $num = $sku_stock_arr[$sku_id];
                    $cart_mod->where(" id = {$check['id']} ")->setField("num",$num);
                    unset($check['id']);
                    $car_log_mod->add($check);// 记录加入购物车的行为日志
                }else{// 普通情况下直接增加商品数量
                    if($cart_mod->where(" id = {$check['id']} ")->setInc("num",$num)){
                        unset($check['id']);
                        $car_log_mod->add($check);// 记录加入购物车的行为日志
                    }else{
                        $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
                    }
                }
            }else{//购物车没有这个商品的信息
                // 查询 在卖的商品信息
                $spu_info = $spu_mod->where("spu_id = {$goods_id} and status=0 and guide_type=0")->field("guide_type,spu_name,price_old,price_now,suppliers_id")->find();
                if(empty($spu_info)){//商品不在上架状态，不把这个商品加入购物车
                    continue;
                }
                $suppliers_name = $suppliers_mod->where("suppliers_id = {$spu_info['suppliers_id']}")->getField("suppliers_name");
                $price_old  = price_format($spu_info['price_old']);
                $sku_info = $sku_list_mod->where("spu_id = {$goods_id} and attr_combination={$subkind_id}")->field("sku_id")->find();
                $spu_extend =  spu_format($goods_id);
                $price_now  =  $spu_extend['price'];
                $time = time();
                $agio = agio_format($price_now,$price_old);
                $sku_price = $spu_extend['sku_price'];
                $sku_price = secToOne($sku_price,'sku_id','price');
                $attr_value = $spu_attr_mod->where("spu_attr_id = $subkind_id ")->getField("attr_value");
                // 2.0  只有  口味
                $spu_attr = serialize(array(
                    array(
                        "attr_name"     => "口味",
                        "attr_value"    => $attr_value
                    )
                ));
                $data = array(
                    "sku_id"         => intval($sku_info['sku_id']),
                    "spu_id"         => intval($goods_id),
                    "spu_name"       => $spu_info['spu_name'],
                    "num"            => intval($num),
                    "price_now"      => $sku_price["{$sku_info['sku_id']}"],
                    "price_old"      => $spu_info['price_old'],
                    "discount_info"  => $agio,
                    "spu_attr"       => $spu_attr,
                    "rec_type"       => $spu_extend['type'],
                    "create_time"    => $time,
                    "update_time"    => $time,
                    "suppliers_id"   => $spu_info['suppliers_id'],
                    "suppliers_name" => $suppliers_name
                );
                $data = array_merge($data,$merge_data);
                if($cart_mod->add($data)){
                    $car_log_mod->add($data);// 记录加入购物车的行为日志
                }else{
                    $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
                }
            }
        }
        $this->successView();
        $json = '[{"goods_id":5089,"kind_id":12,"subkind_id":237,"num":1},{"goods_id":5090,"kind_id":12,"subkind_id":169,"num":1},{"goods_id":5076,"kind_id":12,"subkind_id":133,"num":1},{"goods_id":5070,"kind_id":12,"subkind_id":113,"num":1},{"goods_id":5083,"kind_id":12,"subkind_id":151,"num":1}]';
    }


    /**
    +----------------------------------------------------------
     * 修改购物车商品数量  2602
    +----------------------------------------------------------
     */
    public function edit(){
        $item_id    = intval($this->getField('item_id'));// 购物车小条目
        $num        = intval($this->getField('num','',true));// 不能传0
        // 判断是否有登录
        if($this->uid<=0){
            $where = " cid = {$this->cid} ";
            $cart_mod = M("cart_driver");
            $car_log_mod = M("cart_driver_log");// 设备行为日志
        }else{
            $where = " user_id = {$this->uid} ";
            $cart_mod = M("cart");
            $car_log_mod = M("cart_log");// 用户行为日志
        }
        // 需要对应用户或对应设备才能修改购物车数量
        $check_id = $cart_mod->where("id = {$item_id} and ".$where)->setField("num",$num);
        if($check_id){
            $data = $cart_mod->where("id = {$item_id}")->find();
            if(!empty($data)){
                unset($data['id']);
                $data['action_type'] = 2;//修改购物车
                $car_log_mod->add($data);
            }
            $this->successView();
        }elseif($check_id===0){
            // 数据没有变更。仍返回 1000
            $this->successView();
        }else{
            $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
        }
    }
    /**
    +----------------------------------------------------------
     * 从购物车删除  2603
    +----------------------------------------------------------
     */
    public function del(){
        $ids       = $this->getField('ids');// 不能传0
        // 判断是否有登录
        if($this->uid<=0){
            $where = " cid = {$this->cid} ";
            $cart_mod = M("cart_driver");
            $car_log_mod = M("cart_driver_log");// 设备行为日志
        }else{
            $where = " user_id = {$this->uid} ";
            $cart_mod = M("cart");
            $car_log_mod = M("cart_log");// 用户行为日志
        }
        $cart_list = $cart_mod->where("id in ($ids) and ".$where)->select();
        $result = $cart_mod->where("id in ($ids) and ".$where)->delete();
        if($result||$result===0){
            foreach ($cart_list as $v) {
                unset($v['id']);
                $v['action_type'] = 3;
                $car_log_mod->add($v);
            }
            $this->successView();
        }else{
            $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
        }
    }

}