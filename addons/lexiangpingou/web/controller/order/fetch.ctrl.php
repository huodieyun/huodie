<?php
defined('IN_IA') or exit('Access Denied');
$ops = array('display', 'detail', 'confirm', 'output', 'confrimpay', 'confirmsend', 'cancelsend', 'refund', 'check');
$op_names = array('订单列表', '订单详情', '列表核销', '导出', '确认付款', '订单详情核销', '取消核销', '退款');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'order', 'fetch', $ops[$key], '订单', '自提订单', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'display';
load()->func('tpl');
wl_load()->model("merchant");
wl_load()->model("goods");
$uniacid = $_W['uniacid'];
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$allgoods = pdo_fetchall("select gname,id from" . tablename('tg_goods') . "where uniacid=:uniacid", array(':uniacid' => $_W['uniacid']));
if ($op == 'confirm') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (pdo_update('tg_order', array('status' => 4), array('id' => $id))) {
        /*更新可结算金额*/
        if (!empty($item['merchantid'])) {
            merchant_update_no_money($item['price'], $item['merchantid']);
        }
        /*记录操作*/
        $oplogdata = serialize($item);
        oplog('admin', "后台列表核销", web_url('order/fetch/confirm'), $oplogdata);
        die(json_encode(array('errno' => 0, 'message' => '核销成功！')));
    } else {
        die(json_encode(array('errno' => 1, 'message' => '核销失败！')));
    }
}
if ($op == 'display') {
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $condition = "  uniacid = :uniacid";
    $paras = array(':uniacid' => $_W['uniacid']);

    $servestype = $_GPC['servestype'];
    $transid = $_GPC['transid'];
    $pay_type = $_GPC['pay_type'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];

    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']);
        $condition .= " AND  servesupdatetime >= :starttime AND  servesupdatetime <= :endtime ";
        $paras[':starttime'] = $starttime;
        $paras[':endtime'] = $endtime;
    }
    if (trim($_GPC['goodsid']) != '') {
        $condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
    }
    if (trim($_GPC['goodsid2']) != '') {
        $condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
    }
    if (!empty($_GPC['merchantid'])) {
        $condition .= " AND  merchantid={$_GPC['merchantid']} ";
    }
    if (!empty($_GPC['transid'])) {

        $condition .= " AND  transid =  '{$_GPC['transid']}'";
    }
    if (!empty($_GPC['pay_type'])) {

        $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
    } elseif ($_GPC['pay_type'] === '0') {
        $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
    }
    if (!empty($_GPC['hexiaoma'])) {
        $condition .= " AND  hexiaoma LIKE '%{$_GPC['hexiaoma']}%'";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND (addname LIKE '%{$_GPC['member']}%' or mobile LIKE '%{$_GPC['member']}%')";
    }
    if ($servestype == '') {
        $condition .= " AND  servestype > '" . intval($servestype) . "'";
    } else {
        if ($servestype == 11) {
            $condition .= " AND status = " . intval($servestype);
        } else {
            $condition .= " AND  servestype = '" . intval($servestype) . "'";
        }
    }
    if ($_W['user']['merchant_id'] > 0) {
        $condition .= " and  merchantid = {$_W['user']['merchant_id']} ";
    }
    $sql = "select  * from " . tablename('tg_order') . " where $condition   ORDER BY ptime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
    $list = pdo_fetchall($sql, $paras);
    $paytype = array('0' => array('css' => 'default', 'name' => '未支付'), '1' => array('css' => 'info', 'name' => '余额支付'), '2' => array('css' => 'success', 'name' => '在线支付'), '3' => array('css' => 'warning', 'name' => '货到付款'));
    $orderstatus = array(
        '0' => array('css' => 'default', 'name' => '待付款'),
        '1' => array('css' => 'info', 'name' => '已付款'),
        '2' => array('css' => 'warning', 'name' => '待收货'),
        '3' => array('css' => 'success', 'name' => '已签收'),
        '4' => array('css' => 'success', 'name' => '已退款'),
        '5' => array('css' => 'success', 'name' => '强退款'),
        '6' => array('css' => 'danger', 'name' => '部分退款'),
        '7' => array('css' => 'success', 'name' => '已退款'),
        '8' => array('css' => 'success', 'name' => '待发货'),
        '9' => array('css' => 'success', 'name' => '已取消'),
        '10' => array('css' => 'success', 'name' => '待退款'),
        '11' => array('css' => 'success', 'name' => '退款异常')
    );
    foreach ($list as &$value) {
        $options = pdo_fetch("SELECT title,productprice,marketprice FROM " . tablename("tg_goods_option") . " WHERE id=:id LIMIT 1", array(":id" => $value['optionid']));
        $value['optionname'] = $options['title'];
        $s = $value['status'];
        $value['statuscss'] = $orderstatus[$value['status']]['css'];
        $value['status'] = $orderstatus[$value['status']]['name'];
        $value['css'] = $paytype[$value['pay_type']]['css'];
        if ($value['pay_type'] == 2) {
            if (empty($value['transid'])) {
                $value['paytype'] = '微信支付';
            } else {
                $value['paytype'] = '微信支付';
            }
        } else {
            $value['paytype'] = $paytype[$value['pay_type']]['name'];
        }

        $m = $value['merchantid'];
        if ($m == 0) {
            $value['merchant_name'] = $_W['account']['name'];
        } else {
            $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
            $value['merchant_name'] = $name['name'];
        }

        $goodsss = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$value['g_id']}'");
        $value['gid'] = $goodsss['id'];
        $value['gname'] = $goodsss['gname'];
        $value['gimg'] = $goodsss['gimg'];
        $value['merchant'] = pdo_fetch("select name from" . tablename('tg_merchant') . "where id = '{$value['merchantid']}' and uniacid={$_W['uniacid']}");
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition ", $paras);
    $pager = pagination($total, $pindex, $psize);

    $all = intval(pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and servestype>0 "));
    $status0 = intval(pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and servestype=1 "));
    $status1 = intval(pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and servestype=2 "));
    $status2 = intval(pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and servestype=3 "));
    $status11 = intval(pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status = 11 "));
} elseif ($op == 'detail') {
    $id = intval($_GPC['id']);
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (empty($item)) {
        $tip = '抱歉，订单不存在!';
        echo "<script>alert('" . $tip . "');location.href='" . url('order/fetch') . "';</script>";
        exit;
    }
    $serves = pdo_fetch("SELECT * FROM " . tablename('tg_order_service') . " WHERE orderno=:orderno", array(':orderno' => $item['orderno']));

    if ($item['status'] == 7) {
        $refund_record = pdo_fetch("SELECT * FROM " . tablename('tg_refund_record') . " WHERE orderid = :id", array(':id' => $id));
    }
    if ($item['bukuanstatus'] == 2) {
        $bukuan = pdo_fetch("select price from " . tablename('tg_order') . " where master_orderno = '{$item['orderno']}' ");
        $item['price'] += $bukuan['price'];
    }

    if ($item['veropenid']) {
        if ($item['veropenid'] == 'houtai') {
            $item['verstore'] = '后台核销';
            $item['vername'] = '后台核销';
        } else {
            $list = pdo_fetch("select * from" . tablename('tg_saler') . "where uniacid='{$_W['uniacid']}' and openid = '{$item['veropenid']}'");
            if ($list['storeid']) {
                $storeid_arr = explode(',', $list['storeid']);
                $storename = '';
                foreach ($storeid_arr as $k => $v) {
                    if ($v) {
                        $store = pdo_fetch("select * from" . tablename('tg_store') . "where id='{$v}'");
                        $storename .= $store['storename'] . "/";
                    }
                }
                $storename = substr($storename, 0, strlen($storename) - 1);
            } else {
                $storename = '全局核销员';
            }
            $item['verstore'] = $storename;
            $item['vername'] = $list['nickname'];
        }
    }

    if (checksubmit('getgoods')) {
        if ($_GPC['recvname'] == '' || $_GPC['recvmobile'] == '' || $_GPC['recvaddress'] == '' || $_GPC['addresstype'] == '') {
            message('请输入完整信息！');
        } else {
            pdo_update('tg_order', array('addname' => $_GPC['recvname'], 'mobile' => $_GPC['recvmobile'], 'address' => $_GPC['recvaddress'], 'addresstype' => $_GPC['addresstype']), array('id' => $id));
        }
        $tip = '修改成功！';
        echo "<script>alert('" . $tip . "');location.href='" . url('order/bdelete_detail') . "';</script>";
        exit;

    }

    $item['user'] = pdo_fetch("SELECT * FROM " . tablename('tg_address') . " WHERE id = {$item['addressid']}");
    $goods = goods_get_by_params(" id={$item['g_id']} ");
    $item['goods'] = $goods;
    include wl_template('order/bdelete_detail');
    exit;
} elseif ($op == 'check') {
    $serves = pdo_fetch('SELECT * FROM ' . tablename('tg_order_service') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));
    $order = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));
    if ($_GPC['feedtype'] == '' || $_GPC['servicefeedback'] == '') {
        $bdata = array('errno' => 0, 'message' => '处理方法或意见不能为空');
        die(json_encode($bdata));
        exit();
    } else {
        switch ($_GPC['feedtype']) {
            case '不处理':
                $ftype = 11;
                break;
            case '部分退款':
                $ftype = 12;
                break;
            case '全额退款并退货':
                $ftype = 13;
                break;
            case '全额退款':
                $ftype = 14;
                break;
        }
        if ($_GPC['feedtype'] == '部分退款') {
            if ($_GPC['feedbackfee'] == '') {

                $bdata = array('errno' => 0, 'message' => '请输入退款金额！');
                die(json_encode($bdata));
                exit();
            }
        }

        if (empty($serves['servicelastremark'])) {
            pdo_update('tg_order', array('servestype' => 2, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));

            pdo_update('tg_order_service', array('feedtype' => $_GPC['feedtype'], 'updatetime' => TIMESTAMP, 'servicefeedback' => $_GPC['servicefeedback'], 'feedbackfee' => $_GPC['feedbackfee'], 'feedtime' => TIMESTAMP), array('orderno' => $serves['orderno']));
        } else {
            pdo_update('tg_order', array('servestype' => 2, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));

            pdo_update('tg_order_service', array('servicelastfeedtype' => $_GPC['feedtype'], 'updatetime' => TIMESTAMP, 'overfeedtype' => $_GPC['feedtype'], 'overtime' => TIMESTAMP, 'feedbackfee' => $_GPC['feedbackfee'], 'servicelastfeedback' => $_GPC['servicefeedback'], 'servicelastfeedtime' => TIMESTAMP), array('orderno' => $serves['orderno']));
            if ($_GPC['feedbackfee'] > 0 && $_GPC['feedtype'] == '部分退款') {
                if ($order['pay_type'] == 9) {
                    $res = balance_payment_refund($order['transid'], 2, $order, $_GPC['servicefeedback'], $_GPC['feedbackfee']);
                    $bdatas = array('errno' => $res['status'], 'message' => $res['message']);
                    die(json_encode($bdatas));
                    exit();
                } else {
                    $rs = partrefund($serves['orderno'], 1, $_GPC['feedbackfee']);
                    if ($rs == 'success') {
                        pdo_update('tg_order', array('servestype' => 3, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));

                        $bdatas = array('errno' => 1, 'message' => '退款成功！');
                        die(json_encode($bdatas));
                        exit();
                    } else {
                        $bdatas = array('errno' => 0, 'message' => '退款失败！');
                        die(json_encode($bdatas));
                        exit();
                    }
                }
            }

        }
        if ($_GPC['feedtype'] == '全额退款') {
            pdo_update('tg_order_service', array('overfeedtype' => $_GPC['feedtype'], 'overtime' => TIMESTAMP, 'updatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));
            if ($order['pay_type'] == 9) {
                $res = balance_payment_refund($order['transid'], 1, $order, $_GPC['servicefeedback']);
                $bdatas = array('errno' => $res['status'], 'message' => $res['message']);
                die(json_encode($bdatas));
                exit();
            } else {
                pdo_update('tg_order', array('status' => 10), array('orderno' => $serves['orderno']));
            }
            pdo_update('tg_order', array('servestype' => 3, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));
            $order = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));

            if ($order['selltype'] == 7 && $order['bukuanstatus'] == 2) {
                pdo_update('tg_order', array('status' => 10), array('master_orderno' => $order['orderno']));
            }

        }
        $bdatas = array('errno' => 1, 'message' => '处理成功！');
        die(json_encode($bdatas));
        exit();
    }

} elseif ($op == 'output') {

    $status = $_GPC['status'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];
    $transid = $_GPC['transid'];
    $paytype = $_GPC['pay_type'];
    $starttime = strtotime($_GPC['time']['start']);
    $endtime = strtotime($_GPC['time']['end']);
    $condition = " uniacid='{$_W['uniacid']}' and is_hexiao <>0 ";
    if (trim($_GPC['goodsid']) != '') {
        $condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
    }
    if (trim($_GPC['goodsid2']) != '') {
        $condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
    }
    if (!empty($_GPC['merchantid'])) {
        $condition .= " AND  merchantid={$_GPC['merchantid']} ";
    }
    if ($status != '') {
        $condition .= " AND status= '{$status}' ";
    }
    if ($keyword != '') {
        $condition .= " AND g_id = '{$keyword}'";
    }
    if (!empty($member)) {
        $condition .= " AND (addname LIKE '%{$member}%' or mobile LIKE '%{$member}%')";
    }
    if (!empty($time)) {
        $condition .= " AND  createtime >= $starttime AND  createtime <= $endtime  ";
    }
    if (!empty($transid)) {

        $condition .= " AND  transid =  '{$transid}'";
    }
    if (!empty($paytype)) {
        $condition .= " AND  pay_type = '{$paytype}'";
    }
    $orders = pdo_fetchall("select * from" . tablename('tg_order') . "where $condition  and mobile<>'虚拟'");
//	echo "<pre>";print_r($condition);exit;
    switch ($status) {
        case NULL:
            $str = '全部订单_' . time();
            break;
        case 1:
            $str = '已支付订单_' . time();
            break;
        case 2:
            $str = '待消费订单' . time();
            break;
        case 3:
            $str = '已完成订单' . time();
            break;
        case 4:
            $str = '已完成订单' . time();
            break;
        case 5:
            $str = '已取消订单' . time();
            break;
        case 6:
            $str = '待退款订单' . time();
            break;
        case 7:
            $str = '已退款订单' . time();
            break;
        default:
            $str = '待支付订单' . time();
            break;
    }


    /* 输入到CSV文件 */
    $html = "\xEF\xBB\xBF";
    /* 输出表头 */
    $filter = array('aa' => '订单编号', 'bb' => '姓名', 'cc' => '电话', 'dd' => '总价(元)', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'hh' => '收货地址', 'ii' => '微信订单号', 'jj' => '快递单号', 'kk' => '快递名称', 'll' => '地址类型', 'mm' => '商品规格');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    $html .= "\n";
    foreach ($orders as $k => $v) {
        if ($v['status'] == '0') {
            $thisstatus = '未支付';
        }
        if ($v['status'] == '1') {
            $thisstatus = '已支付';
        }
        if ($v['status'] == '2') {
            $thisstatus = '待消费';
        }
        if ($v['status'] == '3') {
            $thisstatus = '已完成';
        }
        if ($v['status'] == '4') {
            $thisstatus = '已完成';
        }
        if ($v['status'] == '5') {
            $thisstatus = '已取消';
        }
        if ($v['status'] == '6') {
            $thisstatus = '待退款';
        }
        if ($v['status'] == '7') {
            $thisstatus = '已退款';
        }
        if ($v['status'] == '') {
            $thisstatus = '全部订单';
        }
        $thistatus = '待发货';
        $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$v['g_id']}' and uniacid='{$_W['uniacid']}'");
        $time = date('Y-m-d H:i:s', $v['createtime']);
        $options = pdo_fetch("SELECT title,productprice,marketprice,stock FROM " . tablename("tg_goods_option") . " WHERE id=:id LIMIT 1", array(":id" => $v['optionid']));
        if (empty($options['title'])) {
            $options['title'] = '不限';
        }
        $orders[$k]['aa'] = $v['orderno'];
        $orders[$k]['bb'] = $v['addname'];
        $orders[$k]['cc'] = $v['mobile'];
        $orders[$k]['dd'] = $v['price'];
        $orders[$k]['ee'] = $thisstatus;
        $orders[$k]['ff'] = $time;
        $orders[$k]['gg'] = $goods['gname'];
        $orders[$k]['ii'] = $v['transid'];
        $orders[$k]['mm'] = $options['title'];
        foreach ($filter as $key => $title) {
            $html .= $orders[$k][$key] . "\t,";
        }
        $html .= "\n";
    }
    /* 输出CSV文件 */
    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename={$str}.csv");
    echo $html;
    exit();
}
if ($op == 'confrimpay') {
    $id = $_GPC['id'];
    pdo_update('tg_order', array('status' => 1, 'pay_type' => 2, 'ptime' => TIMESTAMP), array('id' => $id));
    $tip = '确认订单付款操作成功！';
    echo "<script>alert('" . $tip . "');location.href='" . url('order/fetch') . "';</script>";
    exit;


}
if ($op == 'confirmsend') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    $r = pdo_update('tg_order', array('status' => 4, 'express' => $_GPC['express'], 'expresssn' => $_GPC['expresssn'], 'sendtime' => TIMESTAMP), array('id' => $id));
    if ($r) {
        /*更新可结算金额*/
        if (!empty($item['merchantid'])) {
            merchant_update_no_money($item['price'], $item['merchantid']);
        }
        /*记录操作*/
        $oplogdata = serialize($item);
        oplog('admin', "后台确认核销", web_url('order/fetch/confirmsend'), $oplogdata);
    }
    $tip = '操作成功！';
    echo "<script>alert('" . $tip . "');location.href='" . url('order/fetch/confirmsend') . "';</script>";
    exit;

}
if ($op == 'cancelsend') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    $r = pdo_update('tg_order', array('status' => 2, 'sendtime' => '', 'express' => '', 'expresssn' => ''), array('id' => $id));
    if ($r) {
        /*更新可结算金额*/
        if (!empty($item['merchantid'])) {
            merchant_update_no_money(0 - $item['price'], $item['merchantid']);
        }
        /*记录操作*/
        $oplogdata = serialize($item);
        oplog('admin', "后台取消核销", web_url('order/fetch/confirmsend'), $oplogdata);
    }
    $tip = '操作成功！';
    echo "<script>alert('" . $tip . "');location.href='" . url('order/fetch/confirmsend') . "';</script>";
    exit;

}
if ($op == 'refund') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    $orderno = $item['orderno'];
    $res = refund($orderno, 2);
    if ($res == 'success') {
        /*记录操作*/
        $oplogdata = serialize($item);
        oplog('admin', "后台订单详情退款", web_url('order/order/refund'), $oplogdata);
        /*退款成功消息提醒*/
        $url = app_url('order/order/detail', array('id' => $item['id']));
        $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$item['g_id']}'");
        refund_success($item['orderno'], $goods['gname'], $item['openid'], $item['price'], time(), $url);
        $tip = '退款成功了！';
        echo "<script>alert('" . $tip . "');location.href='" . url('order/fetch') . "';</script>";
        exit;

    } else {
        $tip = '退款失败，服务器正忙，请稍等等';
        echo "<script>alert('" . $tip . "');location.href='" . url('order/fetch') . "';</script>";
        exit;
        message('退款失败，服务器正忙，请稍等等！', referer(), 'fail');
    }
}
include wl_template('order/bdelete');
exit();