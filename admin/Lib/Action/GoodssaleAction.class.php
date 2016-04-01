<?php

class goodssaleAction extends BaseAction{

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

    public function index() {
        $query     = $_GET['query'];
        $keyword   = isset($_GET['keyword'])    ? intval($_GET['keyword'])         : 0;
        $brand     = isset($_GET['brand'])      ? trim($_GET['brand'])           : '';
        $status    = isset($_GET['status'])     ? trim($_GET['status'])          : '';
        $timeStart = $_GET['time_start'];
        $timeEnd   = $_GET['time_end'];

        $where['topic_id'] = '6';
        if($query) {
            if(!empty($timeStart)) {
                $where['add_time'] = array('egt',strtotime($timeStart));
                $this->assign('time_start',$timeStart);
            }
            if(!empty($timeEnd)) {
                $where['add_time'] = array('elt',strtotime($timeEnd));
                $this->assign('time_end',$timeEnd);
            }
            /*
            if($brand) {
                $spuDBEx = M('spu');
                $spuWhereEx['brand_id'] = $brand;
                $spuDataEx = $spuDBEx->where($spuWhereEx)->select();
                foreach($spuDataEx as $ex) {
                    $spuDataExId[] = $ex['spu_id'];
                }
                $where['spu_id'] = array('in',$spuDataExId);
                $this->assign('brand', $brand);
            }
            */
            if($status) {
                $where['status'] = $status;
                $this->assign('status', $status);
            }
            if($keyword) {
//                $spuDBEx = M('spu');
//                $spuWhereEx['name'] = array('like','%'.$keyword.'%');
//                $spuDataEx = $spuDBEx->where($spuWhereEx)->select();
//                foreach($spuDataEx as $ex) {
//                    $spuDataExId[] = $ex['spu_id'];
//                }
//                $where['spu_id'] = array('in',$spuDataExId);
//                $this->assign('keyword', $keyword);
                $where['spu_id'] = $keyword;
                $this->assign('keyword', $keyword);
            }
            $where['_logic'] = 'AND';
        }

        $topic = M('topic_relation');
        $spuImageModel = M('spu_image');
        import("ORG.Util.Page");
        $find_count = $topic->where($where)->select();
        $count = count($find_count);
        $Page = new Page($count,20);
        $show = $Page->show();
        $topicData = $topic->where($where)->group("spu_id")->order('sort DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        $spuDB = M('spu');
        $adminDB = M('admin');
        $brandDB = M('brand');

        for($i=0; $i<count($topicData); $i++) {
            //查商品
            $spuWhere['spu_id'] = $topicData[$i]['spu_id'];
            $spuData = $spuDB->where($spuWhere)->find();
            //admin
            $adminWhere['id'] = $topicData[$i]['admin_id'];
            $adminData = $adminDB->where($adminWhere)->find();
            if($spuData) {
                //查品牌
                $brandWhere['id'] = $spuData['brand_id'];
                $brandData = $brandDB->where($brandWhere)->find();

                $topicData[$i]['title'] = $spuData['spu_name'];
                $topicData[$i]['brand'] = $brandData['name'];
                $topicData[$i]['admin'] = $adminData['user_name'];
                //状态
//                switch($topicData[$i]['status']) {
//                    case 'locked' :
//                        $topicData[$i]['statusText'] = '下架';
//                        break;
//                    case 'published' :
//                        $topicData[$i]['statusText'] = '上架';
//                        break;
//                    case 'scheduled':
//                        $topicData[$i]['statusText'] = '定时上架';
//                        break;
//                }
                //配图
                //$spuImage = $spuImageModel->where('spu_id='.$topicData[$i]['spu_id'])->getField("images_src");
                $usImage = unserialize($topicData[$i]['image_src']);
                $topicData[$i]['image_src'] = C('LS_IMG_URL') . $usImage['img_url'];
            }
        }

        $this->assign('brand_option',$brandData);
        $this->assign('topic_list',$topicData);
        $this->assign('page',$show);
        $this->display();
    }

    public function changeStatus() {
        $topicRelation = M('topic_relation');
        $extendModel = M('extend_special');

        $id = $_POST['id'];
        $status = $_POST['status'];

        $flag = true;
        if(is_array($id)) {
            $where['rid'] = array('in',$id);
        } else {
            $where['rid'] = $id;
        }

        $topicData = $topicRelation->where($where)->select();
        $relationEndTime = $topicRelation->where($where)->getField('end_time');

        foreach($topicData as $tdval) {
            $spuIdArr[] = $tdval['spu_id'];
        }
        $allWhere['spu_id']   = array('in', $spuIdArr);
        $allWhere['topic_id'] = '6';
        $allWhere['_logic']   = 'AND';

        if($status == 'published') {
            $msg  = '上架成功';
            $saveData['status'] = $status;
            $saveData['publish_time'] = time();

            //if($relationEndTime < time()){

            //} else {
                //修改关系表记录
                $relationSave = $topicRelation->where($allWhere)->save($saveData);
                if($relationSave === false) {
                    $flag = false;
                    $msg  = '上架失败';
                }
                //修改extend表记录
                $relationData = $topicRelation->where($allWhere)->select();
                foreach($relationData as $i) {
                    if($i['type'] == '2') {
                        $extendSave['start_time'] = strtotime(date('y-m-d H:i:s',time()));
                        $extendData = $extendModel->where('id='.$i['atom_id'])->save($extendSave);
                        if($extendData === false) {
                            $flag = false;
                            $msg  = '上架失败';
                        }
                    }
                }
            //}
        } else {
            $msg  = '下架成功';
            $saveData['status'] = $status;
            $saveData['end_time'] = strtotime(date('y-m-d H:i:s',time()));
            //修改关系表记录
            $relationSave = $topicRelation->where($allWhere)->save($saveData);
            if($relationSave === false) {
                $flag = false;
                $msg  = '下架失败';
            }
            //修改extend表记录
            $relationData = $topicRelation->where($allWhere)->select();
            foreach($relationData as $i) {
                if($i['type'] == '2') {
                    $extendSave['end_time'] = strtotime(date('y-m-d H:i:s',time()));
                    $extendData = $extendModel->where('id='.$i['atom_id'])->save($extendSave);
                    if($extendData === false) {
                        $flag = false;
                        $msg  = '下架失败';
                    }
                }
            }
        }
        $res = array(
            'flag' => $flag,
            'msg'  => $msg
        );
        $this->ajaxReturn($res,'json');
    }

    public function del(){
        $id = $_POST['did'];
        $topicRelation = M('topic_relation');
        $extend = M('extend_special');
        $flag = true;
        if(is_array($id)) {
            $where['rid'] = array('in',$id);
            $topicData = $topicRelation->where($where)->select();
            foreach($topicData as $tdval) {
                //$spuIdArr[] = $tdval['spu_id'];

                $allWhere['spu_id']   = array('in', $spuIdArr);

                $allWhere['topic_id'] = 6;
                $allWhere['_logic']   = 'AND';

                if($topicData['type'] == '0'){
                    //淘客不做这步操作
                } else {
                    //查出所有关系表
                    $allRelationData = $topicRelation->where($allWhere)->select();
                    foreach($allRelationData as $a) {
                        $extendDel = $extend->where('id='.$a['atom_id'])->delete();
                        if(!$extendDel) {
                            $flag = false;
                        }
                    }
                }

                //删除所有关系表
                $allRelationDelete = $topicRelation->where($allWhere)->delete();
                if(!$allRelationDelete) {
                    $flag = false;
                }
            }
        } else {
            $where['rid'] = $id;
            $topicData = $topicRelation->where($where)->find();

            $allWhere['spu_id']   = $topicData['spu_id'];
            $allWhere['topic_id'] = 6;
            $allWhere['_logic']   = 'AND';

            if($topicData['type'] == '0') {
                //淘客不做这步操作
            } else {
                //查出所有关系表
                $allRelationData = $topicRelation->where($allWhere)->select();
                foreach($allRelationData as $a) {
                    $extendDel = $extend->where('id='.$a['atom_id'])->delete();
                    if(!$extendDel) {
                        $flag = false;
                    }
                }
            }
            //删除所有关系表
            $allRelationDelete = $topicRelation->where($allWhere)->delete();
            if(!$allRelationDelete) {
                $flag = false;
            }
        }
        $res['flag'] = $flag;
        $this->ajaxReturn($res,'json');
        //清除缓存
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis', $options);
        $expire = 60;
        $set_key = C("LS_SPECIAL_SALE_LIST").$id;// 缓存建名
        $redis->rm($set_key);
    }

    public function add() {
        if (isset($_POST['dosubmit'])) {
            $extend   = M('extend_special');
            $relation = M('topic_relation');
            $topic    = M('topic');
            $spuImageModel = M('spu_image');
            $skuModel = M('sku_list');

            $info     = $_POST['info'];
            $admin_id = $_SESSION['admin_info']['id'];

            $flag = true;
            $message = "添加成功";

            if($info) {
                foreach($info as $v) {
                    $spu          = $v['id'];
                    $publish_time = strtotime($v['time_start']);
                    $end_time     = strtotime($v['time_end']);
                    $sort         = $v['sort'];
                    $type         = $v['type'];
                    $add_time     = strtotime(date('y-m-d H:i:s',time()));
                    /*
                    $image        = substr($v['image_src'],1);
                    $image_src    = $image ? up_yun($image) : '';
                    $new_image_src = array(
                        'img_url' => $image_src['img_src'],
                        'img_w'   => $image_src['img_w'],
                        'img_h'   => $image_src['img_h'],
                        'img_type'=> $image_src['img_type']
                    );
                    */
                    //获取spu图片
                    $new_image_src = $spuImageModel->where('spu_id='.$spu.' AND type=1')->getField("images_src");

                    //接收淘客标识
                    $taoke    = $v['taoke'];

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
                    $relationWhere['spu_id']   = $spu;

                    //根据条件查relation, 并查出对应spu_id
                    $replace = $relation->where($relationWhere)->select();
                    //spu去重
                    if(!$replace) {
                        foreach($v['sku'] as $n) {
                            //非淘客
                            if($taoke == 'no') {
                                //限量活动扣库存
                                if($n['type'] == '2') {
                                    $skuWhere['sku_id'] = $n['skuid'];
                                    $skuWhere['sku_stocks'] = array('EGT',$n['stock']);
                                    $stockCut = $skuModel->where($skuWhere)->setDec('sku_stocks',$n['stock']);
                                    if ($stockCut === false) {
                                        $flag = false;
                                        $message = "该商品库存不足";
                                    }
                                }
                                if($flag) {
                                    //扩展表
                                    $extendData = array(
                                        'sku_id' => $n['skuid'],
                                        'start_time' => $publish_time,
                                        'end_time' => $end_time,
                                        'price' => $n['price'] * 100,
                                        'sku_stocks' => $n['stock'],
                                        'sku_sale' => $n['sale'],
                                        'type' => $n['type']
                                    );
                                    $extendSave = $extend->add($extendData);
                                    if ($extendSave === false) {
                                        $flag = false;
                                        $message = '扩展信息添加失败';
                                    }
                                }
                            }
                            //是淘客
                            else if($taoke == 'yes') {
                                $sku = 0;
                                $extendSave = $n['type'];
                                $type = 0;
                            }
                            if($flag) {
                                //关系表
                                $relationData = array(
                                    'topic_id' => '6',
                                    'atom_id' => $extendSave,
                                    'spu_id' => $spu,
                                    'image_src' => $new_image_src,
                                    'sort' => $sort,
                                    'type' => $type,
                                    'add_time' => $add_time,
                                    'update_time' => $add_time,
                                    'publish_time' => $publish_time,
                                    'end_time' => $end_time,
                                    'status' => 'scheduled',
                                    'admin_id' => $admin_id
                                );
                                $relationAdd = $relation->add($relationData);
                                if ($relationAdd === false) {
                                    $flag = false;
                                    $message = "关系信息添加失败";
                                }
                            }
                        }
                    } else {
                        $flag = false;
                        $message = "该商品已参加其他活动";
                    }
                }
            }
            //输出
            $res['flag'] = $flag;
            $res['msg']  = $message;
            $this->ajaxReturn($res,'json');
        } else {
            $cateMod = D('Spu_cate');
            $brandMod = D('Brand');
            $brandResult = $brandMod->get_list();
            $this->assign('brand_list', $brandResult['sort_list']);
            $cates_list = $cateMod->get_list();
            $this->assign('cate_list', $cates_list['sort_list']);
            $timestamp = time();
            $token = md5('unique_salt' . $timestamp);
            $this->assign('timestamp',$timestamp);
            $this->assign('token',$token);
            $this->display();
        }
    }

    public function edit() {
        $rid = $_GET['id'];

        $relation = M('topic_relation');
        $extend   = M('extend_special');
        $sku      = M('sku_list');
        $spuAttr  = M('spu_attr');
        //获得spu_id
        $keyWhere['rid']    = $rid;
        $keyWhere['_logic'] = 'AND';
        $keyRelation = $relation->where($keyWhere)->find();
        $keySpuId = $keyRelation['spu_id'];
        //根据spu_id获取其他relation
        $allWhere['spu_id']   = $keySpuId;
        $allWhere['topic_id'] = 6;
        $allWhere['_logic']   = 'AND';
        $allRelationData = $relation->where($allWhere)->select();

        $spu = M('spu');
        $spuWhere['spu_id'] = $keySpuId;
        $spuData = $spu->where($spuWhere)->find();
        //图
        $spupicDB   = M('spu_image');
        $spupicData = $spupicDB->where($spuWhere)->find();
        $usImage    = unserialize($spupicData['images_src']);
        //分类
        $spucateDB = M('spu_cate');
        $spucateWhere['id'] = $spuData['cid'];
        $spucateData = $spucateDB->where($spucateWhere)->find();
        //获取淘客标识
        $istaoke = $spuData['guide_type'] == '0' ? 'no' : 'yes';

        foreach($allRelationData as $val) {
            if($istaoke == 'no') {
                //查扩展表
                $extendData = $extend->where('id='.$val['atom_id'])->find();
                //sku名
                $skuData = $sku->where('sku_id='.$extendData['sku_id'])->find();
                $attrData = $spuAttr->where('spu_attr_id='.$skuData['attr_combination'])->find();
                $extendArr[] = array(
                    'id'       => $extendData['sku_id'],
                    'sku'      => $attrData['attr_value'],
                    'price'    => $skuData['price']/100,
                    'sale'     => $extendData['sku_sale'],
                    'stock'    => $skuData['sku_stocks'],
                    'ex_price' => $extendData['price']/100,
                    'ex_stock' => $extendData['sku_stocks'],
                    'type'     => $extendData['type'],
                    'taoke'    => $istaoke
                );
            } else {
                $taokeWhere['spu_id'] = $val['spu_id'];
                //淘客商品,直接用spu '价格' '库存' '销量' 输出
                $spuDataForTaoke = $spu->where($taokeWhere)->find();
                $extendArr[] = array(
                    'id'       => '0',
                    'sku'      => '无',
                    'price'    => $spuDataForTaoke['price_now']/100,
                    'sale'     => $spuDataForTaoke['sales'],
                    'stock'    => $spuDataForTaoke['stocks'],
                    'ex_price' => $spuDataForTaoke['price_now']/100,
                    'ex_stock' => $spuDataForTaoke['stocks'],
                    'type'     => $val['atom_id'],
                    'taoke'    => $istaoke
                );
            }
        }

        $img = unserialize($keyRelation['image_src']);
        $finialImg = C('LS_IMG_URL').$img['img_url'];
        $data = array(
            'rid'        => $rid,
            'spu_id'     => $keyRelation['spu_id'],
            'sort'       => $keyRelation['sort'],
            'time_start' => date('y-m-d H:i:s',$keyRelation['publish_time']),
            'end_start'  => date('y-m-d H:i:s',$keyRelation['end_time']),
            'image_src'  => $finialImg,
            'spu_title'  => $spuData['spu_name'],
            'spu_img'    => C('LS_IMG_URL') . $usImage['img_url'],
            'spu_cate'   => $spucateData['name'],
            'spu_len'    => count($allRelationData)
        );
        $timestamp = time();
        $token = md5('unique_salt' . $timestamp);
        $this->assign('timestamp',$timestamp);
        $this->assign('token',$token);
        $this->assign('data',$data);
        $this->assign('exlist',$extendArr);
        $this->display();
    }

    public function save() {
        $spu          = $_POST['spu'];
        $publish_time = strtotime($_POST['time_start']);
        $end_time     = strtotime($_POST['time_end']);
        $sort         = $_POST['sort'];
        $update_time  = time();
        $status       = $publish_time <= time() ? 'published' : 'scheduled';

        //接收淘客标识
        $taoke    = $_POST['taoke'];
        /*
        if(!empty($_POST['image_src'])) {
            $image        = substr($_POST['image_src'],1);
            $image_src    = $image ? up_yun($image) : '';
            $new_image_src = array(
                'img_url' => $image_src['img_src'],
                'img_w'   => $image_src['img_w'],
                'img_h'   => $image_src['img_h']
            );
        } else {
            $new_image_src = null;
        }
        */
        $admin_id   = $_SESSION['admin_info']['id'];
        $collection = $_POST['collection'];

        $relation = M('topic_relation');
        $extend   = M('extend_special');

        $flag = true;
        //关联的relation
        $allWhere['spu_id']   = $spu;
        $allWhere['topic_id'] = 6;
        $allWhere['_logic']   = 'AND';
        $allRelationData = $relation->where($allWhere)->select();
        //保存关系表
        foreach($allRelationData as $aval) {
            //通用设置
            $relationData = array(
                'sort'         => $sort,
                'update_time'  => $update_time,
                'publish_time' => $publish_time,
                'end_time'     => $end_time,
                'admin_id'     => $admin_id,
                'status'       => $status
            );
            /*
            if($new_image_src){
                $relationData['image_src'] = serialize($new_image_src);
            }
            */
            //遍历，保存通用设置
            $relationSave = $relation->where("rid=".$aval['rid'])->save($relationData);
            if($relationSave === false) {
                $flag = false;
            }
        }
        //保存extend扩展
        foreach($collection as $cval) {
            if($cval['taoke'] == 'yes') {
                //淘客商品不存Extend
            } else {
                if ($cval['type'] == '1') {
                    $csave['price'] = $cval['price'] * 100;
                } else if ($cval['type'] == '2') {
                    $csave['price'] = $cval['price'] * 100;
                    $csave['sku_stocks'] = $cval['stock'];
                }
                $csave['start_time'] = $publish_time;
                $csave['end_time'] = $end_time;
                $exData = $extend->where('sku_id=' . $cval['id'])->save($csave);
                if ($exData === false) {
                    $flag = false;
                }
            }
        }

        $res['flag'] = $flag;
        $res['msg']  = $flag ? '保存成功' : '保存失败';
        $this->ajaxReturn($res,'json');
    }

    /**
    +----------------------------------------------------------
     * 上移下移专题位置 type=1上移  type=3下移
    +----------------------------------------------------------
     */
    function  upDownTopic (){
        $type = I('get.type',0,'intval');
        $rId = I('get.id',0,'intval');
        $topicRelationMod = M('topic_relation');
        $topicResult = $topicRelationMod->find($rId);
        if($type&$topicResult){
            if($type==1){
                $upTopic = $topicRelationMod->where('sort >'.$topicResult['sort'].' AND topic_id = 6')->order('sort asc')->find();
                if(empty($upTopic)){
                    echo 0;exit;
                }
                $tmp = $topicResult['sort'];
                $data = array();
                $data['rid'] = $upTopic['rid'];
                $data['sort'] = $tmp;
                $falg1 = $topicRelationMod->save($data);
                $tmp = $upTopic['sort'];
                $data = array();
                $data['rid'] = $topicResult['rid'];
                $data['sort'] = $tmp;
                $flag2 = $topicRelationMod->save($data);
                echo 1;
            }elseif($type==3){
                $downTopic = $topicRelationMod->where('sort <'.$topicResult['sort'].' AND topic_id = 6')->order('sort desc')->find();
                if(empty($downTopic)){
                    echo 0;exit;
                }
                $tmp = $topicResult['sort'];
                $data = array();
                $data['rid'] = $downTopic['rid'];
                $data['sort'] = $tmp;
                $falg1 = $topicRelationMod->save($data);
                $tmp = $downTopic['sort'];
                $data = array();
                $data['rid'] = $topicResult['rid'];
                $data['sort'] = $tmp;
                $flag2 = $topicRelationMod->save($data);
                echo 1;
            }
            echo $type;
        }else{
            echo 0;
        }
    }
}
?>