<?php

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if(!pdo_fieldexists('tg_notification', 'uniacid')) {
	pdo_query("ALTER TABLE ".tablename('tg_notification')." ADD `uniacid` int( 11 ) ;");
}
if($op =='display'){
	include wl_template('gzhset/notification');
}
if($op =='publish'){


	$data['title'] = $_GPC['title'];
	$data['uniacid'] = $_W['uniacid'];
	$data['summary'] = $_GPC['summary'];
	$data['content'] = $_GPC['content'];
	$data['stime'] = TIMESTAMP;
	
	$sid=$_GPC['sid'];
	if(empty($_GPC['sid'])){
		$storage = pdo_insert('tg_notification', $data);
		$tip='发布成功';
		echo "<script>alert('".$tip."');location.href='".web_url('gzhset/notification-list')."';</script>";
		exit;
	}
	else{
		pdo_update("tg_notification",array('title'=>$_GPC['title'] ,'summary'=>$_GPC['summary'],'content'=>$_GPC['content']),array('id'=>$_GPC['sid']));
		$tip='更新成功';
		echo "<script>alert('".$tip."');location.href='".web_url('gzhset/notification-list')."';</script>";
		exit;
	}

}
if($op =='delete'){
	pdo_query("delete from " . tablename('tg_notification') . " where id= '{$_GPC['id']}'");
	$tip='删除成功';
	echo "<script>alert('".$tip."');location.href='".web_url('gzhset/notification-list')."';</script>";
	exit;
}
exit();