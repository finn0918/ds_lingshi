<?php

/**
 * 同步E店宝数据
 * User: nangua
 * Date: 2015/12/03
 * Time: 18:02
 */
class SyncSkuFromEdbAction extends Action {
    /**
     * +----------------------------------------------------------
     * 将E店宝的数据同步过来
     * +----------------------------------------------------------
     */
    public function sync() {

        $data = file_get_contents("php://input");
        Log::write("接收参数JSON串：".$data,Log::INFO);
        $data = json_decode($data,true);

        Log::write("接收到的数据" . var_export($data, true), Log::INFO);
        // 验证密钥是否正确
        $lsxm = C('SECRET_KEY');
        $xiaomiao = $lsxm['edb']['secret_key'];
        $md5 = $xiaomiao . $data['time'];
        $md5 = md5($md5);
        $answer = array();//响应
        if ($data['encrypt'] != $md5) {
            $answer = array('flag' => 0, 'msg' => 'error');
            die(json_encode($answer));
        }

        // $spu xiaomiao.f3322.net
        $spu_mod = M("spu");
        $sku_list_mod = M("sku_list");
        $spu_cate_rel_mod = M("spu_cate_relation");
        $spu_cate_mod = M("spu_cate");
        $brand_mod = M("brand");
        $suppliers_mod = M("suppliers");
        $spu_attr_mod = M("spu_attr");
        $attr_id = 12;// 这里硬编码，虽然这样不好。
        $sku_data_arr = $data['skulist'];
        $time = time();
        $sku_number_str = "";// sku条形码
        $brand_name_str = ""; // sku的品牌名称
        $cate_name_str = "";// sku 的分类名称
        foreach ($sku_data_arr as $v) {
            // 不存在条形码的话则不处理
            if(!empty($v['bar_code'])){
                $sku_number_str .= "'".$v['bar_code']."',";
            }
            if(!empty($v['brand_name'])){
                $brand_name_str .= "'" . $v['brand_name'] . "',";
            }
            if(!empty($v['category_name'])){
                $cate_name_str .= "'" . $v['category_name'] . "',";
            }
        }

        $sku_number_str = htmlspecialchars(trim($sku_number_str,","));
        $brand_name_str = htmlspecialchars(trim($brand_name_str, ","));
        $cate_name_str = htmlspecialchars(trim($cate_name_str, ","));
        $brand_data = $brand_mod->where("name in($brand_name_str)")->field("id,name")->select();
        $cate_data = $spu_cate_mod->where("name in($cate_name_str)")->field("id,name")->select();
        $sku_list_data = $sku_list_mod->where("sku_number in($sku_number_str)")->getField("sku_number",true);
        $suppliers_name = $suppliers_mod->where("suppliers_id = 4")->getField("suppliers_name");
        // 获得sku的attr_id
        Log::write("记录下拼接的字符串:条形码".$sku_number_str."\n"."品牌名称:".$brand_name_str."\n分类的名称".$cate_name_str,Log::INFO);
        Log::write("记录下查询到的数据:条形码".var_export($sku_list_data,true)."\n"."品牌名称:".var_export($brand_data,true)."\n分类的名称".var_export($cate_data,true),Log::INFO);

//      $brand_ids = $brand_mod->where("name in($sku_number_str)")->field("id");
        // 避免出现空值 e店宝太坑，得做保护。
        $brand_data = empty($brand_data) ? array():secToOne($brand_data, 'id', 'name');
        $cate_data = empty($cate_data) ? array():secToOne($cate_data, "id", 'name');

        // 只添加未存在的sku
        $sku_list_data = empty($sku_list_data)? array():$sku_list_data;

        // 开始插入商品数据，所有商品都是待编辑状态
        $filter_arr = array("num"=>0,"list"=>array());//被过滤数据
        $new_arr    = array("num"=>0,"list"=>array());//新增数据
        foreach ($sku_data_arr as $v) {

            // 避免重新推送的时候，添加重复数据
            if(in_array($v['bar_code'],$sku_list_data)){
                Log::write("被过滤掉的数据".var_export($v,true),Log::INFO);
                $filter_arr['num']++;
                $filter_arr['list'][]=$v['bar_code'];
                continue;
            }
            $new_arr['num']++;
            $new_arr['list'][]=$v['bar_code'];

            $brand_id = array_search($v['brand_name'], $brand_data);//可能找不到就返回false;
            $brand_id = $brand_id ? $brand_id : 0;
            $cate_id = array_search($v['category_name'], $cate_data);
            $cate_id = $cate_id ? $cate_id : 0;

            // 先检查sku表
            $insert_data = array(
                "spu_name"      => htmlspecialchars($v['pro_name'],ENT_QUOTES),
                "details"       => htmlspecialchars($v['pro_intro'],ENT_QUOTES),
                "guide_type"    => 0,
                "suppliers_id"  => 4,
                "suppliers"     => $suppliers_name,
                "brand_id"      => $brand_id,
                "suppliers_now" => intval($v['avg_taxCost']),
                "add_time"      => $time,
                "update_time"   => $time,
                "price_old"     => intval($v['market_price']),
                "status"        => 5,
            );

            $spu_mod->startTrans();// 开启事务
            if ($spu_id = $spu_mod->add($insert_data)) {

                $spu_attr_data = array(
                    "spu_id" => $spu_id,
                    "attr_id" => $attr_id,
                    "attr_value" => $v['pro_attr'] =='NULL'? "": htmlspecialchars($v['pro_attr'],ENT_QUOTES)// 还是避免出错
                );

                if (!$combination = $spu_attr_mod->add($spu_attr_data)) {
                    $err_sql = $spu_attr_mod->getLastSql();
                    $spu_mod->rollback();
                    $answer["{$v['bar_code']}"] = array('flag' => 0, 'msg' => '该sku的规格添加不正常');
                    Log::write("该sku的规格添加不正常" . var_export($v, true).'相关sql'.$err_sql, Log::WARN);
                    continue;//继续下一个循环
                }
                // 添加sku表
                $sku_data = array(
                    "spu_id"            => $spu_id,
                    "attr_combination"  => $combination,
                    "sku_stocks"        => intval($v['sell_stock']),
                    "price"             => intval($v['avg_taxCost']),
                    "sku_number"        => htmlspecialchars($v['bar_code'],ENT_QUOTES),
                    "suppliers_id"      => 4
                );
                if(!$sku_list_mod->add($sku_data)){
                    $err_sql = $sku_list_mod->getLastSql();
                    $answer["{$v['bar_code']}"] = array('flag' => 0, 'msg' => '该sku_list数据添加不成功');
                    Log::write("该sku_list数据添加不成功" . var_export($v, true).'相关sql'.$err_sql, Log::WARN);
                    continue;//继续下一个循环
                }
                // 如果分类id匹配成功，则存入分类与商品关系表
                if ($cate_id) {
                    $spu_cate_data = array(
                        "spu_id" => $spu_id,
                        "cate_id" => $cate_id
                    );
                    if (!$spu_cate_rel_mod->add($spu_cate_data)) {
                        $err_sql = $spu_cate_rel_mod->getLastSql();
                        $spu_mod->rollback();

                        $answer["{$v['bar_code']}"] = array('flag' => 0, 'msg' => '该sku数据添加不成功');
                        Log::write("该sku数据添加不成功" . var_export($v, true).'相关sql'.$err_sql, Log::WARN);
                        continue;//继续下一个循环
                    }
                }
                $spu_mod->commit();
            }else{
                $spu_mod->rollback();
                $answer["{$v['bar_code']}"] = array('flag' => 0, 'msg' => 'spu数据添加失败');
                Log::write("spu数据添加失败" . var_export($v, true), Log::WARN);
                continue;
            }
        }

        Log::write("每次推送条数:".count($sku_data_arr)."。过滤商品数".$filter_arr['num']."新添加商品数:".$new_arr['num'],Log::INFO);
        // 如果没有错误信息返回成功
        if(empty($answer)){

            Log::write("推送统计数据:过滤数据" . var_export($filter_arr, true)."\n"."新增数据:".var_export($new_arr,true), Log::INFO);
            $answer_arr = array('flag' => 1, 'msg' => 'success');
            die(json_encode($answer_arr));
        }else{
            $answer_arr = array('flag' => 0, 'msg' => "某些数据更新失败","err_list"=>$answer);
            die(json_encode($answer_arr));
        }
    }

    /**
     * +----------------------------------------------------------
     * 添加e店宝推送过来的数据
     * +----------------------------------------------------------
     */
    public function addGood($data) {

    }
}