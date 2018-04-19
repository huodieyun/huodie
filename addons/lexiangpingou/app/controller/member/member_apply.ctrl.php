<?
global $_GPC, $_W;
wl_load()->model('setting');
$setting = setting_get_by_name("kaiguan");
$member_rules = setting_get_by_name("member");
$pagetitle = $_W['page']['title'] .' - 会员申请';
$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
if (empty($uniacid)) {
    $uniacid = $_GPC['uniacid'];
}
if (empty($openid)) {
    $openid = $_W['openid'] = $_GPC['openid'];
}

$member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
if ($member['birthday']) {
    $member['birthday'] = date('Y-m-d', $member['birthday']);
} else {
    $member['birthday'] = '';
}

$op = $_GPC['op'] ? $_GPC['op'] : 'display';

if (checksubmit()){
    $data['name'] = $_GPC['name'];
    $data['addmobile'] = $_GPC['mobile'];
    $birthday = $_GPC['birthday'];
    $data['birthday'] = strtotime($birthday);
    $data['member_apply'] = 1;
    $data['member_status'] = 1;
    $data['member_apply_time'] = TIMESTAMP;
    $data['member_check_time'] = TIMESTAMP;
    $res = pdo_update('tg_member' , $data , array('uniacid' => $uniacid , 'openid' => $openid));
    if ($res) {
        $tip = '申请成功';
        echo "<script>alert('" . $tip . "');location.href='" . app_url('member/member_charge') . "';</script>";
    } else {
        $tip = '申请失败';
        echo "<script>alert('" . $tip . "');location.href='" . app_url('member/member_apply') . "';</script>";
    }
    die(json_encode($data));
}


include wl_template('member/member_apply');
exit();
?>