<?php
/**
 * iOS 推送模块
 * @author hhf
 *
 */
class IospushAction extends Action{

	/**
	 +----------------------------------------------------------
	 * iOS 系统实时推送
	 +----------------------------------------------------------
	 */
    public function iosPush() {
        $pushdataAction = A('Pushdata');
        $content = $pushdataAction->commonPush(1);
        if(count($content)) {
            if($content['is_list']){
                $devices = $pushdataAction->getIosUser($content['user_list']); //部分用户
                unset($content['is_list']);
                unset($content['user_list']);
                $this->iospush2app($devices, $content); //推送
            }else{
                $devices = $pushdataAction->getIosUser(); //所有用户
                unset($content['is_list']);
                unset($content['user_list']);
                $this->iospush2app($devices, $content); //全部推送
            }
        } else {
            echo date("Y-m-d H:i:s") ."当前没有推送信息(IOS)\n";
        }
    }

   /**
	 +----------------------------------------------------------
	 * ios推送启动app
	 +----------------------------------------------------------
	 */
	function iospush2app($devices, $content) {
        $deviceToken = '';
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', LIB_PATH . 'Common/ios/snacks-release.pem');//正式环境
        //stream_context_set_option($ctx, 'ssl', 'local_cert', LIB_PATH . 'Common/ios/snacks_dev.pem');//测试环境
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        if($content['type'] == '5'){
            $res_info =  $content['url'];
        }else{
            $res_info =  $content['id'];
        }
        // 消息体
        $body = array(
            'aps' => array(
                'alert' => $content['content'],//推送信息内容
                'sound' => 'default',
                'badge' => 1,
            ),
            'data'=>array(
                'type' => $content['type'],
                'info' => "$res_info",
            )
        );
        /*
        // 以json格式输出
        $payload = json_encode($body);
        */
        $this->fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);//正式环境
        //$this->fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);//测试环境
        foreach($devices as $value) {
            //这个是沙盒测试地址，发布到appstore后记得修改哦
            //$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
            //修改每个设备的红点数量,超过99只显示99
            $body['aps']['badge'] = intval($value['red_num'])>0?(intval($value['red_num'])>99?99:intval($value['red_num'])):1;
            // 以json格式输出
            $payload = json_encode($body);
            if (!$this->fp) {
                echo "链接失败 $err $errstr" . PHP_EOL;
                fclose($this->fp);
                $this->fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);//正式环境
                //$this->fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);//测试环境
            } else {
                echo '成功链接到 APNS' . PHP_EOL;
            }
            $deviceToken = str_replace(' ', '', $value['code']);
            $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
            // 发送到服务器
            $result = fwrite($this->fp, $msg, strlen($msg));
            $time = date("Y-m-d H:i:s",time());
            if (!$result){
                echo '消息推送失败' . PHP_EOL;
                fclose($this->fp);
                $this->fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
                file_put_contents($path,$time.'msg:消息推送失败'."\r\n",FILE_APPEND);
            } else {
                echo $time,'消息推送成功' . PHP_EOL;
                //file_put_contents($path,$time.'msg:消息推送成功'."\r\n",FILE_APPEND);
            }
        }
        // 关闭链接
        fclose($this->fp);
	}
		
	
	/**
	 +----------------------------------------------------------
	 * 测试
	 +----------------------------------------------------------
	 */
	function testIos(){
		$devices = array();
		$devices[] = array('code'=>'f33777057d251a44f495330baf6fe7512053adef8be9e96caf58a2d9b764a610');
		$content = array();
		$content['type'] = 3;
		$content['title'] = "零食小喵";
		$content['content'] = "零食小喵";
        $content['id'] = 2018;
		$content['url'] = "http://www.taobao.com";
		$this->iospush2app($devices,$content);
	}
}
?>