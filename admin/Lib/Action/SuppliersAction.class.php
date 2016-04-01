<?php
/**
 * 供应商管理模块
 * @author hhf
 *
 */

class SuppliersAction extends BaseAction{
	/**
	 +----------------------------------------------------------
	 * 供应商列表
	 +----------------------------------------------------------
	 */

    function index() {
    	$startTime = I('get.time_start','','trim,htmlspecialchars');
    	$where = " 1 = 1 ";
    	if($startTime){
    		$this->assign('time_start',$startTime);
			$startTime = strtotime($startTime);
			$where .= " and add_time >= $startTime";
    	}
    	$endTime = I('get.time_end','','trim,htmlspecialchars');
    	if($endTime){
    		$this->assign('time_end',$endTime);
    		$endTime = strtotime($endTime);
    		$where .= " and add_time <= $endTime";
    	}
    	$suppliersMod = M('suppliers');
    	$suppliersResult = $suppliersMod->where($where)->select();
    	$bankArray = C('BANK_TYPE');
    	foreach($suppliersResult as $k=>$v){
			$suppliersResult[$k]['bank'] = $bankArray[$v['type']];
    	}
    	$this->assign('suppliers',$suppliersResult);
    	$this->display();
    }

    /**
	 +----------------------------------------------------------
	 * 供应商增加
	 +----------------------------------------------------------
	 */

    function add() {
    	if(isset($_POST['dosubmit'])){
    		$suppliersMod = M('suppliers');
    		$suppliers_name = I('post.supplier','','trim,htmlspecialchars');
            $shop_name = I('post.name','','trim,htmlspecialchars');
    		$suppliers_mobile = I('post.phone','','trim,htmlspecialchars');
    		$suppliers_address = I('post.address','','trim,htmlspecialchars');
    		$account = I('post.bankNum','','trim,htmlspecialchars');
    		$type = I('post.bank',0,'intval');
    		$add_time = time();
    		$update_time = time();
    		$data = array();
    		$data['suppliers_name'] = $suppliers_name;
    		$data['suppliers_mobile'] = $suppliers_mobile;
    		$data['suppliers_address'] = $suppliers_address;
    		$data['account'] = $account;
    		$data['add_time'] = $add_time;
    		$data['update_time'] = $update_time;
    		$data['key'] = $this->getRandChar(18);
    		$data['type'] = $type;
            $data['shop_name'] = $shop_name;
    		$user = $_SESSION['admin_info'];
    		$data['admin_id'] = $user['id'];
    		//var_dump($data);
    		$flag = $suppliersMod->add($data);
    		if($flag){
				$this->success('添加成功！！','?m=Suppliers&a=index');
    		}else{
				$this->error('添加失败！请重试！！！');
    		}
    	}else{
    		$bankArray = C('BANK_TYPE');
    		$newBankArray = array();
    		foreach($bankArray as $k=>$v){
				$tmp['k'] = $k;
				$tmp['v'] = $v;
				$newBankArray[] = $tmp;
    		}
    		$this->assign('bank',$newBankArray);
			$this->display();
    	}
    }

    /**
	 +----------------------------------------------------------
	 * 编辑供应商详情
	 +----------------------------------------------------------
	 */
    function edit () {
		$suppliersId = I('request.id',0,'intval');
		$suppliersMod = M('suppliers');
		if(isset($_POST['dosubmit'])){
    		$suppliersMod = M('suppliers');
    		$suppliers_name = I('post.supplier','','trim,htmlspecialchars');
            $shop_name = I('post.name','','trim,htmlspecialchars');
    		$suppliers_mobile = I('post.phone','','trim,htmlspecialchars');
    		$suppliers_address = I('post.address','','trim,htmlspecialchars');
    		$account = I('post.bankNum','','trim,htmlspecialchars');
    		$type = I('post.bank',0,'intval');
    		$update_time = time();
    		$data = array();
    		$data['suppliers_name'] = $suppliers_name;
    		$data['suppliers_mobile'] = $suppliers_mobile;
    		$data['suppliers_address'] = $suppliers_address;
    		$data['account'] = $account;
    		$data['update_time'] = $update_time;
    		$data['type'] = $type;
            $data['shop_name'] = $shop_name;
    		$user = $_SESSION['admin_info'];
    		$data['admin_id'] = $user['id'];
    		$data['suppliers_id'] = $suppliersId;
    		//var_dump($data);
    		$flag = $suppliersMod->save($data);
    		if($flag){
				$this->success('修改成功！！','?m=Suppliers&a=index');
    		}else{
				$this->error('修改失败！请重试！！！');
    		}
    	}else{
    		$bankArray = C('BANK_TYPE');
    		$newBankArray = array();
    		foreach($bankArray as $k=>$v){
				$tmp['k'] = $k;
				$tmp['v'] = $v;
				$newBankArray[] = $tmp;
    		}
    		$this->assign('bank',$newBankArray);
    		$suppliersResult = $suppliersMod->find($suppliersId);
    		$this->assign('suppliers',$suppliersResult);
			$this->display();
    	}
	}
    /**
	 +----------------------------------------------------------
	 * 供应商详情（包含供货订单）
	 +----------------------------------------------------------
	 */

	 function detail () {
		$suppliersId = I('get.id',0,'intval');
		$suppliersMod = M('suppliers');
		$suppliersResult = $suppliersMod->find($suppliersId);
    	$bankArray = C('BANK_TYPE');
    	$type = $suppliersResult['type'];
    	$suppliersResult['bank'] = $bankArray["$type"];
    	$this->assign('suppliers',$suppliersResult);
		$this->display();
	}

	/**
	 +----------------------------------------------------------
	 * 生成随机字符串
	 +----------------------------------------------------------
	 */

	function getRandChar($length){
   		$str = null;
   		$strPol = "0123456789abcdefghijklmnopqrstuvwxyz";
   		$max = strlen($strPol)-1;
  		 for($i=0;$i<$length;$i++){
    		$str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
   		}
  		 return $str;
  	}

}
?>