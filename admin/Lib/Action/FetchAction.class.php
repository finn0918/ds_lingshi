<?php
/**
* 文件:fetchAction.class.php
* ---------------------
* 商品采集类
* ---------------------
* 日期: 2015-1-5 下午4:36:28
* @author: hch
*/
class FetchAction extends BaseAction {
    function _initialize() {
    	import("JSON");
 		import("phpQuery");
    }
    // 代理IP列表
    private $_proxylist = array();

    // 记录失败代理IP
    private $_failure_proxy = array();

    // debug
    public $debug = false;

    public function __construct()
    {
    	// 获取代理IP
    	$this->_proxylist = $this->proxyList();
    }

    /**
     * 获取远程数据
     */
    public function get($url)
    {
    	return $this->disguise_curl($url);
    }
    /**
     * 通过代理方式获取页面内容
     *
     * @param string $url 链接地址
     * @return string $html 远程数据内容
     */
    public function proxyGet($url)
    {
    	// 尝试多次使用代理获取数据
    	for ($i = 0; $i < 3; $i++) {

    		// 获取随机代理IP
    		$proxy_ip = $this->_getRandProxy();

    		if ($this->debug) {
    			$start = microtime(true);
    		}

    		// 获取远程内容
    		$html = $this->disguise_curl($url, $proxy_ip);

    		if ($this->debug) {
    			$len = strlen($html);
    			$used = microtime(true) - $start;
    			echo "proxy_ip:{$proxy_ip} ... used:{$used} ... len:{$len}\n";
    		}

    		if (!empty($html)) {
    		break;
    		}

    		// 记录失败IP信息
    		if (!isset($this->_failure_proxy[$proxy_ip])) {
    		$this->_failure_proxy[$proxy_ip] = 0;
    		}
    		$this->_failure_proxy[$proxy_ip] += 1;
    	}

    		// 代理获取失败，尝试本机获取
    		if (empty($html)) {
    		$html = $this->disguise_curl($url);
    		}

    		// 重置资源
    		//$this->_failure_proxy = array();

    		return $html;
    }
    /**
    * 获取一个随机代理IP
    */
    private function _getRandProxy()
    {
    	$proxy_count = count($this->_proxylist);
    // 尝试多次获取随机代理IP，如果ip有失败记录，换其他ip
    for (;;) {
    // 获取随机代理IP
   		 $rand_key = mt_rand(0, $proxy_count - 1);
   	 	$proxy_ip = $this->_proxylist[$rand_key];
    if (!isset($this->_failure_proxy[$proxy_ip])) {
  		  break;
   		 }
    }

   		 return $proxy_ip;
    }

    /**
    * 代理IP列表
    */
    public function proxyList()
    {
    $proxy_arr = array(
    		'58.20.127.100:3128', // 湖南省长沙市 联通
   			'218.108.168.69:82', // 浙江省杭州市
    		'218.108.170.166:80', // 浙江省杭州市
    		'139.210.98.86:8080', // 吉林省长春市联通
    		'218.108.168.68:82', // 浙江省杭州市
     		'111.161.126.84:80', // 天津市联通
    	);

     	return $proxy_arr;
    }
    	/**
    	* 模拟google抓取内容
    			*
    			* @link http://cn2.php.net/manual/en/function.curl-setopt.php#78046
      * @param string $url 链接地址
      * @param string $proxy 代理Ip信息，如：127.0.0.1:81
          * @return string $html 页面内容
    	*/
    	public function disguise_curl($url, $proxy=null)
    	{
    	$curl = curl_init();

    	// Setup headers - I used the same headers from Firefox version 2.0.0.6
    	// below was split up because php.net said the line was too long. :/
    	$header = array();
    	$header[0]  = "Accept: text/xml,application/xml,application/xhtml+xml,";
    	$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    	$header[] = "Cache-Control: max-age=0";
    	$header[] = "Connection: keep-alive";
    	$header[] = "Keep-Alive: 300";
    	$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    	$header[] = "Accept-Language: en-us,en;q=0.5";
     	$header[] = "Cookie: cna=l+pgC/cDdWcCAWVF+/5q+UVs; miid=3317872020099775032; __utma=6906807.434603219.1390489590.1390489590.1390489590.1; __utmz=6906807.1390489590.1.1.utmcsr=fuwu.taobao.com|utmccn=(referral)|utmcmd=referral|utmcct=/ser/detail.htm; x=e%3D1%26p%3D*%26s%3D0%26c%3D0%26f%3D0%26g%3D0%26t%3D0%26__ll%3D-1%26_ato%3D0; lzstat_uv=24182050841715729711|2581762@3201199@2945730@2948565@2798379@2043323@3045821@2805963@2738597@878758@2208862@3027305@3284827@2581759@2581747@2938535@2938538@2879138@3010391; ali_ab=60.186.203.48.1390489531936.4; l=asd890241::1397182032328::11; v=0; uc3=nk2=AmJam4TqFyWJ&id2=W8ncTY3m5dw%3D&vt3=F8dATHtpuP76kDFmmBg%3D&lg2=V32FPkk%2Fw0dUvg%3D%3D; existShop=MTM5NzYxMTM1NA%3D%3D; lgc=asd890241; tracknick=asd890241; sg=10d; cookie2=6b8940c7741e940084cc149db11f8b7c; mt=cp=0&np=&ci=1_1&cyk=0_0; cookie1=B0EwsR%2FpnjjSjqpUTsxO8woKrOXzJMXtv9vP4SUJA5I%3D; unb=80893640; t=43525df761cffb84c1297592f666dd75; publishItemObj=Ng%3D%3D; _cc_=UIHiLt3xSw%3D%3D; tg=0; _l_g_=Ug%3D%3D; _nk_=asd890241; cookie17=W8ncTY3m5dw%3D; pnm_cku822=117fCJmZk4PGRVHHxtNZngkZ3k%2BaC52PmgTKQ%3D%3D%7CfyJ6Zyd9OGcmY3YkZHYibx4%3D%7CfiB4D15%2BZH9geTp%2FJyN8PDJtLBMbCF4lHw%3D%3D%7CeSRiYjNhIHA3dWI0c2A4eGwmfz16PnhrNHJlMH1kJnc8bS1hfzoT%7CeCVoaEATTRBWFx1IEBRReHZYZg%3D%3D%7CeyR8C0obRRhYABdDABNTFAFGEU8XUxMFTgMVSREMSxxeG1MWCTMa%7CeiJmeiV2KHMvangudmM6eXk%2BAA%3D%3D; _tb_token_=3ee5e3b8e7690; uc1=lltime=1397569800&cookie14=UoLVYuvN1ebuMw%3D%3D&existShop=true&cookie16=Vq8l%2BKCLySLZMFWHxqs8fwqnEw%3D%3D&cym=1&cookie21=URm48syIZJTgtchfymSXVA%3D%3D&tag=3&cookie15=VFC%2FuZ9ayeYq2g%3D%3D";
     	$header[] = "Pragma: "; // browsers keep this blank.

     	if (!is_null($proxy)) {
     		curl_setopt ($curl, CURLOPT_PROXY, $proxy);
     	}

	     	curl_setopt($curl, CURLOPT_URL, $url);
	     	curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
         	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	    	curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
	    	curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
	    	curl_setopt($curl, CURLOPT_AUTOREFERER, true);
	    	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	     	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
         	curl_setopt($curl, CURLOPT_TIMEOUT, 10);

         	$html = curl_exec($curl); // execute the curl command
         	curl_close($curl); // close the connection

         	return $html; // and finally, return $html
    	}

	//添加数据
	function add_goods_data($data) {
		if(is_array($data)) {
			if(empty($data['details'])) {
				$data['details'] = '';
			}
			$goods_mod = M('Spu');
			$filter_goods_mod = M('filter_goods');
			$data['status'] = 5;
			$item_id = $data['id'];
			unset($data['id']);
			$gid = $goods_mod->add($data);
			$url_key = md5($data['taobao_url']);
			$id_key = md5($item_id);
			$filter_goods_data = array(
				'gid' => $gid,
				'url_key' => $url_key,
				'id_key' => $id_key,
				'item_id' =>$item_id,
				'type' =>'1'
			);
			$filter_goods_mod->add($filter_goods_data);

			//添加过滤字段
	}
	return $gid;
	}
	//添加折扣数据
	function add_discount_goods_data($data) {
		if(is_array($data)) {
			if(empty($data['details'])) {
				$data['details'] = '';
			}
			$goods_mod = M('Spu');
			$filter_goods_mod = M('filter_goods');
			$data['status'] = 4;
			$item_id = $data['id'];
			unset($data['id']);
			$gid = $goods_mod->add($data);
			$url_key = md5($data['taobao_url']);
			$id_key = md5($item_id);
			$filter_goods_data = array(
				'gid' => $gid,
				'url_key' => $url_key,
				'id_key' => $id_key,
				'item_id' =>$item_id,
				'type'=>'2'
			);
			$filter_goods_mod->add($filter_goods_data);

			//添加过滤字段
	}
	return $gid;
	}
	//添加普通评论
	public function add_comment($comment_data,$gid){
		$comment_mod = M('comment');
		$comment = array_values($comment_data);
		foreach($comment as $v){
			$cid = $comment_mod->add($v);
		}
	}
	//敏感词过滤
	public function filter_word($comment) {
		//1:禁止  2：替换 3：审核

		//$cache = Cache::getInstance('Memcache',array('host'=>'127.0.0.1', 'port'=>11211, 'expire'=>'36000000'));
		$original_comment = $comment;
		//评论过滤特殊字符及空格后判断
		$strmap = array(
				'/à|á|å|â|ä/' => 'a',
				'/è|é|ê|ẽ|ë/' => 'e',
				'/ì|í|î/' => 'i',
				'/ò|ó|ô|ø/' => 'o',
				'/ù|ú|ů|û/' => 'u',
				'/ç|č/' => 'c',
				'/ñ|ň/' => 'n',
				'/ľ/' => 'l',
				'/ý/' => 'y',
				'/ť/' => 't',
				'/ž/' => 'z',
				'/š/' => 's',
				'/æ/' => 'ae',
				'/ö/' => 'oe',
				'/ü/' => 'ue',
				'/Ä/' => 'Ae',
				'/Ü/' => 'Ue',
				'/Ö/' => 'Oe',
				'/ß/' => 'ss',
				'/&nbsp;/'=>' ',
				'/　/'=>'',
				'/～|·|！|@|#|￥|%|…|&|×|（|）|-|\+|=|『|【|』|】|、|:|；|“|”|\'|《|，|》|。|？|\/|—|_|：|√|＜|°|丶|＞|－|★|｜|│|‖|ˇ/'=>' ',
				'/[^\w\s\x80-\xff]/' => ' ',
				'/\\s+/' => $replacement
		);
		$comment = trim($comment);
		$comment = preg_replace(array_keys($strmap), array_values($strmap), $comment);
		$comment = preg_replace('/\\s+/',$replacement, strtolower($comment));
		$comment = trim($comment,$replacement);

		$memcache_obj = new Memcache;
		$memcache_obj->connect('127.0.0.1', 11211);
		$sensitive_word_data = $memcache_obj->get('sensitive_word');
		if(empty($sensitive_word_data)) {
			$data  = M('sensitive_word')->select();
			$sensitive_word_data = serialize($data);
			S('sensitive_word',$sensitive_word_data);

		}

		$tmp_data =  unserialize($sensitive_word_data);
		foreach($tmp_data as $value) {
			if(strpos($comment, $value['word'])!==false) {
				if($value['type'] == 2) {// 替换
					$comment = str_replace($value['word'], $value['replace_word'], $comment);
					return $comment;
				} else {
					return false;
				}
			}
		}
		return $original_comment; //返回原始字符串
	}
	//广告过滤
	public function filter_ad($comment){
		$comment=trim($comment);
		$line_base=13;//号码的界限(广告号码)
		$line_url=28;//url的界限(广告链接)
		$comm_len=strlen($comment);
		if($comm_len>=$line_base)
		{
			$is_ad=false;
			if($comm_len>=$line_url)
			{
				$quanjiao = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4','５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9', 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E','Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O','Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T','Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y','Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd','ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i','ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n','ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z','（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[','】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']','‘' => '[', '\'' => ']', '｛' => '{', '｝' => '}', '《' => '<','》' => '>','％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-','：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.', '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|', '”' => '"', '\'' => '`', '‘' => '`', '｜' => '|', '〃' => '"','　' => ' ');
				$comment=strtr($comment, $quanjiao);
			}
			//特殊数字符号转换
			$special_num_char=array('①'=>'1','②'=>'2','③'=>'3','④'=>'4','⑤'=>'5','⑥'=>'6','⑦'=>'7','⑧'=>'8','⑨'=>'9','⑩'=>'10','⑴'=>'1','⑵'=>'2','⑶'=>'3','⑷'=>'4','⑸'=>'5','⑹'=>'6','⑺'=>'7','⑻'=>'8','⑼'=>'9','⑽'=>'10');
			$comment=strtr($comment, $special_num_char);

			$flag_arr=array('？','！','￥','（','）','：','‘','’','“','”','《','》','，','…','。','、','nbsp','】','【','～');
			$comm_str=preg_replace('/\s/','',preg_replace("/[[:punct:]]/",'',html_entity_decode(str_replace($flag_arr,'',$comment))));
			preg_match_all('/\d+/',$comm_str,$match);
			//qq号和各种号码的过滤
			if(!empty($match[0]))
			{
				foreach($match[0] as $val)//过滤存在数字的qq号和微信号
				{
					if(strlen($val)>=6)
					{
						$is_ad=true;
						break;
					}
				}
				if(count($match[0])>=10)
				{
					$is_ad=true;
				}
			}

			if(!$is_ad&&$comm_len>=$line_url)
			{
				preg_match_all('/[A-Za-z]+/',$comm_str,$match_url);
				if(!empty($match_url[0]))//广告链接的过滤
				{
					$tmp='';
					$domain=array('www','http','https','com','cn','net','org','info');
					foreach ($match_url[0] as $val)
					{
						$tmp.=$val;
					}
					$tmp=strtolower($tmp);
					if(strlen($tmp)>=10)
					{
						foreach ($domain as $val)
						{
							if(strpos($tmp, $val))
							{
								$is_ad=true;
								break;
							}
						}
					}
				}
			}
			if(!$is_ad&&$comm_len>=23)//qq号和微信等号码是中文和数字的混合
			{
				$num_all_regx=array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖','拾','一','二','三','四','五','六','七','八','九','十','1','2','3','4','5','6','7','8','9','0');
				$nun_upper_regx="/一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾/";
				$nun_regx="/1|2|3|4|5|6|7|8|9|0/";
				$regx1=preg_match($nun_upper_regx,$comm_str);
				$regx2=preg_match($nun_regx,$comm_str);
				if($regx1||($regx1&&$regx2))
				{
					$num_upper_exist=1;
					foreach ($num_all_regx as $val)
					{
						preg_match_all("/$val/",$comm_str,$pg);
						$match_count=count($pg[0]);
						$num_upper_exist+=$match_count;
					}
					if($num_upper_exist>=10)
					{
						$is_ad=true;
					}
				}
			}
			if($is_ad)
			{
				return false;
			}else{
				return $comment;
			}
		}
	}
	//对象转数组
	/*
	public function objectToArray($d)  {
		if (is_object($d)) {
			$d = get_object_vars($d);
		}
		if (is_array($d)) {
			return array_map(__FUNCTION__, $d);
		}else {
			return $d;
		}
	}
	*/
	 function objectToArray($obj){
  		$_arr = is_object($obj) ? get_object_vars($obj) :$obj;
  		foreach ($_arr as $key=>$val){
   			$val = (is_array($val) || is_object($val)) ? $this->objectToArray($val):$val;
   			$arr[$key] = $val;
  		}
  		return $arr;
	 }
	//是否相似评论 相似度80%为true
	public function match_comment($comment,$filter_comment){
		$xunsearch_act = A('Xunsearch');
		if($filter_comment == '') return false;
		$cut_comment = $xunsearch_act->cut_word($comment);
		$cut_comment_count = count($cut_comment);
		foreach($filter_comment as $key=>$val){
			$cut_word = $xunsearch_act->cut_word($val);
			$res = array_intersect($cut_comment,$cut_word);
			$res_count = count($res);
			$result = $res_count/$cut_comment_count;
			unset($cut_word);
			unset($res_count);
			if($result > 0.8) return true;
		}
		return false;
	}
	//通过手机接口获得数据
	public function get_mobile ($id) {
		$url = 'http://hws.m.taobao.com/cache/wdetail/5.0/?id='.$id."&ttid=2014_uxiaomi_23183641%2540baichuan_android_1.5.1";
		$html = file_get_contents($url);
		$a_html = json_decode($html,true);
		$a_stack = json_decode($a_html['data']['apiStack'][0]['value'], true);
		$descInfo = $a_html['data']['descInfo']['briefDescUrl'];
		$goods_imgs = $a_html['data']['itemInfoModel']['picsPath'];
		$title = $a_html['data']['itemInfoModel']['title'];
		$userId = $a_html['data']['seller']['userNumId'];
		$details = file_get_contents($descInfo);
		$details_html = json_decode($details,true);
		$b_stack = $details_html['data']['images'];
		$details = '';
		if($b_stack){
			foreach($b_stack as $k=>$v){
				$details .= '<p><img src="'.$v.'" /></p>';
			}
		}else{
			if(!empty($details_html['data']['pages'])){
				foreach($details_html['data']['pages'] as $v){
					$details .= $v;
				}
			}
		}
		$old = $a_stack['data']['itemInfoModel']['priceUnits'][1]['price'];
		$now = $a_stack['data']['itemInfoModel']['priceUnits'][0]['price'];
		$delivery = $a_stack['data']['delivery']['deliveryFees'];
		if(strpos($delivery[0],'包邮')!==FALSE){
			$delivery = 1;
		}else{
			$delivery = 0;
		}
		$totalSoldQuantity = $a_stack['data']['itemInfoModel']['totalSoldQuantity'];
		$quantity = $a_stack['data']['itemInfoModel']['quantity'];
		$res = array();
		$res['old'] = $old;
		$res['now'] = $now;
		$res['totalSoldQuantity'] = $totalSoldQuantity;
		$res['quantity'] = $quantity;
		$res['delivery'] = $delivery;
		$res['descInfo'] = $details;
		$res['goods_imgs'] = $goods_imgs;
		$res['title'] = $title;
		$res['userid'] = $userId;
		return $res;
	}
}
?>