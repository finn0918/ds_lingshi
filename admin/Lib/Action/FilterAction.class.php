<?php
/**
* 文件:filterAction.class.php
* ---------------------
* 商品采集过滤
* ---------------------
* 日期: 2015-1-4 下午5:51:37
* @author: hch
*/
class filterAction extends Action {

	/**
	* 过滤商品
	* @param:string title 标题
	* @param:string id 商品id
	*/
	function filter_url_title($id, $url) {
		$url_key = md5($url);
		$id_key = md5($id);
		$count1 = M('filter_goods')->where("url_key='$url_key'")->count();
		if($count1) {
			return false;
		} else {
			$count2 = M('filter_goods')->where("id_key='$id_key'")->count();
			if($count2) {
				return false;
			} else {
				return true;
			}
		}
    }
}
?>
