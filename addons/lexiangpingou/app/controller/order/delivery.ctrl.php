<?php

global $_GPC, $_W;

$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
wl_load()->model('setting');
$setting = setting_get_by_name("hexiao");
if (empty($uniacid)) {
    $uniacid = $_GPC['uniacid'];
}
if (empty($openid)) {
    $openid = $_W['openid'] = $_GPC['openid'];
}

if (!$openid) {
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

$member = pdo_fetch("SELECT * FROM " . tablename('tg_delivery_man') . " WHERE openid = '{$openid}' and uniacid = {$uniacid} and status = 1 and merchantid = 0 ");
$record = pdo_fetchall("select * from " . tablename('tg_hexiao_record') . " WHERE openid = '{$openid}' and uniacid = {$uniacid} and status = 0 and type = 2 ");
if ($record) {
    $apply = 0.00;
    foreach ($record as $item) {
        $apply += floatval($item['price']);
    }
}
$qbyj = 0;
$ktx = 0;
$hexiao = pdo_getall('tg_hexiao', array('uniacid' => $uniacid, 'did' => $member['id'], 'status' => 1));
foreach ($hexiao as $item) {
    $qbyj += floatval($item['price']);
    if ($item['createtime'] < TIMESTAMP - $setting['delivery_time'] * 24 * 60 * 60) {
        $ktx += floatval($item['price']);
    }
}
$ktx = floatval($ktx) - floatval($apply) - floatval($member['withdraw']);

unset($hexiao);
unset($record);

$_Session['btitle'] = $member['nickname'];

$op = $_GPC['op'] ? $_GPC['op'] : 'display';

if ($op == 'display') {

    $com = '';
    $start = strtotime($_GPC['start']);
    $end = strtotime($_GPC['end']);
    if ($start) {
        $com .= " and createtime >= {$start} and createtime <= {$end} ";
    }
    $orders = pdo_fetchall("select * from " . tablename('tg_hexiao') . " where uniacid = {$uniacid} and did = {$member['id']} {$com} order by id desc ");
    foreach ($orders as &$v) {

        $order = pdo_get('tg_order', array('id' => $v['oid']));
        $v['orderno'] = $order['orderno'];
        $mem = pdo_get('tg_member', array('uniacid' => $uniacid, 'from_user' => $order['openid']));
        $v['addname'] = $order['addname'];
        $v['mobile'] = $order['mobile'];
        $v['gnum'] = $order['gnum'];
        $v['goodsprice'] = floatval($order['price']);
        $v['gname'] = $order['goodsname'];
        if ($v['cid'] > 0) {
            $collect = pdo_get('tg_collect', array('id' => $v['cid']));
            $v['gnum'] = $collect['num'];
            $v['oprice'] = $collect['oprice'];
            $v['gname'] = $collect['goodsname'];
            $v['goodsprice'] = floatval($collect['oprice'] * $collect['num']);
            unset($collect);
        }

        unset($v);
        unset($mem);
        unset($order);
    }

}

if ($op == 'my_cash') {
    if (checksubmit('submit')) {
        $name = $_GPC['name'];
        $name = floatval($name);
        if (empty($name)) {
            message('请填写金额', referer(), 'error');
        }

        if (floatval($ktx) < $name) {
            message('提现金额不能大于可提现金额', referer(), 'error');
        }
        if ($name < 1) {
            message('提现金额不能小于1元', referer(), 'error');
        }
        $bdata = array(
            'uniacid' => $uniacid,
            'openid' => $openid,
            'status' => 0,
            'createtime' => TIMESTAMP,
            'type' => 2,
            'price' => $name
        );
        pdo_insert('tg_hexiao_record', $bdata);
        message('申请成功', app_url('order/delivery', array('op' => 'withdraw', 'status' => 0)), 'success');
    }
}

if ($op == 'withdraw') {
    $id = $_GPC['id'];
    $status = $_GPC['status'];
    $con = " where uniacid = {$uniacid} and openid = '{$openid}' and type = 2 ";
    if (!empty($id)) {
        $con .= " and id = " . $id;
    }
    if ($status == 0) {
        $con .= " and status = 0 ";
    } elseif ($status == 1) {
        $con .= " and status = 1 ";
    } elseif ($status == -1) {
        $con .= " and status = -1 ";
    }
    $list = pdo_fetchall("select * from " . tablename('tg_hexiao_record') . $con);
//die(json_encode($list));
}

include wl_template('order/delivery');

exit();

?>