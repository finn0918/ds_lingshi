<?php
/**
 * Created by PhpStorm.
 * User: nangua
 * Date: 2015/8/2
 * Time: 20:53
 */

class ShangAction {
    public function peng(){
        header("Content-type: text/html; charset=utf-8");
        $mobile = $_GET["mobi"];
        $client_mod = M('client');
        $user_id = $client_mod->where("mobile = $mobile")->getField("user_id");

        if(empty($user_id)){
            echo "电话号码错误";
            die;
        }
        $sub_order_mod = M('sub_order');
        $sub_order_list = $sub_order_mod->where("user_id = $user_id and status= 1")->select();
        echo "<table>";
        foreach ($sub_order_list as $v) {

            echo "<tr><td>商品名称:【".$v['spu_name']."】  商品单号 ：".$v['sub_order_sn']."</td><td><a href='web.php?m=Shang&a=send&order_sn=".$v['sub_order_sn']."' target='_blank'>发货</a></td></tr>";
        }
        echo "</table>";
    }
    public function modify(){
        $shipping_id = "380017530930";
        $sub_order_mod = M("sub_order");
        $sub_order_mod->where("shipping_id = {$shipping_id}")->setField("shipping_com","qf");
    }
    public function send(){
        header("Content-type: text/html; charset=utf-8");
        $order_sn = $_GET['order_sn'];


        if($_POST){
            $shipping_id = $_POST['shipping_id'];
            $shipping_com = $_POST['shipping_com'];
            if(empty($shipping_id)||empty($shipping_com)){
                echo "不能为空的单号,和运货商,shipping_com";
            }
            $sub_order_mod = M("sub_order");
            $data = array(
                "status"        => 2,
                "shipping_id"   => $shipping_id,
                "shipping_com"  => $shipping_com
            );

            $sub_order_mod->where("sub_order_sn = '$order_sn' ")->save($data);
        }else{
            $str=<<<s
        <html>
            <body>
                <form action="" method='post'>
                    单号：<input type='text' name='shipping_id' ><br/>
                    运货商:<input type='text' name='shipping_com'>
                    <input type='submit' value='提交'>
                </form>
            </body>
        </html>
s;
            echo $str;
        }


    }
}