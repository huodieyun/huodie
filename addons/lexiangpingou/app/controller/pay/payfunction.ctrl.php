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

load()->func('communication');

wl_load()->model("member");
wl_load()->model("goods");
wl_load()->model('setting');
wl_load()->model('activity');
$id =$_GPC['id'];
$num=$_GPC['num'];
$type=$_GPC['type'];
if($_W['uniacid']!=33)
{
	message("非法访问");
}
$discountnum=0;
//$num=1;
//$id=8146;
if($type!=3)
{
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
}
$openid=$_W['openid'];
$buyuniacid=$_GPC['buyuniacid'];
//$buyuniacid=124;
$functionlist=pdo_fetch("select * from ".tablename('tg_function')." where id=".$id);
if($functionlist['type']==2)
{
    $op='error';
}
$bnum=$num-$discountnum;
$pay_price=$functionlist['price']*$bnum;
$orderno=$_GPC['orderno'];
//$orderno="buy".date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
if($op =='error') {
    include wl_template('pay/payfunction');exit();
}
if($op =='display'){
	$order = pdo_fetch("select * from ".tablename('tg_function_order')." where orderno='{$orderno}'");
	//判断该用户是否有推荐码 是否有折扣
	$user_unacid=pdo_fetch("select * from ".tablename('uni_account_users').' where uniacid=:uniacid and role=:role',array(':uniacid'=>$buyuniacid,':role'=>'owner'));
	//查找UID
	$user=pdo_fetch("select * from ".tablename('users').' where uid=:uid',array(':uid'=>$user_unacid['uid']));
	$referral=pdo_fetch("select * from ".tablename('account_wechats').' where referral=:referral',array(':referral'=>$user['referral']));
	$discount=0;
	if(!empty($referral)&&$user['referral_status']==0&&$referral['agent']==1)
	{
		$pay_price=$pay_price*0.95;
		$discount=1;
	}
	//end 
	
	if(empty($order))
	{
		$data = array(
		'uniacid'     => $_W['uniacid'],
		'num'        => $num,
		'openid'      => $openid,
		'buyuniacid'      => $buyuniacid,
		'functionid'      => $id,
		'discountnum'      => $discountnum,
        'ptime'       => '',//支付成功时间
		'orderno'     => $orderno,
		'price'   => $pay_price,
		'discount'   => $discount,
		'status'=>0
		);
		pdo_insert('tg_function_order', $data);
	}
	
	include wl_template('pay/payfunction');exit();
}

if ($op =='ajax') {
	$checkpay = $_GPC['checkpay'];
	$orderno=$_GPC['orderno'];
	$order = pdo_fetch("select * from ".tablename('tg_function_order')." where orderno='{$orderno}'");	
	if($order['status']==1)
	{
		die(json_encode(array('errno'=>1,'message'=>"该订单已成功支付，不需要重复支付")));
	}
	die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
	
}

