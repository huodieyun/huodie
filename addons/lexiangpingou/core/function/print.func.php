<?php
function tuan_print($orderID,$is_auto=false)
{
    global $_W;
    require_once IA_ROOT . '/addons/lexiangpingou/wprint.class.php';
    $row = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . ' WHERE uniacid = :aid AND id = :id', array(':aid' => $_W['uniacid'], ':id' => $orderID));
    $tuan = pdo_fetch("SELECT * FROM cm_tg_group WHERE groupnumber={$row['tuan_id']}");
    if (empty($row["print_id"]) || $row["print_id"] == 0) {
        $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 and merchant_id = :merchant_id ', array(':aid' => $_W['uniacid'] , ':merchant_id' => $row['merchantid']));
    } else {
        $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 AND id = :id', array(':aid' => $_W['uniacid'], ":id" => $row["print_id"]));
    }
    $col = pdo_fetch("select * from " .tablename('tg_order_collect') ." where orderno = '{$row['orderno']}' and uniacid = '{$row['uniacid']}' and openid = '{$row['openid']}' ");
//    internal_log('print' , $col);
//    if ($col) {
//        $dat['print_times'] = $col['print_times'] + 1;
//        $dat['print_time'] = TIMESTAMP;
//        pdo_update('tg_order_collect' , $dat , array('id' => $col['id']));
//    } else {
//        $dat['uniacid'] = $row['uniacid'];
//        $dat['openid'] = $row['openid'];
//        $dat['orderno'] = $row['orderno'];
//        $dat['print_times'] = 1;
//        $dat['print_time'] = TIMESTAMP;
//        pdo_insert('tg_order_collect' , $dat);
//    }

    if ($row['g_id'] > 0) {
//        //判断是否执行打印
//        //获取所有打印机
//        if (empty($row["print_id"]) || $row["print_id"] == 0) {
//            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 and merchant_id = :merchant_id ', array(':aid' => $_W['uniacid'] , ':merchant_id' => $row['merchantid']));
//        } else {
//            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 AND id = :id', array(':aid' => $_W['uniacid'], ":id" => $row["print_id"]));
//        }
        if (count($prints) > 0&&$row['mobile']<>'虚拟') {
            //遍历所有查出来的打印机
            foreach ($prints as $li) {
                if (!empty($li['print_no']) && !empty($li['key'])) {
                    $wprint = new wprint();
                    if ($li['mode'] == 1) {
                        $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE uniacid = :aid AND id = :id', array(':aid' => $_W['uniacid'], ':id' => $row['g_id']));
                        if ($row['dispatchtype'] == 0) {
                            $btype = "无";
                        }
                        if ($row['dispatchtype'] == 2) {
                            $btype = "快递";
                        }
                        if ($row['dispatchtype'] == 3) {
                            $btype = "自提";
                            if($li['since_print'] == 0 && !$is_auto){
                                return;
                            }
                        }
                        if ($row['dispatchtype'] == 1) {
                            $btype = "送货上门";
                        }

                        if ($row['pay_type'] == 2) {
                            $bt = "微信支付";
                            $tt = "实付金额";
                        }
                        if ($row['pay_type'] == 3) {
                            $bt = "货到付款";
                            $tt = "待付金额";
                        }
                        if ($col) {
                            $dat['print_times'] = $col['print_times'] + 1;
                            $dat['print_time'] = TIMESTAMP;
                            pdo_update('tg_order_collect' , $dat , array('id' => $col['id']));
                        } else {
                            $dat['uniacid'] = $row['uniacid'];
                            $dat['openid'] = $row['openid'];
                            $dat['orderno'] = $row['orderno'];
                            $dat['print_times'] = 1;
                            $dat['print_time'] = TIMESTAMP;
                            pdo_insert('tg_order_collect' , $dat);
                        }
                        $cpay = $row['discount_fee'] + $row['first_fee'];
                        $ptime = date('Y-m-d H:i', $row['ptime']);
                        $orderinfo = "";
                        $orderinfo .= "<CB>组团成功</CB><BR>";
                        $orderinfo .= "团ID：{$row['tuan_id']}<BR>";
                        $orderinfo .= "第 <B> {$dat['print_times']} </B> 次打印<BR>";

                        $orderinfo .= "配送类型：{$btype}<BR>";
                        if ($row['dispatchtype'] == 2) {
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_delivery_template') . ' WHERE id = :id', array(':id' => $row['comadd']));
                            $orderinfo .= "快递公司：{$com['name']}<BR>";
                        }
                        $createtime = date('Y-m-d H:i:s', $row['createtime']);
                        $orderinfo .= "下单时间：{$createtime}<BR>";
                        $orderinfo .= "支付方式：{$bt}<BR>";

                        $dates = date('Y-m-d H:i:s', $row['ptime']);
                        $orderinfo .= "支付时间：{$dates}<BR>";

                        if ($row['dispatchtype'] == 3) {
                            $dates = date('Y-m-d', $row['self_time']);
                            $orderinfo .= "自提日期：{$dates}<BR>";
                        }


                        $bdate = $row['senddate'];
                        $btime = $row['sendtime'];
                        if (!empty($btime) && $row['dispatchtype'] == 1) {
                            $orderinfo .= "送货时间：{$bdate} {$row['sendtime']}<BR>";
                        }

                        if ($row['dispatchtype'] == 3) {
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_store') . ' WHERE id = :id', array(':id' => $row['comadd']));
                            $orderinfo .= "自提地点：{$com['storename']}<BR>";
                        }
                        $orderinfo .= "订单号：{$row['orderno']}<BR>";
                        $orderInfo .= "商品信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "商品名称：{$goodsInfo['gname']}<BR>";
                        $orderinfo .= "购买数量：{$row['gnum']}<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        if (!empty($row['optionname'])) {
                            $orderInfo .= "规格：<BR>";
                            $orderinfo .= '------------------------------<BR>';
                            $orderinfo .= "{$row['optionname']}<BR>";
                        }

                        $orderinfo .= "订单金额：￥{$row['goodsprice']}<BR>优惠金额：￥{$cpay}<BR>{$tt}：￥{$row['price']}<BR>";
                        if($tuan['selltype'] == 7){
                            $bprice = pdo_fetch("SELECT * FROM cm_tg_order WHERE master_orderno={$row['orderno']}");
                            $orderinfo .= "补款金额：￥{$bprice['price']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';

                        $orderinfo .= "用户信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "用户名：{$row['addname']}<BR>";
                        $orderinfo .= "手机号：{$row['mobile']}<BR>";
                        if ($ordernew['dispatchtype'] != 3) {
                            $orderinfo .= "地址：{$row['address']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "备注信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "{$row['remark']}<BR>";
                        if (empty($row["print_id"])) {
                            if (strpos($row['address'], $li['name']) !== false || $li['name'] == '全国') {
                                $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                            }
                        } else {
                            $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                        }


                    }
                    if (!is_error($status)) {
                        $i++;
                        $data = array(
                            'uniacid' => $_W['uniacid'],
                            'sid' => $sid,
                            'pid' => $li['id'],
                            'oid' => $orderID, //订单id
                            'status' => 1,
                            'foid' => $status,
                            'addtime' => TIMESTAMP
                        );
                        pdo_insert('tg_order_print', $data);
                    }
                }
            }
        }

    } else {

        $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='" . $_W['uniacid'] . "'   AND orderno='" . $row['orderno'] . "'");
        $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='" . $_W['uniacid'] . "' AND openid = '" . $_W['openid'] . "'");
        $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='" . $_W['uniacid'] . "' AND uid = '" . $fans['uid'] . "'");
        $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];

        $btype = "";
        if ($row['dispatchtype'] == 0) {
            $btype = "无";
        }
        if ($row['dispatchtype'] == 2) {
            $btype = "快递";
        }
        if ($row['dispatchtype'] == 3) {
            $btype = "自提";

        }
        if ($row['dispatchtype'] == 1) {
            $btype = "送货上门";
        }

        $bdate = $row['senddate'];
        $btime = $row['sendtime'];
        foreach ($favoriteqqq as $key => $orderss) {
            $gs = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . "where uniacid='" . $_W['uniacid'] . "' AND id='" . $orderss['sid'] . "' ");
            $orderinfos .= "{$gs['gname']}X{$orderss['num']}<BR>";
            $orderinfos .= "{$orderss['item']}<BR>";
            $newnum = intval($gs['gnum']) - intval($orderss['num']);
            //pdo_update('tg_goods', array('gnum' => $newnum), array('id' => $gs['id']));
        }
        $tt = "实付金额";
        $orno = substr($row['orderno'], -10);
        if ($row['pay_type'] == 2) {
            $bt = "微信支付";
        }
        if ($row['pay_type'] == 3) {
            $bt = "货到付款";
            $tt = "待付金额";
        }
        ///////
//        //获取所有打印机
//        if (empty($row["print_id"]) || $row["print_id"] == 0) {
//            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 and merchant_id = :merchant_id ', array(':aid' => $_W['uniacid'] , ':merchant_id' => $row['merchantid']));
//        } else {
//            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 AND id = :id', array(':aid' => $_W['uniacid'], ":id" => $row["print_id"]));
//        }
        if (!empty($prints)) {
            // require_once IA_ROOT . '/addons/feng_fightgroups/wprint.class.php';
            //include_once 'wprint.class.php';
            //遍历所有打印机
            foreach ($prints as $li) {
                if (!empty($li['print_no']) && !empty($li['key'])) {
                    $wprint = new wprint();
                    if ($li['mode'] == 1) {
                        if ($row['dispatchtype'] == 3) {
                            $btype = "自提";
                            if($li['since_print'] == 0  && !$is_auto){
                                return;
                            }
                        }
                        if ($col) {
                            $dat['print_times'] = $col['print_times'] + 1;
                            $dat['print_time'] = TIMESTAMP;
                            pdo_update('tg_order_collect' , $dat , array('id' => $col['id']));
                        } else {
                            $dat['uniacid'] = $row['uniacid'];
                            $dat['openid'] = $row['openid'];
                            $dat['orderno'] = $row['orderno'];
                            $dat['print_times'] = 1;
                            $dat['print_time'] = TIMESTAMP;
                            pdo_insert('tg_order_collect' , $dat);
                        }

                        $orderinfo = "";
                        $orderinfo .= "<CB>送货清单</CB><BR>";
                        $orderinfo .= "订单编号：{$orno}<BR>";
                        $orderinfo .= "第 <B> {$dat['print_times']} </B> 次打印<BR>";
                        $orderinfo .= "配送类型：{$btype}<BR>";
                        if ($row['dispatchtype'] == 2) {
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_delivery_template') . ' WHERE id = :id', array(':id' => $row['comadd']));
                            $orderinfo .= "快递公司：{$com['name']}<BR>";
                        }
                        $createtime = date('Y-m-d H:i:s', $row['createtime']);
                        $orderinfo .= "下单时间：{$createtime}<BR>";
                        $dates = date('Y-m-d H:i:s', $row['ptime']);
                        $orderinfo .= "支付时间：{$dates}<BR>";
                        if (!empty($btime) && $row['dispatchtype'] == 1) {
                            $orderinfo .= "送货时间：{$bdate} {$row['sendtime']}<BR>";
                        }
                        if ($row['dispatchtype'] == 3) {
                            $dates = date('Y-m-d', $row['self_time']);
                            $orderinfo .= "自提日期：{$dates}<BR>";
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_store') . ' WHERE id = :id', array(':id' => $row['comadd']));
                            $orderinfo .= "自提地点：{$com['storename']}<BR>";
                        }
                        $orderinfo .= "支付方式：{$bt}<BR>";
                        $orderinfo .= "订单号：{$row['orderno']}<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= '商品信息                   <BR>';
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= $orderinfos;
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "订单金额：￥{$row['goodsprice']}<BR>优惠金额：￥{$row['discount_fee']}<BR>{$tt}：￥{$row['pay_price']}<BR>";
                        if($tuan['selltype'] == 7){
                            $bprice = pdo_fetch("SELECT * FROM cm_tg_order WHERE master_orderno={$row['orderno']}");
                            $orderinfo .= "补款金额：￥{$bprice['price']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';

                        $orderinfo .= "用户信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "用户名：{$row['addname']}<BR>";
                        $orderinfo .= "手机号：{$row['mobile']}<BR>";
                        if ($row['dispatchtype'] != 3) {
                            $orderinfo .= "地址：{$row['address']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "备注信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "{$row['remark']}<BR>";
                        if (empty($row["print_id"])) {
                            if (strpos($row['address'], $li['name']) !== false || $li['name'] == '全国') {
                                $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                            }
                        } else {
                            $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                        }

                    }
                    if (!is_error($status)) {
                        $i++;
                        $data = array(
                            'uniacid' => $_W['uniacid'],
                            'sid' => $sid,
                            'pid' => $li['id'],
                            'oid' => $orderID, //订单id
                            'status' => 1,
                            'foid' => $status,
                            'addtime' => TIMESTAMP
                        );
                        pdo_insert('tg_order_print', $data);
                    }
                }
            }
        }
    }
}

function hexiao_print($orderID)
{
    global $_W;

    require_once IA_ROOT . '/addons/lexiangpingou/wprint.class.php';
    $row = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . ' WHERE uniacid = :aid AND id = :id', array(':aid' => $_W['uniacid'], ':id' => $orderID));
    $tuan = pdo_fetch("SELECT * FROM cm_tg_group WHERE groupnumber={$row['tuan_id']}");

    if ($row['g_id'] > 0) {

        //获取所有打印机
        if (empty($row["print_id"]) || $row["print_id"] == 0) {
            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 and merchant_id = :merchant_id ', array(':aid' => $_W['uniacid'] , ':merchant_id' => $row['merchantid']));
        } else {
            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 AND id = :id', array(':aid' => $_W['uniacid'], ":id" => $row["print_id"]));
        }
        if (!empty($prints)) {

            //遍历所有打印机
            foreach ($prints as $li) {
                if (!empty($li['print_no']) && !empty($li['key'])) {
                    $wprint = new wprint();
                    if ($li['mode'] == 1) {
                        

                        $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE uniacid = :aid AND id = :id', array(':aid' => $_W['uniacid'], ':id' => $row['g_id']));
                        if ($row['dispatchtype'] == 0) {
                            $btype = "无";
                        }
                        if ($row['dispatchtype'] == 2) {
                            $btype = "快递";
                        }
                        if ($row['dispatchtype'] == 3) {
                            $btype = "自提";
                        }
                        if ($row['dispatchtype'] == 1) {
                            $btype = "送货上门";
                        }

                        if ($row['pay_type'] == 2) {
                            $bt = "微信支付";
                            $tt = "实付金额";
                        }
                        if ($row['pay_type'] == 3) {
                            $bt = "货到付款";
                            $tt = "待付金额";
                        }
                        $cpay = $row['discount_fee'] + $row['first_fee'];
                        $ptime = date('Y-m-d H:i', $row['ptime']);
                        $orderinfo = "";
                        $orderinfo .= "<CB>核销成功</CB><BR>";
                        $orderinfo .= "团ID：{$row['tuan_id']}<BR>";
                        $orderinfo .= "配送类型：{$btype}<BR>";
                        if ($row['dispatchtype'] == 2) {
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_delivery_template') . ' WHERE id = :id', array(':id' => $row['comadd']));
                            $orderinfo .= "快递公司：{$com['name']}<BR>";
                        }
                        $orderinfo .= "支付方式：{$bt}<BR>";
                        $orderinfo .= "付款时间：{$ptime}<BR>";

                        $dates = date('Y-m-d H:i:s', $row['hexiaotime']);
                        $orderinfo .= "核销时间：{$dates}<BR>";

                        $bdate = $row['senddate'];
                        $btime = $row['sendtime'];
                        if (!empty($btime) && $row['dispatchtype'] == 1) {
                            $orderinfo .= "送货时间：{$bdate} {$row['sendtime']}<BR>";
                        }

                        if ($row['dispatchtype'] == 3) {
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_store') . ' WHERE id = :id', array(':id' => $row['checkstore']));
                            $orderinfo .= "核销门店：{$com['storename']}<BR>";
                        }
                        $orderinfo .= "订单号：{$row['orderno']}<BR>";
                        $orderInfo .= "商品信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "商品名称：{$goodsInfo['gname']}<BR>";
                        $orderinfo .= "购买数量：{$row['gnum']}<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        if (!empty($row['optionname'])) {
                            $orderInfo .= "规格：<BR>";
                            $orderinfo .= '------------------------------<BR>';
                            $orderinfo .= "{$row['optionname']}<BR>";
                        }

                        $orderinfo .= "订单金额：￥{$row['goodsprice']}<BR>优惠金额：￥{$cpay}<BR>{$tt}：￥{$row['price']}<BR>";
                        if($tuan['selltype'] == 7){
                            $bprice = pdo_fetch("SELECT * FROM cm_tg_order WHERE master_orderno={$row['orderno']}");
                            $orderinfo .= "补款金额：￥{$bprice['price']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';

                        $orderinfo .= "用户信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "用户名：{$row['addname']}<BR>";
                        $orderinfo .= "手机号：{$row['mobile']}<BR>";
                        if ($ordernew['dispatchtype'] != 3) {
                            $orderinfo .= "地址：{$row['address']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "备注信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "{$row['remark']}<BR>";

                        if (empty($row["print_id"])) {
                            if (strpos($row['address'], $li['name']) !== false || $li['name'] == '全国') {

                                $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                            }
                        } else {
                            $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                        }
                    }
                    if (!is_error($status)) {
                        $i++;
                        $data = array(
                            'uniacid' => $_W['uniacid'],
                            'sid' => $sid,
                            'pid' => $li['id'],
                            'oid' => $orderID, //订单id
                            'status' => 1,
                            'foid' => $status,
                            'addtime' => TIMESTAMP
                        );
                        pdo_insert('tg_order_print', $data);
                    }
                }
            }
        }

    } else {

        $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='" . $_W['uniacid'] . "'   AND orderno='" . $row['orderno'] . "'");
        $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='" . $_W['uniacid'] . "' AND openid = '" . $_W['openid'] . "'");
        $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='" . $_W['uniacid'] . "' AND uid = '" . $fans['uid'] . "'");
        $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];

        $btype = "";
        if ($row['dispatchtype'] == 0) {
            $btype = "无";
        }
        if ($row['dispatchtype'] == 2) {
            $btype = "快递";
        }
        if ($row['dispatchtype'] == 3) {
            $btype = "自提";
        }
        if ($row['dispatchtype'] == 1) {
            $btype = "送货上门";
        }

        $bdate = $row['senddate'];
        $btime = $row['sendtime'];
        foreach ($favoriteqqq as $key => $orderss) {
            $gs = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . "where uniacid='" . $_W['uniacid'] . "' AND id='" . $orderss['sid'] . "' ");
            $orderinfos .= "{$gs['gname']}X{$orderss['num']}<BR>";
            $orderinfos .= "{$orderss['item']}<BR>";
            $newnum = intval($gs['gnum']) - intval($orderss['num']);
            //pdo_update('tg_goods', array('gnum' => $newnum), array('id' => $gs['id']));
        }
        $tt = "实付金额";
        $orno = substr($row['orderno'], -10);
        if ($row['pay_type'] == 2) {
            $bt = "微信支付";
        }
        if ($row['pay_type'] == 3) {
            $bt = "货到付款";
            $tt = "待付金额";
        }
        ///////
        //获取所有打印机
        if (empty($row["print_id"]) || $row["print_id"] == 0) {
            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 and merchant_id = :merchant_id ', array(':aid' => $_W['uniacid'] , ':merchant_id' => $row['merchantid']));
        } else {
            $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1 AND id = :id', array(':aid' => $_W['uniacid'], ":id" => $row["print_id"]));
        }
        if (!empty($prints)) {
            // require_once IA_ROOT . '/addons/feng_fightgroups/wprint.class.php';
            //include_once 'wprint.class.php';
            //遍历所有打印机
            foreach ($prints as $li) {
                if (!empty($li['print_no']) && !empty($li['key'])) {
                    $wprint = new wprint();
                    if ($li['mode'] == 1) {


                        $orderinfo = "";
                        $orderinfo .= "<CB>核销成功</CB><BR>";
                        $orderinfo .= "订单编号：{$orno}<BR>";
                        $orderinfo .= "配送类型：{$btype}<BR>";
                        if ($row['dispatchtype'] == 2) {
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_delivery_template') . ' WHERE id = :id', array(':id' => $row['comadd']));
                            $orderinfo .= "快递公司：{$com['name']}<BR>";
                        }
                        $dates = date('Y-m-d H:i:s', $row['hexiaotime']);
                        $orderinfo .= "核销时间：{$dates}<BR>";
                        if (!empty($btime) && $row['dispatchtype'] == 1) {
                            $orderinfo .= "送货时间：{$bdate} {$row['sendtime']}<BR>";
                        }
                        if ($row['dispatchtype'] == 3) {
                            $com = pdo_fetch('SELECT * FROM ' . tablename('tg_store') . ' WHERE id = :id', array(':id' => $row['checkstore']));
                            $orderinfo .= "核销门店：{$com['storename']}<BR>";
//                            $row['address'] = '';
                        }
                        $orderinfo .= "支付方式：{$bt}<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= '商品信息                   <BR>';
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= $orderinfos;
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "订单金额：￥{$row['goodsprice']}<BR>优惠金额：￥{$row['discount_fee']}<BR>{$tt}：￥{$row['pay_price']}<BR>";
                        if($tuan['selltype'] == 7){
                            $bprice = pdo_fetch("SELECT * FROM cm_tg_order WHERE master_orderno={$row['orderno']}");
                            $orderinfo .= "补款金额：￥{$bprice['price']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';

                        $orderinfo .= "用户信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "用户名：{$row['addname']}<BR>";
                        $orderinfo .= "手机号：{$row['mobile']}<BR>";
                        if ($row['dispatchtype'] != 3) {
                            $orderinfo .= "地址：{$row['address']}<BR>";
                        }
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "备注信息：<BR>";
                        $orderinfo .= '------------------------------<BR>';
                        $orderinfo .= "{$row['remark']}<BR>";
                        if (empty($row["print_id"]) || $row["print_id"] == 0) {
                            if (strpos($row['address'], $li['name']) !== false || $li['name'] == '全国') {

                                $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                            }
                        } else {
                            $status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
                        }


                    }
                    if (!is_error($status)) {
                        $i++;
                        $data = array(
                            'uniacid' => $_W['uniacid'],
                            'sid' => $sid,
                            'pid' => $li['id'],
                            'oid' => $orderID, //订单id
                            'status' => 1,
                            'foid' => $status,
                            'addtime' => TIMESTAMP
                        );
                        pdo_insert('tg_order_print', $data);
                    }
                }
            }
        }

    }
}

function alltuan_print($tuanid,$is_auto=false)
{
    global $_W;

    $all = pdo_fetchall("select id from " . tablename('tg_order') . " where tuan_id='{$tuanid}' and mobile<>'虚拟' and status in (1,2,8)");
    foreach ($all as $row) {

        tuan_print($row['id'],$is_auto);
    }
}

?>