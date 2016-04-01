<?php
/**
 * 公共模块
 * @author feibo
 *
 */
class PublicAction extends BaseAction {

    /**
      +----------------------------------------------------------
     * 菜单页面
      +----------------------------------------------------------
     */
    public function menu() {
        //$this->checkUser();
        //显示菜单项
        $id = intval($_REQUEST['tag']) == 0 ? 4 : intval($_REQUEST['tag']);
        $menu = array();

        $role_id = D('admin')->where('id=' . $_SESSION['admin_info']['id'])->getField('role_id');
        $node_ids_res = D("access")->where("role_id=" . $role_id)->field("node_id")->select();

        $node_ids = array();
        foreach ($node_ids_res as $row)
        {
            array_push($node_ids, $row['node_id']);
        }

        //读取数据库模块列表生成菜单项
        $node = M("node");
        $where = "auth_type<>2 AND status=1 AND is_show=0 AND group_id=" . $id;
        $list = $node->where($where)->field('id,pid,action,action_name,module,module_name,data')->order('pid ASC,sort DESC')->select();
        $menu = array();
        foreach ($list as $key => $action)
        {
            $pid = intval($action['pid']);
            $id = intval($action['id']);
            $data_arg = array();
            if ($action['data'])
            {
                $data_arr = explode('&', $action['data']);
                foreach ($data_arr as $data_one)
                {
                    $data_one_arr = explode('=', $data_one);
                    $data_arg[$data_one_arr[0]] = $data_one_arr[1];
                }
            }
            $action['url'] = U(ucwords($action['module']) . '/' . $action['action'], $data_arg);
            if ($pid > 0)
            {
                $menu[$pid]['navs'][] = $action;
            } else {
                $menu[$id] = $action;
                $menu[$id]['name'] = $action['module_name'];
            }
        }

        $this->assign('menu', $menu);
        $this->display('');
    }

    /**
      +----------------------------------------------------------
     * 控制面板
      +----------------------------------------------------------
     */
    public function panel()
    {
        $security_info = array();
        if (is_dir(ROOT_PATH . "/install"))
        {
            $security_info[] = "强烈建议删除安装文件夹,点击<a href='" . u('public/delete_install') . "'>【删除】</a>";
        }
        if (APP_DEBUG == true)
        {
            $security_info[] = "强烈建议您网站上线后，建议关闭 DEBUG （前台错误提示）";
        }

        $this->assign('security_info', $security_info);

        $server_info = array(
            '操作系统' => PHP_OS ,
            '运行环境' => $_SERVER["SERVER_SOFTWARE"] ,
            //'PHP运行方式'=>php_sapi_name(),
            '上传附件限制' => ini_get('upload_max_filesize') ,
            '执行时间限制' => ini_get('max_execution_time') . '秒' ,
            //'服务器时间'=>date("Y年n月j日 H:i:s"),
            //'北京时间'=>gmdate("Y年n月j日 H:i:s",time()+8*3600),
            '服务器域名/IP' => $_SERVER['SERVER_NAME'] . ' [ ' . gethostbyname($_SERVER['SERVER_NAME']) . ' ]' ,
            '剩余空间' => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M'
        );
        $this->assign('server_info', $server_info);
        $role_mod = d('role');
        $res = $role_mod->where('id=' . $_SESSION['admin_info']['role_id'])->find();
        $this->assign('role', $res);
        $this->display();
    }

    public function login() {
       
        $admin_mod = M('admin');
        if ($_POST)
        {
            $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username']))  : '';
            $password = isset($_POST['password']) ? htmlspecialchars(trim($_POST['password']))  : '';
            if (empty($username) || empty($password)) {
                $this->redirect(u('Public/login'));
            }
            //生成认证条件
            $map = array();
            // 支持使用绑定帐号登录
            $map['user_name'] = $username;
            $map["status"] = array(
                'gt' ,
                0
            );
            $admin_info = $admin_mod->where("user_name='$username' and status=1")->find();
            //使用用户名、密码和状态的方式进行认证
            if (null === $admin_info) {
                $this->error('帐号不存在或已禁用！');
            } else {
                if ($admin_info['password'] != md5($password)) {
                    $this->error('密码错误！');
                }
				$data['last_time'] = time();
				$admin_mod->where("user_name='$username'")->save($data);
                $_SESSION['admin_info'] = $admin_info;
                if ($admin_info['user_name'] == 'admin') {
                    $_SESSION['administrator'] = true;
                }

                //跳转到首页
                $this->redirect(u('Index/index'));
            }
        }
        $this->display();
    }

    public function logout()
    {
        if (isset($_SESSION['admin_info'])) {
        	$sso_config=C('SSO_CONFIG');
        	$url = $sso_config['logout_url'].'?ci='.$sso_config['site_id'].'&rl=http://'.$_SERVER['HTTP_HOST'].'&access_token='.$_SESSION['admin_info']['login_id'];
        	unset($_SESSION['admin_info']);
            header("Location:$url");
        } else {
            $this->error('已经登出！');
        }
    }
  
    //单点登录
	public function sso()
	{
		if($_GET['rp'])
		{
			$this->sso_config=C('SSO_CONFIG');
			$rp=$_GET['rp'];
			$token=json_decode(base64_decode($rp));
			if(!$token||!$token->login_id||!$token->sg||!$token->tm)
			{
				exit('INVALID_RP');
			}
			if($this->isMatch($token->login_id,$token->sg,$token->tm))
			{
				$user_info=$this->getUserInfo($token->login_id);
				$user_info=json_decode(base64_decode($user_info['user_info']));
				$user_info->login_id=$token->login_id;
				return $user_info;
			}

		}
	}
	public function sign($src, $current_time=FALSE)
	{
	    if (!$current_time)
	    {
	        $current_time = time();
	    }
	    return md5($current_time . $src . $this->sso_config['sign_key']);
	}

	public function isMatch($src, $hash, $tm=FALSE)
	{
	    $signed = $this->sign($src, $tm);
	    if ($signed != $hash)
	    {
	        exit('INVALID_HASH');
	    }
	    if (abs(time() - $tm > $this->sso_config['time_span']))
	    {
        	exit('INVALID_TIME');
	    }
	    return TRUE;
	}
	public function getUserInfo($login_id)
	{
		$c=C('SSO_DB');
		$db = new PDO('mysql:host='.$c['DB_HOST'].';dbname='.$c['DB_NAME'],$c['DB_USER'],$c['DB_PWD']);
	    $db->exec("set names utf8");
	    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	    try {
	        $stmt = $db->prepare('select user_info from login_users where login_id=:login_id');
	        $stmt->bindParam(':login_id',$login_id,PDO::PARAM_INT);
	        $stmt->execute();
	        $data=$stmt->fetch(PDO::FETCH_ASSOC);
	    }catch(Exception $e) {
	    	echo $e->getMessage();
	        echo ' shit';
	    }
	    return $data;
	}
}

?>
