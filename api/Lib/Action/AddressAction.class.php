<?php
/**
 * 收货地址
 * @author FK(429240967@qq.com)
 *
 */
class AddressAction extends BaseAction {

	/**
	 +----------------------------------------------------------
	 * 获取收货地址 2801
	 +----------------------------------------------------------
	 */
	function get() {
		//初始化返回数据
		$items = array();
		$addrModel = M('address');
		$where = 'user_id=' .$this->uid;
		$data = $addrModel->where($where)->order("type desc")->select();

		foreach($data as $val) {
			$item['id']   = intval($val['add_id']);
			$item['name'] = $val['consignee'];
			$item['phone'] = $val['phone'];
			$item['province'] = $val['province'];
			$item['city'] = $val['city'];
			$item['proper'] = $val['proper'];
			$item['full_add'] = $val['street'];
			$item['type'] = intval($val['type']);
			$items[] = $item;
		}
		if(empty($items)) {
			$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
		}
		$this->successView($items);
	}
	
	/**
	 +----------------------------------------------------------
	 * 新增或修改收货地址 2802
	 +----------------------------------------------------------
	 */
	function put() {
		
		$data['consignee'] = isset($_GET['name']) ? htmlspecialchars(trim($_GET['name'])) : '';
		$data['user_id'] = $this->uid;
		$data['phone'] = isset($_GET['phone']) ? htmlspecialchars(trim($_GET['phone'])) : '';
		$data['province'] = isset($_GET['province']) ? htmlspecialchars(trim($_GET['province'])) : '';
		$data['city'] = isset($_GET['city']) ? htmlspecialchars(trim($_GET['city'])) : '';
		$data['proper'] = isset($_GET['proper']) ? htmlspecialchars(trim($_GET['proper'])) : '';
		$data['street'] = isset($_GET['full_add']) ? htmlspecialchars(trim($_GET['full_add'])) : '';
		foreach($data as $key => $val) {

			if(empty($val)) {
				$this->errorView($this->ws_code['empty_error'], $this->msg['empty_error']);
			}
			// 屏蔽掉值为null的
			if(strstr($val,'null')){
				$this->errorView($this->ws_code['empty_error'], $this->msg['empty_error']);
			}
		}

		// type 无需判断
		$data['type'] = isset($_GET['type']) ? intval($_GET['type']) : 0;
		$add_id = isset($_GET['add_id']) ? intval($_GET['add_id']) : 0;
        $addrModel = M('address');

		if ($add_id == 0) { //新增地址
            // 如果当前用户还没有地址 新增地址自动变默认 nangua 20150721 11:24
            $exist_addr_count = $addrModel->where("user_id = {$this->uid}")->count();
            if($data['type']==0&&$exist_addr_count==0){
                $data['type'] = 1;// 设置地址为默认地址
            }
			if($add_id = $addrModel->add($data)) {

                if($exist_addr_count>=0&&$data['type']==1){
                    $is_ok = $addrModel->where("user_id = {$this->uid} and add_id<> {$add_id}")->save(array("type"=>0));
                    if($is_ok||$is_ok===0){
                        $data = array('id' => $add_id);
                        $this->successView('', $data);
                    }else{
                        $this->errorView($this->ws_code['internal_error'], $this->msg['internal_error']);
                    }
                }else{
                    $data = array('id' => intval($add_id));
                    $this->successView('', $data);
                }


			} else {
				$this->errorView($this->ws_code['internal_error'], $this->msg['internal_error']);
			}
		} else { //修改地址
			if($addrModel->where('add_id=' . $add_id)->save($data)!==false) {
				if($data['type'] == 1) {
					// 去除原先默认的地址
					$addrModel->where( "user_id = {$this->uid} and add_id <> {$add_id} ")->setField("type",0);
				}
				$data = array('id' => intval($add_id));
				$this->successView('', $data);
			} else {
				$this->errorView($this->ws_code['internal_error'], $this->msg['internal_error']);
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除收货地址、设置默认收货地址 2803
	 +----------------------------------------------------------
	 */
	function del() {
		$add_id = isset($_GET['add_id']) ? intval($_GET['add_id']) : 0;
		
		$opt = isset($_GET['opt']) ? intval($_GET['opt']) : 0; // 0，删除；1，设置为默认地址
		$addrModel = M('address');
		if($user_id = $this->uid) {
			$where = " user_id=$user_id ";

			if($add_id && $opt==0) { //删除地址
				$where .= ' and add_id='.$add_id;
				$addrModel->where($where)->delete(); // 删除id为5的用户数据

				$data = array('id' => $add_id);// nangua  20150713
				$this->successView();
			} elseif($add_id && $opt==1)  { // 设置默认地址
                // 去掉 $data['add_id']   nangua  20150713
				$data['type'] = 1;
				$where .= " and add_id = {$add_id} ";
				if($addrModel->where($where)->save($data)!==false) {

                    $data['id'] =  $add_id;// nangua  20150713
                    // 去除原先默认的地址
                    $where = "user_id = {$user_id} and add_id <> {$add_id} ";

                    $addrModel->where($where)->setField("type",0);

					$this->successView();
				} else {
					$this->errorView($this->ws_code['validate_error'],$this->msg['internal_error']);
				}
			} else {
				$this->errorView($this->ws_code['empty_error'], $this->msg['empty_error']);
			}
		}
	}
	
}
