<?php
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if($op =='display'){
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . "  ORDER BY create_time DESC ");
	$pager = pagination($total, $pindex, $psize);
	
	$list = pdo_fetchall("select openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d ') as time from".tablename('tg_judgment')." ORDER BY create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize,array());

	include wl_template('service/judgment_admin');
}
if($op =='replay'){
	$judgment_id = $_GPC['jid'];
	$content = $_GPC['content'];
	
	$data_content['judgment_id'] = $judgment_id;
	$data_content['content_id'] = date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
	$data_content['content'] = $content;
	$data_content['update_time'] = TIMESTAMP;
	$data_content['who'] = 1;
	
	$addjudgment_content = pdo_insert('tg_judgment_content', $data_content);
	
	$goimages = $_GPC['img'];
	$goimages = array_reverse($goimages);
	foreach ($goimages as $key => $value) {
		$data2 = array('img_url' => $goimages[$key], 'content_id' => $data_content['content_id']);
		pdo_insert('tg_judgment_img', $data2);
	}	
	
	echo "<script>alert('回复成功');location.href='".web_url('service/judgment_admin',array('op' =>$display))."';</script>";
}exit();