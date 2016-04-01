<?php
/**
 * 基础类
 * @author feibo
 *
 */

class BaseAction extends Action {

    /**
     +----------------------------------------------------------
     * 成功信息提示
     +----------------------------------------------------------
     */
    public function successView($items=array(),$other=array()) {

        $items = $items ? $items : array();
        $data = $items ? array('count'=>count($items),'items'=>$items) : array();
        $other = $other ? $other : array();
        $data = array_merge($data,$other);
    	$rs = array(
    	     "rs_code"=>$this->ws_code['success'],
    	     "data"=>$data?$data:new stdClass(),
             'rs_msg'=>'success',
    	);
    	die(json_encode($rs));
    }

    /**
     +----------------------------------------------------------
     * 参数字段验证(必须传值)
     +----------------------------------------------------------
     */
	public function getField($key,$msg='',$noempty=false) {

        $key = trim($key);
        if (! isset($_GET[$key])) {
            $msg = $msg ? $msg : 'need '.$key;
            $this->errorView($this->ws_code['parameter_error'], $msg);
        }
        if($noempty&&empty($_GET[$key])) {
            $msg= $msg ? $msg : 'need '.$key;
            $this->errorView($this->ws_code['parameter_error'], $msg);
        }
        return $_GET[$key];
    }
    /**
    +----------------------------------------------------------
     * 参数字段验证(可以不传)
    +----------------------------------------------------------
     */
    public function getFieldDefault($key,$default) {
        $key = trim($key);
        if (!isset($_GET[$key])||(isset($_GET[$key])&&!$_GET[$key])) {
            $_GET[$key] = $default;
        }
        return $_GET[$key];
    }
}
?>
