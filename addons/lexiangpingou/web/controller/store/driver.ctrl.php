<?php
/**
 * [weliam] Copyright (c) 2016/3/26
 * 商城系统设置控制器
 */
defined('IN_IA') or exit('Access Denied');
if(!pdo_fieldexists('tg_merchant', 'deliverytype')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `deliverytype` int(11) not null default 0 COMMENT '商城配送方式';");
}
$op = $_GPC['op'];
$op = !empty($op) ? $op : 'copyright';

if ($op == 'copyright'){

    if (checksubmit('submit')){
        $deliverytype = intval($_GPC['deliverytype']);
        $ree = pdo_update('tg_merchant' , array('deliverytype' => $deliverytype) , array('uniacid' => $_W['uniacid'] , 'id' => $_W['user']['merchant_id']));
        echo "<script>window.localtion.href = window.localtion.href;</script>";
    }
    $merchant = pdo_fetch("select * from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$_W['user']['merchant_id']}' ");
}
include wl_template('store/driver');
exit();