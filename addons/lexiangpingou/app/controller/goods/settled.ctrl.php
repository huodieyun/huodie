<?php

global $_W, $_GPC;

$uniacid=pdo_fetch('select * from '.tablename('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
$uniac=pdo_fetch('select * from '.tablename('tg_merchant').' where uniacid=:uniacid and openid=:openid',array(':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid']));

$uniacid['regbg']=tomedia($uniacid['regbg']);

$num = $uniacid['smsnum'];
$boom = 1;
if ($num <= 0) {
    $boom = 0;
}

include wl_template('goods/settled');

exit();
