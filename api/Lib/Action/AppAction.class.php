<?php
/**
 * App系统类
 * @author feibo
 *
 */

class AppAction extends BaseAction {
	
	/**
	 +----------------------------------------------------------
	 * 版本更新 1001
	 +----------------------------------------------------------
	 */
	public function verUpd() {
		$os_type = intval($this->getField('os_type','need os_type'));
        $version = intval($this->getField('version'));
//        $type    = intval($this->getField("type"));//2.0新增参数，检查更新方式，自动为0，手动为1
		$mod_version = M('version');
        $device_mod  = M("device");
        $version_cfg = C('VERSIONS');
		$version_out = C('VERSION_OUT');
        $mem_cfg = C('MEMCACHE');
        $cur_version = $mod_version->where('os_type='.$os_type)->order('version_innumber desc')->find();
        $uid = $this->uid;
        $device_mod->where("id = {$this->cid}")->setField("update_time",time());
		$info=array();

		$flag = false;
		$repeat_time = 172800;
		if($version < $cur_version['version_innumber'] ) {
	       	$info['title'] = '新版本上线，更新提示';
	        $info['desc'] = $cur_version['describle']?str_replace('\n', "\n", $cur_version['describle']):'';
	        $info['upd_url'] = $cur_version['upd_url'];
        }
        if(count($info) == 0){
            $this->errorView('1004','null data');
        }
        $this->successView('',$info);
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取设备id 1002
	 +----------------------------------------------------------
	 */
    public function getDeviceId() {

       	$os_type = intval($this->getField('os_type'));
        $imei = htmlspecialchars($this->getField('imei'),ENT_QUOTES);
        $device_name = htmlspecialchars($this->getField('name','need device name'),ENT_QUOTES);
        $channel_name = htmlspecialchars($this->getFieldDefault('channel_name',''),ENT_QUOTES);
        $mod_device=M('device');


        $device=$mod_device->where("imei='$imei'")->find();
        $device_id=$device?$device['id']:'';
        $wskey=$device?$device['wskey']:'';
        if (empty($device)) {
            $mod_device_type=M('device_type');

            $device_type= $mod_device_type->where("device_name='$device_name'")->find();
            $device_type_id=$device_type?$device_type['id']:'';
            if (empty($device_type_id)) {
                $data_device_type['device_name']=$device_name;
                $device_type_id = $mod_device_type->add($data_device_type);

            }
            $now=time();
            $data_device=array(
                'os_type'=>$os_type,
                'imei'=>$imei,
            	'device_type'=>$device_type_id,
            	'wskey' =>A('Common')->getRandomString(),
            	'create_ip'=>A('Common')->getClientIp(),
            	'create_time'=>$now,
            	'update_time'=>$now
            );
            $device_id=$mod_device->add($data_device);
            $wskey=isset($data_device['wskey'])?$data_device['wskey']:'';
        }
        $info = array();
        $info['cid'] = intval($device_id);
        $info['key'] = $wskey;

        if($channel_name){
            // 插入到对应的数据表
            $channel_count_mod = M("channel_count");
            $data = array(
                "channel_en"    => $channel_name,
                "device_id"     => $info['cid'],
                "add_time"      => time()
            );
            $channel_count_mod->add($data);
        }
        $this->successView('',$info);
    }
    
    /**
     +----------------------------------------------------------
     * 获取app配置项
     +----------------------------------------------------------
     */
    public function getConfig() {
    	$mod_wbshare = M('wbshare');
    	$mem_cfg = C('MEMCACHE');
    	$mod_board = M('board');
    	$api_url = C('API_URL');
    	$mk = new memcache();
		$mk->connect('127.0.0.1',11211);
		$time = time();
		$tms = $this->getField('tms');
		$sig = $this->getField('sig');
		$wssig = $this->getField('wssig');
		$board = $mod_board->where("(os_type=$this->os_type OR os_type=0) and status!=0 and start_time<$time and $time<end_time" )->order('create_time desc')->group('scene_type')->distinct(true)->select();
		$num = 0;
		foreach($board as $key=>$val) {
			if(!$mk->get("yx_device_".$this->cid.'_'.$val['scene_type'].'_'.$val['id']) ) {
				$list[$num]['title'] = $val['title']?$val['title']:'';
				$list[$num]['content'] = $val['content']?$val['content']:'';
				$list[$num]['content'] = $val['content']?str_replace('\n', "\n", $val['content']):'';
				$list[$num]['scene_type'] = $val['scene_type']?intval($val['scene_type']):0;
				$list[$num]['buttons'] = $val['button_arr']?unserialize($val['button_arr']):array();
				$tmp=0;
				foreach($list[$num]['buttons'] as $i=>$value){

	       			$list[$num]['buttons'][$tmp]['action_type'] = $value['action_type']?intval($value['action_type']):0;
	       			$ishl = $list[$num]['buttons'][$tmp]['ishighlight'] = $value['ishighlight']?intval($value['ishighlight']):0;
	       			$list[$num]['buttons'][$tmp]['click_url'] = $api_url."srv=1503&cid=$this->cid&uid=$this->uid&tms=$tms&sig=$sig&wssig=$wssig&os_type=$this->os_type&version=$this->version&scene_type=$val[scene_type]&board_id=$val[id]&ishighlight=$ishl";
	       			$tmp++;
					}
					$num++;
			}
		}
		$info['dialogs']=$list?$list:array();

    	$time = time();
    	$wbshare = $mod_wbshare->where("status=1 and start_time<$time and $time<end_time")->order('id DESC')->find();
		$info['wb_share_title'] = $wbshare?$wbshare['content']:'';
		$this->successView('',$info);
    }
    /**
    +----------------------------------------------------------
     * 获取启动页 1004
    +----------------------------------------------------------
     */
    public function startPage(){
        $os_type =  $this->os_type; //默认android
        $ratio =  isset($_GET['ratio']) ? htmlspecialchars($_GET['ratio'],ENT_QUOTES): '';
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //读取是false
        $Cache = Cache::getInstance('Redis',$options);
        $time = time();
        $keyname = C('START_PAGE_REDIS').$os_type;
        $size = C('STARTPAGE_SIZE');
        $data = $Cache->get($keyname);
        if(empty($data)) {
            $data = array();
            $data = M('start_page')->where("(os_type=$os_type or os_type=0) and start_time<$time and end_time>$time")->field('start_time,end_time,img')->find();
            $Cache->set($keyname,$data,3600);
            $Cache->close();
        }
        if(!empty($ratio))
        {
            foreach($size as $key => $val){
                if($val[$os_type] == $ratio)
                {
                    $size_key = $key;
                    break;
                }
            }
        } else {
            $size_key=3;
        }
        if(!isset($size_key)){
            $size_key=3;
            if($os_type==3){//安卓其他分辨率
                foreach($size as $key => $val){
                    $configRadioW = explode('x',$val[3]);
                    $configRadioArray[] = $configRadioW[0];
                }
                $radioW = explode('x',$ratio);
                if($radioW[0]<$configRadioArray[0]){
                    $size_key = 1;
                }elseif($radioW[0]<$configRadioArray[1]){
                    $size_key = 2;
                }elseif($radioW[0]<$configRadioArray[2]){
                    $size_key = 3;
                }elseif($radioW[0]<$configRadioArray[3]){
                    $size_key = 4;
                }elseif($radioW[0]>$configRadioArray[3]){
                    $size_key = 4;
                }
            }
        }
        $img = unserialize($data['img']);
        $image = $img[$size_key]? $img[$size_key]:'';


        $splashImage = array();
        $splashImage['start_time'] = intval($data['start_time']);
        $splashImage['end_time'] = intval($data['end_time']);
        $startImage = array();
        $startImage['img_w'] = intval($image['img_w']);
        $startImage['img_h'] = intval($image['img_h']);
        $startImage['img_url'] = C('LS_IMG_URL').$image['img_url'];
        $splashImage['img'] = $startImage;

        if(intval($data['start_time'])==0||intval($data['end_time'])==0){
            $this->errorView('1004','null data');
        }
        $this->successView('',$splashImage);
    }
    /**
    +----------------------------------------------------------
     * 客户端收货地址更新方法 2808
    +----------------------------------------------------------
     */
    public function updateAddress(){
        $map_version_mod = M("map_version");
        $address_data = $map_version_mod->order("id desc")->field("version_num,map_url")->find();
        $address_data = array(
            "version"   => intval($address_data['version_num']),
            "url"       => $address_data['map_url']
        );
        $this->successView("",$address_data);
    }
    /**
    +----------------------------------------------------------
     * ios专用 用户开关 1005
    +----------------------------------------------------------
     */
    public function iosUserStatus(){
        $version = isset($_GET['version']) ? intval($_GET['version']) : 0;
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $set_key = C("LS_APP_CONFIG");// 缓存建名

        $app_config = unserialize($redis->get($set_key));
        $expire = 60;
        if(!$app_config){
            $site_mod = M("site_config");
            $app_config = $site_mod->field("service_qq,service_phone,ios_switch,gift_switch,version,feedback_notice")->find(1);
            $redis->set($set_key,serialize($app_config),$expire);
        }
        $data = array(
            "ios_switch" => $app_config['ios_switch'] ,
            "version"    => $app_config['version']
        );

        if($version == $data['version']){
            // $return_data['status'] = 0;
            $return_data['status'] = intval($data['ios_switch']);
        }else{
            // $return_data['status'] = 1;
            $return_data['status'] = 1;
        }

        $redis->close();
        $this->successView('',$return_data);

    }
    /**
    +----------------------------------------------------------
     * 获取关于零食小喵信息 2002
    +----------------------------------------------------------
     */
    public function about(){
        $data = array(
            "desc"      => "亲爱的主人：\n\t小喵终于等到你啦！吃货见吃货，两嘴喵一喵，小喵自己也算半个吃货，特爱吃零食，小喵能为吃货做点什么呢？\n\t〖今日特卖〗超市品牌零食太贵？小喵砍价砍砍砍，天天特惠1折起！而且厂家直发够新鲜，再也不吃小店里那快过期的零食！\n\t〖进口零食〗那些从未吃过的海外零食再也不用托国外的亲戚了，欧美日韩东南亚想吃就吃，告别楼下小卖部老三款！\n\t〖精挑细选〗帮你挑出全淘宝最好吃TOP10零食，日本韩国TOP10零食，个个精挑细选！\n\t〖专题推荐〗周末宅宿舍看片、办公室通宵加班，送男神女神零食大礼包，小喵帮你认真选！\n\t这就是小喵专为吃货量身打造的零食神器——零食小喵，啦啦啦……",
            "service"   => "快来加入小喵吃货大家庭吧……\n新浪微博：\t@零食小喵\n微信号服务号：\t零食小喵lsxiaomiao\n小喵QQ号：\t3188288037\n吃货QQ群：\t399180298"
        );
        $this->successView('',$data);
    }
    /**
    +----------------------------------------------------------
     *  获取注册协议url 2104
    +----------------------------------------------------------
     */
    public function registerUrl(){
        $data['url'] =  C('SITE_URL')."/api.php?apptype=0&srv=2108&cid=10002&uid=0&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$this->os_type."&version=12";
        $this->successView("",$data);
    }
    /**
    +----------------------------------------------------------
     *  获取注册协议 2108
    +----------------------------------------------------------
     */
    public function register(){
        $this->display("Discount/deal");
    }
    /**
    +----------------------------------------------------------
     *  获取服务信息2001
    +----------------------------------------------------------
     */
    public function service(){
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $set_key = C("LS_APP_CONFIG");// 缓存建名
        $app_config = unserialize($redis->get($set_key));
        $expire = 60;
        if(!$app_config){
            $site_mod = M("site_config");
            $app_config = $site_mod->field("service_qq,service_phone,ios_switch,gift_switch,version,feedback_notice")->find(1);
            $redis->set($set_key,serialize($app_config),$expire);
        }
        $data = array(
            "service_qq"      => $app_config['service_qq'] ? $app_config['service_qq'] :"3206494235",
            "service_phone"   => $app_config['service_phone'] ? $app_config['service_phone']:"0592-3156818"
        );
        $redis->close();
        $this->successView('',$data);
    }

    /**
    +----------------------------------------------------------
     *  获取推送码 1007
    +----------------------------------------------------------
     */
    public function checkCode() {
        $os_type =  isset($_GET['os_type']) ? intval($_GET['os_type']) : 3; //默认android
        $types = array('1'=>'ios', '3' => 'android');
        if($type = $types[$os_type]) { //设备
            $code =  isset($_GET['code']) ? trim($_GET['code']) : ''; //默认android
            if(empty($code)){
                $this->errorView('1006','need code');
            }
            $mode = M('push'  . '_' . $type);
            $device_id = $this->cid;
            $short_code = short_code($code);
            $id = $mode->where("short_code='$short_code'")->getField("id");
            $now_time = time();
            if(empty($id)) { //不存在 添加推送码
                $id = $mode->where("device_id='$device_id'")->getField("id"); //以前设备号存在，产生了不同的推送码，要把原来的删掉
                if($id){
                    $mode->delete($id);
                }
                $data = array(
                    'device_id' => $device_id,
                    'code' => $code,
                    'short_code' => $short_code,
                    'add_date' => $now_time,
                    'update' => $now_time
                );
                if($mode->add($data)) {
                    $this->successView();
                } else {
                    $this->errorView($this->ws_code['internal_error'],$this->msg['internal_error']);
                }

                //echo $mode->getLastSql();
            }  else {
                //更新最新打开时间
                $data = array(
                    'id' => $id,
                    'device_id' => $device_id,
                    'update' => $now_time
                );
                $mode->save($data);
                $this->successView();
            }
        }
    }
    /**
    +----------------------------------------------------------
     *  控制个人中心分享好友或礼包功能开关 1008
    +----------------------------------------------------------
     */
    public function shareSwitch(){
        $version = isset($_GET['version']) ? intval($_GET['version']) : 0;
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $set_key = C("LS_APP_CONFIG");// 缓存建名

        $app_config = unserialize($redis->get($set_key));
        $expire = 60;
        if(!$app_config){
            $site_mod = M("site_config");
            $app_config = $site_mod->field("service_qq,service_phone,ios_switch,gift_switch,version,feedback_notice")->find(1);
            $redis->set($set_key,serialize($app_config),$expire);
        }
        $data = array();
        if($version == $app_config['version']){  // 判断是不是该审核版本
            // $return_data['status'] = 0;
            if($app_config['ios_switch'] == 0){  //判断当前iOS是不是关闭
                $data['status'] = 0;
            }else{
                $data['status'] = intval($app_config['gift_switch']);
            }
        }else{
            // $return_data['status'] = 1;
            $data['status'] = intval($app_config['gift_switch']);
        }
        $redis->close();
        $this->successView('',$data);
    }

    /**
    +----------------------------------------------------------
     * 清空后台推送红点数的接口 1006
    +----------------------------------------------------------
     */
    public function clearRed(){
        $device_id = $this->cid;
        $pushMod = M('push_ios');
        $where = array();
        $where['device_id'] = $device_id;
        $data = array();
        $data['red_num'] = 0;//推送红点数量清空
        $flag = $pushMod->where($where)->save($data);
        $this->successView();
    }


    /**
    +----------------------------------------------------------
     * 弹窗信息 1009
    +----------------------------------------------------------
     */
    public function pop(){

        $site_url = C("SITE_URL");// 站点url
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $expire = 3600;// 缓存暂时3600秒
        $ls_pop = C('LS_POP');
        $ls_pop_user = C("LS_POP_USER");// 备用
        $pop_data_s = $redis->get($ls_pop);
        $redis->rm($ls_pop);
        $pop_data = unserialize($pop_data_s);
        if(!$pop_data){

            $pop_mod = M("pop");
            $pop_data = $pop_mod->where("status = 'published' ")->find();
            $redis->set($ls_pop,serialize($pop_data),$expire);
        }

        $redis->close();
        // 如果没有弹框信息了。
        if(empty($pop_data)){
            $data = array(
                "status" => 0,
                "url"    => "",
            );
            $this->successView("",$data);
        }
        
        $tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
        $sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
        $wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
        $cid = $this->cid;

         $n_url1 = $site_url."/api.php?apptype=0&srv=3001&cid=$cid&uid=".$this->uid."&tms=$tms&sig=$sig&wssig=$wwsig&os_type=".$this->os_type."&version=$this->version";
         $n_url2 = $site_url."/api.php?apptype=0&srv=3000&cid=$cid&uid=".$this->uid."&tms=$tms&sig=$sig&wssig=$wwsig&os_type=".$this->os_type."&version=$this->version";


//          $gifts_id = 8;  //老用户目前优惠券Id设为8
//          $sql1 = "select c.* from ls_coupon as c where c.coupon_num 
//          in((select a.coupon_num from ls_cou_gift_relation as a where a.gift_id = {$gifts_id}) )  ";
//          $r1 = $sql1->query($sql1);
         
//          $gifts_id = 8;  //老用户目前优惠券Id设为8
//          $sql1 = "select coupon_num from ls_cou_gift_relation where gift_id = 8 ";
//          $r1 = $sql1->query($sql1);
         
        if($this->uid<=0){
            $data = array(
                "status" => 1,
                "url"    => $n_url1,
            );

        }else{
            // 登录的判断
            $data['status'] = 1;// 默认要弹框
            //判断uid至少有一个订单是付过款的，没付过则$date['pay'] = 0，否则$date['pay'] = 1
            $o = M();
            $sql = "select order_id from ls_order where user_id = '{$this->uid}' and pay_sn <> '' and pay_time <> 0 limit 1 ";
            $r = $o->query($sql);
            if($r){
                $data['url'] = $n_url2;
            }else{
                $data['url'] = $n_url1;
            }
        }
        $this->successView("",$data);
    }

    /**
    +----------------------------------------------------------
     * 弹窗信息 1009
    +----------------------------------------------------------
     */
    public function pop1(){

        $site_url = C("SITE_URL");// 站点url
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $expire = 3600;// 缓存暂时3600秒
        $ls_pop = C('LS_POP');
        $ls_pop_user = C("LS_POP_USER");// 备用
        $pop_data_s = $redis->get($ls_pop);
        $redis->rm($ls_pop);
        $pop_data = unserialize($pop_data_s);
        if(!$pop_data){

            $pop_mod = M("pop");
            $pop_data = $pop_mod->where("status = 'published' ")->find();
            $redis->set($ls_pop,serialize($pop_data),$expire);
        }

        $redis->close();
        // 如果没有弹框信息了。
        if(empty($pop_data)){
            $data = array(
                "status" => 0,
                "url"    => "",
                "pay"    => 0
            );
            $this->successView("",$data);
        }

        $gifts_id = $pop_data['link_id'];
        $url = $site_url."/api.php?m=Pop&a=index&id={$pop_data['id']}&uid=$this->uid&os_type=".$this->os_type."&version=$this->version";
        if($this->uid<=0){
            $data = array(
                "status" => 1,
                "url"    => $url,
                "pay"    => 0
            );

        }else{
            // 登录的判断
            $status = 1;// 默认要弹框
            switch($pop_data['link_type']){//供今后扩展使用

                default :
                    $gift_user_mod = M("gift_user");
                    $user_id = $gift_user_mod->where("gift_id = $gifts_id and user_id = {$this->uid} ")->getField("user_id");

                    if(!empty($user_id)){
                        // 说明有数据
                        //$status = 0;//等于0时 表示已经领过券，这时候客户端将不再领券
						$status = 1;//等于1时 表示已经领过券，这时候客户端将还领券
                    }
                    break;
            }



            $data = array(
                "status" => $status,
                "url"    => $status ? $url:""
            );
            //判断uid至少有一个订单是付过款的，没付过则$date['pay'] = 0，否则$date['pay'] = 1
            $o = M();
            $sql = "select order_id from ls_order where user_id = '{$this->uid}' and pay_sn <> '' and pay_time <> 0 limit 1 ";
            $r = $o->query($sql);
            if($r){
                $data['pay'] = 1;
            }else{
                $data['pay'] = 0;
            }
        }
        $this->successView("",$data);
    }
}
