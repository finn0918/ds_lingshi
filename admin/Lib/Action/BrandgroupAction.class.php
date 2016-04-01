<?php

class brandgroupAction extends BaseAction{

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
        $keyword   = isset($_GET['keyword'])    ? trim($_GET['keyword'])         : '';
        $brand     = isset($_GET['brand'])      ? trim($_GET['brand'])           : '';
        $status    = isset($_GET['status'])     ? trim($_GET['status'])          : '';
        $timeStart = $_GET['time_start'];
        $timeEnd   = $_GET['time_end'];

        $topicSet      = M('topic_set');
        $groupTopic    = M('group_topic');
        $topic         = M('topic');
        $topicRelation = M('top_relation');
        $extendSpecial = M('extend_special');
        $spu           = M('spu');
        $spuImage      = M('spu_image');
        $spuAttr       = M('spu_attr');
        $spuCate       = M('spu_cate');
        $sku           = M('sku_list');
        $brandDB       = M('brand');
        $adminDB = M('admin');

        $where['type'] = '2';

        if($query) {
            if(!empty($timeStart)) {
                $where['add_time'] = array('egt',strtotime($timeStart));
                $this->assign('time_start',$timeStart);
            }
            if(!empty($timeEnd)) {
                $where['add_time'] = array('elt',strtotime($timeEnd));
                $this->assign('time_end',$timeEnd);
            }
            if($status) {
                $where['status'] = $status;
                $this->assign('status', $status);
            }
            if($keyword) {
                $where['title'] = array('like','%'.$keyword.'%');
                $this->assign('keyword', $keyword);
            }
            $where['_logic'] = 'AND';
        }

        import("ORG.Util.Page");
        $count = $topic->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $topicData = $topic->where($where)->order('sort DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($topicData as $v) {
            $oldImage  = unserialize($v['image_src']);
            $realImage = C('LS_IMG_URL') . $oldImage['img_url'];
            //状态
            switch($v['status']) {
                case 'locked' :
                    $statusText = '已下架';
                    break;
                case 'published' :
                    $statusText = '发布中';
                    break;
                case 'scheduled':
                    $statusText = '定时';
                    break;
            }
            //brand
            $brandWhere['id'] = $v['brand_id'];
            $brandData = $brandDB->where($brandWhere)->find();
            //admin
            $adminWhere['id'] = $v['admin_id'];
            $adminData = $adminDB->where($adminWhere)->find();

            $resData[] = array(
                'topic_id'     => $v['topic_id'],
                'title'        => $v['title'],
                'image_src'    => $realImage,
                'brand'        => $brandData['name'],
                'publish_time' => $v['publish_time'],
                'end_time'     => $v['end_time'],
                'status'       => $v['status'],
                'statusText'   => $statusText,
                'sort'         => $v['sort'],
                'admin'        => $adminData['user_name']
            );
        }

        $this->assign('topic_list',$resData);
        $this->assign('page',$show);
        $this->display();
    }

    public function changeStatus() {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $flag = true;

        $topic         = M('topic');
        $topicRelation = M('topic_relation');
        $extend        = M('extend_special');

        $saveData['status'] = $status;

        if($status == 'published') {
            //上架
            $saveData['publish_time'] = time();
            $extendSave['start_time'] = time();
        } else {
            //下架
            $saveData['end_time'] = time();
            $extendSave['end_time'] = time();
        }

        //组装查询条件
        if(is_array($id)) {
            $where['topic_id'] = array('in',$id);
        } else {
            $where['topic_id'] = $id;
        }
        //修改topic 和 topic关系表
        $topic->where($where)->save($saveData);
        $topicRelation->where($where)->save($saveData);
        //修改拓展表开始时间
        $topRelationData = $topicRelation->where($where)->select();
        foreach($topRelationData as $v) {
            if($v['type'] == '2') {
                $atomIdArr[] = $v['atom_id'];
            }
        }
        $extendWhere['id'] = array('in',$atomIdArr);
        $extend->where($extendWhere)->save($extendSave);
        $res['flag'] = $flag;
        $this->ajaxReturn($res,'json');
    }
    //删除
    public function del(){
        $topicId = $_POST['did'];

        $groupTopic    = M('group_topic');
        $topic         = M('topic');
        $topicRelation = M('topic_relation');
        $extend        = M('extend_special');

        $flag = true;

        $where['topic_id'] = $topicId;
        //删合集关系
        $groupDel = $groupTopic->where($where)->delete();
        if($groupDel === false) {
            $flag = false;
        }
        //删合集
        $topicDel = $topic->where($where)->delete();
        if($topicDel === false) {
            $flag = false;
        }
        //合集下所有关系
        $topicData = $topicRelation->where($where)->select();
        foreach($topicData as $v) {
            $atomIdArr[] = $v['atom_id'];
        }
        //删关联
        $topicRelationDel = $topicRelation->where($where)->delete();
        if($topicRelationDel === false) {
            $flag = false;
        }
        //删扩展
        $extendWhere['id'] = array('in',$atomIdArr);
        $extendDel = $extend->where($extendWhere)->delete();
        if($extendDel === false) {
            $flag = false;
        }

        $res['flag'] = $flag;
        $this->ajaxReturn($res,'json');
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

        foreach($skuData as $val) {
            $attr = M('spu_attr')->where('spu_attr_id='.$val['attr_combination'])->find();
            $skuArr[] = array(
                'id'         => $val['sku_id'],
                'name'       => $attr['attr_value'],
                'price'      => $val['price'],
                'sku_sale'   => $val['sku_sale'],
                'sku_stocks' => $val['sku_stocks']
            );
        }
        $info = array(
            'spu_id'    =>  $spuData['spu_id'],
            'title'     =>  $spuData['spu_name'],
            'image_src' =>  C('LS_IMG_URL') . $usImage['img_url'],
            'cate'      =>  $spucateData['name'],
            'sku'       =>  $skuArr
        );
        $this->ajaxReturn($info);
    }

    public function add() {
        if (isset($_POST['dosubmit'])) {

            $info     = $_POST['info'];
            $base     = $_POST['base'];
            $admin_id = $_SESSION['admin_info']['id'];
            $flag     = true;
            $message  = "添加成功";

            $topicSet      = M('topic_set');
            $groupTopic    = M('group_topic');
            $topic         = M('topic');
            $extend        = M('extend_special');
            $relation      = M('topic_relation');
            $spuModel      = M('spu');
            $spuImageModel = M('spu_image');
            $skuModel      = M('sku_list');

            //合集
            $baseImage  = substr($base['image_src'],1);
            $baseImageSrc    = $baseImage ? up_yun($baseImage) : '';
            $BaseNewImageSrc = array(
                'img_url' => $baseImageSrc['img_src'],
                'img_w'   => $baseImageSrc['img_w'],
                'img_h'   => $baseImageSrc['img_h']
            );
            //检测spu去重
            if($info) {
                foreach ($info as $v) {
                    $spuIDArr[] = $v['id'];
                }
            }
            //排除 5，6和品牌团下所用商品
            $topicWhere['type'] = '2';
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
            $relationWhere['spu_id']   = array('in',$spuIDArr);

            //根据条件查relation, 并查出对应spu_id
            $replace = $relation->where($relationWhere)->select();
            //spu去重
            if(!$replace) {
                $topicData = array(
                    'title'        => $base['title'],
                    //之前顺序错了，现在调回来
                    'desc'         => $base['provider'],
                    'provider'     => $base['desc'],
                    'image_src'    => serialize($BaseNewImageSrc),
                    //'sort'         => $base['sort'],
                    'sort'         => time(),
                    'add_time'     => strtotime(date('y-m-d H:i:s',time())),
                    'publish_time' => strtotime($base['time_start']),
                    'end_time'     => strtotime($base['time_end']),
                    'status'       => 'scheduled',
                    'is_show'      => '1',
                    'type'         => '2',
                    'brand_id'     => $base['brand_id'],
                    'admin_id'     => $admin_id
                );
                $topicAdd = $topic->add($topicData);
                if($topicAdd === false) {
                    $flag = false;
                }

                //合集&集合 关系
                $groupTopicData = array(
                    'group_id'    => '1',
                    'topic_id'    => $topicAdd,
                    'create_time' => strtotime(date('y-m-d H:i:s',time())),
                    'sort'        => $base['sort']
                );
                $groupTopicAdd = $groupTopic->add($groupTopicData);
                if($groupTopicAdd === false) {
                    $flag = false;
                }

                if($info) {
                    //添加relation 和 extend
                    foreach($info as $v) {
                        $spu          = $v['id'];
                        $publish_time = strtotime($v['time_start']);
                        $end_time     = strtotime($v['time_end']);
                        $sort         = $v['index'];
                        $type         = $v['type'];
                        $add_time     = strtotime(date('y-m-d H:i:s',time()));
                        //缩略图（不用了），因为接口把relation的图认为是商品图
                        $image        = substr($v['image_src'],1);
                        $image_src    = $image ? up_yun($image) : '';
                        $new_image_src = array(
                            'img_url' => $image_src['img_src'],
                            'img_w'   => $image_src['img_w'],
                            'img_h'   => $image_src['img_h'],
                            'img_type'=> $image_src['img_type']
                        );
                        //商品图片
                        $spuImageData = $spuImageModel->where("spu_id=".$spu)->find();
                        $spuImage = $spuImageData['images_src'];

                        //接收淘客标识
                        $taoke = $v['taoke'];
                        foreach($v['sku'] as $n) {
                            //非淘客
                            if($taoke == 'no') {
                                if($n['type'] == '2') {
                                    $skuWhere['sku_id'] = $n['skuid'];
                                    $skuWhere['sku_stocks'] = array('EGT',$n['stock']);
                                    $stockCut = $skuModel->where($skuWhere)->setDec('sku_stocks',$n['stock']);
                                    if ($stockCut === false) {
                                        $flag = false;
                                        $message = "该商品库存不足";
                                        //删除原先添加的品牌团
                                        $topic->where('topic_id='.$topicAdd)->delete();
                                    }
                                }
                                if($flag) {
                                    //扩展表
                                    $extendData = array(
                                        'sku_id'     => $n['skuid'],
                                        'start_time' => $publish_time,
                                        'end_time'   => $end_time,
                                        'price'      => $n['price']*100,
                                        'sku_stocks' => $n['stock'],
                                        'sku_sale'   => $n['sale'],
                                        'type'       => $n['type']
                                    );
                                    $extendSave = $extend->add($extendData);
                                    if(!$extendSave) {
                                        $flag = false;
                                        $message = '扩展信息添加失败';
                                    }
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
                                    'topic_id' => $topicAdd,
                                    'atom_id' => $extendSave,
                                    'spu_id' => $spu,
                                    'image_src' => $spuImage,
                                    //关系表排序，作为商品排序
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
                                if (!$relationAdd) {
                                    $flag = false;
                                    $message = "关系信息添加失败";
                                }
                            }
                        }
                    }
                }
            } else {
                $flag = false;
                $message = "该商品已参加其他活动";
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

    public function getSpuList () {
        $topic     = M('topic');
        $relation  = M('topic_relation');
        $extend    = M('extend_special');
        $sku       = M('sku_list');
        $spuAttr   = M('spu_attr');
        $spu       = M('spu');
        $spupicDB  = M('spu_image');
        $spucateDB = M('spu_cate');
        $brandModel = M('brand');

        $topic_id = isset($_GET['topicId']) ? $_GET['topicId'] : 0;
        $topicRelationData = $relation->where('topic_id='.$topic_id)->order('sort ASC')->group('spu_id')->select();

        foreach($topicRelationData as $v) {
            //sku名
            //$skuData  = $sku->where('sku_id='.$extendData['sku_id'])->find();
            //$attrData = $spuAttr->where('spu_attr_id='.$skuData['attr_combination'])->find();

            $spuIdArr[] = $v['spu_id'];

            //查spu信息
            $spuWhere['spu_id'] = $v['spu_id'];
            $spuData = $spu->where($spuWhere)->find();
            //图
            $spupicData = $spupicDB->where($spuWhere)->find();
            $usImage    = unserialize($spupicData['images_src']);
            //分类
            $spucateWhere['id'] = $spuData['cid'];
            $spucateData = $spucateDB->where($spucateWhere)->find();

            //合并同类SPU
            $spuArr[] = array(
                'rid'        => $v['rid'],
                'spu_id'     => $v['spu_id'],
                'title'  => $spuData['spu_name'],
                'image_src'    => C('LS_IMG_URL') . $usImage['img_url'],
                'cate'   => $spucateData['name'],
                'spu_height' => '',
                'sort'       => $v['sort'],
                //'sku_list'   => $extendArr,
                //'sku_len'    => count($extendArr)
            );
        }
        $this->ajaxReturn($spuArr,'json');
    }

    public function getSkuList() {

        $topic     = M('topic');
        $relation  = M('topic_relation');
        $extend    = M('extend_special');
        $sku       = M('sku_list');
        $spuAttr   = M('spu_attr');
        $spu       = M('spu');
        $spupicDB  = M('spu_image');
        $spucateDB = M('spu_cate');
        $brandModel = M('brand');

        $topic_id = isset($_GET['topicId']) ? $_GET['topicId'] : 0;
        $topicRelationData = $relation->where('topic_id='.$topic_id)->order('sort DESC')->select();

        foreach($topicRelationData as $v) {
            //查扩展表
            $extendData = $extend->where('id='.$v['atom_id'])->find();
            //sku名
            $skuData  = $sku->where('sku_id='.$extendData['sku_id'])->find();
            $attrData = $spuAttr->where('spu_attr_id='.$skuData['attr_combination'])->find();

            $spuIdArr[] = $v['spu_id'];

            //查spu信息
            $spuWhere['spu_id'] = $v['spu_id'];
            $spuData = $spu->where($spuWhere)->find();
            //图
            $spupicData = $spupicDB->where($spuWhere)->find();
            $usImage    = unserialize($spupicData['images_src']);
            //分类
            $spucateWhere['id'] = $spuData['cid'];
            $spucateData = $spucateDB->where($spucateWhere)->find();
            //获取淘客标识
            $istaoke = $spuData['guide_type'] == '0' ? 'no' : 'yes';

            if($istaoke == 'no') {
                $extendArr[] = array(
                    'spu_id'   => $v['spu_id'],
                    'id'       => $extendData['sku_id'],
                    'atom_id'  => $v['atom_id'],
                    'name'     => $attrData['attr_value'],
                    'price'    => $skuData['price']/100,
                    'sale'     => $extendData['sku_sale'],
                    'stock'    => $skuData['sku_stocks'],
                    'ex_price' => $extendData['price']/100,
                    'ex_stock' => $extendData['sku_stocks'],
                    'type'     => $extendData['type'],
                    'taoke'    => $istaoke
                );
            } else {
                $taokeWhere['spu_id'] = $v['spu_id'];
                //淘客商品,直接用spu '价格' '库存' '销量' 输出
                $spuDataForTaoke = $spu->where($taokeWhere)->find();
                $extendArr[] = array(
                    'spu_id'   => $v['spu_id'],
                    'id'       => '0',
                    'name'     => '无',
                    'price'    => $spuDataForTaoke['price_now']/100,
                    'sale'     => $spuDataForTaoke['sales'],
                    'stock'    => $spuDataForTaoke['stocks'],
                    'ex_price' => $spuDataForTaoke['price_now']/100,
                    'ex_stock' => $spuDataForTaoke['stocks'],
                    'type'     => $v['atom_id'],
                    'taoke'    => $istaoke
                );
            }
        }
        $this->ajaxReturn($extendArr,'json');
    }

    public function edit() {
        $topic_id  = $_GET['id'];

        $topic     = M('topic');
        $relation  = M('topic_relation');
        $extend    = M('extend_special');
        $sku       = M('sku_list');
        $spuAttr   = M('spu_attr');
        $spu       = M('spu');
        $spupicDB  = M('spu_image');
        $spucateDB = M('spu_cate');
        $brandModel = M('brand');

        $keyWhere['topic_id'] = $topic_id;

        //找出对应的那条topic
        $topicData = $topic->where($keyWhere)->find();
        $topicType = $topicData['type'];

        $title     = $topicData['title'];
        $desc      = $topicData['desc'];
        $provider  = $topicData['provider'];
        $sort      = $topicData['sort'];
        $timeStart = date('Y-m-d H:i:s',$topicData['publish_time']);
        $timeEnd   = date('Y-m-d H:i:s',$topicData['end_time']);
        $img       = unserialize($topicData['image_src']);
        $finialImg = C('LS_IMG_URL').$img['img_url'];

        //想知道这个品牌团属于什么活动类型

        //先找出所有商品

        //找出下级所有realtion(多条)
        $topicRelationData = $relation->where($keyWhere)->order('sort DESC')->select();
        if($topicRelationData[0]['type'] == '2') {
            //走扩展表
            $topicActiveType = $extend->where('id='.$topicRelationData[0]['atom_id'])->getField('type');
        } else {
            $topicActiveType = $topicRelationData[0]['atom_id'];
        }
        //找出品牌
        //$firstSpuId = $topicRelationData[0]['spu_id'];
        //$topicActiveBrandId = $spu->where('spu_id='.$firstSpuId)->getField('brand_id');
        $topicActiveBrandId = $topicData['brand_id'];
        $topicActiveBrandName = $brandModel->where('id='.$topicActiveBrandId)->getField('name');

        foreach($topicRelationData as $v) {
            //走extend
            //拓展重置
            $extendArr = array();
            //查扩展表
            $extendData = $extend->where('id='.$v['atom_id'])->find();
            //sku名
            $skuData  = $sku->where('sku_id='.$extendData['sku_id'])->find();
            $attrData = $spuAttr->where('spu_attr_id='.$skuData['attr_combination'])->find();

            $spuIdArr[] = $v['spu_id'];

            //查spu信息
            $spuWhere['spu_id'] = $v['spu_id'];
            $spuData = $spu->where($spuWhere)->find();
            //图
            $spupicData = $spupicDB->where($spuWhere)->find();
            $usImage    = unserialize($spupicData['images_src']);
            //分类
            $spucateWhere['id'] = $spuData['cid'];
            $spucateData = $spucateDB->where($spucateWhere)->find();
            //获取淘客标识
            $istaoke = $spuData['guide_type'] == '0' ? 'no' : 'yes';
            if($istaoke == 'no') {
                $extendArr[] = array(
                    'spu'      => $v['spu_id'],
                    'id'       => $extendData['sku_id'],
                    'atom_id'  => $v['atom_id'],
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
                $taokeWhere['spu_id'] = $v['spu_id'];
                //淘客商品,直接用spu '价格' '库存' '销量' 输出
                $spuDataForTaoke = $spu->where($taokeWhere)->find();
                $extendArr[] = array(
                    'spu'      => $v['spu_id'],
                    'id'       => '0',
                    'sku'      => '无',
                    'price'    => $spuDataForTaoke['price_now']/100,
                    'sale'     => $spuDataForTaoke['sales'],
                    'stock'    => $spuDataForTaoke['stocks'],
                    'ex_price' => $spuDataForTaoke['price_now']/100,
                    'ex_stock' => $spuDataForTaoke['stocks'],
                    'type'     => $v['atom_id'],
                    'taoke'    => $istaoke
                );
            }
            //合并同类SPU
            $spuArr[] = array(
                'rid'        => $v['rid'],
                'spu_id'     => $v['spu_id'],
                'spu_title'  => $spuData['spu_name'],
                'spu_img'    => C('LS_IMG_URL') . $usImage['img_url'],
                'spu_cate'   => $spucateData['name'],
                'spu_height' => '',
                'sort'       => $v['sort'],
                'sku_list'   => $extendArr,
                'sku_len'    => count($extendArr)
            );
        }
        $data = array(
            'spu_id'     => $spuIdArr,
            'rid'        => $topic_id,
            'title'      => $title,
            'desc'       => $desc,
            'provider'   => $provider,
            'sort'       => $sort,
            'time_start' => $timeStart,
            'end_start'  => $timeEnd,
            'image_src'  => $finialImg
        );
        $timestamp = time();
        $token = md5('unique_salt' . $timestamp);
        $this->assign('timestamp',$timestamp);
        $this->assign('token',$token);
        $this->assign('data',$data);
        $this->assign('type',$topicType);
        $this->assign('exlist',$spuArr);
        $cateMod = D('Spu_cate');
        $brandMod = D('Brand');
        $brandResult = $brandMod->get_list();
        $this->assign('brand_list', $brandResult['sort_list']);
        $cates_list = $cateMod->get_list();
        $this->assign('cate_list', $cates_list['sort_list']);
        $this->assign('active_type', $topicActiveType);
        $this->assign('active_brandId',$topicActiveBrandId);
        $this->assign('active_brandName',$topicActiveBrandName);
        $this->display();
    }

    public function save() {
        $topic    = M('topic');
        $relation = M('topic_relation');
        $extend   = M('extend_special');
        $extendHistory = M('extend_special_history');
        $skuModel = M('sku_list');
        $spuModel = M('spu');
        $spuImageModel = M('spu_image');
        $flag         = true;
        $message      = "保存成功";
        $topic_id     = $_POST['topic_id'];
        $spu          = $_POST['spu'];
        $title        = $_POST['title'];
        $desc         = $_POST['desc'];
        $provider     = $_POST['provider'];
        $publish_time = strtotime($_POST['time_start']);
        $end_time     = strtotime($_POST['time_end']);
        $sort         = $_POST['sort'];
        $update_time  = strtotime(date('y-m-d H:i:s',time()));
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
        $admin_id   = $_SESSION['admin_info']['id'];
        $collection = $_POST['collection'];
        $addCollection = $_POST['addSpu'];

        $cutSpu = $_POST['cutSpu'];

        //判断当前时间，及需要更换的状态
        if($publish_time <= time()) {
            if($end_time <= time()) {
                $globalStatus = 'locked';
            } else {
                $globalStatus = 'published';
            }
        } else {
            $globalStatus = 'scheduled';
        }
        //通用设置
        $topicData = array(
            'title'        => $title,
            'desc'         => $provider,
            'provider'     => $desc,
            'update_time'  => $update_time,
            'publish_time' => $publish_time,
            'end_time'     => $end_time,
            'admin_id'     => $admin_id,
            'sort'         => $sort,
            'status'       => $globalStatus
        );
        if($new_image_src){
            $topicData['image_src'] = serialize($new_image_src);
        }
        $topicWhere['topic_id'] = $topic_id;
        $topicSave = $topic->where($topicWhere)->save($topicData);
        if($topicSave === false) {
            $flag = false;
            $message = "品牌团添加失败";
        }
        //修改relation
        $allWhere['topic_id'] = $topic_id;
        $allWhere['_logic']   = 'AND';
        $allRelationData = $relation->where($allWhere)->select();
        $relationData = array(
            //'sort'         => $sort,
            'update_time'  => $update_time,
            'publish_time' => $publish_time,
            'end_time'     => $end_time,
            'admin_id'     => $admin_id
        );
        // hhf 修改 2015/08/20 16:28
        // if($new_image_src){
        // $relationData['image_src'] = serialize($new_image_src);
        // }
        foreach($allRelationData as $aval) {
            //遍历，保存通用设置
            $ridArr[] = $aval['rid'];
        }
        if($ridArr) {
            $relationWhere['rid'] = array('in',$ridArr);
            $relationSave = $relation->where($relationWhere)->save($relationData);
            if($relationSave === false) {
                $flag = false;
                $message = "关系保存失败";
            }
        }
        //修改 relation 结束。

        //原有扩展表修改
        //用topic_id + spu_id 定位relation
        foreach($collection as $d) {
            //更新关系表
            $rsave = array(
                //'spu_id'  => $d['spu_id'],
                'publish_time' => strtotime($d['start_time']),
                'end_time'   => strtotime($d['end_time']),
                'sort' => $d['sort'],
                'status' => $globalStatus
            );
            $relation->where('topic_id='.$topic_id.' AND spu_id='.$d['spu_id'])->save($rsave);
            //更新扩展表
            if($d['taoke'] == 'no') {
                $extendInfoArr = $relation->where('topic_id='.$topic_id.' AND spu_id='.$d['spu_id'])->select();
                foreach($extendInfoArr as $etia) {
                    //根据realtion查扩展表
                    $defaultExtendSave = array(
                        'start_time' => strtotime($d['start_time']),
                        'end_time'   => strtotime($d['end_time'])
                    );
                    $exWhere['id'] = $etia['atom_id'];
                    $extendSaveActive = $extend->where($exWhere)->save($defaultExtendSave);
                    if($extendSaveActive === false) {
                        $flag = false;
                        $message = $etia['atom_id'] . '扩展信息保存失败';
                    }
                }
            }
        }

        //新增sku，先遍历spu，再遍历sku。
        foreach($addCollection as $addn) {
            //接收淘客标识
            $taoke = $addn['taoke'];
            $skuList = $addn['sku'];
            $myImage = $spuImageModel->where('spu_id='.$addn['id'].' AND type=1')->getField('images_src');
            if($myImage) {
                //非淘客
                if($taoke == 'no') {
                    foreach($skuList as $sl) {
                        //限时限量才扣库存
                        if($sl['type'] == '2') {
                            $skuAddWhere['sku_id'] = $sl['skuid'];
                            $skuAddWhere['sku_stocks'] = array('EGT',$sl['stock']);
                            //减库存
                            $stockCut = $skuModel->where($skuAddWhere)->setDec('sku_stocks',$sl['stock']);
                            if ($stockCut === false) {
                                $flag = false;
                                $message = "该商品库存不足";
                            }
                            if($flag) {
                                //扩展表
                                $addExtendAddData = array(
                                    'sku_id'     => $sl['skuid'],
                                    'start_time' => $publish_time,
                                    'end_time'   => $end_time,
                                    'price'      => $sl['price']*100,
                                    'sku_stocks' => $sl['stock'],
                                    'sku_sale'   => $sl['sale'],
                                    'type'       => $sl['type']
                                );
                                $addExtendSave = $extend->add($addExtendAddData);
                                if($addExtendSave === false) {
                                    $flag = false;
                                    $message = '扩展信息添加失败1';
                                }
                            }
                        } else if($sl['type'] == '1') {
                            //扩展表
                            $addExtendAddData = array(
                                'sku_id'     => $sl['skuid'],
                                'start_time' => $publish_time,
                                'end_time'   => $end_time,
                                'price'      => $sl['price']*100,
                                'sku_stocks' => $sl['stock'],
                                'sku_sale'   => $sl['sale'],
                                'type'       => $sl['type']
                            );
                            $addExtendSave = $extend->add($addExtendAddData);
                            if($addExtendSave === false) {
                                $flag = false;
                                $message = '扩展信息添加失败2';
                            }
                        }
                        //关系表
                        $relationData = array(
                            'topic_id' => $topic_id,
                            'atom_id' => $addExtendSave,
                            'spu_id' => $addn['id'],
                            'image_src' => $myImage,
                            'sort' => $addn['sort'],
                            'type' => $addn['type'],
                            'add_time' => time(),
                            'update_time' => time(),
                            'publish_time' => strtotime($addn['time_start']),
                            'end_time' => strtotime($addn['time_end']),
                            'status' => $globalStatus,
                            'admin_id' => $admin_id
                        );
                        if($flag) {
                            $relationAdd = $relation->add($relationData);
                            if (!$relationAdd) {
                                $flag = false;
                                $message = "关系信息添加失败1";
                            }
                        }
                    }
                }
                //是淘客
                else if($taoke == 'yes') {
                    $sku = 0;
                    //atom_id 作为淘客商品活动类型的区分 0:无活动 1:限时 2:限时限量
                    //$addExtendSave = $skuList['type'];
                    $type = 0;
                    //关系表
                    $relationData = array(
                        'topic_id' => $topic_id,
                        'atom_id' => $skuList[0]['type'],
                        'spu_id' => $addn['id'],
                        'image_src' => $myImage,
                        'sort' => $addn['sort'],
                        'type' => '0',
                        'add_time' => time(),
                        'update_time' => time(),
                        'publish_time' => strtotime($addn['time_start']),
                        'end_time' => strtotime($addn['time_end']),
                                                                                                                                                                                                                                                                                                                                                                              'status' => $globalStatus,
                        'admin_id' => $admin_id
                    );
                    if($flag) {
                        $relationAdd = $relation->add($relationData);
                        if (!$relationAdd) {
                            $flag = false;
                            $message = "关系信息添加失败2";
                        }
                    }
                }
            } else {
                $flag = false;
                $message = $addn['id'] . '图片获取不到';
            }
        }
        //删除sku
        foreach($cutSpu as $cutval) {
            $cut_topicid   = $cutval['topicId'];
            $cut_spuid = $cutval['spuId'];
            $cut_taoke = $cutval['taoke'];
            $cut_skuList = $cutval['skuList'];
            $flag = true;
            /* tdl 11.23修复限时库存回滚问题，增加extend_history写入 */
            $delSkuTransfer = M('');
            $delSkuTransfer->startTrans();
            foreach($cut_skuList as $sck) {
                //非淘客才删库存
                if($sck['taoke'] == 'no') {
                    $cut_atomId = $sck['atomId'];
                    if($cut_atomId) {
                        //验type, 1:删除不加库存（限时）, 2:删除加库存（限时限量）
                        if($sck['type'] == '2') {
                            //从扩展表取库存
                            $cut_stock = $extend->where('id='.$cut_atomId)->getField('sku_stocks');
                            if($cut_stock !== false) {
                                $returnStock = $skuModel->where('sku_id='.$sck['skuId'])->setInc('sku_stocks',$cut_stock);
                                if($returnStock === false) {
                                    $flag = false;
                                    $message = '加回库存失败';
                                    Log::write('删除sku-加回库存失败-限时限量：' . $skuModel->getLastSql() , Log::INFO);
                                    $delSkuTransfer->rollback();
                                }
                            } else {
                                $flag = false;
                                $message = '取库存失败';
                                Log::write('删除sku-取库存失败-限时限量：' . $extend->getLastSql() , Log::INFO);
                                $delSkuTransfer->rollback();
                            }
                        } else {
                            //从扩展表取库存
                            $cut_stock = $extend->where('id='.$cut_atomId)->getField('sku_stocks');
                            if($cut_stock !== false) {
                                $returnStockData = array(
                                    'sku_stocks' => $cut_stock
                                );
                                //限时结束直接把库存复制回去
                                $returnStock = $skuModel->where('sku_id='.$sck['skuId'])->save($returnStockData);
                                if($returnStock === false) {
                                    $flag = false;
                                    $message = '加回库存失败';
                                    Log::write('删除sku-加回库存失败-限时：' . $extend->getLastSql() , Log::INFO);
                                    $delSkuTransfer->rollback();
                                }
                            } else {
                                $flag = false;
                                $message = '取库存失败';
                                Log::write('删除sku-取库存失败-限时：' . $extend->getLastSql() , Log::INFO);
                                $delSkuTransfer->rollback();
                            }
                        }
                        //销量回滚操作，移回去而不是加回去
                        $cut_sale = $extend->where('id='.$cut_atomId)->getField('sku_sale');
                        $returnSaleData = array('sku_sale'=>$cut_sale);
                        $returnSale = $skuModel->where('sku_id='.$sck['skuId'])->save($returnSaleData);
                        if($returnSale === false) {
                            $flag = false;
                            $message = '销量回滚失败';
                            Log::write('销量回滚失败：' . $skuModel->getLastSql() , Log::INFO);
                            $delSkuTransfer->rollback();
                        }
                        if($flag) {
                            $extendWantDel = $extend->where('id='.$cut_atomId)->find();
                            //加入历史表
                            $moveDataToHistory = $extendHistory->add($extendWantDel);
                            if($moveDataToHistory === false) {
                                Log::write('extend移至history失败：' . $extendHistory->getLastSql() , Log::INFO);
                            }
                            //删除拓展表
                            $extendDel = $extend->where('id='.$cut_atomId)->delete();
                            if($extendDel === false) {
                                $flag = false;
                                $message = '删扩展表失败';
                                Log::write('删扩展表失败：' . $extend->getLastSql() , Log::INFO);
                                $delSkuTransfer->rollback();
                            }
                        }
                    }
                }
            }
            $delSkuTransfer->commit();

            $cutSpuArr[] = $cut_spuid;
            //删除relation
            $cutRelationWhere['spu_id'] = array('in',array_unique($cutSpuArr));
            $cutRelationWhere['topic_id'] = $cut_topicid;
            $cutRelation = $relation->where($cutRelationWhere)->delete();
            if($cutRelation === false) {
                $flag = false;
                $message = '关系删除失败';
            }
        }

        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis',$options);
        $ls_home = "ls_home";
        $redis->rm($ls_home);
        $redis->close();

        $res['flag'] = $flag;
        $res['msg']  = $message;
        $this->ajaxReturn($res,'json');
    }

    public function addGroup(){
        $topicSet = M('topic_set');
        $save = $_POST['save'];
        $where['group_id'] = '1';

        if($save) {
            $title = $_POST['title'];
            $desc  = $_POST['desc'];
            $flag  = true;
            !empty($title) && $data['title'] = $title;
            !empty($desc) && $data['desc'] = $desc;
            $topicSetSave = $topicSet->where($where)->save($data);
            if($topicSetSave === false) {
                $flag = false;
            }
            $res['flag'] = $flag;
            $this->ajaxReturn($res,'json');
        } else {
            $topicSetData = $topicSet->where($where)->find();
            $this->assign('data',$topicSetData);
            $this->display();
        }
    }

    public function editRemoveGoods() {
        $spuModel = M('spu');
        $extendModel = M('extend_special');
        $topicRelationModel = M('topic_relation');
        $topicId  = $_POST['id'];
        $spuIdArr = $_POST['cut'];
        foreach($spuIdArr as $val) {
            $spuId = $val;
            //要删除的spu
            $relationWhere['spu_id'] = $spuId;
            $relationWhere['topic_id'] = $topicId;
            $model = M('');
            $model->startTrans();
            //查出所有关系表
            $relationData = $topicRelationModel->where($relationWhere)->select();
            foreach($relationData as $v) {
                if($v['type'] == '2') {
                    //从扩展表取库存
                    $stock = $extendModel->where('id='.$v['atom_id'])->getField('sku_stocks');
                    if($stock === false) {
                        $model->rollback();
                        Log::write('品牌团,editRemoveGoods,extend取库存失败' . $extendModel->getLastSql() , Log::ERR);
                    }
                    //加回spu表
                    $rollbackToSpu = $spuModel->where('spu_id='.$spuId)->setInc('stocks',$stock);
                    if($rollbackToSpu === false) {
                        $model->rollback();
                        Log::write('品牌团,editRemoveGoods,回滚库存失败' . $extendModel->getLastSql() , Log::ERR);
                    }
                    //删除拓展表
                    $extendDel = $extendModel->where('id='.$v['atom_id'])->delete();
                    if($extendDel) {
                        $model->rollback();
                        Log::write('品牌团,editRemoveGoods,extend删除失败' . $extendModel->getLastSql() , Log::ERR);
                    }
                }
            }
            //删除关系表
            $topicRelationDel = $topicRelationModel->where($relationWhere)->delete();
            if($topicRelationDel === false) {
                $model->rollback();
                Log::write('品牌团,editRemoveGoods,topic_relation删除失败' . $extendModel->getLastSql() , Log::ERR);
            }
            //提交
            $model->commit();
        }
    }

    /**
    +----------------------------------------------------------
     * 上移下移专题位置 type=1上移  type=3下移
    +----------------------------------------------------------
     */
    function  upDownTopic (){
        $type = I('get.type',0,'intval');
        $topicId = I('get.id',0,'intval');
        $topicMod = M('topic');
        $topicResult = $topicMod->find($topicId);
        if($type&$topicResult){
            if($type==1){
                $upTopic = $topicMod->where('sort >'.$topicResult['sort'].' AND type = 2')->order('sort asc')->find();
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
                echo 1;
            }elseif($type==3){
                $downTopic = $topicMod->where('sort <'.$topicResult['sort'].' AND type = 2')->order('sort desc')->find();
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
                echo 1;
            }
            echo $type;
        }else{
            echo 0;
        }
    }
}
?>