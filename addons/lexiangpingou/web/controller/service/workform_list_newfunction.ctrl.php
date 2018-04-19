<?php
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';


if($op=='display'){
	$develop_status = $_GPC['develop_status'];
	$condition2 = " order by  sort desc , create_time asc ";
	if (empty($develop_status)) {
		$develop_status = '0';
	}
	if ($develop_status == '0') {
		$condition .= " and develop_status <> 2  ";
	}
	if ($develop_status == '2') {
		$condition .= " and develop_status = 2  ";
		$condition2 = " order by  sort desc , update_time desc ";
	}
	if (!empty($_GPC['keyword_form_id'])) {
		$condition .= " and form_id like '%{$_GPC['keyword_form_id']}%' ";
	}
	if (!empty($_GPC['keyword_gzname'])) {
		$condition .= " and name like '%{$_GPC['keyword_gzname']}%' ";
	}
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	
	$allform = pdo_fetchall("select sort,develop_status,vip,title,form_id,status,FROM_UNIXTIME(create_time,'%Y-%m-%d  %H:%i') as create_time,name,FROM_UNIXTIME(update_time,'%Y-%m-%d  %H:%i') as u_time from" . tablename('workform') . " where inner_work = 0 and type = 2 and status =2 " . $condition . $condition2 . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize,array());
	
	$allform2 = pdo_fetchall("select vip,title,form_id,status,FROM_UNIXTIME(create_time,'%Y-%m-%d  %H:%i') as create_time,name from" . tablename('workform') . " where inner_work = 0 and type = 2 and status =2 " .$condition. " order by create_time desc",array());
	$total = count($allform2);
	$pager = pagination($total, $pindex, $psize);
	include wl_template('service/workform_list_newfunction');
}

if($op=='detail'){
	$form_id = $_GPC['form_id'];
	$form_admin = pdo_fetch("select develop_status,type,title,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i') as create_time,uniacid,status from" . tablename('workform') . "where form_id = :form_id order by create_time asc", array(':form_id' => $form_id));
	$allcontent = pdo_fetchall("select content_id,content,FROM_UNIXTIME(update_time,'%Y-%m-%d %H:%i') as update_time,who from" . tablename('workform_content') . " where form_id = :form_id order by update_time asc",array(':form_id' => $form_id));
	include wl_template('service/workform_detail_newfunction');
}
if($op=='develop_start'){
	$form_id = $_GPC['form_id'];
	
	$result_start = pdo_update('workform', array('develop_status' => 1), array('form_id' => $form_id));
	die(json_encode($result_start));
}

if($op=='develop_over'){
	$form_id = $_GPC['form_id'];
	$nowtime = TIMESTAMP;
	$result_over = pdo_update('workform', array('develop_status' => 2 ,'update_time' =>$nowtime), array('form_id' => $form_id));
	die(json_encode($result_over));
}
if($op=='work_sort'){
	$form_id = $_GPC['form_id'];
	$sort = $_GPC['sort'];
	$result_sort = pdo_update('workform', array('sort' => $sort), array('form_id' => $form_id));
	die(json_encode($sort));
}exit();
?>