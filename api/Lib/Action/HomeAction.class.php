<?php
/**
 * 接口公共类
 * @author nangua
 *
 */
class HomeAction extends baseAction{
	
    public $field_ad = "id,link_to,image_src,link_id,link_url,channel_id";
    /**
    +----------------------------------------------------------
     * 获取首页 主题、分类和 品牌团
    +----------------------------------------------------------
     */
	public function home() {

		$ad_mod = M('ad');// banner 表
		$spu_cate_mod = M('spu_cate');
        $group_topic_mod = M("group_topic");
        $topic_mod  = M("topic");
		$options = C('REDIS_CONF');
		$redis = Cache::getInstance('Redis',$options);
		$expire = 60;// 缓存暂时60秒
		$cache_key = 'ls_home';
		$cache_data = unserialize($redis->get($cache_key));
        $channel_name = strtolower($this->getFieldDefault("channel_name",''));

		if(!$cache_data){
			// 获得轮播图数据 1 是banner 数据

           $ad_list = $ad_mod->where("type =1 and status='published'")->field($this->field_ad)->order('sort desc,publish_time desc')->limit(5)->select();
            // 封装 轮播图结构
            $topics = array();// 防止接口出错
            foreach($ad_list as $k=>$v) {
                $info = "";
                if($v['link_to'] == 5){
                    $info = $v['link_url'];
                }else{
                    $info = $v['link_id'];
                }
                $special = array(
                    "id"     => intval($v['id']),
                    "title"  => '',
                    "desc"   => '',
                    "img"    => image($v['image_src']),
                    "action" => array(
                        "type" =>intval($v['link_to']),
                        "info" =>$info
                    ),
                    "channel_id"=> intval($v['channel_id'])
                );
                $topics[] = $special;
            }

            // 获取分类
            $goods_cate_list = $spu_cate_mod->where('pid = 0 and index_num <>0')->order('index_num asc')->limit(6)->select();
            $classifies = array();
            // 封装 首页分类结构
            foreach($goods_cate_list as $key=>$val) {
                $classifies[$key]['id'] = intval($val['id']);
                $classifies[$key]['title'] = $val['name'];
                $classifies[$key]['desc']  = $val['desc']?$val['desc']:'';// mark
                $classifies[$key]['img']['img_url'] =  $val['image_src'] ;
                $classifies[$key]['img']['img_w']   =  $val['img_w'] ? intval($val['img_w']) : 400;
                $classifies[$key]['img']['img_h']   =  $val['img_h'] ? intval($val['img_h']) : 400;
            }
            // 获取 品牌团 数据 默认品牌团id是 1
            $brand_team_id = C("BRAND_TEAM_ID")?C("BRAND_TEAM_ID"):1;

//            $topic_ids = $group_topic_mod->where("group_id = {$brand_team_id}")->getField("topic_id",true);
//
//            $topic_ids = arrayToStr($topic_ids);
            // 获取品牌团数据 越早结束的
            $now = time();
            $brands_list = $topic_mod->where("is_show = 1 and status='published' and type=2 and publish_time<$now and end_time>$now")->order("sort desc,end_time asc")->field("topic_id,title,image_src,desc,end_time")->select();
            $brands = array();
            foreach ($brands_list as $k => $v) {
                $brands[$k] = array(
                    "id"        => intval($v['topic_id']),
                    "title"     => $v['title'],
                    "img"       => image($v['image_src']),
                    "discount"  => $v['desc'],//
                    "provider"  => '',
                    "time"      => intval($v['end_time'])
                );
            }

            // 获取情景数据 2 首页的情景数据 保护数据 保证数据不会为空。不然iOS会出错
            $special_tmp =  $ad_mod->where(" type = 2 and status='published' and position=1 ")->order("publish_time desc")->field($this->field_ad)->find();

            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 2  and position=1 and status not in('scheduled') ")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            $special_tmp = $ad_mod->where(" type = 2 and status='published' and position=2 ")->order("publish_time desc")->field($this->field_ad)->find();

            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 2  and position=2 and status not in('scheduled') ")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            $special_tmp = $ad_mod->where(" type = 2 and status='published' and position=3 ")->order("publish_time desc")->field($this->field_ad)->find();

            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 2  and position=3 and status not in('scheduled') ")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            $special_tmp = $ad_mod->where(" type = 2 and status='published' and position=4 ")->order("publish_time desc")->field($this->field_ad)->find();
            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 2  and position=4 ")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            $specials = array();
            // 封装  情景结构
            foreach($special_list as $k=>$v) {

                ////1:跳转到专题详情； 2:跳转到专题列表；3：跳转到商品详情；4：跳转到商品列表；5: 跳转到指定html5；6；跳转到外部浏览器打开
                // 判断info的值
                $info = "";
                if($v['link_to'] == 5){
                    $info = $v['link_url'];

                }else{
                    $info = $v['link_id'];
                }
                if(!empty($v)){
                    $specials[] = array(
                        "id"     => intval($v['id']),
                        "title"  => '',
                        "desc"   => '',
                        "img"    => image($v['image_src']),
                        "action" => array(
                            "type" =>intval($v['link_to']),
                            "info" =>$info
                        )
                    );
                }
            }
            // 封装首页数据结构
            $topic_set = M("topic_set");
            $brand_team = $topic_set->find($brand_team_id);
            $daily_new_id = C("DAILY_TOPIC_ID")?C('DAILY_TOPIC_ID'):5;// 今日上新
            $daily = $topic_mod->where("topic_id = {$daily_new_id}")->find();
            $info = array(
                "topics"            => $topics,
                "classifies"        => $classifies,
                "brands_title_big"  => $brand_team['title'],
                "brands_title_sml"  => $brand_team['desc'],
                "brands"            => $brands,
                "specials"          => $specials,
                "new_title_big"     => $daily['title'],
                "new_title_sml"     => $daily['desc']
            );
            // 重新开启redis
            $redis->set($cache_key,serialize($info),$expire);
		}else{
			$info = $cache_data;
		}
        //===========用户驱动缓存更新-检测品牌团是否过期品牌
        $now = time();
	
        $tmp_specials = array();
        foreach($info['specials'] as $k=>$special_v){
            // 如果是非小米机子，同时有这个轮播图的情况下，去除该轮播图
           if(strstr($special_v['action']['info'],"http://qing.comwei.com:8888/app_miaoturn")){
               if($this->os_type ==1){
                   $special_v['action']['info'] = "http://qing.comwei.com:8888/app_miaoturn/indexIos.html";
               }else{
                   $special_v['action']['info'] = "http://qing.comwei.com:8888/app_miaoturn/index.html";
               }
           }
            $tmp_specials[] = $special_v;
        }
        $info['specials'] = $tmp_specials;	
	
	
	
        // 判断是否有小米专属的轮播图
        $tmp_topics = array();

        foreach($info['topics'] as $k=>$topic_v){
            // 如果是非小米机子，同时有这个轮播图的情况下，去除该轮播图
            if(!strstr($channel_name,"xiaomi")&&$topic_v['channel_id']==1){
                continue;
            }
            // 如果是低版本的将看不到这个优惠券列表，以及优惠券详情。
            if($this->version<=11 && in_array($topic_v['action']['type'],array(6,7,8,9))){
                continue;
            }
            // 版本12的也不能显示 分享有礼
            if($this->version == 12 && in_array($topic_v['action']['type'],array(9))){
                continue;
            }
            unset($topic_v['channel_id']);
            $tmp_topics[] = $topic_v;
        }

        $info['topics'] = $tmp_topics;
        foreach($info['brands'] as $brand_v){
            // 如果存在时间小于当前时间的，则清除缓存
            if($brand_v['time']<$now){
                $redis->rm($cache_key);
                break;// 直接break 顶掉。
            }
        }

        //安卓的请求不返回分类信息
        if($this->os_type != 1){
            unset($info['classifies']);
        }elseif($this->os_type == 1 && $this->version > 13){
            // 如果是iOS 版本是14以上的则删除掉分类
            unset($info['classifies']);
//            $info['classifies'] = new stdClass();
        }

        //===========检测品牌团是否有过期品牌
        $redis->close();
		$this->successView('',$info);
	}

    /**
    +----------------------------------------------------------
     * 每日上新  2206
    +----------------------------------------------------------
     */
	public function dailyOnNew() {
        // 后台 如果更改排序或者新增每日上新的时候。都需要更新缓存信息。mark
//      $since_id = intval($this->getFieldDefault('since_id',0));// 备用
		$pg_cur = intval($this->getFieldDefault('pg_cur',1));//当前页数
        $pg_size = isset($_GET['pg_size'])?intval($_GET['pg_size']):20;//每页显示的条数
		$start = ($pg_cur - 1)*$pg_size;
        // 确保不会出现负数值
        if($start<=0){
            $start = 0;
        }
		$options = C('REDIS_CONF');
		$options['rw_separate'] = true; //写分离
		$redis = Cache::getInstance('Redis',$options);
        $expire = 60;// 缓存暂时 设置
        $set_key = C("LS_DAILY_ON_NEW");// 缓存建名
        $obj = unserialize($redis->get($set_key));
        if(!isset($obj[$pg_cur])) {

            $topic_realtion_mod = M("topic_relation");
            $spu_mod = M("spu");
            $now = time();
            $topic_id = C("DAILY_TOPIC_ID");

            // 每日上新id为5 当天的商品 同时是发布状态的商品
            $daily_on_new = $topic_realtion_mod->where("topic_id = {$topic_id} and end_time>=$now and status='published' ")->group("spu_id")->order("sort desc,publish_time desc")->limit($start,$pg_size)->field("spu_id,image_src,end_time,type,atom_id")->select();

            if(empty($daily_on_new)){
                // 返回空数据
                $redis->close();
                $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
            }
            // 封装今日上新商品
            $spu_list = array();

            $spu_ids = arrayToStr($daily_on_new,'spu_id');
            $daily_attr = arrayFormat($daily_on_new,"spu_id");
            // 正常销售，无库存的也显示
            $spu_list = $spu_mod->where("status in(0,1) and spu_id in ($spu_ids)")->order("field(`spu_id`,$spu_ids)")->select();
            if(empty($spu_list)){
                // 返回空数据
                $redis->close();
                $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
            }
            // 不需要的信息给空值
            foreach ($spu_list as $k=>$v) {
                $spu_id = $v['spu_id'];
                $type = 0;
                $price_now = price_format($v['price_now']);
                $status = $v['status'];
                // type 大于1 是参加活动的商品 考虑商城商品和导购商品

                $end_time = $daily_attr[$spu_id]['end_time'];
                // 非导购商品判定 0 是商城商品
                if($v['guide_type']==0){
                    $spu_extend = spu_format($spu_id);
                    if($spu_extend['type']==2){
                        // 限时限量要显示库存
                        $type = 1;//1，特卖商品(显示剩余)
                    }elseif($spu_extend['type']==1){
                        // 限时的不需要显示库存
                        $type = 2;//特卖商品(不显示剩余)
                    }
                    $price_now = $spu_extend['price'];

                    if($spu_extend['stocks'] <=0){
                        $status = 1;// 再次确认库存情况
                    }


                }elseif($daily_attr[$spu_id]['type']==0&&$daily_attr[$spu_id]['atom_id']>0){
                    // 导购商品 默认 限时的不需要显示库存 atom_id 在 type 为0 时， atom_id 0 无活动 ，1 限时 2 限量
                    $type = 2;
                }

                $spu_arr[$k] = array(
                    "id"                => intval($v['spu_id']),
                    "title"             => $v['spu_name'],
                    "type"              => intval($type),
                    "guide_type"        => intval($v['guide_type']),
                    "status"            => intval($status),
                    "sold_num"          => 0,// 不需要获取
                    "surplus_num"       => 0,// 不需要获取
                    "price"             => array(
                        "current" => $price_now,
                        "prime"   => price_format($v['price_old'])
                    ),
                    "img"               => image($daily_attr[$spu_id]['image_src']),
                    'freight'           => 0,
                    "time"              => intval($end_time),
                    "fav_num"           => 0,
                    "desc"              => '',
                    "sub_classify_id"   => 0
                );
            }
            $obj[$pg_cur]  = $spu_arr;
            $redis->set($set_key,serialize($obj),$expire);
        }

        //===========用户驱动缓存更新-检测每日上新商品是否过期品牌
        $now = time();
        foreach($obj[$pg_cur] as $daily_v){
            // 如果存在时间小于当前时间的，则清除缓存
            if($daily_v['time']<$now){
                $redis->rm($set_key);
                break;// 直接break 顶掉。
            }
        }

        //===========检测每日上新是否有过期品牌
        $redis->close();

        $this->successView($obj[$pg_cur]);
    }

}