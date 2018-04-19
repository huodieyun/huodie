<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/5/17
 * Time: 16:25
 */
global $_W, $_GPC;
$pagetitle = $config['tginfo']['sname'];
$result = $_GPC['result'];
$merchant_id = $_GPC['merchant_id'];
$storelist = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid = '{$_W['uniacid']}' and status = 1 and merchantid = '{$merchant_id}' ");
if ($_W['isajax']) {
    $member = pdo_fetch("select * from " . tablename('tg_member') . " where uniacid = '{$_W['uniacid']}' and openid = '{$_W['openid']}' order by id asc ");
    $checkorder = pdo_fetch("select * from " . tablename('tg_saler') . " where uniacid = '{$_W['uniacid']}' and openid = '{$_W['openid']}' and merchantid = '{$merchant_id}' ");
    if (!empty($checkorder)) {
        wl_json(0, '您已是核销员，请勿重复申请');
    }
    $merchant_id = $_GPC['merchant_id'];
    $bdata = array(
        'uniacid' => $_W['uniacid'],
        'openid' => $_W['openid'],
        'storeid' => $_GPC['id'] . ",",
        'nickname' => $member['nickname'],
        'avatar' => $member['avatar'],
        'open' => 2,
        'status' => 1,
        'createtime' => TIMESTAMP,
        'merchantid' => $merchant_id
    );
    pdo_insert('tg_saler', $bdata);
    wl_json(1 , 'success');
}
include wl_template('home/info_saler');
exit();
