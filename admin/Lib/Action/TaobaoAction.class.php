<?php
/**
 * 淘宝商品管理模块
 * @author hhf
 *
 */

class TaobaoAction extends BaseAction{

    /**
    +----------------------------------------------------------
     * 构造函数
    +----------------------------------------------------------
     */
    function _initialize() {
        parent::_initialize();
        $cacheKey = "mainActivity";
        updateCache($cacheKey);//清除主会场缓存
    }

	/**
	 +----------------------------------------------------------
	 * 淘宝商品列表
	 +----------------------------------------------------------
	 */

    function index() {
        $_SESSION['pageNow'] = intval($_GET['p'])>0?intval($_GET['p']):1;
        $this->assign('pageNow',$_SESSION['pageNow']);
    	$cateMod = D('Spu_cate');
    	$brandMod = D('Brand');
    	$spuMod = D('Spu');
    	$spuAttrMod = D('Spu_attr');
    	$suppliersMod = M('suppliers');
    	$spuTagMod = M('spu_tag');
        $topicRelationMod = M('topic_relation');
        $topicMod = M('topic');
        $filterMod = M('filter_goods');
        $spuCateRelation = M('spu_cate_relation');//多分类
    	$imgHost = C('LS_IMG_URL');
    	$status = array();
		$spuStatus = C('SPU_STATUS');
		$where = " guide_type != 0 AND status != 6";
    	$timeStart = I('get.time_start','','trim,htmlspecialchars');//发布时间区间开始
    	$timeStart = strtotime($timeStart);
    	$timeEnd = I('get.time_end','','trim,htmlspecialchars');//发布时间区间结束
    	$timeEnd = strtotime($timeEnd);
    	$cateId = I('get.cate_id',0,'intval');//分类id
		$brand = I('get.brands',0,'intval');//品牌id
		$status = I('get.status',10,'intval');//状态
		$keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
		$lowPrice = I('get.low_price','','floatval');//价格区间低
		$highPrice = I('get.high_price','','floatval');//价格区间高
		$freeDelivery = I('get.free_delivery','','');//包邮
		$hot = I('get.hot','','');//爆款
		$characteristic = I('get.characteristic','','');//特色
		$edit = I('get.edit','','');//需编辑
		$pageSize = I('get.page_size','','');//每页显示数量
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
		if($brand){
			$where .= " AND brand_id = $brand ";
			$this->assign('brand_id', $brand);
		}else{
			$this->assign('brand_id', 'null');
		}
		if($freeDelivery){
			$where .= " AND shipping_free = 1 ";
			$this->assign('free_delivery', "checked='checked'");
			$_SESSION['free_delivery'] = 1;
		}else{
			$_SESSION['free_delivery'] = null;
		}
		if($highPrice){
			$where .= " AND price_now <=" . intval($highPrice*100);
			$this->assign('high_price', intval($highPrice));
		}
		if($lowPrice){
			$where .= " AND price_now >=" . intval($lowPrice*100);
			$this->assign('low_price', intval($lowPrice));
		}
		if($pageSize){
			$pageSize = intval($pageSize);
			$_SESSION['page_size'] = $pageSize;
			$this->assign('page_size', intval($pageSize));
		}else{
			if($_SESSION['page_size']){
				$pageSize = $_SESSION['page_size'];
			}else{
				$pageSize = 20;
			}
			$this->assign('page_size', $pageSize);
		}
		if($status!=10){
			$where .= " AND status =" . intval($status);
			$this->assign('select_status', intval($status));
		}else{
			$this->assign('select_status', 10);
		}
		if($hot){
			$spuTagHot = $spuTagMod->where('tag = 1')->getField('spu_id',true);
			$spuTagHot = implode(',',$spuTagHot);
			$where .= " AND spu_id in ($spuTagHot)";
			$this->assign('hot', "checked='checked'");
			$_SESSION['hot'] = 1;
		}else{
			$_SESSION['hot'] = null;
		}
		if($characteristic){
			$spuCharacteristic = $spuTagMod->where('tag = 2')->getField('spu_id',true);
			$spuCharacteristic = implode(',',$spuCharacteristic);
			$where .= " AND spu_id in ($spuCharacteristic)";
			$this->assign('characteristic', "checked='checked'");
			$_SESSION['characteristic'] = 1;
		}else{
			$_SESSION['characteristic'] = null;
		}
		if($edit){
			$spuTagResEdit = $spuTagMod->where('tag = 3')->getField('spu_id',true);
			$spuTagResEdit = implode(',',$spuTagResEdit);
			$where .= " AND spu_id in ($spuTagResEdit)";
			$this->assign('edit', "checked='checked'");
			$_SESSION['edit'] = 1;
		}else{
			$_SESSION['edit'] = null;
		}
		$count = $spuMod->where($where)->count();
        import("ORG.Util.Page");
        $p = new Page($count, $pageSize);
    	$spuResult = $spuMod->relation(true)->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
    	foreach($spuResult as $key=>$val){
			$spuResult[$key]['key'] = ++$p->firstRow;
            $spuCate = array();//查找多分类
            foreach($val['relationCate'] as $v){
                $spuCate[] = $v['cate_id'];
            }
            $cateCondition = array();
            $cateCondition['id'] = array('in',$spuCate);
            $cateResult = $cateMod->where($cateCondition)->getField('name',true);
            $cateResult = implode('<br/>',$cateResult);
            $spuResult[$key]['multipleCate'] = $cateResult;
			$images1 = $val['images1'][0]['images_src'];
			$images1 = unserialize($images1);
			$images1 = $imgHost.$images1['img_url'];
			$spuResult[$key]['images1'] = $images1;
			$spuResult[$key]['price_now'] =$val['price_now']/100;
            $topicRelationResult = $topicRelationMod->where('spu_id = '.$val['spu_id'])->getField('topic_id',true);
            $topicResult = $topicMod->where('type = 2')->getField('topic_id',true);
            if(in_array('6',$topicRelationResult)){
                $spuResult[$key]['mod'] = "今日特卖";
            }elseif(in_array('5',$topicRelationResult)){
                $spuResult[$key]['mod'] = "每日上新";
            }
            foreach($topicResult as $v){
                if(in_array($v,$topicRelationResult)){
                    $spuResult[$key]['mod'] = "品牌团";
                    break;
                }
            }
    	}
    	$page = $p->show();
    	$this->assign('page',$page);
    	$this->assign('spu',$spuResult);
    	$status = array();
		foreach($spuStatus as $key=>$val){
			$status[$key]['id'] = $key;
			$status[$key]['status'] = $val;
		}
		$this->assign('status',$status);//状态
    	$cates_list = $cateMod->get_list();
		$this->assign('cate_list', $cates_list['sort_list']);//分类
		$brandResult = $brandMod->get_list();
		$this->assign('brand',$brandResult['sort_list']);//品牌
    	$this->display();
    }

    /**
	 +----------------------------------------------------------
	 * 淘宝商品添加
	 +----------------------------------------------------------
	 */

    function add() {
		$_SESSION['chooseGuess'] = '';
    	$title = I('post.title','','trim,htmlspecialchars');//标题
        $cate = I('post.cateStr','','');//多分类
        $cate = explode(',',$cate);
    	$priceNow = I('post.pricenow',0,'floatval');
    	$priceNow = $priceNow*100;//价钱以分为单位，现价
    	$priceOld = I('post.priceold',0,'floatval');
    	$priceOld = $priceOld*100;//价钱以分为单位，超市价格
    	$brand = I('post.brand',0,'intval');//品牌
        $sales = I('post.sale',0,'intval');//销量
    	$tbUrl = I('post.url','','trim,htmlspecialchars');//淘宝地址
    	$tbkUrl = I('post.tbk_url','','trim,htmlspecialchars');//淘宝客地址
    	$imgp = I('post.file','','');//普通商品图
    	$imgs = I('post.imgsss','','');//商品主图
    	$scene = I('post.scene','','');//情景标签
    	$free_delivery = I('post.free_delivery','','');//包邮
    	$guessId = I('post.guessId','','');//猜你喜欢
        $buyType = I('post.buy_type',0,'intval');//购买类型 1 淘宝客链接 0 百川
        $is_auth = I('post.auth',0,'intval');//是否认证
        $limitNUm = I('post.limit',0,'floatval');//拍立减金额
        $limitStart = I('post.limit_start',0,'trim,htmlspecialchars');//拍立减开始时间
        $limitEnd = I('post.limit_end',0,'trim,htmlspecialchars');//拍立减结束时间
        $isUseLimit = I('post.startLimit',0,'intval');//是否启用拍立减
    	if($free_delivery){
			$free_delivery = 1;
    	}else{
    		$free_delivery = 0;
    	}
    	if(I('post.custom_tags',' ','')){//自定义标签
			$custom_tags = I('post.tags_name','','trim,htmlspecialchars');
    	}
    	$time_publish = I('post.time_publish','','trim,htmlspecialchars');//上架时间
    	$info = I('post.info','','');//详情
    	$time_publish = strtotime($time_publish);
    	$data = array();
    	$data['spu_name'] = $title;
    	$data['price_old'] = $priceOld;
    	$data['price_now'] = $priceNow;
    	$data['suppliers_price'] = 0;
    	$data['brand_id'] = $brand;
    	$data['publish_time'] = $time_publish;
    	$data['shipping_free'] = $free_delivery;
    	$data['tags_name'] = $custom_tags;
    	$data['taobao_url'] = $tbUrl;
        $data['sales'] = $sales;
        $data['open_type'] = $buyType;
    	if(strpos($tbUrl,'taobao')!== false){
    		$data['guide_type'] = 1;
    	}else if(strpos($tbUrl,'tmall')!== false){
    		$data['guide_type'] = 2;
    	}else{
    		$data['guide_type'] = 1;
    	}
    	$data['tbk_url'] = $tbkUrl;
    	$data['type'] = 0;
    	$data['status'] = "3";
        $data['is_auth'] = $is_auth;
    	$data['add_time'] = time();
    	$data['update_time'] = time();
    	$user = $_SESSION['admin_info'];
    	$data['admin_id'] = $user['id'];
    	$data['details'] = $info;
    	$imageMod = M('spu_image');
    	$attrMod = M('type_attr');
    	$spuAttrMod = M('spu_attr');
    	$supMod = M('spu');
    	$skuMod = M('sku_list');
    	$guessMod = M('guess_love');
    	$spuTagMod = M('spu_tag');
    	$supplierMod = M('Suppliers');
        $spuCateRelation = M('spu_cate_relation');
        $limitMod = M('limit_buy');
    	$imgHost = C('LS_IMG_URL');
    	if(isset($_POST['dosubmit'])){
			//var_dump($data);exit;
			//将详情里面的淘宝图片上传到又拍云
			preg_match_all("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"|\?].*?[\/]?>/", $data['details'], $result);
			//var_dump($result);
			$patterns = array();
			$patterns2 = array();
			$replace1 = array();
			$image_details = array();
			//var_dump($result[1]);exit;
			$time = time();
			foreach($result[1] as $key=>$val){
					if(strpos($val,'ueditor') !== false){//本地图片上传到又拍云
						$pth = strstr($val, 'upload');
						$pth = img_zip($pth);
						$res = up_yun($pth);
						//var_dump($res);exit;
						$replace1[$key] = 'src="'.$imgHost.$res['img_src'].'"';
						$image_details[$key]['img_url'] = $res['img_src'];
						$image_details[$key]['img_w'] = $res['img_w'];
						$image_details[$key]['img_h'] = $res['img_h'];
						//$image_details[$key]['img_type'] = $res['img_type'];
						//$image_details[$key]['type'] = 7;
						//$image_details[$key]['add_time'] = $time++;
						$res = @unlink($pth);
					}else if(strpos($val,'lingshi') !== false){//如果已经是又拍云的图片就不要上传了
						$replace1[$key] = 'src="'.$val.'"';
					} else{//淘宝的图片要上传
						$pth = savetaobao($val);
						$pth = img_zip($pth);
						//var_dump($pth);exit;
						$res = up_yun($pth);
						//var_dump($res);
						$replace1[$key] = 'src="'.$imgHost.$res['img_src'].'"';
						$image_details[$key]['img_url'] = $res['img_src'];
						$image_details[$key]['img_w'] = $res['img_w'];
						$image_details[$key]['img_h'] = $res['img_h'];
						//$image_details[$key]['img_type'] = $res['img_type'];
						//$image_details[$key]['type'] = 7;
						//$image_details[$key]['add_time'] = $time++;
						$res = @unlink($pth);
					}
				$patterns[$key] = "/src=[\'|\"](.*?)[\'|\"]/";
				$patterns2[$key] = "/#lingshi/";
			}
			//var_dump($replace);
		    $data['details'] = preg_replace($patterns, '#lingshi' , $data['details'] , 1);
			$data['details'] = preg_replace($patterns2, $replace1 , $data['details'] , 1);
    		$supFlag = $supMod->add($data);
    		if($supFlag){
    			/*
				foreach($image_details as $val){//将详情里面的图片地址存到数据库
					$imgSer = serialize($val);//序列化图片地址和宽高
					$img = array();
					$img['spu_id'] = $supFlag;
					$img['type'] = 4;
					$img['add_time'] = $time++;
					$img['images_src'] = $imgSer;
					$imageMod->add($img);
				}
				*/
				$img = array();
    			$path = save_img64($imgp[0]);//上传缩略图
				$path = img_quality_size_zip($path);
				if($path){
					$res = up_yun($path);
					$img['img_url'] = $res['img_src'];
					$img['img_w']=$res['img_w'];
					$img['img_h']=$res['img_h'];
					$result = @unlink($path);
				}
				$imgSer = serialize($img);//序列化图片地址和宽高
				$img = array();
				$img['type'] = 1;
				$img['images_src'] = $imgSer;
				$img['add_time'] = time();
				$img['spu_id'] = $supFlag;
				$imageMod->add($img);
				$time = time();
				foreach($imgs as $k=>$v){//上传商品主图
					$path = save_img64($v);
					$path = img_quality_size_zip($path);
					$img = array();
					if($path){
						$res = up_yun($path);
						$img['img_url'] = $res['img_src'];
						$img['img_w']=$res['img_w'];
						$img['img_h']=$res['img_h'];
						$result = @unlink($path);
					}
					$imgSer = serialize($img);//序列化图片地址和宽高
					$img = array();
					$img['type'] = 3;
					$img['images_src'] = $imgSer;
					$img['add_time'] = $time++;
					$img['spu_id'] = $supFlag;
					$imageMod->add($img);
				}
				foreach($guessId as $k=>$v){//添加猜你喜欢
					$saveGuess = array();
					$saveGuess['love_id'] = $v;
					$saveGuess['spu_id'] = $supFlag;
					$saveGuess['type'] = 1;
					$guessMod->add($saveGuess);
	    		}
	    		foreach($scene as $v){
					$spuTag = array();
					$spuTag['spu_id'] =  $supFlag;
					$spuTag['tag'] =  $v;
					$spuTagMod->add($spuTag);
	    		}
                if($limitNUm){
                    $limitData = array();//添加限购数量记录
                    $limitData['spu_id'] = $supFlag;
                    $limitData['limit_num'] = $limitNUm*100;
                    $limitData['start_time'] = strtotime($limitStart);
                    $limitData['end_time'] = strtotime($limitEnd);
                    $limitData['is_use'] = $isUseLimit;
                    $limitData['type'] = 2;
                    $limitFlag = $limitMod->add($limitData);
                }
                if($cate){//添加多分类
                    foreach($cate as $val){
                        $cateDate = array();
                        $cateDate['spu_id'] = $supFlag;
                        $cateDate['cate_id'] = $val;
                        $spuCateRelation->add($cateDate);
                    }
                }
	    		$this->success('添加成功！','?m=Taobao&a=index');
    		}else{
    			$this->error('添加失败！重新添加！！');
    		}
    	}else{
    		//商品分类
    		$cateMod = D("Spu_cate");
			$cates_list = $cateMod->get_list();
			$this->assign('cate', $cates_list['sort_list']);
    		$this->display();
    	}
    }

   /**
	 +----------------------------------------------------------
	 * 获取推荐品牌
	 +----------------------------------------------------------
	 */
    public function getBrands()
    {
        $cate_mod = D('Spu_cate');
        $cate_brand_mod = D('Cate_brand');
        $brand_mod = D('Brand');
        $parent_id = isset($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : null;
        $res = $cate_mod->where('id in('.$parent_id.')')->field('pid')->select();
        $cate_array = '';
        foreach($res as $key=>$val){
        	$cate_array .= $val['pid'].',';
        }
        $cate_array = substr($cate_array,0,-1);
        $brand_ids_list = $cate_brand_mod->relation(true)->where('cate_id in('.$cate_array.')')->select();
        foreach($brand_ids_list as $key=>$val){
				$brand_ids_list[$key]['id'] = $val['brand']['id'];
				$brand_ids_list[$key]['name'] = $val['brand']['name'];
        	}
        $brand_ids_list = $brand_mod->brand_list($brand_ids_list);
        $content = "<option value=''>--请选择--</option>";
        foreach ($brand_ids_list['sort_list'] as $val)
        {
        	if(is_numeric($val['id'])){
				$content .= "<option value='" . $val['id'] . "'>" . $val['name'] . "</option>";
        	}else{
        		$content .= "<option value='" . $val['id'] . "' class='color' >" . $val['name'] . "</option>";
        	}

        }
        $data = array(
            'content' => $content
        );
        echo json_encode($data);
    }

    /**
	 +----------------------------------------------------------
	 * 淘宝商品修改
	 +----------------------------------------------------------
	 */

    function edit() {
    	$spuId = I('request.id',0,'intval');//商品id
		$imageMod = M('spu_image');
    	$spuMod = D('Spu');
    	$guessMod = M('guess_love');
    	$spuTagMod = M('spu_tag');
        $topicRelationMod = M('topic_relation');
        $spuCateRelation = M('spu_cate_relation');
        $topicMod = M('topic');
        $limitMod = M('limit_buy');
    	$imgHost = C('LS_IMG_URL');
    	$title = I('post.title','','trim,htmlspecialchars');//标题
        $cate = I('post.cateStr','','');//多分类
        $cate = explode(',',$cate);
    	$priceNow = I('post.pricenow',0,'floatval');
    	$priceNow = $priceNow*100;//价钱以分为单位，现价
    	$priceOld = I('post.priceold',0,'floatval');
    	$priceOld = $priceOld*100;//价钱以分为单位，超市价格
    	$brand = I('post.brand',0,'intval');//品牌
        $sales = I('post.sale',0,'intval');//销量
    	$tbUrl = I('post.url','','trim,htmlspecialchars');//淘宝地址
    	$tbkUrl = I('post.tbk_url','','trim,htmlspecialchars');//淘宝客地址
    	$imgp = I('post.file','','');//普通商品图
    	$imgs = I('post.imgsss','','');//商品主图
    	$scene = I('post.scene','','');//情景标签
    	$free_delivery = I('post.free_delivery','','');//包邮
    	$guessId = I('post.guessId','','');//猜你喜欢
        $buyType = I('post.buy_type',0,'intval');//购买类型 1 淘宝客链接 0 百川
        $is_auth = I('post.auth',0,'intval');//是否认证
        $limitNUm = I('post.limit',0,'floatval');//拍立减金额
        $limitStart = I('post.limit_start',0,'trim,htmlspecialchars');//拍立减开始时间
        $limitEnd = I('post.limit_end',0,'trim,htmlspecialchars');//拍立减结束时间
        $isUseLimit = I('post.startLimit',0,'intval');//是否启用拍立减
    	if($free_delivery){
			$free_delivery = 1;
    	}else{
    		$free_delivery = 0;
    	}
    	if(I('post.custom_tags',' ','')){//自定义标签
			$custom_tags = I('post.tags_name','','trim,htmlspecialchars');
    	}
    	$time_publish = I('post.time_publish','','trim,htmlspecialchars');//上架时间
    	$info = I('post.info','','');//详情
    	$time_publish = strtotime($time_publish);
    	$data = array();
    	$data['spu_name'] = $title;
    	$data['price_old'] = $priceOld;
    	$data['price_now'] = $priceNow;
    	$data['brand_id'] = $brand;
    	$data['publish_time'] = $time_publish;
    	$data['shipping_free'] = $free_delivery;
    	$data['tags_name'] = $custom_tags;
    	$data['taobao_url'] = $tbUrl;
        $data['sales'] = $sales;
        $data['open_type'] = $buyType;
    	$data['tbk_url'] = $tbkUrl;
        $data['is_auth'] = $is_auth;
        $spuResult = $spuMod->relation(true)->find($spuId);
        if($spuResult['status']==5){
            $data['status'] = "3";
        }
    	$data['update_time'] = time();
    	$user = $_SESSION['admin_info'];
    	$data['admin_id'] = $user['id'];
    	$data['details'] = $info;
    	if(isset($_POST['dosubmit'])){
			//将详情里面的淘宝图片上传到又拍云
			preg_match_all("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"|\?].*?[\/]?>/", $data['details'], $result);
			//var_dump($result);
			$patterns = array();
			$patterns2 = array();
			$replace1 = array();
			$image_details = array();
			//var_dump($result[1]);exit;
			$time = time();
			foreach($result[1] as $key=>$val){
					if(strpos($val,'ueditor') !== false){//本地图片上传到又拍云
						$pth = strstr($val, 'upload');
						$pth = img_zip($pth);
						$res = up_yun($pth);
						//var_dump($res);exit;
						$replace1[$key] = 'src="'.$imgHost.$res['img_src'].'"';
						$image_details[$key]['img_url'] = $res['img_src'];
						$image_details[$key]['img_w'] = $res['img_w'];
						$image_details[$key]['img_h'] = $res['img_h'];
						//$image_details[$key]['img_type'] = $res['img_type'];
						//$image_details[$key]['type'] = 7;
						//$image_details[$key]['add_time'] = $time++;
						$res = @unlink($pth);
					}else if(strpos($val,'lingshi') !== false){//如果已经是又拍云的图片就不要上传了
						$replace1[$key] = 'src="'.$val.'"';
					} else{//淘宝的图片要上传
						$pth = savetaobao($val);
						$pth = img_zip($pth);
						//var_dump($pth);exit;
						$res = up_yun($pth);
						//var_dump($res);
						$replace1[$key] = 'src="'.$imgHost.$res['img_src'].'"';
						$image_details[$key]['img_url'] = $res['img_src'];
						$image_details[$key]['img_w'] = $res['img_w'];
						$image_details[$key]['img_h'] = $res['img_h'];
						//$image_details[$key]['img_type'] = $res['img_type'];
						//$image_details[$key]['type'] = 7;
						//$image_details[$key]['add_time'] = $time++;
						$res = @unlink($pth);
					}
				$patterns[$key] = "/src=[\'|\"](.*?)[\'|\"]/";
				$patterns2[$key] = "/#lingshi/";
			}
			//var_dump($replace);
		    $data['details'] = preg_replace($patterns, '#lingshi' , $data['details'] , 1);
			$data['details'] = preg_replace($patterns2, $replace1 , $data['details'] , 1);
			$data['spu_id'] = $spuId;
			$flag = $spuMod->save($data);
			if($flag !== false){
				foreach($imgs as $k=>$v){//上传商品主图
					$path = save_img64($v);
					$path = img_quality_size_zip($path);
					$img = array();
					if($path){
						$res = up_yun($path);
						$img['img_url'] = $res['img_src'];
						$img['img_w']=$res['img_w'];
						$img['img_h']=$res['img_h'];
						$result = @unlink($path);
					}
					$imgSer = serialize($img);//序列化图片地址和宽高
					$img = array();
					$img['type'] = 3;
					$img['images_src'] = $imgSer;
					$img['add_time'] = $time++;
					$img['spu_id'] = $spuId;
					$imageMod->add($img);
				}
				$guessMod->where('spu_id = '.$spuId)->delete();
				foreach($guessId as $k=>$v){//添加猜你喜欢
					$saveGuess = array();
					$saveGuess['love_id'] = $v;
					$saveGuess['spu_id'] = $spuId;
					$saveGuess['type'] = 1;
					$guessMod->add($saveGuess);
	    		}
	    		$spuTagMod->where('spu_id = '.$spuId)->delete();
	    		foreach($scene as $v){//添加标签
					$spuTag = array();
					$spuTag['spu_id'] =  $spuId;
					$spuTag['tag'] =  $v;
					$spuTagMod->add($spuTag);
	    		}
                if($cate){//添加多分类
                    if($cate[0]!=0){
                        $spuCateRelationFlag = $spuCateRelation->where("spu_id = $spuId")->delete();
                        if($spuCateRelationFlag!==false){
                            foreach($cate as $val){
                                $cateDate = array();
                                $cateDate['spu_id'] = $spuId;
                                $cateDate['cate_id'] = $val;
                                $spuCateRelation->add($cateDate);
                            }
                        }
                    }
                }
                if($limitNUm){//修改限购
                    $limitResult = $limitMod->where('spu_id = '.$spuId)->field('id')->find();
                    if($limitResult){
                        $limitData = array();
                        $limitData['id'] = $limitResult['id'];
                        $limitData['spu_id'] = $spuId;
                        $limitData['limit_num'] = $limitNUm*100;
                        $limitData['start_time'] = strtotime($limitStart);
                        $limitData['end_time'] = strtotime($limitEnd);
                        $limitData['is_use'] = $isUseLimit;
                        $limitFlag = $limitMod->save($limitData);
                    }else{
                        $limitData = array();//添加限购数量记录
                        $limitData['spu_id'] = $spuId;
                        $limitData['limit_num'] = $limitNUm*100;
                        $limitData['start_time'] = strtotime($limitStart);
                        $limitData['end_time'] = strtotime($limitEnd);
                        $limitData['is_use'] = $isUseLimit;
                        $limitFlag = $limitMod->add($limitData);
                    }
                }
	    		$this->success('修改成功！','?m=Taobao&a=index&p='.$_SESSION['pageNow']);
			}else{
				$this->error('修改失败！重新修改！！');
			}
    	}else {
    		$spuResult = $spuMod->relation(true)->find($spuId);
    		$spuResult['price_old'] = $spuResult['price_old']/100;
    		$spuResult['price_now'] =$spuResult['price_now']/100;
    		$spuTagRes = $spuTagMod->where('spu_id = '.$spuId)->field('tag')->select();
			foreach($spuTagRes as $val){
				switch ($val['tag']) {
					case 1:
						$spuResult['hot'] = "checked=true";
						break;
					case 2:
						$spuResult['characteristic'] = "checked=true";
						break;
					case 3:
						$spuResult['edit'] = "checked=ttrue";
						break;
					default:
						break;
				}
			}
			$images1 = $spuResult['images1'][0]['images_src'];
			$images1 = unserialize($images1);
			$images1 = $imgHost.$images1['img_url'];
			$spuResult['imgSmall']['url'] = $images1;//商品缩略图
			$spuResult['imgSmall']['id'] = $spuResult['images1'][0]['image_id']>0?$spuResult['images1'][0]['image_id']:0;
			unset($spuResult['images1']);
            $topicRelationResult = $topicRelationMod->where('spu_id = '.$spuId)->getField('topic_id',true);
            $topicResult = $topicMod->where('type = 2')->getField('topic_id',true);
            if(in_array('6',$topicRelationResult)){
                $spuResult['mod'] = "今日特卖";
            }elseif(in_array('5',$topicRelationResult)){
                $spuResult['mod'] = "每日上新";
            }
            foreach($topicResult as $val){
                if(in_array($val,$topicRelationResult)){
                    $spuResult['mod'] = "品牌团";
                    break;
                }
            }
			foreach($spuResult['images3'] as $k=>$v){//商品主图
				$images3 = $v['images_src'];
				$images3 = unserialize($images3);
				$img_url = $imgHost.$images3['img_url'];
				$spuResult['imgMain'][$k]['url'] = $img_url;
				$spuResult['imgMain'][$k]['id'] = $v['image_id'];
			}
			unset($spuResult['images3']);
			$this->assign('imgMain',$spuResult['imgMain']);
			foreach($spuResult['guess'] as $k=>$v){
				$sessionArray[] = $v['love_id'];
				$spuGuess = $spuMod->field('spu_name')->find($v['love_id']);
				$spuResult['guess'][$k]['spu_name'] = $spuGuess['spu_name'];
			}
			$_SESSION['chooseGuess'] = implode(',',$sessionArray);
    		$this->assign('spu',$spuResult);
    		$this->assign('guess',$spuResult['guess']);
    		//商品分类
    		$cateMod = D("Spu_cate");
			$cates_list = $cateMod->get_list();
			$this->assign('cate', $cates_list['sort_list']);
            $res_cate = $spuCateRelation->where('spu_id = '.$spuId)->getField('cate_id',true);
            $this->assign('select_cate',$res_cate);
            $res_str = implode(',',$res_cate);
            $this->assign('res_cate',$res_str);
            $cateMod = M('spu_cate');
            $cateIds = $cateMod->where('id in ('.$res_str.')')->getField('pid',true);
            $cateIds = implode(',',$cateIds);
			$cate_brand_mod = D('Cate_brand');
			$brand_mod = D('Brand');
			$brand_ids_list = $cate_brand_mod->relation(true)->where('cate_id in('.$cateIds.')')->select();
        	foreach($brand_ids_list as $key=>$val){
				$brand_ids_list[$key]['id'] = $val['brand_id'];
				$brand_ids_list[$key]['name'] = $val['brand']['name'];
        	}
        	$brand_ids_list = $brand_mod->brand_list($brand_ids_list);
        	$this->assign('brand',$brand_ids_list['sort_list']);
            $limitResult = $limitMod->where('spu_id = '.$spuId)->find();//拍立减信息
            $limitResult['limit_num'] = $limitResult['limit_num']/100;
            $this->assign('limit',$limitResult);
			$this->display();
		}
    }

    /**
	 +----------------------------------------------------------
	 * 淘宝商品抓取
	 +----------------------------------------------------------
	 */

    function taobaoCatch() {
    	$this->display();
    }

    /**
	 +----------------------------------------------------------
	 * 淘宝商品发布
	 +----------------------------------------------------------
	 */

	function publish () {
		$spuId = I('request.id',0,'intval');//商品id
		$spuMod = M('spu');
		$data['spu_id'] = $spuId;
		$data['status'] = 0;
		$count = $spuMod->where('spu_id = '.$spuId.' AND status=0')->count();
		if($count){
			$this->error('商品已经发布了，不要重复发布！');
		}else{
			$flag = $spuMod->save($data);
			if($flag!==false){
                $xun = A('Xunsearch');//索引
                $xun->update($spuId);

				$this->success('商品上架成功！','?m=Taobao&a=index&p='.$_SESSION['pageNow']);
			}else{
				$this->error('操作失败！');
			}
		}
	}

	/**
	 +----------------------------------------------------------
	 * 取消发布商品
	 +----------------------------------------------------------
	 */

	 function cancel_pub () {
		$spuId = I('request.id',0,'intval');//商品id
		$spuMod = M('spu');
		$data['spu_id'] = $spuId;
		$data['status'] = 2;
		$count = $spuMod->where('spu_id = '.$spuId.' AND status=2')->count();
		if($count){
			$this->error('商品已经下架了，不要重复操作！');
		}else{
			$flag = $spuMod->save($data);
            if($flag!==false){
                updateCache("ls_daily_on_new");//更新每日上新缓存
                updateCache("ls_special_sale_list");//更新特卖缓存
                updateCache("ls_brand_spu_list_");//更新品牌团列表缓存
                $xun = A('Xunsearch');//索引
                $xun->del($spuId);
				$this->success('商品下架成功！','?m=Taobao&a=index&p='.$_SESSION['pageNow']);
			}else{
				$this->error('操作失败！');
			}
		}
	}

	/**
	 +----------------------------------------------------------
	 * 一键下架商品
	 +----------------------------------------------------------
	 */
	function onceOff () {
		$spuId = I('request.id',0,'trim,htmlspecialchars');//商品id
		$spuMod = M('spu');
		$data['status'] = 2;
		$flag = $spuMod->where('spu_id in ( '.$spuId.' )')->save($data);
		if($flag!==false){
            $spuArray = explode(',',$spuId);
            updateCache("ls_daily_on_new");//更新每日上新缓存
            updateCache("ls_special_sale_list");//更新特卖缓存
            updateCache("ls_brand_spu_list_");//更新品牌团列表缓存
            $xun = A('Xunsearch');//索引
            foreach($spuArray as $val){
                $xun->del($val);
            }
			$this->success('一键下架所选商品成功！','?m=Taobao&a=index&p='.$_SESSION['pageNow']);
		}else{
			$this->error('操作失败！');
		}
	}

	/**
	 +----------------------------------------------------------
	 * 一键上架商品
	 +----------------------------------------------------------
	 */
	function onceOn () {
		$spuId = I('request.id',0,'trim,htmlspecialchars');//商品id
		$spuMod = M('spu');
		$data['status'] = 0;
		$flag = $spuMod->where('spu_id in ( '.$spuId.' )')->save($data);
		if($flag!==false){
            $spuArray = explode(',',$spuId);
            $xun = A('Xunsearch');//索引
            foreach($spuArray as $val){
                $xun->update($val);
            }
			$this->success('一键上架所选商品成功！','?m=Taobao&a=index&p='.$_SESSION['pageNow']);
		}else{
			$this->error('操作失败！');
		}
	}

	/**
	 +----------------------------------------------------------
	 * 删除商品,status改为6
	 +----------------------------------------------------------
	 */
	function del () {
		$spuId = I('request.id',0,'');//商品id
		if(is_array($spuId)){
			$spuId = implode(',',$spuId);
		}
		$spuMod = M('spu');
        $filterMod = M('filter_goods');
        $m = M();
        $m->startTrans();
        $delData = array();
        $delData['status'] = 6;
        $flag = $spuMod->where("spu_id in ($spuId)")->save($delData);
        if($flag){
            $filterFlag = $filterMod->where("gid in ($spuId)")->delete();
            if($filterFlag!==false){
                $m->commit();
                updateCache("ls_daily_on_new");//更新每日上新缓存
                updateCache("ls_special_sale_list");//更新特卖缓存
                updateCache("ls_brand_spu_list_");//更新品牌团列表缓存
                $this->success("商品删除成功","?m=Taobao&a=index&p=".$_SESSION['pageNow']);
            }else{
                $m->rollback();
                $this->error("商品删除失败");
            }
        }else{
            $m->rollback();
            $this->error("商品删除失败");
        }
	}

    /**
    +----------------------------------------------------------
     * 评论查看与添加
    +----------------------------------------------------------
     */
    function comment()
    {
        $comment_mod = M('comment');
        $imgHost = C('LS_IMG_URL');
        if (isset($_POST['dosubmit'])) {
            $comment_nickname = isset($_POST['nickname']) ? ($_POST['nickname']) : $this->error('评论昵称不能为空！'); //评论昵称
            $comment = isset($_POST['comment']) ? ($_POST['comment']) : $this->error('评论内容不能为空！'); //评论内容
            $id = isset($_POST['id']) ? intval($_POST['id']) : $this->error('id不能为空！', '?m=Taobao&a=index&p='.$_SESSION['pageNow']); //商品id
            $map = array();
            $imgs = isset($_POST['file']) ? $_POST['file'] : null;
            foreach ($comment_nickname as $key => $val) {
                $map[$key]['nickname'] = htmlspecialchars(trim($val));
            }
            foreach ($comment as $key => $val) {
                $map[$key]['comment'] = htmlspecialchars(trim($val));
            }
            foreach ($imgs as $key => $val) {
                $map[$key]['images'] = $val;
            }
            foreach ($map as $key => $val) {
                $data = array();
                $path = save_img64($val['images']);
                if($path) {
                    $res = up_yun($path);
                    $data['avatar_src'] = $imgHost.$res['img_src'];
                    $result = @unlink($path);
                }else{
                    $data['avatar_src'] = "http://assets.alicdn.com/app/sns/img/default/avatar-80.png";
                }
                $data['nickname'] = $val['nickname'];
                $data['comment'] = $val['comment'];
                $data['type'] = 0;
                $data['spu_id'] = $id;
                $data['add_time'] = time();
                $flag = $comment_mod->add($data);
            }
            $this->success('添加评论操作成功');
        } else {
            $id = isset($_REQUEST['spuId']) ? intval($_REQUEST['spuId']) : $this->error('id不能为空！', '?m=Taobao&a=index&p='.$_SESSION['pageNow']);
            $this->assign('id', $id);
            $comments = $comment_mod->where('spu_id =' . $id . ' AND type = 0')->select();//只查询抓取过来的评论
            $this->assign('comments', $comments);
            $this->display();
        }
    }
    /**
    +----------------------------------------------------------
     * 删除评论
    +----------------------------------------------------------
     */
    public function delComment(){
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : $this->error('请选择要删除的评论！','?m=Taobao&a=index&p='.$_SESSION['pageNow']);
        $commentMod = M('comment');
        $commentMod->where('id ='.$id.' AND type = 0')->delete();
        $this->success('删除评论成功！');
    }
    /**
    +----------------------------------------------------------
     * 修改评论
    +----------------------------------------------------------
     */
    function editComment(){
        $imgHost = C('LS_IMG_URL');
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $commentMod = M('comment');
        if(isset($_POST['dosubmit'])){
            $nickname = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
            $comment = isset($_POST['comment']) ? htmlspecialchars(trim($_POST['comment'])) : '';
            if(empty($nickname))$this->error("昵称不能为空");
            if(empty($comment))$this->error("评论不能为空");
            $data['nickname'] = $nickname;
            $data['comment'] = $comment;
            $imgs = isset($_POST['file']) ? $_POST['file'] : null;
            $path = save_img64($imgs[0]);
            if($path){
                $res = up_yun($path);
                $data['avatar_src'] = $imgHost.$res['img_src'];
                $result = @unlink($path);
                if(!$data['avatar_src']){
                    $data['avatar_src'] = "http://assets.alicdn.com/app/sns/img/default/avatar-80.png";
                }
            }

            if($commentMod->where("id = $id")->save($data)){
                echo "<script>alert('修改成功!')</script>";
                $this->success(L('修改成功'), '', '', 'edit_comment');
            }else{
                $this->error("修改失败,请填写修改信息");
            }
        }else{
            $com_list = $commentMod->where("id = $id")->select();
            $this->assign('com_list',$com_list);
            $this->display();
        }
    }


}
?>