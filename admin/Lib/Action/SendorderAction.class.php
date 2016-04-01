<?php
/**
 * 订单发货管理,非订单管理
 * @author tdl
 * Date: 15/9/19
 * Time: 下午11:37
 */
class SendorderAction extends BaseAction
{
    function index(){
        $orderModel = M('order');
        $subOrderMod = M('sub_order');
        $suppliersMod = M('suppliers');

        $timeStart = I('get.time_start', '', 'trim,htmlspecialchars');//添加时间区间开始
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end', '', 'trim,htmlspecialchars');//添加时间区间结束
        $timeEnd = strtotime($timeEnd);
        $suppliers = I('get.supplier', 0, 'intval');
        $status = I('get.status', 1, 'intval');
        $keyword = I('get.keyword', '', 'trim,htmlspecialchars');   //关键词，主订单号
        $people = I('get.people', '', 'trim,htmlspecialchars');

        $subGroupWhere['is_show'] = '1';
        $subGroupWhere['status'] = $status;
        $this->assign('status', $status);
        // time range
        if ($timeStart && $timeEnd) {
            //$orderWhere['add_time'] = array('egt', $timeStart);
            $subGroupWhere['add_time'] = array('between',array($timeStart,$timeEnd));
            $timeStart = date("Y-m-d H:i", $timeStart);
            $timeEnd = date("Y-m-d H:i", $timeEnd);
            $this->assign('time_start', $timeStart);
            $this->assign('time_end', $timeEnd);
        } else {
            if ($timeStart) {
                $subGroupWhere['add_time'] = array('egt', $timeStart);
                $timeStart = date("Y-m-d H:i", $timeStart);
                $this->assign('time_start', $timeStart);
            }
            if ($timeEnd) {
                $subGroupWhere['add_time'] = array('elt', $timeEnd);
                $timeEnd = date("Y-m-d H:i", $timeEnd);
                $this->assign('time_end', $timeEnd);
            }
        }
        if($suppliers) {
            $subGroupWhere['suppliers_id'] = $suppliers;
            $this->assign('select_supplier', $suppliers);
        }
        if($keyword) {
            $peopleOrderSn = $keyword;
            $subGroupWhere['order_sn'] = $peopleOrderSn;
            $this->assign('keyword',$keyword);
        } else if($people) {
            //consignee
            $peopleOrderSn = array();
            $peopleWhere['consignee'] = array('like','%'.$people.'%');
            $peopleData = $orderModel->where($peopleWhere)->select();
            foreach($peopleData as $p) {
                array_push($peopleOrderSn,$p['order_sn']);
            }
            $subGroupWhere['order_sn'] = array('in',$peopleOrderSn);
            $this->assign('people',$people);
        }

        $count = count($subOrderMod->where($subGroupWhere)->group('order_sn,suppliers_id')->select());
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $subOrderGroup = $subOrderMod->where($subGroupWhere)->group('order_sn,suppliers_id')->limit($p->firstRow . ',' . $p->listRows)->order('order_sn DESC,add_time DESC')->select();

        //print_r($subOrderMod->getLastSql());

        foreach($subOrderGroup as $v) {

            $orderSn = $v['order_sn'];
            $suppliers_id = $v['suppliers_id'];
            $subOrderList = array();

            //可以不考虑主单的状态
            $orderWhere['order_sn'] = $orderSn;
            $orderData = $orderModel->where($orderWhere)->find();

            $consignee = $orderData['consignee'];
            $address = $orderData['address'];

            $subWhere['status'] = $status;
            $subWhere['suppliers_id'] = $suppliers_id;
            $subWhere['order_sn'] = $orderSn;
            if($suppliers) {
                $subWhere['suppliers'] = $suppliers;
            }

            $subOrderInSuppliers = $subOrderMod->where($subWhere)->order('order_sn DESC,add_time DESC')->select();

            foreach($subOrderInSuppliers as $s) {
                $skuName = $s['spu_name'];
                $subOrderList[] = array(
                    'sku_name' => $skuName,
                    'suppliers_id'   => $s['suppliers_id'],
                    'suppliers_name' => $s['suppliers_name'],
                    'sub_order_sn' => $s['sub_order_sn'],
                    'shipping' => $s['shipping_id'],
                    'company' => $s['shipping_com'],
                    'add_time' => $s['add_time'],
                    'send_time' => $s['send_time'],
                    'status' => $s['status']
                );
            };
            $orderList[] = array(
                'suppliers_id' => $v['suppliers_id'],
                'suppliers_name' => $v['suppliers_name'],
                'order_sn' => $v['order_sn'],
                'consignee' => $consignee,
                'address' => $address,
                'status' => $v['status'],
                'add_time' => $v['add_time'],
                'sub_order' => $subOrderList
            );
        }
        $this->assign('order',$orderList);
        $page = $p->show();
        $this->assign('page', $page);
        $supplierResult = $suppliersMod->select();
        $this->assign('suppliers', $supplierResult);
        $this->display();
    }

    function send() {
        $myId = $_POST['ordersn'];
        $shipping = $_POST['shipping'];
        $company = $_POST['company'];
        $suppliers = $_POST['suppliers'];

        //$orderMod = M('order');
        $subOrderMod = M('sub_order');

        //子单发货，更新 shipping_id(运单号), shipping_com(快递公司), send_time(发货时间), status(状态)
        $subWhere['order_sn'] = $myId;
        $subWhere['status'] = '1';
        $subWhere['suppliers_id'] = $suppliers;

        $subData['shipping_id'] = $shipping;
        $subData['shipping_com'] = $company;
        $subData['send_time'] = time();
        $subData['status'] = '2';

        $subOrderSend = $subOrderMod->where($subWhere)->save($subData);

        //主单信息不用更新
        //$orderData['send_time'] = time();
        //$orderData['status'] = '2';
        //$orderWhere['order_sn'] = $myId;
        //$orderWhere['status'] = '1';
        //$orderSend = $orderMod->where($orderWhere)->save($orderData);

        Log::write('子单发货：' . $subOrderMod->getLastSql() , Log::INFO);

        //print_r($subOrderMod->getLastSql());

        if(!$subOrderSend) {
            $res['flag'] = true;
            $res['msg'] = '发货失败';
        } else {
            $res['flag'] = true;
            $res['msg'] = '发货成功';
        }

        $this->ajaxReturn($res,'json');
    }

    function test()
    {
        $timeStart = I('get.time_start', '', 'trim,htmlspecialchars');//添加时间区间开始
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end', '', 'trim,htmlspecialchars');//添加时间区间结束
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword', '', 'trim,htmlspecialchars');//关键词，主订单号
        $suppliers = I('get.supplier', 0, 'intval');//供应商，子单搜索有效
        $type = I('get.type', 0, 'intval');//状态
        $orderMod = D('Order');
        $subOrderMod = D('SubOrder');
        $imageMod = M('spu_image');
        $suppliersMod = M('suppliers');
        $imgHost = C('LS_IMG_URL');
        if ($timeStart) {
            $where['add_time'] = array('egt', $timeStart);
            $timeStart = date("Y-m-d H:i", $timeStart);
            $this->assign('time_start', $timeStart);
        }
        if ($timeEnd) {
            $where['add_time'] = array('elt', $timeEnd);
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

    function subIndex() {

        $orderModel = M('order');
        $subOrderMod = M('sub_order');
        $supp = M('suppliers');

        $status = $_GET['status'];
        $keyword = $_GET['keyword'];
        $people = $_GET['people'];

        $timeStart = I('get.time_start', '', 'trim,htmlspecialchars');//添加时间区间开始
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end', '', 'trim,htmlspecialchars');//添加时间区间结束
        $timeEnd = strtotime($timeEnd);

        if ($timeStart && $timeEnd) {
            //$orderWhere['add_time'] = array('egt', $timeStart);
            $orderWhere['add_time'] = array('between',array($timeStart,$timeEnd));
            $timeStart = date("Y-m-d H:i", $timeStart);
            $this->assign('time_start', $timeStart);

            $timeEnd = date("Y-m-d H:i", $timeEnd);
            $this->assign('time_end', $timeEnd);
        } else {
            if ($timeStart) {
                $orderWhere['add_time'] = array('egt', $timeStart);
                $timeStart = date("Y-m-d H:i", $timeStart);
                $this->assign('time_start', $timeStart);
            }
            if ($timeEnd) {
                $orderWhere['add_time'] = array('elt', $timeEnd);
                $timeEnd = date("Y-m-d H:i", $timeEnd);
                $this->assign('time_end', $timeEnd);
            }
        }
        if(!empty($status)) {
            $orderWhere['status'] = $status;
            $this->assign('status',$status);
        } else {
            $orderWhere['status'] = array('in','1,2');
        }
        if(!empty($people)) {
            $orderWhere['consignee'] = array('like','%'.$people.'%');
            $this->assign('people',$people);
        }
        if(!empty($keyword)) {
            $orderWhere['order_sn'] = $keyword;
            $this->assign('keyword',$keyword);
        }
        $orderWhere['_logic'] = 'AND';

        $count = $orderModel->where($orderWhere)->count();
        import("ORG.Util.Page");
        $pageSize = 20;
        $p = new Page($count, $pageSize);
        $orderResult = $orderModel->where($orderWhere)->limit($p->firstRow . ',' . $p->listRows)->order('add_time DESC')->select();

        //print_r($orderModel->getLastSql());
        foreach($orderResult as $v) {

            $subOrderWhere['order_sn'] = $v['order_sn'];

            $subOrderResult = $subOrderMod->where($subOrderWhere)->select();

            $subOrderList = array();

            foreach($subOrderResult as $s) {
                $subOrderList[] = array(
                    'sub_order_sn' => $s['sub_order_sn'],
                    'shipping' => $s['shipping_id'],
                    'company' => $s['shipping_com'],
                    'add_time' => $s['add_time']
                );
            }

            $orderList[] = array(
                'order_sn' => $v['order_sn'],
                'consignee' => $v['consignee'],
                'address' => $v['address'],
                'add_time' => $v['add_time'],
                'status' => $v['status'],
                'sub_order' => $subOrderList
            );
        }

        $this->assign('order',$orderList);
        $page = $p->show();
        $this->assign('page', $page);
        $supplierResult = $suppliersMod->select();
        $this->assign('suppliers', $supplierResult);
        $this->display();

    }
}