<?php

class selectgoodsAction extends BaseAction{

    public function query(){
        $query      = $_POST['query'];
        $way        = $_POST['way'];
        $page       = $_POST['pagex'];
        $brandId    = $_POST['goods_brand'];
        $keyword    = $_POST['goods_keyword'];
        $timeStart  = $_POST['goods_time_start'];
        $timeEnd    = $_POST['goods_time_end'];
        $priceStart = $_POST['price_start'];
        $priceEnd   = $_POST['price_end'];
        $type       = $_POST['goods_type'];
        $cateId     = $_POST['cate_id'];

        $relations = M('topic_relation');
        $topic     = M('topic');

        $listRows   = 100;
        $first      = isset($_POST['pagex']) ? $_POST['pagex'] : 0;
        $firstRow   = $first * $listRows;

        $flag = true;
        $info = '';
        $msg = '无错误';

        $where['status'] = '0';   //上架状态

        //排除 5，6和品牌团下所用商品
        $topicWhere['type'] = 2;
        $topicsData= $topic->where($topicWhere)->select();
        foreach($topicsData as $t) {
            $topicIdGounpArr[] = $t['topic_id'];
        }
        if($topicIdGounpArr) {
            array_push($topicIdGounpArr,"5","6");
        } else {
            $topicIdGounpArr = array('5','6');
        }
        $relationWhere['topic_id'] = array('in',$topicIdGounpArr);
        //根据条件查relation, 并查出对应spu_id
        $relationsData = $relations->where($relationWhere)->select();
        foreach($relationsData as $i) {
            $spuIdNotArr[] = $i['spu_id'];
        }
        $spuIdNotArr = array_unique($spuIdNotArr);
        if($spuIdNotArr){
            $where['spu_id'] = array('not in',$spuIdNotArr);
        }
        //品牌筛选
        if(!empty($brandId)) {
            $where['brand_id'] = $brandId;
            $this->assign('brand_id',$brandId);
        }
        if($query) {
            //关键字
            if(!empty($keyword)) {
                $where['spu_name'] = array('like','%'.$keyword.'%');
                $this->assign('keyword',$keyword);
            }
            if(!empty($timeStart)) {
                $where['add_time'] = array('EGT',strtotime($timeStart));
                $this->assign('time_start',$timeStart);
            }
            if(!empty($timeEnd)) {
                $where['add_time'] = array('ELT',strtotime($timeEnd));
                $this->assign('time_end',$timeEnd);
            }
            //开始价格
            if(!empty($priceStart)) {
                $where['price_now'] = array('EGT',$priceStart*100);
                $this->assign('price_start',$priceStart);
            }
            //结束价格
            if(!empty($priceEnd)) {
                $where['price_now'] = array('ELT',$priceEnd*100);
                $this->assign('price_end',$priceEnd);
            }
            //分类
            if($cateId != 0) {
                $where['cid'] = $cateId;
                $this->assign('cate_id',$cateId);
            }
            //商品类型 0:无 1:商城 2:淘客
            if($type != '0') {
                switch($type) {
                    case '1' :
                        $where['guide_type'] = '0';
                        break;
                    case '2' :
                        $where['guide_type'] = array('in','1,2');
                        break;
                }
                $this->assign('type',$type);
            }
            $where['_logic'] = 'AND';
        }
        $spu = M('spu');
        $spuImage = M('spu_image');
        $count = $spu->where($where)->count();
        $spuData = $spu->where($where)->limit($firstRow.','.$listRows)->select();
        //file_put_contents('upload/912.txt', '['.date('Y-m-d h:i',time()).']'.$spu->getLastSql());
        foreach($spuData as $val) {
            $imgData = $spuImage->where('spu_id='.$val['spu_id'].' AND type=1')->find();
            $oldimg  = unserialize($imgData['images_src']);
            $img = C('LS_IMG_URL') . $oldimg['img_url'];
            $info[] = array(
                'id'        => $val['spu_id'],
                'name'      => $val['spu_name'],
                'image'     => $img,
                'transform' => $val['shipping_free'] == 1 ? '包邮' : '不包邮',
                'oldprice'  => $val['price_old']/100,
                'nowprice'  => $val['price_now']/100,
            );
        }
        if(!$info) {
            $flag = false;
            $msg = '暂无满足条件的商品';
        }
        $data = array(
          'flag' => $flag,
          'info' => $info,
          'len'  => $count,
          'msg'  => $msg
        );
        $this->ajaxReturn($data,'json');
    }

    //取sku列表信息
    public function getSku(){
        $spu_id = $_POST['spu_id'];

        $spu = M('spu');
        $spuWhere['spu_id'] = $spu_id;
        $spuData = $spu->where($spuWhere)->find();

        $spupicDB   = M('spu_image');
        $spupicData = $spupicDB->where($spuWhere)->find();
        $usImage    = unserialize($spupicData['images_src']);

        $spucateDB = M('spu_cate');
        $spucateWhere['id'] = $spuData['cid'];
        $spucateData = $spucateDB->where($spucateWhere)->find();

        $sku = M('sku_list');
        $skuWhere['spu_id'] = $spu_id;
        $skuData = $sku->where($skuWhere)->select();

        $isTaoke = 'yes';
        if($spuData['guide_type'] == '0'){
            $isTaoke = 'no';
        }

        foreach($skuData as $val) {
            $attr = M('spu_attr')->where('spu_attr_id='.$val['attr_combination'])->find();
            $skuArr[] = array(
                'id'         => $val['sku_id'],
                'name'       => $attr['attr_value'],
                'price'      => $val['price']/100,
                'sku_sale'   => $val['sku_sale'],
                'sku_stocks' => $val['sku_stocks']
            );
        }
        $info = array(
            'spu_id'    =>  $spuData['spu_id'],
            'title'     =>  $spuData['spu_name'],
            'image_src' =>  C('LS_IMG_URL') . $usImage['img_url'],
            'cate'      =>  $spucateData['name'],
            'istaoke'   =>  $isTaoke,
            'spu_price' =>  $spuData['price_now']/100,
            'spu_stock' =>  $spuData['stocks'],
            'spu_sale'  =>  $spuData['sales'],
            'sku'       =>  $skuArr,
            'len'       =>  count($skuArr)
        );

        $this->ajaxReturn($info);
    }

    public function index(){

        $query      = $_POST['query'];
        $brandId    = $_POST['goods_brand'];
        $keyword    = $_POST['goods_keyword'];
        $timeStart  = $_POST['goods_time_start'];
        $timeEnd    = $_POST['goods_time_end'];
        $priceStart = $_POST['price_start'];
        $priceEnd   = $_POST['price_end'];
        $type       = $_POST['goods_type'];
        $cateId     = $_POST['cate_id'];

        $relations = M('topic_relation');
        $topic     = M('topic');

        $cateMod = D('Spu_cate');
        $brandMod = D('Brand');

        $info = '';

        //上架状态
        $where['status'] = '0';

        //排除 5，6和品牌团下所用商品
        $topicWhere['type'] = 2;
        $topicsData= $topic->where($topicWhere)->select();
        foreach($topicsData as $t) {
            $topicIdGounpArr[] = $t['topic_id'];
        }
        if($topicIdGounpArr) {
            array_push($topicIdGounpArr,"5","6");
        } else {
            $topicIdGounpArr = array('5','6');
        }
        $relationWhere['topic_id'] = array('in',$topicIdGounpArr);
        //根据条件查relation, 并查出对应spu_id
        $relationsData = $relations->where($relationWhere)->select();
        foreach($relationsData as $i) {
            $spuIdNotArr[] = $i['spu_id'];
        }
        $spuIdNotArr = array_unique($spuIdNotArr);
        if($spuIdNotArr){
            $where['spu_id'] = array('not in',$spuIdNotArr);
        }

        if($query) {
            //关键字
            if(!empty($keyword)) {
                $where['spu_name'] = array('like','%'.$keyword.'%');
                $this->assign('keyword',$keyword);
            }
            if(!empty($timeStart)) {
                $where['add_time'] = array('EGT',strtotime($timeStart));
                $this->assign('time_start',$timeStart);
            }
            if(!empty($timeEnd)) {
                $where['add_time'] = array('ELT',strtotime($timeEnd));
                $this->assign('time_end',$timeEnd);
            }
            //开始价格
            if(!empty($priceStart)) {
                $where['price_now'] = array('EGT',$priceStart*100);
                $this->assign('price_start',$priceStart);
            }
            //结束价格
            if(!empty($priceEnd)) {
                $where['price_now'] = array('ELT',$priceEnd*100);
                $this->assign('price_end',$priceEnd);
            }
            //品牌筛选
            if(!empty($brandId)) {
                $where['brand_id'] = $brandId;
                $this->assign('brand_id',$brandId);
            }
            //分类
            if($cateId != 0) {
                $where['cid'] = $cateId;
                $this->assign('cate_id',$cateId);
            }
            //商品类型 0:无 1:商城 2:淘客
            if($type != '0') {
                switch($type) {
                    case '1' :
                        $where['guide_type'] = '0';
                        break;
                    case '2' :
                        $where['guide_type'] = array('in','1,2');
                        break;
                }
                $this->assign('type',$type);
            }
            $where['_logic'] = 'AND';
        }

        $spu = M('spu');
        $spuImage = M('spu_image');

        import("ORG.Util.Page");
        $count = $spu->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $spuData = $spu->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($spuData as $val) {
            $imgData = $spuImage->where('spu_id='.$val['spu_id'])->find();
            $oldimg  = unserialize($imgData['images_src']);
            $img = C('LS_IMG_URL') . $oldimg['img_url'];
            $info[] = array(
                'id'        => $val['spu_id'],
                'name'      => $val['spu_name'],
                'image'     => $img,
                'transform' => $val['shipping_free'] == 1 ? '包邮' : '不包邮',
                'oldprice'  => $val['price_old']/100,
                'nowprice'  => $val['price_now']/100,
            );
        }

        if(!$info) {
            $info = '暂无满足条件的商品';
        }

        $brandResult = $brandMod->get_list();
        $this->assign('brand_list', $brandResult['sort_list']);
        $cates_list = $cateMod->get_list();
        $this->assign('cate_list', $cates_list['sort_list']);
        $this->assign('page',$show);
        $this->assign('list',$info);
        $this->display();
    }

    public function group() {
        $groupModel = M('topic_set');

        $query = $_GET['query'];
        $timeStart = $_GET['time_start'];
        $timeEnd = $_GET['time_end'];
        $keyword = $_GET['keyword'];

        $where['group_id'] = array('NOT IN','1');

        if($query) {    //edit hhf
            if(!empty($timeStart)) {
                $where['add_time'][] = array('egt',strtotime($timeStart));
                $this->assign('time_start', $timeStart);
            }
            if(!empty($timeEnd)) {
                $where['add_time'][] = array('elt',strtotime($timeEnd));
                $this->assign('time_end', $timeEnd);
            }
            if(!empty($keyword)) {
                $where['title'][] = array('like','%'.$keyword.'%');
                $this->assign('keyword', $keyword);
            }
        }
        $where['title'][] = array('notlike','%优惠券列表%');
        $where['_logic'] = 'AND';

        import("ORG.Util.Page");
        $count = $groupModel->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $groupData = $groupModel->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('page', $show);
        $this->assign('group_list',$groupData);
        $this->display();
    }

    public function topic() {

        $topicModel = M('topic');

        $type  = $_GET['type'];
        $query = $_GET['query'];
        $timeStart = $_GET['time_start'];
        $timeEnd = $_GET['time_end'];
        $keyword = $_GET['keyword'];

        //0: 隐形专题 1:正常专题 2:品牌团
        $where['type']     = $type;
        $where['status']   = 'published';
        $where['topic_id'] = array('not in','5,6');
        if(!empty($query)) {
            if(!empty($timeStart)) {
                $where['add_time'] = array('egt',strtotime($timeStart));
                $this->assign('time_start', $timeStart);
            }
            if(!empty($timeEnd)) {
                $where['add_time'] = array('elt',strtotime($timeEnd));
                $this->assign('time_end', $timeEnd);
            }
            if(!empty($keyword)) {
                $where['title'] = array('like','%'.$keyword.'%');
                $this->assign('keyword', $keyword);
            }
        }
        $where['_logic'] = 'AND';

        import("ORG.Util.Page");
        $count = $topicModel->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $groupData = $topicModel->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($groupData as $v) {
            $pic = unserialize($v['image_src']);
            $image = C('LS_IMG_URL') . $pic['img_url'];
            switch($v['status']) {
                case 'published' :
                    $statusText = '已发布';
                    break;
                case 'locked' :
                    $statusText = '已下架';
                    break;
                case 'secheduled' :
                    $statusText = '定时';
                    break;
            }
            $listArr[] = array(
                'topic_id'   => $v['topic_id'],
                'title'      => $v['title'],
                'add_time'   => $v['add_time'],
                'image_src'  => $image,
                'sort'       => $v['sort'],
                'status'     => $v['status'],
                'statusText' => $statusText
            );
        }
        $this->assign('page', $show);
        $this->assign('topic_list',$listArr);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 优惠券详情选择
     * @author:hhf
    +----------------------------------------------------------
     */
    public function coupon(){
        $couponModel = M('coupon');

        $query = $_GET['query'];
        $timeStart = $_GET['time_start'];
        $timeEnd = $_GET['time_end'];
        $keyword = $_GET['keyword'];
        $where['status']   = 'published';
        $where['type']   = 1;
        if(!empty($query)) {
            if(!empty($timeStart)) {
                $add_time[] = array('egt',strtotime($timeStart));
                $this->assign('time_start', $timeStart);
            }
            if(!empty($timeEnd)) {
                $add_time[] = array('elt',strtotime($timeEnd));
                $this->assign('time_end', $timeEnd);
            }
            if(!empty($keyword)) {
                $where['name'] = array('like','%'.$keyword.'%');
                $this->assign('keyword', $keyword);
            }
            $where['add_time'] = $add_time;
        }


        import("ORG.Util.Page");
        $count = $couponModel->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $groupData = $couponModel->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($groupData as $v) {
            switch($v['status']) {
                case 'published' :
                    $statusText = '已发布';
                    break;
                default:
                    $statusText = '不可用';
            }
            $listArr[] = array(
                'coupon_id'   => $v['id'],
                'name'      => $v['name'],
                'add_time'   => $v['add_time'],
                'get_start_time'   => $v['get_start_time'],
                'get_end_time'   => $v['get_end_time'],
                'use_start_time'   => $v['use_start_time'],
                'use_end_time'   => $v['use_end_time'],
                'rule_money'   => $v['rule_money']/100,
                'rule_free'   => $v['rule_free']/100,
                'statusText' => $statusText
            );
        }
        $this->assign('page', $show);
        $this->assign('coupon_list',$listArr);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 优惠券列表选择
     * @author:hhf
    +----------------------------------------------------------
     */
    public function couponList(){
        $couponModel = M('coupon');

        $query = $_GET['query'];
        $timeStart = $_GET['time_start'];
        $timeEnd = $_GET['time_end'];
        $keyword = $_GET['keyword'];
        $where['status']   = 'published';
        $where['type']   = 1;
        if(!empty($query)) {
            if(!empty($timeStart)) {
                $add_time[] = array('egt',strtotime($timeStart));
                $this->assign('time_start', $timeStart);
            }
            if(!empty($timeEnd)) {
                $add_time[] = array('elt',strtotime($timeEnd));
                $this->assign('time_end', $timeEnd);
            }
            if(!empty($keyword)) {
                $where['name'] = array('like','%'.$keyword.'%');
                $this->assign('keyword', $keyword);
            }
            $where['add_time'] = $add_time;
        }


        import("ORG.Util.Page");
        $count = $couponModel->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $groupData = $couponModel->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($groupData as $v) {
            switch($v['status']) {
                case 'published' :
                    $statusText = '已发布';
                    break;
                default:
                    $statusText = '不可用';
            }
            $listArr[] = array(
                'coupon_id'   => $v['id'],
                'name'      => $v['name'],
                'add_time'   => $v['add_time'],
                'get_start_time'   => $v['get_start_time'],
                'get_end_time'   => $v['get_end_time'],
                'use_start_time'   => $v['use_start_time'],
                'use_end_time'   => $v['use_end_time'],
                'rule_money'   => $v['rule_money']/100,
                'rule_free'   => $v['rule_free']/100,
                'statusText' => $statusText
            );
        }
        $this->assign('page', $show);
        $this->assign('coupon_list',$listArr);
        $this->display();
    }
    /**
    +----------------------------------------------------------
     * 已经选择的优惠券
     * @author:hhf
    +----------------------------------------------------------
     */
    public function getTable(){
        $ids = I('get.ids','','trim,htmlspecialchars');
        $ids = explode(',',$ids);
        foreach($ids as $key=>$val){
            if( !$val )
                unset( $ids[$key] );
        }
        $couponModel = M('coupon');
        $ids = implode(',',$ids);
        $couponResult = $couponModel->where("id in ($ids)")->order("field(`id`,$ids)")->select();
        $str = $ids;
        foreach($couponResult as $key=>$val){
            $td = "<td align='center'>{$val['id']}</td>";
            $td .= "<td align='center'>{$val['name']}</td>";
            $td .= "<td align='center'><a href='#' onclick='moveCoupon({$val['id']},1)'><span style='color: #0000ff'>上移</span></a><br/><a href='#' onclick='moveCoupon({$val['id']},0)'><span style='color: #0000ff'>下移</span></a></td>";
            $td .= "<td align='center'><a href='#' onclick='delCoupon({$val['id']})'><span style='color: red'>删除</span></a></td>";
            $tr = "<tr>".$td."</tr>";
            $str .= $tr;
        }
        $return['data'] = $str;
        $return['ids'] = $ids;
        $this->ajaxReturn($return,'json');
    }

    /**
    +----------------------------------------------------------
     * 商品列表
     * @author:hhf
    +----------------------------------------------------------
     */
    public function getSpuTable(){
        $topicId = I('get.ids',0,'intval');
        if($topicId){
            $topicRelationMod = M('topic_relation');
            $spuIds = $topicRelationMod->where("topic_id = {$topicId}")->order('sort asc')->getField('spu_id',true);
            $spuIds = implode(',',$spuIds);
            $topicClass = A('Topic');
            $res = $topicClass->getTable($spuIds);
            $_SESSION['chooseSpu'] = $spuIds;
            echo $res;
        }else{
            echo '';
        }
    }

    /**
    +----------------------------------------------------------
     * 优惠券大礼包礼包
     * @author:hhf
    +----------------------------------------------------------
     */
    function giftList(){
        $timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
        $search['time_start'] = $timeStart;
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
        $search['time_end'] = $timeEnd;
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
        $search['keyword'] = $keyword;
        $this->assign('search',$search);
        $where = " 1 = 1 ";
        $where = " 1=1 ";
        if($keyword){
            $where .= " AND name LIKE '%" . $keyword . "%'";
        }
        if($timeStart){
            $where .= " AND add_time >= $timeStart ";
        }
        if($timeEnd){
            $where .= " AND add_time <= $timeEnd ";
        }
        $couponMod = M('coupon');
        $giftUserMod = M('gift_user');
        $couponGift = M('coupon_gift');
        $count = $couponGift->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $couponResult = $couponGift->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
        foreach($couponResult as $k=>$v){
            $sendNum = $giftUserMod->where("gift_id = {$v['id']}")->count();
            $couponResult[$k]['sendNum'] = $sendNum;
        }
        $this->assign('coupon',$couponResult);
        $page = $p->show();
        $this->assign('page',$page);
        $this->display();
    }
}
?>