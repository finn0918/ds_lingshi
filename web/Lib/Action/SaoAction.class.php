<?php
/**
 * 扫码活动
 * @author wyh
 */
class SaoAction extends BaseAction {
	public function getUserInfo() {
		if(!$_COOKIE['openId'])
		{
			$appId = 'wxc98bbd7d8484e946';
			$secret = '63e083b505dfa59ebdadd5450e334b63';
			$host = $_SERVER['HTTP_HOST'];
			$jumpurl = 'http://'.$host.'/web.php?m=Sao&a=index';
			if(!isset($_GET['code'])) {
				header("location:https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appId&redirect_uri=$jumpurl&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect");
			}else {
				$code = $_GET['code'];
			}
			// 通过code换取网页授权access_token
			$getAss = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appId&secret=$secret&code=$code&grant_type=authorization_code";
			$json_data = file_get_contents($getAss);
			$obj_data = json_decode($json_data,true);
			$access_token = $obj_data['access_token'];
			$openId = $obj_data['openid'];
			// 拉取用户信息(需scope为 snsapi_userinfo)
			$urlInfo = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openId&lang=zh_CN";
			$json_data2 = file_get_contents($urlInfo);
			$obj_data2 = json_decode($json_data2,true);
			setCookie("openId",$openId);
		}
	}
	/**
    +----------------------------------------------------------
     * 随机生成的签名字符串
    +----------------------------------------------------------
     */
    public function getRandomString($len) {
        $chars = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9";
        $chars_array = explode(',', $chars);

        $charsLen = count($chars_array) - 1;
        shuffle($chars_array);
        $output = '';
        for ($i = 0; $i < $len; $i ++) {
            $output .= $chars_array[mt_rand(0, $charsLen)];
        }
        return $output;
    }
	public function index() {
		$this->getUserInfo();
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
	 *  根据openId查找用户手机
	+----------------------------------------------------------
	 */
	 public function getMobile() {
		$ClientMod = M('client_wechat');
		$UserMod = M('client');
		$openId = $_GET['open_id'] ? $_GET['open_id'] : 0;
		setcookie('openId',$openId);
		$userId = $ClientMod->where("open_id='$openId'")->getField('user_id');
		$mobi_num = $UserMod->where("user_id=$userId")->getField('mobile');  //可能存在多个手机号的可能
		if($mobi_num) {
			$res['flag'] = true;
			$res['mobile'] = $mobi_num;
			$res['message'] = '查询成功';
		}else {
			$res['flag'] = false;
			$res['mobile'] = '';
			$res['message'] = '没有记录'; 
		}
		$this->ajaxReturn($res,'json');
	 }
	/**
	+----------------------------------------------------------
	 *  判断是否领取当天礼包
	+----------------------------------------------------------
	 */
	public function checkGift() {
		$ActiveMod = M('activity');
		
		//本地测试环境
		//$mobile = intval($this->getFieldDefault('mobile',''));
		$mobile = $this->getFieldDefault('mobile','');
		$is_ajax = intval($this->getFieldDefault('is_ajax',0));
		
		if($is_ajax == 1) {
			// 查询当前活动表中的创建时间
			$create_time = $ActiveMod->where("mobile='$mobile'")->getField('create_time');
			if(!empty($create_time)) {
				$rs['flag'] = true;
				$rs['message'] = '';
			}
		}else {
			$rs['flag'] = false;
			$rs['message'] = 'is_ajax参数不正确';
		}
		$this->ajaxReturn($rs,'json');
	}
	/**
	+----------------------------------------------------------
	 *  打乱数组顺序
	+----------------------------------------------------------
	 */
	public function get_rand($proArr) {
		$result = ''; 
		//概率数组的总概率精度 
		$proSum = array_sum($proArr); 
		//概率数组循环 
		foreach ($proArr as $key => $proCur) { 
			$randNum = mt_rand(1, $proSum); 
			if ($randNum <= $proCur) { 
				$result = $key; 
				break; 
			} else { 
				$proSum -= $proCur; 
			} 
		} 
		unset ($proArr); 
		return $result; 
	}
	/**
	+----------------------------------------------------------
	 *  领取礼包
	+----------------------------------------------------------
	 */
	 public function getGift() {
		$CouponMod = M('coupon');
		$CouponUserMod = M('coupon_user');
		$ActiveMod = M('activity');
		$UserMod = M('client');
		
		$is_ajax = intval($this->getFieldDefault('is_ajax',0));
		$mobile = $this->getFieldDefault('mobile','');
		$begin = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$end = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		$userId = $UserMod->where("mobile='$mobile'")->getField('user_id');
		
		// id为奖项，prize为描述，rate为概率
		$prize_arr = array(
			'0' => array(
				'id'	=> 1,
				'prize' => '20元优惠劵',
				'rate'	=> 5
			), 
			'1' => array(
				'id'	=> 2,
				'prize' => '10元优惠劵',
				'rate'	=> 10
			), 
			'2' => array(
				'id'	=> 3,
				'prize'	=> '5元优惠劵',
				'rate'	=> 50
			), 
			'3' => array(
				'id'	=> 4,
				'prize' => '3元优惠劵',
				'rate'	=> 15
			),
			'4' => array(
				'id'	=> 5,
				'prize' => '',
				'rate'	=> 20
			)
		);
		foreach ($prize_arr as $key => $val) { 
			$arr[$val['id']] = $val['rate']; 
		} 
		$rid = $this->get_rand($arr); //根据概率获取奖项id 
		//echo $rid;
		// 在表格中插入中间数据
		$where['create_time'] = array('between',"$begin,$end");
		$where['user_id'] = $userId;
		//$userData = $ActiveMod->where($where)->find();
		$giftId = $ActiveMod->where($where)->getField('gift_id');
		
		//祝福语
		$blessArray = array(
			"0" => "今天吃喝不努力，明天努力找吃喝。",
			"1" => "吃货的座右铭：Just eat it !",
			"2" => "吃货的思路是什么？好吃你就多吃点，不好吃多少也要吃点。",
			"3" => "据说，吃货，都不会 挂科…因为，吃货太重，挂不住…",
			"4" => "偷吃不是你的错，是嘴巴的寂寞！",
			"5" => "读万卷书，吃万里路！",
			"6" => "要坚信，只要活着就一定会遇到好吃的！",
			"7" => "吃东西最不累，最适合小主你啦!",
			"8" => "空有一颗想减肥的心，偏偏生了一条吃货的命!",
			"9" => "Life is like a box of chocolates：you never know what you’re gonna get!《阿甘正传》",
			"10" => "我在人民广场吃着炸鸡  而此时此刻你在哪里……",
			"11" => "我愿似一块扣肉，我愿似一块扣肉，我愿似一块扣肉，扣住你梅菜扣住你手！",
			"12" => "深情告白的肯定是个吃货——烧肉粽，如果你想吃不需要等到端午节；烧肉粽，你要酸的甜的苦的辣的随时给你！",
			"13" => "吃在人，命在天，亘古滔滔转眼间，唯席上，千年丰盛永不变！",
			"14" => "不开心睡一觉，就让他过去吧，伤心还好，伤胃就不好了。——麦兜",
			"15" => "当你在朋友圈发你和美食的照并获赞时，那些赞不是点给你的，是点给美食哒！",
			"16" => "一天吃五块巧克力就好像在天堂一样！——樱桃小丸子",
			"17" => "让我们红尘作伴吃的白白胖胖。",
			"18" => "据说这就是吃货狂吃时的状态：嘴里很享受，心里很想瘦。",
			"19" => "吃货的人生就像一列火车，总结起来就是，逛吃，逛吃，逛吃。",
			"20" => "冰冻三尺非一日之寒，小腹三层非一日之馋。",
			"21" => "人生就像烙饼，得翻够了回合才能成熟。",
			"22" => "今天天气很好，在房间里宅久了，准备去客厅散散心。",
			"23" => "青春一去不复返，我祝它旅途愉快。",
			"24" => "青春是最棒的——致最爱吃的你！"
		);
		
		if($giftId) {
			$res['flag'] = false;
			$res['prize'] = intval($giftId);
			$res['user_id'] = $userId; //祝福语奖项
			$res['result'] = '当天已经领取过优惠劵';	

			if(empty($_COOKIE["$userId"."bs"])) {
				$res['message'] = $blessArray[mt_rand(0,count($blessArray)-1)];	
			}else {
				$res['message'] = '';
			}
		}else {
			if($rid == 5) {
				$bless = $blessArray[mt_rand(0,count($blessArray)-1)];
				$res['flag'] = true;
				$res['prize'] = $rid;
				$res['message'] = $bless; //祝福语奖项
				$res['user_id'] = $userId; //祝福语奖项
				setCookie($userId.'bs',$bless);
				
				$data['user_id'] = $userId;
				$data['gift_id'] = $rid;
				$data['mobile'] = $mobile;
				$data['create_time'] = time();
				$giftData = $ActiveMod->add($data);
			} else {
				// 强制关联优惠劵明码
				switch ($rid) {
					case 1 : 
						$tmp['id'] = 108 ;
					break;
					case 2 : 
						$tmp['id'] = 109 ;
					break;
					case 3 : 
						$tmp['id'] = 110 ;
					break;
					case 4 : 
						$tmp['id'] = 111 ;
					break;
				}
				$counponNum = $CouponMod->where($tmp)->getField('coupon_num');
				$cdata['coupon_num'] = $counponNum;
				$cdata['user_id'] = $userId;
				$cdata['send_time'] = time();
				$addCounpontoUser = $CouponUserMod->add($cdata);

				$data['user_id'] = $userId;
				$data['gift_id'] = $rid;
				$data['mobile'] = $mobile;
				$data['create_time'] = time();
				$giftData = $ActiveMod->add($data);
				
				if($giftData && $addCounpontoUser) {
					$res['flag'] = true;
					$res['prize'] = $rid;
					$res['message'] = $prize_arr[$rid-1]['prize']; //中奖项 
				}else {
					$res['flag'] = false;
					$res['message'] = '领取失败，请重新领取'; //中奖项
				}
			}
		}
		// 每日排行版
		//$condition['create_time'] = array('between',"$begin,$end");
		$condition['gift_id'] = array('lt',5);
		$getRank = $ActiveMod->where($condition)->order('gift_id')->select();
		//echo $ActiveMod->getLastsql();
		
		foreach($getRank as $v) {
			$mobile = $v['mobile'];
			$giftId = $v['gift_id'];
			switch ($giftId) {
				case 1 : $prize = '20元优惠劵';break;
				case 2 : $prize = '10元优惠劵';break;
				case 3 : $prize = '5元优惠劵';break;
				case 4 : $prize = '3元优惠劵';break;
			};

			$rankList[] = array(
				'mobile'  => substr_replace($mobile,'*****',3,5),
				'gift_id' => $v['gift_id'],
				'prize'   => $prize
			);
		}
		$res['rankList'] = $rankList;
		$this->ajaxReturn($res,'json');
		shuffle($prize_arr);
		unset($prize_arr[$rid-1]); //将中奖项从数组中剔除，剩下未中奖项 
	 }
	 /**
	+----------------------------------------------------------
	 *  注册/修改手机
	+----------------------------------------------------------
	 */
	 public function setMobile() {
		$UserMod = M('client'); 
		$mobile = $this->getFieldDefault('mobile');
		$nickName = $UserMod->where("mobile='$mobile'")->getField('nickname');
		//echo $UserMod->getLastsql();
		//表中是否有这条手机的记录
		if($nickName) {
			$res['flag'] = false;
			$res['nickname'] = $nickName;
			$res['message'] = '该手机号已经被注册';
		}else {
			$res['flag'] = true;
			$res['message'] = '该号码可用';
		}
		$this->ajaxReturn($res,'json');
	 }
	/**
	+----------------------------------------------------------
	 *  发送验证码
	+----------------------------------------------------------
	 */
	public function getMsgcde(){
		/*
		 * 消息发送页面，判断用户是否是已注册用户。注册了的需发验证码通过，才能领取
		 * 未注册的需要填写密码
		*/
		$mobile = $this->getFieldDefault('mobile',0);
		$is_ajax = intval($this->getFieldDefault('is_ajax',0));
		
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis', $options);
        $key = 'ls_saoma_'.$mobile;
        $oldRedis = $Reids->get($key);
		echo $oldRedis;
        if($is_ajax == 1) {
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
        }
	}
	/**
	+----------------------------------------------------------
	 *  注册
	+----------------------------------------------------------
	 */
	public function setRegister() {
		$ClientMod = M('client_wechat');
		
        $password = $this->getFieldDefault('password',''); //密码
        $mobile = $this->getFieldDefault('mobile',''); //手机号
        $msg_code = intval($this->getFieldDefault('msg_code','')); //手机验证码

        $key = 'ls_saoma_'.$mobile;
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis',$options);
        
        $UserMod = M('client');
        $auth_key = C('AUTH_KEY');
        $cache_code = intval($Reids->get($key));
		//echo $auth_key.','.$password;
        // 检查验证码 是否正确
        if($msg_code != $cache_code || $msg_code == 0){
            $rs['flag'] = false;
			$rs['code'] = '201';
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
			$rs['code'] = '202';
            $rs['message'] = '密码由6-16位字母或数字组成';
            $this->ajaxReturn($rs,'json');
            exit;
        }
        // 判断手机号码是否已经被注册
        $check_user = $UserMod->where(array("mobile"=>$mobile))->find();
        if(!empty($check_user)){
            $rs['flag'] = false;
			$rs['code'] = '203';
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
            'wskey'             => $this->getRandomString(16),
            'create_time'       => $now,
            'update_time'       => $now,
            'last_login_time'   => $now,
            'last_device_id'    => '0'
        );
        $user_id = $UserMod->add($data);
				
		if($user_id) {
			$rs['flag'] = true;
			$rs['code'] = '200';			
			$rs['message'] = "注册成功";
			$data = array();
			$data['user_id'] = $user_id;
			$data['open_id'] = $_COOKIE['openId'] ? $_COOKIE['openId'] : 0;
			$data['create_time'] = time();
			$data['open_id'] == 0 ? '' : $ClientMod->add($data);
			$this->ajaxReturn($rs,'json');
		}else {
			$rs['flag'] = false;
			$rs['code'] = '204';			
			$rs['message'] = "注册失败,请重新注册";
			$this->ajaxReturn($rs,'json');
		}
	}
	
	/**
	+----------------------------------------------------------
	 *  登录
	+----------------------------------------------------------
	 */
	public function checkLogin() {
		$ClientMod = M('client_wechat');
		
        $password = $this->getFieldDefault('password',''); //密码
        $mobile = $this->getFieldDefault('mobile',''); //手机号
        $msg_code = intval($this->getFieldDefault('msg_code','')); //手机验证码
        $key = 'ls_saoma_'.$mobile;
        $options = C('REDIS_CONF');
        $Reids = Cache::getInstance('Redis',$options);
        
        $UserMod = M('client');
        $auth_key = C('AUTH_KEY');
        $cache_code = intval($Reids->get($key));

        // 检查验证码 是否正确
        if($msg_code != $cache_code || $msg_code == 0){
            $rs['flag'] = false;
			$rs['code'] = '201';
            $rs['message'] = '验证码错误';
        }else {
			$rs['flag'] = true;
			$rs['code'] = '200';
            $rs['message'] = '验证成功';
			$userId = $UserMod->where("mobile='$mobile'")->getField("user_id");
			$data['user_id'] = $userId;
			$data['open_id'] = !empty($_COOKIE['openId']) ? $_COOKIE['openId'] : "";
			$data['create_time'] = time();
			$map['open_id'] = $data['open_id'];
			$flag = $ClientMod->where($map)->find();
			if($data['open_id']){
				if($flag){
					unset($data['open_id']);
					$ClientMod->where($map)->save($data);
				}else{
					$ClientMod->add($data);
				}
			}
		}
		$this->ajaxReturn($rs,'json');
		
        // 验证码验证通过删除key
        $Reids->rm($key);
	}
	/**
	+----------------------------------------------------------
	 *  注册页面
	+----------------------------------------------------------
	 */
	 public function register() {
		$this->getUserInfo();
		 $this->display();
	 }
	/**
	+----------------------------------------------------------
	 *  登录页面
	+----------------------------------------------------------
	 */
	 public function login() {
		$this->getUserInfo();
		$this->display();
	 }
	/**
	+----------------------------------------------------------
	 *  领奖页面
	+----------------------------------------------------------
	 */
	 public function prize() {
		$this->getUserInfo();
		$this->display();
	 }
}
?>