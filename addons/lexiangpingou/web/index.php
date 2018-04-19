<?php
define('IN_MOBILE', true);
wl_load()->func('template');
wl_load()->model('permissions');
global $_W, $_GPC;
$controller = $_GPC['do'];
$action = $_GPC['ac'];
$op = $_GPC['op'];
//file_get_contents(app_url("home/task"));
$moblile_uid = pdo_fetch("select uid from " . tablename('uni_account_users') . " where uniacid = '{$_W['uniacid']}' and role='owner' ");
$checkmobile = pdo_fetch("select mobile from " . tablename('users_profile') . " where uid = '{$moblile_uid['uid']}'  ");
$_W['unicid_array']=[33,826,1950,69,1647,1681,1800,1148,1789,1829,1839,1908,1793,1923,1621,1855,106,1934,1938,1771,1901,1404,837,1595,1817,1571,913,45,1749,1505,382,1370,59,1019,1870,1722,1158,799,1111,1793,108,715,532,1867,1711,1898,1062,959,991,1714,1953,989,1883,1840,460,1555,1757,1938,1593,1930,1270,1939,1772,779,1429];
if(!pdo_fieldexists('tg_order', 'over_time')) {
    pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `over_time` int( 11 )");
}
if(!pdo_fieldexists('tg_member', 'sell_total')) {
    pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `sell_total` double(10,2)");
}
if(!pdo_fieldexists('tg_member', 'balance_sell_total')) {
    pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `balance_sell_total` double(10,2)");
}
if (empty($controller) || empty($action)) {
    $_GPC['do'] = $controller = 'store';
    $_GPC['ac'] = $action = 'setting';
}
$roots = 'w9.huodiesoft.com';
if ($_W['uniacid'] != 53) {
    $roots = 'www.lexiangpingou.cn';
}
$root_url = "http://" . $_W['uniaccount']['key'] . "." . $roots . "/" . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';
if ($_W['uniacid'] == 33) {
    $root_url = "http://" . $roots . "/" . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';
}
// 服务号到期时间
$uni = pdo_fetchall("select vip,endtime,ordernum,uniacid from " . tablename('account_wechats') . ' where vip=:vip and endtime<:endtime ', array(':vip' => 1, ':endtime' => time()));
foreach ($uni as $key => $value) {
    pdo_update('account_wechats', array('vip' => 0), array('uniacid' => $value['uniacid']));
}
//// APP 到期时间
//$apps = pdo_fetchall("SELECT * FROM " . tablename('account_vip') . ' WHERE app=:app AND app_time<:app_time ', array(':app' => 1, ':app_time' => time()));
//foreach ($apps as $key => $value) {
//    pdo_update('account_vip', array('app' => 0), array('uniacid' => $value['uniacid']));
//}
//// 美工包到期时间
//$arts = pdo_fetchall("SELECT * FROM " . tablename('account_vip') . ' WHERE art=:art AND art_time<:art_time ', array(':art' => 1, ':art_time' => time()));
//foreach ($arts as $key => $value) {
//    pdo_update('account_vip', array('art' => 0), array('uniacid' => $value['uniacid']));
//}
//// 小程序到期时间
//$applets = pdo_fetchall("SELECT * FROM " . tablename('account_vip') . ' WHERE applet=:applet AND applet_time<:applet_time ', array(':applet' => 1, ':applet_time' => time()));
//foreach ($applets as $key => $value) {
//    pdo_update('account_vip', array('applet' => 0), array('uniacid' => $value['uniacid']));
//}
$acct = pdo_fetch("select * from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
if (empty($acct['tpl'])) {
    pdo_update('account_wechats', array('tpl' => 8146), array('uniacid' => $_W['uniacid']));
}

//多商户时间到期将其下架
//$mer = pdo_fetch("select * from " .tablename('account_wechats') ." where uniacid = '{$_W['uniacid']}'");
//if (!empty($mer['expire_time']) && $mer['expire_time'] != 0 && $mer['expire_time'] < TIMESTAMP && $mer['is_merchant'] == 1){
//    pdo_update('account_wechats' , array('applet' => 0) , array('uniacid' => $_W['uniacid']));
//}elseif (!empty($mer['expire_time']) && $mer['expire_time'] != 0 && $mer['expire_time'] > TIMESTAMP && $mer['is_merchant'] == 1){
//    pdo_update('account_wechats' , array('applet' => 1) , array('uniacid' => $_W['uniacid']));
//}
//多商户批次到期下架
$endtime = time();
$batch = pdo_fetchall("select * from " .tablename('tg_merchant_batch') ." where endtime > 0 and endtime < '{$endtime}' order by endtime asc ");
foreach ($batch as $v) {
    pdo_update('tg_merchant' , array('status' => 4) , array('uniacid' => $v['uniacid'] , 'merchant_batch' => $v['merchant_batch'] , 'status' => 1));
    pdo_update('tg_merchant_batch' , array('endtime' => 0) , array('id' => $v['id']));
}

unset($batch);

$account_vip = pdo_get('account_vip' , array('uniacid' => $_W['uniacid']));
if ($account_vip['applet_time'] < $endtime) {
    pdo_update('account_vip' , array('applet' => 0) ,array('id'=>$account_vip['id']));
}
if ($account_vip['app_time'] < $endtime) {
    pdo_update('account_vip' , array('app' => 0) ,array('id'=>$account_vip['id']));
}
if ($account_vip['art_time'] < $endtime) {
    pdo_update('account_vip' , array('art' => 0) ,array('id'=>$account_vip['id']));
}
if ($account_vip['erp_time'] < $endtime) {
    pdo_update('account_vip' , array('erp' => 0) ,array('id'=>$account_vip['id']));
}
if ($account_vip['offline_time'] < $endtime) {
    pdo_update('account_vip' , array('offline' => 0) ,array('id'=>$account_vip['id']));
}
unset($account_vip);
unset($endtime);

if ($_W['user']['merchant_id'] > 0) {
    $m_user = pdo_fetch('select * from ' . tablename('tg_merchant') . ' where id=:id ', array(':id' => $_W['user']['merchant_id']));
    if ($m_user['status'] == 0) {
        $tip = '本账户正在审核中，请耐心等待！';
    } else if ($m_user['status'] == 3) {
        $tip = '本账户已被下架，请联系管理员！';
    } else if ($m_user['status'] == 2) {
        $tip = '本账户已被拒绝入驻，请联系管理员！';
    } elseif ($m_user['status'] == 4){
        $tip = '本商户已到期，请联系管理员！';
    } elseif ($m_user['status'] == 6){
        $tip = '对不起！您已取消入驻';
    }
    $roots = 'w9.huodiesoft.com';
    if ($_W['uniacid'] != 53) {
        $roots = 'www.lexiangpingou.cn';
    }
    $wechat = pdo_fetch('select * from ' . tableName('account_wechats') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
    $head_http = "http://";
    if ($wechat['is_https'] == 1) {
        $head_http = "https://";
    }
//    $url = $head_http . $wechat['key'] . "." . $roots . "/";
//    $uni_settings = pdo_fetch('select oauth from ' . tablename('uni_settings') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
//    $oauth = iunserializer($uni_settings['oauth']);
//    if (!empty($oauth['host'])) {
//        $url = $oauth['host'];
//    }
    if ($m_user['status'] == 0 || $m_user['status'] == 2 || $m_user['status'] == 3 || $m_user['status'] == 4 || $m_user['status'] == 6) {
        $url .= 'index.php?c=user&a=shop_login&i=' .$_W['uniacid'] ."&";
        echo "<script>alert('" . $tip . "');location.href='" . $url . "';</script>";
        exit;
    }
    if ($m_user['margin'] == 0 && $acct['margin'] > 0){
        $tip = "请缴纳保证金！";
//        echo "<script>alert('" . $tip . "');</script>";

        $controller = 'store';
        $action = 'merchant_margin';
    }


}
//判定新用户 给7天VIP专享
if (intval($acct['vip']) == 0 && intval($acct['endtime']) == 0) {
    $endtime = strtotime(date('Y-m-d', strtotime('+3 day')));
    pdo_update('account_wechats', array('vip' => 1, 'endtime' => $endtime), array('uniacid' => $_W['uniacid']));
}
isetcookie('__uniacid', $_W['uniacid'], 7 * 86400);

isetcookie('__uid', $_W['uid'], 7 * 86400);
if (empty($_W['uniacid'])) {
    header("Location: index.php?c=user&a=login&");
    exit;
}

/*
$paylist=pdo_fetchall("SELECT * from cm_tg_order where uniacid=91 and  `status`=10 and transid ='' ");
foreach ($paylist as $key3 => $value) {
	$paylog=pdo_fetch("SELECT * from cm_core_paylog where tid=:tid",array(':tid'=>$value['orderno']));
	$params=unserialize($paylog['tag']);
	pdo_update('tg_order', array('transid'=>$params['transaction_id']), array('orderno' => $paylog['tid']));
}*/
if ($_W['user']['merchant_id'] > 0) {
    $condition .= " and  uniacid = {$_W['uniacid']} ";
} else {
    $condition .= " and  uniacid in( {$_W['uniacid']},33) ";
}
$notification = pdo_fetchall("select id,title,summary,FROM_UNIXTIME(stime,'%Y-%m-%d ') as stime from" . tablename('tg_notification') . " where 1 {$condition} ORDER BY id DESC LIMIT 0,5");
unset($condition);
$getlistFrames = 'get' . $controller . 'Frames';
$frames = $getlistFrames();
$top_menus = get_top_menus();
/*
$funrecordlist=pdo_fetchall("SELECT * from cm_tg_function_detail where uniacid={$_W['uniacid']} ");
if(empty($funrecordlist))
{
	$funlist=pdo_fetchall("SELECT * from cm_tg_function where isdingzhi<>1 and type<>3");
	
	foreach ($funlist as $key3 => $value) {
		if($value['id']==8146)
		{
			$endtime=strtotime(date('Y-m-d', strtotime('+240 month')));
		}else{
			$endtime=strtotime(date('Y-m-d', strtotime('+7 day')));
		}
	$data = array(
		'uniacid'     => $_W['uniacid'],
		'functionid'=>$value['id'],
		'endtime'=>$endtime
	);
	pdo_insert('tg_function_detail', $data);
}
}
*/
//报表基础数据实时更新

$system_task = pdo_fetch("select * from cm_core_settings where cm_core_settings.key='task_time'");
$_W['task_time'] = $system_task['value'];
$_W['auto_run_data'] = 0;
$system_run = (TIMESTAMP - $system_task['value']) / 60;
if ($system_run > 10) {
    $n = date('Y-m-d');
    $y = date('Y-m-d', $system_task['value']);
    if ($n != $y) {
        pdo_update('core_settings', array('value' => TIMESTAMP), array('key' => 'task_time'));
        $_W['auto_run_data'] = 2;

    } else {
        pdo_update('core_settings', array('value' => TIMESTAMP), array('key' => 'task_time'));
        $_W['auto_run_data'] = 1;
    }

}


if($_W['uniacid'] == 33){
    wl_load()->func('message');
    $message = pdo_fetchall("select * from " .tablename('tg_service_process')." order by id limit 30 ");
    foreach ($message as $item) {
        m_service_process($item['openid'] , $item['first'] , $item['keyword1'],$item['keyword2'],$item['keyword3'],$item['keyword4'],$item['remark']);
        pdo_delete('tg_service_process' , array('id' => $item['id']));
//    internal_log('service_process' ,
//        array(
//            'ip' => CLIENT_IP,
//            'op' => "模板消息调试",
//            'filepath' => __FILE__,
//            'line' => __LINE__,
//            'setting' => '',
//            'm_ref' => '',
//            'postdata' => '',
//            'openid' => '',
//            'url' => '',
//            'err_msg' => '',
//            'time' => date('Y-m-d H:i:s', TIMESTAMP)
//        ));
    }
}


$file = TG_WEB . 'controller/' . $controller . '/' . $action . '.ctrl.php';

if (!file_exists($file)) {
    header("Location: index.php?i={$_W['uniacid']}&c=entry&do=home&ac=index&m=lexiangpingou");
    exit;
}

require $file;

