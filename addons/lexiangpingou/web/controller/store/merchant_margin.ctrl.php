<?php
defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
load()->func("tpl");
ini_set('date.timezone', 'Asia/Shanghai');
require_once __DIR__ . "/WxPay.Api.php";
require_once __DIR__ . "/WxPay.NativePay.php";
require_once __DIR__ . '/log.php';

//多商户保证金记录
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_merchant_margin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL COMMENT '公众账号id',
  `merchant_id` int(11) NOT NULL COMMENT '多商户id',
  `createtime` int(11) NOT NULL DEFAULT 0 COMMENT '缴纳保证金时间',
  `updatetime` int(11) NOT NULL DEFAULT 0 COMMENT '退保证金时间',
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '是否退款',
  `margin` DOUBLE(10,2) NOT NULL DEFAULT 0 COMMENT '保证金金额',
  `cash_fee` DOUBLE(10,2) NOT NULL DEFAULT 0 COMMENT '实际支付保证金金额',
  `orderid` VARCHAR(50) NOT NULL COMMENT '支付单号',
  `transaction_id` VARCHAR(50) NOT NULL COMMENT '微信支付单号',
  `openid` VARCHAR(50) NOT NULL COMMENT '微信id',
  PRIMARY KEY (`id`),
  KEY `idx_merchant_id` (`merchant_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_createtime` (`createtime`),
  KEY `idx_orderid` (`orderid`),
  KEY `idx_status` (`status`),
  KEY `idx_uniacid` (`uniacid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$op = $_GPC['op'];
$account = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}' ");
wl_load()->model('setting');
$ifrefund = setting_get_by_name('refund');
//$ifrefund = pdo_fetch("select * from " .tablename('tg_setting') ." where uniacid = '{$_W['uniacid']}' and `key` = 'refund' ");
//$ifrefund = unserialize($ifrefund['value']);
$appid = $account['key'];
$mchid = $ifrefund['mchid'];
$key = $ifrefund['apikey'];
$appsecret = $account['secret'];
//die(json_encode(array('a'=>$appid,'m'=>$mchid,'k'=>$key,'p'=>$appsecret)));
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
    $input->SetBody("缴纳保证金");
    $input->SetAttach("缴纳保证金");
    $input->SetOut_trade_no($mchid . date("YmdHis"));
    $input->SetTotal_fee($money);//分为单位
    $input->SetTime_start(date("YmdHis"));//开始时间
    $input->SetTime_expire(date("YmdHis", time() + 600));//结束时间
    $input->SetGoods_tag("二维码标签");
    $input->SetNotify_url(web_url("store/merchant_margin/check"));
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
    file_put_contents(TG_DATA . "merchant_margin.log", var_export($_REQUEST, true) . PHP_EOL, FILE_APPEND);
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
    //校验是否 接受
//    $order_id = intval($order_id);
    $input = new WxPayOrderQuery();
    $input->SetOut_trade_no($orderid);
    $result = WxPayApi::orderQuery($input);
//    die(json_encode($result));
    if ($result['trade_state'] == "SUCCESS") {
        //查询金额
        $res = pdo_fetch("select uniacid,margin from " . tablename("account_wechats") . " where uniacid = '{$_W['uniacid']}' ");
        //支付成功
        $data["margin"] = $res["margin"];
        pdo_update('tg_merchant', $data, array('uniacid' => $_W['uniacid'], 'id' => $_W['user']['merchant_id']));
        $dat = array();
        $dat['uniacid'] = $res['uniacid'];
        $dat['merchant_id'] = $_W['user']['merchant_id'];
        $dat['createtime'] = TIMESTAMP;
        $dat['status'] = 0;
        $dat['margin'] = $data['margin'];
        $dat['cash_fee'] = intval($result["cash_fee"]) / 100;
        $dat['orderid'] = $orderid;
        $dat['transaction_id'] = $result['transaction_id'];
        $dat['openid'] = $result['openid'];
        pdo_insert('tg_merchant_margin' , $dat);
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
    $merchant = pdo_fetch("select * from " .tablename('tg_merchant') ." where uniacid = '{$_W['uniacid']}' and id = '{$id}' and margin > 0 ");
    $margin = pdo_fetch("select * from " .tablename('tg_merchant_margin') ." where uniacid = '{$_W['uniacid']}' and merchant_id = '{$id}' and status = 0 ");
    $transaction_id = $margin["transaction_id"];//支付单号
    $fee = $margin["cash_fee"] * 100;
    $openid = $margin["openid"];
    $refund_no = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
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
    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_cert.pem';
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_key.pem';

    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = TG_CERT . $_W['uniacid'] . '/apiclient_cert.pem';
        $path_key = TG_CERT . $_W['uniacid'] . '/apiclient_key.pem';
    }

    include_once TG_CERT . 'WxPay.Api.php';
    $WxPayApi = new WxPayApi();
    $input = new WxPayRefund();
    $input->SetAppid($appid);
    $input->SetMch_id($mchid);
    $input->SetOp_user_id($mchid);
    $input->SetRefund_fee($fee);
    $input->SetTotal_fee($fee);
    $input->SetTransaction_id($transaction_id);
    $input->SetOut_trade_no($margin["orderid"]);
    $input->SetOut_refund_no($refund_no);
    //$input -> SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
    $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);

    if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {
        /*退款成功消息提醒*/
        pdo_update('tg_merchant' , array('status' => 6 , 'margin' => 0) , array('id' => $id));
        pdo_update('tg_merchant_margin' , array('status' => 1) , array('id' => $margin['id']));
        die(json_encode(array('errno' => 0 , 'message' => '退款成功！')));
    } else {
        die(json_encode(array('errno' => 1 , 'message' => '退款失败！')));
    }
}
include wl_template('store/merchant_margin');
exit();
