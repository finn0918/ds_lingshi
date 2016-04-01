<?php
/**
 * 支付宝回调请求
 * @author:hhf
 * Time: 2015/08/2410:25
 */

class NotifyAction extends Action{
    /**
     * 支付宝回调函数
     * @author:hhf
     * Time: 2015/08/2410:25
     */
    function notify() {
        require_once(THINK_PATH."Extend/Vendor/alipay/alipayRefund.config.php");
        require_once(THINK_PATH."Extend/Vendor/alipay/lib/alipay_notify_md5.class.php");

        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        $refundMod = M('refund');
        $subOrderMod = M('sub_order');
        if($verify_result){
            $batch_no = $_POST['batch_no'];
            $where['batch_no'] = $batch_no;
            $where['status'] = 1;
            $data['status'] = 3;//支付成功，交易关闭
            $OrderSn = $refundMod->where($where)->field('sub_order_sn')->find();
            $flag = $refundMod->where($where)->save($data);
            if($flag&&$OrderSn){
                $where = array();
                $where['sub_order_sn'] = $OrderSn['sub_order_sn'];
                $data = array();
                $data['status'] = 7;//支付成功，交易关闭
                $subOrderMod->where($where)->save($data);
            }
            Log::write("支付宝回调成功：".var_export($_POST,true),Log::INFO);
            echo "success";		//请不要修改或删除
        }else{
            //验证失败
            Log::write("支付宝回调失败：".var_export($_POST,true),Log::INFO);
            echo "fail";
        }
    }

} 