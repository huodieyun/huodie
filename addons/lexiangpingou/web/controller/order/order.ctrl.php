<?php
defined('IN_IA') or exit('Access Denied');
wl_load()->model('merchant');
wl_load()->func('print');
wl_load()->classs('qrcode');

$ops = array('changestatus','hexiao_code', 'master_refund', 'import', 'modifyPrice', 'refunds', 'dayin', 'update', 'summary', 'received', 'wholesale', 'detail', 'once_card_detail', 'output', 'remark', 'address', 'store', 'confrimpay', 'confirmsend', 'cancelsend', 'refund', 'updaterefund', 'remind');
$op_names = array('退款', '打印', '修改价格', '订单概况', '订单列表', '订单详情', '导出', '卖家备注', '修改收货地址', '确认付款', '发货', '取消发货', '退款');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'order', 'order', $ops[$key], '订单', '订单管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'summary';

$gettime = $this->module['config']['gettime'];//自动签收时间
$uniacid = $_W['uniacid'];
//$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
session_start();
$dispatchtype = $_GPC['dispatchtype'];
if (!empty($_GPC['dispatchtype'])) {
    $_SESSION['dispatchtype'] = $_GPC['dispatchtype'];
    $dispatchtype = $_SESSION['dispatchtype'];
}
if ($op == 'dayin') {
    $id = intval($_GPC['id']);
    tuan_print($id,true);

    message('打印小票群发发送中');

}

//自提提醒
if ($op == 'remind') {
    $id = $_GPC['id'];
    $order = pdo_fetch("SELECT id,uniacid,openid,orderno,comadd FROM " . tablename('tg_order') . " WHERE id = " . $id);
    $col = pdo_fetch("select * from " . tablename('tg_order_collect') . " where orderno = '{$order['orderno']}' and uniacid = '{$order['uniacid']}' and openid = '{$order['openid']}' ");
    if ($col) {
        $dat['remind_times'] = $col['remind_times'] + 1;
        $dat['remind_time'] = TIMESTAMP;
        $res = pdo_update('tg_order_collect', $dat, array('id' => $col['id']));
    } else {
        $dat['uniacid'] = $order['uniacid'];
        $dat['openid'] = $order['openid'];
        $dat['orderno'] = $order['orderno'];
        $dat['remind_times'] = 1;
        $dat['remind_time'] = TIMESTAMP;
        $res = pdo_insert('tg_order_collect', $dat);
    }

    $store = pdo_fetch("SELECT storename FROM " . tablename('tg_store') . " WHERE id = " . $order['comadd']);
    $postData['openid'] = $order['openid'];
    $postData['orderid'] = $order['id'];
    $postData['title'] = "您有一个自提订单未取货";
    $postData['message'] = "自提地址：【" . $store['storename'] . "】";
    $postData['remark'] = '自提提醒';
    $postData_res = serialize($postData);
    $xc["content"] = $postData_res;
    $xc["uniacid"] = $order['uniacid'];
    $xc["openid"] = $order['openid'];
    $xc["mess_tpl"] = 'result_remind';
    pdo_insert("tg_message_log", $xc);
    die(json_encode(array('status' => $dat['remind_times'], 'message' => "已提醒" . $dat['remind_times'] . "次")));

}

if ($op == 'hexiao_code') {
    $createqrcode = new creat_qrcode();
    $im = $createqrcode->createhexiaoQrcode($_GPC['orderno']);
    ////w9.huodiesoft.com/addons/lexiangpingou/data/qrcode/53/20171023575105293967.png
    die(json_encode(array('status' => 1)));
}
if ($op == 'update') {
    $id = intval($_GPC['id']);
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (!empty($_GPC['price'])) {

        pdo_update('tg_order', array('price' => $_GPC['price']), array('id' => $id));

    }
}
if ($op == 'updaterefund') {
    $goodsid = $_GPC['goodsid2'];

    if (empty($goodsid)) {
        message('请先搜索后,才可变更订单状态为待退款');
    }

    $condition = "  uniacid = '{$_W['uniacid']}'";
    $condition2 = "  uniacid = '{$_W['uniacid']}'";
    $paras = array(':uniacid' => $_W['uniacid']);
    $salers = $_GPC['salers'];
    $status = $_GPC['status'];
    $transid = $_GPC['transid'];
    $pay_type = $_GPC['pay_type'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];
    $merchantid = $_GPC['merchantid'];
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-7 day');
        $endtime = time();
    }

    $type = intval($_GPC['type']);
    if ($type == 0) {
        if (!empty($_GPC['time'])) {
            //message($_GPC['time']['start']);
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
            $condition2 .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
    } else {
        if (!empty($_GPC['time'])) {
            //message($_GPC['time']['start']);
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  hexiaotime >= {$starttime} AND  hexiaotime <= {$endtime} ";
            $condition2 .= " AND  hexiaotime >= {$starttime} AND  hexiaotime <= {$endtime} ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
    }

    if (trim($_GPC['goodsid']) != '') {
        $condition .= " and (g_id = '{$_GPC['goodsid']}' or orderno in (select orderno from cm_tg_collect where sid ='{$_GPC['goodsid']}')) ";
        $condition2 .= " and g_id like '%{$_GPC['goodsid']}%' ";;
    }
    if (trim($_GPC['goodsid2']) != '') {
        $goodsid2 = trim($_GPC['goodsid2']);
        $condition .= " and (goodsname like '%{$goodsid2}%' or orderno in (select orderno from cm_tg_collect where goodsname like '%{$goodsid2}%')) ";
        $condition2 .= " and (goodsname like '%{$goodsid2}%' or orderno in (select orderno from cm_tg_collect where goodsname like '%{$goodsid2}%')) ";

        //$condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
        //$condition2 .= " and g_id like '%{$_GPC['goodsid2']}%' ";
    }

    if (!empty($_GPC['keyword'])) {
        $condition .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
        $condition2 .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND addname = '{$_GPC['member']}' ";
        $condition2 .= " AND addname = '{$_GPC['member']}'";
    }
    if (!empty($_GPC['mobile'])) {
        $condition .= " AND  mobile = '{$_GPC['mobile']}' ";
        $condition2 .= " AND  mobile = '{$_GPC['mobile']}'";
    }
    if (!empty($_GPC['addresstype'])) {
        $condition .= " AND  addresstype='{$_GPC['addresstype']}' ";
        $condition2 .= " AND  addresstype='{$_GPC['addresstype']}' ";
    }
    if (!empty($_GPC['salers'])) {
        $condition .= " AND  veropenid='{$_GPC['salers']}' ";
        $condition2 .= " AND  veropenid='{$_GPC['salers']}' ";
    }
    $condition .= " and status in (1,2,8) and dispatchtype=3 and supply_goodsid=0";
    pdo_run('update ' . tablename('tg_order') . ' set status=10 where  ' . $condition);

//    pdo_update('tg_order', array('status' => 10), array('g_id' => $goodsid, 'status' => 1));
//    pdo_update('tg_order', array('status' => 10), array('g_id' => $goodsid, 'status' => 2));
//    pdo_update('tg_order', array('status' => 10), array('g_id' => $goodsid, 'status' => 8));
    $op = 'received';
}
if ($op == 'modifyPrice') {
    $id = intval($_GPC['id']);
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (!empty($_GPC['price'])) {
        pdo_update('tg_order', array('pay_price' => $_GPC['price']), array('id' => $id));
        pdo_update('core_paylog', array('fee' => $_GPC['price']), array('tid' => $item['orderno']));
    }
    echo 1;
    exit;
}
if ($op == 'summary') {
    $seven_orders = 0;
    $seven_nocash_orders = 0;
    $obligations = 0;
    $undelivereds = 0;
    $incomes = 0;
    $yesterday_orders = 0;
    $yesterday_payorder = 0;
    $yesterday_obligation = 0;
    $stime = strtotime(date('Y-m-d')) - 6 * 86400;

    $etime = strtotime(date('Y-m-d')) + 86400;
    $yetime = strtotime(date('Y-m-d'));
    $ytime = strtotime(date('Y-m-d')) - 86400;
//    if ($_W['user']['merchant_id'] > 0) {
    $condition .= " and  merchantid = {$_W['user']['merchant_id']} ";
//    }
    $seven_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟'   and createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $stime, ':endtime' => $etime));
    $seven_nocash_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status=0   and createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $stime, ':endtime' => $etime));
    $obligations = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status in (1,2,3,4,5,6,7,8)   and createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $stime, ':endtime' => $etime));
    $undelivereds = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status in (4,6,7)   and createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $stime, ':endtime' => $etime));

    $seven = pdo_fetchcolumn("select Sum(pay_price) as totalprice  from" . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' and status in(1,2,3,4,5,6,7,8,10)  AND createtime >= :createtime AND createtime <= :endtime {$condition}  ORDER BY createtime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    /*foreach($seven as$key=>$value){
        $incomes += $value['pay_price'];
    }*/
    $incomes = $seven;
    $yesterday_orders_list = pdo_fetchall("SELECT id,status FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟'   AND createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $ytime, ':endtime' => $yetime));
    $yesterday_payorder = 0;
    $yesterday_obligation = 0;
    foreach ($yesterday_orders_list as $item) {
        if ($item['status'] == 1 || $item['status'] == 2 || $item['status'] == 3 || $item['status'] == 4 || $item['status'] == 6 || $item['status'] == 7) {
            $yesterday_payorder += 1;
        }
        if ($item['status'] == 3) {
            $yesterday_obligation += 1;
        }
    }
    $yesterday_orders = count($yesterday_orders_list);
    /*
    $yesterday_orders=pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟'   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $ytime, ':endtime' => $yetime));
    $yesterday_payorder = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' and status in(1,2,3,4,6,7)   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $ytime, ':endtime' => $yetime));
    $yesterday_obligation = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' and status = 3   AND sendtime >= :createtime AND sendtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $ytime, ':endtime' => $yetime));
*/
    $con = "uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' ";
    $starttime = empty($_GPC['time']['start']) ? $stime : strtotime($_GPC['time']['start']);
    $endtime = empty($_GPC['time']['end']) ? $etime : strtotime($_GPC['time']['end']) + 86400;
    $s = $starttime;
    $e = $endtime;
    $list = array();
    $j = 0;

    while ($e >= $s) {
        $listone = pdo_fetchcolumn("SELECT COUNT(id)  FROM " . tablename('tg_order') . " WHERE $con   AND createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $e - 86400, ':endtime' => $e));
        $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $con and status  in (1,2,3,4,5,6,7,8,10)   AND createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $e - 86400, ':endtime' => $e));
        $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $con and status=3   AND createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $e - 86400, ':endtime' => $e));
        $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $con and status in (4,5,6,7,10)   AND createtime >= :createtime AND createtime <= :endtime {$condition} ", array(':createtime' => $e - 86400, ':endtime' => $e));

        $list[$j]['gnum'] = $listone;
        $list[$j]['status4'] = $status4;
        $list[$j]['status1'] = $status1;
        $list[$j]['status2'] = $status2;
        $list[$j]['createtime'] = $e - 86400;
        $j++;
        $e = $e - 86400;
    }

    $day = $hit = $status4 = $status1 = $status2 = array();
    if (!empty($list)) {
        foreach ($list as $row) {
            $day[] = date('m-d', $row['createtime']);
            $hit[] = intval($row['gnum']);
            $status4[] = intval($row['status4']);
            $status1[] = intval($row['status1']);
            $status2[] = intval($row['status2']);
        }
    }

    for ($i = 0; $i = count($hit) < 2; $i++) {
        $day[] = date('m-d', $endtime);
        $hit[] = $day[$i] == date('m-d', $endtime) ? $hit[0] : '0';
    }
    include wl_template('order/summary');
    exit;
}

if ($op == 'received') {
    if (!empty($_GPC['merchantid'])) {
        if (intval($_GPC['merchantid']) == -1) {
            $cons .= " and merchantid > 0 ";
        } else {
            $cons .= " and merchantid = {$_W['user']['merchant_id']} ";
        }
    } else {
        $cons .= " and merchantid = {$_W['user']['merchant_id']} ";
    }
    $cons .= ' and supply_goodsid=0';
    $allgoods = pdo_fetchall("select gname,id from" . tablename('tg_goods') . "where uniacid=:uniacid {$cons}", array(':uniacid' => $_W['uniacid']));

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;
    $condition = "  uniacid = '{$_W['uniacid']}'";
    $condition2 = "  uniacid = '{$_W['uniacid']}'";
    $paras = array(':uniacid' => $_W['uniacid']);
    $godluck = $_GPC['godluck'];
    $issued = $_GPC['issued'];
    $salers = $_GPC['salers'];
    $status = $_GPC['status'];
    $transid = $_GPC['transid'];
    $pay_type = $_GPC['pay_type'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];
    $merchantid = $_GPC['merchantid'];
    $is_wholesale = $_GPC['is_wholesale'];
    $is_once_card = $_GPC['is_once_card'];
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-7 day');
        $endtime = time();
    }

    $type = intval($_GPC['type']);
    if ($type == 0) {
        if (!empty($_GPC['time'])) {
            //message($_GPC['time']['start']);
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
            $condition2 .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
    } else {
        if (!empty($_GPC['time'])) {
            //message($_GPC['time']['start']);
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  hexiaotime >= {$starttime} AND  hexiaotime <= {$endtime} ";
            $condition2 .= " AND  hexiaotime >= {$starttime} AND  hexiaotime <= {$endtime} ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
    }

    if (trim($_GPC['goodsid']) != '') {
        $condition .= " and (g_id = '{$_GPC['goodsid']}' or orderno in (select orderno from cm_tg_collect where sid ='{$_GPC['goodsid']}')) ";
        $condition2 .= " and g_id like '%{$_GPC['goodsid']}%' ";;
    }
    if (trim($_GPC['goodsid2']) != '') {
        $goodsid2 = trim($_GPC['goodsid2']);
        $condition .= " and (goodsname like '%{$goodsid2}%' or orderno in (select orderno from cm_tg_collect where goodsname like '%{$goodsid2}%')) ";
        $condition2 .= " and (goodsname like '%{$goodsid2}%' or orderno in (select orderno from cm_tg_collect where goodsname like '%{$goodsid2}%')) ";

        //$condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
        //$condition2 .= " and g_id like '%{$_GPC['goodsid2']}%' ";
    }
    if (trim($_GPC['address']) != '') {

        $condition .= " and address like '%{$_GPC['address']}%' ";
        $condition2 .= " and address like '%{$_GPC['address']}%' ";
    }

    if (!empty($_GPC['transid'])) {
        $condition .= " AND  transid  like '%{$_GPC['transid']}%' ";
        $condition2 .= " AND  transid  like '%{$_GPC['transid']}%' ";
    }
    if (!empty($merchantid)) {
        if (intval($merchantid) == -1) {
            $condition .= " AND  merchantid > 0 ";
            $condition2 .= " AND  merchantid > 0 ";
        } else {
            $condition .= " AND  merchantid = '{$merchantid}' ";
            $condition2 .= " AND  merchantid = '{$merchantid}' ";
        }
    } else {
        $condition .= " AND  merchantid = '{$_W['user']['merchant_id']}' ";
        $condition2 .= " AND  merchantid = '{$_W['user']['merchant_id']}' ";
    }


    if (!empty($_GPC['pay_type'])) {
        $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
        $condition2 .= " AND  pay_type = '{$_GPC['pay_type']}'";
    } elseif ($_GPC['pay_type'] === '0') {
        $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
        $condition2 .= " AND  pay_type = '{$_GPC['pay_type']}'";
    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
        $condition2 .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND addname = '{$_GPC['member']}' ";
        $condition2 .= " AND addname = '{$_GPC['member']}'";
    }
    if (!empty($_GPC['mobile'])) {
        $condition .= " AND  mobile = '{$_GPC['mobile']}' ";
        $condition2 .= " AND  mobile = '{$_GPC['mobile']}'";
    }

    $comadd = intval($_GPC['comadd']);
    $checkstore = intval($_GPC['checkstore']);
    $deliveryid = intval($_GPC['deliveryid']);
    if ($comadd) {
        $condition .= " and comadd = {$comadd} ";
        $condition2 .= " and comadd = {$comadd} ";
    }
    if ($checkstore) {
        $condition .= " and checkstore = {$checkstore} and dispatchtype = 3 ";
        $condition2 .= " and checkstore = {$checkstore} and dispatchtype = 3 ";
    }
    if ($deliveryid) {
        $deliver = pdo_fetch("select * from " . tablename('tg_delivery_man') . " where id = {$deliveryid} ");
        $condition .= " and ( storeid = {$deliveryid} or veropenid = '{$deliver['openid']}' ) and dispatchtype = 1 ";
        $condition2 .= " and ( storeid = {$deliveryid} or veropenid = '{$deliver['openid']}' ) and dispatchtype = 1 ";
    }

    if (!$checkstore && !$deliveryid) {
        if (!empty($dispatchtype) && $dispatchtype != 0 && $dispatchtype != -1) {
            $condition .= " AND  dispatchtype = '{$_SESSION['dispatchtype']}'";
            $condition2 .= " AND  dispatchtype = '{$_SESSION['dispatchtype']}'";
        }
    }
    if ($dispatchtype == -1) {
        $condition .= " AND  selltype = '5' ";
        $condition2 .= " AND  selltype = '5'";
    }
    if ($is_wholesale == 1) {
        $condition .= " AND  is_wholesale = 1";
        $condition2 .= " AND  is_wholesale = 1";
    }
    if ($is_once_card == 1) {
        $condition .= " AND  is_once_card = 1";
        $condition2 .= " AND  is_once_card = 1";
    }
    if ($_GPC['issued'] == -1) {

        $condition .= " AND  issued='0' and status='8'";
        $condition2 .= " AND  issued='0' and status='8'";
    }
    if ($_GPC['issued'] == 2) {

        $condition .= " AND  issued='2' ";
        $condition2 .= " AND  issued='2' ";
    }
    if ($_GPC['issued'] == 1) {

        $condition .= " AND  issued='1' ";
        $condition2 .= " AND  issued='1' ";
    }
    //message($_GPC['salers']);
    if ($godluck == -1) {
        $condition .= " AND  godluck='0' ";
        $condition2 .= " AND  godluck='0'";
    }
    if ($godluck == 1) {
        $condition .= " AND  godluck='1' ";
        $condition2 .= " AND  godluck='1'";
    }
    if (!empty($_GPC['addresstype'])) {
        $condition .= " AND  addresstype='{$_GPC['addresstype']}' ";
        $condition2 .= " AND  addresstype='{$_GPC['addresstype']}' ";
    }
    if (!empty($_GPC['salers'])) {
        $condition .= " AND  veropenid='{$_GPC['salers']}' ";
        $condition2 .= " AND  veropenid='{$_GPC['salers']}' ";
    }
    if ($status != '') {
        if ($status == 1) {
            $condition .= " AND status = '" . intval($status) . "' ";

        } else if ($status == 8) {
            $condition .= " AND status='8' AND is_partsend = 1 ";

        } else if ($status == 4) {
            $condition .= " AND status in (4,7)";

        } else if ($status == 7) {
            $condition .= " AND status in (4,5,6,7)";

        } else if ($status == 17) {
            $condition .= " AND is_tuan = '0'";
            $table = 'tg_order';
        } else if ($status == 23) {
            $condition .= " AND status='8' AND is_partsend = 2 ";
        } else {
            $condition .= " AND status = '" . intval($status) . "'";

        }
    }
    $condition .= " and master_orderno is NULL";
    $condition2 .= " and master_orderno is NULL";
    $condition .= ' and supply_goodsid=0';
    $condition2 .= ' and supply_goodsid=0';
    $sql = "select  * from " . tablename('tg_order') . " where $condition   and mobile<>'虚拟' {$cons}  ORDER BY createtime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
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

    foreach ($list as $key => $value) {

        $prints = pdo_fetchall("SELECT * FROM " . tablename('tg_print') . " WHERE uniacid = '{$_W['uniacid']}' AND status = 1 and merchant_id = '{$_W['user']['merchant_id']}' ");
        if ($prints) {
            $coll = pdo_fetch("select print_times from " . tablename('tg_order_collect') . " where orderno = '{$value['orderno']}' and uniacid = '{$value['uniacid']}' and openid = '{$value['openid']}' ");
            if ($coll) {
                $list[$key]['print_times'] = intval($coll['print_times']);
            }
        }

        $options = pdo_fetch("SELECT title,productprice,marketprice FROM " . tablename("tg_goods_option") . " WHERE id=:id LIMIT 1", array(":id" => $value['optionid']));
        //$list[$key]['optionname'] = $options['title'];
        $s = $value['status'];
        $list[$key]['statuscss'] = $orderstatus[$value['status']]['css'];
        $list[$key]['status'] = $orderstatus[$value['status']]['name'];
        $list[$key]['css'] = $paytype[$value['pay_type']]['css'];
        $list[$key]['resultfee'] = 0;
        $m = $list[$key]['merchantid'];
        if ($m == 0) {
            $list[$key]['merchant_name'] = $_W['account']['name'];
        } else {
            $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
            $list[$key]['merchant_name'] = $name['name'];
        }
        if ($value['ptime'] > 0 && empty($value['transid'])) {
            checkpaytransid_orderno($value['orderno']);
        }

        if ($value['pay_type'] == 2) {
            if (empty($value['transid'])) {
                $list[$key]['paytype'] = '微信支付';
            } else {
                $list[$key]['paytype'] = '微信支付';
                $rfee = pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id AND status=1 AND refund_id<>'' GROUP BY refund_id", array(':id' => $value['transid']));
                $resultfee = 0;
                foreach ($rfee as $rf => $rforder) {
                    $resultfee += $rforder['refundfee'];
                }
                $list[$key]['resultfee'] = $resultfee;
            }
        } else {
            $list[$key]['paytype'] = $paytype[$value['pay_type']]['name'];
        }
        if ($list[$key]['senddate'] == '0000-00-00') {
            $list[$key]['senddate'] = null;
        }
        if (!(strpos($list[$key]['sendtime'], '-') !== false)) {
            $list[$key]['sendtime'] = date('Y-m-d H:i', $list[$key]['sendtime']);
        }
        $goodsss = pdo_fetch("select id,gname,gimg,merchantid,unit from" . tablename('tg_goods') . "where id = '{$value['g_id']}'");
        $list[$key]['unit'] = $goodsss['unit'];
        $list[$key]['gid'] = $goodsss['id'];
        $list[$key]['gname'] = $value['goodsname'];
        $list[$key]['gimg'] = $goodsss['gimg'];
        $list[$key]['merchant'] = pdo_fetch("select name from" . tablename('tg_merchant') . "where id = '{$value['merchantid']}' and uniacid={$_W['uniacid']}");
        if ($list[$key]['servestype'] == 1) {
            if ($list[$key]['status'] == '已退款') {
                $list[$key]['serves_status'] = '已处理';
            } else {
                $list[$key]['serves_status'] = '待处理';
            }
        }
        /*次卡*/
        $list[$key]['once_card_send'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order_once_card_record') . " WHERE orderid={$list[$key]['id']}");// 发货次数
        if ($list[$key]['is_tuan'] == 1) {
            $sg = pdo_get('tg_group', array('groupnumber' => $list[$key]['tuan_id']), array('successtime'));
            $successTime = $sg['successtime'];
        } else {
            $successTime = $list[$key]['ptime'];
        }
        $list[$key]['once_card_validity'] = date('Y-m-d', strtotime("+".$list[$key]['once_card_day']." day", $successTime));// 有效期
        // 配送时间
        $numDay = $list[$key]['once_card_ynum'] + 1;
        switch ($list[$key]['once_card_json']) {
            case 'once_card_mon':
                $list[$key]['once_card_week'] = '星期一';
                if (strtotime("+".$numDay." Monday") > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Monday", $successTime)).$str;// 下次发货时间

                break;
            case 'once_card_tues':
                $list[$key]['once_card_week'] = '星期二';
                if (strtotime("+".$numDay." Tuesday", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Tuesday", $successTime)).$str;// 下次发货时间
                break;
            case 'once_card_wed':
                $list[$key]['once_card_week'] = '星期三';
                if (strtotime("+".$numDay." Wednesday", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Wednesday", $successTime)).$str;// 下次发货时间
                break;
            case 'once_card_thur':
                $list[$key]['once_card_week'] = '星期四';
                if (strtotime("+".$numDay." Thursday", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Thursday", $successTime)).$str;// 下次发货时间
                break;
            case 'once_card_fir':
                $list[$key]['once_card_week'] = '星期五';
                if (strtotime("+".$numDay." Friday", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Friday", $successTime)).$str;// 下次发货时间
                break;
            case 'once_card_sat':
                $list[$key]['once_card_week'] = '星期六';
                if (strtotime("+".$numDay." Saturday", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Saturday", $successTime)).$str;// 下次发货时间
                break;
            case 'once_card_sun':
                $list[$key]['once_card_week'] = '星期天';
                if (strtotime("+".$numDay." Sunday", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Sunday", $successTime)).$str;// 下次发货时间
                break;
            case 'once_card_half_month':
                $list[$key]['once_card_week'] = '半个月';
                $dayNum = 15*$numDay;
                if (strtotime("+".$dayNum." day", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$dayNum." day", $successTime)).$str;// 下次发货时间
                break;
            case 'once_card_month':
                $list[$key]['once_card_week'] = '一个月';
                if (strtotime("+".$numDay." Month", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+".$numDay." Month", $successTime)).$str;// 下次发货时间
                break;
            default:
                $list[$key]['once_card_week'] = '未选择';
                if (strtotime("+1 day", $successTime) > strtotime("+".$list[$key]['once_card_day']." day", $successTime)) {
                    $str = '<span class="text-muted label btn-danger">过期</span>';// 下次发货时间
                } else {
                    $str = '';
                }
                $list[$key]['once_card_next'] = date("Y-m-d", strtotime("+1 day", $successTime)).$str;// 下次发货时间
                break;
        }
    }


    //$alldan = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and is_tuan=0 and  uniacid='{$_W['uniacid']}' and mobile<>'虚拟'  ");

    //
    if ($dispatchtype > 0) {
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition  and mobile<>'虚拟' {$cons} ", $paras);
        $pager = pagination($total, $pindex, $psize);
        $all = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and  uniacid='{$_W['uniacid']}' and mobile<>'虚拟' {$cons} ");
        $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=0 and mobile<>'虚拟' {$cons} ");//待付款
        $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟' {$cons} ");//已付款
        $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=2 and mobile<>'虚拟' {$cons}");//待收货
        $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=3 and mobile<>'虚拟' {$cons} ");//已签收
        $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status in (4,5,6,7) and mobile<>'虚拟' {$cons} ");//已退款
        $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=5 and mobile<>'虚拟' {$cons} ");//强退款
        $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$cons} ");//部分退款
        $status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=7 and mobile<>'虚拟' {$cons} ");//团长免单
        $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' AND is_partsend = 1 and status=8 and mobile<>'虚拟' {$cons} ");//待发货
        $status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=9 and mobile<>'虚拟' {$cons} ");//已取消
        $status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$cons} ");//已关闭
        $status17 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and is_tuan=0 {$cons}");//单买单
        $status11 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status = 11 {$cons}");//退款异常单
        $status23 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' AND status='8' AND is_partsend = 2 {$cons}");//部分发货单

    } else {
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition  and mobile<>'虚拟' {$cons} ", $paras);
        $pager = pagination($total, $pindex, $psize);
        $all = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and mobile<>'虚拟' {$cons}");
        $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=0 and mobile<>'虚拟' {$cons} ");//待付款
        $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟' {$cons} ");//已付款
        $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=2 and mobile<>'虚拟' {$cons} ");//待收货
        $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=3 and mobile<>'虚拟' {$cons} ");//已签收
        $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status in (4,5,6,7) and mobile<>'虚拟' {$cons} ");//已退款
        $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=5 and mobile<>'虚拟' {$cons}");//强退款
        $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$cons}");//部分退款
        $status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=7 and mobile<>'虚拟' {$cons}");//团长免单
        $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' AND is_partsend = 1 and status=8 and mobile<>'虚拟' {$cons}");//待发货
        $status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=9 and mobile<>'虚拟' {$cons}");//已取消
        $status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$cons} ");//已关闭
        $status17 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and is_tuan=0 {$cons}");//单买单
        $status18 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  status=8  and uniacid='{$_W['uniacid']}' and issued = 0 and selltype=5 {$cons}");//单买单
        $status19 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=8  and uniacid='{$_W['uniacid']}' and godluck = 1 and selltype=5 {$cons}");//单买单
        $status20 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=7  and uniacid='{$_W['uniacid']}' and godluck = 0 and selltype=5 {$cons}");//单买单
        $status21 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=3 and uniacid='{$_W['uniacid']}' and issued = 2 and selltype=5 {$cons}");//单买单
        $status11 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status = 11 and mobile<>'虚拟' {$cons}");//退款异常单
        $status23 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and uniacid='{$_W['uniacid']}' and mobile<>'虚拟' AND status='8' AND is_partsend = 2 {$cons}");//部分发货单
    }

}

if ($op == 'detail') {
    wl_load()->model('activity');
    wl_load()->model('goods');
    load()->model('mc');
    $province = pdo_getall('erp_area', array('level' => 1));
    $id = intval($_GPC['id']);
    unset($item);
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (empty($item)) {
        message("抱歉，订单不存在!", referer(), "error");
    }

    $coll = pdo_fetch("select remind_times from " . tablename('tg_order_collect') . " where orderno = '{$item['orderno']}' and uniacid = '{$item['uniacid']}' and openid = '{$item['openid']}' ");
    $item['remind_times'] = intval($coll['remind_times']);
    $group = '';
    if ($item['pay_type'] != 9) {
        $group = ' GROUP BY refund_id ';
    }
    $rfee = pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id AND status = 1 AND refund_id <> '' {$group} ", array(':id' => $item['transid']));
    $resultfee = 0;
    if (!empty($item['transid'])) {
        foreach ($rfee as $rf => $rforder) {
            $resultfee += $rforder['refundfee'];
        }
    }
    if ($item['bukuanstatus'] == 2) {
        $bukuan = pdo_fetch("select price from " . tablename('tg_order') . " where master_orderno = '{$item['orderno']}' AND status = 3 ");
        $item['price'] += $bukuan['price'];
    }
    $mfee = $item['price'] - $resultfee;

    $coupon_template = coupon_template($item['couponid']);
    $option = pdo_fetch("SELECT title,productprice,marketprice FROM " . tablename("tg_goods_option") . " WHERE id=:id LIMIT 1", array(":id" => $item['optionid']));
    if ($item['status'] == 7) {
        $refund_record = pdo_fetch("SELECT * FROM " . tablename('tg_refund_record') . " WHERE orderid = :id", array(':id' => $id));
    }

    unset($saler);
    if ($item['veropenid']) {
        $saler = pdo_fetch("select nickname from " . tablename('tg_member') . " where uniacid = '{$_W['uniacid']}' and from_user = '" . $item['veropenid'] . "'");
    }
    $uid = mc_openid2uid($item['openid']);
    $result = mc_fansinfo($uid, $_W['acid'], $_W['uniacid']);
    $item['fanid'] = $result['fanid'];
    $item['user'] = pdo_fetch("SELECT * FROM " . tablename('tg_address') . " WHERE id = {$item['addressid']}");
    $goods = goods_get_by_params(" id={$item['g_id']} ");
    $item['goods'] = $goods;
    if ($item['status'] == 4 || $item['status'] == 6 || $item['status'] == 7 || $item['status'] == 5) {
        $refund = pdo_fetch("select createtime from" . tablename('tg_refund_record') . "where orderid={$item['id']}");
        $refund_time = $refund['createtime'];
    }

    if ($item['dispatchtype'] == 1) {
        $delivery_man = pdo_fetchall("select * from " . tablename('tg_delivery_man') . " where uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and status = 1 ");
    }

    if ($item['senddate'] == '0000-00-00') {
        $item['senddate'] = null;
    }
    if (!(strpos($item['sendtime'], '-') !== false)) {
        $item['sendtime'] = date('Y-m-d H:i', $item['sendtime']);
    }
    $store = pdo_fetch("select * from" . tablename('tg_store') . " where id ='" . $item['comadd'] . "'");

    $c_order = pdo_fetchall("SELECT * FROM ".tablename('tg_order_child')." where orderno='".$item['orderno']."'");

    $storelist = pdo_fetchall("SELECT * FROM ".tablename('tg_store')." where uniacid='{$_W['uniacid']}' and status=1");

    include wl_template('order/order_detail');
    exit;
}
if ($op == 'once_card_detail') {
    $id = $_GPC['id'];
    $list = pdo_getall('tg_order_once_card_record', array('orderid' => $id));
    include wl_template('order/order_detail_once_card');
    exit;
}
if ($op == 'confrimpay') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    pdo_update('tg_order', array('status' => 1, 'pay_type' => 2, 'ptime' => TIMESTAMP), array('id' => $id));
    $oplogdata = serialize($item);
    oplog('admin', "后台确认付款", web_url('order/order/confrimpay'), $oplogdata);
    message('确认订单付款操作成功！', referer(), 'success');
}
if ($op == 'confirmsend') {
    $id = $_GPC['id'];
    $isexpress = intval($_GPC['isexpress']);
//die(($isexpress.$_GPC['express'].$_GPC['storeid']."<br>".intval($_GPC['expresssn'])));
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if ($item['is_once_card'] == 1) {
        $dat['orderid'] = $id;
        $dat['orderno'] = $item['orderno'];
        $dat['orderno_child'] = date('Ymd') . mt_rand(100, 999);
        $dat['openid'] = $item['openid'];
        $dat['order_status'] = $item['dispatchtype'];
        // 配送时间
        switch ($item['once_card_json']) {
            case 'once_card_mon':
                $dat['send_date'] = '星期一';
                break;
            case 'once_card_tues':
                $dat['send_date'] = '星期二';
                break;
            case 'once_card_wed':
                $dat['send_date'] = '星期三';
                break;
            case 'once_card_thur':
                $dat['send_date'] = '星期四';
                break;
            case 'once_card_fir':
                $dat['send_date'] = '星期五';
                break;
            case 'once_card_sat':
                $dat['send_date'] = '星期六';
                break;
            case 'once_card_sun':
                $dat['send_date'] = '星期天';
                break;
            case 'once_card_half_month':
                $dat['send_date'] = '半个月';
                break;
            case 'once_card_month':
                $dat['send_date'] = '一个月';
                break;
            default:
                $dat['send_date'] = '未选择';
                break;
        }
        // $dat['send_date'] = $item['send_date'];
        $dat['delivery_date'] = TIMESTAMP;
        $dat['express'] = $_GPC['express'];
        $dat['expresssn'] = $_GPC['expresssn'];
        $dat['mobile'] = $item['mobile'];
        $dat['province'] = $item['province'];
        $dat['city'] = $item['city'];
        $dat['county'] = $item['county'];
        $dat['detailed_address'] = $item['detailed_address'];
        $dat['comadd'] = $item['comadd'];
        $dat['uniacid'] = $_W['uniacid'];
        pdo_insert('tg_order_once_card_record', $dat);
    }
    if (!empty($_GPC['expresssn'])) {
        $r = pdo_update('tg_order', array('status' => 2, 'express' => $_GPC['express'], 'expresssn' => $_GPC['expresssn'], 'delivery_time' => TIMESTAMP), array('id' => $id));
    } elseif ($isexpress) {
        $r = pdo_update('tg_order', array('status' => 2, 'express' => $_GPC['express'], 'storeid' => $_GPC['storeid'], 'delivery_time' => TIMESTAMP), array('id' => $id));
    } else {
        $r = pdo_update('tg_order', array('status' => 2, 'express' => $_GPC['express'], 'expresssn' => $_GPC['storeid'], 'delivery_time' => TIMESTAMP), array('id' => $id));
    }

    if ($r) {
        /*更新可结算金额*/
        if (!empty($item['merchantid'])) {
            merchant_update_no_money($item['price'], $item['merchantid']);
        }
        /*记录操作*/
        $oplogdata = serialize($item);
        oplog('admin', "后台确认发货", web_url('order/order/confirmsend'), $oplogdata);
        /*发货成功消息提醒*/
        $url = app_url('order/order/detail', array('id' => $item['id']));
        if ($item['dispatchtype'] == 1) {
            $dispatch = pdo_fetch("select nickname,tel from " . tablename('tg_delivery_man') . " where id = '{$item['storeid']}' ");
            $nickname = $dispatch['nickname'];
            $tel = $dispatch['tel'];

            if ($isexpress) {

                $title = "亲，您的商品已派送！订单编号：" . $item['orderno'] . "  派送员：" . $nickname;

                $message = '订单派送';
                $rem = '当派送员将货物送至时，请将确认收货的二维码给派送员扫码确认收货【代收请将二维码截图，并告知派送员代收】';
                if ($item['g_id'] > 0) {
                    $good = pdo_fetch("SELECT gname FROM " . tablename('tg_goods') . " WHERE id = " . $item['g_id']);
                    $gname = $good['gname'];
                } else {
                    $collect = pdo_fetchall("SELECT goodsname FROM " . tablename('tg_collect') . " WHERE orderno = '" . $item['orderno']."'");
                    $gname = '';
                    foreach ($collect as $val) {
                        $gname .= $val['goodsname'] . "\r";
                    }
                }
//            die($dispatch['nickname'].$dispatch['tel']);
                dispatch_success($gname, $item['orderno'], $item['openid'], $dispatch['nickname'], $dispatch['tel'], app_url('order/order/detail', array('id' => $item['id'])));
            } else {
                send_success($item['orderno'], $item['openid'], $_GPC['express'], $_GPC['storeid'], $url);
            }
        } else {
            send_success($item['orderno'], $item['openid'], $_GPC['express'], $_GPC['expresssn'], $url);
        }
        message('发货操作成功！', web_url('order/order/detail', array('id' => $item['id'], 'dispatchtype' => $item['dispatchtype'])), 'success');
    } else {
        message('发货操作失败！', web_url('order/order/detail', array('id' => $item['id'], 'dispatchtype' => $item['dispatchtype'])), 'error');
    }

}
if ($op == 'cancelsend') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    $r = pdo_update('tg_order', array('status' => 8, 'express' => '', 'expresssn' => '', 'sendtime' => ''), array('id' => $id));
    if ($r) {
        /*更新可结算金额*/
        if (!empty($item['merchantid'])) {
            merchant_update_no_money(0 - $item['price'], $item['merchantid']);
        }
        /*记录操作*/
        $oplogdata = serialize($item);
        oplog('admin', "后台取消发货", web_url('order/order/cancelsend'), $oplogdata);
        message('取消发货操作成功！', referer(), 'success');
    } else {
        message('取消发货操作失败！', referer(), 'error');
    }

}
if ($op == 'refund') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    $orderno = $item['orderno'];
    pdo_update('tg_order', array('refund_res' => $_GPC['refundres']), array('id' => $id));
    if ($_GPC['refundall'] == 1) {
        $res = refund($orderno, 2);
        if ($item['selltype'] == 7 && $item['bukuanstatus'] == 2) {
            $master = pdo_get('tg_order' , array('master_orderno' => $item['orderno']));
            $res = refund($master['orderno'], 2);
        }
    } else {
        $rfee = pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id AND status=1 ", array(':id' => $item['transid']));
        $resultfee = 0;
        if (!empty($item['transid'])) {
            foreach ($rfee as $rf => $rforder) {
                $resultfee += $rforder['refundfee'];
            }
        }
        $mfee = $item['price'] - $resultfee;
        $res = partrefund($orderno, 2, $_GPC['refundnum']);
    }

    if ($res == 'success') {
        $oplogdata = serialize($item);
        oplog('admin', "后台订单详情退款", web_url('order/order/refund'), $oplogdata);

        /*退款成功消息提醒*/
        $url = app_url('order/order/detail', array('id' => $item['id']));
        $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$item['g_id']}'");
        if ($_GPC['refundall'] == 1) {
            refund_success($item['orderno'], $goods['gname'], $item['openid'], $item['price'], time(), $url);
        }else{
            refund_success($item['orderno'], $goods['gname'], $item['openid'],$_GPC['refundnum'], time(), $url);
        }
        die(json_encode(array('status' => 1)));
    } elseif ($res != 'success') {
        die(json_encode(array('status' => 0, 'message' => $res)));
    } else {
        die(json_encode(array('status' => 0)));
    }
}
if ($op == 'import') {
    $file = $_FILES['fileName'];
    $max_size = "2000000";
    $fname = $file['name'];
    $ftype = strtolower(substr(strrchr($fname, '.'), 1));
    //文件格式
    $uploadfile = $file['tmp_name'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (is_uploaded_file($uploadfile)) {
            if ($file['size'] > $max_size) {
                echo "Import file is too large";
                exit;
            }
            if ($ftype == 'csv') {
                if (empty ($uploadfile)) {
                    echo '请选择要导入的CSV文件！';
                    exit;
                }
                $handle = fopen($uploadfile, 'r');
                $n = 0;
                while ($data = fgetcsv($handle, 10000)) {
                    $num = count($data);
                    for ($i = 0; $i < $num; $i++) {
                        $out[$n][$i] = $data[$i];
                    }
                    $n++;
                }
                $result = $out; //解析csv
                $len_result = count($result);
                if ($len_result == 0) {
                    echo '没有任何数据！';
                    exit;
                }
                $succ_result = 0;
                $error_result = 0;
                for ($i = 1; $i < $len_result; $i++) { //循环获取各字段值
                    $orderNo = trim(iconv('gb2312', 'utf-8', $result[$i][0])); //中文转码
                    if ($orderNo == '') {
                        continue;
                    }

                    $expressOrder = trim(iconv('gb2312', 'utf-8', $result[$i][31]));

                    if (!empty($expressOrder)) {

                        $order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderNo}'");
                        if ($expressOrder == '是') {
                            $godluck = 1;
                            $cj_message = 2;
                        } else {
                            $godluck = 0;
                            $cj_message = 1;
                        }
                        $res = pdo_update('tg_order', array('godluck' => $godluck, 'issued' => 1, 'cj_message' => $cj_message), array('orderno' => $orderNo, 'selltype' => 5));
                        if ($res) {
                            $url = app_url('order/order/detail', array('id' => $order['id']));
                            //send_success($order['orderno'], $order['openid'], $expressName, $orderNo, $url);
                            $succ_result += 1;
                        } else {
                            $error_result += 1;
                        }
                    } else {
                        $error_result += 1;
                    }
                }
                fclose($handle); //关闭指针
            } else {
                echo "文件后缀格式必须csv";
                exit;
            }
        } else {
            echo "文件名不能为空!";
            exit;
        }
    }
    message('导入抽奖订单操作成功！成功' . $succ_result . '条，失败' . $error_result . '条', referer(), 'success');
}
if ($op == 'output') {
    set_time_limit(0);
    $condition = "  uniacid = '{$_W['uniacid']}' ";
//    $condition .= " and  merchantid = {$_W['user']['merchant_id']} ";

    $condition .= ' and supply_goodsid=0';

    $paras = array();
    $status = $_GPC['status'];
    $transid = $_GPC['transid'];
    $pay_type = $_GPC['pay_type'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];
    $merchantid = $_GPC['merchantid'];

    if (!empty($merchantid)) {
        if (intval($merchantid) == -1) {
            $condition .= " AND  merchantid > 0 ";
            $condition2 .= " AND  merchantid > 0 ";
        } else {
            $condition .= " AND  merchantid = '{$merchantid}' ";
            $condition2 .= " AND  merchantid = '{$merchantid}' ";
        }
    } else {
        $condition .= " AND  merchantid = '{$_W['user']['merchant_id']}' ";
        $condition2 .= " AND  merchantid = '{$_W['user']['merchant_id']}' ";
    }
    $dispatchtype = $_GPC['dispatchtype'];


    $comadd = intval($_GPC['comadd']);
    $checkstore = intval($_GPC['checkstore']);
    $deliveryid = intval($_GPC['deliveryid']);
    if ($comadd) {
        $condition .= " and comadd = {$comadd} ";
    }
    if ($checkstore) {
        $condition .= " and checkstore = {$checkstore} ";
    }
    if ($deliveryid) {
        $deliver = pdo_fetch("select * from " . tablename('tg_delivery_man') . " where id = {$deliveryid} ");
        $condition .= " and ( storeid = {$deliveryid} or veropenid = '{$deliver['openid']}' ) ";
    }

    if (!empty($dispatchtype) && $dispatchtype != 0 && $dispatchtype != -1) {
        $condition .= " AND  dispatchtype = '{$dispatchtype}'";
    }

    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    $type = intval($_GPC['type']);
    if ($type == 0) {
        if (!empty($_GPC['time'])) {
            //message($_GPC['time']['start']);
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
            $condition2 .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
    } else {
        if (!empty($_GPC['time'])) {
            //message($_GPC['time']['start']);
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  hexiaotime >= {$starttime} AND  hexiaotime <= {$endtime} ";
            $condition2 .= " AND  hexiaotime >= {$starttime} AND  hexiaotime <= {$endtime} ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
    }

    if (trim($_GPC['goodsid']) != '') {
        $condition .= " and (g_id = '{$_GPC['goodsid']}' or orderno in (select orderno from cm_tg_collect where sid ='{$_GPC['goodsid']}')) ";
        $condition2 .= " and g_id like '%{$_GPC['goodsid']}%' ";;
    }
    if (trim($_GPC['goodsid2']) != '') {
        $goodsid2 = trim($_GPC['goodsid2']);
        $condition .= " and (goodsname like '%{$goodsid2}%' or orderno in (select orderno from cm_tg_collect where goodsname like '%{$goodsid2}%')) ";
        $condition2 .= " and (goodsname like '%{$goodsid2}%' or orderno in (select orderno from cm_tg_collect where goodsname like '%{$goodsid2}%')) ";

        //$condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
        //$condition2 .= " and g_id like '%{$_GPC['goodsid2']}%' ";
    }
    if (trim($_GPC['address']) != '') {

        $condition .= " and address like '%{$_GPC['address']}%' ";
    }
    if (!empty($_GPC['merchantid'])) {
        $condition .= " AND  merchantid={$_GPC['merchantid']} ";
    }
    if (!empty($_GPC['transid'])) {
        $condition .= " AND  transid LIKE  '%{$_GPC['transid']}%'";
    }
    if (!empty($_GPC['addresstype'])) {
        $condition .= " AND  addresstype={$_GPC['addresstype']} ";
    }
    if (!empty($_GPC['pay_type'])) {
        $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
    } elseif ($_GPC['pay_type'] === '0') {
        $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
    }
    if (!empty($_GPC['salers'])) {
        $condition .= " AND  veropenid='{$_GPC['salers']}' ";
        $condition2 .= " AND  veropenid='{$_GPC['salers']}' ";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND (addname LIKE '%{$_GPC['member']}%')";
    }
    if (!empty($_GPC['mobile'])) {
        $condition .= " AND ( mobile = '{$_GPC['mobile']}')";
    }

    if ($dispatchtype == -1) {
        $condition .= " AND  selltype = 5";
    }

    if ($_GPC['issued'] == -1) {

        $condition .= " AND  issued=0 and status=8 ";
        $condition2 .= " AND  issued=0 and status=8";
    }
    if ($_GPC['issued'] == 2) {

        $condition .= " AND  issued=2 ";
        $condition2 .= " AND  issued=2 ";
    }
    if ($_GPC['issued'] == 1) {

        $condition .= " AND  issued=1 ";
        $condition2 .= " AND  issued=1 ";
    }
    if ($status != '') {
        if ($status == 1) {
            $condition .= " AND status = '" . intval($status) . "' ";

        } else if ($status == 8) {
            $condition .= " AND status=8 ";

        } else if ($status == 4) {
            $condition .= " AND status in (4,7)";
            $condition .= " AND is_tuan=1";
        } else if ($status == 7) {
            $condition .= " AND status in (4,5,6,7)";

        } else if ($status == 17) {
            $condition .= " AND is_tuan=0";
            $table = 'tg_order';
        } else {
            $condition .= " AND status = '" . intval($status) . "'";

        }

    }
    $condition .= " and master_orderno is NULL";

    //message($condition);
    $sql = "select * from" . tablename('tg_order') . "where $condition and mobile<>'虚拟' order by successtime asc";
    $orders = pdo_fetchall($sql, $paras);

    //$orders = pdo_fetchall("select * from" . tablename('tg_order') . "where $condition and mobile<>'虚拟' order by successtime asc");
    switch ($status) {
        case NULL:
            $str = '全部订单_' . time();
            break;
        case 1:
            $str = '已支付订单_' . time();
            break;
        case 8:
            $str = '待发货订单' . time();
            break;
        case 2:
            $str = '待收货订单' . time();
            break;
        case 3:
            $str = '已签收订单' . time();
            break;
        case 4:
            $str = '已退款订单' . time();
            break;
        case 5:
            $str = '强退款订单' . time();
            break;
        case 6:
            $str = '部分退款订单' . time();
            break;
        case 7:
            $str = '已退款订单' . time();
            break;
        case 9:
            $str = '已取消订单' . time();
            break;
        case 10:
            $str = '待退款订单' . time();
            break;
        case 11:
            $str = '退款异常订单' . time();
            break;
        default:
            $str = '待支付订单' . time();
            break;
    }
    /* 输入到CSV文件 */
    $html = "\xEF\xBB\xBF";
    /* 输出表头 */
    $filter = array('aa' => '订单编号', 'sj' => '所属商家', 'll' => '团ID', 'bb' => '姓名', 'cc' => '电话', 'm2' => '运费', 'dd' => '总价(元)', 'd1' => '退款金额', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'pp' => '商品规格', 'oo' => '购买数量', 'mm' => '买家留言', 'h1' => '省', 'h2' => '市', 'h3' => '区', 'h4' => '详细地址', 'm6' => '地址类型', 'ii' => '微信订单号', 'jj' => '快递单号', 'ps' => '派送员标志id', 'kk' => '快递名称', 'm1' => '配送方式', 'mz' => '组团成功时间', 'dd1' => '派送员', 'dd2' => '签收方式', 'm3' => '核销员', 'm4' => '核销门店', 'm8' => '核销时间', 'm5' => '客选门店', 'm7' => '是否中奖', 'm9' => '卖家留言', 'm11' => '粉丝昵称', 'm12' => '团长昵称','m13' => '团长姓名');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    $html .= "\n";
    foreach ($orders as $k => $v) {
        if ($v['status'] == '0') {
            $thistatus = '未支付';
        }
        if ($v['status'] == '1') {
            $thistatus = '已支付';
        }
        if ($v['status'] == '2') {
            $thistatus = '待收货';
        }
        if ($v['status'] == '3') {
            $thistatus = '已签收';
        }
        if ($v['status'] == '4') {
            $thistatus = '已退款';
        }
        if ($v['status'] == '5') {
            $thistatus = '强退款';
        }
        if ($v['status'] == '6') {
            $thistatus = '部分退款';
        }
        if ($v['status'] == '7') {
            $thistatus = '已退款';
        }
        if ($v['status'] == '8') {
            $thistatus = '待发货';
        }
        if ($v['status'] == '9') {
            $thistatus = '已取消';
        }
        if ($v['status'] == '10') {
            $thistatus = '待退款';
        }
        if ($v['status'] == '11') {
            $thistatus = '退款异常';
        }
        if ($v['status'] == '') {
            $thistatus = '全部订单';
        }
        if ($v['dispatchtype'] == 0) {
            $disname = '无';
        }
        if ($v['dispatchtype'] == 1) {
            $disname = '送货上门';
        }
        if ($v['dispatchtype'] == 2) {
            $disname = '快递';
        }

        wl_load()->model('member');
        $mermber = member_get_by_params(" openid='{$v['openid']}' ");
        if (!empty($v['addressid']) && $v['addressid'] > 0 && $v['dispatchtype'] != 3) {
            $add = pdo_fetch("select * from" . tablename('tg_address') . "where id = '{$v['addressid']}' ");
        }

        $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$v['g_id']}' and uniacid='{$_W['uniacid']}'");
        $time = date('Y-m-d H:i:s', $v['createtime']);
        if ($v['addresstype'] == 1) {
            $addresstype = '公司';
        } else {
            $addresstype = '家庭';
        }
        if ($v['dispatchtype'] == 3) {
            $disname = '自提';
            $v['address'] = '';
            $v['province'] = '';
            $v['city'] = '';
            $v['county'] = '';
            $v['detailed_address'] = '';
            $addresstype = '';
        }
        if (!empty($v['veropenid'])) {
            $saler = pdo_fetch("select nickname from" . tablename('tg_member') . " where from_user ='" . $v['veropenid'] . "'");
            $saler['nickname'] = str_replace("“", " ", $saler['nickname']);
            $saler['nickname'] = str_replace("”", " ", $saler['nickname']);
        }
        if (!empty($v['checkstore'])) {
            $realstore = pdo_fetch("select storename from" . tablename('tg_store') . " where id ='" . $v['checkstore'] . "'");
        }

        if ($v['dispatchtype'] == 1) {
            if ($v['checkstore'] == 1) {
                $dstatus = '本人签收';
            } elseif ($v['checkstore'] == 2) {
                $dstatus = '朋友代收';
            }
            if ($v['storeid'] > 0) {
                $delivery = pdo_fetch("select nickname from " . tablename('tg_delivery_man') . " where id = '" . $v['storeid'] . "'");
            }
        }

        if (!empty($v['comadd']) && $v['dispatchtype'] == 3) {
            $com = pdo_fetch("select storename from" . tablename('tg_store') . " where id ='" . $v['comadd'] . "'");
        }
        if (!empty($v['hexiaotime'])) {
            $hexiaotime = date('Y-m-d H:i:s', $v['hexiaotime']);

        }
        $options = pdo_fetch("SELECT title,productprice,marketprice FROM " . tablename("tg_goods_option") . " WHERE id=:id LIMIT 1", array(":id" => $v['optionid']));
        if (empty($options['title'])) {
            $options['title'] = '不限';
        }
        $resultfee = 0;
        $rfee = pdo_fetchall("SELECT refundfee FROM " . tablename('tg_refund_record') . " WHERE transid = :id AND status=1 AND uniacid=:uniacid ", array(':id' => $v['transid'], ':uniacid' => $_W['uniacid']));
        foreach ($rfee as $rf => $rforder) {
            $resultfee += $rforder['refundfee'];
        }

        if ($v['g_id'] > 0) {
            $gname = $v['goodsname'];
            $gopname = $v['optionname'];
            $orders[$k]['aa'] = $v['orderno'];
            $m = $v['merchantid'];
            if ($m == 0) {
                $merchant_name = $_W['account']['name'];
            } else {
                $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                $merchant_name = $name['name'];
            }
            $orders[$k]['sj'] = $merchant_name;
            $orders[$k]['ll'] = intval($v['tuan_id']) ? intval($v['tuan_id']) : '';
            //$orders[$k]['qq'] = $mermber['nickname'];
            $orders[$k]['bb'] = $v['addname'];
            $orders[$k]['cc'] = $v['mobile'];
            $orders[$k]['m2'] = $v['freight'];
            $orders[$k]['dd'] = $v['pay_price'];
            $orders[$k]['d1'] = $resultfee;
            $orders[$k]['ee'] = $thistatus;
            $orders[$k]['ff'] = $time;
            $orders[$k]['gg'] = $gname;
            if (!empty($v['province'])) {
                $orders[$k]['h1'] = $v['province'];
                $orders[$k]['h2'] = $v['city'];
                $orders[$k]['h3'] = $v['county'];
                $orders[$k]['h4'] = $v['detailed_address'];
            } else {
                $orders[$k]['h1'] = '';
                $orders[$k]['h2'] = '';
                $orders[$k]['h3'] = '';
                $orders[$k]['h4'] = $v['address'];
            }
//            $orders[$k]['hh'] = $v['address'];
            $orders[$k]['ii'] = $v['transid'];
            $orders[$k]['jj'] = $v['expresssn'];
            $orders[$k]['ps'] = $v['storeid'];
            $orders[$k]['kk'] = $v['express'];
            $orders[$k]['m1'] = $disname;

            if (!empty($v['successtime'])) {
                $orders[$k]['mz'] = date('Y-m-d H:i:s', $v['successtime']);
            } else {
                $orders[$k]['mz'] = '';
            }
            $orders[$k]['dd1'] = $delivery['nickname'];
            $orders[$k]['dd2'] = $dstatus;
            $orders[$k]['mm'] = $v['remark'];
            $orders[$k]['m3'] = $saler['nickname'];
            $orders[$k]['m4'] = $realstore['storename'];
            $orders[$k]['m5'] = $com['storename'];
            $orders[$k]['m6'] = $addresstype;
            $orders[$k]['m7'] = '';
            $orders[$k]['m9'] = $v['adminremark'];
            if (!empty($v['hexiaotime'])) {
                $orders[$k]['m8'] = date('Y-m-d H:i:s', $v['hexiaotime']);
            } else {
                $orders[$k]['m8'] = '';
            }

            $orders[$k]['oo'] = $v['gnum'];
            $orders[$k]['pp'] = $gopname;
            $orders[$k]['m11'] = '昵称：' . $mermber['nickname'];
            if ($v['is_tuan'] == 1) {
            $tuanzhang = pdo_get("tg_order", array('tuan_first' => 1, 'tuan_id' => $v['tuan_id']));
            $tuanzhangname = member_get_by_params(" openid='{$tuanzhang['openid']}' ");
            $orders[$k]['m12'] = '团长：' . $tuanzhangname['nickname'];
                $orders[$k]['m13'] = '团长：' . $tuanzhang['addname'];
           }else{
                $orders[$k]['m12'] ='';
                $orders[$k]['m13'] ='';
            }

            foreach ($filter as $key => $title) {
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
        } else {
            $col = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE  orderno=:orderno", array('orderno' => $v['orderno']));
            $orders[$k]['aa'] = $v['orderno'];
            $m = $v['merchantid'];
            if ($m == 0) {
                $merchant_name = $_W['account']['name'];
            } else {
                $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                $merchant_name = $name['name'];
            }
            $orders[$k]['sj'] = $merchant_name;
            $orders[$k]['ll'] = '';
            $orders[$k]['bb'] = $v['addname'];
            $orders[$k]['cc'] = $v['mobile'];
            $orders[$k]['dd'] = $v['price'];
            $orders[$k]['d1'] = $resultfee;
            $orders[$k]['ee'] = $thistatus;
            $orders[$k]['ff'] = $time;
            $orders[$k]['gg'] = '';
            if (!empty($v['province'])) {
                $orders[$k]['h1'] = $v['province'];
                $orders[$k]['h2'] = $v['city'];
                $orders[$k]['h3'] = $v['county'];
                $orders[$k]['h4'] = $v['detailed_address'];
            } else {
                $orders[$k]['h1'] = '';
                $orders[$k]['h2'] = '';
                $orders[$k]['h3'] = '';
                $orders[$k]['h4'] = $v['address'];
            }
//            $orders[$k]['hh'] = $v['address'];
            $orders[$k]['ii'] = $v['transid'];
            $orders[$k]['jj'] = $v['expresssn'];
            $orders[$k]['ps'] = $v['storeid'];
            $orders[$k]['kk'] = $v['express'];
            $orders[$k]['m1'] = $disname;
            $orders[$k]['m2'] = $v['freight'];
            $orders[$k]['m3'] = $saler['nickname'];
            $orders[$k]['m4'] = $realstore['storename'];
            $orders[$k]['m5'] = $com['storename'];
            $orders[$k]['m6'] = $addresstype;
            if (!empty($v['hexiaotime'])) {
                $orders[$k]['m8'] = date('Y-m-d H:i:s', $v['hexiaotime']);
            } else {
                $orders[$k]['m8'] = '';
            }
            $orders[$k]['m9'] = $v['adminremark'];
            $orders[$k]['ll'] = $v['tuan_id'];
            if (!empty($v['successtime'])) {
                $orders[$k]['mz'] = date('Y-m-d H:i:s', $v['successtime']);
            } else {
                $orders[$k]['mz'] = '';
            }
            $orders[$k]['dd1'] = $delivery['nickname'];
            $orders[$k]['dd2'] = $dstatus;
            $orders[$k]['mm'] = $v['remark'];
            $orders[$k]['oo'] = '';
            $orders[$k]['pp'] = '';
            $orders[$k]['m11'] = '昵称：' . $mermber['nickname'];
            foreach ($filter as $key => $title) {
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
            foreach ($col as $c => $cc) {
                $gname = $cc['goodsname'];
                $gopname = $cc['item'];
                $orders[$k]['aa'] = '';
                $m = $v['merchantid'];
                if ($m == 0) {
                    $merchant_name = $_W['account']['name'];
                } else {
                    $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                    $merchant_name = $name['name'];
                }
                $orders[$k]['sj'] = $merchant_name;
                $orders[$k]['ll'] = '';
                $orders[$k]['bb'] = '';
                $orders[$k]['cc'] = '';
                $orders[$k]['dd'] = $cc['oprice'];
                $orders[$k]['ee'] = '';
                $orders[$k]['ff'] = $time;
                $orders[$k]['gg'] = $gname;
                $orders[$k]['h1'] = '';
                $orders[$k]['h2'] = '';
                $orders[$k]['h3'] = '';
                $orders[$k]['h4'] = '';
//                $orders[$k]['hh'] = '';
                $orders[$k]['ii'] = '';
                $orders[$k]['jj'] = '';
                $orders[$k]['ps'] = '';
                $orders[$k]['kk'] = '';
                $orders[$k]['m1'] = '';
                $orders[$k]['m2'] = '';
                $orders[$k]['m3'] = '';
                $orders[$k]['m4'] = '';
                $orders[$k]['m5'] = '';
                $orders[$k]['m6'] = $addresstype;
                $orders[$k]['m7'] = '';
                $orders[$k]['m8'] = '';
                $orders[$k]['m9'] = '';
                $orders[$k]['ll'] = '';
                $orders[$k]['mz'] = '';
                $orders[$k]['dd1'] = '';
                $orders[$k]['dd2'] = '';
                $orders[$k]['mm'] = '';
                $orders[$k]['oo'] = $cc['num'];
                $orders[$k]['pp'] = $gopname;
                $orders[$k]['m11'] = '昵称：' . $mermber['nickname'];
                $orders[$k]['m12'] ='';
                $orders[$k]['m13'] ='';
                foreach ($filter as $key => $title) {
                    $html .= $orders[$k][$key] . "\t,";
                }
                $html .= "\n";
            }
        }

        unset($mermber);
        unset($addresstype);
        unset($gopname);
        unset($cc);
        unset($realstore);
        unset($com);
        unset($saler);
        unset($add);
        unset($dstatus);
        unset($delivery);
    }
    /* 输出CSV文件 */
    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename={$str}.csv");
    echo $html;
    exit();
}
if ($op == 'remark') {
    $orderid = intval($_GPC['id']);
    $remark = $_GPC['remark'];
    if (pdo_update('tg_order', array('adminremark' => $remark), array('id' => $orderid))) {
        die(json_encode(array('errno' => 0)));
    } else {
        die(json_encode(array('errno' => 1)));
    }
}
if ($op == 'address') {
    $orderid = intval($_GPC['id']);
    $province = $_GPC['province'];
    $city = $_GPC['city'];
    $county = $_GPC['county'];
    $address = $_GPC['address'];
    $realname = $_GPC['realname'];
    $mobile = $_GPC['mobile'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $orderid));

    if (pdo_update('tg_order', array(
        'province' => $province,
        'city' => $city,
        'county' => $county,
        'detailed_address' => $address,
        'address' => $province.$city.$county.$address,
        'addname' => $realname,
        'mobile' => $mobile
    ), array('id' => $orderid))) {
        $oplogdata = serialize($item);
        oplog($_W['username'], "后台修改地址", web_url('order/order/confrimpay'), $oplogdata);
        die(json_encode(array('errno' => 0)));
    } else {
        die(json_encode(array('errno' => 1)));
    }
}
// 修改自提收货信息
if ($op == 'store') {
    $orderid = intval($_GPC['id']);
    pdo_update('tg_order', array('comadd' => $_GPC['order_id']), array('id' => $orderid));
    die(json_encode(array('errno' => 1)));
}

if ($op == 'master_refund') {
    $orderno = $_GPC['orderno'];
    $order = pdo_fetch('select * from ' . tablename('tg_order') . ' where orderno=:orderno', array(':orderno' => $orderno));
    pdo_update('tg_order', array('status' => 10), array('orderno' => $orderno));
    pdo_update('tg_order', array('status' => 10), array('master_orderno' => $orderno));
    die(json_encode(array('errno' => 1)));
}
if($op == 'changestatus'){
    $orderno = $_GPC['orderno'];
    $rs = pdo_update('tg_order',array('status'=>8),array('orderno'=>$orderno,'uniacid'=>$_W['uniacid']));
    if($rs){
        die(json_encode(array('errno'=>1)));

    }else{
        die(json_encode(array('errno'=>0)));

    }
}
include wl_template('order/order');
exit();
