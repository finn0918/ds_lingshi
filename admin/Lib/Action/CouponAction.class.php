<?php
/**
 * 优惠券管理模块
 * @author:hhf
 * Date: 2015/8/31
 * Time: 9:53
 */

class CouponAction extends BaseAction{

    /**
    +----------------------------------------------------------
     * 优惠券列表
    +----------------------------------------------------------
     */
    function index(){
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
        $type = I('get.type','','intval');//优惠券类型
        $search['type'] = $type;
        $this->assign('search',$search);
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
        if($status){
            $where .= " AND status = '$status'";
        }
        if($type){
            $where .= " AND type = '$type'";
        }
        $couponMod = M('coupon');
        $couponUserMod = M('coupon_user');
        $orderCouponMod = M('sub_order_coupon');
        $count = $couponMod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $couponResult = $couponMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('add_time desc')->select();
        foreach($couponResult as $key=>$val){
            switch($val['status']){
                case 'scheduled':
                    $couponResult[$key]['statusCH'] = "<font color='green'>定时</font>";
                    break;
                case 'published':
                    $couponResult[$key]['statusCH'] = "<font color='red'>发布</font>";
                    break;
                case 'locked':
                    $couponResult[$key]['statusCH'] = "<font color='blue'>下架</font>";
                    break;
            }
            $couponResult[$key]['rule_free'] = number_format($val['rule_free']/100,2);
            $couponResult[$key]['rule_money'] = number_format($val['rule_money']/100,2);
            $couponResult[$key]['total_num'] = $val['surplus_num']+$val['send_num'];//发放数量（包括剩余数量和领取数量）
            $orderCouponCount = $orderCouponMod->where("coupon_num = '{$val['coupon_num']}'")->count();//优惠券被使用数量
            $couponResult[$key]['use_num'] = $orderCouponCount>0?$orderCouponCount:0;
            switch($val['type']){
                case '1':
                    $couponResult[$key]['typeText'] = "按商品发放";
                    break;
                case '2':
                    $couponResult[$key]['typeText'] = "按用户发放";
                    if($couponUserMod->where("coupon_num = '{$val['coupon_num']}' and user_id = 0")->find()){
                        $resultCount = "全部用户";
                    }else{
                        $resultCount = $couponUserMod->where("coupon_num = '{$val['coupon_num']}'")->count();
                    }
                    $couponResult[$key]['total_num'] = $resultCount;
                    break;
            }
            $useredCoupon = $couponUserMod->where("coupon_num = '{$val['coupon_num']}' AND is_use = 1")->count();
            $couponResult[$key]['use_num'] = $useredCoupon;
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('coupon',$couponResult);
        $this->display();
    }


    /**
    +----------------------------------------------------------
     * 新增按商品发放优惠券
    +----------------------------------------------------------
     */
    function couponBySpu(){
        $couponNum = createCouponNum();
        $type = 1;//表示按商品发放
        $postInfo = I('post.');
        $suppliersMod = M('suppliers');
        $couponMod = D('Coupon');
        $couponSpuMod = M('coupon_spu');
        $spuMod = M('spu');
        if(isset($_POST['dosubmit'])){
            $data = array();
            $data['name'] = htmlspecialchars(trim($postInfo['name']));
            $data['online_time'] = strtotime($postInfo['publishTime']);
            $data['get_start_time'] = strtotime($postInfo['sendTimeStart']);
            $data['get_end_time'] = strtotime($postInfo['sendTimeEnd']);
            $data['use_start_time'] = strtotime($postInfo['useTimeStart']);
            $data['use_end_time'] = strtotime($postInfo['useTimeEnd']);
            $data['add_time'] = time();
            $data['status'] = "scheduled";
            while($couponMod->where("coupon_num = '$couponNum'")->find()){//确保优惠券标识唯一
                $couponNum = createCouponNum();
            }
            $data['coupon_num'] = $couponNum;
            $data['surplus_num'] = intval($postInfo['num']);
            $data['send_num'] = 0;
            $data['suppliers_id'] = intval($postInfo['suppliers']);
            $data['rule_desc'] = htmlspecialchars(trim($postInfo['detail']));
            $data['rule_money'] = floatval($postInfo['price'])*100;
            $data['rule_free'] = floatval($postInfo['cash'])*100;
            $data['type'] = $type;
            $m = M();
            $m->startTrans();
            $flag = $couponMod->add($data);
            if($flag){
                $couponData = array();
                if($data['suppliers_id']==0){
                    $couponData[0]['coupon_num'] = $couponNum;
                    $couponData[0]['spu_id'] = 1;
                }else{
                    $spuResult = $spuMod->where("suppliers_id = {$data['suppliers_id']}")->getField('spu_id',true);
                    foreach($spuResult as $key=>$val){
                        $couponData[$key]['coupon_num'] = $couponNum;
                        $couponData[$key]['spu_id'] = $val;
                    }
                }
                if($couponData){
                    $flagSpu = $couponSpuMod->addAll($couponData);
                    if(!$flagSpu){
                        $m->rollback();
                        $this->error('添加失败，请重新添加');
                    }
                }
            }else{
                $m->rollback();
                $this->error('添加失败，请重新添加');
            }
            $m->commit();
            $this->success('添加成功，正在跳转到优惠券列表...','?m=Coupon&a=index');
        }else{
            $suppliersResult = $suppliersMod->select();
            $this->assign('suppliers',$suppliersResult);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 修改按商品发放优惠券
    +----------------------------------------------------------
     */
    function editCouponBySpu(){
        $couponId = I('request.id',0,'intval');
        $postInfo = I('post.');
        $suppliersMod = M('suppliers');
        $couponMod = M('coupon');
        if(isset($_POST['dosubmit'])){
            $data = array();
            $data['id'] = $couponId;
            $data['name'] = htmlspecialchars(trim($postInfo['name']));
            $data['online_time'] = strtotime($postInfo['publishTime']);
            $data['get_start_time'] = strtotime($postInfo['sendTimeStart']);
            $data['get_end_time'] = strtotime($postInfo['sendTimeEnd']);
            $data['use_start_time'] = strtotime($postInfo['useTimeStart']);
            $data['use_end_time'] = strtotime($postInfo['useTimeEnd']);
            $data['surplus_num'] = intval($postInfo['num']);
            $data['rule_desc'] = htmlspecialchars(trim($postInfo['detail']));
            $m = M();
            $m->startTrans();
            $flag = $couponMod->save($data);
            if($flag){
                $m->commit();
                $this->success('修改成功，正在跳转到优惠券列表...','?m=Coupon&a=index');
            }else{
                $m->rollback();
                $this->error('修改失败，请重新修改');
            }
        }else{
            $couponResult = $couponMod->find($couponId);
            $suppliers = $suppliersMod->find($couponResult['suppliers_id']);
            $couponResult['suppliers'] = $suppliers['suppliers_name'];
            if($couponResult['suppliers_id']==0){
                $couponResult['suppliers'] = "全平台通用";
            }
            $couponResult['rule_money'] = number_format($couponResult['rule_money']/100,2);
            $couponResult['rule_free'] = number_format($couponResult['rule_free']/100,2);
            $this->assign('coupon',$couponResult);
            if($couponResult['type']==2){//按用户发放
                $couponUser = M('coupon_user');
                $where = array();
                $where['coupon_num'] = $couponResult['coupon_num'];
                $clientResult = $couponUser->where($where)->getField('user_id',true);
                if(count($clientResult)==1&&$clientResult[0]==0){//所有用户
                    $allClient = 1;
                    $_SESSION['clientSelectWhere'] = '';
                }else{
                    $users = implode(',',$clientResult);
                    $_SESSION['clientSelectWhere'] = "&keyword=$users";
                    $allClient = 0;
                }
                $this->assign('allClient',$allClient);
                $this->assign('count',count($clientResult));
                $this->assign('where',$_SESSION['clientSelectWhere']);
                $this->display("Coupon/editCouponByClient");exit;
            }
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 发布优惠券活动，状态变成published,发布时间改成当前时间
    +----------------------------------------------------------
     */

    function publish () {
        $couponId = I('request.id',0,'intval');//优惠券活动id
        $couponMod = M('coupon');
        $data['id'] = $couponId;
        $type = $couponMod->where($data)->field('type')->find();
        $data['status'] = "published";
        $time = time();
        if($type['type']==2){
            $data['get_start_time'] = $time;
            $data['online_time'] = $time;
        }elseif($type['type']==1){
            $data['online_time'] = $time;
        }
        $count = $couponMod->where('id = '.$couponId." AND status='published'")->count();
        if($count){
            $this->error('优惠券活动已经发布了，不要重复操作！');
        }else{
            $flag = $couponMod->save($data);
            if($flag!==false){
                $this->success('优惠券活动发布成功！',"?m=Coupon&a=index");
            }else{
                $this->error('操作失败！');
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 取消发布优惠券活动，状态变成locked
    +----------------------------------------------------------
     */

    function cancel_pub () {
        $couponId = I('request.id',0,'intval');//优惠券活动id
        $couponMod = M('coupon');
        $data['id'] = $couponId;
        $data['status'] = "locked";
        $count = $couponMod->where('id = '.$couponId." AND status='locked'")->count();
        if($count){
            $this->error('优惠券活动已经下架了，不要重复操作！');
        }else{
            $flag = $couponMod->save($data);
            if($flag!==false){
                $this->success('优惠券活动下架成功！',"?m=Coupon&a=index");
            }else{
                $this->error('操作失败！');
            }
        }
    }
    /**
    +----------------------------------------------------------
     * 删除优惠券活动
    +----------------------------------------------------------
     */
    function del () {
        $couponIds = I('request.id');//优惠券id
        $couponIdsMod = M('coupon');
        $couponUserMod = M('coupon_user');
        $where['id'] = $couponIds;
        $m = M();
        $m->startTrans();
        $couponNums = $couponIdsMod->where($where)->getField('coupon_num');
        $flag = $couponIdsMod->where($where)->delete();
        if($flag!==false){
            $map['coupon_num'] = array('in',$couponNums);
            $flagUser = $couponUserMod->where($map)->delete();
            if($flagUser!==false){
                $m->commit();
                $this->success('优惠券活动删除成功！',"?m=Coupon&a=index");
            }else{
                $m->rollback();
                $this->error('删除关联用户失败！');
            }
        }else{
            $m->rollback();
            $this->error('操作失败！');
        }
    }

    /**
    +----------------------------------------------------------
     * 新增按用户发放优惠券
    +----------------------------------------------------------
     */
    function couponByClient(){
        $_SESSION['clientSelectWhere'] = '';
        $couponNum = createCouponNum();
        $type = 2;//表示按用户发放
        $postInfo = I('post.');
        $suppliersMod = M('suppliers');
        $couponMod = M('coupon');
        $couponSpuMod = M('coupon_spu');
        $couponUserMod = M('coupon_user');
        $spuMod = M('spu');
        $clientMod = M('client');
        if(isset($_POST['dosubmit'])){
            $data = array();
            $data['name'] = htmlspecialchars(trim($postInfo['name']));
            $data['online_time'] = strtotime($postInfo['sendTimeStart']);
            $data['get_start_time'] = strtotime($postInfo['sendTimeStart']);
            $data['use_start_time'] = strtotime($postInfo['useTimeStart']);
            $data['use_end_time'] = strtotime($postInfo['useTimeEnd']);
            $data['add_time'] = time();
            $data['status'] = "scheduled";
            while($couponMod->where("coupon_num = '$couponNum'")->find()){//确保优惠券标识唯一
                $couponNum = createCouponNum();
            }
            $data['coupon_num'] = $couponNum;
            $data['send_num'] = 0;
            $range = $postInfo['range'];
            if($range){
                $data['suppliers_id'] = intval($postInfo['suppliers']);
            }else{
                $data['suppliers_id'] = 0;
            }
            $data['rule_desc'] = htmlspecialchars(trim($postInfo['detail']));
            $data['rule_money'] = floatval($postInfo['price'])*100;
            $data['rule_free'] = floatval($postInfo['cash'])*100;
            $data['type'] = $type;
            $m = M();
            $m->startTrans();
            $flag = $couponMod->add($data);
            if($flag){
                $couponData = array();
                if($data['suppliers_id']==0){
                    $couponData[0]['coupon_num'] = $couponNum;
                    $couponData[0]['spu_id'] = 1;//表示全平台
                }else{
                    $spuResult = $spuMod->where("suppliers_id = {$data['suppliers_id']}")->getField('spu_id',true);
                    foreach($spuResult as $key=>$val){
                        $couponData[$key]['coupon_num'] = $couponNum;
                        $couponData[$key]['spu_id'] = $val;
                    }
                }
                if($couponData){
                    $flagSpu = $couponSpuMod->addAll($couponData);
                    if(!$flagSpu){
                        $m->rollback();
                        $this->error('管理供应商添加失败，请重新添加');
                    }
                }
                $clientRange = $postInfo['people'];
                $clientArray = array();
                if($clientRange==0){//全部用户
                    //$clientArray[0]['coupon_num'] = $couponNum;
                    //$clientArray[0]['user_id'] = 0;//表示全部用户
                    $clientIds = $clientMod->getField('user_id',true);
                    foreach($clientIds as $key=>$val){
                        $clientArray[$key]['coupon_num'] = $couponNum;
                        $clientArray[$key]['user_id'] = $val;
                    }
                }else{
                    $where = "1 = 1";
                    $search = unserialize($_SESSION['clientSelectWhereArr']);
                    //组合搜索条件
                    $moneyLow = floatval($search['moneyLow']);
                    $moneyHigh = floatval($search['moneyHigh']);
                    if($moneyLow){
                        $where .= " AND money_pay >= $moneyLow";
                        if($moneyHigh){
                            $where .= " AND money_pay <= $moneyHigh";
                        }
                    }
                    $timeStart = htmlspecialchars(trim($search['time_start']));
                    $timeEnd = htmlspecialchars(trim($search['time_end']));
                    if($timeStart){
                        $timeStart = strtotime($timeStart);
                        $where .= " AND create_time >= $timeStart";
                        if($timeEnd){
                            $timeEnd = strtotime($timeEnd);
                            $where .= " AND create_time <= $timeEnd";
                        }
                    }
                    $loginStart = htmlspecialchars(trim($search['login_start']));
                    $loginEnd = htmlspecialchars(trim($search['login_end']));
                    if($loginStart){
                        $loginStart = strtotime($loginStart);
                        $where .= " AND last_login_time >= $loginStart";
                        if($loginEnd){
                            $loginEnd = strtotime($loginEnd);
                            $where .= " AND last_login_time <= $loginEnd";
                        }
                    }
                    $keywords = htmlspecialchars(trim($search['keyword']));
                    if($keywords){
                        $keywordArray = explode(',',$keywords);
                        $mobileArray = array();
                        $clientIdArray = array();
                        foreach($keywordArray as $val){
                            if(is_numeric($val)){
                                if(strlen($val)>=10){
                                    $mobileArray[] = $val;
                                }else{
                                    $clientIdArray[] = $val;
                                }
                            }
                        }
                        if($mobileArray){
                            $map['mobile'] = array('in',$mobileArray);
                            $userArray = $clientMod->where($map)->getField('user_id',true);
                            $clientIdArray = array_merge($clientIdArray,$userArray);
                        }
                        if($clientIdArray){
                            $clienteStr = implode(',',$clientIdArray);
                            $where .= " AND user_id in ($clienteStr)";
                        }
                    }
                    $clientIds = $clientMod->where($where)->getField('user_id',true);
                    foreach($clientIds as $key=>$val){
                        $clientArray[$key]['coupon_num'] = $couponNum;
                        $clientArray[$key]['user_id'] = $val;
                    }
                }
                if($clientArray){
                    $flagUser = $couponUserMod->addAll($clientArray);
                    if(!$flagUser){
                        $m->rollback();
                        $this->error('关联用户添加失败，请重新添加');
                    }
                }
            }else{
                $m->rollback();
                $this->error('优惠券添加失败，请重新添加');
            }
            $m->commit();
            $this->success('添加成功，正在跳转到优惠券列表...','?m=Coupon&a=index');
        }else{
            $suppliersResult = $suppliersMod->select();
            $this->assign('suppliers',$suppliersResult);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 修改按用户发放优惠券
    +----------------------------------------------------------
     */
    function editCouponByClient(){
        $postInfo = I('post.');
        $suppliersMod = M('suppliers');
        $couponMod = M('coupon');
        $couponSpuMod = M('coupon_spu');
        $couponUserMod = M('coupon_user');
        $spuMod = M('spu');
        $clientMod = M('client');
        if(isset($_POST['dosubmit'])){
            $data = array();
            $data['id'] =  intval($postInfo['id']);
            $data['name'] = htmlspecialchars(trim($postInfo['name']));
            $data['online_time'] = strtotime($postInfo['sendTimeStart']);
            $data['get_start_time'] = strtotime($postInfo['sendTimeStart']);
            $data['use_start_time'] = strtotime($postInfo['useTimeStart']);
            $data['use_end_time'] = strtotime($postInfo['useTimeEnd']);
            $data['surplus_num'] = intval($postInfo['num']);
            $data['rule_desc'] = htmlspecialchars(trim($postInfo['detail']));
            $m = M();
            $m->startTrans();
            $flag = $couponMod->save($data);
            if($flag!==false){
                $couponResult = $couponMod->find($data['id']);
                if($couponResult['status']!='published'){//不是发布状态的才可以修改优惠券关联的用户
                    $flagDel = $couponUserMod->where("coupon_num = '{$couponResult['coupon_num']}'")->delete();
                    if($flagDel===false){
                        $m->rollback();
                        $this->error('关联用户取消操作失败！！');
                    }
                    $clientRange = $postInfo['people'];
                    $clientArray = array();
                    if($clientRange==0){//全部用户
                        //$clientArray[0]['coupon_num'] = $couponResult['coupon_num'];
                        //$clientArray[0]['user_id'] = 0;//表示全部用户
                        $clientIds = $clientMod->getField('user_id',true);
                        foreach($clientIds as $key=>$val){
                            $clientArray[$key]['coupon_num'] = $couponResult['coupon_num'];
                            $clientArray[$key]['user_id'] = $val;
                        }
                    }else{
                        $where = "1 = 1";
                        $search = unserialize($_SESSION['clientSelectWhereArr']);
                        //组合搜索条件
                        $moneyLow = floatval($search['moneyLow']);
                        $moneyHigh = floatval($search['moneyHigh']);
                        if($moneyLow){
                            $where .= " AND money_pay >= $moneyLow";
                            if($moneyHigh){
                                $where .= " AND money_pay <= $moneyHigh";
                            }
                        }
                        $timeStart = htmlspecialchars(trim($search['time_start']));
                        $timeEnd = htmlspecialchars(trim($search['time_end']));
                        if($timeStart){
                            $timeStart = strtotime($timeStart);
                            $where .= " AND create_time >= $timeStart";
                            if($timeEnd){
                                $timeEnd = strtotime($timeEnd);
                                $where .= " AND create_time <= $timeEnd";
                            }
                        }
                        $loginStart = htmlspecialchars(trim($search['login_start']));
                        $loginEnd = htmlspecialchars(trim($search['login_end']));
                        if($loginStart){
                            $loginStart = strtotime($loginStart);
                            $where .= " AND last_login_time >= $loginStart";
                            if($loginEnd){
                                $loginEnd = strtotime($loginEnd);
                                $where .= " AND last_login_time <= $loginEnd";
                            }
                        }
                        $keywords = htmlspecialchars(trim($search['keyword']));
                        if($keywords){
                            $keywordArray = explode(',',$keywords);
                            $mobileArray = array();
                            $clientIdArray = array();
                            foreach($keywordArray as $val){
                                if(is_numeric($val)){
                                    if(strlen($val)>=10){
                                        $mobileArray[] = $val;
                                    }else{
                                        $clientIdArray[] = $val;
                                    }
                                }
                            }
                            if($mobileArray){
                                $map['mobile'] = array('in',$mobileArray);
                                $userArray = $clientMod->where($map)->getField('user_id',true);
                                $clientIdArray = array_merge($clientIdArray,$userArray);
                            }
                            if($clientIdArray){
                                $clienteStr = implode(',',$clientIdArray);
                                $where .= " AND user_id in ($clienteStr)";
                            }
                        }
                        $clientIds = $clientMod->where($where)->getField('user_id',true);
                        foreach($clientIds as $key=>$val){
                            $clientArray[$key]['coupon_num'] = $couponResult['coupon_num'];
                            $clientArray[$key]['user_id'] = $val;
                        }
                    }
                    if($clientArray){
                        $flagUser = $couponUserMod->addAll($clientArray);
                        if(!$flagUser){
                            $m->rollback();
                            $this->error('关联用户修改失败，请重新添加');
                        }
                    }
                }
            }else{
                $m->rollback();
                $this->error('优惠券修改失败，请重新修改');
            }
            $m->commit();
            $this->success('修改优惠券成功，正在跳转到优惠券列表...','?m=Coupon&a=index');
        }else{
            $this->display();
        }
    }
    /**
    +----------------------------------------------------------
     * 新增按用户发放优惠券,选择指定用户
    +----------------------------------------------------------
     */
    function selectClient(){
        $search = I('get.');
        $clientMod = M('client');
        $where = " 1 = 1";
        $getStr = "";
        $getArray = $search;
        $moneyLow = floatval($search['moneyLow'])*100;
        $moneyHigh = floatval($search['moneyHigh'])*100;
        if($moneyLow){
            $where .= " AND money_pay >= $moneyLow";
            $getStr .= "&moneyLow=$moneyLow";
            $this->assign('moneyLow',$moneyLow);
            if($moneyHigh){
                $where .= " AND money_pay <= $moneyHigh";
                $getStr .= "&moneyHigh=$moneyHigh";
                $this->assign('moneyHigh',$moneyHigh);
            }
        }
        $timeStart = htmlspecialchars(trim($search['time_start']));
        $timeEnd = htmlspecialchars(trim($search['time_end']));
        if($timeStart){
            $getStr .= "&time_start=$timeStart";
            $this->assign('time_start',$timeStart);
            $timeStart = strtotime($timeStart);
            $where .= " AND create_time >= $timeStart";
            if($timeEnd){
                $getStr .= "&time_end=$timeEnd";
                $this->assign('time_end',$timeEnd);
                $timeEnd = strtotime($timeEnd);
                $where .= " AND create_time <= $timeEnd";
            }
        }
        $loginStart = htmlspecialchars(trim($search['login_start']));
        $loginEnd = htmlspecialchars(trim($search['login_end']));
        if($loginStart){
            $getStr .= "&login_start=$loginStart";
            $this->assign('login_start',$loginStart);
            $loginStart = strtotime($loginStart);
            $where .= " AND last_login_time >= $loginStart";
            if($loginEnd){
                $getStr .= "&login_end=$loginEnd";
                $this->assign('login_end',$loginEnd);
                $loginEnd = strtotime($loginEnd);
                $where .= " AND last_login_time <= $loginEnd";
            }
        }
        $keywords = htmlspecialchars(trim($search['keyword']));
        if($_SESSION['clientSelectWhere']){
            if(!$keywords){
                $keywords = str_replace("&keyword=", "", $_SESSION['clientSelectWhere']);
            }
        }
        if($keywords){
            $keywordArray = explode(',',$keywords);
            $mobileArray = array();
            $clientArray = array();
            foreach($keywordArray as $val){
                if(is_numeric($val)){
                    if(strlen($val)>=10){
                        $mobileArray[] = $val;
                    }else{
                        $clientArray[] = $val;
                    }
                }
            }
            if($mobileArray){
                $map['mobile'] = array('in',$mobileArray);
                $userArray = $clientMod->where($map)->getField('user_id',true);
                $clientArray = array_merge($clientArray,$userArray);
            }
            if($clientArray){
                $clienteStr = implode(',',$clientArray);
                $where .= " AND user_id in ($clienteStr)";
            }
            $getStr .= "&keyword=$keywords";
            $this->assign('keyword',$keywords);
        }
        if(isset($_POST['dosubmit'])){
            $this->success('选择用户成功','selectClient');
        }else{
            $count = $clientMod->where($where)->count();
            $_SESSION['clientSelectCount'] = $count;
            $_SESSION['clientSelectWhere'] = $getStr;
            $_SESSION['clientSelectWhereArr'] = serialize($getArray);
            import("ORG.Util.Page");
            $pageSize = 10;
            $p = new Page($count, $pageSize);
            $clientResult = $clientMod->where($where)->limit($p->firstRow.','.$p->listRows)->order('create_time desc')->select();
            foreach($clientResult as $k=>$v){
                $clientResult[$k]['money_pay'] = $v['money_pay']/100;
            }
            $this->assign('client',$clientResult);
            $page = $p->show();
            $this->assign('page',$page);
            $this->assign('count',$count);
            $this->display();
        }
    }
    /**
    +----------------------------------------------------------
     * 获取选择用户
    +----------------------------------------------------------
     */
    function getSelectClient(){
        $clientArray['count'] = $_SESSION['clientSelectCount'];
        $clientArray['where'] = $_SESSION['clientSelectWhere'];
        echo json_encode($clientArray);
    }

    /**
    +----------------------------------------------------------
     * 批量发布促销活动，状态变成published,发布时间改成当前时间
    +----------------------------------------------------------
     */

    function allPublish () {
        $CouponId = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $CouponMod = M('coupon');
        $scheduledCoupon = $CouponMod->where("id in ($CouponId) AND status = 'scheduled'")->select();
        $time = time();
        if($scheduledCoupon){
            $m = M();
            $m->startTrans();
            foreach($scheduledCoupon as $val){
                if($val['type']==1){
                    $save = array();
                    $save['status'] = "published";
                    $save['online_time'] = $time;
                }elseif($val['type']==2){
                    $save = array();
                    $save['status'] = "published";
                    $save['online_time'] = $time;
                    $save['get_start_time'] = $time;
                }
                $save['id'] = $val['id'];
                $flag = $CouponMod->save($save);
                if($flag!==false){

                }else{
                    $this->error("操作失败！！！");
                    $m->rollback();
                }
            }
            $m->commit();
            $this->success("批量上架成功！！！","?m=Coupon&a=index");
        }else{
            $this->success('没有符合上架条件的优惠券活动！！！','?m=Coupon&a=index');
        }
    }


    /**
    +----------------------------------------------------------
     * 批量下架促销活动，状态变成locked
    +----------------------------------------------------------
     */

    function allCancel () {
        $couponId = I('request.id',0,'trim,htmlspecialchars');//促销活动id
        $couponMod = M('coupon');
        $scheduledCoupon = $couponMod->where("id in ($couponId) AND status = 'published'")->getField('id',true);
        if($scheduledCoupon){
            $save = array();
            $save['status'] = "locked";
            $where = array();
            $where['id'] = array('in',$scheduledCoupon);
            $flag = $couponMod->where($where)->save($save);
            if($flag!==false){
                $this->success("批量下架成功！！！","?m=Coupon&a=index");
            }else{
                $this->error("操作失败！！！");
            }
        }else{
            $this->success('没有符合下架条件的优惠券活动！！！','?m=Coupon&a=index');
        }
    }

    /**
    +----------------------------------------------------------
     * 新增优惠券礼包(里面包含多种按用户推送的优惠券)
    +----------------------------------------------------------
     */
    function couponGiftAdd(){
        if(isset($_POST['dosubmit'])){
            $giftMod = M('coupon_gift');
            $m = M();
            $m->startTrans();
            $giftRelationMod = M('cou_gift_relation');
            $couponMod = M('coupon');
            $name = I('post.name','','trim,htmlspecialchars');
            $image = I('post.file','','trim,htmlspecialchars');
            $detail = I('post.detail','','trim,htmlspecialchars');
            $path = save_img64($image[0]);//上传配图
            if($path){
                $res = up_yun($path);
                $img['img_url'] = $res['img_src'];
                $img['img_w']=$res['img_w'];
                $img['img_h']=$res['img_h'];
                $result = @unlink($path);
            }
            $imgSer = serialize($img);//序列化图片地址和宽高
            $data['image_src'] = $imgSer;
            $data['title'] = $name;
            $data['detail'] = $detail;
            $data['add_time'] = time();
            $flag = $giftMod->add($data);
            if($flag){
                $couponList = $_SESSION['couponList'];
                $couponList = explode(',',$couponList);
                $resultData = array();
                foreach($couponList as $k=>$v){
                    if($v){
                        $insertArray = array();
                        $coupon = $couponMod->field('coupon_num')->find($v);
                        $insertArray['coupon_num'] = $coupon['coupon_num'];
                        $insertArray['gift_id'] = $flag;
                        $insertArray['num'] = I("post.{$v}",1,'intval');
                        $resultData[] = $insertArray;
                    }
                }
                $flag = $giftRelationMod->addAll($resultData);
                if($flag){
                    $this->success("优惠券礼包添加成功！");
                    $m->commit();
                }else{
                    $this->error("优惠券礼包与优惠券关系添加失败！");
                    $m->rollback();
                }
            }else{
                $this->error("优惠券礼包添加失败！");
                $m->rollback();
            }
        }else{
            $_SESSION['couponList'] = "";
            $this->display();
        }
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
        $where['type']   = 2;
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
        if(I('post.chk','','')){
            $ids = $_POST['chk'];
            if($_SESSION['couponList']){
                $_SESSION['couponList'] = ",".$_SESSION['couponList'];
            }
            $_SESSION['couponList'] = implode(',',$ids).$_SESSION['couponList'];
            exit;
        }
        if(I('get.id','','')){
            $ids = $_GET['id'];
            $arrayDel = explode(',',$_SESSION['couponList']);
            foreach($arrayDel as $key=>$val){
                if($val==$ids){
                    unset($arrayDel[$key]);
                    break;
                }
            }
            $_SESSION['couponList'] = implode(',',$arrayDel);
        }
        if(I('get.gift','','')){
            $giftId = I('get.gift','','');
            $giftRelationMod = M('cou_gift_relation');
            $couponMod = M('coupon');
            $gifres = $giftRelationMod->where("gift_id = $giftId")->select();
            foreach($gifres as $k=>$v){
                $tmpRes = $couponMod->where("coupon_num = '{$v['coupon_num']}'")->find();
                $numArray[$tmpRes['id']] = $v['num'];
            }
        }
        $ids = $_SESSION['couponList'];
        $ids = explode(',',$ids);
        foreach($ids as $key=>$val){
            if( !$val )
                unset( $ids[$key] );
        }
        $couponModel = M('coupon');
        $ids = implode(',',$ids);
        $couponResult = $couponModel->where("id in ($ids)")->order("field(`id`,$ids)")->select();
        $str = '';
        foreach($couponResult as $key=>$val){
            $num = 1;
            if(isset($numArray[$val['id']])){
                $num = $numArray[$val['id']];
            }
            $td = "<td align='center'>{$val['id']}</td>";
            $td .= "<td align='center'>{$val['name']}</td>";
            $td .= "<td align='center'><input name='".$val['id']."' value='".$num."' size='10'></td>";
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

    /**
    +----------------------------------------------------------
     * 编辑优惠券大礼包礼包
     * @author:hhf
    +----------------------------------------------------------
     */
    function editCouponGift(){
        $giftMod = M('coupon_gift');
        $giftRelationMod = M('cou_gift_relation');
        $couponMod = M('coupon');
        if(isset($_POST['dosubmit'])){
            $giftId = I('post.id',0,'intval');
            $giftMod = M('coupon_gift');
            $m = M();
            $m->startTrans();
            $giftRelationMod = M('cou_gift_relation');
            $couponMod = M('coupon');
            $name = I('post.name','','trim,htmlspecialchars');
            $image = I('post.file','','trim,htmlspecialchars');
            $detail = I('post.detail','','trim,htmlspecialchars');
            if($image[0]){
                $path = save_img64($image[0]);//上传配图
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
            $data['title'] = $name;
            $data['detail'] = $detail;
            $data['add_time'] = time();
            $data['id'] = $giftId;
            $flag = $giftMod->save($data);
            if($flag!==false) {
                $couponList = $_SESSION['couponList'];
                $couponList = explode(',', $couponList);
                $resultData = array();
                foreach ($couponList as $k => $v) {
                    if ($v) {
                        $insertArray = array();
                        $coupon = $couponMod->field('coupon_num')->find($v);
                        $insertArray['coupon_num'] = $coupon['coupon_num'];
                        $insertArray['gift_id'] = $giftId;
                        $insertArray['num'] = I("post.{$v}", 1, 'intval');
                        $resultData[] = $insertArray;
                    }
                }
                $flag = $giftRelationMod->where("gift_id = $giftId")->delete();
                if($flag===false){
                    $this->error("优惠券礼包与优惠券关系解除失败！");
                    $m->rollback();
                }
                $flag = $giftRelationMod->addAll($resultData);
                if ($flag) {
                    $this->success("优惠券礼包添加成功！","?m=Coupon&a=giftList");
                    $m->commit();
                } else {
                    $this->error("优惠券礼包与优惠券关系添加失败！");
                    $m->rollback();
                }
            }else{
                $this->error("优惠券礼包修改失败！");
                $m->rollback();
            }
        }else{
            $giftId = I('get.id',0,'intval');
            $giftResult = $giftMod->find($giftId);
            $image = unserialize($giftResult['image_src']);
            $imageSrc = C('LS_IMG_URL').$image['img_url'];
            $giftResult['img'] = $imageSrc;
            $this->assign('giftResult',$giftResult);
            $gift_relation = $giftRelationMod->where("gift_id = $giftId")->getField('coupon_num',true);
            $gift_relation = "'".implode("','",$gift_relation)."'";
            $ids = $couponMod->where("coupon_num in ($gift_relation)")->getField('id',true);
            $_SESSION['couponList'] = implode(',',$ids);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 将优惠券设为分享可用
     * @author:hhf
    +----------------------------------------------------------
     */
    function shareUsed(){
        $shareId = I('get.id',0,'intval');
        if($shareId){
            $giftMod = M('coupon_gift');
            $data = array(
                'id'=>$shareId,
                'status'=>1
            );
            $giftMod->save($data);
            $otherData = array(
                'status'=>0
            );
            $giftMod->where("id <> $shareId")->save($otherData);
        }
        $this->success("设置为分享可用成功！","?m=Coupon&a=giftList");
    }
    /**
    +----------------------------------------------------------
     * 优惠券大礼包礼包 弹窗页 添加操作
     * @author:nangua
    +----------------------------------------------------------
     */
    public function popAdd(){
        // 添加了数据
        if($_POST){
            $name = I('post.name','','trim,htmlspecialchars');
            $start_time = I('post.sendTimeStart','','trim,htmlspecialchars,strtotime');
            $end_time = I('post.sendTimeEnd','','trim,htmlspecialchars,strtotime');
            $link_id  = I('post.link_id','','trim,intval');
            $pop_mod = M('pop');
            $now = time();
            if($start_time < $now){
                $this->error('开始时间不能小于当前时间');
            }

            $data = array(
                "name"          => $name,
                "start_time"    => $start_time,
                "end_time"      => $end_time,
                "create_time"   => time(),
                "status"        => "scheduled",
                "link_to"       => 1,
                "link_id"       => $link_id
            );
            if($pop_mod->add($data)){
                $this->success("添加成功！","?m=Coupon&a=popIndex");
                die;
            }else{
                $this->error('操作失败');
            }
        }
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 弹窗查询操作
     * @author:cxh
    +----------------------------------------------------------
     */


    /**
    +----------------------------------------------------------
     * 弹窗编辑操作
     * @author:cxh
    +----------------------------------------------------------
     */

    public function popEdit(){
        $id =I('get.id','','trim,intval');

        $pop_mod = M('pop');
        if(isset($_POST['dosubmit'])){

            $name =I('post.name','','trim,htmlspecialchars');
            $start_time = I('post.sendTimeStart','','trim,htmlspecialchars,strtotime');
            $end_time = I('post.sendTimeEnd','','trim,htmlspecialchars,strtotime');
            $link_id = I('post.link_id','','trim,intval');

            $data = array(
                "name"          => $name,
                "start_time"    => $start_time,
                "end_time"      => $end_time,
                "link_to"       => 1,
                "link_id"       => $link_id
            );

            if($data['start_time']>$data['end_time'] ){
                $this->error('开始时间不能大于结束时间');
            }


            if(false!==$pop_mod->where('id='.$_POST['id'])->save($data)){
                $this->success("修改成功！","?m=Coupon&a=popIndex");
                die;
            }else{
                $this->error('操作失败');
                exit();
            }
        }

        $a = $pop_mod->where('id='.$_GET['id'])->find();
        $a['start_time'] = date("Y-m-d H:i", $a['start_time']);
        $a['end_time'] =  date("Y-m-d H:i", $a['end_time']);


        $this->assign('name',$a['name']);
        $this->assign('sendTimeStart',$a['start_time']);
        $this->assign('sendTimeEnd',$a['end_time']);
        $this->assign('link_id',$a['link_id']);
        //$this->assign('id', $id);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 优惠券大礼包礼包 弹窗页 上架操作
     * @author:nangua
    +----------------------------------------------------------
     */
    public function publishPop(){
        $id = I('get.id',0,'intval');
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $ls_pop = C('LS_POP');
        $redis->rm($ls_pop);
        $redis->close;
        $pop_mod = M("pop");
        $pop_data = $pop_mod->where("id = $id")->find();
        $time = time();
        if($pop_data['end_time'] < $time ){
            $this->error('上架的弹窗已过期，请先修改时间');
        }
        // 先让发布状态的弹窗下架
        $pop_mod->startTrans();// 开启事务
        $is_ok = $pop_mod->where("status ='published' ")->setField("status","locked");
        if($is_ok !== flase){

            // 下一步启动该弹窗
            $data = array(
                "status"        => "published",
                "start_time"    => time()
            );
            if($pop_mod->where("id = {$pop_data['id']} ")->save($data)){
                $pop_mod->commit();
                $this->success("上架成功！","?m=Coupon&a=popIndex");
            }else{
                $pop_mod->rollback();
                $this->error('操作失败');
            }
        }else{
            $pop_mod->rollback();
            $this->error('操作失败');
        }

    }
    /**
    +----------------------------------------------------------
     * 优惠券大礼包礼包 弹窗页 下架操作
     * @author:nangua
    +----------------------------------------------------------
     */
    public function cancelPop(){
        $id = I('get.id',0,'intval');
        $options = C('REDIS_CONF');
        $redis = Cache::getInstance('Redis',$options);
        $ls_pop = C('LS_POP');
        $redis->rm($ls_pop);
        $redis->close;
        $pop_mod = M("pop");
        $pop_data = $pop_mod->where("id = $id")->find();
        // 下一步启动该弹窗
        if(false!==$pop_mod->where("id = {$pop_data['id']} ")->setField("status","locked")){
            $this->success("下架成功！","?m=Coupon&a=popIndex");
        }else{
            $this->error('操作失败');
        }
    }
    /**
    +----------------------------------------------------------
     * 弹窗搜索
     * @author:cxh
    +----------------------------------------------------------
     */
    public function popIndex(){
        $timeStart = I('get.time_start','','trim,htmlspecialchars,strtotime');//添加时间区间开始
        $search['time_start'] = $timeStart;

        $timeEnd = I('get.time_end','','trim,htmlspecialcharsm,strtotime');//添加时间区间结束
        $search['time_end'] = $timeEnd;

        $keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
        $search['keyword'] = $keyword;
        $status = I('get.status','','trim,htmlspecialchars');//状态
        $search['status'] = $status;

        $this->assign('search',$search);
        $where = " 1=1 ";
        if($keyword){
            $where .= " AND name LIKE '%" . $keyword . "%'";
        }
        if($timeStart){
            $where .= " AND create_time >= $timeStart ";
        }
        if($timeEnd){
            $where .= " AND create_time <= $timeEnd ";
        }
        if($status){
            $where .= " AND status = '$status'";
        }


        $pop_mod = M('pop');
        $count = $pop_mod->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $popResult = $pop_mod->where($where)->limit($p->firstRow.','.$p->listRows)->order('id desc')->select();
        foreach($popResult as $key=>$val){
            switch($val['status']){
                case 'scheduled':
                    $popResult[$key]['statusCH'] = "<font color='green'>定时</font>";
                    break;
                case 'published':
                    $popResult[$key]['statusCH'] = "<font color='red'>发布</font>";
                    break;
                case 'locked':
                    $popResult[$key]['statusCH'] = "<font color='blue'>下架</font>";
                    break;
            }
        }
        $page = $p->show();
        $this->assign('page',$page);
        $this->assign('pop',$popResult);
        $this->display();
    }
}