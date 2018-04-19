<?php

if($op == 'insert'){
	$acct = pdo_fetch("select name,uniacid,vip from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");

	$data_admin['form_id'] =  date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
	$data_admin['uniacid'] = $_W['uniacid'];
	$data_admin['create_time'] = TIMESTAMP;
	$data_admin['update_time'] = $data_admin['create_time'];
	$data_admin['name'] = $acct['name'];
	if( $_GPC['type'] == 2 && $acct['vip'] == 0){
		$data_admin['status'] = -3;
	}else{
		$data_admin['status'] = -1;	
	}
	$data_admin['type'] = $_GPC['type'];
	$data_admin['vip'] = $acct['vip'];
	$data_admin['title'] = $_GPC['title'];
	$storage = pdo_insert('workform', $data_admin);
	
	$data_content['form_id'] =  $data_admin['form_id'];
	$data_content['content_id'] =  $data_admin['form_id'];
	$data_content['content'] = $_GPC['content'];
	$data_content['update_time'] = $data_admin['create_time'];
	$data_content['who'] = $_W['uniacid'];
	$storage = pdo_insert('workform_content', $data_content);
	
	$goimages = $_GPC['img'];
	$goimages = array_reverse($goimages);
	foreach ($goimages as $key => $value) {
		$data2 = array('img_url' => $goimages[$key], 'content_id' => $data_content['content_id']);
		pdo_insert('workform_img', $data2);
	}	
	if( $_GPC['type'] == 2 && $acct['vip'] == 0){
		echo "<script>location.href='".web_url('service/workform_submit',array('op' =>$display))."';</script>";
		exit();
	}else{
		echo "<script>location.href='".web_url('service/workform_list',array('op' =>$display))."';</script>";
		exit();	
	}
}

include wl_template('service/workform_submit');exit();
?>