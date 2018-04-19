<?php
define('IN_MOBILE', true);

global $_W, $_GPC;
wl_load()->model('setting');
wl_load()->model('member');
wl_load()->model('order');
wl_load()->model('group');
wl_load()->func('global');
wl_load()->func('pdo');
wl_load()->func('tpl');
wl_load()->func('message');
wl_load()->func('print');
$config = tgsetting_load();
$openid = getOpenid();
$controller = $_GPC['do'];
$action = $_GPC['ac'];
session_start();
$uniacid_tpl = pdo_fetch("select tpl,refund_type,last_refund_time,last_run_time,receive_time from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
$function_tpl = pdo_fetch('SELECT color FROM ' . tablename('tg_function') . ' WHERE id=:id', array(':id' => $uniacid_tpl['tpl']));
$_W['system_color'] = $function_tpl['color'];
$_W['uniacid_tpl'] = $uniacid_tpl['tpl'];


$ip = getClientIP();
/*
if($ip!='112.16.53.66')
{
	if (empty($openid)) {
		die("<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                    <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                </head>
                <body>
                <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>请在微信客户端打开链接</h4></div></div></div>
                </body>
            </html>");
	}
}
*/

$member = pdo_fetch("SELECT id,total,from_user,parentid FROM " . tablename('tg_member') . " WHERE from_user='{$_W['openid']}' and uniacid='{$_W['uniacid']}'");
$blacklist = pdo_fetch("SELECT * FROM " . tablename('tg_blacklist') . " WHERE uniacid = '{$_W['uniacid']}' and openid = '{$_W['openid']}' and status = 1 ");
if (!empty($blacklist)) {
    $_GPC['do'] = $controller = 'member';
    $_GPC['ac'] = $action = 'blacklist';
//    echo "<script>alert('您已被拉黑！');</script>";
}
unset($blacklist);

if ((empty($member) || $member['parentid'] == -1) && !empty($_GPC['mid'])) {
    //message(intval($_GPC['sharenum']));
    if ($member['parentid'] == -1 && !empty($fans)) {
        $anum = 0;
    } else {
        $anum = intval($_GPC['mid']);
    }
    $data = array(
        'parentid' => $anum
    );
    pdo_update('tg_member', $data, array('id' => $member['id']));

}
$member = pdo_fetch("SELECT id,total,from_user,parentid,type,enable FROM " . tablename('tg_member') . " WHERE from_user='{$_W['openid']}' and uniacid='{$_W['uniacid']}'");
if ($member['parentid'] != 99) {
    $pmember = pdo_fetch("SELECT shopname FROM " . tablename('tg_member') . " WHERE id='{$member['parentid']}' ");
    $_Session['btitle'] = $pmember['shopname'];
}
if ($member['type'] == 1 && $member['enable'] == 1) {
    $_Session['mid'] = $member['id'];
}
checkMember();
//

    updategourp();

$date_run = (TIMESTAMP - $uniacid_tpl['last_run_time']) / 60;

//报表基础数据实时更新
/*
$system_task=pdo_fetch("select * from cm_core_settings where cm_core_settings.key='task_time'");
$system_run=(TIMESTAMP-$system_task['value'])/60;
if($system_run>10)
{
	ihttp_request('http://www.lexiangpingou.cn/minapi.php?op=run_data_order',null,null,1);
	pdo_update('core_settings',array('value'=>TIMESTAMP),array('key'=>'task_time'));
}
*/
/*
 * 自动签收  按公众号 每2个小时执行一次
 */

$system_run = (TIMESTAMP - $uniacid_tpl['receive_time']) / 60;
//file_put_contents(TG_DATA . "test.log", var_export($_W['uniacid'] . '自动签' . $uniacid_tpl['receive_time'] . '收时间' . $system_run . date('Y-m-d H:i:s', TIMESTAMP), true) . PHP_EOL, FILE_APPEND);

if ($system_run > 1) {
  //  file_put_contents(TG_DATA . "test.log", var_export($_W['uniacid'] . '执行自动签收' . date('Y-m-d H:i:s', TIMESTAMP), true) . PHP_EOL, FILE_APPEND);
    pdo_update('account_wechats', array('receive_time' => TIMESTAMP), array('uniacid' => $_W['uniacid']));
    check_membercash();
    check_jpmembercash();
}
/*
 * 极限单品自动签收  按公众号 每2个小时执行一次
 */

//$system_run = (TIMESTAMP - $uniacid_tpl['receive_time']) / 60;
//file_put_contents(TG_DATA . "test.log", var_export($_W['uniacid'] . '自动签' . $uniacid_tpl['receive_time'] . '收时间' . $system_run . date('Y-m-d H:i:s', TIMESTAMP), true) . PHP_EOL, FILE_APPEND);
//if ($system_run > 1) {
//    file_put_contents(TG_DATA . "test.log", var_export($_W['uniacid'] . '执行极限单品自动签收' . date('Y-m-d H:i:s', TIMESTAMP), true) . PHP_EOL, FILE_APPEND);
//    pdo_update('account_wechats', array('receive_time' => TIMESTAMP), array('uniacid' => $_W['uniacid']));
//    //极限单品自动签收
//
//}

//多商户自动结算订单
//check_settle($_W['uniacid']);

//阶梯团退款及自动退款
if ($_W['uniacid'] == 33) {
    check_levelrefund();
}
if ($uniacid_tpl['refund_type'] == 0) {
    check_levelrefund();
    if ($config['refund']['auto_refund'] == 1) {
        check_refund();
    }
} else {
    $date_refund = (TIMESTAMP - $uniacid_tpl['last_refund_time']) / 60;
    if ($date_refund > 2) {

        check_levelrefund();
        if ($config['refund']['auto_refund'] == 1) {
            check_refund();
        }
    }
}
//组团人数不足通知

$no_num_success_notice = pdo_fetch('SELECT * FROM cm_tg_task WHERE uniacid=:uniacid LIMIT 1', array(':uniacid' => $_W['uniacid']));
if (!empty($no_num_success_notice)) {
    no_num_success($no_num_success_notice['openid'], '团号:' . $no_num_success_notice['groupnumber'] . '的拼团还有' . $no_num_success_notice['lasttime'] . '就要到期了,还差' . $no_num_success_notice['neednum'] . '哟，快去叫上小伙伴一起拼吧', $no_num_success_notice['goodsname'], $no_num_success_notice['lasttime'], $no_num_success_notice['neednum'], $no_num_success_notice['remark'], app_url('order/group', array('tuan_id' => $no_num_success_notice['groupnumber'])));
    pdo_delete('tg_task', array('id' => $no_num_success_notice['id']));
}

//checkpaytransid();
checkpay($openid);
if ($_GPC['eid'] == 380) {
    $controller = 'member';
    $action = 'home';
}
if ($_GPC['eid'] == 382) {
    $controller = 'order';
    $action = 'order';
}
if ($_GPC['eid'] == 381) {
    $controller = 'order';
    $action = 'mygroup';
}

if (empty($controller) || empty($action)) {
    $_GPC['do'] = $controller = 'goods';
    $_GPC['ac'] = $action = 'list';
}
/*
if($_W['uniacid']==53&&$_GPC['isTZ']!='a')
{

	$aburl='http://lx.huodiesoft.com/app/index.php?i=826&c=entry&m=lexiangpingou&do=goods&ac=frame&url='.base64_encode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&isTZ=a');
	//message($_W['oo']);
	header("Location: ".$aburl);
	exit;
}
*/
$file = TG_APP . 'controller/' . $controller . '/' . $action . '.ctrl.php';
if (!file_exists($file)) {
    header("Location: index.php?i={$_W['uniacid']}&c=entry&do=goods&ac=list&m=lexiangpingou");
    exit;
}
if ($action != 'group' && $action != 'detail' && $action != 'orderconfirm' && $action != 'addmanage' && $action != 'createadd' && $action != 'cash') {

    unset($_SESSION['goodsid']);
    unset($_SESSION['tuan_id']);
    unset($_SESSION['groupnum']);
    //unset($_SESSION['optionid']);
    unset($_SESSION['num']);
}
//$tpl_res = pdo_fetch("SELECT * FROM " . tablename("tg_message_log") . " WHERE uniacid = :uniacid LIMIT 1", array(":uniacid" => $_W["uniacid"]));
//switch ($tpl_res["mess_tpl"]) {
//    case "result_type";
//        $content = unserialize($tpl_res["content"]);
//        $content = $content["data"];
//        $url = app_url("order/order", array("id" => $content["orderno"], "op" => "detail"));
//        unset($content["orderno"]);
//        $title = $content["first"];
//        unset($content["first"]);
//        $remark = $content["first"];
//        unset($content["remark"]);
////    var_dump($content);die();
//        $res = result_type($tpl_res["openid"], $title, $content["keyword2"], $remark, $url);
//        pdo_delete("tg_message_log",array("id"=>$tpl_res["id"]));
//        break;
//}
$tpl_res = pdo_fetch("SELECT * FROM " . tablename("tg_message_log") . " WHERE uniacid = :uniacid LIMIT 1", array(":uniacid" => $_W["uniacid"]));
switch ($tpl_res["mess_tpl"]) {
    case "result_type";
        $content = unserialize($tpl_res["content"]);
        $content = $content["data"];
        $url = app_url("order/order", array("id" => $content["orderno"], "op" => "detail"));
        unset($content["orderno"]);
        $title = $content["first"];
        unset($content["first"]);
        $remark = $content["first"];
        unset($content["remark"]);
        $res = result_type($tpl_res["openid"], $title, $content["keyword2"], $remark, $url);
        pdo_delete("tg_message_log", array("id" => $tpl_res["id"]));
        break;
    case "result_commission";
        $content = unserialize($tpl_res["content"]);
        $content = $content;
        $url = app_url("order/order", array("id" => $content["orderid"], "op" => "detail"));
        $title = $content["title"];
        $remark = $content["remark"];
        $res = result_type($tpl_res["openid"], $title, $content["message"], $remark);
        pdo_delete("tg_message_log", array("id" => $tpl_res["id"]));
        break;

    case "result_remind";
        $content = unserialize($tpl_res["content"]);
        $content = $content;
        $url = app_url("order/order", array("id" => $content["orderid"], "op" => "detail"));
        $title = $content["title"];
        $remark = $content["remark"];
        $res = result_type($tpl_res["openid"], $title, $content["message"], $remark , $url);
        pdo_delete("tg_message_log", array("id" => $tpl_res["id"]));
        break;

    case "result_kanjia";
        $content = unserialize($tpl_res["content"]);
        $content = $content;
        $url = app_url('goods/kanjia/detail_kj', array('id' => $content['kid']));
        $title = $content["title"];
        $remark = $content["remark"];
        $res = result_type($tpl_res["openid"], $title, $content["message"], $remark , $url);
        pdo_delete("tg_message_log", array("id" => $tpl_res["id"]));
        break;
}

require $file;

