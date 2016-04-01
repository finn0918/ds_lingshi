<?php
/**
 * 操作节点
 * @author feibo
 *
 */

class NodeAction extends BaseAction
{
	/**
	 +----------------------------------------------------------
	 * 节点列表
	 +----------------------------------------------------------
	 */
	function index() {
		$node_mod = D('node');
		import("ORG.Util.Page");
		$count = $node_mod->count();
		$p = new Page($count,15);

		$tmp = D('node')->where('pid=0')->order('sort desc')->select();
		$parent_list = array('0'=>'/');
		foreach($tmp AS $v) {
			$parent_list[$v['id']] = $v['module_name'];
		}

		$node_list = $node_mod->limit($p->firstRow.','.$p->listRows)->order('module DESC')->select();
		foreach ($node_list as $key => $val) {
			$node_list[$key]['module_name'] = empty($v['pid']) ? $val['module_name'] : $parent_list[$val['pid']];
			if ($val['is_show'] == 1) {
				$node_list[$key]['is_show'] = 0;
			} else {
				$node_list[$key]['is_show'] = 1;
			}
		}
		$big_menu = array('javascript:window.top.art.dialog({id:\'add\',iframe:\'?m=Node&a=add\', title:\'添加菜单\', width:\'500\', height:\'480\', lock:true}, function(){var d = window.top.art.dialog({id:\'add\'}).data.iframe;var form = d.document.getElementById(\'dosubmit\');form.click();return false;}, function(){window.top.art.dialog({id:\'add\'}).close()});void(0);', '添加菜单');
		$page = $p->show();
		$this->assign('page',$page);
		$this->assign('big_menu',$big_menu);
		$this->assign('node_list',$node_list);
		$this->display();
	}
	/**
	 +----------------------------------------------------------
	 * 添加节点
	 +----------------------------------------------------------
	 */
	function add() {
		//分组
		if(isset($_POST['dosubmit'])) {
			if(!isset($_POST['module'])||($_POST['module']=='')) {
				$this->error('请填写模型');
			}
			if(!isset($_POST['module_name'])||($_POST['module_name']=='')) {
				$this->error('请填写模型名称');
			}

			$node_mod = D('node');
			$node_mod->create();
			$result = $node_mod->add();
			if($result){
				$this->success(L('operation_success'), '', '', 'add');
			}else{
				$this->error(L('operation_failure'));
			}
		} else {
			$group_mod = D('group');
			$group_list = $group_mod->select();
			$parent_list = D('node')->where('pid=0')->order('sort desc')->select();
			$this->assign('group_list',$group_list);
			$this->assign('parent_list',$parent_list);
			$this->assign('show_header', false);
			$this->display();
		}
	}

	/**
	 +----------------------------------------------------------
	 * 编辑节点
	 +----------------------------------------------------------
	 */
	public function edit() {
		if(isset($_POST['dosubmit'])){
			if(!isset($_POST['module'])||($_POST['module']=='')) {
				$this->error('请填写模型');
			}
			if(!isset($_POST['module_name'])||($_POST['module_name']=='')) {
				$this->error('请填写模型名称');
			}
			$node_mod = D('node');
			if (false === $node_mod->create()) {
				$this->error($node_mod->getError());
			}
			$result = $node_mod->save();
			if(false !== $result){
				$this->success(L('operation_success'), '', '', 'edit');
			}else{
				$this->error(L('operation_failure'));
			}

		} else {
			if( isset($_GET['id']) ){
				$id = isset($_GET['id']) && intval($_GET['id']) ? intval($_GET['id']) : $this->error('参数错误');
			}

			$group_mod = D('group');
			$group_list = $group_mod->select();
			$this->assign('group_list',$group_list);

			$node_mod = D('node');
			$node_info = $node_mod->where('id='.$id)->find();

			$parent_list = D('node')->where('pid=0')->order('sort desc')->select();
			$this->assign('parent_list',$parent_list);

			$this->assign('node_info', $node_info);
			$this->assign('show_header', false);
			$this->display();

		}
	}

	/**
	 +----------------------------------------------------------
	 * 删除节点
	 +----------------------------------------------------------
	 */
	function delete() {
		if((!isset($_GET['id']) || empty($_GET['id'])) && (!isset($_POST['id']) || empty($_POST['id']))) {
			$this->error('请选择要删除的角色！');
		}
		$node_mod = D('node');
		if (isset($_POST['id']) && is_array($_POST['id'])) {
			$ids = implode(',', $_POST['id']);
			$node_mod->delete($ids);
		} else {
			$id = intval($_GET['id']);
			$node_mod->delete($id);
		}
		$this->success(L('operation_success'));
	}

	/**
	 +----------------------------------------------------------
	 * 节点状态
	 +----------------------------------------------------------
	 */
	public function status() {
		$id = intval($_REQUEST['id']);
		$type = trim($_REQUEST['type']);
		$node_mod = D('node');
		$res = $node_mod->where('id=' . $id)->setField($type, array('exp', "(" . $type . "+1)%2"));
		$values = $node_mod->where('id=' . $id)->getField($type);
		$this->ajaxReturn($values[$type]);
	}
}
?>
