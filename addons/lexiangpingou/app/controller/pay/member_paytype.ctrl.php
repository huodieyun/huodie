<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * paytype.ctrl
 * 支付方式控制器
 */

session_start();
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$uniacid = $_W['uniacid'];
$openid = $_W['openid'];

$pagetitle = '支付方式';

$orderno = $_GPC['orderno'];

if ($_W['isajax'] && $op == 'ajax') {

    $pars[':uniacid'] = $uniacid;
    $pars[':module'] = "lexiangpingou";
    $pars[':tid'] = $orderno;
    $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid` = :uniacid AND `module` = :module AND `tid` = :tid';
    $log = pdo_fetch($sql, $pars);
    $tag = unserialize($log['tag']);
    if (!empty($tag['transaction_id'])) {
        $recharge_id = intval($_GPC['id']);
        if ($recharge_id) {
            $recharge = pdo_get('tg_member_recharge' , array('id' => $recharge_id));
            if ($recharge['member_selling'] == $log['fee']) {
                $data['uniacid'] = $log['uniacid'];
                $data['openid'] = $log['openid'];
                $data['orderno'] = $log['tid'];
                $data['member_amount'] = $recharge['member_amount'];
                $data['member_selling'] = $log['fee'];
                $data['recharge_id'] = $recharge_id;
                $data['createtime'] = TIMESTAMP;
                pdo_insert('tg_member_recharge_record', $data);
                $member = pdo_fetch("select id , member_amount , member_balance from " .tablename('tg_member') ." where uniacid = '{$uniacid}' and from_user = '{$openid}' ");
                pdo_update('tg_member' , array('member_amount' => floatval($member['member_amount']) + floatval($recharge['member_amount']) , 'member_balance' => floatval($member['member_balance']) + floatval($recharge['member_amount'])) , array('id' => $member['id']));
//                pdo_update('core_paylog' , array('status' => 1) , array('plid' => $log['plid']));
                $res = 1;
                $message = '充值成功';
            } else {
                $res = 0;
                $message = '充值失败';
            }

        } else {
            $res = 0;
            $message = '传入参数错误';
        }

    } else {
        $res = 0;
        $message = '支付失败';
    }

    die(json_encode(array('errno' => $res, 'message' => $message)));
}

exit();
