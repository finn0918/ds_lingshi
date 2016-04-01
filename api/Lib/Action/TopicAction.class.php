<?php
/**
 * 专题列表
 * @author nangua
 */

class TopicAction extends  BaseAction{
    public $special_fields = "spu_id,spu_name,type,guide_type,status,price_now,price_old,end_time,tag";
    private $_spu_fields = "spu_id,spu_name,type,details,sales,stocks,price_now,price_old,shipping_free,desc,fav_count,guide_type,status";
    public $field_ad = "id,link_to,image_src,link_id,link_url";

    /**
    +----------------------------------------------------------
     * 通过品牌id获取品牌团详情 2205
    +----------------------------------------------------------
     */
    public function brandSpuList()
    {
        $brand_id = intval($this->getField('brand_id','',true));//
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis', $options);
        $expire = 60;
        $set_key = C("LS_BRAND_SPU_LIST").$brand_id;// 缓存建名
        $redis->rm($set_key);
        $obj = unserialize($redis->get($set_key));
        $redis->close();
        if (!isset($obj[$pg_cur])) {

            $topic_realtion_mod = M("topic_relation");
            $topic_mod = M("topic");
            $spu_mod = M("spu");
            $now   = time();

            $brand =  $topic_mod->where("topic_id = $brand_id")->field("topic_id,title,image_src,desc,end_time,provider")->find();

            if(empty($brand)){
                $this->errorView($this->ws_code['brand_topic_not_exist'],$this->msg['brand_topic_not_exist']);
            }
            // 获取该品牌的相关商品
            $brand_spu_list = $topic_realtion_mod->where("topic_id = $brand_id and end_time > $now and status='published' ")->group("spu_id")->order("sort asc,publish_time desc")->field("spu_id,image_src")->limit($start,$pg_size)->select();
            // 没有数据提示

            if(empty($brand_spu_list)){
                $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
            }
            // 封装今日上新商品
            $spu_list = array();

            $spu_ids = arrayToStr($brand_spu_list,"spu_id");
            $spu_list = $spu_mod->where("status in(0,1) and spu_id in ($spu_ids)")->order("field(`spu_id`,$spu_ids)")->select();
            $spu_img_mod = M("spu_image");
            $brand_spu_list = $spu_img_mod->where("spu_id in($spu_ids) and type=1")->field("spu_id,images_src")->select();
            $brand_spu_list = secToOne($brand_spu_list,"spu_id","images_src");

            // 不需要的信息给空值
            foreach ($spu_list as $k=>$v) {
                $spu_id = $v['spu_id'];
                $type = 0;
                $price_now = price_format($v['price_now']);
                $price_old = price_format($v['price_old']);
                $status = $v['status'];
                if($v['guide_type']==0){
                    $spu_extend = spu_format($spu_id);

                    if($spu_extend['type']==2){
                        // 限时限量要显示库存
                        $type = 1;
                    }elseif($spu_extend['type']==1){
                        // 限时的不需要显示库存
                        $type = 2;
                    }
                    if($spu_extend['stocks']<=0){
                        $status=1;// 已失效，下架
                    }
                    $price_now = $spu_extend['price'];
                }
                $agio = agio_format($price_now,$price_old);
                $spu_arr[$k] = array(
                    "id"            => intval($v['spu_id']),
                    "title"         => $v['spu_name'],
                    "type"          => intval($type),
                    "guide_type"    => intval($v['guide_type']),
                    "status"        => intval($status),
                    "sold_num"      => 0,
                    "surplus_num"   => 0,
                    "price"         => array(
                        "current" => $price_now,// 格式化价格，如果是特卖商品给出特卖价
                        "prime"   => price_format($v['price_old'])// 格式化市场价
                    ),
                    "img"           => image($brand_spu_list["{$v['spu_id']}"]),
                    'freight'       => 0,
                    "time"          => intval($brand['end_time']),
                    "tag"           => array(
                                "title"=> $agio,
                                "color"=> 0
                            ),
                    "fav_num"       => 0,
                    "desc"          => '',
                    "sub_classify_id"=> 0,
                );
            }
            $obj[$pg_cur]  = array(
                "brand"     =>array(
                    "id"        => intval($brand['topic_id']),
                    "title"     => cut_str($brand['title'],22),
                    "img"       => image($brand['image_src']),
                    "discount"  => $brand['desc'],
                    "provider"  => $brand['provider']?$brand['provider']:'零食小喵特供',//不填就写小喵特供
                    "time"      => intval($brand['end_time'])
                ),
                "goodses"   =>$spu_arr? $spu_arr:array()
            );
            $redis = Cache::getInstance('Redis', $options);
            $redis->set($set_key,serialize($obj),$expire);
            $redis->close();
        }

        $this->successView('',$obj[$pg_cur]);
    }

    /**
    +----------------------------------------------------------
     *  今日特卖商品列表 2301
    +----------------------------------------------------------
     */
    public function specialSaleList(){
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;
        if($start<=0){
            $start = 0;
        }
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis', $options);
        $expire = 60;// 暂时 缓存60秒
        $set_key = C("LS_SPECIAL_SALE_LIST");// 缓存建名
//        $redis->rm($set_key);
        $obj = unserialize($redis->get($set_key));
        if (!isset($obj[$pg_cur])) {
            $topic_realtion_mod = M("topic_relation");
            $topic_mod = M("topic");
            $spu_mod = M("spu");
            $now = time();

            $special_topic_id = C("SPECIAL_TOPIC_ID");
            $special_sale_title =  $topic_mod->where("topic_id = {$special_topic_id}")->getField("title");
            // 获取该特卖专场的相关商品
            $special_spu_list = $topic_realtion_mod->where("topic_id = {$special_topic_id} and  end_time>= $now and status='published' ")->group("spu_id")->order("sort desc,publish_time desc")->limit($start,$pg_size)->field("spu_id,image_src,topic_id,end_time")->select();

            if(empty($special_spu_list)){
                $redis->close();
                $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
            }
            $spu_img = secToOne($special_spu_list,"spu_id","image_src");
            $spu_end_time = secToOne($special_spu_list,"spu_id","end_time");
            // 封装今日特卖商品
            $spu_list = array();

            $spu_ids = arrayToStr($special_spu_list,'spu_id');

            $spu_list = $spu_mod->where("status in(0,1) and spu_id in ($spu_ids)")->order("field(`spu_id`,$spu_ids)")->field("spu_id,spu_name,type,guide_type,status,price_now,price_old")->select();
            if(empty($spu_list)){
                $redis->close();
                $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
            }
            // 不需要的信息给空值
            foreach ($spu_list as $k=>$v) {
                $spu_id  = $v['spu_id'] ;
                $type = 1;
                $price_old = price_format($v['price_old']);
                $status = $v['status'];
                if($v['guide_type']==0){
                    $spu_extend = spu_format($spu_id);
                    if($spu_extend['type']==2){
                        // 限时限量要显示库存
                        $type = 1;
                    }elseif($spu_extend['type']==1){
                        // 限时的不需要显示库存
                        $type = 2;
                    }
                    $price_now = $spu_extend['price'];
                    if($spu_extend['stocks'] <= 0){
                        $status = 1;// 失效
                    }

                }else{
                    $price_now = price_format($v['price_now']);
                }
                $agio = agio_format($price_now,$price_old);


                $spu_arr[$k] = array(
                    "id"            => intval($v['spu_id']),
                    "title"         => $v['spu_name'],
                    "type"          => intval($type),
                    "guide_type"    => intval($v['guide_type']),
                    "status"        => intval($status),
                    "sold_num"      => 0,
                    "surplus_num"   => 0,
                    "price"         => array(
                        "current" => $price_now ,// 格式化价格，如果是特卖商品给出特卖价
                        "prime"   => $price_old //格式化市场价
                    ),
                    "img"           => image($spu_img[$spu_id]),
                    'freight'       => 0,
                    "time"          => intval($spu_end_time[$spu_id]),
                    "tag"           => array(
                            "title" => $agio,
                            "color" => 1
                                ),
                    "fav_num"       => 0,
                    "desc"          => ''
                );
            }
            $obj[$pg_cur]  = $spu_arr;
            $redis->set($set_key,serialize($obj),$expire);
        }
        //===========用户驱动缓存更新-检测特卖商品是否过期品牌
        $now = time();
        foreach($obj[$pg_cur] as $special_v){
            // 如果存在时间小于当前时间的，则清除缓存
            if($special_v['time']<$now){
                $redis->rm($set_key);
                break;// 直接break 顶掉。
            }
        }
        //===========检测特卖商品是否有过期品牌

        $redis->close();
        $this->successView($obj[$pg_cur]);
    }
    /**
    +----------------------------------------------------------
     *  获取发现首页大专题 2401
    +----------------------------------------------------------
     */
    public function foundAd(){
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis', $options);
        $expire = 60;
        $set_key = C("LS_FOUND_AD");// 缓存建名
//        $redis->rm($set_key);
        $obj = unserialize($redis->get($set_key));
        if(empty($obj)){

            $ad_mod = M('ad');// ad 表
            // 获取情景数据 保护数据。保证有固定4个显示。所以else会取出 旧的数据
            $special_tmp =  $ad_mod->where(" type = 3 and status='published' and position=1 ")->order("publish_time desc")->field($this->field_ad)->find();

            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 3  and position=1 and status not in('scheduled')")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            $special_tmp = $ad_mod->where(" type = 3 and status='published' and position=2 ")->order("publish_time desc")->field($this->field_ad)->find();

            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 3  and position=2 and status not in('scheduled') ")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            $special_tmp = $ad_mod->where(" type = 3 and status='published' and position=3 ")->order("publish_time desc")->field($this->field_ad)->find();

            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 3  and position=3 and status not in('scheduled') ")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            $special_tmp = $ad_mod->where(" type = 3 and status='published' and position=4 ")->order("publish_time desc")->field($this->field_ad)->find();
            if(empty($special_tmp)){
                $special_list[] = $ad_mod->where(" type = 3  and position=4 and status not in('scheduled') ")->order("publish_time desc")->field($this->field_ad)->find();
            }else{
                $special_list[] = $special_tmp;
            }

            // 封装  情景结构
            ////1:跳转到专题详情； 2:跳转到专题列表；3：跳转到商品详情；4：跳转到商品列表；5: 跳转到指定html5；6；跳转到外部浏览器打开
            foreach($special_list as $k=>$v) {

                // 判断info的值
                $info = "";

                if($v['link_to'] == 5){
                    $info = $v['link_url'];
                }else{
                    $info = $v['link_id'];
                }
                $specials[$k] = array(
                    "id"     => intval($v['id']),
                    "title"  => '',
                    "desc"   => '',
                    "img"    => image($v['image_src']),
                    "action" => array(
                        "type" => intval($v['link_to']),
                        "info" =>$info
                    )
                );
            }
            $redis->set($set_key,serialize($specials),$expire);
        }else{
            $specials = $obj;
        }
        $redis->close();
        $this->successView($specials);
    }
    /**
    +----------------------------------------------------------
     *  通过专题id获取专题详情商品列表 2408 客户端使用
    +----------------------------------------------------------
     */
    public function detailApp(){
        $subject_id =  intval($this->getField('subject_id')); //必须接受专题id
        $topic_mod = M("topic");
        // 判断专题状态
        $topic = $topic_mod->where(" topic_id = {$subject_id} and status = 'published' ")->find();
        if (empty($topic)) {
            $this->errorView($this->ws_code['topic_id_error'],$this->msg['topic_id_error']);
        }
        // 判断该用户是否收藏
        if($this->uid<=0){
            $collect_status = 0;
        }else{
            $fav_mod = M("favs");
            $is_have = $fav_mod->where("user_id = {$this->uid} and opt_id = $subject_id and type = 1")->find();
            $collect_status = $is_have? 1:0;
        }
        // 专题信息
        $img_arr = image($topic['image_src']);
        $active = 0;
        if($img_arr['img_w'] == 0 || in_array($topic['topic_id'],array(258))){
            $active = $subject_id == 253 ?1:2;// 31代表 活动介绍页 其他是 主会场
        }

        $subject = array(
            "id"        => intval($topic['topic_id']),
            "desc"      => $topic['desc']?$topic['desc']:"",
            "title"     => $topic['title']?$topic['title']:"",
            "collect_status"=> intval($collect_status),
            "hotindex"  => intval($topic['hotindex']),
            "share_num" => intval($topic['share_num'])+intval($topic['virtual_share']), //edit by hhf 2015.10.28
            "web_url"   => C('SITE_URL')."/api.php?active=".$active."&apptype=0&srv=2414&subject_id=".$topic['topic_id']."&cid=10002&uid=0&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$this->os_type."&version=12",
        );
        if($active){
            if(in_array($topic['topic_id'],array(258))){
                $subject['img'] = $img_arr;
            }else{
                $subject['img'] = new stdClass();
            }
        }else{
            $subject['img'] = $img_arr;
        }

        $this->successView("",$subject);
    }
    /**
    +----------------------------------------------------------
     * 通过专题id获取商品列表列表  2407
    +----------------------------------------------------------
     */
    public function spuList(){
        $subject_id = intval($this->getField('subject_id','',true));//
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;
        // 判断专题状态
        $topic_mod = M("topic");
        $topic_relation_mod = M('topic_relation');
        $topic = $topic_mod->where(" topic_id = {$subject_id} and status = 'published' ")->find();
        if (empty($topic)) {
            $this->errorView($this->ws_code['topic_id_error'],$this->msg['topic_id_error']);
        }
        // 查询专题下的商品
        $topic_spu_list = $topic_relation_mod->where(" topic_id = $subject_id ")->group("spu_id")->order("sort desc,add_time desc")->field("spu_id,image_src,desc")->limit($start,$pg_size)->select();
        if(empty($topic_spu_list)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }


        $topic_attr = arrayFormat($topic_spu_list,'spu_id');

        $id_arr = array_keys($topic_attr);
        $ids = implode(",",$id_arr);
        // 查处所有相关商品
        $spu_mod = M("spu");
        $topic_relation_mod = M("topic_relation");// 关系表

        $spu_list = $spu_mod->where(" spu_id in ( {$ids} ) and status in(0,1)")->field($this->_spu_fields)->order("field(`spu_id`,$ids)")->select();
        $spu_img_mod = M("spu_image");
        $spu_img_list = $spu_img_mod->where("spu_id in ( {$ids} ) and type=1")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");
        // 商品循环
        foreach ($spu_list as $v) {
            $spu_id  = $v['spu_id'] ;
            $type = 0;
            $end_time = 0;
            $price_old = price_format($v['price_old']);
            $status = $v['status'];
            if($v['guide_type']==0){
                $spu_extend = spu_format($spu_id);
                if($spu_extend['type']==2){
                    // 限时限量要显示库存
                    $type = 1;
                }elseif($spu_extend['type']==1){
                    // 限时的不需要显示库存
                    $type = 2;
                }
                if($spu_extend['stocks'] <=0 ){
                    $status = 1;// 失效状态
                }
                $price_now = $spu_extend['price'];
                $end_time  = $spu_extend['end_time'];// 倒计时时间
            }else{
                // 导购商品 分析
                $relation_info = $topic_relation_mod->where("spu_id = {$spu_id} and atom_id>0 and type=0")->field("publish_time,end_time")->find();
                if(isset($relation_info)&&$relation_info['publish_time']<$relation_info['end_time']){
                    $type = 2;
                    $end_time = $relation_info['end_time'];
                }
                $price_now = price_format($v['price_now']);
            }
            // 判断这个商品 应该显示的标签
            $tag = tag_format($type,$v['shipping_free'],$price_now,$price_old);

            $goodses[] = array(
                "id"            => intval($v['spu_id']),
                "title"         => $v['spu_name'],
                "type"          => intval($type),
                "guide_type"    => intval($v['guide_type']),
                "status"        => intval($status),
                "sold_num"      => intval($v['sales']),// 销量
                "surplus_num"   => intval($v['stocks']),
                "img"           =>image($spu_img_list["{$v['spu_id']}"]),
                "price"         => array(
                    "current"=>  $price_now,
                    "prime"  =>  price_format($v['price_old'])
                ),
                "freight"       => 0,
                "time"          => $end_time,
                "tag"           => $tag,
                "fav_num"       => intval($v['fav_count']),
                "desc"          => $topic_attr["{$v['spu_id']}"]['desc'],
                "sub_classify_id"=> 0
            );
        }

        $this->successView($goodses);
    }

    /**
    +----------------------------------------------------------
     *  通过专题id获取专题详情商品列表 2404 H5 页面专用
    +----------------------------------------------------------
     */
    public function detail(){
       $subject_id =  intval($this->getField('subject_id')); //必须接受专题id

       $topic_mod = M("topic");
       $topic_relation_mod = M('topic_relation');
       // 判断专题状态
        $topic = $topic_mod->where(" topic_id = {$subject_id} and status = 'published' ")->find();
        if (empty($topic)) {
            $this->errorView($this->ws_code['topic_id_error'],$this->msg['topic_id_error']);
        }
        // 查询专题下的商品
       $topic_spu_list = $topic_relation_mod->where(" topic_id = $subject_id ")->group("spu_id")->order("sort desc,add_time desc")->field("spu_id,image_src,desc")->select();

        // 专题信息
        $subject = array(
            "id"        => intval($topic['topic_id']),
            "desc"      => $topic['desc'],
            "title"     => $topic['title'],
            "img"       => image($topic['image_src']),
            "hotindex"  => intval($topic['hotindex']),
            "share_num" => intval($topic['share_num'])+intval($topic['virtual_share']), //edit by hhf 2015.10.28
            "web_url"   => ""
        );
        if(empty($topic_spu_list)){
            $data = array(
                "subject"   => $subject,
                "goodses"   => array()
            );
            $this->successView("",$data);
        }
//        p($topic_spu_list);
        $topic_attr = arrayFormat($topic_spu_list,'spu_id');

        $id_arr = array_keys($topic_attr);
        $ids = implode(",",$id_arr);
        // 查处所有相关商品
        $spu_mod = M("spu");
        $spu_list = $spu_mod->where(" spu_id in ( {$ids} ) and status in(0,1) ")->field($this->_spu_fields)->order("field(`spu_id`,$ids)")->select();

        // 商品循环
        foreach ($spu_list as $v) {
            $spu_id  = $v['spu_id'] ;
            $type = 1;
            $price_old = price_format($v['price_old']);
            if($v['guide_type']==0){
                $spu_extend = spu_format($spu_id);
                if($spu_extend['type']==2){
                    // 限时限量要显示库存
                    $type = 1;
                }elseif($spu_extend['type']==1){
                    // 限时的不需要显示库存
                    $type = 2;
                }
                $price_now = $spu_extend['price'];
            }else{
                $price_now = price_format($v['price_now']);
            }
            $agio = agio_format($price_now,$price_old);
            $goodses[] = array(
                "id"            => intval($v['spu_id']),
                "title"         => $v['spu_name'],
                "type"          => intval($type),
                "guide_type"    => intval($v['guide_type']),
                "status"        => intval($v['status']),
                "sold_num"      => intval($v['sales']),// 销量
                "surplus_num"   => intval($v['stocks']),
                "price"         => array(
                       "current"=>  $price_now,
                       "prime"  =>  price_format($v['price_old'])
                    ),
                "freight"       => 0,
                "time"          => 0,
                "fav_num"       => intval($v['fav_count']),
                "posters"       => image($topic_attr["{$v['spu_id']}"]['image_src'],2),
                "desc"          => $topic_attr["{$v['spu_id']}"]['desc'],
                "sub_classify_id"=> 0
            );
        }
        $data = array(
            "subject"      => $subject,
            "goodses"      => $goodses
        );
        $this->successView('',$data);
    }
    /**
    +----------------------------------------------------------
     *   通过大专题id获取小专题列表  2403
    +----------------------------------------------------------
     */
    public function subjectList(){
        $special_id =  intval($this->getField('special_id')); //必须接受专题id
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;


        $group_topic_mod = M("group_topic");
        $result = $group_topic_mod->where(" group_id = {$special_id} ")->order("sort desc ")->limit($start,$pg_size)->getField("topic_id",true);
        // 没有更多数据
        if(empty($result)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }

        $topic_ids = arrayToStr($result);

        $topic_mod = M("topic");
        $topic_list = $topic_mod->where(" topic_id in( {$topic_ids})")->order("field(`topic_id`,{$topic_ids})")->field("topic_id,title,desc,image_src,hotindex,share_num")->select();

        foreach ($topic_list as $v) {
             $subjects[] = array(
                 "id"       => intval($v['topic_id']),
                 "desc"     => $v['desc'],
                 "title"    => $v['title']? $v['title']: '',
                 "img"      => image($v['image_src']),
                 "hotindex" => intval($v['hotindex']),
                 "share_num" => intval($v['share_num'])+intval($v['virtual_share'])//edit by hhf 2015.10.28
             );
        }
        $this->successView($subjects);
    }


    /**
    +----------------------------------------------------------
     *   获取发现首页小专题列表  2405
    +----------------------------------------------------------
     */
    public function topicList(){
        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $pg_size = 10;//强制使用10分页。兼容iOS与安卓
        $start = ($pg_cur - 1)*$pg_size;
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis', $options);
        $expire = 3600;// 缓存暂时设置60秒
        $set_key = C("LS_TOPIC_LIST");// 缓存建名
//        $redis->rm($set_key);
        $obj = unserialize($redis->get($set_key));
        $redis->close();
        if (!isset($obj[$pg_cur])) {
            // 专题表
            $topic_mod = M("topic");
            $topic_list = $topic_mod->where(" status = 'published' and type=1")->order("sort desc,add_time desc")->field("topic_id,title,desc,image_src,hotindex,share_num")->limit($start, $pg_size)->select();
            if(empty($topic_list)){
                $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
            }
            foreach ($topic_list as $v) {
                $subjects[] = array(
                    "id" => intval($v['topic_id']),
                    "desc" => $v['desc'],
                    'title' => $v['title'] ? $v['title'] : '',
                    "img" => image($v['image_src']),
                    "hotindex" => intval($v['hotindex']),
                    "share_num" => intval($v['share_num'])+intval($v['virtual_share']) //edit by hhf 2015.10.28
                );
            }
            $obj[$pg_cur]  = $subjects;
            $redis = Cache::getInstance('Redis', $options);
            $redis->set($set_key,serialize($obj),$expire);
            $redis->close();
        }

        $this->successView($obj[$pg_cur]);
    }

    /**
    +----------------------------------------------------------
     *   调取专题详情 h5 页 2414
    +----------------------------------------------------------
     */
    public function detailWap(){
        $subject_id = intval($this->getField("subject_id"));
        $active = intval($this->getFieldDefault("active",0));
        $this->assign("subject_id",$subject_id);
        $site_url = C("SITE_URL");
        $this->assign("site_url",$site_url);

        if($this->os_type==1){
            // ios
            if($active){

                switch($subject_id){
                    case 253:
                        $active_tpl = "Activity/guideIos914";
                        break;
                    case 257:
                        $active_tpl = "Activity/guideIos917";
                        break;
                    case 258:
                        $active_tpl = "Activity/guideRulerIos";
                        break;
                    case 61:
                    case 192:
                        $active_tpl = "Activity/guideIos917";
                        break;
                    case 62:
                    case 193:
                        $active_tpl = "Activity/guideRulerIos";
                        break;
                    default :
                        $active_tpl = "Activity/guideIos914";
                        break;
                }
                $this->display($active_tpl);
            }else{
                $this->display("Topic/indexIos");
            }

        }else{
            if($active){

                switch($subject_id){
                    case 253:
                        $active_tpl = "Activity/guide914";
                        break;
                    case 257:
                        $active_tpl = "Activity/guide917";
                        break;
                    case 258:
                        $active_tpl = "Activity/guideRuler";
                        break;

                    case 61:

                    case 192:

                        $active_tpl = "Activity/guide917";
                        break;
                    case 62 :
                    case 193:
                        $active_tpl = "Activity/guideRuler";
                        break;
                    default :
                        $active_tpl = "Activity/guide914";
                        break;
                }
                $this->display($active_tpl);
            }else{
                $this->display("Topic/index");
            }
        }

    }
    /**
    +----------------------------------------------------------
     *  专题分享次数统计 1107 接口
    +----------------------------------------------------------
     */
    public function shareStatistics(){
        if(intval($_GET['type'])){//如果type=1 就是任意购的回调接口
            $shareLogMod = M("share_log");
            $data = array();
            $data['type'] = 2;
            $data['user_id'] = $this->uid>0?$this->uid:0;
            $data['create_time'] = time();
            $data['auth_code'] = md5($this->uid."20190910".$this->uid."xiaomiao");
            $flag = $shareLogMod->add($data);
            if($flag){
                $this->successView();
            }
        }else{
            $fav_id = intval($this->getField('fav_id','',true));// 接收
            $topic_mod = M("topic");
            $topic_mod->where("topic_id = {$fav_id}")->setInc("share_num");
            $topic_mod->where("topic_id = {$fav_id}")->setInc("hotindex");
        }
    }
}