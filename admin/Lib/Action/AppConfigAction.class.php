<?php
/**
 * 
 * @author: shenchao
 * @since: 2015-08-27
 */

class AppConfigAction extends BaseAction{
    /**
    +----------------------------------------------------------
     * iOS开关控制
    +----------------------------------------------------------
     */
    public function iosStatus(){
        $site_mod = M("site_config");
        if(!empty($_POST)){
            //更新成功之后要更新缓存
            $options = C('REDIS_CONF');
            $redis = Cache::getInstance('Redis',$options);
            $set_key = C("LS_APP_CONFIG");// 缓存建名
            $redis->rm($set_key);
            if($site_mod->where("id = 1")->save($_POST)!==false){
                $arr = array(
                    "flag" => 1
                );
                die(json_encode($arr));
            }else{
                $arr = array(
                    "flag" => 0
                );
                die(json_encode($arr));
            }
            die;
        }

        $data = $site_mod->field("ios_switch,version")->find(1);

        $this->assign("data",$data);
        $this->display();
    }

    /**
    +----------------------------------------------------------
     * 分享有礼开关
    +----------------------------------------------------------
     */
    public function giftSwitch(){
        $site_mod = M("site_config");
        if(!empty($_POST)){
            //更新成功之后要更新缓存
            $options = C('REDIS_CONF');
            $redis = Cache::getInstance('Redis',$options);
            $set_key = C("LS_APP_CONFIG");// 缓存建名
            $redis->rm($set_key);
            if($site_mod->where("id = 1")->save($_POST)!==false){
                $arr = array(
                    "flag" => 1
                );
                die(json_encode($arr));
            }else{
                $arr = array(
                    "flag" => 0
                );
                die(json_encode($arr));
            }
            die;
        }
        $data = $site_mod->field("gift_switch")->find(1);
        $this->assign("data",$data);
        $this->display();
    }


    /**
    +----------------------------------------------------------
     * 配置转盘中奖的优惠券（暂时没有使用这个方法）
    +----------------------------------------------------------
     */
    public function getPrice(){
        $site_mod = M("app_config");
        $couponMod = M("coupon");
        if(!empty($_POST)){
            $data5 = $site_mod->where("config_name = 'lhj_coupon_5'")->find();
            $data10 = $site_mod->where("config_name = 'lhj_coupon_10'")->find();
            if(!$data5){
                $dataAdd = array(
                    "config_name"=>"lhj_coupon_5",
                    "config_value"=>"0"
                );
                $site_mod->add($dataAdd);
            }
            if(!$data10){
                $dataAdd = array(
                    "config_name"=>"lhj_coupon_10",
                    "config_value"=>"0"
                );
                $site_mod->add($dataAdd);
            }
            $data5 = $couponMod->field("coupon_num")->find($_POST['lhj_coupon_5']);
            $data['config_value'] = $data5['coupon_num'];
            if($site_mod->where("config_name = 'lhj_coupon_5'")->save($data)!==false){
                $data10 = $couponMod->field("coupon_num")->find($_POST['lhj_coupon_10']);
                $data['config_value'] = $data10['coupon_num'];
                if($site_mod->where("config_name = 'lhj_coupon_10'")->save($data)!==false){
                    $arr = array(
                        "flag" => 1
                    );
                    die(json_encode($arr));
                }else{
                    $arr = array(
                        "flag" => 0
                    );
                    die(json_encode($arr));
                }
            }else{
                $arr = array(
                    "flag" => 0
                );
                die(json_encode($arr));
            }
            die;
        }
        $data5 = $site_mod->where("config_name = 'lhj_coupon_5'")->find();
        $data5 = $couponMod->where("coupon_num = '{$data5['config_value']}'")->find();
        $data10 = $site_mod->where("config_name = 'lhj_coupon_10'")->find();
        $data10 = $couponMod->where("coupon_num = '{$data10['config_value']}'")->find();
        $data['lhj_coupon_5'] = $data5['id'];
        $data['lhj_coupon_10'] = $data10['id'];
        $this->assign("data",$data);
        $this->display();
    }


    /**
    +----------------------------------------------------------
     * QQ，电话修改
    +----------------------------------------------------------
     */
    public function qqAndPhone(){
        $site_mod = M("site_config");
        $siteResult = $site_mod->find();
        if($_POST['save']){
            $qq = I('post.qq',0,'trim,htmlspecialchars');
            $phone = I('post.phone',0,'trim,htmlspecialchars');
            $feedback = I('post.feedback','','trim,htmlspecialchars');
            $data['id'] = $siteResult['id'];
            $data['service_qq'] = $qq;
            $data['service_phone'] = $phone;
            $data['feedback_notice'] = $feedback;
            $flag = $site_mod->save($data);
            if($flag!==false){
                $return = array();
                $return['flag'] = true;
            }else{
                $return = array();
                $return['flag'] = false;
            }
            //更新成功之后要更新缓存
            $options = C('REDIS_CONF');
            $redis = Cache::getInstance('Redis',$options);
            $set_key = C("LS_APP_CONFIG");// 缓存建名
            $redis->rm($set_key);
            $this->ajaxReturn($return,'json');
        }else{
            $this->assign('siteConfig',$siteResult);
            $this->display();
        }
    }

    /**
    +----------------------------------------------------------
     * 不发送给亚莉蒂斯的收货人电话号码
    +----------------------------------------------------------
     */
    public function noSendMobile(){
        $site_mod = M("app_config");
        $where['config_name'] = "test_mobile";
        $siteResult = $site_mod->where($where)->find();
        if($_POST['save']){
            $mobile = I('post.mobile',0,'trim,htmlspecialchars');
            str_replace("，", ",", $mobile);
            $data['config_value'] = $mobile;
            $flag = $site_mod->where($where)->save($data);
            if($flag!==false){
                $return = array();
                $return['flag'] = true;
            }else{
                $return = array();
                $return['flag'] = false;
            }
            $this->ajaxReturn($return,'json');
        }else{
            $this->assign('mobile',$siteResult['config_value']);
            $this->display();
        }
    }
}