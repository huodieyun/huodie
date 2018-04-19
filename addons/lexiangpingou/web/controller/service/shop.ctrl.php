<?php
defined('IN_IA') or exit('Access Denied');

pdo_query("CREATE TABLE IF NOT EXISTS `cm_account_wechats_merchant` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `uniacid` INT(11) NOT NULL COMMENT '公众号id',
  `uid` INT(11) NOT NULL COMMENT '操作员id',
  `username` VARCHAR(45) NOT NULL COMMENT '操作员名',
  `salename` VARCHAR(45) NOT NULL COMMENT '申请人',
  `old_merchant_num` INT(11) NOT NULL COMMENT '修改前商户上限数',
  `merchant_num` INT(11) NOT NULL COMMENT '修改后商户上限数',
  `old_smsnum` INT(11) NOT NULL DEFAULT 0 COMMENT '修改前短信条数',
  `smsnum` INT(11) NOT NULL DEFAULT 0 COMMENT '修改后短信条数',
  `month` INT(11) NOT NULL DEFAULT 0 COMMENT '购买月数',
  `old_expire_time` INT(11) NOT NULL COMMENT '修改前到期时间',
  `expire_time` INT(11) NOT NULL COMMENT '修改后到期时间',
  PRIMARY KEY (`id`),
  KEY `indx_uniacid` (`uniacid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

if (!pdo_fieldexists('account_wechats', 'is_https')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats') . " ADD `is_https` INT(2) NOT NULL DEFAULT 0 COMMENT '是否开通https';");
}
if (!pdo_fieldexists('account_wechats', 'is_applet')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats') . " ADD `is_applet` INT(2) NOT NULL DEFAULT 0 COMMENT '是否开通小程序';");
}
if (!pdo_fieldexists('account_wechats_merchant', 'is_https')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats_merchant') . " ADD `is_https` INT(2) NOT NULL DEFAULT 0 COMMENT '修改后https状态';");
}
if (!pdo_fieldexists('account_wechats_merchant', 'is_applet')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats_merchant') . " ADD `is_applet` INT(2) NOT NULL DEFAULT 0 COMMENT '修改后小程序开通状态';");
}
if (!pdo_fieldexists('account_wechats_merchant', 'is_merchant')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats_merchant') . " ADD `is_merchant` INT(2) NOT NULL DEFAULT 0 COMMENT '修改后多商户开通状态';");
}
if (!pdo_fieldexists('account_wechats_merchant', 'day')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats_merchant') . " ADD `day` INT(11) NOT NULL DEFAULT 0 COMMENT '多商户购买天数';");
}

if (!pdo_fieldexists('account_wechats', 'expire_time')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats') . " ADD `expire_time` INT( 11 ) COMMENT '多商户到期时间';");
}
if (!pdo_fieldexists('account_wechats', 'applet')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats') . " ADD `applet` INT(1) NOT NULL DEFAULT 1 COMMENT '多商户小程序是否到期';");
}

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if ($op == 'display') {
    $_W['page']['title'] = '多商户购买记录管理 - 列表';
    $cateTitle = '添加';
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $condition = " WHERE is_merchant = 1 ";

    if (!empty($_GPC['uniacidname'])) {
        $condition .= " and name like '%{$_GPC['uniacidname']}%'";
    }

    $sqlTotal = pdo_sql_select_count_from('account_wechats') . $condition;
    $sqlData = pdo_sql_select_all_from('account_wechats') . $condition . ' ORDER BY expire_time ';
    $account = pdo_pagination($sqlTotal, $sqlData, $params, 'id', $total, $page, $size);
    $pager = pagination($total, $page, $size);

    foreach ($account as &$value) {
        $merchant_now = pdo_fetchcolumn("select count(*) from " . tablename('tg_merchant') . " WHERE uniacid = {$value['uniacid']} and status = 1");
        $value['merchant_new'] = $value['merchant_num'] - $merchant_now;
    }

} elseif ($op == 'post') {

    if (checksubmit('submit')) {

        $uniacid = $_GPC['publicNum'];
        $month = empty($_GPC['month']) ? 0 : $_GPC['month'];
        $day = empty($_GPC['day']) ? 0 : $_GPC['day'];
        $salename = $_GPC['salename'];
        $merchant_num = empty($_GPC['merchant_num']) ? 0 : $_GPC['merchant_num'];
        $smsnum = empty($_GPC['smsnum']) ? 0 : $_GPC['smsnum'];
//        $time = strtotime(date('Y-m-d', strtotime('+'.$month." month".' +'.$day." day")));
        $https = intval($_GPC['is_https']);
        $applet = intval($_GPC['is_applet']);
        $is_merchant = intval($_GPC['is_merchant']);
        $merchant = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = '{$uniacid}'");
        $time = strtotime(date('Y-m-d', strtotime('+' . $month . " month" . ' +' . $day . " day")));

        if (!empty($merchant['expire_time']) && $merchant['expire_time'] < $time) {
            $dat = date('Y-m-d', $merchant['expire_time']);
            $expire_time = strtotime(date('Y-m-d', strtotime($dat . '+' . $month . " month" . ' +' . $day . " day")));
        } elseif(empty($merchant['expire_time'])) {
            $expire_time = strtotime(date('Y-m-d', strtotime('+' . $month . " month" . ' +' . $day . " day")));
        }elseif (!empty($merchant['expire_time']) && $merchant['expire_time'] > $time){
            $expire_time = $merchant['expire_time'];
        }

        $data = [
            'uniacid' => $uniacid,
            'uid' => $_W['user']['uid'],
            'username' => $_W['user']['username'],
            'salename' => $salename,
            'old_merchant_num' => $merchant['merchant_num'],
            'merchant_num' => $merchant['merchant_num'] + $merchant_num,
            'old_smsnum' => $merchant['smsnum'],
            'smsnum' => $merchant['smsnum'] + $smsnum,
            'month' => $month,
            'day' => $day,
            'old_expire_time' => $merchant['expire_time'],
            'expire_time' => $expire_time,
            'is_https' => $https,
            'is_applet' => $applet,
            'is_merchant' => $is_merchant
        ];

        $data1 = array();
        $data1['uniacid'] = $uniacid;
        $data1['merchant_batch'] = date('YmdHis', TIMESTAMP);
        $data1['createtime'] = TIMESTAMP;
        $data1['merchant_num'] = $merchant_num;
        $data1['endtime'] = strtotime(date('Y-m-d', strtotime('+' . $month . " month" . ' +' . $day . " day")));
        $data1['addtime'] = $month * 30 + $day;
        $data1['merchant_stock'] = $merchant_num;

        pdo_insert('tg_merchant_batch', $data1);
        pdo_insert('account_wechats_merchant', $data);
        pdo_update('account_wechats',
            array(
                'merchant_num' => $data['merchant_num'],
                'expire_time' => $data['expire_time'],
                'smsnum' => $data['smsnum'],
                'is_https' => $data['is_https'],
                'is_applet' => $data['is_applet'],
                'is_merchant' => $data['is_merchant']
            ), array('uniacid' => $uniacid));
        echo "<script>location.href=location.href;</script>";
    }


} elseif ($op == 'batch') {
    if (checksubmit('submit')) {

        $uniacid = $_GPC['publicNum'];
        $month = empty($_GPC['month']) ? 0 : $_GPC['month'];
        $day = empty($_GPC['day']) ? 0 : $_GPC['day'];
        $salename = $_GPC['salename'];
        $batch = $_GPC['batch'];
//        $time = ($month * 30 + $day) * 24 * 60 * 60;
        $merchant = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = '{$uniacid}'");

        $b = pdo_fetch("select * from " . tablename('tg_merchant_batch') . " where uniacid = '{$uniacid}' and merchant_batch = '{$batch}'");
        if (empty($b['endtime']) || $b['endtime'] < TIMESTAMP) {
            $b['endtime'] = strtotime(date('Y-m-d', strtotime('+' . $month . " month" . ' +' . $day . " day")));
        } else {
            $dat = date('Y-m-d', $b['endtime']);
            $b['endtime'] = strtotime(date('Y-m-d', strtotime($dat . '+' . $month . "month" . ' +' . $day . " day")));
        }
        $data1 = array();
        $data1['addtime'] = $b['addtime'] + $month * 30 + $day;
        $data1['endtime'] = $b['endtime'];
        pdo_update('tg_merchant_batch', $data1, array('merchant_batch' => $batch));
        pdo_update('tg_merchant', array('status' => 1), array('uniacid' => $uniacid, 'merchant_batch' => $batch, 'status' => 4));
        if ($merchant['expire_time'] < $b['endtime']) {
            pdo_update('account_wechats', array('expire_time' => $b['endtime']), array('uniacid' => $uniacid));
        }
        echo "<script>location.href=location.href;</script>";
    }
} elseif ($op == 'ajax') {
    $id = $_GPC['id'];
    $acc = pdo_fetch("select name,uniacid,merchant_num,smsnum,expire_time,is_https,is_applet,is_merchant,vip,endtime from " . tablename('account_wechats') . " where uniacid = '{$id}' ");
    $merchant_now = pdo_fetchcolumn("select count(*) from " . tablename('tg_merchant') . " WHERE uniacid = {$acc['uniacid']} and status = 1");
    $acc['merchant_now'] = $acc['merchant_num'] - $merchant_now;
    $acc['expire_time'] = date('Y-m-d', $acc['expire_time']);
    $acc['endtime'] = date('Y-m-d', $acc['endtime']);
    echo json_encode($acc);
    exit;
}

//多商户 VIP 立即到期  expire
//传人uniacid  多商户 1 VIP 2 状态
//返回成功与否状态
if ($op == 'expire') {

    $uniacid = $_GPC['uniacid'];
    $status = $_GPC['status'];
    $salename = $_GPC['salename'];
    $acc = pdo_fetch("select name,uniacid,merchant_num,smsnum,expire_time,is_https,is_applet,is_merchant,vip from " . tablename('account_wechats') . " where uniacid = '{$id}' ");
    if ($status == 1) {
        $is_merchant = -1;
        $re = pdo_update('account_wechats',
            array(
                'expire_time' => strtotime(date('Y-m-d', TIMESTAMP)),
                'applet' => 1,
                'is_applet' => 0,
                'is_merchant' => 0
            ), array('uniacid' => $uniacid));
    } elseif ($status == 1) {
        $is_merchant = -2;
        $acc['expire_time'] = $acc['endtime'];
        $re = pdo_update('account_wechats',
            array(
                'endtime' => strtotime(date('Y-m-d', TIMESTAMP)),
                'vip' => 0
            ), array('uniacid' => $uniacid));
    }

    $data = [
        'uniacid' => $uniacid,
        'uid' => $_W['user']['uid'],
        'username' => $_W['user']['username'],
        'salename' => $salename,
//        'old_merchant_num' => $merchant['merchant_num'],
//        'merchant_num' => $merchant['merchant_num'] + $merchant_num,
//        'old_smsnum' => $merchant['smsnum'],
//        'smsnum' => $merchant['smsnum'] + $smsnum,
//        'month' => $month,
//        'day' => $day,
        'old_expire_time' => $acc['expire_time'],
        'expire_time' => strtotime(date('Y-m-d', TIMESTAMP)),
//        'is_https' => $https,
//        'is_applet' => $applet,
        'is_merchant' => $is_merchant
    ];


    $res = pdo_insert('account_wechats_merchant', $data);
    die(json_encode(array('status' => ($res && $re))));

}

if ($op == 'buy_history') {

//    $acc = pdo_fetchall("select * from " .tablename('account_wechats') . " where is_merchant = 1");
//    foreach ($acc as $item) {
//        $num = pdo_fetchcolumn("select id from " .tablename('tg_merchant') ." where uniacid = '{$item['uniacid']}' and status = 1 ");
//        $merchant = pdo_fetchall("select * from " .tablename('account_wechats_merchant') ." where uniacid = '{$item['uniacid']}' ");
//        $data = array();
//        $data['uniacid'] = $item['uniacid'];
//        $data['merchant_batch'] = date('YmdHis' , '1501516800');
//        $data['createtime'] = TIMESTAMP;
//        $data['merchant_num'] = $item['merchant_num'];
//        $data['endtime'] = $item['expire_time'];
//        $data['addtime'] = $merchant['month'] * 30 + $merchant['day'];
//        $data['merchant_stock'] = $item['merchant_num'] - $num;
//
//        pdo_insert('tg_merchant_batch' , $data);
//        pdo_update('tg_merchant' , array('merchant_batch' => $data['merchant_batch']) , array('uniacid' => $data['uniacid']));
//    }
    $uniacid = $_GPC['uniacid'];
    $page = $_GPC['page'];
    $size = 10;
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $list = pdo_fetchall("select * from " . tablename('tg_merchant_batch') . " where uniacid = '{$uniacid}' order by endtime limit " . ($page - 1) * $size . "," . $size);
    $total = pdo_fetchcolumn("select id from " . tablename('tg_merchant_batch') . " where uniacid = '{$uniacid}' ");
    foreach ($list as &$value) {

    }
    $pager = pagination($total, $page, $size);
}

include wl_template('service/shop');
exit();