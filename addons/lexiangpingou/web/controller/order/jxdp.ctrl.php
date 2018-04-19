<?php
defined('IN_IA') or exit('Access Denied');
wl_load()->model('merchant');
wl_load()->func('print');
wl_load()->classs('qrcode');

$ops = array('master_refund', 'import', 'modifyPrice', 'refunds', 'dayin', 'update', 'summary', 'received', 'detail', 'output', 'remark', 'address', 'confrimpay', 'confirmsend', 'cancelsend', 'refund', 'updaterefund');
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


    message('打印成功');

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


//    file_put_contents(TG_DATA."aa.log", var_export(array('id1' => $merchantid), true).PHP_EOL, FILE_APPEND);


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
    $condition .= " and status in (1,2,8) and dispatchtype=3";
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
    $condition .= ' and dispatchtype=2 and supply_goodsid>0';
    $all = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE        uniacid='{$_W['uniacid']}' and mobile<>'虚拟' {$condition} ");
    $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=0 and mobile<>'虚拟' {$condition} ");//待付款
    $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟' {$condition} ");//已付款
    $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=2 and mobile<>'虚拟' {$condition} ");//待收货
    $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=3 and mobile<>'虚拟' {$condition} ");//已签收
    $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status in (4,5,6,7) and mobile<>'虚拟' {$condition} ");//已退款
    $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=5 and mobile<>'虚拟' {$condition}");//强退款
    $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$condition}");//部分退款
    $status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=7 and mobile<>'虚拟' {$condition}");//团长免单
    $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=8 and mobile<>'虚拟' {$condition}");//待发货
    $status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=9 and mobile<>'虚拟' {$condition}");//已取消
    $status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$condition} ");//已关闭
    $status17 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE    uniacid='{$_W['uniacid']}' and is_tuan=0 {$condition}");//单买单
    $status18 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  status=8  and uniacid='{$_W['uniacid']}' and issued = 0 and selltype=5 {$condition}");//单买单
    $status19 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=8  and uniacid='{$_W['uniacid']}' and godluck = 1 and selltype=5 {$condition}");//单买单
    $status20 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=7  and uniacid='{$_W['uniacid']}' and godluck = 0 and selltype=5 {$condition}");//单买单
    $status21 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=3 and uniacid='{$_W['uniacid']}' and issued = 2 and selltype=5 {$condition}");//单买单
//    }
    $seven_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟'   and createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    $seven_nocash_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status=0   and createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    $obligations = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status in (1,2,3,4,5,6,7,8)   and createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    $undelivereds = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status in (4,6,7)   and createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $stime, ':endtime' => $etime));

    $seven = pdo_fetchcolumn("select Sum(pay_price) as totalprice  from" . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' and status in(1,2,3,4,5,6,7,8,10)  AND createtime >= :createtime AND createtime <= :endtime {$condition}  ORDER BY createtime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    /*foreach($seven as$key=>$value){
        $incomes += $value['pay_price'];
    }*/
    $incomes = $seven;
    $yesterday_orders_list = pdo_fetchall("SELECT id,status FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟'   AND createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $ytime, ':endtime' => $yetime));
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
        $listone = pdo_fetchcolumn("SELECT COUNT(id)  FROM " . tablename('tg_order') . " WHERE $con   AND createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $e - 86400, ':endtime' => $e));
        $statusa1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $con and status  in (1,2,3,4,5,6,7,8,10)   AND createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $e - 86400, ':endtime' => $e));
        $statusa4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $con and status=3   AND createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $e - 86400, ':endtime' => $e));
        $statusa2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $con and status in (4,5,6,7,10)   AND createtime >= :createtime AND createtime <= :endtime {$condition} ORDER BY createtime ASC", array(':createtime' => $e - 86400, ':endtime' => $e));

        $list[$j]['gnum'] = $listone;
        $list[$j]['status4'] = $statusa4;
        $list[$j]['status1'] = $statusa1;
        $list[$j]['status2'] = $statusa2;
        $list[$j]['createtime'] = $e - 86400;
        $j++;
        $e = $e - 86400;
    }

    $day = $hit = $statusa4 = $statusa1 = $statusa2 = array();
    if (!empty($list)) {
        foreach ($list as $row) {
            $day[] = date('m-d', $row['createtime']);
            $hit[] = intval($row['gnum']);
            $statusa4[] = intval($row['statusa4']);
            $statusa1[] = intval($row['statusa1']);
            $statusa2[] = intval($row['statusa2']);
        }
    }

    for ($i = 0; $i = count($hit) < 2; $i++) {
        $day[] = date('m-d', $endtime);
        $hit[] = $day[$i] == date('m-d', $endtime) ? $hit[0] : '0';
    }
    //include wl_template('order/summary');
    //  exit;
}

if ($op == 'received') {
    if (empty($_GPC['merchantid'])) {
        $cons .= " and  merchantid = {$_W['user']['merchant_id']} ";
    }
    $cons .= ' and supply_goodsid>0';
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
        $condition .= " AND  merchantid = '{$merchantid}' ";
        $condition2 .= " AND  merchantid = '{$merchantid}' ";
    }
//    file_put_contents(TG_DATA."aa.log", var_export(array('id1' => $merchantid), true).PHP_EOL, FILE_APPEND);

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

    if (!empty($dispatchtype) && $dispatchtype != 0 && $dispatchtype != -1) {
        $condition .= " AND  dispatchtype = '{$_SESSION['dispatchtype']}'";
        $condition2 .= " AND  dispatchtype = '{$_SESSION['dispatchtype']}'";
    }
    if ($dispatchtype == -1) {
        $condition .= " AND  selltype = '5' ";
        $condition2 .= " AND  selltype = '5'";
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
            $condition .= " AND status='8' ";

        } else if ($status == 4) {
            $condition .= " AND status in (4,7)";

        } else if ($status == 7) {
            $condition .= " AND status in (4,5,6,7)";

        } else if ($status == 17) {
            $condition .= " AND is_tuan='0'";
            $table = 'tg_order';
        } else {
            $condition .= " AND status = '" . intval($status) . "'";

        }

    }
    $condition .= " and master_orderno is NULL";
    $condition2 .= " and master_orderno is NULL";
    $condition .= ' and supply_goodsid>0';
    $condition2 .= ' and supply_goodsid>0';
    file_put_contents(TG_DATA . "order_condition.log", var_export($condition, true) . PHP_EOL, FILE_APPEND);
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
        '10' => array('css' => 'success', 'name' => '待退款')
    );
//    file_put_contents(TG_DATA."order.log", var_export(array('sql'=>$sql,'list' => $list), true).PHP_EOL, FILE_APPEND);

    foreach ($list as $key => $value) {

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
        $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=8 and mobile<>'虚拟' {$cons} ");//待发货
        $status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=9 and mobile<>'虚拟' {$cons} ");//已取消
        $status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$cons} ");//已关闭
        $status17 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2 and dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and is_tuan=0 {$cons}");//单买单

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
        $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=8 and mobile<>'虚拟' {$cons}");//待发货
        $status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=9 and mobile<>'虚拟' {$cons}");//已取消
        $status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' {$cons} ");//已关闭
        $status17 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE $condition2  and uniacid='{$_W['uniacid']}' and is_tuan=0 {$cons}");//单买单
        $status18 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  status=8  and uniacid='{$_W['uniacid']}' and issued = 0 and selltype=5 {$cons}");//单买单
        $status19 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=8  and uniacid='{$_W['uniacid']}' and godluck = 1 and selltype=5 {$cons}");//单买单
        $status20 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=7  and uniacid='{$_W['uniacid']}' and godluck = 0 and selltype=5 {$cons}");//单买单
        $status21 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE status=3 and uniacid='{$_W['uniacid']}' and issued = 2 and selltype=5 {$cons}");//单买单

    }

}

if ($op == 'detail') {
    wl_load()->model('activity');
    wl_load()->model('goods');
    load()->model('mc');
    $id = intval($_GPC['id']);
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (empty($item)) {
        message("抱歉，订单不存在!", referer(), "error");
    }

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
    include wl_template('order/jxdp_detail');
    exit;
}
if ($op == 'confrimpay') {
    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    pdo_update('tg_order', array('status' => 1, 'pay_type' => 2, 'ptime' => TIMESTAMP), array('id' => $id));
    $oplogdata = serialize($item);
    oplog('admin', "后台确认付款", web_url('order/jxdp/confrimpay'), $oplogdata);
    message('确认订单付款操作成功！', referer(), 'success');
}
if ($op == 'confirmsend') {
    $id = $_GPC['id'];

    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (!empty($_GPC['expresssn'])) {
        $r = pdo_update('tg_order', array('status' => 2, 'express' => $_GPC['express'], 'expresssn' => $_GPC['expresssn'], 'delivery_time' => TIMESTAMP), array('id' => $id));
    } else {
        $r = pdo_update('tg_order', array('status' => 2, 'express' => $_GPC['express'], 'storeid' => $_GPC['storeid'], 'delivery_time' => TIMESTAMP), array('id' => $id));
    }
    if ($r) {
        /*更新可结算金额*/
        if (!empty($item['merchantid'])) {
            merchant_update_no_money($item['price'], $item['merchantid']);
        }
        /*记录操作*/
        $oplogdata = serialize($item);
        oplog('admin', "后台确认发货", web_url('order/jxdp/confirmsend'), $oplogdata);
        /*发货成功消息提醒*/
        $url = app_url('order/jxdp/detail', array('id' => $item['id']));
        if ($item['dispatchtype'] == 1) {
            $dispatch = pdo_fetch("select nickname,tel from " . tablename('tg_delivery_man') . " where id = '{$item['storeid']}' ");
            $nickname = $dispatch['nickname'];
            $tel = $dispatch['tel'];

            $title = "亲，您的商品已派送！订单编号：" . $item['orderno'] . "  派送员：" . $nickname;

            $message = '订单派送';
            $rem = '当派送员将货物送至时，请将确认收货的二维码给派送员扫码确认收货【代收请将二维码截图，并告知派送员代收】';
            if ($item['g_id'] > 0) {
                $good = pdo_fetch("SELECT gname FROM " . tablename('tg_goods') . " WHERE id = " . $item['g_id']);
                $gname = $good['gname'];
            } else {
                $collect = pdo_fetchall("SELECT goodsname FROM " . tablename('tg_collect') . " WHERE orderno = " . $item['orderno']);
                $gname = '';
                foreach ($collect as $val) {
                    $gname .= $val['goodsname'] . "\r";
                }
            }
//            die($dispatch['nickname'].$dispatch['tel']);
            dispatch_success($gname, $item['orderno'], $item['openid'], $nickname, $tel, app_url('order/jxdp/detail', array('id' => $item['id'])));
        } else {
            send_success($item['orderno'], $item['openid'], $_GPC['express'], $_GPC['expresssn'], $url);
        }
        message('发货操作成功！', web_url('order/jxdp/detail', array('id' => $item['id'], 'dispatchtype' => $item['dispatchtype'])), 'success');
    } else {
        message('发货操作失败！', web_url('order/jxdp/detail', array('id' => $item['id'], 'dispatchtype' => $item['dispatchtype'])), 'error');
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
        oplog('admin', "后台取消发货", web_url('order/jxdp/cancelsend'), $oplogdata);
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
        oplog('admin', "后台订单详情退款", web_url('order/jxdp/refund'), $oplogdata);
        /*退款成功消息提醒*/
        $url = app_url('order/jxdp/detail', array('id' => $item['id']));
        $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$item['g_id']}'");
        refund_success($item['orderno'], $goods['gname'], $item['openid'], $item['price'], time(), $url);

        die(json_encode(array('status' => 1)));
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

                    $expressOrder = trim(iconv('gb2312', 'utf-8', $result[$i][27]));

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
                            $url = app_url('order/jxdp/detail', array('id' => $order['id']));
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
    if (empty($_GPC['merchantid'])) {
        $condition .= " and  merchantid = {$_W['user']['merchant_id']} ";
    }
    $paras = array();
    $status = $_GPC['status'];
    $transid = $_GPC['transid'];
    $pay_type = $_GPC['pay_type'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];
    $merchantid = $_GPC['merchantid'];

    if (!empty($merchantid)) {
        $condition .= " AND  merchantid = '{$merchantid}' ";
        $condition2 .= " AND  merchantid = '{$merchantid}' ";
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
    if (!empty($dispatchtype) && $dispatchtype != 0 && $dispatchtype != -1) {
        $condition .= " AND  dispatchtype = '{$_SESSION['dispatchtype']}'";
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
        default:
            $str = '待支付订单' . time();
            break;
    }
    /* 输入到CSV文件 */
    $html = "\xEF\xBB\xBF";
    /* 输出表头 */
    $filter = array('aa' => '订单编号', 'll' => '团ID', 'bb' => '姓名', 'cc' => '电话', 'm2' => '运费', 'dd' => '总价(元)', 'd1' => '退款金额', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'pp' => '商品规格', 'oo' => '购买数量', 'mm' => '买家留言', 'h1' => '省', 'h2' => '市', 'h3' => '区', 'h4' => '详细地址', 'm6' => '地址类型', 'ii' => '微信订单号', 'jj' => '快递单号', 'kk' => '快递名称', 'm1' => '配送方式', 'mz' => '组团成功时间', 'dd1' => '派送员', 'dd2' => '签收方式', 'm3' => '核销员', 'm4' => '核销门店', 'm8' => '核销时间', 'm5' => '客选门店', 'm7' => '是否中奖', 'm9' => '卖家留言', 'm11' => '粉丝昵称');
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
        if ($v['dispatchtype'] == 3) {
            $disname = '自提';
            $v['address'] = '';
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
            $orders[$k]['ll'] = $v['tuan_id'];
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
            $orders[$k]['kk'] = $v['express'];
            $orders[$k]['m1'] = $disname;


            $orders[$k]['mz'] = date('Y-m-d H:i:s', $v['successtime']);
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
            }

            $orders[$k]['oo'] = $v['gnum'];
            $orders[$k]['pp'] = $gopname;
            $orders[$k]['m11'] = $mermber['nickname'];
            foreach ($filter as $key => $title) {
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
        } else {
            $col = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE  orderno=:orderno", array('orderno' => $v['orderno']));
            $orders[$k]['aa'] = $v['orderno'];
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
            $orders[$k]['kk'] = $v['express'];
            $orders[$k]['m1'] = $disname;
            $orders[$k]['m2'] = $v['freight'];
            $orders[$k]['m3'] = $saler['nickname'];
            $orders[$k]['m4'] = $realstore['storename'];
            $orders[$k]['m5'] = $com['storename'];
            $orders[$k]['m6'] = $addresstype;
            if (!empty($v['hexiaotime'])) {
                $orders[$k]['m8'] = date('Y-m-d H:i:s', $v['hexiaotime']);
            }
//            $orders[$k]['m8'] = date('Y-m-d H:i:s', $v['hexiaotime']);
            $orders[$k]['m9'] = $v['adminremark'];
            $orders[$k]['ll'] = $v['tuan_id'];
            $orders[$k]['mz'] = date('Y-m-d H:i:s', $v['successtime']);
            $orders[$k]['dd1'] = $delivery['nickname'];
            $orders[$k]['dd2'] = $dstatus;
            $orders[$k]['mm'] = $v['remark'];
            $orders[$k]['oo'] = '';
            $orders[$k]['pp'] = '';
            $orders[$k]['m11'] = $mermber['nickname'];
            foreach ($filter as $key => $title) {
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
            foreach ($col as $c => $cc) {
                $gname = $cc['goodsname'];
                $gopname = $cc['item'];
                $orders[$k]['aa'] = '';
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

                foreach ($filter as $key => $title) {
                    $html .= $orders[$k][$key] . "\t,";
                }
                $html .= "\n";
            }
        }
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
    $address = $_GPC['address'];
    $realname = $_GPC['realname'];
    $mobile = $_GPC['mobile'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $orderid));
    if (pdo_update('tg_order', array('address' => $address, 'addname' => $realname, 'mobile' => $mobile), array('id' => $orderid))) {
        $oplogdata = serialize($item);
        oplog('admin', "后台修改地址", web_url('order/jxdp/confrimpay'), $oplogdata);
        die(json_encode(array('errno' => 0)));
    } else {
        die(json_encode(array('errno' => 1)));
    }
}
if ($op == 'master_refund') {
    $orderno = $_GPC['orderno'];
    pdo_update('tg_order', array('status' => 10), array('orderno' => $orderno));
    pdo_update('tg_order', array('status' => 10), array('master_orderno' => $orderno));
    die(json_encode(array('errno' => 1)));
}
if ($op == "refund_output_log") {


    $where = array();
    $where['!=mobile'] = "'虚拟'";//排除虚拟订单
    if ($_GPC['orderType'] == 'fetch')
        $where['#is_hexiao#'] = '(1,2)';
    else
        $where['is_hexiao'] = 0;
    if (TG_MERCHANTID) $where['merchantid'] = $_SESSION['role_id'];
    if (!empty($_GPC['status'])) $where['status'] = $_GPC['status'];
    if (!empty($_GPC['pay_type'])) $where['pay_type'] = $_GPC['pay_type'];

    if (!empty($_GPC['times']) && !empty($_GPC['timetype'])) {
        $starttime = strtotime($_GPC['times']);
        $endtime = strtotime($_GPC['timee']);
        switch ($_GPC['timetype']) {
            case 1:
                $where['createtime>'] = $starttime;
                $where['createtime<'] = $endtime;
                break;
            case 2:
                $where['ptime>'] = $starttime;
                $where['ptime<'] = $endtime;
                break;
            case 3:
                $where['sendtime>'] = $starttime;
                $where['sendtime<'] = $endtime;
                break;
            default:
                break;
        }
    }
    if (!empty($_GPC['keyword'])) {
        if (!empty($_GPC['keywordtype'])) {
            switch ($_GPC['keywordtype']) {
                case 1:
                    $where['@orderno@'] = $_GPC['keyword'];
                    break;
                case 2:
                    $where['@transid@'] = $_GPC['keyword'];
                    break;
                case 3:
                    $where['@addname@'] = $_GPC['keyword'];
                    break;
                case 4:
                    $where['@mobile@'] = $_GPC['keyword'];
                    break;
                case 5:
                    $goods = model_goods::getNumGoods('id', array('@gname@' => $_GPC['keyword']), 'id desc', 0, 0, 0);
                    if ($goods[0][0]) $where['g_id'] = $goods[0][0]['id'];
                    break;
                case 6:
                    $merchant = pdo_fetch("select id from" . tablename('tg_merchant') . "where name like '%{$_GPC['keyword']}%' and uniacid={$_W['uniacid']}");
                    if ($merchant['id']) $where['merchantid'] = $merchant['id'];
                    break;
                case 7:
                    $where['@hexiaoma@'] = $_GPC['keyword'];
                    $where['#status#'] = '(2,4,6,7)';
                    break;
                case 8:
                    $asd = pdo_fetch("SELECT id FROM " . tablename('tg_store') . "WHERE uniacid = {$_W['uniacid']} and storename like '%{$_GPC['keyword']}%'");
                    $where['storeid'] = $asd['id'];
                    break;
                case 9:
                    if ($_GPC['keyword'] == '后台核销') {
                        $where['veropenid'] = 'houtai';
                    } else {
                        $asd = pdo_fetch("SELECT openid FROM " . tablename('tg_saler') . "WHERE uniacid = {$_W['uniacid']} and nickname like '%{$_GPC['keyword']}%'");
                        $where['veropenid'] = $asd['openid'];
                    }
                    break;
                default:
                    break;
            }
        } else {
            $condition .= " AND orderno = '无查询结果' ";
        }
    }
    $orderData = model_order::getNumOrder('*', $where, 'pay_type desc', 0, 0, 0);
    $orders = $orderData[0];
    switch ($_GPC['status']) {
        case 7:
            $str = '已退款订单' . time();
            break;
    }
    $html = "\xEF\xBB\xBF";
    $filter = array('aa' => '订单编号', 'bb' => '姓名', 'cc' => '电话', 'dd' => '总价(元)', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'hh' => '收货地址', 'ii' => '微信订单号', 'jj' => '快递单号', 'kk' => '快递名称', 'll' => '地址类型', 'mm' => '商品规格', 'nn' => '购买数量', 'oo' => '客户留言', 'pp' => '我的备注', 'qq' => '订单类型', 'rr' => '核销门店', 'ss' => '核销店员', 'tt' => '支付方式', 'uu' => '核销码');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    $html .= "\n";
    foreach ($orders as $k => $v) {
        if ($v['status'] == '7') $thisstatus = '已退款';
        $goods = model_goods::getSingleGoods($v['g_id'], 'gname');
        $time = date('Y-m-d H:i:s', $v['createtime']);
        if ($v['addresstype'] == 1) {
            $addresstype = '公司';
        } else {
            $addresstype = '家庭';
        }
        if (empty($v['optionname'])) $v['optionname'] = '不限';
        $orders[$k]['aa'] = $v['orderno'];
        $orders[$k]['bb'] = $v['addname'];
        $orders[$k]['cc'] = $v['mobile'];
        $orders[$k]['dd'] = $v['price'];
        $orders[$k]['ee'] = $thisstatus;
        $orders[$k]['ff'] = $time;
        $orders[$k]['gg'] = $goods['gname'];
        $orders[$k]['hh'] = $v['address'];
        $orders[$k]['ii'] = $v['transid'];
        $orders[$k]['jj'] = $v['expresssn'];
        $orders[$k]['kk'] = $v['express'];
        $orders[$k]['ll'] = $addresstype;
        $orders[$k]['mm'] = $v['optionname'];
        $orders[$k]['nn'] = $v['gnum'];
        $orders[$k]['oo'] = $v['remark'];
        $orders[$k]['pp'] = $v['adminremark'];
        $orders[$k]['qq'] = $_GPC['orderType'] == 'fetch' ? '自提订单' : '快递订单';
        $orders[$k]['tt'] = $v['pay_typeName'];
        $orders[$k]['uu'] = $v['hexiaoma'];
        $asd = pdo_fetch("SELECT storename FROM " . tablename('tg_store') . "WHERE id = '{$v['storeid']}' ");
        $orders[$k]['rr'] = $asd['storename'];
        if ($v['veropenid'] == 'houtai') {
            $orders[$k]['ss'] = '后台核销';
        } else {
            $asd = pdo_fetch("SELECT nickname FROM " . tablename('tg_saler') . "WHERE openid = '{$v['veropenid']}'");
            $orders[$k]['ss'] = $asd['nickname'];
        }

        foreach ($filter as $key => $title) {
            $html .= $orders[$k][$key] . "\t,";
        }
        $html .= "\n";
    }

    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename={$str}.csv");
    echo $html;
    exit();
}
include wl_template('order/jxdp');
exit();
