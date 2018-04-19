<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 商品详情控制器
 */

defined('IN_IA') or exit('Access Denied');

wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
wl_load()->model('account');
wl_load()->model('activity');
load()->func('communication');


$pagetitle = '订单提交';
session_start();
$id =$_GPC['id'];
$num=$_GPC['num'];
//

if($num>=6&&$num<12)
{
	$discountnum=1;
}
if($num>=12&&$num<24)
{
	$discountnum=2;
}
if($num==24)
{
	$discountnum=5;
}
$openid=$_W['openid'];


$functionlist=pdo_fetch("select * from ".tablename('tg_function')." where id=".$id);
$bnum=$num-$discountnum;
$pay_price=$functionlist['price']*$bnum;




if ($_W['isajax']) {
	
	
	
	
	$data = array(
		'uniacid'     => $_W['uniacid'],
		'num'        => $num,
		'openid'      => $openid,
        'ptime'       => '',//支付成功时间
		'orderno'     => date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99)),
		'price'   => $pay_price,
		'status'=>0
	);
	pdo_insert('tg_function_order', $data);
	
	
	
	
	$params['orderno'] = $data['orderno'];
	$params['pay_price'] = $data['price'];
	wl_json(1,$params);
}

include wl_template('order/functionconfirm');exit();
