<?php
/**
 * 商品管理模块
 * @author hhf
 *
 */

class SpuAction extends BaseAction{

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
	 * 商品列表
	 +----------------------------------------------------------
	 */
    function index() {
        $_SESSION['pageNow'] = intval($_GET['p'])>0?intval($_GET['p']):1;
        $this->assign('pageNow',$_SESSION['pageNow']);
    	$cateMod = D('Spu_cate');
    	$spuMod = D('Spu');
    	$spuAttrMod = D('Spu_attr');
    	$suppliersMod = M('suppliers');
    	$spuTagMod = M('spu_tag');
        $topicRelationMod = M('topic_relation');
        $topicMod = M('topic');
        $filterMod = M('filter_goods');
        $limitMod = M('limit_buy');
	$spuCateRelation = M('spu_cate_relation');//多分类
    	$imgHost = C('LS_IMG_URL');
    	//var_dump(I('get.'));
    	$where = " guide_type=0 AND status != 6";
    	$timeStart = I('get.time_start','','trim,htmlspecialchars');//发布时间区间开始
    	$timeStart = strtotime($timeStart);
    	$timeEnd = I('get.time_end','','trim,htmlspecialchars');//发布时间区间结束
    	$timeEnd = strtotime($timeEnd);
    	$cateId = I('get.cate_id',0,'intval');//分类id
		$supplierId = I('get.supplier',0,'intval');//供应商id
		$status = I('get.status',10,'intval');//状态
		$keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
		$lowPrice = I('get.low_price','','floatval');//价格区间低
		$highPrice = I('get.high_price','','floatval');//价格区间高
		$freeDelivery = I('get.free_delivery','','');//包邮
		$hot = I('get.hot','','');//爆款
		$characteristic = I('get.characteristic','','');//特色
		$edit = I('get.edit','','');//需编辑
		$pageSize = I('get.page_size','','');//每页显示数量
        $limit = I('get.limit',0,'intval');//是否开启限购
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
        if($limit==1){//启用限购
            $limitSpu = $limitMod->where('is_use = 1')->getField('spu_id',true);
            $limitSpuIds = implode(',',$limitSpu);
            $where .= " AND spu_id in ($limitSpuIds)";
        }elseif($limit==2){//不启用限购
            $limitSpu = $limitMod->where('is_use = 1')->getField('spu_id',true);
            $limitSpuIds = implode(',',$limitSpu);
            $where .= " AND spu_id not in ($limitSpuIds)";
        }
        $this->assign('select_limit',$limit);
		$count = $spuMod->where($where)->count();
        import("ORG.Util.Page");
        $p = new Page($count, $pageSize);
    	$spuResult = $spuMod->relation(true)->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
    	foreach($spuResult as $key=>$val){
			$spuResult[$key]['key'] = ++$p->firstRow;
			$images1 = $val['images1'][0]['images_src'];
			$images1 = unserialize($images1);
			$images1 = $imgHost.$images1['img_url'];
			$spuResult[$key]['images1'] = $images1;
            $spuCate = array();//查找多分类
            foreach($val['relationCate'] as $v){
                $spuCate[] = $v['cate_id'];
            }
            $cateCondition = array();
            $cateCondition['id'] = array('in',$spuCate);
            $cateResult = $cateMod->where($cateCondition)->getField('name',true);
            $cateResult = implode('<br/>',$cateResult);
            $spuResult[$key]['multipleCate'] = $cateResult;
			if($val['skulist']){//sku信息
				$skuStr = "<tr>
            			<th>名称</th>
            			<th>价格</th>
            			<th>库存</th>
            			<th>销量</th>
            		</tr>";
				foreach($val['skulist'] as $k=>$v){
					$sku_sale = $v['sku_sale'];
					$sku_stocks = $v['sku_stocks'];
					$price = $v['price']/100;
					$spuAttr = $spuAttrMod->where('spu_attr_id = '.$v['attr_combination'])->find();
					$sku_name = $spuAttr['attr_value'];
					$skuStr .= "<tr>
            					<td align='center'>$sku_name</td>
            					<td align='center'>$price</td>
            					<td align='center'>$sku_stocks</td>
            					<td align='center'>$sku_sale</td>
            					</tr>";
				}
				$spuResult[$key]['skuStr'] = $skuStr;
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
    	}
    	$page = $p->show();
    	$this->assign('page',$page);
    	$this->assign('spu',$spuResult);
    	//var_dump($spuResult);
    	$status = array();
		$spuStatus = C('SPU_STATUS');
		foreach($spuStatus as $key=>$val){
			$status[$key]['id'] = $key;
			$status[$key]['status'] = $val;
		}
		$this->assign('status',$status);
    	$cates_list = $cateMod->get_list();
		$this->assign('cate_list', $cates_list['sort_list']);
		$supplierResult = $suppliersMod->select();
		$this->assign('suppliers', $supplierResult);
    	$this->display();
    }

    /**
	 +----------------------------------------------------------
	 * 商品添加
	 +----------------------------------------------------------
	 */
    function add() {
    	$_SESSION['chooseGuess'] = '';
    	$title = I('post.title','','trim,htmlspecialchars');
        $cate = I('post.cateStr','','');//多分类
        $cate = explode(',',$cate);
    	$brand = I('post.brand',0,'intval');//品牌
    	$priceNow = I('post.pricenow',0,'floatval');
    	$priceNow = $priceNow*100;//价钱以分为单位
    	$priceOld = I('post.priceold',0,'floatval');
    	$priceOld = $priceOld*100;
    	$priceSupplier = I('post.supplierPrice',0,'floatval');
    	$priceSupplier = $priceSupplier*100;
        $virtualSales = I('post.virtualSales',0,'intval');
    	$supplier = I('post.supplier',0,'intval');
    	$shipping = I('post.shippingTemplate',0,'intval');
    	$imgp = I('post.file','','');//普通商品图
    	$imgs = I('post.imgsss','','');//商品主图
    	$skuName  = I('post.skuName','','');//SKU名称
    	$skuSale = I('post.skuSale','','');//SKU库存
    	$skuPrice = I('post.skuPrice','','');//SKU价格
        $skuLocation = I('post.skuLocation','','');//库位
        $skuCode = I('post.skuCode','','');//商品标码
    	$scene = I('post.scene','','');//情景标签
    	$free_delivery = I('post.free_delivery','','');//包邮
    	$guessId = I('post.guessId','','');//猜你喜欢
        $is_auth = I('post.auth',0,'intval');//是否认证
        $limitNUm = I('post.limit',0,'intval');//限购数量
        $limitStart = I('post.limit_start',0,'trim,htmlspecialchars');//限购开始时间
        $limitEnd = I('post.limit_end',0,'trim,htmlspecialchars');//限购结束时间
        $isUseLimit = I('post.startLimit',0,'intval');//是否启用限购
    	if($free_delivery){
			$free_delivery = 1;
    	}else{
    		$free_delivery = 0;
    	}
    	if(I('post.custom_tags',' ','')){//自定义标签
			$custom_tags = I('post.tags_name','','trim,htmlspecialchars');
    	}
    	$exp = I('post.exp','','intval');//过期时间
    	$place = I('post.place','','trim,htmlspecialchars');//产地
    	$date = I('post.date','','trim,htmlspecialchars');//生产日期
    	$time_publish = I('post.time_publish','','trim,htmlspecialchars');//上架时间
    	$info = I('post.info','','');//详情
    	$time_publish = strtotime($time_publish);
    	$data = array();
    	$data['spu_name'] = $title;
    	$data['price_old'] = $priceOld;
    	$data['price_now'] = $priceNow;
    	$data['suppliers_price'] = $priceSupplier;
    	$data['publish_time'] = $time_publish;
    	$data['shipping_free'] = $free_delivery;
    	$data['tags_name'] = $custom_tags;
    	$data['type'] = 0;
    	$data['guide_type'] = 0;
    	$data['status'] = "3";
    	$data['add_time'] = time();
    	$data['shipping_template'] = $shipping;
    	$data['update_time'] = time();
        $data['is_auth'] = $is_auth;
    	$user = $_SESSION['admin_info'];
    	$data['admin_id'] = $user['id'];
    	$data['details'] = $info;
    	$data['brand_id'] = $brand;
        $data['virtual_sales'] = $virtualSales;
    	$imageMod = M('spu_image');
    	$attrMod = M('type_attr');
    	$spuAttrMod = M('spu_attr');
    	$supMod = M('spu');
    	$skuMod = M('sku_list');
    	$guessMod = M('guess_love');
    	$spuTagMod = M('spu_tag');
    	$supplierMod = M('Suppliers');
        $limitMod = M('limit_buy');
	    $couponMod = M('coupon');
        $couponSpuMod = M('coupon_spu');
        $spuCateRelation = M('spu_cate_relation');
    	$imgHost = C('LS_IMG_URL');
        $m = M();
        $m->startTrans();
    	if(isset($_POST['dosubmit'])){
            if(empty($imgp[0])){
                $this->error('没有上传缩略图！！','?m=Spu&a=add',2);
            }
    		$data['suppliers_id'] = $supplier;
    		$supplierName = $supplierMod->field('suppliers_name')->find($supplier);
    		$data['suppliers'] = $supplierName['suppliers_name'];
            if($supplier){
                $isCoupon = $couponMod->where('suppliers_id = '.$supplier)->getField('coupon_num',true);
            }
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
                foreach($isCoupon as $val){//新增加的商品如果所属供应商参加优惠券，这个商品也要参加
                    $dataCoupon = array();
                    $dataCoupon['spu_id'] = $supFlag;
                    $dataCoupon['coupon_num'] = $val;
                    $couponFlag = $couponSpuMod->add($dataCoupon);
                    if(!$couponFlag){
                        $m->rollback();
                        $this->error("增加到优惠券失败！");
                    }
                }
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
				if($limitNUm){
                    $limitData = array();//添加限购数量记录
                    $limitData['spu_id'] = $supFlag;
                    $limitData['limit_num'] = $limitNUm;
                    $limitData['start_time'] = strtotime($limitStart);
                    $limitData['end_time'] = strtotime($limitEnd);
                    $limitData['is_use'] = $isUseLimit;
                    $limitFlag = $limitMod->add($limitData);
                    if(!$limitFlag){
                        $m->rollback();
                    }
                }
                if($cate){//添加多分类
                    foreach($cate as $val){
                        $cateDate = array();
                        $cateDate['spu_id'] = $supFlag;
                        $cateDate['cate_id'] = $val;
                        $spuCateRelation->add($cateDate);
                    }
                }
				$tasteId = $attrMod->where("attr_name = '口味' ")->field('attr_id')->find();
				foreach($skuName as $k=>$v){
					if($v){
		    			$spuAttr = array();
		    			$spuAttr['attr_value'] = $v;//具体SKU值
		    			$spuAttr['spu_id'] = $supFlag;
		    			$spuAttr['attr_id'] = $tasteId['attr_id'];
		    			$spuAttrId = $spuAttrMod->add($spuAttr);
		    			if($spuAttrId){
							$skuListArray = array();
							$skuListArray['attr_combination'] = $spuAttrId;
							$skuListArray['spu_id'] = $supFlag;
							$skuListArray['sku_stocks'] = $skuSale[$k];
							$skuListArray['price'] = intval(strval($skuPrice[$k]*100));
                            $skuListArray['suppliers_id'] = $supplier;
                            $skuListArray['sku_number'] = $skuCode[$k];
                            $skuListArray['library_number'] = $skuLocation[$k];
							$skuMod->add($skuListArray);
		    			}else{
                            $m->rollback();
                            $this->error('SKU添加失败！重新添加！！');
                        }
					}
				}
	    		$expId = $attrMod->where("attr_name = '保质期' ")->field('attr_id')->find();
	    		if($exp){
	    			$spuAttr = array();
	    			$spuAttr['attr_value'] = $exp."个月";//保质期
	    			$spuAttr['spu_id'] = $supFlag;
	    			$spuAttr['attr_id'] = $expId['attr_id'];
	    			$spuAttrMod->add($spuAttr);
	    		}
	    		$dateId = $attrMod->where("attr_name = '生产日期' ")->field('attr_id')->find();
	    		if($date){
	    			$spuAttr = array();
	    			$spuAttr['attr_value'] = $date;//生产日期
	    			$spuAttr['spu_id'] = $supFlag;
	    			$spuAttr['attr_id'] = $dateId['attr_id'];
	    			$spuAttrMod->add($spuAttr);
	    		}
	    		if($place){
		    		$placeId = $attrMod->where("attr_name = '产地' ")->field('attr_id')->find();
		    		$spuAttr = array();
		    		$spuAttr['attr_value'] = $place;//产地
		    		$spuAttr['spu_id'] = $supFlag;
		    		$spuAttr['attr_id'] = $placeId['attr_id'];
		    		$spuAttrMod->add($spuAttr);
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
                $m->commit();
	    		$this->success('添加成功！','?m=Spu&a=index');
    		}else {
                $m->rollback();
				$this->error('商品添加失败！重新添加！！');
			}
    	}else{
    		//商品运费模板
    		$shippingMod = M('shipping_template');
    		$shippingResult = $shippingMod->select();
    		$shippingTemplate = array();
    		foreach($shippingResult as $key=>$val){
    			$res = $supplierMod->find($val['suppliers_id']);
				$tmp['key'] = $val['id'];
				$tmp['value'] = $res['suppliers_name'].'--'.$val['template_name'];
				$shippingTemplate[] = $tmp;
    		}
    		$this->assign('shippingTemplate',$shippingTemplate);
    		//商品分类
    		$cateMod = D("Spu_cate");
			$cates_list = $cateMod->get_list();
			$this->assign('cate', $cates_list['sort_list']);
			$supplierRes = $supplierMod->select();
			$this->assign('supplier',$supplierRes);
    		$this->display();
    	}
    }

    /**
	 +----------------------------------------------------------
	 * 商品编辑
	 +----------------------------------------------------------
	 */
	function edit () {
		//商品分类
		$spuId = I('request.spu_id',0,'intval');
		$spuMod = D('Spu');
		$spuAttrMod = M('spu_attr');
		$attrMod = M('type_attr');
		$spuTagMod = M('spu_tag');
		$imageMod = M('spu_image');
		$supplierMod = M('suppliers');
		$skuMod = M('sku_list');
		$guessMod = M('guess_love');
		$spuTagMod = M('spu_tag');
		$topicRelationMod = M('topic_relation');
		$topicMod = M('topic');
		 $limitMod = M('limit_buy');
        $couponMod = M('coupon');
        $couponSpuMod = M('coupon_spu');
        $spuCateRelation = M('spu_cate_relation');
		$imgHost = C('LS_IMG_URL');
        $m = M();
        $m->startTrans();
		if(isset($_POST['dosubmit'])){
            $spuOld = $spuMod->field('status')->find($spuId);
			$title = I('post.title','','trim,htmlspecialchars');
            $cate = I('post.cateStr','','');//多分类
            $cate = explode(',',$cate);
	    	$brand = I('post.brand',0,'intval');//品牌
	    	$priceNow = I('post.pricenow',0,'floatval');
	    	$priceNow = $priceNow*100;//价钱以分为单位
	    	$priceOld = I('post.priceold',0,'floatval');
	    	$priceOld = $priceOld*100;
	    	$priceSupplier = I('post.supplierPrice',0,'floatval');
	    	$priceSupplier = $priceSupplier*100;
            $virtualSales = I('post.virtualSales',0,'intval');
	    	$supplier = I('post.supplier',0,'intval');
	    	$shipping = I('post.shippingTemplate',0,'intval');
	    	$imgs = I('post.imgsss','','');//商品主图
	    	$skuName  = I('post.skuName','','');//SKU名称
	    	$skuSale = I('post.skuSale','','');//SKU库存
	    	$skuPrice = I('post.skuPrice','','');//SKU价格
            $skuLocation = I('post.skuLocation','','');//库位
            $skuCode = I('post.skuCode','','');//商品标码
	    	$scene = I('post.scene','','');//情景标签
	    	$free_delivery = I('post.free_delivery','','');//包邮
	    	$guessId = I('post.guessId','','');//猜你喜欢
            $is_auth = I('post.auth',0,'intval');//是否认证
            $limitNUm = I('post.limit',0,'intval');//限购数量
            $limitStart = I('post.limit_start',0,'trim,htmlspecialchars');//限购开始时间
            $limitEnd = I('post.limit_end',0,'trim,htmlspecialchars');//限购结束时间
            $isUseLimit = I('post.startLimit',0,'intval');//是否启用限购
	    	if($free_delivery){
				$free_delivery = 1;
	    	}else{
	    		$free_delivery = 0;
	    	}
	    	if(I('post.custom_tags','','')){//自定义标签
				$custom_tags = I('post.tags_name','','trim,htmlspecialchars');
	    	}else{
	    		$custom_tags = '';
	    	}
	    	$exp = I('post.exp','','intval');//过期时间
	    	$place = I('post.place','','trim,htmlspecialchars');//产地
	    	$date = I('post.date','','trim,htmlspecialchars');//生产日期
	    	$time_publish = I('post.time_publish','','trim,htmlspecialchars');//上架时间
	    	$info = I('post.info','','');//详情
	    	$time_publish = strtotime($time_publish);
	    	$data = array();
            if($spuOld['status']==1){//如果原来已经售空。现在补库存，状态变成上架0
                foreach($skuSale as $v){
                    if($v){
                        $data['status'] = 0;
                        break;
                    }
                }
            }else{//其他状态下改库存，如果都为0，商品专题为售空
                $emptyStock = false;
                foreach($skuSale as $v){
                    if($v){
                       $emptyStock = true;
                        break;
                    }
                }
                if(!$emptyStock){
                    $data['status'] = 1;
                }
            }
            if($spuOld['status']==5){
                $data['status'] = 3;
            }
	    	$data['spu_name'] = $title;
	    	$data['brand_id'] = $brand;
            $data['virtual_sales'] = $brand;
	    	$data['price_old'] = $priceOld;
	    	$data['price_now'] = $priceNow;
	    	$data['suppliers_price'] = $priceSupplier;
	    	$data['publish_time'] = $time_publish;
	    	$data['shipping_free'] = $free_delivery;
	    	$data['tags_name'] = $custom_tags;
	    	$data['update_time'] = time();
	    	$user = $_SESSION['admin_info'];
	    	$data['admin_id'] = $user['id'];
	    	$data['details'] = $info;
	    	$data['suppliers_id'] = $supplier;
	    	$data['shipping_template'] = $shipping;
            $data['is_auth'] = $is_auth;
            $data['virtual_sales'] = $virtualSales;
    		$supplierName = $supplierMod->field('suppliers_name')->find($supplier);
    		$data['suppliers'] = $supplierName['suppliers_name'];
    		preg_match_all("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"|\?].*?[\/]?>/", $data['details'], $result);
			//var_dump($result);
			$patterns = array();
			$patterns2 = array();
			$replace1 = array();
			$image_details = array();
			$image_details_tx = array();
			$updata_img = array();
			$newImages = array();
			foreach($result[1] as $key=>$val){
					if(strpos($val,'cccwei') !== false){//如果已经是又拍云的图片就不要上传了
							$replace1[$key] = 'src="'.$val.'"';
							$val = substr($val,strpos($val,'/lingshi/'));
							$image_details[$key]['img_url'] = $val;
							$newImages[$key] = $val;
							$image_details[$key]['img_w'] = 0;
							$image_details[$key]['img_h'] = 0;
							$updata_img[] = $val;
					}else if(strpos($val,'ueditor') !== false){//本地图片上传到又拍云
								if(strpos($val,'gif') == false){//gif不上传
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
									//$image_details[$key]['add_time'] = time();
								    $res = @unlink($pth);
								}else{
									$replace1[$key] = ' ';
								}
						}else{//淘宝的图片要上传
							if(strpos($val,'gif') == false){//gif不上传
								$pth = savetaobao($val);
								//var_dump($pth);exit;
								$pth = img_zip($pth);
								$res = up_yun($pth);
								//var_dump($res);
								$replace1[$key] = 'src="'.$imgHost.$res['img_src'].'"';
								$image_details[$key]['img_url'] = $res['img_src'];
								$image_details[$key]['img_w'] = $res['img_w'];
								$image_details[$key]['img_h'] = $res['img_h'];
								//$image_details[$key]['img_type'] = $res['img_type'];
								//$image_details[$key]['type'] = 7;
								//$image_details[$key]['add_time'] = time();
								$res = @unlink($pth);
							}else{
									$replace1[$key] = ' ';
							}
					}
				$patterns[$key] = "/src=[\'|\"](.*?)[\'|\"]/";
				$patterns2[$key] = "/#lingshi/";
			}
			$data['details'] = preg_replace($patterns, '#lingshi' , $data['details'] , 1);
			$data['details'] = preg_replace($patterns2, $replace1 , $data['details'] , 1);
			$data['spu_id'] = $spuId;
			//var_dump($image_details);exit;
			$flag = $spuMod->save($data);
			$time = time();
			if($flag !== false){
				/*
				$imagesRes = $imageMod->where('spu_id = '.$spuId.' AND type = 4')->field('images_src,image_id')->select();
				foreach($imagesRes as $val){
					$imageId = $val['image_id'];
					$val = unserialize($val['images_src']);
					foreach($newImages as $k=>$v){
						if($v == $val['img_url']){
							$image_details[$k]['img_w'] = $val['img_w'];
							$image_details[$k]['img_h'] = $val['img_h'];
						}
					}
				}
				$imgFlag = $imageMod->where('spu_id = '.$spuId.' AND type = 4')->delete();
				foreach($image_details as $val){//将详情里面的图片地址存到数据库
					$imgSer = serialize($val);//序列化图片地址和宽高
					$img = array();
					$img['spu_id'] = $spuId;
					$img['type'] = 4;
					$img['add_time'] = $time++;
					$img['images_src'] = $imgSer;
					$imageMod->add($img);
				}
				*/
                if($supplier){
                    $couponNums = $couponMod->where('suppliers_id = '.$supplier)->getField('coupon_num',true);//查找供应商对应的优惠券
                }
                $couponSpuFlag = $couponSpuMod->where("spu_id = $spuId")->delete();//删除原有优惠券关系
                if($couponSpuFlag!==false){
                    $allData = array();//为商品添加新的优惠券关系
                    foreach($couponNums as $val){
                        $dataCoupon = array();
                        $dataCoupon['coupon_num'] = $val;
                        $dataCoupon['spu_id'] = $spuId;
                        $allData[] = $dataCoupon;
                    }
                    if($allData){
                        $couponSpuAdd = $couponSpuMod->addAll($allData);
                        if($couponSpuAdd===false){
                            $m->rollback();
                            $this->error("新增商品和供应商优惠券关系失败！请重试！");
                        }
                    }
                }
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
                        $limitData['limit_num'] = $limitNUm;
                        $limitData['start_time'] = strtotime($limitStart);
                        $limitData['end_time'] = strtotime($limitEnd);
                        $limitData['is_use'] = $isUseLimit;
                        $limitFlag = $limitMod->save($limitData);
                        if($limitFlag===false){
                            $m->rollback();
                        }
                    }else{
                        $limitData = array();//添加限购数量记录
                        $limitData['spu_id'] = $spuId;
                        $limitData['limit_num'] = $limitNUm;
                        $limitData['start_time'] = strtotime($limitStart);
                        $limitData['end_time'] = strtotime($limitEnd);
                        $limitData['is_use'] = $isUseLimit;
                        $limitFlag = $limitMod->add($limitData);
                        if(!$limitFlag){
                            $m->rollback();
                        }
                    }
                }
				$tasteId = $attrMod->where("attr_name = '口味' ")->field('attr_id')->find();
				//$spuAttrMod->where('spu_id = '.$spuId)->delete();
                $skuResult = $skuMod->where("spu_id = $spuId")->find();
				//$skuMod->where('spu_id = '.$spuId)->delete();
				$spuAttr = array();
//		    			$spuAttr['attr_value'] = $v;//具体SKU值
				$spuAttr['spu_id'] = $spuId;
				$spuAttr['attr_id'] = $tasteId['attr_id'];
				$spuAttrIdList = $spuAttrMod->where($spuAttr)->select();
				foreach($skuName as $k=>$v){
					if($v){
						$spuAttrId = $spuAttrIdList[$k]['spu_attr_id'];
		    			if($spuAttrId){
							$skuListArray = array();
                            $whereSku = array();
							$whereSku['attr_combination'] = $spuAttrId;
                            $whereSku['spu_id'] = $spuId;
							$skuListArray['sku_stocks'] = $skuSale[$k];
							$skuListArray['price'] = intval(strval($skuPrice[$k]*100));
                            $skuListArray['sku_number'] = $skuCode[$k];
                            $skuListArray['suppliers_id'] = $supplier;
                            $skuListArray['library_number'] = $skuLocation[$k];
							$attrListArray = array();
							$whereAttr = array();
							$attrListArray['attr_value'] = $v;
							$attrListArray['spu_id'] = $spuId;
							$attrListArray['attr_id'] = $tasteId['attr_id'];
							$attrListArray['spu_attr_id'] = $spuAttrId;
							$whereAttr['spu_attr_id'] = $spuAttrId;
							$skuFlag = $skuMod->where($whereSku)->save($skuListArray);
							$attrFlag = $spuAttrMod->where($whereAttr)->save($attrListArray);
                            if($skuFlag===false){
                                $m->rollback();
                            }
							if($attrFlag===false){
								$m->rollback();
							}
		    			}else{
							$spuAttr['attr_value'] = $v;
                            $spuAttrId = $spuAttrMod->add($spuAttr);
                            $skuListArray = array();
                            $skuListArray['attr_combination'] = $spuAttrId;
                            $skuListArray['spu_id'] = $spuId;
                            $skuListArray['sku_stocks'] = $skuSale[$k];
                            $skuListArray['price'] = intval(strval($skuPrice[$k]*100));
                            $skuListArray['suppliers_id'] = $supplier;
                            $skuListArray['sku_number'] = $skuCode[$k];
                            $skuListArray['library_number'] = $skuLocation[$k];
                            $skuFlag = $skuMod->add($skuListArray);
                            if($skuFlag===false){
                                $m->rollback();
                            }
                        }
					}
				}
				$expId = $attrMod->where("attr_name = '保质期' ")->field('attr_id')->find();
	    		if($exp){
	    			$spuAttr = array();
	    			$spuAttr['attr_value'] = $exp."个月";//保质期
                    $whereAttr = array();
                    $whereAttr['spu_id'] = $spuId;
                    $whereAttr['attr_id'] = $expId['attr_id'];
	    			$flag = $spuAttrMod->where($whereAttr)->save($spuAttr);
                    $flagExp = $spuAttrMod->where($whereAttr)->find();
                    if(!$flagExp){
                        $spuAttr = array();
                        $spuAttr['attr_value'] = $exp."个月";//保质期
                        $spuAttr['spu_id'] = $spuId;
                        $spuAttr['attr_id'] = $expId['attr_id'];
                        $spuAttrMod->add($spuAttr);
                    }
	    		}
	    		$dateId = $attrMod->where("attr_name = '生产日期' ")->field('attr_id')->find();
	    		if($date){
	    			$spuAttr = array();
	    			$spuAttr['attr_value'] = $date;//生产日期
                    $whereAttr = array();
                    $whereAttr['spu_id'] = $spuId;
                    $whereAttr['attr_id'] = $dateId['attr_id'];
	    			$flag = $spuAttrMod->where($whereAttr)->save($spuAttr);
                    $flagDate = $spuAttrMod->where($whereAttr)->find();
                    if(!$flagDate){
                        $spuAttr = array();
                        $spuAttr['attr_value'] = $date;//生产日期
                        $spuAttr['spu_id'] = $spuId;
                        $spuAttr['attr_id'] = $dateId['attr_id'];
                        $spuAttrMod->add($spuAttr);
                    }
	    		}
                $placeId = $attrMod->where("attr_name = '产地' ")->field('attr_id')->find();
	    		if($place){
		    		$spuAttr = array();
		    		$spuAttr['attr_value'] = $place;//产地
                    $whereAttr = array();
                    $whereAttr['spu_id'] = $spuId;
                    $whereAttr['attr_id'] = $placeId['attr_id'];
                    $flag = $spuAttrMod->where($whereAttr)->save($spuAttr);
                    $flagPlace = $spuAttrMod->where($whereAttr)->find();
                    if(!$flagPlace){
                        $spuAttr = array();
                        $spuAttr['attr_value'] = $place;//产地
                        $spuAttr['spu_id'] = $spuId;
                        $spuAttr['attr_id'] = $placeId['attr_id'];
                        $spuAttrMod->add($spuAttr);
                    }
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
                $m->commit();
				$this->success('修改成功！','?m=Spu&a=index&p='.$_SESSION['pageNow']);
			}else{
                $m->rollback();
				$this->error('修改失败！重新修改！！');
			}
		}else{
			$spuResult = $spuMod->relation(true)->find($spuId);
			$spuResult['price_old'] = $spuResult['price_old']/100;
			$spuResult['price_now'] = $spuResult['price_now']/100;
			$spuResult['suppliers_price'] = $spuResult['suppliers_price']/100;
			$expId = $attrMod->where("attr_name = '保质期' ")->field('attr_id')->find();
			$placeId = $attrMod->where("attr_name = '产地' ")->field('attr_id')->find();
			$dateId = $attrMod->where("attr_name = '生产日期' ")->field('attr_id')->find();
			$spuAttrResult = $spuAttrMod->where('spu_id = '.$spuId.' AND attr_id = '.$expId['attr_id'])->field('attr_value')->find();
			$spuResult['exp'] = intval($spuAttrResult['attr_value']);
			$spuAttrResult = $spuAttrMod->where('spu_id = '.$spuId.' AND attr_id = '.$placeId['attr_id'])->field('attr_value')->find();
			$spuResult['place'] = $spuAttrResult['attr_value'];
			$spuAttrResult = $spuAttrMod->where('spu_id = '.$spuId.' AND attr_id = '.$dateId['attr_id'])->field('attr_value')->find();
			$spuResult['date'] = $spuAttrResult['attr_value']?$spuAttrResult['attr_value']:"见包装";
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
			$spuResult['imgSmall']['url'] = $images1;
			$spuResult['imgSmall']['id'] = $spuResult['images1'][0]['image_id']>0?$spuResult['images1'][0]['image_id']:0;
			unset($spuResult['images1']);
			foreach($spuResult['skulist'] as $k=>$v){
				$spuAttr = $spuAttrMod->find($v['attr_combination']);
				$spuResult['skulist'][$k]['sku_name'] = $spuAttr['attr_value'];
				$spuResult['skulist'][$k]['price'] = $v['price']/100;
			}
			foreach($spuResult['guess'] as $k=>$v){
				$sessionArray[] = $v['love_id'];
				$spuGuess = $spuMod->field('spu_name')->find($v['love_id']);
				$spuResult['guess'][$k]['spu_name'] = $spuGuess['spu_name'];
			}
			foreach($spuResult['images3'] as $k=>$v){
				$images3 = $v['images_src'];
				$images3 = unserialize($images3);
				$img_url = $imgHost.$images3['img_url'];
				$spuResult['imgMain'][$k]['url'] = $img_url;
				$spuResult['imgMain'][$k]['id'] = $v['image_id'];
			}
			unset($spuResult['images3']);
			$this->assign('imgMain',$spuResult['imgMain']);
			$_SESSION['chooseGuess'] = implode(',',$sessionArray);
			$this->assign('guess',$spuResult['guess']);
			$this->assign('sku',$spuResult['skulist']);
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
			$this->assign('spu',$spuResult);
			//var_dump($spuResult);
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
			$supplierMod = M('Suppliers');
			$supplierRes = $supplierMod->select();
			$this->assign('supplier',$supplierRes);
			$shippingTemplateMod = M('shipping_template');
			$where['suppliers_id'] = $spuResult['suppliers_id'];
			$supplier = $supplierMod->field('suppliers_name')->find($spuResult['suppliers_id']);
			$shipping = $shippingTemplateMod->where($where)->select();
			$option = "";
			foreach($shipping as $val){
				if($spuResult['shipping_template']==$val['id']){
					$option .= '<option value="'.$val['id'].'" selected="selected">'.$supplier['suppliers_name'].'--'.$val['template_name'].'</option>';
				}else{
					$option .= '<option value="'.$val['id'].'">'.$supplier['suppliers_name'].'--'.$val['template_name'].'</option>';
				}
			}
			$defaultSupplier = "零食小喵";
			$map['suppliers_name'] = $defaultSupplier;
			$defaultSupplierId = $supplierMod->where($map)->find();
			$shipping = $shippingTemplateMod->where('suppliers_id = '.$defaultSupplierId['suppliers_id'])->select();
			foreach($shipping as $val){
				if($spuResult['shipping_template']==$val['id']){
					$option .= '<option value="'.$val['id'].'" selected="selected">'.$defaultSupplier.'--'.$val['template_name'].'</option>';
				}else{
					$option .= '<option value="'.$val['id'].'">'.$defaultSupplier.'--'.$val['template_name'].'</option>';
				}
			}
            $isEditSku = $topicRelationMod->where('spu_id = '.$spuId.' AND type = 2')->find();
            if($isEditSku){
                $this->assign('isEditSku',1);
            }else{
                $this->assign('isEditSku',0);
            }
			$this->assign('shipping',$option);
            $limitResult = $limitMod->where('spu_id = '.$spuId)->find();
            $this->assign('limit',$limitResult);
			$this->display();
		}
	}

	 /**
	 +----------------------------------------------------------
	 * 删除指定id图片
	 +----------------------------------------------------------
	 */

	 function del_pic () {
		$id = I('request.id',0,'intval');
		$imageMod = D('spu_image');
		$flag = $imageMod->delete($id);
		if($flag){
			$this->success('图片删除成功！！');
		}else{
			$this->error('操作失败');
		}
	}


     /**
	 +----------------------------------------------------------
	 * 商品添加猜你喜欢
	 +----------------------------------------------------------
	 */

	 function addGuess () {
	 	$cateMod = D('Spu_cate');
	 	$suppliersMod = M('suppliers');
	 	$spuMod = D('Spu');
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
			if(is_numeric($keyword)){
				$where .= " AND spu_id =" . intval($keyword);
			}else{
				$where .= " AND spu_name LIKE '%" . $keyword . "%'";
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
			$where .= " AND cid = $cateId ";
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
			$spuResult[$key]['price_old'] = $val['price_old']/100;
			$spuResult[$key]['price_now'] = $val['price_now']/100;
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
	 * 添加/删除猜你喜欢选择
	 +----------------------------------------------------------
	 */
	 function asyncGuess() {
	 	if(I('post.id','','')){
	 		$idArray = I('post.id','','');
	 		if($_SESSION['chooseGuess']){
	 			$_SESSION['chooseGuess'] .= ",";
	 		}
	 		$_SESSION['chooseGuess'] .= implode(',',$idArray);
	 	}
	 	if(I('get.type','','')){
	 		$spuMod = D('Spu');
			//echo $_SESSION['chooseGuess'];
			$ids = $_SESSION['chooseGuess'];
			$spuResult = $spuMod->where('spu_id in ( '.$ids.' )')->select();
			foreach($spuResult as $key=>$val){
				$str = "<tr>
    			<input type='hidden' name='guessId[]' value=".$val['spu_id'].">
                <td align='center'>".$val['spu_id']."</td>
                <td align='center'>".$val['spu_name']."</td>
                <td align='center'>上移<br/>下移</td>
                <td align='center'><input type='button' value='移除' onclick='delGuess(".$val['spu_id'].")' class='removeGuess button'></td>
            </tr>";
				$res .= $str;
			}
			echo $res;
	 	}
	 	if(I('get.del','','')&&I('get.spu_id','','intval')){
	 		$id = I('get.spu_id',0,'intval');
			$ids = $_SESSION['chooseGuess'];
			$idArray = explode(',',$ids);
			foreach($idArray as $k=>$v){
				if($v == $id){
					unset($idArray[$k]);
				}
			}
			$spuMod = D('Spu');
			$ids = implode(',',$idArray);
			$_SESSION['chooseGuess'] = $ids;
			$spuResult = $spuMod->where('spu_id in ( '.$ids.' )')->select();
			foreach($spuResult as $key=>$val){
				$str = "<tr>
    			<input type='hidden' name='guessId[]' value=".$val['spu_id'].">
                <td align='center'>".$val['spu_id']."</td>
                <td align='center'>".$val['spu_name']."</td>
                <td align='center'>上移<br/>下移</td>
                <td align='center'><input type='button' value='移除' onclick='delGuess(".$val['spu_id'].")' class='removeGuess button'></td>
            </tr>";
				$res .= $str;
			}
			echo $res;
	 	}
	 }

	 /**
	 +----------------------------------------------------------
	 * 修改图片 type=1修改缩略图
	 +----------------------------------------------------------
	 */
	 function update_img () {
		$id= I('request.id',0,'intval'); //图片id
		$spu_id= I('request.spu_id',0,'intval'); //商品id
		$type= I('request.type',1,'intval'); //图片类型
		$imageMod = M('spu_image');
		$imgHost = C('LS_IMG_URL');
		if (isset($_POST['dosubmit']))
        {
			$imgs = isset($_POST['file']) ? $_POST['file'] : null;
			$path = save_img64($imgs[0]);
			if($path){
				$path = img_quality_size_zip($path);
				$res = up_yun($path);
				$img['img_url'] = $res['img_src'];
				$img['img_w']=$res['img_w'];
				$img['img_h']=$res['img_h'];
				$imgSer = serialize($img);
				$saveArray['images_src'] = $imgSer;
				$saveArray['add_time'] = time();
				$result = @unlink($path);
   				if($id>0){
   					$saveArray['image_id'] = $id;
   					$flag = $imageMod->save($saveArray);
   				}else{
   					$saveArray['spu_id'] = $spu_id;
   					$saveArray['type'] = $type;
   					$flag = $imageMod->add($saveArray);
   				}
   				if($flag){
                    updateCache("ls_daily_on_new");//更新每日上新缓存
                    updateCache("ls_special_sale_list");//更新特卖缓存
                    updateCache("ls_brand_spu_list_");//更新品牌团列表缓存
   					$this->success(L('operation_success'),'', '', 'update_img');
   				}else{
   					$this->error('操作失败');
   				}
			}

        }else{
			$img = $imageMod->where('image_id = '.$id)->find();
			$img = unserialize($img['images_src']);
			$img = $imgHost.$img['img_url'];
			$this->assign('img',$img);
			$this->assign('id',$id);
			$this->assign('spu_id',$spu_id);
			$this->assign('type',$type);
			$this->display();
        }
	}
	/**
	 +----------------------------------------------------------
	 * 异步获取运费模板
	 +----------------------------------------------------------
	 */

	function get_shipping () {
		$supplierId = I('get.supplier',0,'intval');//供应商id
		$supplierMod = M('suppliers');
		$shippingTemplateMod = M('shipping_template');
		$where['suppliers_id'] = $supplierId;
		$supplier = $supplierMod->field('suppliers_name')->find($supplierId);
		$shipping = $shippingTemplateMod->where($where)->select();
		$option = "";
		foreach($shipping as $val){
			$option .= '<option value="'.$val['id'].'">'.$supplier['suppliers_name'].'--'.$val['template_name'].'</option>';
		}
		$defaultSupplier = "零食小喵";
		$map['suppliers_name'] = $defaultSupplier;
		$defaultSupplierId = $supplierMod->where($map)->find();
		$shipping = $shippingTemplateMod->where('suppliers_id = '.$defaultSupplierId['suppliers_id'])->select();
		foreach($shipping as $val){
			$option .= '<option value="'.$val['id'].'">'.$defaultSupplier.'--'.$val['template_name'].'</option>';
		}
		echo $option;
	}


	/**
	 +----------------------------------------------------------
	 * 商品发布
	 +----------------------------------------------------------
	 */

	function publish () {
		$spuId = I('request.id',0,'intval');//商品id
		$spuMod = M('spu');
		$data['spu_id'] = $spuId;
		$data['status'] = 0;
		$count = $spuMod->where('spu_id = '.$spuId.' AND status=0')->count();
        $statusResult = $spuMod->where('spu_id = '.$spuId)->field('status')->find();
        if($statusResult['status']==5){
            $this->error('商品等待编辑，编辑后再发布！！');
        }
		if($count){
			$this->error('商品已经发布了，不要重复发布！');
		}else{
			$flag = $spuMod->save($data);
			if($flag!==false){
                $xun = A('Xunsearch');//索引
                $xun->update($spuId);
				$this->success('商品上架成功！','?m=Spu&a=index&p='.$_SESSION['pageNow']);
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
				$this->success('商品下架成功！','?m=Spu&a=index&p='.$_SESSION['pageNow']);
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
			$this->success('一键下架所选商品成功！','?m=Spu&a=index&p='.$_SESSION['pageNow']);
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
			$this->success('一键上架所选商品成功！','?m=Spu&a=index&p='.$_SESSION['pageNow']);
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
                $this->success("商品删除成功","?m=Spu&a=index&p=".$_SESSION['pageNow']);
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
     * 抓取基本商品信息
    +----------------------------------------------------------
     */
    function spuCatch() {
        $this->display();
    }

}
?>