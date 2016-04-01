<?php

/**
 * 订单管理
 * @author hhf
 *
 */
class OrderAction extends BaseAction
{

    /**
     * +----------------------------------------------------------
     * 订单列表
     * +----------------------------------------------------------
     */
    function index()
    {
        $timeStart = I('get.time_start', '', 'trim,htmlspecialchars');//添加时间区间开始
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end', '', 'trim,htmlspecialchars');//添加时间区间结束
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword', '', 'trim,htmlspecialchars');//关键词，主订单号
        $name = I('get.name', '', 'trim,htmlspecialchars');//购买人姓名
        $mobile = I('get.mobile', '', 'trim,htmlspecialchars');//购买人电话
        $suppliers = I('get.supplier', 0, 'intval');//供应商，子单搜索有效
        $type = I('get.type', 0, 'intval');//状态
        $orderMod = D('Order');
        $subOrderMod = D('SubOrder');
        $imageMod = M('spu_image');
        $suppliersMod = M('suppliers');
        $imgHost = C('LS_IMG_URL');
        if ($timeStart) {
            $where['add_time'] = array(array('egt', $timeStart));
            $timeStart = date("Y-m-d H:i", $timeStart);
            $this->assign('time_start', $timeStart);
        }
        if ($timeEnd) {
            $where['add_time'][] = array('elt', $timeEnd);
            $timeEnd = date("Y-m-d H:i", $timeEnd);
            $this->assign('time_end', $timeEnd);
        }
        $where['status'] = $type;
        $this->assign('status', $type);
        if (!$type) {
            if ($keyword) {
                $where['order_sn'] = $keyword;
                $this->assign('keyword', $keyword);
            }
            if($name){
                $where['consignee'] = "$name";
                $this->assign('name', $name);
            }
            if(is_numeric($mobile)){
                $where['mobile'] = "$mobile";
                $this->assign('mobile', $mobile);
            }
            $count = $orderMod->where($where)->count();
            import("ORG.Util.Page");
            $pageSize = 10;
            $p = new Page($count, $pageSize);
            $orderResult = $orderMod->relation(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('add_time desc')->select();
            foreach ($orderResult as $key => $val) {
                $adderss = array();
                $adderss[] = $val['consignee'];
                $adderss[] = $val['mobile'];
                $adderss[] = $val['address'];
                $orderResult[$key]['info'] = implode('，', $adderss);
                $orderResult[$key]['nickName'] = $val['client']['nickname'];
                switch ($val['status']) {
                    case 0:
                        $orderResult[$key]['status'] = "待付款";
                        break;
                    default:
                        break;
                }
                $table = '';
                $tr = '';
                $map['order_sn'] = $val['order_sn'];
                $subResult = $subOrderMod->relation(true)->where($map)->select();
                $money = 0;
                $shippingPrice = 0;
                foreach ($subResult as $v) {
                    $money += $v['sum_price'];
                    $shippingPrice += $v['shipping_fee'];
                    $td = '';
                    $imgWhere['spu_id'] = $v['spu_id'];
                    $imgWhere['type'] = 1;
                    $imageResult = $imageMod->where($imgWhere)->find();
                    $images = unserialize($imageResult['images_src']);
                    $img_url = $imgHost . $images['img_url'];
                    $spuAttr = unserialize($v['spu_attr']);
                    if (!isset($spuAttr['attr_name'])) {
                        $spuAttr = $spuAttr[0];
                    }
                    $td .= '<td width="20%"><img width="60" src="' . $img_url . '"></td>';
                    $td .= '<td>' . $v['spu']['spu_name'] . '<br/><br/><span style="color:#828282;">' . $spuAttr['attr_name'] . ':' . $spuAttr['attr_value'] . '</span></td>';
                    $td .= '<td width="15%">￥' . number_format($v['price'] / 100, 2) . '<br/><span style="text-decoration:line-through;color:#828282;">￥' . number_format($v['spu']['price_old'] / 100, 2) . '</span><br/><br/>' . $v['nums'] . '</td>';
                    $tr .= "<tr>$td</tr>";
                    $tr .= "<tr><td colspan = '3'>买家留言：" . $v['postscript'] . "</td></tr>";
                }
                $table .= "<table width='100%' cellspacing='0' class='tb_rec'>
                                $tr
                               </table>";
                $orderResult[$key]['allPrice'] = $money + $shippingPrice;
                $orderResult[$key]['allPrice'] = number_format($orderResult[$key]['allPrice'] / 100, 2);//换算单位
                $orderResult[$key]['allShipping'] = $shippingPrice;
                $orderResult[$key]['allShipping'] = number_format($orderResult[$key]['allShipping'] / 100, 2);
                $orderResult[$key]['spuDetail'] = $table;
                $orderResult[$key]['show_order_sn'] = $orderResult[$key]['order_sn'];
            }
            $this->assign('order', $orderResult);
            $page = $p->show();
            $this->assign('page', $page);
        } else {
            if ($keyword) {
                $where['order_sn'] = $keyword;
                $this->assign('keyword', $keyword);
            }
            if ($suppliers) {
                $where['suppliers_id'] = $suppliers;
                $this->assign('select_supplier', $suppliers);
            }
            if($name){
                $whereOrder['consignee'] = "$name";
                $this->assign('name', $name);
            }
            if(is_numeric($mobile)){
                $whereOrder['mobile'] = "$mobile";
                $this->assign('mobile', $mobile);
            }
            if($whereOrder&&!$keyword){
                $whereOrder['status'] = array('gt',0);
                $resSn = $orderMod->where($whereOrder)->getField("order_sn",true);//获取符合条件的主订单号
                $where['order_sn'] = array("in",$resSn);
            }
            $count = $subOrderMod->where($where)->count();
            import("ORG.Util.Page");
            $pageSize = 10;
            $p = new Page($count, $pageSize);
            $spuOrderResult = $subOrderMod->relation(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('add_time desc')->select();
            foreach ($spuOrderResult as $key => $val) {
                $orderWhere['order_sn'] = $val['order_sn'];
                $orderResult = $orderMod->relation(true)->where($orderWhere)->find();
                $adderss = array();
                $adderss[] = $orderResult['consignee'];
                $adderss[] = $orderResult['mobile'];
                $adderss[] = $orderResult['address'];
                $spuOrderResult[$key]['info'] = implode(',',$adderss);
                $spuOrderResult[$key]['nickName'] = $orderResult['client']['nickname'];
                switch ($val['status']) {
                    case 0:
                        $spuOrderResult[$key]['status'] = "待付款";
                        break;
                    case 1:
                        $spuOrderResult[$key]['status'] = "待发货";
                        $this->assign('afterSale',1);
                        break;
                    case 2:
                        $spuOrderResult[$key]['status'] = "待收货";
                        $this->assign('afterSale',1);
                        break;
                    case 3:
                        $spuOrderResult[$key]['status'] = "交易成功-未评价";
                        break;
                    case 4:
                        $spuOrderResult[$key]['status'] = "交易成功-已评价";
                        break;
                    case 7:
                        $spuOrderResult[$key]['status'] = "交易关闭";
                        break;
                    default:
                        break;
                }
                $table = '';
                $tr = '';
                $money = 0;
                $shippingPrice = 0;
                $money += $val['sum_price'];
                $shippingPrice += $val['shipping_fee'];
                $td = '';
                $imgWhere['spu_id'] = $val['spu_id'];
                $imgWhere['type'] = 1;
                $imageResult = $imageMod->where($imgWhere)->find();
                $images = unserialize($imageResult['images_src']);
                $img_url = $imgHost . $images['img_url'];
                $spuAttr = unserialize($val['spu_attr']);
                if (!isset($spuAttr['attr_name'])) {
                    $spuAttr = $spuAttr[0];
                }
                $td .= '<td width="20%"><img width="60" src="' . $img_url . '"></td>';
                $td .= '<td>' . $val['spu']['spu_name'] . '<br/><br/><span style="color:#828282;">' . $spuAttr['attr_name'] . ':' . $spuAttr['attr_value'] . '</span></td>';
                $td .= '<td width="15%">￥' . number_format($val['price'] / 100, 2) . '<br/><span style="text-decoration:line-through;color:#828282;">￥' . number_format($val['spu']['price_old'] / 100, 2) . '</span><br/><br/>' . $val['nums'] . '</td>';
                $tr .= "<tr>$td</tr>";
                $tr .= "<tr><td colspan = '3'>买家留言：" . $val['postscript'] . "</td></tr>";
                $table .= "<table width='100%' cellspacing='0' class='tb_rec'>
                                $tr
                               </table>";
                $spuOrderResult[$key]['allPrice'] = $money + $shippingPrice;
                $spuOrderResult[$key]['allPrice'] = number_format($spuOrderResult[$key]['allPrice'] / 100, 2);//换算单位
                $spuOrderResult[$key]['allShipping'] = $shippingPrice;
                $spuOrderResult[$key]['allShipping'] = number_format($spuOrderResult[$key]['allShipping'] / 100, 2);
                $spuOrderResult[$key]['spuDetail'] = $table;
                $spuOrderResult[$key]['order_sn'] = $val['sub_order_sn'];
                $spuOrderResult[$key]['show_order_sn'] = "---主订单编号：".$orderResult['order_sn']."---子订单编号：".$val['sub_order_sn'];
            }
            $this->assign('order', $spuOrderResult);
            $page = $p->show();
            $this->assign('page', $page);
        }
        $orderType = C('ORDER_TYPE');
        $orderTypeArray = array();
        foreach ($orderType as $k => $v) {
            $tmp['key'] = $k;
            $tmp['value'] = $v;
            $orderTypeArray[] = $tmp;
        }
        $this->assign('orderType', $orderTypeArray);
        $supplierResult = $suppliersMod->select();
        $this->assign('suppliers', $supplierResult);
        $this->display();
    }

    /**
     * +----------------------------------------------------------
     * 订单详情
     * +----------------------------------------------------------
     */
    function detail()
    {
        $orderSn = I('get.order_sn', '', 'trim,htmlspecialchars');//订单号
        $orderStatus = I('get.status', 0, 'intval');//订单号
        switch ($orderStatus) {
            case 0:
                $this->detailMain($orderSn);//代付款
                break;
            case 1:
                $this->detailSub($orderSn, $orderStatus);//待发货
                break;
            case 2:
                $this->detailSub($orderSn, $orderStatus);//待收货
                break;
            case 3:
                $this->detailSub($orderSn, $orderStatus);//交易成功-待评价
                break;
            case 4:
                $this->detailSub($orderSn, $orderStatus);//交易成功-已评价
                break;
            default:
                break;
        }
    }

    /**
     * +----------------------------------------------------------
     * 订单详情页，待发货，待收货，待评价
     * +----------------------------------------------------------
     */
    function detailSub($orderSn, $orderStatus)
    {
        $subOrderMod = D('SubOrder');
        $orderMod = M('order');
        $suppliersMod = M('suppliers');
        $imageMod = M('spu_image');
        $imgHost = C('LS_IMG_URL');
        $subOrderResult = $subOrderMod->relation(true)->where("sub_order_sn = '$orderSn'")->find();
        $orderResult = $orderMod->where("order_sn = '{$subOrderResult['order_sn']}'")->find();//子单对应的主单信息
        $info = $orderResult['consignee'].','.$orderResult['mobile'].','.$orderResult['address'];//收货人信息
        $subOrderResult['info'] = $info;
        $buyer = $subOrderResult['client']['nickname'];
        $mobile = $orderResult['mobile'];
        $subOrderResult['buyer'] = "昵称：".$buyer."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;联系电话：".$mobile;
        $suppliersResult = $suppliersMod->where("suppliers_name = '{$subOrderResult['suppliers_name']}'")->find();
        $subOrderResult['suppliers'] = "供应商：".$suppliersResult['suppliers_name']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;供货联系电话：".$suppliersResult['suppliers_mobile']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;发货地址：".$suppliersResult['suppliers_address'];
        //var_dump($subOrderResult);
        $imgResult = $imageMod->where('spu_id ='.$subOrderResult['spu_id'])->find();//缩略图
        $imgResult = unserialize($imgResult['images_src']);
        $spuAttr = unserialize($subOrderResult['spu_attr']);
        $order = array();
        $tmp['spu_name'] = $subOrderResult['spu']['spu_name'];
        $tmp['spu_image'] = $imgHost.$imgResult['img_url'];
        $tmp['spu_attr'] = $spuAttr[0]['attr_value'];
        $tmp['pay'] = number_format($subOrderResult['price'] / 100, 2);
        $tmp['num'] = $subOrderResult['nums'];
        $tmp['sumPrice'] = number_format($subOrderResult['sum_price'] / 100, 2);
        $realPrice = number_format($subOrderResult['sum_price'] / 100, 2);
        if($orderStatus==1){
            $addTime = date('Y-m-d H:i:s',$subOrderResult['add_time']);
            $payTime = date('Y-m-d H:i:s',$subOrderResult['pay_time']);
            $orderInfo = "订单编号:{$subOrderResult['sub_order_sn']} 创建时间：$addTime 付款时间：$payTime";
            $subOrderResult['orderInfo'] = $orderInfo;
            $this->assign('shipping',"卖家未发货，物流信息空缺");
            $this->assign('status',"待发货");
            $tmp['status'] = "代发货";
            $order[] = $tmp;
        }elseif($orderStatus==2){
            $addTime = date('Y-m-d H:i:s',$subOrderResult['add_time']);
            $payTime = date('Y-m-d H:i:s',$subOrderResult['pay_time']);
            $sendTime = date('Y-m-d H:i:s',$subOrderResult['send_time']);
            $orderInfo = "订单编号:{$subOrderResult['sub_order_sn']} 创建时间：$addTime 付款时间：$payTime 发货时间：$sendTime";
            $subOrderResult['orderInfo'] = $orderInfo;
            $express = C('SHIPPING_COM');
            foreach($express as $k=>$v){
                if($v==$subOrderResult['shipping_com']){
                    $subOrderResult['shipping_com_sign']=$subOrderResult['shipping_com'];
                    $subOrderResult['shipping_com']=$k;
                }
            }
            $this->assign('shipping',"这里要显示物流啊啊啊啊啊");
            $this->assign('status',"待收货");
            $tmp['status'] = "待收货";
            $order[] = $tmp;
        }elseif($orderStatus==3||$orderStatus==4){
            $addTime = date('Y-m-d H:i:s',$subOrderResult['add_time']);
            $payTime = date('Y-m-d H:i:s',$subOrderResult['pay_time']);
            $sendTime = date('Y-m-d H:i:s',$subOrderResult['send_time']);
            $successTime = date('Y-m-d H:i:s',$subOrderResult['finish_time']);
            $orderInfo = "订单编号:{$subOrderResult['sub_order_sn']} 创建时间：$addTime 付款时间：$payTime 发货时间：$sendTime 成交时间：$successTime";
            $subOrderResult['orderInfo'] = $orderInfo;
            $express = C('SHIPPING_COM');
            foreach($express as $k=>$v){
                if($v==$subOrderResult['shipping_com']){
                    $subOrderResult['shipping_com_sign']=$subOrderResult['shipping_com'];
                    $subOrderResult['shipping_com']=$k;
                }
            }
            if($orderStatus==4){
                $this->assign('shipping',"这里要显示物流");
                $this->assign('status',"交易完成-已评价");
                $tmp['status'] = "交易完成-已评价";
            }else{
                $this->assign('shipping',"这里要显示物流");
                $this->assign('status',"交易完成-待评价");
                $tmp['status'] = "交易完成-待评价";
            }
            $order[] = $tmp;
        }elseif($orderStatus==7){

        }
        $this->assign('realPrice',$realPrice);
        $this->assign('order', $subOrderResult);
        $this->assign('orders', $order);
        $this->display();
    }


    /**
     * +----------------------------------------------------------
     * 订单详情页，待付款
     * +----------------------------------------------------------
     */
    function detailMain($orderSn) {
        $subOrderMod = D('SubOrder');
        $orderMod = D('Order');
        $suppliersMod = M('suppliers');
        $imageMod = M('spu_image');
        $imgHost = C('LS_IMG_URL');
        $orderResult = $orderMod->relation(true)->where("order_sn = '$orderSn'")->find();
        $info = $orderResult['consignee'].','.$orderResult['mobile'].','.$orderResult['address'];//收货人信息
        $orderResult['info'] = $info;
        $buyer = $orderResult['client']['nickname'];
        $orderResult['buyer'] = "昵称：".$buyer."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;联系电话：".$orderResult['mobile'];
        $subOrderResult = $subOrderMod->relation(true)->where("order_sn = '$orderSn'")->select();
        $suppliers = "";
        $suppliersArray = array();
        $order = array();
        $realPrice = 0;
        foreach($subOrderResult as $val){
            if(!in_array($val['suppliers_name'],$suppliersArray)){
                $suppliersResult = $suppliersMod->where("suppliers_name = '{$val['suppliers_name']}'")->find();
                if($suppliersResult){
                    $suppliers .= "供应商：".$suppliersResult['suppliers_name']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;供货联系电话：".$suppliersResult['suppliers_mobile']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;发货地址：".$suppliersResult['suppliers_address']."<br/>";
                }
                array_push($suppliersArray,$val['suppliers_name']);
            }
            $tmp['spu_name'] = $val['spu']['spu_name'];
            $imgResult = $imageMod->where('spu_id = '.$val['spu_id'])->find();
            $imgResult = unserialize($imgResult['images_src']);
            $tmp['spu_image'] = $imgHost.$imgResult['img_url'];
            $spuAttr = unserialize($val['spu_attr']);
            $tmp['spu_attr'] = $spuAttr[0]['attr_value'];
            $tmp['pay'] = number_format($val['price'] / 100, 2);
            $tmp['num'] = $val['nums'];
            $tmp['sumPrice'] = number_format($val['sum_price'] / 100, 2);
            $realPrice += $tmp['sumPrice'];
            $tmp['status'] = "代付款";
            $order[] = $tmp;
        }
        $this->assign('realPrice',$realPrice);
        $orderResult['suppliers'] = $suppliers;
        $this->assign('order',$orderResult);
        $this->assign('orders',$order);
        $this->assign('shipping',"钱都还没付，发什么货");
        $this->assign('status',"待付款");
        $this->display();
    }

    /**
     * +----------------------------------------------------------
     * 添加到售后处理
     * +----------------------------------------------------------
     */
    function  afterSale() {
        $orderSn = I('request.order_sn','','trim');
        $subMod = M('sub_order');
        $refundMod = M('refund');
        $why = array(
            '1' => "不想要了",
            '2' => "质量有问题",
            '3' => "不好吃",
            '4' => "其他",
        );
        if($orderSn){
            if(isset($_POST['dosubmit'])){
                $where['sub_order_sn'] = $orderSn;
                $save['status'] = 5;
                $mainOrder = $subMod->where($where)->field('order_sn,status')->find();
                $flag = $subMod->where($where)->save($save);
                $reason = I('post.why',1,'intval');
                $reason = $why[$reason];
                $type = I('post.condition',1,'intval');
                $remark = I('post.info','','trim,htmlspecialchars');
                $data = array(
                    "reason" => $reason,
                    "type"   => $type,
                    "remark" => $remark,
                    "sub_order_sn" => $orderSn,
                    "order_sn" => $mainOrder['order_sn'],
                    "order_status"=>$mainOrder['status'],
                    "add_time" => time(),
                );
                if($flag!==false){
                    $refundResult = $refundMod->add($data);
                    if($refundResult){
                        $this->success("已经转到售后处理（待审核）！",'', '', 'afterSale');
                    }
                }
            }else{
                $saleWhy = array();
                foreach($why as $k=>$v) {
                    $saleWhy[$k]['key'] = $k;
                    $saleWhy[$k]['val'] = $v;
                }
                $this->assign('why',$saleWhy);
                $this->assign('orderSn',$orderSn);
                $this->display();
            }
        }
    }


	 /**
     * 订单下载
     * Enter description here ...
     */
    function orderDump(){

        $orderMod = D('Order');
        $subOrderMod = D('SubOrder');
        $imageMod = M('spu_image');
        $suppliersMod = M('suppliers');
        $imgHost = C('LS_IMG_URL');


        //订单状态和供应商赋值
//        $orderType = C('ORDER_TYPE');
//        $orderTypeArray = array();
//        foreach ($orderType as $k => $v) {
//            $tmp['key'] = $k;
//            $tmp['value'] = $v;
//            $orderTypeArray[] = $tmp;
//        }
        //$this->assign('orderType', $orderTypeArray);
        $supplierResult = $suppliersMod->select();
        $this->assign('suppliers', $supplierResult);

        //接收打印查询的条件
        if(!EMPTY($_GET)) {
             $timeStart = I('get.time_start', '', 'trim,htmlspecialchars');//添加时间区间开始
            $ts=$timeStart;
            $timeStart = strtotime($timeStart);
            $timeEnd = I('get.time_end', '', 'trim,htmlspecialchars');//添加时间区间结束
            $te=$timeEnd;
            $timeEnd = strtotime($timeEnd);
            $suppliers = I('get.supplier', 0, 'intval');//供应商，子单搜索有效
            $type = I('get.type', 0, 'intval');//状态
//             if ($type == 0) {
//                 $where = "sub.pay_time > 0  and";
//             }

//             if ($type == 1) {
//                 $where = "sub.pay_time <= 0  and";
//             }

            if ($type == 10) {
                $where = "";
            }else{
                     $where =' sub.status = '. $type.' and';

            }
            $where .= "  sub.suppliers_id = {$suppliers} and sub.add_time >={$timeStart} and sub.add_time <={$timeEnd}";

            $sql = "select ls_order.consignee as '收货人',ls_order.mobile as '电话号码',ls_order.address as '收货地址',
sub.add_time as '下单时间',ls_order.order_sn as '组合单号',sub.sub_order_sn as '子单号',sub.spu_name as '购买的商品',
sub.price as '商品单价',sub.sum_price as '子单总价',sub.nums as '购买数量',sub.spu_attr as '购买的口味',
sub.suppliers_name as '供应商',sub.postscript as '用户的留言信息',ls_sku_list.library_number as '仓位号',
ls_sku_list.sku_number as '商品标号',sub.status as '订单状态'
from ls_sub_order as sub left join ls_order as ls_order on sub.order_sn = ls_order.order_sn
left join ls_suppliers on ls_suppliers.suppliers_id=sub.suppliers_id
left join ls_sku_list on sub.sku_id = ls_sku_list.sku_id
where  " . $where . "
order by sub.add_time desc ,sub.order_sn asc ";

            $r = M();
            $result = $r->query($sql);
           // print_r($r->getLastSql());die;
            if ($result) {
                foreach ($result as $kk => $vv) {
                    foreach ($vv as $kkk => $vvv) {
                        if ($kkk == '下单时间') {
                            $result[$kk][$kkk] = date("Y-m-d H:i:s", $vvv);            //echo $vvv; die;
                        }
                        if ($kkk == '购买的口味') {
                            $tmp = unserialize($vvv);

                            $result[$kk][$kkk] = $tmp[0]['attr_name'] . ":" . $tmp[0]['attr_value'];
                        }
                        if ($kkk == '商品单价 ') {
                            $result[$kk][$kkk] = price_format($vvv);
                        }
                        if ($kkk == '子单总价 ') {
                            $result[$kk][$kkk] = price_format($vvv);
                        }
                        if ($kkk == '用户留言信息 ') {
                            $result[$kk][$kkk] = str_replace("\n", '', $vvv);
                        }
                        if ($kkk == '商品标号 ') {
                            $result[$kk][$kkk] = "'" . $vvv . "'";
                        }
                        if ($kkk == '订单状态') {
//                             if($result[$kk][$kkk]){
//                                 $result[$kk][$kkk] = "";
//                             }
                            switch ($result[$kk][$kkk]) {

                                case 0:
                                    $result[$kk][$kkk] = "未付款";
                                    break;
                                case 1:
                                    $result[$kk][$kkk] = "付款";
                                    break;
                                case 2:
                                    $result[$kk][$kkk] = "已发货";
                                    break;
                                case 3:
                                     $result[$kk][$kkk] = "交易成功(待评价)";
                                     break;
                                case 4:
                                     $result[$kk][$kkk] = "交易成功(已评价)";
                                     break;
                                case 5:
                                     $result[$kk][$kkk] = "售后处理中(待审核)";
                                     break;
                                case 6:
                                     $result[$kk][$kkk] = "售后处理中(售后处理中)";
                                     break;
                                case 7:
                                     $result[$kk][$kkk] = "交易关闭";
                                     break;
                                case 8:
                                     $result[$kk][$kkk] = "结算完成";
                                     break;
                                 default: 
                                    $result[$kk][$kkk] = "全部状态";                                 
                            }
                            //$result[$kk][$kkk] = "'" . $vvv . "'";
                        }
                    }
                }
               $d = $ts."至".$te;
                header('Content-type:application/vnd.ms-excel');
                header("Content-Disposition:attachment;filename=" . "$d" . ".xls");
                echo "订单信息\n";
                foreach ($result[0] as $k => $v) {
                    echo $k . "\t";//取字段名称
                }
//            echo "收货人\t";
//            echo "电话号码\t";
//            echo "收货地址\t";
//            echo "下单时间\t";
//            echo "组合单号\t";
//            echo "子单号\t";
//            echo "购买的商品\t";
//            echo "商品单价\t";
//            echo "子单总价\t";
//            echo "购买数量\t";
//            echo "购买的口味\t";
//            echo "供应商\t";
//            echo "用户的留言信息\t";
//            echo "仓位号\t";
//            echo "商品标号\t";
                echo "\n";
                foreach ($result as $key => $value) {
                    echo $result[$key]['收货人'] . "\t";
                    echo $result[$key]['电话号码'] . "\t";
                    echo $result[$key]['收货地址'] . "\t";
                    echo $result[$key]['下单时间'] . "\t";
                    echo $result[$key]['组合单号'] . "\t";
                    echo $result[$key]['子单号'] . "\t";
                    echo $result[$key]['购买的商品'] . "\t";
                    echo $result[$key]['商品单价'] . "\t";
                    echo $result[$key]['子单总价'] . "\t";
                    echo $result[$key]['购买数量'] . "\t";
                    echo $result[$key]['购买的口味'] . "\t";
                    echo $result[$key]['供应商'] . "\t";
                    echo $result[$key]['用户的留言信息'] . "\t";
                    echo $result[$key]['仓位号'] . "\t";
                    echo $result[$key]['商品标号'] . "\t";
                    echo $result[$key]['订单状态'] . "\t";
                    echo "\n";
                }
                exit;
            }
            else{
                $this->error('亲，查询无相关订单的数据！请联系管理员！','',1);
            }
        }
        $this->display();
    }
}

?>