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
	pdo_update('tg_function_order', array('status'=>1,'ptime'=>time()),array('orderno'=>$orderno));
	$order = pdo_fetch("select * from ".tablename('tg_function_order')." where orderno='{$orderno}'");
	$vip = pdo_get('account_vip', array('uniacid' => $_W['uniacid']));
	switch ($order['functionid']) {
		case '8214':// APP年度套餐
			$res = pdo_update('account_vip', array('app' => 1, 'app_time' => getEndtime($vip['app_time'], $order['num'])), array('uniacid' => $_W['uniacid']));
			die(json_encode(array('errno'=>!$res,'message'=>"支付成功.")));
			break;
		case '8215':// 美工年度套餐
			$res = pdo_update('account_vip', array('art' => 1, 'art_time' => getEndtime($vip['art_time'], $order['num'])), array('uniacid' => $_W['uniacid']));
			die(json_encode(array('errno'=>!$res,'message'=>"支付成功.")));
			break;
		case '8216':// 小程序年度
			$res = pdo_update('account_vip', array('applet' => 1, 'applet_time' => getEndtime($vip['applet_time'], $order['num'])), array('uniacid' => $_W['uniacid']));
			die(json_encode(array('errno'=>!$res,'message'=>"支付成功.")));
			break;

	default: // VIP服务号
		$funct=pdo_fetch("select * from ".tablename('tg_function')." where id=".$order['functionid']);
		$func_order=pdo_fetch("select * from ".tablename('tg_function_order')." where orderno='{$orderno}'");
		$func_detail=pdo_fetch("select * from ".tablename('tg_function_detail')." where uniacid=:uniacid and functionid=:functionid",array(':uniacid'=>$func_order['buyuniacid'],':functionid'=>$func_order['functionid']));
		//die(json_encode(array('errno'=>0,'message'=>$func_order['orderno'])));
		//判断该用户是否有推荐码 是否有折扣
		$user_unacid=pdo_fetch("select * from ".tablename('uni_account_users').' where uniacid=:uniacid and role=:role',array(':uniacid'=>$func_order['buyuniacid'],':role'=>'owner'));
		//查找UID
		$user=pdo_fetch("select * from ".tablename('users').' where uid=:uid',array(':uid'=>$user_unacid['uid']));
		$referral=pdo_fetch("select * from ".tablename('account_wechats').' where referral=:referral',array(':referral'=>$user['referral']));
		//上级分润
		if(!empty($referral)&&$referral['agent']==1)
		{
			
				$data = array(
				'uniacid'     => $func_order['buyuniacid'],
				'parent_uniacid'        => $referral['uniacid'],				
				'ptime'       => $func_order['ptime'],//支付成功时间
				'orderno'     => $func_order['orderno'],
				'cash'   => $func_order['price'],
				'refund_time'   => $func_order['ptime'] + 15*24*60*60,
				'refund_cash'   => $func_order['price']*$referral['agent_discount'],
				'discount'   => $referral['agent_discount'],
				'status'=>0
				);
				pdo_insert('tg_distributor_cash', $data);
			//更新销售额
			$newtotal=$referral['totalsell']+$func_order['price'];
			$level = pdo_fetch("SELECT * FROM ".tablename('tg_agentrule')." WHERE  minprice <= :price and maxprice >= :price ", array(':price' => $newtotal));
			pdo_update('account_wechats', array('totalsell'=>$newtotal,'agent_level_id'=>$level['id'],'agent_discount'=>$level['commission']/100), array('uniacid' =>$referral['uniacid']));
			pdo_update('users', array('referral_status'=>1), array('uid' =>$user['uid']));
			
		}
		
		//end 
		
		if($funct['type']==3)
		{
			$wechats=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=".$order['buyuniacid']);
			$nowsmsnum=$wechats['smsnum']+intval($funct['name']);
			pdo_update('account_wechats', array('smsnum'=>$nowsmsnum), array('uniacid' =>$order['buyuniacid']));
			die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
			exit();
		}elseif($funct['type']==5){
			$wechats=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=".$order['buyuniacid']);
			$nowtime=time();
			if($nowtime>$wechats['endtime'])
			{
						$endtime = strtotime(date('Y-m-d', strtotime('+'.$func_order['num']." year")));
			}else{
						$data1=date('Y-m-d',$wechats['endtime']);
						$endtime = strtotime(date('Y-m-d',strtotime($data1.'+'.$func_order['num']."year")));
			}
			pdo_update('account_wechats', array('vip'=>1,'endtime'=>$endtime), array('uniacid' =>$order['buyuniacid']));
			die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
			exit();
		}
		elseif($funct['type']==4){
			$wechats=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=".$order['buyuniacid']);
			$nowsmsnum=$wechats['ordernum']+intval($funct['name']);
			pdo_update('account_wechats', array('ordernum'=>$nowsmsnum), array('uniacid' =>$order['buyuniacid']));
			die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
			exit();
		}
		else{
				if(empty($func_detail))
				{
					$endtime = strtotime(date('Y-m-d', strtotime('+'.$func_order['num']." month")));
					//更改公众号功能
					$data = array(
						'uniacid'     => $func_order['buyuniacid'],				
						'functionid'      => $func_order['functionid'],				
						'endtime'       =>$endtime
					);
					pdo_insert('tg_function_detail', $data);
					die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
				}else{
					$nowtime=time();
					if($nowtime>$func_detail['endtime'])
					{
						$endtime = strtotime(date('Y-m-d', strtotime('+'.$func_order['num']." month")));
					}else{
						$data1=date('Y-m-d',$func_detail['endtime']);
						$endtime = strtotime(date('Y-m-d',strtotime($data1.'+'.$func_order['num']."month")));
					}
					pdo_update('tg_function_detail', array('endtime'=>$endtime), array('id' =>$func_detail['id']));
					die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
				}
				}
			break;
	}
/**
 * 获取到期时间
 * @param  int $time 原截止时间
 * @param  int $num  购买数量
 * @return int       现截止时间
 */
function getEndtime($time, $num)
{
	$nowtime = time();
	if($nowtime > $time) {
		$endtime = strtotime(date('Y-m-d', strtotime('+'.$num." year")));
	} else {
		$data1 = date('Y-m-d', $time);
		$endtime = strtotime(date('Y-m-d', strtotime($data1.'+'.$num."year")));
	}
	return $endtime;
}
