<?php
/**
 * 运费管理模块
 * @author hhf
 *
 */
class ShippingAction extends BaseAction{

	/**
	 +----------------------------------------------------------
	 * 新增运费模板
	 +----------------------------------------------------------
	 */

    function add() {
    	$selectArea = I('post.citys');//选择的区域
    	$selectAreaPrice = I('post.ccityPrice');//选择的区域对应的邮费
    	$shipping = I('post.shipping',1,'intval');//是否卖家包邮 0是 1否
    	$name = I('post.name','','trim,htmlspecialchars');//运费模板名称
    	$suppliers = I('post.supplier','','trim,htmlspecialchars');//供应商名称
    	$price = I('post.price',0,'float');//默认运费
    	$shippingAreaMod = M('shipping_area_code');
    	$suppliersMod = M('suppliers');
    	$shippingTemplateMod = M('shipping_template');
    	if($_POST['dosubmit']){
            if(empty($name)){
                $this->error("模板名称不能为空！！");
            }
    		$data = array();
    		$data['type'] = $shipping;
    		$data['template_name'] = $name;
    		$where['suppliers_name'] = $suppliers;
    		$suppliersId = $suppliersMod->where($where)->find();
    		if($suppliersId){
    			$data['suppliers_id'] = $suppliersId['suppliers_id'];
    			$data['default_price'] = $price*100;
    			$data['add_time'] = time();
				$flag = $shippingTemplateMod->add($data);
				if($flag){
					if($shipping){//买家承担运费
						foreach($selectArea as $key=>$val){
							$areas = $val;
							$areas = explode(';',$areas);
							foreach($areas as $k=>$v){
								if($v<40){//省份
									$this->findCity($flag,$selectAreaPrice[$key],$v,$v);
								}else{//城市
									$this->findCity($flag,$selectAreaPrice[$key],$v);
								}
							}
						}
					}
					$this->success("添加成功！","?m=Shipping&a=index");
				}else{
					$this->error("操作失败！！");
				}

    		}else{
				$this->error("没有找到你输入的供应商！！请检查！！");
    		}
    	}else{
    		$suppliersResult = $suppliersMod->getField('suppliers_name',true);
    		$suppliersResult = implode('|',$suppliersResult);
    		$this->assign("suppliers",$suppliersResult);
            $map = $this->createMap();
            $this->assign('map',$map);
			$this->display();
    	}
    }

	/**
	 +----------------------------------------------------------
	 * 编辑运费模板
	 +----------------------------------------------------------
	 */

    function edit() {
    	$shippingId = I('request.id',0,'intval');
    	$selectArea = I('post.citys');//选择的区域
    	$selectAreaPrice = I('post.ccityPrice');//选择的区域对应的邮费
    	$shipping = I('post.shipping',1,'intval');//是否卖家包邮 0是 1否
    	$name = I('post.name','','trim,htmlspecialchars');//运费模板名称
    	$suppliers = I('post.supplier','','trim,htmlspecialchars');//供应商名称
    	$price = I('post.price',0,'float');//默认运费
    	$shippingAreaMod = M('shipping_area_code');
    	$suppliersMod = M('suppliers');
    	$shippingTemplateMod = M('shipping_template');
    	$shippingTemplateAreaMod = M('shipping_template_area');
    	if($_POST['dosubmit']){
    		$data = array();
    		$data['type'] = $shipping;
    		$data['template_name'] = $name;
    		$where['suppliers_name'] = $suppliers;
    		$suppliersId = $suppliersMod->where($where)->find();
    		if($suppliersId){
    			$data['suppliers_id'] = $suppliersId['suppliers_id'];
    			$data['default_price'] = $price*100;
    			$data['add_time'] = time();
    			$data['id'] = $shippingId;
				$flag = $shippingTemplateMod->save($data);
				if($flag!==false){
					if($shipping){//买家承担运费
						$where = array();
						$where['template_id'] = $shippingId;
						$shippingTemplateAreaMod->where($where)->delete();
						foreach($selectArea as $key=>$val){
							$areas = $val;
							$areas = explode(';',$areas);
							foreach($areas as $k=>$v){
								if($v<40){//省份
									$this->findCity($shippingId,$selectAreaPrice[$key],$v,$v);
								}else{//城市
									$this->findCity($shippingId,$selectAreaPrice[$key],$v);
								}
							}
						}
					}
					$this->success("修改成功！","?m=Shipping&a=index");
				}else{
					$this->error("操作失败！！");
				}
    		}else{
				$this->error("没有找到你输入的供应商！！请检查！！");
    		}

    	}else{
    		$suppliersResult = $suppliersMod->getField('suppliers_name',true);
    		$suppliersResult = implode('|',$suppliersResult);
    		$this->assign("suppliers",$suppliersResult);
    		$shippingRes = $shippingTemplateMod->find($shippingId);
    		$supplier = $suppliersMod->find($shippingRes['suppliers_id']);
    		$shippingRes['supplier'] = $supplier['suppliers_name'];
    		$shippingRes['default_price'] = $shippingRes['default_price']/100;
    		$this->assign('shipping',$shippingRes);
        	$this->assign('shippingId',$shippingId);
            $this->assign('map',$this->createMap());
			$this->display();
    	}
    }

    /**
	 +----------------------------------------------------------
	 * js异步获取模板已经添加的地区
	 +----------------------------------------------------------
	 */

	 function getArea () {
		$shippingId = I('post.id',0,intval);
		$shippingAreaMod = M('shipping_area_code');
    	$suppliersMod = M('suppliers');
    	$shippingTemplateMod = M('shipping_template');
    	$shippingTemplateAreaMod = M('shipping_template_area');
		$map['template_id'] = $shippingId;
		$cityArray = $shippingTemplateAreaMod->where($map)->field('code,price')->select();
		$province=$city= array();
		foreach($cityArray as $v){
			if($v['code']<40){//全省
				$province[$v['code']] = $v['price'];
			}else{//部分城市
				$city[$v['code']] = $v['price'];
			}
		}
		$shippingPrice = array();
		$cityCode = array();
		$where = array();
		if($province){
			foreach($province as $k=>$v){
				switch ($k) {
					case 1:
						$where['province'] = "北京市";
						break;
					case 2:
						$where['province'] = "上海市";
						break;
					case 3:
						$where['province'] = "天津市";
						break;
					case 4:
						$where['province'] = "重庆市";
						break;
					case 5:
						$where['province'] = "河北省";
						break;
					case 6:
						$where['province'] = "山西省";
						break;
					case 7:
						$where['province'] = "河南省";
						break;
					case 8:
						$where['province'] = "辽宁省";
						break;
					case 9:
						$where['province'] = "吉林省";
						break;
					case 10:
						$where['province'] = "黑龙江省";
						break;
					case 11:
						$where['province'] = "内蒙古自治区";
						break;
					case 12:
						$where['province'] = "江苏省";
						break;
					case 13:
						$where['province'] = "山东省";
						break;
					case 14:
						$where['province'] = "安徽省";
						break;
					case 15:
						$where['province'] = "浙江省";
						break;
					case 16:
						$where['province'] = "福建省";
						break;
					case 17:
						$where['province'] = "湖北省";
						break;
					case 18:
						$where['province'] = "湖南省";
						break;
					case 19:
						$where['province'] = "广东省";
						break;
					case 20:
						$where['province'] = "广西壮族自治区";
						break;
					case 21:
						$where['province'] = "江西省";
						break;
					case 22:
						$where['province'] = "四川省";
						break;
					case 23:
						$where['province'] = "海南省";
						break;
					case 24:
						$where['province'] = "贵州省";
						break;
					case 25:
						$where['province'] = "云南省";
						break;
					case 26:
						$where['province'] = "西藏自治区";
						break;
					case 27:
						$where['province'] = "陕西省";
						break;
					case 28:
						$where['province'] = "甘肃省";
						break;
					case 29:
						$where['province'] = "青海省";
						break;
					case 30:
						$where['province'] = "宁夏回族自治区";
						break;
					case 31:
						$where['province'] = "新疆维吾尔族自治区";
						break;

					default:
						break;
				}
				$cityCodeArray = $shippingAreaMod->where($where)->getField('code',true);
				//var_dump($where);
				$cityCode = array_merge($cityCodeArray,$cityCode);
				$shippingPrice[$v][] = $where['province'];
				$shippingCode[$v][] = $k;
			}
    	}
    	//var_dump($cityCode);
    	$where = array();
    	foreach($city as $k=>$v){
			if(!in_array($k,$cityCode)){
				$where['code'] = $k;
				$cityName = $shippingAreaMod->where($where)->field('city')->find();
				$shippingPrice[$v][] = $cityName['city'];
				$shippingCode[$v][] = $k;
			}
    	}
    	$areaArray = array();
    	$count = 0;
    	foreach($shippingCode as $k=>$v){
			$codeArray = implode(';',$v);
			$nameArray = implode(' ',$shippingPrice[$k]);
			$resultStr = $codeArray."|".$nameArray."|".($k/100);
			$areaArray[] = $resultStr;
			$count++;
    	}
		exit(json_encode($areaArray));
	}

    /**
	 +----------------------------------------------------------
	 * 运费模板列表
	 +----------------------------------------------------------
	 */

	function index () {
		$shippingAreaMod = M('shipping_area_code');
    	$suppliersMod = M('suppliers');
    	$shippingTemplateMod = M('shipping_template');
    	$shippingTemplateAreaMod = M('shipping_template_area');
    	$timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
    	$timeStart = strtotime($timeStart);
    	$timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
    	$timeEnd = strtotime($timeEnd);
		$keyword = I('get.keyword','','trim,htmlspecialchars');//关键词，模板名称
		$suppliers = I('get.suppliers',0,'intval');//供应商
		$shippingStart = I('get.shipping_start',0,'float');//运费区间开始
		$shippingEnd = I('get.shipping_end',0,'float');//运费区间结束
		$where = " 1=1 ";
		if($suppliers){
			$where .= " and suppliers_id = $suppliers ";
			$this->assign('select_supplier',$suppliers);
		}
		if($keyword){
			$where .= " AND template_name LIKE '%" . $keyword . "%'";
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
		if($shippingStart){
			$where .= " AND default_price >= ".$shippingStart*100;
			$this->assign('shipping_start', $shippingStart);
		}
		if($shippingEnd){
			$where .= " AND default_price <= ".$shippingEnd*100;
			$this->assign('shipping_end', $shippingEnd);
		}
		$count = $shippingTemplateMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $shippingResult = $shippingTemplateMod->where($where)->order('add_time desc')->select();
        foreach($shippingResult as $key=>$val){
			$suppliersName = $suppliersMod->field('suppliers_name')->find($val['suppliers_id']);
			$shippingResult[$key]['suppliers'] = $suppliersName['suppliers_name'];
			$shippingResult[$key]['default_price'] = $val['default_price']/100;
			$map['template_id'] = $val['id'];
			$cityArray = $shippingTemplateAreaMod->where($map)->field('code,price')->select();
			$province=$city= array();
			foreach($cityArray as $v){
				if($v['code']<40){//全省
					$province[$v['code']] = $v['price'];
				}else{//部分城市
					$city[$v['code']] = $v['price'];
				}
			}
			$shippingPrice = array();
			$cityCode = array();
			$where = array();
			if($province){
				foreach($province as $k=>$v){
					switch ($k) {
						case 1:
							$where['province'] = "北京市";
							break;
						case 2:
							$where['province'] = "上海市";
							break;
						case 3:
							$where['province'] = "天津市";
							break;
						case 4:
							$where['province'] = "重庆市";
							break;
						case 5:
							$where['province'] = "河北省";
							break;
						case 6:
							$where['province'] = "山西省";
							break;
						case 7:
							$where['province'] = "河南省";
							break;
						case 8:
							$where['province'] = "辽宁省";
							break;
						case 9:
							$where['province'] = "吉林省";
							break;
						case 10:
							$where['province'] = "黑龙江省";
							break;
						case 11:
							$where['province'] = "内蒙古自治区";
							break;
						case 12:
							$where['province'] = "江苏省";
							break;
						case 13:
							$where['province'] = "山东省";
							break;
						case 14:
							$where['province'] = "安徽省";
							break;
						case 15:
							$where['province'] = "浙江省";
							break;
						case 16:
							$where['province'] = "福建省";
							break;
						case 17:
							$where['province'] = "湖北省";
							break;
						case 18:
							$where['province'] = "湖南省";
							break;
						case 19:
							$where['province'] = "广东省";
							break;
						case 20:
							$where['province'] = "广西壮族自治区";
							break;
						case 21:
							$where['province'] = "江西省";
							break;
						case 22:
							$where['province'] = "四川省";
							break;
						case 23:
							$where['province'] = "海南省";
							break;
						case 24:
							$where['province'] = "贵州省";
							break;
						case 25:
							$where['province'] = "云南省";
							break;
						case 26:
							$where['province'] = "西藏自治区";
							break;
						case 27:
							$where['province'] = "陕西省";
							break;
						case 28:
							$where['province'] = "甘肃省";
							break;
						case 29:
							$where['province'] = "青海省";
							break;
						case 30:
							$where['province'] = "宁夏回族自治区";
							break;
						case 31:
							$where['province'] = "新疆维吾尔族自治区";
							break;

						default:
							break;
					}
					$cityCodeArray = $shippingAreaMod->where($where)->getField('code',true);
					//var_dump($where);
					$cityCode = array_merge($cityCodeArray,$cityCode);
					$shippingPrice[$v][] = $where['province'];
				}
        	}
        	//var_dump($cityCode);
        	$where = array();
        	foreach($city as $k=>$v){
				if(!in_array($k,$cityCode)){
					$where['code'] = $k;
					$cityName = $shippingAreaMod->where($where)->field('city')->find();
					$shippingPrice[$v][] = $cityName['city'];
				}
        	}
        	//var_dump($shippingPrice);
        	$tr = "";
        	foreach($shippingPrice as $k=>$v){
        		$area = implode(' ',$v);
				$tr .= '<tr>
	                        <td align="center" width="60%">'.$area.'</td>
	                        <td align="center" width="40%">'.($k/100).'</td>
                    	</tr>';
        	}
			$shippingResult[$key]['tr'] = $tr;
        }
        $this->assign('shipping',$shippingResult);
        $page = $p->show();
    	$this->assign('page',$page);
    	$suppliersResult = $suppliersMod->select();
    	$suppliers = array();
    	foreach($suppliersResult as $val){
			$tmp['key'] = $val['suppliers_id'];
			$tmp['value'] = $val['suppliers_name'];
			$suppliers[] = $tmp;
    	}
    	$this->assign('suppliers',$suppliers);
		$this->display();
	}

	/**
	 +----------------------------------------------------------
	 * 根据省份查询城市
	 +----------------------------------------------------------
	 */

	function findCity ($template,$price,$city,$province=0) {
		$shippingAreaMod = M('shipping_area_code');
		if($province){
			switch ($province) {
				case 1:
					$where['province'] = "北京市";
					break;
				case 2:
					$where['province'] = "上海市";
					break;
				case 3:
					$where['province'] = "天津市";
					break;
				case 4:
					$where['province'] = "重庆市";
					break;
				case 5:
					$where['province'] = "河北省";
					break;
				case 6:
					$where['province'] = "山西省";
					break;
				case 7:
					$where['province'] = "河南省";
					break;
				case 8:
					$where['province'] = "辽宁省";
					break;
				case 9:
					$where['province'] = "吉林省";
					break;
				case 10:
					$where['province'] = "黑龙江省";
					break;
				case 11:
					$where['province'] = "内蒙古自治区";
					break;
				case 12:
					$where['province'] = "江苏省";
					break;
				case 13:
					$where['province'] = "山东省";
					break;
				case 14:
					$where['province'] = "安徽省";
					break;
				case 15:
					$where['province'] = "浙江省";
					break;
				case 16:
					$where['province'] = "福建省";
					break;
				case 17:
					$where['province'] = "湖北省";
					break;
				case 18:
					$where['province'] = "湖南省";
					break;
				case 19:
					$where['province'] = "广东省";
					break;
				case 20:
					$where['province'] = "广西壮族自治区";
					break;
				case 21:
					$where['province'] = "江西省";
					break;
				case 22:
					$where['province'] = "四川省";
					break;
				case 23:
					$where['province'] = "海南省";
					break;
				case 24:
					$where['province'] = "贵州省";
					break;
				case 25:
					$where['province'] = "云南省";
					break;
				case 26:
					$where['province'] = "西藏自治区";
					break;
				case 27:
					$where['province'] = "陕西省";
					break;
				case 28:
					$where['province'] = "甘肃省";
					break;
				case 29:
					$where['province'] = "青海省";
					break;
				case 30:
					$where['province'] = "宁夏回族自治区";
					break;
				case 31:
					$where['province'] = "新疆维吾尔族自治区";
					break;

				default:
					break;
			}
			$cityArea = $shippingAreaMod->where($where)->field('code')->select();
			$this->cityPrice($cityArea,$price,$template,$province);
		}else{
			$this->cityPrice($city,$price,$template);
		}
	}

	/**
	 +----------------------------------------------------------
	 * 将城市编号和运费插入模板运费表
	 +----------------------------------------------------------
	 */

	function cityPrice ($city,$price,$template,$province=0) {
		$shippingTemplateAreaMod = M('shipping_template_area');
		if(is_array($city)){
			foreach($city as $val){
				$data['code'] = $val['code'];
				$data['price'] = $price*100;
				$data['template_id'] = $template;
				$shippingTemplateAreaMod->add($data);
			}
			if($province){//如果选了全省就把省的编号也存进模板运费
				$data['code'] = $province;
				$data['price'] = $price*100;
				$data['template_id'] = $template;
				$shippingTemplateAreaMod->add($data);
			}
		}else{
			$data['code'] = $city;
			$data['price'] = $price*100;
			$data['template_id'] = $template;
			$shippingTemplateAreaMod->add($data);
		}
	}

	/**
	 +----------------------------------------------------------
	 * 删除模板会影响的商品
	 +----------------------------------------------------------
	 */

	 function delSpu () {
		$shippingId = I('request.id',0,'intval');//模板id
		$shippingAreaMod = M('shipping_area_code');
    	$suppliersMod = M('suppliers');
    	$shippingTemplateMod = M('shipping_template');
    	$shippingTemplateAreaMod = M('shipping_template_area');
    	$spuMod = M('spu');
    	$spuResult = $spuMod->where('shipping_template = '.$shippingId)->getField('spu_id',true);
    	$spuResultStr = implode(',',$spuResult);
    	echo $spuResultStr;
	}

	/**
	 +----------------------------------------------------------
	 * 删除模板并将影响的商品的运费模板改为零食小喵下的通用删除模板
	 +----------------------------------------------------------
	 */

	 function del () {
		$spuIds = I('request.id',0,'trim,htmlspecialchars');//商品id
		$shippingIds = I('request.shipping',0,'intval');//运费模板id
		$shippingAreaMod = M('shipping_area_code');
    	$suppliersMod = M('suppliers');
    	$shippingTemplateMod = M('shipping_template');
    	$shippingTemplateAreaMod = M('shipping_template_area');
    	$supplier = '零食小喵';
    	$where['suppliers_name'] = $supplier;
    	$suppliersId = $suppliersMod->where($where)->field('suppliers_id')->find();
    	$shippingDel = '通用删除';
    	$where = array();
    	$where['suppliers_id'] = $suppliersId['suppliers_id'];
    	$where['template_name'] = $shippingDel;
    	$shippingComDel = $shippingTemplateMod->where($where)->find();
    	$spuMod = M('spu');
    	if($shippingComDel['id']){
    		$save['shipping_template'] = $shippingComDel['id'];
	    	$spuResult = $spuMod->where('spu_id in ( '.$spuIds.' )')->save($save);
	    	if($spuResult!==false){
	    		$where= array();
	    		$where['template_id'] = $shippingIds;
				$shippingTemplateAreaMod->where($where)->delete();
				$where= array();
	    		$where['id'] = $shippingIds;
				$shippingTemplateMod->where($where)->delete();
				$this->success("删除成功！");
	    	}else{
	    		$this->error("删除失败");
	    	}
    	}else{
    		$this->error("找不到通用删除模板，无法删除，请先添加通用删除模板！");
    	}

	}

	/**
	 +----------------------------------------------------------
	 * 仅仅删除模板，不想影响其他
	 +----------------------------------------------------------
	 */

	 function onlyDel () {
		$shippingIds = I('request.shipping',0,'intval');//运费模板id
		$shippingTemplateMod = M('shipping_template');
    	$shippingTemplateAreaMod = M('shipping_template_area');
    	$where= array();
		$where['template_id'] = $shippingIds;
		$shippingTemplateAreaMod->where($where)->delete();
		$where= array();
		$where['id'] = $shippingIds;
		$shippingTemplateMod->where($where)->delete();
		$this->success("删除成功！");
	 }

    /**
    +----------------------------------------------------------
     * 检查模板名称是否存在
    +----------------------------------------------------------
     */
    function checkName() {
        $templateName = I('get.name','','trim,htmlspecialchars');
        $shippingMod = M('shipping_template');
        if($templateName){
            $template = $shippingMod->where("template_name = '$templateName'")->find();
            if($template){
                echo 1;
            }else{
                echo 2;
            }
        }else{
            echo 0;
        }
    }

    /**
     +---------------------------------------------------------
     * 生成地图
     +---------------------------------------------------------
     */
    function createMap(){
        $map = array();
        $areaMod = M('shipping_area_code');
        $areaResult = $areaMod->field('code,province,city')->select();
        foreach($areaResult as $k=>$v){
            $codeCity = array();
            $codeCity['code'] = $v['code'];
            $codeCity['city'] = $v['city'];
            $map[$v['province']][] = $codeCity;
        }
        unset($areaResult);
        $provinceCount = $areaMod->group('province')->field('province')->order('id asc')->select();
        foreach($provinceCount as $key=>$val){
            switch ($val['province']) {
                case "北京市":
                    $provinceCount[$key]['province'] =  "北京";
                    $provinceCount[$key]['key'] = 1;
                    break;
                case "上海市":
                    $provinceCount[$key]['province'] =  "上海";
                    $provinceCount[$key]['key'] = 2;
                    break;
                case "天津市":
                    $provinceCount[$key]['province'] =  "天津";
                    $provinceCount[$key]['key'] = 3;
                    break;
                case "重庆市":
                    $provinceCount[$key]['province'] =  "重庆";
                    $provinceCount[$key]['key'] = 4;
                    break;
                case "河北省":
                    $provinceCount[$key]['province'] =  "河北";
                    $provinceCount[$key]['key'] = 5;
                    break;
                case "山西省":
                    $provinceCount[$key]['province'] =  "山西";
                    $provinceCount[$key]['key'] = 6;
                    break;
                case "河南省":
                    $provinceCount[$key]['province'] =  "河南";
                    $provinceCount[$key]['key'] = 7;
                    break;
                case "辽宁省":
                    $provinceCount[$key]['province'] =  "辽宁";
                    $provinceCount[$key]['key'] = 8;
                    break;
                case "吉林省":
                    $provinceCount[$key]['province'] =  "吉林";
                    $provinceCount[$key]['key'] = 9;
                    break;
                case "黑龙江省":
                    $provinceCount[$key]['province'] =  "黑龙江";
                    $provinceCount[$key]['key'] = 10;
                    break;
                case "内蒙古自治区":
                    $provinceCount[$key]['province'] =  "内蒙古";
                    $provinceCount[$key]['key'] = 11;
                    break;
                case "江苏省":
                    $provinceCount[$key]['province'] =  "江苏";
                    $provinceCount[$key]['key'] = 12;
                    break;
                case "山东省":
                    $provinceCount[$key]['province'] =  "山东";
                    $provinceCount[$key]['key'] = 13;
                    break;
                case "安徽省":
                    $provinceCount[$key]['province'] =  "安徽";
                    $provinceCount[$key]['key'] = 14;
                    break;
                case "浙江省":
                    $provinceCount[$key]['province'] =  "浙江";
                    $provinceCount[$key]['key'] = 15;
                    break;
                case "福建省":
                    $provinceCount[$key]['province'] =  "福建";
                    $provinceCount[$key]['key'] = 16;
                    break;
                case "湖北省":
                    $provinceCount[$key]['province'] =  "湖北";
                    $provinceCount[$key]['key'] = 17;
                    break;
                case "湖南省":
                    $provinceCount[$key]['province'] =  "湖南";
                    $provinceCount[$key]['key'] = 18;
                    break;
                case "广东省":
                    $provinceCount[$key]['province'] =  "广东";
                    $provinceCount[$key]['key'] = 19;
                    break;
                case "广西壮族自治区":
                    $provinceCount[$key]['province'] =  "广西";
                    $provinceCount[$key]['key'] = 20;
                    break;
                case "江西省":
                    $provinceCount[$key]['province'] =  "江西";
                    $provinceCount[$key]['key'] = 21;
                    break;
                case "四川省":
                    $provinceCount[$key]['province'] =  "四川";
                    $provinceCount[$key]['key'] = 22;
                    break;
                case "海南省":
                    $provinceCount[$key]['province'] =  "海南";
                    $provinceCount[$key]['key'] = 23;
                    break;
                case "贵州省":
                    $provinceCount[$key]['province'] =  "贵州";
                    $provinceCount[$key]['key'] = 24;
                    break;
                case "云南省":
                    $provinceCount[$key]['province'] =  "云南";
                    $provinceCount[$key]['key'] = 25;
                    break;
                case "西藏自治区":
                    $provinceCount[$key]['province'] =  "西藏";
                    $provinceCount[$key]['key'] = 26;
                    break;
                case "陕西省":
                    $provinceCount[$key]['province'] =  "陕西";
                    $provinceCount[$key]['key'] = 27;
                    break;
                case "甘肃省":
                    $provinceCount[$key]['province'] =  "甘肃";
                    $provinceCount[$key]['key'] = 28;
                    break;
                case "青海省":
                    $provinceCount[$key]['province'] =  "青海";
                    $provinceCount[$key]['key'] = 29;
                    break;
                case "宁夏回族自治区":
                    $provinceCount[$key]['province'] = "宁夏";
                    $provinceCount[$key]['key'] = 30;
                    break;
                case "新疆维吾尔自治区":
                    $provinceCount[$key]['province'] = "新疆";
                    $provinceCount[$key]['key'] = 31;
                    break;
                default:
                    break;
            }
            $provinceCount[$key]['city'] = $map[$val['province']];
        }
        return $provinceCount;
    }
}
?>