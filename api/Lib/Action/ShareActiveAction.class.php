<?php

/**
 *
 * @author: nangua
 * @since: 2015-10-16
 */
class ShareActiveAction extends BaseAction{
    /**
    +----------------------------------------------------------
     * 分享有礼展示列表 2921
    +----------------------------------------------------------
     */
    public function index(){
        $site_url = C("SITE_URL");
        // 判断当前用户是否登录
        $today = strtotime("today");
        $str = "";
        if($this->uid>0){
            $str = $this->uid."20190910".$this->uid."xiaomiao";
            $str = md5($str);
        }
        // 获取用户获得的优惠券奖励
        $share_gift_mod = M("share_gift");
        $share_gift_list = array();
        $total_money = 0;
        if($this->uid>0){
            $share_gift_result = $share_gift_mod->where("user_id = $this->uid")->group("type")->field("SUM(money) as total_money,count(type) as nums,type,money")->select();
            if(!empty($share_gift_result)){
                // 格式化数据
                foreach($share_gift_result as $k=>$gift_v){
                    $share_gift_result[$k]['money'] = price_format($gift_v['money']);
                    $total_money += $gift_v['total_money'];
                }
                $share_gift_list = arrayFormat($share_gift_result,"type");
            }
        }

        // 红包领取排行
        $share_order = $share_gift_mod->where("user_id <> 60290")->group("user_id")->field("SUM(money) as total_money,self_mobile")->order("total_money desc")->limit(10)->select();
        $temp_order = array();
        foreach ($share_order as $k=>$v) {
            $i = $k<9?"0".($k+1):$k+1;
            $temp_order[$i]['total_money'] = price_format($v['total_money']);
            $temp_order[$i]['mobile']      =  mb_substr($v['self_mobile'],0,1,'utf-8').'**'.mb_substr($v['self_mobile'],-1,1,'utf-8');
            $temp_order[$i]['order']       = $i;
        }

        $site_url = C("SITE_URL");
        $share_url = $site_url."/web.php?m=Share&a=active&auth_code=".$str;
        $this->assign("auth_code",$str);
        $this->assign('share_url',$share_url);// 分配授权码
        $this->assign('my_gift',$share_gift_list);
        $this->assign("share_order",$temp_order);
        $this->assign("total_money",price_format($total_money));
        $this->assign("os_type",$this->os_type);
        $this->assign("site_url",$site_url);
        $this->assign("uid",$this->uid);
        $this->display("Share/index");

    }
    /**
    +----------------------------------------------------------
     * 分享成功后调用的接口 2922
    +----------------------------------------------------------
     */
    public function shareSuccess(){
        $code = trim($this->getField('code','',true),'"');// 商品id 或 供应商id 或优惠券集合id
        $tms  = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
        if(!empty($code)){
            $share_log_mod = M("share_log");
            $today = strtotime("today");
            $now = time();
            $auth_code = $this->uid."20190910".$this->uid."xiaomiao";
            $auth_code = md5($auth_code);
            $str = "xiaomiao".$tms;
            $str = md5($str);

            if($code !=$str){
                $this->errorView($this->ws_code['no_privilege'],$this->msg['no_privilege']);
            }

            $share_time_arr = $share_log_mod->where("auth_code = '{$auth_code}' and type=1")->getField("create_time",true);

            if(!empty($share_time_arr)){
                // 避免sql 排序
                $max_time = max($share_time_arr);

                // 说明今天还没有分享记录
                if($max_time<$today){
                    // 插入分享记录
                    $data = array(
                        "user_id"       => $this->uid,
                        "auth_code"     => $auth_code,
                        "create_time"   => $now,
                        "type"           => 1
                    );
                    // 开启事务
                    $share_log_mod->startTrans();
                    if($share_log_mod->add($data)){
                        // share_gif 记录
                        $share_gift_mod = M("share_gift");
                        $client_mod = M("client");
                        $self_mobile = $client_mod->where("user_id = {$this->uid}")->getField("mobile");
                        // 获取首次分享的奖励金额
                        $site_config_mod = M("site_config");
                        $self_share = $site_config_mod->where("id = 1")->getField("self_share");
                        $coupon_num_mod = M("coupon");
                        $money = $coupon_num_mod->where("coupon_num = '{$self_share}' ")->getField("rule_free");
                        $update_data = array(
                            "self_mobile"   => $self_mobile,
                            "user_id"       => $this->uid,
                            "money"         => $money,
                            "coupon_num"    => $self_share,
                            "type"          => 1,
                            "add_time"      => time(),
                            "friend_mobile" => "0"
                        );

                        Log::write("记录分享没插入的数据".var_export($update_data,true),Log::INFO);

                        if($share_gift_mod->add($update_data)){
                            $this->_sendCoupon($this->uid);
                        }else{
                            $share_log_mod->rollback();
                            $this->successView();
                        }
                    }else{
                        $share_log_mod->rollback();
                        $this->successView();
                    }
                    $share_log_mod->commit();
                }
            }else{
                // 新记录 插入分享记录
                $data = array(
                    "user_id"       => $this->uid,
                    "auth_code"     => $auth_code,
                    "create_time"   => $now,
                    "type"           => 1
                );
                // 开启事务
                $share_log_mod->startTrans();
                if($share_log_mod->add($data)){
                    // share_gif 记录
                    $share_gift_mod = M("share_gift");
                    $client_mod = M("client");
                    $self_mobile = $client_mod->where("user_id = {$this->uid}")->getField("mobile");
                    // 获取首次分享的奖励金额
                    $site_config_mod = M("site_config");
                    $self_share = $site_config_mod->where("id = 1")->getField("self_share");
                    $coupon_num_mod = M("coupon");
                    $money = $coupon_num_mod->where("coupon_num = '{$self_share}' ")->getField("rule_free");
                    $update_data = array(
                        "self_mobile"   => $self_mobile,
                        "user_id"       => $this->uid,
                        "money"         => $money,
                        "coupon_num"    => $self_share,
                        "type"          => 1,
                        "add_time"      => time(),
                        "friend_mobile" => "0"
                    );

                    Log::write("记录分享没插入的数据".var_export($update_data,true),Log::INFO);

                    if($share_gift_mod->add($update_data)){
                        $this->_sendCoupon($this->uid);
                    }else{
                        $share_log_mod->rollback();
                        $this->successView();
                    }
                }else{
                    $share_log_mod->rollback();
                    $this->successView();
                }
                $share_log_mod->commit();
            }
        }
        // 提示操作成功
        $this->successView();
    }
    // 发放优惠券
    private function _sendCoupon($uid){

        $site_config_mod = M('site_config');
        $self_share = $site_config_mod->where("id = 1")->getField("self_share");
        $coupon_user_mod = M("coupon_user");
        $data = array(
            "user_id"       => $uid,
            "coupon_num"   => $self_share,
            "is_show"       => 1,
            "is_use"        => 0
        );
        if(!$coupon_user_mod->add($data)){
            Log::write("用户uid=>$uid 的优惠券没发放成功",Log::ERR);
        }
    }


}