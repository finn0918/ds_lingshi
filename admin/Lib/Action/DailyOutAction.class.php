<?php
/**
 * 每日数据查询
 * @author wyh
 *
 */
class DailyOutAction extends Action
{
    public function index() {
		$subMol = M('sub_order');
		$timeStart = $_GET['time_start'] ? $_GET['time_start'] : '';
		$timeEnd   = $_GET['time_end'] ? $_GET['time_end'] : '';
		$keyword   = $_GET['keyword'] ? trim($_GET['keyword']) : '';
		$page = $_GET['page'] ? trim($_GET['page']) : '';
		
		//执行查询语句
				
		$where['pay_time'] = array('gt',0);
		$where['pay_time'] = array('between',array($timeStart,$timeEnd));
		$where['spu_name'] = array('like','%'.$keyword.'%');
		$limit = 20;
		// 查询限制索引，从0开始
		$page > 1 ? $fristRow = intval(($page-1)*$limit) : $fristRow = intval($page)-1;
		$lastRow = $fristRow+$limit;
		// 开始查询
		$payData = $subMol->where($where)->field("spu_id,spu_name,sum(nums) as sum_num")->group('spu_id')->limit($fristRow,$lastRow)->order('sum_num desc')->select();// edit by hhf 
		$payPage = $subMol->where($where)->count("DISTINCT(spu_id)");// edit by hhf 
		$count = $payPage;
		//print_r($subMol->getLastSql());	

		// 循环输出数组
		foreach($payData as $v){
			$list_array[] = array (
				'spu_id' 	=> $v['spu_id'],
				'spu_name' 	=> $v['spu_name'],
				'nums' 		=> $v['sum_num']
			);
		};
		// 返回结果
		$data = array(
			'result'  => $list_array,
			'page'    => $show,
			'count'   => $count
		);
		$jsonData = json_encode($data);
		//$this->assign('page',$show);
		print_r($jsonData);
    }
	public function query(){
		$this->display();
	}
}
?>