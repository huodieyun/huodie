<?php

$_W['page']['title'] = "极限单品 - 供应商列表";
wl_load()->func('message');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$uniacid = $_W['uniacid'];
if ($op == 'detail') {
    $list = pdo_fetch("SELECT * FROM " . tablename('tg_platform_supplier') . ' WHERE id=:id', array(':id' => $_GPC['id']));
//
    $manage = pdo_fetch('SELECT * FROM ' . tablename('tg_manage_cate') . ' WHERE id=:id', array(':id' => $list['manage_cate_id']));
    $list['manage_cate_name'] = $manage['cname'];
    $list['id_card_img'] = tomedia($list['id_card_img']);
    $list['id_card_fan'] = tomedia($list['id_card_fan']);
    $list['id_card_xy'] = tomedia($list['id_card_xy']);
    $list['license_img'] = tomedia($list['license_img']);
    die(json_encode($list));
}
if ($op == 'display') {

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;

    $condition = " where 1 ";

    if (!empty($_GPC['keyword'])) {
        $key = $_GPC['keyword'];
        $condition .= " and ( id = '{$key}' or uniacid = '{$key}' or uniname like '%{$key}%' or name like '%{$key}%') ";
    }
    if ($_W['uniacid'] != 33) {
        $condition .= " and uniacid={$_W['uniacid']}";
    }

    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_platform_supplier') . $condition . " ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($list as &$item) {

        $item['amount_total'] = pdo_fetchcolumn("select sum(price) from " . tablename('tg_supply_collect') . " where supply_id = {$item['uniacid']} ");;
        $item['amount_total'] = number_format($item['amount_total'] , 2);
        $total_price = pdo_fetchcolumn("select sum(price) from " . tablename('tg_supply_collect') . " where supply_id = {$item['uniacid']} ");
        $item['taxrate_total'] = pdo_fetchcolumn("select sum(taxrate) from " . tablename('tg_supply_collect') . " where supply_id = {$item['uniacid']} and refund <> 2 ");
        $item['taxrate_total'] = number_format($item['taxrate_total'],2);
        $item['sent_total'] = pdo_fetchcolumn("select sum(price-taxrate) from " . tablename('tg_supply_collect') . " where supply_id = {$item['uniacid']} and platform_supplier_orderid > 0 and refund <> 2 ");
        $item['sent_total'] = number_format($item['sent_total'],2);
        $item['refund_total'] = pdo_fetchcolumn("select sum(refund_price) from " . tablename('tg_supply_collect') . " where supply_id = {$item['uniacid']} and refund = 2 ");
        $item['refund_total'] = number_format($item['refund_total'],2);
        $item['unsent_total'] = number_format(floatval($total_price) - floatval($item['sent_total']) - floatval($item['taxrate_total']) - floatval($item['refund_total']) , 2);

        $score = pdo_fetch("select total_score,platform_cycle from " . tablename('tg_supply_score') . " where supply_id = '{$item['supply_id']}' ");
        if ($score) {
            $item['total_score'] = $score['total_score'];
            $item['platform_cycle'] = $score['platform_cycle'];
        } else {
            $item['total_score'] = 5;
            $item['platform_cycle'] = 7;
        }
        $item['id_card_xy'] = tomedia($item['id_card_xy']);
        unset($score);
        unset($item);
    }

    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_platform_supplier') . $condition);
    $pager = pagination($total, $pindex, $psize);
}
if ($op == 'supply_pay') {

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;

    $condition = " where 1 ";

    if (!empty($_GPC['keyword'])) {
        $key = $_GPC['keyword'];
        $condition .= " and ( id = '{$key}' or uniacid = '{$key}' or uniname like '%{$key}%' or name like '%{$key}%' ) ";
    }

    $condition .= " and status = 0 and financial_price = 0 ";


    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_platform_supplier') . $condition . " ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

    $total = count($list);
    $pager = pagination($total, $pindex, $psize);
}
if ($op == 'supply_check') {

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;

    $condition = " where 1 ";

    if (!empty($_GPC['keyword'])) {
        $key = $_GPC['keyword'];
        $condition .= " and ( id = '{$key}' or uniacid = '{$key}' or uniname like '%{$key}%' or name like '%{$key}%' ) ";
    }

    $condition .= " and type <> 0 and status = 0 and financial_price > 0 ";


    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_platform_supplier') . $condition . " ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

    $total = count($list);
    $pager = pagination($total, $pindex, $psize);
}
if ($op == 'sub_check') {

    $id = $_GPC['id'];
    $data = array();
    $data['updatetime'] = TIMESTAMP;
    $data['type'] = 0;
    $data['status'] = 0;
    $check_code = pdo_fetch('select * from ' . tablename('tg_scan_code') . " where code='{$_GPC['code']}'");
    if (!empty($check_code)) {
        // $respose=ihttp_request("https://api.weixin.qq.com/cgi-bin/user/info?access_token={$_W['access_token']}&openid={$check_code['openid']}&lang=zh_CN");
        //  var_dump();
        $fans = pdo_fetch('SELECT follow FROM ' . tablename('mc_mapping_fans') . ' WHERE openid=:openid  ', array(':openid' => $check_code['openid']));
        if ($fans['follow'] == 1) {
            $data['openid'] = $check_code['openid'];
            $res = pdo_update('tg_platform_supplier', $data, array('id' => $id));
            //pdo_delete('tg_scan_code',array('id'=>$check_code['id']));
            $dat['openid'] = $data['openid'];
            if ($dat['openid']) {
                $dat['first'] = '供应商入驻申请进度';
                $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
                $dat['keyword2'] = '供应商入驻申请';
                $dat['keyword3'] = '入驻火蝶云极限单品供应商申请提交成功，等候财务打款';
                $dat['keyword4'] = '审核中';
                $dat['remark'] = '';
                pdo_insert('tg_service_process', $dat);

                $acceptor = pdo_getall('tg_platform_acceptor', array('status' => 1));
                if ($acceptor) {
                    $accept = array();
                    $shop = pdo_get('tg_platform_supplier', array('id' => $id));
                    $accept['first'] = '供应商入驻申请进度';
                    $accept['keyword1'] = $dat['keyword1'];
                    $accept['keyword2'] = '供应商入驻申请';
                    $accept['keyword3'] = "【" . $shop['name'] . '】入驻火蝶云极限单品供应商申请提交成功，等候财务打款';
                    $accept['keyword4'] = '审核中';
                    $accept['remark'] = '';
                    foreach ($acceptor as $item) {
                        $accept['openid'] = $item['openid'];
                        pdo_insert('tg_service_process', $accept);
                    }
                }

            }
            die(json_encode(array('errno' => 1, 'message' => '申请提交成功！')));
        } else {
            die(json_encode(array('errno' => 0, 'message' => '您还未关注乐想拼购公众号，请立即关注此公众号以便及时接收平台信息！！')));
        }
    } else {
        die(json_encode(array('errno' => 0, 'message' => '通信失败' . $_GPC['code'])));
    }

}
//供应商审核
if ($op == 'check') {
    $status = $_GPC['status'];
    $id = $_GPC['id'];
    $reason = $_GPC['reason'];
    $data = array();
//    $data['updatetime'] = TIMESTAMP;
    $data['checktime'] = TIMESTAMP;
    $data['status'] = $status;
    $data['reason'] = $reason;
    $res = pdo_update('tg_platform_supplier', $data, array('id' => $id));

    if ($status == 1) {
        $message = '审核成功';
    } else {
        $message = '审核失败';
        $reason = '审核失败' . "(原因:" . $reason . ") 请前往【极限单品】【供应商入驻】修改提交审核";
    }

    $supplier = pdo_fetch("SELECT openid FROM " . tablename('tg_platform_supplier') . " WHERE id = " . $id);
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '供应商入驻申请进度';
        $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $dat['keyword2'] = '入驻审核结果';
        $dat['keyword3'] = '入驻火蝶云极限单品供应商申请' . $reason;
        $dat['keyword4'] = $message;
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }

    if ($res > 0) {
        die(json_encode(array('errno' => 0, 'message' => '审核成功！')));
    }

    die(json_encode(array('errno' => 1, 'message' => '审核失败！')));
}
//供应商上下架
if ($op == 'shelves') {
    $status = $_GPC['status'];
    $id = $_GPC['id'];
    $reason = $_GPC['reason'];
    $data = array();
//    $data['updatetime'] = TIMESTAMP;
    $data['shelvestime'] = TIMESTAMP;
    $data['status'] = $status;
    $data['reason'] = $reason;
    $res = pdo_update('tg_platform_supplier', $data, array('id' => $id));

    if ($status == 1) {
        $reason = '上架成功';
        $message = '供应商上架成功';
    } else {
        $message = '供应商下架';
        $reason = '已被下架' . "(原因:" . $reason . ")";
    }

    $supplier = pdo_fetch("SELECT openid FROM " . tablename('tg_platform_supplier') . " WHERE id = " . $id);
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '供应商上下架';
        $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $dat['keyword2'] = '供应商上下架结果';
        $dat['keyword3'] = '火蝶云极限单品供应商' . $reason;
        $dat['keyword4'] = $message;
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }

    if ($res > 0) {
        die(json_encode(array('errno' => 0, 'message' => '审核成功！')));
    }

    die(json_encode(array('errno' => 1, 'message' => '审核失败！')));
}
if ($op == "cat") {
    $id = $_GPC['id'];
    $supplier = pdo_fetch("SELECT * FROM " . tablename("tg_platform_supplier") . " WHERE id = :id", array(":id" => $id));
    include wl_template("platform/platform_detail");
    exit();
}
if ($op == 'financial_price') {
    $id = $_GPC['id'];
    $price = $_GPC['price'];
    $orderno = $_GPC['orderno'];
    $data['type'] = 0;
    $data['financial_price'] = $price;
    $data['financial_orderno'] = $orderno;
    pdo_update('tg_platform_supplier', $data, array('id' => $id));
    $supplier = pdo_fetch("SELECT * FROM " . tablename("tg_platform_supplier") . " WHERE id = :id", array(":id" => $id));
    //短信通知
    //  $content=$supplier['bank_name'].'账户号:'.substr($supplier['bank_account'],-6).'打了一笔款，请将款数填入乐享拼购系统';
    send_platform_sms(substr($supplier['bank_account'], -6), $supplier['contact_phone'], $supplier['bank_name']);
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '供应商入驻申请进度';
        $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $dat['keyword2'] = '财务打款验证';  //【火蝶科技】您好，我司已向账户名:账户尾号:汇入一笔随机金额款项，请将金额回填乐享拼购系统完成验证。
        $dat['keyword3'] = '我司已向账户名' . $supplier['bank_name'] . '账户尾号:' . substr($supplier['bank_account'], -6) . '汇入一笔随机金额款项，请将具体金额回填乐享拼购系统完成验证';
        $dat['keyword4'] = '财务打款验证';
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }
    die(json_encode(array('status' => 1)));
}
if ($op == 'financial_check') {
    $id = $_GPC['id'];
    $price = $_GPC['price'];
    $supplier = pdo_fetch("SELECT * FROM " . tablename("tg_platform_supplier") . " WHERE id = :id", array(":id" => $id));
    if ($price != $supplier['financial_price']) {
        $data['financial_check_num'] = $supplier['financial_check_num'] + 1;
        pdo_update('tg_platform_supplier', $data, array('id' => $id));
        //短信通知
        // send_platform_sms($content,$supplier['contact_phone'], $supplier['contact_person']);
        $dat['openid'] = $supplier['openid'];
        if ($dat['openid']) {
            $dat['first'] = '供应商入驻申请进度';
            $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $dat['keyword2'] = '商家验证';  //【火蝶科技】您好，我司已向账户名:账户尾号:汇入一笔随机金额款项，请将金额回填乐享拼购系统完成验证。
            $dat['keyword3'] = '商家验证金额错误';
            $dat['keyword4'] = '商家验证';
            $dat['remark'] = '';
            pdo_insert('tg_service_process', $dat);
        }
        die(json_encode(array('status' => 0)));
    } else {
        $data['type'] = 2;
        pdo_update('tg_platform_supplier', $data, array('id' => $id));
        //短信通知
        // $content=$supplier['bank_name'].'账户号:'.$supplier['bank_account'].'打了一笔款，请将款数填入乐享拼购系统';

        $dat['openid'] = $supplier['openid'];
        if ($dat['openid']) {
            $dat['first'] = '供应商入驻申请进度';
            $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $dat['keyword2'] = '供应商验证';  //【火蝶科技】您好，我司已向账户名:账户尾号:汇入一笔随机金额款项，请将金额回填乐享拼购系统完成验证。
            $dat['keyword3'] = '供应商验证成功';
            $dat['keyword4'] = '供应商验证';
            $dat['remark'] = '';
            pdo_insert('tg_service_process', $dat);

            $acceptor = pdo_getall('tg_platform_acceptor', array('status' => 1));
            if ($acceptor) {
                $accept = array();
                $shop = pdo_get('tg_platform_supplier', array('id' => $id));
                $accept['first'] = '供应商入驻申请进度';
                $accept['keyword1'] = $dat['keyword1'];
                $accept['keyword2'] = '供应商验证';
                $accept['keyword3'] = "【" . $shop['name'] . '】供应商财务打款验证成功';
                $accept['keyword4'] = '供应商验证';
                $accept['remark'] = '';
                foreach ($acceptor as $item) {
                    $accept['openid'] = $item['openid'];
                    pdo_insert('tg_service_process', $accept);
                }
            }

        }
        // send_platform_sms($content,$supplier['contact_phone'], $supplier['contact_person']);
        die(json_encode(array('status' => 1)));
    }
}
include wl_template('platform/platform_list');
exit();
?>