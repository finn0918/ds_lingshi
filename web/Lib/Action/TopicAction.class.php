<?php
/**
 * 专题列表
 * @author nangua
 */

class TopicAction extends  BaseAction{
    public $special_fields = "spu_id,spu_name,type,guide_type,status,price_now,price_old,end_time,tag";
    private $_spu_fields = "spu_id,spu_name,type,details,sales,stocks,price_now,price_old,shipping_free,desc,fav_count";
    public $field_ad = "id,link_to,image_src,link_id,link_url";
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
       $where['topic_id'] = $subject_id;
       $where['status'] = 'published';
        $topic = $topic_mod->where($where)->find();

        // 查询专题下的商品
       $topic_spu_list = $topic_relation_mod->where(" topic_id = $subject_id ")->group("spu_id")->order("sort asc")->field("spu_id,image_src")->select();

        // 专题信息
        $subject = array(
            "id"        => intval($topic['topic_id']),
            "desc"      => $topic['desc'],
            "title"     => $topic['title'],
            "img"       => image($topic['image_src']),
            "hotindex"  => intval($topic['hotindex']),
            "share_num" => intval($topic['share_num']),
            "web_url"   => ""
        );
        if(empty($topic_spu_list)){
            $data = array(
                "subject"   => $subject,
                "goodses"   => array()
            );
            $this->successView("",$data);
        }
        $topic_spu_list = secToOne($topic_spu_list,"spu_id","image_src");
        $id_arr = array_keys($topic_spu_list);
        $ids = implode(",",$id_arr);

        // 查处所有相关商品
        $spu_mod = M("spu");
        $spu_list = $spu_mod->where(" spu_id in ( {$ids} ) and status = 0 ")->field($this->_spu_fields)->order("field(`spu_id`,$ids)")->select();



        // 商品循环
        foreach ($spu_list as $v) {

            $goodses[] = array(
                "id"            => (int)$v['spu_id'],
                "title"         => $v['spu_name'],
                "type"          => (int)$v['type'],
                "guide_type"    => (int)$v['guide_type'],
                "status"        => (int)$v['status'],
                "sold_num"      => (int)$v['sales'],// 销量
                "surplus_num"   => (int)$v['stocks'],
                "price"         => array(
                       "current"=>  price_format($v['price_now']),
                       "prime"  =>  price_format($v['price_old'])
                    ),
                "img"           => new stdClass(),
                "freight"       => 0,
                "time"          => 0,
                "tag"           => new stdClass(),
                "fav_num"       => intval($v['fav_count']),
                "posters"       => image($topic_spu_list["{$v['spu_id']}"],2),
                "desc"          => $v['desc'],
                "sub_classify_id"=> 0
            );
        }
        $data = array(
            "subject"      => $subject,
            "goodses"      => $goodses
        );
        $this->successView('',$data);
    }
}