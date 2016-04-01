<?php

class aftersellAction extends BaseAction{

    public function index() {
        $query     = $_GET['query'];
        $keyword   = isset($_GET['keyword'])    ? trim($_GET['keyword'])         : '';
        $afterSell     = isset($_GET['afterSell'])      ? trim($_GET['afterSell'])           : 0;
        $status    = isset($_GET['status'])     ? trim($_GET['status'])          : '';
        $startTime = isset($_GET['time_start']) ? strtotime($_GET['time_start']) : 0;
        $endTime   = isset($_GET['time_end'])   ? strtotime($_GET['time_end'])   : 0;

        $orderModel = M('order');
        $subOrderModel = M('sub_order');
        $spuModel = M('spu');
        $spuImageModel = M('spu_image');
        $spuAttrModel = M('spu_attr');
        $typeAttrModel = M('type_attr');
        $clientModel = M('client');
        $refundMod = M('refund');
        $where = " 1=1 ";
        if($startTime){
            //$where['add_time'] = array('egt',$startTime);
            $where .= " AND add_time > $startTime";
            $this->assign('time_start',date('Y-m-d H:i',$startTime));
        }
        if($endTime){
            //$where['add_time'] = array('elt',$endTime);
            $where .= " AND add_time < $endTime";
            $this->assign('time_end',date('Y-m-d H:i',$endTime));
        }
        if($status){
            //$where['type'] = $status;
            $where .= " AND type = $status";
            $this->assign('status',$status);
        }
        $where .= " AND status = $afterSell";
        $this->assign('afterSell',$afterSell);
        $refundResult = $refundMod->where($where)->select();
        $refundOrderIds = array();
        $refundInfo = array();
        foreach($refundResult as $k=>$val){
            $refundOrderIds[] = $val['sub_order_sn'];
            $refundInfo[$val['sub_order_sn']] = $val;
        }
        $where = array();
        $where['sub_order_sn'] = array('in',$refundOrderIds);
        //$where['status'] = array('in','5,6');
        $key = substr($keyword,0,1);
        if(($key=="E") || ($key == 'F')){
            $where['order_sn'] = $keyword;
        }
        if(is_numeric($keyword)){
            $map['mobile'] = $keyword;
            $orderResult = $orderModel->where($map)->field('order_sn')->find();
            $where['order_sn'] = $orderResult['order_sn'];
        }
        $this->assign('keyword',$keyword);
        import("ORG.Util.Page");
        $count = $subOrderModel->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $subOrderData = $subOrderModel->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('add_time desc')->select();

        foreach($subOrderData as $v){
            $subWhere['order_sn'] = $v['order_sn'];
            $orderData = $orderModel->where($subWhere)->find();

            $spuWhere['spu_id'] = $v['spu_id'];
            $imageData = $spuImageModel->where($spuWhere)->find();
            $img = unserialize($imageData['images_src']);
            $spuImage = C('LS_IMG_URL').$img['img_url'];

            $spuData = $spuModel->where($spuWhere)->find();
            $spuAttrData = $spuAttrModel->where($spuWhere)->find();
            $spuAttrKeyData = $typeAttrModel->where('attr_id='.$spuAttrData['attr_id'])->find();
            $clientData = $clientModel->where('user_id='.$orderData['user_id'])->find();

            switch($refundInfo["{$v['sub_order_sn']}"]['status']) {
                case '0' :
                    $statusText = '售后待审核';
                    break;
                case '1' :
                    $statusText = '审核通过,支付宝退款中';
                    break;
                case '2' :
                    $statusText = '审核不通过';
                    break;
                case '3' :
                    $statusText = '交易关闭';
                    break;
                case '4' :
                    $statusText = '等待批量退款';
                    break;
            }

            $orderArr[] = array(
                'spu_name'       => $spuData['spu_name'],
                'spu_image'      => $spuImage,
                'consignee'      => $orderData['consignee'],
                'user_id'        => $orderData['user_id'],
                'user_nickname'  => $clientData['nickname'],
                'mobile'         => $orderData['mobile'],
                'address'        => $orderData['address'],
                'province'       => $orderData['province'],
                'city'           => $orderData['city'],
                'proper'         => $orderData['proper'],
                'street'         => $orderData['street'],
                'attr_key'       => $spuAttrKeyData['attr_name'],
                'attr_value'     => $spuAttrData['attr_value'],
                'sub_id'         => $v['sub_id'],
                'sum_price'      => $v['sum_price']/100,
                'shipping_fee'   => $v['shipping_fee']/100,
                'sub_order_sn'   => $v['sub_order_sn'],
                'order_sn'       => $v['order_sn'],
                'suppliers_name' => $v['suppliers_name'],
                'shipping_com'   => $v['shipping_com'],
                'shipping_id'    => $v['shipping_id'],
                'add_time'       => $v['add_time'],
                'postscript'     => $v['postscript'],
                'status'         => $v['status'],
                'order_status'   => $refundInfo["{$v['sub_order_sn']}"]['order_status'],
                'status_text'    => $statusText,
                'pay_sn'         => $orderData['pay_sn'],
                'why'            => $refundInfo["{$v['sub_order_sn']}"]['reason'],
            );
        }

        $this->assign('order_list',$orderArr);
        $this->assign('page',$show);
        $this->display();
    }
    /**
     * +----------------------------------------------------------
     * 售后备注追加
     * @author:hhf
     * +----------------------------------------------------------
     */
    function remark(){
        $orderSn = I('request.order_sn','','trim');
        $subMod = M('sub_order');
        $refundMod = M('refund');
        if(isset($_POST['dosubmit'])){
            $remark = I('post.info','','trim,htmlspecialchars');//追加的备注
            $save = array();
            if($orderSn){
                $where['sub_order_sn'] = $orderSn;
            }
            $save['remark'] = $remark;
            $flag = $refundMod->where($where)->save($save);
            if($flag!==false){
                $this->success("备注更新成功！",'', '', 'remark');
            }

        }else{
            $remark = $refundMod->where("sub_order_sn = '$orderSn'")->field('remark')->find();
            $this->assign('remark',$remark['remark']);
            $this->assign('orderSn',$orderSn);
            $this->display();
        }

    }
    /**
     * +----------------------------------------------------------
     * 不同意售后
     * @author:hhf
     * +----------------------------------------------------------
     */
    function refuse(){
        $orderSn = I('request.order_sn','','trim');
        $name = I('request.name','','trim');
        $why = I('request.why','','trim');
        $subMod = M('sub_order');
        $refundMod = M('refund');
        if(isset($_POST['dosubmit'])){
            $remark = I('post.info','','trim,htmlspecialchars');//追加的备注
            $save = array();
            if($orderSn){
                $where['sub_order_sn'] = $orderSn;
                $where['status'] = 0;
            }
            $save['remark'] = $remark;
            $save['status'] = 2; //拒绝售后
            $res = $refundMod->where($where)->find();
            $flag = $refundMod->where($where)->save($save);
            if($flag!==false){
                unset($where['status']);
                $save = array();
                $save['status'] = $res['order_status'];
                $subMod->where($where)->save($save);
                $this->success("订单原路返回！",'', '', 'refuse');
            }
        }else{
            $remark = $refundMod->where("sub_order_sn = '$orderSn'")->field('remark')->find();
            $this->assign('remark',$remark['remark']);
            $this->assign('orderSn',$orderSn);
            $this->assign('name',$name);
            $this->assign('why',$why);
            $this->display();
        }
    }


    /**
     * +----------------------------------------------------------
     * 加入批量退款（这时候还没有开始退款，只是做好了退款钱的准备）
     * @author:hhf
     * +----------------------------------------------------------
     */
    function allRefund(){
        $refundMod = M('refund');
        $orderSn = I('request.orderSn','','trim,htmlspecialchars');
        $num = I('request.num','','trim,htmlspecialchars');
        $money = I('request.money','','trim,htmlspecialchars');
        $name = I('request.name','','trim,htmlspecialchars');
        if(isset($_POST['dosubmit'])){
            $where['sub_order_sn'] = $orderSn;
            $where['status'] = 0;//待审核
            $data['status'] = 4;//转入批量退款
            $data['refund_money'] = $money*100;//转换单位为分
            $flag = $refundMod->where($where)->save($data);
            if($flag!==false){
                echo "<script>alert('已经加入到批量退款队列');</script>";
                $this->success("操作完成！",'', '', 'allRefund');
            }else{
                echo "<script>alert('加入到批量退款队列失败，请重试');</script>";
                $this->error("操作失败！",'', '', 'allRefund');
            }

        }else{
            $this->assign('orderSn',$orderSn);
            $this->assign('name',$name);
            $this->assign('money',$money);
            $this->assign('num',$num);
            $this->display();
        }
    }


    /**
     * +----------------------------------------------------------
     * 退款情况
     * @author:hhf
     * +----------------------------------------------------------
     */
    function refundResult(){
        $refundMod = M('refund');
        $orderSn = I('request.orderSn','','trim,htmlspecialchars');
        $num = I('request.num','','trim,htmlspecialchars');
        $money = I('request.money','','trim,htmlspecialchars');
        $name = I('request.name','','trim,htmlspecialchars');
        if(isset($_POST['dosubmit'])){
            $where['sub_order_sn'] = $orderSn;
            if($_SESSION['WIDbatch_no']){
                $data['status'] = 1;
                $data['refund_money'] = $money*100;//转换单位为分
                $data['batch_no'] = $_SESSION['WIDbatch_no'];
                $_SESSION['WIDbatch_no'] = "";
                $refundMod->where($where)->save($data);
            }
            $_SESSION['WIDbatch_no'] = "";
            $this->success("操作完成！",'', '', 'refundResult');
        }else{
            $this->assign('orderSn',$orderSn);
            $this->assign('name',$name);
            $this->assign('money',$money);
            $this->assign('num',$num);
            $this->display();
        }
    }
    /**
     * +----------------------------------------------------------
     * 点击同意退款
     * @author:hhf
     * +----------------------------------------------------------
     */
    function agreeRefund(){
        $refundMod = M('refund');
        $orderSn = I('request.orderSn','','trim,htmlspecialchars');
        $where['sub_order_sn'] = $orderSn;
        if($_SESSION['WIDbatch_no']){
            $data['status'] = 1;
            $data['batch_no'] = $_SESSION['WIDbatch_no'];
            $_SESSION['WIDbatch_no'] = "";
            $refundMod->where($where)->save($data);
        }
        $_SESSION['WIDbatch_no'] = "";
        $this->success("操作完成！",'?m=Aftersell&a=index&afterSell=1','1');
    }

    /**
     * +----------------------------------------------------------
     * 取消退款，订单回归审核状态
     * @author:hhf
     * +----------------------------------------------------------
     */
    function cancelRefund(){
        $refundMod = M('refund');
        $orderSn = I('get.orderSn','','trim,htmlspecialchars');
        $where['sub_order_sn'] = $orderSn;
        $where['status'] = 1;
        $data['status'] = 0;
        $flag = $refundMod->where($where)->save($data);
        if($flag){
            $this->success("订单退款取消成功！","?m=Aftersell&a=index");
        }else{
            $this->error("订单退款取消失败！");
        }
    }

    /**
     * +----------------------------------------------------------
     * 再次确认退款金额
     * @author:hhf
     * +----------------------------------------------------------
     */
    function moneyAgain(){
        $refundMod = M('refund');
        $orderSn = I('request.orderSn','','trim,htmlspecialchars');
        $num = I('request.num','','trim,htmlspecialchars');
        $money = I('request.money','','trim,htmlspecialchars');
        $name = I('request.name','','trim,htmlspecialchars');
        if(isset($_POST['dosubmit'])){
            $where['sub_order_sn'] = $orderSn;
            $where['status'] = 4;//待审核
            $data['refund_money'] = $money*100;//转换单位为分
            $flag = $refundMod->where($where)->save($data);
            if($flag!==false){
                echo "<script>alert('成功修改了退款金额');</script>";
                $this->success("操作完成！",'', '', 'moneyAgain');
            }else{
                echo "<script>alert('修改退款金额失败，请重试');</script>";
                $this->error("操作失败！",'', '', 'moneyAgain');
            }
        }else{
            $result = $refundMod->where("sub_order_sn = '{$orderSn}'")->find();
            $this->assign('orderSn',$orderSn);
            $this->assign('name',$name);
            $this->assign('money',($result['refund_money']/100));
            $this->assign('num',$num);
            $this->display();
        }
    }
    /**
     * +----------------------------------------------------------
     * 批量退款
     * @author:hhf
     * +----------------------------------------------------------
     */
    function refundOnce(){
        $refundMod = M('refund');
        $orderMod = M('order');
        if($_POST['data']){
            $data['status'] = 1;
            $data['batch_no'] = $_SESSION['WIDbatch_no'];
            $_SESSION['WIDbatch_no'] = "";
            $refundMod->where("status = 4")->save($data);
            die($_POST['data']);
        }
        $refundResult = $refundMod->where("status = 4")->select();
        $detail = array();
        $num = 0;
        foreach($refundResult as $key=>$val){
            $where['order_sn'] = $val['order_sn'];
            $order = $orderMod->where($where)->field('pay_sn')->find();
            $pay = $order['pay_sn']."^".($val['refund_money']/100)."^".$val['reason'];
            $detail[] = $pay;
            $num++;
        }
        Log::write("您批量操作了如下几笔退款请求到支付宝：".var_export($detail,true),Log::INFO);
        if($num>1){
            $detail = implode('#',$detail);
        }else{
            $detail = $detail[0];
        }
        $return['detail'] = $detail;
        $return['num'] = $num;
        $return['flag'] = true;
        die(json_encode($return));
    }

    /**
     * +----------------------------------------------------------
     * 取消批量退款
     * @author:hhf
     * +----------------------------------------------------------
     */
    function move(){
        $order_sn = $_POST['data'];
        $refund = M('refund');
        $where['sub_order_sn'] = $order_sn;
        $where['status'] = 4;
        $data['status'] = 0;
        $refund->where($where)->save($data);
    }

}
?>