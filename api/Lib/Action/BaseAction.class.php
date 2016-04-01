<?php
/**
 * 基础类
 * @author feibo
 *
 */

class BaseAction extends Action {
    var $srv;//接口号
    var $cid;//设备id
    var $uid;//用户id
    var $os_type;//设备类型
    var $version;//内部版本号
    var $ws_code;
	var $msg;
    var $wscheck_init=array(1002,2506,2502,2503,2404,2414,2507,2216,2915,2916,2910,2108,2905,2901,2926,2925,2217,2218);//使用默认wskey的设备签名验证  需要分配 一个默认的cid
    var $uscheck_init=array();//使用默认wskey的用户签名验证
    var $uscheck_ignore=array(1002,2506,2502,2503,2507,2404,2414,2216,2915,2916,2910,2108,2905,2901,2926,2925,2217,2218,2105);//不进行用户验证()
    var $wsh5_init     = array(2506,2502,2503,2507,2404,2414,2216,2910,2108,2915,2916,2905,2901,2926,2925,2217,2218); // h5 页面需要忽略 验签
    /**
     +----------------------------------------------------------
     * 构造函数
     +----------------------------------------------------------
     */
    public function _initialize() {
    	$this->ws_code = C('WS_CODE'); // 成功与错误提示码
    	$this->msg     = C("MSG");// 错问提示信息
        $this->getParam();

    }
    
    /**
     +----------------------------------------------------------
     * 设备签名验证
     +----------------------------------------------------------
     */
    public function checkSig() {
        $srv=$this->srv;

        if(!$srv) {
            $this->errorView($this->ws_code['internal_error'], 'need srv');
        }

        if(in_array($srv, $this->wscheck_init))  {
        	//设备签名验证

            $this->wsCheckAuth(TRUE);
        } else {
            $this->wsCheckAuth();
        }
        // 不在忽略的用户,则验证
        if(!in_array($srv, $this->uscheck_ignore)) {//用户签名验证

            if(in_array($srv, $this->uscheck_init)) {

                $this->usCheckAuth(TRUE);
            } else {

                $this->usCheckAuth();
            }
        }
    }
    
    /**
     +----------------------------------------------------------
     * 获取参数
     +----------------------------------------------------------
     */
    public function getParam() {
        $api_no=C('API_NO');
       
        $this->srv=intval($this->getField('srv'));
        $this->cid=intval($this->getFieldDefault('cid',0));
        $this->os_type=intval($this->getField('os_type'));
        $this->version=intval($this->getField('version'));

        if(!isset($api_no[$this->srv])) {
            $this->errorView($this->ws_code['internal_error'], 'invalid srv');
        }
        if(!class_exists($api_no[$this->srv]['m'].'Action')) {

            $this->errorView($this->ws_code['internal_error'], 'invalid action_name');
        }
        if(!in_array($this->srv, $this->uscheck_ignore)) {
            $this->uid=intval($this->getFieldDefault('uid',0));
        }
    }
    
    /**
     +----------------------------------------------------------
     * 设备签名验证
     +----------------------------------------------------------
     */
    private function wsCheckAuth($use_init_wskeys = FALSE) {
        $ws_config = C('WS_CONFIG');

        if ($ws_config['ignore_wsauth']) {
            if (isset($_GET['cid']))
            {
                $token['cid'] = intval($_GET['cid']);
                return $token;
            }
            $this->errorView($this->ws_code['parameter_error'], 'need cid');
        }
        $token['cid'] = intval($this->getField('cid'));
        $token['tms'] = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
        $token['sig'] = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
        $cid = $token['cid'];
        $tms = substr($token['tms'], 0, 4) . '-' . substr($token['tms'], 4, 2) . '-' . substr($token['tms'], 6, 2) . ' ' . substr($token['tms'], 8, 2) . ':' .substr($token['tms'], 10, 2) . ':' . substr($token['tms'], 12, 2);
        $time = strtotime($tms);
        if ($time === FALSE) {
            $this->errorView($this->ws_code['parameter_error'], 'Invalid tms format');
        }
        $time_span = $ws_config['tms_max_span'];
        if ($time_span > 0 && abs(time() - $time) > $time_span) {
            $this->errorView($this->ws_code['parameter_error'], 'tms error。ref time：' . date('Y-m-d H:i:s', time()));
        }


        if ($use_init_wskeys) {
        	//使用默认签名

            if (isset($ws_config['init_wskeys'][$cid])) {
                $token['key'] = $ws_config['init_wskeys'][$cid];

                if(in_array($this->srv, $this->wsh5_init)){

                    $token['sig'] = substr(md5($token['key'] . $token['tms']),16);
                }
            }


        } else {
            $mod_device = M('device');

            $device = $mod_device->where("id=$cid")->find();

            $key='';
            if ($device) {
                $key = $device['wskey'];
            }
            $token['key'] = $key;
        }

        if (empty($token['key'])) {
            $this->errorView($this->ws_code['validate_error'], 'No such cid');
        }

        //验证
        $sig = substr(md5($token['key'] . $token['tms']),16);
        if ($sig == strtolower($token['sig'])){
            return $token;
        }

        $this->errorView($this->ws_code['validate_error'], 'Invalid sig');
    }
	
    /**
     +----------------------------------------------------------
     * 用户签名验证
     +----------------------------------------------------------
     */
    private function usCheckAuth($use_init_wskeys = FALSE, $user_must_wskeys = FALSE) {
        $us_config = C('US_CONFIG');
        if ($us_config['ignore_usauth']) {
            if (isset($_GET['uid'])) {
                $token['uid'] = intval($_GET['uid']);
                return $token;
            }
            $this->errorView($this->ws_code['parameter_error'], 'Invalid uid');
        }

        $token['uid'] = intval($this->getFieldDefault('uid',0));
        $token['tms'] = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
        $token['wssig'] = htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
        $uid = $token['uid'];
        $tms = substr($token['tms'], 0, 4) . '-' . substr($token['tms'], 4, 2) . '-' . substr($token['tms'], 6, 2) . ' ' . substr($token['tms'], 8, 2) . ':' .substr($token['tms'], 10, 2) . ':' . substr($token['tms'], 12, 2);
        $time = strtotime($tms);
        if ($time === FALSE) {
            $this->errorView($this->ws_code['parameter_error'], 'Invalid tms format');
        }
        $time_span = $us_config['tms_max_span'];
        if ($time_span > 0 && abs(time() - $time) > $time_span) {
            $this->errorView($this->ws_code['parameter_error'], 'tms error。ref time：' . date('Y-m-d H:i:s', time()));
        }
        $token['key']='';
        $token['last_cid']='';
        $token['cid']='';
        if ($use_init_wskeys || ($token['uid'] == 0 && ! $user_must_wskeys)) {
        	//使用默认签名
            if (isset($us_config['default_token']['key'])) {
                $token['key'] = $us_config['default_token']['key'];
                $token['tms'] = $us_config['default_token']['tms'];
                $token['wssig'] = $us_config['default_token']['wssig'];
            }

        } else {
            $mod_client = M('client');
            $key = '';

            if (! $key) {
                $key = $mod_client->field("wskey, last_device_id as last_cid")->where("user_id=$uid")->find();
            }
            $token['key'] = $key['wskey'];
            $token['last_cid'] = $key['last_cid'];
            $token['cid'] = isset($_GET['cid'])?$_GET['cid']:'';

        }
        if (empty($token['key'])) {
            $this->errorView($this->ws_code['validate_error'], 'No such uid');
        }

        //验证
        $wssig = strtolower(substr(md5($token['key'] . $token['tms']),16));

        if ($wssig == strtolower($token['wssig']))  {
            return $token;
        }
        $this->errorView($this->ws_code['validate_error'], 'Invalid wssig');
    }
    
    /**
     +----------------------------------------------------------
     * 错误信息输出
     +----------------------------------------------------------
     */
    public function errorView($rs_code, $re_msg='') {
    	$re_msg = empty($re_msg) ? 'can not empty' : $re_msg;
    	die(json_encode(array("rs_code"=>$rs_code,"rs_msg"=>$re_msg)));
    }
    
    /**
     +----------------------------------------------------------
     * 成功信息提示
     +----------------------------------------------------------
     */
    public function successView($items=array(),$other=array()) {
       
        $items = $items ? $items : array();
        $data = $items ? array('count'=>count($items),'items'=>$items) : array();
        $other = $other ? $other : array();
        $data = array_merge($data,$other);
    	$rs = array(
    	     "rs_code"=>$this->ws_code['success'],
    	     "data"=>$data?$data:new stdClass(),
             'rs_msg'=>'success',
    	);
    	die(json_encode($rs));
    }
    // test
    public function successView_test($items=array(),$other=array()) {


        $items = $items ? $items : array();
        $data = $items ? array('count'=>count($items),'cart'=>$items) : array();
        $other = $other ? $other : array();
        $data = array_merge($data,$other);
        $rs = array(
            "rs_code"=>$this->ws_code['success'],
            "data"=>$data?$data:new stdClass(),
            'rs_msg'=>'success',
        );
        die(json_encode($rs));
    }
    /**
     +----------------------------------------------------------
     * 参数字段验证(必须传值)
     +----------------------------------------------------------
     */
	public function getField($key,$msg='',$noempty=false) {
      
        $key = trim($key);
        if (! isset($_GET[$key])) {
            $msg = $msg ? $msg : 'need '.$key;
            $this->errorView($this->ws_code['parameter_error'], $msg);
        }
        if($noempty&&empty($_GET[$key])) {
            $msg= $msg ? $msg : 'need '.$key;
            $this->errorView($this->ws_code['parameter_error'], $msg);
        }
        return $_GET[$key];
    }
    
    /**
     +----------------------------------------------------------
     * 参数字段验证(可以不传)
     +----------------------------------------------------------
     */
    public function getFieldDefault($key,$default) {
        $key = trim($key);
        if (!isset($_GET[$key])||(isset($_GET[$key])&&!$_GET[$key])) {
            $_GET[$key] = $default;
        }
        return $_GET[$key];
    }
    
    /**
     +----------------------------------------------------------
     * 参数字段验证(必须传值)
     +----------------------------------------------------------
     */
    public function postField($key,$msg='',$noempty=false) {
        
        $key = trim($key);
        if (! isset($_POST[$key])) {
            $msg = $msg ? $msg : 'need '.$key;
            $this->errorView($this->ws_code['parameter_error'], $msg);
        }
   	 	if($noempty&&empty($_POST[$key])) {
            $msg = $msg ? $msg : 'need '.$key;
            $this->errorView($this->ws_code['parameter_error'], $msg);
        }
        return $_POST[$key];
    }
    
    /**
     +----------------------------------------------------------
     * 参数字段验证(可以不传)
     +----------------------------------------------------------
     */
    public function postFieldDefault($key,$default) {
        $key = trim($key);
        if (!isset($_POST[$key])||(isset($_POST[$key])&&!$_POST[$key]))
        {
            $_POST[$key] = $default;
        }
        return $_POST[$key];
    }
    
    /**
     +----------------------------------------------------------
     * 接口输出空值验证
     +----------------------------------------------------------
     */
    public function jugeNull($value) {
    	$msg = 'returns null data ';
    	if(empty($value)) {
			$this->errorView($this->ws_code['empty_error'], $msg);
    	}
    }
}
?>
