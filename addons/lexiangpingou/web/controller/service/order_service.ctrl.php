<?php

defined('IN_IA') or exit('Access Denied');
$_W['page']['title'] = '订单套餐';
$ops = array('check','buy','send','list','display','sendsms','import','output','ajax','clear','clearmobile');
$op=$_GPC['op'];

$op = in_array($op, $ops) ? $op : 'display';
$wechat=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=:uniacid",array(':uniacid'=>$_W['uniacid']));
$num=intval($wechat['ordernum']);
if($op=='check')
{
	$orderno=$_GPC['orderno'];
	$order = pdo_fetch("select * from ".tablename('tg_function_order')." where orderno='{$orderno}'");
		
		if($order['status']==1)
		{
			die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
		}else{
			die(json_encode(array('errno'=>1,'message'=>$orderno)));
		}
}
include wl_template('service/order_service');
exit();