<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * success.ctrl
 * 支付成功控制器
 */
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$pagetitle = '支付结果';
wl_load()->func('global');
wl_load()->func('pdo');
wl_load()->func('tpl');
wl_load()->func('message');
wl_load()->func('print');
if($op =='display'){
	$orderno = $_GPC['orderno'];
    $tuan_id = intval($_GPC['tuan_id']);
	$errno = $_GPC['errno'];
	$order = order_get_by_params(" orderno = '{$orderno}' ");
	

	include wl_template('pay/success');
}
exit();
