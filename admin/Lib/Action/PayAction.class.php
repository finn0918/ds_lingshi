<?php
/**
 * Created by PhpStorm.
 * User: nangua
 * Date: 2015/7/22
 * Time: 17:44
 */

class PayAction extends BaseAction{
    /**
    +----------------------------------------------------------
     * 支付宝批量有密退款方法
    +----------------------------------------------------------
     */
    public function refund(){
        require_once(THINK_PATH."Extend/Vendor/alipay/alipayRefund.config.php");
        require_once(THINK_PATH."Extend/Vendor/alipay/lib/alipay_submit.class.php");
//        $_POST['reason'] = "3333";
//        $_POST['trade_no'] = "2015081800001000580062228986";
//        $_POST['price'] = 99;
        // 检查 退款理由的字符长度
        if(mb_strlen($_POST['reason'])>256){
            $this->error("推荐理由超过256个字符，请重新填写");
        }
        $orderSn = htmlspecialchars($_POST['orderSn']);
        $_POST['reason'] = preg_replace('/\^|\||$|\#|/', '', $_POST['reason']);
        $_POST['WIDseller_email'] =  C("SELLER_EMAIL");
        $_POST['WIDrefund_date']  = date("Y-m-d H:i:s",time());
        $_POST['WIDbatch_no']     = date("Ymd",time()).mt_rand(121,30000);
        $_POST['WIDbatch_num']    = $_POST['num'];
        //$_POST['WIDdetail_data']  = $_POST['trade_no']."^".$_POST['price']."^".$_POST['reason'];
        $_POST['WIDdetail_data']  = $_POST['detail'];

        $_SESSION['WIDbatch_no'] = '';
        $_SESSION['WIDbatch_no'] = $_POST['WIDbatch_no'];//存储流水号，供回调使用

        /**************************请求参数**************************/
        //服务器异步通知页面路径
        $notify_url = C("ALIPAY_REFUND_NOTIFY_URL");
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //卖家支付宝帐户
        $seller_email = $_POST['WIDseller_email'];
        //必填

        //退款当天日期
        $refund_date = $_POST['WIDrefund_date'];
        //必填，格式：年[4位]-月[2位]-日[2位] 小时[2位 24小时制]:分[2位]:秒[2位]，如：2007-10-01 13:13:13

        //批次号
        $batch_no = $_POST['WIDbatch_no'];
        //必填，格式：当天日期[8位]+序列号[3至24位]，如：201008010000001

        //退款笔数
        $batch_num = $_POST['WIDbatch_num'];
        //必填，参数detail_data的值中，“#”字符出现的数量加1，最大支持1000笔（即“#”字符出现的数量999个）

        //退款详细数据
        $detail_data = $_POST['WIDdetail_data'];
        //必填，具体格式请参见接口技术文档
        /************************************************************/
        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"       => "refund_fastpay_by_platform_pwd",
            "partner"       => trim($alipay_config['partner']),
            "notify_url"	=> $notify_url,
            "seller_email"	=> $seller_email,
            "refund_date"	=> $refund_date,
            "batch_no"	=> $batch_no,
            "batch_num"	=> $batch_num,
            "detail_data"	=> $detail_data,
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
        );
        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        echo $html_text;
    }
    /**
    +----------------------------------------------------------
     * 退款信息填写支付宝批量有密退款
    +----------------------------------------------------------
     */
    public function index(){

    }
}