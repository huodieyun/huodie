<?php

$_W['page']['title'] = "极限单品 - 订单管理";
$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}
global $_W, $_GPC;
load()->func("tpl");


function setting_get_by_name($name='',$uniacid){
    global $_W;
    $setting = pdo_fetch("select * from".tablename('tg_setting')." where `key`  = '{$name}' and uniacid={$uniacid}");
    if($setting){
        $set = unserialize($setting['value']);
        return $set;
    }else{
        return FALSE;
    }
}
$setting = setting_get_by_name('subsidy',33);

if(empty($setting)){
    $percent = 0;
}else{
    $percent = $setting['percent']/100;
}

$uniacid = $_W['uniacid'];
$province = pdo_getall('erp_area', array('level' => 1));

if ($op == 'address_edit') {
    $id = intval($_GPC['id']);
    $data['province'] = trim($_GPC['province']);
    $data['city'] = trim($_GPC['city']);
    $data['county'] = trim($_GPC['county']);
    $data['address'] = trim($_GPC['address']);
    $data['realname'] = trim($_GPC['realname']);
    $data['mobile'] = trim($_GPC['mobile']);

    $res = pdo_update('tg_supply_collect', $data, array('id' => $id));
    if ($res) {
        $message = '修改成功！';
    } else {
        $message = '修改失败，请重试！';
    }
    die(json_encode(array('status' => $res, 'message' => $message)));

}

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
            $sid = intval(str_replace('JXDP', '', $keyword));
            $con .= " and supply_id = {$sid} ";
        }
        if ($_GPC['con_type'] == 3) {
            $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
        }
    }
    $goodses = pdo_fetchall("select * from " . tablename('tg_goods') . " where uniacid = '{$uniacid}' and supply_goodsid > 0 " . $con  . " ORDER BY supply_goodsid DESC limit " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_goods') . " where uniacid = '{$uniacid}' and supply_goodsid > 0 " . $con);
    $pager = pagination($total, $page, $size);
    foreach ($goodses as &$item) {
        $item['pici'] = "JXDP" . sprintf("%04d", $item['supply_goodsid']);
        $count = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=8 and g_id={$item['id']} and mobile<>'虚拟' {$cons} ");//待付款
        $count1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_supply_collect') . " WHERE  goodsid={$item['id']} ");//已申请发货

        $item['send_count'] = $count;
        $item['check_send_count'] = $count1;
        $item['need_send_count'] = $count - $count1;
        unset($count);
        unset($count1);
        unset($item);
    }
}

if ($op == 'shop') {
    $id = $_GPC['id'];

    $page = max(1, intval($_GPC['page']));
    $size = 10;

    $order = pdo_fetchall("select * from " . tablename('tg_order') . " where uniacid = {$uniacid} and g_id = {$id} and status = 8 and singleno = '' order by id asc limit " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_order') . " where uniacid = {$uniacid} and g_id = {$id} and status = 8 and singleno = '' order by id asc ");
    $pager = pagination($total, $page, $size);

}

if ($op == 'shop_submit') {

    $id = $_GPC['id'];

    $order = pdo_fetchall("select * from " . tablename('tg_order') . " where uniacid = {$uniacid} and g_id = {$id} and status = 8 and singleno = '' order by id asc ");
    if (!empty($order)) {
        $data = array();
        $data['singleno'] = $singleno = 'L' . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $data['uniacid'] = $uniacid;
        $data['supply_goodsid'] = $order[0]['supply_goodsid'];
        $goods = pdo_fetch("select * from " . tablename('tg_supply_goods') . " where id = {$order[0]['supply_goodsid']} ");
        $data['supply_id'] = $goods['supply_id'];
        $price = 0.0;
        $sprice = 0.0;
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
            $collect['freight'] = floatval($value['freight']);
            if (!empty($value['optionname'])) {
                $option = pdo_fetch("select * from " . tablename('tg_goods_supply_option') . " where goodsid = '{$value['supply_goodsid']}' and title = '{$value['optionname']}' ");
                $collect['oprice'] = $option['costprice'];
                $collect['price'] = $option['costprice'] * $value['gnum'] + floatval($value['freight']);
                $collect['pay_price'] = $option['costprice'] * $value['gnum'] + floatval($value['freight']);
                $price += $option['costprice'] * $value['gnum'] + floatval($value['freight']);
                $sprice += $option['costprice'] * $value['gnum'];
            } else {
                $goods = pdo_fetch("select * from " . tablename('tg_supply_goods') . " where id = '{$value['supply_goodsid']}' ");
                $collect['oprice'] = $goods['mprice'];
                $collect['price'] = $goods['mprice'] * $value['gnum'] + floatval($value['freight']);
                $collect['pay_price'] = $goods['mprice'] * $value['gnum'] + floatval($value['freight']);
                $price += $goods['mprice'] * $value['gnum'] + floatval($value['freight']);
                $sprice += $goods['mprice'] * $value['gnum'];

            }
            $collect['taxrate'] = floatval($collect['oprice']) * floatval($goods['taxrate']) * floatval($collect['num']) * 0.01;

            $member = pdo_fetch("select nickname from " . tablename('tg_member') . " where uniacid = {$_W['uniacid']} and from_user = '{$value['openid']}' ");
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
        $data['subsidy'] = $percent*$sprice;
        pdo_insert('tg_supply_order', $data);
        $order_id = pdo_insertid();
        if($goods['has_subsidy'] != 1){
            $percent = 0;
        }
        pdo_update('tg_supply_collect', array('supply_orderid' => $order_id), array('singleno' => $singleno));
        die(json_encode(array('order_id' => $order_id, 'money' => $price,'percent'=>$percent*$sprice, 'message' => '订单生成成功，请支付！')));
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
        $list = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where id = {$order_id} order by id desc ");
        foreach ($list as &$item) {
            $item['uni_payimg'] = tomedia($item['uni_payimg']);
            $goods = pdo_fetch("select * from " . tablename('tg_supply_goods') . " where id = {$item['supply_goodsid']} ");
            if($goods['has_subsidy'] !=1){
                $item['percent'] = 0;
            }else{
                $item['percent'] = $percent;
            }

            unset($item);
        }
//        die(json_encode(array('list' => $list)));
    } else {
        $page = max(1, intval($_GPC['page']));
        $size = 10;
        $keyword = $_GPC['keyword'];
        $con = '';
        $status = intval($_GPC['status']);
        $platform_status = intval($_GPC['platform_status']);
        $supply_status = intval($_GPC['supply_status']);
        if ($status == 1) {
            $status -= 1;
            $con .= " and supply_status = {$status}";
            if ($status == 0) {
                $con .= " and platform_status = 0 ";
            }
        }

        if ($platform_status > 0) {
            $con .= " and platform_status = {$platform_status}";
        }

        if ($supply_status > 0) {
            $supply_status -= 1;
            $con .= " and supply_status = {$supply_status}";
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
        if (intval($_GPC['supply_status']) >= 1) {
            $supply_status = intval($_GPC['supply_status']) - 1;
            $con .= " and platform_status=1 and supply_status=" . $supply_status;
        }

        //    var_dump($con);
        $list = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where uniacid = '{$uniacid}' " . $con . " order by id desc limit " . ($page - 1) * $size . " , " . $size);
        $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_order') . " where uniacid = '{$uniacid}' " . $con);
        foreach ($list as &$item) {
            $item['uni_payimg'] = tomedia($item['uni_payimg']);
            $goods = pdo_fetch("select * from " . tablename('tg_supply_goods') . " where id = {$item['supply_goodsid']} ");
            if($goods['has_subsidy'] !=1){
                $item['percent'] = 0;
            }else{
                $item['percent'] = $percent;
            }
            unset($item);
        }
        $pager = pagination($total, $page, $size);
//        die(json_encode(array('list' => $list)));
    }


}
if ($op == 'refund_order') {
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $keyword = $_GPC['keyword'];
    $con = '';
    $status = intval($_GPC['status']);
    if (!empty($keyword)) {
        if ($_GPC['con_type'] == 1) {
            $con .= " and gname like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 2) {
            $con .= " and singleno like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 3) {
            $con .= " and orderno like '%{$keyword}%'";
        }
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']);
        $con .= " AND  refund_time >= {$starttime} AND  refund_time <= {$endtime} ";
    }
    if (intval($_GPC['supply_status']) >= 1) {
        $supply_status = intval($_GPC['supply_status']) - 1;
        $con .= "  and supply_status=" . $supply_status;
    }
    $con .= "  and order_status=7 and platform_status=1";
//       var_dump($con);
    $list = pdo_fetchall("select * from v_supply_collect where uniacid = '{$uniacid}' " . $con . " limit " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("select count(id) from v_supply_collect where uniacid = '{$uniacid}' " . $con);
    foreach ($list as &$item) {
        $item['uni_payimg'] = tomedia($item['uni_payimg']);
        unset($item);
    }
    $pager = pagination($total, $page, $size);

}
//子单列表
if ($op == 'collect') {
    $order_id = intval($_GPC['collect_id']);
    if ($order_id > 0) {

        //用于前台显示总单信息
        $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where id = '{$order_id}'");
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
        $list = pdo_fetchall("select * from " . tablename('tg_supply_collect') . " where supply_orderid = '{$order_id}' " . $con . " limit " . ($page - 1) * $size . " , " . $size);
        foreach ($list as &$item) {

            $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $item['supply_goodsid']));
            $item['taxrate'] = floatval($goods['taxrate']) * 0.01 * floatval($item['oprice']) * floatval($item['num']);

            $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$item['orderid']}'");
            $item['orderno'] = $o['orderno'];
            unset($item);
        }
        $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_collect') . " where supply_orderid = '{$order_id}' " . $con);
        $pager = pagination($total, $page, $size);
//        die(json_encode($list));
    } else {

    }

}

//订单详情
//status=1成功 data详细数据
//0失败 data错误信息
//
if ($op == 'detail') {
//    $id = intval($_GPC['id']);
//    if ($id > 0) {
//
//        $page = max(1, intval($_GPC['page']));
//        $size = 10;
//        $keyword = $_GPC['keyword'];
//        $con = '';
//        if (!empty($keyword)) {
//            $con .= " and gname like '%{$keyword}%' ";
//        }
//        $list = pdo_fetch("select * from " . tablename('tg_supply_collect') . " where id = '{$id}' ");
//        $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$list['orderid']}'");
//        $list['orderno'] = $o['orderno'];
//        $list['refund_imgs'] = unserialize($list['refund_imgs']);
//        die(json_encode(array('data' => $list, 'status' => 1)));
//    } else {
//        die(json_encode(array('data' => '传入参数错误！', 'status' => 0)));
//    }
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
        $o = pdo_fetch("select orderno,uniacid from " . tablename('tg_order') . " where id = '{$list['orderid']}'");
        $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where singleno = '{$list['singleno']}'");
        $account = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = {$order['uniacid']}");
        if (!empty($order['uni_payimg'])) {
            $order['uni_payimg'] = tomedia($order['uni_payimg']);
        }

        $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $list['supply_goodsid']));
        $list['taxrate'] = floatval($goods['taxrate']) * 0.01 * $list['oprice'] * $list['num'];
        $list['unit'] = $goods['unit'];

        $list['order'] = $order;
        $list['shop_accountname'] = $account['name'];
        $list['orderno'] = $o['orderno'];
        $list['refund_imgs'] = unserialize($list['refund_imgs']);
        die(json_encode(array('data' => $list, 'status' => 1)));
    } else {
        die(json_encode(array('data' => '传入参数错误！', 'status' => 0)));
    }
}

//商家申请退款  status>0成功 否则失败   message输出信息
if ($op == 'refund') {
    $id = intval($_GPC['id']);
    $col = pdo_get('tg_supply_collect' , array('id'=>$id));
    $refund_price = $col['price'];
    $refund_reason = $_GPC['refund_reason'];
    $refund_imgs = $_GPC['img'];
    $refund_imgs = serialize($refund_imgs);
    $re = pdo_update('tg_supply_collect', array('refund' => 1, 'refund_reason' => $refund_reason, 'refund_price' => $refund_price, 'refund_imgs' => $refund_imgs, 'refund_time' => TIMESTAMP), array('id' => $id));
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
        $data['subsidy'] = $_GPC['subsidy'];
        if ($order['price'] > ($data['uni_payprice'] + $_GPC['subsidy'])) {
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
        } else if ($data['uni_pay'] == 2) {

            $acceptor = pdo_getall('tg_platform_acceptor', array('status' => 1));
            if ($acceptor) {
                $accept = array();

                $shop = pdo_get('tg_platform_shop', array('uniacid' => $order['uniacid']));
                $supplier = pdo_get('tg_platform_supplier', array('uniacid' => $order['supply_id']));
                $accept['first'] = '商家申请发货进度';
                $accept['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
                $accept['keyword2'] = '商家申请发货';
                $accept['keyword3'] = "火蝶云极限单品商家【" . $shop['name'] . "】购买供货商【".$supplier['name']."】的商品【" . $order['gname'] . '】申请发货提交成功，等候平台审核';
                $accept['keyword4'] = '待审核';
                $accept['remark'] = '';
                foreach ($acceptor as $item) {
                    $accept['openid'] = $item['openid'];
                    pdo_insert('tg_service_process', $accept);
                }
            }
        }

    } else {
        $message = '支付失败！请联系平台处理';
    }
    die(json_encode(array('status' => $re, 'message' => $message)));
}

if ($op == 'wechats') {
    $order_id = $_GPC['order_id'];
    $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where id = '{$order_id}' ");

}

include wl_template("platform/shop_order");
exit();