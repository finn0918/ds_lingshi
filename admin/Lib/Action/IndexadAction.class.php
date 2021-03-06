<?php

class indexadAction extends BaseAction{


    function index() {

        $query = $_GET['query'];
        $timeStart = $_GET['time_start'];
        $timeEnd   = $_GET['time_end'];
        $status    = isset($_GET['status'])     ? trim($_GET['status']) : '';
        $keyword   = isset($_GET['keyword'])    ? trim($_GET['keyword']) : '';

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
                $this->assign('status',$status);
            }
            if($keyword) {
                $where['title'] = array('like','%'.$keyword.'%');
                $this->assign('keyword',$keyword);
            }
            $where['_logic'] = 'AND';
        }

        $ad = M('ad');
        import("ORG.Util.Page");
        $count = $ad->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $ad_list = $ad->where($where)->order('status ASC')->limit($Page->firstRow.','.$Page->listRows)->select();

        for($i=0; $i<count($ad_list); $i++) {
            $adminId = $ad_list[$i]['admin_id'];
            switch($ad_list[$i]['position']) {
                case '1' :
                    $ad_list[$i]['positionText'] = '左';
                    break;
                case '2' :
                    $ad_list[$i]['positionText'] = '右上';
                    break;
                case '3' :
                    $ad_list[$i]['positionText'] = '右下1';
                    break;
                case '4' :
                    $ad_list[$i]['positionText'] = '右下2';
                    break;
            }
            //status
            switch($ad_list[$i]['status']) {
                case 'published' :
                    $ad_list[$i]['status'] = '已发布';
                    break;
                case 'locked' :
                    $ad_list[$i]['status'] = '锁定';
                    break;
                case 'scheduled':
                    $ad_list[$i]['status'] = '定时';
                    break;
            }
            //linkto
            switch($ad_list[$i]['link_to']) {
                case '1' :
                    $ad_list[$i]['link_to'] = '专题详情';
                    break;
                case '2' :
                    $ad_list[$i]['link_to'] = '专题列表';
                    break;
                case '3' :
                    $ad_list[$i]['link_to'] = '商品详情';
                    break;
                case '4' :
                    $ad_list[$i]['link_to'] = '商品列表';
                    break;
                case '5' :
                    $ad_list[$i]['link_to'] = '自定义链接';
                    break;
                case '7' :
                    $ad_list[$i]['link_to'] = '优惠券列表';//edit hhf
                    break;
                case '6' :
                    $ad_list[$i]['link_to'] = '优惠券详情';//edit hhf
            }
            //image
            $img = unserialize($ad_list[$i]['image_src']);
            $ad_list[$i]['image_src'] = C('LS_IMG_URL').$img['img_url'];
            //admin
            $admin = M('admin');
            $adminInfo = $admin->where("id = $adminId")->select();
            $ad_list[$i]['admin'] = $adminInfo[0]['user_name'];
        }

        $this->assign('page',$show);
        $this->assign('ad_list',$ad_list);
        $this->display();
    }

    public function edit() {
        $_SESSION['chooseSpu'] = '';
        $id = $_GET['id'];
        $ad = M('ad');
        $where = "id=$id";
        $adInfo = $ad->where($where)->find();
        $pic = unserialize($adInfo['image_src']);
        $adInfo['image_src'] = C('LS_IMG_URL'). $pic['img_url'];
        $adInfo['publish_time'] = date('Y-m-d H:i:s',$adInfo['publish_time']);
        $adInfo['end_time'] = date('Y-m-d H:i:s',$adInfo['end_time']);

        $timestamp = time();
        $token = md5('unique_salt' . $timestamp);
        $this->assign('timestamp',$timestamp);
        $this->assign('token',$token);
        if($adInfo['link_to']==7){   //edit hhf
            $groupTopicMod = M('group_topic');
            $res = $groupTopicMod->where("group_id = {$adInfo['link_id']}")->field('topic_id')->order('sort asc')->getField('topic_id',true);
            $adInfo['link_id'] = implode(',',$res);
        }elseif($adInfo['link_to']==4){   //edit hhf 商品列表
            $topicRelationMod = M('topic_relation');
            $spuIds = $topicRelationMod->where("topic_id = {$adInfo['link_id']}")->order('sort asc')->getField('spu_id',true);
            $spuIds = implode(',',$spuIds);
            $topicClass = A('Topic');
            $res = $topicClass->getTable($spuIds);
            $adInfo['table'] = $res;
            $_SESSION['chooseSpu'] = $spuIds;
        }
        $this->assign('focusdata',$adInfo);
        $this->display();
    }
    public function editsave() {

        $id = $_POST['id'];
        $linkto  = $_POST['linkto'];
        $linkid  = $_POST['linkid'];
        $linkurl = $_POST['linkurl'];

        $title   = isset($_POST['title']) ? trim($_POST['title']) : '';
        if(!empty($_POST['image_src'])) {
            $image   = substr($_POST['image_src'],1);
            $upImage = $image ? up_yun($image) : '';
            $new_image_src = array(
                'img_url' => $upImage['img_src'],
                'img_w'   => $upImage['img_w'],
                'img_h'   => $upImage['img_h']
            );
        } else {
            $new_image_src = null;
        }
        $position     = $_POST['posi'];
        $sort         = isset($_POST['sort']) ? intval($_POST['sort']) : 0;
        //$add_time     = strtotime(date('y-m-d H:i:s',time()));
        $publish_time = $_POST['time_start'] ? strtotime($_POST['time_start']) : 0;
        $end_time     = $_POST['time_end'] ? strtotime($_POST['time_end']) : 0;
        $status       = $publish_time <= time() ? 'published' : 'scheduled';
        $admin_id     = $_SESSION['admin_info']['id'];
        $channel_id   = $_POST['channel_id'];
        $detail_id    = $_POST['linkdetail'];

        $title && $data['title'] = $title;
        if($new_image_src){
            $data['image_src'] = serialize($new_image_src);
        }
        $sort && $data['sort'] = $sort;
        $linkto && $data['link_to'] = $linkto;
        if($linkto == '3') {
            $data['link_id'] = $detail_id;
        } else {
            if($linkto == '7'){ //edit hhf
                $linkid = $this->updateCouponList($linkid,$id);
                $data['link_id'] = $linkid > 0 ? $linkid : 1;
            }else{
                if($linkto== '4'){//取直接选的商品
                    A('Focusbanner')->editSpuList($_POST);
                }
                $data['link_id'] = $linkid;
            }
        }
        $linkurl && $data['link_url'] = $linkurl;
        $data['position'] = $position;
        //$data['add_time'] = $add_time;
        $data['publish_time'] = $publish_time;
        $data['end_time'] = $end_time;
        $data['status'] = $status;
        $admin_id && $data['admin_id'] = $admin_id;
        $channel_id && $data['channel_id'] = $channel_id;

        $ad = M('ad');
        $where = "id=$id";
        $adinfo = $ad->where($where)->save($data);
        if($adinfo!==false) {
            updateCache("ls_home");//更新首页缓存
            $this->success('保存成功','admin.php?m=Indexad&a=index');
        } else {
            $this->error('添加失败');
        }
    }

    public function add() {
        $_SESSION['chooseSpu'] = '';
        $timestamp = time();
        $token = md5('unique_salt' . $timestamp);
        $this->assign('timestamp',$timestamp);
        $this->assign('token',$token);
        $this->display();
    }

    public function addsave() {

        $linkto = $_POST['linkto'];
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $image = substr($_POST['image_src'],1);
        $upImage = $image ? up_yun($image) : '';
        $new_image_src = array(
            'img_url' => $upImage['img_src'],
            'img_w'   => $upImage['img_w'],
            'img_h'   => $upImage['img_h']
        );

        $position = $_POST['posi'];
        $sort = isset($_POST['sort']) ? intval($_POST['sort']) : 0;
        $add_time = strtotime(date('y-m-d H:i:s',time()));
        $publish_time = $_POST['time_start'] ? strtotime($_POST['time_start']) : 0;
        $end_time = $_POST['time_end'] ? strtotime($_POST['time_end']) : 0;
        $status = 'scheduled';
        $admin_id = $_SESSION['admin_info']['id'];
        $channel_id = $_POST['channel_id'];

        $data['title'] = $title;
        $data['image_src'] = serialize($new_image_src);
        $data['sort'] = $sort;
        $data['position'] = $position;
        $data['type'] = '2';
        $data['link_to'] = $linkto;
        $data['add_time'] = $add_time;
        $data['publish_time'] = $publish_time;
        $data['end_time'] = $end_time;
        $data['status'] = $status;
        $data['admin_id'] = $admin_id;
        $data['channel_id'] = $channel_id;

        switch($linkto) {
            //专题合集
            case '1' :
                $data['link_id'] = isset($_POST['linkid']) ? intval($_POST['linkid']) : 1;
                break;
            //专题列表
            case '2' :
                $data['link_id'] = isset($_POST['linkid']) ? intval($_POST['linkid']) : 1;
                break;
            //跳商品详情
            case '3' :
                $data['link_id'] = $_POST['linkdetail'];
                break;
            //跳商品列表
            case '4' :
                $data['link_id'] = isset($_POST['linkid']) ? intval($_POST['linkid']) : 1;
                if($data['link_id']==0){//取直接选的商品
                    $data['link_id'] = A('Focusbanner')->addSpuList($_POST,"这是从首页广告直接生成的一个隐形专题");
                }elseif($_POST['addId']){
                    A('Focusbanner')->editSpuList($_POST);
                }
                break;
            //跳转地址
            case '5' :
                $data['link_url'] = $_POST['linkurl'];
                break;
            //优惠券列表
            case '7' :
                $groupId = $this->saveCouponList($_POST['linkid'],$data['title']);
                $data['link_id'] = $groupId>0 ? intval($groupId) : 1;
                break;
            //优惠券详情
            case '6' :
                $data['link_id'] = isset($_POST['linkid']) ? intval($_POST['linkid']) : 1;
                break;
        }

        $ad = M('ad');
        $adinfo = $ad->add($data);
        if($adinfo) {
            updateCache("ls_home");//更新首页缓存
            $this->success('添加成功','admin.php?m=Indexad&a=index');
        } else {
            $this->error('添加失败');
        }
    }

    public function group() {
        $group = M('topic_set');
        $where['group_id'] = array('NOT IN','1');
        $where['is_show'] = '1';
        $where['status'] = 'published';
        $where['_logic'] = 'AND';
        import("ORG.Util.Page");
        $count = $group->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $groupData = $group->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('group_list',$groupData);
        $this->display();
    }

    public function topic() {
        $type = $_GET['type'];
        $group = M('topic');
        //0: 隐形专题 1:正常专题 2:品牌团
        $where['type'] = $type;
        $where['status'] = 'published';
        $where['topic_id'] = array('not in','5,6');
        if($_GET['time_start']) {
            $where['time_start'] = $_GET['time_start'];
        }
        if($_GET['time_start']) {
            $where['title'] = array('like','%'.$_GET['keyword'].'%');
        }
        $where['_logic'] = 'AND';

        import("ORG.Util.Page");
        $count = $group->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $groupData = $group->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        for($i=0; $i<$count; $i++) {
            $pic = unserialize($groupData[$i]['image_src']);
            $groupData[$i]['image_src'] = C('LS_IMG_URL') . $pic['img_url'];
            switch($groupData[$i]['status']) {
                case 'published' :
                    $groupData[$i]['status'] = '发布';
                    break;
                case 'locked' :
                    $groupData[$i]['status'] = '锁定';
                    break;
                case 'secheduled' :
                    $groupData[$i]['status'] = '定时';
                    break;
            }
        }
        $this->assign('page', $show);
        $this->assign('topic_list',$groupData);
        $this->display();
    }

    //上架
    public function setUpTable() {
        $adModel = M('ad');
        $id = $_POST['id'];
        //我的坑
        $posi = $_POST['posi'];
        $flag = true;
        $msg = '上架成功!';
        //检索相同坑位有 "上架中" 的人吗
//        $posiWhere['position'] = $posi;
//        $posiWhere['type'] = '2';
//        $posiWhere['status'] = 'published';
//        $adData = $ad->where($posiWhere)->select();
//        if($adData) {
//            $flag = false;
//            $msg  = '这个坑位有其他广告上架中！';
        $saveData['status'] = 'published';
        $saveData['publish_time'] = time();
        $where['id'] = $id;
        $endTime = $adModel->where($where)->getField('end_time');
        if($endTime < time()) {
            $flag = false;
            $msg  = '上架失败，请检查下架时间。';
        } else {
            $adSave = $adModel->where($where)->save($saveData);
            if($adSave === false) {
                $flag = false;
                $msg  = '上架失败！';
            }
        }
        $res['flag'] = $flag;
        $res['msg']  = $msg;
        updateCache("ls_home");//更新首页缓存
        $this->ajaxReturn($res,'json');
    }

    public function setDownTable(){
        $ad = M('ad');
        $id = $_POST['id'];
        $flag = true;
        $msg = '下架成功!';
        $saveData['status'] = 'locked';
        $saveData['end_time'] = time();
        $where['id'] = $id;
        $ad->where($where)->save($saveData);
        if($ad === false) {
            $flag = false;
            $msg  = '下架失败！';
        }
        $res['flag'] = $flag;
        $res['msg']  = $msg;
        updateCache("ls_home");//更新首页缓存
        $this->ajaxReturn($res,'json');
    }

    //del
    public function del() {
        $ad = M('ad');
        $delId = $_POST['did'];
        if(is_array($delId)){
            $where['id'] = array('in',$delId);
        }else{
            $where['id'] = $delId;
        }
        $adDel = $ad->where($where)->delete();
        $result['flag'] = $adDel ? true : false;
        updateCache("ls_home");//更新首页缓存
        $this->ajaxReturn($result);
    }

    /**
    +----------------------------------------------------------
     * 保存优惠券列表 在ls_topic_set表新建一个专题合集 对应关系保存到ls_group_topic
     * @author:hhf
    +----------------------------------------------------------
     */
    public function saveCouponList($ids,$title){
        $groupMod = M('topic_set');
        $groupTopicMod = M('group_topic');
        $data['title'] = "优惠券列表-".$title;
        $data['add_time'] = time();
        $data['desc'] = "这是一个优惠券列表合集";
        $flag = $groupMod->add($data);
        if($flag){
            $idArray = explode(',',$ids);
            $time = time();
            foreach($idArray as $key=>$val){
                $data = array();
                if($val){
                    $data['group_id'] = $flag;
                    $data['topic_id'] = $val;
                    $data['sort'] = ++$key;
                    $data['create_time'] = $time;
                    $topicData[] = $data;
                }
            }
            if(isset($topicData)){
                $flagGroup = $groupTopicMod->addAll($topicData);
                if($flagGroup){
                    return $flag;
                }
            }
            return 0;
        }else{
            return 0;
        }
    }

    /**
    +----------------------------------------------------------
     * 修改优惠券列表 对应关系ls_group_topic
     * @author:hhf
    +----------------------------------------------------------
     */
    public function updateCouponList($ids,$id){
        $groupTopicMod = M('group_topic');
        $adMod = M('ad');
        $adResult = $adMod->find($id);
        $groupId = $adResult['link_id'];
        $flag = $groupTopicMod->where("group_id = $groupId")->delete();
        if($flag!==false){
            $idArray = explode(',',$ids);
            $time = time();
            foreach($idArray as $key=>$val){
                $data = array();
                if($val){
                    $data['group_id'] = $groupId;
                    $data['topic_id'] = $val;
                    $data['sort'] = ++$key;
                    $data['create_time'] = $time;
                    $topicData[] = $data;
                }
            }
            if(isset($topicData)){
                $flagGroup = $groupTopicMod->addAll($topicData);
                if($flagGroup){
                    return $groupId;
                }
            }
            return 0;
        }else{
            return 0;
        }
    }

}
?>