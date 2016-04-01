<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/7
 * Time: 15:22
 */
class GetlogAction extends Action{
    public function test()
    {
        $spuMod = M('spu');
        $goods_array = $spuMod->where("guide_type=0")->field("spu_id,guide_type")->select();

        $goods_spuid_array = array();
        //$goods_guide_type_array = array();
        foreach ($goods_array as $k => $v) {
            $goods_spuid_array[] = $v['spu_id'];
            //$goods_guide_type_array[] = $v['guide_type'];
        }


        $taskTestMod = M("test.statistics_analysis1","ls_");

        $len = 1000;
        $i = 0;
        $flag = 1;
        $arr = array();
        $arr_tmp = array();
        $mod = M('statistics_log');
        while ($flag) {
            $res = $mod->where("1=1")->limit("$i, $len")->select();

            foreach($res as $k => $v) {
                $tmp = explode('?', $v['url'], 2);

                $resURL = array();
                parse_str($tmp[1],$resURL);
                if ($resURL['srv'] == 2502&&in_array($resURL['goods_id'], $goods_spuid_array)) {
                    $arr_tmp['time'] = $res[$k]['add_time'];
                    $arr_tmp['goods_id'] = $resURL['goods_id'];
                    $taskTestMod->add($arr_tmp);
                    //$arr[] = $arr_tmp;

                }
            }

            $i += $len;
            if (empty($res)) {
                $flag = 0;
            }

            /*$values = '';
            foreach ($arr as $k => $v){
                $values .= "(".$v['time'].", ".$v['goods_id']."),";
            }
            $values = substr($values, 0, strlen($values)-1).";";
            $taskTestMod->execute("insert into ls_statistics_analysis1(time, goods_id) values".$values);

            echo $taskTestMod->getLastSql();*/

        }
    }

    public function test09()
    {
        $spuMod = M("ds_lingshi.spu","ls_");
        $goods_array = $spuMod->where("guide_type=0")->field("spu_id")->select();

        $goods_spuid_array = array();
        //$goods_guide_type_array = array();
        foreach ($goods_array as $k => $v) {
            $goods_spuid_array[] = $v['spu_id'];
            //$goods_guide_type_array[] = $v['guide_type'];
        }

        $taskTestMod = M();
        $len = 5000;
        $i = 0;
        $flag = 1;
        $arr = array();
        $arr_tmp = array();
        $mod = M("ds_lingshi.statistics_log","ls_");
        while ($flag) {
            $values = "";
            $res = $mod->where("id > 0")->limit("$i, $len")->select();

            foreach($res as $k => $v) {
                $tmp = explode('?', $v['url'], 2);

                $resURL = array();
                parse_str($tmp[1],$resURL);
                if ($resURL['srv'] == 2502&&in_array($resURL['goods_id'], $goods_spuid_array)) {
                    //$arr_tmp['time'] = $res[$k]['add_time'];
                    //$arr_tmp['goods_id'] = $resURL['goods_id'];
                    //$taskTestMod->add($arr_tmp);
                    $values .= "(".$v['add_time'].", ".$resURL['goods_id']."),";
                }
            }

            $i += $len;
            echo "现在是找到地{$i}条记录","\n";
            if (empty($res)) {
                //$flag = 0;
                exit;
            }
            unset($res);
            if($values){
                $values = substr($values, 0, strlen($values)-1);
                $sql = "insert into `ls_statistics_analysis1`(`time`, `goods_id`) values ".$values;
                $taskTestMod->execute($sql);
                echo $taskTestMod->getLastSql(),"\n";
            }


        }



        /*$len = 1000;
        $i = 0;
        $flag = 1;
        $arr = array();
        $arr_tmp = array();
        $mod = M('statistics_log_2015_10');
        while ($flag) {
            $res = $mod->where("1=1")->limit("$i, $len")->select();

            foreach($res as $k => $v) {
                $tmp = explode('/api.php?', $v['url']);
                parse_str($tmp[1]);
                if ($srv == 2502) {
                    $tmp_key = array_search($goods_id, $goods_spuid_array);
                    if ($goods_guide_type_array[$tmp_key] == 0) {
                        $arr_tmp['time'] = $res[$k]['add_time'];
                        $arr_tmp['goods_id'] = $goods_id;
                        $arr[] = $arr_tmp;
                    }
                }
            }
            $i += $len;
            if (empty($res)) {
                $flag = 0;
            }

            unset($res);
            //$taskTestMod = M('task_test');
            //$taskTestMod->addAll($arr);
            foreach ($arr as $k => $v){
                $taskTestMod->add($v);
            }
            unset($arr);
        }*/


        /*$len = 1000;
        $i = 0;
        $flag = 1;
        $arr = array();
        $arr_tmp = array();
        $mod = M('statistics_log_2015_11');
        while ($flag) {
            $res = $mod->where("1=1")->limit("$i, $len")->select();

            foreach($res as $k => $v) {
                $tmp = explode('/api.php?', $v['url']);
                parse_str($tmp[1]);
                if ($srv == 2502) {
                    $tmp_key = array_search($goods_id, $goods_spuid_array);
                    if ($goods_guide_type_array[$tmp_key] == 0) {
                        $arr_tmp['time'] = $res[$k]['add_time'];
                        $arr_tmp['goods_id'] = $goods_id;
                        $arr[] = $arr_tmp;
                    }
                }
            }
            $i += $len;
            if (empty($res)) {
                $flag = 0;
            }

            //$taskTestMod->addAll($arr);
            foreach ($arr as $k => $v){
                $taskTestMod->add($v);
            }
        }*/
        //$taskTestMod = M('task_test');

    }

    public function test_analysis() {
        $spuMod = M('spu');
        $goods_array = $spuMod->where("guide_type=0")->field("spu_id,spu_name")->select();

        $goods_spuid_array = array();
        $goods_spuname_array = array();
        foreach ($goods_array as $k => $v) {
            $goods_spuid_array[] = $v['spu_id'];
            $goods_spuname_array[] = $v['spu_name'];
        }
//unset($goods_array);
        //unset($spuMod);
        $len = 1000;
        $i = 0;
        $flag = 1;
        $arr = array();
        $arr_tmp = array();
        $taskTestMod = M("test.statistics_analysis1","ls_");
        while ($flag) {
            $res = $taskTestMod->where("1=1")->limit("$i, $len")->select();

            foreach ($res as $k => $v){
                $date = date("Y-m-d", $v['time']);
                $tmp_key = array_search($v['goods_id'], $goods_spuid_array);
                $spu_name = $goods_spuname_array[$tmp_key];
                //$date_spuname = $date."%".$spu_name."%".$v['goods_id'];
                if (isset($arr_tmp[$date])) {
                    $arr_tmp[$date]['click_num'] += 1;
                } else {
                    $arr_tmp[$date]['click_num'] = 1;
                }
                $arr_tmp[$date]['goods_name'] = $spu_name;
                $arr_tmp[$date]['goods_id'] = $v['goods_id'];
            }

            $i += $len;
            if (empty($res)) {
                $flag = 0;
            }
        }
//unset($goods_spuid_array);
        //unset($goods_spuname_array);
        //unset($res);

        $subOrderMod = M('sub_order');
        $lenOrder = 1000;
        $iOrder = 0;
        $flagOrder = 1;
        while ($flagOrder) {
            $res = $subOrderMod->where("1=1")->group("order_sn,spu_id")->limit("$iOrder, $lenOrder")->select();
            foreach ($res as $k => $v) {
                $tmp_date_order = date("Y-m-d H:i:s", $v['add_time']);
                $tmp_date_order_arr = explode(" ", $tmp_date_order);
                //var_dump($tmp_date_order_arr[0]);
                //var_dump($arr_tmp);
                //exit;
                if ($v['pay_time'] > 0) {
                    $arr_tmp[$tmp_date_order_arr[0]]['pay_num'] += 1;
                }
            }

            $iOrder += $lenOrder;
            if (empty($res)) {
                $flagOrder = 0;
            }
        }

        //unset()
        //var_dump($arr_tmp);
        foreach ($arr_tmp as $k => $v) {
            //$tmp_k = explode("%", $k, 3);
            $insert_tmp_arr['date'] = strtotime($k);
            $insert_tmp_arr['goods_name'] = $v['goods_name'];
            $insert_tmp_arr['goods_id'] = $v['goods_id'];
            $insert_tmp_arr['click_num'] = $v['click_num'];
            //$insert_tmp_arr['pay_num'] = $v['pay_num'];
            $arr[] = $insert_tmp_arr;
        }
        $taskAnalysis1Mod = M("statistics_analysis2","ls_","mysql://zengnanlin:znl88fe@117.25.155.98:3306/test");
        $taskAnalysis1Mod->addAll($arr);
    }
}