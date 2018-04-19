<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * order.ctrl
 * 订单控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('order');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('functions');
$banquanfunction = checkfunc(8170);
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'detail';
$pagetitle = '我的售后';
if ($op == 'detail') {
    $id = intval($_GPC['id']);
    if ($id) {
        $order = order_get_by_id($id);
        $serves = pdo_fetch("SELECT * FROM " . tablename('tg_order_service') . " WHERE orderno=:orderno", array(':orderno' => $order['orderno']));
        if ($order['bukuanstatus'] == '2') {
            $bukuan = pdo_fetch("select * from " .tablename('tg_order') ." where status = 3 and master_orderno = '{$order['orderno']}' ");
            $order['bkprice'] = $bukuan['price'];
            $order['pay_price'] += $bukuan['price'];
        }
        $goods = goods_get_by_params(" id = {$order['g_id']} ");
        if ($order['merchantid']) {
            $merchant = merchant_get_by_id($order['merchantid']);
            $order['merchant_name'] = $merchant['name'];
        } else {
            $order['merchant_name'] = $_W['account']['name'];
        }
        $sto = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$order['comadd']}' and uniacid='{$_W['uniacid']}'");
        if ($goods['is_hexiao'] == 1) {

            //门店信息
            $storesids = explode(",", $goods['hexiao_id']);
            foreach ($storesids as $key => $value) {
                if ($value) {
                    $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
                }
            }
        }
        $sal = pdo_fetch("select * from" . tablename('tg_member') . " where from_user ='" . $order['veropenid'] . "'");
    }
    include wl_template('order/servers_detail');
}

if ($op == 'cancel') {
    $orderno = $_GPC['orderno'];
    if ($orderno) {
        $order = order_update_by_params(array('status' => 9), array('orderno' => $orderno));
        if ($order) {
            //$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE orderno = :orderno", array(':orderno' => $orderno));
            //$goods = goods_get_by_params(" id={$item['g_id']} ");
            //cancelorder($_W['openid'], $item['price'], $goods['gname'], $orderno, '');
            wl_json(1);
        } else {
            wl_json(0, '取消订单失败！');
        }
    } else {
        wl_json(0, '缺少订单号');
    }
}

if ($op == 'topay') {
    $orderno = $_GPC['orderno'];
    if ($orderno) {
        $order = order_get_by_params(" orderno = '{$orderno}' ");
        if ($order['status'] == 0) {

            wl_json(1);
        } else {
            wl_json(0, '订单状态错误');
        }
    } else {
        wl_json(0, '缺少订单号');
    }
}
if ($op == 'part_refund') {
    $orderno = $_GPC['orderno'];
    $serves = pdo_fetch('SELECT * FROM ' . tablename('tg_order_service') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));
    $order = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));
    $feedbackfee = $serves['feedbackfee'];

    if ($order['pay_type'] == 9) {
        $res = balance_payment_refund($order['transid'], 2 , $order , $serves['servicefeedback'] , $feedbackfee);
        if ($res['status'] == 1) {
            pdo_update('tg_order_service', array('overtime' => TIMESTAMP, 'overfeedtype' => $serves['feedtype']), array('orderno' => $serves['orderno']));
            pdo_update('tg_order', array('servestype' => 3, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));

            wl_json(1, $res['message']);
        } else {
            wl_json(0, $res['message']);
        }
    } else {
        $rs = partrefund($orderno, 1, $feedbackfee);
        if ($rs == 'success') {
            pdo_update('tg_order_service', array('overtime' => TIMESTAMP, 'overfeedtype' => $serves['feedtype']), array('orderno' => $serves['orderno']));
            pdo_update('tg_order', array('servestype' => 3, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));

            wl_json(1, '退款成功，售后完成');
        } else {
            wl_json(0, '退款失败，请联系客服处理');
        }
    }
}
if ($op == 'check') {
    $orderno = $_GPC['orderno'];
    order_update_by_params(array('servestype' => 1), array('orderno' => $orderno));
    $serves = pdo_fetch('SELECT * FROM ' . tablename('tg_order_service') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));


    if (empty($serves)) {
        $data = array(
            'orderno' => $orderno,
            'servicereson' => $_GPC['servicereson'],
            'serviceremark' => $_GPC['serviceremark'],
            'serversstatus' => 1,
            'servicetime' => TIMESTAMP
        );
        pdo_insert('tg_order_service', $data);
        pdo_update('tg_order', array('servestype' => 1, 'servesupdatetime' => TIMESTAMP), array('orderno' => $orderno));

    } else {
        pdo_update('tg_order', array('servestype' => 1, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));
        if ($serves['feedtype'] == '退货') {

            pdo_update('tg_order_service', array('servicelastremark' => '同意退货', 'servicelasttime' => TIMESTAMP, 'feedbackexpress' => $_GPC['feedbackexpress'], 'feedbackexpresssn' => $_GPC['feedbackexpresssn']), array('orderno' => $serves['orderno']));

        } else {
            pdo_update('tg_order_service', array('servicelastremark' => $_GPC['servicelastremark'], 'servicelasttime' => TIMESTAMP), array('orderno' => $serves['orderno']));

        }
    }
    // 售后通知
    $o = order_get_by_params('`orderno`="'.$_GPC['orderno'].'"');
    if ($o) {
        // 售后接收员
        $set = setting_get_list();
        if(empty($set)){
            $settings = $this->module['config'];
        }else{
            $settings = array();
            foreach($set as$key=>$value){
                $settingarray= $value['value'];
                foreach($settingarray as $k=>$v){
                    $settings[$k] = $v;
                }
            }
        }
        // 通知发送
        $serviceOpenid = $settings['service_openid'];

        if ($serviceOpenid) {
            $first = "“".$o['addname']."”发起售后";
            $keyword2 = "售后待处理";
            $keyword3 = date("Y/m/d h:i:s");
            $remark = "退款原因：". $_GPC['servicereson']."；申请理由：". $_GPC['serviceremark'];
            send_service($serviceOpenid, $first, $o['orderno'], $keyword2, $keyword3, $remark);
        }
    } else {
        wl_json(-1, '订单不存在');
    }
    wl_json(1, '申请成功');
}
exit();