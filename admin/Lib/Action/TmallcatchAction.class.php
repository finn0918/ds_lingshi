<?php
/**
 * 天猫采集
 * @author hhf
 *
 */
class TmallcatchAction extends FetchAction{

   	/**
	 +----------------------------------------------------------
	 * 采集天猫详细数据
	 +----------------------------------------------------------
	 */

	 public function get_tmall_data(){
		if (isset($_POST['dosubmit'])){
			$url = isset($_POST['tmall_url']) ? trim($_POST['tmall_url']) : '';
            $spu = isset($_POST['spu']) ? intval($_POST['spu']) : 0;
            if($spu){
                $returnUrl = "m=Spu&a=spuCatch";
            }else{
                $returnUrl = "m=Taobao&a=taobaoCatch";
            }
			//$url = isset($_GET['tmall_url']) ? trim($_GET['tmall_url']) : '';
			//$url = "https://detail.tmall.com/item.htm?spm=a220m.1000858.1000725.211.Tns2LA&id=39391137624&skuId=64701907271&areaId=350200&cat_id=2&rn=3ff8963934555e67ad4a4695f0009cb1&user_id=2046293456&is_b=1";
			// 匹配站点、店铺名称
			$preg_name = '/(http:\/\/)?(\w+)\.(\w+)\./';
			preg_match($preg_name, $url, $matches);
			$sitename = $matches[3];
			// 检查站点名称
			if (!in_array($sitename, array('tmall'))) {
				$this->error('链接错误');
			}
			$preg = '/&id=\d+/';
			preg_match_all($preg, $url,$res);
			$id = $res['0']['0'];
			$data['id'] = str_replace('&id=', '', $id);
			if(empty($data['id'])){
				$preg = '/id=\d+/';
				preg_match_all($preg, $url,$res);
				$id = $res['0']['0'];
				$data['id'] = str_replace('id=', '', $id);
			}
			if(!empty($data['id'])){
				$filter_mod = M('filter_goods');
				$res = $filter_mod->where('type = 1 AND item_id = '.$data['id'])->find();
				if($res){
					die(json_encode(array('flag'=>false,'data'=>array('url'=>"admin.php?$returnUrl"),'msg' => '抓取失败','goods_id'=>$res['gid'])));
				}
			}
			phpQuery::newDocumentFileHTML($url,'GBK');
			$data['taobao_url'] = $url;
			$data['type'] = 0;
			$data['guide_type'] = 2;
            if($spu){
                $data['guide_type'] = 0;
            }
			$data['add_time'] = time();
			$data['publish_time'] = time();
			$data['cid'] = 0;
			$data['brand_id'] = 0;
			//$data['desc'] = trim(pq('.tb-subtitle')->text());
			$price_mobile = $this->get_mobile($data['id']);
			$data['spu_name'] = $price_mobile['title'];
			$price_old_mobile = explode(' - ', $price_mobile['old']);
			$data['price_old'] = $price_old_mobile['0']*100;
			if(empty($data['price_old'])){
				$price_old = str_replace('¥','',pq('.originPrice')->text());//原先获取原价方法
				$price_old = explode(' - ', $price_old);
				$data['price_old'] = $price_old['0']*100;
			}
			$data['shipping_free'] = $price_mobile['delivery'];//包邮
			$price_now_mobile = explode(' - ', $price_mobile['now']);
			$data['price_now'] = $price_now_mobile['0']*100;
			$discount_num = round(10 / ((double)$data['price_old'] / (double)$data['price_now']), 1); //计算折扣
			$discount_num = number_format($discount_num,1);
			$data['discount_info'] = $discount_num;
			$data['sales'] = $price_mobile['totalSoldQuantity'];
			$data['stocks'] = $price_mobile['quantity'];
			$data['details'] = $price_mobile['descInfo'];
			if(empty($data['spu_name'])) return false;
            if(!$data['price_now']){
                die(json_encode(array('flag'=>false,'data'=>array('url'=>"admin.php?$returnUrl"),'msg' => '抓取失败,获取不到价格')));
            }
			$gid = $this->add_goods_data($data);
			//添加商品图片
			$imgs = $price_mobile['goods_imgs'];
			if($imgs){
				$images_mod = M('spu_image');
				$time = time();
				foreach($imgs as $key=>$val){
					$pth = savetaobao($val);
					$pth = img_zip($pth);
					$res = up_yun($pth);
					$img = array();
					$img['img_url'] = $res['img_src'];
					$img['img_w'] = $res['img_w'];
					$img['img_h'] = $res['img_h'];
					$imageSer = serialize($img);
					$img_data['images_src'] = $imageSer;
					$img_data['add_time'] = time();
					$img_data['spu_id'] = $gid;
					$img_data['type'] = 3;
					$images_mod->add($img_data);
					if($key === 0){//添加商品缩略图
						$img_data['type'] = 1;
						$images_mod->add($img_data);
					}
					$res = @unlink($pth);
				}
			}
			$userid = $price_mobile['userid'];
			$comment_data['id'] = $data['id'];
			$comment_data['userid'] = $userid;
			$comment_data['cid'] = $gid;//插入数据的id
			//获取评论
			//var_dump($userid);
			$comment_list = $this->get_comment($comment_data);
			//var_dump($comment_list);
			//添加评论
			$this->add_comment($comment_list, $gid);
			if($gid){
				$return_data = array('url'=>'admin.php?m=Taobao&a=edit&id='.$gid);
                if($spu){
                    $return_data = array('url'=>'admin.php?m=Spu&a=edit&spu_id='.$gid);
                }
				die(json_encode(array('flag'=>true,'data'=>$return_data,'msg' => '抓取成功')));
			}else{
				die(json_encode(array('flag'=>false,'msg' => '抓取失败')));
			}
		}
	}

	/**
	 +----------------------------------------------------------
	 * 采集天猫评论
	 +----------------------------------------------------------
	 */

	public function get_comment($data){
		$page = 1;
		$filter_comment = array();
		while($page){
			$comment_url = 'http://rate.tmall.com/list_detail_rate.htm?itemId='.$data['id'].'&sellerId='.$data['userid'].'&order=1&currentPage='.$page;
			$result = $this->get($comment_url);
			$result = iconv('GBK','UTF-8',str_replace('"rateDetail":','',trim($result)));
			$result=json_decode($result);
			$result = $this->objectToArray($result);
			foreach($result['rateList'] as $key=>$val){
				$comment = $val['rateContent'];
				if(mb_strlen($comment, 'utf8') < 20) continue;//过滤50个字评论
				if(in_array($comment,$filter_comment)) continue;//过滤重复评论
				//if($this->match_comment($comment,$filter_comment)) continue;//过滤相似评论
				//$num = 1748;
				//$head_mod = D('head');
				//$rand = mt_rand(1,$num);
				//$head_res = $head_mod->field('img_src')->find($rand);
				$filter_comment[] = $comment;
				$comment_list['spu_id'] = $data['cid'];
				$comment_list['nickname'] = $val['displayUserNick'];
				$comment_list['comment'] = $comment;
				$comment_list['add_time'] = time();
                $comment_list['type'] = 0;
				$comment_list['avatar_src'] = "http://assets.alicdn.com/app/sns/img/default/avatar-80.png";
				$comment_data[] = $comment_list;
				$count = count($comment_data);
				if($count == 23) break;
			}
			if($page == 10){
				$page = false;
			}
			if($count >=23){
				$page = false;
			}else{
				$page++;
			}
		}
		return $comment_data;
	}



}
?>