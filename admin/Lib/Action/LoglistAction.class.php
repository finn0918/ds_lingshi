<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/7
 * Time: 14:54
 */
class LoglistAction extends BaseAction
{

    public function index()
    {
        $timeStart = I('get.time_start','','trim,htmlspecialchars');//添加时间区间开始
        $search['time_start'] = $timeStart;
        $timeStart = strtotime($timeStart);
        $timeEnd = I('get.time_end','','trim,htmlspecialchars');//添加时间区间结束
        $search['time_end'] = $timeEnd;
        $timeEnd = strtotime($timeEnd);
        $keyword = I('get.keyword','','trim,htmlspecialchars');//关键词
        $search['keyword'] = $keyword;
        $this->assign('search',$search);
        $where = " 1=1";
        if ($timeStart) {
            $where .= " AND date >= $timeStart ";
        }
        if ($timeEnd) {
            $where .= " AND date <= $timeEnd ";
        }
        $statisticsAnalysis2Mod = M("statistics_analysis");
        $spuMod = M("spu");
        $subOrderMod = M("sub_order");
        if($keyword){
            $where .= " AND goods_id = $keyword";
        }
        $count = $statisticsAnalysis2Mod->where($where)->count();
        import("ORG.Util.Page");
        $pageSize = 10;
        $p = new Page($count, $pageSize);
        $page = $p->show();
        $list = $statisticsAnalysis2Mod->where($where)->limit($p->firstRow . " , " . $p->listRows)->field("id,date,goods_id,click_num")->order("date desc")->select();
	$time_space = 24*60*60;
        foreach ($list as $k => $v) {
            $spu_name = $spuMod->where("spu_id=".$v['goods_id'])->find();
            $pay_num = $subOrderMod->where("spu_id=".$v['goods_id']." AND add_time>=".($v['date'])." AND add_time<".($v['date']+$time_space)." AND pay_time>0")->count();
            $list[$k]['goods_name'] = $spu_name['spu_name'];
            $list[$k]['pay_num'] = $pay_num;
        }
        if($_GET['kaiguan'] == 1){
		 $this->LogGoodsDown($timeStart,$timeEnd,$keyword,$time_space);
        }
        $this->assign('template', $list);
        $this->assign('page', $page);
        $this->display();
    }
    
    //商品日志列表下载
    public function LogGoodsDown($timeStart,$timeEnd,$keyword,$time_space)
    {
        $statisticsAnalysis2Mod = M("statistics_analysis");
        $spuMod = M("spu");
        $subOrderMod = M("sub_order");
        $where = " 1=1";
        if ($timeStart) {
            $where .= " AND date >= $timeStart ";
        }
        if ($timeEnd) {
            $where .= " AND date <= $timeEnd ";
        }
        
        if($keyword){
            $where .= " AND goods_id = $keyword";
        }
    $list = $statisticsAnalysis2Mod->where($where)->field("id,date,goods_id,click_num")->order("date desc,click_num desc")->select();
 //   print_r($statisticsAnalysis2Mod->getLastSql());echo '新查的';echo '<hr />';
  //  echo count($list);echo '<hr />';print_r($list);echo '<hr />';
        $time_space = 24*60*60;
        foreach ($list as $k => $v) {
            $spu_name = $spuMod->where("spu_id=".$v['goods_id'])->find();
            $pay_num = $subOrderMod->where("spu_id=".$v['goods_id']." AND add_time>=".($v['date'])." AND add_time<".($v['date']+$time_space)." AND pay_time>0")->count();
            $list[$k]['goods_name'] = $spu_name['spu_name'];
            $list[$k]['pay_num'] = $pay_num;
        }
       // echo'改变后的';echo count($list);echo '<hr />';print_r($list);echo '<hr />';
        
        
        if ($list) {
            foreach ($list as $kk => $vv) {
                foreach ($vv as $kkk => $vvv) {
                    if ($kkk == 'date') {
                        $list[$kk][$kkk] = date("Y-m-d", $vvv);            //echo $vvv; die;
                    } 
                }
            }
         //   echo'excel';echo count($list);echo '<hr />';print_r($list);echo '<hr />';
            $ts = date("Y-m-d",$timeStart);
            $te = date("Y-m-d",$timeEnd);
            $d = $ts."至".$te;
            header('Content-type:application/vnd.ms-excel');
            header("Content-Disposition:attachment;filename=" . "$d" . ".xls");
            echo "商品日志信息\n";
//             foreach ($list[0] as $k => $v) {
//                 echo $k . "\t";//取字段名称
//             }
                       echo "id\t";
                       echo "日期\t";
                       echo "商品id\t";
                       echo "商品名称\t";
                       echo "访问数\t";
                       echo "下单数\t";
            echo "\n";
            foreach ($list as $key => $value) {
                echo $list[$key]['id'] . "\t";
                echo $list[$key]['date'] . "\t";
                echo $list[$key]['goods_id'] . "\t";
                echo $list[$key]['goods_name'] . "\t";
                echo $list[$key]['click_num'] . "\t";
                echo $list[$key]['pay_num'] . "\t";
                echo "\n";
            }
            exit;
        }
        else{
            $this->error('亲，查询无相关的数据！请联系管理员！','',1);
        }
        $this->display();
    }
    
  
}
