<?php
/**
 * 手动同步教程
 * User: hhf
 * Date: 2015/10/16
 * Time: 18:02
 */
class SyncSkuAction extends Action
{
    function sync()
    {

        //$postData = $_POST['sku'];
        $data = file_get_contents("php://input");
        Log::write(var_export($data,true),Log::INFO);
//        $data = "{\"time\":\"2015-10-18 10:41:59\",\"encrypt\":\"630efd30c75f3a6c686dc5ee213267a7\",\"stocknumlist\":[{\"pronum\":1223,\"prosn\":\"A010\"}]}";
        $data = json_decode($data,true);
        $lsxm = C('SECRET_KEY');
        $xiaomiao = $lsxm['yalidisi']['secret_key'];
        $md5 = $xiaomiao.$data['time'];
        $md5 = md5($md5);
        $miaosha = array("C099","A978","D303","A060","D204","A940");//秒杀的库位号
        if($md5==$data['encrypt']){//通过验证
            $postData = $data['stocknumlist'];
            $skuMod = M('sku_list');
            foreach ($postData as $key => $val) {
                $where = array();
                $where['library_number'] = $val['prosn'];//库位号
                $saveData = array();
                $val['pronum'] = $val['pronum']>=0?$val['pronum']:0;
                $saveData['sku_stocks'] = $val['pronum'];//库存
                if($skuMod->where($where)->find()){
                    if(!in_array($val['prosn'],$miaosha)){//秒杀商品不更新库存
                        $flag = $skuMod->where($where)->save($saveData);
                        if($flag!==false){
                            $success[] = $val['prosn'];
                            $this->setActivityStocks($val['prosn'],$val['pronum']);//更新扩张表库存
                            if($val['pronum']==0){//库存为0 判断是否所有的SKU都为0 如果为0则将商品状态设为售空
                                $this->stocksIsEmpty($val['prosn']);
                            }else{//如果原来为售空状态的，加入库存之后要变成上架状态
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
        $result = $skuMod->where("library_number = '{$prosn}'")->field('spu_id,sku_id')->find();
        $where['sku_id'] = $result['sku_id'];
        $save['sku_stocks'] = 0;
        $flag = $extendMod->where($where)->save($save);//将这个SKU的商品处于特卖等其他活动的库存也改为0
        $spuInfo = spu_format($result['spu_id']);
        if($spuInfo['stocks']==0){//库存为0
            $saveData['status'] = 1;//设为售空状态
            $whereData['spu_id'] = $result['spu_id'];
            $whereData['staus'] = 0;//原来是上架状态
            $spuMod->where($whereData)->save($saveData);
        }

    }

    function stocksNotEmpty($prosn){//原来是售空状态的，现在加入库存之后要变成上架
        $skuMod = M('sku_list');
        $spuMod = M('spu');
        $result = $skuMod->where("library_number = '{$prosn}'")->field('spu_id')->find();
        $where['status'] = 1;//售空
        $where['spu_id'] = $result['spu_id'];
        $saveData['status'] = 0;
        $spuMod->where($where)->save($saveData);
    }

    function setActivityStocks($prosn,$num){
        $skuMod = M('sku_list');
        $extendMod = M('extend_special');
        $result = $skuMod->where("library_number = '{$prosn}'")->field('sku_id')->find();
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