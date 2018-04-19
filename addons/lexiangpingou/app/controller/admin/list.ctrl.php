<?php
defined('IN_IA') or exit('Access Denied');
wl_load()->model('adv');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->func('print');
wl_load()->func('message');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$saler=pdo_fetch('select nickname,storeid,openid from '.tablename('tg_saler').' where uid=:uid and uniacid=:uniacid and status=1',array(':uid'=>$_GPC['uid'],':uniacid'=>$_W['uniacid']));
if(empty($saler))
{
	message('非核销员');
}

$stores=pdo_fetch('select storename from '.tablename('tg_store').' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=> substr($saler['storeid'],0,strlen($saler['storeid'])-1)));

if($op =='display'){
	$keyword = $_GPC['keyword'];	
	include wl_template('admin/index');
}
if($op=='serch'){
	$orderno=$_GPC['orderno'];
	$WriteOff=$_GPC['Auto_verification'];
	$order=pdo_fetch('select orderno,ptime,addname,mobile,address,status,g_id,id,sendtime,senddate,discount_fee,dispatchtype,goodsprice,pay_price,buyremark,pay_type,comadd,checkstore,veropenid,hexiaotime from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));	
	
	if($order['status']!=1&&$order['status']!=2&&$order['status']!=8&&$order['status']!=3)
	{
		$result=array('statustype'=>0,'message'=>'本订单无效');
		die(json_encode($result));
	}
	if(empty($order))
	{
		$result=array('statustype'=>0,'message'=>'订单不存在');
		die(json_encode($result));
	}
	$order['ptime']=date('Y-m-d H:i:s', $order['ptime']);
	$order['message']='';
	if(!empty($order['comadd']))
	{
		$store=pdo_fetch('select * from '.tablename('tg_store').' where id=:id',array(':id'=>$order['comadd']));
		$order['comadd']=$store['storename'];
	}
	$order['storename']=$stores['storename'];
	$order['nickname']=$saler['nickname'];
	$time=TIMESTAMP;
	
	$order['hexiao']=0;
	 if($WriteOff==1&&$order['status']!=3)
	 {
		 $order['message']='核销成功';
		 $storesids = explode(",", $saler['storeid']);
		  $url = app_url('order/order/detail', array('id' => $order['id']));
		hexiao_success($order['goodsname'],$order['openid'],$order['gnum'],  TIMESTAMP, $url);
		pdo_update('tg_order',array('status'=>3,'is_hexiao'=>2,'veropenid' => $saler['openid'],'hexiaotime'=>$time,'sendtime'=>$time,'gettime'=>$time,'checkstore'=>$storesids[0]),array('orderno'=>$orderno));
		tuan_print($order['id']);
		$order['hexiao']=1;
	 }
	 if($order['status']==3)
	 {
		 $order['hexiaotime']=date('Y-m-d H:i:s', $order['hexiaotime']);
		 $order['message']='该订单已核销';
		 $nowsaler=pdo_fetch('select nickname,storeid from '.tablename('tg_saler').' where openid=:openid and uniacid=:uniacid and status=1',array(':openid'=>$order['veropenid'],':uniacid'=>$_W['uniacid']));	
		$nowstores=pdo_fetch('select storename from '.tablename('tg_store').' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$order['checkstore']));
		$order['storename']=$nowstores['storename'];
		$order['nickname']=$nowsaler['nickname'];
	 }else{$order['hexiaotime']=date('Y-m-d H:i:s', $time);}
	 $order['ttime']=$order['senddate'].' '.$order['sendtime'];
	 if(empty($order['sendtime']))
	 {
		 $order['ttime']='无';
	 }
	 switch($order['dispatchtype']){
		 case 1:$order['dispatchtype']='送货上门';break;
		 case 2:$order['dispatchtype']='快递';break;
		 case 3:$order['dispatchtype']='自提';break;
	 }
	 $order['Remark']=$order['buyremark'];
	 $order['pay_type']="微信支付";
	 if(empty($order['buyremark']))
	 {
		 $order['Remark']='无';
	 }else{
		  $order['Remark']=$order['buyremark'];
	 }
	 
	switch($order['status']){
			case 0:$statusname='待付款';break;
			case 1:$statusname='已付款';break;
			case 2:$statusname='待收货';break;
			case 3:$statusname='已签收';break;
			case 4:$statusname='已退款';break;
			case 5:$statusname='强退款';break;
			case 6:$statusname='部分退款';break;
			case 7:$statusname='已退款';break;
			case 8:$statusname='待发货';break;
			case 9:$statusname='已取消';break;
			case 10:$statusname='待退款';break;
			default:$statusname='待付款';
		}
		$order['status']=$statusname;
		if($order['g_id']>0)
		{
			$params = pdo_fetchall("SELECT goodsname,goodsprice,gnum,optionname  FROM " . tablename('tg_order') .  " WHERE orderno = '{$orderno}'  ");
		}else{			
			$params = pdo_fetchall("SELECT goodsname,oprice as goodsprice,num as gnum,item as optionname FROM" . tablename('tg_collect') .  "WHERE orderno = '{$orderno}'  ");
			
		}
		$order['params'] = $params;
	 $order['statustype']=1;
	 
	die(json_encode($order));
}
if($op=='check')
{
	$orderno=$_GPC['orderno'];
	$order=pdo_fetch('select orderno,ptime,addname,mobile,address,status,g_id,id from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));	
	if($order['status']==3)
	{
		$result=array('statustype'=>0,'message'=>'本订单已核销，请勿重复核销');
		die(json_encode($result));
	}
	if($order['status']!=1&&$order['status']!=2&&$order['status']!=8)
	{
		$result=array('statustype'=>0,'message'=>'本订单无效');
		die(json_encode($result));
	}
	 tuan_print($order['id']);
	 $storesids = explode(",", $saler['storeid']);
		  $url = app_url('order/order/detail', array('id' => $order['id']));
		hexiao_success($order['goodsname'],$order['openid'],$order['gnum'],  TIMESTAMP, $url);
		pdo_update('tg_order',array('status'=>3,'is_hexiao'=>2,'veropenid' => $saler['openid'],'hexiaotime'=>TIMESTAMP,'sendtime'=>TIMESTAMP,'gettime'=>TIMESTAMP,'checkstore'=>$storesids[0]),array('orderno'=>$orderno));
		$result=array('statustype'=>1,'message'=>'核销成功');
		die(json_encode($result));
}
if($op=='print')
{
	$orderno=$_GPC['orderno'];
	$order=pdo_fetch('select orderno,ptime,addname,mobile,address,status,g_id,id,checkstore,veropenid from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));	
	$nowsaler=pdo_fetch('select nickname,storeid from '.tablename('tg_saler').' where openid=:openid and uniacid=:uniacid and status=1',array(':openid'=>$order['veropenid'],':uniacid'=>$_W['uniacid']));	
	$nowstores=pdo_fetch('select storename from '.tablename('tg_store').' where uniacid=:uniacid and id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$order['checkstore']));
	$order['storename']=$nowstores['storename'];
	$order['nickname']=$nowsaler['nickname'];
	tuan_print($order['id']);
	$result=array('statustype'=>1,'message'=>'打印成功','list'=>$order);
	die(json_encode($result));
}
if($op=='list')
{
	$mobile=$_GPC['mobile'];
	$order=pdo_fetchall("select orderno,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,addname,mobile,address,status,g_id,id from ".tablename('tg_order').' where mobile=:mobile and status in (1,2,3,8) order by ptime desc',array(':mobile'=>$mobile));	
	foreach($order as &$li) {
	switch($li['status']){
			case 0:$statusname='待付款';break;
			case 1:$statusname='已付款';break;
			case 2:$statusname='待收货';break;
			case 3:$statusname='已签收';break;
			case 4:$statusname='已退款';break;
			case 5:$statusname='强退款';break;
			case 6:$statusname='部分退款';break;
			case 7:$statusname='已退款';break;
			case 8:$statusname='待发货';break;
			case 9:$statusname='已取消';break;
			case 10:$statusname='待退款';break;
			default:$statusname='待付款';
		}
		
		$li['status']=$statusname;
	}
	if(empty($order))
	{
		$result=array('statustype'=>0,'list'=>$order);
	}else{
		$result=array('statustype'=>1,'list'=>$order);
	}
	
	die(json_encode($result));
}
  
 
  
 