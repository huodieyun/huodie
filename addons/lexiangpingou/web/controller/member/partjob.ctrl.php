<?php

defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'base';
$pindex = max(1, intval($_GPC['page']));
$psize = 20;
//权限控制
$tid = 8167;
//
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$_W['page']['title'] = $checkfunction['name'];
wl_load()->model('setting');
$setting = setting_get_by_name("jobsmscode");
if (!pdo_fieldexists('tg_collect', 'commissiontype')) {
    pdo_query("ALTER TABLE `cm_tg_collect` ADD `commissiontype` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '佣金计算方式';");
}
if ($op == 'base') {

    if (checksubmit()) {
        $commission_time = $_GPC['commission_time'];
        if ($commission_time < 0) {
            $commission_time = 0;
        }
        pdo_update('account_wechats', array('commission_time' => $commission_time), array('uniacid' => $_W['uniacid']));
        $tip = '修改佣金提现时间成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/partjob') . "';</script>";
        exit;
    }
    $acc = pdo_fetch("select commission_time from " . tablename('account_wechats') . " where uniacid = " . $_W['uniacid']);
    include wl_template('member/partjob');
    exit;
}

if ($op == 'ajax') {

    $shop_switch = intval($_GPC['shop_switch']);
    $smscode = intval($_GPC['jobsmscode']);
    if (empty($setting)) {
        if ($shop_switch) {
            $value = array('shop_switch' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'jobsmscode',
                'value' => serialize($value)
            );
            setting_insert($data);
        } elseif ($smscode) {
            $value = array('jobsmscode' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'jobsmscode',
                'value' => serialize($value)
            );
            setting_insert($data);
        }
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    } else {
        if ($shop_switch) {
            $status = intval($setting['shop_switch']) == 1 ? 2 : 1;
            $setting['shop_switch'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'jobsmscode', 'uniacid' => $_W['uniacid']));
        } elseif ($smscode) {
            $status = intval($setting['jobsmscode']) == 1 ? 2 : 1;
            $setting['jobsmscode'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'jobsmscode', 'uniacid' => $_W['uniacid']));
        }

        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    }

}

if ($op == 'display') {

    /*搜索条件*/
    $groupstatus = $_GPC['groupstatus'];
    $time = $_GPC['time'];
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND  addtime >= {$starttime} AND  addtime <= {$endtime} ";

    }

    if (!empty($groupstatus)) {
        if ($groupstatus == 2) {
            $condition .= " AND (enable =0 or enable is NULL)";
        }
        if ($groupstatus == 1) {
            $condition .= " AND enable =1";
        }
    }
    if (!empty($_GPC['keyword'])) {
        $keyword = $_GPC['keyword'];
        $condition .= " AND nickname like '%{$keyword}%'";
    }
    $members = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} and type>=0 $condition ORDER BY id asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} and type>=0 $condition");
    $pager = pagination($total, $pindex, $psize);

} else if ($op == 'modify') {
    $id = intval($_GPC['id']);
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
    if (checksubmit()) {


        //if(empty($_GPC['enable'])){ message('请选择是否推广员');}
        pdo_update('tg_member', array('enable' => $_GPC['enable']), array('id' => $id));
        $tip = '保存成功';
        echo "<script>alert('" . $tip . "');location.href='" . url('member/partjob') . "';</script>";
        exit;

    }
} else if ($op == 'upd') {
    $id = intval($_GPC['id']);
    $mems = pdo_fetchall('SELECT * FROM ' . tablename('tg_member') . ' WHERE enable=1');
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
    if ($_GPC['b'] == 1) {

        //if(empty($_GPC['enable'])){ message('请选择是否推广员');}
        pdo_update('tg_member', array('shopname' => $_GPC['shopname']), array('id' => $id));
        $tip = '保存成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/partjob/display') . "';</script>";
        exit;

    }
} else if ($op == 'group_detail') {
    $id = intval($_GPC['id']);
    $member = pdo_fetch("select addtime from " . tablename('tg_member') . " where id = " . $id);

    //$orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,addname FROM " . tablename('tg_order') . " WHERE uniacid ='{$_W['uniacid']}' and status not in (0,4,5,9)  and (commission>0 or g_id=0)  and openid in (select from_user from ".tablename('tg_member')." where parentid={$id})");
    $mens = pdo_fetchall("select from_user FROM " . tablename('tg_member') . " where parentid={$id}");
    foreach ($mens as $m => $k) {
        $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,addname,status,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid ='{$_W['uniacid']}' and status in(2,3,8) and ptime > {$member['addtime']} and (com_type = 1 or g_id=0)  and openid='{$k['from_user']}'");
        foreach ($orders1 as $ma => $ka) {
            $orderlist[] = $ka;
        }
    }
    $groupstatus = 3;
} else if ($op == 'member_detail') {
    $id = intval($_GPC['id']);
    $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE parentid = {$id}");
    $groupstatus = 3;

} else if ($op == 'checked') {
    $id = intval($_GPC['id']);
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
    pdo_update('tg_member', array('enable' => 1, 'addtime' => TIMESTAMP), array('id' => $id));

    $tip = '审核成功';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/partjob/display') . "';</script>";
    exit;
} else if ($op == 'unchecked') {
    $id = intval($_GPC['id']);
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
    pdo_update('tg_member', array('enable' => 0, 'type' => 0), array('id' => $id));
    pdo_update('tg_member', array('parentid' => 0), array('parentid' => $id));

    //TODO 创建日志
    $path = IA_ROOT . "/addons/lexiangpingou/data/log/" . $_W['uniacid'] . "/partjob/";
    //首先判断目录存在否
    if (!is_dir($path)) {
        //第3个参数“true”意思是能创建多级目录，iconv防止中文目录乱码
        $res = mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
    }
    $date = date('Y-m-d', TIMESTAMP);
    file_put_contents($path . $date . ".log", var_export(
            array(
                'ip' => CLIENT_IP,
                'op' => "取消兼职",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'uid' => $_W['user']['uid'],
                'username' => $_W['username'],
                'member' => $member,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ),
            true) . PHP_EOL, FILE_APPEND);

    $tip = '取消成功';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/partjob/display') . "';</script>";
    exit;
} else if ($op == 'payment') {
    load()->func('communication');
    load()->model('account');
    $id = intval($_GPC['id']);
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");


    $url5 = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    $apikey = $this->module['config']['apikey'];
    $pars = array();
    $pars['mch_appid'] = $_W['account']['key'];//身份标识（appid）
    $pars['mchid'] = $this->module['config']['mchid'];//微信支付商户号(mchid)
    $pars['nonce_str'] = random(32);
    $pars['partner_trade_no'] = random(10) . date('Ymd') . random(3);
    $pars['openid'] = $member['from_user'];
    $pars['check_name'] = "NO_CHECK";
    $pars['amount'] = $member['wallet'] * 100;
    $pars['desc'] = $_W['account']['name'] . "佣金发放";
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
    $extras['CURLOPT_CAINFO'] = IA_ROOT . '/addons/feng_fightgroups/cert/' . $_W['uniacid'] . '/rootca.pem';
    $extras['CURLOPT_SSLCERT'] = IA_ROOT . '/addons/feng_fightgroups/cert/' . $_W['uniacid'] . '/apiclient_cert.pem';//证书路径
    $extras['CURLOPT_SSLKEY'] = IA_ROOT . '/addons/feng_fightgroups/cert/' . $_W['uniacid'] . '/apiclient_key.pem';//证书路径


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
                pdo_update('tg_member', array('wallet' => 0, 'cash' => $member['cash'] + $member['wallet']), array('id' => $member['id']));

                $bdata = array(
                    'uniacid' => $_W['uniacid'],
                    'openid' => $member['from_user'],
                    'type' => 1,
                    'addtime' => TIMESTAMP,
                    'price' => $member['wallet']
                );
                pdo_insert('tg_cashrecord', $bdata);
                $tip = '发放成功';
                echo "<script>alert('" . $tip . "');location.href='" . web_url('member/partjob/display') . "';</script>";
                exit;

                $procResult = true;
            } else {
                $tip = '付款失败';
                echo "<script>alert('" . $tip . "');location.href='" . web_url('member/partjob/display') . "';</script>";
                exit;

                $error = $xpath->evaluate('string(//xml/err_code_des)');
                $procResult = error(-2, $error);
            }
        } else {
            $tip = '付款成功3';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('member/partjob/display') . "';</script>";
            exit;

            $procResult = error(-1, 'error response');
        }
    }

}

include wl_template('member/partjob');
exit();
?>