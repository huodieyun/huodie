<?php
global $_GPC;
if($op == 'wholesaler_apply'){
    pdo_update('tg_member',
        array(
            'name' => $_GPC['name'],
            'mobile' => $_GPC['mobile'],
            'wholesaler_apply'=>1,
            'wholesaler_apply_time'=>TIMESTAMP
        ), array('id' => $_GPC['id']));
    $member = pdo_fetch("select uid from " . tablename('tg_member') . " where id = '{$_GPC['id']}'");
    pdo_update('mc_members', array('mobile' => $_GPC['mobile']), array('uid' => $member['uid']));
    die(json_encode(array('status' => 1)));
}