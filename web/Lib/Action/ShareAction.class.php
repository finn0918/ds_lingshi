<?php
/**
 * 分享
 * @author hhf
 */
class ShareAction extends BaseAction{

	/**
    +----------------------------------------------------------
     *  分享专题
    +----------------------------------------------------------
     */
    function themes_detail() {
    	$topicId = I('request.subject_id',0,'intval');
        $site_url = C("SITE_URL");
        $this->assign("site_url",$site_url);
    	$this->assign('subject_id',$topicId);
    	$this->display();
    }
	/**
	+----------------------------------------------------------
	 *  分享有礼分享页活动说明
	+----------------------------------------------------------
	 */
	public function rule(){
		$this->display();
	}
	/**
	+----------------------------------------------------------
	 *  分享有礼分享页
	+----------------------------------------------------------
	 */
	public function active(){
		// 根据code 查
        $this->assign('site_url',C('SITE_URL'));
		$this->display();
	}
	/**
	+----------------------------------------------------------
	 *  分享有礼结果页面
	+----------------------------------------------------------
	 */
	public function result(){
		//

	}
    /**
    +----------------------------------------------------------
     *  提前插入分享记录
    +----------------------------------------------------------
     */
    public function setShareLog(){
        $share_log_mod = M("share_log");
        $share_gift_mod = M("share_gift");
        $client_mod = M("client");
        $site_config_mod = M("site_config");
        $coupon_num_mod = M("coupon");
        $now = time();
        $today = strtotime("today");
        $uid = $_GET['uid'];

        $str = $uid."20190910".$uid."xiaomiao";
        $auth_code = md5($str);

        $flag = true;
        $info = $auth_code;

        $share_time_arr = $share_log_mod->where("auth_code = '{$auth_code}' ")->getField("create_time",true);
        if(!empty($share_time_arr)){
            // 避免sql 排序
            $max_time = max($share_time_arr);
            // 说明今天还没有分享记录
            if($max_time<$today){
                // 插入分享记录
                $data = array(
                    "user_id"       => $uid,
                    "auth_code"     => $auth_code,
                    "create_time"   => $now,
                    "type"           => 1
                );
                // 开启事务
                $share_log_mod->startTrans();
                if($share_log_mod->add($data)){
                    // share_gif 记录
                    $self_mobile = $client_mod->where("user_id = {$uid}")->getField("mobile");
                    // 获取首次分享的奖励金额
                    $self_share = $site_config_mod->where("id = 1")->getField("self_share");

                    $money = $coupon_num_mod->where("coupon_num = '{$self_share}' ")->getField("rule_free");
                    $update_data = array(
                        "self_mobile"   => $self_mobile,
                        "user_id"       => $uid,
                        "coupon_num"    => $self_share,
                        "money"         => $money,
                        "type"          => 1,
                        "add_time"      => time(),
                        "friend_mobile" => "0"
                    );

                    Log::write("记录分享没插入的数据".var_export($update_data,true),Log::INFO);

                    if($share_gift_mod->add($update_data)){
                        $this->_sendCoupon($uid);
                    }else{
                        $flag = false;
                        $info = 'sharegift添加失败';
                        $share_log_mod->rollback();
                    }
                } else {
                    $flag = false;
                    $info = 'sharelog添加失败';
                    $share_log_mod->rollback();
                }
                $share_log_mod->commit();
            }
        } else {
            // 新记录 插入分享记录
            $data = array(
                "user_id"       => $uid,
                "auth_code"     => $auth_code,
                "create_time"   => $now,
                "type"          => 1
            );
            // 开启事务
            $share_log_mod->startTrans();
            if($share_log_mod->add($data)){
                // share_gif 记录
                $self_mobile = $client_mod->where("user_id = {$uid}")->getField("mobile");
                // 获取首次分享的奖励金额
                $self_share = $site_config_mod->where("id = 1")->getField("self_share");

                $money = $coupon_num_mod->where("coupon_num = '{$self_share}' ")->getField("rule_free");
                $update_data = array(
                    "self_mobile"   => $self_mobile,
                    "user_id"       => $uid,
                    "coupon_num"    => $self_share,
                    "money"         => $money,
                    "type"          => 1,
                    "add_time"      => time(),
                    "friend_mobile" => "0"
                );

                Log::write("记录分享没插入的数据".var_export($update_data,true),Log::INFO);

                if($share_gift_mod->add($update_data)){
                    $this->_sendCoupon($uid);
                }else{
                    $flag = false;
                    $info = 'sharegift添加失败';
                    $share_log_mod->rollback();
                }
            } else {
                $flag = false;
                $info = 'sharelog添加失败';
                $share_log_mod->rollback();
            }
            $share_log_mod->commit();
        }

        $rs['flag'] = $flag;
        $rs['info'] = $info;

        $this->ajaxReturn($rs,'json');
    }

    // 发放优惠券
    private function _sendCoupon($uid){
        $site_config_mod = M('site_config');
        $self_share = $site_config_mod->where("id = 1")->getField("self_share");
        $coupon_user_mod = M("coupon_user");
        $data = array(
            "user_id"       => $uid,
            "coupon_num"   => $self_share,
            "is_show"       => 1,
            "is_use"        => 0
        );
        if(!$coupon_user_mod->add($data)){
            Log::write("用户uid=>$uid 的优惠券没发放成功",Log::ERR);
        }
    }
	/**
	+----------------------------------------------------------
	 *  发送验证码 + 短信页面
	+----------------------------------------------------------
	 */
	public function message(){
		/*
		 * 消息发送页面，判断用户是否是已注册用户。注册了的需发验证码通过，才能领取
		 * 未注册的需要填写密码
		 */
		$mobile = intval($this->getFieldDefault('mobi_num',0));
        $is_ajax = intval($this->getFieldDefault('is_ajax',0));
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis', $options);
        $key = 'ls_' . $mobile;
        $oldRedis = $Reids->get($key);
        if($is_ajax==1) {
            if(!$oldRedis) {
                if (!preg_match("/1\d{10}$/", $mobile)) {
                    //验证不通过
                    $rs['flag'] = false;
                    $rs['message'] = '请输入正确的手机号';
                } else {
                    $cache_code = mt_rand(100000, 999999);
                    $usreAction = A("User");
                    $usreAction->sendSms($mobile, $cache_code);
                    $Reids->set($key, $cache_code, 60);
                    Log::write('手机号:'.$mobile.'发送验证码：'.$cache_code, Log::INFO);
                    $rs['flag'] = true;
                    $rs['message'] = '发送成功';
                }
            } else {
                $rs['flag'] = false;
                $rs['message'] = '不可重复获取验证码';
            }
            $this->ajaxReturn($rs,'json');
        } else {
            $this->assign('site_url',C('SITE_URL'));
            $this->display();
        }
	}
	/**
    +----------------------------------------------------------
     *  分享商品
    +----------------------------------------------------------
     */
    function pro_detail() {
    	$spuId = I('request.spu_id',0,'intval');
    	$this->assign('spu_id',$spuId);
    	$this->display();
    }
    /**
    +----------------------------------------------------------
     *  分享大礼包
    +----------------------------------------------------------
     */
    public function gift_share(){
        $spuId = I('request.gift_id',0,'intval');
        $this->assign('gift_id',$spuId);
        $this->display();
    }
    /**
    +----------------------------------------------------------
     *  微信分享
    +----------------------------------------------------------
     */
	function signPackage () {
		require_once "web/Lib/wx/jssdk.php";
		$jssdk = new JSSDK("wxc98bbd7d8484e946", "63e083b505dfa59ebdadd5450e334b63");
		$signPackage = $jssdk->GetSignPackage();
		die(json_encode($signPackage));
	}

    /**
    +----------------------------------------------------------
     * 随机生成的签名字符串
    +----------------------------------------------------------
     */
    public function getRandomString() {
        $length=16;
        $chars = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9";
        $chars_array = explode(',', $chars);
        $charsLen = count($chars_array) - 1;
        shuffle($chars_array);
        $output = '';
        for ($i = 0; $i < $length; $i ++) {
            $output .= $chars_array[mt_rand(0, $charsLen)];
        }
        return $output;
    }
    /**
    +----------------------------------------------------------
     *  用户注册
    +----------------------------------------------------------
     */
    function setRegister() {
        $password = $this->getFieldDefault('pwd', '');//密码
        $mobile = intval($this->getField('mobi_num'));//手机号
        $msg_code = intval($this->getFieldDefault('msg_code', ''));
        $secret = $this->getFieldDefault('secret', '');//秘钥

        $key = 'ls_'.$mobile;
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis',$options);

        $user_mod = M('client');
        $auth_key = C('AUTH_KEY');
        $cache_code = intval($Reids->get($key));

        if($secret==''){
            $rs['flag'] = false;
            $rs['message'] = '缺少秘钥';
            $this->ajaxReturn($rs,'json');
            exit;
        }
        // 检查验证码 是否正确
        if($msg_code!=$cache_code || $msg_code==0){
            $rs['flag'] = false;
            $rs['message'] = '验证码错误';
            $this->ajaxReturn($rs,'json');
            exit;
        }
        // 验证码验证通过删除key
        $Reids->rm($key);

        // 判断密码
        $preg = '/^[a-zA-Z0-9]{6,16}$/';

        // 验证手机号
        if(!preg_match($preg,$password)){
            $rs['flag'] = false;
            $rs['message'] = '密码由6-16位字母或数字组成';
            $this->ajaxReturn($rs,'json');
            exit;
        }
        // 判断手机号码是否已经被注册
        $check_user = $user_mod->where(array("mobile"=>$mobile))->find();
        if(!empty($check_user)){
            $rs['flag'] = false;
            $rs['message'] = '该手机号已被注册';
            $this->ajaxReturn($rs,'json');
            exit;
        }

        $icon = C('DEFAULT_ICON');
        $nickname = C("NICK_NAME");
        $now = time();

        $data = array(
            'nickname'          => $mobile,
            'mobile'            => $mobile,
            'avatar'            => 'http://ds.lingshi.cccwei.com/statics/default.png',
            'password'          => md5($password.$auth_key),
            'pf_type'           => 4, //手机注册
            'status'            => 'active',
            'wskey'             => $this->getRandomString(),
            'create_time'       => $now,
            'update_time'       => $now,
            'last_login_time'   => $now,
            'last_device_id'    => '0',
        );

        $user_id = $user_mod->add($data);

        if($user_id) {
            //去领礼盒
            $this->checkGiftbox($mobile,$secret);
        }
    }
    /**
    +----------------------------------------------------------
     *  用户登录(验证码登录)
    +----------------------------------------------------------
     */
    function checkLogin() {
        $mobile = intval($this->getField('mobi_num')); //手机号
        $msg_code = intval($this->getFieldDefault('msg_code', '')); //验证码
        $secret = $this->getFieldDefault('secret', ''); //秘钥

        Log::write('登录接收的参数:手机['.$mobile.'],验证码['.$msg_code.'],秘钥['.$secret.']', Log::INFO);

        $key = 'ls_'.$mobile;
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis',$options);

        $user_mod = M('client');
        $auth_key = C('AUTH_KEY');
        $cache_code = intval($Reids->get($key));

        if($secret==''){

            Log::write('缺少秘钥:', Log::INFO);

            $rs['flag'] = false;
            $rs['message'] = '缺少秘钥';
            $this->ajaxReturn($rs,'json');
            exit;
        }
        // 检查验证码 是否正确
        if($msg_code!=$cache_code || $msg_code==0){

            Log::write('验证码错:'.$cache_code.','.$msg_code, Log::INFO);

            $rs['flag'] = false;
            $rs['message'] = '验证码错误';
            $this->ajaxReturn($rs,'json');
            exit;
        }

        // 验证码验证通过删除key
        $Reids->rm($key);

        $this->checkGiftbox($mobile,$secret);
    }
    /**
    +----------------------------------------------------------
     *  检测手机号是否注册
    +----------------------------------------------------------
     */
    function checkMobile() {
        $clientMod = M('client');

        $mobileNumber = $_GET['mobi_num'];
        if(isset($mobileNumber) && !empty($mobileNumber)) {
            $clientData = $clientMod->where("mobile=$mobileNumber")->find();
            if($clientData) {
                $rs['flag'] = true;
                $rs['code'] = '200';
                $rs['message'] = '已注册用户';
            } else {
                $rs['flag'] = true;
                $rs['code'] = '201';
                $rs['message'] = '未注册用户';
            }
        } else {
            $rs['flag'] = false;
            $rs['code'] = '203';
            $rs['message'] = '手机号不能为空';
        }
        $this->ajaxReturn($rs,'json');
    }
    /**
    +----------------------------------------------------------
     *  领取礼盒
    +----------------------------------------------------------
     */
    public function getGiftbox($uid) {
        $giftUserMod = M('gift_user');
        $couponGiftMod = M('coupon_gift');
        $couGiftRelationMod = M('cou_gift_relation');
        $couponUserMod = M('coupon_user');

        $userId = $uid;
        $giftId = $couponGiftMod->where("status=1")->getField('id');
        //礼盒去重
        $checkGift = $giftUserMod->where("user_id=$userId AND gift_id=$giftId")->select();
        if($checkGift) {
            $rs['flag'] = false;
            $rs['message'] = "已领过礼盒";
        } else {
            //添加优惠券记录(领取礼盒)
            if($giftId && $userId) {
                $couGiftRelationData = $couGiftRelationMod->where("gift_id=$giftId")->select();
                $model = M();
                $model->startTrans();
                //先将礼包的优惠券给用户
                $sql = "INSERT INTO ls_coupon_user (user_id,coupon_num) VALUES ";
                foreach ($couGiftRelationData as $v) {
                    $tmp = str_repeat("($userId,'{$v['coupon_num']}'),",$v['num']);
                    $sql .= $tmp;
                }
                $sql = rtrim($sql,",");
                if($model->execute($sql)){
                    // 全部给完再插入关系表
                    $gift_data = array(
                        "user_id"  => $userId,
                        "gift_id"  => $giftId
                    );
                    if($giftUserMod->add($gift_data)){
                        $model->commit();
                        $rs['flag'] = true;
                        $rs['message'] = "成功领取礼盒";
                    }else{
                        $model->rollback();
                        $rs['flag'] = false;
                        $rs['message'] = "礼盒领取失败";
                        Log::write('礼盒领取失败'.$giftUserMod->getLastSql(), Log::INFO);
                    }
                }else{
                    $model->rollback();
                    $rs['flag'] = false;
                    $rs['message'] = "礼盒的优惠券领取失败";
                    Log::write('礼盒的优惠券领取失败'.$sql, Log::INFO);
                }
            }
        }

        return $rs;
    }
    /**
    +----------------------------------------------------------
     *  领取优惠券
    +----------------------------------------------------------
     */
    public function getCoupon($secret, $friendMobile) {
        $shareGiftMod = M('share_gift');
        $shareLogMod = M('share_log');
        $clientMod = M('client');
        $siteConfigMod = M('site_config');
        $couponMod = M('coupon');
        $couponUserMod = M('coupon_user');

        if(isset($secret) && !empty($secret)) {
            $userWhere['auth_code'] = $secret;
            $userId = $shareLogMod->where($userWhere)->getField('user_id');
            $selfMobile = $clientMod->where("user_id=$userId")->getField('mobile');
            $couponNum = $siteConfigMod->getField('friend_share');
            $moneyWhere['coupon_num'] = $couponNum;
            $money = $couponMod->where($moneyWhere)->getField('rule_free');
            if(isset($friendMobile) && !empty($friendMobile)) {
                if($selfMobile == $friendMobile) {
                    //这个地方是true没错
                    $rs['flag'] = true;
                    $rs['message'] = '自己领礼包不加优惠券';
                    Log::write('自己领礼包不加优惠券', Log::INFO);
                } else {
                    //添加优惠券记录(领取优惠券)
                    $shareGiftData = array(
                        self_mobile => $selfMobile,
                        user_id => $userId,
                        money => $money,
                        coupon_num => $couponNum,
                        type => '2',
                        add_time => time(),
                        friend_mobile => $friendMobile
                    );
                    $model = M();
                    $model->startTrans();
                    $addShareCoupon = $shareGiftMod->add($shareGiftData);
                    Log::write('分享者的优惠券关系:'.$shareGiftMod->getLastSql(), Log::INFO);
                    if($addShareCoupon === false) {
                        //优惠券领取失败
                        $rs['flag'] = false;
                        $rs['message'] = '优惠券关系添加失败';
                        $model->rollback();
                    } else {
                        $couponUserData = array(
                            'user_id' => $userId,
                            'coupon_num' => $couponNum,
                            'send_time' => time(),
                            'is_show' => 1,
                            'is_use' => 0
                        );
                        $couponUserAdd = $couponUserMod->add($couponUserData);
                        if($couponUserAdd) {
                            $rs['flag'] = true;
                            $rs['message'] = '优惠券领取成功';
                            $model->commit();
                        } else {
                            $rs['flag'] = false;
                            $rs['message'] = '优惠券领取失败';
                            Log::write('优惠券添加失败：'.$couponUserMod->getLastSql(), Log::INFO);
                            $model->rollback();
                        }
                    }
                }
            } else {
                $rs['flag'] = false;
                $rs['message'] = '缺少参数';
            }
        }

        return $rs;
    }
    /**
    +----------------------------------------------------------
     *  领取礼盒主流程
    +----------------------------------------------------------
     */
    public function checkGiftbox($mobileNumber,$secret) {
        $clientMod = M('client');
        Log::write('查询语句:'.$mobileNumber.','.$secret, Log::INFO);
        if(isset($mobileNumber) && !empty($mobileNumber)) {
            $clientData = $clientMod->where("mobile=$mobileNumber")->find();
            if($clientData) {
                $userId = $clientData['user_id'];
                if($userId) {
                    //领取礼盒
                    $getGift = $this->getGiftbox($userId);
                    if($getGift['flag']) {
                        //给分享者加优惠券
                        $getCoupon = $this->getCoupon($secret, $mobileNumber);
                        if($getCoupon['flag']) {
                            $rs['flag'] = true;
                            $rs['message'] = '流程成功';
                        } else {
                            $rs['flag'] = false;
                            $rs['message'] = $getCoupon['message'];
                        }
                    } else {
                        $rs['flag'] = false;
                        $rs['message'] = $getGift['message'];
                    }
                } else {
                    $rs['flag'] = false;
                    $rs['message'] = '用户信息错误';
                }
            } else {
                $rs['flag'] = false;
                $rs['message'] = '未注册用户';
            }
        } else {
            $rs['flag'] = false;
            $rs['message'] = '手机号不能为空';
        }
        $this->ajaxReturn($rs,'json');
    }
}
?>