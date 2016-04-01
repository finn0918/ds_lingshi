<?php
/*
 * 供应商发货接口
 * @author tdl
 * @date 2015/10/16
 * @time 11:00
 */
class CooperationAction extends Action
{
    public function orderSend() {
        $data      = file_get_contents("php://input");
        $data      = json_decode($data,true);
        $vcode     = $data['vcode'];
        $secret    = $data['sk'];
        $time      = $data['time'];
        $oid       = $data['oid'];
        $express   = $data['express_company'];
        $expressNo = $data['express_no'];
        $appkey    = C('SECRET_KEY');
        Log::write('接收参数:['.$vcode.','.$oid.','.$express.','.$expressNo.']', Log::INFO);

        $suppliersMod = M('suppliers');
        $subOrderMode = M('sub_order');
        if(!$oid) {
            $res['code'] = 1;
            $res['message'] = "缺少订单号";
            $this->ajaxReturn($res,'json');
            return false;
        }
        if($express == 'undefined') {
            $res['code'] = 1;
            $res['message'] = "无法识别的快递公司";
            $this->ajaxReturn($res,'json');
            return false;
        }
        if(!$expressNo) {
            $res['code'] = 1;
            $res['message'] = "缺少运单号";
            $this->ajaxReturn($res,'json');
            return false;
        }
        if($secret && $time) {
            $verification = MD5($appkey['yalidisi']['secret_key']. $time);
        }
        if($verification && $secret == $verification) {
            //快递公司名字与"聚合数据"的编号对应
            if(strstr($express, '顺丰')) {
                $expressCode = 'sf';
            } else if(strstr($express, '申通')) {
                $expressCode = 'sto';
            } else if(strstr($express, '圆通')) {
                $expressCode = 'yt';
            } else if(strstr($express, '韵达')) {
                $expressCode = 'yd';
            } else if(strstr($express, '天天')) {
                $expressCode = 'tt';
            } else if(strstr($express, 'EMS')) {
                $expressCode = 'ems';
            } else if(strstr($express, '中通')) {
                $expressCode = 'zto';
            } else if(strstr($express, '汇通')) {
                $expressCode = 'ht';
            } else if(strstr($express, '全峰')) {
                $expressCode = 'qf';
            } else if(strstr($express, '邮政国内小包')) {
                $expressCode = 'yzxb';
            } else {
                //无法识别的快递公司
                $expressCode = 'unknow';
                $res['code'] = 1;
                $res['message'] = "无法识别的快递公司";
            }
            //验证供应商
            $suppWhere['key'] = $vcode;
            $suppData = $suppliersMod->where($suppWhere)->find();
            if($suppData) {
                $where['order_sn'] = $oid;
                $where['suppliers_id'] = $suppData['suppliers_id'];
                $where['status'] = '1';
                $hadOrder = $subOrderMode->where($where)->select();
                if($hadOrder) {
                    $save['shipping_id'] = $expressNo;
                    $save['shipping_com'] = $expressCode;
                    $save['status'] = 2;
                    $save['send_time'] = time();
                    $subOrderSaveData = $subOrderMode->where($where)->save($save);
                    if($subOrderSaveData === false) {
                        $res['code'] = 1;
                        $res['message'] = "发货失败";
                        Log::write('发货失败:'.$subOrderMode->getLastSql(), Log::ERR);
                    } else {
                        $res['code'] = 0;
                        $res['message'] = "发货成功";
                    }
                    Log::write($res['message'].':'.$subOrderMode->getLastSql(), Log::INFO);
                } else {
                    $res['code'] = 1;
                    $res['message'] = "无可发货的订单";
                    Log::write('无可发货的订单', Log::INFO);
                }
            } else {
                $res['code'] = 1;
                $res['message'] = "无此供应商信息";
                Log::write('无此供应商信息', Log::INFO);
            }
        } else {
            $res['code'] = 1;
            $res['message'] = "验签失败";
            Log::write('验签失败:vcode:'.$vcode.', secret:'.$secret, Log::INFO);
        }
        $this->ajaxReturn($res,'json');
    }
}