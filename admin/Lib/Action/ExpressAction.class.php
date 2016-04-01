<?php
/**
 * 物流查询
 * @author tdl
 *
 */
class ExpressAction extends Action
{
    public function query() {
		$key = '7d0dafd7fa980897ae5bc3a32c5f7bcc'; 
		$com = isset($_GET['com']) ? $_GET['com'] : '';
		$no = isset($_GET['expressno']) ? $_GET['expressno'] : 0;
		empty($no) ? $reason = '单号为空' : $reason = '查询成功';
		$datatype = "json";
        if(isset($no)) {
			if($com == "yzxb") {
				$url = "http://my.kiees.cn/post.php?wen=$no&ajax=1&rnd=0.7950082935858518";
				$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$output = curl_exec($ch);
				
				//打印获得的数据 数组格式
				preg_match_all("/<tr><td>(.*?)<\/td><td>/is",$output,$time);
				$time_data = $time["1"];
				preg_match_all("/<\/td><td>(.*?)<\/td><\/tr>/is",$output,$record);
				$record_data = $record["1"];
				foreach($time_data as $key => $val) {
					$list[$key]["datetime"] = $val;
					$list[$key]["remark"] = $record_data[$key];
					$list[$key]["zone"] = "";
				}
				$data["list"] = empty($list) ? "" : $list;
				$data["no"] = $no;
				empty($list) ? $status = 0 : $status = 1;
				empty($list) ? $resultcode = "404" : $resultcode = "200";
				$output_array = array(
					'result' => $data,
					'resultcode' => $resultcode,
					'reason' => $reason,
					'status' => $status
				);
				$output_json = json_encode($output_array);
				curl_close($ch);
				print_r($output_json);
			}else {
				$url = "http://v.juhe.cn/exp/index";
				$post_data = array (
					"key"   => $key,
					"com"   => $com,
					"no"    => $no,
					"dtype" => $datatype
				);
				$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
				$output = curl_exec($ch);
				curl_close($ch);
				print_r($output);
			}
        } else {
            return '运单号不能为空';
        }
    }
}
?>