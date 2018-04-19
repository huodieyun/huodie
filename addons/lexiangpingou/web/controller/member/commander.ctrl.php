<?php

defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$uniacid = $_W['uniacid'];
$pindex = max(1, intval($_GPC['page']));
$psize = 20;

wl_load()->model('setting');
$setting = setting_get_by_name("commander");

if ($op == 'base') {

}

if ($op == 'ajax') {

    $apply = intval($_GPC['apply']);
    $smscode = intval($_GPC['smscode']);
    if (empty($setting)) {
        if ($apply) {
            $value = array('apply' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'commander',
                'value' => serialize($value)
            );
            setting_insert($data);
        } elseif ($smscode) {
            $value = array('smscode' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'commander',
                'value' => serialize($value)
            );
            setting_insert($data);
        }
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    } else {
        if ($apply) {
            $status = intval($setting['apply']) == 1 ? 2 : 1;
            $setting['apply'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'commander', 'uniacid' => $_W['uniacid']));
        } elseif ($smscode) {
            $status = intval($setting['smscode']) == 1 ? 2 : 1;
            $setting['smscode'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'commander', 'uniacid' => $_W['uniacid']));
        }

        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    }
}

if ($op == 'display') {

    /*搜索条件*/
    $status = intval($_GPC['groupstatus']);
    $type = intval($_GPC['type']);
    $time = $_GPC['time'];
    if (!$type && (empty($starttime) || empty($endtime))) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if ($type && !empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        if ($type == 1) {
            $condition .= " AND apply_time >= {$starttime} AND apply_time <= {$endtime} ";
        }else {
            $condition .= " AND shenhetime >= {$starttime} AND shenhetime <= {$endtime} ";
        }
    }

    if ($status < 9) {
        if ($status == 1) {
            $condition .= " and apply_status = 1 ";
        } elseif ($status == 0) {
            $condition .= " and apply_status = 0 ";
        } elseif ($status == -1) {
            $condition .= " and apply_status = -1 ";
        }
    }
    if (!empty($_GPC['keyword'])) {
        $keyword = $_GPC['keyword'];
        $condition .= " AND ( nickname like '%{$keyword}%' or name like '%{$keyword}%' ) ";
    }
    $members = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND apply = 1 $condition ORDER BY apply_time asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND apply = 1 $condition ");
    $pager = pagination($total, $pindex, $psize);

}

if ($op == 'unapply') {

    /*搜索条件*/
    $status = intval($_GPC['groupstatus']);
    $type = intval($_GPC['type']);
    $time = $_GPC['time'];
    if (!$type && (empty($starttime) || empty($endtime))) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if ($type && !empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        if ($type == 1) {
            $condition .= " AND apply_time >= {$starttime} AND apply_time <= {$endtime} ";
        }else {
            $condition .= " AND shenhetime >= {$starttime} AND shenhetime <= {$endtime} ";
        }
    }

    if ($status < 9) {
        if ($status == 1) {
            $condition .= " and apply_status = 1 ";
        } elseif ($status == 0) {
            $condition .= " and apply_status = 0 ";
        } elseif ($status == -1) {
            $condition .= " and apply_status = -1 ";
        }
    }
    if (!empty($_GPC['keyword'])) {
        $keyword = $_GPC['keyword'];
        $condition .= " AND ( nickname like '%{$keyword}%' or name like '%{$keyword}%' ) ";
    }
    if ($condition) {
        $condition = 'and openid in ( select from_user from ' . tablename('tg_member') ." where uniacid = {$uniacid} " .$condition .' ) ';
    }
    $commanders = pdo_fetchall("select * from " .tablename('tg_commander') ." where uniacid = {$uniacid} {$condition} group by openid ORDER BY createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $members = array();
    foreach ($commanders as $it) {
        $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$uniacid} AND from_user = '{$it['openid']}' ");
        if (!$member['name']) {
            $member['name'] = $member['nickname'];
        }

        $openid = $it['openid'];

        $orderlist = pdo_fetchall("select a.*,count(b.id) as tuan_num,(b.price-b.freight) as price from " . tablename('tg_commander') . "a left join cm_tg_order b on a.tuan_id=b.tuan_id where a.uniacid = '{$uniacid}' and a.openid = '{$openid}' and b.status not in (4,6,7,10) and b.mobile<>'虚拟' group by a.tuan_id order by a.id desc" );
        $un_price = 0;
        foreach ($orderlist as $key=>$value){
            if($value['commissiontype'] == 1){
                $unorder = pdo_fetch("select (sum(price)-sum(freight)) as un_price from cm_tg_order where uniacid={$uniacid} and tuan_id={$value['tuan_id']} and comtype=0");
                $un_price += $unorder['un_price']*$value['commission']/100;
            }else{
                $un_price += ($value['tuan_num']-$value['success_num'])*$value['commission'];
            }
        }
        $member['unprice'] = $un_price;
        $members[] = $member;

    }

    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' .tablename('tg_commander') ." where uniacid = {$uniacid} {$condition} group by openid ");
    $pager = pagination($total, $pindex, $psize);

}

if ($op == 'checked') {
    $id = intval($_GPC['id']);
//    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
    pdo_update('tg_member', array('apply_status' => 1, 'shenhetime' => TIMESTAMP), array('id' => $id));

    $tip = '审核成功';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/commander/display' , array('groupstatus' => 9)) . "';</script>";
    exit;
}

if ($op == 'unchecked') {
    $id = intval($_GPC['id']);
//    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
    pdo_update('tg_member', array('apply_status' => -1, 'shenhetime' => TIMESTAMP), array('id' => $id));

    internal_log('/commander/' , array('username'=>$_W['username']) , __LINE__ , __FILE__);
    $tip = '取消成功';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/commander/display' , array('groupstatus' => 9)) . "';</script>";
    exit;
}

if ($op == 'group_detail') {
    $openid = $_GPC['openid'];

    $orderlist = pdo_fetchall("select a.*,count(b.id) as tuan_num,(b.price-b.freight) as price from " . tablename('tg_commander') . "a left join cm_tg_order b on a.tuan_id=b.tuan_id where a.uniacid = '{$_W['uniacid']}' and a.openid = '{$openid}' and b.mobile<>'虚拟' and b.status not in (4,6,7,10) group by a.tuan_id order by a.id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($orderlist as $key=>$value){
        if($value['commissiontype'] == 1){
            $unorder = pdo_fetch("select (sum(price)-sum(freight)) as un_price from cm_tg_order where uniacid={$_W['uniacid']} and tuan_id={$value['tuan_id']} and comtype=0");
            $orderlist[$key]['un_price'] = $unorder['un_price'];
        }
    }
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_commander') . " where uniacid = '{$_W['uniacid']}' and openid = '{$openid}' ");
    $pager = pagination($total, $pindex, $psize);
}

if ($op == 'withdraw'){
    $status = $_GPC['status'];
    $time = $_GPC['time'];
    $con = " where uniacid = '{$uniacid}' ";
    if (!empty($_GPC['keyword'])) {
        $keyword = $_GPC['keyword'];
        $con .= " AND openid in ( select from_user from " .tablename('tg_member') . " where uniacid = {$uniacid} and nickname like '%{$keyword}%' ) ";
    }
    if ($status == 0){
        $con .= " and status = 0 ";
    }elseif ($status == 1){
        $con .= " and status = 1 ";
    }elseif ($status == -1){
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
    $list = pdo_fetchall("select * from " .tablename('tg_commander_record') .$con . " order by createtime limit " . ($pindex - 1) * $psize ."," .$psize);
    foreach ($list as &$item) {
        $item['member'] = pdo_fetch("select nickname,avatar from " .tablename('tg_member') ." where uniacid = '{$uniacid}' and from_user = '{$item['openid']}' ");
        unset($item);
    }
    $total = pdo_fetchcolumn("select count(id) from " .tablename('tg_commander_record') .$con);
    $pager = pagination($total, $pindex, $psize);
}

if ($op == 'cash') {
    load()->func('communication');
    load()->model('account');
    wl_load()->model('setting');

    $id = intval($_GPC['id']);
    $set = setting_get_by_name('refund');


    $m = pdo_fetch("SELECT * FROM " . tablename('tg_commander_record') . " WHERE id = {$id}");
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$m['openid']}' and uniacid = '{$_W['uniacid']}'");
    $pri = $m['price'] + $member['commander_withdraw'];
    $apply = $member['commander_apply'] - $m['price'];

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
    $pars['desc'] = $_W['account']['name'] . "团长佣金发放";
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
                pdo_update('tg_member', array('commander_withdraw' => $pri , 'commander_apply' => $apply), array('id' => $member['id']));
                pdo_update('tg_commander_record', array('status' => 1, 'updatetime' => TIMESTAMP), array('id' => $id));
                $tip = '发放成功';
                echo "<script>alert('" . $tip . "');location.href='" . web_url('member/commander/withdraw') . "';</script>";
                exit;

            } else {
                $tip = '付款失败,请检查商户余额1';

                $error = $xpath->evaluate('string(//xml/err_code_des)');
                $procResult = error(-2, $error);
                echo "<script>alert('" . $error . "');location.href='" . web_url('member/commander/withdraw') . "';</script>";
                exit;
            }
        } else {
            $tip = '付款失败,请检查商户余额2';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('member/commander/withdraw') . "';</script>";
            exit;

            $procResult = error(-1, 'error response');
        }
    }

}

if ($op == 'web_cash') {


    $id = $_GPC['id'];
    $record = pdo_get('tg_commander_record' , array('id' => $id));
    $saler = pdo_get('tg_member' , array('uniacid' => $record['uniacid'] , 'from_user' => $record['openid']));
    $ds = $saler;

}

if ($op == 'cash_add') {
    $id = $_GPC['id'];

    $record = pdo_get('tg_commander_record', array('id' => $id));
    if ($record['status'] == 1) {
        $res = 0;
        $tip = '非常抱歉！该佣金已发放';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/commander/withdraw') . "';</script>";
        exit;
    }
    $member = pdo_get('tg_member' , array('uniacid' => $record['uniacid'] , 'from_user' => $record['openid']));

    $res = pdo_update('tg_member', array(
        'commander_withdraw' => floatval($member['commander_withdraw']) + floatval($record['price']) ,
        'commander_apply' => floatval($member['commander_apply']) - floatval($record['price'])
    ), array('uniacid' => $record['uniacid'] , 'from_user' => $record['openid']));
    $res = pdo_update('tg_commander_record', array('status' => 1, 'web_cash' => 1, 'updatetime' => TIMESTAMP), array('id' => $id));
    if ($res) {
        $tip = '发放成功';
    } else {
        $tip = '发放失败';
    }
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/commander/withdraw') . "';</script>";
    exit;

    if ($res) {
        $tip = '发放成功';
    } else {
        $tip = '发放失败';
    }
    die(json_encode(array('status' => $res , 'message' => $tip)));
}

if ($op == 'query_member') {
    $con = " uniacid = '{$_W['uniacid']}' AND apply_status = 1 ";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and nickname LIKE '%{$keyword}%'";
    }
    $ds = pdo_fetchall("select * from " . tablename('tg_member') . " where {$con} ");
    include wl_template('store/query_saler');
    exit;
}

include wl_template('member/commander');
exit();
?>