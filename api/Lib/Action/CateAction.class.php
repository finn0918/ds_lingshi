<?php
/**
 * 分类
 * @author feibo
 *
 */

class CateAction extends BaseAction {
	
	/**
	 +----------------------------------------------------------
	 * 通过分类id获取分类列表 : 2402
	 +----------------------------------------------------------
	 */
	function getSpuByCid() {
		$cid = isset($_GET['classify_id']) ? intval($_GET['classify_id']) : 0;
		
		if ($cid) {
			$cateModel = M('spu_cate');


			$sub_classifies = $cateModel->field('id,name as title')->where('pid=' . $cid.' AND is_show=1')->select();
            // nangua 20150716 23:45 增加 id int类型转换
            foreach ($sub_classifies as $k=>$v) {
                $sub_classifies[$k]['id'] = intval($v['id']);
            }
            if(empty($sub_classifies)) {
            	$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
            }
            $this->successView($sub_classifies);
		} else {
			$this->errorView($this->ws_code['empty_error'], $this->msg['empty_error']);
		}
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 通过子分类id获取商品列表 2406
	 +----------------------------------------------------------
	 */
	function getSpuBySubCid() {
		$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
		$sub_id = isset($_GET['sub_id']) ? intval($_GET['sub_id']) : 0;
		$pg_cur = intval($this->getFieldDefault('pg_cur',1));     //当前页数
		$pg_size = intval($this->getFieldDefault('pg_size',20));  //每页展示数
		
		$pg_cur = $pg_size * ($pg_cur-1);
		$pg_cur = $pg_cur > 0 ? $pg_cur : 0;
        $now = time();
		if($sub_id) {
			$cid = $sub_id;
		} else {
			$cateModel = M('spu_cate');
			//$cid = $parent_id . ',';
            $cid = '';
			
			$sub_classifies = $cateModel->field('id')->where('pid=' . $parent_id)->select();
			foreach($sub_classifies as $val) {
				$cid .= $val['id'] .',';
			}
			$cid = substr($cid, 0, -1);
		}
		if ($cid) {
            $topic_relation_mod = M("topic_relation");// 关系表
            // 通过cid 与商品的关系表 

            $spu_cate_relation_mod = M("spu_cate_relation");
//5420
            $spu_id_arr = $spu_cate_relation_mod->where("cate_id in($cid)")->limit($pg_cur,$pg_size)->getField("spu_id",true);

            $spu_id_strs = implode(',',$spu_id_arr);

			$where = "spu_id in ($spu_id_strs) and status in(0,1)";
			$spuModel = M('spu');
			$spuImageModel = M('spu_image');
			$goods_list = $spuModel->where($where)->select();
			
			if(empty($goods_list)) {
				$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
			}
			//获取缩略图
            $spu_ids = '';// nangua 20150716 23:49
			foreach($goods_list as $value) {
				$spu_ids .= $value['spu_id'] . ',';
			}
			$spu_ids = substr($spu_ids, 0, -1);
			$img_data = $spuImageModel->where("spu_id in ($spu_ids) and type=1")->select();
			$img_data = secToOne($img_data,"spu_id","images_src");
			foreach($goods_list as $key=>$val) {
				$goods['id'] = intval($val['spu_id']);
				$goods["title"] = $val['spu_name'];
				$goods["type"] = intval($val['status']);
				$goods["guide_type"] = (int)$val['guide_type'];
                $status = $val['status'];
                $type = 0;
				if($goods['guide_type']==0) {//非导购
                	$spu_data = spu_format($val['spu_id']);
// nangua
                    if($spu_data['type']==2){
                        // 限时限量要显示库存
                        $type = 1;
                    }elseif($spu_data['type']==1){
                        // 限时的不需要显示库存
                        $type = 2;
                    }
                    $price_now = $spu_data['price'];
                    $price_old = price_format($val['price_old']);
// nangua
                    if($spu_data['stocks'] <=0){
                        $status = 1; // 商品失效
                    }
                	$goods["sold_num"] = intval($spu_data['sale']);
                	$goods["surplus_num"] = intval($spu_data['stocks']);
                	$goods["price"] = array('current'=>$spu_data['price'], 'prime'=>$price_old);
                    $goods["type"] = intval($type);
                } else {
                	// 导购
                	$goods["sold_num"] = intval($val['sales']);
                	$goods["surplus_num"] =  intval($val['stocks']);
                    $relation_info = $topic_relation_mod->where("spu_id = {$val['spu_id']} and atom_id>0 and type=0 and status='published' and publish_time< {$now} and end_time > {$now} ")->field("publish_time,end_time")->find();
                    if(isset($relation_info)&&$relation_info['publish_time']<$relation_info['end_time']){
                        $type = 2;// 统一显示 特卖 1 2 对于导购商品没有区别
                    }
                    $goods['type'] = intval($type);
                	$price_now = price_format($val['price_now']);
                    $price_old = price_format($val['price_old']);
                	$goods["price"] = array('current'=>$price_now, 'prime'=>$price_old);
                    $status = 0;
                }

				$goods['status'] = intval($status);
				$goods["img"] = image($img_data["{$val['spu_id']}"]);
				$goods["freight"] = 0; //不需要填值
				$goods["time"] = (int)$val['off_time'];
                // 判断这个商品 应该显示的标签
                $goods["tag"] = tag_format($type,$val['shipping_free'],$price_now,$price_old);

				$goods["desc"] = $val['desc']; 
				$goods['fav_num'] = intval($val['fav_count']);

				$goods['sub_classify_id'] = intval($sub_id);
				$goodses[] = $goods;
			}
			if(empty($goodses)) {
				$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
			}
			$this->successView($goodses);
		} else {
			$this->errorView($this->ws_code['empty_error'], $this->msg['empty_error']);
		}
	
	}
}