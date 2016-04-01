<?php
/**
 * 用户相关类
 * @author nangua
 *
 */
class UserAction extends BaseAction {

	/**
	 +----------------------------------------------------------
	 * 用户登录/注册（第三方平台） 1101
	 +----------------------------------------------------------
	 */
	public function userLogin() {
        $pf_type=intval($this->getFieldDefault('pf_type', 0));
        $openid=htmlspecialchars(trim($this->getField('openid','need openid',true)),ENT_QUOTES);
        $nickname=htmlspecialchars($this->getField('nickname'),ENT_QUOTES);
        $icon=htmlspecialchars($this->getField('icon'),ENT_QUOTES);
        $uid=$this->uid;
        if($uid>0) {
            //$response_code=C('RESPONSE_CODE');
        	//$this->errorView($response_code['user_status_is_logining'], 'user_status_is_logining');
        }
        $device_id = $this->cid;
        if($pf_type==0) {
        	//	无平台，未启用
        	$info['uid'] = 0;
        	$info['wskey'] = '';
        	$this->successView($info);
        }
        $mod_client=M('client');
        $mod_client_platform=M('client_platform');
        $platform=$mod_client_platform->where("openid='$openid' AND pf_type=$pf_type")->find();
        $wskey='';
        $client_id='';
        $now=time();

        if (empty($platform)) {
            //添加用户
//            $client_status=C('CLIENTS_STATUS');
            $wskey=A('Common')->getRandomString();
            $data_client=array(//添加用户
                'nickname'=>$nickname,
                'avatar'=>$icon,
                'status'=>'active',
                'wskey'=>$wskey,
                'create_time'=>$now,
                'update_time'=>$now,
                'last_login_time'=>$now,
                'last_device_id'=>$device_id,
                'pf_type'=>$pf_type
            );
            $client_id=$mod_client->add($data_client);
            if($client_id) {
                $data_client_platforms=array(//添加第三方用户
                    'client_id'=>$client_id,
                    'pf_type'=>$pf_type,
                    'nickname'=>$nickname,
                    'openid'=>$openid,
                    'icon'=>$icon,
                    'create_time'=>$now,
                );

                $mod_client_platform->add($data_client_platforms);
            }
            // 初次登录 电话号码为空
            $mobile = "";
        } else {
            $client_id=$platform['client_id'];

        	if($nickname!=$platform['nickname']||$icon!=$platform['icon']) {
        		$upd_platform=array(//更新第三方信息
        		    'openid'=>$openid,
        		    'nickname'=>$nickname,
        		    'icon'=>$icon,
        		    'id'=>$platform['id']
        		);
        		$mod_client_platform->save($upd_platform);
        		$upd_client=array(//更新用户信息
//        		    'nickname'=>$nickname,
//        		    'avatar'=>$icon,
        			'update_time'=>$now,
        			'last_login_time'=>$now,
        			'last_device_id'=>$this->cid,
        			'user_id'=>$client_id
        		);
        		$mod_client->save($upd_client);
        	} else {
        		$upd_client_login=array(//更新用户登录信息
        		    'last_login_time'=>$now,
        		    'last_device_id'=>$this->cid,
        			'user_id'=>$client_id
        		);
        	    $mod_client->save($upd_client_login);
        	}
        	$res = $mod_client->where("user_id = $client_id")->find();
            $nickname = htmlspecialchars_decode($res['nickname'],ENT_QUOTES);// 昵称直接取client表数据
            $icon     = $res['avatar'];// 头像直接取client表数据
        	$wskey=$res['wskey'];
        	if(empty($wskey)&&$uid==0) {
        		//修复wskey为空的bug,生成新的wskey
        
        		$wskey=A('Common')->getRandomString();
        		$upd_client_wskey=array(
        			'wskey'=>$wskey,
        			'user_id'=>$client_id
        		);
        		$mod_client->save($upd_client_wskey);
        	}
            // 获取电话号码信息
            $mobile = $mod_client->where("user_id = {$client_id}")->getField("mobile");
            $mobile = empty($mobile)?'':$mobile;
        }
        $info = array();
        $info['uid'] = $client_id;
        $info['wskey'] = $wskey;
        $info['avatar'] = $icon;
        $info['nickname'] = $nickname;
        $info['mobi_num'] = $mobile;
        // 说明是绑定过的用户 则更新时间
        if(!empty($mobile)){
            $mod_client->where("user_id = {$info['uid']}")->setField("last_login_time",time());
        }

        mergerCart($this->cid,$info['uid']);// 登录后将设备表中的购物车信息合并到该用户下
        $this->successView('',$info);
	}
    /**
    +----------------------------------------------------------
     * 手机号注册和登录 2102
    +----------------------------------------------------------
     */
    public function smslogin(){

        $password = $this->getFieldDefault('pwd', '');//密码
        $opt = intval($this->getFieldDefault('opt', 0));// 操作 0,登录；1，注册；2，忘记密码-完成
        $mobile = intval($this->getField('mobi_num'));//手机号
        $msg_code = intval($this->getFieldDefault('msg_code', ''));

        $key = 'ls_'.$mobile;
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis',$options);

        $user_mod = M('client');
        $auth_key = C('AUTH_KEY');
        $cache_code = intval($Reids->get($key));
        /*************** 登录 ********************/
        if($opt==0) {
            $password = md5($password.$auth_key);
            // 手机号码格式判断
            if(!preg_match("/1\d{10}$/",$mobile)){
                //验证不通过
                $this->errorView($this->ws_code['mobile_format_error'],$this->msg['mobile_format_error']);
            }
            // 检查 是否有该号码
            $check_user = $user_mod->where(array("mobile"=>$mobile))->count();
            if(empty($check_user)){
                $this->errorView($this->ws_code['forget_wrong_num'],$this->msg['forget_wrong_num']);
            }
            $check_user = $user_mod->where(array("mobile"=>$mobile,"password"=>$password))->field("user_id,mobile,wskey,avatar,nickname")->find();
            if(empty($check_user)){
                $this->errorView($this->ws_code['pwd_error'],$this->msg['pwd_error']);
            }
            // 更新用户的update_time last_device_id
            $user_login_update = array(
                "last_device_id"     => intval($this->cid),
                "last_login_time"    => time()
            );
            $user_mod->where("user_id = {$check_user['user_id']}")->save($user_login_update);
            // 返回登录信息
            $user = array(
                "uid"       => $check_user['user_id'],
                "mobi_num"  => $check_user['mobile'],
                "wskey"     => $check_user['wskey'],
                "avatar"    => $check_user['avatar'],
                "nickname"  => htmlspecialchars_decode($check_user['nickname'],ENT_QUOTES)
            );
            mergerCart($this->cid,$check_user['user_id']);// 登录后将设备表中的购物车信息合并到该用户下
            // 返回消息体
            $this->successView('',$user);

        }elseif($opt==1) {
            /*************** 注册 ********************/

            // 检查验证码 是否正确
            if($msg_code!=$cache_code||$msg_code==0){
                $this->errorView($this->ws_code['validate_error'],$this->msg['validate_error']);
            }
            // 验证码验证通过删除key
            $Reids->rm($key);
            // 判断密码
            $preg = '/^[a-zA-Z0-9]{6,16}$/';

            if(!preg_match($preg,$password)){
                $this->errorView($this->ws_code['pwd_validate_error'],$this->msg['pwd_validate_error']);
            }

            // 判断手机号码是否已经被注册
            $check_user = $user_mod->where(array("mobile"=>$mobile))->find();
            if(!empty($check_user)){
                $this->errorView($this->ws_code['repeat_mobile_error'],$this->msg['repeat_mobile_error']);
            }


            $icon = C('DEFAULT_ICON');
            $nickname = C("NICK_NAME");
            $now = time();


            $data = array(
                'nickname'          => $mobile,
                'mobile'            => $mobile,
                'avatar'            => $icon,
                'password'          => md5($password.$auth_key),
                'pf_type'           => 4,//手机注册
                'status'            => 'active',
                'wskey'             => A('Common')->getRandomString(),
                'create_time'       => $now,
                'update_time'       => $now,
                'last_login_time'   => $now,
                'last_device_id'    => $this->cid,
            );
            $user_id = $user_mod->add($data);
            if($user_id){
                $info = array();
                $info['uid']      = $user_id;
                $info['wskey']    = $data['wskey'];
                $info['mobi_num'] = $data['mobile'];
                $info['avatar']   = '';
                $info['nickname'] = $mobile;
                mergerCart($this->cid,$info['uid']);// 登录后将设备表中的购物车信息合并到该用户下
                $this->successView('',$info);

            }else{
                $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
            }

        }elseif($opt==2){
            /*************** 忘记密码 ********************/
            // 检查验证码 是否正确

            if($msg_code!=$cache_code||$msg_code==0){

                $this->errorView($this->ws_code['validate_error'],$this->msg['validate_error']);
            }
            // 验证码验证通过删除key
            $Reids->rm($key);
            // 判断密码
            $preg = '/^[a-zA-Z0-9]{6,16}$/';
            if(!preg_match($preg,$password)){
                $this->errorView($this->ws_code['pwd_validate_error'],$this->msg['pwd_validate_error']);
            }
            $check_user = $user_mod->where(array("mobile"=>$mobile))->field("user_id,mobile,wskey,avatar,nickname")->find();
            // 号码不存在
            if(empty($check_user)){
                $this->errorView($this->ws_code['forget_wrong_num'],$this->msg['forget_wrong_num']);
            }


            // 更新密码
            $data = array(
                'password'           => md5($password.$auth_key),
                "last_login_time"   => time(),
                "last_device_id"    => intval($this->cid)
            );
            // 更新密码
            $is_ok = $user_mod->where(array("mobile"=>$mobile))->save($data);

            if($is_ok||$is_ok===0){
                $info = array();
                $info['uid']      = $check_user['user_id'];
                $info['wskey']    = $check_user['wskey'];
                $info['mobi_num'] = $check_user['mobile'];
                $info['avatar']   = $check_user['avatar'];
                $info['nickname'] = $check_user['nickname'];
                mergerCart($this->cid,$info['uid']);
                $this->successView('',$info);
            }else{
                $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
            }
        }elseif($opt==3) {
            /*************** 绑定手机号码 ********************/
            // 检查验证码 是否正确

            if($msg_code!=$cache_code||$msg_code==0){
                $this->errorView($this->ws_code['validate_error'],$this->msg['validate_error']);
            }
            // 验证码验证通过删除key
            $Reids->rm($key);
            // 判断密码
            $preg = '/^[a-zA-Z0-9]{6,16}$/';
            if(!preg_match($preg,$password)){
                $this->errorView($this->ws_code['pwd_validate_error'],$this->msg['pwd_validate_error']);
            }
            $check_user = $user_mod->where(array("mobile"=>$mobile))->count();
            // 号码已存在
            if(!empty($check_user)){
                $this->errorView($this->ws_code['bind_mobile_error'],$this->msg['bind_mobile_error']);
            }
            // 更新用户表
            $data = array(
                "mobile"    => $mobile,
                "password"  => md5($password.$auth_key),
            );
            // 缺少 uid
            if($this->uid<=0){
                $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
            }

            $is_ok = $user_mod->where(array("user_id"=>$this->uid))->save($data);
            $check_user = $user_mod->where(array("mobile"=>$mobile))->field("user_id,wskey,mobile,avatar,nickname")->find();
            if($is_ok){
                $info = array();
                $info['uid']      = $this->uid;
                $info['wskey']    = $check_user['wskey'];
                $info['mobi_num'] = $check_user['mobile'];
                $info['avatar']   = $check_user['avatar'];
                $info['nickname'] = $check_user['nickname'];

                $this->successView('',$info);
            }else{
                $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
            }
        }
    }

    /**
     *判断用户是是否已经注册   2105
     *
     */
    public function isUserRegist(){
        $mobile = intval($this->getField('mobi_num'));//手机号
        $version = intval($this->getField('version'));//app的版本
        $user_mod = M('client');
        $message = array();//提示信息

        // 检查 是否有该号码
        $check_user = $user_mod->where(array("mobile"=>$mobile))->count();
        //对新版本的返回值进行兼容
        if($version < 18){
            if(empty($check_user) || (!$mobile)){
                //手机没有被注册
                $message = array('code'=>'300','mes'=>'false');
            }else{
                $message = array('code'=>'200','mes'=>'true');
            }
        }else{
            //version在18之后使用这个规范
            if(empty($check_user) || (!$mobile)){
                $message = array(
                    'rs_code' =>  1000,
                    'data' => array("code"=>0),
                    'rs_msg' =>'success'
                );
            }else{
                $message = array(
                    'rs_code' =>  1000,
                    'data' => array("code"=>1),
                    'rs_msg' =>'success'
                );
            }
        }

        echo json_encode($message);
    }
    /**
    +----------------------------------------------------------
     * 短信发送
     * @mobile 手机号
     * @content 短信内容
    +----------------------------------------------------------
     */
    public function sendSms($mobile='',$content=""){

        if(empty($mobile)||empty($content)){
            return false;
        }
        $wsdl = "http://211.147.239.62/Service/WebService.asmx?wsdl";
        //$wsdl = "http://211.147.239.62:8500/Service/WebService.asmx?wsdl";
        $client = new SoapClient($wsdl);
        $sms_config = C("SMS_CONF");
        $content = $sms_config['msg'].$content;
        //PostSingle发送短信方法：
        $param=array('account'=>$sms_config['account'],'password'=>$sms_config['password'],'mobile'=>$mobile,'content'=>$content,'subid'=>'');
        $ret = $client->PostSingle($param);
        Log::write("手机号码:".$mobile."内容是:".$content."响应消息".var_export($ret,true),Log::INFO);
    }
    /**
    +----------------------------------------------------------
     * 图形验证
    +----------------------------------------------------------
     */
    public function createCode(){
        import("ORG.Util.Code");
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis',$options);
        $code = new Code;
        $resource = $code->show($this->cid);
        $codes = $code->getCode();
        $code_cache = C("CODE_CACHE");
        $Reids->set($code_cache.$this->cid,$codes);
        return $resource;
    }

    /**
    +----------------------------------------------------------
     * 短信验证 2103
    +----------------------------------------------------------
     */
    public function smsverify()
    {
        //兼容过去版本实现对图形验证码的去除
        $version = $_GET['version'];
        if ($version > 16) {
            $varify_code = true;//去掉对图片的验证
            //$_GET['uid'] = 0;//兼容第三方登陆之后注册获取密码
        }else{
            $varify_code = $this->getFieldDefault('img_code', '');
        }
        $mobile = intval($this->getFieldDefault('mobi_num',0));
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis',$options);

        // 如果 验证码和电话没填 (一般发生在打开页面时加载)
        if($varify_code==''&&$mobile==0){
            $image = $this->createCode();
            $image = array(
                "img_bytes" =>base64_encode($image)
            );
            $this->successView('',$image);
        }
        // 如果电话号码填写，但未填写验证码，提示验证码需要填写
        if($varify_code==''&&$mobile!=0){
            $this->errorView($this->ws_code['validate_error'],$this->msg['validate_error']);
        }
        // 只是填了验证码,不填电话号码 发短信的情况
        if($varify_code!=''&&$mobile==0){
            $this->errorView($this->ws_code['mobile_miss'],$this->msg['mobile_miss']);
        }
        $code_cache = C("CODE_CACHE");
        $check_code = $Reids->get($code_cache.$this->cid);
		//将这块的代码进行注释，这里的图片验证码存入redis内
        if($version < 17) {
            if ($check_code != strtoupper($varify_code)) {
                $this->errorView($this->ws_code['img_code_error'], $this->msg['img_code_error']);
            }
        }
        $code_cache = C("CODE_CACHE");
        $Reids->rm($code_cache.$this->cid);

        $key = 'ls_'.$mobile;

        //生成验证码发送到用户手机

        $cache_code = mt_rand(100000,999999);
        // 发送短信
        $this->sendSms($mobile,$cache_code);
        $Reids->set($key,$cache_code,300);
        $this->successView();// 返回空信息

    }
    /**
    +----------------------------------------------------------
     * 用户反馈
    +----------------------------------------------------------
     */
    public function submitSuggest()
    {//用户反馈
        $user_mod = M('client');
        $feedback_mod = M('feedback');
        $version_mod = M('version');
        $default_author = '零食小喵';
        $content = $this->getFieldDefault('content', '');
        $os_type = $this->getFieldDefault('os_type', '');
        $version = $this->getFieldDefault('version', '');
        //$feedback_id = $this->getFieldDefault('id', 0);
        $content = htmlspecialchars(trim($content),ENT_QUOTES);//过滤特殊标签，防攻击
        $content = A('Common')->sqlCheck($content);
        $tbl['device_id'] = $this->cid;
        $tbl['client_id'] = $this->uid;
        $tbl['content'] = $content;
        $tbl['os_type'] = $os_type;
        $v = $version_mod->where("os_type=$os_type and version_innumber=$version")->getField('version_outnumber');
        $tbl['version'] = $v ? $v : 0;
        $tbl['create_time'] =time();
        $nick_name = $user_mod->where('user_id='.$tbl['client_id'])->getField('nickname');
        $tbl['nickname'] = $nick_name?$nick_name:"匿名用户_".$this->cid;
        $user_info = $user_mod->where("user_id=".$this->uid)->find();
        // 加入redis 缓存
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $set_key = C("LS_APP_CONFIG");// 缓存建名
        $expire = 60;
        $app_config = unserialize($redis->get($set_key));
        if(!$app_config){
            $site_mod = M("site_config");
            $app_config = $site_mod->field("service_qq,service_phone,ios_switch,gift_switch,version,feedback_notice")->find(1);
            $redis->set($set_key,serialize($app_config),$expire);
        }

        if($user_info&&empty($user_info->last_device_id))
        {
            $upd_client=array('last_device_id'=>$this->cid);
            $user_mod->where("user_id=".$this->uid)->save($upd_client);
        }
        if($content)
        {
            $res[0]['id']=$feedback_mod->add($tbl);
            $res[0]['type']=0;
            $res[0]['author']=A('Common')->getAuthorInfo($tbl['client_id']);
            $res[0]['content']=$tbl['content'];
            $info=array();
            $info['count']=count($res);
            $info['items']=$res;
        }
        else
        {
            $res=array();
            if($this->uid)
            {
                $res = $feedback_mod->where(" client_id = ".$this->uid)->select();
            } else {
                $res = $feedback_mod->where(" client_id=0 and device_id = ".$this->cid)->select();
            }
            $info=array();
            $info['items'] = array();
            $items = array();
            $info['count'] = count($res);
            $first_item = array();
            $first_item['type'] = 0;
            $first_item['content'] = $app_config['feedback_notice'] ? $app_config['feedback_notice'] : '喵亲，终于等到你。有什么不爽的统统告诉我吧！（欢迎来约：客服电话 010-57236350 手机 185006916205，微信 lingshixiaomiao，客服QQ 3206494235，微博@零食小喵）';
            $first_item['author']['uid'] = 0;
            $first_item['author']['nickname'] = $default_author;
            $first_item['author']['icon'] = '';
            $items[] = $first_item;

            //echo count($res);exit;
            if($info['count'])
            {

                foreach ($res as $key => $value)
                {
                    $tmp = array();
                    $tmp['type'] = 1;
                    $tmp['content'] = $value['content'];
                    $tmp['author'] = A('Common')->getAuthorInfo($value['client_id']);
                    $items[] = $tmp;
                    if($value['auid'])
                    {
                        $tmp = array();
                        $tmp['type'] = 0;
                        $tmp['content'] = $value['reply_content'];
                        $tmp['author']['uid'] = 0;
                        $tmp['author']['nickname'] = $default_author;
                        $tmp['author']['icon'] = '';
                        $items[] = $tmp;
                    }

                }
            }


            $info['items'] = $items;
        }
        $this->successView($items);
    }
	/**
	 +----------------------------------------------------------
	 * 用户反馈
	 +----------------------------------------------------------
	 */
	public function submitSuggest1() {
		$mod_client = M('client');
		$mod_feedback = M('feedback');
		$mod_version = M('version');

		$default_author = C('DEFAULT_LENG_AUTHOR');

   		$c = $this->getFieldDefault('content', '');
        $os_type = $this->getFieldDefault('os_type', '');
        $version = $this->getFieldDefault('version', '');
        //$feedback_id = $this->getFieldDefault('id', 0);
		$c = htmlspecialchars(trim($c),ENT_QUOTES);//过滤特殊标签，防攻击
		$c = A('Common')->sqlCheck($c);

        $tbl['device_id'] = $this->cid;
        $tbl['client_id'] = $this->uid;
        $tbl['content'] = $c;
        $tbl['os_type'] = $os_type;
        $v = $mod_version->where("os_type=$os_type and version_innumber=$version")->getField('version_outnumber');
        $tbl['version'] = $v ? $v : 0;
        $tbl['create_time'] =time();
        $nick_name = $mod_client->where('user_id='.$tbl['client_id'])->getField('nickname');

        $tbl['nickname'] = $nick_name?$nick_name:"小喵小伙伴";
        $user_info = $mod_client->where("user_id=".$this->uid)->find();
        if($user_info&&empty($user_info->last_device_id)) {
            $upd_client=array('last_device_id'=>$this->cid);
            $mod_client->where("user_id=".$this->uid)->save($upd_client);
        }

        if($c) {

        	$res[0]['id']=$mod_feedback->add($tbl);
        	$res[0]['type']=0;
        	$res[0]['author']=A('Common')->getAuthorInfo($tbl['client_id']);
        	$res[0]['content']=$tbl['content'];
        	$info=array();
        	$info['count']=count($res);
        	$info['items']=$res;
        } else {

            $res=array();
            if($this->uid) {
                $res = $mod_feedback->where(" client_id = ".$this->uid)->select();
            } else {
				$res = $mod_feedback->where(" client_id=0 and device_id = ".$this->cid)->select();
			}
        	$info=array();
            $info['items'] = array();
            $tmp = 0;
            $info['count'] = count($res);
            if($info['count']) {
                $items = array();
                foreach ($res as $key => $value) {
                    $items[$tmp]['type'] = 1;
                    $items[$tmp]['content'] = $value['content'];
                    $items[$tmp]['author'] = A('Common')->getAuthorInfo($value['client_id']);
                    $tmp++;
                    if($value['auid']) {

                    	$items[$tmp]['type'] = 0;
                   	 	$items[$tmp]['content'] = $value['replay_content'];
                    	$items[$tmp]['author'] = $default_author;
                    	$tmp++;
                    }

                }

            } else {
				 $items[0]['type'] = 0;
                 $items[0]['content'] = '';
                 $items[0]['author'] =$default_author;
        	}
        	$info['items'] = $items;
        }
        $this->successView($items,'');
	}
}