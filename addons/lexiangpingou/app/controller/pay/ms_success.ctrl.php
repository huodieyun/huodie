<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/6/30
 * Time: 9:51
 */
global $_W, $_GPC;
$orderno = $_GPC['orderno'];
$order=pdo_fetch('select * from '.tablename('tg_merchant_order').' where orderno=:orderno and uniacid=:uniacid',array(':uniacid'=>$_W['uniacid'],':orderno'=>$orderno));
include wl_template('pay/ms_success');
exit();
