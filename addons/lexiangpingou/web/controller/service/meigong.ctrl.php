<?php
defined('IN_IA') or exit('Access Denied');
$_W['page']['title'] = '美工包';

$wechat = pdo_get('account_vip', array('uniacid' => $_W['uniacid']));
$package = pdo_get('tg_function', array('id' => 8215));

include wl_template('service/meigong');
exit();
?>
