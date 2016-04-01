<?php
/**
 * 数据迁移
 * @author: hhf
 */

class TransferAction extends Action{
    /**
    +----------------------------------------------------------
     * 迁移淘宝商品1.4版本数据到2.0版本
    +----------------------------------------------------------
     */
    public function transfer() {
        $goodsMod = M('lingshi.goods','ls_');
        $spuMod = M('spu');
        $filterMod = M('filter_goods');
        for($i=1;$i<5050;$i++){
            $time= time();
            $goodsResult = $goodsMod->find($i);
            if(!$goodsResult){
                continue;
            }
            $taobaoTmall = $this->taobaoTmall($goodsResult['taobao_url']);
            switch($goodsResult['status']){
                case 2:
                    $status = 0;
                    break;
                case 4:
                    $status = 5;
                    break;
                case 3:
                    $status = 2;
                    break;
                case 1:
                    $status = 3;
                    break;
                default:
                    $status = 3;
                    break;
            }
            $data = array(
                "spu_id" => $goodsResult['id'],
                "spu_name" => $goodsResult['title'],
                "cid" => 0,
                "price_old" => $goodsResult['price_old']*100,
                "price_now" => $goodsResult['price_now']*100,
                "brand_id" => $goodsResult['brand'],
                "add_time" => $time,
                "update_time" => $time,
                "off_time" => 0,
                "publish_time" => $time,
                "shipping_free" => $goodsResult['free_delivery']?$goodsResult['free_delivery']:0,
                "tags_name" => $goodsResult['tags_name'],
                "taobao_url" => $goodsResult['taobao_url'],
                "tbk_url" => $goodsResult['tbk_url'],
                "sales" => $goodsResult['sales'],
                "desc" => $goodsResult['desc'],
                "discount_info" => 0,
                "open_type" => 0,
                "type" => 0,
                "guide_type" => $taobaoTmall,
                "suppliers_price" => 0,
                "status" => $status,
                "details" => $goodsResult['details'],
                "admin_id" => 1,
            );
            $flag = $spuMod->add($data);
            if($flag){
                echo $flag,"\n";
                $filter = array();
                $filter['gid'] = $flag;
                $filter['url_key'] = md5($data['taobao_url']);
                $filter['type'] = 1;
                $preg = '/&id=\d+/';
                preg_match_all($preg, $data['taobao_url'],$res);
                $id = $res['0']['0'];
                $filter['item_id'] = str_replace('&id=', '', $id);
                if(empty($filter['item_id'])){
                    $preg = '/id=\d+/';
                    preg_match_all($preg, $data['taobao_url'],$res);
                    $id = $res['0']['0'];
                    $filter['item_id'] = str_replace('id=', '', $id);
                }
                $filter['id_key'] = md5($filter['item_id']);
                $filterMod->add($filter);
            }
            //var_dump($data);exit;
        }
    }
    /**
    +----------------------------------------------------------
     * 判断是天猫还是淘宝 1淘宝 2天猫
    +----------------------------------------------------------
     */
    public function taobaoTmall($url) {
        $res = strpos('taobao',$url);
        if($res){
            return 1;
        }else{
            return 2;
        }
    }
    /**
    +----------------------------------------------------------
     * 迁移淘宝商品
    +----------------------------------------------------------
     */
    public function transferImage() {
        $imagesMod = M('lingshi.images','ls_');
        $spuImageMod = M('spu_image');
        $spuMod = M('spu');
        $spuIds = $spuMod->field('spu_id')->select();
        foreach($spuIds as $val){
            //echo $val['spu_id'],"\n";
            $imageResult = $imagesMod->where("goods_id = {$val['spu_id']} and type in(1,3)")->select();
            if($imageResult){
                $addImages = $this->image($imageResult);
                foreach($addImages as $v){
                    $flag = $spuImageMod->add($v);
                    echo $flag,"\n";
                }
            }
        }
    }
    /**
    +----------------------------------------------------------
     * 图片格式转换
    +----------------------------------------------------------
     */
    public function image($imageResult){
        $imgResult = array();
        foreach($imageResult as $val){
            $img_url = substr($val[image_src],29);
            $data = array(
                "img_url" => "$img_url",
                "img_w"   => $val['img_w'],
                "img_h"   => $val['img_h'],
            );
            $tmpImage = array(
                "spu_id" => $val['goods_id'],
                "images_src" => serialize($data),
                "type" => $val['type'],
                "add_time" => $val['add_time'],
            );
            $imgResult[] = $tmpImage;
        }
        return $imgResult;
    }
    /**
    +----------------------------------------------------------
     * 淘宝商品的评论迁移
    +----------------------------------------------------------
     */
    public function transferComment() {
        $commentMod = M('lingshi.comment','ls_');
        $newCommentMod = M('comment');
        $spuMod = M('spu');
        $spuIds = $spuMod->field('spu_id')->select();
        foreach($spuIds as $val){
            $commentResult = $commentMod->where("cid = {$val['spu_id']}")->select();
            $time = time();
            if($commentResult){
                foreach($commentResult as $v){
                    $data = array(
                        "spu_id" => $v['cid'],
                        "nickname" => $v['name'],
                        "avatar_src" => $v['image_src'],
                        "comment" => $v['comment'],
                        "user_id" => 0,
                        "add_time" => $time,
                        "type" => 0,
                    );
                    $flag = $newCommentMod->add($data);
                    if($flag){
                        echo "{$v['cid']}","\n";
                    }
                }
            }
        }
    }
    /**
    +----------------------------------------------------------
     * 设备表迁移
    +----------------------------------------------------------
     */
    function devicesTransfer() {
        $deviceMod = M('lingshi.device','ls_');
        $nowDeviceMod = M('device');
        $num = 299999;
        $now = 1;
        for($i=1;$i<$num;){
            $i += 1000;
            $deviceResult = $deviceMod->where('id >'.$now.' AND id <='.$i)->select();
            if($deviceResult){
                //var_dump($deviceResult);break;
                $value = "";
                foreach($deviceResult as $val){
                    $tmp = "'{$val['id']}','{$val['imei']}','{$val['wskey']}',{$val['os_type']},{$val['device_type']},'{$val['create_ip']}','{$val['update_ip']}',{$val['create_time']},{$val['update_time']}";
                    $tmp = "($tmp),";
                    $value .= $tmp;
                }
                $value = substr($value,0,-1);
                $sql = "INSERT INTO `ls_device` VALUES $value";
                M()->execute($sql);
                echo $i;
            }
            $now = $i;
        }
    }
    /**
    +----------------------------------------------------------
     * 用户表迁移
    +----------------------------------------------------------
     */
    function clientTransfer() {
        $userMod = M('lingshi.user','ls_');
        $clientMod = M('client');
        $num = 59999;
        $now = 0;
        for($i=1;$i<=$num;){
            $i += 1000;
            $userResult = $userMod->where('id >'.$now.' AND id <='.$i)->select();
            $value = "";
            foreach($userResult as $val) {
                $name = mysql_real_escape_string($val['nickname']);
                $tmp = "'{$val['id']}','$name','{$val['icon']}','{$val['status']}',{$val['lock_day']},'{$val['wskey']}',{$val['last_device_id']},{$val['create_time']},{$val['update_time']},{$val['last_login_time']},{$val['pf_type']}";
                $tmp = "($tmp),";
                $value .= $tmp;
            }
            unset($userResult);
            $value = substr($value,0,-1);
            $sql = "INSERT INTO `ls_client`(`user_id`,`nickname`,`avatar`,`status`,`lock_day`,`wskey`,`last_device_id`,`create_time`,`update_time`,`last_login_time`,`pf_type`) VALUES $value";
            M()->execute($sql);
            unset($sql);
            echo $i,"\n";
            $now = $i;
        }
    }
    /**
    +----------------------------------------------------------
     * platform表迁移
    +----------------------------------------------------------
     */
    function platformTransfer() {
        $platformMod = M('lingshi.platform','ls_');
        $clientPlatformMod = M('client_platform');
        $m = M();
        $num = 59999;
        $now = 0;
        for ($i = 1; $i <= $num;){
            $i += 1000;
            $platformResult = $platformMod->where('id >'.$now.' AND id <='.$i)->select();
            if (empty($platformResult)){
                $now = $i;
                continue;
            }
            $value = "";
            foreach($platformResult as $val){
                $name = mysql_real_escape_string($val['nickname']);
                $tmp = "'{$val['id']}','{$val['uid']}','{$val['icon']}','{$val['status']}','{$val['openid']}','$name','{$val['create_time']}','{$val['pf_type']}'";
                $tmp = "($tmp),";
                //echo "tmp: $tmp\n";
                $value .= $tmp;
            }
            $value = substr($value,0,-1);
            unset($userResult);
            $sql = "INSERT INTO `ls_client_platform`(`id`,`client_id`,`icon`,`status`,`openid`,`nickname`,`create_time`,`pf_type`) VALUES $value";
            $ret = $m->execute($sql);

            echo "ret: $ret \n";
            if ($ret <= 0) {
                echo "i: $i\n";
                echo "value: $value \n";
                echo "sql: $sql \n";
                echo "error: " . $m->getError() . "\n";
                exit;
            }
            unset($sql);
            echo $i . "\n";
            $now = $i;
        }
    }
    /**
    +----------------------------------------------------------
     * 用户商品收藏表迁移
    +----------------------------------------------------------
     */
    function spuFavTransfer() {
        $goodsFavMod = M('lingshi.favs','ls_');//商品收藏表
        $spuFavMod = M('favs');
        $m = M();
        $num = 400000;
        $now = 0;
        for ($i = 1; $i <= $num;){
            $i += 5000;
            $platformResult = $goodsFavMod->where('id >'.$now.' AND id <='.$i.' AND goods_id <=5043')->group('uid,goods_id')->select();
            if (empty($platformResult)){
                $now = $i;
                continue;
            }
            $value = "";
            foreach($platformResult as $val){
                $tmp = "'{$val['uid']}','{$val['goods_id']}','{$val['add_time']}','0'";
                $tmp = "($tmp),";
                //echo "tmp: $tmp\n";
                $value .= $tmp;
            }
            $value = substr($value,0,-1);
            unset($userResult);
            $sql = "INSERT INTO `ls_favs`(`user_id`,`opt_id`,`add_time`,`type`) VALUES $value";
            $ret = $m->execute($sql);

            echo "ret: $ret \n";
            if ($ret <= 0) {
                echo "i: $i\n";
                //echo "value: $value \n";
                //echo "sql: $sql \n";
                echo "error: " . $m->getError() . "\n";
                exit;
            }
            unset($sql);
            echo $i . "\n";
            $now = $i;
        }

    }
    /**
    +----------------------------------------------------------
     * 用户反馈表迁移
    +----------------------------------------------------------
     */
    function feedbackTransfer() {
        $feedbackMod = M('lingshi.feedback','ls_');
        $nowFeedbackMod = M('feedback');
        $m = M();
        $num = 1000;
        $now = 0;
        for ($i = 1; $i <= $num;){
            $i += 1000;
            $platformResult = $feedbackMod->where('id >'.$now.' AND id <='.$i)->select();
            if (empty($platformResult)){
                $now = $i;
                continue;
            }
            $value = "";
            foreach($platformResult as $val){
                $content = $val['content'];
                $name = $val['nickname'];
                $reply_content = $val['replay_content'];
                $tmp = "'{$content}','{$val['create_time']}','{$val['client_id']}','{$name}','{$val['device_id']}','{$val['os_type']}','{$val['version']}','{$val['auid']}','{$reply_content}','{$val['replay_time']}'";
                $tmp = "($tmp),";
                //echo "tmp: $tmp\n";
                $value .= $tmp;
            }
            $value = substr($value,0,-1);
            unset($userResult);
            $sql = "INSERT INTO `ls_feedback`(`content`,`create_time`,`client_id`,`nickname`,`device_id`,`os_type`,`version`,`auid`,`reply_content`,`reply_time`) VALUES $value";
            $ret = $m->execute($sql);
            echo "ret: $ret \n";
            if ($ret <= 0) {
                echo "i: $i\n";
                //echo "value: $value \n";
                //echo "sql: $sql \n";
                echo "error: " . $m->getError() . "\n";
                exit;
            }
            unset($sql);
            echo $i . "\n";
            $now = $i;
        }
    }
    /**
    +----------------------------------------------------------
     * 补充用户设备表
    +----------------------------------------------------------
     */
    function deviceAgain() {
        $deviceMod = M('lingshi.device','ls_');
        $nowDeviceMod = M('device');
        //$array = array(1,3001,4001,5001,27001,65001,69001,87001,148001,152001,163001,168001,224001,228001,236001,254001,256001);
        $array = array(6001,271001,272001,273001,274001);
        foreach($array as $val){
            //echo "$val\n";
            $begin = $val;
            $end = $val+1000;
            //echo "$end\n";
            $res = $deviceMod->where("id > $begin AND id <= $end")->select();
            echo $deviceMod->getLastSql();
            foreach($res as $v){
                $flag = $nowDeviceMod->add($v);
                if($flag){
                    echo "$flag\n";
                }else{
                    file_put_contents(ROOT_PATH."/recordDevice.txt",var_export($v,true)."\n",FILE_APPEND);
                }
            }
        }
    }
    /**
    +----------------------------------------------------------
     * 用户收藏专题迁移
    +----------------------------------------------------------
     */
    function transferTopic() {
        $favMod = M('lingshi.favs_activity','ls_');
        $nowfavMod = M('favs');
        $array = array(
            "75" => "21",
            "78" => "28",
            "99" => "29",
            "97" => "32",
            "121" => "23",
            "145" => "45",
            "49" => "46",
            "149" => "47",
            "175" => "48",
            "52" => "49",
            "176" => "50",
            "51" => "51",
            "55" => "52",
            "148" => "53",
            "84" => "54",
            "151" => "56",
            "102" => "58",
            "154" => "59",
            "37" => "60",
            "107" => "61",
            "130" => "63",
            "157" => "64",
            "108" => "65",
            "109" => "66",
            "143" => "67",
            "114" => "68",
            "118" => "70",
            "161" => "71",
            "126" => "72",
            "162" => "73",
            "146" => "74",
            "164" => "75",
            "167" => "76",
            "127" => "77",
            "135" => "78",
            "147" => "79",
            "139" => "80",
            "142" => "81",
            "168" => "82",
            "169" => "83",
            "152" => "84",
            "155" => "85",
            "144" => "86",
            "141" => "88",
            "170" => "89",
            "158" => "90",
            "171" => "91",
            "172" => "92",
            "173" => "93",
            "165" => "94",
            "174" => "95",
        );
        $begin = 0;
        $end = 1000;
        $num = 12000;
        for($begin;$begin<=$num;){
            $favsResult = $favMod->where('id >'.$begin.' AND id <='.$end)->select();
            foreach($favsResult as $val) {
                if($array["{$val['activity_id']}"]){
                    $opt_id = $array["{$val['activity_id']}"];
                    $data = array();
                    $data['user_id'] = $val['uid'];
                    $data['opt_id'] = $opt_id;
                    $data['add_time'] = $val['add_time'];
                    $data['type'] = 1;
                    $flag = $nowfavMod->add($data);
                    if($flag){
                        echo "success: $flag\n";
                    }else{
                        echo "error: 插入失败，记录已经存在\n";
                    }
                }else{
                    echo "error: {$val['activity_id']}这个专题被遗弃了\n";
                }
            }
            $begin += 1000;
            $end += 1000;
        }
    }
    /**
    +----------------------------------------------------------
     * 商品的分类迁移到多分类表（ls_spu_cate_relation）
    +----------------------------------------------------------
     */
    public function transferCate(){
        $multipleCateMod = M('spu_cate_relation');
        $spuMod = M('spu');
        $start = 0;
        $leng = 100;
        $result = $spuMod->field('spu_id,cid')->limit("$start,$leng")->select();
        while($result){
            foreach($result as $val){
                if($val['cid']){
                    $data = array();
                    $data['spu_id'] = $val['spu_id'];
                    $data['cate_id'] = $val['cid'];
                    $multipleCateMod->add($data);
                    unset($data);
                }
            }
            $start += $leng;
            unset($result);
            $result = $spuMod->field('spu_id,cid')->limit("$start,$leng")->select();
        }
    }

}