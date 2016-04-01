<?php
/**
 * 
 * @author: zengnanlin
 * @since: 2015-08-25
 */

class FreeFeeAction extends BaseAction{
    public $spu_fields = "spu_id,price_old,guide_type,price_now,shipping_free,spu_name,status,sales,stocks,fav_count";
    /**
    +----------------------------------------------------------
     * 获取某供应商下的所有商品（点击优惠券，满包邮活动进入） :2909
    +----------------------------------------------------------
     */
    public function index(){
        $supplier_id = intval($this->getField('supplier_id',''));//
        $discoupon_id = intval($this->getFieldDefault("coupon_id",0));

        $since_id = intval($this->getFieldDefault('since_id', 0));//
        $pg_cur = intval($this->getFieldDefault('pg_cur', 1));//当前页数
        $pg_size = isset($_GET['pg_size']) ? intval($_GET['pg_size']) : 20;//每页显示的条数
        $start = ($pg_cur - 1)*$pg_size;

        $topic_relation_mod = M("topic_relation");// 关系表
        $spu_mod = M("spu");

        // 如果是优惠券获取商品列表的方法是 查询 优惠券与商品的关系表
        if($discoupon_id){
            $coupon_spu_mod = M("coupon_spu");
            $coupon_mod = M("coupon");
            $coupon_num = $coupon_mod->where("id = {$discoupon_id}")->getField("coupon_num");
            if(empty($coupon_num)){
                $this->errorView($this->ws_code['coupon_id_not_exist'],$this->msg['coupon_id_not_exist']);
            }
            $spu_arr = $coupon_spu_mod->where("coupon_num = '$coupon_num'")->limit($start,$pg_size)->getField("spu_id",true);
            $spu_strs = implode(',',$spu_arr);
            $spu_list = $spu_mod->where("spu_id in($spu_strs) and status in(0,1)")->field($this->spu_fields)->select();
        }else{
            $spu_list = $spu_mod->where("suppliers_id = {$supplier_id} and status in(0,1)")->limit($start,$pg_size)->field($this->spu_fields)->order("publish_time desc")->select();
        }
        if(empty($spu_list)){
            $this->errorView($this->ws_code['returnsnull_error'],$this->msg['returnsnull_error']);
        }

        $ids = arrayToStr($spu_list,'spu_id');
        $spu_img_mod = M("spu_image");
        $spu_img_list = $spu_img_mod->where("spu_id in ( {$ids} ) and type=1")->field("spu_id,images_src")->select();
        $spu_img_list = secToOne($spu_img_list,"spu_id","images_src");

        // 商品循环
        foreach ($spu_list as $v) {
            $spu_id  = $v['spu_id'] ;
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

            $goodses[] = array(
                "id"            => intval($v['spu_id']),
                "title"         => $v['spu_name'],
                "type"          => intval($type),
                "guide_type"    => intval($v['guide_type']),
                "status"        => intval($v['status']),
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
                "sub_classify_id"=> 0
            );
        }
        $this->successView($goodses);
    }
}