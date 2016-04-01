<?php
/**
 * 定时发布和下架
 * @author: hhf
 *
 */

class PublishAction extends Action{

    /**
    +----------------------------------------------------------
     * 定时发布下架商品，专题,特卖，广告
    +----------------------------------------------------------
     */
    public function cronPublish (){
        $spuMod = M('spu');
        $topicMod = M('topic');
        $adMod = M('ad');
        $topicRelationMod = M('topic_relation');
        $time = time();
        $spuWhere = array();
        $spuWhere['publish_time'] = array('elt',$time);//到了发布时间
        $spuWhere['status'] = 3;//商品当前是定时状态
        $spuSave = array();
        $spuSave['status'] = 0;//商品状态改为上架
        $flagPublish = $spuMod->where($spuWhere)->save($spuSave);//商品发布
        $topicWhere = array();
        $topicWhere['end_time'] = array('elt',$time);//专题时间过期
        $topicWhere['type'] = array('egt',1);//普通专题和品牌团
        $topicWhere['status'] = 'published';//专题处于发布状态
        $topicSave = array();
        $topicSave['status'] = 'locked';//过期专题状态改为锁定
        $flagLocked = $topicMod->where($topicWhere)->save($topicSave);//专题下架
        //品牌团下面的商品同时也要改为下架状态
        $topicWhere = array();
        $topicWhere['status'] = 'locked';
        $topicWhere['type'] = 2;
        $topicResult = $topicMod->where($topicWhere)->field('topic_id')->select();
        $topicIds = '';
        foreach($topicResult as $val){
            $topicIds .= $val['topic_id'].',';
        }
        $topicIds = substr($topicIds,0,-1);
        $topicSave = array();
        $topicSave['status'] = 'locked';//专题关联商品状态改为发布状态
        $topicRelationLocked = $topicRelationMod->where("topic_id in ($topicIds) AND status = 'published'")->save($topicSave);
        $topicWhere = array();
        $topicWhere['publish_time'] = array('elt',$time);//专题到了发布时间
        $topicWhere['type'] = array('egt',1);//普通专题和品牌团
        $topicWhere['status'] = 'scheduled';//专题处于定时状态
        $topicSave = array();
        $topicSave['status'] = 'published';//专题状态改为发布状态
        $flagPublished = $topicMod->where($topicWhere)->save($topicSave);//专题上架
        //品牌团下面的商品同时也要改为发布状态
        $topicWhere = array();
        $topicWhere['status'] = 'published';
        $topicWhere['type'] = 2;
        $topicResult = $topicMod->where($topicWhere)->field('topic_id')->select();
        $topicIds = '';
        foreach($topicResult as $val){
            $topicIds .= $val['topic_id'].',';
        }
        $topicIds = substr($topicIds,0,-1);
        $topicSave = array();
        $topicSave['status'] = 'published';//专题关联商品状态改为发布状态
        $topicRelationPublish = $topicRelationMod->where("topic_id in ($topicIds) AND status = 'scheduled'")->save($topicSave);
        $adWhere = array();
        $adWhere['publish_time'] = array('elt',$time);//广告到了发布时间
        $adWhere['status'] = 'scheduled';//广告处于定时状态
        $adSave = array();
        $adSave['status'] = 'published';//过期广告状态改为发布状态
        $flagAdPublish = $adMod->where($adWhere)->save($adSave);//广告上架
        $adWhere = array();
        $adWhere['end_time'] = array('elt',$time);//广告到了下架时间
        $adWhere['status'] = 'published';//广告处于发布状态
        $adSave = array();
        $adSave['status'] = 'locked';//过期广告状态改为锁定状态
        $flagAdOff = $adMod->where($adWhere)->save($adSave);//广告下架
        $topicRelationWhere = array();
        $topicRelationWhere['publish_time'] = array('elt',$time);//特卖到了上架时间
        $topicRelationWhere['status'] = 'scheduled';//特卖处于定时状态
        $topicRelationWhere['topic_id'] = 6;
        $topicRelationSave = array();
        $topicRelationSave['status'] = "published";//状态改为已经发布
        $flagSalePublish = $topicRelationMod->where($topicRelationWhere)->save($topicRelationSave);
        $topicRelationWhere = array();
        $topicRelationWhere['end_time'] = array('elt',$time);//特卖到了下架时间
        $topicRelationWhere['status'] = 'published';//特卖处于发布状态
        $topicRelationWhere['topic_id'] = 6;
        $topicRelationSave = array();
        $topicRelationSave['status'] = "locked";//状态改为下架锁定
        $flagSaleOff = $topicRelationMod->where($topicRelationWhere)->save($topicRelationSave);
        $topicRelationWhere = array();
        $topicRelationWhere['publish_time'] = array('elt',$time);//每日上新到了上架时间
        $topicRelationWhere['status'] = 'scheduled';//每日上新处于定时状态
        $topicRelationWhere['topic_id'] = 5;
        $topicRelationSave = array();
        $topicRelationSave['status'] = "published";//状态改为已经发布
        $flagDayPublish = $topicRelationMod->where($topicRelationWhere)->save($topicRelationSave);
        $topicRelationWhere = array();
        $topicRelationWhere['end_time'] = array('elt',$time);//每日上新到了下架时间
        $topicRelationWhere['status'] = 'published';//每日上新处于发布状态
        $topicRelationWhere['topic_id'] = 5;
        $topicRelationSave = array();
        $topicRelationSave['status'] = "locked";//状态改为下架锁定
        $flagDayOff = $topicRelationMod->where($topicRelationWhere)->save($topicRelationSave);
        // nangua 增加包邮信息上下架
        $free_fee_mod = M("free_fee_template");
        $free_fee_mod->where("status = 'scheduled' and start_time <= $time and end_time >= $time ")->setField("status",'published');
        $free_fee_mod->where("end_time <= $time ")->setField("status","locked");
        // nangua 增加包邮信息上下架

        // 弹窗自动上下架
        $pop_mod = M("pop");
        $pop_data = $pop_mod->where("status = 'scheduled' and start_time <= $time and end_time >= $time ")->getField("id");
        if(!empty($pop_data)){
            $pop_mod->where("status = 'published' ")->setField("status",'locked');
            $pop_mod->where("id = {$pop_data['id']} ")->setField("status","published");
        }
        $pop_mod->where("end_time <= $time ")->setField("status","locked");

        //新用户免邮活动上下架
        $clientFreeShippingMod = M('client_free_shipping');
        $time = time();
        //上架
        $wherePublish['status'] = 'scheduled';
        $wherePublish['start_time'] = array('elt', $time);
        $wherePublish['end_time'] = array('egt', $time);
        $dataPublish['status'] = 'published';
        $clientFreeShippingMod->where($wherePublish)->save($dataPublish);
        //下架
        $whereCancel['status'] = 'published';
        $whereCancel['end_time'] = array('lt', $time);
        $dataCancel['status'] = 'locked';
        $clientFreeShippingMod->where($whereCancel)->save($dataCancel);

        $topicKey = C('LS_TOPIC_LIST');//专题列表
        $brandKey = C('LS_BRAND_SPU_LIST');//品牌团
        $dayKey = C('LS_DAILY_ON_NEW');//每日上新
        $popKey = C("LS_POP");//弹窗缓存
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis',$options);
        $redis->rm($topicKey);
        $redis->rm($dayKey);
        $redis->rm($brandKey."*");
        $redis->rm($popKey);
        $redis->close();
    }


}