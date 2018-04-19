<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * success.ctrl
 * 支付成功控制器
 */
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$pagetitle = '支付结果';

if($op =='display'){
	$orderno = $_GPC['orderno'];	
	$order = pdo_fetch("select * from ".tablename('tg_function_order')." where orderno='{$orderno}'");
	
	include wl_template('pay/functionsuccess');exit();
}

