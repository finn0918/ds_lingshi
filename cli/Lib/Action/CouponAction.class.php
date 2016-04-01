<?php
/**
 * 优惠券的定时功能
 * @author: hhf
 *
 */
class CouponAction extends Action{
    /**
    +----------------------------------------------------------
     * 取消有效期时间超过1分钟的优惠券的显示
    +----------------------------------------------------------
     */
    public function cancelCouponShow(){
//        $deadline = 12096000; //  14天
//        $deadline = 60;// 一分钟
        $time = time();//
        $where = array(
            'use_end_time'=>array('elt',$time),
            'is_done'=>0
        );
        $couponMod = M('coupon');
        $couponuserMod = M('coupon_user');
        $couponRes = $couponMod->where($where)->field('coupon_num')->select();
        foreach($couponRes as $val){
            $data['is_show'] = 0;
            $flag = $couponuserMod->where("coupon_num = '{$val['coupon_num']}' AND is_show = 1")->save($data);
            if($flag===false||(!isset($flag))){
                Log::write("定时清除过期优惠券失败！",Log::WARN);
            }else{
                $sql = "UPDATE ls_coupon SET is_done = 1  WHERE coupon_num = '{$val['coupon_num']}'";
                $couponMod->execute($sql);
            }
        }
    }

    /**
    +----------------------------------------------------------
     * 定时上架优惠券
    +----------------------------------------------------------
     */
    public function publishCoupon(){
        $couponMod = M('coupon');
        $time = time();
        $where = array();
        $where['online_time'] = array('elt',$time);
        $where['status'] = "scheduled";
        $data = array();
        $data['status'] = "published";
        $couponResult = $couponMod->where($where)->save($data);
        if($couponResult===false){
            Log::write("定时上架优惠券失败！",Log::WARN);
        }
    }
}