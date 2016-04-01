<?php
/**
 * APP 单一入口
 * @author feibo
 *
 */

class IndexAction extends BaseAction {
	
	/**
	 +----------------------------------------------------------
	 * 构造函数
	 +----------------------------------------------------------
	 */
    public function _initialize() {
        parent::_initialize();
        $this->checkSig();
    }
    
    /**
     +----------------------------------------------------------
     * 获取接口号后跳转
     +----------------------------------------------------------
     */
    public function index() {
        // 记录访问信息
        $now_time = time();
        $end_extend = date("Y_m",$now_time);
        $statistics_log_mod = M("statistics_log_".$end_extend);
        $data = array(
            "url"       => $_SERVER['REQUEST_URI'],
            "add_time"  => $now_time
        );
        $statistics_log_mod->add($data);
	    $api_no=C('API_NO');
	    $api=$api_no[$this->srv];

	    call_user_func_array(array(A($api['m']),$api['a']), array());

	}
}