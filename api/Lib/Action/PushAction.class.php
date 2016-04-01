<?php
/**
 * 推送相关类
 * @author feibo
 *
 */
class PushAction extends BaseAction {
	
	/**
	 +----------------------------------------------------------
	 * 添加推送设备
	 +----------------------------------------------------------
	 */
	public function addPushDev() {
		$mod_push_devices = M('push_devices');
		$platform = C('OS_TYPE');
		$os_type = $this->getField('os_type');
		$pushcode = $this->getField('pushcode');
		$device_id = $this->cid;
		$data_now = time();
		if($os_type == 1) {
			$pushcode = ltrim($pushcode,'<');
			$pushcode = rtrim($pushcode,'>');
		}
		$push_code_short = $this->shortUrl($pushcode); //生成短字符
		$info = $mod_push_devices->where("push_code_short='$push_code_short' and os_type=$os_type")->find();

		if(empty($info)){
			//设备不存在
			$data_devices=array(
                        'device_id'=>$device_id,
                        'push_code'=>$pushcode,
                        'os_type'=>$os_type,
                        'create_time'=>$data_now,
                        'push_code_short'=>$push_code_short
                    );
         	$mod_push_devices->add($data_devices);
		} else {
			if($info['device_id']!=$device_id) {
				//设备存在，与当前设备号不一致
				$data_devices=array(
                        'device_id'=>$device_id,
                        'upd_time'=>$data_now
                        );
            	$mod_push_devices->where('id='.$info['id'])->save($data_devices);
			}

		}
		$this->successView();

    }
    
    /**
     +----------------------------------------------------------
     * 微博长链接变成短链接
     +----------------------------------------------------------
     */
    private function shortUrl($long_url) {
        if(empty($long_url)) {
            return '';
        }
        $base32 = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        // 利用md5算法方式生成hash值
        $hex = hash('md5', $long_url);
        $hexLen = strlen($hex);
        $subHexLen = $hexLen / 8;
        $output = array();
        for ($i = 0; $i < $subHexLen; $i ++) {// 将这32位分成四份，每一份8个字符，将其视作16进制串与0x3fffffff(30位1)与操作
            $subHex = substr($hex, $i * 8, 8);
            $idx = 0x3FFFFFFF & (1 * ('0x' . $subHex));
            $out = '';// 这30位分成6段, 每5个一组，算出其整数值，然后映射到我们准备的62个字符
            for ($j = 0; $j < 6; $j ++) {
                $val = 0x0000003D & $idx;
                $out .= $base32[$val];
                $idx = $idx >> 5;
            }
            $output[$i] = $out;
        }
        return $output[0];
    }
}
?>