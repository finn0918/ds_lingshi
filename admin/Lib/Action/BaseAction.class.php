<?php
/**
 * 基础Action
 * @author feibo
 *
 */

class BaseAction extends Action {
    var $user_mode;
    var $admin_mod;

     /**
     +----------------------------------------------------------
     * 构造函数
     +----------------------------------------------------------
     */
    function _initialize() {
       
        $this->modInit();
		
        // 网站地址
        $this->site_root = "http://" . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT']) . __ROOT__ . "/";
        $this->assign('site_root', $this->site_root);

        // 读取项目公共语言包
        $langSet = C('DEFAULT_LANG');
        if (is_file(LANG_PATH . $langSet . '/common.php'))
        {
            L(include LANG_PATH . $langSet . '/common.php');
        }
        
        // 用户权限检查，admin=1 超级管理员 无需检查权限
        if($_SESSION['admin_info']['id'] != 1) {
        	$this->checkPriv();
        }

        // 载入菜单语言包
        if (is_file(LANG_PATH . $langSet . '/menu.php')) {
            L(include LANG_PATH . $langSet . '/menu.php');
        }
        // 登陆用户信息
        $admin_info = $_SESSION['admin_info'];
        $this->assign('my_info', $admin_info);
        
         // 顶部菜单
        $model = M("group");
        $top_menu = $model->field('id,title')->where('status=1')->order('sort ASC')->select();
        $this->assign('top_menu', $top_menu);

        $this->assign('show_header', true);
        $this->assign('const', get_defined_constants());

        $this->assign('iframe', $_REQUEST['iframe']);
        $def = array(
            'request' => $_REQUEST
        );
        $this->assign('def', json_encode($def));


        //目录树操作
        $this->update_item_nums();

        if (!$this->isAjax()) {
            $menu = array();

            $role_id = D('admin')->where('id=' . $_SESSION['admin_info']['id'])->getField('role_id');
            $node_ids_res = D("access")->where("role_id=" . $role_id)->field("node_id")->select();
            //默认显示文章添加页面
            $menuid = 103;
            $default_url = U('article/add');

            $node_ids = array();
            foreach ($node_ids_res as $row)
            {
                array_push($node_ids, $row['node_id']);
            }

            //读取数据库模块列表生成菜单项
            $node = M("node");
            $where = "auth_type<>2 AND status=1 AND is_show=0";
            $list = $node->where($where)->field('id,pid,action,action_name,module,module_name,group_id,data')->order('pid ASC,sort DESC')->select();
            $menu = array();
            foreach ($list as $key => $action) {
                $pid = intval($action['pid']);
                $id = intval($action['id']);
                $data_arg = array();
                if ($action['data']) {
                    $data_arr = explode('&', $action['data']);
                    foreach ($data_arr as $data_one) {
                        $data_one_arr = explode('=', $data_one);
                        $data_arg[$data_one_arr[0]] = $data_one_arr[1];
                    }
                }
                $action['url'] = U($action['module'] . '/' . $action['action'], $data_arg);
                if ($pid > 0)
                {
                    if ($this->isAdmin() || in_array($id,$node_ids)) {
                        $action['on'] = 0;
                        if ($id == 103) {
                            $action['on'] = 1;
                            $menu[$pid]['on'] = 1;
                        }
                        $menu[$pid]['navs'][] = $action;
                    }
                } else {
                    $menu[$id] = $action;
                    $menu[$id]['on'] = 0;
                    $menu[$id]['name'] = $action['module_name'];
                }
            }
            foreach($menu AS $k =>$v) {
                if(empty($v['navs'])) {
                    unset($menu[$k]);
                }
            }
            $menu_list = array();
            foreach($menu AS $v) {
                $menu_list[$v['group_id']][] = $v;
            }
            $this->assign('menu_list', $menu_list);
            $this->assign('default_url',$default_url);
        }
    }

     /**
     +----------------------------------------------------------
     * 后台用户表初始化
     +----------------------------------------------------------
     */
    function modInit() {
    	$this->admin_mod = D('admin');
    }
    
    /**
     +----------------------------------------------------------
     *  检查权限
     +----------------------------------------------------------
     */
    public function checkPriv() {
    	
    	// 用户是否是登陆状态
    	$user_data = isset($_SESSION['admin_info']) ?  $_SESSION['admin_info'] : '';
    	
    	// 过滤指定模块
    	$filter_action_arr = array('login', 'verify_code');
    	
    	if(empty($user_data) && !in_array(ACTION_NAME, $filter_action_arr)) {
            $this->redirect('Public/login');
        }
        
        // admin 管理员
        if ($_SESSION['admin_info']['role_id'] == 1) {
            return true;
        }
        
        // 无需验证权限操作
        if (in_array(ACTION_NAME, array('status', 'sort_order', 'ordid'))) {
            return true;
        }
        //排除一些不必要的权限检查,/admin/conf/config.php
        foreach (C('IGNORE_PRIV_LIST') as $key => $val) {
            if (MODULE_NAME == $val['module_name']) {
                if (count($val['action_list']) == 0)
                    return true;

                foreach ($val['action_list'] as $action_item) {
                    if (ACTION_NAME == $action_item)
                        return true;
                }
            } 
        }
         
        // 模块权限
        $node_mod = M('node');
        $node_id = $node_mod->where(array(
            'module' => strtolower(MODULE_NAME), //兼容旧版，后台权限控制模块都小写
            'action' => ACTION_NAME
        ))->getField('id');
       
        
        $access_mod = D('access');
        $r = $access_mod->where(array(
            'node_id' => $node_id ,
            'role_id' => $_SESSION['admin_info']['role_id']
        ))->count();
        
        // 提示无权限
        if ($r == 0) {
            $this->error(L('_VALID_ACCESS_'));
        }
    }
    
    /**
     +----------------------------------------------------------
     * 操作错误输出
     +----------------------------------------------------------
     */
    protected function error($message, $url_forward = '', $ms = 3, $dialog = false, $ajax = false, $returnjs = '') {
        $this->jumpUrl = $url_forward;
        $this->waitSecond = $ms;
        $this->assign('dialog', $dialog);
        $this->assign('returnjs', $returnjs);
        parent::error($message, $ajax);
    }
    
    /**
     +----------------------------------------------------------
     * 操作成功输出
     +----------------------------------------------------------
     */
    protected function success($message, $url_forward = '', $ms = 3, $dialog = false, $ajax = false, $returnjs = '') {
        $this->jumpUrl = $url_forward;
        $this->waitSecond = $ms;
        $this->assign('dialog', $dialog);
        $this->assign('returnjs', $returnjs);
        parent::success($message, $ajax);
    }
    
    /**
     +----------------------------------------------------------
     * 目录树操作
     +----------------------------------------------------------
     */
    function update_item_nums() {
    	$item_mod = D('items');
    	$item_cate_mod = D('items_cate');
    	$item_nums = $item_mod->field('cid,count(id) as cate_nums')->group('cid')->select();
    
    	foreach ($item_nums as $val) {
    		$item_cate_mod->save(array(
    				'id' => $val['cid'] ,
    				'item_nums' => $val['cate_nums']
    		));
    	}
    }
    
    /**
     +----------------------------------------------------------
     * 是否为管理员
     +----------------------------------------------------------
     */
    public function isAdmin()
    {
    	return $_SESSION['admin_info']['id'] == 1 || $_SESSION['admin_info']['role_id'] == 1;
    }
}
?>
