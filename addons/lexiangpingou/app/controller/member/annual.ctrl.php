<?
global $_GPC, $_W;
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

$fans = setting_get_by_name("fans_data");
if($fans['fans_data_spring'] == 1){
    $data = pdo_fetch("SELECT * FROM " . tablename('fans_data_spring') . " WHERE uniacid={$uniacid} and openid='{$openid}'");

}else{
    $data = pdo_fetch("SELECT * FROM " . tablename('fans_data') . " WHERE uniacid={$uniacid} and openid='{$openid}'");

}
//$data = json_encode($data);
$uniacname = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid={$uniacid}");
$uniacname = $uniacname['name'];
wl_load()->model('setting');
$setting = setting_get_by_name("tginfo");
include wl_template('member/annual');
exit();
?>