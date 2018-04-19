<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
//uni_user_permission_check('profile_payment');
$_W['page']['title'] = '支付参数 - 公众号选项';
$setting = uni_setting($_W['uniacid'], array('payment', 'recharge'));
$pay = $setting['payment'];
$recharge =  $setting['recharge'];
$rooturl="http://".$_W['uniaccount']['key'].".".$_SERVER['HTTP_HOST']."/";
if(!is_array($pay)) {
	$pay = array();
}
$op = $_GPC['op'];
if(!$op){
    $op = 'payment';
}
$wechats=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=".$_W['uniacid']);
if($_W['ispost']) {
	wl_load()->model('setting');
	$credit = array_elements(array('switch'), $_GPC['credit']);
	$credit['switch'] = $credit['switch'] == 'true';
	$card = array_elements(array('switch'), $_GPC['card']);
	$card['switch'] = intval($card['switch']);
	$alipay = array_elements(array('switch', 'account', 'partner', 'secret'), $_GPC['alipay']);
	$alipay['switch'] = $alipay['switch'] == 'true';
	$alipay['account'] = trim($alipay['account']);
	$alipay['partner'] = trim($alipay['partner']);
	$alipay['secret'] = trim($alipay['secret']);
	$delivery = array_elements(array('switch'), $_GPC['delivery']);
	$delivery['switch'] = $delivery['switch'] == 'true';
	$line = array_elements(array('switch'),$_GPC['line']);
	$line['switch'] = $line['switch'] == 'true';
	load()->func('file');
        $r = mkdirs(IA_ROOT . '/attachment/lexiangpingou/cert/'. $_W['uniacid']);
		$r2 = mkdirs(TG_CERT.$_W['uniacid']);
		if(!empty($_GPC['cert'])) {
            $ret = file_put_contents(IA_ROOT . '/attachment/lexiangpingou/cert/'.$_W['uniacid'].'/apiclient_cert.pem', trim($_GPC['cert']));
            $ret2 = file_put_contents(TG_CERT.$_W['uniacid'].'/apiclient_cert.pem', trim($_GPC['cert']));
            $r = $r && $ret;
        }
        if(!empty($_GPC['key'])) {
            $ret = file_put_contents(IA_ROOT . '/attachment/lexiangpingou/cert/'.$_W['uniacid'].'/apiclient_key.pem', trim($_GPC['key']));
            $ret2 = file_put_contents(TG_CERT.$_W['uniacid'].'/apiclient_key.pem', trim($_GPC['key']));
            $r = $r && $ret;
        }
		if(!empty($_GPC['rootca'])) {
            $ret = file_put_contents(IA_ROOT . '/attachment/lexiangpingou/cert/'.$_W['uniacid'].'/apiclient_rootca.pem', trim($_GPC['rootca']));
            $ret2 = file_put_contents(TG_CERT.$_W['uniacid'].'/apiclient_rootca.pem', trim($_GPC['rootca']));
            $r = $r && $ret;
        }
		if(!$r) {
            message('证书保存失败, 请保证该目录可写');
        }
	if($alipay['switch'] && (empty($alipay['account']) || empty($alipay['partner']) || empty($alipay['secret']))) {
		message('请输入完整的支付宝接口信息.');
	}
	if($_GPC['alipay']['t'] == 'true') {
		$params = array();
		$params['tid'] = md5(uniqid());
		$params['user'] = '测试用户';
		$params['fee'] = '0.01';
		$params['title'] = '测试支付接口';
		load()->model('payment');
		load()->func('communication');
		$ret = alipay_build($params, $alipay);
		if($ret['url']) {
			header("location: {$ret['url']}");
		}
		exit();
	}
	$wechat = array_elements(array('switch', 'account', 'signkey', 'partner', 'key', 'version', 'mchid', 'apikey', 'version'), $_GPC['wechat']);
	$wechat['switch'] = $wechat['switch'] == 'true';
	$wechat['signkey'] = $wechat['version'] == 2 ? trim($wechat['apikey']) : trim($wechat['signkey']);
	$wechat['partner'] = trim($wechat['partner']);
	$wechat['key'] = trim($wechat['key']);


    $app_payment['appmchid'] = $_GPC['appmchid'];
    $app_payment['appapikey'] = $_GPC['appapikey'];
    $app_payment['appid'] = $_GPC['appid'];
    $app_payment['appsecret'] = $_GPC['appsecret'];

	if($wechat['switch'] && empty($wechat['account'])) {
		message('请输入完整的微信支付接口信息.');
	}
	
	$unionpay = array_elements(array('switch', 'signcertpwd', 'merid'), $_GPC['unionpay']);
	$unionpay['switch'] = $unionpay['switch'] == 'true';
	if($unionpay['switch'] && (empty($unionpay['merid']) || empty($unionpay['signcertpwd']))) {
		message('请输入完整的银联支付接口信息.');
	}
	if ($unionpay['switch'] && empty($_FILES['unionpay']['tmp_name']['signcertpath']) && !file_exists(IA_ROOT . '/attachment/unionpay/PM_'.$_W['uniacid'].'_acp.pfx')) {
		message('请上联银商户私钥证书.');
	}
	$baifubao = array_elements(array('switch', 'signkey', 'mchid'), $_GPC['baifubao']);
	$baifubao['switch'] = $baifubao['switch'] == 'true';
	if($baifubao['switch'] && (empty($baifubao['signkey']) || empty($baifubao['mchid']))) {
		message('请输入完整的百付宝支付接口信息.');
	}
	$line = array_elements(array('switch','message'),$_GPC['line']);
	$line['switch'] = $line['switch'] == 'true';
	if(!is_array($pay)) {
		$pay = array();
	}
	$pay['credit'] = $credit;
	$pay['alipay'] = $alipay;
	$pay['wechat'] = $wechat;
	$pay['delivery'] = $delivery;
	$pay['unionpay'] = $unionpay;
	$pay['baifubao'] = $baifubao;
	$pay['card'] = $card;
	$pay['line'] = $line;
    $pay['app_payment'] = $app_payment;

		$recharge = array();
	foreach($_GPC['recharge'] as $key=>$row) {
		$row = floatval($row);
		$back = floatval($_GPC['back'][$key]);
		if(!$row || !$back) continue;
		$recharge[] = array(
			'recharge' => $row,
			'back' => $back,
		);
	}
	$recharge = iserializer($recharge);
	$dat = iserializer($pay);

	if(!empty($_GPC['appid'])){
        pdo_update('account_wechats', array('appid'=>$_GPC['appid']), array('uniacid' => $_W['uniacid']));
    }
    if(!empty($_GPC['appsecret'])){
        pdo_update('account_wechats', array('appsecret'=>$_GPC['appsecret']), array('uniacid' => $_W['uniacid']));
    }


	if(!empty($_GPC['secret']))
		{
			pdo_update('account_wechats', array('secret'=>$_GPC['secret']), array('uniacid' => $_W['uniacid']));
		}
		$refund = array(
			'mchid' => $wechat['mchid'],
			'apikey' => $wechat['apikey'],
			'auto_refund'=>$_GPC['auto_refund']
		);
		$pdorefund = array(
			'uniacid'=>$_W['uniacid'],
			'key'=>'refund',
			'value'=>serialize($refund)
		);
		$ifrefund = setting_get_by_name('refund');
		if(!empty($ifrefund)){
			setting_update_by_params($pdorefund, array('key'=>'refund','uniacid'=>$_W['uniacid']));
		}else{
			setting_insert($pdorefund);
		}
	if(pdo_update('uni_settings', array('payment' => $dat, 'recharge' => $recharge), array('uniacid' => $_W['uniacid'])) !== false) {
		cache_delete("unisetting:{$_W['uniacid']}");
		
		echo "<script>alert('保存支付信息成功');location.href='".web_url('gzhset/payment')."';</script>";
		exit;
	} else {
		message('保存支付信息失败, 请稍后重试. ');
	}
	exit();
}
$pay['unionpay']['signcertexists'] = file_exists(IA_ROOT . '/attachment/unionpay/PM_'.$_W['uniacid'].'_acp.pfx');
$accounts = array();
$accounts[$_W['acid']] = array_elements(array('name', 'acid', 'key', 'secret', 'level'), $_W['account']);

include wl_template('gzhset/payment');exit();