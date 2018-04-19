<?php
defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
load()->func("tpl");
date_default_timezone_set('Asia/Shanghai');
require_once MODULE_ROOT . "/web/controller/platform/WxPay.Api.php";
require_once MODULE_ROOT . "/web/controller/platform/WxPay.NativePay.php";
require_once MODULE_ROOT . "/web/controller/platform/log.php";

$op = $_GPC['op'];

$appyid = 'wxd944ffe7d0caafda';
$mchid = '1336922201';
$key = 'zl5dscvpfbemgzf3jddw3wgpeorjqsky';
$appsecret = 'a79de016f41789b8af9de1802c35ecef';

if ($op == "pay") {
    ini_set('date.timezone', 'Asia/Shanghai');
    $money = $_GPC["money"];
    $money = $money * 100;
    $money = intval($money);
    //模式二
    /**
     * 流程：
     * 1、调用统一下单，取得code_url，生成二维码
     * 2、用户扫描二维码，进行支付
     * 3、支付完成之后，微信服务器会通知支付成功
     * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
     */
    $notify = new NativePay();
    //    $url1 = $notify->GetPrePayUrl("");
    $input = new WxPayUnifiedOrder();
    $num = $mchid . date("YmdHis");
    $input->SetOut_trade_no($num);
    $input->SetBody("缴纳订单费用");
    $input->SetAttach("缴纳订单费用");
    $input->SetOut_trade_no($mchid . date("YmdHis"));
    $input->SetTotal_fee($money);//分为单位
    $input->SetTime_start(date("YmdHis"));//开始时间
    $input->SetTime_expire(date("YmdHis", time() + 600));//结束时间
    $input->SetGoods_tag("二维码标签");
    $input->SetNotify_url(web_url("platform/platform_pay/check"));
    $input->SetTrade_type("NATIVE");
    $input->SetProduct_id("123456789");//传过来的id
//    die(json_encode($input->GetTrade_type("NATIVE")));
    $result = $notify->GetPayUrl($input);
//    die(json_encode($result));
    $url2 = $result["code_url"];
    if (!empty($url2)) {
        $ret = array("status" => "success", "data" => array("url" => urlencode($url2), "order_id" => $num));
        die(json_encode($ret));
    } else {
        $ret = array("status" => "success", "data" => "参数缺失");
        die(json_encode($ret));
    }
    exit();
}
function printf_info($data)
{
    foreach ($data as $key => $value) {
        echo "<font color='#f00;'>$key</font> : $value <br/>";
    }
}

if ($op == "check") {
    $order_id = $_GPC["out_trade_no"];
//    var_dump($_GPC);
    file_put_contents(TG_DATA . "platform_pay.log", var_export($_REQUEST, true) . PHP_EOL, FILE_APPEND);
    $logHandler = new CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
    $log = Log::Init($logHandler, 15);
    if (isset($_REQUEST["transaction_id"]) && $_REQUEST["transaction_id"] != "") {
        $transaction_id = $_REQUEST["transaction_id"];
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
//        printf_info(WxPayApi::orderQuery($input));
        $result = WxPayApi::orderQuery($input);
        echo $result['trade_state'];
        exit();
    }
    if (isset($_REQUEST["out_trade_no"]) && $_REQUEST["out_trade_no"] != "") {
        $out_trade_no = $_REQUEST["out_trade_no"];
        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($out_trade_no);
//        printf_info(WxPayApi::orderQuery($input));
        $result = WxPayApi::orderQuery($input);
        echo $result['trade_state'];
        exit();
    }
}

if ($op == "check_success") {
    $orderid = $_GPC["order_id"];//订单编号
    $oid = $_GPC["orderid"];//订单编号
    //校验是否 接受
//    $order_id = intval($order_id);
    $input = new WxPayOrderQuery();
    $input->SetOut_trade_no($orderid);
    $result = WxPayApi::orderQuery($input);
//    die(json_encode($result));
    file_put_contents(TG_DATA . "platform_pay1.log", var_export(array('result' => $result), true) . PHP_EOL, FILE_APPEND);
    if ($result['trade_state'] == "SUCCESS") {
        //支付成功
        $dat = array();
        $dat['uni_paytime'] = TIMESTAMP;
        $dat['uni_payprice'] = floatval($result["cash_fee"]) / 100;
        $dat['uni_pay'] = 2;
        $dat['uni_paytype'] = 0;
        $dat['uni_payno'] = $result['transaction_id'];
        pdo_update('tg_supply_order' , $dat , array('id' => $oid));

        $data = array(
            'uniacid' => $_W['uniacid'],
            'acid' => 33,
            'openid' => $result['openid'],
            'module' => 'platform',
            'tid' => $orderid,
            'fee' => floatval($result["cash_fee"]) / 100,
            'card_fee' => floatval($result["cash_fee"]) / 100,
            'status' => '1',
            'uniontid' => date('YmdHis') . $moduleid . random(8, 1),
            'createtime' => TIMESTAMP,
            'is_usecard' => '0',
            'type' => 'wechat',
            'tag' => serialize($result)
        );
        pdo_insert('core_paylog', $data);
//        pdo_insert('tg_supply_order' , $dat , array('id' => $orderid));

        $acceptor = pdo_getall('tg_platform_acceptor' , array('status' => 1));
        if ($acceptor) {
            $accept = array();
            $orders = pdo_get('tg_supply_order' , array('id' => $oid));
            $shop = pdo_get('tg_platform_shop' , array('uniacid' => $orders['uniacid']));
            $supplier = pdo_get('tg_platform_supplier', array('uniacid' => $orders['supply_id']));
            $accept['first'] = '商家申请发货进度';
            $accept['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $accept['keyword2'] = '商家申请发货';
            $accept['keyword3'] = "火蝶云极限单品商家【" . $shop['name'] . "】购买供货商【".$supplier['name']."】的商品【" . $orders['gname'] . '】申请发货提交成功，等候平台审核';
            $accept['keyword4'] = '待审核';
            $accept['remark'] = '';
            foreach ($acceptor as $item) {
                $accept['openid'] = $item['openid'];
                pdo_insert('tg_service_process', $accept);
            }
        }

        $ret = array("status" => "success", "data" => "成功");
        die(json_encode($ret));
    } else {
        $ret = array("status" => "error", "data" => "订单状态有误");
        die(json_encode($ret));
    }
}


if ($op == "check_success_pifa") {
    $orderid = $_GPC["order_id"];//商户订单编号
    $oid = $_GPC["orderid"];//订单编号
    //校验是否 接受
//    $order_id = intval($order_id);
    $input = new WxPayOrderQuery();
    $input->SetOut_trade_no($orderid);
    $result = WxPayApi::orderQuery($input);
//    die(json_encode($result));
    file_put_contents(TG_DATA . "platform_pay1.log", var_export(array('result' => $result), true) . PHP_EOL, FILE_APPEND);
    if ($result['trade_state'] == "SUCCESS") {
        //支付成功
        $dat = array();
        $dat['uni_paytime'] = TIMESTAMP;
        $dat['uni_payprice'] = floatval($result["cash_fee"]) / 100;
        $dat['uni_pay'] = 2;
        $dat['uni_paytype'] = 0;
        $dat['uni_payno'] = $result['transaction_id'];
        pdo_update('tg_supply_order' , $dat , array('id' => $oid));

        $data = array(
            'uniacid' => $_W['uniacid'],
            'acid' => 33,
            'openid' => $result['openid'],
            'module' => 'platform',
            'tid' => $orderid,
            'fee' => floatval($result["cash_fee"]) / 100,
            'card_fee' => floatval($result["cash_fee"]) / 100,
            'status' => '1',
            'uniontid' => date('YmdHis') . $moduleid . random(8, 1),
            'createtime' => TIMESTAMP,
            'is_usecard' => '0',
            'type' => 'wechat',
            'tag' => serialize($result)
        );
        pdo_insert('core_paylog', $data);
//        pdo_insert('tg_supply_order' , $dat , array('id' => $orderid));
        $ret = array("status" => "success", "data" => "成功");
        die(json_encode($ret));
    } else {
        $ret = array("status" => "error", "data" => "订单状态有误");
        die(json_encode($ret));
    }
}

if ($op == 'apply'){
    $id = $_GPC['id'];
    $re = pdo_update('tg_merchant' , array('status' => 5) , array('id' => $id));
    if ($re){
        die(json_encode(array('errno' => 0 , 'message' => '申请成功！')));
    } else {
        die(json_encode(array('errno' => 1 , 'message' => '申请失败！')));
    }
}

if ($op == 'refund') {
    $id = $_GPC['id'];
    $collect = pdo_fetch("select * from " .tablename('tg_supply_collect') ." where id = '{$id}' ");
    $order = pdo_fetch("select * from " .tablename('tg_supply_order') ." where id = '{$collect['supply_orderid']}' ");
    $paylog = pdo_fetch("select * from " .tablename('core_paylog') ." where uniacid = '{$_W['uniacid']}' and status = 1 ");
    $tag = unserialize($paylog["tag"]);//支付单号
    $transaction_id = intval($tag['transaction_id']);//支付单号
    $fee = $paylog["cash_fee"] * 100;
    $openid = $paylog["openid"];
    $refund_no = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $redund_url = "https://api.mch.weixin.qq.com/secapi/pay/refund";

//    $inputObj = new WxPayRefund();
//    $inputObj->SetAppid($appid);
//    $inputObj->SetMch_id($mchid);
//    $inputObj->SetTransaction_id($transaction_id);
//    $inputObj->SetOut_refund_no($margin["orderid"]);
//    $inputObj->SetRefund_fee($fee);
//    $inputObj->SetTotal_fee($fee);
//    $inputObj->SetOp_user_id($openid);
//    $inputObj->SetOut_refund_no($refund_no);
//    $result = WxPayApi::refund($inputObj);
//die(json_encode($result));
    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/33/apiclient_cert.pem';
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/33/apiclient_key.pem';

    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = TG_CERT . '33/apiclient_cert.pem';
        $path_key = TG_CERT . '33/apiclient_key.pem';
    }
    //require_once MODULE_ROOT ."/WxPay.Api.php";
        //var_dump($ifrefund);die();
    $WxPayApi = new WxPayApi();
    $input = new WxPayRefund();
    $input->SetAppid($appid);
    $input->SetMch_id($mchid);
    $input->SetOp_user_id($mchid);
    $input->SetRefund_fee($fee);
    $input->SetTotal_fee($fee);
    $input->SetTransaction_id($transaction_id);
    $input->SetOut_trade_no($tag["out_trade_no"]);
    $input->SetOut_refund_no($refund_no);
    //$input -> SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
    $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);
//    die(json_encode($result));
    if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {
        /*退款成功消息提醒*/
        pdo_update('core_paylog' , array('cash_fee' => $paylog['cash_fee'] - $collect['oprice']) , array('plid' => $paylog['plid']));
        pdo_update('tg_supply_order' , array('cash_fee' => $paylog['cash_fee'] - $collect['oprice']) , array('id' => $order['id']));
        pdo_update('tg_supply_collect' , array('refund_price' => $collect['oprice'] , 'refund' => 2 , 'refund_update_time' => TIMESTAMP) , array('id' => $collect['id']));
        die(json_encode(array('errno' => 1 , 'message' => '退款成功！')));
    } else {
        die(json_encode(array('errno' => 0 , 'message' => '退款失败！')));
    }
}

exit();
