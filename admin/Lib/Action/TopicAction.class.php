<?php
/**
 * 专题管理
 * @author hhf
 *
 */
class TopicAction extends BaseAction{

	/**
	 +----------------------------------------------------------
	 * 专题列表
	 +----------------------------------------------------------
	 */
    function index() {
        $_SESSION['pageNow'] = intval($_GET['p'])>0?intval($_GET['p']):1;
    	$timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
    	$timeStart = strtotime($timeStart);
    	$timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
    	$timeEnd = strtotime($timeEnd);
		$keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
		$status = I('get.status','','trim,htmlspecialchars');//状态
		$topicMod = M('topic');
		$user = M('admin');
		$imgHost = C('LS_IMG_URL');
		$where = " type=1 ";
		if($keyword){
			if(is_numeric($keyword)){
				$where .= " AND topic_id =" . intval($keyword);
			}else{
				$where .= " AND title LIKE '%" . $keyword . "%'";
			}
			$this->assign('keyword', $keyword);
		}
		if($timeStart){
			$where .= " AND add_time >= $timeStart ";
			$timeStart = date("Y-m-d H:i",$timeStart);
			$this->assign('time_start', $timeStart);
		}
		if($timeEnd){
			$where .= " AND add_time <= $timeEnd ";
			$timeEnd = date("Y-m-d H:i",$timeEnd);
			$this->assign('time_end', $timeEnd);
		}
		if($status){
			$where .= " AND status = '$status'";
			$this->assign('select_status', $status);
		}
		$count = $topicMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
    	$topicResult = $topicMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('sort desc,add_time desc')->select();
    	foreach($topicResult as $key=>$val){
			$topicResult[$key]['key'] = ++$p->firstRow;
			$images = $val['image_src'];
			$images = unserialize($images);
			$images = $imgHost.$images['img_url'];
			$topicResult[$key]['image'] = $images;
			switch ($val['status']) {
				case 'published':
					$topicResult[$key]['status'] = "已上架";
					break;
				case 'scheduled':
					$topicResult[$key]['status'] = "未上架";
					break;
				case 'locked':
					$topicResult[$key]['status'] = "已下架";
					break;
				default:
					break;
			}
			$name = $user->field('user_name')->find($val['admin_id']);
			$topicResult[$key]['userName'] = $name['user_name'];
    	}
    	$page = $p->show();
    	$this->assign('page',$page);
    	$this->assign('topic',$topicResult);
    	$this->display();
    }

    /**
	 +----------------------------------------------------------
	 * 新增单个专题
	 +----------------------------------------------------------
	 */
    function add() {
		$_SESSION['chooseSpu'] = '';
		$spuIdArray = I('post.addId','','');
		$title = I('post.title','','trim,htmlspecialchars');//专题标题
		$detail = I('post.desc','','trim,htmlspecialchars');//专题描述
		$share_num = I('post.virtual_share','','trim,htmlspecialchars');//分享数目
		$publishTime = I('post.time_publish','','trim,htmlspecialchars');//专题发布时间
		$offTime = I('post.time_end','','trim,htmlspecialchars');//专题下架时间
		$topicImages = I('post.file','','');
		$type = I('post.type',0,'intval');//专题类型 1 普通 0 不显示
		$topicMod = M('topic');
		$relationMod = M('topic_relation');
		$imageMod = M('spu_image');
		if(isset($_POST['dosubmit'])){
			$img = array();
			$path = save_img64($topicImages[0]);//上传专题配图
			if($path){
				$res = up_yun($path);
				$img['img_url'] = $res['img_src'];
				$img['img_w']=$res['img_w'];
				$img['img_h']=$res['img_h'];
				$result = @unlink($path);
			}
			$imgSer = serialize($img);//序列化图片地址和宽高
			$data = array();
			$data['title'] = $title;
			$data['desc'] = $detail;
			$data['virtual_share'] = $share_num;
			$data['add_time'] = time();
            $data['sort'] = time();
			$data['publish_time'] = strtotime($publishTime);
			$data['end_time'] = strtotime($offTime);
			$user = $_SESSION['admin_info'];
    		$data['admin_id'] = $user['id'];
    		$data['status'] = "scheduled";
            if(!$type){
                $data['status'] = "published";
            }
    		$data['image_src'] = $imgSer;
    		$data['is_show'] = 1;
    		$data['hotindex'] = 0;
    		$data['sharenum'] = 0;
    		$data['template_id'] = 1;
    		$data['type'] = $type;
    		$flag = $topicMod->add($data);
    		if($flag){
    			$detail = $_SESSION['addDetail'];
    			//var_dump($detail);exit;
    			foreach($spuIdArray as $key=>$val){
    				$detailTmp = $detail[$val];
					$data = array();
					$data['spu_id'] = $val;
					$data['topic_id'] = $flag;
					$data['add_time'] = time();
					$data['update_time'] = time();
					$data['publish_time'] = time();
					$data['end_time'] = time();
					$data['admin_id'] = $user['id'];
					$data['sort'] = ++$key;
                    if($type){
                        if($_SESSION['addDetail'][$val]){
                            $data['desc'] = isset($_SESSION['addDetail'][$val]['desc'])?$_SESSION['addDetail'][$val]['desc']:'';
                            $topicSpuImage = array();
                            foreach($_SESSION['addDetail'][$val]['img'] as $k=>$v){
                                if($k<2){
                                    $img = $v['images_src'];
                                    $topicSpuImage[] = $img;
                                }
                            }
                            $data['image_src'] = serialize($topicSpuImage);
                        }
                    }
					$relationFlag = $relationMod->add($data);
    			}
				$this->success('添加成功！','?m=Topic&a=index');
    		}else{
				$this->error('添加失败！重新添加！！');
    		}
		}else{
			$_SESSION['addDetail'] = '';
			$this->display();
		}
    }

	/**
	 +----------------------------------------------------------
	 * 编辑单个专题
	 +----------------------------------------------------------
	 */
    function edit() {
		$topicId= I('request.id',0,'intval');
		$spuIdArray = I('post.addId','','');
		$title = I('post.title','','trim,htmlspecialchars');//专题标题
		$detail = I('post.desc','','trim,htmlspecialchars');//专题描述
		$share_num = I('post.virtual_share','','trim,htmlspecialchars');//分享数目
		$publishTime = I('post.time_publish','','trim,htmlspecialchars');//专题发布时间
		$offTime = I('post.time_end','','trim,htmlspecialchars');//专题下架时间
		$topicImages = I('post.file','','');
		$type = I('post.type',0,'intval');//专题类型 1 普通 0 不显示
		$topicMod = M('topic');
		$relationMod = D('Topic_relation');
		$imageMod = M('spu_image');
		$imgHost = C('LS_IMG_URL');
		if(isset($_POST['dosubmit'])){
			$img = array();
			$data = array();
			if($topicImages){
				$path = save_img64($topicImages[0]);//上传专题配图
				if($path){
					$res = up_yun($path);
					$img['img_url'] = $res['img_src'];
					$img['img_w']=$res['img_w'];
					$img['img_h']=$res['img_h'];
					$result = @unlink($path);
				}
				$imgSer = serialize($img);//序列化图片地址和宽高
				$data['image_src'] = $imgSer;
			}
			$data['title'] = $title;
			$data['desc'] = $detail;
			$data['publish_time'] = strtotime($publishTime);
			$data['end_time'] = strtotime($offTime);
			$user = $_SESSION['admin_info'];
    		$data['admin_id'] = $user['id'];
    		$data['type'] = $type;
    		$data['topic_id'] = $topicId;
    		$flag = $topicMod->save($data);
    		if($flag!==false){
    			$detail = $_SESSION['addDetail'];
    			$flagDel = $relationMod->where('topic_id = '.$topicId)->delete();
    			if($flagDel!==false){
					foreach($spuIdArray as $key=>$val){
	    				$detailTmp = $detail[$val];
						$data = array();
						$data['spu_id'] = $val;
						$data['topic_id'] = $topicId;
						$data['add_time'] = time();
						$data['update_time'] = time();
						$data['publish_time'] = time();
						$data['end_time'] = time();
						$data['admin_id'] = $user['id'];
						$data['sort'] = ++$key;
                        if($type){
                            if($_SESSION['addDetail'][$val]){
                                $data['desc'] = isset($_SESSION['addDetail'][$val]['desc'])?$_SESSION['addDetail'][$val]['desc']:'';
                                $topicSpuImage = array();
                                foreach($_SESSION['addDetail'][$val]['img'] as $k=>$v){
                                    if($k<2){
                                        $img = $v['images_src'];
                                        $topicSpuImage[] = $img;
                                    }
                                }
                                $data['image_src'] = serialize($topicSpuImage);
                            }
                        }
						$relationFlag = $relationMod->add($data);
    				}
    			}
                updateCache("ls_topic_list");//更新专题列表缓存
                if(!$type){
                    $this->success('编辑成功！','?m=Topic&a=indexList&p='.$_SESSION['pageNow']);
                }else{
                    $this->success('编辑成功！','?m=Topic&a=index&p='.$_SESSION['pageNow']);
                }
    		}else{
				$this->error('编辑失败！重新添加！！');
    		}
		}else{
			$_SESSION['addDetail'] = '';
			$_SESSION['chooseSpu'] = '';
			$topicResult = $topicMod->find($topicId);
			$img = $topicResult['image_src'];
			$img = unserialize($img);
			$img = $imgHost.$img['img_url'];
			$topicResult['image_src'] = $img;
			$this->assign('topic',$topicResult);
			$relationRes = $relationMod->where("topic_id = $topicId")->order('sort asc')->select();
			foreach($relationRes as $key=>$val){
				$spuIdArray[] = $val['spu_id'];
				$topicSpuImage = $val['image_src'];
				$topicSpuImage = unserialize($topicSpuImage);
				$detail = array();
				if($topicSpuImage[0]){
					$img = $topicSpuImage[0];
					$img1 = $imgHost.$img['img_url'];
					$sessionImage['img_url'] = $img['img_url'];
					$sessionImage['img_w'] = $img['img_w'];
					$sessionImage['img_h'] = $img['img_h'];
					$imagesArray['images_src'] = $sessionImage;
					$imagesArray['nowSrc'] = $img1;
					$detail['img'][] = $imagesArray;
					$relationRes[$key]['images'][] = $img1;
				}
				if($topicSpuImage[1]){
					$img = $topicSpuImage[1];
					$img2 = $imgHost.$img['img_url'];
					$sessionImage['img_url'] = $img['img_url'];
					$sessionImage['img_w'] = $img['img_w'];
					$sessionImage['img_h'] = $img['img_h'];
					$imagesArray['images_src'] = $sessionImage;
					$imagesArray['nowSrc'] = $img2;
					$detail['img'][] = $imagesArray;
					$relationRes[$key]['images'][] = $img2;
				}
				$detail['desc'] = $val['desc'];
				$_SESSION['addDetail'][$val['spu_id']] = $detail;
			}
			if($spuIdArray){
				$ids = implode(',',$spuIdArray);
				$_SESSION['chooseSpu'] = $ids;
				$res = $this->getTable($ids);
				$this->assign('tr',$res);
			}
			//var_dump($relationRes);
			$this->display();
		}
    }


	/**
	 +----------------------------------------------------------
	 * 专题合集列表
	 +----------------------------------------------------------
	 */
    function topicSetIndex() {
        $_SESSION['pageNow'] = intval($_GET['p'])>0?intval($_GET['p']):1;
    	$timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
    	$timeStart = strtotime($timeStart);
    	$timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
    	$timeEnd = strtotime($timeEnd);
		$keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
		$topicSetMod = M('topic_set');
		$where = " 1=1 AND title NOT LIKE '%优惠券列表%'";
		if($keyword){
			if(is_numeric($keyword)){
				$where .= " AND group_id =" . intval($keyword);
			}else{
				$where .= " AND title LIKE '%" . $keyword . "%'";
			}
			$this->assign('keyword', $keyword);
		}
		if($timeStart){
			$where .= " AND add_time >= $timeStart ";
			$timeStart = date("Y-m-d H:i",$timeStart);
			$this->assign('time_start', $timeStart);
		}
		if($timeEnd){
			$where .= " AND add_time <= $timeEnd ";
			$timeEnd = date("Y-m-d H:i",$timeEnd);
			$this->assign('time_end', $timeEnd);
		}
		$count = $topicSetMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
    	$topicSetResult = $topicSetMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
    	foreach($topicSetResult as $key=>$val){
			$topicSetResult[$key]['key'] = ++$p->firstRow;
    	}
    	$page = $p->show();
    	$this->assign('page',$page);
    	$this->assign('topicSet',$topicSetResult);
    	$this->display();
    }

	/**
	 +----------------------------------------------------------
	 * 新增专题合集
	 +----------------------------------------------------------
	 */
    function topicSetAdd() {
		$title = I('post.title','','trim,htmlspecialchars');//专题标题
		$topicSetMod = M('topic_set');
		$relationMod = M('group_topic');
		if(isset($_POST['dosubmit'])){
			$data = array();
			$data['title'] = $title;
			$data['add_time'] = time();
    		$flag = $topicSetMod->add($data);
    		if($flag){
    			$topicIds = $_SESSION['chooseTopic'];
    			$topicArray = explode(',',$topicIds);
    			foreach($topicArray as $key=>$val){
					$data = array();
					$data['group_id'] = $flag;
					$data['topic_id'] = $val;
					$data['create_time'] = time();
					$data['sort'] = ++$key;
					$relationFlag = $relationMod->add($data);
    			}
				$this->success('添加成功！','?m=Topic&a=topicSetIndex');
    		}else{
				$this->error('添加失败！重新添加！！');
    		}
		}else{
			$_SESSION['chooseTopic'] = '';
			$this->display();
		}
    }

    /**
	 +----------------------------------------------------------
	 * 修改专题合集
	 +----------------------------------------------------------
	 */
    function editTopicSet() {
		$topicSetId = I('request.topicSetId','','');
		$title = I('post.title','','trim,htmlspecialchars');//专题标题
		$topicSetMod = M('topic_set');
		$relationMod = M('group_topic');
		if(isset($_POST['dosubmit'])){
			$data = array();
			$data['title'] = $title;
			$data['group_id'] = $topicSetId;
    		$flag = $topicSetMod->save($data);
    		if($flag !== false){
    			$delFlag = $relationMod->where('group_id = '.$topicSetId)->delete();
    			if($delFlag){
    				$topicIds = $_SESSION['chooseTopic'];
    				$topicArray = explode(',',$topicIds);
    				foreach($topicArray as $key=>$val){
						$data = array();
						$data['group_id'] = $topicSetId;
						$data['topic_id'] = $val;
						$data['create_time'] = time();
						$data['sort'] = ++$key;
						$relationFlag = $relationMod->add($data);
    				}
					$this->success('修改成功！','?m=Topic&a=topicSetIndex');
    			}
    		}else{
				$this->error('修改失败！重新添加！！');
    		}
		}else{
			$topicSetResult = $topicSetMod->find($topicSetId);
			$topicArray = $relationMod->where('group_id = '.$topicSetId)->order('sort asc')->getField('topic_id',true);
			$topicIds = implode(',',$topicArray);
			$_SESSION['chooseTopic'] = $topicIds;
			$this->assign('topicSet',$topicSetResult);
			$this->assign('tr',$this->getTopicTable($topicIds));
			$this->assign('topicSetId',$topicSetId);
			$this->display();
		}
    }

     /**
	 +----------------------------------------------------------
	 * 增加专题商品
	 +----------------------------------------------------------
	 */

	 function addSpu () {
	 	$cateMod = D('Spu_cate');
	 	$suppliersMod = M('suppliers');
	 	$spuMod = D('Spu');
         $filterMod = M('filter_goods');
         $spuCateRelation = M('spu_cate_relation');//多分类
	 	$where = " status = 0 ";
	 	$timeStart = I('get.time_start','','trim,htmlspecialchars');//发布时间区间开始
    	$timeStart = strtotime($timeStart);
    	$timeEnd = I('get.time_end','','trim,htmlspecialchars');//发布时间区间结束
    	$timeEnd = strtotime($timeEnd);
    	$cateId = I('get.cate_id',0,'intval');//分类id
		$supplierId = I('get.supplier',0,'intval');//供应商id
		$spuType = I('get.spu_type',10,'intval');//商品类型
		$keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
		$lowPrice = I('get.low_price','','floatval');//价格区间低
		$highPrice = I('get.high_price','','floatval');//价格区间高
         if($keyword){
             $keyArray = explode(',',$keyword);
             foreach($keyArray as $val){
                 if(is_numeric($val)){
                     if(strlen($val)>10){
                         $filterWhere ['item_id'] = $val;
                         $filterResult = $filterMod->where($filterWhere)->find();
                         if($filterResult){
                             $whereIds[] = intval($filterResult['gid']);
                         }
                     }else{
                         $whereIds[] = intval($val);
                     }
                 }else {
                     $whereIds = array();
                     $where .= " AND spu_name LIKE '%" . $keyword . "%'";
                     break;
                 }
             }
             if($whereIds){
                 $whereIds = implode(',',$whereIds);
                 $where .= " AND spu_id in ($whereIds)";
             }
             $this->assign('keyword', $keyword);
         }
		if($timeStart){
			$where .= " AND add_time >= $timeStart ";
			$timeStart = date("Y-m-d H:i",$timeStart);
			$this->assign('time_start', $timeStart);
		}
		if($timeEnd){
			$where .= " AND add_time <= $timeEnd ";
			$timeEnd = date("Y-m-d H:i",$timeEnd);
			$this->assign('time_end', $timeEnd);
		}
		if($cateId){
            $multipleCate = $spuCateRelation->where("cate_id = $cateId")->getField('spu_id',true);
            $multipleCate = implode(',',$multipleCate);
            $where .= " AND spu_id in ($multipleCate)";
			$this->assign('cate_id', $cateId);
		}
		if($supplierId){
			$where .= " AND suppliers_id = $supplierId ";
			$this->assign('select_supplier', $supplierId);
		}
		if($spuType!=10){
			$where .= " AND guide_type =" . intval($spuType);
			$this->assign('select_type', intval($spuType));
		}else{
			$this->assign('select_type', 10);
		}
		if($highPrice){
			$where .= " AND price_now <=" . intval($highPrice*100);
			$this->assign('high_price', intval($highPrice));
		}
		if($lowPrice){
			$where .= " AND price_now >=" . intval($lowPrice*100);
			$this->assign('low_price', intval($lowPrice));
		}
	 	$pageSize = 10;
	 	$imgHost = C('LS_IMG_URL');
	 	$count = $spuMod->where($where)->count();
        import("ORG.Util.Page");
        $p = new Page($count, $pageSize);
    	$spuResult = $spuMod->relation(true)->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
    	foreach($spuResult as $key=>$val){
			$images1 = $val['images1'][0]['images_src'];
			$images1 = unserialize($images1);
			$images1 = $imgHost.$images1['img_url'];
			$spuResult[$key]['images1'] = $images1;
			$spuResult[$key]['price_old'] = number_format($val['price_old']/100,2);
			$spuResult[$key]['price_now'] = number_format($val['price_now']/100,2);
    	}
    	$page = $p->show();
    	$this->assign('page',$page);
    	$this->assign('spu',$spuResult);
	 	$type = array();
		$spuType = C('SPU_TYPE');
		foreach($spuType as $key=>$val){
			$type[$key]['id'] = $key;
			$type[$key]['value'] = $val;
		}
		$this->assign('spu_type',$type);
	 	$cates_list = $cateMod->get_list();
		$this->assign('cate_list', $cates_list['sort_list']);
		$supplierResult = $suppliersMod->select();
		$this->assign('suppliers', $supplierResult);
		$this->display();
	}

	/**
	 +----------------------------------------------------------
	 * 合集增加专题
	 +----------------------------------------------------------
	 */

	 function addTopic () {
	 	$topicMod = D('Topic');
	 	$where = " type = 1 ";
	 	$timeStart = I('get.time_start','','trim,htmlspecialchars');//发布时间区间开始
    	$timeStart = strtotime($timeStart);
    	$timeEnd = I('get.time_end','','trim,htmlspecialchars');//发布时间区间结束
    	$timeEnd = strtotime($timeEnd);
		$keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
		if($keyword){
			if(is_numeric($keyword)){
				$where .= " AND topic_id =" . intval($keyword);
			}else{
				$where .= " AND title LIKE '%" . $keyword . "%'";
			}
			$this->assign('keyword', $keyword);
		}
		if($timeStart){
			$where .= " AND add_time >= $timeStart ";
			$timeStart = date("Y-m-d H:i",$timeStart);
			$this->assign('time_start', $timeStart);
		}
		if($timeEnd){
			$where .= " AND add_time <= $timeEnd ";
			$timeEnd = date("Y-m-d H:i",$timeEnd);
			$this->assign('time_end', $timeEnd);
		}
	 	$pageSize = 10;
	 	$imgHost = C('LS_IMG_URL');
	 	$count = $topicMod->where($where)->count();
        import("ORG.Util.Page");
        $p = new Page($count, $pageSize);
    	$topicResult = $topicMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
    	foreach($topicResult as $key=>$val){
			$images = $val['image_src'];
			$images = unserialize($images);
			$images = $imgHost.$images['img_url'];
			$topicResult[$key]['images'] = $images;
    	}
    	$page = $p->show();
    	$this->assign('page',$page);
    	$this->assign('topic',$topicResult);
		$this->display();
	}



	/**
	 +----------------------------------------------------------
	 * 添加/删除专题商品
	 +----------------------------------------------------------
	 */
	 function asyncSpu() {
	 	$imgHost = C('LS_IMG_URL');
	 	if(I('post.id','','')){
	 		$idArray = I('post.id','','');
	 		if($_SESSION['chooseSpu']){
	 			$_SESSION['chooseSpu'] = ",".$_SESSION['chooseSpu'];
	 		}
	 		$_SESSION['chooseSpu'] = implode(',',$idArray).$_SESSION['chooseSpu'];
	 	}
	 	if(I('get.type','','')){
	 		$spuMod = D('Spu');
			$ids = $_SESSION['chooseSpu'];
            if(I('get.any','','')==1){
                $res = $this->getTable($ids,1);
            }else if(I('get.any','','')==2){
                $res = $this->getTable($ids,2);
            }else{
                $res = $this->getTable($ids);
            }
			echo $res;
	 	}
	 	if(I('get.del','','')&&I('get.spu_id','','intval')){
	 		$id = I('get.spu_id','','trim');
	 		$idArr = explode(',',$id);
			$ids = $_SESSION['chooseSpu'];
			$idArray = explode(',',$ids);
			foreach($idArray as $k=>$v){
				if(in_array($v,$idArr)){
					unset($idArray[$k]);
					if($_SESSION['addDetail'][$v]){
						unset($_SESSION['addDetail'][$v]);
					}
				}
			}
			$spuMod = D('Spu');
			$ids = implode(',',$idArray);
			$_SESSION['chooseSpu'] = $ids;
            if(I('get.any','','')==1){
                $res = $this->getTable($ids,1);
            }elseif(I('get.any','','')==2){
                $res = $this->getTable($ids,2);
            }else{
                $res = $this->getTable($ids);
            }
			echo $res;
	 	}
	 	if(I('post.addDetail','','intval')&&I('post.sid','','intval')){
            if(I('post.any','','intval')){//为任意购活动的商品单独添加标题和描述
                $session = $_SESSION['addAnyDetail'];
                $id = I('post.sid','','intval');
                $desc = I('post.desc','','trim,htmlspecialchars');
                $title = I('post.title','','trim,htmlspecialchars');
                $img1 = I('post.img1','','trim,htmlspecialchars');
                $tmp['desc'] = $desc;
                $tmp['title'] = $title;
                if($session[$id]){
                    if(empty($tmp['desc'])&&$session[$id]['desc']){
                        $tmp['desc'] = $session[$id]['desc'];
                    }
                    if(empty($tmp['title'])&&$session[$id]['title']){
                        $tmp['title'] = $session[$id]['title'];
                    }
                    if($session[$id]['img']){
                        $tmp['img'] = $session[$id]['img'];
                    }
                }
                if($img1){
                    $p = save_img64($img1);
                    $res = up_yun($p);
                    $img = $imgHost.$res['img_src'];
                    $imgArray['img_url'] = $res['img_src'];
                    $imgArray['img_w'] = $res['img_w'];
                    $imgArray['img_h'] = $res['img_h'];
                    $imgs['nowSrc'] =  $img;
                    $imgs['images_src'] =  $imgArray;
                    $tmp['img'] = $imgs;
                    unlink($p);
                }
                $session[$id] = $tmp;
                $_SESSION['addAnyDetail'] = $session;
            }else{
                $session = $_SESSION['addDetail'];
                $id = I('post.sid','','intval');
                $desc = I('post.desc','','trim,htmlspecialchars');
                $img1 = I('post.img1','','trim,htmlspecialchars');
                $img2 = I('post.img2','','trim,htmlspecialchars');
                $tmp['desc'] = $desc;
                if($session[$id]){
                    if(empty($tmp['desc'])&&$session[$id]['desc']){
                        $tmp['desc'] = $session[$id]['desc'];
                    }
                    if($session[$id]['img']){
                        $tmp['img'] = $session[$id]['img'];
                    }
                }
                if($img1){
                    $p = save_img64($img1);
                    $res = up_yun($p);
                    $img = $imgHost.$res['img_src'];
                    $imgArray['img_url'] = $res['img_src'];
                    $imgArray['img_w'] = $res['img_w'];
                    $imgArray['img_h'] = $res['img_h'];
                    $imgs['nowSrc'] =  $img;
                    $imgs['images_src'] =  $imgArray;
                    $tmp['img'][] = $imgs;
                    unlink($p);
                }
                if($img2){
                    $p = save_img64($img2);
                    $res = up_yun($p);
                    $img = $imgHost.$res['img_src'];
                    $imgArray['img_url'] = $res['img_src'];
                    $imgArray['img_w'] = $res['img_w'];
                    $imgArray['img_h'] = $res['img_h'];
                    $imgs['nowSrc'] =  $img;
                    $imgs['images_src'] =  $imgArray;
                    $tmp['img'][] = $imgs;
                    unlink($p);
                }
                $session[$id] = $tmp;
                $_SESSION['addDetail'] = $session;
            }
	 	}
	 }

	 /**
	 +----------------------------------------------------------
	 * 添加/删除专题
	 +----------------------------------------------------------
	 */
	 function asyncTopic() {
	 	$imgHost = C('LS_IMG_URL');
	 	if(I('post.id','','')){
	 		$idArray = I('post.id','','');
	 		if($_SESSION['chooseTopic']){
	 			$_SESSION['chooseTopic'] = ",".$_SESSION['chooseTopic'];
	 		}
	 		$_SESSION['chooseTopic'] = implode(',',$idArray).$_SESSION['chooseTopic'];
	 	}
	 	if(I('get.type','','')){
			$ids = $_SESSION['chooseTopic'];
			$res = $this->getTopicTable($ids);
			echo $res;
	 	}
	 	if(I('get.del','','')&&I('get.topic_id','','')){
	 		$id = I('get.topic_id','','trim');
	 		$idArr = explode(',',$id);
			$ids = $_SESSION['chooseTopic'];
			$idArray = explode(',',$ids);
			foreach($idArray as $k=>$v){
				if(in_array($v,$idArr)){
					unset($idArray[$k]);
				}
			}
			$ids = implode(',',$idArray);
			$_SESSION['chooseTopic'] = $ids;
			$res = $this->getTopicTable($ids);
			echo $res;
	 	}
	 }


	 /**
	 +----------------------------------------------------------
	 * 添加商品描述
	 +----------------------------------------------------------
	 */
	 function addDetail () {
	 	$id = I('request.id',0,'intval');
		$this->assign('id',$id);
		$this->display();
	}

	 /**
	 +----------------------------------------------------------
	 * 上移/下移商品
	 +----------------------------------------------------------
	 */
	 function upDown(){
	 	$id = I('request.id',0,'intval');//id
	 	$type = I('request.type',0,'intval');//类型 1上移 0下移
	 	$topic = I('request.topic',0,'intval');//上下移动的是否为专题或者商品
	 	if($topic){
			$ids = $_SESSION['chooseTopic'];
	 	}else{
	 		$ids = $_SESSION['chooseSpu'];
	 	}
		$idArray = explode(',',$ids);
		$length = count($idArray);
	 	if($type == 1){
			foreach($idArray as $k=>$v){
				if($v==$id&&$k!==0){
					$tmp = $v;
					$idArray[$k] = $idArray[$k-1];
					$idArray[$k-1] = $tmp;
				}
			}
	 	}else if($type == 0){
	 		$length = $length-1;
			foreach($idArray as $k=>$v){
				if($v==$id&&$k!=$length){
					$tmp = $v;
					$idArray[$k] = $idArray[$k+1];
					$idArray[$k+1] = $tmp;
				}
			}
	 	}
	 	$ids = implode(',',$idArray);
	 	if($topic){
			$_SESSION['chooseTopic'] = $ids;
	 		$res = $this->getTopicTable($ids);
	 	}else{
	 		$_SESSION['chooseSpu'] = $ids;
            if(I('get.any','','')==1){
                $res = $this->getTable($ids,1);
            }elseif(I('get.any','','')==2){
                $res = $this->getTable($ids,2);
            }else{
                $res = $this->getTable($ids);
            }
	 	}
	 	echo $res;
	 }


	/**
	 +----------------------------------------------------------
	 * 删除商品配图
	 +----------------------------------------------------------
	 */
	function delImage () {
		$img_url = I('get.src','','');
		$detail = $_SESSION['addDetail'];
		foreach($detail as $k=>$v){
			foreach($v['img'] as $key=>$val){
				if($val['images_src']['img_url'] == $img_url){
					$idArr[] = $k;
					unset($detail[$k]['img'][$key]);
				}
			}
		}
		$_SESSION['addDetail'] = $detail;
		$res = $this->getTable($_SESSION['chooseSpu']);
		echo $res;
	}



	 /**
	 +----------------------------------------------------------
	 * 生成显示的商品表单
	 +----------------------------------------------------------
	 */
	 function getTable ($ids,$detailType=0)
     {
         $imgHost = C('LS_IMG_URL');
         $mod = M();
         $sql = "select s.spu_name,s.spu_id,i.images_src from ls_spu s,ls_spu_image i where s.spu_id in ( $ids ) AND s.spu_id = i.spu_id AND i.type = 1 order by find_in_set(s.spu_id, '$ids') ";
         $spuResult = $mod->query($sql);
         $res = "";
         if ($detailType == 1) {//生成任意购活动的商品列表（包含标题和描述）
             foreach ($spuResult as $key => $val) {
                 $img = $val['images_src'];
                 $imgSer = unserialize($img);
                 $img_src = $imgHost . $imgSer['img_url'];
                 $spuID = $val['spu_id'];
                 $detail = $_SESSION['addAnyDetail'][$spuID];
                 $str = "<tr>
                        <input type='hidden' name='addId[]' value=" . $val['spu_id'] . ">
                        <td><input type='checkbox' name='chk' data-id='" . $val['spu_id'] . "' class='inputRadio becheck' /></td>
                        <td align='center'>" . $val['spu_id'] . "</td>
                        <td align='center'><div style='float:left'><img width='60' src='" . $img_src . "'></div><div style='float:left'>" . $val['spu_name'] . "</div></td>" .
                     "<td align='center'>" . $detail['title'] . "</td>" .
                     "<td align='center'>" . $detail['desc'] . "</td>" .
                     "<td align='center'><a href='javascript:;' onclick='upSpu($spuID,1)'>上移</a><br/><a href='javascript:;' onclick='upSpu($spuID,0)'>下移</a></td>
                        <td align='center'><input type='button' value='添加描述' onclick='addDetail(" . $val['spu_id'] . ")' class='removeGuess button desc'><br/><br/><input type='button' value='移除' onclick='delSpu(" . $val['spu_id'] . ")' class='removeGuess button'></td>
                        </tr>";
                 $res .= $str;
             }
         } else if ($detailType == 2) {
             foreach($spuResult as $key=>$val){
                 $img = $val['images_src'];
                 $imgSer = unserialize($img);
                 $img_src = $imgHost.$imgSer['img_url'];
                 $spuID = $val['spu_id'];
                 $detail = $_SESSION['addAnyDetail'][$spuID];
                 $imgStr = "";
                 if($detail['img']){
                     $imgStr = "<img height=60 src='".$detail['img']['nowSrc']."'>";
                 }
                 $str = "<tr>
                        <input type='hidden' name='addId[]' value=".$val['spu_id'].">
                        <td><input type='checkbox' name='chk' data-id='".$val['spu_id']."' class='inputRadio becheck' /></td>
                        <td align='center'>".$val['spu_id']."</td>
                        <td align='center'><div style='float:left'><img width='60' src='".$img_src."'></div><div style='float:left'>".$val['spu_name']."</div></td>" .
                     "<td align='center'>$imgStr</td>" .
                     "<td align='center'>".$detail['title']."</td>" .
                     "<td align='center'>".$detail['desc']."</td>" .
                     "<td align='center'><a href='javascript:;' onclick='upSpu($spuID,1)'>上移</a><br/><a href='javascript:;' onclick='upSpu($spuID,0)'>下移</a></td>
                        <td align='center'><input type='button' value='添加描述' onclick='addDetail(".$val['spu_id'].")' class='removeGuess button desc'><br/><br/><input type='button' value='移除' onclick='delSpu(".$val['spu_id'].")' class='removeGuess button'></td>
                        </tr>";
                 $res .= $str;
             }
         }else{//生成普通活动的商品列表（包含图片和描述）
             foreach($spuResult as $key=>$val){
                 $img = $val['images_src'];
                 $imgSer = unserialize($img);
                 $img_src = $imgHost.$imgSer['img_url'];
                 $spuID = $val['spu_id'];
                 $detail = $_SESSION['addDetail'][$spuID];
                 $imgStr = '';
                 foreach($detail['img'] as $k=>$v){
                     $imgSpu = $v['images_src']['img_url'];
                     $imgStr .= "<img height=60 src='".$v['nowSrc']."'><a href='javascript:;' onclick='delImage(\"".$imgSpu."\")'>移除</a>";
                 }
                 $str = "<tr>
                        <input type='hidden' name='addId[]' value=".$val['spu_id'].">
                        <td><input type='checkbox' name='chk' data-id='".$val['spu_id']."' class='inputRadio becheck' /></td>
                        <td align='center'>".$val['spu_id']."</td>
                        <td align='center'><div style='float:left'><img width='60' src='".$img_src."'></div><div style='float:left'>".$val['spu_name']."</div></td>" .
                                 "<td align='center'>$imgStr</td>" .
                                 "<td align='center'>".$detail['desc']."</td>" .
                                 "<td align='center'><a href='javascript:;' onclick='upSpu($spuID,1)'>上移</a><br/><a href='javascript:;' onclick='upSpu($spuID,0)'>下移</a></td>
                        <td align='center'><input type='button' value='添加描述' onclick='addDetail(".$val['spu_id'].")' class='removeGuess button desc'><br/><br/><input type='button' value='移除' onclick='delSpu(".$val['spu_id'].")' class='removeGuess button'></td>
                        </tr>";
                 $res .= $str;
             }
         }
		return $res;
	 }

	 /**
	 +----------------------------------------------------------
	 * 生成显示的专题表单
	 +----------------------------------------------------------
	 */
	 function getTopicTable ($ids){
		$imgHost = C('LS_IMG_URL');
		$mod = M();
		$sql = "select t.topic_id,t.title,t.image_src from ls_topic t where t.topic_id in ( $ids ) order by find_in_set(t.topic_id, '$ids') ";
		$topicResult = $mod->query($sql);
		//$spuResult = $spuMod->relation(true)->where('spu_id in ( '.$ids.' )')->select();
		foreach($topicResult as $key=>$val){
			//$img = $val['images1'][0]['images_src'];
			$img = $val['image_src'];
			$imgSer = unserialize($img);
			$img_src = $imgHost.$imgSer['img_url'];
			$topicID = $val['topic_id'];
			$str = "<tr>
			<input type='hidden' name='addId[]' value=".$topicID.">
			<td><input type='checkbox' name='chk' data-id='".$topicID."' class='inputRadio becheck' /></td>
            <td align='center'>".$topicID."</td>
            <td align='center'>".$val['title']."</td>" .
            "<td align='center'><img src='$img_src' width=120 height=60></td>" .
            "<td align='center'><a href='javascript:;' onclick='upTopic($topicID,1)'>上移</a><br/><a href='javascript:;' onclick='upTopic($topicID,0)'>下移</a></td>
            <td align='center'><input type='button' value='移除' onclick='delTopic(".$topicID.")' class='removeGuess button'></td>
        </tr>";
			$res .= $str;
		}
		return $res;
	 }

	 /**
	 +----------------------------------------------------------
	 * 专题发布
	 +----------------------------------------------------------
	 */

	function publish () {
		$topicId = I('request.id',0,'intval');//专题id
		$topicMod = M('topic');
        $topicRelationMod = M('topic_relation');
		$data['topic_id'] = $topicId;
		$data['status'] = "published";
		$count = $topicMod->where('topic_id = '.$topicId." AND status='published' AND type = 1")->count();
        $topicRelationResult = $topicRelationMod->where('topic_id = '.$topicId)->select();
        if(!$topicRelationResult){
            $this->error('发布失败！！专题里面的没有商品！！请先上传！');
        }
        $sign = true;
        foreach($topicRelationResult as $val){
            $img = unserialize($val['image_src']);
            if(!$img[0]){
                $sign = false;
            }
        }
        if($sign){

        }else{
            $this->error('发布失败！！里面的商品有的没有上传配图！！请先编辑上传！！');
        }
		if($count){
			$this->error('专题已经发布了，不要重复发布！');
		}else{
			$flag = $topicMod->save($data);
			if($flag!==false){
                updateCache("ls_topic_list");//更新专题列表缓存
				$this->success('专题上架成功！',"?m=Topic&a=index&p=".$_SESSION['pageNow']);
			}else{
				$this->error('操作失败！');
			}
		}
	}

	/**
	 +----------------------------------------------------------
	 * 取消发布专题，状态变成locked
	 +----------------------------------------------------------
	 */

	 function cancel_pub () {
		$topicId = I('request.id',0,'intval');//专题id
		$topicMod = M('topic');
		$data['topic_id'] = $topicId;
		$data['status'] = "locked";
		$count = $topicMod->where('topic_id = '.$topicId." AND status='locked' AND type=1")->count();
		if($count){
			$this->error('专题已经下架了，不要重复操作！');
		}else{
			$flag = $topicMod->save($data);
			if($flag!==false){
                updateCache("ls_topic_list");//更新专题列表缓存
				$this->success('专题下架成功！',"?m=Topic&a=index&p=".$_SESSION['pageNow']);
			}else{
				$this->error('操作失败！');
			}
		}
	}


	/**
	 +----------------------------------------------------------
	 * 删除专题
	 +----------------------------------------------------------
	 */
	function del () {
		$topicIds = I('request.id');//专题id
		$topicMod = M('topic');
        $adMod = M('ad');
        $map['link_to'] = 1;
        $map['link_id'] = array('in',$topicIds);
        $adResult = $adMod->where($map)->find();
        if($adResult){
            echo 1;exit;
        }
		$topicRelationMod = M('topic_relation');
		$where['topic_id'] = array('in',$topicIds);
		$flag = $topicMod->where($where)->delete();
		if($flag){
			$flag = $topicRelationMod->where($where)->delete();
			if($flag!==false){
                updateCache("ls_topic_list");//更新专题列表缓存
				echo 2;
			}
		}else{
			echo 0;
		}
	}


    /**
    +----------------------------------------------------------
     * 上移下移专题位置 type=1上移  type=2下移
    +----------------------------------------------------------
     */
    function  upDownTopic (){
        $type = I('get.type',0,'intval');
        $topicId = I('get.id',0,'intval');
        $topicMod = M('topic');
        $topicResult = $topicMod->find($topicId);
        if($type&$topicResult){
            if($type==1){
                $upTopic = $topicMod->where('sort >'.$topicResult['sort'].' AND type = 1')->order('sort asc')->find();
                if(empty($upTopic)){
                    echo 0;exit;
                }
                $tmp = $topicResult['sort'];
                $data = array();
                $data['topic_id'] = $upTopic['topic_id'];
                $data['sort'] = $tmp;
                $falg1 = $topicMod->save($data);
                $tmp = $upTopic['sort'];
                $data = array();
                $data['topic_id'] = $topicResult['topic_id'];
                $data['sort'] = $tmp;
                $flag2 = $topicMod->save($data);
                updateCache("ls_topic_list");//更新专题列表缓存
                echo 1;
            }elseif($type==3){
                $downTopic = $topicMod->where('sort <'.$topicResult['sort'].' AND type = 1')->order('sort desc')->find();
                if(empty($downTopic)){
                    echo 0;exit;
                }
                $tmp = $topicResult['sort'];
                $data = array();
                $data['topic_id'] = $downTopic['topic_id'];
                $data['sort'] = $tmp;
                $falg1 = $topicMod->save($data);
                $tmp = $downTopic['sort'];
                $data = array();
                $data['topic_id'] = $topicResult['topic_id'];
                $data['sort'] = $tmp;
                $flag2 = $topicMod->save($data);
                updateCache("ls_topic_list");//更新专题列表缓存
                echo 1;
            }
            echo $type;
        }else{
            echo 0;
        }
    }

    /**
    +----------------------------------------------------------
     * 专题商品专题列表（里面的商品按列表方式）
    +----------------------------------------------------------
     */
    function indexList() {
        $timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
        $status = I('get.status','','trim,htmlspecialchars');//状态
        $topicMod = M('topic');
        $user = M('admin');
        $imgHost = C('LS_IMG_URL');
        $where = " type=0 AND topic_id not in (5,6)";
        if($keyword){
            if(is_numeric($keyword)){
                $where .= " AND topic_id =" . intval($keyword);
            }else{
                $where .= " AND title LIKE '%" . $keyword . "%'";
            }
            $this->assign('keyword', $keyword);
        }
        if($timeStart){
            $where .= " AND add_time >= $timeStart ";
            $timeStart = date("Y-m-d H:i",$timeStart);
            $this->assign('time_start', $timeStart);
        }
        if($timeEnd){
            $where .= " AND add_time <= $timeEnd ";
            $timeEnd = date("Y-m-d H:i",$timeEnd);
            $this->assign('time_end', $timeEnd);
        }
        if($status){
            $where .= " AND status = '$status'";
            $this->assign('select_status', $status);
        }
        $count = $topicMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $topicResult = $topicMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('sort desc,add_time desc')->select();
        foreach($topicResult as $key=>$val){
            $topicResult[$key]['key'] = ++$p->firstRow;
            $images = $val['image_src'];
            $images = unserialize($images);
            $images = $imgHost.$images['img_url'];
            $topicResult[$key]['image'] = $images;
            switch ($val['status']) {
                case 'published':
                    $topicResult[$key]['status'] = "已上架";
                    break;
                case 'scheduled':
                    $topicResult[$key]['status'] = "未上架";
                    break;
                case 'locked':
                    $topicResult[$key]['status'] = "已下架";
                    break;
                default:
                    break;
            }
            $name = $user->field('user_name')->find($val['admin_id']);
            $topicResult[$key]['userName'] = $name['user_name'];
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('topic',$topicResult);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 更改专题的热度值
    +----------------------------------------------------------
     */
    function changeLove(){
        $id= intval($_GET['topic']);
        $num = intval($_GET['love_num']);
        $topicMod = M('Topic');
        $old_love = $topicMod->field('hotindex')->find($id);
        if($old_love['hotindex'] > $num){
            echo 1;
        }else{
            $save = array();
            $save['topic_id'] = $id;
            $save['hotindex'] = $num;
            if($topicMod->save($save)){
                updateCache("ls_topic_list");//更新专题列表缓存
                echo 2;
            }else{
                echo 0;
            }
        }
    }
	
	/**
    +----------------------------------------------------------
     * 更改专题的分享值
    +----------------------------------------------------------
     */
	 function changeShareNum() { 
		$id = intval($_GET['topic']);
		$topicMod = M('topic');
		$share_num = $_GET['virtual_share'] ? $_GET['virtual_share'] : '';
		$old_share = $topicMod->field('virtual_share')->find($id);
        if($old_share['virtual_share'] < $share_num){
			$flag = 'success';
            $save = array();
            $save['topic_id'] = $id;
            $save['virtual_share'] = $share_num;
			$set = $topicMod->save($save);
			if($set) {
				updateCache("ls_topic_list");//更新专题列表缓存
                print_r(json_encode($flag));
			}else {
				$flag = 'fail';
				print_r(json_encode($flag));
			}
        }else{
			$flag = 'no';
            print_r(json_encode($flag));
        }
		
	 }
}
?>