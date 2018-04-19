<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * addmanage.ctrl
 * 我的地址控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('address');

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

session_start();
$_SESSION['goodsid'] = $_GPC['goodsid'];
$goodsid = $_SESSION['goodsid'];
$tuan_id = $_GPC['tuan_id'];
$groupnum = $_GPC['groupnum'];
$selltype = intval($_GPC['selltype']);
$kid = intval($_GPC['kid']);

$openid = $_W['openid'];
$pagetitle = '我的收货地址';
wl_load()->model('setting');
//权限控制
$tid=8154;
//权限控制
if (empty($_W['openid'])) {
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
wl_load()->model('functions');
$isshop=$_GPC['isshop'];
$checkfunction=checkfunc(8154);
$setting=setting_get_by_name("autoaddress");
$autoaddr=0;
if($checkfunction['status']&&$setting['autoaddr'])
{
	$autoaddr=1;
}
if (empty($isshop)) {
    if ($goodsid) {
        $bakurl = app_url('order/orderconfirm', array('id' => $goodsid, 'tuan_id' => $tuan_id, 'groupnum' => $groupnum, 'num' => $_GPC['num']));
        if ($selltype == 10) {
            $bakurl = app_url('order/kj_orderconfirm', array('id' => $goodsid, 'groupnum' => $groupnum, 'kid' => $kid , 'selltype' => $selltype));
        }
    } else {
        $bakurl = app_url('member/addmanage');
    }
} else {
    $bakurl = app_url('order/shoporderconfirm');
}


if($op == 'display'){
	
	$pagetitle = '地址列表';
	$address = address_get_list("openid = '{$openid}' and uniacid='{$_W['uniacid']}' and ctype=0 and openid<>''");
	if($autoaddr)
	{
		$address = address_get_list("openid = '{$openid}' and uniacid='{$_W['uniacid']}' and ctype=1 and openid<>''");
	}
	include wl_template('address/addmanage',array('isshop'=>$isshop));
}

if($op == 'select'){
	$id = $_GPC['id'];

	address_set_by_id($id,$openid);
	$logdata=array(
		'orderno'=>$bakurl,
		'log'=>'地址',
		'from'=>'地址'
		);
		pdo_insert('tg_log', $logdata);
	header("location:".$bakurl);
}
exit();