<?php
require '../../../../framework/bootstrap.inc.php';
require '../../../../addons/lexiangpingou/core/common/defines.php';
require TG_CORE . 'class/wlloader.class.php';
wl_load()->func('global');
wl_load()->func('pdo');
wl_load()->func('tpl');
wl_load()->func('message');
wl_load()->func('print');

	global $_W,$_GPC;
	$op=$_GPC['op'];
	if($op=='getuniacid')
	{
		$unib='';
		//查询自动退款公众号
		$autouniacid=pdo_fetchall("select * from ".tablename('tg_setting')." where cm_tg_setting.key='refund' and uniacid<>53  ");
		foreach($autouniacid as $unis=>$v){
			
			$ss=iunserializer($v['value']);		
			
			if($ss['auto_refund']==1)
			{
				$unib.=$v['uniacid'].',';
			}
		}
		$newstr = substr($unib,0,strlen($unib)-1); 
		$newstr =explode(',',$newstr);
			
		echo wl_json($newstr);
		exit();
	}
	if($op=='run')
	{
		$_W['uniacid']=$_GPC['id'];
		$sql = "SELECT orderno,id,openid,price,uniacid,goodsname FROM " . tablename('tg_order') . " where mobile<>'虚拟' and `status`=10 and openid<>'' and transid<>''  and uniacid=".$_W['uniacid']."  order by ptime asc limit 1";
		$orders = pdo_fetchall($sql);
		$ordernos='|';
		foreach($orders as $unis=>$v){
			$_W['acid']=$v['uniacid'];
			 wl_load()->func('message');
				$r=refunds($v['orderno'], 1);
				
					if($r=='success'){
					$ordernos.=$v['orderno'].',';	
						/*退款成功消息提醒*/
						$url="http://www.lexiangpingou.cn/app/index.php?i=".$_W['uniacid']."&c=entry&m=lexiangpingou&do=order&ac=order&op=detail&id=".$v['id'];
						
						//$url = app_url('order/order/detail', array('id' => $v['id']));
						refund_success($v['orderno'],$v['goodsname'],$v['openid'], $v['price'],time(),$url);						
					}
		}
			
		if($ordernos==',')
		{
			$data=array('status'=>1);
			die(json_encode($data));
		}
		$data=array('status'=>$_W['uniacid']);
			die(json_encode($data));
				exit();
	}

	?>
	 