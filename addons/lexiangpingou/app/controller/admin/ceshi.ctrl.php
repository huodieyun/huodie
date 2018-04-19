<?php
defined('IN_IA') or exit('Access Denied');




$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

if($op =='display'){
	$keyword = $_GPC['keyword'];	
	include wl_template('admin/ceshi');
}
if($op=='serch'){
	$orderno=$_GPC['oid'];
	$order=pdo_fetch('select orderno,ptime,addname,mobile,address,status,g_id,id,sendtime,senddate,discount_fee,dispatchtype,goodsprice,pay_price from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));	
	if(empty($order))
	{
		$result=array('statustype'=>0,'message'=>'订单不存在');
		die(json_encode($result));
	}
	if($order['status']==3)
	{
		$result=array('statustype'=>0,'message'=>'本订单已核销，请勿重复核销');
		die(json_encode($result));
	}
	
	if($order['status']!=1&&$order['status']!=2&&$order['status']=8)
	{
		$result=array('statustype'=>0,'message'=>'本订单无效');
		die(json_encode($result));
	}
	
	$order['ttime']=$order['senddate'].' '.$order['sendtime'];
	$order['ptime']=date('Y-m-d H:i:s', $order['ptime']);
	$order['message']='';
	switch($order['dispatchtype']){
		 case 1:$order['dispatchtype']='送货上门';break;
		 case 2:$order['dispatchtype']='快递';break;
		 case 3:$order['dispatchtype']='自提';break;
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

if($op=='mob'){
	$mobile=$_GPC['mobile'];
	$mobilelist = pdo_fetchall("select orderno,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,addname,mobile,address,status,g_id,id from" . tablename('tg_order') . "where mobile=:mobile and status in (1,2,3,8)", array(':mobile'=>$mobile));
	if(empty($mobilelist))
	{
		$result=array('statustype'=>0,'list'=>$mobilelist);
	}else{
		$result=array('statustype'=>1,'list'=>$mobilelist);
	}
	die(json_encode($result));
}
 
  
 