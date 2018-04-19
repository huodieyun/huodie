<?php

defined('IN_IA') or exit('Access Denied');

wl_load()->model('setting');
//权限控制
$tid = 8152;
//权限控制
wl_load()->model('functions');
$merchant = pdo_fetch("select * from " .tablename('account_wechats') ." where uniacid = '{$_W['uniacid']}'");
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'list';
if ($op == 'ajax') {
//    if (empty($merchant)) {
//        $value = array('helpbuy' => 1);
//        $data = array(
//            'uniacid' => $_W['uniacid'],
//            'key' => 'helpbuy',
//            'value' => serialize($value)
//        );
//        setting_insert($data);
//        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
//    } else {
//        $status = $setting['helpbuy'] == 1 ? 2 : 1;
//        setting_update_by_params(array('value' => serialize(array('helpbuy' => $status))), array('key' => 'helpbuy', 'uniacid' => $_W['uniacid']));
//        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
//    }
}
if ($op == 'list') {
    $_W['page']['title'] = '多商户购买';
//    $list = pdo_fetchall("select * from" . tablename('tg_helpbuy') . "where uniacid={$_W['uniacid']}");

    include wl_template('store/shopbuy');
}
exit();