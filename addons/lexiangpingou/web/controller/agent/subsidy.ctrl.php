<?php
defined('IN_IA') or exit('Access Denied');

if($op=='defined_subsidy'){
	$orderno = $_GPC['orderno'];
	$subsidy = $_GPC['subsidy'];

	$updete_subsidy = pdo_update("tg_distributor_cash",array('subsidy_cash'=>$subsidy),array('orderno'=>$orderno));
	die(json_encode($updete_subsidy));
}


$pindex = max(1, intval($_GPC['page']));
$psize = 10;
$log2 = pdo_fetchall("SELECT id FROM ".tablename('tg_distributor_cash')."   order by ptime desc " ,array());
$total = count($log2);
$pager = pagination($total, $pindex, $psize);

$log = pdo_fetchall("SELECT id,parent_uniacid,orderno,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,discount,refund_cash,subsidy_cash,status FROM ".tablename('tg_distributor_cash')."   order by ptime desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize,array());
foreach($log as &$vs)
{
	$gzname = pdo_fetch("SELECT name FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid  ", array(':uniacid' => $vs['parent_uniacid']));
	$vs['gzname'] = $gzname['name'];
	
	$orderno = pdo_fetch("SELECT functionid FROM ".tablename('tg_function_order')." WHERE orderno = :orderno  ", array(':orderno' => $vs['orderno']));
	$ordername = pdo_fetch("SELECT name,type FROM ".tablename('tg_function')." WHERE id = :functionid  ", array(':functionid' => $orderno['functionid']));

	$vs['discount'] = $vs['discount']*100;
	if($ordername['type']=="3"){
		$vs['name'] = "短信购买";
	}else if($ordername['type']=="4"){
		$vs['name'] = "订单购买";
	}else{
		$vs['name'] = $ordername['name'];
	}
	
}
	 
include wl_template('agent/subsidy');
exit();
	
