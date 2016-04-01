<?php

function uimg($img)
{
    if (empty($img))
    {
        return SITE_ROOT . "data/user/avatar.gif";
    }
    return $img;
}
function save_img64($base64_image_content)
{
	$time = time();
	$rand_num = rand(0,1000);
	$time = $time.$rand_num;
	$md5_str = md5($time);
   	if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
  		$type = $result[2];
        if(!is_dir('upload')){
            mkdir('upload',0775);
        }
        if(!is_dir('upload/tmp')){
            mkdir('upload/tmp',0775,true);
        }
  		$new_file = "upload/tmp/{$md5_str}.{$type}";
  		//echo $new_file;exit;
  		if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
    	 return $new_file;
  		}else{
			return ;
  		}
	}
}

function savetaobao($taobao_img){
	$taobao_img = str_replace('https','http',$taobao_img);
	$time = time();
	$rand_num = rand(0,1000);
	$time = $time.$rand_num;
	$md5_str = md5($time);
	$type = substr($taobao_img, strrpos($taobao_img, '.'));
	$new_file = "upload/tmp/{$md5_str}{$type}";
	//var_dump($taobao_img);exit;
	$content = file_get_contents($taobao_img);
	file_put_contents($new_file, $content);
   return $new_file;

}
function img_zip($pth){
	$imagick = new Imagick($pth);//压缩图片
	$size = $imagick->getImagePage ();
	$width = 640;
	if($size['width']>$width){
		$height = intval($width*$size['height']/$size['width']);
		try{
			$imagick->thumbnailImage ($width, $height, true );
		}catch(Exception $e){

		}
	}else{

	}
	$imagick->setImageFormat('JPEG');
	$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
	$a = $imagick->getImageCompressionQuality() * 0.9;
    if($a==0){
        $a = 90;
    }
	$imagick->setImageCompressionQuality($a);
	$imagick->stripImage();
	$imagick->writeImage($pth);
   return $pth;

}
function img_quality_size_zip($pth){
	$imagick = new Imagick($pth);//压缩图片
	$imagick->setImageFormat('JPEG');
	$size = $imagick->getImagePage ();
	$width = 640;
	if($size['width']>$width){
		$height = intval($width*$size['height']/$size['width']);
		$imagick->thumbnailImage ( $width, $height, true );
	}else{

	}
	$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
    $a = $imagick->getImageCompressionQuality() * 0.9;
    if($a==0){
        $a = 90;
    }
	$imagick->setImageCompressionQuality($a);
	$imagick->stripImage();
	$imagick->writeImage($pth);
   	return $pth;

}
function img_quality_zip($pth){
	$imagick = new Imagick($pth);//压缩图片
	$imagick->setImageFormat('JPEG');
	$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
    $a = $imagick->getImageCompressionQuality() * 0.9;
    if($a==0){
        $a = 90;
    }
	$imagick->setImageCompressionQuality($a);
	$imagick->stripImage();
	$imagick->writeImage($pth);
   	return $pth;

}
require_once "admin/Lib/Api/upaiyun/upload_yun.php";
function up_yun($paths){ //上传图片到又拍云
	$time = time();
	$rand_num = rand(0,1000);
	$time = $time.$rand_num;
	$md5_str = md5($time);
	$str3 = substr($md5_str,-3);
	$str2 = substr($md5_str,-2);
	$str1 = substr($md5_str,-1);
	$type = substr($paths, strrpos($paths, '.'));
	$path = '/lingshi/'.$str3.'/'.$str2.'/'.$str1.'/'.$md5_str.$type;
	//$img_src = 'http://img.lingshi.cccwei.com'.$path;
	$img_src = $path;
	$res_array = array();
	$res_array['img_src'] = $img_src;
	$res = up($paths,$path);
	$img_w = $res['x-upyun-width'];
	$img_h = $res['x-upyun-height'];
	$res_array['img_w'] = $img_w;
	$res_array['img_h'] = $img_h;
	$type = substr($type,1);
	$res_array['img_type'] = $type;
	return $res_array;
}

function up_file($paths,$name){//上传文件到又拍云 $paths 本地文件地址
    $time = time();
    $rand_num = rand(0,1000);
    $time = $time.$rand_num;
    $md5_str = md5($time);
    $str3 = substr($md5_str,-3);
    $str2 = substr($md5_str,-2);
    $str1 = substr($md5_str,-1);
    $type = substr($paths, strrpos($paths, '.'));
    $path = '/lingshi/'.$str3.'/'.$str2.'/'.$str1.'/'.$name;
    $filePath = $path;
    $res = up($paths,$path,'file');
    $return['url'] = $filePath;
    $return['status'] = $res;
    return $return;
}
function del_upyun($path){
	$i = strrpos($path,'/ling');
	$path = substr($path,$i);
	//var_dump($path);exit;
	$res = del_yun($path);
	var_dump($res);
}
function articleCateSearch()
{
    $access = M('Access');
    $role_id = $_SESSION['admin_info']['role_id'];
    if ($role_id == 1)
    {
        return FALSE;
    }
    $c_auth = $access->field('cate_id')->where('role_id = ' . $role_id)->select();
    foreach ($c_auth as $c_val)
    {
        if ($c_val['cate_id'] != NULL)
        {
            $cate_check[] = $c_val['cate_id'];
        }
    }
    return $cate_check;

}
//创建目录
function mk_dirs() {
	$Y = date('Y', time());
	$m = date('m', time());
	if(!file_exists("upload/$Y")) {
		mkdir("upload/$Y", 0755);
	}

	if(!file_exists("upload/$Y/$m")) {
		mkdir("upload/$Y/$m",0755);
	}
	return "upload/$Y/$m/";
}
 function _upload()
{
	import("ORG.Net.UploadFile");
	$upload = new UploadFile();
	//设置上传文件大小
	$upload->maxSize = 3292200;
	$upload->allowExts = explode(',', 'jpg,gif,png,jpeg');
	$date = date('Y-m-d',time());
	//$upload->savePath = './upload/'.$date.'/';
	$upload->savePath =mk_dirs();
	$upload->thumb = true;
	$upload->thumbPrefix = '';
	//$upload->thumbMaxWidth = '150';
	//$upload->thumbMaxHeight = '150';

	$upload->saveRule = uniqid;

	if (!$upload->upload())
	{
		//捕获上传异常
		// $upload->getErrorMsg();
	}
	else
	{
		//取得成功上传的文件信息
		$uploadList = $upload->getUploadFileInfo();
	}
	return $uploadList;
}
function check_img_up($mod,$id){
	$game_mod = M('game');
	$way_mod = M('way');
	$upload_act = A('upload');
	$simg_size = C('SIMG_SIZE');
	if($mod == 'game'){
		$game = $game_mod->where("id =".$id)->field('icon,content')->find();
		$old_icon = $game['icon'];
		$old_content = $game['content'];
		$pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
		preg_match_all($pattern,$old_content,$match_content);
		$new_content = $match_content[1];
		$new_content_html = $match_content[0];
		$save_dir = '/'.mk_dirs();
		//icon
		if($old_icon){
			if(strpos($old_icon,'http://img.xd.feibo.com') === false){
				$local_icon = get_file($old_icon,$save_dir);
				$local_icon = $local_icon['save_path'];//本地icon图片地址
				$pic_url = preg_replace('/(.*?)\/u/i', './u',$local_icon);
				$new_icon = $upload_act->img_upyun($pic_url);//又拍云icon图片地址
				$data['icon'] = $new_icon;
				file_put_contents('fb.log','icon本地地址：'.$local_icon."\r\n",FILE_APPEND);
				file_put_contents('fb.log','icon云地址：'.$new_icon."\r\n",FILE_APPEND);
			}
		}
		//content图片
		if($new_content){
			foreach($new_content as $key=>$val){
				if(strpos($val,'http://img.xd.feibo.com') === false){
					if(substr_count($val,'http://i')){
						file_put_contents('fb.log','下载图片'."\r\n",FILE_APPEND);
						$local_pic = get_file($val,$save_dir);
						$local_content[$key] = $local_pic['save_path'];//本地content图片地址
					}else{
						file_put_contents('fb.log','未下载图片'."\r\n",FILE_APPEND);
						$local_content[$key] = $val;
					}
				}else{
					$local_content[$key] = $val;
				}
			}
			$local_content_log = implode("\r\n", $local_content);
			file_put_contents('fb.log','content图片本地地址：'.$local_content_log."\r\n",FILE_APPEND);
			if(!empty($local_content)){
				foreach($local_content as $key=>$val){
					if(strpos($val, 'http://img.xd.feibo.com') === false){
						$content_img = preg_replace('/(.*?)\/u/i', './u',$val);
						$new_content_img[$key] = $upload_act->img_upyun($content_img);//又拍云content图片地址
					}else{
						$new_content_img[$key] = $val;
					}
				}
				$new_content_img_log = implode("\r\n", $new_content_img);
				file_put_contents('fb.log','content图片云地址：'.$new_content_img_log."\r\n",FILE_APPEND);
				if(count($local_content)) {
					for($i=0;$i<count($local_content);$i++){
						$old_content = str_replace($new_content[$i], $new_content_img[$i], $old_content);
					}
				}
				$old_content = str_replace('&nbsp;', '',$old_content);
				$old_content = preg_replace('/<p>(\s+)<br(\s+)\/>(\s+)<\/p>/', '', $old_content);
				file_put_contents('fb.log','修改完的content：'.$old_content."\r\n",FILE_APPEND);
				$data['content'] = $old_content;
			}
		}else{
			$old_content = str_replace('&nbsp;', '',$old_content);
			$old_content = preg_replace('/<p>(\s+)<br(\s+)\/>(\s+)<\/p>/', '', $old_content);
			file_put_contents('fb.log','修改完的content：'.$old_content."\r\n",FILE_APPEND);
			$data['content'] = $old_content;
		}
		$p="/<img src=\"([^<]*?)\"/is";
		preg_match_all($p,$data['content'],$dimgs);
		if(!empty($dimgs)) {
    	$sort = array_keys($dimgs[1]);
		foreach($sort as $v){
			$key = $v+1;
			$sort[$key] = getimgmsg($dimgs[1][$v],$simg_size['detail']);
			}
		$data['dimgs'] = serialize($sort);
		}
		$flag = $game_mod->where("id =".$id)->save($data);
	}
	if($mod == 'way'){
		$way = $way_mod->where("id =".$id)->field('content')->find();
		$old_content = $way['content'];
		$pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
		preg_match_all($pattern,$old_content,$match_content);
		$new_content = $match_content[1];
		$new_content_html = $match_content[0];
		$save_dir = '/'.mk_dirs();
		//content图片
		if($new_content){
			foreach($new_content as $key=>$val){
				if(strpos($val,'http://img.xd.feibo.com') === false){
					if(substr_count($val,'http://i')){
						file_put_contents('fb.log','下载图片'."\r\n",FILE_APPEND);
						$local_pic = get_file($val,$save_dir);
						$local_content[$key] = $local_pic['save_path'];//本地content图片地址
					}else{
						file_put_contents('fb.log','未下载图片'."\r\n",FILE_APPEND);
						$local_content[$key] = $val;
					}
				}else{
					$local_content[$key] = $val;
				}
			}
			$local_content_log = implode("\r\n", $local_content);
			file_put_contents('fb.log','content图片本地地址：'.$local_content_log."\r\n",FILE_APPEND);
			if(!empty($local_content)){
				foreach($local_content as $key=>$val){
					if(strpos($val, 'http://img.xd.feibo.com') === false){
						$content_img = preg_replace('/(.*?)\/u/i', './u',$val);
						$new_content_img[$key] = $upload_act->img_upyun($content_img);//又拍云content图片地址
					}else{
						$new_content_img[$key] = $val;
					}
				}
				$new_content_img_log = implode("\r\n", $new_content_img);
				file_put_contents('fb.log','content图片云地址：'.$new_content_img_log."\r\n",FILE_APPEND);
				if(count($local_content)) {
					for($i=0;$i<count($local_content);$i++){
						$old_content = str_replace($new_content[$i], $new_content_img[$i], $old_content);
					}
				}
				$old_content = str_replace('&nbsp;', '',$old_content);
				$old_content = preg_replace('/<p>(\s+)<br(\s+)\/>(\s+)<\/p>/', '', $old_content);
				file_put_contents('fb.log','修改完的content：'.$old_content."\r\n",FILE_APPEND);
				$data['content'] = $old_content;
			}
		}else{
			$old_content = str_replace('&nbsp;', '',$old_content);
			$old_content = preg_replace('/<p>(\s+)<br(\s+)\/>(\s+)<\/p>/', '', $old_content);
			file_put_contents('fb.log','修改完的content：'.$old_content."\r\n",FILE_APPEND);
			$data['content'] = $old_content;
		}
		$flag = $way_mod->where("id =".$id)->save($data);
	}
}
/**
* 将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
* @access public
* @param string $str 待转换字串
* @return string $str 处理后字串
*/
function make_semiangle($str)
{
	 $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                 '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
                 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
                 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
                 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
                 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
                 'ｙ' => 'y', 'ｚ' => 'z',
                 '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
                 '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
                 '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
                 '》' => '>',
                 '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
                 '：' => ':', '。' => '.', '、' => ',', '，' => ',', '、' => '.',
                 '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
                 '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
                 '　' => ' ');

    return strtr($str, $arr);
}
/*
*功能：php完美实现下载远程图片保存到本地
*参数：文件url,保存文件目录,保存文件名称，使用的下载方式
*当保存文件名称为空时则使用远程文件原来的名称
*/
function get_file($url,$save_dir='',$filename='',$type=0){
	$save_dir = ROOT_PATH .$save_dir;
    if(trim($url)==''){
		return array('file_name'=>'','save_path'=>'','error'=>1);
	}
	if(trim($save_dir)==''){
		$save_dir='./';
	}
    if(trim($filename)==''){//保存文件名
        $ext=strrchr($url,'.');
        //if($ext!='.gif'&&$ext!='.jpg'){
			//return array('file_name'=>'','save_path'=>'','error'=>3);
		//}
        $filename=time().md5($url).$ext;
    }
/*	if(0!==strrpos($save_dir,'/')){
		$save_dir.='/';
	}*/
	//创建保存目录
	if(!file_exists($save_dir)&&!mk_dirs($save_dir,0777,true)){
		return array('file_name'=>'','save_path'=>'','error'=>5);
	}
    //获取远程文件所采用的方法
    if($type){
		$ch=curl_init();
		$timeout=5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$img=curl_exec($ch);
		curl_close($ch);
    }else{
	    ob_start();
	    readfile($url);
	    $img=ob_get_contents();
	    ob_end_clean();
    }
    //$size=strlen($img);
    //文件大小
    $fp2=@fopen($save_dir.$filename,'a');
    fwrite($fp2,$img);
    fclose($fp2);
	unset($img,$url);
    return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
}
//获取文件后缀
function fileext($file_name)
{
	$retval="";
	$pt=strrpos($file_name, ".");
	if ($pt) $retval=substr($file_name, $pt+1, strlen($file_name) - $pt);
	return ($retval);
}
//获取图片详细信息
function getimgmsg($imgs,$size)
{
    $img_url = C("IMG_URL");
    if (is_array($imgs)) {
        foreach ($imgs as $k => $v) {
            if (!is_array($v)) {
                $info = getimagesize($v);
                $name = substr($v, 0, strrpos($v, '.'));
                $mine_arr = explode('/', $info['mime']);
                $img[$k]['img_url'] = $v;
                $img[$k]['img_file'] = str_replace($img_url, '', $name);
                $img[$k]['img_type'] = fileext($v);
                if ($info[0]) {
                    $img[$k]['img_o_h'] = $info[1];
                } else {
                    $img[$k]['img_o_h'] = '0';
                }
                if ($info[1]) {
                    $img[$k]['img_o_w'] = $info[0];
                } else {
                    $img[$k]['img_o_w'] = '0';
                }
                $img[$k]['img_s_h'] = $size['h'];
                $img[$k]['img_s_w'] = $size['w'];
            }
        }
        return $img;
    } else {
        $info = getimagesize($imgs);
        $name = substr($imgs, 0, strrpos($imgs, '.'));
        $mine_arr = explode('/', $info['mime']);
        $img['img_url'] = $imgs;
        $img['img_file'] = str_replace($img_url, '', $name);
        $img['img_type'] = fileext($imgs);
        if ($info[0]) {
            $img['img_o_h'] = $info[1];
        } else {
            $img['img_o_h'] = '0';
        }
        if ($info[1]) {
            $img['img_o_w'] = $info[0];
        } else {
            $img['img_o_w'] = '0';
        }
        $img['img_s_h'] = $size['h'];
        $img['img_s_w'] = $size['w'];
        return $img;
    }
}
    /**
    +----------------------------------------------------------
     * 根据键名更新缓存
     * @author：hhf
    +----------------------------------------------------------
     */
    function updateCache($keyName){
        $options = C('REDIS_CONF');
        $options['rw_separate'] = true; //写分离
        $redis = Cache::getInstance('Redis',$options);
        switch($keyName){
            case "ls_brand_spu_list_" :
                $redis->rm($keyName."*");
                break;
            default:
                $redis->rm($keyName);
                break;
        }
        $redis->close();
    }
    /**
    +----------------------------------------------------------
     * 生成优惠券唯一代码
    +----------------------------------------------------------
     */
    function createCouponNum(){
        return date('md'). substr(time(), -7) . substr(microtime(), 2, 5) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
    }

















