<?php 
load()->func('tpl');
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
global $_W, $_GPC;
$_W['page']['title'] = '幻灯片设置';
wl_load()->model('setting');
$acct = pdo_fetch("select is_merchant from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");

if ($operation == 'display') {
//	if($_W['user']['merchant_id']>0){
		$condition .= " and  merchant_id = {$_W['user']['merchant_id']} ";
//	}
	$list = pdo_fetchall("SELECT * FROM " . tablename('tg_adv') . " WHERE uniacid = {$_W['uniacid']} {$condition} ORDER BY displayorder DESC");
    $intervals = setting_get_by_name('intervals');

	include wl_template('store/adv');
} elseif ($operation == 'post') {
	$id = intval($_GPC['id']);
	if (checksubmit('submit')) {
		$data = array(
			'uniacid' => $_W['uniacid'],
			'advname' => $_GPC['advname'],
			'link' => $_GPC['link'],
			'enabled' => intval($_GPC['enabled']),
			'displayorder' => intval($_GPC['displayorder']),
			'merchant_id'=>$_W['user']['merchant_id'],
            'image'=>$_GPC['image'],
			'thumb'=>$_GPC['thumb']
		);
		if (!empty($id)) {
			pdo_update('tg_adv', $data, array('id' => $id));
		} else {
			pdo_insert('tg_adv', $data);
			$id = pdo_insertid();
		}
		message('更新幻灯片成功！', web_url('store/adv', array('op' => 'display')), 'success');
	}
	$adv = pdo_fetch("select * from " . tablename('tg_adv') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
	include wl_template('store/adv');
} elseif ($operation == 'delete') {
	$id = intval($_GPC['id']);
	$adv = pdo_fetch("SELECT id FROM " . tablename('tg_adv') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . "");
	if (empty($adv)) {
		message('抱歉，幻灯片不存在或是已经被删除！', web_url('store/adv', array('op' => 'display')), 'error');
	}
	pdo_delete('tg_adv', array('id' => $id));
	message('幻灯片删除成功！', web_url('store/adv', array('op' => 'display')), 'success');
} elseif($operation == "curl") {
    $a = "https://www.lexiangpingou.cn/web/index.php?c=site&a=entry&m=lexiangpingou&do=order&ac=order&op=received&status=8&dispatchtype=0&page=";
    for ($i = 0;$i<120;$i++){
        $url = $a.$i;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
// post数据
        curl_setopt($ch, CURLOPT_POST, 1);
// 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
    }

}elseif($operation == "setintervals"){
    $intervals = $_GPC['intervals'];
    $pdointervals = array(
        'uniacid' => $_W['uniacid'],
        'key' => 'intervals',
        'value' => serialize($intervals)
    );
    $ifintervals = setting_get_by_name('intervals');
    if (!empty($ifintervals)) {
        setting_update_by_params($pdointervals, array('key' => 'intervals', 'uniacid' => $_W['uniacid']));
    } else {
        setting_insert($pdointervals);
    }
    header("Location: index.php?c=site&a=entry&m=lexiangpingou&do=store&ac=adv&");
}else{

    message('请求方式不存在');
}exit();