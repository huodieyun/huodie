<?php

global $_GPC, $_W;

$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
wl_load()->model('setting');
$setting = setting_get_by_name("commander");
if (empty($uniacid)) {
    $uniacid = $_GPC['uniacid'];
}
if (empty($openid)) {
    $openid = $_W['openid'] = $_GPC['openid'];
}

$member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
$record = pdo_fetchall("select * from " .tablename('tg_commander_record') . " WHERE openid = '{$openid}' and uniacid = '{$uniacid}' and status = 0 ");
if ($record) {
    $apply = 0.00;
    foreach ($record as $item) {
        $apply += $item['price'];
    }
    pdo_update('tg_member', array('commander_apply' => $apply), array('id' => $member['id']));
}
unset($record);

$_Session['btitle'] = $member['nickname'];

$op = $_GPC['op'] ? $_GPC['op'] : 'display';

if ($op == 'display') {
    $orders = pdo_fetchall("select * from " .tablename('tg_commander') ." where uniacid = '{$uniacid}' and openid = '{$openid}' order by id desc ");
    foreach ($orders as &$v) {
        $goods = pdo_fetch("select * from " .tablename('tg_goods') ." where id = '{$v['gid']}' ");
        $v['goods'] = $goods;
        if (empty($goods['share_image'])) {
            $v['goods']['gimg'] = tomedia($goods['gimg']);
        } else {
            $v['goods']['gimg'] = tomedia($goods['share_image']);
        }
        unset($v);
    }
//    die(json_encode($orders));
}

if ($op == 'my_cash') {
    if (checksubmit('submit')){
        $name = $_GPC['name'];
        $name = floatval($name);
        if (empty($name)) {
            message('请填写金额', referer(), 'error');
        }
        $commander_settled = floatval($member['commander_settled']);
        $commander_apply = floatval($member['commander_apply']);
        $commander_withdraw = floatval($member['commander_withdraw']);
        $settle = $commander_settled - $commander_apply - $commander_withdraw;

        if ($settle < $name) {
            message('提现金额不能大于可提现金额', referer(), 'error');
        }
        if ($name < 1) {
            message('提现金额不能小于1元', referer(), 'error');
        }
        pdo_update('tg_member', array('commander_apply' => $commander_apply + $name), array('id' => $member['id']));
        $bdata = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $openid,
            'status' => 0,
            'createtime' => TIMESTAMP,
            'price' => $name
        );
        pdo_insert('tg_commander_record', $bdata);
        message('申请成功', app_url('order/commander' , array('op' => 'withdraw' , 'status' => 0)), 'success');
    }
}

if ($op == 'withdraw'){
    $id = $_GPC['id'];
    $status = $_GPC['status'];
    $con = " where uniacid = '{$uniacid}' and openid = '{$openid}' ";
    if (!empty($id)){
        $con .= " and id = " .$id;
    }
    if ($status == 0){
        $con .= " and status = 0 ";
    }elseif ($status == 1){
        $con .= " and status = 1 ";
    }elseif ($status == -1){
        $con .= " and status = -1 ";
    }
    $list = pdo_fetchall("select * from " .tablename('tg_commander_record') .$con);
//die(json_encode($list));
}

//if ($setting['apply'] == 1 && $member['apply_status'] != 1) {
//    header("location: " . app_url('order/comjob'));
//    exit;
//} else {
    include wl_template('order/commander');
//}

exit();

?>