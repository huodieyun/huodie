<?php

defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$uniacid = $_W['uniacid'];
$pindex = max(1, intval($_GPC['page']));
$psize = 20;
//*
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_hexiao` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uniacid` INT(10) NOT NULL DEFAULT 0 COMMENT '商家公众账号id',
  `sid` INT(11) NOT NULL DEFAULT 0 COMMENT '核销员id',
  `did` INT(11) NOT NULL DEFAULT 0 COMMENT '派送员id',
  `storeid` INT(11) NOT NULL DEFAULT 0 COMMENT '核销门店id',
  `openid` VARCHAR(50) NOT NULL COMMENT '核销openid',
  `nickname` VARCHAR(50) NOT NULL COMMENT '昵称',
  `oid` INT(11) NOT NULL DEFAULT 0 COMMENT 'orderid',
  `cid` INT(11) NOT NULL DEFAULT 0 COMMENT 'collectid',
  `gid` INT(11) NOT NULL DEFAULT 0 COMMENT '商品id',
  `commissiontype` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '佣金提成模式',
  `commission` DOUBLE(10,2) NOT NULL COMMENT '提成佣金',
  `price` DOUBLE(10,2) NOT NULL COMMENT '可提成佣金',
  `status` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '是否计算提成 1计算',
  `refund` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '是否退款 1退货 2部分退款',
  `refund_storeid` INT(11) NOT NULL DEFAULT 0 COMMENT '退货核销门店id',
  `refund_openid` VARCHAR(50) NOT NULL COMMENT '退货核销员openid',
  `refund_nickname` VARCHAR(50) NOT NULL COMMENT '退货核销员昵称',
  `createtime` INT(11) NOT NULL DEFAULT 0 COMMENT '核销时间',
  `refundtime` INT(11) NOT NULL DEFAULT 0 COMMENT '退款时间',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_sid` (`sid`),
  KEY `idx_did` (`did`),
  KEY `idx_openid` (`openid`),
  KEY `idx_oid` (`oid`),
  KEY `idx_cid` (`cid`),
  KEY `idx_gid` (`gid`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

if (!pdo_fieldexists('tg_saler', 'withdraw')) {
    pdo_query("ALTER TABLE `cm_tg_saler` ADD `withdraw` DOUBLE(10,2) NOT NULL DEFAULT 0 COMMENT '已提现佣金';");
}

if (!pdo_fieldexists('tg_delivery_man', 'withdraw')) {
    pdo_query("ALTER TABLE `cm_tg_delivery_man` ADD `withdraw` DOUBLE(10,2) NOT NULL DEFAULT 0 COMMENT '已提现佣金';");
}

if (!pdo_fieldexists('tg_collect', 'com_type')) {
    pdo_query("ALTER TABLE `cm_tg_collect` ADD `com_type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '佣金模式';");
}

pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_hexiao_record` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uniacid` INT(10) NOT NULL COMMENT '商家公众账号id',
  `openid` VARCHAR(50) NOT NULL COMMENT 'openid',
  `nickname` VARCHAR(50) NOT NULL COMMENT '昵称',
  `price` DOUBLE(10,2) NOT NULL DEFAULT 0 COMMENT '申请提现佣金',
  `type` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '提现类型1核销，2派送',
  `web_cash` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '0线上发放1后台添加线下打款',
  `status` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '审核状态0待审，1通过，-1不通过',
  `createtime` INT(11) NOT NULL DEFAULT 0 COMMENT '申请时间',
  `updatetime` INT(11) NOT NULL DEFAULT 0 COMMENT '审核时间',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_openid` (`openid`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

//*/

wl_load()->model('setting');
$setting = setting_get_by_name("hexiao");

if ($op == 'base') {
    if (checksubmit()) {
        $saler_time = $_GPC['saler_time'];
        if ($saler_time < 0) {
            $saler_time = 0;
        }
        if (empty($setting)) {
            $value = array('saler_time' => $saler_time);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'hexiao',
                'value' => serialize($value)
            );
            setting_insert($data);
        } else {
            $setting['saler_time'] = $saler_time;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'hexiao', 'uniacid' => $_W['uniacid']));
        }
        $tip = '修改提现时间成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/saler/base') . "';</script>";
        exit;
    }
}

if ($op == 'ajax') {

    $saler = intval($_GPC['saler']);
    $saler_time = intval($_GPC['saler_time']);
    if (empty($setting)) {
        if ($saler) {
            $value = array('saler' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'hexiao',
                'value' => serialize($value)
            );
            setting_insert($data);
        }
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    } else {
        if ($saler) {
            $status = intval($setting['saler']) == 1 ? 2 : 1;
            $setting['saler'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'hexiao', 'uniacid' => $_W['uniacid']));
        }
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    }
}

if ($op == 'display') {

    /*搜索条件*/
//    $status = intval($_GPC['groupstatus']);
//    $type = intval($_GPC['type']);
//    $time = $_GPC['time'];
//    if (!$type && (empty($starttime) || empty($endtime))) {
//        $starttime = strtotime('-1 month');
//        $endtime = time();
//    }
//    if ($type && !empty($_GPC['time'])) {
//        $starttime = strtotime($_GPC['time']['start']);
//        $endtime = strtotime($_GPC['time']['end']) + 86399;
//        if ($type == 1) {
//            $condition .= " AND  apply_time >= {$starttime} AND apply_time <= {$endtime} ";
//        }else {
//            $condition .= " AND  shenhetime >= {$starttime} AND shenhetime <= {$endtime} ";
//        }
//    }
//
//    if ($status < 9) {
//        if ($status == 1) {
//            $condition .= " and apply_status = 1 ";
//        } elseif ($status == 0) {
//            $condition .= " and apply_status = 0 ";
//        } elseif ($status == -1) {
//            $condition .= " and apply_status = -1 ";
//        }
//    }
    if (!empty($_GPC['keyword'])) {
        $keyword = $_GPC['keyword'];
        $condition .= " AND nickname like '%{$keyword}%' ";
    }
    $members = pdo_fetchall("SELECT * FROM " . tablename('tg_saler') . " WHERE uniacid = {$_W['uniacid']} AND status = 1 and merchantid = {$_W['user']['merchant_id']} $condition ORDER BY createtime asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($members as &$member) {
        $hexiao = pdo_getall('tg_hexiao', array('sid' => $member['id']));
        $saler_total = pdo_fetchcolumn("select sum(price) from " . tablename('tg_hexiao') . " where sid = {$member['id']} and status = 1 ");
        $create = TIMESTAMP - $setting['saler_time'] * 24 * 60 * 60;
        $cash_total = pdo_fetchcolumn("select sum(price) from " . tablename('tg_hexiao') . " where sid = {$member['id']} and status = 1 and refund = 0 and createtime < {$create} ");
        $apply_total = pdo_fetchcolumn("SELECT sum(price) FROM " . tablename('tg_hexiao_record') . " WHERE type = 1 AND status = 0 and openid = '{$member['openid']}' ");
//        $withdraw_total = pdo_fetchcolumn("select sum(price) from " .tablename('tg_hexiao_record') ." where type = 1 and status = 1 ");
        foreach ($hexiao as $item) {
            if ($item['status'] == 1) {
                if ($item['commissiontype'] == 1) {

                }
            }
        }
        $member['saler_total'] = $saler_total;
        $member['cash_total'] = $cash_total;
        $member['apply_total'] = $apply_total;
        $member['withdraw_total'] = $member['withdraw'];
        unset($saler_total);
        unset($cash_total);
        unset($apply_total);
        unset($member);
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_saler') . " WHERE uniacid = {$_W['uniacid']} and merchantid = {$_W['user']['merchant_id']} AND status = 1 $condition ");
    $pager = pagination($total, $pindex, $psize);

}

if ($op == 'group_detail') {
    $sid = intval($_GPC['sid']);

    $orderlist = pdo_fetchall("select * from " . tablename('tg_hexiao') . " where uniacid = {$_W['uniacid']} and sid = {$sid} and status = 1 LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($orderlist as &$item) {
        if ($item['cid'] > 0) {
            $collect = pdo_fetch("select orderno,goodsname from " . tablename('tg_collect') . " where id = {$item['cid']} ");
            $item['orderno'] = $collect['orderno'];
            $item['goodsname'] = $collect['goodsname'];
        } else {
            $collect = pdo_fetch("select orderno,goodsname from " . tablename('tg_order') . " where id = {$item['oid']} ");
            $item['orderno'] = $collect['orderno'];
            $item['goodsname'] = $collect['goodsname'];
        }

        $store = pdo_get('tg_store', array('id' => $item['storeid']));
        $item['store'] = $store['storename'];
        $store = pdo_get('tg_store', array('id' => $item['refund_storeid']));
        $item['refund_store'] = $store['storename'];
        $createtime = intval(TIMESTAMP - (intval($setting['saler_time']) * 24 * 60 * 60));
        if ($item['refund'] == 1) {
            $item['refund'] = '已退款';
        } elseif ($item['refund'] == 0 && intval($item['createtime']) <= $createtime) {
            $item['refund'] = '可提现';
        } elseif ($item['refund'] == 0 && intval($item['createtime']) > $createtime) {
            $item['refund'] = '未到提现期';
        }
        $item['createtime'] = date('Y-m-d H:i', $item['createtime']);
        if ($item['refundtime'] > 0) {
            $item['refundtime'] = date('Y-m-d H:i', $item['refundtime']);
        } else {
            $item['refundtime'] = '';
        }
        unset($item);
    }
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_hexiao') . " where uniacid = {$_W['uniacid']} and sid = {$sid} and status = 1 ");
    $pager = pagination($total, $pindex, $psize);
}

if ($op == 'withdraw') {
    $status = $_GPC['status'];
    $time = $_GPC['time'];
    $con = " where uniacid = '{$uniacid}' ";
    if (!empty($_GPC['keyword'])) {
        $keyword = $_GPC['keyword'];
        $con .= " AND openid in ( select from_user from " . tablename('tg_member') . " where uniacid = {$uniacid} and nickname like '%{$keyword}%' ) ";
    }
    if ($status == 0) {
        $con .= " and status = 0 ";
    } elseif ($status == 1) {
        $con .= " and status = 1 ";
    } elseif ($status == -1) {
        $con .= " and status = -1 ";
    }
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if ($time) {
        $start = strtotime($time['start']);
        $end = strtotime($time['end'] . " +1 day");
        $con .= " and createtime > '{$start}' and createtime < '{$end}' ";
    }
//    die(json_encode($con));
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_hexiao_record') . $con . " and type = 1 ORDER BY createtime LIMIT " . ($pindex - 1) * $psize . "," . $psize);
    foreach ($list as &$item) {
        $item['member'] = pdo_fetch("select nickname,avatar from " . tablename('tg_member') . " where uniacid = '{$uniacid}' and from_user = '{$item['openid']}' ");
        unset($item);
    }
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_hexiao_record') . $con);
    $pager = pagination($total, $pindex, $psize);
}

if ($op == 'cash') {
    load()->func('communication');
    load()->model('account');
    wl_load()->model('setting');

    $id = intval($_GPC['id']);
    $set = setting_get_by_name('refund');


    $m = pdo_fetch("SELECT * FROM " . tablename('tg_hexiao_record') . " WHERE id = {$id} ");
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_saler') . " WHERE id = '{$m['sid']}' and uniacid = {$_W['uniacid']} ");
    $pri = $m['price'] + $member['withdraw'];

    $url5 = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    $apikey = $set['apikey'];
    $pars = array();
    $pars['mch_appid'] = $_W['account']['key'];//身份标识（appid）
    $pars['mchid'] = $set['mchid'];//微信支付商户号(mchid)
    $pars['nonce_str'] = random(32);
    $pars['partner_trade_no'] = random(10) . date('Ymd') . random(3);
    $pars['openid'] = $m['openid'];
    $pars['check_name'] = "NO_CHECK";
    $pars['amount'] = $m['price'] * 100;
    $pars['desc'] = $_W['account']['name'] . "核销佣金发放";
    $pars['spbill_create_ip'] = "121.43.108.152";
    ksort($pars, SORT_STRING);
    $string1 = '';
    foreach ($pars as $k => $v) {
        $string1 .= "{$k}={$v}&";
    }
    $string1 .= "key={$apikey}";//商户支付秘钥（API秘钥)
    $pars['sign'] = strtoupper(md5($string1));
    $xml = array2xml($pars);
    $extras = array();
    $extras['CURLOPT_CAINFO'] = IA_ROOT . '/addons/lexiangpingou/cert/' . $_W['uniacid'] . '/rootca.pem';
    $extras['CURLOPT_SSLCERT'] = IA_ROOT . '/addons/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_cert.pem';//证书路径
    $extras['CURLOPT_SSLKEY'] = IA_ROOT . '/addons/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_key.pem';//证书路径


    $procResult = null;
    $resp = ihttp_request($url5, $xml, $extras);

    if (is_error($resp)) {
        $procResult = $resp;
    } else {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
        $dom = new DOMDocument();
        if ($dom->loadXML($xml)) {
            $xpath = new DOMXPath($dom);
            $code = $xpath->evaluate('string(//xml/return_code)');
            $ret = $xpath->evaluate('string(//xml/result_code)');
            if (strtolower($code) == 'success' && strtolower($ret) == 'success') {
                pdo_update('tg_saler', array('withdraw' => $pri), array('id' => $member['id']));
                pdo_update('tg_hexiao_record', array('status' => 1, 'updatetime' => TIMESTAMP), array('id' => $id));
                $tip = '发放成功';
                echo "<script>alert('" . $tip . "');location.href='" . web_url('member/saler/withdraw') . "';</script>";
                exit;

            } else {
                $tip = '付款失败,请检查商户余额1';

                $error = $xpath->evaluate('string(//xml/err_code_des)');
                $procResult = error(-2, $error);
                echo "<script>alert('" . $error . "');location.href='" . web_url('member/saler/withdraw') . "';</script>";
                exit;
            }
        } else {
            $tip = '付款失败,请检查商户余额2';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('member/saler/withdraw') . "';</script>";
            exit;

            $procResult = error(-1, 'error response');
        }
    }

}

if ($op == 'cash_add') {
    $id = $_GPC['id'];

    $record = pdo_get('tg_hexiao_record', array('id' => $id));
    if ($record['status'] == 1) {
        $res = 0;
        $tip = '非常抱歉！该佣金已发放';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/saler/withdraw') . "';</script>";
        exit;
    }
    $member = pdo_get('tg_saler', array('uniacid' => $record['uniacid'], 'openid' => $record['openid'], 'status' => 1, 'merchantid' => 0));

    $res = pdo_update('tg_saler', array('withdraw' => floatval($member['withdraw']) + floatval($record['price'])), array('uniacid' => $record['uniacid'], 'openid' => $record['openid'], 'status' => 1, 'merchantid' => 0));
    $res = pdo_update('tg_hexiao_record', array('status' => 1, 'web_cash' => 1, 'updatetime' => TIMESTAMP), array('id' => $id));
    if ($res) {
        $tip = '发放成功';
    } else {
        $tip = '发放失败';
    }
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/saler/withdraw') . "';</script>";
    exit;

    if ($res) {
        $tip = '发放成功';
    } else {
        $tip = '发放失败';
    }
    die(json_encode(array('status' => $res, 'message' => $tip)));
}

include wl_template('member/saler');
exit();
?>