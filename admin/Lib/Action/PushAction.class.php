<?php
/**
 * 推送类
 * @author hhf
 *
 */

class pushAction extends BaseAction{

	/**
	 +----------------------------------------------------------
	 * 推送列表
	 +----------------------------------------------------------
	 */
	public function index() {
		$push_mod = M('push_message');
		$os_type = C('OS_TYPE');
		$push_status = C('PUSH_STATUS');
	    $where = '1=1';
        $_GET['time'] = strtotime($_GET['time']);
        if (isset($_GET['keyword']) && trim($_GET['keyword']))
        {
            $where .= " AND content LIKE '%" . $_GET['keyword'] . "%'";
            $this->assign('keyword', $_GET['keyword']);
        }
        if (isset($_GET['time']) && trim($_GET['time']))
        {
            $time = $_GET['time'];
            $where .= " AND start_time < $time AND end_time > $time";
            $this->assign('time', date('Y-m-d',$_GET['time']));
        }
        import("ORG.Util.Page");
        $count = $push_mod->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $push_list = $push_mod->where($where)->order('create_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($push_list as $key=>$val)
		{
			$push_list[$key]['os_type'] = $os_type[$val['os_type']];
			$push_list[$key]['status'] = $push_status[$val['status']];
		}
	 	$this->assign('os_type', $os_type);
        $this->assign('page', $show);
        $this->assign('push_list',$push_list);
        $this->display();
	}

	/**
	 +----------------------------------------------------------
	 * 添加推送
	 +----------------------------------------------------------
	 */
	public function add(){
		$os_type = C('OS_TYPE');
		$push_status = C('PUSH_STATUS');
		$msg_type = C('MSG_TYPE');
       if (isset($_POST['dosubmit'])){
            $push_mod = M('push_message');
            $data = $_POST;
            if($data['start_time']==''){
            	$data['start_time'] =  time();
            }else{
            	$data['start_time'] = strtotime($data['start_time']);
            }
			if($data['end_time'] == ''){
				$data['end_time'] = time();
			}else{
				$data['end_time'] = strtotime($data['end_time']);
			}
            $data['create_time'] =  time();
            $data['status'] = 2;
            $pid = $push_mod->add($data);
            if(false === $pid ){
            	 $this->error('操作失败','?m=Push&a=index');
            }else{
            	$this->success(L('operation_success'),'?m=Push&a=index');
            }
        }else{
        	$this->assign('push_status', $push_status);
        	$this->assign('os_type', $os_type);
        	$this->assign('msg_type', $msg_type);
            $this->display();
        }
	}

	/**
	 +----------------------------------------------------------
	 * 修改推送
	 +----------------------------------------------------------
	 */
	public function edit() {
		$os_type = C('OS_TYPE');
		$push_status = C('PUSH_STATUS');
        $msg_type = C('MSG_TYPE');
		if (isset($_POST['dosubmit'])){
			$push_mod = M('push_message');
            $data = $_POST;
            if($data['start_time']==''){
            	$data['start_time'] =  time();
            }else{
            	$data['start_time'] = strtotime($data['start_time']);
            }
			if($data['end_time'] == ''){
				$data['end_time'] = time();
			}else{
				$data['end_time'] = strtotime($data['end_time']);
			}
            $flag = $push_mod->save($data);
            //file_put_contents('tags.log',$push_mod->getLastSql()."\r\n",FILE_APPEND);
			if($flag !== false){
				$this->success(L('operation_success'),'?m=Push&a=index');
			}else{
				$this->error('操作失败','?m=Push&a=index');
			}
		}else{
			$id = $_GET['id'];
			$push_mod = M('push_message');
			$this->assign('os_type', $os_type);
			$push = $push_mod->where("id = ".$id)->find();
			$this->assign('push_status', $push_status);
            $this->assign('msg_type', $msg_type);
			$this->assign('push',$push);
			$this->display();
		}
	}

	/**
	 +----------------------------------------------------------
	 * 删除推送
	 +----------------------------------------------------------
	 */
	public function del(){
		$id = isset($_GET['id']) ? trim($_GET['id']) : '';
		if($id){
			$push_mod = M('push_message');
			$flag = $push_mod->where("id=".$id)->delete();
			if($flag){
				$this->success(L('operation_success'),'?m=Push&a=index');
			}else{
				$this->error('操作失败','?m=Push&a=index');
			}
		}
	}
}