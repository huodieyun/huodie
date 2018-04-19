<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * order.ctrl
 * 订单控制器
 */


defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'order_detail';
wl_load()->model('order');
wl_load()->model('address');

/**
 * 次卡发货记录页面
 * @var [type]
 */
if ($op == "order_detail") {
    $list = pdo_getall('tg_order_once_card_record', array('orderid' => $_GPC['id'], 'uniacid' => $_W['uniacid']));
    include wl_template('order/order_detail_once_card');
}
/**
 * 次卡修改信息页面
 * @var [type]
 */
if ($op == "order_edit") {
    $item = pdo_get('tg_order', array('id' => $_GPC['id'], 'uniacid' => $_W['uniacid']));
    $goods = pdo_get('tg_goods', array('id' => $item['g_id']));
    if ($item['dispatchtype'] == '3') {
        $list = explode(',', $goods['hexiao_id']);
        $stores = [];
        foreach ($list as $skey => $svalue) {
            if (count($list) != $skey) {
                $st = pdo_get('tg_store', array('id' => $svalue));
                if ($st) {
                    array_push($stores, $st);
                }
            }
        }
    } else {
        $adress_fee = pdo_get('tg_address', array('openid' => $_W['openid'], 'status' => 1));
        $adresses = pdo_getall('tg_address', array('openid' => $_W['openid']));
    }
    include wl_template('order/order_once_card');
}
/**
 * 次卡修改的接口
 * @var [type]
 */
if ($op == "order_post") {
    $item = pdo_get('tg_order', array('id' => $_GPC['orderid'], 'uniacid' => $_W['uniacid']));
    // 1 送货上门 2快递 3自提
    if ($item['dispatchtype'] == 3) {
        $data = array(
            'comadd' => $_GPC['storeid'],
            'addname' => $_GPC['name'],
            'mobile' => $_GPC['mobile'],
            'once_card_json' => $_GPC['once_card_json']
        );
        pdo_update('tg_order', $data, array('id' => $_GPC['orderid']));
    } else {
        $address = pdo_get('tg_address', array('id' => $_GPC['addressid']));
        if ($_GPC['addressid'] != 0) {
            $data = array(
                'province' => $address['province'],
                'city' => $address['city'],
                'county' => $address['county'],
                'detailed_address' => $address['detailed_address'],
                'addname' => $address['cname'],
                'mobile' => $address['tel']
            );
        }
        $data['once_card_json'] = $_GPC['once_card_json'];
        pdo_update('tg_order', $data, array('id' => $_GPC['orderid']));
    }
    header("Location: " . app_url('order/order/detail', array('id' => $_GPC['orderid'])));
}
exit();
