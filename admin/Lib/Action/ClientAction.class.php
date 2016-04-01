<?php
/**
 * 用户模板
 * @author feibo
 *
 */
class ClientAction extends BaseAction {
	
	// 用户状态
	var $c_status;
	
	// 平台列表
	var $platform_list;
	
	/**
	 +----------------------------------------------------------
	 * 构造函数
	 +----------------------------------------------------------
	 */
	function _initialize() {
		// 用户状态配置
		$this->c_status = array(
			'inactive' => '<span style="color:green;font-weight:bold;">已激活</span>',
			'deleted' => '<span style="color:red;font-weight:bold;">已删除</span>',
		);
		$this->platform_list = array(1 => '腾讯QQ', 2 => '新浪微博', 3 => '微信');
	}
	
	/**
	 +----------------------------------------------------------
	 * 用户列表
	 +----------------------------------------------------------
	 */
	public function index() {
	   	$ttype_list = C('PLATFORM_TYPE');
		$mod_client = M('client');
		$mod_tag_client = M('tag_client');
		$mod_tag = M('tag');
		
		$where = " 1=1 ";
		
		// 查询条件
		$search['ttype'] = $ttype = isset($_REQUEST['ttype']) ? intval($_REQUEST['ttype']) : '';
		$search['time_start'] = $time_start = isset($_REQUEST['time_start']) ? htmlspecialchars(trim($_REQUEST['time_start'])) : '';
		$search['time_end'] = $time_end = isset($_REQUEST['time_end']) ? htmlspecialchars(trim($_REQUEST['time_end'])) : '';
		$search['nickname'] = $nickname = isset($_REQUEST['nickname']) ? htmlspecialchars(trim($_REQUEST['nickname'])) : '';

		if(!empty($ttype)) {
			$where .= " and pf_type = '".$ttype."'";
		}
		if(!empty($nickname)) {
			$where .= " and nickname='".$nickname."'";
		}
		if(!empty($time_start)) {
			$where .= " and create_time >".strtotime($time_start);
		}
		if(!empty($time_end)) {
			$where .= " and create_time <".strtotime($time_end);
		}
		
		// 分页
		$count = $mod_client->where($where)->count();
		import("ORG.Util.Page");
		$page = new Page($count,15,$parameter="&ttype=$ttype&nickname=$nickname&time_start=$time_start&time_end=$time_end");
		
		// 列表数据
		$client_list = $mod_client->where($where)->limit($page->firstRow.','.$page->listRows)->select();

		$show = $page->show();
		foreach($client_list as $key=>$value) {
			$client_list[$key]['platform_type'] = $this->platform_list[$client_list[$key]['pf_type']];
			$client_list[$key]['status'] = $this->c_status[$client_list[$key]['status']];
			
		}
		$this->assign('search',$search);
		$this->assign('page',$show);
		$this->assign('client_list',$client_list);
	   	$this->assign('ttype',$ttype_list);
		$this->display();
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除用户操作（单个）
	 +----------------------------------------------------------
	 */
	function delete() {
		$id = intval($_GET['id']);
		$mod_client = M('client');
		$where['id'] =$id;
		if($mod_client->where($where)->setField('status','deleted')) {
			$this->success(L('operation_success'));
		} else {
			$this->error(L('operation_failure'));
		}


	}
	
	/**
	 +----------------------------------------------------------
	 * 批量删除用户操作
	 +----------------------------------------------------------
	 */
	public function dels() {
		$mod_client = M('client');
		if (isset($_POST['id'])) {
			$id_arr = $_POST['id'];
			$ids = implode(',', $id_arr);
			$where = "id in ($ids)";
			if($mod_client->where($where)->setField('status','deleted')) {
				$this->success(L('operation_success'));
			} else {
				$this->error(L('operation_failure'));
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 显示用户详情
	 +----------------------------------------------------------
	 */
	public function show() {
		
		$mod_tag = M('tag');
		$mod_tag_client = M('tag_client');
		$id = isset($_GET['id']) ? intval($_GET['id']) : '';
		
		$data = M('client')->where('id='.$id)->find();
		$data['platform_type'] = $this->platform_list[$data['pf_type']];
		$data['status'] = $this->c_status[$data['status']];

		$data['tags'] = $mod_tag_client->where('client_id='.$data[0]['id'])->field('tag_id')->select();
		foreach($data['tags'] as $i=>$val)
		{
				$data['tag'][$i] = $mod_tag->where('id='.$val['tag_id'])->getField('name');
		}
		$this->assign('data',$data);
		$this->display();
	}

}
?>