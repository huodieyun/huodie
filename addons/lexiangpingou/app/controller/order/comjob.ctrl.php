<?
global $_GPC, $_W;
wl_load()->model('setting');
$setting = setting_get_by_name("commander");

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
    $data['addmobile'] = $_GPC['mobile'];
    $data['apply'] = 1;
    $data['apply_status'] = 0;
    $data['apply_time'] = TIMESTAMP;
    $res = pdo_update('tg_member' , $data , array('uniacid' => $uniacid , 'openid' => $openid));
    if ($res) {
        $tip = '申请成功';
        echo "<script>alert('" . $tip . "');location.href='" . app_url('order/comjob') . "';</script>";
    } else {
        $tip = '申请失败';
        echo "<script>alert('" . $tip . "');location.href='" . app_url('order/comjob') . "';</script>";
    }
    die(json_encode($data));
}
if ($op == 'apply') {
    die(json_encode(1));
}

include wl_template('order/comjob');
exit();
?>