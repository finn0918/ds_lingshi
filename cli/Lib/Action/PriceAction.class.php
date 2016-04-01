<?php
/**
 * 定时更改淘宝商品的价格
 * @author: hhf
 *
 */

class PriceAction extends Action{
    /**
    +----------------------------------------------------------
     * 获取最新的价格
    +----------------------------------------------------------
     */
    public function getNewPrice($id){
        $url = 'http://hws.m.taobao.com/cache/wdetail/5.0/?id='.$id."&ttid=2014_uxiaomi_23183641%2540baichuan_android_1.5.1";//淘宝抓取请求地址
        $ch = curl_init();
        $timeout = 5;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $html = curl_exec($ch);
        curl_close($ch);
        if(!$html){
            Log::write("商品淘宝id为{$id}的商品抓取价格失败",Log::DEBUG);
            return false;
        }
        $a_html = json_decode($html,true);
        $a_stack = json_decode($a_html['data']['apiStack'][0]['value'], true);
        $delivery = $a_stack['data']['delivery']['deliveryFees'];
        if(strpos($delivery[0],"包邮",0)!==FALSE){
            $deliverys = 1;//包邮
        }else{
            $deliverys = 0;//不包邮
        }
        $res = array();
        if(isset($a_stack['data']['itemInfoModel']['priceUnits'][0]['price'])){
            $res['priceNow'] =  $a_stack['data']['itemInfoModel']['priceUnits'][0]['price'];//当前价格
            $res['priceOld'] =  $a_stack['data']['itemInfoModel']['priceUnits'][1]['price'];//原来价格
            if(!$res['priceOld']){
                $res['priceOld'] = $res['priceNow'];
            }
            if($a_stack['data']['itemControl']['unitControl']['cartSupport']=="false"){
                $res['errorMessage'] = "已下架";
            }
        }else{
            return false;
        }
        $res['delivery'] = $deliverys;
        return $res;
    }

    /**
    +----------------------------------------------------------
     * 定期更新
    +----------------------------------------------------------
     */
    public function changePrice(){
        echo microtime(true)."\n";
        $filterMod = M('filter_goods');
        $spuMod = M('spu');
        $filterResult = $filterMod->field('item_id,gid')->select();//item_id是淘宝客id，gid是商品在spu表的id
        foreach($filterResult as $val){
            $where = array();
            $save = array();
            $where['spu_id'] = $val['gid'];
            $where['guide_type'] = array('gt',0);
            $newPrice = $this->getNewPrice($val['item_id']);
            if($newPrice === false){
                continue;
            }
            if($newPrice['priceOld']){
                $sign = strpos($newPrice['priceOld'],'-',0);//有价格区间的取低价
                if($sign){
                    $newPrice['priceOld'] = substr($newPrice['priceOld'],0,$sign);
                }
                $save['price_old'] = $newPrice['priceOld']*100;
            }
            if($newPrice['priceNow']){
                $sign = strpos($newPrice['priceNow'],'-',0);//有价格区间的取低价
                if($sign){
                    $newPrice['priceNow'] = substr($newPrice['priceNow'],0,$sign);
                }
                $save['price_now'] = $newPrice['priceNow']*100;
            }
            if($newPrice['delivery']){
                $save['shipping_free'] = 1;
            }else{
                $save['shipping_free'] = 0;
            }
            if($newPrice['errorMessage']){//如果淘宝上的商品已经下架，我们也采取下架措施
                $save['status'] = 2;
            }
            $flag = $spuMod->where($where)->save($save);
            if($flag!==false){
                //echo "更新成功";
            }
        }
        echo microtime(true)."\n";
    }
}
