<?php
/**
 * 商品详情页
 * @author nangua(410966126@qq.com)
 */

class SpuAction extends BaseAction {
    private $_spu_fields = "is_auth,spu_id,cid,spu_name,status,type,guide_type,suppliers,taobao_url,tbk_url,discount_info,details,sales,price_now,price_old,discount_info,shipping_free,tags_name,open_type,suppliers_id,virtual_sales";
//    private $_spu_fields = "spu_id,cid,spu_name,status,type,guide_type,suppliers,taobao_url,discount_info,details,sales,price_now,price_old,discount_info,shipping_free,tags_name,open_type";

    /**
    +----------------------------------------------------------
     * 商品详情id 2502 供H5 页面使用
    +----------------------------------------------------------
     */
    public function detail(){

        $this->addStatisticsLog();

        $spu_id  = intval($this->getField("goods_id"));
        $uid     = intval($this->getFieldDefault("uid",0));
        $spu_mod = M("spu");// spu表
        $spu_img_mod = M("spu_image");// 商品图标
        $spu = $spu_mod->where("spu_id = {$spu_id}")->field($this->_spu_fields)->find();

        if(empty($spu)){
            $this->errorView($this->ws_code['spu_not_exist'],$this->msg['spu_not_exist']);
        }
        $now = time();
        // 查一次数据库获取 轮播图片
        $spu_imgs = $spu_img_mod->where(" spu_id = {$spu_id} and type =3")->field("images_src,type")->order("add_time asc")->limit(5)->select();

        // 主图
        $main_imgs = array();
        foreach ($spu_imgs as $v) {
            // 轮播主图
            if ($v['type'] == 3 ) {
                $main_imgs[]    = image($v['images_src']);
            }
        }

        $price_old = price_format($spu['price_old']);
        $type = 0;
        $end_time = 0;// 倒计时结束时间
        $status = $spu['status'];
        if($spu['guide_type']==0){// 平台商品
            $spu_extend = spu_format($spu_id);

            if($spu_extend['type']==2){
                // 限时限量要显示库存
                $type = 1;
            }elseif($spu_extend['type']==1){
                // 限时的不需要显示库存
                $type = 2;
            }
            $price_now = $spu_extend['price'];
            $sold_num  = $spu_extend['sale'];
            $stock_num = $spu_extend['stocks'];
            $original_price =  getOriginalPrice($spu_id);
            if($stock_num <= 0 ){
                $status = 1;// 失效状态
            }
            $end_time  = $spu_extend['end_time'];// 倒计时时间
        }else{
            $sold_num  = $spu['sales'];
            $stock_num = $spu['stocks'];
            $price_now = price_format($spu['price_now']);
            // 分析这个导购商品是否在特卖期
            $topic_relation_mod = M("topic_relation");
            // 需保证atom_id 大于0 说明这个导购商品有在做活动
            $relation_info = $topic_relation_mod->where("spu_id = {$spu_id} and atom_id>0 and type=0 and status='published' and publish_time< {$now} and end_time > {$now} ")->field("publish_time,end_time")->find();
            if(isset($relation_info)&&$relation_info['publish_time']<$relation_info['end_time']){
                $type = 2;
                $end_time = $relation_info['end_time'];
            }
        }

        $agio = agio_format($price_now,$price_old);

        //--------商品标签 开始-----------------------
        $tags[] = array(
            "title" => $agio ? $agio:'',
            "color" => "#ff2d4b"
        );
        if($spu['shipping_free']){
            $tags[] = array(
                "title" => "包邮",
                "color" => "#40bff5"
            );
        }else{
            $tags[] = array(
                "title" => "",
                "color" => "#40bff5"
            );
        }

        $tags[] = array(
            "title" => $spu['is_auth'] ? "认证":"",
            "color" => "#9acf4f"
        );
        $tags[] = array(
            "title" => $spu['tags_name'],
            "color" => "#9acf4f"
        );
        //--------商品标签 结束-----------------------

        //--------评论信息获取------------------------
        $comment_mod = M("comment");
        $comment_list = $comment_mod->where("spu_id = {$spu_id} ")->field("id,avatar_src,nickname,comment,type")->limit(2)->order("add_time desc")->select();
        $comment_total = $comment_mod->where("spu_id = {$spu_id} ")->count();
        foreach ($comment_list as $v) {

            if($v['type']==2){
                // 需要截取用户名
                $nickname = mb_substr($v['nickname'],0,1,'utf-8').'**'.mb_substr($v['nickname'],-1,1,'utf-8');
            }else{
                $nickname = $v['nickname'];
            }


            $comment_arr[] = array(
                "id"        => intval($v['id']),
                "avatar"    => array(
                        "img_url"   => $v['avatar_src'],
                        "img_w"     => 100,
                        "img_h"     => 100
                    ),
                "nickname"  => $nickname?$nickname:"小喵小伙伴",
                "content"   => $v['comment'],
            );
        }
        $comment_list = array(
            "total_num"     => intval($comment_total),
            "comments"      => $comment_arr ? $comment_arr:array()
        );

        $comments = $comment_list;
        //-----------------评论信息获取结束-------------

        //-----------------商品参数获取开始-------------
        if ($spu['guide_type'] == 0) {//  非导购商品
            $spu_attr_mod = M("spu_attr");
            $type_attr_mod = M("type_attr");
            $attr_arrs = $type_attr_mod->where(" attr_type = 0 ")->field("attr_name,attr_id")->select();
            $attr_ids = arrayToStr($attr_arrs,'attr_id');
            $spu_attr_list = $spu_attr_mod->where("spu_id = {$spu_id} and attr_id in ($attr_ids)")->order("field(`attr_id`,$attr_ids)")->field("attr_id,attr_value")->select();
            foreach ($attr_arrs as $v) {
                $tmp_attr["{$v['attr_id']}"] = $v['attr_name'];
            }

            $infos[]  = array(
                "key"    => "供应商",
                "value"  => $spu['suppliers']
            );
            foreach ($spu_attr_list as $v) {
                $infos[] = array(
                    "key"    => $tmp_attr["{$v['attr_id']}"] ,
                    "value"  => $v['attr_value'],
                );
            }

            $arguments = array(
                "title"  => "美味信息",
                "infos"  => $infos
            );

            $kind_info = $type_attr_mod->where("type_id = 4 and attr_type = 1")->field("attr_id,attr_name")->find();
            // 获取 种类的 id
            $spu_arr_list = $spu_attr_mod->where("spu_id = {$spu_id} and attr_id = {$kind_info['attr_id']}")->field("spu_attr_id,attr_value")->select();
            $spu_attr_ids  = arrayToStr($spu_arr_list,'spu_attr_id');

            // 库存表
            $spu_sku_mod  = M("sku_list");

            $spu_sku_list = $spu_sku_mod->where("spu_id ={$spu_id} and attr_combination in({$spu_attr_ids})")->order("field(`attr_combination`,$spu_attr_ids)")->field("sku_id,attr_combination,sku_stocks,price")->select();

            $sku_stocks = $spu_extend['sku_stocks'];
            $sku_stocks = secToOne($sku_stocks,"sku_id","sku_stocks");

            $sku_price_arr = $spu_extend['sku_price'];
            $sku_price_arr = secToOne($sku_price_arr,"sku_id","price");

            foreach ($spu_sku_list as $v) {
                $subjec_info["{$v['attr_combination']}"] = array(
                    "sku_stocks"  => $sku_stocks["{$v['sku_id']}"],
                    "price"       => $sku_price_arr["{$v['sku_id']}"]
                );
            }
            $sub_kinds = array();
            foreach ($spu_arr_list as $v) {
                $current  =  $subjec_info["{$v['spu_attr_id']}"]['price'];
                $sub_kinds[] = array(
                    "id"            => (int)$v['spu_attr_id'],
                    "title"         => $v['attr_value'],
                    "price"         => array(
                        "current"   => price_format($current),
                        "prime"     => price_format($spu['price_old'])
                    ),
                    "surplus_num"   => (int)$subjec_info["{$v['spu_attr_id']}"]['sku_stocks']
                );
            }

            $kinds = array(
                "id"       => (int)$kind_info['attr_id'],
                "title"    => $kind_info['attr_name'],
                "kinds"    => $sub_kinds
            );
        }else{
            $arguments = array();
            $kinds = array();// 种类 指口味等信息
        }

        //-----------------商品参数获取结束-------------
//         $guess_love_mod = M("guess_love");
//         $love_id_arr = $guess_love_mod->where("spu_id = {$spu_id} ")->limit(4)->getField("love_id",true);

//         $less_count = 4-count($love_id_arr);// 还缺多少个猜你喜欢的商品
//         if(!empty($love_id_arr)){
//             $love_ids = arrayToStr($love_id_arr);
//             $love_list = $spu_mod->where("status in(0,1) and spu_id in ($love_ids)")->field("spu_id,status,spu_name,type,guide_type,price_now,price_old")->select();
//             $less_count = 4 - count($love_list);
//             // 获取猜你喜欢 商品
//             // 影响因素 喜欢中的商品下架了，如果是特卖商品，特卖商品
//         }

//         if ($less_count > 0) {
//             if(!empty($love_ids)){
//                 $guess_filter_ids = $love_ids.','.$spu_id;
//             }else{
//                 $guess_filter_ids = $spu_id;
//             }

//             $less_list = $spu_mod->where("cid = {$spu['cid']} and spu_id not in($guess_filter_ids) and status in(0,1)  ")->field("spu_id,status,spu_name,type,guide_type,price_now,price_old,shipping_free")->limit($less_count)->select();
//             foreach ($less_list as $v) {
//                 $love_list[] = $v;
//             }
//         }
//******************************************本次修改代码1、先取同分类下商品 2、不够时取新上架商品补齐****start**********************************************//
    $less_count = 4;
        $love_list = array();
		$where = '';
		$aa='';
		$spuids = array();
		$spu_mod = M('spu');
		$cat_mod = M('spu_cate_relation');
		//查出本商品分类Id
		$cateids = $cat_mod ->where("  spu_id = {$spu_id} ")->field("cate_id")->select(); 
		if(count($cateids)>0){
    		if(count($cateids)>1) {
                $ids=array();
        		foreach($cateids as $k=>$v){
        		    $ids[]=$v['cate_id'];
        		}
        		$aa=implode(',',$ids);
        		$where = "cate_id in ($aa) and ";
    		}
    		if(count($cateids)==1){
    		  $cid = $cateids[0][cate_id];  
    		  $where = "cate_id in ($cid) and ";
    		}
    		$spuids = $cat_mod ->where(" $where spu_id <> {$spu_id} ")->field("spu_id")->order( "rand()" )->select();
		}
		//获取到一批同品类下的spu_id
		if(count($spuids)>0){
    		if(count($spuids)>1) {
        		foreach($spuids as $kk=>$vv){
        		    $idss[]=$vv['spu_id'];
        		}
        		$bb=implode(',',$idss);
        		$wheres = "spu_id in($bb) and ";
    		}
    		
    		if(count($spuids)==1){
    		    $id = $spuids[0][spu_id];
    		    $wheres = "spu_id in($id) and ";  
    		}
    		$love_list = $spu_mod->where(" $wheres  status in(0,1) and guide_type=0 ")->field("spu_id,spu_name,type,guide_type,price_now,price_old")->limit($less_count)->order( "rand()" )->select();
		}	
		$res = 4;
        $res=$less_count - count($love_list);        
        //计算总数-同分类下=不够补齐的（新上线且上架或售空的）
        if($res > 0){
            $w='';
            $sid=array();
            if(count($love_list)>0){
        		if(count($love_list)>1) {
            		foreach($spuids as $kk=>$vvv){
            		    $sid[]=$vvv['spu_id'];
            		}
            		$cc=implode(',',$sid);
            		$w = "spu_id not in($cc) and ";
        		}
        		
        		if(count($love_list)==1){
        		    $id = $love_list[0][spu_id];
        		    $w = "spu_id not in($id) and ";  
        		}
        		
    		}
            $less_list = $spu_mod->where(" {$w} spu_id <> {$spu_id}  and status in(0,1) and guide_type=0 ")->field("spu_id,spu_name,type,guide_type,price_now,price_old")->limit($res)->order('spu_id desc')->select();
            foreach ($less_list as $v) {
                $love_list[] = $v;
            } 
        }
//******************************************本次修改代码1、先取同分类下商品 2、不够时取新上架商品补齐******end**************************************************//
        $guess_love_ids = arrayToStr($love_list,'spu_id');
        $spu_img_list = $spu_img_mod->where("spu_id in($guess_love_ids) and type = 1")->order("field(`spu_id`,{$guess_love_ids})")->field("spu_id,images_src")->select();
        // 将图片spu_id 转成建名
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");

        $guess_love = array();//初始化
        foreach ($love_list as $v) {
            // 猜你喜欢获取最新价格
            $love_type = 0;
            $love_spu_extend = array();
            if($v['guide_type']==0){
                $love_spu_extend = spu_format($v['spu_id']);
                $love_price_now = $love_spu_extend['price'];
                $love_type = $love_spu_extend['type'];
            }else{
                $love_price_now = price_format($v['price_now']);
            }

            $love_price_old = price_format($v['price_old']);
            $love_tag = tag_format($love_type,$v['shipping_free'],$love_price_now,$love_price_old);
            // 猜你喜欢获取最新价格
            $guess_love[] = array(
                "id"                => intval($v['spu_id']),
                "title"             => $v['spu_name'],
                "type"              => intval($love_type),
                "guide_type"        => intval($v['guide_type']),
                "status"            => intval($v['status']),
                "sold_num"          => 0,
                "surplus_num"       => 0,
                "price"             => array(
                    "current" => $love_price_now,
                    "prime"   => $love_price_old
                ),
                "img"               => image($spu_img_list["{$v['spu_id']}"]),
                'freight'           => 0,
                "time"              => 0,
                "tag"               => $love_tag,
                "fav_num"           => 0,
//                "posters"           => array(),
                "desc"              => '',
                "sub_classify_id"   => 0
            );
        }
        // 是否是导购商品
        if($spu['guide_type']>0){
            $preg = '/&id=\d+/';
            preg_match_all($preg, $spu['taobao_url'],$id_res);
            $id = $id_res['0']['0'];
            $item_id = str_replace('&id=', '', $id);
            if(empty($item_id)){
                $preg = '/id=\d+/';
                preg_match_all($preg, $spu['taobao_url'],$id_res);
                $id = $id_res['0']['0'];
                $item_id = str_replace('id=', '', $id);
            }


        }else{
            $item_id = 0;
        }




        $GoodsDetail = array(
            "id"            => intval($spu_id),
            "title"         => $spu['spu_name'],
            "type"          => intval($type),
            "guide_type"    => intval($spu['guide_type']),
            "status"        => intval($status),//
            "sold_num"      => intval($sold_num+$spu['virtual_sales']),//
            "surplus_num"   => intval($stock_num),
            "price"         => array(
                        "current" => $price_now,
                        "prime"   => $price_old,
                        "original" => $original_price,
                    ),
            "img"           => new stdClass(),
            "freight"       => 6.00,
            "time"          => $end_time,//
            "server_time"   => time(),
            "tag"           => new stdClass(),
            "fav_num"       => 0,
            "posters"       => 0,
            "desc"          => $spu['desc']? $spu['desc']: $spu['spu_name'],
            "sub_classify_id"=> 0,
            "tags"          => $tags,
            "main_imgs"     => $main_imgs,
            "details"       => $spu['details'],
            "comments"      => $comment_list,
            "kinds"         => $kinds,
            "arguments"     => $arguments,
            "guess_love"    => $guess_love,
            "real_id"       => $item_id
        );
        // 如果是 2.0.1 版本则加上优惠券的结构体

            // 搜索
        $free_fee_template_mod = M("free_fee_template");
        $now = time();

        // 先检查是否有平台包邮
        if($spu['guide_type'] == 0){
            $plat_form_free = $free_fee_template_mod->where("type = 2 and status='published' and start_time<={$now} and end_time>={$now}")->order("price asc")->field("template_name")->find();
            $free_title = '';
            // 运营需求变更,过滤某些商品不显示全平台包邮活动
            $filter_free_arr = C("LS_FREE_SHIPPING");

            if(!empty($plat_form_free)) {

                $free_title = $plat_form_free['template_name'];
            }else{
                $suppliers_free_fee = $free_fee_template_mod->where("suppliers_id = {$spu['suppliers_id']} and status='published' and start_time<={$now} and end_time>={$now}")->order("price asc")->field("template_name")->find();
                $free_title = $suppliers_free_fee['template_name'] ? $suppliers_free_fee['template_name']: '';
            }
            if(!empty($free_title)&&!in_array($spu_id,$filter_free_arr)){
                $os_version = $this->version;
                $GoodsDetail['activity'] = array(
                    "icon_title" => $this->version ==11? "满包邮":"优惠",
                    "title"    => $free_title,
                    "action"   => array(
                        "type"  => 1,
                        "info"  => $spu['suppliers_id']
                    )
                );

            }elseif($this->version>=12){
                // 优惠券活动更新

                $coupon_spu_mod = M("coupon_spu");
                $coupon_num_arr = $coupon_spu_mod->where("spu_id = {$spu_id} or spu_id = 1")->getField("coupon_num",true);

                if(!empty($coupon_num_arr)){

                    $coupon_num_str = "'".implode('\',\'',$coupon_num_arr)."'";
                    $coupon_mod = M('coupon');

                    $coupon = $coupon_mod->where("coupon_num in($coupon_num_str) and status='published' and use_end_time>={$now} and type = 1")->order("rule_free desc,rule_money asc")->field("name,suppliers_id")->find();

                    // 确保有数据
                    if(!empty($coupon)){
                        //
                        $GoodsDetail['activity'] = array(
                            "icon_title" => "优惠",
                            "title"    => $coupon['name'],
                            "action"   => array(
                                "type"  => 1,
                                "info"  => $spu['suppliers_id']
                            )
                        );
                    }else{
                        // 可能关系表已存在，但实际上已下架的。
                        $GoodsDetail['activity'] = new stdClass();
                    }
                }else{
                    // 没有优惠券活动
                    $GoodsDetail['activity'] = new stdClass();
                }
            }else{
                // 11 版本 在没有包邮的情况下返回 空
                $GoodsDetail['activity'] = new stdClass();
            }
        }else{
            $GoodsDetail['activity'] = new stdClass();
        }
        $this->successView('',$GoodsDetail);
    }

    /**
    +----------------------------------------------------------
     * 添加访问记录
    +----------------------------------------------------------
     */
    public function addStatisticsLog()
    {
        $statistics_analysis2_mod = M('statistics_analysis');
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);

        $time = time();
        $today_time = strtotime(date("Y-m-d", $time) . " 00:00:00");
        $yes_time = $today_time - 24*60*60;

        //获取昨天缓存中的数据如果有数据则存入数据库并清空缓存
        $yes_log = unserialize($redis->get($yes_time));
        $redis->rm($yes_time);
        if (!empty($yes_log)) {
            $data = array();
            $tmp_data = array();
            foreach($yes_log as $k => $v) {
                $tmp = $statistics_analysis2_mod->where("date=".$yes_time." and goods_id=".$k)->field("id")->find();
                if (empty($tmp)) {
                    $tmp_data['date'] = $yes_time;
                    $tmp_data['goods_id'] = $k;
                    $tmp_data['click_num'] = $v;
                    $data[] = $tmp_data;
                } else {
                    $statistics_analysis2_mod->where("id=".$tmp['id'])->setInc("click_num", $v);
                }
            }
            if (!empty($data)) {
                $statistics_analysis2_mod->addAll($data);
            }
        }

        //获取今天缓存中的数据如果有数据的click_num达到10或以上则存入数据库并清除该条缓存
        $today_log = unserialize($redis->get($today_time));
        $redis->rm($today_time);
        $data = array();
        $tmp_data = array();
            if ($today_log[I("get.goods_id")] >= 10) {
                if (!$statistics_analysis2_mod->where("date=".$today_time." and goods_id=".I("get.goods_id"))->setInc("click_num", $today_log[I("get.goods_id")])) {
                    $tmp_data['date'] = $today_time;
                    $tmp_data['goods_id'] = I("get.goods_id");
                    $tmp_data['click_num'] = $today_log[I("get.goods_id")];
                    $statistics_analysis2_mod->add($tmp_data);
                }
                unset($today_log[I("get.goods_id")]);
            }


        //把目前访问的记录放入缓存
        if (!empty($today_log[I("get.goods_id")])) {
            $today_log[I("get.goods_id")] += 1;
        } else {
            $today_log[I("get.goods_id")] = 1;
        }
        $redis->set($today_time, serialize($today_log));

    }


    /**
    +----------------------------------------------------------
     * 供客户端使用 2506     2505 获取到到的url
    +----------------------------------------------------------
     */
    public function template(){
        $spu_id = intval($this->getField("goods_id"));
        $site_url = C("SITE_URL");
        $this->assign("site_url",$site_url);
        $this->assign("spu_id",$spu_id);

        if($this->os_type==1){
            // ios
            $this->display("Spu/templateIos");
        }else{
            $this->display("Spu/template");
        }
    }
    /**
    +----------------------------------------------------------
     * 商品详情id 2505 供客户端使用
    +----------------------------------------------------------
     */
    public function detailApp(){

        $spu_id  = intval($this->getField("goods_id"));
        $uid     = intval($this->getFieldDefault("uid",0));//
        $spu_mod = M("spu");// spu表
        $spu_img_mod = M("spu_image");// 商品图标
        $spu = $spu_mod->where("spu_id = {$spu_id} ")->field($this->_spu_fields)->find();
        // 不存在该商品
        if(empty($spu)){
            $this->errorView($this->ws_code['spu_not_exist'],$this->msg['spu_not_exist']);
        }



        // 是否是导购商品
        if($spu['guide_type']>0){
            $preg = '/&id=\d+/';
            preg_match_all($preg, $spu['taobao_url'],$id_res);
            $id = $id_res['0']['0'];
            $item_id = str_replace('&id=', '', $id);
            if(empty($item_id)){
                $preg = '/id=\d+/';
                preg_match_all($preg, $spu['taobao_url'],$id_res);
                $id = $id_res['0']['0'];
                $item_id = str_replace('id=', '', $id);
            }
        }else{
            $item_id = 0;
        }

        $active_info = array("status" => 0);//默认为0 不显示拍立减
        if ($spu['guide_type'] == 0) {//  非导购商品
            $spu_extend = spu_format($spu_id);
            $status = $spu_extend['stocks']? 0:1;// 1 表示失效，也就是库存是0

            $spu_attr_mod = M("spu_attr");
            $type_attr_mod = M("type_attr");

            $kind_info = $type_attr_mod->where("type_id = 4 and attr_type = 1")->field("attr_id,attr_name")->find();
            // 获取 种类的 id
            $spu_arr_list = $spu_attr_mod->where("spu_id = {$spu_id} and attr_id = {$kind_info['attr_id']}")->field("spu_attr_id,attr_value")->select();
            $spu_attr_ids  = arrayToStr($spu_arr_list,'spu_attr_id');
            // 库存表
            $spu_sku_mod  = M("sku_list");
            $spu_sku_list = $spu_sku_mod->where("spu_id ={$spu_id} and attr_combination in({$spu_attr_ids})")->order("field(`attr_combination`,$spu_attr_ids)")->field("sku_id,attr_combination,sku_stocks,price")->select();


            $sku_stocks = $spu_extend['sku_stocks'];
            $sku_stocks = secToOne($sku_stocks,"sku_id","sku_stocks");

            $sku_price_arr = $spu_extend['sku_price'];
            $sku_price_arr = secToOne($sku_price_arr,"sku_id","price");

            foreach ($spu_sku_list as $v) {
                $subjec_info["{$v['attr_combination']}"] = array(
                    "sku_stocks"  => $sku_stocks["{$v['sku_id']}"],
                    "price"       => $sku_price_arr["{$v['sku_id']}"]
                );
            }

            $sub_kinds = array();
            foreach ($spu_arr_list as $v) {
                $current  =  $subjec_info["{$v['spu_attr_id']}"]['price'];
                $sub_kinds[] = array(
                    "id"            => (int)$v['spu_attr_id'],
                    "title"         => $v['attr_value'],
                    "price"         => array(
                        "current"   => price_format($current),
                        "prime"     => price_format($spu['price_old'])
                    ),
                    "surplus_num"   => (int)$subjec_info["{$v['spu_attr_id']}"]['sku_stocks']
                );
            }

            $kinds[] = array(
                "id"       => intval($kind_info['attr_id']),
                "title"    => $kind_info['attr_name'],
                "kinds"    => $sub_kinds
            );
        }else{

            // 拍立减判断
            $now = time();
            $limit_buy_mod = M("limit_buy");
            $spu_active = $limit_buy_mod->where("spu_id = $spu_id")->field('is_use,start_time,end_time,limit_num')->find();
            if(!empty($spu_active)&&$spu_active['is_use']==1){
                if($now<=$spu_active['end_time']&&$now>=$spu_active['start_time']){

                    $active_info = array(
                        "real_price"    => price_format($spu_active['limit_num']),
                        "end_time"      => intval($spu_active['end_time'] ),
                        "status"        => 1
                    );
                }else{
                    $active_info = array(
                        "status"    => 0
                    );
                }

            }else{
                $active_info = array(
                    "status"    => 0
                );
            }


            $status = $spu['status'];
            $kinds = array();// 种类 指口味等信息
        }
        // 状态大于1 统一表示下架状态
        if($spu['status']>1){
            $status = 2;
        }
        $spu_img_mod = M("spu_image");// 商品图标
        $spu_img = $spu_img_mod->where("spu_id = {$spu_id} and type=1")->getField("images_src");

        // 查一次数据库获取两套规格

        // 判断用户是否已收藏过该商品
        $fav_mod = M("favs");
        $fav_count = $fav_mod->where("user_id = {$this->uid} and type = 0 and opt_id = {$spu_id}")->count();

        $GoodsDetail = array(
            "id"             => intval($spu_id),
            "url"            => C("SITE_URL")."/api.php?apptype=0&srv=2506&goods_id=".$spu_id."&cid=10002&uid=".$uid."&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$this->os_type."&version=".$this->version,
            "collect_status" => $fav_count? 1:0,
            "desc"           => "这里的零食看了好想吃",
            "title"          => $spu['spu_name'],
            "status"         => intval($status),// 待定
            "guide_type"     => intval($spu['guide_type']),
            "img"            => image($spu_img),
            "kinds"          => $kinds,
            "guide_info"     => array(
                "open_type"   => intval($spu['open_type']),// 0 百川 1 淘宝链接
                "real_url"    => htmlspecialchars_decode($spu['tbk_url']),
                "real_id"     => intval($item_id)
            ),
            "active_info"     => $active_info
        );

        $this->successView('',$GoodsDetail);
    }
    /**
    +----------------------------------------------------------
     * 通过商品id获取商品评价列表 2507 载入模板
    +----------------------------------------------------------
     */
    public function templateComment(){
        $spu_id = intval($this->getField("goods_id"));
        $this->assign("spu_id",$spu_id);
        $this->display("Spu/commentList");
    }
    /**
    +----------------------------------------------------------
     * 通过商品id获取商品评价列表 2503
    +----------------------------------------------------------
     */
    public function commentList(){

        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $goods_id = intval($this->getField("goods_id"));
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;

        // 评论模型
        $comment_mod = M("comment");
        $total_num = $comment_mod->where(" spu_id = {$goods_id} ")->count();
        $comment_list = $comment_mod->where(" spu_id = {$goods_id} ")->order("add_time desc ")->limit($start,$pg_size)->field("id,avatar_src,nickname,comment,type")->select();
        // 没有更多数据
        if(empty($comment_list)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }
        foreach ($comment_list as $v) {
            if($v['type']==2){
                // 需要截取用户名
                $nickname = mb_substr($v['nickname'],0,1,'utf-8').'**'.mb_substr($v['nickname'],-1,1,'utf-8');
            }else{
                $nickname = $v['nickname'];
            }

            $comments[] = array(
                 "id"        => intval($v['id']),
                 "avatar"    => $v['avatar_src'],
                 "nickname"  => $nickname?$nickname:"小喵小伙伴",
                 "content"   => $v['comment']
            );
        }
        $comments_list = array(
            "total_num"     => intval($total_num),
            "comments"      => $comments
        );
        $this->successView('',$comments_list);
    }
}