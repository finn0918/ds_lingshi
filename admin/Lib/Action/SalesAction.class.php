<?php
/**
 * 促销管理
 * Date: 2015/8/17
 * @author hhf
 */

class SalesAction extends BaseAction{

    /**
    +----------------------------------------------------------
     * 新增促销活动（满减）
    +----------------------------------------------------------
     */
    function addFullCut() {
        $suppliersMod = M('suppliers');
        $name = I('post.name','','trim,htmlspecialchars');//活动名称
        $money = I('post.money','','float');//满多少钱
        $timePublish = I('post.time_publish','','trim,htmlspecialchars');//发布时间
        $timeEnd = I('post.time_off','','trim,htmlspecialchars');//下架时间
        $shipping = I('post.shipping',1,'intval');//包邮类型 1 商家包邮 2 平台包邮
        $suppliers = I('post.suppliers',0,'intval');//供应商id
        if($shipping==2){
            $suppliers = 0;//供应商id
        }
        $areaArray = I('post.citys');
        $FullSaleMod = M('free_fee_template');
        $FullSaleAreaMod = M('free_fee_template_area');
        if(isset($_POST['dosubmit'])){
            $data = array();
            $data['template_name'] = $name;
            $data['price'] = $money*100;//以分为单位
            $data['type'] = $shipping;
            $data['suppliers_id'] = $suppliers;
            $data['status'] = "scheduled";
            $data['add_time'] = time();
            $data['start_time'] = strtotime($timePublish);
            $data['end_time'] = strtotime($timeEnd);
            $selectArea = explode(';',$areaArray[0]);
            $flag = $FullSaleMod->add($data);
            if($flag){
                foreach($selectArea as $key=>$val){
                    if($val<40){//省份
                        $this->findCity($flag,$val,$val);
                    }else{//城市
                        $this->findCity($flag,$val);
                    }
                }
                $this->success('添加成功！','?m=Sales&a=index');
            }else{
                $this->error('操作失败');
            }
        }else{
            $supplierResult = $suppliersMod->select();
            $suppliers = array();
            foreach($supplierResult as $key=>$val){
                $suppliers[$key]['key'] = $val['suppliers_id'];
                $suppliers[$key]['value'] = $val['suppliers_name'];
            }
            $this->assign('suppliers',$suppliers);
            $map = A('Shipping')->createMap();
            $this->assign('map',$map);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 编辑促销活动
    +----------------------------------------------------------
     */
    function edit() {
        $suppliersMod = M('suppliers');
        $name = I('post.name','','trim,htmlspecialchars');//活动名称
        $money = I('post.money','','float');//满多少钱
        $timePublish = I('post.time_publish','','trim,htmlspecialchars');//发布时间
        $timeEnd = I('post.time_off','','trim,htmlspecialchars');//下架时间
        $templateId = I('request.id',0,'intval');//模板id
        $areaArray = I('post.citys');
        $fullSaleMod = M('free_fee_template');
        $fullSaleAreaMod = M('free_fee_template_area');
        if(isset($_POST['dosubmit'])){
            $data = array();
            $data['template_name'] = $name;
            $data['price'] = $money*100;//以分为单位
            $data['start_time'] = strtotime($timePublish);
            $data['end_time'] = strtotime($timeEnd);
            $data['id'] = $templateId;
            $selectArea = explode(';',$areaArray[0]);
            $m = M();
            $m->startTrans();
            $flag = $fullSaleMod->save($data);
            if($flag!==false){
                $flagDel = $fullSaleAreaMod->where("template_id = $templateId")->delete();
                if($flagDel!==false){
                    foreach($selectArea as $key=>$val){
                        if($val<40){//省份
                            $this->findCity($templateId,$val,$val);
                        }else{//城市
                            $this->findCity($templateId,$val);
                        }
                    }
                    $m->commit();
                    $this->success('修改成功！','?m=Sales&a=index');
                }else{
                    $m->rollback();
                    $this->error('操作失败');
                }
            }else{
                $m->rollback();
                $this->error('操作失败');
            }
        }else{
            $templateResult = $fullSaleMod->find($templateId);
            $templateResult['price'] = $templateResult['price']/100;
            $templateResult['start_time'] = date("Y-m-d H:i",$templateResult['start_time']);
            $templateResult['end_time'] = date("Y-m-d H:i",$templateResult['end_time']);
            $this->assign('fullSale',$templateResult);
            $supplierResult = $suppliersMod->select();
            $suppliers = array();
            foreach($supplierResult as $key=>$val){
                $suppliers[$key]['key'] = $val['suppliers_id'];
                $suppliers[$key]['value'] = $val['suppliers_name'];
            }
            $this->assign('suppliers',$suppliers);
            $this->assign('templateId',$templateId);
            $map = A('Shipping')->createMap();
            $this->assign('map',$map);
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
        $shippingTemplateAreaMod = M('free_fee_template_area');
        $map['template_id'] = $shippingId;
        $cityArray = $shippingTemplateAreaMod->where($map)->field('code')->select();
        $province=$city= array();
        foreach($cityArray as $v){
            if($v['code']<40){//全省
                $province[$v['code']] = 0;
            }else{//部分城市
                $city[$v['code']] = 0;
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
     * 促销活动列表
    +----------------------------------------------------------
     */
    function index() {
        $timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
        $search['time_start'] = $timeStart;
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
        $search['time_end'] = $timeEnd;
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
        $search['keyword'] = $keyword;
        $status = I('get.status','','trim,htmlspecialchars');//状态
        $search['status'] = $status;
        $this->assign('search',$search);
        $where = " 1=1 ";
        if($keyword){
            $where .= " AND template_name LIKE '%" . $keyword . "%'";
        }
        if($timeStart){
            $where .= " AND add_time >= $timeStart ";
        }
        if($timeEnd){
            $where .= " AND add_time <= $timeEnd ";
        }
        if($status){
            $where .= " AND status = '$status'";
        }
        $freeFeeTemplateMod = M('free_fee_template');
        $count = $freeFeeTemplateMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $templateResult = $freeFeeTemplateMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
        foreach($templateResult as $key=>$val){
            switch($val['status']){
                case 'scheduled':
                    $templateResult[$key]['status'] = "定时";
                    break;
                case 'published':
                    $templateResult[$key]['status'] = "发布";
                    break;
                case 'locked':
                    $templateResult[$key]['status'] = "下架";
                    break;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('template',$templateResult);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 根据省份查询城市
    +----------------------------------------------------------
     */

    function findCity ($template,$city,$province=0) {
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
            $this->cityPrice($cityArea,$template,$province);
        }else{
            $this->cityPrice($city,$template);
        }
    }

    /**
    +----------------------------------------------------------
     * 将城市编号和运费插入模板运费表
    +----------------------------------------------------------
     */

    function cityPrice ($city,$template,$province=0) {
        $shippingTemplateAreaMod = M('free_fee_template_area');
        if(is_array($city)){
            foreach($city as $val){
                $data['code'] = $val['code'];
                $data['template_id'] = $template;
                $shippingTemplateAreaMod->add($data);
            }
            if($province){//如果选了全省就把省的编号也存进模板运费
                $data['code'] = $province;
                $data['template_id'] = $template;
                $shippingTemplateAreaMod->add($data);
            }
        }else{
            $data['code'] = $city;
            $data['template_id'] = $template;
            $shippingTemplateAreaMod->add($data);
        }
    }

    /**
    +----------------------------------------------------------
     * 查询时间有没有与现有的优惠活动重叠
    +----------------------------------------------------------
     */
    function checkTime(){
        $start = I('get.start',0,'trim,htmlspecialchars');
        $start = strtotime($start);
        $end = I('get.end',0,'trim,htmlspecialchars');
        $end = strtotime($end);
        $freeFeeTemplateMod = M('free_fee_template');
        $suppliers = I('get.suppliers',0,'intval');
        $where = array();
        $where['suppliers_id'] = $suppliers;
        $where['start_time'] = array('elt',$start);
        $where['end_time'] = array('egt',$end);
        $status = array("scheduled","published");
        $where['status'] = array('in',$status);
        $flag = $freeFeeTemplateMod->where($where)->find();
        if($flag){

        }else{

        }
    }


    /**
    +----------------------------------------------------------
     * 批量发布促销活动，状态变成published,发布时间改成当前时间
    +----------------------------------------------------------
     */

    function allPublish () {
        $templateId = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $freeFeeTemplateMod = M('free_fee_template');
        $scheduledTem = $freeFeeTemplateMod->where("id in ($templateId) AND status = 'scheduled'")->getField('id',true);
        if($scheduledTem){
            $save = array();
            $save['status'] = "published";
            $save['start_time'] = time();
            $where = array();
            $where['id'] = array('in',$scheduledTem);
            $flag = $freeFeeTemplateMod->where($where)->save($save);
            if($flag!==false){
                $this->success("批量上架成功！！！","?m=Sales&a=index");
            }else{
                $this->error("操作失败！！！");
            }
        }else{
            $this->success('没有符合上架条件的促销活动！！！','?m=Sales&a=index');
        }
    }

    /**
    +----------------------------------------------------------
     * 批量下架促销活动，状态变成locked
    +----------------------------------------------------------
     */

    function allCancel () {
        $templateId = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $freeFeeTemplateMod = M('free_fee_template');
        $scheduledTem = $freeFeeTemplateMod->where("id in ($templateId) AND status = 'published'")->getField('id',true);
        if($scheduledTem){
            $save = array();
            $save['status'] = "locked";
            $where = array();
            $where['id'] = array('in',$scheduledTem);
            $flag = $freeFeeTemplateMod->where($where)->save($save);
            if($flag!==false){
                $this->success("批量下架成功！！！","?m=Sales&a=index");
            }else{
                $this->error("操作失败！！！");
            }
        }else{
            $this->success('没有符合下架条件的促销活动！！！','?m=Sales&a=index');
        }
    }

    /**
    +----------------------------------------------------------
     * 发布促销活动，状态变成published,发布时间改成当前时间
    +----------------------------------------------------------
     */

    function publish () {
        $templateId = I('request.id',0,'intval');//促销活动id
        $freeFeeTemplateMod = M('free_fee_template');
        $data['id'] = $templateId;
        $data['status'] = "published";
        $data['start_time'] = time();
        $count = $freeFeeTemplateMod->where('id = '.$templateId." AND status='published'")->count();
        if($count){
            $this->error('促销活动已经发布了，不要重复操作！');
        }else{
            $flag = $freeFeeTemplateMod->save($data);
            if($flag!==false){
                $this->success('促销活动发布成功！',"?m=Sales&a=index");
            }else{
                $this->error('操作失败！');
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 取消发布促销活动，状态变成locked
    +----------------------------------------------------------
     */

    function cancel_pub () {
        $templateId = I('request.id',0,'intval');//促销活动id
        $freeFeeTemplateMod = M('free_fee_template');
        $data['id'] = $templateId;
        $data['status'] = "locked";
        $count = $freeFeeTemplateMod->where('id = '.$templateId." AND status='locked'")->count();
        if($count){
            $this->error('促销活动已经下架了，不要重复操作！');
        }else{
            $flag = $freeFeeTemplateMod->save($data);
            if($flag!==false){
                $this->success('促销活动下架成功！',"?m=Sales&a=index");
            }else{
                $this->error('操作失败！');
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 删除促销活动
    +----------------------------------------------------------
     */
    function del ()
    {
        $templatesIds = I('request.id');//专题id
        $freeFeeTemplateMod = M('free_fee_template');
        $freeFeeTemplateAreaMod = M('free_fee_template_area');
        $where['id'] = array('in', $templatesIds);
        $m = M();
        $m->startTrans();
        $flag = $freeFeeTemplateMod->where($where)->delete();
        if ($flag) {
            $map['template_id'] = array('in', $templatesIds);
            $flag = $freeFeeTemplateAreaMod->where($map)->delete();
            if ($flag !== false) {
                $m->commit();
                $this->success('促销活动删除成功！', "?m=Sales&a=index");
            } else {
                $m->rollback();
                $this->error('操作失败！');
            }
        } else {
            $this->error('操作失败！');
        }
    }

    /**
    +----------------------------------------------------------
     * 新增限购商品，一次可以选择多个商品
    +----------------------------------------------------------
     */
    function addLimit(){
        $spuIds = I('post.addId','','');//商品id数组
        $spuIds = I('post.limit','','');//商品限购数量数组
        if(isset($_POST['dosubmit'])){
            var_dump($_POST);

        }else{
            $_SESSION['limitSpu'] = "";
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
        $where = " status = 0 ";
        $timeStart = I('get.time_start','','trim,htmlspecialchars');//发布时间区间开始
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end','','trim,htmlspecialchars');//发布时间区间结束
        $timeEnd = strtotime($timeEnd);
        $cateId = I('get.cate_id',0,'intval');//分类id
        $supplierId = I('get.supplier',0,'intval');//供应商id
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
            $where .= " AND cid = $cateId ";
            $this->assign('cate_id', $cateId);
        }
        if($supplierId){
            $where .= " AND suppliers_id = $supplierId ";
            $this->assign('select_supplier', $supplierId);
        }
        if($highPrice){
            $where .= " AND price_now <=" . intval($highPrice*100);
            $this->assign('high_price', intval($highPrice));
        }
        if($lowPrice){
            $where .= " AND price_now >=" . intval($lowPrice*100);
            $this->assign('low_price', intval($lowPrice));
        }
        $where .= " AND guide_type = 0";
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
     * 添加、删除限购商品
    +----------------------------------------------------------
     */
    function asyncSpu()
    {
        if (I('post.id', '', '')) {
            $idArray = I('post.id', '', '');
            if ($_SESSION['limitSpu']) {
                $_SESSION['limitSpu'] = "," . $_SESSION['limitSpu'];
            }
            $_SESSION['limitSpu'] = implode(',', $idArray) . $_SESSION['limitSpu'];
        }
        if (I('get.type', '', '')) {
            $ids = $_SESSION['limitSpu'];
            $res = $this->getTable($ids);
            echo $res;
        }
        if(I('get.del','','')&&I('get.spu_id','','intval')){
            $id = I('get.spu_id','','trim');
            $idArr = explode(',',$id);
            $ids = $_SESSION['limitSpu'];
            $idArray = explode(',',$ids);
            foreach($idArray as $k=>$v){
                if(in_array($v,$idArr)){
                    unset($idArray[$k]);
                }
            }
            $ids = implode(',',$idArray);
            $_SESSION['limitSpu'] = $ids;
            $res = $this->getTable($ids);
            echo $res;
        }
    }

    /**
    +----------------------------------------------------------
     * 生成显示的商品表单
    +----------------------------------------------------------
     */
    function getTable ($ids){
        $imgHost = C('LS_IMG_URL');
        $mod = M();
        $sql = "select s.spu_name,s.spu_id,i.images_src from ls_spu s,ls_spu_image i where s.spu_id in ( $ids ) AND s.spu_id = i.spu_id AND i.type = 1 order by find_in_set(s.spu_id, '$ids') ";
        $spuResult = $mod->query($sql);
        //$spuResult = $spuMod->relation(true)->where('spu_id in ( '.$ids.' )')->select();
        foreach($spuResult as $key=>$val){
            //$img = $val['images1'][0]['images_src'];
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
                "<td align='center'><input name='limit[]' size='10' type='text'/></td>
            <td align='center'><input type='button' value='移除' onclick='delSpu(".$val['spu_id'].")' class='removeGuess button'></td>
        </tr>";
            $res .= $str;
        }
        return $res;
    }

    /**
    +----------------------------------------------------------
     * N元任意购
    +----------------------------------------------------------
     */
    function anyBuy(){
        if($_POST['dosubmit']){
            $title = I('post.title','','trim,htmlspecialchars');
            $money = I('post.money','','trim,floatval');
            $num = I('post.num','','trim,intval');
            $virtual_num = I('post.virtual','','trim,intval');
            $start = I('post.time_publish','','trim,htmlspecialchars');
            $end = I('post.time_end','','trim,htmlspecialchars');
            $arbitraryBuyMod = M('arbitrary_buy');
            $spuIds = $_SESSION['chooseSpu'];
            $spuArray = explode(',',$spuIds);
            $focusbanner = A('Focusbanner');
            $post = array();
            $post['title'] = $title;
            $post['time_start'] = $start;
            $post['time_end'] = $end;
            $post['addId'] = $spuArray;
            $post['detail'] = $_SESSION['addAnyDetail'];
            $spuList = $focusbanner->addSpuList($post,"这是一个来自N元任意购直接生成的隐形专题");
            if($spuList>0){
                $data  = array();
                $data['title'] = $title;
                $data['num'] = $num;
                $data['money'] = $money*100;
                $data['add_time'] = time();
                $data['start_time'] = strtotime($start);
                $data['end_time'] = strtotime($end);
                $data['spu_list'] = $spuList;
                $data['virtual_num'] = $virtual_num;
                $flag = $arbitraryBuyMod->add($data);
                if($flag){
                    $this->success("添加成功！");
                }else{
                    $this->success("添加失败！");
                }
                $_SESSION['chooseSpu'] = '';
            }else{
                $this->error("添加失败，原因是商品关系无法建立！");
            }
        }else{
            $_SESSION['chooseSpu'] = '';//每次进入添加页面，清除session记录上次操作的商品id集合
            $_SESSION['addDetail'] = '';
            $_SESSION['addAnyDetail'] = '';
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 修改N元任意购
    +----------------------------------------------------------
     */
    function editAny(){
        $anyMod = M('arbitrary_buy');
        $topicRelation = M('topic_relation');
        if(isset($_POST['dosubmit'])){
            $anyId = I('post.id','','intval');
            $anyResult = $anyMod->find($anyId);
            $title = I('post.title','','trim,htmlspecialchars');
            $money = I('post.money','','trim,floatval');
            $num = I('post.num','','trim,intval');
            $virtual_num = I('post.virtual','','trim,intval');
            $start = I('post.time_publish','','trim,htmlspecialchars');
            $end = I('post.time_end','','trim,htmlspecialchars');
            $arbitraryBuyMod = M('arbitrary_buy');
            $spuIds = $_SESSION['chooseSpu'];
            $spuArray = explode(',',$spuIds);
            $focusbanner = A('Focusbanner');
            $post = array();
            $post['linkid'] = $anyResult['spu_list'];
            $post['addId'] = $spuArray;
            $post['detail'] = $_SESSION['addAnyDetail'];
            $spuList = $focusbanner->editSpuList($post);
            $data  = array();
            $data['id'] = $anyId;
            $data['title'] = $title;
            $data['num'] = $num;
            $data['money'] = $money*100;
            $data['start_time'] = strtotime($start);
            $data['end_time'] = strtotime($end);
            $data['virtual_num'] = $virtual_num;
            $flag = $arbitraryBuyMod->save($data);
            if($flag!==false){
                $this->success("修改成功！","?m=Sales&a=anyList");
            }else{
                $this->success("修改失败！");
            }
            $_SESSION['chooseSpu'] = '';
            $_SESSION['addAnyDetail'] = '';
        }else{
            $_SESSION['chooseSpu'] = '';//每次进入添加页面，清除session记录上次操作的商品id集合
            $_SESSION['addDetail'] = '';
            $anyId = I('get.id','','intval');
            $anyResult = $anyMod->find($anyId);
            $anyResult['money'] = $anyResult['money']/100;
            $this->assign("any",$anyResult);
            $spuList = $anyResult['spu_list'];
            $relationResult = $topicRelation->where("topic_id = $spuList")->field('spu_id,desc,title')->select();
            foreach($relationResult as $key=>$val){
                $spuIds[] = $val['spu_id'];
                $tmp = array(
                    'title'=>$val['title'],
                    'desc'=>$val['desc'],
                );
                $addAnyDetail[$val['spu_id']] = $tmp;
            }
            $_SESSION['chooseSpu'] = implode(',',$spuIds);
            $_SESSION['addAnyDetail'] = $addAnyDetail;
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 添加商品活动描述和活动标题
    +----------------------------------------------------------
     */
    function addDetail () {
        $id = I('request.id',0,'intval');
        $main = I('request.main',0,'intval');
        $this->assign('id',$id);
        $this->assign('type',$main);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * N元任意购列表展示
    +----------------------------------------------------------
     */
    function anyList(){
        $timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
        $search['time_start'] = $timeStart;
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
        $search['time_end'] = $timeEnd;
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
        $search['keyword'] = $keyword;
        $status = I('get.status','','trim,htmlspecialchars');//状态
        $search['status'] = $status;
        $this->assign('search',$search);
        $where = " 1=1 ";
        if($keyword){
            $where .= " AND title LIKE '%" . $keyword . "%'";
        }
        if($timeStart){
            $where .= " AND add_time >= $timeStart ";
        }
        if($timeEnd){
            $where .= " AND add_time <= $timeEnd ";
        }
        if($status){
            $where .= " AND status = '$status'";
        }
        $anyMod = M('arbitrary_buy');
        $count = $anyMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $templateResult = $anyMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
        foreach($templateResult as $key=>$val){
            switch($val['status']){
                case 'scheduled':
                    $templateResult[$key]['status'] = "<font color='green'>定时</font>";
                    break;
                case 'published':
                    $templateResult[$key]['status'] = "<font color='red'>发布</font>";
                    break;
                case 'locked':
                    $templateResult[$key]['status'] = "<font color='blue'>下架</font>";
                    break;
            }
            $templateResult[$key]['money'] = $val['money']/100;
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('template',$templateResult);
        $this->display();
    }


    /**
    +----------------------------------------------------------
     * 发布任意购活动，状态变成published
    +----------------------------------------------------------
     */

    function publish_any () {
        $anyId = I('request.id',0,'intval');//活动id
        $anyMod = M('arbitrary_buy');
        $data['id'] = $anyId;
        $data['status'] = "published";
        //$data['start_time'] = time();
        $count = $anyMod->where('id = '.$anyId." AND status='published'")->count();
        if($count){
            $this->error('活动已经发布了，不要重复操作！');
        }else{
            $flag = $anyMod->save($data);
            if($flag!==false){
                $this->success('活动发布成功！',"?m=Sales&a=anyList");
            }else{
                $this->error('操作失败！');
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 取消发布任意购活动，状态变成locked
    +----------------------------------------------------------
     */

    function cancel_pub_any () {
        $anyId = I('request.id',0,'intval');//促销活动id
        $anyMod = M('arbitrary_buy');
        $data['id'] = $anyId;
        $data['status'] = "locked";
        $count = $anyMod->where('id = '.$anyId." AND status='locked'")->count();
        if($count){
            $this->error('活动已经下架了，不要重复操作！');
        }else{
            $flag = $anyMod->save($data);
            if($flag!==false){
                $this->success('活动下架成功！',"?m=Sales&a=anyList");
            }else{
                $this->error('操作失败！');
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 删除促销活动
    +----------------------------------------------------------
     */
    function del_any ()
    {
        $Ids = I('request.id');//专题id
        $anyMod = M('arbitrary_buy');
        $where['id'] = $Ids;
        $m = M();
        $m->startTrans();
        $flag = $anyMod->where($where)->delete();
        if ($flag) {
            $m->commit();
            $this->success('活动删除成功！', "?m=Sales&a=anyList");
        } else {
            $this->error('操作失败！');
        }
    }


    /**
    +----------------------------------------------------------
     * 批量发布任意购活动，状态变成published
    +----------------------------------------------------------
     */

    function allPublishAny () {
        $templateId = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $freeFeeTemplateMod = M('arbitrary_buy');
        $scheduledTem = $freeFeeTemplateMod->where("id in ($templateId) AND status = 'scheduled'")->getField('id',true);
        if($scheduledTem){
            $save = array();
            $save['status'] = "published";
            //$save['start_time'] = time();
            $where = array();
            $where['id'] = array('in',$scheduledTem);
            $flag = $freeFeeTemplateMod->where($where)->save($save);
            if($flag!==false){
                $this->success("批量上架成功！！！","?m=Sales&a=anyList");
            }else{
                $this->error("操作失败！！！");
            }
        }else{
            $this->success('没有符合上架条件的任意购活动！！！','?m=Sales&a=anyList');
        }
    }

    /**
    +----------------------------------------------------------
     * 批量下架任意购活动，状态变成locked
    +----------------------------------------------------------
     */

    function allCancelAny () {
        $templateId = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $freeFeeTemplateMod = M('arbitrary_buy');
        $scheduledTem = $freeFeeTemplateMod->where("id in ($templateId) AND status = 'published'")->getField('id',true);
        if($scheduledTem){
            $save = array();
            $save['status'] = "locked";
            $where = array();
            $where['id'] = array('in',$scheduledTem);
            $flag = $freeFeeTemplateMod->where($where)->save($save);
            if($flag!==false){
                $this->success("批量下架成功！！！","?m=Sales&a=anyList");
            }else{
                $this->error("操作失败！！！");
            }
        }else{
            $this->success('没有符合下架条件的任意购活动！！！','?m=Sales&a=anyList');
        }
    }

    /**
    +----------------------------------------------------------
     * 设置分享有礼活动
    +----------------------------------------------------------
     */
    function shareGift(){
        $site_mod = M("site_config");
        if(!empty($_POST)){
            $array = $_POST;
            $couponMod = M('coupon');
            $self = $couponMod->find($array['self_share']);
            $array['self_share'] = $self['coupon_num'];
            $friend = $couponMod->find($array['friend_share']);
            $array['friend_share'] = $friend['coupon_num'];
            if($site_mod->where("id = 1")->save($array)!==false){
                $arr = array(
                    "flag" => 1
                );
                die(json_encode($arr));
            }else{
                $arr = array(
                    "flag" => 0
                );
                die(json_encode($arr));
            }
            die;
        }
        $data = $site_mod->field("self_share,friend_share")->find(1);
        $couponMod = M('coupon');
        $self = $couponMod->where("coupon_num = '{$data['self_share']}'")->find();
        $data['self_share'] = $self['id'];
        $friend = $couponMod->where("coupon_num = '{$data['friend_share']}'")->find();
        $data['friend_share'] = $friend['id'];
        $this->assign("data",$data);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 设置主会场
    +----------------------------------------------------------
     */
    function setMain(){
        $_SESSION['chooseSpu'] = '';
        $_SESSION['addAnyDetail'] = '';
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 设置主会场具体模块商品
    +----------------------------------------------------------
     */
    function editMain(){
        $cacheKey = "mainActivity";
        updateCache($cacheKey);//清除主会场缓存
        $position = intval($_GET['position']);
        $topic_relation = M("topic_relation");
        $imgHost = C('LS_IMG_URL');
        $appMod = M("app_config");
        switch($position){
            case 1:
                $title = "口碑爆款";
                break;
            case 2:
                $title = "国民小食";
                break;
            case 3:
                $title = "味蕾旅行";
                break;
            case 4:
                $title = "无肉不欢";
                break;
            case 5:
                $title = "超值精选";
                break;
        }
        if(!$position){
            $title = htmlspecialchars(trim($_POST['title']),ENT_QUOTES);
        }
        if(!($appRes = $appMod->where("config_name = '{$title}'")->find())){
            $data = array(
                "config_name"=>$title,
                "config_value"=>0
            );
            $appMod->add($data);
        }else{
            if(isset($_POST['dosubmit'])){
                if($appRes['config_value']!=0){
                    $spuIds = $_SESSION['chooseSpu'];
                    $spuArray = explode(',',$spuIds);
                    $spuArray = array_unique($spuArray);
                    $focusbanner = A('Focusbanner');
                    $post = array();
                    $post['linkid'] = $appRes['config_value'];
                    $post['addId'] = $spuArray;
                    $post['detail'] = $_SESSION['addAnyDetail'];
                    $spuList = $focusbanner->editSpuList($post);
                    $_SESSION['chooseSpu'] = '';
                    $_SESSION['addAnyDetail'] = '';
                    $this->success("修改成功！！！","?m=Sales&a=setMain");
                }else{
                    $spuIds = $_SESSION['chooseSpu'];
                    $spuArray = explode(',',$spuIds);
                    $spuArray = array_unique($spuArray);
                    $focusbanner = A('Focusbanner');
                    $post = array();
                    $post['title'] = $title;
                    $post['time_start'] = time();
                    $post['time_end'] = time();
                    $post['addId'] = $spuArray;
                    $post['detail'] = $_SESSION['addAnyDetail'];
                    $spuList = $focusbanner->addSpuList($post,"这是一个来自主会场-{$title}-的隐形专题");
                    $data = array(
                        "config_value"=>$spuList
                    );
                    $appMod->where("config_name = '{$title}'")->save($data);
                    $_SESSION['chooseSpu'] = '';
                    $_SESSION['addAnyDetail'] = '';
                    $this->success("添加成功！！！","?m=Sales&a=setMain");
                }
            }else{
                $res = $topic_relation->where("topic_id = {$appRes['config_value']}")->select();
                foreach($res as $key=>$val){
                    $spuIds[] = $val['spu_id'];
                    $detail[$val['spu_id']]['title'] = $val['title'];
                    $detail[$val['spu_id']]['desc'] = $val['desc'];
                    $img = unserialize($val['image_src']);
                    $detail[$val['spu_id']]['img']['images_src'] = $img;
                    $detail[$val['spu_id']]['img']['nowSrc'] = $imgHost.$img['img_url'];
                }
                $_SESSION['chooseSpu'] = implode(',',$spuIds);
                $_SESSION['addAnyDetail'] = $detail;
                $topicAction = A("Topic");
                $res = $topicAction->getTable($_SESSION['chooseSpu'],2);
                $this->assign("insert",$res);
            }
        }
        $this->assign("title",$title);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 新增新用户免邮活动
    +----------------------------------------------------------
     */
    public function addFreeShipping() {
        if (isset($_POST['dosubmit'])) {
            $name = I('post.name','','trim,htmlspecialchars');//活动名称
            $startTime = I('post.time_publish','','trim,htmlspecialchars,strtotime');//开始时间
            $endTime = I('post.time_off','','trim,htmlspecialchars,strtotime');//结束时间
            $shipping = I('post.shipping',1,'intval');//包邮类型 1 商家包邮 2 平台包邮
            $suppliers = I('post.suppliers',0,'intval');//供应商id
            if($shipping==2){
                $suppliers = 0;//供应商id
            }
            $clientFreeShippingMod = M('client_free_shipping');
            $data['name'] = $name;
            $data['create_time'] = time();
            $data['start_time'] = $startTime;
            $data['end_time'] = $endTime;
            if ($data['create_time'] > $data['start_time']) {
                $this->error('开始时间不能早于当前时间！');
            }
            if ($data['create_time'] > $data['end_time']) {
                $this->error('结束时间不能早于当前时间！');
            }
            if ($data['start_time'] >= $data['end_time']) {
                $this->error('开始时间必须小于结束时间！');
            }
            $data['suppliers_id'] = $suppliers;
            $admin_info = session('admin_info');
            $data['admin_id'] = $admin_info['id'];
            $data['is_show'] = 1;
            $flag = $clientFreeShippingMod->add($data);
            //echo $clientFreeShippingMod->getLastSql();
            //exit();
            if ($flag !== false) {
                //Log::$format = '[ Y-m-d H:i:s ]';
                $adminMod = M('admin');
                $adminName = $adminMod->where("id=".$admin_info['id'])->find();
                Log::write("新增用户免邮活动sql：".$clientFreeShippingMod->getLastSql()." 方法：m=Sales&a=addFreeShipping"." 编辑人员：".$adminName['user_name']."参数：name=".$name."&startTime=".$startTime."&endTime=".$endTime."&shipping=".$shipping."suppliers=".$suppliers, Log::INFO);
                $this->success('添加成功！','?m=Sales&a=listFreeShipping');
            } else {
                $this->error('操作失败！');
            }
        } else {
            $suppliersMod = M('suppliers');
            $supplierResult = $suppliersMod->select();
            $suppliers = array();
            foreach($supplierResult as $key=>$val){
                $suppliers[$key]['key'] = $val['suppliers_id'];
                $suppliers[$key]['value'] = $val['suppliers_name'];
            }
            $this->assign('suppliers',$suppliers);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 新用户免邮活动列表
    +----------------------------------------------------------
     */
    public function listFreeShipping() {
        $timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
        $search['time_start'] = $timeStart;
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
        $search['time_end'] = $timeEnd;
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
        $search['keyword'] = $keyword;
        $status = I('get.status','','trim,htmlspecialchars');//状态
        $search['status'] = $status;
        $this->assign('search',$search);
        $where = " 1=1 AND is_show=1";
        if($keyword){
            $where .= " AND name LIKE '%" . $keyword . "%'";
        }
        if($timeStart){
            $where .= " AND start_time >= $timeStart ";
        }
        if($timeEnd){
            $where .= " AND start_time <= $timeEnd ";
        }
        if($status){
            $where .= " AND status = '$status'";
        }
        $clientFreeShippingMod = M('client_free_shipping');
        $count = $clientFreeShippingMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $page = $p->show();
        $info = $clientFreeShippingMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('create_time desc')->select();


        //$clientFreeShippingMod = M('client_free_shipping');
        $adminMod = M('admin');
        //$info = $clientFreeShippingMod->order("create_time desc")->select();
        foreach ($info as $k => $v) {
            //$info[$k]['start_time'] = date("Y-m-d H:i:s", $v['start_time']);
            //$info[$k]['end_time'] = date("Y-m-d H:i:s", $v['end_time']);
            $adminTmp = $adminMod->where("id=".$v['admin_id'])->find();
            $info[$k]['editor'] = $adminTmp['user_name'];
            if ($v['status'] == "published") {
                $info[$k]['status'] = "发布";
            } elseif ($v['status'] == "locked") {
                $info[$k]['status'] = "下架";
            } else {
                $info[$k]['status'] ="定时";
            }
        }
        $this->assign("template", $info);
        $this->assign("page", $page);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 编辑免邮活动
    +----------------------------------------------------------
     */
    public function editFreeShipping() {
        $id = I("get.id");
        if (isset($_POST['dosubmit'])) {
            $id = I('post.id');//活动id
            $name = I('post.name','','trim,htmlspecialchars');//活动名称
            $startTime = I('post.time_publish','','trim,htmlspecialchars,strtotime');//开始时间
            $endTime = I('post.time_off','','trim,htmlspecialchars,strtotime');//结束时间
            $shipping = I('post.shipping',1,'intval');//包邮类型 1 商家包邮 2 平台包邮
            $suppliers = I('post.suppliers',0,'intval');//供应商id
            if($shipping==2){
                $suppliers = 0;//供应商id
            }
            $clientFreeShippingMod = M('client_free_shipping');
            $data['id'] = $id;
            $data['name'] = $name;
            //$data['create_time'] = time();
            $data['start_time'] = $startTime;
            $data['end_time'] = $endTime;
            if ($data['start_time'] >= $data['end_time']) {
                $this->error('开始时间必须小于结束时间！');
            }
            $data['suppliers_id'] = $suppliers;
            $admin_info = session('admin_info');
            $data['admin_id'] = $admin_info['id'];
            $flag = $clientFreeShippingMod->save($data);
            if ($flag !== false) {
                $adminMod = M('admin');
                $adminName = $adminMod->where("id=".$admin_info['id'])->find();
                Log::write("编辑免邮活动sql：".$clientFreeShippingMod->getLastSql()." 方法：m=Sales&a=editFreeShipping"." 编辑人员：".$adminName['user_name']."参数：id=".$id."&name=".$name."&startTime=".$startTime."&endTime=".$endTime."&shipping=".$shipping."suppliers=".$suppliers, Log::INFO);
                $this->success('编辑成功！','?m=Sales&a=listFreeShipping');
            } else {
                $this->error('操作失败！');
            }
        } else {
            $clientFreeShippingMod = M('client_free_shipping');
            $info = $clientFreeShippingMod->where("id=" . $id)->find();

            $suppliersMod = M('suppliers');
            $supplierResult = $suppliersMod->select();
            $suppliers = array();
            foreach ($supplierResult as $key => $val) {
                $suppliers[$key]['key'] = $val['suppliers_id'];
                $suppliers[$key]['value'] = $val['suppliers_name'];
            }
            $this->assign('suppliers', $suppliers);
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 上架免邮活动
    +----------------------------------------------------------
     */
    public function publishFreeShipping() {
        $id = I('request.id',0,'intval');//促销活动id
        $clientFreeShippingMod = M('client_free_shipping');
        $data['id'] = $id;
        $data['status'] = "published";
        $data['start_time'] = time();
        //$count = $clientFreeShippingMod->where('id = '.$id." AND status='published'")->count();
        $clientTmp = $clientFreeShippingMod->where("id=".$id)->find();
        if($clientTmp['status'] == 'published'){
            $this->error('促销活动已经发布了，不要重复操作！');
        }else{
            if (time() < $clientTmp['end_time']) {
                $flag = $clientFreeShippingMod->save($data);
                if ($flag !== false) {
                    $admin_info = session('admin_info');
                    $adminMod = M('admin');
                    $adminName = $adminMod->where("id=" . $admin_info['id'])->find();
                    Log::write("上架免邮活动sql：" . $clientFreeShippingMod->getLastSql() . " 方法：m=Sales&a=publishFreeShipping" . " 编辑人员：" . $adminName['user_name'] . "参数：id=" . $id, Log::INFO);
                    $this->success('促销活动发布成功！', "?m=Sales&a=listFreeShipping");
                } else {
                    $this->error('操作失败！');
                }
            } else {
                $this->error('当前时间已超过活动时间，请到编辑页进行修改！');
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 下架免邮活动
    +----------------------------------------------------------
     */
    public function cancelPubFreeShipping() {
        $id = I('request.id',0,'intval');//促销活动id
        $clientFreeShippingMod = M('client_free_shipping');
        $data['id'] = $id;
        $data['status'] = "locked";
        $count = $clientFreeShippingMod->where('id = '.$id." AND status='locked'")->count();
        if($count){
            $this->error('促销活动已经下架了，不要重复操作！');
        }else{
            $flag = $clientFreeShippingMod->save($data);
            if($flag!==false){
                $admin_info = session('admin_info');
                $adminMod = M('admin');
                $adminName = $adminMod->where("id=".$admin_info['id'])->find();
                Log::write("下架免邮活动sql：".$clientFreeShippingMod->getLastSql()." 方法：m=Sales&a=cancelPubFreeShipping"." 编辑人员：".$adminName['user_name']."参数：id=".$id, Log::INFO);
                $this->success('促销活动下架成功！',"?m=Sales&a=listFreeShipping");
            }else{
                $this->error('操作失败！');
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 删除免邮活动
    +----------------------------------------------------------
     */
    public function delFreeShipping() {
        $id = I('request.id');//专题id
        $clientFreeShippingMod = M('client_free_shipping');
        $data['id'] = $id;
        $data['status'] = "locked";
        $data['is_show'] = 0;
        $flag = $clientFreeShippingMod->save($data);
        if($flag!==false){
            $admin_info = session('admin_info');
            $adminMod = M('admin');
            $adminName = $adminMod->where("id=".$admin_info['id'])->find();
            Log::write("删除免邮活动sql：".$clientFreeShippingMod->getLastSql()." 方法：m=Sales&a=delFreeShipping"." 编辑人员：".$adminName['user_name']."参数：id=".$id, Log::INFO);
            $this->success('促销活动移除成功！',"?m=Sales&a=listFreeShipping");
        }else{
            $this->error('操作失败！');
        }
    }

    /**
    +----------------------------------------------------------
     * 批量上架免邮活动
    +----------------------------------------------------------
     */
    public function allPublishFreeShipping() {
        $ids = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $clientFreeShippingMod = M('client_free_shipping');
        $closeTime = time();
        $scheduledTem = $clientFreeShippingMod->where("id in ($ids) AND status = 'scheduled' AND end_time>".$closeTime)->getField('id',true);
        if($scheduledTem){
            $save = array();
            $save['status'] = "published";
            $save['start_time'] = time();
            $where = array();
            $where['id'] = array('in',$scheduledTem);
            $flag = $clientFreeShippingMod->where($where)->save($save);
            if($flag !== false){
                $admin_info = session('admin_info');
                $adminMod = M('admin');
                $adminName = $adminMod->where("id=".$admin_info['id'])->find();
                Log::write("批量上架免邮活动sql：".$clientFreeShippingMod->getLastSql()." 方法：m=Sales&a=allPublishFreeShipping"." 编辑人员：".$adminName['user_name']."参数：ids=".$ids, Log::INFO);
                $this->success("批量上架成功！！！","?m=Sales&a=listFreeShipping");
            }else{
                $this->error("操作失败！！！");
            }
        }else{
            $this->success('没有符合上架条件的促销活动！！！','?m=Sales&a=listFreeShipping');
        }
    }

    /**
    +----------------------------------------------------------
     * 批量下架免邮活动
    +----------------------------------------------------------
     */
    public function allCancelFreeShipping() {
        $ids = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $clientFreeShippingMod = M('client_free_shipping');
        $scheduledTem = $clientFreeShippingMod->where("id in ($ids) AND status = 'published'")->getField('id',true);
        if($scheduledTem){
            $save = array();
            $save['status'] = "locked";
            $where = array();
            $where['id'] = array('in',$scheduledTem);
            $flag = $clientFreeShippingMod->where($where)->save($save);
            if($flag !== false){
                $admin_info = session('admin_info');
                $adminMod = M('admin');
                $adminName = $adminMod->where("id=".$admin_info['id'])->find();
                Log::write("批量下架免邮活动sql：".$clientFreeShippingMod->getLastSql()." 方法：m=Sales&a=allCancelFreeShipping"." 编辑人员：".$adminName['user_name']."参数：ids=".$ids, Log::INFO);
                $this->success("批量下架成功！！！","?m=Sales&a=listFreeShipping");
            }else{
                $this->error("操作失败！！！");
            }
        }else{
            $this->success('没有符合下架条件的促销活动！！！','?m=Sales&a=listFreeShipping');
        }
    }
}