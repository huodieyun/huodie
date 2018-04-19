<?php
defined('IN_IA') or exit('Access Denied');
$_W['page']['title'] = 'APP年度套餐';

$wechat = pdo_get('account_vip', array('uniacid' => $_W['uniacid']));
$package = pdo_get('tg_function', array('id' => 8214));

include wl_template('service/APP');
exit();
