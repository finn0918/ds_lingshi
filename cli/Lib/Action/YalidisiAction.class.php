<?php

/**
 * 亚莉蒂斯库存对接
 * Author: hhf
 * Date: 2015/11/3
 * Time: 10:40
 */
class YalidisiAction extends Action
{
    /**
     * +----------------------------------------------------------
     * 组合售卖的商品的库位号比较特殊，需要拆分后，再得到库存量
     * 第一种类型：30*D102 一个库位号商品，多个绑定一起卖
     * 第二种类型：5*F453-4*E409 多个库位号商品，多个绑定一起卖
     * +----------------------------------------------------------
     */
    public function syncSku()
    {
        $yalidisi = C("SECRET_KEY");//读取相关配置
        $now_time = date("Y-m-d H:i:s");
        $code = $yalidisi['yalidisi']['secret_key'];
        $sellername = $yalidisi['yalidisi']['sellername'];
        $url = $yalidisi['yalidisi']['wsdl'];
        $address = array(
            "province" => "福建省",
            "city" => "厦门市",
            "proper" => "思明区",
            "street" => "软件园二期观日路46号"
        );
        $skuMod = M("sku_list");
        $where['library_number'] = array("like", "%*%");
        //$where['suppliers_id'] = 8;//小喵自营 就是亚莉蒂斯的商品
        $syncResult = $skuMod->where($where)->field("library_number")->select();
        var_dump($syncResult);
        foreach ($syncResult as $key => $val) {
            if (strpos($val["library_number"], "-") !== false) {//第二种类型
                $tmp = explode("-", $val["library_number"]);
                $minStocks = array();
                $libraryNo = false;
                foreach ($tmp as $v) {
                    $productlist = array();
                    $libraryTmp = explode("*", $v);
                    if(!isset($libraryTmp[1])){//出现A234-D543的情况
                        $libraryTmp[1] = $v;
                        $libraryTmp[0] = 1;
                    }
                    $productlist[] = array(
                        'prosn' => "{$libraryTmp[1]}",
                        "num" => "1"
                    );
                    if (!empty($productlist)) {
                        $data = array(
                            "encrypt" => md5($code . $now_time),
                            "time" => $now_time,
                            "sellername" => $sellername,
                            "province" => $address['province'],
                            "city" => $address['city'],
                            "district" => $address['proper'],
                            "address" => $address['street'],
                            "productlist" => $productlist
                        );
                        $data = array("in0" => json_encode($data));
                        $return = $this->_soapCall($url, $data, "findAreaAndStock");
                        var_dump($return);
                        if ($return["stocklist"] && $return["success"] == "true") {
                            if($return["hasstock"] == "0"){
                                $minStocks[] = 0;
                            }else{
                                $nowStocks = $return["stocklist"][0]['stock']<0?0:$return["stocklist"][0]['stock'];
                                $minStocks[] = floor($nowStocks / $libraryTmp[0]);
                            }
                        } else {
                            $libraryNo = true;//出现不存在的库位号
                            var_dump("不存在啊，".$libraryTmp[1]);
                        }
                    }
                }
                if (!$libraryNo) {//没有出现不存在的库位号才更新库存
                    $finalStocks = min($minStocks);//找出最小库存量，作为整个套装的库存
                    $saveData = array();
                    $saveData['sku_stocks'] = $finalStocks;
                    $whereData['library_number'] = $val["library_number"];
                    $skuMod->where($whereData)->save($saveData);
                    $this->setActivityStocks($val["library_number"],$finalStocks);//更新扩张表库存
                    if ($finalStocks == 0) {//库存为0 判断是否所有的SKU都为0 如果为0则将商品状态设为售空
                        $this->stocksIsEmpty($val["library_number"]);
                    } else {//如果原来为售空状态的，加入库存之后要变成上架状态
                        $this->stocksNotEmpty($val["library_number"]);
                    }
                }
            } else {//第一种类型
                $productlist = array();
                $tmp = explode("*", $val["library_number"]);
                $productlist[] = array(
                    'prosn' => "{$tmp[1]}",
                    "num" => "1"
                );
                if (!empty($productlist)) {
                    $data = array(
                        "encrypt" => md5($code . $now_time),
                        "time" => $now_time,
                        "sellername" => $sellername,
                        "province" => $address['province'],
                        "city" => $address['city'],
                        "district" => $address['proper'],
                        "address" => $address['street'],
                        "productlist" => $productlist
                    );
                    $data = array("in0" => json_encode($data));
                    $return = $this->_soapCall($url, $data, "findAreaAndStock");
                    if ($return["stocklist"] && $return["success"] == "true") {
                        if($return["hasstock"] == "0"){
                            $nowStocks = 0;
                        }else{
                            $nowStocks = $return["stocklist"][0]['stock']<0?0:$return["stocklist"][0]['stock'];
                        }
                        $saveData = array();
                        $saveData['sku_stocks'] = floor($nowStocks / $tmp[0]);
                        $whereData['library_number'] = $val["library_number"];
                        $skuMod->where($whereData)->save($saveData);
                        $this->setActivityStocks($val["library_number"],$saveData['sku_stocks']);//更新扩张表库存
                        if ($nowStocks == 0) {//库存为0 判断是否所有的SKU都为0 如果为0则将商品状态设为售空
                            $this->stocksIsEmpty($val["library_number"]);
                        } else {//如果原来为售空状态的，加入库存之后要变成上架状态
                            $this->stocksNotEmpty($val["library_number"]);
                        }
                    }
                }
            }
        }
    }

    /**
     * +----------------------------------------------------------
     * Soap 版发起请求会话调用webServer 服务
     * +----------------------------------------------------------
     */
    private function _soapCall($url, $data, $method)
    {
        $client = new SoapClient($url);
        $data = $client->$method($data);
        $respon = json_decode($data->out, true);// 转成array 非object 根据wsdl 文档访问值包在out属性内
        return $respon;
    }

    /**
     * +----------------------------------------------------------
     * //如果返回库存为0，要设置商品为售空
     * +----------------------------------------------------------
     */
    function stocksIsEmpty($prosn)
    {
        $skuMod = M('sku_list');
        $spuMod = M('spu');
        $extendMod = M('extend_special');
        $result = $skuMod->where("library_number = '{$prosn}'")->field('spu_id,sku_id')->find();
        $where['sku_id'] = $result['sku_id'];
        $save['sku_stocks'] = 0;
        $flag = $extendMod->where($where)->save($save);//将这个SKU的商品处于特卖等其他活动的库存也改为0
        $spuInfo = spu_format($result['spu_id']);
        if ($spuInfo['stocks'] == 0) {//库存为0
            $saveData['status'] = 1;//设为售空状态
            $whereData['spu_id'] = $result['spu_id'];
            $whereData['status'] = 0;//原来是上架状态
            $spuMod->where($whereData)->save($saveData);
        }
    }

    /**
     * +----------------------------------------------------------
     * //原来是售空状态的，现在加入库存之后要变成上架
     * +----------------------------------------------------------
     */
    function stocksNotEmpty($prosn)
    {
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