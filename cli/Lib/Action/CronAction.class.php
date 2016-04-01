<?php
/**
 * 推送模块
 * @author feibo
 *
 */
class CronAction extends Action
{
	/**
	 +----------------------------------------------------------
	 * 定时准备推送队列
	 +----------------------------------------------------------
	 */
	public function cachePushMsg() {
		$mod_push_msg = M('push_msg');
		$now = time();
		$redis_cfg=C('REDIS_CFG');
		$redis = new redis();  
		$redis->connect($redis_cfg['host'], $redis_cfg['port']);
		$redis->auth($redis_cfg['auth']);
		
		//抓取待推送列表
		$push_list=$mod_push_msg->where("status=1")->select();
		
		if($push_list) {
			foreach ($push_list as $val) {
				if($val['os_type']==1||$val['os_type']==0) {
					$yx_push_list_key='yx_push_list_ios';
					$msg_arr=$redis->get($yx_push_list_key)?unserialize($redis->get($yx_push_list_key)):array();
					$msg_arr[$val['id']]=$val;
					$redis->set($yx_push_list_key,serialize($msg_arr));
				}
				if($val['os_type']==3||$val['os_type']==0) {
					$yx_push_list_key='yx_push_list_android';
					$msg_arr=$redis->get($yx_push_list_key)?unserialize($redis->get($yx_push_list_key)):array();
					$msg_arr[$val['id']]=$val;
					$redis->set($yx_push_list_key,serialize($msg_arr));
				}
				$this->setPushQueue($val);
				$mod_push_msg->save(array('id'=>$val['id'],'status'=>2));//状态变为已定时
			}
		}	
		
		//设置推送状态，已定时变成已推送
		$push_list_ds=$mod_push_msg->where("status=2")->select();
		if($push_list_ds) {
			foreach ($push_list_ds as $key=>$val) {
				if($val['start_time']<time()) {
					$mod_push_msg->save(array('id'=>$val['id'],'status'=>3));
				}
			}
		}

	}
	
	/**
	 +----------------------------------------------------------
	 * 设置推送队列
	 +----------------------------------------------------------
	 */
    private function setPushQueue($tbl) {
		switch($tbl['os_type']) {
			case 1:
				$this->pushQueue($tbl,1);
				break;
			case 3:
			 	//$this->pushQueue($tbl,3);
				break;
			case 0:
				$this->pushQueue($tbl,1);
				//$this->pushQueue($tbl,3);
				break;
			default:
                throw new Exception('操作系统错误');
		}
    }
    
    /**
     +----------------------------------------------------------
     * 获取推送设备
     +----------------------------------------------------------
     */
    private function pushQueue($msg_tbl,$os_type) {
    	$redis_cfg=C('REDIS_CFG');
		$redis = new redis();  
		$r=$redis->connect($redis_cfg['host'], $redis_cfg['port']);
		$redis->auth($redis_cfg['auth']);
		
		$mod_push_devices=M('push_devices');
		$start_id=0;
		$count=100;
		$redis_queue_key='yx_ios_queue_';
		if($os_type==3) {
			$redis_queue_key='yx_android_queue_';
		} 
		$redis_queue_key.=$msg_tbl['start_time'];
		$push_body['push_type']=1;
    	$push_body['content']=$msg_tbl['content'];
    	
    	while(true) {
    		$where="os_type=$os_type ";
    		if($start_id) {
    			$where.=" AND id<$start_id ";
    		}
    		$devices = $mod_push_devices->field('id,device_id,push_code')->where($where)->order('id DESC')->limit($count)->select();
    		
    		foreach($devices as $device) {
    			$push_body['push_code'] = $device['push_code'];
    			$push_body['device_id'] = $device['device_id'];	
    			$queue_data=serialize($push_body);
    			$redis->LPUSH($redis_queue_key,$queue_data);		
		    }
		   
    		$start_id = $device['id']; 
    		if( count($devices) < $count ) {
    			break;
    		}
    	}	
    } 
   
    /**
     +----------------------------------------------------------
     * 错误日志
     +----------------------------------------------------------
     */
    private function echoErrorMessage(Exception $e)
	{
		$message = date ( "[Y-m-d H:i:s] ", time () ) . $e->getMessage () . "\n";
		$file = C('LOG_PATH') . date ( "Y-m-d" ) . 'error' . '.log';
		@error_log ( $message, 3, $file );
	}		    

}
?>