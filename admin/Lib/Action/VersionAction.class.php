<?php
/**
 * App 版本类
 * @author feibo
 *
 */

class VersionAction extends BaseAction{
	
	/**
	 +----------------------------------------------------------
	 * 版本列表
	 +----------------------------------------------------------
	 */
    public function index() {
    	$c_ostype = C('OS_TYPE');
    	$mod_version = M('version');
    	$where = "1=1";
		$os_type = isset($_REQUEST['os_type']) ? intval(trim($_REQUEST['os_type'])) :'';
    	if(!empty($os_type)) {
			$where .= " and os_type=".$os_type;
    	}
     	import("ORG.Util.Page");
    	$count = $mod_version->where($where)->count();
		$page = new Page($count,15,$parameter="&os_type=$os_type");
		$show = $page->show();
		$version_list = $mod_version->where($where)->limit($page->firstRow.','.$page->listRows)->order('create_time desc')->select();
    	foreach($version_list as $key=>$val) {
			$version_list[$key]['os_type'] = $c_ostype[$val['os_type']];
    	}

    	$this->assign('page',$show);
    	$this->assign('os_type',$c_ostype);
    	$this->assign('version_list',$version_list);
    	$this->display();
    }
    
    /**
     +----------------------------------------------------------
     * 添加版本
     +----------------------------------------------------------
     */
    public function add() {
    	if($_POST['addsubmit']) {
			$data = $_POST;
			$data['create_time'] = time();
			if(M('version')->add($data)) {
				$this->success(L('operation_success'),'?m=Version&a=index');
			} else {
				$this->error('添加失败','?m=Version&a=index');
			}

    	}
    	$this->display();
    }
    
    /**
     +----------------------------------------------------------
     * 删除
     +----------------------------------------------------------
     */
    public function delete() {
		$id = intval($_REQUEST['id']);
		if(M('version')->where('id='.$id)->delete()){
			$this->success(L('operation_success'),'?m=Version&a=index');
		}

    }
    
    /**
     +----------------------------------------------------------
     * 编辑
     +----------------------------------------------------------
     */
    public function edit() {
    	$id = intval($_REQUEST['id']);
    	if (isset($_POST['dosubmit'])) {
			$data = $_POST ;
			if( M('version')->where('id='.$id)->save($data)){
				$this->success(L('operation_success'), '', '', 'edit');
			} else {
				$this->error(L('operation_failure'));
			}
    	}
    	$info = M('version')->where('id='.$id)->select();
    	$this->assign('info',$info['0']);
		$this->display();
    }
}
?>