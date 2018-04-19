<?php
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$allendtime = pdo_fetchall("SELECT update_time,form_id FROM ".tablename('workform')." WHERE status <> 2 and uniacid =:uniacid  ", array(':uniacid' => $_W['uniacid']));
foreach($allendtime as &$endtime_one)
	{
			$new_endtime = date('Y-m-d H:i:s',$endtime_one['update_time']);
			$over_endtime = strtotime("$new_endtime +5 day");
			$now_time = time();
			if($now_time >= $over_endtime){
				$update_status = pdo_update('workform', array('status' => 2), array('form_id' => $endtime_one['form_id']));
			}
	}

if($op=='display'){
	$status = $_GPC['status'];
	if (empty($status)) {
		$status = '-1';
	}
	if ($status == '-1') {
		$condition = "and status <> 2 and status <> -2";
	}
	if ($status == '2') {
		$condition = "and status in (2,-2) ";
	}
	if ($status == '9') {
		$condition = "";
	}
	if (!empty($_GPC['keyword_form_id'])) {
		$condition .= " and form_id like '%{$_GPC['keyword_form_id']}%' ";
	}

	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	
	$allform = pdo_fetchall("select develop_status,type,title,form_id,status,FROM_UNIXTIME(create_time,'%Y-%m-%d  %H:%i') as create_time from" . tablename('workform') . " where uniacid = :uniacid and inner_work = 0 and status <> -3 " .$condition. " order by create_time desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize,array(':uniacid' => $_W['uniacid']));
	
	$allform2 = pdo_fetchall("select title,form_id,status,FROM_UNIXTIME(create_time,'%Y-%m-%d  %H:%i') as create_time from" . tablename('workform') . " where uniacid = :uniacid and inner_work = 0 and status <> -3 " .$condition. " order by create_time desc ",array(':uniacid' => $_W['uniacid']));
	$total = count($allform2);
	$pager = pagination($total, $pindex, $psize);
	include wl_template('service/workform_list');
}

if($op=='detail'){
	$form_id = $_GPC['form_id'];
	$form_admin = pdo_fetch("select vip,type,title,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i') as create_time,uniacid,status from" . tablename('workform') . "where form_id = :form_id order by create_time asc", array(':form_id' => $form_id));
	$allcontent = pdo_fetchall("select content_id,content,FROM_UNIXTIME(update_time,'%Y-%m-%d %H:%i') as update_time,who from" . tablename('workform_content') . " where form_id = :form_id order by update_time asc",array(':form_id' => $form_id));
	include wl_template('service/workform_detail');
}
if($op=='replay'){
	$form_id = $_GPC['form_id'];
	
	$data_content['form_id'] = $form_id;
	$data_content['content_id'] = date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
	$data_content['content'] = $_GPC['content'];
	$data_content['update_time'] = TIMESTAMP;
	$data_content['who'] = $_W['uniacid'];
	$storage = pdo_insert('workform_content', $data_content);
	
	$goimages = $_GPC['img'];
	$goimages = array_reverse($goimages);
	foreach ($goimages as $key => $value) {
		$data2 = array('img_url' => $goimages[$key], 'content_id' => $data_content['content_id']);
		pdo_insert('workform_img', $data2);
	}	
	
	$result = pdo_update('workform', array('status' => -1 ,'update_time' => $data_content['update_time']), array('form_id' => $form_id));
	echo "<script>location.href='".web_url('service/workform_list/detail',array('form_id' =>$form_id ,'after' => 1))."';</script>";
	exit();
}
if($op=='workform_over'){
	$form_id = $_GPC['form_id'];
	
	$result_over = pdo_update('workform', array('status' => 2), array('form_id' => $form_id));
	die(json_encode($result_over));
}exit();
?>