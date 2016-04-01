<?php

class buyerAction extends BaseAction{
    //买家列表
    function index() {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $type = isset($_GET['pf_type']) ? intval($_GET['pf_type']) : '';
        $status = isset($_GET['status']) ? intval($_GET['status']) : '';
        $startTime = isset($_GET['time_start']) ? strtotime($_GET['time_start']) : 0;
        $endTime = isset($_GET['time_end']) ? strtotime($_GET['time_end']) : 0;
        //查询条件
        $where = '1=1';
        if($keyword) {
            $preg = "/^\d{1,3}$/";
            if(!preg_match($preg,$keyword)){
                //说明是昵称
                $where .= " AND nickname LIKE '%$keyword%'";
                $this->assign('keyword', $keyword);
            }else{
                // 说明数字
                $where .= " AND user_id = $keyword";
                $this->assign('keyword', $keyword);
            }
        }
        if($type) {
            $where .= " AND pf_type = $type";
            $this->assign('pf_type', $type);
        }
        if($status) {
            $where .= " AND status = 'active' ";
            $this->assign('status', $status);
        }
        if($startTime) {
            $where .= " AND create_time>='" . $startTime . "'";
            $this->assign('time_start', date('Y-m-d',$startTime));
        }
        if($endTime) {
            $where .= " AND create_time<='" . $endTime . "'";
            $this->assign('time_end',date('Y-m-d',$endTime));
        }

        $buyer = M('client');
        import("ORG.Util.Page");
        $count = $buyer->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $buyerData = $buyer->where($where)->order('create_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        for($i=0; $i<count($buyerData); $i++) {
            switch($buyerData[$i]['pf_type']) {
                case '0' :
                    $buyerData[$i]['pf_type'] = '其他';
                    break;
                case '1' :
                    $buyerData[$i]['pf_type'] = 'QQ账号';
                    break;
                case '2' :
                    $buyerData[$i]['pf_type'] = '微信';
                    break;
                case '3' :
                    $buyerData[$i]['pf_type'] = '新浪微博';
                    break;
                case '4' :
                    $buyerData[$i]['pf_type'] = '本地注册';
                    break;
            }
            switch($buyerData[$i]['status']) {
                case 'active' :
                    $buyerData[$i]['status'] = '活跃';
                    break;
                case 'inactive' :
                    $buyerData[$i]['status'] = '不活跃';
                    break;
                case 'locked' :
                    $buyerData[$i]['status'] = '锁定';
                    break;
            }
        }
        $this->assign('page', $show);
        $this->assign("buyer_list",$buyerData);
        $this->display();
    }
    //查看买家
    function view() {
        $uid = isset($_GET['id']) ? $_GET['id'] : 0;

        $buyer = M('client');
        $order = M('order');
        $suborder = M('sub_order');
        $address = M('address');

        //查收货地址
        $addressList = $address->where('user_id='.$uid)->select();

        $data = $buyer->where('user_id='.$uid)->find();
        switch($data['pf_type']) {
            case '0' :
                $data['pf_type'] = '其他';
                break;
            case '1' :
                $data['pf_type'] = 'QQ账号';
                break;
            case '2' :
                $data['pf_type'] = '微信';
                break;
            case '3' :
                $data['pf_type'] = '新浪微博';
                break;
            case '4' :
                $data['pf_type'] = '本地注册';
                break;
        }
        switch($data['status']) {
            case 'active' :
                $data['status'] = '活跃';
                break;
            case 'inactive' :
                $data['status'] = '不活跃';
                break;
            case 'locked' :
                $data['status'] = '锁定';
                break;
        }

        $orderData = $order->where('user_id='. $uid)->select();

        import("ORG.Util.Page");
        $count = $order->where('user_id='. $uid)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $orderData = $order->where('user_id='. $uid)->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($orderData as $v) {
            $subWhere['order_sn'] = $v['order_sn'];
            $suborderData = $suborder->where($subWhere)->select();
            foreach($suborderData as $i) {
                switch($i['status']) {
                    case '0' : //下单
                        $nearTime = $i['add_time'];
                        break;
                    case '1' : //付款
                        $nearTime = $i['pay_time'];
                        break;
                    case '2' : //发货
                        $nearTime = $i['send_time'];
                        break;
                    case '3' : //确认收货
                        $nearTime = $i['finish_time'];
                        break;
                    default :
                        $nearTime = $i['finish_time'];
                        break;
                }
                //查订单
                $orderList[] = array(
                    'user_id'    => $uid,
                    'spu_id'     => $i['spu_id'],
                    'order_id'   => $i['sub_order_sn'],
                    'suppliers'  => $i['suppliers_name'],
                    'price'      => $i['sum_price']/100,
                    'status'     => $i['status'],
                    'add_time'   => date('Y-m-d h:m:s',$i['add_time']),
                    'near_time'  => date('Y-m-d h:m:s',$nearTime)
                );
            }
        }

        $this->assign('order_list',$orderList);
        $this->assign('address_list',$addressList);
        $this->assign('data',$data);
        $this->assign('page',$show);
        $this->display();
    }

    //锁定&解锁
    public function changeStatus() {
        $id = $_POST['id'];
        $status = $_POST['status'];
        if(is_array($id)) {
            $where['user_id'] = array('in',$id);
        } else {
            $where['user_id'] = $id;
        }
        $saveData['status'] = $status;
        $topicRelation = M('client');
        $topicRelation->where($where)->save($saveData);
        $res['flag'] = $topicRelation ? true : false;
        $this->ajaxReturn($res,'json');
    }

    //删除
    public function del() {

    }
}
?>