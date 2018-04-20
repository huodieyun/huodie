<?php
defined('IN_IA') or exit('Access Denied');
wl_load()->func('global');
wl_load()->func('print');
wl_load()->func('message');
wl_load()->classs('qrcode');
$ops = array('send_groupsuccess', 'passdate', 'print', 'refundgroup', 'sendgroup', 'summary', 'all', 'group_detail', 'autogroup', 'output','delay','ordercode');
$op_names = array('全团打印', '全团退款', '强制发货', '团购概况', '订单列表', '团详情', '后台核销', '导出','延期');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'order', 'group', $ops[$key], '订单', '团管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'all';
wl_load()->model('goods');
if ($op == 'sendgroup') {
    $groupnumber = intval($_GPC['groupnumber']);
    pdo_update('tg_group', array('sendtype' => 1), array('groupnumber' => $groupnumber));
    pdo_update('tg_order', array('status' => 8), array('tuan_id' => $groupnumber, 'status' => 1));
    pdo_update('tg_order', array('status' => 8), array('tuan_id' => $groupnumber, 'status' => 10));
    message('更改发货状态成功', referer(), 'success');
}
if($op == 'ordercode'){
    $url = 123;
    $groupnumber = $_GPC['groupnumber'];
    $qr = new creat_qrcode();
    $qrcode = $qr->createOrderQrcode($url,$groupnumber);
    die(json_encode($qrcode));
}

if ($op == 'delay') {
    $groupnumber = intval($_GPC['groupnumber']);
    $delaytime = strtotime($_GPC['delaytime']);
    $r =  pdo_fetchall("SELECT * FROM " . tablename('tg_group') . " WHERE groupnumber = '{$groupnumber}' ");
    $endtime = $r[0]['endtime'];
    if($endtime == $delaytime){
        die(json_encode(2));
    }
    $rs = pdo_update('tg_group', array('endtime' => $delaytime , 'successtime' => TIMESTAMP), array('groupnumber' => $groupnumber));
    die(json_encode($rs));
//    file_get_contents(app_url("home/task"));
//    message('更改到期时间成功1', referer(), 'success');
}
if ($op == 'passdate') {

    $groupnumber = intval($_GPC['groupnumber']);
    pdo_update('tg_group', array('endtime' => TIMESTAMP , 'successtime' => TIMESTAMP), array('groupnumber' => $groupnumber));
    file_get_contents(app_url("home/task"));
    message('更改到期时间成功', referer(), 'success');
}
if ($op == 'refundgroup') {

    $groupnumber = intval($_GPC['groupnumber']);
    //指定团的id
    $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' and status in (1,10) ");
    $success_num = 0;
    $fail_num = 0;

    foreach ($orders as $k => $value) {
        $res = refund($value['orderno'], 2);

        if ($res == 'success') {
            $success_num += 1;
        } else {
            $fail_num += 1;
        }
    }
    message('全团退款操作成功！成功' . $success_num . '人,失败' . $fail_num . '人', referer(), 'success');
}
if ($op == 'print') {
    $groupnumber = intval($_GPC['groupnumber']);
    alltuan_print($groupnumber,true);
    message('打印成功', referer(), 'success');
}
if ($op == 'summary') {
    $seven_orders = 0;
    $obligations = 0;
    $succ_list = 0;
    $undelivereds = 0;
    $incomes = 0;
    $yesterday_orders = 0;
    $yesterday_payorder = 0;
    $yesterday_obligation = 0;
    $yesterday_succ_list = 0;
    $stime = strtotime(date('Y-m-d')) - 6 * 86400;
    $etime = strtotime(date('Y-m-d')) + 86400;
    $ytime = strtotime(date('Y-m-d')) - 86400;
    if ($_W['user']['merchant_id'] > 0) {
        $condition .= " and goodsid in ( select id from cm_tg_goods where  merchantid = {$_W['user']['merchant_id']} )";
    }
    $seven_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_group') . " WHERE uniacid = {$_W['uniacid']}    and starttime >= :createtime AND starttime <= :endtime {$condition} ORDER BY starttime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    $obligations = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=1 and starttime >= :createtime AND starttime <= :endtime {$condition} ORDER BY starttime ASC", array(':createtime' => $stime, ':endtime' => $etime));

    $succ_list = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=2 and starttime >= :createtime AND starttime <= :endtime {$condition} ORDER BY starttime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    $undelivereds = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=3 and starttime >= :createtime AND starttime <= :endtime {$condition} ORDER BY starttime ASC", array(':createtime' => $stime, ':endtime' => $etime));
    //$seven = pdo_fetchall("select pay_price  from" . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus in(1,2,3,4,6)  AND starttime >= :createtime AND endtime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
    //foreach($seven as$key=>$value){
    //	$incomes += $value['pay_price'];
    //}

    $yesterday_orders = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'    and starttime >= :createtime AND starttime <= :endtime {$condition}  ORDER BY starttime ASC", array(':createtime' => $ytime, ':endtime' => $etime));
    $yesterday_payorder = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=1 and starttime >= :createtime AND starttime <= :endtime {$condition} ORDER BY starttime ASC", array(':createtime' => $ytime, ':endtime' => $etime));
    $yesterday_obligation = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=3 and starttime >= :createtime AND starttime <= :endtime {$condition} ORDER BY starttime ASC", array(':createtime' => $ytime, ':endtime' => $etime));
    $yesterday_succ_list = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=2 and starttime >= :createtime AND starttime <= :endtime {$condition} ORDER BY starttime ASC", array(':createtime' => $ytime, ':endtime' => $etime));

    $con = "uniacid = '{$_W['uniacid']}'  ";
    $starttime = empty($_GPC['time']['start']) ? strtotime(date('Y-m-d')) - 7 * 86400 : strtotime($_GPC['time']['start']);
    $endtime = empty($_GPC['time']['end']) ? strtotime(date('Y-m-d')) + 86400 : strtotime($_GPC['time']['end']) + 86400;
    $s = $starttime;
    $e = $endtime;
    $list = array();
    $j = 0;

    while ($e >= $s) {
        $listone = pdo_fetchall("SELECT id  FROM " . tablename('tg_group') . " WHERE $con   AND starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array(':createtime' => $e - 86400, ':endtime' => $e));
        $status1 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE $con and groupstatus=1   AND starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array(':createtime' => $e - 86400, ':endtime' => $e));
        $status4 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE $con and groupstatus=2   AND starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array(':createtime' => $e - 86400, ':endtime' => $e));
        $list[$j]['gnum'] = count($listone);
        $list[$j]['status4'] = $status4;
        $list[$j]['status1'] = $status1;
        $list[$j]['createtime'] = $e - 86400;
        $j++;
        $e = $e - 86400;
    }

    $day = $hit = $status4 = $status1 = array();
    if (!empty($list)) {
        foreach ($list as $row) {
            $day[] = date('m-d', $row['createtime']);
            $hit[] = intval($row['gnum']);
            $status4[] = intval($row['status4']);
            $status1[] = intval($row['status1']);
        }
    }

    for ($i = 0; $i = count($hit) < 2; $i++) {
        $day[] = date('m-d', $endtime);
        $hit[] = $day[$i] == date('m-d', $endtime) ? $hit[0] : '0';
    }
    include wl_template('order/tuansummary');
    exit;
}
if ($op == 'all') {
    //更新团状态
    $groupstatus = $_GPC['groupstatus'];
    $will_die = $_GPC['will_die'];
    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;
    /*搜索条件*/
    $allgoods = pdo_fetchall("select gname from" . tablename('tg_goods') . "where uniacid=:uniacid and isshow=:isshow", array(':uniacid' => $_W['uniacid'], ':isshow' => 1));
    $condition = "uniacid = {$_W['uniacid']}";
    $time = $_GPC['time'];
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']);
        $condition .= " AND  starttime >= {$starttime} AND  starttime <= {$endtime} ";

    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND groupnumber LIKE '%{$_GPC['keyword']}%'";
    }
    if (!empty($_GPC['goods'])) {
        $condition .= " AND goodsname LIKE '%{$_GPC['goods']}%'";
    }
    if (!empty($_GPC['goods2'])) {
        $condition .= " AND (goodsname LIKE '%{$_GPC['goods2']}%' or goodsid LIKE '%{$_GPC['goods2']}%') ";
    }
    if (!empty($groupstatus)) {
        $condition .= " AND groupstatus ='{$groupstatus}'";
    }
    if (!empty($will_die)) {
        $endhour = intval($_GPC['endhour']);
        $lacknumber = intval($_GPC['lacknumber']);
        if (!empty($_GPC['goods3'])) {
            $condition .= " AND (goodsname LIKE '%{$_GPC['goods3']}%' or goodsid LIKE '%{$_GPC['goods3']}%') ";
        }
        if ($endhour) {
            $nowtime = time();
            $endtime_tuan = $nowtime + 3600;
            $condition .= " AND endtime <= '{$endtime_tuan}' ";
        }
        if ($lacknumber) {
            $condition .= " AND lacknum = {$_GPC['lacknumber']} ";
        }
    }
//    if ($_W['user']['merchant_id'] > 0) {
//    $condition .= " and goodsid in ( select id from cm_tg_goods where  merchantid = {$_W['user']['merchant_id']} )";
    $condition .= " and merchantid = {$_W['user']['merchant_id']} ";
//    }
    /*搜索条件*/

    $alltuan = pdo_fetchall("select * from" . tablename('tg_group') . "where $condition  AND lacknum <>neednum order by id desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $nowtime = time();
    foreach ($alltuan as $key => $value) {
        $goods = goods_get_by_params(" id={$value['goodsid']} ");
        $alltuan[$key]['goods'] = $goods;
        $refund_orders = pdo_fetchcolumn("select COUNT(id) from " . tablename('tg_order') . " where tuan_id='{$value['groupnumber']}' and uniacid='{$_W['uniacid']}' and status=7");
        $send_orders = pdo_fetchcolumn("select COUNT(id) from " . tablename('tg_order') . " where tuan_id='{$value['groupnumber']}' and uniacid='{$_W['uniacid']}' and status in(2,3)");
        $all_orders = pdo_fetchcolumn("select COUNT(id) from " . tablename('tg_order') . " where tuan_id='{$value['groupnumber']}' and uniacid='{$_W['uniacid']}' and status in(1,2,3,6,7,8,10)");
        $alltuan[$key]['lasttime'] = $value['endtime'] - $nowtime;
        $alltuan[$key]['refundnum'] = intval($refund_orders);
        $alltuan[$key]['sendnum'] = intval($send_orders);
        $alltuan[$key]['allnum'] = intval($all_orders);

        $m = $value['merchantid'];
        if ($m == 0) {
            $alltuan[$key]['merchant_name'] = $_W['account']['name'];
        } else {
            $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
            $alltuan[$key]['merchant_name'] = $name['name'];
        }

    }
    $alltuan2 = pdo_fetchall("select * from " . tablename('tg_group') . " where $condition AND lacknum <> neednum order by id desc ");
    $total = count($alltuan2);
    $pager = pagination($total, $pindex, $psize);
    $shop = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = '{$_W['uniacid']}'");
    $all = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE $condition and uniacid='{$_W['uniacid']}' AND lacknum <>neednum");
    $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE $condition and uniacid='{$_W['uniacid']}' and groupstatus=2 AND lacknum <>neednum");
    $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE $condition and uniacid='{$_W['uniacid']}' and groupstatus=1 AND lacknum <>neednum");
    $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE $condition and uniacid='{$_W['uniacid']}' and groupstatus=3 AND lacknum <>neednum");

}elseif($op=='send_groupsuccess'){
    $groupnumber=$_GPC['groupnumber'];
    /*团成功通知*/
    $url = app_url('order/group', array('tuan_id' => $groupnumber));
    group_success($groupnumber, $url);
    die(json_encode(array('status'=>1)));
} elseif ($op == 'group_detail') {
    $groupnumber = intval($_GPC['groupnumber']);
    //指定团的id
    $thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
    $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' ORDER BY id asc");
    $goods = goods_get_by_params(" id='{$thistuan['goodsid']}' ");
} elseif ($op == 'delete') {
    $groupnumber = intval($_GPC['id']);
    //要删除的商品的id
    if (empty($groupnumber)) {
        message('未找到指定的团');
    }
    $result1 = pdo_delete('tg_group', array('groupnumber' => $groupnumber, 'uniacid' => $_W['uniacid']));
    $result = pdo_delete('tg_order', array('tuan_id' => $groupnumber, 'uniacid' => $_W['uniacid']));
    if (intval($result1) == 1) {
        message('删除团成功.', referer(), 'success');
    } else {
        message('删除团失败.');
    }
} elseif ($op == 'autogroup') {

    set_time_limit(0);
    $will_die = $_GPC['will_die'];
    $filename = TG_WEB . "resource/nickname.text";
    //$url=TG_WEB.'resource/images/head_imgs';
    $url = '../addons/lexiangpingou/web/resource/images/head_imgs';
    $groupnumber = intval($_GPC['groupnumber']);
    //指定团的id
    $thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
    if ($thistuan['groupstatus'] == 2){
        echo "<script>alert('该团已成功！');location.href='" . web_url('order/will_die', array('will_die' => 'will_die', 'op' => 'all', 'groupstatus' => '3', 'page' => $_GPC['page'])) . "';</script>";
        exit;
    }
    $orders2 = pdo_fetch("SELECT ptime,createtime FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' order by ptime desc  limit 1");
    $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$thistuan['goodsid']}'");

    $endtime = $orders2['ptime'];
    $nownum = intval($_GPC['num']);
    //虚拟订单
    $t = time();
    $init = $orders2['createtime'];
    $num = array();
    $lacknum = $thistuan['lacknum'];
    if ($thistuan['lacknum'] > $nownum) {
        $lacknum = $nownum;
    }
    $lack = $thistuan['lacknum'];
    $head_imgs_array = get_head_img($url, $lacknum);
    $nickname_array = get_nickname($filename, $lacknum);

    //$time_array = get_randtime($init,$t,$lacknum);
    $nn = 1;
    for ($i = 0; $i < $lacknum; $i++) {
        $lack = $lack - 1;
        $nn = mt_rand($nn + 1, $nn + 5);
        $data = array(
            'uniacid' => $_W['uniacid'],
            'gnum' => 1,
            'openid' => $head_imgs_array[$i],
            'ptime' => '',
            'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
            'price' => 0,
            'status' => 3,
            'addressid' => 0,
            'addname' => $nickname_array[$i]['nickname'],
            'mobile' => '虚拟',
            'address' => '虚拟',
            'g_id' => $thistuan['goodsid'],
            'tuan_id' => $thistuan['groupnumber'],
            'is_tuan' => 1,
            'tuan_first' => 0,
            'starttime' => TIMESTAMP,
            'createtime' => TIMESTAMP + $nn
        );
        pdo_insert('tg_order', $data);
    }
    //校验延时
    $sleeptime = rand(0.01, 0.2);
    sleep($sleeptime);
    pdo_update('tg_group', array('lacknum' => $lack, 'nownum' => $thistuan['nownum'] + $lacknum), array('groupnumber' => $thistuan['groupnumber']));
    //校验延时
    $sleeptime = rand(0.01, 0.2);
    sleep($sleeptime);
    $nowthistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
    if ($nowthistuan['lacknum'] == 0) {
        pdo_update('tg_group', array('groupstatus' => 2, 'successtime' => TIMESTAMP), array('groupnumber' => $nowthistuan['groupnumber']));
        //删除分享图片
        unlink("../addons/lexiangpingou/lxapi/share_tuan/".$groupnumber.'.jpeg');
        //$orders3 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟' ");

        pdo_update('tg_order', array('status' => 8,'successtime' => TIMESTAMP), array('tuan_id' => $groupnumber, 'status' => 1));


        /*团成功通知*/
        $url = app_url('order/group', array('tuan_id' => $groupnumber));
        group_success($groupnumber, $url);
        $all = pdo_fetchall("select * from " . tablename('tg_order') . " where tuan_id='{$groupnumber}' and status in (3,8,1,6,7,10)");
        $g = pdo_fetch("select is_amount,selltype from " . tablename('tg_goods') . " where id = '{$nowthistuan['goodsid']}'");
        if ($g['is_amount'] == 1 && ($g['selltype'] == 4 || $g['selltype'] == 7)) {
            $auto_orders = pdo_fetchcolumn("select SUM(gnum) from " . tablename('tg_order') . " where tuan_id=:tuan_id and uniacid=:uniacid and status in (3,8,1,6,7,10)", array(':tuan_id' => $groupnumber, ':uniacid' => $_W['uniacid']));

            $allnum = $auto_orders;
        } else {
            $allnum = count($all);
        }

        foreach ($all as $row) {
            if ($row['mobile'] != '虚拟') {
                tuan_print($row['id']);
            }


            $goodsInfo = goods_get_by_params(" id = {$row['g_id']}");
            //如果是阶梯团，插入退款记录
            if ($goodsInfo['selltype'] == 4) {
                $param_level = unserialize($nowthistuan['group_level']);
                for ($i = 0; $i < count($param_level); $i++) {
                    for ($j = 0; $j < count($param_level) - $i - 1; $j++) {
                        if ($param_level[$j]['groupnum'] < $param_level[$j + 1]['groupnum']) {
                            $temp = $param_level[$j];
                            $param_level[$j] = $param_level[$j + 1];
                            $param_level[$j + 1] = $temp;
                        }
                    }
                }

                for ($i = 0; $i < count($param_level); $i++) {
                    //$bdata=array('from'=>$param_level[$i]['groupnum'],'log'=>$allnum,'orderno'=>$i);
                    //pdo_insert('tg_log', $bdata);
                    if ($param_level[$i]['groupnum'] > $allnum && $param_level[$i + 1]['groupnum'] <= $allnum) {
                        $tempprice = $param_level[$i + 1]['groupprice'];
                    }
                    if ($param_level[$i]['groupnum'] == $allnum) {
                        $tempprice = $param_level[$i]['groupprice'];
                    }
                }
                //$bdata=array('from'=>$row['gnum'],'log'=>$row['gnum'],'orderno'=>'阶梯团测试');
                //	pdo_insert('tg_log', $bdata);
                $refundprice = (round($row['price'], 2) - round($tempprice, 2) * $row['gnum'] - round($row['freight'], 2));
                //优惠券折扣
                if($row['couponid']){
                    $coupon = pdo_fetch("SELECT cash FROM cm_tg_coupon WHERE id={$row['couponid']}");
                    $refundprice =  round($coupon['cash'],2)+(round($row['price'], 2) - round($tempprice, 2) * $row['gnum'] - round($row['freight'], 2));
                }
                //插入退款记录
                if ($refundprice > 0 && $row['mobile'] <> '虚拟') {
                    $data1 = array('orderno' => $row['orderno'], 'status' => 1, 'refundprice' => $refundprice);
                    pdo_insert('tg_order_level_refund', $data1);
                }

            }
            if ($goodsInfo['gnum'] >= $row['gnum'] && $row['mobile'] <> '虚拟') {
                goods_update_by_params(array('gnum' => $goodsInfo['gnum'] - $row['gnum'], 'salenum' => $goodsInfo['salenum'] + $row['gnum']), array('id' => $row['g_id']));
            } elseif (!empty($goodsInfo['gnum']) && $row['mobile'] <> '虚拟') {
                goods_update_by_params(array('gnum' => $goodsInfo['gnum'] - $row['gnum'], 'salenum' => $goodsInfo['salenum'] + $row['gnum']), array('id' => $row['g_id']));
            }

            if (!empty($row['optionname'])) {
                $stock = pdo_fetch("SELECT * FROM " . tablename('tg_goods_option') . " WHERE goodsid=:goodsid AND title=:title", array(':goodsid' => $row['g_id'], ':title' => $row['optionname']));
                pdo_update('tg_goods_option', array('stock' => $stock['stock'] - $row['gnum']), array('goodsid' => $row['g_id'], 'title' => $row['optionname']));
            }
            $v_goods = goods_get_by_params(" id = {$row['g_id']}");
            if($v_goods['has_store_stock'] == 1){
                if (!empty($row['optionname'])) {
                    $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $row['g_id'], ':storeid' => $row['comadd'],':optionid'=>$row['optionid'],':uniacid'=>$row['uniacid']));
                    pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $row['gnum']),array('id'=>$store_stock['id']));
                }else{
                    $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and uniacid=:uniacid", array(':goodsid' => $row['g_id'], ':storeid' => $row['comadd'],':uniacid'=>$row['uniacid']));
                    pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $row['gnum']),array('id'=>$store_stock['id']));
                }


            }
        }
    }
    $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
    $thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
    if ($nowthistuan['lacknum'] == 0) {
        echo "<script>alert('组团成功');location.href='" . web_url('order/will_die', array('will_die' => 'will_die', 'op' => 'all', 'groupstatus' => '3', 'page' => $_GPC['page'])) . "';</script>";


    } else {
        echo "<script>alert('本次新增{$lacknum}人参团');location.href='" . web_url('order/will_die', array('will_die' => 'will_die', 'op' => 'all', 'groupstatus' => '3', 'page' => $_GPC['page'])) . "';</script>";

    }
    exit;
} elseif ($op == 'output') {
    $groupstatus = $_GPC['groupstatus'];
    if ($groupstatus == 1) {
        $str = '团购失败订单_' . time();
    }
    if ($groupstatus == 2) {
        $str = '团购成功订单_' . time();
    }
    if ($groupstatus == 3) {
        $str = '组团中订单_' . time();
    }
    if (empty($groupstatus)) {
        $str = '所有团订单_' . time();
    }
    $con = "uniacid = {$_W['uniacid']}";
    if (!empty($_GPC['goods'])) {
        $con .= " AND goodsname LIKE '%{$_GPC['goods']}%'";
    }
    if (!empty($_GPC['goods2'])) {
        $con .= " AND (goodsname LIKE '%{$_GPC['goods2']}%' or goodsid LIKE '%{$_GPC['goods2']}%') ";
    }
    if (!empty($groupstatus)) {
        $con .= " and groupstatus='{$groupstatus}' ";
    }
    if (!empty($_GPC['keyword'])) {
        $con .= " AND groupnumber LIKE '%{$_GPC['keyword']}%'";
    }
    if (!empty($_GPC['starttime'])) {
        $con .= " and starttime >='{$_GPC['starttime']}' ";
    }
    if (!empty($_GPC['endtime'])) {
        $con .= " and starttime <='{$_GPC['endtime']}' ";
    }
    $groups = pdo_fetchall("select * from" . tablename('tg_group') . "where $con ");

    $html = "\xEF\xBB\xBF";
    $filter = array('ll' => '团编号', 'mm' => '团状态', 'aa' => '订单编号','sj' => '所属商家', 'bb' => '姓名', 'cc' => '电话', 'dd' => '总价(元)', 'd1' => '退款金额', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'oo' => '购买数量', 'm10' => '买家留言', 'hh' => '收货地址', 'ii' => '微信订单号', 'jj' => '快递单号', 'kk' => '快递名称', 'm1' => '配送方式', 'mz' => '组团成功时间', 'm3' => '核销员', 'm4' => '核销门店', 'm8' => '核销时间', 'm5' => '客选门店', 'm7' => '是否中奖', 'm9' => '卖家留言', 'm12' => '商户订单号');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    //					$html .= "\n";
    foreach ($groups as $k => $v) {
        $html .= "\n";
        $orders = pdo_fetchall("select * from" . tablename('tg_order') . "where tuan_id='{$v['groupnumber']}' and uniacid='{$_W['uniacid']}' and mobile<>'虚拟'");
        if ($v['groupstatus'] == 1) {
            $tuanstatus = '团购失败';
        }
        if ($v['groupstatus'] == 2) {
            $tuanstatus = '团购成功';
        }
        if ($v['groupstatus'] == 3) {
            $tuanstatus = '组团中';
        }
        foreach ($orders as $kk => $vv) {
            if ($vv['status'] == 0) {
                $thistatus = '待付款';
            }
            if ($vv['status'] == 1) {
                $thistatus = '已支付';
            }
            if ($vv['status'] == 2) {
                $thistatus = '待收货';
            }
            if ($vv['status'] == 3) {
                $thistatus = '已签收';
            }
            if ($vv['status'] == 4) {
                $thistatus = '已退款';
            }
            if ($vv['status'] == 5) {
                $thistatus = '强退款';
            }
            if ($vv['status'] == 6) {
                $thistatus = '部分退款';
            }
            if ($vv['status'] == 7) {
                $thistatus = '已退款';
            }
            if ($vv['status'] == 8) {
                $thistatus = '待发货';
            }
            if ($vv['status'] == '9') {
                $thistatus = '已取消';
            }
            if ($vv['status'] == '10') {
                $thistatus = '待退款';
            }
            if ($vv['status'] == '') {
                $thistatus = '全部订单';
            }
            if ($vv['dispatchtype'] == 0) {
                $disname = '无';
            }
            if ($vv['dispatchtype'] == 1) {
                $disname = '送货上门';
            }
            if ($vv['dispatchtype'] == 2) {
                $disname = '快递';
            }
            if ($vv['dispatchtype'] == 3) {
                $disname = '自提';
                $vv['address'] = '';
            }
            if ($vv['addresstype'] == 1) {
                $addresstype = '公司';
            } else {
                $addresstype = '家庭';
            }
            $nickname = '';
            if (!empty($vv['veropenid'])) {
                $saler = pdo_fetch("select nickname from" . tablename('tg_member') . " where from_user ='" . $vv['veropenid'] . "'");
                $nickname = $saler['nickname'];
            }
            $realstorename = '';
            if (!empty($v['checkstore'])) {
                $realstore = pdo_fetch("select storename from" . tablename('tg_store') . " where id ='" . $vv['checkstore'] . "'");
                $realstorename = $realstore['storename'];
            }
            $storename = '';
            if (!empty($vv['comadd']) && $vv['dispatchtype'] == 3) {
                $com = pdo_fetch("select storename from" . tablename('tg_store') . " where id ='" . $vv['comadd'] . "'");
                $storename = $com['storename'];
            }
            if (!empty($vv['hexiaotime'])) {
                $hexiaotime = date('Y-m-d H:i:s', $vv['hexiaotime']);

            }
            $rfee = pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id AND status=1 ", array(':id' => $v['transid']));
            $resultfee = 0;
            if (!empty($v['transid'])) {
                foreach ($rfee as $rf => $rforder) {
                    $resultfee += $rforder['refundfee'];
                }
            }
            $core = pdo_fetch('SELECT * FROM ' . tablename('core_paylog') . ' WHERE tid=:tid AND uniacid=:uniacid', array(':tid' => $vv['orderno'], ':uniacid' => $_W['uniacid']));
            $gopname = $vv['optionname'];
            $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$vv['g_id']}' and uniacid='{$_W['uniacid']}'");
            $time = date('Y-m-d H:i:s', $vv['createtime']);
            $orders[$kk]['ll'] = $v['groupnumber'];
            $orders[$kk]['mm'] = $tuanstatus;
            $orders[$kk]['aa'] = $vv['orderno'];
            $m = $vv['merchantid'];
            if ($m == 0) {
                $orders[$kk]['merchant_name'] = $_W['account']['name'];
            } else {
                $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                $orders[$kk]['merchant_name'] = $name['name'];
            }
            $orders[$kk]['sj'] = $orders[$kk]['merchant_name'];
            $orders[$kk]['bb'] = $vv['addname'];
            $orders[$kk]['cc'] = $vv['mobile'];
            $orders[$kk]['dd'] = $vv['price'];
            $orders[$kk]['d1'] = $resultfee;
            $orders[$kk]['ee'] = $thistatus;
            $orders[$kk]['ff'] = $time;
            $orders[$kk]['gg'] = $goods['gname'];
            $orders[$kk]['hh'] = $vv['address'];
            $orders[$kk]['ii'] = $vv['transid'];
            $orders[$kk]['jj'] = $vv['expresssn'];
            $orders[$kk]['kk'] = $vv['express'];
            $orders[$kk]['m1'] = $disname;
            $orders[$kk]['m10'] = $vv['remark'];
            $orders[$kk]['m12'] = $core['uniontid'];
            $orders[$kk]['mz'] = date('Y-m-d H:i:s', $vv['successtime']);

            $orders[$kk]['m3'] = $nickname;
            $orders[$kk]['m4'] = $realstorename;
            $orders[$kk]['m5'] = $storename;
            $orders[$kk]['m6'] = $addresstype;
            $orders[$kk]['m7'] = '';
            $orders[$kk]['m9'] = $vv['adminremark'];
            $orders[$kk]['m8'] = $hexiaotime;
            $orders[$kk]['oo'] = $vv['gnum'];
            $orders[$kk]['pp'] = $gopname;
            foreach ($filter as $key => $title) {
                $html .= $orders[$kk][$key] . "\t,";
            }
            $html .= "\n";
        }

    }
    /* 输出CSV文件 */
    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename={$str}.csv");
    echo $html;
    exit();
}
include wl_template('order/group');
exit();