<?php

/**
 *
 * @author: nangua
 * @since: 2015-10-19
 */
class UserAction extends BaseAction{

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

    }

    /**
    +----------------------------------------------------------
     * 短信验证 请求
    +----------------------------------------------------------
     */
    public function smsverify(){
        $varify_code = $this->getFieldDefault('img_code', '');
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

        if($check_code!=strtoupper($varify_code)){
            $this->errorView($this->ws_code['img_code_error'],$this->msg['img_code_error']);
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
}