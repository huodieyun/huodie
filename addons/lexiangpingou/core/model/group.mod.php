<?php

/**
 * [weliam] Copyright (c) 2016/3/23
 * 团model
 */
defined('IN_IA') or exit('Access Denied');
/**
 * 函数getgroupList，按检索条件检索出所有团
 * $params : 类型：array
 *
 */
function group_get_list($args = array())
{
    global $_W;

    $usepage = !empty($args['usepage']) ? $args['usepage'] : false;
    $page = !empty($args['page']) ? intval($args['page']) : 1;
    $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
    $orderby = !empty($args['orderby']) ? $args['orderby'] : 'order by id desc';

    $condition = ' and `uniacid` = :uniacid';
    $params = array(':uniacid' => $_W['uniacid']);


    if ($usepage) {
        $sql = "SELECT * FROM " . tablename('tg_group') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    } else {
        $sql = "SELECT * FROM " . tablename('tg_group') . " where 1 {$condition} ";
    }

    $list = pdo_fetchall($sql, $params);
    foreach ($list as $key => &$value) {
        $value['gimg'] = tomedia($value['gimg']);
        $value['a'] = app_url('group/detail/display', array('id' => $value['id']));
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . "where 1 $condition ", $params);

    $data = array();
    $data['list'] = $list;
    $data['total'] = $total;
    return $data;
}

/**
 * 函数getgroupByParams，按检索条件检索出指定团
 * $params : 类型：字符串
 *
 */

function group_get_by_params($params = '')
{
    global $_W;
    if (!empty($params)) {
        $params = ' where ' . $params;
    }
    $sql = "SELECT * FROM " . tablename('tg_group') . $params;
    $group = pdo_fetch($sql);
    return $group;
}

/**
 * 函数getgroupByParams，按条件检索更新团
 * $data : 类型：array ; $params 类型：array
 *
 */
function group_update_by_params($data, $params)
{
    global $_W;
    $flag = pdo_update('tg_group', $data, $params);
    return $flag;
}

/**
 * 函数insertgroup，插入新团
 * $data : 类型：array
 * 返回值：插入ID
 */
function group_insert($data)
{
    global $_W;
    $flag = pdo_insert('tg_group', $data);
    if ($flag) {
        $insertid = pdo_insertid();
    } else {
        $insertid = FALSE;
    }
    return $insertid;
}

/**
 * 函数deletegroup，删除团
 * $id : 类型：int
 * 返回值：
 */
function group_delete($id)
{
    global $_W;
    $flag = pdo_delete('tg_group', $id);
    return $flag;
}

function updategourp()
{
    global $_W, $_GPC;
    wl_load()->model('goods');
    $now = time();
    $allgroups = pdo_fetchall("select * from " . tablename('tg_group') . " where uniacid={$_W['uniacid']} and groupstatus = 3 and endtime < {$now} and lacknum > 0 order by endtime limit 3 ");
    if ($allgroups) {

        foreach ($allgroups as $key => $value) {

            if ($value['on_success'] == 0) {
                if ($value['selltype'] != 4) {

                    $orders = pdo_fetchall("select id from " . tablename('tg_order') . " where tuan_id = '{$value['groupnumber']}' and uniacid = '{$value['uniacid']}' and status = 1");
                    foreach ($orders as $k => $v) {
                        $res = pdo_update('tg_order', array('status' => 10), array('id' => $v['id']));
                    }
                    pdo_update('tg_group', array('groupstatus' => 1), array('groupnumber' => $value['groupnumber']));
                } else {

                    ///统计数量 阶梯团
                    $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = :tuan_id AND uniacid = :uniacid AND ptime > 0 ", array(':tuan_id' => $value['groupnumber'], ':uniacid' => $value['uniacid']));

                    $g = pdo_fetch("select is_amount from " . tablename('tg_goods') . " where id = '{$value['goodsid']}'");
                    if ($g['is_amount'] == 1) {
                        $auto_orders = pdo_fetchcolumn("SELECT SUM(gnum) FROM " . tablename('tg_order') . " WHERE tuan_id = :tuan_id AND uniacid = :uniacid AND status = 3 ", array(':tuan_id' => $value['groupnumber'], ':uniacid' => $value['uniacid']));
                        $gcount = pdo_fetchcolumn("SELECT SUM(gnum) FROM " . tablename('tg_order') . " WHERE tuan_id = :tuan_id AND uniacid = :uniacid AND ptime > 0 ", array(':tuan_id' => $value['groupnumber'], ':uniacid' => $value['uniacid']));
                        $allnum = intval($gcount) + intval($auto_orders);
                    } else {
                        $auto_orders = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE tuan_id = :tuan_id AND uniacid = :uniacid AND status = 3 ", array(':tuan_id' => $value['groupnumber'], ':uniacid' => $value['uniacid']));
                        $allnum = count($orders) + count($auto_orders);
                    }
                    foreach ($orders as $k => $v) {
                        //$goodsInfo = pdo_fetch("select id,group_level,gprice from" . tablename('tg_goods') . "where id=:id and uniacid=:uniacid ",array(':id'=>$v['g_id'],':uniacid'=>$value['uniacid']));

                        $param_level = unserialize($value['group_level']);
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

                            if ($param_level[$i]['groupnum'] > $allnum && $param_level[$i + 1]['groupnum'] <= $allnum) {
                                $tempprice = $param_level[$i + 1]['groupprice'];
                                break;
                            }
                            if ($param_level[$i]['groupnum'] == $allnum) {
                                $tempprice = $param_level[$i]['groupprice'];
                                break;
                            }
                        }

                        if ($param_level[count($param_level) - 1]['groupnum'] >= $allnum) {
                            $tempprice = $param_level[count($param_level) - 1]['groupprice'];

                        }

                        $refundprice = round((round($v['price'], 2) - round($tempprice, 2) * $v['gnum']),2) - round($v['freight'],2);
                        //优惠券折扣
                        if($v['couponid']){
                            $coupon = pdo_fetch("SELECT cash FROM cm_tg_coupon WHERE id={$v['couponid']}");
                            $refundprice = round($coupon['cash'],2)+round((round($v['price'], 2) - round($tempprice, 2) * $v['gnum']),2) - round($v['freight'],2);
                        }
                        if ($refundprice > 0) {
                            //插入退款记录
                            if ($v['orderno'] != '20160929382019210903') {
                                $data1 = array('orderno' => $v['orderno'], 'status' => 1, 'refundprice' => $refundprice);
                                pdo_insert('tg_order_level_refund', $data1);
                            }

                        }

                        $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id=:goodsid ", array(':goodsid' => $v['g_id']));
                        /*更改规格库存*/
                        if (!empty($v['optionname'])) {
                            $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $v['g_id'], ':title' => $v['optionname']));
                            pdo_update('tg_goods_option', array('stock' => $stock['stock'] - $v['gnum']), array('goodsid' => $v['g_id'], 'title' => $v['optionname']));
                        } else {
                            pdo_update('tg_goods', array('gnum' => $goods['gnum'] - $v['gnum']), array('id' => $v['g_id']));
                        }

                        $v_goods = goods_get_by_params(" id = {$v['g_id']}");

                        if($v_goods['has_store_stock'] == 1){
                            if (!empty($v['optionname'])) {
                                $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $v['g_id'], ':storeid' => $v['comadd'],':optionid'=>$v['optionid'],':uniacid'=>$v['uniacid']));
                                pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $v['gnum']),array('id'=>$store_stock['id']));
                            }else{
                                $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and uniacid=:uniacid", array(':goodsid' => $v['g_id'], ':storeid' => $v['comadd'],':uniacid'=>$v['uniacid']));
                                pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $v['gnum']),array('id'=>$store_stock['id']));
                            }


                        }
                        pdo_update('tg_goods', array('salenum' => $goods['salenum'] + $v['gnum']), array('id' => $v['g_id']));


                    }
                    pdo_update('tg_order', array('status' => 8, 'successtime' => TIMESTAMP), array('tuan_id' => $value['groupnumber'], 'status' => 1));
                    pdo_update('tg_group', array('groupstatus' => 2,'successtime' => TIMESTAMP), array('groupnumber' => $value['groupnumber']));

                    if ($value['groupnumber'] != 532601) {
                        group_success($value['groupnumber'], '');
                        wl_load()->func('print');
                        alltuan_print($value['groupnumber']);
                    }
                }
            }
            if ($value['on_success'] == 1) {

                $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = :tuan_id AND uniacid = :uniacid AND ptime > 0 and status = 1 ", array(':tuan_id' => $value['groupnumber'], ':uniacid' => $value['uniacid']));
                foreach ($orders as $order) {
                    /*增加历史购买数量*/
                    $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $order['uniacid'], ':openid' => $order['openid'], ':g_id' => $order['g_id']));
                    if (empty($old_data)) {
                        $logdata = array(
                            'g_id' => $order['g_id'],
                            'openid' => $order['openid'],
                            'num' => $order['gnum'],
                            'uniacid' => $order['uniacid']
                        );
                        pdo_insert('tg_goods_openid', $logdata);
                    } else {
                        pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $order['gnum']), array('id' => $old_data['id']));
                    }
                    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id=:goodsid ", array(':goodsid' => $order['g_id']));
                    /*更改规格库存*/
                    if (!empty($order['optionname'])) {
                        $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $order['g_id'], ':title' => $order['optionname']));
                        pdo_update('tg_goods_option', array('stock' => $stock['stock'] - $order['gnum']), array('goodsid' => $order['g_id'], 'title' => $order['optionname']));
                    } else {
                        pdo_update('tg_goods', array('gnum' => $goods['gnum'] - $order['gnum']), array('id' => $order['g_id']));
                    }
                    $v_goods = goods_get_by_params(" id = {$order['g_id']}");
                    if($v_goods['has_store_stock'] == 1){
                        if (!empty($order['optionname'])) {
                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $order['g_id'], ':storeid' => $order['comadd'],':optionid'=>$order['optionid'],':uniacid'=>$order['uniacid']));
                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order['gnum']),array('id'=>$store_stock['id']));
                        }else{
                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and uniacid=:uniacid", array(':goodsid' => $order['g_id'], ':storeid' => $order['comadd'],':uniacid'=>$order['uniacid']));
                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order['gnum']),array('id'=>$store_stock['id']));
                        }


                    }
                    pdo_update('tg_goods', array('salenum' => $goods['salenum'] + $order['gnum']), array('id' => $order['g_id']));
                }

                if ($value['selltype'] == 7) {
                    $group = pdo_fetch('SELECT * FROM ' . tablename('tg_group') . ' WHERE groupnumber=:groupnumber AND uniacid=:uniacid', array(':groupnumber' => $value['groupnumber'], ':uniacid' => $value['uniacid']));
                    $param_level = unserialize($group['group_level']);
                    $allnum = $group['nownum'];

                    for ($i = 0; $i < count($param_level); $i++) {
                        for ($j = 0; $j < count($param_level) - $i - 1; $j++) {
                            if ($param_level[$j]['groupnum'] > $param_level[$j + 1]['groupnum']) {
                                $temp = $param_level[$j];
                                $param_level[$j] = $param_level[$j + 1];
                                $param_level[$j + 1] = $temp;
                            }
                        }
                    }

                    for ($i = 0; $i < count($param_level); $i++) {

                        if ($param_level[$i]['groupnum'] <= $allnum && $param_level[$i + 1]['groupnum'] > $allnum) {
                            $tempprice = $param_level[$i]['groupprice'];
                            break;
                        }

                    }
                    if ($param_level[0]['groupnum'] > $allnum) {
                        $goods = pdo_fetch('SELECT oprice FROM ' . tablename('tg_goods') . ' WHERE id=:id', array(':id' => $value['goodsid']));
                        $tempprice = $goods['oprice'];

                    }
                    if ($param_level[count($param_level) - 1]['groupnum'] <= $allnum) {
                        $tempprice = $param_level[count($param_level) - 1]['groupprice'];

                    }

                    if (empty($tempprice)) {
                        $g = pdo_fetch("select oprice from " . tablename('tg_goods') . " where id = '{$value['goodsid']}' ");
                        $tempprice = $g['oprice'];
                    }

                    pdo_update('tg_order', array('successtime' => TIMESTAMP, 'bukuanstatus' => 1), array('tuan_id' => $value['groupnumber'], 'status' => 1));

                    pdo_update('tg_group', array('groupstatus' => 2, 'successtime' => TIMESTAMP), array('groupnumber' => $value['groupnumber']));

                    //补款通知
                    $order_list = pdo_fetchall('select orderno,openid,goodsname,ptime,price,gnum from ' . tablename('tg_order') . ' where tuan_id=:tuan_id and status=1', array(':tuan_id' => $value['groupnumber']));
                    foreach ($order_list as $item) {
                        $pay_price = sprintf("%.2f", $item['gnum'] * $tempprice - $item['price']);

                        if ($pay_price > 0) {
                            $url = app_url('order/master_orderconfirm', array('master_orderno' => $item['orderno']));
                            $openid = $item['openid'];
                            $title = '您好，你参与的' . $item['goodsname'] . '的拼团，可以补齐尾款了';
                            $goodsname = $item['goodsname'];
                            $orderno = $item['orderno'];
                            $datetime = date('Y-m-d H:i', $item['ptime']);
                            $remark = '请在' . $item['goodsname'] . '活动结束之前补齐尾款，过时失效系统将自动退款处理';
                            bukuan_notice($openid, $title, $goodsname, $orderno, $datetime, $remark, $url);

                        } else {
                            pdo_update('tg_order', array('status' => 8, 'bukuanstatus' => 2), array('orderno' => $item['orderno']));
                        }

                    }

                } else {
                    pdo_update('tg_order', array('status' => 8, 'successtime' => TIMESTAMP), array('tuan_id' => $value['groupnumber'], 'status' => 1));

                    pdo_update('tg_group', array('groupstatus' => 2, 'successtime' => TIMESTAMP), array('groupnumber' => $value['groupnumber']));

                    /*团成功通知*/
                    if ($value['groupnumber'] != 532601) {
                        group_success($value['groupnumber'], '');
                        wl_load()->func('print');
                        alltuan_print($value['groupnumber']);
                    }

                }

            }

        }
    } else {

        $sql = "SELECT a.id,a.tuan_id,a.`status`,a.ptime,a.g_id,a.orderno,a.uniacid,a.selltype from cm_tg_order a left join cm_tg_group b on a.tuan_id=b.groupnumber where a.`status`=1 and b.groupstatus=2 and a.ptime>0 and a.selltype<>7";
        $allorder = pdo_fetchall($sql);

        if ($allorder) {
            $tuan_id = 0;
            foreach ($allorder as $item) {

                internal_log('updategroup', $item);
                pdo_update('tg_order', array('status' => 8), array('id' => $item['id']));
                $print_times = pdo_get('tg_order_collect', array('orderno' => $item['orderno']), 'print_times');
                if ($tuan_id != $item['tuan_id']) {
                    $tuan_id = $item['tuan_id'];
                    if (!$print_times && $tuan_id) {

//                    group_success($tuan_id, '');
//                    wl_load()->func('print');
//                    alltuan_print($tuan_id);

                    }
                }
            }
        }

    }
}
