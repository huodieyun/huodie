<?
global $_GPC, $_W;
wl_load()->model('setting');
$setting = setting_get_by_name("wholesale");

$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
if (empty($uniacid)) {
    $uniacid = $_GPC['uniacid'];
}
if (empty($openid)) {
    $openid = $_W['openid'] = $_GPC['openid'];
}

$member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");

$op = $_GPC['op'] ? $_GPC['op'] : 'display';

if (checksubmit()){
    $data['name'] = $_GPC['name'];
    $data['mobile'] = $_GPC['mobile'];
    $data['wholesaler_apply'] = 1;
    $data['wholesaler_apply_time'] = TIMESTAMP;
    $res = pdo_update('tg_member' , $data , array('uniacid' => $uniacid , 'openid' => $openid));
    if ($res) {
        $tip = '申请成功';
        echo "<script>alert('" . $tip . "');location.href='" . app_url('member/wholesale') . "';</script>";
    } else {
        $tip = '申请失败';
        echo "<script>alert('" . $tip . "');location.href='" . app_url('member/wholesale') . "';</script>";
    }
    die(json_encode($data));
}

include wl_template('member/wholesale');
exit();
?>