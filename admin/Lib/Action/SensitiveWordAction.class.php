<?php
/**
 * 敏感词类
 * @author feibo
 *
 */

class SensitiveWordAction extends BaseAction
{

    /**
	 +----------------------------------------------------------
	 * 敏感词列表
	 +----------------------------------------------------------
	 */
    public function index()
    {
        
        $sensitiveWord_mod = M('sensitive_word');
        $admin_mod = M('Admin');
        import('ORG.Util.Page');
        $is_ajax = isset($_REQUEST['is_ajax']) ? intval($_REQUEST['is_ajax']) : '';
        $sensitiveWord_type = $this->SW_type();
        $this->assign('sensitiveWord_type', $sensitiveWord_type);
        if ($_REQUEST['search'])
        {
            if (isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']))
            {
                $where['find'] = array(
                    'like' , 
                    "%" . $_REQUEST['keyword'] . "%"
                    );
                    $this->assign('keyword', $_REQUEST['keyword']);
            }
            if (isset($_REQUEST['type']) && trim($_REQUEST['type']))
            {

                if($_REQUEST['type'] !== 4)
                {
                    $where['type'] = $_REQUEST['type'];
   
                }
                $this->assign('type', $_REQUEST['type']);
            }
            $sensitiveWord_count = $sensitiveWord_mod->where($where)->count();
            $p = new Page($sensitiveWord_count, '50');
            $sensitiveWord_info = $sensitiveWord_mod->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
             
        }
        else
        {
            $sensitiveWord_count = $sensitiveWord_mod->count();
            $p = new Page($sensitiveWord_count, '50');
            $sensitiveWord_info = $sensitiveWord_mod->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
        }
        foreach ($sensitiveWord_info as $key=>$val)
        {
            $admin_info = $admin_mod->where('id = '.$val['uid'])->find();
            $sensitiveWord_info[$key]['admin'] = $admin_info['user_name'];
            $sensitiveWord_type_info = $sensitiveWord_type[$val['type']];
            $sensitiveWord_info[$key]['type'] = $sensitiveWord_type_info['name'];
            if ($val['type'] == 1)
            {
                $sensitiveWord_info[$key]['replacement'] = '无';
            }
        }
         
        $page = $p->show();
       
        if(!$is_ajax)
        {
            $this->assign('sensitiveWord_info', $sensitiveWord_info);
            $this->assign('keyword',$_REQUEST['keyword']);
            $this->assign('page', $page);
            $this->display();
        }
        else
        {
            $return_data['sensitiveWord_info'] = $sensitiveWord_info;
            $return_data['keyword'] = $_REQUEST['keyword'];
            $return_data['sensitiveWord_type'] =  $sensitiveWord_type;
            $return_data['page'] = $page;
            $return_data['count'] = $sensitiveWord_count;
            die(json_encode($return_data));
        }
    }
    
    /**
     +----------------------------------------------------------
     * 敏感词添加
     +----------------------------------------------------------
     */
    public function add()
    {
        $sensitiveWord_mod = M('Sensitive_word');
        if (isset($_POST['dosubmit']))
        {
            $find = trim($_POST['find']);
            $find = explode('，', $find);
            $data['type'] = intval($_POST['type']);
            switch ($data['type'])
            {
                case '1':
                    $data['replacement'] = '';
                    break;
                case '2':
                    $data['replacement'] = $_POST['replacement'];
                    break;
                case '3':
                    $data['replacement'] = '';
                    break;
                default:
                    $this->error(L('operation_failure'));
                    break;
            }
            $data['uid'] = $_SESSION['admin_info']['id'];
           
            foreach ($find as $key => $val)
            {
                $data['find'] = $val;
                $add_state = $sensitiveWord_mod->data($data)->add();
            }
            
            // 更新Redis缓存
            
            $redis_conf = C('REDIS_CONF');
            $redis_obj = Cache::getInstance('Redis', $redis_conf);
            $data  = M('sensitive_word')->select();
            $sensitive_word_data = serialize($data);
            $redis_obj->set('sensitive_word', $sensitive_word_data, 86400);
            if ($add_state)
            {
                $this->success(L(operation_success), '', '', 'add');
            }
            else
            {
                $this->error(L('operation_failure'));
            }
        }
        $sensitiveWord_type = $this->SW_type();
        $this->assign('sensitiveWord_type', $sensitiveWord_type);
        $this->display();
    }

     /**
     +----------------------------------------------------------
     * 编辑敏感词
     +----------------------------------------------------------
     */
    public function edit()
    {
        $sensitiveWord_mod = M('Sensitive_word');
        $is_ajax = isset($_REQUEST['is_ajax']) ? intval($_REQUEST['is_ajax']) : '';
        if (isset($_POST['dosubmit']))
        {
            $id = intval($_POST['id']);
            $data['find'] = trim($_POST['find']);
            $data['type'] = intval($_POST['type']);
            switch ($data['type'])
            {
                case '1':
                    $data['replacement'] = '';
                    break;
                case '2':
                    $data['replacement'] = trim($_POST['replacement']);
                    break;
                case '3':
                    $data['replacement'] = '';
                    break;
                default:
                    $this->error(L('operation_failure'));
                    break;
            }
            $update_status = $sensitiveWord_mod->where('id = ' . $id)->save($data);
            $redis_conf = C('REDIS_CONF');
            $redis_obj = Cache::getInstance('Redis', $redis_conf);
            $data  = M('sensitive_word')->select();
            $sensitive_word_data = serialize($data);
            $redis_obj->set('sensitive_word', $sensitive_word_data, 86400);
            if ($update_status == 0 || $update_status > 0)
            {
                if($is_ajax)
                {
                    $status['flag'] = true;
                    $status['msg'] = "更新成功！！";
                    die(json_encode($status));
                }
                $this->success(L('operation_success'), '', '', 'edit', TRUE);
            }
            else
            {
                if($is_ajax)
                {

                    $status['flag'] = false;
                    $status['msg'] = "更新失败！！";
                    die(json_encode($status));

                }
                $this->error(L('operation_failure'));
            }
        }
        $Sword_id = intval($_GET['id']);
        $Sword = $sensitiveWord_mod->where('id = ' . $Sword_id)->find();
        $sensitiveWord_type = $this->SW_type();

        if($is_ajax)
        {
            $return_data['sensitiveWord_type'] = $sensitiveWord_type;
            $return_data['Sword'] = $Sword;
            die(json_encode($return_data));
        }
        $this->assign('sensitiveWord_type', $sensitiveWord_type);
        $this->assign('Sword', $Sword);
        $this->display();
    }

     /**
     +----------------------------------------------------------
     * 删除敏感词
     +----------------------------------------------------------
     */
    public function delete()
    {
        $sensitiveWord_mod = M('Sensitive_word');
        $is_ajax = isset($_REQUEST['is_ajax']) ? intval($_REQUEST['is_ajax']) : '';
        if (! isset($_POST['id']))
        {
            $this->error(L('please_select'));
        }
        if (is_array($_POST['id']))
        {
            $id = implode(',', $_POST['id']);
        }
        else
        {
            $id = intval($_POST['id']);
        }
        $delete_status = $sensitiveWord_mod->delete($id);
        $redis_conf = C('REDIS_CONF');
        $redis_obj = Cache::getInstance('Redis', $redis_conf);
        $data  = M('sensitive_word')->select();
        $sensitive_word_data = serialize($data);
        $redis_obj->set('sensitive_word', $sensitive_word_data, 86400);
        if($is_ajax)
        {
            $status['flag'] = true;
            $status['msg'] = "删除成功！！";
            die(json_encode($status));
        }
        $this->success(L('operation_success'), '', '');
    }

    public function SW_type()
    {
        $type = C('SENSITIVE_WORD_TYPE');
        $sensitiveWord_type = array();
        foreach ($type as $key => $val)
        {

            switch ($val)
            {
                case 'forbidden':
                    $sensitiveWord_type[$key]['id'] = $key;
                    $sensitiveWord_type[$key]['name'] = '禁止';
                    $sensitiveWord_type[$key]['showtype'] = $val;
                    break;
                case 'replace':
                    $sensitiveWord_type[$key]['id'] = $key;
                    $sensitiveWord_type[$key]['name'] = '替换';
                    $sensitiveWord_type[$key]['showtype'] = $val;
                    break;
                case 'verify':
                    $sensitiveWord_type[$key]['id'] = $key;
                    $sensitiveWord_type[$key]['name'] = '过滤';
                    $sensitiveWord_type[$key]['showtype'] = $val;
                    break;
            }
        }
        return $sensitiveWord_type;
    }
}
