<?php
/**
 * 推送数据模块
 * @author feibo
 *
 */
class PushdataAction extends Action{

   /**
     +----------------------------------------------------------
     * 获取iOS用户
     +----------------------------------------------------------
     */
	public function getIosUser($list=false) {
		$push_ios = M('push_ios');
		if($list){
			$user_array = explode(',',$list);
			$user_mod = M('client');
			$where['user_id'] = array('in',$user_array);
			$device_ids = $user_mod->where($where)->field('last_device_id')->select();
			foreach($device_ids as $k=>$v){
				$device[] = $v['last_device_id'];
			}
			$map['device_id'] = array('in',$device);
            $push_ios->where($map)->setInc('red_num',1);//新的推送，红点数量加1
			$data = $push_ios->where($map)->field('code,red_num')->select();
		}else{
            $push_ios->setInc('red_num',1);//新的推送，红点数量加1
			$data = $push_ios->field('code,red_num')->select();
		}

		return $data;

	}

	/**
     +----------------------------------------------------------
     * 获取android用户指定列表
     +----------------------------------------------------------
     */
	public function getAndroidUser($list) {
		//接收方1
		$user_array = explode(',',$list);
		$user_mod = M('client');
		$where['user_id'] = array('in',$user_array);
		$device_ids = $user_mod->where($where)->field('last_device_id')->select();
		foreach($device_ids as $k=>$v){
			$device[] = $v['last_device_id'];
		}
		$map['device_id'] = array('in',$device);
		$push_android = M('push_android');
		$data = $push_android->where($map)->field('code')->select();
		return $data;
	}

	/**
     +----------------------------------------------------------
     * 推送信息
     +----------------------------------------------------------
     */
   public function commonPush($type=0) {
   		$delay_time = 300; //提前及延迟时间
   		$where = '';
   		if($type == 3) {//安卓
			$where .= ' and (os_type=3 or os_type=0)';
   		}
   		if($type == 1) {//ios
			$where .= ' and (os_type=1 or os_type=0)';
   		}
   		$now_time = time();
   		$start_time = $now_time - $delay_time;
		$end_time = $now_time + $delay_time;
		$where .= " and start_time > $start_time and start_time < $end_time ";
		$data = $this->getPushData($where);
		return $data;
   }

   //获取数据
   public function getPushData($str) {
		$where = " status=2 ";
		$where .= $str;
		$push_mode = M('push_message');
		$data = array();
		if($tmp = $push_mode->where($where)->find()) {
			$data = array(//透传的数组，记得json
				"is_list" => $tmp['type'],
				"user_list" => $tmp['user_ids'],
				"type" => $tmp['msg_type'],
				"title"=>$tmp['title'],
				"content"=>$tmp['content'],
				"id"=>$tmp['goods_id'],
				"url"=>$tmp['msg_link'],
				"msg_link"=>'',
			);
			$upd_data = array(
				'id'=>$tmp['id'],
				'status'=>3,
			);
			$push_mode->save($upd_data);
			unset($tmp);
		}
		return $data;
	}
}
?>