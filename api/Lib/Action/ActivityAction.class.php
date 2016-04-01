<?php
/**
 * 活动页数据展示
 * @author nangua
 */

class ActivityAction extends BaseAction{
    private $_spu_fields = "is_auth,spu_id,cid,spu_name,status,type,guide_type,suppliers,taobao_url,sales,price_now,price_old,discount_info,shipping_free,tags_name,open_type,suppliers_id,virtual_sales";

    /**
    +----------------------------------------------------------
     *  获取专场活动的页面 2216
    +----------------------------------------------------------
     */
    public function seqActivity(){
        //==========组合三个时段的商品
        //
        $arr_1 = array(6730,6734);// 10点时段的商品 线上
        $arr_2 = array(6739,6740);// 15点时段的商品
        $arr_3 = array(6217,6744);// 20点时段的商品



        // 特卖活动的相关数据
        $arr_4 = array(6339,6340,6352,6157,6707,5433,6343);// 今日抄底价

        $arr_5 = array(6710,5470,6180,6290,6043,6191,6392,6153,6194,5477,6016,5411,5418,5435);// 让你乐不思蜀的小零食
        $arr_6 = array(6358,6404,6643,6449,6803,6330,6345,6451,6334,6448,6253,6338);//进口直采-全球尖货疯抢
        $arr_7 = array(6644,5996,5439,5461,5458,6058,5989,5471,3471,6335);//干了这杯-我们还是朋友
        $arr_8 = array(6308,6743,6378,6329,5481,5992,6149,6236,6452,6349,6151,6018,5436,6258,5970,6347);// 吃完这口
        $arr_9 = array(6713,6711,5695,6712,5701,5679,6714,6672,6304,6715); // 中秋朝礼
        $spu_mod = M("spu");
        $spu_img_mod = M("spu_image");
        $spu_id_strs = implode(",",$arr_1).",".implode(",",$arr_2).",".implode(",",$arr_3).",".implode(",",$arr_4).",".implode(",",$arr_5).",".implode(",",$arr_6).",".implode(",",$arr_7).",".implode(",",$arr_8).",".implode(",",$arr_9);

        $spu_img_list = $spu_img_mod->where("spu_id in ( {$spu_id_strs} ) and type=1")->order("field(`spu_id`,$spu_id_strs)")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");
        $spu_list = $spu_mod->where("spu_id in($spu_id_strs)")->order("field(`spu_id`,$spu_id_strs)")->field($this->_spu_fields)->select();
        $spu_list = arrayFormat($spu_list,"spu_id");
        $one_arr = array_merge_recursive($arr_1,$arr_2,$arr_3,$arr_4,$arr_5,$arr_6,$arr_7,$arr_8,$arr_9);
        $topic_relation_mod = M('topic_relation');

        foreach($one_arr as $one_v){
            $spu_id  =$one_v ;
            $v =  $spu_list[$one_v];
            $type = 0;
            $end_time = 0;
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
            $tmp_arr  = array(
                "id"            => intval($spu_id),
                "title"         => $v['spu_name'],
                "type"          => intval($type),
                "guide_type"    => intval($v['guide_type']),
                "status"        => intval($v['status']),
                "sold_num"      => intval($v['sales']),// 销量
                "surplus_num"   => intval($v['stocks']),
                "img"           =>image($spu_img_list[$spu_id]),
                "price"         => array(
                    "current"=>  $price_now,
                    "prime"  =>  price_format($v['price_old'])
                ),
                "freight"       => 0,
                "time"          => $end_time,
                "tag"           => $tag,
                "fav_num"       => intval($v['fav_count']),
                "sub_classify_id"=> 0
            );
            if(in_array($spu_id,$arr_1)){
                $ten_arr[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_2)){
                $fifteen_arr[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_3)){
                $twenty_arr[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_4)){
                $floor_two[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_5)){
                $floor_three[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_6)){
                $floor_four[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_7)){
                $floor_five[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_8)){
                $floor_six[] = $tmp_arr;
            }
            if(in_array($spu_id,$arr_9)){
                $floor_seven[] = $tmp_arr;
            }
        }
        // 按时段的商品
        $one_spu_arr = array(
            "ten"       =>  $ten_arr,
            "fifteen"   =>  $fifteen_arr,
            "twenty"    =>  $twenty_arr
        );

        $two_spu_arr = $floor_two;// 第二层的数据
        $three_spu_arr = $floor_three;// 第三层的数据
        $data = array(
            "time"          => time(),
            "one_time"      => strtotime("today +10hour"),
            "two_time"      => strtotime("today +15hour"),
            "three_time"    => strtotime("today +20hour"),
            "one_floor"     => $one_spu_arr,
            "two_floor"     => $two_spu_arr,
            "three_floor"   => $three_spu_arr,
            "four_floor"    => $floor_four,
            "five_floor"    => $floor_five,
            "six_floor"     => $floor_six,
            "seven_floor"   => $floor_seven,
        );
        $this->successView("",$data);// 返回数据
    }


    /**
    +----------------------------------------------------------
     *  获取N元任意购的商品信息 2217
    +----------------------------------------------------------
     */
    public function nYuanActivity(){
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //读取是false
        $Cache = Cache::getInstance('Redis',$options);
        $cachekey = "nyuancache";
        $userId = I('get.userid',0,'intval');//如果不为0则此时用户是登陆的
        $spu = M("spu");
        $spuImgMod = M("spu_image");
        $relationMod = M("topic_relation");
        $anyMod = M("arbitrary_buy");
        $spuAttrMod = M("spu_attr");
        $shareMod = M("share_log");
        $where = array(
            "status" => "published",
            "start_time" => array("elt",time()),
            "end_time" => array("egt",time())
        );
        $anyResult = $anyMod->where($where)->field("id,title,money,num,spu_list,start_time,end_time,view_num,virtual_num")->find();
        if(($cacheNum = $Cache->incr($cachekey))>100){
            $anyMod->where("id = {$anyResult['id']}")->setInc("view_num",$cacheNum);
            $Cache->set($cachekey,0);
        }
        $anyResult['view_num'] = $anyResult['view_num']+$anyResult['virtual_num']+$cacheNum;
        unset($anyResult['virtual_num']);
        $anyResult['money'] = strval($anyResult['money']/100);
        $spuListId = $anyResult['spu_list'];
        $spuList = $relationMod->where("topic_id = {$spuListId}")->field("spu_id,title,desc")->order('sort asc')->select();
        foreach($spuList as $val){
            $spuIds[] = $val['spu_id'];
        }
        $spuList = arrayFormat($spuList,"spu_id");
        $spu_id_strs = implode(',',$spuIds);
        $statusRes = $spu->where("spu_id in ( {$spu_id_strs} ) and status in (0,1)")->order("field(`spu_id`,$spu_id_strs)")->field("spu_id,status")->select();
        foreach($statusRes as $val){
            $tmp['spu_id'] = $val['spu_id'];
            $tmp['title'] = $spuList[$val['spu_id']]['title'];
            $tmp['desc'] = $spuList[$val['spu_id']]['desc'];
            $tmp['status'] = $val['status'];
            $newList[] = $tmp;
        }
        $spuList = $newList;
        $spu_img_list = $spuImgMod->where("spu_id in ( {$spu_id_strs} ) and type=1")->order("field(`spu_id`,$spu_id_strs)")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");
        $attrResult = $spuAttrMod->where("spu_id in ( {$spu_id_strs} ) and attr_id = 12")->field('spu_id,spu_attr_id')->select();
        $attrResult = secToOne($attrResult,"spu_id","spu_attr_id");
        foreach($spuList as $key=>$val){
            $spuList[$key]['img'] = image($spu_img_list[$val['spu_id']]);
            $spuList[$key]['kind_id'] = 12;//口味的类型id
            $spuList[$key]['subkind_id'] = intval($attrResult[$val['spu_id']]);//口味的子属性id
            $spuList[$key]['num'] = 1;//选择的数量
        }
        $anyResult['spu_list'] = $spuList;
        $shareResult = $shareMod->where("user_id = {$userId} and type = 2")->find();//查看用户是否有分享这个页面的行为
        if($shareResult&&$userId!=0){
            $anyResult['share'] = 1;
        }else{
            $anyResult['share'] = 0;
        }
        $this->successView("",$anyResult);// 返回数据
    }

    /**
    +----------------------------------------------------------
     *  主会场调用商品 type=1:口碑爆款 type=2：国民小食 type=2：味蕾旅行 type=2：无肉不欢 type=2：超值精选 2218
    +----------------------------------------------------------
     */
    function mainActivity(){
        //$type = $this->getField("type");
        //$double11 = strtotime("2015-11-11 0:0:0");
        $smallPic = "!s400";//缩略图
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //读取是false
        $Cache = Cache::getInstance('Redis',$options);
        $cacheKey = "mainActivity";
        $today = strtotime(date('Y-m-d'));//获取今天凌晨的时间戳
        $now = time();
        $ten  = $today+36000;//每天10点时间戳
        $three  = $today+54000;//每天15点时间戳
        $eight  = $today+72000;//每天20点时间戳
        $double11 = "1447257600";//双十一凌晨时间
        if(time()>=$double11){
            $isDouble11 = true;
        }else{
            $isDouble11 = false;
        }
        $double10 = "1447171200";//双十一凌晨时间
        if(time()>=$double10){
            $isDouble10 = true;
        }else{
            $isDouble10 = false;
        }
        $return = array();
        $return['isDouble11'] = $isDouble11;
        $return['isDouble10'] = true;
        $return['ten'] = $ten;
        $return['fifteenth'] = $three;
        $return['twenty'] = $eight;
        $return['now'] = $now;
        $appMod = M("app_config");
        $topicRelationMod = M("topic_relation");
        $imgMod = M("spu_image");
        $spuMod = M("spu");
        if(!$Cache->get($cacheKey)) {
            $name = "";
            $mainArray = array(1 => "口碑爆款", 2 => "国民小食", 3 => "味蕾旅行", 4 => "无肉不欢", 5 => "超值精选");
            foreach ($mainArray as $k => $v) {
                $name = $v;
                $type = $k;
                $spuList = $appMod->where("config_name = '{$name}'")->getField("config_value");
                if ($spuList) {
                    $goodsArray = $topicRelationMod->where("topic_id = {$spuList}")->getField("spu_id", true);
                    $where['spu_id'] = array("in", $goodsArray);
                    if ($type != 1 && $type !=2 ) {
                        $where['type'] = 1;
                        $spuImage = $imgMod->where($where)->field("spu_id,images_src")->select();
                        $spuImage = arrayFormat($spuImage, "spu_id");
                    }
                    $result = $topicRelationMod->where("topic_id = {$spuList}")->order("sort asc")->select();
                    $spuArry = array();
                    foreach ($result as $key => $val) {
                        $spu = array();
                        $spu['spu_id'] = $val['spu_id'];
                        if ($type == 1 || $type == 2) {
                            $img = image($val['image_src']);
                            $spu['image_src'] = $img['img_url'];
                            $spuInfo = spu_format($val['spu_id']);
                            if ($spuInfo['stocks'] > 0) {
                                $spu['status'] = 0;
                            } else {
                                $spu['status'] = 1;
                            }
                        } else {
                            $img = image($spuImage[$val['spu_id']]['images_src']);
                            $spu['image_src'] = $img['img_url'].$smallPic;
                            $spuInfo = spu_format($val['spu_id']);
                            $spu['price'] = $spuInfo['price'];
                            if ($spuInfo['stocks'] > 0) {
                                $spu['status'] = 0;
                            } else {
                                $spu['status'] = 1;
                            }
                        }
                        $spu['title'] = $val['title'];
                        $spu['desc'] = $val['desc'];
                        $spuArry[] = $spu;
                    }
                    $spuData["$v"] = $spuArry;
                } else {
                    $spuData["$v"] = array();
                }
            }
            $return['list'] = $spuData;
            $Cache->set($cacheKey,serialize($spuData),600);
        }else{
            $CacheData = $Cache->get($cacheKey);
            $return['list'] = unserialize($CacheData);
        }
        $Cache->close();
        //秒杀商品设置
        $miao = array(7718, 7719, 7720, 7721, 7722, 7723);
        //$miao = array(5069, 5068, 5050, 5049, 5048, 5047);
        //$ten = array(5069,5068);
        //$fifteenthSpu = array(5050,5049);
        //$twentypu = array(5048,5047);
        $whereMiao['spu_id'] = array("in", $miao);
        $miaoRes = $spuMod->where($whereMiao)->field('spu_id,spu_name,price_old,status')->select();
        $miaoRes = arrayFormat($miaoRes, "spu_id");
        $whereMiao['type'] = 1;
        $miaoImg = $imgMod->where($whereMiao)->field("spu_id,images_src")->select();
        $miaoImg = arrayFormat($miaoImg, "spu_id");
        $miaoList = array();
        foreach ($miao as $k => $v) {
            $img = image($miaoImg[$v]['images_src']);
            //$spuInfo = spu_format($v);
            $tmp = array();
            $tmp['spu_id'] = $v;
            $tmp['image_src'] = $img['img_url'].$smallPic;
            $tmp['title'] = $miaoRes[$v]['spu_name'];
            $tmp['status'] = $miaoRes[$v]['status'] < 1 ? 0 : 1;
            $tmp['price'] = $miaoRes[$v]['price_old'] / 100;
            if ($k < 2) {//十点
                $miaoList['ten'][] = $tmp;
            } elseif ($k < 4) {//十五点
                $miaoList['fifteenth'][] = $tmp;
            } else {//二十点
                $miaoList['twenty'][] = $tmp;
            }
        }
        $return['list']['秒杀'] = $miaoList;
        $this->successView("",$return);
    }
}