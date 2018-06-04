<?php

/**
 * ECSHOP 支付宝插件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: lili $
 * $Id: alipay.php 17217 2018-05-31 06:29:08Z lili $
 */


/********回调部分***********/
header('Content-type:text/html;charset=utf-8');
$ReturnArray = array( // 返回字段
    "mch_id" => $_REQUEST["mch_id"], // 商户ID
    "out_trade_no" =>  $_REQUEST["out_trade_no"], // 订单号
    "total_fee" =>  $_REQUEST["total_fee"], // 交易金额
    "trade_state" => $_REQUEST["trade_state"],
    "sign" => $_REQUEST["sign"],
);
  
$Md5key = "ttwp1gx75sf4qlcw0j9qejlouwi6tb";

ksort($ReturnArray);
reset($ReturnArray);
$md5str = "";
foreach ($ReturnArray as $key => $val) {
    $md5str = $md5str . $key . "=" . $val . "&";
}
$sign = strtoupper(md5($md5str . "key=" . $Md5key)); 
if ($sign == $_REQUEST["sign"]) {
    
    if ($_REQUEST["returncode"] == "00") {

           $str = "交易成功！订单号：".$_REQUEST["orderid"];
           exit("SUCCESS");
    }
}




/*******************/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/ykppay.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'ykppay_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = 'lili';

    /* 网址 */
    $modules[$i]['website'] = '';

    /* 版本号 */
    $modules[$i]['version'] = '1.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'pay_memberid',            'type' => 'text',   'value' => ''), //商户ID
        array('name' => 'Md5key',                    'type' => 'text',   'value' => ''),//密钥
        array('name' => 'pay_bankcode',            'type' => 'text',   'value' => ''), //银行编码
        array('name' => 'pay_tongdao',               'type' => 'text',   'value' => ''),//选择支付通道
        array('name' => 'pay_tradetype',             'type' => 'text',   'value' => ''),//通道编码
        
    );

    return;
}

/**
 * 类
 */
class ykppay
{

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function ykppay()
    {
    }

    function __construct()
    {
        $this->ykppay();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {

        date_default_timezone_set('Asia/Shanghai'); 
        header("Content-type: text/html; charset=utf-8");
        if (!defined('EC_CHARSET'))
        {
            $charset = 'utf-8';
        }
        else
        {
            $charset = EC_CHARSET;
        }
       
        $version    = '1.0'; //版本号
        $pay_memberid = $payment['pay_memberid'];   //商户ID
        $pay_orderid = $order['order_sn'].substr( md5(date("YmdHis")) ,0,6);    //订单号
        $pay_amount = $order['order_amount'];    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_bankcode = empty($payment['pay_bankcode'])?'WXZF' : $payment['pay_bankcode'];   //银行编码
        $pay_notifyurl = return_url(basename(__FILE__, '.php'));   //服务端返回地址
        $pay_callbackurl = return_url(basename(__FILE__, '.php'));  //页面跳转返回地址
        $pay_tongdao = empty($payment['pay_tongdao'])?'Ips':$payment['pay_tongdao'];;  //选择支付通道
        $pay_tradetype = empty($payment['pay_tradetype'])?'WEB':$payment['pay_tradetype'];  //通道编码
        $pay_productname = "Product";//商品名称
        $Md5key = $payment['Md5key'];    //密钥
        $tjurl = "http://www.ykppay.com/Pay_Index.html"; //网关提交地址

        $jsapi = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );

        ksort($jsapi);
        $md5str = "";
        foreach ($jsapi as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $jsapi["pay_md5sign"] = $sign;
        $jsapi["pay_tongdao"] = $pay_tongdao; //通道
        $jsapi["pay_tradetype"] = $pay_tradetype; //通道类型 
        $jsapi["pay_productname"] = $pay_productname; //商品名称



         $def_url = '<form name="pay" action="http://www.ykppay.com/Pay_Index.html" method="post" target="_blank">'.            
                   "<input type=\"hidden\" name=\"pay_memberid\" value=\"{$pay_memberid}\" />".
                   "<input type=\"hidden\" name=\"pay_orderid\" value=\"{$pay_orderid}\" />".
                   "<input type=\"hidden\" name=\"pay_amount\" value=\"{$pay_amount}\" />".
                   "<input type=\"hidden\" name=\"pay_applydate\" value=\"{$pay_applydate}\" />".
                   "<input type=\"hidden\" name=\"pay_bankcode\" value=\"{$pay_bankcode}\" />".
                   "<input type=\"hidden\" name=\"pay_notifyurl\" value=\"{$pay_notifyurl}\" />".
                   "<input type=\"hidden\" name=\"pay_callbackurl\" value=\"{$pay_callbackurl}\" />".
                   "<input type=\"hidden\" name=\"pay_md5sign\" value=\"{$sign}\" />".
                   "<input type=\"hidden\" name=\"pay_tongdao\" value=\"{$pay_tongdao}\" />".
                   "<input type=\"hidden\" name=\"pay_tradetype\" value=\"{$pay_tradetype}\" />".
                   "<input type=\"hidden\" name=\"pay_productname\" value=\"{$pay_productname}\" />".
                   "&nbsp;<input type=\"submit\" value=\"{$GLOBALS['_LANG']['pay_button']}\" />".
                    '</form>';




       
       
        return $def_url;
    }

    /**
     * 响应操作
     */
    function respond()
    {

 

        $input_str = file_get_contents('php://input');
        


        if (!empty($_REQUEST))
        {
            $arr = $_REQUEST;
        }
       
       


        $dataarr = $this->xmlToArray($arr['paymentResult']);

        $ReqDate = $dataarr['GateWayRsp']['head']['ReqDate'];
        $RspDate = $dataarr['GateWayRsp']['head']['RspDate'];
        $Signature = $dataarr['GateWayRsp']['head']['Signature'];


        $MerBillNo = $dataarr['GateWayRsp']['body']['MerBillNo'];
        $CurrencyType = $dataarr['GateWayRsp']['body']['CurrencyType'];
        $Amount = $dataarr['GateWayRsp']['body']['Amount'];
        $Status = $dataarr['GateWayRsp']['body']['Status'];
        $IpsBillNo = $dataarr['GateWayRsp']['body']['IpsBillNo'];
        $IpsTradeNo = $dataarr['GateWayRsp']['body']['IpsTradeNo'];
        $RetEncodeType = $dataarr['GateWayRsp']['body']['RetEncodeType'];
        $BankBillNo = $dataarr['GateWayRsp']['body']['BankBillNo'];
        $ResultType = $dataarr['GateWayRsp']['body']['ResultType'];
        $IpsBillTime = $dataarr['GateWayRsp']['body']['IpsBillTime'];

        $order_sn = substr($MerBillNo,0,-6);

        $log_id = get_order_id_by_sn($order_sn);
        /* 检查支付的金额是否相符 */
        if (!check_money($log_id, $Amount))
        {
            
            return false;
        }

        if ($dataarr['GateWayRsp']['body']['Status'] != 'Y') {
             /* 改变订单状态 */
              /* 改变订单状态 */
            // echo('n');
            // echo($order_sn);
            // echo($Amount);
            order_paid($log_id, 2);

            return true;
        }


        if ($dataarr['GateWayRsp']['body']['Status'] == 'Y') {
             /* 改变订单状态 */
              /* 改变订单状态 */
            // echo('Y');
            // echo($order_sn);
            // echo($Amount);
            order_paid($log_id);

            return true;
            
        }


        return false;
    }


 

        /**
         * 作用：将xml转为array
         */
    function xmlToArray($xml)
    {       
           //将XML转为array        
           $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);       
            return $array_data;
    }


 

}

?>