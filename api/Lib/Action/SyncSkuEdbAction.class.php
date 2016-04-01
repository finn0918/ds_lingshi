<?php
/**
 * 同步E店宝数据
 * User: nangua
 * Date: 2015/12/03
 * Time: 18:02
 */
class SyncSkuEdbAction extends Action
{
    function sync()
    {
        $data = file_get_contents("php://input");
        Log::write("接收参数JSON串：".$data,Log::INFO);
        $data = json_decode($data,true);

        Log::write("json转化后的数据".var_export($data,true),Log::INFO);

        $lsxm = C('SECRET_KEY');
        $xiaomiao = $lsxm['edb']['secret_key'];//echo 'my_secrpt'.$xiaomiao;
        $md5 = $xiaomiao.$data['time'];//echo 'my_data'.$md5;
        $md5 = md5($md5);//echo 'my_en'.$md5;exit;
        Log::write("原始数据".$xiaomiao.$data['time'].";md5后的结果".$md5,Log::INFO);
        $miaosha = array("C099","A978","D303","A060","D204","A940");//秒杀的库位号
        if($md5==$data['encrypt']){//通过验证
            $postData = $data['stocknumlist'];
            $skuMod = M('sku_list');
            //这里对还未付款的订单中的商品数量进行查询
            $sub_order = M('sub_order');
            $order_sku = $sub_order->query('SELECT SUM(nums)as nums,sku_id FROM `ls_sub_order` WHERE `status`=0 GROUP BY sku_id');
            foreach ($postData as $key => $val) {
                $where = array();
                $where['sku_number'] = $val['prosn'];//库位号
                $saveData = array();
                $val['pronum'] = $val['pronum']>=0?$val['pronum']:0;
                $saveData['sku_stocks'] = $val['pronum'];//库存
                $sku_info = $skuMod->query("SELECT * FROM `ls_sku_list` WHERE sku_number='".$val['prosn']."'");
                //print_r($sku_info);exit;
                if(isset($sku_info[0]['sku_id']) && $sku_info[0]['sku_id']){
                    if(!in_array($val['prosn'],$miaosha)){//秒杀商品不更新库存
                        //使用e店宝的库存将未付款的订单的商品数量相减就是保留在数据库中的小喵的后台库存
                        foreach ($order_sku as $k=>$v){
                            if ($v['sku_id'] == $sku_info[0]['sku_id']) {
                                $saveData['sku_stocks'] = $saveData['sku_stocks']-$v['nums'];
                            }
                        }
                        $saveData['sku_stocks'] = ($saveData['sku_stocks']>0)?$saveData['sku_stocks']:0;
                        $flag = $skuMod->where($where)->save($saveData);
                        if ($flag !== false) {
                            $success[] = $val['prosn'];
                            $this->setActivityStocks($val['prosn'], $val['pronum']);//更新扩张表库存
                            if ($val['pronum'] == 0) {//库存为0 判断是否所有的SKU都为0 如果为0则将商品状态设为售空
                                $this->stocksIsEmpty($val['prosn']);
                            } else {//如果原来为售空状态的，加入库存之后要变成上架状态
                                    $this->stocksNotEmpty($val['prosn']);
                            }
                        }
                    }
                }else{//找不到这个库位号
                    $notFound[] = $val['prosn'];
                }
                $prosnArray[] = $val['prosn'];
            }
            $resStr = "'".implode("','",$prosnArray)."'";
            $successStr = "'".implode("','",$success)."'";
            $notFoundStr = "'".implode("','",$notFound)."'";
            Log::write("手动同步库存接收到的数据：".var_export($postData,true),Log::INFO);
            Log::write("成功手动同步库存的库位号（库存有变动的库位号）：$successStr",Log::INFO);
            Log::write("我们数据库中没有的库位号：$notFoundStr",Log::INFO);
            Log::write("我们数据库中没有的库位号：$notFoundStr",Log::WARN);
            Log::write("手动同步库存接收的所有库位号：$resStr",Log::INFO);
            $answer = array('flag'=>1,'msg'=>'success');
            die(json_encode($answer));
        }else{
            $answer = array('flag'=>0,'msg'=>'error');
            die(json_encode($answer));
        }
    }

    function stocksIsEmpty($prosn){
        $skuMod = M('sku_list');
        $spuMod = M('spu');
        $extendMod = M('extend_special');
        $result = $skuMod->where("sku_number = '{$prosn}'")->field('spu_id,sku_id')->select();
        foreach($result as $value){
            $where['sku_id'] = $value['sku_id'];
            $save['sku_stocks'] = 0;
            $flag = $extendMod->where($where)->save($save);//将这个SKU的商品处于特卖等其他活动的库存也改为0
            $spuInfo = spu_format($value['spu_id']);
            if($spuInfo['stocks']==0){//库存为0
                $saveData['status'] = 1;//设为售空状态
                $whereData['spu_id'] = $value['spu_id'];
                $whereData['status'] = 0;//原来是上架状态
                $spuMod->where($whereData)->save($saveData);
            }
        }
    }

    function stocksNotEmpty($prosn){//原来是售空状态的，现在加入库存之后要变成上架
        $skuMod = M('sku_list');
        $spuMod = M('spu');
        $result = $skuMod->where("sku_number = '{$prosn}'")->field('spu_id')->select();
        foreach($result as $v){
            $where['status'] = 1;//售空
            $where['spu_id'] = $v['spu_id'];
            $saveData['status'] = 0;
            $spuMod->where($where)->save($saveData);
        }
    }

    function setActivityStocks($prosn,$num){
        $skuMod = M('sku_list');
        $extendMod = M('extend_special');
        $result = $skuMod->where("sku_number = '{$prosn}'")->field('sku_id')->find();
        if($result["sku_id"]){//找到相关sku记录
            $where['sku_id'] = $result["sku_id"];
            $where['sku_stocks'] = array("gt",$num);
            $save['sku_stocks'] = $num;
            $flag = $extendMod->where($where)->save($save);
            if($flag!=false){
                Log::write("同步扩张表：SKUID：{$result["sku_id"]},更新的库存为：{$num}",Log::INFO);
            }
        }
    }

}