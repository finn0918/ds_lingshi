<?php
/**
 * 物流信息
 * @author feibo
 *
 */
class ExpressAction extends BaseAction {
	
	/**
	 +----------------------------------------------------------
	 * 获取物流详情 2707
	 +----------------------------------------------------------
	 */
	function get() {
		$order_sn = isset($_GET['order_sn']) ? trim($_GET['order_sn']) : '';
		if($order_sn) {
			$sign = substr($order_sn, 0, 1);  //字母 a 为主订单  b为子订单
			$order_sn = substr($order_sn,1);
			if ($sign=='b') { // 子订单
			
				$subOrderModel = M('sub_order');
				$field_data = $subOrderModel->where("sub_order_sn='$order_sn' and user_id = {$this->uid}")->field('order_sn, suppliers_id')->find();
				// 如果为空，提示无此订单
                if(empty($field_data)){
                    $this->errorView($this->ws_code['order_not_exist'],$this->msg['order_not_exist']);
                }
				$main_order_sn = $field_data['order_sn'];
				$suppliers_id  = $field_data['suppliers_id'];

				$sub_order_data = $subOrderModel->where("order_sn='$main_order_sn' and suppliers_id ='$suppliers_id'")->select();
                $sub_data = $sub_order_data[0];

                // 获得缩略图 start
                $spu_img_mod = M('spu_image');
                $ids = arrayToStr($sub_order_data,"spu_id");
                $spu_img_list = $spu_img_mod->where("spu_id in ($ids) and type=1")->field("spu_id,images_src")->select();
                $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");// 使用spu_id 做键名
                // 获得缩略图 end

                //订单具体购物列表
                $suppliers = array();
                $final_sum = 0;
                $sum_freight = 0;

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
                    $suppliers['id'] = intval($val['suppliers_id']);
                    $suppliers['name'] = $val['suppliers_name'];
                    $suppliers['num'] += $val['nums'];
                    $suppliers['sum_price'] += $val['sum_price'];
                    $suppliers['note'] = $val['postscript'];

                    // 以供应商为单位聚合数据
                    $suppliers['items'][] = array(
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
                    $suppliers['freight'] = $suppliers['freight']>$val['shipping_fee']?$suppliers['freight']:$val['shipping_fee'];
                }

                $suppliers['sum_price'] = price_format($suppliers['sum_price']+$suppliers['freight']);
                $suppliers['freight']   = price_format($suppliers['freight']);
				$logistics = array();
				if($sub_data['shipping_com'] && $sub_data['shipping_id']) {
					$shipping_data = $this->getExpressInfo($sub_data['shipping_com'],$sub_data['shipping_id']);
					if($shipping_data['result']['list']) {
						foreach($shipping_data['result']['list'] as $val) {
							$logistics[] = array(
									'desc' => $val['remark'],
									'time' => $val['datetime']
					
							);
						}
					}
				} 
				
				$shipping_info = C('SHIPPING_INFO');
                // 因为同一个供应商的都是一个物流，所以只取第一个子单的信息作为物流信息
				$name =  array_search($sub_data['shipping_com'], C('SHIPPING_COM'));
				$com =  $sub_data['shipping_com'];
				$logo = $shipping_info['logo'][$com];
				//$img_info_arr = getimagesize($logo);
				$express = array(
					'name' => $name?$name:'',
					'logo' => array(  
							"img_url" => $logo?$logo:'', // 图片链接
						    "img_w"=>70,  // 图片宽
						    "img_h"=>70   // 图片高
						    ),
					'number' => $sub_data['shipping_id']?$sub_data['shipping_id']:"",
					'detail' => array_reverse($logistics)
				);
				if(empty($logistics)) {
					unset($express['detail']);
				}

				$data = array(
						'cart_suppliers' => $suppliers,
						'express' => $express
						);
				if(empty($data)) {
					$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
				}
				$this->successView('', $data);
			} else {
					$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取物流数据
	 +----------------------------------------------------------
	 */
	public function getExpressInfo($shipping_com, $shipping_id) {
		
		$shipping_info = C('SHIPPING_INFO');
		$url = $shipping_info['url'];
		$post_data = array (
				"key"   => $shipping_info['appkey'],    //Appkey
				"com"   => $shipping_com,   	 //快递公司编号
				"no"    => $shipping_id,         //运单号
				"dtype" => 'json'                //返回数据格式(Json,XML)
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$output = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($output, TRUE);
		
		return $data;
		
	}
}