<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * paytype.ctrl
 * 支付方式控制器
 */

session_start();
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$pagetitle = '支付方式';
wl_load()->model("member");
wl_load()->model("goods");
wl_load()->model('setting');
wl_load()->model('activity');

	$orderno=$_GPC['orderno'];
	$order = pdo_fetch("select * from ".tablename('tg_function_order')." where orderno='{$orderno}'");
		
		if($order['status']==1)
		{
			die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
		}else{
			die(json_encode(array('errno'=>1,'message'=>$orderno)));
		}
exit();
