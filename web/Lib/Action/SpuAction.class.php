<?php
/**
 * 商品详情页
 * @author nangua(410966126@qq.com)
 */

class SpuAction {
    private $_spu_fields = "spu_id,cid,spu_name,type,guide_type,suppliers,taobao_url,discount_info,details,sales,price_now,price_old,discount_info,shipping_free,tags_name";

    /**
    +----------------------------------------------------------
     * 商品详情id  供H5 页面使用
    +----------------------------------------------------------
     */
    public function detail(){

        $spu_id  = intval($_GET("goods_id"));
        if($spu_id){
            $this->error("商品id错误");// 需要修改 反应的错误信息
        }
        $spu_mod = M("spu");// spu表
        $spu_img_mod = M("spu_image");// 商品图标
        $spu = $spu_mod->where("spu_id = {$spu_id} ")->field($this->_spu_fields)->find();

        // 查一次数据库获取两套规格
        $spu_imgs = $spu_img_mod->where(" spu_id = {$spu_id} and type in(3,4)")->field("images_src,type")->select();

        // 主图
        foreach ($spu_imgs as $v) {
            // 轮播主图
            if ($v['type'] == 3 ) {
                $main_imgs[]    = image($v['images_src']);
            }else{
            // 详情图
                $details_imgs[] = image($v['images_src']);
            }
        }

//        p($spu);

        //--------商品标签 开始-----------------------
        $tags[] = array(
            "title" => $spu['discount_info'] ? $spu['discount_info']:'',
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
            "title" => "认证",
            "color" => "#9acf4f"
        );
        $tags[] = array(
            "title" => $spu['tags_name'],
            "color" => "#9acf4f"
        );
        //--------商品标签 结束-----------------------

        //--------评论信息获取------------------------
        $comment_mod = M("comment");
        $comment_list = $comment_mod->where("spu_id = {$spu_id} ")->field("id,avatar_src,nickname,comment")->limit(2)->order("add_time desc")->select();
        $comment_total = $comment_mod->where("spu_id = {$spu_id} ")->count();
        foreach ($comment_list as $v) {
            $comment_arr[] = array(
                "id"        => intval($v['id']),
                "avatar"    => array(
                        "img_url"   => $v['avatar_src'],
                        "img_w"     => 100,
                        "img_h"     => 100
                    ),
                "nickname"  => $v['nickname'],
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
            //获取商品的原始价格
            $original_price =  getOriginalPrice($spu_id);
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
            foreach ($spu_sku_list as $v) {
                $subjec_info["{$v['attr_combination']}"] = array(
                    "sku_stocks"  => $v['sku_stocks'],
                    "price"       => $v['price']
                );
            }

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
//         $love_id_arr = $guess_love_mod->where("spu_id = {$spu_id} ")->getField("love_id",true);
//         $less_count = 4-count($love_id_arr);// 还缺多少个猜你喜欢的商品
//         if(!empty($love_id_arr)){
//             $love_ids = arrayToStr($love_id_arr);
//             $love_list = $spu_mod->where("status = 0 and spu_id in ($love_ids)")->field("spu_id,spu_name,type,guide_type,price_now,price_old")->select();
//             // 获取猜你喜欢 商品
//             // 影响因素 喜欢中的商品下架了，如果是特卖商品，特卖商品
//         }
//         if ($less_count > 0) {
//             $less_list = $spu_mod->where("cid = {$spu['cid']} and spu_id <> {$spu_id} ")->field("spu_id,spu_name,type,guide_type,price_now,price_old")->limit($less_count)->select();
//             foreach ($less_list as $v) {
//                 $love_list[] = $v;
//             }
//         }
//***********************************猜你喜欢 算法  一：先取同分类下商品，二：不够时取最新上架商品补齐************start*********************************************//
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
//******************************************************************end***************************//
        $guess_love_ids = arrayToStr($love_list,'spu_id');
        $spu_img_list = $spu_img_mod->where("spu_id in($guess_love_ids) and type = 1")->order("field(`spu_id`,{$guess_love_ids})")->field("spu_id,images_src")->select();
        // 将图片spu_id 转成建名
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");

        foreach ($love_list as $v) {
            $guess_love[] = array(
                "id"                => intval($v['spu_id']),
                "title"             => $v['spu_name'],
                "type"              => intval($v['type']),
                "guide_type"        => intval($v['guide_type']),
                "status"            => intval($v['status']),
                "sold_num"          => 0,
                "surplus_num"       => 0,
                "price"             => array(
                    "current" => price_format($v['price_now']),
                    "prime"   => price_format($v['price_old'])
                ),
                "img"               => image($spu_img_list["{$v['spu_id']}"]),
                'freight'           => 0,
                "time"              => 0,
                "tag"               => new stdClass(),
                "fav_num"           => 0,
                "posters"           => array(),
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
            "id"            => (int)$spu_id,
            "title"         => $spu['spu_name'],
            "type"          => (int)$spu['type'],
            "guide_type"    => (int)$spu['guide_type'],
            "status"        => 0,// 待定
            "sold_num"      => (int)$spu['sales'],// 待定
            "surplus_num"   => (int)$spu['stocks'],
            "price"         => array(
                        "current" => price_format($spu['price_now']),
                        "prime"   => price_format($spu['price_old']),
                        "original" => $original_price,
                    ),
            "img"           => new stdClass(),
            "freight"       => 6.00,
            "time"          => time(),// 待定
            "server_time"   => time(),
            "tag"           => new stdClass(),
            "fav_num"       => 0,
            "posters"       => 0,
            "desc"          => "",
            "sub_classify_id"=> 0,
            "tags"          => $tags,
            "main_imgs"     => $main_imgs,
            "details_imgs"  => $details_imgs,
            "comments"      => $comment_list,
            "kinds"         => $kinds,
            "arguments"     => $arguments,
            "guess_love"    => $guess_love,
            "real_id"       => $item_id
        );

        $this->successView('',$GoodsDetail);
    }

    /**
    +----------------------------------------------------------
     * 供客户端使用 2506 2502 获取到到的url
    +----------------------------------------------------------
     */
    public function share(){
        $spu_id = intval($this->getField("goods_id"));
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
        $spu_mod = M("spu");// spu表
        $spu_img_mod = M("spu_image");// 商品图标
        $spu = $spu_mod->where("spu_id = {$spu_id} ")->field($this->_spu_fields)->find();


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

        if ($spu['guide_type'] == 0) {//  非导购商品
            $spu_attr_mod = M("spu_attr");
            $type_attr_mod = M("type_attr");

            $kind_info = $type_attr_mod->where("type_id = 4 and attr_type = 1")->field("attr_id,attr_name")->find();
            // 获取 种类的 id
            $spu_arr_list = $spu_attr_mod->where("spu_id = {$spu_id} and attr_id = {$kind_info['attr_id']}")->field("spu_attr_id,attr_value")->select();
            $spu_attr_ids  = arrayToStr($spu_arr_list,'spu_attr_id');
            // 库存表
            $spu_sku_mod  = M("sku_list");
            $spu_sku_list = $spu_sku_mod->where("spu_id ={$spu_id} and attr_combination in({$spu_attr_ids})")->order("field(`attr_combination`,$spu_attr_ids)")->field("sku_id,attr_combination,sku_stocks,price")->select();
            foreach ($spu_sku_list as $v) {
                $subjec_info["{$v['attr_combination']}"] = array(
                    "sku_stocks"  => $v['sku_stocks'],
                    "price"       => $v['price']
                );
            }

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
                "id"       => (int)$kind_info['attr_id'],
                "title"    => $kind_info['attr_name'],
                "kinds"    => $sub_kinds
            );
        }else{

            $kinds = array();// 种类 指口味等信息
        }
        $spu_img_mod = M("spu_image");// 商品图标
        $spu_img = $spu_img_mod->where("spu_id = {$spu_id} and type=1")->getField("images_src");

        // 查一次数据库获取两套规格

        // 判断用户是否已收藏过该商品
        $fav_mod = M("favs");
        $fav_count = $fav_mod->where("user_id = {$this->uid} and type = 0 and opt_id = {$spu_id}")->count();

        $GoodsDetail = array(
            "id"             => intval($spu_id),
            "url"            => C("SITE_URL")."/api.php?apptype=0&srv=2506&goods_id=".$spu_id."&cid=10002&uid=0&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$this->os_type."&version=7",
            "collect_status" => $fav_count? 1:0,
            "desc"           => "分享描述",
            "title"          => $spu['spu_name'],
            "status"         => 0,// 待定
            "guide_type"     => intval($spu['guide_type']),
            "img"            => image($spu_img),
            "kinds"          => $kinds,
            "guide_info"     => array(
                "open_type"   => $spu['taobao_url']? 0:1,// 0 百川 1 淘宝链接
                "real_url"    => htmlspecialchars_decode($spu['taobao_url']),
                "real_id"     => intval($item_id)
            ),
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
        $comment_list = $comment_mod->where(" spu_id = {$goods_id} ")->order("add_time desc ")->limit($start,$pg_size)->select();
        foreach ($comment_list as $v) {
            $comments[] = array(
                 "id"        => (int)$v['id'],
                 "avatar"    => image($v['avatar_src']),
                 "nickname"  => $v['nickname'],
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