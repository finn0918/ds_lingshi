<?php

/**
 *
 * @author: nangua
 * @since: 2015-11-26
 */
class PopAction extends BaseAction{
    /**
    +----------------------------------------------------------
     * 弹窗信息
    +----------------------------------------------------------
     */
    public function index(){
        $site_url = C("SITE_URL");// 站点url
        $os_type = I('get.os_type','','trim,intval');
        $version = I('get.version','','trim,intval');
        $id = I('get.id','','trim,intval');
        $uid = I('get.uid','','trim,intval');
        $pop_mod = M("pop");
        $pop_data = $pop_mod->where("id = $id ")->find();
        if(empty($pop_data)){
            $url = "http://lingshi.cccwei.com/wap/";
        }else{
            $url = $site_url."/api.php?apptype=0&srv=2926&gifts_id=".$pop_data['link_id']."&cid=10002&uid=".$uid."&tms=20150721190147&sig=8c35f5a024148111&wssig=308efe4382a088e0&os_type=".$os_type."&version=$version";
        }

        $this->assign("url",$url);
        $this->display("pop/pop");
    }
    
    //优惠券老用户跳转页 3000
    public function pop(){	
    $site_url = C("SITE_URL");// 站点url
    $os_type = I('get.os_type','','trim,intval');
    $version = I('get.version','','trim,intval');
    //	$id = I('get.id','','trim,intval');
    $uid = I('get.uid','','trim,intval');
     
     
    $tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
   
    $sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
   
    $wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
    $cid = $this->cid;
    //$cid = $cid?$cid:10002;
    $url = $site_url."/api.php?srv=3002&cid=$cid&uid=$uid&tms=$tms&sig=$sig&wssig=$wwsig&os_type=$os_type&version=$version&u=1";
    $this->assign("url",$url);
    $this->display("pop/pop");
    }
    //优惠券新用户跳转页 3001
    public function popNew(){
    	$site_url = C("SITE_URL");// 站点url
    	$os_type = I('get.os_type','','trim,intval');
    	$version = I('get.version','','trim,intval');

    	$uid = I('get.uid','','trim,intval');
    
    	$tms = htmlspecialchars($this->getField('tms'),ENT_QUOTES);
    	$sig = htmlspecialchars($this->getField('sig'),ENT_QUOTES);
    	$wwsig =  htmlspecialchars($this->getField('wssig'),ENT_QUOTES);
    	$cid = $this->cid;

    	$url = $site_url."/api.php?apptype=0&srv=3002&cid=$cid&uid=$uid&tms=$tms&sig=$sig&wssig=$wwsig&os_type=$os_type&version=$version";

    
    	$this->assign("url",$url);
    	$this->display("pop/popNew");
    }
}