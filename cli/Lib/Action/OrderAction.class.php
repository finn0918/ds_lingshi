<?php
/**
 * 未付款订单回滚
 * @author: hhf
 *
 */

class OrderAction extends Action{
    /**
    +----------------------------------------------------------
     * 未付款订单的库存销量回滚
    +----------------------------------------------------------
     */
    public function backOrder () {
        $orderMod = M('order');
        $subOrderMod = M('sub_order');
        $extendMod = M('extend_special');
        $skuMod = M('sku_list');
        $spuMod = M('spu');
        $m = M();
        $m->startTrans();
        $time = time();
        $backTime = $time-86400;//1天以前
        //$backTime = $time;//3天以前
        $orderWhere = array();
        $orderWhere['add_time'] = array('elt',$backTime);//下单时间早于三天前
        $orderWhere['status'] = 0;
        $orderResult = $orderMod->where($orderWhere)->field('order_sn')->select();
        $skuIds = array();
        foreach($orderResult as $val){
            $subWhere = array();
            $subWhere['order_sn'] = $val['order_sn'];
            $subResult = $subOrderMod->where($subWhere)->field('sku_id,nums,price')->select();//得到sku_id和对应的数量
            foreach($subResult as $v){
                $skuIds[] = $v['sku_id'];
                $map = array();
                $map_modify = array();
                $map['sku_id'] = $v['sku_id'];//先去扩展表里面看活动有没有过期
                $map['price'] = $v['price'];
                $extendResult = $extendMod->where($map)->find();
                if($extendResult){//在活动表

                    $map_modify['sku_id'] = array("eq",$v['sku_id']);
                    $map_modify['price']  = array("eq",$v['price']);
                    $map_modify['sku_sale'] = array("egt",$v['nums']);

                    $extendFlagInc = $extendMod->where($map)->setInc('sku_stocks',$v['nums']);//库存加
                    $extendFlagDec = $extendMod->where($map_modify)->setDec('sku_sale',$v['nums']);//销量减
                    if(!$extendFlagDec){
                        $extendMod->where($map)->setField("sku_sale",0);
                    }
                    if($extendFlagInc===false){
                        $m->rollback();
                    }
                }else{//不在活动表
                    $skuWhere = array();
                    $skuWhere['sku_id'] = $v['sku_id'];
                    $skuFlagInc = $skuMod->where($skuWhere)->setInc('sku_stocks',$v['nums']);//库存加

                    $map_modify['sku_id'] = array("eq",$v['sku_id']);
                    $map_modify['sku_sale'] = array("egt",$v['nums']);
                    $skuFlagDec = $skuMod->where($map_modify)->setDec('sku_sale',$v['nums']);//销量减
                    if(!$skuFlagDec){
                        $extendMod->where($map)->setField("sku_sale",0);
                    }
                    if($skuFlagInc===false){
                        $m->rollback();
                    }
                }
            }
            $subOrderSave['status'] = 7;//变成关闭状态
            $subSaveFlag = $subOrderMod->where($subWhere)->save($subOrderSave);//子单变成交易关闭状态
            $orderSaveFlag = $orderMod->where($subWhere)->save($subOrderSave);//主单变成交易关闭状态
            if($subSaveFlag===false||$orderSaveFlag===false){
                $m->rollback();
            }
        }
        $skuIds = array_unique($skuIds);
        $where = array();
        $where['sku_id'] = array('in',$skuIds);
        $skuResult = $skuMod->where($where)->field('spu_id')->select();
        $spuIds = array();
        foreach($skuResult as $key=>$val){
            $spuIds[] = $val['spu_id'];
        }
        $spuIds = array_unique($spuIds);
        $spuIds = implode(',',$spuIds);
        $spuData = array();
        $spuData['status'] = 0;
        $spuMod->where("spu_id in ($spuIds) AND status = 1")->save($spuData);
        $m->commit();
    }
}