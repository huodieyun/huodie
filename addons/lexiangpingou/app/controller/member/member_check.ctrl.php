<?php

$uniacid = $_W['uniacid'];
$check_openid = $_W['openid'];
$id = $_GPC['id'];
$merchantid = $_GPC['merchantid'];
$openid = $_GPC['openid'];
$orderno = $_GPC['orderno'];
$result = $_GPC['result'];
$price = floatval($_GPC['price']);
wl_load()->func("message");
wl_load()->func("print");
wl_load()->model('setting');
$setting = setting_get_by_name("kaiguan");
$pagetitle = $_W['page']['title'] .' - 线下收银';
if (!$check_openid) {
    die("<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                    <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                </head>
                <body>
                <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>请在微信客户端打开链接</h4></div></div></div>
                </body>
            </html>");
}

$is_hexiao_member = FALSE;

//*判断是否是核销人员*/
$hexiao_member = pdo_fetch("select * from " . tablename('tg_saler') . " where uniacid = :uniacid and openid = :openid and status = 1 and merchantid = '{$merchantid}'", array(":uniacid" => $uniacid, ":openid" => $check_openid));

if (!empty($hexiao_member)) { //如果是核销员
    $is_hexiao_member = TRUE;

    if ($hexiao_member['storeid'] == '') {//如果这个数组中的店铺id是空的话

        $storelist = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid = '{$uniacid}' and status = 1 ");//公众号对应的店铺
        foreach ($storelist as $key => $value) {
            $hexiao_member['storeid'] .= $value['id'] . ",";
        }

    } else {
        if (empty($store_ids)) {

        } else {
            $hexiao_ids = explode(',', substr($hexiao_member['storeid'], 0, strlen($hexiao_member['storeid']) - 1));

            foreach ($hexiao_ids as $value) {
                if (in_array($value, $store_ids)) {
                    break;
                }
            }
        }
    }
}

//门店信息
$storesids = explode(",", $hexiao_member['storeid']);
foreach ($storesids as $key => $value) {
    if ($value) {
        $st = pdo_fetch("select * from " . tablename('tg_store') . " where id = '{$value}' and merchantid = '{$merchantid}' and uniacid = '{$uniacid}'");
        if (!empty($st)) {
            $stores[$key] = $st;
        }
    }
}

if ($result == 'success') {

} else {

    $con = '';
    if ($setting['member_apply'] == 1) {
        $con .= " and member_status = 1 ";
    }
    $member = pdo_fetch("select * from " . tablename('tg_member') . " where uniacid = {$uniacid} and openid = '{$openid}' and id = {$id} {$con} ");

    if (!$member) {
        die("<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                    <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                </head>
                <body>
                <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>该用户还不是本店会员</h4></div></div></div>
                </body>
            </html>");
    }

    if ($_W['isajax']) {

        $bill = pdo_get('tg_member_billrecord' , array('orderno' => $orderno));
        if ($bill) {
            $wOpt = array('status' => 0, 'message' => '非常抱歉！此订单已成交！');
            die(json_encode($wOpt));
        }
        $params['tid'] = $orderno;
        $params['user'] = $openid;
        $params['fee'] = $price;
        $params['title'] = '线下交易';
        $params['ordersn'] = $orderno;
        $params['module'] = "lexiangpingou";
        $params['storeid'] = intval($_GPC['storeid']);
        $params['type'] = 2;

        $wOpt = array('status' => 0, 'message' => '交易失败！');

        $wOpt = balance_payment($uniacid, $params);

        if ($wOpt['status'] > 0) {
            $url = app_url('member/member');
            $storename = '';
            foreach ($stores as $store) {
                if ($store['id'] == $params['storeid']) {
                    $storename = $store['storename'];
                }
            }
            $title = '线下门店消费';
            $content = "尊敬的" . $member["name"] . "，您在线下门店【" . $storename . "】已消费 ：￥" . $price;

            $remaark = "";
            result_type($openid, $title, $content, $remark, $url);
        }
        die(json_encode($wOpt));
    }
}
include wl_template('member/member_check');
exit();