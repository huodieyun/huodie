<?php

defined('IN_IA') or exit('Access Denied');
$_W['page']['title'] = '年度VIP';
$ops = array('buy','send','list','display','sendsms','import','output','ajax','clear','clearmobile');
$op=$_GPC['op'];
$op = in_array($op, $ops) ? $op : 'display';
$wechat=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=:uniacid",array(':uniacid'=>$_W['uniacid']));
if($op=='buy')
{
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	$condition = "  buyuniacid = {$_W['uniacid']}";
	$status=1;
	$time = $_GPC['time'];
	$mobile=$_GPC['mobile'];
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}

	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end'])+86400 ;
		$condition .= " AND  ptime >= {$starttime} AND  ptime <= {$endtime} ";
	}
	if(!empty($status))
	{
		$condition.=" and status={$status}";
	}
	if(!empty($mobile))
	{
		$condition .= " AND  mobile LIKE '%{$_GPC['mobile']}%'";
	}
	$condition.=" and functionid>=8178 and functionid<=8182";
	$sql = "select  * from " . tablename('tg_function_order') . " where $condition  ORDER BY ptime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql);
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_function_order') . " WHERE $condition ");
	$pager = pagination($total, $pindex, $psize);
}
include wl_template('service/vip_service');exit();
