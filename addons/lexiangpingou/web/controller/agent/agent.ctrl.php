<?php
defined('IN_IA') or exit('Access Denied');
$power=pdo_fetch("select agent from ". tablename('account_wechats')." where uniacid = '{$_W['uniacid']}'  ");
if($op=='log'){
	echo "<script>location:reload();</script>";	
	$rel = pdo_fetch("SELECT Referral FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
	
	$log = pdo_fetchall("SELECT subsidy_cash,refund_time,id,uniacid,parent_uniacid,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,FROM_UNIXTIME(refund_time,'%Y-%m-%d %H:%i') as srtime,refund_cash,status FROM ".tablename('tg_distributor_cash')." WHERE parent_uniacid = :uniacid order by ptime desc ", array(':uniacid' => $_W['uniacid']));
		foreach($log as &$vs)
		{
			$sql3 = pdo_fetch("SELECT name FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid  ", array(':uniacid' => $vs['uniacid']));
        	$vs['name'] = $sql3['name']; 
        	$sum['sum'] += $vs['cash'];
        	$sum['sum_r'] += $vs['refund_cash'] + $vs['subsidy_cash'];
		}
		$agent = pdo_fetch("SELECT yt,openid FROM ".tablename('tg_agent')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
		
		$message = pdo_fetch("SELECT level,name,maxprice,minprice,commission,img FROM ".tablename('tg_agentrule')." WHERE  minprice <= :price and maxprice >= :price ", array(':price' => $sum['sum']));
		switch ($message['minprice'])
		{
		case 0:
		  $message['bl'] = $sum['sum']/$message['maxprice']*100;
		  break;
		case 30000:
		  $message['bl'] = ($sum['sum']-30000)/($message['maxprice']-30000)*100;
		  break;
		case 60000:
		  $message['bl'] = ($sum['sum']-60000)/($message['maxprice']-60000)*100;
		  break;
		case 120000:
		  $message['bl'] = ($sum['sum']-120000)/($message['maxprice']-120000)*100;
		  break;
		case 240000:
		  $message['bl'] = ($sum['sum']-240000)/($message['maxprice']-240000)*100;
		  break;
		case 480000:
		  $message['bl'] = 100;
		  break;
		}
		if($message['bl'] >= 99){
		  $message['bl'] = 99;
		}
	$message['Referral'] = $rel['Referral'];
	$message['sum'] = $sum['sum'];

	$message['yt'] = $agent['yt'];
	$message['wt'] = $sum['sum_r']-$message['yt'];
	$message['sum_r'] = $sum['sum_r'];
	
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_present_record') . " where openid = :openid  ", array(':openid' => $agent['openid']));
	$pager = pagination($total, $pindex, $psize);
	
	
	$log = pdo_fetchall("SELECT nickname,id,pstatus,openid,status,money,FROM_UNIXTIME(stime,'%Y-%m-%d %H:%i') as stime,FROM_UNIXTIME(ftime,'%Y-%m-%d %H:%i') as ftime FROM ".tablename('tg_present_record')." WHERE openid = :openid order by stime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array(':openid' => $agent['openid']));
	include wl_template('agent/agent-log');
	exit();
}elseif($op=='selectsaler'){
		$con     = "uniacid='33' ";
		$keyword = $_GPC['keyword'];
		if ($keyword != '') {
			$con .= " and nickname LIKE '%{$keyword}%'";
		}
		$ds = pdo_fetchall("select * from" . tablename('tg_member') . "where $con");
		include wl_template('agent/query_saler');
	}
else{
	if($op=='gh'){
		$keyword = $_GPC['keyword'];
		$con = "  id = '{$keyword}'";
		
		$default = pdo_fetch("SELECT name,blank,cardno,openid FROM ".tablename('tg_agent')." WHERE " . $con . " LIMIT 1", array());
		include wl_template('agent/ghblank');
		exit();
	}
	if($op=='delete'){
		$cardno = $_GPC['cardno'];
		$openid = $_GPC['openid'];
		$result = pdo_delete('tg_agent', array('cardno' => $cardno,'uniacid'=> $_W['uniacid'], 'type'=>'2'));
		
		$default = pdo_fetch("SELECT name,blank,cardno FROM ".tablename('tg_agent')." WHERE openid = :openid and type = :type LIMIT 1", array(':openid' => $_GPC['openid'],':type' => '2'));
		
		pdo_update("tg_agent",array('name'=>$default['name'] ,'blank'=>$default['blank'],'cardno'=>$default['cardno']),array('uniacid'=>$_W['uniacid'],'type'=>'1'));
		
		$blank = pdo_fetchall("SELECT name,blank,cardno,id FROM ".tablename('tg_agent')." WHERE type = :type and openid = :openid", array(':openid' => $_GPC['openid'],':type' => '2'));
		
		$ops= 'xkh';
		echo "<script>location.href='".web_url('agent/agent',array('ops' =>'xkh'))."';</script>";
		exit();
	}
	if($op=='xk'){
		pdo_update("tg_agent",array('name'=>$_GPC['name'] ,'blank'=>$_GPC['blank'],'cardno'=>$_GPC['cardno']),array('uniacid'=>$_W['uniacid'],'type'=>'1'));
		
		$def = pdo_fetch("SELECT nickname,openid FROM ".tablename('tg_agent')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
		
		$data['uniacid'] = $_W['uniacid'];
		$data['openid'] = $def['openid'] ;
		$data['nickname'] = $def['nickname'] ;
		$data['name'] = $_GPC['name'] ;
		$data['blank'] = $_GPC['blank'];
		$data['cardno'] = $_GPC['cardno'];
		$data['type'] = 2;
		$storage = pdo_insert('tg_agent', $data);
		
		$default = pdo_fetch("SELECT name,blank,cardno,openid FROM ".tablename('tg_agent')." WHERE uniacid = :uniacid and type = :type LIMIT 1", array(':uniacid' => $_W['uniacid'],'type'=>'1'));
		$blank = pdo_fetchall("SELECT name,blank,cardno,id FROM ".tablename('tg_agent')." WHERE type = :type and openid = :openid", array(':openid' => $default['openid'],':type' => '2'));
		

		
		echo "<script>location.href='".web_url('agent/agent',array('ops' =>'xkh'))."';</script>";
		exit();
	
	}
	if($op=='tx'){

		
		$default = pdo_fetch("SELECT nickname,openid,yt,price FROM ".tablename('tg_agent')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
		
		$data['openid'] = $default['openid'] ;
		$data['nickname'] = $default['nickname'] ;
		$data['pstatus'] = $_GPC['pstatus'] ;
		$data['money'] = $_GPC['money'];
		$data['name'] = $_GPC['name'] ;
		$data['blank'] = $_GPC['blank'];
		$data['cardno'] = $_GPC['cardno'];
		$data['status'] = '0';
		$data['stime'] = TIMESTAMP;
		$data['record_id'] = $_GPC['ssid'];

		$update['yt'] = $default['yt']+$data['money'];

		pdo_update("tg_agent",array('yt'=>$update['yt']),array('uniacid'=>$_W['uniacid'],'type' => 1));
		$storage = pdo_insert('tg_present_record', $data);
		$result = pdo_query("UPDATE ".tablename('tg_distributor_cash')." SET status = 1 WHERE id in ( ". $_GPC['ssid'] ." )", array());

		$tip='申请成功，请等待审核！';
		echo "<script>alert('".$tip."');location.href='".web_url('agent/agent',array('ops' =>'post'))."';</script>";
		exit();
		
	}
	if($power['agent']==1 && empty($_GPC['ops'])){
		$ops='post';
	}else{
		$ops = !empty($_GPC['ops']) ? $_GPC['ops'] : 'display';
	}
	if($op=='bd'){
		$tip='距离完成,还差一步！';
		echo "<script>location.href='".web_url('agent/agent',array('ops' =>'payment','openid'=>$_GPC['openid'],'nickname'=>$_GPC['saler']))."';</script>";
	}
	if($op=='erweima'){
		
		include wl_template('agent/agent');
		exit;
	}
	if($op=='payment'){
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$referral = "";  
		for ( $i = 0; $i < 6; $i++ )  
		{  
		// 使用 substr 截取$chars中的任意一位字符；  
		$referral .= $chars[ mt_rand(0, strlen($chars) - 1) ];  
		}  
		$referral= (string)$referral.(string)$_W['uniacid'];
		$time['atime'] = TIMESTAMP;
		$def= pdo_fetch("SELECT id,commission FROM ".tablename('tg_agentrule')." WHERE level = :level LIMIT 1", array(':level' => '青铜推客'));
		$def['commission']=$def['commission']/100;
		pdo_update("account_wechats",array('atime'=>$time['atime'] ,'agent'=>1,'agent_level_id'=>$def['id'],'agent_discount'=>$def['commission'],'referral'=>$referral),array('uniacid'=>$_W['uniacid']));
		
		$data['uniacid'] = $_W['uniacid'];
		$data['openid'] = $_GPC['openid'] ;
		$data['nickname'] = $_GPC['nickname'] ;
		$data['name'] = $_GPC['name'] ;
		$data['blank'] = $_GPC['blank'];
		$data['cardno'] = $_GPC['cardno'];
		$data['type'] = 1;
		$storage = pdo_insert('tg_agent', $data);
		
		$datas['uniacid'] = $_W['uniacid'];
		$datas['openid'] = $_GPC['openid'] ;
		$datas['nickname'] = $_GPC['nickname'] ;
		$datas['name'] = $_GPC['name'] ;
		$datas['blank'] = $_GPC['blank'];
		$datas['cardno'] = $_GPC['cardno'];
		$datas['type'] = 2;
		$storage = pdo_insert('tg_agent', $datas);
		
		$tip='恭喜您已申请成功,快去推荐赢厚礼吧！';
		echo "<script>location.href='".web_url('agent/agent',array('ops' =>'post'))."';</script>";	
		exit();
	}
	if($ops=='display'||$ops=='erweima'||$ops=='bd'||$ops=='payment'){
        
		include wl_template('agent/agent');
	}
	if($ops=='post'){
		echo "<script>location:reload();</script>";	
		$dtime['dtime'] = TIMESTAMP;
		
		$rel = pdo_fetch("SELECT Referral FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
		$sum['sum']=0;
		$log = pdo_fetchall("SELECT subsidy_cash,refund_time,id,uniacid,parent_uniacid,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,FROM_UNIXTIME(refund_time,'%Y-%m-%d %H:%i') as srtime,refund_cash,status FROM ".tablename('tg_distributor_cash')." WHERE parent_uniacid = :uniacid order by ptime desc ", array(':uniacid' => $_W['uniacid']));
		foreach($log as &$vs)
		{
			$sql3 = pdo_fetch("SELECT name FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid  ", array(':uniacid' => $vs['uniacid']));
        	$vs['name'] = $sql3['name']; 
        	$sum['sum'] += $vs['cash'];
        	$sum['sum_r'] += $vs['refund_cash'] + $vs['subsidy_cash'];
		}
		$agent = pdo_fetch("SELECT yt,openid FROM ".tablename('tg_agent')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
		
		$message = pdo_fetch("SELECT level,name,maxprice,minprice,commission,img FROM ".tablename('tg_agentrule')." WHERE  minprice <= :price and maxprice >= :price ", array(':price' => $sum['sum']));
		 
		//die(json_encode($message));
		switch ($message['minprice'])
		{
		case 0:		
		  $message['bl'] = $sum['sum']/$message['maxprice']*100;
		  break;
		case 30000:
		  $message['bl'] = ($sum['sum']-30000)/($message['maxprice']-30000)*100;
		  break;
		case 60000:
		  $message['bl'] = ($sum['sum']-60000)/($message['maxprice']-60000)*100;
		  break;
		case 120000:
		  $message['bl'] = ($sum['sum']-120000)/($message['maxprice']-120000)*100;
		  break;
		case 240000:
		  $message['bl'] = ($sum['sum']-240000)/($message['maxprice']-240000)*100;
		  break;
		case 480000:
		  $message['bl'] = 100;
		  break;
		}
		if($message['bl'] >= 99){
		  $message['bl'] = 99;
		}
		$message['Referral'] = $rel['Referral'];
		$message['sum'] = $sum['sum'];
	
		$message['yt'] = $agent['yt'];
		$message['wt'] = $sum['sum_r']-$message['yt'];
		$message['sum_r'] = $sum['sum_r'];

		include wl_template('agent/agent');
		
	
	}
	if($ops=='tx'){
		$data['rtime'] = TIMESTAMP;
		
		$default = pdo_fetch("SELECT name,blank,cardno,openid FROM ".tablename('tg_agent')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
		$blank = pdo_fetchall("SELECT name,blank,cardno,id FROM ".tablename('tg_agent')." WHERE type = :type and openid = :openid", array(':openid' => $default['openid'],':type' => '2'));
		
		$log = pdo_fetchall("SELECT subsidy_cash,id,uniacid,parent_uniacid,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,FROM_UNIXTIME(refund_time,'%Y-%m-%d %H:%i') as rtime,refund_cash,status FROM ".tablename('tg_distributor_cash')." WHERE parent_uniacid = :uniacid and status=0 and refund_time <= :time order by ptime desc ", array(':uniacid' => $_W['uniacid'],':time' => $data['rtime']));
		foreach($log as &$vs)
		{
			$sql3 = pdo_fetch("SELECT name FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid  ", array(':uniacid' => $vs['parent_uniacid']));
        	$vs['name'] = $sql3['name']; 
		}
		include wl_template('agent/agent');
	}
	if($ops=='xk'){
	
		include wl_template('agent/agent');
		$ops=='';
	}
	if($ops=='xkh'){
		$data['rtime'] = TIMESTAMP;
		$default = pdo_fetch("SELECT name,blank,cardno,openid FROM ".tablename('tg_agent')." WHERE uniacid = :uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
		$blank = pdo_fetchall("SELECT name,blank,cardno,id FROM ".tablename('tg_agent')." WHERE type = :type and openid = :openid", array(':openid' => $default['openid'],':type' => '2'));
		
		$log = pdo_fetchall("SELECT id,uniacid,parent_uniacid,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,FROM_UNIXTIME(refund_time,'%Y-%m-%d %H:%i') as rtime,refund_cash,status FROM ".tablename('tg_distributor_cash')." WHERE parent_uniacid = :uniacid and status=0 and refund_time <= :time order by ptime desc ", array(':uniacid' => $_W['uniacid'],':time' => $data['rtime']));
		foreach($log as &$vs)
		{
			$sql3 = pdo_fetch("SELECT name FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid  ", array(':uniacid' => $vs['parent_uniacid']));
        	$vs['name'] = $sql3['name']; 
		}
		include wl_template('agent/agent');
		$ops=='';
	}
	
	
	
}
exit();
