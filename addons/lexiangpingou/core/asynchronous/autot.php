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

	global $_W;
	set_time_limit(0);
	$abs='公众号ID';
	$unib='';
	//查询自动退款公众号
	$autouniacid=pdo_fetchall("select * from ".tablename('tg_setting')." where cm_tg_setting.key='refund'   ");
	foreach($autouniacid as $unis=>$v){
		
		$ss=iunserializer($v['value']);		
		
		if($ss['auto_refund']==1)
		{
			$unib.=$v['uniacid'].',';
		}
	}
			$newstr = substr($unib,0,strlen($unib)-1); 
			$newstr =explode(',',$newstr);
			//$abs.=','.$v['uniacid'].'|'.$ss['auto_refund'];
			$nos='';
			$vs='';
			foreach($newstr as $v)			
			{
				
			$vs.=$v.'<br>';
			$sql = "SELECT orderno,id,openid,price,uniacid FROM " . tablename('tg_order') . " where mobile<>'虚拟' and status=10 and openid<>'' and transid<>''  and uniacid=".$v."  order by ptime asc limit 2";
			//$sql = "SELECT orderno,id,openid,price,uniacid FROM " . tablename('tg_order') . " where mobile<>'虚拟' and status=10 and openid<>'' and transid<>''  and uniacid in ('{$newstr}')  order by ptime asc limit 1";
			
			$orders = pdo_fetchall($sql);
			
			foreach($orders as $key=>$value){	
			$_W['uniacid']=$value['uniacid'];
			$_W['acid']=$value['uniacid'];
			 wl_load()->func('message');
				$r=refunds($value['orderno'], 1);
				$nos.=$value['orderno'].'<br>';
					if($r=='success'){
						
						$goods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id='{$value['g_id']}'");
						/*退款成功消息提醒*/
						$url="http://www.lexiangpingou.cn/app/index.php?i=".$v."&c=entry&m=lexiangpingou&do=order&ac=order&op=detail&id=".$value['id'];
						//$url = app_url('order/order/detail', array('id' => $value['id']));
						refund_success($value['orderno'],$goods['gname'],$value['openid'], $value['price'],time(),$url);						
					}
			}	
	}
	
	
	$autouniacid=pdo_fetchall("select * from ".tablename('tg_setting')." where cm_tg_setting.key='base' ");
	foreach($autouniacid as $unis=>$v){
			$ss=iunserializer($v['value']);
			$uniacid=$v['uniacid'];
						$_W['uniacid']=$v['uniacid'];
			$_W['acid']=$v['uniacid'];
			 wl_load()->func('message');

			//送货模版消息
			$sende="select  id,orderno,openid,express,expresssn,uniacid from " . tablename('tg_order') . " where  uniacid = {$uniacid} and sendepressmessage=1 order by uniacid asc limit 2";
			$sendelist = pdo_fetchall($sende);
			foreach($sendelist as $key=>$value){
				$_W['uniacid']=$value['uniacid'];
			$_W['acid']=$value['uniacid'];
			 wl_load()->func('message');
				$url = app_url('order/order/detail', array('id' => $value['id']));
				send_success($value['orderno'], $value['openid'], $value['express'], $value['expresssn'], $url);
			pdo_update('tg_order', array('sendepressmessage'=>2), array('orderno' => $value['orderno']));
									
			}
			/*
			//未支付提醒
			$cancle_time = !empty($ss['cancle_time'])?$ss['cancle_time']:10;
			$noprice_time = !empty($ss['noprice_time'])?$ss['noprice_time']:5;
			$sql = "select  createtime,orderno,uniacid from " . tablename('tg_order') . " where  uniacid = {$uniacid} and status = 0 and nopricestatus=1 order by uniacid asc limit 1";
			$sql2 = "select  createtime,orderno,openid,g_id,pay_price,uniacid from " . tablename('tg_order') . " where  uniacid = {$uniacid} and status = 0 and (nopricestatus=0 or nopricestatus is null) order by uniacid asc limit 1";

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
			$_W['uniacid']=$value['uniacid'];
			$_W['acid']=$value['uniacid'];
			 wl_load()->func('message');
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
			*/
	}
	
//抽奖团
$cjallrefund=pdo_fetchall("select orderno,openid,id,g_id,price,couponsids from ".tablename('tg_order')." where selltype=5  and status =8 and issued=1 and godluck=0 and couponsids<>'' limit 1");

foreach($cjallrefund as $key=>$value){
	$arr = explode(",",$value['couponsids']);
		foreach($arr as $u){
$coupon_template=pdo_fetch("select * from ".tablename('tg_coupon_template')." where id=".$u);			
			$coupon_data = array(
		'uniacid' => $coupon_template['uniacid'],
		'coupon_template_id' => $coupon_template['id'],
		'name' => $coupon_template['name'],
		'cash' => $coupon_template['value'],
		'is_at_least' => $coupon_template['is_at_least'],
		'at_least' => $coupon_template['at_least'],
		'description' => $coupon_template['description'],
		'start_time' => $coupon_template['start_time'],
		'end_time' => $coupon_template['end_time'],
		'use_time' => 0,
		'openid' => $value['openid'],
		'createtime' => TIMESTAMP
			);
			pdo_insert('tg_coupon', $coupon_data);
		}
	pdo_update('tg_order', array('issued'=>2,'status'=>10), array('orderno' => $value['orderno']));
	
}
//抽奖结果通知cj_message

$cjmessage=pdo_fetchall("select g_id,id,orderno,tuan_id,openid,uniacid from ".tablename('tg_order')." where cj_message=2 order by  uniacid asc limit 1");
foreach($cjmessage as $key=>$value){
	$_W['uniacid']=$value['uniacid'];
			$_W['acid']=$value['uniacid'];
			 wl_load()->func('message');
				$url = app_url('order/order/detail', array('id' => $value['id']));
				$goods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id='{$value['g_id']}'");
				choujian_success($value['orderno'],$value['tuan_id'], $value['openid'], $goods['gname'], $url,'');
			pdo_update('tg_order', array('cj_message'=>3), array('orderno' => $value['orderno']));
									
}
//echo $vs;
	echo $nos."系统当前时间戳为：";
echo "";
echo date("Y-m-d H:i:s",time());

echo ("<script type=\"text/javascript\">");
echo ("function fresh_page()");    
echo ("{");
echo ("window.location.reload();");
echo ("}"); 
echo ("setTimeout('fresh_page()',5000);");      
echo ("</script>");
	?>
	 