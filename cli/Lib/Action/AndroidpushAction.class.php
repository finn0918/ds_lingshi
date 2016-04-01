<?php
/**
 * Android 推送模块
 * @author hhf
 *
 */
class AndroidpushAction extends Action{

	/**
	 +----------------------------------------------------------
	 * 构造函数
	 +----------------------------------------------------------
	 */
    function _initialize() {
    	import("IGT");//导入个推库文件
    }

    /**
	 +----------------------------------------------------------
	 * 实时推送
	 +----------------------------------------------------------
	 */
    function androidPush() {
        $pushdataAction = A('Pushdata');
        $content = $pushdataAction->commonPush(3);
        if(count($content)) {
            if($content['is_list']){
                var_dump($content['user_list']);
                $android_users = $pushdataAction->getAndroidUser($content['user_list']);
                unset($content['is_list']);
                unset($content['user_list']);
                echo "推送内容";var_dump($content);
                $this->pushMessageToList(json_encode($content),$android_users);
            }else{
                unset($content['is_list']);
                unset($content['user_list']);
                echo "推送内容";var_dump($content);
                $this->pushMessageToApp(json_encode($content)); //全部推送
            }
        } else {
            echo date("Y-m-d H:i:s") ."no push message!当前没有推送信息\n";
        }
    }

    /**
     +----------------------------------------------------------
     * 推送到App
     +----------------------------------------------------------
     */
    function pushMessageToApp($content){
		$igt = new IGeTui(C('HOST'),C('APPKEY'),C('MASTERSECRET'));
		//透传模版
		$template =  new IGtTransmissionTemplate();
		$template->set_appId(C('APPID')); //应用appid
		$template->set_appkey(C('APPKEY'));//应用appkey
		$template->set_transmissionType(2);//透传消息类型
		$template->set_transmissionContent($content);//透传内容
		//基于应用消息体
		$message = new IGtAppMessage();
		$message->set_isOffline(true);
		$message->set_offlineExpireTime(3600*6*1000);
		$message->set_data($template);
		$message->set_appIdList(array(C('APPID')));
		$message->set_phoneTypeList(array('ANDROID'));
		$rep = $igt->pushMessageToApp($message);
		//var_dump($rep);
	    //echo ("<br><br>");
	    if($rep['result']=='ok') {
			echo date("Y-m-d H:i:s") ."--推送成功！\n";
		} else {
			echo date("Y-m-d H:i:s") ."--推送失败！\n";
		}

	}

	 /**
     +----------------------------------------------------------
     * 推送到指定用户列表
     +----------------------------------------------------------
     */
	function pushMessageToList($content,$data){
		$igt = new IGeTui(C('HOST'),C('APPKEY'),C('MASTERSECRET'));
		//透传模版
		$template =  new IGtTransmissionTemplate();
		$template->set_appId(C('APPID')); //应用appid
		$template->set_appkey(C('APPKEY'));//应用appkey
		$template->set_transmissionType(2);//透传消息类型
		$template->set_transmissionContent($content);//透传内容
		//基于应用消息体
		$message = new IGtAppMessage();
		$message->set_isOffline(true);
		$message->set_offlineExpireTime(3600*6*1000);
		$message->set_data($template);
		$contentId = $igt->getContentId($message);
		$ids = '';
		foreach($data as $value){
			$target = new IGtTarget();
			$target->set_appId(C('APPID'));
			$target->set_clientId($value['code']);
			$targetList[] = $target;
			unset($target);
		}
		$rep = $igt->pushMessageToList($contentId, $targetList);
		//var_dump($rep);
	    //echo ("<br><br>");
		if($rep['result']=='ok') {
			echo date("Y-m-d H:i:s") ."--推送成功！\n";
		} else {
			echo date("Y-m-d H:i:s") ."--推送失败！\n";
		}
	}
	
	/**
     +----------------------------------------------------------
     * 测试推送到个人
     +----------------------------------------------------------
     */
	function pushMessageToSingle(){
		$igt = new IGeTui(C('HOST'),C('APPKEY'),C('MASTERSECRET'));
		$data=array(//透传的数组，记得json
			"type" => '4',//打开应用
			"title"=>'测试推送，喵',
			"content"=>'推送收到了吗',
            "id"=> 14,
            "url"=>"http://www.taobao.com",
			"msg_link"=>'',
		);
		$content = json_encode($data);
		$template =  new IGtTransmissionTemplate();
		$template->set_appId(C('APPID')); //应用appid
		$template->set_appkey(C('APPKEY'));//应用appkey
		$template->set_transmissionType(2);//透传消息类型
		$template->set_transmissionContent($content);//透传内容
		//个推信息体
		$message = new IGtSingleMessage();
		$message->set_isOffline(true);//是否离线
		$message->set_offlineExpireTime(1000*60);//离线时间
		$message->set_data($template);//设置推送消息类型
		//接收方
		$target = new IGtTarget();
		$target->set_appId(C('APPID'));
		$target->set_clientId('0b4473083224fe1a25464f195da326c4');
		$rep = $igt->pushMessageToSingle($message,$target);
		//var_dump($rep);
	    //echo ("<br><br>");
	    if($rep['result']=='ok') {
			echo date("Y-m-d H:i:s") ."--success推送成功！\n";
		} else {
			echo date("Y-m-d H:i:s") ."--error推送失败！\n";
		}
	}
}
?>