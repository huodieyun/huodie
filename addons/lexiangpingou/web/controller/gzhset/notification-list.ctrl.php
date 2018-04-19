<?php
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if(!pdo_fieldexists('tg_notification', 'uniacid')) {
	pdo_query("ALTER TABLE ".tablename('tg_notification')." ADD `uniacid` int( 11 ) ;");
}
if($op =='display'){
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	$condition="";

	if($_W['user']['merchant_id']>0){
		$condition .= " and  uniacid = {$_W['uniacid']} ";
	}else{
		$condition .= " and  uniacid in( {$_W['uniacid']},33) ";
	}
	file_put_contents(TG_DATA."notice.log", var_export($condition, true).PHP_EOL, FILE_APPEND);
	$list = pdo_fetchall("select id,title,summary,FROM_UNIXTIME(stime,'%Y-%m-%d ') as stime,uniacid from".tablename('tg_notification')." where 1 {$condition} ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,array());
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_notification') . " where 1 {$condition}  ORDER BY id DESC ");
	$pager = pagination($total, $pindex, $psize);
	$con = " where status=1 AND is_advert_img=1 ";
	$supply_goods = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods') . $con . ' ORDER BY displayorder desc');
	include wl_template('gzhset/notification-list');
}
if($op =='content'){

	$list = pdo_fetchall("select id,title,summary,content,FROM_UNIXTIME(stime,'%Y-%m-%d ') as stime from".tablename('tg_notification')."where id = :id ",array(':id' => $_GPC['id']));

	include wl_template('gzhset/notification-content');
}
if($op =='post'){

	$lists = pdo_fetch("select id,title,summary,content from".tablename('tg_notification')."where id = :id ",array(':id' => $_GPC['id']));

	include wl_template('gzhset/notification');
}
exit();
