<?php
$orderno = $_GPC['mid'];

$result = $_GPC['result'];
$order = pdo_fetch("select * from".tablename('tg_order')."where uniacid='{$_W['uniacid']}' and orderno = '{$orderno}'");
$goods = pdo_fetch("select * from".tablename('tg_goods')."where id = '{$order['g_id']}' and uniacid='{$_W['uniacid']}'");
if($order['selltype']==7){
	$master_order=pdo_fetch('select * from '.tablename('tg_order').' where uniacid=:uniacid and master_orderno=:orderno and status=3',array(':uniacid'=>$_W['uniacid'],':orderno'=>$orderno));
	
	}
$is_hexiao_member = FALSE;
$store_ids = explode(',',substr($goods['hexiao_id'],0,strlen($goods['hexiao_id'])-1)); 
if($order['g_id']==0)
{
	$store_ids = explode(',',substr($config['base']['hexiao_id'],0,strlen($config['base']['hexiao_id'])-1)); 
	//$storesids = explode(",", $config['base']['hexiao_id']);
}
//message($goods['hexiao_id']);
 //*判断是否是核销人员*/
$hexiao_member = pdo_fetch("select *from".tablename('tg_saler')."where openid='{$_W['openid']}' and  uniacid='{$_W['uniacid']}' and status=1 ");

wl_load()->func('message');
if($hexiao_member){
	if($hexiao_member['storeid']==''){
		$is_hexiao_member = TRUE;
		$storelist=pdo_fetchall("select * from".tablename('tg_store')."where  uniacid='{$_W['uniacid']}' and status=1 ");
		foreach($storelist as$key=>$value){
			$hexiao_member['storeid'].=$value['id'].",";
		}
	}else{
		if(empty($store_ids)){
			$is_hexiao_member = TRUE;
		}else{
			
			$hexiao_ids = explode(',', substr($hexiao_member['storeid'],0,strlen($hexiao_member['storeid'])-1)); 
			foreach($hexiao_ids as $key=> $value){
				if(in_array($value,$store_ids)){
					$is_hexiao_member = TRUE;
					break;
				}
			}
		}
		
	}
	
}

//门店信息
$storesids = explode(",", $hexiao_member['storeid']);
foreach($storesids as$key=>$value){
	if($value){
		$stores[$key] =  pdo_fetch("select * from".tablename('tg_store')."where id ='{$value}' and uniacid='{$_W['uniacid']}'");
	}
}

$store=pdo_fetch("select * from".tablename('tg_store')."where  id='{$order['comadd']}'");
if($_W['isajax']){

	 $url = app_url('order/order/detail', array('id' => $order['id']));
	hexiao_success($goods['gname'],$order['openid'],$order['gnum'],  TIMESTAMP, $url);
	$checkorder=pdo_fetch("select checkstore,status from".tablename('tg_order')."where  orderno='{$orderno}' ");
	if($checkorder['status']==2)
	{
		wl_json(0,'该订单已发货，请勿重复发货！');
	}
	if(!empty($checkorder['checkstore']))
	{
		wl_json(0,'该订单已核销完毕，请勿重复核销！');
	}


	if(pdo_update('tg_order',array('status'=>3,'is_hexiao'=>2,'veropenid' => $_W['openid'],'hexiaotime'=>TIMESTAMP,'sendtime'=>TIMESTAMP,'gettime'=>TIMESTAMP,'checkstore'=>$_GPC['id']),array('orderno'=>$orderno))){
		//佣金
		$value = pdo_fetch("select * from".tablename('tg_order')."where  uniacid='{$_W['uniacid']}' and orderno='{$orderno}'");
		if(intval($value['comtype'])==0)
		{
					/**********************/
					//积分
			if($value['g_id']>0){
						$goodsInfo = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid ='{$_W['uniacid']}' and id = '{$value['g_id']}'");
						$fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$_W['uniacid']}' and openid = '{$value['openid']}'");
						$mencredit=pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='{$_W['uniacid']}' and uid = '{$fans['uid']}'");
						$creditnum=$mencredit['credit1']+$goodsInfo['credit'];
						pdo_update('mc_members',array('credit1'=>$creditnum),array('uid'=>$fans['uid']));
						//佣金
					$ud = pdo_fetch("select parentid from".tablename('tg_member')."where  uniacid='{$_W['uniacid']}' and from_user='{$value['openid']}'");
					if(intval($ud['parentid'])>0)
					{
					$parent=pdo_fetch("select * from".tablename('tg_member')."where  uniacid='{$_W['uniacid']}' and id={$ud['parentid']}");
					if($value['commissiontype']==2)
					{
						$price1=$value['commission'];
					}
					else{
					$price1=($value['price']-$value['cost_fee'])*$value['commission']/100;//佣金计算			
						}
					$billing=$parent['billing']+$price1;//已结算佣金
					$wallet=$parent['wallet']+$price1;//钱包总佣金
					//$nobilling=$parent['nobilling']-$price;//未结算佣金
					pdo_update('tg_member',array('billing'=>$billing,'wallet'=>$wallet),array('id'=>$parent['id']));
					//佣金结算记录
					$bdata = array(
					'uniacid' => $_W['uniacid'],
					'openid' => $parent['from_user'],
					'orderno' => $value['orderno'],
					'billdate' => TIMESTAMP,
					'price' => $price1
					);
					pdo_insert('tg_billrecord', $bdata);
					
					}
//	
			}
			if($value['g_id']==0)
			{
				
				$favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='{$_W['uniacid']}'   and orderno='{$value['orderno']}'");
				$fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$_W['uniacid']}' and openid = '{$_W['openid']}'");
				$mencredit=pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='{$_W['uniacid']}' and uid = '{$fans['uid']}'");
				$creditnum=$mencredit['credit1']+$goodsInfo['credit'];
				$price1=0;
				foreach ($favoriteqqq as $key => $orderss) 
					{				
						$gs= pdo_fetch("select * from ".tablename('tg_goods')."where uniacid='{$_W['uniacid']}' and id={$orderss['sid']} ");
						if(!empty($gs['credit'])&&$gs['credit']!=0)
						{
							$creditnum+=$gs['credit']*$orderss['num'];		
						}
					if($gs['commissiontype']==2){$price1+=$gs['commission'];}
					     else{
							 $price1+=($orderss['oprice']*$orderss['num'])*$orderss['commission']/100;//佣金计算			
						 }					
					}
					pdo_update('mc_members',array('credit1'=>$creditnum),array('uid'=>$fans['uid']));
				//积分
				//佣金
					$ud = pdo_fetch("select parentid from".tablename('tg_member')."where  uniacid='{$_W['uniacid']}' and from_user='{$value['openid']}'");
					if(intval($ud['parentid'])>0)
					{
					$parent=pdo_fetch("select * from".tablename('tg_member')."where  uniacid='{$_W['uniacid']}' and id={$ud['parentid']}");
					
					$billing=$parent['billing']+$price1;//已结算佣金
					$wallet=$parent['wallet']+$price1;//钱包总佣金
					//$nobilling=$parent['nobilling']-$price;//未结算佣金
					pdo_update('tg_member',array('billing'=>$billing,'wallet'=>$wallet),array('id'=>$parent['id']));
					//佣金结算记录
					$bdata = array(
					'uniacid' => $_W['uniacid'],
					'openid' => $parent['from_user'],
					'orderno' => $value['orderno'],
					'billdate' => TIMESTAMP,
					'price' => $price1
					);
					pdo_insert('tg_billrecord', $bdata);
					
					}
//	
			}
			pdo_update('tg_order',array('comtype'=>1),array('id'=>$value['id']));
		}
		wl_json(1);
	}else{
		wl_json(0,'核销失败，请重试！');
	}
}
include wl_template('order/check');exit();