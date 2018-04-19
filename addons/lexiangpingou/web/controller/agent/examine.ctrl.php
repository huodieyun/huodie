<?php
$power=pdo_fetch("select Referral from ". tablename('account_wechats')." where uniacid = '{$_W['uniacid']}'  ");


defined('IN_IA') or exit('Access Denied');

	if($op == 'addmobile'){
		$mobile=$_GPC['mobile'];
		$addmobile=pdo_query("UPDATE ".tablename('users_profile')." set mobile = :mobile WHERE uid = :uid ", array(':mobile' => $mobile,':uid' => $_W['uid'] ));
		die(json_encode($mobile));
	}
	if($op == 'share'){

		isetcookie('__session', '', -10000);
		SetCookie("Referral",$power['Referral']);
		

		$forward = $_GPC['forward'];
		if(empty($forward)) {
			$forward = './?refersh';
		}
		//message($_COOKIE['Referral']);
		//header('Location:' . url('account/welcome'));
		header('Location:' . url('account/welcome'));
	}
//message(md5(uniqid()));
	if($op == 'det'){
		$id = intval($_GPC['id']);

		$det = pdo_fetch("select note,record_id,nickname,cardno,blank,name,pstatus,id,openid,status,money,FROM_UNIXTIME(stime,'%Y-%m-%d %H:%i') as stime,FROM_UNIXTIME(ftime,'%Y-%m-%d %H:%i') as ftime from" . tablename('tg_present_record') . "where id = {$id} order by stime desc ");
		if ($det['pstatus']==1){
			$det['pstatus']="线上提款";
		}else if($det['pstatus']==2){
			$det['pstatus']="线下提款";
		}
		if ($det['ftime']== null){
			$det['ftime']="——";
		}
		$log = pdo_fetchall("SELECT orderno,id,uniacid,parent_uniacid,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,FROM_UNIXTIME(refund_time,'%Y-%m-%d %H:%i') as rtime,refund_cash,status FROM ".tablename('tg_distributor_cash')." WHERE parent_uniacid = :uniacid and id in ( ". $det['record_id'] ." ) order by ptime desc ", array(':uniacid' => $_W['uniacid']));
		foreach($log as &$vs)
		{
			$sql3 = pdo_fetch("SELECT name FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid  ", array(':uniacid' => $vs['parent_uniacid']));
        	$vs['name'] = $sql3['name']; 
        	$sum['sum'] += $vs['cash'];
        	$sum['sum_r'] += $vs['refund_cash'] + $vs['subsidy_cash'];

		}
		$det['log'] = $log;
		die(json_encode($det));
	}
	if($op == 'tx'){
		$ssid = $_GPC['dc_id'];
		$ssid = implode(",",$ssid);
		$log = pdo_fetchall("SELECT subsidy_cash,id,uniacid,parent_uniacid,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,FROM_UNIXTIME(refund_time,'%Y-%m-%d %H:%i') as rtime,refund_cash,status FROM ".tablename('tg_distributor_cash')." WHERE id in ( ". $ssid ." ) order by ptime desc ", array());
		foreach($log as &$vs)
		{

        	$sum['sum'] += $vs['cash'];
        	$sum['sum_r'] += $vs['refund_cash']+$vs['subsidy_cash'];

		}
		$result=array('ssid'=>$ssid,'message'=>$sum['sum_r'] );
		die(json_encode($result));
	}
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$status = intval($_GPC['status']);
 if ($op == 'display') {

	$selltype = $_GPC['selltype'];
	
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	$condition = " status = 1 ";
	if (empty($status)) {
		$condition = " status = 0";

	}
	if ($status == '1') {
		$condition = " status = 1";

	}
	if ($status == '2') {
		$condition = " status = 2";

	}
	if ($status == '3') {
		$condition = " status in (-1,0,1,2)";

	}

	$time = $_GPC['time'];
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}
	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end']) + 86399;
		$condition .= " AND  stime >= {$starttime} AND  stime <= {$endtime} ";

	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " AND openid  in (select from_user from " . tablename('tg_member') . " where nickname LIKE '%{$_GPC['keyword']}%') ";
	}

	
	$alltuan = pdo_fetchall("select record_id,cardno,blank,name,pstatus,id,openid,status,money,FROM_UNIXTIME(stime,'%Y-%m-%d %H:%i') as stime,FROM_UNIXTIME(ftime,'%Y-%m-%d %H:%i') as ftime  from" . tablename('tg_present_record') . " where " . $condition . " order by stime desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
	
	$alltuan2 = pdo_fetchall("select * from" . tablename('tg_present_record') . "where " . $condition . " order by stime desc ");

	$total = count($alltuan2);
	$pager = pagination($total, $pindex, $psize);
	include wl_template('agent/examine');exit();
  }else if($op == 'sendcash')
  {
	$id = intval($_GPC['id']);
	$sstatus = $_GPC['sstatus'];
	$sopenid = $_GPC['openid'];
	$smoney = $_GPC['money'];
	$xssid = $_GPC['xssid'];
	$time= TIMESTAMP;
	$upd=pdo_update("tg_present_record",array('status'=>$sstatus),array('id'=>$id));
	if ($sstatus == -1){
		$note = $_GPC['note'];
		$upd=pdo_update("tg_present_record",array('note'=>$note),array('id'=>$id));
		
		$default = pdo_fetch("SELECT yt FROM ".tablename('tg_agent')." WHERE openid = :openid LIMIT 1", array(':openid' => $sopenid));
		$update['yt'] = $default['yt']-$smoney;

		$ftime=pdo_query("UPDATE ".tablename('tg_agent')." set yt = :yt WHERE openid = :openid and type = :type", array(':yt' => $update['yt'] , ':openid' => $sopenid , ':type' => 1));
		$result = pdo_query("UPDATE ".tablename('tg_distributor_cash')." SET status = 0 WHERE id in ( ". $xssid ." )", array());
		
		$tip='状态已修改，变更为审核失败';
		echo "<script>alert('".$tip."');location.href='".web_url('agent/examine')."';</script>";
		exit();
	}
	if ($sstatus == 2){
		$ftime=pdo_update("tg_present_record",array('ftime'=>$time),array('id'=>$id));
		$tip='状态已修改，变更为已发放';
		echo "<script>alert('".$tip."');location.href='".web_url('agent/examine')."';</script>";
		exit();
	}
	if ($sstatus == 1){
		$tip='状态已修改，变更为待发放';
		echo "<script>alert('".$tip."');location.href='".web_url('agent/examine')."';</script>";
		exit();
	}

	 
  }
exit();
	
