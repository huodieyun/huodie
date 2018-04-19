<?php
defined('IN_IA') or exit('Access Denied');
global $_W,$_GPC;
load()->func("tpl");
ini_set('date.timezone','Asia/Shanghai');

require_once __DIR__."/WxPay.Api.php";
require_once __DIR__."/WxPay.NativePay.php";
require_once __DIR__.'/log.php';
ini_set('date.timezone','Asia/Shanghai');
$op = $_GPC["op"]?$_GPC["op"]:"video_shop";

if ($op == "video_shop") {
    //视频余额查询
    $nums = pdo_fetch("select * from ".tablename("account_wechats")." where uniacid=:uniacid",array(":uniacid"=>$_W["uniacid"]));
    $shop_list = pdo_fetchall("select * from " . tablename("tg_video_shoplist"));
    $num = pdo_fetch("select * from " . tablename("account_wechats") . " where uniacid=:uniacid", array("uniacid" => $_W["uniacid"]));
    //查找会员的购买记录
    $list = pdo_fetchall("select * from " . tablename("tg_video_shop") . " where uniacid = :uniacid", array(":uniacid" => $_W["uniacid"]));
    for ($i = 0; $i < count($list); $i++) {
        $id = $list[$i]["id"];
        $gname = pdo_fetch("select * from " . tablename("tg_video_shoplist") . " where id = :id", array(":id" => $id));
        $list[$i]["name"] = $gname["name"];
        $list[$i]["time"] = date("Y-m-d H:i:s",$list[$i]["time"] );
    }
    include wl_template("goods/video_list");
}
if($op == "pay"){
    ini_set('date.timezone','Asia/Shanghai');
    $money = $_GPC["money"];
    $money = $money*100;
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
    $num=WxPayConfig::MCHID.date("YmdHis");
    $input->SetOut_trade_no($num);
    $input->SetBody("扫码购买视频次数");
    $input->SetAttach("扫码购买视频次数");
    $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
    $input->SetTotal_fee($money);//分为单位
    $input->SetTime_start(date("YmdHis"));//开始时间
    $input->SetTime_expire(date("YmdHis", time() + 600));//结束时间
    $input->SetGoods_tag("二维码标签");
    $input->SetNotify_url(web_url("goods/video_shop/check"));
    $input->SetTrade_type("NATIVE");
    $input->SetProduct_id("123456789");//传过来的id
//    var_dump($input);die();
    $result = $notify->GetPayUrl($input);
    $url2 = $result["code_url"];
    if (!empty($url2)){
        $ret = array("status"=>"success","data"=>array("url"=>urlencode($url2),"order_id"=>$num));
        die(json_encode($ret));
    }else{
        $ret = array("status"=>"success","data"=>"参数缺失");
        die(json_encode($ret));
    }
    exit();
}
function printf_info($data)
{
    foreach($data as $key=>$value){
        echo "<font color='#f00;'>$key</font> : $value <br/>";
    }
}
if ($op == "check"){
    $order_id = $_GPC["out_trade_no"];
//    var_dump($_GPC);
    file_put_contents(TG_DATA."payresult2.log", var_export($_REQUEST, true).PHP_EOL, FILE_APPEND);
    $logHandler= new CLogFileHandler("./logs/".date('Y-m-d').'.log');
    $log = Log::Init($logHandler, 15);
    if(isset($_REQUEST["transaction_id"]) && $_REQUEST["transaction_id"] != ""){
        $transaction_id = $_REQUEST["transaction_id"];
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
//        printf_info(WxPayApi::orderQuery($input));
        $result=WxPayApi::orderQuery($input);
        echo $result['trade_state'];
        exit();
    }
    if(isset($_REQUEST["out_trade_no"]) && $_REQUEST["out_trade_no"] != ""){
        $out_trade_no = $_REQUEST["out_trade_no"];
        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($out_trade_no);
//        printf_info(WxPayApi::orderQuery($input));
        $result=WxPayApi::orderQuery($input);
        echo $result['trade_state'];
        exit();
    }
}
if ($op == "check_success"){
    $orderid = $_GPC["order_id"];//订单编号
    //校验是否 接受
//    $order_id = intval($order_id);
    $input = new WxPayOrderQuery();
    $input->SetOut_trade_no($orderid);
    $result=WxPayApi::orderQuery($input);
    if ($result['trade_state'] == "SUCCESS"){
        //查询现有条数
        $res = pdo_fetch("select play_num from ".tablename("account_wechats")." where uniacid = :uniacid",array(":uniacid"=>$_W["uniacid"]));
        //支付成功 //增加次数
        $num = $_GPC["num"];
        $num = intval($num);
        $data["play_num"] = $res["play_num"]+$num;
        pdo_update("account_wechats",$data,array("uniacid"=>$_W["uniacid"]));
        $data2["gid"] = 0;
        $data2["uniacid"] = $_W["uniacid"];
//        $data["money"] = intval($result["cash_fee"])/100;
        $data2["money"] = 0.01;
        $data2["time"] = time();
        pdo_insert("tg_video_shop",$data2);
        $ret = array("status"=>"success","data"=>"成功");
        die(json_encode($ret));
    }else{
        $ret = array("status"=>"error","data"=>"订单状态有误");
        die(json_encode($ret));
    }
}