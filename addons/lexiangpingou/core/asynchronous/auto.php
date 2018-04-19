<?php
require '../../../../framework/bootstrap.inc.php';
require '../../../../addons/lexiangpingou/core/common/defines.php';
require TG_CORE . 'class/wlloader.class.php';
wl_load()->func('global');
wl_load()->func('pdo');
wl_load()->func('tpl');
wl_load()->func('message');
wl_load()->func('print');
error_reporting(0);
set_time_limit(0);
global $_GPC;
if ($openid) {
    puvindex($openid);
}
$uniacid=$_W['uniacid'];
if($config['refund']['auto_refund']==1){
	check_refund();
}
///$checktuan=pdo_fetchall("select id,groupnumber from ".tablename('tg_group')." where lacknum=0 and groupstatus=3 and uniacid='{$uniacid}'");

//未支付提醒
$cancle_time = !empty($config['base']['cancle_time'])?$config['base']['cancle_time']:10;
$noprice_time = !empty($config['base']['noprice_time'])?$config['base']['noprice_time']:5;
$sql = "select  createtime,orderno from " . tablename('tg_order') . " where  uniacid = {$uniacid} and status = 0 and nopricestatus=1 order by createtime asc limit 1";
$sql2 = "select  createtime,orderno,openid,g_id,pay_price from " . tablename('tg_order') . " where  uniacid = {$uniacid} and status = 0 and (nopricestatus=0 or nopricestatus is null) order by createtime asc limit 1";

$list = pdo_fetchall($sql);

//message(count($list));
foreach($list as $key=>$value){
	$tm=time();	
	

	if( ($tm - $value['createtime']) > $cancle_time*3600  ){
		$r = pdo_query("UPDATE".tablename('tg_order')."SET status ='9' WHERE orderno= :orderno and uniacid = {$uniacid}" , array(':orderno' => $value['orderno'] ));
		
	}
}

$list2 = pdo_fetchall($sql2);

foreach($list2 as $key=>$value){
	$tm=time();	
	if( ($tm - $value['createtime']) > $noprice_time*3600  ){
		$g=pdo_fetch("select * from ".tablename('tg_goods')." where id=:id",array(':id'=>$value['g_id']));
	//$r = pdo_query("UPDATE".tablename('tg_order')."SET nopricestatus ='1' WHERE orderno= :orderno and uniacid = {$uniacid}" , array(':orderno' => $value['orderno'] ));
		pdo_update('tg_order', array('nopricestatus'=>1), array('orderno' => $value['orderno']));
	//插入未支付提醒模板消息
	$url = app_url('order/order/list',array('status'=>0));
	nopay_success($value['openid'], $value['pay_price'],$value['orderno'], $g['gname'],date("Y-m-d H:i:s", $value['createtime']) ,$url);
	
	}
}	
//未支付提醒
exit('fail');