<?php
/**
 * 收藏
 * @author feibo
 *
 */

class CollectAction extends BaseAction{
	
	/**
	 +----------------------------------------------------------
	 * 获取收藏夹商品列表 2806\2804,2804 返回有效收藏商品数
	 +----------------------------------------------------------
	 */
	function get() {
		//初始化返回数据
		$count = 0;
		$items = array();
		$pg_cur = intval($this->getFieldDefault('pg_cur',1));     //当前页数
		$pg_size = intval($this->getFieldDefault('pg_size',20));  //每页展示数
		$pg_cur= $pg_size * ($pg_cur-1);
		$pg_cur = $pg_cur > 0 ? $pg_cur : 0;

       /*
        * http://zengnanlin.lingshi/api.php?srv=2806&type=0&cid=107&uid=2&tms=20150723180504&sig=d18f073f9f1e3718&wssig=309ec190ad2064fe&os_type=3&version=6&channel_name=feibo&since_id=4&pg_cur=2&pg_size=20
        */

		$type = isset($_GET['type']) ? intval($_GET['type']) : 0;  // 0、商品  1、专题
		$key = ($type==0) ? 'Goods' : 'Subject';
		$goodses = array();  // 初始化数组
		$user_id = $this->uid;
		$favModel = M('favs');
		$where = array();
        $where['user_id'] = $user_id;
        $where['type'] = $type;
       
		$id_arr = $favModel->field('opt_id')->order('add_time desc')->where($where)->select();
 		if(empty($id_arr)) {
            $this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
		}
		
		
		$ids = '';
		foreach($id_arr as $val) {
			$ids .= $val['opt_id'] . ',';
		}
		
		$ids = substr($ids, 0, -1);
		
		
		if($type==0) { //商品
			$count = $this->getFavCount($type);
		 
			$spuModel = M('spu');
			$spuImageModel = M('spu_image');
            $topic_relation_mod = M("topic_relation");//关系表 用于判定商品在特卖期 by nangua 2015-08-09
			$sql = "select * from ls_spu where status <=2 and spu_id in ($ids) order by field(spu_id,$ids ), status asc,stocks desc limit $pg_cur,$pg_size"; //按 id 的顺序
			$data = M('spu')->query($sql);
			
			//$data = M('spu')->where("status in(0,1) and spu_id in ($ids)")->limit("$pg_cur,$pg_size")->order("status asc,stocks desc")->select();
			$sort = 0; //用于排序
			$now = time();
			foreach($data as $val) {
				
                $spu_id = intval($val['spu_id']);
                $goods['sort'] = $sort++;
				$goods['id'] = $spu_id;
				$goods["title"] = $val['spu_name'];
				$goods["type"] = intval($val['type']);
				$goods["guide_type"] = (int)$val['guide_type'];
				$goods['status']    = intval($val['status']);
                if($goods['guide_type']==0) {//非导购
                	$spu_data = spu_format($spu_id);
                	$goods["sold_num"] = intval($spu_data['sale']);
                	$goods["surplus_num"] = intval($spu_data['stocks']);
                	$goods["price"] = array('current'=>$spu_data['price'], 'prime'=>price_format($val['price_old']));
                	if($spu_data['stocks']<=0) {
                		$goods["status"] = 1;
                	}
                    $type = $spu_data['type'];
                } else {
                	// 导购
                	$goods["sold_num"] = intval($val['sales']);
                	$goods["surplus_num"] =  intval($val['stocks']);
                    $goods["status"] = intval($val['status']);//导购商品直接取他存的值。
//                	if($goods["surplus_num"] ) {
//                		$goods["status"] = 0;
//                	} else {
//                		$goods["status"] = 1;
//                	}
                	$goods["price"] = array('current'=>price_format($val['price_now']), 'prime'=>price_format($val['price_old']));
                	$relation_info = $topic_relation_mod->where("spu_id = {$val['spu_id']} and atom_id>0 and type=0 and status='published' and publish_time< {$now} and end_time > {$now} ")->field("publish_time,end_time")->find();
                	
                	//$relation_info = $topic_relation_mod->where("spu_id = {$spu_id}")->field("publish_time,end_time")->find();
                    if(isset($relation_info)&&$relation_info['publish_time']<$relation_info['end_time']){
                        $type = 1;
                    }
                }
               
                $image_src = $spuImageModel->where("type=1 and spu_id=$spu_id")->getField('images_src');
				$goods["img"] = image($image_src);// nangua 20150713
				
				$goods["freight"] = price_format($val['shipping_fee']); //运费标准，为0时显示包邮
				$goods["time"] = intval($val['off_time']); //特卖商品截止时间
				
				$goods["tag"] = tag_format($type,$val['shipping_free'],$goods["price"]["current"],$goods["price"]["prime"]);
				

				$goods["desc"] = $val['desc']; //商品描述，专题详情页商品列表需要
				$goods['fav_num'] = intval($val['fav_count']);
				$goods['posters'] = array();
				$goods['sub_classify_id'] = intval($val['cid']);;
				$goodses[] = $goods;
				
			}
			
			// 目前只能这么排序
			$sort1 = array();
			$sort2 = array();
			foreach ($goodses as $key=>$goods) {
				$sort1[] = $goods['sort'];
				$sort2[] = $goods['status'];
				unset($goodses[$key]['sort']);
			}
			array_multisort($sort1, SORT_ASC, $sort2, SORT_ASC, $goodses);
			
            if(empty($goodses)) {
                $this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
            }
          //  unset($data);
            $data1 = array(
					'count' => intval($count),
					'items' => $goodses,
			);

			//die(json_encode($data));
			$this->successView('',$data1);
		} elseif ($type==1) { //专题
			$topicModel = M('topic');
			$data = $topicModel->where("is_show=1 and status='published' and topic_id in ($ids) ")->limit($pg_cur,$pg_size)->select();
			foreach($data as $val) {
				$topic_id = intval($val['topic_id']);
				$check_arr[] = $topic_id;
				$item['id'] = $topic_id;
				$item['desc'] = $val['desc'];
				$item['title'] = $val['title'];
				$item["img"] = image($val['image_src']);// nangua 20150713
				
				$item['hotindex'] = (int)$val['hotindex'];
				$item['share_num'] = (int)$val['share_num'];
				$items[] = $item;
			}
			if(empty($items)) {
				$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
			}
			$this->successView($items);
		} else {
			$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);		
		}
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 添加/移除收藏夹商品 2805
	 +----------------------------------------------------------
	 */
	function put() {
		
		$opt_id = isset($_GET['fav_id']) ? trim($_GET['fav_id']) : '';
		$type = isset($_GET['type']) ? intval($_GET['type']) : 0;  // 0、商品  1、专题
		$opt = isset($_GET['opt']) ? intval($_GET['opt']) : 0;  // 0，添加；1，删除
		$user_id = $this->uid; 
		$where = " user_id=$user_id ";
		$favsModel = M('favs');
		$model = M();
		if(empty($opt_id) || empty($user_id)) {
			$this->errorView($this->ws_code['empty_error'], $this->msg['empty_error']);
		}
		
		if($opt==0) { //添加
			if($favsModel->where($where ." and type=$type and opt_id=$opt_id")->count()) {			//判断是否已收藏
				$this->errorView($this->ws_code['collect_repeat'], $this->msg['collect_repeat']);
			}
			$data['type'] = $type;
			$data['user_id'] = $user_id;
			$data['add_time'] = time();
			
			$data['opt_id'] = $opt_id;
			
			if($favsModel->where($where)->add($data)!==false) {
				if($type) {  //专题
					$sql = "update ls_topic set `hotindex`=`hotindex`+1, `fav_count`=`fav_count`+1 where topic_id=$opt_id";
				} else { //商品
					$sql = "update ls_spu set `fav_count`=`fav_count`+1 where `spu_id`=$opt_id";
				}
				
				$model->execute($sql);
				$count = $this->getFavCount($type);
				$this->successView('', array('count' => (int)$count));
			} else { 
				$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
			}
		} elseif ($opt==1) {//删除
			if($opt_id) {
				$where .= "  and type=$type and opt_id in ($opt_id)  ";
				if($row=$favsModel->where($where)->delete()!==false) {
					
					if($type) {  //专题
						$sql = "update ls_topic set `hotindex`=`hotindex`-1, `fav_count`=`fav_count`-1 where topic_id=$opt_id";
						$count_arr = M('topic')->where("topic_id=$opt_id")->field('hotindex,fav_count')->find();
					} else { //商品
						$sql = "update ls_spu set `fav_count`=`fav_count`-1 where spu_id=$opt_id";
						$count_arr = M('spu')->where("spu_id=$opt_id")->field('fav_count')->find();
					}
					
					if($row>0) {
						if($type) {
							if($count_arr['hotindex']>0 && $count_arr['fav_count']>0) {
								$model->execute($sql);
							}
						} else {
							if($count_arr['fav_count']>0) {
								$model->execute($sql);
							}
						}
						
					}					
					$count = $this->getFavCount($type);
					$this->successView('', array('count' =>intval($count)));
				} else {
					$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
				}
			}else {
					$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
				}
			
		} else {
			$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
		
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取商品/专题收藏数量
	 +----------------------------------------------------------
	 */
	function getFavCount($type=0) {
		
		$where['user_id'] = $this->uid;
		$where['type'] = $type;
		$favModel =M('favs');
		$spuModel = M('spu');
		$topicModel = M('topic');
		
		$id_arr = $favModel->field('opt_id')->order('add_time desc')->where($where)->select();
		if(empty($id_arr)) return 0;
		
		$ids = '';
		foreach($id_arr as $val) {
			$ids .= $val['opt_id'] . ',';
		}
		
		$ids = substr($ids, 0, -1);
		if($type==0) { //商品
			$data = $spuModel->field('spu_id')->where("status=0 and spu_id in ($ids)")->select();
			$count = 0;
			foreach($data as $val) {  //判断库存
				$spu_data = spu_format($spu_id);
				if($spu_data['stocks']==0) {
					$count++;
				} 
			}
			return $count;
		} else {
			$count = $topicModel->where("is_show=1 and status='published' and topic_id in ($ids)")->count();
		}
		return $count;
	}
	
}