<?php

$_W['page']['title'] = "极限单品 - 订单管理";
$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}
global $_W, $_GPC;
load()->func("tpl");
$uniacid = $_W['uniacid'];
$province = pdo_getall('erp_area', array('level' => 1));

if ($op == "display") {

    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $keyword = $_GPC['keyword'];
    $con = '';
    if (!empty($keyword)) {
        if ($_GPC['con_type'] == 1) {
            $con .= " and gname like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 2) {
            $con .= " and singleno like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 3) {
            $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
        }
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']);
        $con .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
    }
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_order') . " WHERE platform_status = 0 AND uni_pay > 0 {$con} order by id desc LIMIT " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_order') . " WHERE platform_status = 0 AND uni_pay > 0 {$con} ");
    foreach ($list as &$item) {
        $item['pici'] = "JXDP" . sprintf("%04d", $item['supply_goodsid']);
        $item['uni_payimg'] = tomedia($item['uni_payimg']);
        $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $item['uniacid']);
        $item['uni_name'] = $ac['name'];
        unset($ac);
        unset($item);
    }
    $pager = pagination($total, $page, $size);

}

if ($op == 'shop') {
    $id = $_GPC['id'];

    $page = max(1, intval($_GPC['page']));
    $size = 10;

    $order = pdo_fetchall("select * from " . tablename('tg_order') . " where uniacid = '{$uniacid}' and g_id = '{$id}' and status = 8 and singleno is null order by id asc limit " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_order') . " where uniacid = '{$uniacid}' and g_id = '{$id}' and status = 8 and singleno is null order by id asc ");
    $pager = pagination($total, $page, $size);
}

if ($op == 'shop_submit') {

    $id = $_GPC['id'];

    $order = pdo_fetchall("select * from " . tablename('tg_order') . " where uniacid = '{$uniacid}' and g_id = '{$id}' and status = 8 and singleno is null order by id asc ");

    if (!empty($order)) {
        $data = array();
        $data['singleno'] = $singleno = 'L' . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $data['uniacid'] = $uniacid;
        $data['supply_goodsid'] = $order[0]['supply_goodsid'];
        $goods = pdo_fetch("select supply_id from " . tablename('tg_supply_goods') . " where id = '{$order[0]['supply_goodsid']}' ");
        $data['supply_id'] = $goods['supply_id'];
        $price = 0.0;

        foreach ($order as $value) {
            $collect['supply_id'] = $goods['supply_id'];
            $collect['uniacid'] = $value['uniacid'];
            $collect['orderid'] = $value['id'];
            $collect['goodsid'] = $value['g_id'];
            $collect['goodsname'] = $value['goodsname'];
            $collect['supply_goodsid'] = $value['supply_goodsid'];
            $collect['singleno'] = $singleno;
            $collect['optionname'] = $value['optionname'];
            $collect['num'] = $value['gnum'];
            if (!empty($value['optionname'])) {
                $option = pdo_fetch("select * from " . tablename('tg_goods_supply_option') . " where goodsid = '{$value['supply_goodsid']}' and title = '{$value['optionname']}' ");
                $collect['oprice'] = $option['costprice'];
                $collect['price'] = $option['costprice'] * $value['gnum'];
                $collect['pay_price'] = $option['costprice'] * $value['gnum'];
                $price += $option['costprice'] * $value['gnum'];
            } else {
                $goods = pdo_fetch("select * from " . tablename('tg_supply_goods') . " where id = '{$value['supply_goodsid']}' ");
                $collect['oprice'] = $goods['mprice'];
                $collect['price'] = $goods['mprice'] * $value['gnum'];
                $collect['pay_price'] = $goods['mprice'] * $value['gnum'];
                $price += $goods['mprice'] * $value['gnum'];
            }
            $member = pdo_fetch("select nickname from " . tablename('tg_member') . " where uniacid = '{$_W['uniacid']}' and from_user = '{$value['openid']}' ");
            $collect['nickname'] = $member['nickname'];
            $collect['realname'] = $value['addname'];
            $collect['mobile'] = $value['mobile'];
            $collect['province'] = $value['province'];
            $collect['city'] = $value['city'];
            $collect['county'] = $value['county'];
            $collect['address'] = $value['address'];
            if (!empty($value['province'])) {
                $collect['address'] = $value['detailed_address'];
            }
            $collect['supply_status'] = 0;

            pdo_insert('tg_supply_collect', $collect);
        }
        pdo_update('tg_order', array('singleno' => $singleno), array('uniacid' => $uniacid, 'g_id' => $id, 'status' => 8));

        $data['gname'] = $order[0]['goodsname'];
        $data['price'] = $price;
        $data['createtime'] = TIMESTAMP;
        pdo_insert('tg_supply_order', $data);
        $order_id = pdo_insertid();
        pdo_update('tg_supply_collect', array('supply_orderid' => $order_id), array('singleno' => $singleno));
        die(json_encode(array('order_id' => $order_id, 'message' => '申请成功，请等候供应商处理！')));
//        $tip = '申请成功，请等候供应商处理！';
    } else {
        die(json_encode(array('order_id' => 0, 'message' => '申请失败，暂无可申请订单！')));
//        $tip = '申请失败，暂无可申请订单！';
    }
//    echo "<script>alert(" .$tip .");window.location.href=" .web_url('platform/shop_order' , array('op' => 'order' , 'order_id' => $order_id)) .";</script>";
    exit();
}

if ($op == 'order') {

    $order_id = intval($_GPC['order_id']);
    if ($order_id > 0) {
        $list = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where id = {$order_id} ");
        foreach ($list as &$item) {
            $item['uni_payimg'] = tomedia($item['uni_payimg']);
            unset($item);
        }
//        die(json_encode(array('list' => $list)));
    } else {
        $page = max(1, intval($_GPC['page']));
        $size = 10;
        $keyword = $_GPC['keyword'];
        $con = '';
        $status = intval($_GPC['status']);
        if ($status > 0) {
            $status -= 1;
            $con .= " and supply_status = '{$status}'";
        }
        if (!empty($keyword)) {
            if ($_GPC['con_type'] == 1) {
                $con .= " and gname like '%{$keyword}%' ";
            }
            if ($_GPC['con_type'] == 2) {
                $con .= " and singleno like '%{$keyword}%' ";
            }
            if ($_GPC['con_type'] == 3) {
                $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
            }
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $con .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
        }
        $list = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where uniacid = {$uniacid} " . $con . " order by id desc limit " . ($page - 1) * $size . " , " . $size);
        $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_order') . " where uniacid = '{$uniacid}' " . $con);
        foreach ($list as &$item) {
            $item['uni_payimg'] = tomedia($item['uni_payimg']);
            unset($item);
        }
        $pager = pagination($total, $page, $size);
//        die(json_encode(array('list' => $list)));
    }


}
//子单列表
if ($op == 'collect') {

    $order_id = intval($_GPC['collect_id']);
    if ($order_id > 0) {

        //用于前台显示总单信息
        $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where id = {$order_id} ");
        $order['pici'] = "JXDP" . sprintf("%04d", $order['supply_goodsid']);
        $page = max(1, intval($_GPC['page']));
        $size = 10;
        $mobile = $_GPC['mobile'];
        $realname = $_GPC['realname'];
        $con = '';
        if (!empty($mobile)) {
            $con .= " and mobile like '%{$mobile}%' ";
        }
        if (!empty($realname)) {
            $con .= " and realname like '%{$realname}%' ";
        }
        $list = pdo_fetchall("select * from " . tablename('tg_supply_collect') . " where supply_orderid = {$order_id} " . $con . " order by id desc limit " . ($page - 1) * $size . " , " . $size);
        foreach ($list as &$item) {
            $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = {$item['orderid']}");
//            $item['orderno'] = $o['orderno'];

            $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $item['supply_goodsid']));
            $item['taxrate'] = floatval($goods['taxrate']) * 0.01 * floatval($item['oprice']) * floatval($item['num']);

            $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $item['uniacid']);
            $item['orderno'] = $ac['name'] . "<br>" . $o['orderno'];
            unset($o);
            unset($ac);
            unset($item);
        }
        $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_collect') . " where supply_orderid = '{$order_id}' " . $con);
        $pager = pagination($total, $page, $size);
//        die(json_encode($list));
    } else {
        $page = max(1, intval($_GPC['page']));
        $size = 50;
        $status = intval($_GPC['status']);
        $mobile = $_GPC['mobile'];
        $realname = $_GPC['realname'];
        $con = '';
        if ($status == 1) {
            $con .= " and refund = 1 ";
        } elseif ($status == 2) {
            $con .= " and refund = 2 ";
        } elseif ($status == 3) {
            $con .= " and supply_status = 1 ";
        } elseif ($status == 4) {
            $con .= " and supply_status = 2 ";
            $con .= " and platform_supplier_orderid <= 0 ";
            $con .= " and refund = 0 ";
        }
        if (intval($_GPC['id']) > 0) {
            $con .= ' and supply_id = ' . intval($_GPC['id']);
        }
        if (!empty($mobile)) {
            $con .= " and mobile like '%{$mobile}%' ";
        }
        if (!empty($realname)) {
            $con .= " and realname like '%{$realname}%' ";
        }
        if (!empty($_GPC['singleno'])) {
            $con .= " and singleno like '%{$_GPC['singleno']}%'";
        }
//var_dump($con);
        $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE 1 = 1 {$con} order by id desc LIMIT " . ($page - 1) * $size . " , " . $size);
        foreach ($list as &$item) {
            $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$item['orderid']}'");
//            $item['orderno'] = $o['orderno'];
            $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $item['uniacid']);

            $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $item['supply_goodsid']));
            $item['taxrate'] = floatval($goods['taxrate']) * 0.01 * floatval($item['oprice']) * floatval($item['num']);

            $item['orderno'] = $ac['name'] . "<br>" . $o['orderno'];
            unset($o);
            unset($ac);
            unset($item);
        }
        $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} ");
        $pager = pagination($total, $page, $size);
    }

}
//平台向商家打退款单
if ($op == 'shop_refund_list') {
    $page = max(1, intval($_GPC['page']));
    $size = 50;
    $status = intval($_GPC['status']);
    $mobile = $_GPC['mobile'];
    $realname = $_GPC['realname'];
    $con = '';
    $con .= " and shop_refund_orderid<=0 ";
    $con .= " and refund=2 ";
    if (intval($_GPC['id']) > 0) {
        $con .= ' and uniacid = ' . intval($_GPC['id']);
    }
    if (!empty($mobile)) {
        $con .= " and mobile like '%{$mobile}%' ";
    }
    if (!empty($realname)) {
        $con .= " and realname like '%{$realname}%' ";
    }
    if (!empty($_GPC['singleno'])) {
        $con .= " and singleno like '%{$_GPC['singleno']}%'";
    }

    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} LIMIT 50");
    foreach ($list as &$item) {
        $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$item['orderid']}'");
//            $item['orderno'] = $o['orderno'];
        $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $item['uniacid']);
        $item['orderno'] = $ac['name'] . "<br>" . $o['orderno'];
        unset($o);
        unset($ac);
        unset($item);
    }
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} ");
    $pager = pagination($total, $page, $size);
}
if ($op == 'shop_refund_submit') {
    $con = '';
    $con .= " and shop_refund_orderid<=0 ";
    $con .= " and refund=2 ";
    if (intval($_GPC['id']) > 0) {
        $con .= ' and uniacid = ' . intval($_GPC['id']);
    }
    if (!empty($mobile)) {
        $con .= " and mobile like '%{$mobile}%' ";
    }
    if (!empty($realname)) {
        $con .= " and realname like '%{$realname}%' ";
    }
    if (!empty($_GPC['singleno'])) {
        $con .= " and singleno like '%{$_GPC['singleno']}%'";
    }

    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} LIMIT 50");

    $price = 0;
    foreach ($list as &$item) {
        $price += $item['price'];
        pdo_update('tg_supply_collect', array('shop_refund_orderid' => -1), array('id' => $item['id']));
    }
    if (count($list) > 0) {
        die(json_encode(array('status' => 1, 'price' => $price)));
    } else {
        die(json_encode(array('status' => 0, 'price' => 0)));
    }
}
//平台向商家打退款
if ($op == 'shop_refund_check_submit') {
    $supplier = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:id', array(':id' => $_GPC['id']));
    $data['singleno'] = $singleno = 'SR' . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $data['uni_payno'] = $_GPC['uni_payno'];
    $data['uni_payimg'] = $_GPC['uni_payimg'];
    $data['paytime'] = strtotime($_GPC['paytime']);
    $data['supply_id'] = $_GPC['id'];
    $data['price'] = $_GPC['price'];
    $data['account_name'] = $supplier['name'];
    $data['createtime'] = TIMESTAMP;
    pdo_insert('tg_platform_pay_shop_refund', $data);
    $id = pdo_insertid();
    pdo_update('tg_supply_collect', array('shop_refund_orderid' => $id), array('shop_refund_orderid' => -1, 'uniacid' => $_GPC['id']));
    //短信通知
    //  $content=$supplier['bank_name'].'账户号:'.substr($supplier['bank_account'],-6).'打了一笔款，请将款数填入乐享拼购系统';

    // 您好，我司已将退款单号：#tkno#款项汇入账户名:#jxname#账户尾号:#jxno#，请查收。

    $mobile = $supplier['contact_phone'];
    $tkno = $data['singleno'];
    $jxname = $supplier['bank_name'];
    $jxno = substr($supplier['bank_account'], -6);
    send_platform_refund_sms($mobile, $tkno, $name, $jxno);
    $message = "您好，我司已将退款单号：" . $tkno . "款项汇入账户名:【" . $jxname . "】账户尾号:" . $jxno . "，请查收";
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '平台打款';
        $dat['keyword1'] = $tkno;
        $dat['keyword2'] = '平台打款';
        $dat['keyword3'] = '火蝶云极限单品平台向商家退款';
        $dat['keyword4'] = $message;
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }
//    send_platform_sms(substr($supplier['bank_account'], -6), $supplier['contact_phone'], $supplier['bank_name']);
    die(json_encode(array('status' => 1)));
}
//平台向供应商申请退款
if ($op == 'supply_refund_list') {
    $page = max(1, intval($_GPC['page']));
    $size = 50;
    $status = intval($_GPC['status']);
    $mobile = $_GPC['mobile'];
    $realname = $_GPC['realname'];
    $con = '';
    $con .= " and supply_refund_orderid <= 0 ";
    $con .= " and refund = 2 ";
    $con .= " and shop_refund_orderid > 0 and platform_supplier_orderid > 0 ";
    if (intval($_GPC['id']) > 0) {
        $con .= ' and supply_id = ' . intval($_GPC['id']);
    }
    if (!empty($mobile)) {
        $con .= " and mobile like '%{$mobile}%' ";
    }
    if (!empty($realname)) {
        $con .= " and realname like '%{$realname}%' ";
    }
    if (!empty($_GPC['singleno'])) {
        $con .= " and singleno like '%{$_GPC['singleno']}%'";
    }
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} LIMIT 50");
    foreach ($list as &$item) {
        $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$item['orderid']}'");
//            $item['orderno'] = $o['orderno'];
        $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $item['uniacid']);
        $item['orderno'] = $ac['name'] . "<br>" . $o['orderno'];
        unset($o);
        unset($ac);
        unset($item);
    }
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} ");
    $pager = pagination($total, $page, $size);
}
//平台向供应商申请退款
if ($op == 'supply_refund_submit') {
    $con = '';
    $con .= " and supply_refund_orderid <= 0 ";
    $con .= " and shop_refund_orderid > 0 and refund = 2 and platform_supplier_orderid > 0";
    if (intval($_GPC['id']) > 0) {
        $con .= ' and supply_id = ' . intval($_GPC['id']);
    }
    if (!empty($mobile)) {
        $con .= " and mobile like '%{$mobile}%' ";
    }
    if (!empty($realname)) {
        $con .= " and realname like '%{$realname}%' ";
    }
    if (!empty($_GPC['singleno'])) {
        $con .= " and singleno like '%{$_GPC['singleno']}%'";
    }

    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} LIMIT 50");

    $price = 0;
    foreach ($list as &$item) {
        $price += $item['price'];
        pdo_update('tg_supply_collect', array('supply_refund_orderid' => -1), array('id' => $item['id']));
    }
    if (count($list) > 0) {
        die(json_encode(array('status' => 1, 'price' => $price)));
    } else {
        die(json_encode(array('status' => 0, 'price' => 0)));
    }
}
//向供应商申请退款
if ($op == 'supply_refund_check_submit') {
    $supplier = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:id', array(':id' => $_GPC['id']));
    $data['singleno'] = $singleno = 'SuplyR' . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
//    $data['uni_payno'] = $_GPC['uni_payno'];
//    $data['uni_payimg'] = $_GPC['uni_payimg'];
//    $data['paytime'] = strtotime($_GPC['paytime']);
    $data['supply_id'] = intval($_GPC['id']) ? intval($_GPC['id']) : intval($_W['uniacid']);
    $data['price'] = $_GPC['price'];
    $data['account_name'] = $supplier['name'];
    $data['createtime'] = TIMESTAMP;
    pdo_insert('tg_platform_pay_supply_refund', $data);
    $id = pdo_insertid();
    pdo_update('tg_supply_collect', array('supply_refund_orderid' => $id), array('supply_refund_orderid' => -1, 'supply_id' => $_GPC['id']));
    //短信通知
    //  $content=$supplier['bank_name'].'账户号:'.substr($supplier['bank_account'],-6).'打了一笔款，请将款数填入乐享拼购系统';

    // 您好，我司已将退款单号：#tkno#款项汇入账户名:#jxname#账户尾号:#jxno#，请查收。

    $mobile = $supplier['contact_phone'];
    $tkno = $data['singleno'];
    $jxname = $supplier['bank_name'];
    $jxno = substr($supplier['bank_account'], -6);
//    send_platform_refund_sms($mobile, $tkno , $name , $jxno);
    $message = "您好，由于商家售后申请退款已审核通过，火蝶云已向您申请退款，请及时审核";
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '平台申请退款';
        $dat['keyword1'] = $tkno;
        $dat['keyword2'] = '平台申请退款';
        $dat['keyword3'] = '火蝶云极限单品平台向供应商申请退款';
        $dat['keyword4'] = $message;
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }

//    send_platform_sms(substr($supplier['bank_account'], -6), $supplier['contact_phone'], $supplier['bank_name']);
    die(json_encode(array('status' => 1)));
}

if ($op == 'supply_pay_receive') {
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $keyword = $_GPC['keyword'];
    $con = '';
    if (!empty($keyword)) {
        $con .= " and gname like '%{$keyword}%' ";
    }
//    $con .= " and status=1";
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_platform_pay_supply_refund') . " WHERE 1 {$con} LIMIT " . ($page - 1) * $size . " , " . $size);
    foreach ($list as &$item) {
        $item['uni_payimg'] = tomedia($item['uni_payimg']);
        $item['uni_payimg'] = tomedia($item['uni_payimg']);
        if ($item['status'] == 0) {
            $item['statusname'] = '供货商待打款';
        } elseif ($item['status'] == 1) {
            $item['statusname'] = '供货商已打款';
        } elseif ($item['status'] == 2) {
            $item['statusname'] = '平台已确认收款';
        }
        unset($item);
    }
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_platform_pay_supply_refund') . " WHERE 1 {$con} ");
    $pager = pagination($total, $page, $size);

}
//预打款
if ($op == 'platform_submit') {
    $status = intval($_GPC['status']);
    $mobile = $_GPC['mobile'];
    $realname = $_GPC['realname'];
    $con = '';
    if ($status == 1) {
        $con .= " and refund = 1 ";
    } elseif ($status == 2) {
        $con .= " and refund = 2 ";
    } elseif ($status == 3) {
        $con .= " and supply_status = 1 ";
    } elseif ($status == 4) {
        $con .= " and supply_status = 2 ";
        $con .= " and platform_supplier_orderid <= 0 ";
        $con .= " and refund=0 ";
    }
    if (!empty($_GPC['id'])) {
        $con .= ' and supply_id=' . intval($_GPC['id']);
    }
    if (!empty($mobile)) {
        $con .= " and mobile like '%{$mobile}%' ";
    }
    if (!empty($realname)) {
        $con .= " and realname like '%{$realname}%' ";
    }
//    var_dump($_GPC['id']);
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE 1 {$con} LIMIT 50");

    $price = 0;
    $total_price = 0;
    $service_price = 0;
    foreach ($list as &$item) {
        $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $item['supply_goodsid']));
        $price += $item['price'] - $item['taxrate'];
//        $item['taxrate'] = $goods['taxrate'] / 100;
        $total_price += $item['price'];
        $service_price += $item['taxrate'];
        pdo_update('tg_supply_collect', array('platform_supplier_orderid' => -1), array('id' => $item['id']));
        unset($item);
        unset($goods);
    }
    $supplier = pdo_get('tg_platform_supplier', array('uniacid' => $_GPC['id']));
    if (count($list) > 0) {
        die(json_encode(array('status' => 1, 'price' => number_format($price, 2), 'total_price' => number_format($total_price, 2), 'service_price' => number_format($service_price, 2), 'bank_name' => $supplier['bank_name'], 'bank' => $supplier['bank_type'], 'bank_num' => $supplier['bank_account'])));
    } else {
        die(json_encode(array('status' => 0, 'price' => 0)));
    }

}
//平台打款
if ($op == 'platform_check_submit') {
    $supplier = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:id', array(':id' => $_GPC['id']));
    $data['singleno'] = $singleno = 'P' . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $data['uni_payno'] = $_GPC['uni_payno'];
    $data['uni_payimg'] = $_GPC['uni_payimg'];
    $data['paytime'] = strtotime($_GPC['paytime']);
    $data['supply_id'] = $_GPC['id'];
    $data['price'] = $_GPC['price'];
    $data['account_name'] = $supplier['name'];
    $data['createtime'] = TIMESTAMP;
    pdo_insert('tg_platform_pay_supply', $data);
    $id = pdo_insertid();
    pdo_update('tg_supply_collect', array('platform_supplier_orderid' => $id), array('platform_supplier_orderid' => -1, 'supply_id' => $_GPC['id']));
    //短信通知
    // 您好，我司已将结算单号：#jsno#款项汇入账户名:#jxname#账户尾号:#jxno#，请查收  $content=$supplier['bank_name'].'账户号:'.substr($supplier['bank_account'],-6).'打了一笔款，请将款数填入乐享拼购系统';

    $mobile = $supplier['contact_phone'];
    $jsno = $data['singleno'];
    $jxname = $supplier['bank_name'];
    $jxno = substr($supplier['bank_account'], -6);
    send_platform_account_sms($mobile, $jsno, $name, $jxno);
    $message = "您好，我司已将结算单号：" . $jsno . "款项汇入账户名:【" . $jxname . "】账户尾号:" . $jxno . "，请查收";
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '平台打款';
        $dat['keyword1'] = $jsno;
        $dat['keyword2'] = '平台打款';
        $dat['keyword3'] = '火蝶云极限单品平台向供货商打款';
        $dat['keyword4'] = $message;
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }
    die(json_encode(array('status' => 1)));
}
//订单详情
//status=1成功 data详细数据
//0失败 data错误信息
//
if ($op == 'detail') {
    $id = intval($_GPC['id']);
    if ($id > 0) {

        $page = max(1, intval($_GPC['page']));
        $size = 10;
        $keyword = $_GPC['keyword'];
        $con = '';
        if (!empty($keyword)) {
            $con .= " and gname like '%{$keyword}%' ";
        }
        $list = pdo_fetch("select * from " . tablename('tg_supply_collect') . " where id = '{$id}' ");
        $o = pdo_fetch("select orderno,uniacid,remark,adminremark from " . tablename('tg_order') . " where id = '{$list['orderid']}'");
        $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where singleno = '{$list['singleno']}'");
        $account = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = {$order['uniacid']}");
        if (!empty($order['uni_payimg'])) {
            $order['uni_payimg'] = tomedia($order['uni_payimg']);
        }

        $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $list['supply_goodsid']));
        $list['taxrate'] = floatval($goods['taxrate']) * 0.01 * $list['oprice'] * $list['num'];

        $list['order'] = $order;
        $list['shop_accountname'] = $account['name'];
        if ($o) {
            $list['orderno'] = $o['orderno'];
            $list['remark'] = $o['remark'];
            $list['adminremark'] = $o['adminremark'];
        } else {
            $list['orderno'] = $list['singleno'];
        }
        $list['refund_imgs'] = unserialize($list['refund_imgs']);
        foreach ($list['refund_imgs'] as &$refund_img) {
            $refund_img = tomedia($refund_img);
            unset($refund_img);
        }
        die(json_encode(array('data' => $list, 'status' => 1)));
    } else {
        die(json_encode(array('data' => '传入参数错误！', 'status' => 0)));
    }

}

//商家申请退款  status>0成功 否则失败   message输出信息
if ($op == 'refund') {
    $id = $_GPC['id'];
    $refund_reason = $_GPC['refund_reason'];
    $re = pdo_update('tg_supply_collect', array('refund' => 1, 'refund_reason' => $refund_reason, 'refund_time' => TIMESTAMP), array('id' => $id));
    if ($re) {
        $message = '申请成功！请等候平台处理';
    } else {
        $message = '申请失败！请联系平台处理';
    }
    die(json_encode(array('status' => $re, 'message' => $message)));

}

if ($op == 'order_submit') {

    $order_id = $_GPC['order_id'];
    $uni_paytype = intval($_GPC['uni_paytype']);
    $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where id = '{$order_id}' ");

    $data = array();
    $data['uni_paytype'] = $uni_paytype;
    if ($uni_paytype == 0) {

    } else {
        $data['uni_payprice'] = $_GPC['uni_payprice'];
        $data['uni_payno'] = $_GPC['uni_payno'];
        $data['uni_payimg'] = $_GPC['uni_payimg'];
        $data['uni_paytime'] = TIMESTAMP;
        if ($order['price'] > $data['uni_payprice']) {
            $data['uni_pay'] = 1;
        } else {
            $data['uni_pay'] = 2;
        }
    }
    $re = pdo_update('tg_supply_order', $data, array('id' => $order_id));
    if ($re) {
        $message = '支付成功！请等候平台审核';
        if ($data['uni_pay'] == 1) {
            $message .= "【未全额支付，会导致审核失败，无法发货】";
        }
    } else {
        $message = '支付失败！请联系平台处理';
    }
    die(json_encode(array('status' => $re, 'message' => $message)));
}
if ($op == 'supply_account') {
    $keyword = $_GPC['keyword'];
    $con = '';
    if ($keyword) {
        $con .= " and cm_tg_platform_supplier.name like '%{$keyword}%' ";
    }
    $ds = pdo_fetchall('select * from ' . tablename('tg_platform_supplier') . " where status = 1 {$con} ");
    include wl_template('platform/query_supply');
    exit;
}
if ($op == 'wechats') {
    $order_id = $_GPC['order_id'];
    $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where id = '{$order_id}' ");

}

if ($op == 'ship_review') {
    $id = $_GPC['id'];
    $status = $_GPC['status'];
    $platform_reason = $_GPC['platform_reason'];
    $re = pdo_update('tg_supply_order', array('platform_status' => $status, 'platform_reason' => $platform_reason, 'platform_time' => TIMESTAMP), array('id' => $id));
    pdo_update('tg_supply_collect', array('platform_status' => $status), array('supply_orderid' => $id));
    if ($re) {
        $message = '审核成功！';

        $order = pdo_get('tg_supply_order', array('id' => $id));
        $supplier = pdo_get('tg_platform_supplier', array('uniacid' => $order['supply_id']));
        $shop = pdo_get('tg_platform_shop', array('uniacid' => $order['uniacid']));
        $dat['openid'] = $supplier['openid'];
        if ($dat['openid']) {
            $dat['first'] = '商家申请发货';
            $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $dat['keyword2'] = '火蝶云极限单品商家申请发货';
            $dat['keyword3'] = '火蝶云极限单品商家申请【' . $order['gname'] . '】发货，等候供应商发货';
            $dat['keyword4'] = '待发货';
            $dat['remark'] = '';
            pdo_insert('tg_service_process', $dat);
        }
        $dat['openid'] = $shop['openid'];
        if ($dat['openid']) {
            $dat['first'] = '商家申请发货进度';
            $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $dat['keyword2'] = '火蝶云极限单品商家申请发货，平台审核通过';
            $dat['keyword3'] = '火蝶云极限单品商家申请【' . $order['gname'] . '】发货，平台审核已通过，等候供应商发货';
            $dat['keyword4'] = '待发货';
            $dat['remark'] = '';
            pdo_insert('tg_service_process', $dat);
        }

    } else {
        $message = '审核失败！';
    }
    die(json_encode(array('status' => $re, 'message' => $message)));
}

if ($op == 'refund_detail') {
    $id = $_GPC['id'];
    $collect = pdo_fetch("select * from " . tablename('tg_supply_collect') . " where id = '{$id}' ");
    foreach ($collect as &$item) {
        $item['refund_imgs'] = unserialize($item['refund_imgs']);
        unset($item);
    }
}

if ($op == 'refund_review') {
    $id = intval($_GPC['id']);
    $col = pdo_get('tg_supply_collect', array('id' => $id));
    $refund_price = $col['refund_price'];
    $status = $_GPC['status'];
    $platform_reason = $_GPC['platform_reason'];
//    $refund_price = $_GPC['refund_price'];
    $re = pdo_update('tg_supply_collect', array('refund' => $status, 'refund_update_reason' => $platform_reason, 'refund_update_time' => TIMESTAMP, 'refund_price' => $refund_price), array('id' => $id));
//    pdo_update('tg_supply_collect' , array('platform_status' => $status) , array('supply_orderid' => $id));
    if ($re) {
        $message = '审核成功！';
    } else {
        $message = '审核失败！';
    }
    die(json_encode(array('status' => $re, 'message' => $message)));
}

include wl_template("platform/platform_order");
exit();