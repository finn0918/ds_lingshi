<?php
/**
 * 接口公共类
 * @author feibo
 *
 */
class CommonAction extends Action {
	
	/**
	 +----------------------------------------------------------
	 * 获取用户信息
	 +----------------------------------------------------------
	 */
    public function getAuthorInfo($client_id) { 
        $default_author = C('DEFAULT_LENG_AUTHOR');
        $author['uid'] = $default_author['uid'];
        $author['nickname'] = $default_author['nickname'];
        $autho['icon']      = '';

        if ($client_id) {
            $mod = M('client');
            $tmp = $mod->where("user_id=$client_id")->find();
            if ($tmp) {
                $author['uid'] = intval($tmp['user_id']);
                $author['nickname'] = html_entity_decode($tmp['nickname'],ENT_QUOTES);
                $author['icon'] = $tmp['avatar'];
            }
        }

        return $author;
    }
	
    /**
     +----------------------------------------------------------
     * 随机生成的签名字符串
     +----------------------------------------------------------
     */
    public function getRandomString() {
	    $ws_config=C('WS_CONFIG');
	    $length=$ws_config['wskey_length'];
        $chars = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9";
        $chars_array = explode(',', $chars);
        $charsLen = count($chars_array) - 1;
        shuffle($chars_array);
        $output = '';
        for ($i = 0; $i < $length; $i ++) {
            $output .= $chars_array[mt_rand(0, $charsLen)];
        }
        return $output;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取ip
	 +----------------------------------------------------------
	 */
	public function getClientIp() {
	    $ip = $_SERVER['REMOTE_ADDR'];
        return $ip?substr($ip, 0, 15):'';
	}

	/**
	 +----------------------------------------------------------
	 * 防sql注入
	 +----------------------------------------------------------
	 */
    public function sqlCheck($str) {
        return preg_replace('/select|insert|and|or|update|delete|union|into|load_file|outfile/', ' ', $str);
    }
    
    /**
     +----------------------------------------------------------
     * 图片信息输出
     +----------------------------------------------------------
     */
    public function getImgInfo($imgs) {
    	if($imgs) {
    		$rs=array();
    		$imgs=unserialize($imgs);
    		$tmp=0;
	    	foreach ($imgs as $key=>$val) {
				$rs[$tmp]['img_file']=$val['img_file'];
				$rs[$tmp]['img_type']=$val['img_type'];
				$rs[$tmp]['img_o_h']=$val['img_o_h'];
				$rs[$tmp]['img_o_w']=$val['img_o_w'];
				$rs[$tmp]['img_s_h']=$val['img_s_h'];
				$rs[$tmp]['img_s_w']=$val['img_s_w'];
				$tmp++;
	    	}
	    	return $rs;
    	}
		return array();
    }

}
?>