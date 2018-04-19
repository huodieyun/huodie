<?
global $_GPC, $_W;

$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
$pagetitle = $_W['page']['title'] .' - 在线充值';
$op = $_GPC['op'] ? $_GPC['op'] : 'display';

if ($op == 'display') {

    $member = pdo_get('tg_member' , array('uniacid' => $uniacid , 'from_user' => $openid));
    if ($member['member_status'] != 1) {
        header("location: " . app_url('member/member_apply'));
        exit;
    }
    $list = pdo_getall('tg_member_recharge' , array('uniacid' => $uniacid , 'status' => 1));

    $record_list = pdo_getall('tg_member_recharge_record' , array('uniacid' => $uniacid , 'openid' => $openid));
    foreach ($record_list as &$item) {
        $item['createtime'] = date('Y-m-d H:i:s' , $item['createtime']);
        unset($item);
    }

}


include wl_template('member/member_charge');
exit();
?>