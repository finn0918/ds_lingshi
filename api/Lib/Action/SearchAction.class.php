<?php
/**
 * 搜索
 * @author FK(429240967@qq.com)
 *
 */
class SearchAction extends BaseAction {

	/**
	 +----------------------------------------------------------
	 * 搜索商品列表
	 * 导入 MySQL 数据库的 dbname.tbl_post 表到 demo 项目中，并且平滑重建
	 * /usr/local/webserver/php/bin/php util/Indexer.php --source=mysql://phper:phper111@127.0.0.1/ds_lingshi --sql="SELECT * FROM ls_spu" --project=ds_lingshi
	 * util/Indexer.php --rebuild --source=mysql://phper:phper111@localhost/ds_lingshi --sql="SELECT * FROM ls_spu" --project=ds_lingshi
	 * 
	 +----------------------------------------------------------
	 */
	function get() {
        error_reporting(0);
		$prefix = '/usr/local/xunsearch';
		require_once "$prefix/sdk/php/lib/XS.php";
		$keyword = isset($_GET['keyword']) ? urldecode($_GET['keyword']) : '';
		$pg_cur = intval($this->getFieldDefault('pg_cur',1));     //当前页数
		$pg_size = intval($this->getFieldDefault('pg_size',20));  //每页展示数
        $now = time();//当前时间。下面有用到
		$pg_cur= $pg_size * ($pg_cur-1);
		$pg_cur = $pg_cur > 0 ? $pg_cur : 0;
		if(empty($keyword)){
			$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
		}

		$spuModel = M('spu');

		if($keyword) {
            $xun_search = C("XUN_SEARCH");//
			$xs = new XS($xun_search); // 建立 XS 对象，项目名称为：demo

			$search = $xs->search; // 获取 搜索对象
            $search->setQuery("spu_name:".$keyword);
//			$search->setQuery($keyword); // 设置搜索语句
			$search->setLimit($pg_size, $pg_cur); // 设置返回结果最多为 5 条，并跳过前 10 条
			$docs = $search->search(); // 执行搜索，将搜索结果文档保存在 $docs 数组中
            $user_search_mod = M("user_search");//
            $search_data = array(
                "user_id"       => $this->uid,
                "driver_num"    => $this->cid,
                "create_time"   => time(),
                "words"         => htmlspecialchars($keyword,ENT_QUOTES)
            );
            $user_search_mod->add($search_data);
			if(!empty($docs)){
				foreach($docs as $key=>$val){

					$ids[] = $val->spu_id;
				}
				// 去除掉秒杀的商品
				$second_kill = C("LS_SECOND_KILL");
				if($second_kill){
					$ids = array_diff($ids,$second_kill);
				}
				// 如果数据是空的，则返回空
				if(empty($ids)){
					$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
				}
				$ids = implode(',', $ids);

				$where = "s.status<2 and s.spu_id in ($ids)";
				$sql = "select s.* from ls_spu as s where $where";
				
				$data = M()->query($sql);
                //获取缩略图
                $spu_ids = $ids;
                $spuImageModel = M('spu_image');
                $img_data = $spuImageModel->where("spu_id in ($spu_ids) and type=1")->select();
                $img_data = secToOne($img_data,"spu_id","images_src");
                $topic_relation_mod = M("topic_relation");// 关系表

				foreach($data as $val) {
                    $goods["status"] = intval($val['status']);
                    $goods['id'] = (int)$val['spu_id'];
                    $goods["title"] = $val['spu_name'];
                    $goods["type"] = (int)$val['type'];
                    $goods["guide_type"] = (int)$val['guide_type'];
                    $type = 0;
					if($goods['guide_type']==0) {//非导购
	                	$spu_data = spu_format($val['spu_id']);
	                	$goods["sold_num"] = intval($spu_data['sale']);
	                	$goods["surplus_num"] = intval($spu_data['stocks']);
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

	                	$goods["price"] = array('current'=>$price_now, 'prime'=>$price_old);
                        $goods["type"] = intval($type);
                        if($spu_data['stocks'] <= 0) {
                        	$goods["status"] = 1;
                        }

	                } else {
	                	// 导购
	                	$goods["status"] = intval($val['status']);//导购商品直接取他存的值。
	                	$goods["sold_num"] = intval($val['sales']);
	                	$goods["surplus_num"] =  intval($val['stocks']);
                        // nangua

                        $relation_info = $topic_relation_mod->where("spu_id = {$val['spu_id']} and atom_id>0 and type=0 and status='published' and publish_time< {$now} and end_time > {$now} ")->field("publish_time,end_time")->find();
                        if(isset($relation_info)&&$relation_info['publish_time']<$relation_info['end_time']){
                            $type = 2;// 统一显示 特卖 1 2 对于导购商品没有区别
                        }
                        $goods['type'] = intval($type);
                        $price_now = price_format($val['price_now']);
                        $price_old = price_format($val['price_old']);
                        // nangua
	                	$goods["price"] = array('current'=>$price_now, 'prime'=>$price_old);
	                }
                    
                    $goods["img"] = image($img_data["{$val['spu_id']}"]);
                    $goods["freight"] = 0; //运费标准，为0时显示包邮
                    $goods["time"] = (int)$val['off_time']; //运费标准，为0时显示包邮

                    $goods["tag"] = tag_format($type,$val['shipping_free'],$price_now,$price_old);

                    $goods["desc"] = $val['desc']; //运费标准，为0时显示包邮
                    $goods['fav_num'] = intval($val['fav_count']);
                    $goods['sub_classify_id'] = 0;
                    $goodses[] = $goods;
				}
			}
			if(empty($goodses)) {
				$this->errorView($this->ws_code['returnsnull_error'], $this->msg['returnsnull_error']);
			}
			$this->successView($goodses);
		} else {
			$this->errorView($this->ws_code['parameter_error'], $this->msg['parameter_error']);
		}
	}
}