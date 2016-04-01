<?php
/**
 * 角色操作
 * @author feibo
 *
 */

class roleAction extends BaseAction {
	
	/**
	 +----------------------------------------------------------
	 * 角色列表
	 +----------------------------------------------------------
	 */	
	function index() {
		$role_mod = D('role');
		import("ORG.Util.Page");
		$count = $role_mod->count();
		$p = new Page($count,30);
		$role_list = $role_mod->limit($p->firstRow.','.$p->listRows)->select();
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Role&a=add\', title:\'添加角色\', width:\'400\', height:\'220\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', '添加角色');
		$page = $p->show();
		$this->assign('page',$page);
		$this->assign('big_menu',$big_menu);
		$this->assign('role_list',$role_list);
		$this->display();
	}
	
	/**
	 +----------------------------------------------------------
	 * 添加角色
	 +----------------------------------------------------------
	 */
	function add() {
		if(isset($_POST['dosubmit'])){
			$role_mod = D('role');
			if(!isset($_POST['name'])||($_POST['name']=='')) {
				$this->error('请填写角色名');
			}
			$result = $role_mod->where("name='".$_POST['name']."'")->count();
			if($result) {
				$this->error('角色已经存在');
			}
			$role_mod->create();
			$result = $role_mod->add();
			if($result) {
				$this->success(L('operation_success'), '', '', 'add');
			} else {
				$this->error(L('operation_failure'));
			}
		} else {
			$this->assign('show_header', false);
			$this->display();
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 编辑角色
	 +----------------------------------------------------------
	 */
	public function edit()
	{
		if(isset($_POST['dosubmit'])) {
			$role_mod = D('role');
			if (false === $role_mod->create()) {
				$this->error($role_mod->getError());
			}
			$result = $role_mod->save();
			if(false !== $result) {
				$this->success(L('operation_success'), '', '', 'edit');
			} else {
				$this->error(L('operation_failure'));
			}
		} else {
			if( isset($_GET['id']) ) {
				$id = isset($_GET['id']) && intval($_GET['id']) ? intval($_GET['id']) : $this->error('参数错误');
			}
			$role_mod = D('role');
			$role_info = $role_mod->where('id='.$id)->find();
			$this->assign('role_info', $role_info);
			$this->assign('show_header', false);
			$this->display();
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除角色
	 +----------------------------------------------------------
	 */
	function delete() {
		if((!isset($_GET['id']) || empty($_GET['id'])) && (!isset($_POST['id']) || empty($_POST['id']))) {
			$this->error('请选择要删除的角色！');
		}
		$role_mod = D('role');
		if (isset($_POST['id']) && is_array($_POST['id'])) {
			$ids = implode(',', $_POST['id']);
			$role_mod->delete($ids);
		} else {
			$id = intval($_GET['id']);
			$role_mod->delete($id);
		}
		$this->success(L('operation_success'));
	}

	/**
	 +----------------------------------------------------------
	 * 授权角色
	 +----------------------------------------------------------
	 */
	public function auth() {
		$cate = M('Article_cate');
		$role_id = intval($_REQUEST['id']);
		$node_ids_res = M("access")->where("role_id=".$role_id)->field("node_id, role_id")->select();
		$node_ids = array();
		$role_ids = array();
		foreach ($node_ids_res as $row) {
			array_push($node_ids,$row['node_id']);
			array_push($role_ids, $row['role_id']);
		}
		//取出模块授权
		$tmp = D("node")->where("status = 1")->order('pid asc')->select();
		$modules = array();
		foreach ($tmp as  $v) {
			if ($v['pid'] == 0) {
				$modules[$v['id']] = $v;
			} else {
				$modules[$v['pid']]['actions'][] = $v;
			}	
		}

		foreach ($modules as $k=>$module) {
			if (in_array($module['id'],$node_ids)) {
				$modules[$k]['checked'] = true;
			} else {
				$modules[$k]['checked'] = false;
			}
			foreach ($module['actions'] as $kk=>$action) {
				if(in_array($action['id'],$node_ids)) {
					$modules[$k]['actions'][$kk]['checked'] = true;
				} else {
					$modules[$k]['actions'][$kk]['checked'] = false;
				}
			}
		}
//		$cate_modules = M('node')->where("status = 1 AND pid = 0")->select();
//		foreach ($cate_modules as $key=>$article_cate) {
//			if (in_array($article_cate['id'], $node_ids)) {
//				$cate_modules[$key]['checked'] = true;
//			} else {
//				$cate_modules[$key]['checked'] = false;
//			}
//		}
//		$this->assign('article_cate', $cate_modules);
		$this->assign('access_list',$modules);
		$this->assign('id',$role_id);
		$this->display();
	}
	
	/**
	 +----------------------------------------------------------
	 * 授权提交
	 +----------------------------------------------------------
	 */
	public function authSubmit() {
		$access = M("Access");
		$role_id = intval($_REQUEST['id']);
		D("access")->where("role_id=".$role_id)->delete();

		$node_ids = $_REQUEST['access_node'];
		$cate_ids = $_REQUEST['access_cate'];
	
		foreach ($cate_ids as $cate_id)
		{
			$data_cate['role_id'] = $role_id;
			$data_cate['cate_id'] = $cate_id;
			$access->add($data_cate);	 	
		}
		foreach ($node_ids as $node_id) {
			$data_node['role_id'] = $role_id;
			$data_node['node_id'] =$node_id ;
			$access->add($data_node);
		}
		$this->success(L('operation_success'));
	}

	/**
	 +----------------------------------------------------------
	 * 修改状态
	 +----------------------------------------------------------
	 */
	function status() {
		$role_mod = D('role');
		$id 	= intval($_REQUEST['id']);
		$type 	= trim($_REQUEST['type']);
		$sql 	= "update ".C('DB_PREFIX')."role set $type=($type+1)%2 where id='$id'";
		$res 	= $role_mod->execute($sql);
		$values = $role_mod->where('id='.$id)->find();
		$this->ajaxReturn($values[$type]);
	}
}
?>
