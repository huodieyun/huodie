<?php

global $_GPC, $_W;
wl_load()->model('setting');
//$_W['openid']='oHg6Twk42LyJXFz6sc3QW9gB9U0Q';
//$_W['uniacid']=511;

$op = $_GPC['op'];
$op = !empty($op) ? $op : 'apply';


$config = tgsetting_load();
//$member = pdo_fetch("SELECT id,total,from_user,parentid,enable FROM " . tablename('tg_member') . " WHERE from_user='{$_W['openid']}' and uniacid='{$_W['uniacid']}'");
$member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user='{$_W['openid']}' and uniacid='{$_W['uniacid']}'");
$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
//$uniacid = $_GPC['uniacid'];
//$openid = $_GPC['openid'];

$group = pdo_fetch("select * from " . tablename('tg_member_group') . " where openid = '" . $openid . "'");
$commission = $group['commission'];

//$apply_total = pdo_fetchcolumn("select SUM(price) from " . tablename('tg_cashrecord')
//    . " where uniacid = '{$uniacid}' and account = 0 and openid in ( select openid from "
//    . tablename('tg_member_group_detail') . " where uniacid = '{$uniacid}' and parentid = ( select id from "
//    .tablename('tg_member_group') ." where uniacid = '{$uniacid}' and openid = '{$openid}' and fail = 0 ) ) ");
//$apply_total = $apply_total * $commission * 0.01;

$apply = pdo_fetchcolumn("select SUM(sell_total) from " . tablename('tg_member')
    . " where uniacid = '{$uniacid}' and openid in ( select openid from "
    . tablename('tg_member_group_detail') . " where uniacid = '{$uniacid}' and parentid = ( select id from "
    .tablename('tg_member_group') ." where uniacid = '{$uniacid}' and openid = '{$openid}' and fail = 0 ) ) ");
$apply_total = ($apply - $member['balance_sell_total'] + $member['sell_total']) * $commission * 0.01;
$get_total = pdo_fetchcolumn("select SUM(cm_tg_member_group_commission.get) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$openid}' and status = 1");
$gets_total = pdo_fetchcolumn("select SUM(cm_tg_member_group_commission.get) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$openid}'  and status = 2");
$give_total = pdo_fetchcolumn("select SUM(give) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$openid}'");

if ($op == 'apply'){

//    $id = $group['id'];
//    $page = $_GPC['page'];
//    $size = 10;
//    $page = !empty($page) ? intval($_GPC['page']) : 1;
//    $orderby = ' order by ptime';
//
//    $sql = "select * from " . tablename('tg_cashrecord') . " where uniacid = '{$uniacid}' and account = 0 ";
//    $sql .= " and openid in ( select openid from " . tablename('tg_member_group_detail') . " where uniacid = '{$uniacid}' and parentid = '{$id}' ) ";
//    $total = pdo_fetchcolumn("select count(*) from " . tablename('tg_cashrecord')
//        . " where uniacid = '{$uniacid}' and account = 0 and openid in ( select openid from "
//        . tablename('tg_member_group_detail')
//        . " where uniacid = '{$uniacid}' and parentid = '{$id}' ) ");
//
//    $sql .= $orderby . " limit " . ($page - 1) * $size . " , " . $size;
//    $record = pdo_fetchall($sql);
//
//    $pager = pagination($total, $page, $size);
//
//    foreach ($record as &$value) {
//        $value['member'] = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = '{$uniacid}' and from_user = '{$value['openid']}' ");
//        $value['commission'] = $value['price'] * $commission * 0.01;
//    }
//
//    if (checksubmit('submit')) {
//
//        $sql = "select * from " . tablename('tg_cashrecord') . " where uniacid = '{$uniacid}' and account = 0 ";
//        $sql .= " and openid in ( select openid from " . tablename('tg_member_group_detail') . " where uniacid = '{$uniacid}' and parentid = '{$id}' ) ";
//        $record = pdo_fetchall($sql);
//        $data = array();
//        $data['group_no'] = "GROUP" .date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
//        $data['uniacid'] = $uniacid;
//        $data['openid'] = $openid;
//        $data['group_id'] = $id;
//        $data['apply'] = 0.00;
//        $data['commission'] = $commission;
//        foreach ($record as &$value) {
//            $data['cash_id'] .= $value['id'] ."|";
//            $data['apply'] += floatval($value['price'] * $commission * 0.01);
//        }
//        $data['createtime'] = TIMESTAMP;
//        $data['updatetime'] = TIMESTAMP;
//        $data['get'] = $data['apply'];
////    die(json_encode($data));
//        pdo_insert('tg_member_group_commission' , $data);
//        $commission_id = pdo_insertid();
//        foreach ($record as &$value) {
//            pdo_update('tg_cashrecord' , array('account' => 1 , 'account_time' => TIMESTAMP , 'commission_id' => $commission_id) , array('id' => $value['id']));
//        }
//        message('申请成功', app_url('member/leader' , array('op' => 'withdraw' , 'id' => $commission_id)), 'success');
//    }
    if (checksubmit('submit')){
        if ($apply < 1){
            message('可申请提现金额不足', app_url('member/leader' , array('op' => 'apply')), 'error');
        }
        $id = $group['id'];
        $balance = intval($_GPC['name']);
        if ($balance < 1){
            message('请输入大于1元的金额', app_url('member/leader' , array('op' => 'apply')), 'error');
        }
        $data = array();
        $data['group_no'] = "GROUP" .date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
        $data['uniacid'] = $uniacid;
        $data['openid'] = $openid;
        $data['group_id'] = $id;
        $data['apply'] = $balance;
        $data['commission'] = $commission;
        $data['createtime'] = TIMESTAMP;
        $data['updatetime'] = TIMESTAMP;
        $data['get'] = $data['apply'];
        $balance_sell_total = $balance / ($commission * 0.01);

        pdo_insert('tg_member_group_commission' , $data);
        pdo_update('tg_member' , array('balance_sell_total' => $member['balance_sell_total'] + $balance_sell_total) , array('id' => $member['id']));
        message('申请成功', app_url('member/leader' , array('op' => 'withdraw' , 'status' => 1)), 'success');
    }
}

if ($op == 'withdraw'){
    $id = $_GPC['id'];
    $status = $_GPC['status'];
    $con = " where uniacid = '{$uniacid}' and openid = '{$openid}' ";
    if (!empty($id)){
        $con .= " and id = " .$id;
    }
    if ($status == 1){
        $con .= " and status = 1 ";
    }elseif ($status == 2){
        $con .= " and status = 2 ";
    }elseif ($status == 3){
        $con .= " and status = 3 ";
    }
    $list = pdo_fetchall("select * from " .tablename('tg_member_group_commission') .$con);
//die(json_encode($list));
}

if ($member['enable'] != 1) {

    header("location: " . app_url('order/infojob'));
    exit;
} else {
    include wl_template('member/leader');
}

exit();

?>