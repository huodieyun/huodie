<?php

global $_GPC, $_W;

$op = $_GPC['op'] ? $_GPC['op'] : 'display';
//if ($_W['openid'] == 'oCKOnuEOOFxkF5WpX22IEbx74JBM') {
//    $_W['openid'] = 'oCKOnuDW_PmELfZpnJi3NP6jRmIc';
//}

$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
$pagetitle = $_W['page']['title'] .' - 会员信息';
$pindex = max(1, intval($_GPC['page']));
$psize = 6;

wl_load()->model('setting');
$setting = setting_get_by_name("kaiguan");
if (empty($uniacid)) {
    $uniacid = $_GPC['uniacid'];
}
if (empty($openid)) {
    $openid = $_W['openid'] = $_GPC['openid'];
}

$member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
if ($member['member_status'] != 1) {
    header("location: " . app_url('member/member_apply'));
    exit;
}
if ($member['birthday']) {
    $member['birthday'] = date('Y-m-d', $member['birthday']);
} else {
    $member['birthday'] = '';
}

if ($op == 'display') {

    include wl_template('member/member');

}

if ($op == 'list') {

    $time = $_GPC['time'];
    $condition = '';
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND createtime >= {$starttime} AND createtime <= {$endtime} ";

    }
    $orderno = trim($_GPC['keyword']);
    if ($orderno) {
        $condition .= " AND orderno like '%{$orderno}%' ";
    }
    $list = pdo_fetchall("select * from " .tablename('tg_member_billrecord') ." where uniacid = {$member['uniacid']} and openid = '{$member['from_user']}' {$condition} ORDER BY createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $total = pdo_fetchcolumn("select count(id) from " .tablename('tg_member_billrecord') ." where uniacid = {$member['uniacid']} and openid = '{$member['from_user']}' {$condition} ");
    $pager = pagination($total, $pindex, $psize);
    foreach ($list as &$item) {
        $item['nickname'] = $member['nickname'];
        $item['name'] = $member['name'];
        $item['createtime'] = date('Y-m-d H:i:s' , $item['createtime']);
        if ($item['type'] == 1) {
            $item['type'] = '线上商城';
            $order = pdo_get('tg_order' , array('orderno' => $item['orderno']));
            if ($order['g_id'] > 0) {
                $goods = pdo_getall('tg_goods' , array('id' => $order['g_id']));
                foreach ($goods as &$good) {
                    if ($good['share_image']) {
                        $good['gimg'] = tomedia($good['share_image']);
                    } else {
                        $good['gimg'] = tomedia($good['gimg']);
                    }
                    unset($good);
                }
            } else {
                $collect = pdo_getall('tg_collect' , array('orderno' => $order['orderno']));
                $goods = array();
                foreach ($collect as $it) {
                    $good = pdo_get('tg_goods' , array('id' => $it['sid']));
                    if ($good['share_image']) {
                        $good['gimg'] = tomedia($good['share_image']);
                    } else {
                        $good['gimg'] = tomedia($good['gimg']);
                    }
                    $goods[] = $good;
                }
            }
            $item['goods'] = $goods;
        } elseif ($item['type'] == 2) {
            unset($good);
            unset($goods);
            $item['type'] = '线下交易';
            $goods = array();
            $good['gimg'] = tomedia('addons/lexiangpingou/app/resource/images/xxjy.png');
            $good['gname'] = '线下交易';
            $goods[] = $good;
            $item['goods'] = $goods;
        }
        if ($item['status'] == 1) {
            $item['status'] = '全额退款';
        } elseif ($item['status'] == 2) {
            $item['status'] = '部分退款';
        }
        unset($item);
    }
    die(json_encode(array('list' => $list , 'total' => $total)));
}

if($op =='payment_code'){

    $pagetitle = $_W['page']['title'] .' - 线下付款';
    $orderno = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    include wl_template('member/payment_code');
}
exit();

?>