<?php
/**
 * [weliam] Copyright (c) 2016/3/26
 * 商城系统设置控制器
 */
defined('IN_IA') or exit('Access Denied');

load()->func('communication');
$ops = array('withdraw', 'play', 'change', 'account', 'record', 'get', 'review', 'give', 'shelf', 'unsettlement', 'buy_history');
$op_names = array('系统设置');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'store', 'setting', $ops[$key], '订单', '参数设置', $op_names[$key]);
}
unset($merchant);
$op = $_GPC['op'];
$op = in_array($op, $ops) ? $op : 'copyright';
wl_load()->model('setting');

$acc = pdo_fetch("select merchant_num,merchant_account_time from " .tablename('account_wechats') ." where uniacid = " .$_W['uniacid']);
if (!empty($oauth['host'])) {
    $url = $oauth['host'] . '/web/index.php?c=user&a=shop_login&i=' . $_W['uniacid'];
} else {
    $url = "https://www.lexiangpingou.cn" . '/web/index.php?c=user&a=shop_login&i=' . $_W['uniacid'];
}

//$merchant_num = pdo_fetch("SELECT merchant_num FROM " . tablename('account_wechats') . " WHERE uniacid = " . $_W['uniacid']);
$merchant_num = $acc['merchant_num'];
$merchant_now = pdo_fetchcolumn("select count(*) from " . tablename('tg_merchant') . " WHERE uniacid = {$_W['uniacid']} and status in (1,5) ");

//商户到期提醒
$boom = 0;
$batch = pdo_fetch("select * from " . tablename('tg_merchant_batch') . " where uniacid = '{$_W['uniacid']}' order by endtime ");
if (intval($batch['endtime']) > 0 && (intval($batch['endtime']) - 3 * 24 * 60 * 60) < TIMESTAMP) {
    $boom = 1;
}

if ($op == 'copyright') {
    $_W['page']['title'] = $checkfunction['name'];

    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $condition = " WHERE uniacid = {$_W['uniacid']} ";
    if ($_W['user']['merchant_id'] > 0) {
        $condition .= " and id = {$_W['user']['merchant_id']} ";
    }
    if ($_GPC['keyword']) {
        $key = trim($_GPC['keyword']);
        $condition .= " and ( uname like '%{$key}%' or linkman_mobile like '%{$key}%' ) ";
    }

    $sql = "SELECT * FROM " . tablename('tg_merchant') . $condition . " LIMIT " . ($page - 1) * $size . " , " . $size;

    $merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . $condition . " ORDER BY status asc,createtime asc LIMIT " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('tg_merchant') . $condition);
    $pager = pagination($total, $page, $size);
    foreach ($merchants as &$value) {
//        $time = TIMESTAMP;
//        $create = $time - 15 * 24 * 60 * 60;
//
//        $merchant = pdo_fetch("SELECT * FROM " .tablename('tg_merchant') ." WHERE uniacid = '{$_W['uniacid']}' and id = '{$value['id']}' " );
//
//        $con = " and over_time < " .$create;

        $value['goodsnum'] = pdo_fetchcolumn("select COUNT(*) from " . tablename('tg_goods') . " where uniacid = '{$_W['uniacid']}' and merchantid = '{$value['id']}' and isshow = 1");
        $value['amount_total'] = pdo_fetchcolumn("SELECT SUM(price) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and merchantid = {$value['id']} and status = 3 ");

        $accounts = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant_account') . " WHERE uniacid = {$_W['uniacid']} and merchantid = {$value['id']}");
//        $value['amount_total'] = 0.00;
        $value['apply_total'] = 0.00;
        $value['total'] = 0.00;
        $value['point_total'] = 0.00;
        $value['give_total'] = 0.00;
        if (!empty($accounts)) {
            foreach ($accounts as &$v) {
//                $value['amount_total'] += $v['amount'];
                $value['apply_total'] += $v['apply'];
                $value['total'] += $v['get_amount'];
                $value['point_total'] += $v['point'];
                $value['give_total'] += $v['give'];
            }
            $value['commit'] = $value['total'] - $value['give_total'];
        }
    }

    include wl_template('store/userList');
}

/*
 * 商家下架
 */
if ($op == 'shelf') {

    $id = $_GPC['id'];
    $status = $_GPC['status'];
    $uniacid = $_W['uniacid'];
    if ($status == 1) {
        $merchant = pdo_fetchcolumn("select COUNT(*) from " . tablename('tg_merchant') . "  where uniacid = '{$uniacid}' and status = 1");
        $account = pdo_fetch("select merchant_num from " . tablename('account_wechats') . "  where uniacid = '{$uniacid}'");
        $num = $account['merchant_num'];
        if ($merchant >= $num) {
            die(json_encode(array("errno" => 1, 'message' => '商家数量已达到上限!')));
        }
        $res = pdo_update('tg_merchant', array('status' => $status), array('id' => $id));
        if ($res) {
            die(json_encode(array("errno" => 0, 'message' => '商家上架成功!')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '商家上架失败!')));
        }
    } elseif ($status == 3) {

        $res = pdo_update('tg_merchant', array('status' => $status), array('id' => $id));
        $ret = pdo_update('tg_goods', array('isshow' => 2), array('uniacid' => $uniacid, 'merchantid' => $id));
        if ($res) {
            die(json_encode(array("errno" => 0, 'message' => '商家下架成功!')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '商家下架失败!')));
        }
    }
    die(json_encode(array("errno" => 1, 'message' => '请求出错!')));
}

//订单自动结算
//addons/lexiangpingou/web/controller/store/userList.ctrl.php?op=settle
//传人商家id
//if($op == 'settle'){
//    $id = $_W['user']['merchant_id'];
//    if ($id > 0) {
//
//        $time = TIMESTAMP;
//        $create = $time - 15 * 24 * 60 * 60;
//        $order = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and status = 3 and m_status = 0 and over_time < " . $create ." limit 0,20 ");
//
//        foreach ($order as &$value) {
//            $data = array();
//            $merchant = pdo_fetch("SELECT percent FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' and id = '{$value['merchantid']}' ");
//            $data['merchantid'] = $value['merchantid'];
//            $data['money'] = $value['price'];
//            $data['createtime'] = TIMESTAMP;
//            $data['uniacid'] = $_W['uniacid'];
//            $data['orderno'] = $value['orderno'];
//            $data['percent'] = $merchant['percent'];
//            $data['status'] = 0;
//            $data['point'] = round(($value['price'] * $merchant['percent'] * 0.01), 2);
//            $data['get_money'] = $data['money'] - $data['point'];
//            $data['gname'] = $value['goodsname'];
//            if (empty($data['gname'])) {
//                $goods = pdo_fetch("select goodsname from " . tablename('tg_collect') . " where uniacid = " . $_W['uniacid'] . " and orderno = " . $value['orderno']);
//                $data['gname'] = $goods['goodsname'];
//            }
//            pdo_update('tg_order', array('m_status' => 1), array('orderno' => $value['orderno']));
//            pdo_insert('tg_merchant_record', $data);
//        }
//    }
//}

//未结算订单
if ($op == 'unsettlement') {
    $id = $_GPC['id'];
    $page = $_GPC['page'];
    $size = 10;
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $time = TIMESTAMP;

    $create = $time - intval($acc['merchant_account_time']) * 24 * 60 * 60;

    $merchant = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' and id = '{$id}' ");
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and merchantid = {$id} and status = 3 and m_status = 0 LIMIT " . ($page - 1) * $size . "," . $size);
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and merchantid = {$id} and status = 3 and m_status = 0 ");
    $total_price = pdo_fetchcolumn("SELECT SUM(price) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and merchantid = {$id} and status = 3 and m_status = 0 ");
    $pager = pagination($total, $page, $size);
    include wl_template('store/userList');
}

//商家申请结算提现
if ($op == 'account') {
    $id = $_GPC['id'];
    $page = $_GPC['page'];
    $size = 10;

//    if (empty($page)){
//        $order = pdo_fetchall("SELECT * FROM " .tablename('tg_order') ." WHERE uniacid = {$_W['uniacid']} and merchantid = {$id} and status = 3 and m_status = 0 ");
//
//        $data = array();
//        foreach ($order as &$value) {
//            $data['merchantid'] = $value['merchantid'];
//            $data['money'] = $value['price'];
//            $data['createtime'] = TIMESTAMP;
//            $data['uniacid'] = $_W['uniacid'];
//            $data['orderno'] = $value['orderno'];
//            $data['percent'] = $merchant['percent'];
//            $data['status'] = 0;
//            $data['point'] = round(($value['price'] * $merchant['percent'] * 0.01) , 2);
//            $data['get_money'] = $data['money'] - $data['point'];
//            $data['gname'] = $value['goodsname'];
//            if (empty($data['gname'])){
//                $goods = pdo_fetch("select goodsname from " .tablename('tg_collect') ." where uniacid = " .$_W['uniacid'] ." and orderno = " .$value['orderno']);
//                $data['gname'] = $goods['goodsname'];
//            }
//            pdo_update('tg_order' , array('m_status' => 1) , array('orderno' => $value['orderno']));
//            pdo_insert('tg_merchant_record' , $data);
//        }
//    }

    $con = "";
    if ($id > 0) {
        $merchant = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = {$_W['uniacid']} and id = {$id}");
        $con .= " and merchantid = '{$id}' ";
    }
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant_record') . " WHERE uniacid = " . $_W['uniacid'] . $con . " AND status = 0 ");
    $total_price = 0;
    $get_total = 0;
    foreach ($list as &$value) {
        $total_price = $total_price + $value['money'];
        if ($value['get_status'] == 0) {
            $get_total = $get_total + $value['get_money'];
        }
    }

    $orderby = ' order by id';
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $sql = "SELECT * FROM " . tablename('tg_merchant_record') . " where uniacid = " . $_W['uniacid'] . $con . " and status = 0 {$orderby} LIMIT " . ($page - 1) * $size . "," . $size;
    $list = pdo_fetchall($sql, $params);
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_merchant_record') . " WHERE uniacid = " . $_W['uniacid'] . $con . " AND status = 0 ");
    $pager = pagination($total, $page, $size);

    if (checksubmit('submit')) {
        $account['code'] = "";
        $account['amount'] = 0.00;
        $account['get_amount'] = 0.00;
        $account['merchantid'] = $id;
        $account['uniacid'] = $_W['uniacid'];
        $account['status'] = 1;

        $sql = "SELECT * FROM " . tablename('tg_merchant_record') . " WHERE uniacid = " . $_W['uniacid'] . $con . " AND status = 0 ";
        $list = pdo_fetchall($sql);
        $i = 0;
        foreach ($list as &$value) {
            if (empty($account['code'])) {
                $account['code'] .= $value['orderno'];
            } else {
                $account['code'] .= "|" . $value['orderno'];
            }
            $account['apply'] += floatval($value['get_money']);
            $account['point'] += floatval($value['point']);
            $account['amount'] += floatval($value['money']);
            $account['get_amount'] += floatval($value['get_money']);
            pdo_update('tg_merchant_record', array('status' => 1, 'get_status' => 0), array('id' => $value['id']));
            $record_id[$i++]['id'] = $value['id'];
        }
        $account['createtime'] = TIMESTAMP;
        $account['txno'] = "TX" . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        pdo_insert('tg_merchant_account', $account);
        $account_id = pdo_insertid();
        foreach ($record_id as $value) {
            pdo_update('tg_merchant_record', array('account_id' => $account_id), array('id' => $value['id']));
        }

        message('申请提现成功！请等候确认打款', web_url('store/userList', array('op' => 'review', 'id' => $id)), 'success');
    }
    include wl_template('store/userList');
}

//提现列表
if ($op == 'record') {
    $id = $_GPC['id'];

    $page = $_GPC['page'];
    $size = 10;
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $orderby = ' order by id';

    $account = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_account') . " WHERE id = " . $id);
    $merchant = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE id = " . $account['merchantid']);
    $total_price = $account['amount'];
    $get_total = $account['get_amount'];

    $list = pdo_fetchall("select * from " . tablename('tg_merchant_record') . " where account_id = " . $id . " and status = 1 {$orderby} LIMIT " . ($page - 1) * $size . "," . $size);
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_merchant_record') . " WHERE account_id = " . $id . " AND status = 1 ");
    $pager = pagination($total, $page, $size);

    foreach ($list as &$value) {
        $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE uniacid = " . $_W['uniacid'] . " AND merchantid = " . $merchant['id'] . " AND orderno = " . $value['orderno']);

        $value['orderid'] = $item['id'];
        if ($value['get_status'] == -1) {
            $value['reason'] = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant_reason') . " WHERE record_id = " . $value['id']);
        }
    }

//    $funcop = $_GPC['funcop'];
//    die(json_decode($funcop));
//    提现审核通过
    if (checksubmit('submit')) {
        $id = $_GPC['id'];
        $data['status'] = 2;
        $data['updatetime'] = TIMESTAMP;

        pdo_update('tg_merchant_account', $data, array('id' => $id));

        $list = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant_record') . " WHERE account_id = " . $id . " AND status = 1 AND get_status = 0");
        foreach ($list as &$value) {
            pdo_update('tg_merchant_record', array('get_status' => 2), array('id' => $value['id']));
        }
        $time = TIMESTAMP;
        $pay_time = pdo_fetch("SELECT merchant_pay_time FROM " . tablename('account_wechats') . " WHERE uniacid = {$_W['uniacid']}");
        $pay_time = $pay_time['merchant_pay_time'];
        $create = $time - $pay_time * 24 * 60 * 60;

        $account = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_account') . " WHERE id = " . $id);
        if ($create > $account['createtime']) {
            message('确认成功！将直接跳转到打款页面！', web_url('store/userList', array('op' => 'give')), 'success');
        } else {
            message('确认成功！请准备打款', web_url('store/userList', array('op' => 'withdraw', 'id' => $merchant['id'])), 'success');
        }

    }
    include wl_template('store/userList');
}

if ($op == 'play') {
    $id = $_GPC['pid'];

    $status = intval($_GPC['status']);
    $account = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_account') . " WHERE id = " . $id);
    $merchant = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE id = " . $account['merchantid']);
    $total_price = $account['amount'];
    $get_total = $account['get_amount'];

    if (intval($merchant['bank']) == 2) {

//    提现
        if (checksubmit('submit')) {
            $id = $_GPC['pid'];
            $data = $_GPC['account']; // 获取打包值
            $data['status'] = 3;
            $data['updatetime'] = TIMESTAMP;
            $data['give'] = $account['get_amount'];
            $data['give_time'] = TIMESTAMP;
            $data['payment'] = 1;
            pdo_update('tg_merchant_account', $data, array('id' => $id));

            foreach ($list as &$value) {
                if ($value['get_status'] == 0) {
                    pdo_update('tg_merchant_record', array('get_status' => 1), array('id' => $value['id']));
                }
            }

            message('打款成功！', web_url('store/userList', array('op' => 'withdraw')), 'success');
        }
        include wl_template('store/userList');
    } elseif ($status == 1) {
        if ($merchant['openid'] == '') {
            message('打款出错！该商户没有填写提现微信号', '', 'error');
        }

        wl_load()->model('setting');

        $set = setting_get_by_name('refund');

        $url5 = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $apikey = $set['apikey'];
        $pars = array();
        $pars['mch_appid'] = $_W['account']['key'];//身份标识（appid）
        $pars['mchid'] = $set['mchid'];//微信支付商户号(mchid)
        $pars['nonce_str'] = random(32);
        $pars['partner_trade_no'] = random(10) . date('Ymd') . random(3);
        $pars['openid'] = $merchant['openid'];
        $pars['check_name'] = "NO_CHECK";
        $pars['amount'] = $get_total * 100;
        $pars['desc'] = $_W['account']['name'] . "商户提现";
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
                    $str =  json_decode(json_encode(simplexml_load_string($resp['content'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                    //TODO 创建日志
                    $path = IA_ROOT . "/addons/lexiangpingou/data/log/" . $_W['uniacid'] . "/userpay/";
                    //首先判断目录存在否
                    if (!is_dir($path)) {
                        //第3个参数“true”意思是能创建多级目录，iconv防止中文目录乱码
                        $res = mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
                    }
                    $date = date('Y-m-d', TIMESTAMP);
                    file_put_contents($path . $date . ".log", var_export(array(
                            'file' => __FILE__,
                            'line' => __LINE__,
                            'str' => $str,
                            'time' => date('Y-m-d H:i:s', TIMESTAMP)
                        ), true) . PHP_EOL, FILE_APPEND);
                    pdo_update('tg_merchant_account', array('give' => $get_total, 'give_time' => TIMESTAMP, 'status' => 3 , 'single' => $str['payment_no'] , 'payment' => 0), array('id' => $account['id']));
                    $tip = '打款成功';
                    echo "<script>alert('" . $tip . "');location.href='" . web_url('store/userList', array('op' => 'withdraw', 'id' => $account['merchantid'])) . "';</script>";
                    exit;

                } else {
                    $tip = '付款失败,请检查商户余额1';


                    $error = $xpath->evaluate('string(//xml/err_code_des)');
                    $procResult = error(-2, $error);
                    echo "<script>alert('" . $error . "');location.href='" . web_url('store/userList', array('op' => 'give')) . "';</script>";
                    exit;
                }
            } else {
                $tip = '付款失败,请检查商户余额2';
                echo "<script>alert('" . $tip . "');location.href='" . web_url('store/userList', array('op' => 'give')) . "';</script>";
                exit;

                $procResult = error(-1, 'error response');
            }
        }
        message('打款成功！', web_url('store/userList', array('op' => 'withdraw')), 'success');
    }

}

if ($op == 'withdraw') {
    $id = $_GPC['id'];
    $time = TIMESTAMP;
    $pay_time = pdo_fetch("SELECT merchant_pay_time FROM " . tablename('account_wechats') . " WHERE uniacid = {$_W['uniacid']}");
    $pay_time = $pay_time['merchant_pay_time'];
    $create = $time - $pay_time * 24 * 60 * 60;

    $con = "";
    if ($id > 0) {
        $merchant = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = {$_W['uniacid']} and id = {$id}");
        $con .= " and merchantid = '{$id}' ";
    }

    $account = pdo_fetch("select * from " . tablename('tg_merchant_account') . " where uniacid = {$_W['uniacid']} " . $con);
    $total_price = $account['amount'];
    $get_total = $account['get_amount'];
    $page = $_GPC['page'];
    $size = 10;
    $orderby = ' order by id';
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $sql = "SELECT * FROM " . tablename('tg_merchant_account') . " where uniacid = " . $_W['uniacid'] . $con . " and status = 3 {$orderby} LIMIT " . ($page - 1) * $size . "," . $size;
    $list = pdo_fetchall($sql, $params);
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_merchant_account') . " WHERE uniacid = " . $_W['uniacid'] . $con. " and status = 3");
    $pager = pagination($total, $page, $size);

    // $amount_total = 0;
    $apply_total = 0;
    $total = 0;
    $point_total = 0;
    $give_total = 0;
    foreach ($list as &$value) {
        if($value['payment'] == 1) {
            $value['single'] = '银行流水单号：' . $value['single'];
        } else {
            $value['single'] = '微信打款单号：' . $value['single'];
        }
        $apply_total += $value['apply'];
        $total += $value['get_amount'];
        $point_total += $value['point'];
        $give_total += $value['give'];
        if ($create > $value['createtime']) {
            $value['get'] = 1;
        } else {
            $value['get'] = 0;
        }
    }
    $commit = $total - $give_total;
    include wl_template('store/userList');
}

//待审核记录
if ($op == 'review') {
//    $id = $_GPC['id'];
//    $time = TIMESTAMP;
//    $pay_time = pdo_fetch("SELECT merchant_pay_time FROM ".tablename('account_wechats')." WHERE uniacid = {$_W['uniacid']}");
//    $pay_time = $pay_time['merchant_pay_time'];
//    $create = $time - $pay_time * 24 * 60 * 60;

    $id = $_GPC['id'];
    $condition = "";
    if ($id > 0) {
        $merchant = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = {$_W['uniacid']} and id = {$id}");
        $condition .= " and merchantid = '{$id}' ";
    }

//    $merchant = pdo_fetch("SELECT * FROM ".tablename('tg_merchant')." WHERE uniacid = {$_W['uniacid']} and id = {$_W['user']['merchant_id']}");
//    if($_W['user']['merchant_id'] > 0){
//        $condition .= " and id = {$_W['user']['merchant_id']} ";
//    }
    $account = pdo_fetchall("select * from " . tablename('tg_merchant_account') . " where uniacid = {$_W['uniacid']} and status = 1 " . $condition);

//    foreach ($account as &$value) {
//        $merchant = pdo_fetch("SELECT * FROM ".tablename('tg_merchant')." WHERE uniacid = {$_W['uniacid']} and id = {$account['merchantid']}");
//    }


    $total_price = $account['amount'];
    $get_total = $account['get_amount'];
    $page = $_GPC['page'];
    $size = 10;
    $orderby = ' order by createtime';
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $sql = "SELECT * FROM " . tablename('tg_merchant_account') . " where uniacid = " . $_W['uniacid'] . " and status = 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $size . "," . $size;
    $list = pdo_fetchall($sql);
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_merchant_account') . " where uniacid = " . $_W['uniacid'] . " and status = 1 {$condition}");
    $pager = pagination($total, $page, $size);


//    foreach ($list as &$value) {
//        $merchant = pdo_fetch("SELECT * FROM ".tablename('tg_merchant')." WHERE uniacid = {$_W['uniacid']} and id = {$value['merchantid']}");
//    }
    // $amount_total = 0;
    $apply_total = 0;
    $total = 0;
    $point_total = 0;
    $give_total = 0;
    foreach ($list as &$value) {
        $apply_total += $value['apply'];
        $total += $value['get_amount'];
        $point_total += $value['point'];
        $give_total += $value['give'];
//        if ($create > $value['createtime']){
//            $value['get'] = 1;
//        }else{
//            $value['get'] = 0;
//        }
        $me = pdo_fetch("SELECT name FROM " . tablename('tg_merchant') . " WHERE id = " . $value['merchantid']);
        $value['name'] = $me['name'];
        unset($value);
    }
    $commit = $total - $give_total;

    include wl_template('store/userList');
}

//待打款记录
if ($op == 'give') {
//    $id = $_GPC['id'];
    $time = TIMESTAMP;
    $pay_time = pdo_fetch("SELECT merchant_pay_time FROM " . tablename('account_wechats') . " WHERE uniacid = {$_W['uniacid']}");
    $pay_time = $pay_time['merchant_pay_time'];
    $create = $time - $pay_time * 24 * 60 * 60;

    $id = $_GPC['id'];
    $condition = "";
    if ($id > 0) {
        $merchant = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = {$_W['uniacid']} and id = {$id}");
        $condition .= " and merchantid = '{$id}' ";
    }

    $account = pdo_fetch("select * from " . tablename('tg_merchant_account') . " where uniacid = {$_W['uniacid']} and status = 1 " . $condition);
    $total_price = 0.0;
    $get_total = 0.0;
    foreach ($account as &$value) {

        $total_price += $value['amount'];
        $get_total += $value['get_amount'];
        unset($value);
    }
    $page = $_GPC['page'];
    $size = 10;
    $orderby = ' order by createtime';
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $sql = "SELECT * FROM " . tablename('tg_merchant_account') . " where uniacid = " . $_W['uniacid'] . " and status = 2 {$condition} {$orderby} LIMIT " . ($page - 1) * $size . "," . $size;
    $list = pdo_fetchall($sql);
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_merchant_account') . " WHERE uniacid = " . $_W['uniacid'] . " AND status = 2 " . $condition);
    $pager = pagination($total, $page, $size);

    // $amount_total = 0;
    $apply_total = 0;
    $total = 0;
    $point_total = 0;
    $give_total = 0;
    foreach ($list as &$value) {
        $apply_total += $value['apply'];
        $total += $value['get_amount'];
        $point_total += $value['point'];
        $give_total += $value['give'];
        if ($create > $value['createtime']) {
            $value['get'] = 1;
        } else {
            $value['get'] = 0;
        }
        $me = pdo_fetch("SELECT name FROM " . tablename('tg_merchant') . " WHERE id = " . $value['merchantid']);
        $value['name'] = $me['name'];
        unset($value);
    }
    $commit = $total - $give_total;

    include wl_template('store/userList');
}

if ($op == 'change') {
    $funcop = $_GPC['funcop'];
    if ($funcop == 'c') {
        $data['record_id'] = $_GPC['id'];
        $data['uni_reason'] = $_GPC['val'];
        $id = $_GPC['id'];

        if (pdo_update('tg_merchant_record', array('get_status' => -1), array('id' => $id))) {
            $record = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_record') . " WHERE id = " . $id);
            $account = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_account') . " WHERE id = " . $record['account_id']);
            pdo_update('tg_merchant_account', array('get_amount' => $account['get_amount'] - $record['get_money']), array('id' => $record['account_id']));
            $data['uniacid'] = $record['uniacid'];
            $data['merchant_id'] = $record['merchantid'];
            $data['status'] = $record['get_status'];
            $data['updatetime'] = TIMESTAMP;
            pdo_insert('tg_merchant_reason', $data);
            die(json_encode(array("errno" => 0, 'message' => '拒绝成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '拒绝失败')));
        }
    } elseif ($funcop == 'changeAll') {
        $goods_ids = $_GPC['goods_ids'];
        foreach ($goods_ids as &$value) {
            $record = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_record') . " WHERE id = " . $id);
            $account = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_account') . " WHERE id = " . $record['account_id']);
            pdo_update('tg_merchant_account', array('get_amount' => $account['get_amount'] - $record['get_money']), array('id' => $record['account_id']));
            pdo_update('tg_merchant_record', array('get_status' => -1), array('id' => $value));
        }
        die(json_encode(array("errno" => 0, 'message' => '拒绝成功')));
    } elseif ($funcop == 'detail') {

        $id = $_GPC['id'];
        $record = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_record') . " WHERE id = " . $id);
        $data['record_id'] = $_GPC['id'];
        $data['uni_reason'] = $_GPC['val'];
        $data['uniacid'] = $record['uniacid'];
        $data['merchant_id'] = $record['merchantid'];
        $data['status'] = $record['get_status'];
        $data['updatetime'] = TIMESTAMP;
        pdo_insert('tg_merchant_reason', $data);
        die(json_encode(array("errno" => 0, 'message' => '提交成功')));
    } elseif ($funcop == 'reason') {
        $id = $_GPC['id'];
        $record = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_record') . " WHERE id = " . $id);
        $data['record_id'] = $_GPC['id'];
        $data['merchant_reason'] = $_GPC['val'];
        $data['uniacid'] = $record['uniacid'];
        $data['merchant_id'] = $record['merchantid'];
        $data['status'] = $record['get_status'];
        $data['updatetime'] = TIMESTAMP;
        pdo_insert('tg_merchant_reason', $data);
        die(json_encode(array("errno" => 0, 'message' => '提交成功')));
    } elseif ($funcop == 'final') {
        $id = $_GPC['id'];
        if (pdo_update('tg_merchant_record', array('get_status' => -2), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '拒绝成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '拒绝失败')));
        }
    } elseif ($funcop == 'freed') {
        $id = $_GPC['id'];
        if (pdo_update('tg_merchant_record', array('get_status' => 0, 'uni_status' => 1), array('id' => $id))) {
            $record = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_record') . " WHERE id = " . $id);
            pdo_update('tg_order', array('m_status' => 0), array('orderno' => $record['orderno'], 'uniacid' => $record['uniacid'], 'merchantid' => $record['merchantid']));
            die(json_encode(array("errno" => 0, 'message' => '重审成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '重审失败')));
        }
    }
}

if ($op == 'get') {
    $id = $_GPC['id'];
    $reason = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant_reason') . " WHERE record_id = " . $id . " ORDER BY updatetime");
    $i = 0;
    $j = 0;
    foreach ($reason as &$value) {
        if (!empty($value['uni_reason'])) {
            $uni_reason[$i++] = $value['uni_reason'];
        } elseif (!empty($value['merchant_reason'])) {
            $merchant_reason[$j++] = $value['merchant_reason'];
        }
    }
    die(json_encode(array('uni' => $uni_reason, 'merchant' => $merchant_reason)));
//    die(json_encode(array('reason' => $res)));
}

if ($op == 'buy_history') {

    $uniacid = $_W['uniacid'];
    $page = $_GPC['page'];
    $size = 10;
    $page = !empty($page) ? intval($_GPC['page']) : 1;
    $list = pdo_fetchall("select * from " . tablename('tg_merchant_batch') . " where uniacid = '{$uniacid}' order by endtime limit " . ($page - 1) * $size . "," . $size);
    $total = pdo_fetchcolumn("select id from " . tablename('tg_merchant_batch') . " where uniacid = '{$uniacid}' ");
    foreach ($list as &$value) {

    }
    $pager = pagination($total, $page, $size);
    include wl_template('store/userList');
}

exit();