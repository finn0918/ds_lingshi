<?php
/**
 * 定时跑扩展表，将过期商品的销量，库存回滚
 * @author hhf
 *
 */
class ExtendAction extends Action
{
    /**
    +----------------------------------------------------------
     * 定时跑扩展表，将过期商品的销量，库存回滚
    +----------------------------------------------------------
     */
    public function extendBak() {
        $extendMod = M('extend_special');//扩展表
        $skuMod = M('sku_list');//sku表
        $topicRelationMod = M('topic_relation');
        $topicMod = M('topic');
        $spuMod = M('spu');
        $m = M();
        $m->startTrans();
        $time = time();//当前时间
        $where = array();//条件
        $where['end_time'] = array('lt',$time);//时间过期
        $extendResult = $extendMod->where($where)->select();
        $delIds = array();
        $skuIds = array();
        foreach($extendResult as $key=>$val){
            if($val['sku_id']){
                if($val['type']==1){//限时活动
                    $save = array();
                    $save['sku_id'] = $val['sku_id'];
                    $save['sku_stocks'] = $val['sku_stocks'];
                    $save['sku_sale'] = $val['sku_sale'];
                    $flag = $skuMod->save($save);//销量，库存回滚
                    $skuIds[] = $val['sku_id'];
                    if($flag!==false){
                        $backFlag = $this->backExtend($val);
                        if($backFlag){
                            $delIds[] = $val['id'];
                            $delFlag = $extendMod->delete($val['id']);
                            if($delFlag===false){
                                $m->rollback();
                            }
                        }else{
                            $m->rollback();
                        }
                    }else{
                        $m->rollback();
                    }
                }else if($val['type']==2){//限时限量活动
                    $where = array();
                    $where['sku_id'] = $val['sku_id'];
                    $flagStock = $skuMod->where($where)->setInc('sku_stocks',$val['sku_stocks']);//库存回滚
                    $flagSale = $skuMod->where($where)->setInc('sku_sale',$val['sku_sale']);//库存回滚
                    $skuIds[] = $val['sku_id'];
                    if($flagStock!==false&&$flagSale!==false){
                        $backFlag = $this->backExtend($val);
                        if($backFlag){
                            $delIds[] = $val['id'];
                            $delFlag = $extendMod->delete($val['id']);
                            if($delFlag===false){
                                $m->rollback();
                            }
                        }else{
                            $m->rollback();
                        }
                    }else{
                        $m->rollback();
                    }
                }
            }
        }
        $delIds = implode(',',$delIds);
        $where = array();
        $where['atom_id'] = array('in',$delIds);
        $topicResult = $topicRelationMod->where($where)->select();
        $topicIds = array();
        foreach($topicResult as $key=>$val){
            $backFlag = $this->backRelation($val);
            if($backFlag){
                $delFlag = $topicRelationMod->delete($val['rid']);
                if($delFlag===false){
                    $m->rollback();
                }
                $topicIds[] = $val['topic_id'];
            }else{
                $m->rollback();
            }
        }
        $topicIds = implode(',',$topicIds);
        $topicBrandResult = $topicMod->where("topic_id in ($topicIds) and type = 2 and topic_id>6")->select();//品牌团
        foreach($topicBrandResult as $val){
            $backFlag = $this->backTopic($val);
            if($backFlag){
                $delFlag = $topicMod->delete($val['topic_id']);
                if($delFlag===false){
                    $m->rollback();
                }
            }else{
                $m->rollback();
            }
        }
        $skuIds = array_unique($skuIds);
        $skuIds = implode(',',$skuIds);
        $spuResult = $skuMod->where("sku_id in ($skuIds)")->field('spu_id')->select();
        $spuIds = array();
        foreach($spuResult as $val){
            $spuIds[] = $val['spu_id'];
        }
        $spuIds = array_unique($spuIds);
        $spuIds = implode(',',$spuIds);
        $spuSave = array();
        $spuSave['status'] = 0;
        $spuMod->where("spu_id in ($spuIds) AND status = 1")->save($spuSave);
        //开始清除专题关系表中处于活动的淘宝客商品
        $where = array();
        $where['end_time'] = array('lt',$time);
        $where['atom_id'] = array('gt',0);
        $where['type'] = 0;
        $relationResult = $topicRelationMod->where($where)->select();
        $topicArray = array();
        foreach($relationResult as $val){
            $backFlag = $this->backRelation($val);
            $topicArray[] = $val['topic_id'];
            if($backFlag){
                $delFlag = $topicRelationMod->delete($val['rid']);
                if($delFlag===false){
                    $m->rollback();
                }
            }else{
                $m->rollback();
            }
        }
        //如果品牌团中的所有商品都是淘宝客，就要通过以下逻辑把品牌团删除
        $topicStr = implode(',',$topicArray);
        $brandResult = $topicMod->where('topic_id in ('.$topicStr.') AND type = 2 AND topic_id >6')->select();//查找符合条件的品牌团
        if($brandResult){
            foreach($brandResult as $val){
                $backFlag = $this->backTopic($val);
                if($backFlag){
                    $delFlag = $topicMod->delete($val['topic_id']);
                    if($delFlag===false){
                        $m->rollback();
                    }
                }else{
                    $m->rollback();
                }
            }
        }
        $m->commit();
    }

    /**
    +----------------------------------------------------------
     * 定时跑topic_relation 把今日特卖（topic_id=6） 每日上新（topic_id=5）的商品关系解散
     * 将扩张表里面的活动库存和销量回滚
    +----------------------------------------------------------
     */
    function extend(){
        $extendMod = M('extend_special');//扩展表
        $skuMod = M('sku_list');//sku表
        $topicRelationMod = M('topic_relation');
        $spuMod = M('spu');
        $m = M();
        $m->startTrans();
        $time = time();//当前时间
        $where = array();//条件
        $where['end_time'] = array('lt',$time);//时间过期
        $where['topic_id'] = array('in','5,6');
        $topicRelationResult = $topicRelationMod->where($where)->select();
        var_dump($topicRelationResult);
        $atomIds = array();
        foreach($topicRelationResult as $val){
            if($val['type']==2){
                $atomIds[] = $val['atom_id'];
            }
            $backFlag = $this->backRelation($val);
            if($backFlag){
                $delFlag = $topicRelationMod->delete($val['rid']);
                if($delFlag===false){
                    $m->rollback();
                }
            }else{
                $m->rollback();
            }
        }
        $where = array();
        $where['id'] = array('in',$atomIds);
        $entendResult = $extendMod->where($where)->select();
        $skuIds = array();
        foreach($entendResult as $key=>$val){
            if($val['sku_id']){
                if($val['type']==1){//限时活动
                    $save = array();
                    $save['sku_id'] = $val['sku_id'];
                    //$save['sku_stocks'] = $val['sku_stocks'];
                    $save['sku_sale'] = $val['sku_sale'];
                    $flag = $skuMod->save($save);//销量，库存回滚
                    $skuIds[] = $val['sku_id'];
                    if($flag!==false){
                        $backFlag = $this->backExtend($val);
                        if($backFlag){
                            $delFlag = $extendMod->delete($val['id']);
                            if($delFlag===false){
                                $m->rollback();
                            }
                        }else{
                            $m->rollback();
                        }
                    }else{
                        $m->rollback();
                    }
                }else if($val['type']==2){//限时限量活动
                    $where = array();
                    $where['sku_id'] = $val['sku_id'];
                    $flagStock = $skuMod->where($where)->setInc('sku_stocks',$val['sku_stocks']);//库存回滚
                    $flagSale = $skuMod->where($where)->setInc('sku_sale',$val['sku_sale']);//库存回滚
                    $skuIds[] = $val['sku_id'];
                    if($flagStock!==false&&$flagSale!==false){
                        $backFlag = $this->backExtend($val);
                        if($backFlag){
                            $delFlag = $extendMod->delete($val['id']);
                            if($delFlag===false){
                                $m->rollback();
                            }
                        }else{
                            $m->rollback();
                        }
                    }else{
                        $m->rollback();
                    }
                }
            }
        }
        $where = array();
        $where['sku_id'] = array('in',$skuIds);
        $where['sku_stocks'] = array('gt',0);
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

    /**
    +----------------------------------------------------------
     * 扩展表记录在删除之前备份一份到扩展历史记录表
    +----------------------------------------------------------
     */
    public function backExtend($extendResult) {
        $extendHistoryMod = M('extend_special_history');//扩展历史记录表
        $extendResult['ori_id'] = $extendResult['id'];
        unset($extendResult['id']);
        $flag = $extendHistoryMod->add($extendResult);
        return $flag;
    }

    /**
    +----------------------------------------------------------
     * 过期的活动从topic_relation备份到topic_relation_history
    +----------------------------------------------------------
     */
    public function backRelation($TopicResult) {
        $relationHistoryMod = M('topic_relation_history');
        unset($TopicResult['rid']);
        $flag = $relationHistoryMod->add($TopicResult);
        return $flag;
    }
    /**
    +----------------------------------------------------------
     * 删除topic表里面的品牌团商品之前，先备份
    +----------------------------------------------------------
     */
    public function backTopic($TopicResult) {
        $topicHistoryMod = M('topic_history');
        $flag = $topicHistoryMod->add($TopicResult);
        return $flag;
    }

}
?>