<?php

class everydaynewsAction extends BaseAction{

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

        $spuModel = M('spu');

        $where['topic_id'] = '5';
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
            //编号
            if($keyword) {
                $where['spu_id'] = $keyword;
                $this->assign('keyword', $keyword);
            }
            $where['_logic'] = 'AND';
        }

        $topicRelationModel = M('topic_relation');
        $spuModel = M('spu');
        $adminModel = M('admin');
        $brandModel = M('brand');

        import("ORG.Util.Page");
        $find_count = $topicRelationModel->where($where)->select();
        $count = count($find_count);
        $Page = new Page($count,20);
        $show = $Page->show();
        $topicData = $topicRelationModel->where($where)->group("spu_id")->order('sort DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        if($topicData) {
            foreach($topicData as $v) {
                $spuData = $spuModel->where('spu_id='.$v['spu_id'])->find();
                if($spuData) {
                    $spuId = $spuData['spu_id'];
                    $title = $spuData['spu_name'];
                    $brandData = $brandModel->where('id='.$spuData['brand_id'])->find();
                    $brand = $brandData['name'];
                    $admin = $adminModel->where('id='.$v['admin_id'])->getField('user_name');
                    $upImage = unserialize($v['image_src']);
                    $newImage = C('LS_IMG_URL') . $upImage['img_url'];
                    $status = $v['status'];
                    switch($status) {
                        case 'locked' :
                            $statusText = '已下架';
                            break;
                        case 'published' :
                            $statusText = '已发布';
                            break;
                        case 'scheduled':
                            $statusText = '定时';
                            break;
                    }
                    $listArr[] = array(
                        'rid'          => $v['rid'],
                        'spu_id'       => $spuId,
                        'title'        => $title,
                        'image_src'    => $newImage,
                        'publish_time' => date('y-m-d H:i:s',$v['publish_time']),
                        'end_time'     => date('y-m-d H:i:s',$v['end_time']),
                        'brand'        => $brand,
                        'sort'         => $v['sort'],
                        'status'       => $status,
                        'statusText'   => $statusText,
                        'update_time'  => date('y-m-d H:i:s',$v['update_time']),
                        'admin'        => $admin
                    );
                }
            }
        }
        $this->assign('brand_option',$brandData);
        $this->assign('topic_list',$listArr);
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
        foreach($topicData as $tdval) {
            $spuIdArr[] = $tdval['spu_id'];
        }
        $allWhere['spu_id']   = array('in', $spuIdArr);
        $allWhere['topic_id'] = '5';
        $allWhere['_logic']   = 'AND';

        if($status == 'published') {
            $msg  = '上架成功';
            $saveData['status'] = $status;
            $saveData['publish_time'] = strtotime(date('y-m-d H:i:s',time()));
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
                //$allWhere['spu_id']   = array('in', $spuIdArr);
                $allWhere['spu_id']   = $tdval['spu_id'];
                $allWhere['topic_id'] = '5';
                $allWhere['_logic']   = 'AND';

                if($tdval['type'] == '0'){
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
            $allWhere['topic_id'] = 5;
            $allWhere['_logic']   = 'AND';
            if($topicData['type'] == '0'){
                //淘客不做这步操作
            } else {
                //查出所有关系表
                $allRelationData = $topicRelation->where($allWhere)->select();
                foreach ($allRelationData as $a) {
                    $extendDel = $extend->where('id=' . $a['atom_id'])->delete();
                    if (!$extendDel) {
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
    }

    public function add() {
        $extend   = M('extend_special');
        $relation = M('topic_relation');
        $topic    = M('topic');
        $skuModel = M('sku_list');
        if (isset($_POST['dosubmit'])) {
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
                    $image        = substr($v['image_src'],1);
                    $image_src    = $image ? up_yun($image) : '';
                    $new_image_src = array(
                        'img_url' => $image_src['img_src'],
                        'img_w'   => $image_src['img_w'],
                        'img_h'   => $image_src['img_h'],
                        //'img_type'=> $image_src['img_type']
                    );
                    $taoke = $v['taoke'];

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

                    $relation->startTrans();
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
                                        $relation->rollback();
                                    }
                                }
                                //类型!=无活动
                                if($flag && $type=='2') {
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
                                        $message = "扩展表添加失败";
                                        $relation->rollback();
                                    }
                                } else {
                                    //无活动，atom_id 就用来放 sku_id
                                    $extendSave = $n['skuid'];
                                    $type = 1;
                                }
                            }
                            //是淘客
                            else if($taoke == 'yes') {
                                $sku = 0;
                                //atom_id 作为淘客商品活动类型的区分 0:无活动 1:限时 2:限时限量
                                $extendSave = $n['type'];
                                $type = 0;
                            }
                            if($flag) {
                                //关系表
                                $relationData = array(
                                    'topic_id' => '5',
                                    'atom_id' => $extendSave,
                                    'spu_id' => $spu,
                                    'image_src' => serialize($new_image_src),
                                    'sort' => time(),
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
                                    $message = "关系表添加失败";
                                    $relation->rollback();
                                }
                            }
                        }
                    } else {
                        $flag = false;
                        $message = "该商品已加入其他活动";
                        $relation->rollback();
                    }
                    $relation->commit();
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

        $relationMod = M('topic_relation');
        $extendMod = M('extend_special');
        $skuMod = M('sku_list');
        $spuAttrMod = M('spu_attr');
        $spuMod = M('spu');
        $spupicMod = M('spu_image');
        $spucateMod = M('spu_cate');

        $keyWhere['rid'] = $rid;
        $keyWhere['_logic'] = 'AND';
        $keyRelation = $relationMod->where($keyWhere)->find();
        $keySpuId = $keyRelation['spu_id'];
        //根据spu_id获取其他relation
        $allWhere['spu_id'] = $keySpuId;
        $allWhere['topic_id'] = '5';
        $allWhere['_logic'] = 'AND';
        $allRelationData = $relationMod->where($allWhere)->select();

        $spuWhere['spu_id'] = $keySpuId;
        $spuData = $spuMod->where($spuWhere)->find();

        $spupicData = $spupicMod->where($spuWhere)->find();
        $usImage    = unserialize($spupicData['images_src']);

        $spucateWhere['id'] = $spuData['cid'];
        $spucateData = $spucateMod->where($spucateWhere)->find();
        $istaoke = $spuData['guide_type'] == '0' ? 'no' : 'yes';
        
        foreach($allRelationData as $val) {
            if($istaoke == 'no') {
                if($val['type'] == '2') {
                    $extendData = $extendMod->where('id='.$val['atom_id'])->find();
                    $skuData = $skuMod->where('sku_id='.$extendData['sku_id'])->find();
                    $attrData = $spuAttrMod->where('spu_attr_id='.$skuData['attr_combination'])->find();
                    $extendArr[] = array(
                        'id' => $extendData['sku_id'],
                        'sku' => $attrData['attr_value'],
                        'price' => $skuData['price'] / 100,
                        'sale' => $extendData['sku_sale'],
                        'stock' => $skuData['sku_stocks'],
                        'ex_price' => $extendData['price'] / 100,
                        'ex_stock' => $extendData['sku_stocks'],
                        'type' => $extendData['type'],
                        'taoke' => $istaoke
                    );
                } else if($val['type'] == '1') {
                    $skuData = $skuMod->where('sku_id='.$val['atom_id'])->find();
                    $attrData = $spuAttrMod->where('spu_attr_id='.$skuData['attr_combination'])->find();
                    $extendArr[] = array(
                        'id'       => $val['atom_id'],
                        'sku'      => $attrData['attr_value'],
                        'price'    => $skuData['price'] / 100,
                        'sale'     => $skuData['sku_sale'],
                        'stock'    => $skuData['sku_stocks'],
                        'ex_price' => $skuData['price'] / 100,
                        'ex_stock' => $skuData['sku_stocks'],
                        'type'     => 0,
                        'taoke'    => $istaoke
                    );
                }
            } else {
                $taokeWhere['spu_id'] = $spuData['spu_id'];
                //淘客商品,直接用spu '价格' '库存' '销量' 输出
                $spuDataForTaoke = $spuMod->where($taokeWhere)->find();
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
        $relationMod = M('topic_relation');
        $extendMod   = M('extend_special');

        $flag = true;
        $rid = $_POST['rid'];
        $spu = $_POST['spu'];
        $publish_time = strtotime($_POST['time_start']);
        $end_time = strtotime($_POST['time_end']);
        $sort = $_POST['sort'];
        $status = $publish_time <= time() ? 'published' : 'scheduled';
        if(!empty($_POST['image_src'])) {
            $image = substr($_POST['image_src'],1);
            $image_src = $image ? up_yun($image) : '';
            $new_image_src = array(
                'img_url' => $image_src['img_src'],
                'img_w'   => $image_src['img_w'],
                'img_h'   => $image_src['img_h']
            );
        } else {
            $new_image_src = null;
        }
        $admin_id = $_SESSION['admin_info']['id'];
        $collection = $_POST['collection'];

        //关联的relation
        $allWhere['spu_id'] = $spu;
        $allWhere['topic_id'] = 5;
        $allWhere['_logic'] = 'AND';
        $allRelationData = $relationMod->where($allWhere)->select();
        foreach($allRelationData as $aval) {
            //通用设置
            $relationData = array(
                'sort'         => $sort,
                'update_time'  => time(),
                'publish_time' => $publish_time,
                'end_time'     => $end_time,
                'admin_id'     => $admin_id,
                'status'       => $status
            );
            if($new_image_src){
                $relationData['image_src'] = serialize($new_image_src);
            }
            //遍历，保存通用设置
            $relationSave = $relationMod->where("rid=".$aval['rid'])->save($relationData);
            if($relationSave === false) {
                $flag = false;
            }
        }
        //保存 extend 信息
        foreach ($collection as $cval) {
            //非淘客商品，更新扩展表时间
            if($cval['taoke'] == 'no') {
                $csave['start_time'] = $publish_time;
                $csave['end_time'] = $end_time;
                $exData = $extendMod->where('sku_id=' . $cval['id'])->save($csave);
                if ($exData === false) {
                    $flag = false;
                    Log::write('扩展表保存出错：' . $extendMod->getLastSql() , Log::ERR);
                }
            }
        }
        $res['flag'] = $flag;
        $res['msg']  = $flag ? '保存成功' : '保存失败';
        $this->ajaxReturn($res,'json');
    }
    public function addGroup(){
        $topicModel = M('topic');
        $save = $_POST['save'];
        $where['topic_id'] = '5';

        if($save) {
            $title = $_POST['title'];
            $desc  = $_POST['desc'];
            $flag  = true;
            !empty($title) && $data['title'] = $title;
            !empty($desc) && $data['desc'] = $desc;
            $topicSetSave = $topicModel->where($where)->save($data);
            if($topicSetSave === false) {
                $flag = false;
            }
            $res['flag'] = $flag;
            $this->ajaxReturn($res,'json');
        } else {
            $topicSetData = $topicModel->where($where)->find();
            $this->assign('data',$topicSetData);
            $this->display();
        }
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
                $upTopic = $topicRelationMod->where('sort >'.$topicResult['sort'].' AND topic_id = 5')->order('sort asc')->find();
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
                $downTopic = $topicRelationMod->where('sort <'.$topicResult['sort'].' AND topic_id = 5')->order('sort desc')->find();
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