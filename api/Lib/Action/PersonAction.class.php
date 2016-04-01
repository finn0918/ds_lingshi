<?php
/**
 * 个人中心
 * @author FK(429240967@qq.com)
 *
 */

class PersonAction extends BaseAction {

	/**
	 +----------------------------------------------------------
	 * 个人中心订单分类接口 2704
	 *  0 未付款（客户端显示：待付款）
	 *	1 付款（客户端显示：待发货）
	 *	2 已发货（客户端显示：待收货）
	 *	3 交易成功（客户端显示：1、交易成功）
	 *  4 交易成功（客户端显示：1、交易成功）
	 *  5 售后处理中(客户端显示：售后处理中，后台显示：售后待审核)
	 *	6 售后处理中(客户端显示：售后处理中，后台显示：售后处理中)
	 *	7 交易关闭 (客户端显示：交易关闭) 后台为用户退款后显示此状态
	 *	8 结算完成（与供应商结算，客户端不显示）
	 +----------------------------------------------------------
	 */
	public function get() {
		//初始化返回数据
		$count = 0;
		$items = array();
		$pg_cur = intval($this->getFieldDefault('pg_cur',1));     //当前页数
        if($pg_cur==0){
            $pg_cur = 1;
        }
		$pg_size = intval($this->getFieldDefault('pg_size',20));  //每页展示数
		$pg_cur= $pg_size * ($pg_cur-1);
		$pg_cur = $pg_cur > 0 ? $pg_cur : 0;


		$items = array();  // 初始化数组
		$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
		
		$status_arr = array(
				'all' => '',                //全部订单
				'wait_pay' => ' and status=0',   //待付款
				'wait_send' => ' and status in(1,5) ',   //待发货
				'wait_receive' => ' and status in(2,5) ',   //待收货
				'wait_comment' => ' and status=3',  //待评价
		);
		$user_id = $this->uid;
		$orderModel = M('order');
        $spu_mod = M("spu");
        $refund_mod = M("refund");
        $suborder_coupon_mod = M("sub_order_coupon");
		$where = " is_show=1 and user_id=$user_id";
		$sub_where = " is_show=1 and  user_id=$user_id " .  $status_arr[$type];


        $spu_img_mod = M("spu_image");
		$subOrderModel = M('sub_order');
		
		$limit1 = '';  //未付款
		$limit2 = ''; //已付款
		if($type=='all') {  //全部订单
			//分页
			$count_unpay = $orderModel->where("status=0 and " . $where)->count();
			
			$limit1 = '';  //未付款
			$limit2 = ''; //已付款
			$level_count = ($pg_cur+1)*$pg_size - $count_unpay;
			$rate = ($pg_cur+1) - intval($count_unpay/20);
			if($count_unpay==0) {
				$limit2 = $pg_cur . ',' . $pg_size;
			} elseif($level_count>=0) { //按子单商家分
				if($count_unpay%20!=0 && intval($count_unpay/20)>=$pg_cur) {
					$limit1 = $pg_cur*$pg_size . ',' . abs($level_count-$pg_size);
				}
				
				if($rate>1) {
					$limit2 =  $level_count + ($rate-2)*$pg_size . ',' .$pg_size;
				} else {
					$limit2 =  0 . ',' .$level_count;
				}
				
			} else {  // 按主单分
				$limit1 = $pg_cur . ',' . $pg_size;
			}
			
			// 未付款按订单分
			if($limit1) {

				$data = $orderModel->where("status=0 and " . $where)->limit($limit1)->order('add_time desc')->select();
				foreach($data as $val) {
				
					// a+'order_sn'为总订单，b+'order_sn' 为子订单
					$item = array();
					$sub_data = $subOrderModel->field("spu_attr,order_sn,sub_order_sn,shipping_fee,sum_price,nums,price,spu_id,spu_name,suppliers_id,suppliers_name,status")->where("order_sn='" . $val['order_sn'] . "' and " . $sub_where)->select();
					$name = '零食小喵';
					$item['order_sn'] = 'a' . $val['order_sn'];
					$item['type'] = intval($val['status']);
					$spu_ids = '';
					$suppliers = array();
					foreach ($sub_data as $key=>$v) {
						// 计算各个供应商最贵的运费
						$suppliers["{$v['suppliers_id']}"]['freight'] = $suppliers["{$v['suppliers_id']}"]['shipping_fee']>$v['shipping_fee']?$suppliers["{$v['suppliers_id']}"]['shipping_fee']:$v['shipping_fee'];
						$item['final_sum'] += $v['sum_price'];
						$item['num'] += intval($v['nums']);
						$spu_ids .= $v['spu_id'].',';
						$suppliers_name = $v['suppliers_name'];
					}
					if(count($suppliers)==1) { //一个供应商显示供应商名
						$name = $suppliers_name;
					}
					$item['name'] = $name;
					// 单独计算每个供应商运费
					foreach ($suppliers as $v_sup) {
						$item['final_sum']  += $v_sup['freight'];
						$item['freight']    += $v_sup['freight'];
					}

                    // 计算扣除优惠券的总价
                    $coupon_data = array();
                    $coupon_data = $suborder_coupon_mod->where("order_sn = '{$val['order_sn']}'")->field("rule_free")->find();

                    // 如果不为空 则说明该订单有使用优惠券
                    if(!empty($coupon_data)){
                        $item['final_sum'] -= $coupon_data['rule_free'];
                    }
					$active_amount = 0;
					// 查询其他优惠
					$suborder_active_mod = M("sub_order_active");
					$active_amount = $suborder_active_mod->where("order_sn = '{$val['order_sn']}' ")->getField("free_amount");

					if(!empty($active_amount)){
						$item['final_sum'] -= $active_amount;
					}

                    $item['final_sum'] = price_format($item['final_sum']);
                    // 计算扣除优惠券的总价

					$item['freight'] = price_format($item['freight']);

					if($item) {
						$list_format = $this->listFormat($spu_ids,$sub_data,$spu_img_mod,$spu_mod);
						$item['single'] = $list_format['single'];
						$item['multi']  = $list_format['multi'];
						if(substr($item['order_sn'],0,1)=='a'){
							$sum_price_in_list = $this->getSumPriceInListByOrder(substr($item['order_sn'],1));
						}else if(substr($item['order_sn'],0,1)=='b'){
							$sum_price_in_list = $this->getSumPriceInListBySubOrder(substr($item['order_sn'],1));
						}
						if($sum_price_in_list>=9900) {
							$item['final_sum'] -= 50;
						}
						$items[] = $item;
					}
			
				}
			}
			//已付款按供应商分


			if($limit2) {
				$sub_data = $subOrderModel->group('order_sn,suppliers_id')->field("spu_attr,order_sn,sub_order_sn,shipping_fee,sum_price,nums,price,spu_id,spu_name,suppliers_id,suppliers_name,max(status) as status")->limit($pg_cur . ',' . $pg_size)->order('add_time desc')->where("status<>0 and " .  $sub_where)->select();

	            $suppliers = array();
	            $big_order = array();

//                echo $subOrderModel->getLastSql();
	            foreach ($sub_data as $v) {

                    $key = $v['suppliers_id'];
	                $a_order_sn = $v['order_sn'];
	                $big_order[$a_order_sn][$key]['a_order_sn'] = $v['order_sn'];
	                $big_order[$a_order_sn][$key]['type'] = intval($v['status']);
	                $big_order[$a_order_sn][$key]['order_sn'] = 'b' . $v['sub_order_sn'];
	                $big_order[$a_order_sn][$key]['freight'] = $suppliers[$key]['freight']>$v['shipping_fee']? $suppliers[$key]['freight'] : $v['shipping_fee'];
	                $sub_order_data = $subOrderModel->field("spu_attr,order_sn,sub_order_sn,shipping_fee,sum_price,nums,price,spu_id,spu_name,suppliers_id,suppliers_name,status")->where("order_sn='$a_order_sn' and suppliers_id='$key' and" .$sub_where )->select();
	
	                foreach ($sub_order_data as $sv) {
	                	$big_order[$a_order_sn][$key]['sub_order'][] = $sv;
	                	$big_order[$a_order_sn][$key]['spu_ids'] .= $sv['spu_id'].',';
	                	$big_order[$a_order_sn][$key]['num'] += intval($sv['nums']);
	                	$big_order[$a_order_sn][$key]['final_sum'] += $sv['sum_price'];
	                }
	                //$big_order[$a_order_sn][$key]['sub_order'][] = $v;
	                $big_order[$a_order_sn][$key]['name'] = $v['suppliers_name'];
	                
	            }


	            // 拼接 single multi
	            foreach($big_order as $v_big){
	                foreach ($v_big as $key=>$v_sup) {
	                    $list_format = $this->listFormat($v_sup['spu_ids'],$v_sup['sub_order'],$spu_img_mod,$spu_mod);

	                    $v_sup['final_sum'] += $v_sup['freight'];

                        $v_sup['final_sum'] = $this->finalSum($v_sup['final_sum'],array($key),$v_sup['a_order_sn'],$subOrderModel,$suborder_coupon_mod);
						$sum_price_in_list = $this->getSumPriceInListByOrder($a_order_sn);  //取得订单中在列表商品的价格总和
						if($sum_price_in_list>=9900){
							$v_sup['final_sum'] -= 5000;
						}
                        $v_sup['final_sum'] = price_format($v_sup['final_sum']);
	                    $v_sup['freight']   = price_format($v_sup['freight']);
	                    $v_sup['single'] = $list_format['single'];
	                    $v_sup['multi']  = $list_format['multi'];

                        unset($v_sup['spu_ids']);
                        unset($v_sup['sub_order']);
                        unset($v_sup['a_order_sn']);

						if(substr($v_sup['order_sn'],0,1)=='a'){
							$sum_price_in_list = $this->getSumPriceInListByOrder(substr($v_sup['order_sn'],1));
						}else if(substr($v_sup['order_sn'],0,1)=='b'){
							$sum_price_in_list = $this->getSumPriceInListBySubOrder(substr($v_sup['order_sn'],1));
						}
						if($sum_price_in_list>=9900) {
							$v_sup['final_sum'] -= 50;
						}


	                    $items[] = $v_sup;
	                }



	            }

			}
				
		} elseif ($type=='wait_pay') {  //待付款
			$data = $orderModel->where("status=0 and " . $where)->limit($pg_cur . ',' . $pg_size)->order('add_time desc')->select();
			foreach($data as $val) {
				// a+'order_sn'为总订单，b+'order_sn' 为子订单
                $item = array();
            	$sub_data = $subOrderModel->field("spu_attr,order_sn,sub_order_sn,shipping_fee,sum_price,nums,price,spu_id,spu_name,suppliers_id,suppliers_name,status")->where("order_sn='" . $val['order_sn'] . "' and " . $sub_where)->select();
            	$name = '零食小喵';
                $item['order_sn'] = 'a' . $val['order_sn'];
                $item['type'] = intval($val['status']);
               
                $spu_ids = '';
                $suppliers = array();
                foreach ($sub_data as $key=>$v) {
                    // 计算各个供应商最贵的运费
                    $suppliers["{$v['suppliers_id']}"]['freight'] = $suppliers["{$v['suppliers_id']}"]['shipping_fee']>$v['shipping_fee']?$suppliers["{$v['suppliers_id']}"]['shipping_fee']:$v['shipping_fee'];
                    $item['final_sum'] += $v['sum_price'];
                    $item['num'] += intval($v['nums']);
                    $spu_ids .= $v['spu_id'].',';
                    $suppliers_name = $v['suppliers_name'];
                }

                // 单独计算每个供应商运费
                if(count($suppliers)==1) { //一个供应商显示供应商名
                	$name = $suppliers_name;
                }
                $item['name'] = $name;
                foreach ($suppliers as $v_sup) {
                    $item['final_sum'] += $v_sup['freight'];
                    $item['freight']    += $v_sup['freight'];
                }


                $item['final_sum'] = $this->finalSum($item['final_sum'],array_keys($suppliers),$val['order_sn'],$subOrderModel,$suborder_coupon_mod,true);
                $item['final_sum'] = price_format($item['final_sum']);
                $item['freight'] = price_format($item['freight']);

                if($item) {
                    $list_format = $this->listFormat($spu_ids,$sub_data,$spu_img_mod,$spu_mod);
                    $item['single'] = $list_format['single'];
                    $item['multi']  = $list_format['multi'];
					if(substr($item['order_sn'],0,1)=='a'){
						$sum_price_in_list = $this->getSumPriceInListByOrder(substr($item['order_sn'],1));
					}else if(substr($item['order_sn'],0,1)=='b'){
						$sum_price_in_list = $this->getSumPriceInListBySubOrder(substr($item['order_sn'],1));
					}
					if($sum_price_in_list>=9900) {
						$item['final_sum'] -= 50;
					}
                    $items[] = $item;
                }
			}
			
		} else { // 其他的商家订单分开
			$sub_data = $subOrderModel->group('order_sn,suppliers_id')->field("spu_attr,order_sn,sub_order_sn,shipping_fee,sum_price,nums,price,spu_id,spu_name,suppliers_id,suppliers_name,status")->limit($pg_cur . ',' . $pg_size)->order('add_time desc')->where( $sub_where)->select();
            $suppliers = array();
            $big_order = array();
            foreach ($sub_data as $v) {

                // 判断有没有进入售后的单子，并查看 是否跟当前的状态一致
                if($type=="wait_send"||$type=="wait_receive"){

                    $sub_status = $refund_mod->where("sub_order_sn = '{$v['sub_order_sn']}' and status in(0,1)")->order("add_time desc")->getField("order_status");

                    if(!empty($sub_status)){
                        if($type == "wait_send" && $sub_status!=1){
                            continue;// 说明该订单 原先不属于 待发货状态
                        }
                        if($type == "wait_receive" && $sub_status!=2){
                            continue;// 说明该订单 原先不属于 待收货状态
                        }
                    }
                }

                $key = $v['suppliers_id'];
                $a_order_sn = $v['order_sn'];
                $big_order[$a_order_sn][$key]['a_order_sn'] = $v['order_sn'];
                $big_order[$a_order_sn][$key]['type'] = intval($v['status']);
                $big_order[$a_order_sn][$key]['order_sn'] = 'b' . $v['sub_order_sn'];
                $big_order[$a_order_sn][$key]['freight'] = $suppliers[$key]['freight']>$v['shipping_fee']? $suppliers[$key]['freight'] : $v['shipping_fee'];
				$sub_order_data = $subOrderModel->field("spu_attr,order_sn,sub_order_sn,shipping_fee,sum_price,nums,price,spu_id,spu_name,suppliers_id,suppliers_name,status")->where("order_sn='$a_order_sn' and suppliers_id='$key' and" .$sub_where )->select();

                foreach ($sub_order_data as $sv) {
                	$big_order[$a_order_sn][$key]['sub_order'][] = $sv;
                	$big_order[$a_order_sn][$key]['spu_ids'] .= $sv['spu_id'].',';
                	$big_order[$a_order_sn][$key]['num'] += intval($sv['nums']);
                	$big_order[$a_order_sn][$key]['final_sum'] += $sv['sum_price'];
                }
                //$big_order[$a_order_sn][$key]['sub_order'][] = $v;
                $big_order[$a_order_sn][$key]['name'] = $v['suppliers_name'];
                
            }


            // 拼接 single multi
            foreach($big_order as $v_big){
                foreach ($v_big as $key=>$v_sup) {
                    $list_format = $this->listFormat($v_sup['spu_ids'],$v_sup['sub_order'],$spu_img_mod,$spu_mod);

                    $v_sup['final_sum'] += $v_sup['freight'];

                    $v_sup['final_sum'] = $this->finalSum($v_sup['final_sum'],array($key),$v_sup['a_order_sn'],$subOrderModel,$suborder_coupon_mod);
                    $v_sup['final_sum'] = price_format($v_sup['final_sum']);
                    $v_sup['freight']   = price_format($v_sup['freight']);
                    $v_sup['single'] = $list_format['single'];
                    $v_sup['multi']  = $list_format['multi'];

                    unset($v_sup['spu_ids']);
                    unset($v_sup['sub_order']);
                    unset($v_sup['a_order_sn']);

					if(substr($v_sup['order_sn'],0,1)=='a'){
						$sum_price_in_list = $this->getSumPriceInListByOrder(substr($v_sup['order_sn'],1));
					}else if(substr($v_sup['order_sn'],0,1)=='b'){
						$sum_price_in_list = $this->getSumPriceInListBySubOrder(substr($v_sup['order_sn'],1));
					}
					if($sum_price_in_list>=9900) {
						$v_sup['final_sum'] -= 50;
					}
                    $items[] = $v_sup;
                }
            }
			
		}
		
		if(empty($items)) {
			$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
		}
		$this->successView($items);
	}
	/**
	+----------------------------------------------------------
	 *  用户申请退款接口 2713
	+----------------------------------------------------------
	 */
	public function refund(){
		// 申请退款接口

		if($this->uid<=0){
			$this->errorView($this->ws_code['no_privilege'],$this->msg['no_privilege']);
		}

		$order_sn = htmlspecialchars($this->getField("order_sn"),ENT_QUOTES);// 订单号
		$sign = substr($order_sn, 0, 1);
		$order_sn = substr($order_sn,1);// 将bE1111,aE1111 拆解成E1111


		$suborder_mod = M("sub_order");
		$status = 0;
		if($sign =='a'){

			$sub_order_data = $suborder_mod->where("order_sn = '{$order_sn}' ")->getField("sub_order_sn,status");
			if(!$sub_order_data){
				$this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);//
			}
			$sub_order_list = array_keys($sub_order_data);
			$sub_order_data['status'] = current($sub_order_data);
			$sub_order_data['order_sn'] = $order_sn;

		}else{
			$sub_order_data = $suborder_mod->where("sub_order_sn = '$order_sn'")->field("order_sn,suppliers_id,status")->find();
			// 找不到这个单子了。
			if(!$sub_order_data){
				$this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);//
			}

			// 获得到所有子单数据
			$sub_order_list = $suborder_mod->where("order_sn = '{$sub_order_data['order_sn']}' and suppliers_id={$sub_order_data['suppliers_id']}")->getField("sub_order_sn",true);
		}

		// 未付款与交易关闭的单子将不能进入审核流程 1付款完成 2 已发货 5 售后处理中 6售后处理中
		if(!in_array($sub_order_data['status'],array(1,2))){
			$this->errorView($this->ws_code['no_privilege'],$this->msg['no_privilege']);
		}


		// 判断是否已经申请退款
		$refund_mod = M("refund");
		// 如果该订单已进入审核状态，贼回绝


		$time = time();
		$refund_mod->startTrans();
		foreach ($sub_order_list as $list_v) {
			$data[] = array(
				"order_sn" 		=> $sub_order_data['order_sn'],
				"sub_order_sn"	=> $list_v,
				"reason"		=> "暂无原因",
				"type"			=> 1,
				"add_time"		=> $time,
				"status"	    => 0,
				"order_status"	=> $sub_order_data['status'],
			);
		}

		if($refund_mod->addAll($data)){
			// 修改整个组合单状态
			if($sign=='a'){
				$is_ok = $suborder_mod->where("order_sn = '{$sub_order_data['order_sn']}'")->setField("status",5);
			}else{
				// 修改该供应商下的单子的状态为5
				$is_ok = $suborder_mod->where("order_sn = '{$sub_order_data['order_sn']}' and suppliers_id={$sub_order_data['suppliers_id']}")->setField("status",5);
			}
/********************************************作废优惠券判断开始**********start*******************************************************/
			$order_mod = M("order");

			$orders = $order_mod->where("order_sn = '{$sub_order_data['order_sn']}'")->field("user_id,order_id")->find();

			$user_id = $orders['user_id'];
			$order_id = $orders['order_id'];
			$coupon_user_mod = M("coupon_user");
			$is_use = array('is_use'=>'1');
			$re=$coupon_user_mod->where("user_id = '{$user_id}' and order_id = '{$order_id}'")->save($is_use);

			if (!$re) {
				Log::write("用户uid=>{$user_id}的单号为{$order_id}的优惠券作废失败", Log::ERR);
			}
			
/*******************************************作废优惠券判断结束*************end*******************************************************/			

			if(!$is_ok){
				$refund_mod->rollback();

				$this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);//
			}else{
				$refund_mod->commit();
				$this->successView("");
			}
		}else{

			$refund_mod->rollback();
			$this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);//
		}
	}
    /**
	 +----------------------------------------------------------
	 *  获取 单图 多图模式下的信息
	 +----------------------------------------------------------
	 */
    public function listFormat($spu_ids,$sub_data,$spu_img_mod,$spu_mod){
    	
        $spu_ids = trim($spu_ids,',');
        $spu_id_arr = explode(',', $spu_ids);
        foreach($spu_id_arr as $spu_id) { 
        	$spu_img_list[] = $spu_img_mod->where("spu_id=$spu_id and type =1 ")->field("spu_id,images_src")->find();
        }
       
       // $spu_img_list = $spu_img_mod->where("spu_id in($spu_ids) and type =1 ")->field("spu_id,images_src")->select();
       //$spu_img_list = secToOne($spu_img_list,"spu_id","images_src");

        // 判断是单图 还是多图
        if(count($spu_img_list)>1){
            foreach ($spu_img_list as $img_v) {
                $item['multi'][] = image($img_v['images_src']);
            }
            // 单图放空
            $item['single'] = new stdClass();
        }else{
            $item['multi']  = array();
            $spu_attr = unserialize($sub_data[0]['spu_attr']);
            $kinds = '';
            if(isset($spu_attr['attr_name'])){
                $kinds = $spu_attr['attr_name'] .":". $spu_attr['attr_value'];
            }else{
                foreach ($spu_attr as $v_attr) {
                    $kinds .= $v_attr['attr_name'] .":". $v_attr['attr_value'];
                }
            }
            $price_old = $spu_mod->where("spu_id = {$spu_ids}")->getField("price_old");
            $item['single'] = array(
                "id"          => 0,
                "goods_id"    => intval($spu_ids),// 只有一个商品
                "goods_title" => $sub_data[0]['spu_name'],
                "state"       => 0,
                "num"         => intval($sub_data[0]['nums']),
                "surplus_num" => 0,
                "kinds"       => $kinds,
                "price"       => array(
                    "current"  => price_format($sub_data[0]['price']),
                    "prime"    => price_format($price_old)
                ),
                "img"           => image($spu_img_list[0]['images_src'])
            );

        }

        return $item;
    }
	/**
	 +----------------------------------------------------------
	 * 订单详情 2701
	 +----------------------------------------------------------
	 */

    public function detail() {
		$order_sn = isset($_GET['order_sn']) ? trim($_GET['order_sn']) : 0;
		$origin_order_sn = $order_sn;
		$sign = substr($order_sn, 0, 1);
		$order_sn = substr($order_sn,1);
		
		$subOrderModel = M('sub_order');
		$orderModel = M('order');
		$model = M();
        $suppliers_id = 0; // 供应商初始值
		if ($sign=='a') { // 主订单
			$data = $orderModel->where("order_sn='$order_sn' and user_id = {$this->uid}")->find();
            if(empty($data)){
                // 找不到该用户的订单
                $this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);
            }
            // 说明该订单不是一个交易关闭的订单，需要剔除交易关闭的订单
            if($data['status']!=7){
                $sub_order_data = $subOrderModel->where("order_sn='$order_sn' and user_id = {$this->uid} and status <>7")->select();
            }else{
                $sub_order_data = $subOrderModel->where("order_sn='$order_sn' and user_id = {$this->uid}")->select();
            }

            if(empty($sub_order_data)){
                $this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);
            }

			if($sub_order_data[0]['status'] != $data['status']){
				$item['type'] = intval($sub_order_data[0]['status']);
			}else{
				$item['type'] = intval($data['status']);
			}


			$main_order_sn = $order_sn;
			$sub_order_sn = $sub_order_data[0]['sub_order_sn'];
		} else if($sign=='b') {
            // 获取子单信息
			$sub_data = $subOrderModel->where("sub_order_sn='$order_sn' and user_id = {$this->uid} ")->find();
            if(empty($sub_data)){
                $this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);
            }
			$data = $orderModel->where("order_sn='".$sub_data['order_sn']."' and user_id = {$this->uid}")->find();// 主单信息

            if($sub_data['status'] == 7){
                $sub_order_data = $subOrderModel->where("order_sn='".$sub_data['order_sn']."' and user_id = {$this->uid} and suppliers_id = {$sub_data['suppliers_id']} and status = 7")->select();//通过主单获得子单
            }else{
                $sub_order_data = $subOrderModel->where("order_sn='".$sub_data['order_sn']."' and user_id = {$this->uid} and suppliers_id = {$sub_data['suppliers_id']} and status <> 7")->select();//通过主单获得子单
            }
			// 获取当前状态 中的最高级别的状态
			$max_status = $sub_order_data[0]['status'];
			foreach ($sub_order_data as $status_v) {
				$max_status =  $status_v['status'] > $max_status ? $status_v['status']:$max_status ;
			}

			$item['type'] = intval($max_status);
			$main_order_sn = $sub_data['order_sn'];
			$sub_order_sn = $order_sn;
            $suppliers_id = $sub_data['suppliers_id'];
		} else {
			$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
		}
		// 收货地址
		if($data['consignee']) {
			$item['address'] = array(
					"id" => 0,               //地址id
					"name" => $data['consignee'],                // 收货人姓名
					"phone"=> $data['mobile'],       // 收货人联系电话
					"province" => $data['province'],             //省
					"city" => $data['city'],            //市
					"proper"  => $data['proper'],              //区
					"full_add"  => $data['address'],                   //完整地址
					"type" => intval($data['type'])
			);
		}
		$final_sum = 0;
        $sum_goods = 0;
		$sum_freight = 0;
        $spu_img_mod = M('spu_image');
        $ids = arrayToStr($sub_order_data,"spu_id");
        $spu_img_list = $spu_img_mod->where("spu_id in ($ids) and type=1")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");// 使用spu_id 做键名
		//订单具体购物列表
        $suppliers = array();
        foreach($sub_order_data as $val) {
            // 商品的spu信息
            $kinds = '';

			if($val['spu_attr']) {
				$spu_attr = unserialize($val['spu_attr']);

                // 兼容多层以及单层规格
                if(isset($spu_attr['attr_name'])){

                    $kinds .= $spu_attr['attr_name'] .':'. $spu_attr['attr_value'];


                }else{
                    foreach($spu_attr as $k=>$v) {
                        $kinds .= $v['attr_name'] .':'.  $v['attr_value'];
                    }
                }
			}

            $suppliers["{$val['suppliers_id']}"]['id'] = intval($val['suppliers_id']);
            $suppliers["{$val['suppliers_id']}"]['name'] = $val['suppliers_name'];
            $suppliers["{$val['suppliers_id']}"]['num'] += $val['nums'];
            $suppliers["{$val['suppliers_id']}"]['sum_price'] += $val['sum_price'];
            $suppliers["{$val['suppliers_id']}"]['note'] = $val['postscript'];
      
            // 以供应商为单位聚合数据
            $suppliers["{$val['suppliers_id']}"]['items'][] = array(
					'id' => 0,
					'goods_id' => intval($val['spu_id']),
					'goods_title' =>$val['spu_name'],
					'state' => 0,
					'num' => intval($val['nums']),
					'surplus_num' => 0,
					'kinds' => empty($kinds) ? '' : $kinds,
					'price' => array('current'=>price_format($val['price']), 'prime'=>price_format($val['price'])),
					'img' => image($spu_img_list["{$val['spu_id']}"]),
			);
            $suppliers["{$val['suppliers_id']}"]['freight'] = $suppliers["{$val['suppliers_id']}"]['freight']>$val['shipping_fee']?$suppliers["{$val['suppliers_id']}"]['freight']:$val['shipping_fee'];
        }
        foreach ($suppliers as $k=>$v) {
            $suppliers_freight             = $v['freight'];
            $suppliers[$k]['sum_price']    = price_format($v['sum_price']+$v['freight']);
            $suppliers_sum_price           = $v['sum_price']+$v['freight'];
            $sum_goods                    += $v['sum_price'];
            $suppliers[$k]['freight']      = price_format($suppliers_freight);
            $sum_freight                  +=$suppliers_freight;
            $final_sum                    +=$suppliers_sum_price;
            $item['cart_suppliers'][]      = $suppliers[$k];
        }
        //包括订单编号、成交时间等，每个String含标题和值，如“成交时间：2015-06-07 20:28:01”
		$item['infos'] = array("订单编号:$main_order_sn");
		if($sub_order_data[0]['add_time']) {
			$str = "创建时间:".date('Y-m-d H:i:s', $sub_order_data[0]['add_time']);
			array_push($item['infos'], $str);
       	}
       	
       	if($sub_order_data[0]['pay_time']) {
			$str = "付款时间:".date('Y-m-d H:i:s', $sub_order_data[0]['pay_time']);
			array_push($item['infos'], $str);
       	}
       	if($sub_order_data[0]['send_time']) {
       		$str = "发货时间:".date('Y-m-d H:i:s', $sub_order_data[0]['send_time']);
       		array_push($item['infos'], $str);
       	}
       	if($sub_order_data[0]['finish_time']) {
       		$str = "成交时间:".date('Y-m-d H:i:s', $sub_order_data[0]['finish_time']);
       		array_push($item['infos'], $str);
       	}
		
		//最新物流信息描述
		if($sign=='b') {
			if($sub_order_data[0]['shipping_com'] && $sub_order_data[0]['shipping_id']) {
				$shipping_data = A('Express')->getExpressInfo($sub_order_data[0]['shipping_com'],$sub_order_data[0]['shipping_id']);
				if($num = count($shipping_data['result']['list'])) {
					$item['logistics'] = array(
							'desc' => $shipping_data['result']['list'][$num-1]['remark'],
							'time' =>  $shipping_data['result']['list'][$num-1]['datetime'],
				
					);
				}
			} 
		} 
		$item['order_sn'] = $origin_order_sn;  //子订单号

        $item['sum_goods'] = price_format($sum_goods);
		$item['sum_freight'] = price_format($sum_freight);
        // 判断该订单是否有优惠券
        $suborder_coupon_mod = M("sub_order_coupon");
        $coupon_data = $suborder_coupon_mod->where("order_sn = '{$main_order_sn}'")->field("rule_free,suppliers_id")->find();// 找到优惠券的使用记录
        if(empty($coupon_data)){
            $item['discoupon_num'] = 0;
            $item['validation_amount'] = 0;
        }else{
            // 有使用优惠券
            if($coupon_data['suppliers_id'] == 0){
                $item['discoupon_num'] = 1;
                if($sign == "a"){
                    $item['validation_amount'] = free_format($coupon_data['rule_free']);
                }else{
                    $sub_order_count = $subOrderModel->where("order_sn='{$main_order_sn}' and user_id = {$this->uid}")->count('DISTINCT suppliers_id');
                    $item['validation_amount'] = free_format(floor($coupon_data['rule_free']/$sub_order_count));
                }
            }else{
                // 指定某个供应商的话直接显示数量
                if($sign == "a"){
                    $item['discoupon_num'] = 1;
                    $item['validation_amount'] = free_format($coupon_data['rule_free']);
                }else{
                    // 供应商id
                    if($suppliers_id == $coupon_data['suppliers_id']){
                        $item['discoupon_num'] = 1;
                        $item['validation_amount'] = free_format($coupon_data['rule_free']);
                    }else{
                        $item['discoupon_num'] = 0;
                        $item['validation_amount'] = 0;
                    }
                }
            }
        }
		$suborder_active_mod = M("sub_order_active");
		$active_amount = 0;
		$free_amount   = $suborder_active_mod->where("order_sn = '{$main_order_sn}' ")->getField("free_amount");

		// mark 目前是只有一个供应商可以这样直接计算
		if(!empty($free_amount)){
			$active_amount = $free_amount;
		}
		$final_sum = $final_sum - $active_amount;
		//
		$item['discount_amount'] = price_format($active_amount);
		if($sign=='a'){
			$sum_price_in_list = $this->getSumPriceInListByOrder($order_sn);//获取订单中的商品处于活动列表中的价格的和
		}else if($sign=='b'){
			$sum_price_in_list = $this->getSumPriceInListBySubOrder($order_sn);//获取订单中的商品处于活动列表中的价格的和
		}
		if($sum_price_in_list>=9900){
			$final_sum -= 5000;
			$item['discount_amount'] += 50;
		}
        $final_sum = price_format($final_sum);
        $item['final_sum'] = price_format($final_sum*100 - $item['validation_amount']*100);
		if(empty($item)) {
			$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
		}
		$this->successView('', $item);
		
	}
    /**
    +----------------------------------------------------------
     * 计算订单列表页，订单总价方法
    +----------------------------------------------------------
     */
    public function finalSum($final_sum,$suppliers_id_arr,$order_sn,$sub_order_mod,$suborder_coupon_mod,$status=false){
// 扣除优惠券

        $coupon_data = array();
        $coupon_data = $suborder_coupon_mod->where("order_sn = '{$order_sn}'")->field("suppliers_id,rule_free")->find();
        // 如果不为空 则说明该订单有使用优惠券
        $validation_amount = 0;
		$active_amount = 0;
//      echo $suborder_coupon_mod->getLastSql();
        if(!empty($coupon_data)){
            if($coupon_data['suppliers_id'] == 0){
                if(!$status){
                    $sub_order_count = $sub_order_mod->where("order_sn='{$order_sn}' and user_id = {$this->uid} ")->count('DISTINCT suppliers_id');
                    $validation_amount = floor($coupon_data['rule_free']/$sub_order_count);
                }else{
                    $validation_amount = $coupon_data['rule_free'];
                }
            }elseif(in_array($coupon_data['suppliers_id'],$suppliers_id_arr) ){
                $validation_amount = $coupon_data['rule_free'];
            }
        }
		// 查询其他优惠
		$suborder_active_mod = M("sub_order_active");
		$active_amount = 0;// 默认优惠金额为0
		$free_amount = $suborder_active_mod->where("order_sn = '{$order_sn}' ")->getField("free_amount");
		if(!empty($free_amount)){
			$active_amount = $free_amount;
		}
        return $final_sum - $validation_amount - $active_amount;
    }
	
	/**
	 +----------------------------------------------------------
	 * 取消/删除/确认收货 2705
	 +----------------------------------------------------------
	 */
	function put() {
		$order_sn = isset($_GET['order_sn']) ? htmlspecialchars(trim($_GET['order_sn'])) : 0; 
		$opt = isset($_GET['opt']) ? intval($_GET['opt']) : 0;  //操作类型：0；确认收货；1，取消；2，删除；
		$sign = substr($order_sn, 0, 1);  //字母 a 为主订单  b为子订单
		$order_sn = substr($order_sn,1);
		$user_id = $this->uid;

		if($order_sn) {
			$orderModel = M('order');
			$subOrderModel = M('sub_order');
            $model = M();
            $model->startTrans();
			switch ($opt) {
				case 0:  //确认收货
					$sub_data = $subOrderModel->where("user_id=$user_id and sub_order_sn='$order_sn'")->field('status,order_sn,suppliers_id')->find();
					$data['status'] = 3;
					$data['finish_time'] = time();
					$real_status = $sub_data['status'];
					$suppliers_id = $sub_data['suppliers_id'];
					$main_order_sn = $sub_data['order_sn'];
					if( $real_status > $data['status'] ) {
						$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
					}

                    // 一般不会有这种情况。只是防止恶意提交确认收货操作
                    if($real_status<=1){
                        $this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
                    }

					if($subOrderModel->where("user_id=$user_id and suppliers_id=$suppliers_id and order_sn='$main_order_sn'")->save($data)!==false) {
						$model->commit();
                        $this->successView();
					} else {
                        $model->rollback();
						$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
					}
					break;
				case 1:  //取消
					$real_status = $orderModel->where("user_id=$user_id and order_sn='$order_sn'")->getField('status');

                    if($real_status == 0) {
						$data['status'] = 7;
						$this->backOrder($order_sn);  //回滚
						if($orderModel->where("user_id=$user_id and  order_sn='$order_sn'")->save($data)!==false) {
							if($subOrderModel->where("user_id = $user_id and order_sn = '$order_sn'")->save($data)!==false){
                                $model->commit();
                                $this->successView();
                            }else{
                                $model->rollback();
                                $this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
                            }
						} else {
							$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
						}
						
					} else { 
						$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
					}
					break;
				case 2: //删除
					$data['is_show'] = 0;
					if($sign=='a') {
                        if($orderModel->where("user_id=$user_id and  order_sn='$order_sn'")->save($data)!==false) {
                            if($subOrderModel->where("user_id=$user_id and  order_sn='$order_sn'")->save($data)!==false) {
                                $model->commit();
                            }else {
                                $model->rollback();
                                $this->errorView($this->ws_code['internal_error'], $this->msg['internal_error']);
                            }
                        }
					} elseif($sign=='b') {
                        // 修复 删除订单 同一个供应商下的单子不能同步删除 nangua 2015-08-09 16:36
                        $sub_data = $subOrderModel->where("user_id=$user_id and sub_order_sn='$order_sn'")->field('status,order_sn,suppliers_id')->find();
                        // end
                        $suppliers_id = $sub_data['suppliers_id'];
                        $main_order_sn = $sub_data['order_sn'];
						if($subOrderModel->where("user_id=$user_id and suppliers_id=$suppliers_id and order_sn='$main_order_sn'")->save($data)!==false){
                            $model->commit();
                        }else{
                            $model->rollback();
                            $this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
                        }
					} else {
						$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
					}
					$this->successView();
					break;
				default:
					$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
			}
			
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 评论接口 2706
	 +----------------------------------------------------------
	 */
	function comment() {

        $comments  = json_decode($this->getFieldDefault("comments",''));//备注的json串，见备注
        $comments  = object_to_array($comments);// json 对象转数组

        $opt     = intval($this->getField("opt"));
        $opt++;// 0 表示 淘宝用户 1 普通用户 2 普通用户匿名
		$user_id = $this->uid;

		if($comments) {
			//敏感词过滤
			$redis_conf = C('REDIS_CONF');
			$redis_obj = Cache::getInstance('Redis', $redis_conf);
			$sensitive_word = $redis_obj->get('sensitive_word');
			$sensitive_word = unserialize($sensitive_word);
			foreach($sensitive_word as $val) {
                foreach ($comments as $v) {
                    if(strpos($v['content'], $val) !== false) {
                        $this->errorView($this->ws_code['sensitive_word'], $this->msg['sensitive_word']);
                    }
                }
			}
			$model = M();
            $model->startTrans();// 开启事务
			
			$subOrderModel = M('sub_order');

			$clientModel = M('client');
			$commentModel = M('comment');
			$user_data = $clientModel->field('nickname,avatar')->where('user_id=' . $user_id)->find();
            $order_sn_ids = '';
            foreach ($comments as $v) {
                $order_sn_ids .= '"'.$v['order_sn']. '",';
            }
            foreach ($comments as $v) {
                $order_sn_ids .= '"'.$v['order_sn']. '",';
            }
            $order_sn_ids = trim($order_sn_ids,',');
            $sub_order_list = $subOrderModel->where("sub_order_sn in($order_sn_ids) and user_id = $user_id")->field("sub_id,spu_id,spu_attr,sub_order_sn")->select();
           

            if(empty($sub_order_list)){
                $this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);
            }

            $sub_order_list = arrayFormat($sub_order_list,'sub_order_sn');

            foreach($comments as $v){
                $data['spu_id'] = $sub_order_list["{$v['order_sn']}"]['spu_id'];
                $data['spu_attr'] = $sub_order_list["{$v['order_sn']}"]['spu_attr'];
                $data['comment'] = $v['content'];
                $data['sub_order_sn'] =  $v['order_sn'];
                $data['add_time'] = time();
                $data['avatar_src'] = empty($user_data['avatar']) ? '' : $user_data['avatar'];
                $data['nickname'] = empty($user_data['nickname']) ? '' : $user_data['nickname'];
                $data['user_id'] = $user_id;
                $data['type'] = $opt;
                $sub_id = $sub_order_list["{$v['order_sn']}"]['sub_id'];
                if($commentModel->add($data)) {
                    $update_data = array(
                        "status" => 4
                    );
                    if($subOrderModel->where("sub_id = $sub_id ")->save($update_data)!==false){
                    	
                    }else{
                        $model->rollback();
                        $this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
                    }
                } else {
                    $model->rollback();
                    $this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
                }
            }
            $model->commit();
            $this->successView();
		} else {
			$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单商品列表 2709
	 +----------------------------------------------------------
	 */
	function getOrderSpu() {
		$order_sn = isset($_GET['order_sn']) ? htmlspecialchars(trim($_GET['order_sn'])) : '';
		$user_id = $this->uid;
		$sign = substr($order_sn, 0, 1);  //字母 a 为主订单  b为子订单
		$order_sn = substr($order_sn,1);
		$subOrderModel = M('sub_order');
		$orderModel = M('order');
		$model = M();
		if ($sign=='a') { // 主订单
			//$data = $orderModel->where("order_sn='$order_sn'")->find();
			$sub_order_data = $subOrderModel->where("user_id=$user_id and order_sn='$order_sn'")->select();
		} else if($sign=='b') {
			$sub_data = $subOrderModel->where("sub_order_sn='$order_sn'")->find();
			//$data = $orderModel->where("user_id=$user_id and order_sn='".$sub_data['order_sn']."'")->find();
			$sub_order_data = $subOrderModel->where("user_id=$user_id and order_sn='".$sub_data['order_sn']."' and suppliers_id= '".$sub_data['suppliers_id']."'")->select();
		} else {
			$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
		}

        $spu_img_mod = M('spu_image');
        $ids = arrayToStr($sub_order_data,"spu_id");
        $spu_img_list = $spu_img_mod->where("spu_id in ($ids) and type=1")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");// 使用spu_id 做键名
        //订单具体购物列表
        $cartItem = array();

        foreach($sub_order_data as $val) {
            // 商品的spu信息
            $kinds = '';
            if($val['spu_attr']) {
                $spu_attr = unserialize($val['spu_attr']);
                // 兼容多层以及单层规格
                if(isset($spu_attr['attr_name'])){
                    $kinds .= $spu_attr['attr_name'] .':'. $spu_attr['attr_value'];
                }else{
                    foreach($spu_attr as $k=>$v) {
                        $kinds .= $v['attr_name'] .':'.  $v['attr_value'];
                    }
                }
            }

            // 以供应商为单位聚合数据
            $cartItem[] = array(
                'id' => 0,
                'goods_id' => intval($val['spu_id']),
                'goods_title' =>$val['spu_name'],
                'state' => 0,
                'num' => intval($val['nums']),
                'surplus_num' => 0,
                'kinds' => empty($kinds) ? '' : $kinds,
                'price' => array('current'=>price_format($val['price']), 'prime'=>price_format($val['price'])),
                'img' => image($spu_img_list["{$val['spu_id']}"]),
                "order_sn"=> $val['sub_order_sn']
            );
        }
        if(empty($cartItem)) {
			$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
		}
		$this->successView($cartItem);
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取购物车、订单相关红点信息 2607
	 +----------------------------------------------------------
	 */
	public function getRedDotInfo() {
		//获取方式：0，全部红点数量；1，只获取购物车数量；2.只获取订单相关数量
		$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
		$user_id = $this->uid;
		$cid = $this->cid;


		$orderModel = M('order');
		$subOrderModel = M('sub_order');
        $refund_mod = M("refund");
		if($user_id>0) {
			$cartModel = M('cart');
			//$cart_num = $cartModel->where('user_id=' . $user_id)->count();  //按sku个数展示
			$cart_num = $cartModel->where('user_id=' . $user_id)->sum('num'); //按商品个数展示

		} elseif($cid) {
			$cartDirverModel = M('cart_driver');
			//$cart_num = $cartDirverModel->where('cid=' . $cid)->count(); //按sku个数展示
			$cart_num = $cartDirverModel->where('cid=' . $cid)->sum('num'); //按商品个数展示
		} else {
			$cart_num = 0;
		}


		if($user_id) {
			switch ($type)
			{
				case 0:
				  $cart_num = intval($cart_num);
				  $wpay_num = $orderModel->where('is_show=1 and status=0 and user_id=' . $user_id)->count();
				  $wsend_num = count($subOrderModel->where('is_show=1 and status=1 and user_id=' . $user_id)->group("order_sn,suppliers_id")->select());
				  $wreceive_num =  count($subOrderModel->where('is_show=1 and status=2 and user_id=' . $user_id)->group("order_sn,suppliers_id")->select());
                  $wcomment_num = count($subOrderModel->where('is_show=1 and status=3  and user_id=' . $user_id)->group("order_sn,suppliers_id")->select());

				  break;
				case 1:
				  $cart_num = intval($cart_num);
				  break;
				case 2:
				  $wpay_num = $orderModel->where('is_show=1 and status=0 and user_id=' . $user_id)->count();
				  $wsend_num = count($subOrderModel->where('is_show=1 and status=1 and user_id=' . $user_id)->group("order_sn,suppliers_id")->select());
				  $wreceive_num =  count($subOrderModel->where('is_show=1 and status=2 and user_id=' . $user_id)->group("order_sn,suppliers_id")->select());
				  $wcomment_num = count($subOrderModel->where('is_show=1 and status=3  and user_id=' . $user_id)->group("order_sn,suppliers_id")->select());
				  break;
				default:
				$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
			
			}
		} else {
			$wpay_num = 0;
			$wsend_num = 0;
			$wreceive_num = 0;
			$wcomment_num = 0;
		} 
		$data = array(
				'cart_num' => intval($cart_num),         //购物车内商品数
				'wpay_num' => intval($wpay_num),         //等待付款订单数
				'wsend_num' => intval($wsend_num),       //待发货订单数
				'wreceive_num' => intval($wreceive_num), //待收货订单数
				'wcomment_num' => intval($wcomment_num), //待评论订单数
				
		);
		$this->successView('', $data);
	}
    /**
    +----------------------------------------------------------
     * 计算点数
    +----------------------------------------------------------
     */
    public function countDot($sub_data){
        $num = 0;
        foreach ($sub_data as $v) {
            $key = $v['suppliers_id'];
            $a_order_sn = $v['order_sn'];
            if(!isset($big_order[$a_order_sn][$key])){
                $big_order[$a_order_sn][$key] = $v['suppliers_id'];
                $num ++;
            }
        }
        return $num;
    }
	/**
	 +----------------------------------------------------------
	 * 获取订单信息
	 +----------------------------------------------------------
	 */
	public function getSubOrder($data) {
			
			$item['order_sn'] = $data['order_sn'];
			
			$item['type'] = $data['type'];
			if($data['status'] == 0) {
				$item['name'] = '零食小喵';
			} else {
				$item['name'] = $data['name'];
			}

			$item['multi'] = array();
			$item['kinds'] = $data['kinds'];
			$item['num'] = intval($data['num']);
			$item['spu_ids'] = substr($data['spu_ids'], 0, -1) ;
			
			$spu_image = $this->getSpuImage($item);
			
			
			$item['num'] = 0;
            $item['final_sum'] = 0;
            $item['freight']  = 0;
			if(count($spu_image['single'])) {
				foreach($spu_image as $key=>$val) {
					$item['final_sum'] += $val['price']['current'];
					$item['freight'] += $val['shipping_fee'];
					unset($spu_image[$key]['shipping_fee']);
					$item['num'] += $val['num'];
				}
			} else {
				$spu_image['single'] = new stdClass();  //输出空对象
				unset($spu_image['spu_ids']);
				unset($spu_image['kinds']);
			}

			unset($item['kinds']);
			unset($item['spu_ids']);
            unset($spu_image['spu_ids']);
            unset($spu_image['kinds']);

			return array_merge($item, $spu_image);
			
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取当个商品或多个商品订单的显示信息
	 +----------------------------------------------------------
	 */
	public function getSpuImage($item) {
	
		$item['multi'] = array();
		$item['single'] = array();
		$imgModel =  M('spu_image');
		$shippingModel =  M('shipping_price');
		$spu_ids = $item['spu_ids'];
		$num = $item['num'];
		$spu_id_arr = explode(',', $spu_ids);
		foreach($spu_id_arr as $spu_id) {
			$img_data[]['images_src'] = $imgModel->where("type=1 and spu_id=$spu_id")->getField('images_src');
		}
		
		if(count($img_data) > 1) {
			foreach($img_data as $val) {
				$item['multi'][] = image($val['images_src']);
			}
			if($item['kinds']) {
				$kinds = unserialize($item['kinds']);
				unset($item['kinds']);
				foreach($kinds as $k=>$v) {
					$item["kinds"] .= $v['attr_name'] . ':' . $v['attr_value'];
				}
			} else {
				$item["kinds"] = "";
			}
			
			return $item;
		} else {
			$ids = $spu_ids;
			$where = "s.spu_id in ($ids)";
			$sql = "select s.*,si.images_src from ls_spu as s left join ls_spu_image as si" .
					" on s.spu_id=si.spu_id where $where";
			
			$data = M()->query($sql);
			foreach($data as $val) {
				$goods['id'] = 0;
				$goods["goods_id"] = intval($val['spu_id']);
				$goods["goods_title"] = $val['spu_name'];
				$goods["state"] =  intval($val['status']);
				// 特殊处理
				if($goods['kinds']) {
					$kinds = unserialize($goods['kinds']);
					foreach($kinds as $k=>$v) {
						$goods["kinds"] .= $v['attr_name'] . $v['attr_value'];
					}
				} else {
					$goods["kinds"] = '';
				}
				$spu_data = spu_format($val['spu_id']);
				$goods["price"] = array('current'=>price_format($spu_data['price']), 'prime'=>price_format($spu_data['price_old']));
				if(shipping_free==0) {
					$shipping_fee = $shippingModel->where('spu_id=' . $val['spu_id'])->getField('price');
					$goods['shipping_fee'] =intval($shipping_fee);
				} else {
					$goods['shipping_fee'] = 0;
				}
				$goods['surplus_num'] = intval($spu_data['stocks']);
				$goods["img"] = image($val['images_src']);
				$goods["num"] = intval($num);
				$goodses['single']= $goods;
				
			}
			return $goodses;
		}
		
	}
    /**
    +----------------------------------------------------------
     * 修改用户头像 2710
    +----------------------------------------------------------
     */
    public function modifyNickname(){
        // 获取客户端端发过来的昵称
        if($this->uid<=0){
            $this->errorView($this->ws_code['login_out_error'],$this->msg['login_out_error']);
        }
        $nickname = htmlspecialchars(strip_tags($this->getField('nickname')), ENT_QUOTES);// 头像昵称
        $client_mod = M("client");
        if($client_mod->where("user_id = {$this->uid}")->setField("nickname",$nickname)!==false){
            $this->successView();
        }else{
            $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
        }
    }
    /**
    +----------------------------------------------------------
     * 修改用户头像 2711
    +----------------------------------------------------------
     */
    public function modifyAvatar(){
        // 获取客户端发过来的头像url地址
        if($this->uid<=0){
            $this->errorView($this->ws_code['login_out_error'],$this->msg['login_out_error']);
        }
        $avatar_url = $this->getField('avatar_url');//头像地址
        if(!filter_var($avatar_url,FILTER_VALIDATE_URL)){
            $this->errorView($this->ws_code['parameter_error'],$this->msg['parameter_error']);
        }
        if(!get_magic_quotes_gpc()){
            $avatar_url = addslashes($avatar_url);
        }
        $client_mod = M("client");
        if($client_mod->where("user_id = {$this->uid}")->setField("avatar",$avatar_url)!==false){
            $this->successView();
        }else{
            $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
        }
    }
	/**
	 +----------------------------------------------------------
	 * 订单库存量与销量回滚
	 +----------------------------------------------------------
	 */
	public function backOrder ($order_sn) {
        $subOrderMod = M('sub_order');
        $extendMod = M('extend_special');
        $skuMod = M('sku_list');
        $spuMod = M("spu");// by nangua 2015-08-26 15:53
	    $subWhere = array();
        $subWhere['order_sn'] = $order_sn;
        $subResult = $subOrderMod->where($subWhere)->field('sku_id,spu_id,nums,price')->select();//得到sku_id和对应的数量
       
		foreach($subResult as $v){
			$map = array();
            $map_modify = array();
			$map['sku_id'] = $v['sku_id'];//先去扩展表里面看活动有没有过期
			$map['price'] = $v['price'];
			$extendResult = $extendMod->where($map)->find();

			if($extendResult){//在活动表
				$extendFlagInc = $extendMod->where($map)->setInc('sku_stocks',$v['nums']);//库存加
                // nangua 2015-09-01 14:45
                $map_modify['sku_id'] = array("eq",$v['sku_id']);
                $map_modify['price']  = array("eq",$v['price']);
                $map_modify['sku_sale'] = array("egt",$v['nums']);

                $extendFlagDec = $extendMod->where($map_modify)->setDec('sku_sale',$v['nums']);//销量减
                if(!$extendFlagDec){
                    $extendMod->where($map)->setField("sku_sale",0);
                }
			}else{//不在活动表
				$skuWhere = array();
				$skuWhere['sku_id'] = $v['sku_id'];
				$skuFlagInc = $skuMod->where($skuWhere)->setInc('sku_stocks',$v['nums']);//库存加

                $map_modify['sku_id'] = array("eq",$v['sku_id']);
                $map_modify['sku_sale'] = array("egt",$v['nums']);

                $skuFlagDec = $skuMod->where($map_modify)->setDec('sku_sale',$v['nums']);//销量减
                if(!$skuFlagDec){
                    $extendMod->where($map)->setField("sku_sale",0);
                }
            }
            $spuMod->where("spu_id = {$v['spu_id']} and status=1")->setField("status",0);
		}
        
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

	/**
	 * 获取订单里的商品中在活动列表里的商品总价，单位为分，根据子订单号
	 */
	private function  getSumPriceInListBySubOrder($subordersn){
		$sum_price_in_list = 0;
		$relationMod = M('topic_relation');  //商品与活动的关系
		$OrderModel = M('order');
		$subOrderModel = M('sub_order');
		$subOrder = $subOrderModel->where("sub_order_sn = '{$subordersn}'")->find();
		$ordersn = $subOrder['order_sn'];

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