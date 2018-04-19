<?php
defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}

$uniacid = $_W['uniacid'];
$openid = $_W['openid'];


if ($op == 'storelist') {
    $merchantid = intval($_GPC['merchantid']);
    $store = pdo_fetchall("SELECT * FROM " . tablename('tg_store') . " WHERE uniacid = {$uniacid} and status = 1 and merchantid = {$merchantid} ");
    foreach ($store as &$item) {
        $item['image'] = tomedia($item['image']);
        unset($item);
    }
    die(json_encode(array('store' => $store)));
}

// 店铺搜索
if ($op == 'stores') {
    $merchantid = intval($_GPC['merchantid']);
    $search = $_GPC['search'];
    $key = $_GPC['checked'];
    if ($key == 'shopName') {
        $merchantid = intval($_GPC['merchantid']);
        $stores = pdo_fetchall("SELECT * FROM " . tablename('tg_store') . " WHERE uniacid = {$uniacid} AND status = 1 AND merchantid = {$merchantid} AND storename LIKE '%{$search}%' ");
        die(json_encode(array('stores' => $stores)));
    } else {
        $stores = pdo_fetchall("SELECT * FROM " . tablename('tg_store') . " WHERE uniacid = {$uniacid} AND status = 1 AND merchantid = {$merchantid}  AND address LIKE '%{$search}%' ");
        die(json_encode(array('stores' => $stores)));
    }
}

include wl_template('goods/store_list');
exit();
