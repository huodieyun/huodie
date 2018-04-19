<?php
/**
 * [weliam] Copyright (c) 2016/3/26
 * 设置mod
 */
defined('IN_IA') or exit('Access Denied');
function checkfunc($functionid=''){
	global $_W;
	$usefunction=array();
	$usefunction['status']=0;
	$usefunction['des']=0;
	$usefunction['endtime']='';
	$usefunction['price']=0;
	$func=pdo_fetch("select * from ".tablename('tg_function') ." where id={$functionid}");
	$usefunction['price']=$func['price'];
	$funclist=pdo_fetch("select * from ".tablename('tg_function_detail') ." where functionid={$functionid} and uniacid={$_W['uniacid']}");
	$usefunction['name']=$func['name'];
	$usefunction['des']=$func['des'];
	if(!empty($funclist)&&$funclist['endtime']>time())
	{		
		$usefunction['status']=1;
		$usefunction['endtime']=$funclist['endtime'];
	}
	if($func['isdingzhi']!=1)
	{
		$uni=pdo_fetch("select vip,endtime,ordernum from ".tablename('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		if(($uni['vip']>=1&&$uni['endtime']>time())||$uni['ordernum']>0)
		{		
			$usefunction['status']=1;
			$usefunction['endtime']=$uni['endtime'];
			if($uni['ordernum']>0)
			{
				$usefunction['endtime']=1606492800;
			}
		}
	}
	return $usefunction;
}
