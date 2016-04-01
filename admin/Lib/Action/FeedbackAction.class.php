<?php
/**
 * 用户反馈类
 * @author hch
 *
 */
class FeedbackAction extends BaseAction{
	
	/**
	 +----------------------------------------------------------
	 * 反馈列表
	 +----------------------------------------------------------
	 */
	public function index() {
        $feedback = M('feedback');
        $admin_mod = M('admin');
        //搜索
        $where = '1=1';
        if (isset($_GET['keyword']) && trim($_GET['keyword']))
        {
            $where .= " AND content LIKE '%" . $_GET['keyword'] . "%'";
            $this->assign('keyword', $_GET['keyword']);
        }
        if (isset($_GET['os_type']) && intval($_GET['os_type']))
        {
            $where .= " AND os_type=" . $_GET['os_type'];
            $this->assign('os_type', $_GET['os_type']);
        }
        if (isset($_GET['time_start']) && trim($_GET['time_start']))
        {
            $time_start = strtotime($_GET['time_start']);
            $where .= " AND create_time>='" . $time_start . "'";
            $this->assign('time_start', trim($_GET['time_start']));
        }
        if (isset($_GET['time_end']) && trim($_GET['time_end']))
        {
            $time_end = strtotime($_GET['time_end']);
            $where .= " AND create_time<='" . $time_end . "'";
            $this->assign('time_end',trim($_GET['time_end']));
        }
        import("ORG.Util.Page");
        $count = $feedback->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $feedback_list = $feedback->where($where)->order('create_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($feedback_list as $k => $v){
            if($v['auid'] == 0){
                $feedback_list[$k]['auid'] = '';
            }else{
                $user_name = $admin_mod->where('id ='.$v['auid'])->getField('user_name');
                $feedback_list[$k]['auid'] = $user_name;
            }
        }
        $this->assign('page', $show);
        $this->assign('feedback_list', $feedback_list);
        $this->display();
	}
	
	/**
	 +----------------------------------------------------------
	 * 回复用户反馈
	 +----------------------------------------------------------
	 */
	public function reply(){
        $feedback = M('feedback');
        $auid = $_SESSION['admin_info']['id'];
        if (isset($_POST['dosubmit'])){
            $data = $_POST;
            $id = $data['id'];
            $data_add['reply_content'] = $data['reply_content'];
            $data_add['auid'] = $auid;
            $data_add['reply_time'] = time();
            if($feedback->where('id ='.$id)->save($data_add)){
                $this->success(L('operation_success'));
            }else{
                $this->error('提交失败');
            }
        }else{
            $id = $_GET['id'];
            $data = $feedback->where('id ='.$id)->find();
            $feedback_list = $feedback->where("nickname = '{$data['nickname']}'")->order('create_time')->select();
            if($data['os_type'] == 1){
                $data['os_type'] = 'IOS';
            }elseif($data['os_type'] == 3){
                $data['os_type'] = '安卓';
            }else{
                $data['os_type'] ='';
            }
            $this->assign('data',$data);
            $this->assign('feedback_list',$feedback_list);
            $this->display();
        }

	}
	
	/**
	 +----------------------------------------------------------
	 * 删除用户反馈
	 +----------------------------------------------------------
	 */
	public function del(){
		$feedback = M('feedback');
		$id = isset($_GET['id'])?intval($_GET['id']) : '';

		if($feedback -> where('id ='.$id)->delete()) {
			$this->success(L('operation_success'));
		} else {
			$this->error(L('operation_failure'));
		}

	}
	
	/**
	 +----------------------------------------------------------
	 * 批量删除用户反馈
	 +----------------------------------------------------------
	 */
	public function dels(){
		$feedback = M('feedback');
		if (isset($_POST['id'])) {
			$id = $_POST['id'];
			$count = count($id);
			for($i=0;$i<$count;$i++) {
				$where['id'] = $id[$i];
				$feedback->where('id ='.$id[$i])->delete();
			}
			$this->success(L('operation_success'));
		}
	}


}