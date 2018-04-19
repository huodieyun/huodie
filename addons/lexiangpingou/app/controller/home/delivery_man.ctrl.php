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
if ($merchant_id > 0 ){
    $merchant = pdo_fetch("select name from " .tablename('tg_merchant') ." where id = " .$merchant_id);
    $mname = $merchant['name'];
} else {
    $merchant = pdo_fetch("select name from " .tablename('account_wechats') ." where uniacid = " .$_W['uniacid']);
    $mname = $merchant['name'];
}
$list = pdo_fetch("select * from " . tablename('tg_delivery_man') . " where uniacid = '{$_W['uniacid']}' and openid = '{$_W['openid']}' and merchantid = '{$merchant_id}' ");
if ($_W['isajax']) {
    $member = pdo_fetch("select * from " . tablename('tg_member') . " where uniacid = '{$_W['uniacid']}' and from_user = '{$_W['openid']}' order by id asc ");
    $checkorder = pdo_fetch("select * from " . tablename('tg_delivery_man') . " where uniacid = '{$_W['uniacid']}' and openid = '{$_W['openid']}' and merchantid = '{$merchant_id}' ");
    if (!empty($checkorder)) {
        wl_json(0, '您已是派送员，请勿重复申请');
    }
    $merchant_id = $_GPC['merchant_id'];
    $bdata = array(
        'uniacid' => $_W['uniacid'],
        'openid' => $_W['openid'],
        'nickname' => $member['nickname'],
        'avatar' => $member['avatar'],
        'tel' => $_GPC['tel'],
        'status' => 1,
        'createtime' => TIMESTAMP,
        'merchantid' => $merchant_id
    );
    pdo_insert('tg_delivery_man', $bdata);
    wl_json(1, 'success');
}
include wl_template('home/delivery_man');
exit();
