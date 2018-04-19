<?php
$orderno = $_GPC['mid'];
$result = $_GPC['result'];
wl_load()->func("message");
wl_load()->func("print");
$order = pdo_fetch("select * from " . tablename('tg_order') . " where uniacid = '{$_W['uniacid']}' and orderno = '{$orderno}'");
if ($order['g_id'] > 0) {
    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id = '{$order['g_id']}' and uniacid = '{$_W['uniacid']}'");
} else {
    $col = pdo_fetchall("select id,goodsname,oprice,sid,num,item from " . tablename('tg_collect') . " where orderno = '{$order['orderno']}'");
    foreach ($col as &$vv) {
        if (empty($vv['goodsname'])) {
            $g = pdo_fetch("select gname from " . tablename('tg_goods') . " where id = '{$vv['sid']}'");
            $vv['goodsname'] = $g['gname'];
            pdo_update('tg_collect', array('goodsname' => $g['gname']), array('id' => $vv['id']));
        }
    }
}
//die(json_encode($col));
if ($order['selltype'] == 7) {
    $master_order = pdo_fetch('select * from ' . tablename('tg_order') . ' where uniacid = :uniacid and master_orderno = :orderno and status = 3', array(':uniacid' => $_W['uniacid'], ':orderno' => $orderno));
}
$is_hexiao_member = FALSE;

$store_ids = explode(',', substr($goods['hexiao_id'], 0, strlen($goods['hexiao_id']) - 1));
if ($order['g_id'] == 0) {
    $store_ids = explode(',', substr($config['base']['hexiao_id'], 0, strlen($config['base']['hexiao_id']) - 1));
    //$storesids = explode(",", $config['base']['hexiao_id']);
}
//uniacid = 1356
//message($goods['hexiao_id']);
//*判断是否是核销人员*/
$hexiao_member = pdo_fetch("select * from " . tablename('tg_saler') . " where uniacid = :uniacid and openid = :openid and status = 1 and merchantid = '{$order['merchantid']}'", array(":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"]));

if (!empty($hexiao_member)) { //如果是核销员
    $is_hexiao_member = TRUE;

    if ($hexiao_member['storeid'] == '') {//如果这个数组中的店铺id是空的话

        $storelist = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid = '{$_W['uniacid']}' and status = 1 ");//公众号对应的店铺
        foreach ($storelist as $key => $value) {
            $hexiao_member['storeid'] .= $value['id'] . ",";
        }

    } else {
        if (empty($store_ids)) {

        } else {
            $hexiao_ids = explode(',', substr($hexiao_member['storeid'], 0, strlen($hexiao_member['storeid']) - 1));

            foreach ($hexiao_ids as $value) {
                if (in_array($value, $store_ids)) {
                    break;
                }
            }
        }
    }
}

//门店信息
$storesids = explode(",", $hexiao_member['storeid']);
foreach ($storesids as $key => $value) {
    if ($value) {
        $st = pdo_fetch("select * from " . tablename('tg_store') . " where id = '{$value}' and merchantid = '{$order['merchantid']}' and uniacid = '{$_W['uniacid']}'");
        if (!empty($st)) {
            $stores[$key] = $st;
        }
    }
}

$store = pdo_fetch("select * from " . tablename('tg_store') . " where id = '{$order['comadd']}'");
if ($_W['isajax']) {

    $url = app_url('order/order/detail', array('id' => $order['id']));
    // hexiao_success($goods['gname'], $order['openid'], $order['gnum'], TIMESTAMP, $url);
    $checkorder = pdo_fetch("select checkstore,status,servestype from " . tablename('tg_order') . " where orderno='{$orderno}' ");
    if ($checkorder['servestype'] >= 1) {
        wl_json(0, '该订单已申请售后，请勿重复发货！');
    }
    if ($checkorder['status'] == 2) {
        wl_json(0, '该订单已发货，请勿重复发货！');

    }
    if (!empty($checkorder['checkstore'])) {
        wl_json(0, '该订单已核销完毕，请勿重复核销！');

    }


    if (pdo_update('tg_order', array('status' => 3, 'over_time' => TIMESTAM, 'is_hexiao' => 2, 'veropenid' => $_W['openid'], 'hexiaotime' => TIMESTAMP, 'sendtime' => TIMESTAMP, 'gettime' => TIMESTAMP, 'checkstore' => $_GPC['id']), array('orderno' => $orderno))) {
        //佣金
        $value = pdo_fetch("select * from" . tablename('tg_order') . "where  uniacid='{$_W['uniacid']}' and orderno='{$orderno}'");
        if (intval($value['comtype']) == 0) {
            /**********************/
            //积分
            if ($value['g_id'] > 0) {
                $goodsInfo = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid ='{$_W['uniacid']}' and id = '{$value['g_id']}'");
                $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$_W['uniacid']}' and openid = '{$value['openid']}'");
                $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='{$_W['uniacid']}' and uid = '{$fans['uid']}'");
                $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
                pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                //佣金
//                $ud = pdo_fetch("select parentid from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and from_user='{$value['openid']}'");
//                if (intval($ud['parentid']) > 0) {
//                    $parent = pdo_fetch("select * from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and id={$ud['parentid']}");
//                    if ($value['commissiontype'] == 2) {
//                        $price1 = $value['commission'];
//                    } else {
//                        $price1 = ($value['price'] - $value['cost_fee']) * $value['commission'] / 100;//佣金计算
//                    }
//                    $billing = $parent['billing'] + $price1;//已结算佣金
//                    $wallet = $parent['wallet'] + $price1;//钱包总佣金
//                    $sell_total = $parent['sell_total'] + $value['price'];//统计销售额
//                    //$nobilling=$parent['nobilling']-$price;//未结算佣金
//                    pdo_update('tg_member', array('billing' => $billing, 'wallet' => $wallet, 'sell_total' => $sell_total), array('id' => $parent['id']));
//                    //佣金结算记录
//                    $bdata = array(
//                        'uniacid' => $_W['uniacid'],
//                        'openid' => $parent['from_user'],
//                        'orderno' => $value['orderno'],
//                        'billdate' => TIMESTAMP,
//                        'price' => $price1
//                    );
//                    pdo_insert('tg_billrecord', $bdata);
//
//                }
////
            }
            if ($value['g_id'] == 0) {

                $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='{$_W['uniacid']}'   and orderno='{$value['orderno']}'");
                $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$_W['uniacid']}' and openid = '{$_W['openid']}'");
                $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='{$_W['uniacid']}' and uid = '{$fans['uid']}'");
                $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
//                $price1 = 0;
                foreach ($favoriteqqq as $key => $orderss) {
                    $gs = pdo_fetch("select * from " . tablename('tg_goods') . "where uniacid='{$_W['uniacid']}' and id={$orderss['sid']} ");
                    if (!empty($gs['credit']) && $gs['credit'] != 0) {
                        $creditnum += $gs['credit'] * $orderss['num'];
                    }
//                    if ($gs['commissiontype'] == 2) {
//                        $price1 += $gs['commission'];
//                    } else {
//                        $price1 += ($orderss['oprice'] * $orderss['num']) * $orderss['commission'] / 100;//佣金计算
//                    }
                }
                pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                //积分
                //佣金
//                $ud = pdo_fetch("select parentid from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and from_user='{$value['openid']}'");
//                if (intval($ud['parentid']) > 0) {
//                    $parent = pdo_fetch("select * from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and id={$ud['parentid']}");
//
//                    $billing = $parent['billing'] + $price1;//已结算佣金
//                    $wallet = $parent['wallet'] + $price1;//钱包总佣金
//                    $sell_total = $parent['sell_total'] + $value['price'];//统计销售额
//                    //$nobilling=$parent['nobilling']-$price;//未结算佣金
//                    pdo_update('tg_member', array('billing' => $billing, 'wallet' => $wallet, 'sell_total' => $sell_total), array('id' => $parent['id']));
//                    //佣金结算记录
//                    $bdata = array(
//                        'uniacid' => $_W['uniacid'],
//                        'openid' => $parent['from_user'],
//                        'orderno' => $value['orderno'],
//                        'billdate' => TIMESTAMP,
//                        'price' => $price1
//                    );
//                    pdo_insert('tg_billrecord', $bdata);
//
//                }
//	
            }
//            pdo_update('tg_order', array('comtype' => 1), array('id' => $value['id']));
            //获取当前会员的openid
            $openid = $value["openid"];
            //获取当前会员的等级
            $user_info = pdo_fetch("select * from " .
                tablename("tg_member") .
                " where from_user = :openid and uniacid = :uniacid",
                array(":openid" => $openid, ":uniacid" => $value["uniacid"])
            );
            //查出来会员的总订单金额是多少
            $order_money = pdo_fetch("select sum(price) as price from " .
                tablename("tg_order") .
                " where openid = :openid and uniacid = :uniacid and status = 3",
                array(":openid" => $openid, ":uniacid" => $value["uniacid"])
            );
            $order_price = $order_money["price"];
            //计算出总金额之后然后查看赋予会员的等级是多少
            $auto_level = pdo_fetch("select * from " .
                tablename("tg_member_leave") .
                " where :order_price >= money and uniacid = :uniacid order by money desc",
                array(":uniacid" => $value["uniacid"], ":order_price" => $order_price));
            if ($auto_level["id"] == intval($user_info["level"])) {
                //不做变化
            } elseif ($auto_level["id"] < intval($user_info["level"])) {
                //不做变化
            } else {
                //升级会员等级
                $data["level"] = $auto_level["id"];
                $res = pdo_update("tg_member",
                    $data,
                    array("from_user" => $openid, "uniacid" => $value["uniacid"])
                );
            }

        }

        $openid = $order["openid"];
        //确认是邻够团  确认是团长 确认状态是1||1
        if ($order["selltype"] == 3 && $order["tuan_first"] == 1 and $order["status"] == 1 || $order["status"] == 2) {
            $tuan_list = pdo_fetchall("select * from " . tablename("tg_order") . " where status <>3 and status<>9 and tuan_id = :tuan_id and tuan_first = 0", array(":tuan_id" => $order["tuan_id"]));
            foreach ($tuan_list as $v) {
//                pdo_update("tg_order",array("status"=>3),array("tuan_id"=>$order["tuan_id"],"status"=>2));
//                $sql = "update " . tablename('tg_order') . " set status = 3 , over_time =  where tuan_id = " . $order["tuan_id"] . ' and status <>3 and status <>9 and tuan_first = 0';
//                pdo_query($sql);
                pdo_update('tg_order', array('status' => 3, 'over_time' => TIMESTAMP), array('tuan_id' => $order["tuan_id"], 'tuan_first' => 0, 'status' => 2));
                if (intval($value['comtype']) == 0) {
                    /**********************/
                    //积分
                    if ($value['g_id'] > 0) {
                        $goodsInfo = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid ='{$_W['uniacid']}' and id = '{$value['g_id']}'");
                        $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$_W['uniacid']}' and openid = '{$value['openid']}'");
                        $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='{$_W['uniacid']}' and uid = '{$fans['uid']}'");
                        $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
                        pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                        //佣金
//                        $ud = pdo_fetch("select parentid from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and from_user='{$value['openid']}'");
//                        if (intval($ud['parentid']) > 0) {
//                            $parent = pdo_fetch("select * from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and id={$ud['parentid']}");
//                            if ($value['commissiontype'] == 2) {
//                                $price1 = $value['commission'];
//                            } else {
//                                $price1 = ($value['price'] - $value['cost_fee']) * $value['commission'] / 100;//佣金计算
//                            }
//                            $billing = $parent['billing'] + $price1;//已结算佣金
//                            $wallet = $parent['wallet'] + $price1;//钱包总佣金
//                            $sell_total = $parent['sell_total'] + $value['price'];//统计销售额
//                            //$nobilling=$parent['nobilling']-$price;//未结算佣金
//                            pdo_update('tg_member', array('billing' => $billing, 'wallet' => $wallet, 'sell_total' => $sell_total), array('id' => $parent['id']));
//                            //佣金结算记录
//                            $bdata = array(
//                                'uniacid' => $_W['uniacid'],
//                                'openid' => $parent['from_user'],
//                                'orderno' => $value['orderno'],
//                                'billdate' => TIMESTAMP,
//                                'price' => $price1
//                            );
//                            pdo_insert('tg_billrecord', $bdata);
//
//                        }
//
                    }
                    if ($value['g_id'] == 0) {

                        $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='{$_W['uniacid']}'   and orderno='{$value['orderno']}'");
                        $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$_W['uniacid']}' and openid = '{$_W['openid']}'");
                        $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid ='{$_W['uniacid']}' and uid = '{$fans['uid']}'");
                        $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
//                        $price1 = 0;
                        foreach ($favoriteqqq as $key => $orderss) {
                            $gs = pdo_fetch("select * from " . tablename('tg_goods') . "where uniacid='{$_W['uniacid']}' and id={$orderss['sid']} ");
                            if (!empty($gs['credit']) && $gs['credit'] != 0) {
                                $creditnum += $gs['credit'] * $orderss['num'];
                            }
//                            if ($gs['commissiontype'] == 2) {
//                                $price1 += $gs['commission'];
//                            } else {
//                                $price1 += ($orderss['oprice'] * $orderss['num']) * $orderss['commission'] / 100;//佣金计算
//                            }
                        }
                        pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                        //积分
                        //佣金
//                        $ud = pdo_fetch("select parentid from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and from_user='{$value['openid']}'");
//                        if (intval($ud['parentid']) > 0) {
//                            $parent = pdo_fetch("select * from" . tablename('tg_member') . "where  uniacid='{$_W['uniacid']}' and id={$ud['parentid']}");
//
//                            $billing = $parent['billing'] + $price1;//已结算佣金
//                            $wallet = $parent['wallet'] + $price1;//钱包总佣金
//                            $sell_total = $parent['sell_total'] + $value['price'];//统计销售额
//                            //$nobilling=$parent['nobilling']-$price;//未结算佣金
//                            pdo_update('tg_member', array('billing' => $billing, 'wallet' => $wallet, 'sell_total' => $sell_total), array('id' => $parent['id']));
//                            //佣金结算记录
//                            $bdata = array(
//                                'uniacid' => $_W['uniacid'],
//                                'openid' => $parent['from_user'],
//                                'orderno' => $value['orderno'],
//                                'billdate' => TIMESTAMP,
//                                'price' => $price1
//                            );
//                            pdo_insert('tg_billrecord', $bdata);
//
//                        }
//
                    }
//                    pdo_update('tg_order', array('comtype' => 1), array('id' => $value['id']));
                    //获取当前会员的openid
                    $openid = $value["openid"];
                    //获取当前会员的等级
                    $user_info = pdo_fetch("select * from " .
                        tablename("tg_member") .
                        " where from_user = :openid and uniacid = :uniacid",
                        array(":openid" => $openid, ":uniacid" => $value["uniacid"])
                    );
                    //查出来会员的总订单金额是多少
                    $order_money = pdo_fetch("select sum(price) as price from " .
                        tablename("tg_order") .
                        " where openid = :openid and uniacid = :uniacid and status = 3",
                        array(":openid" => $openid, ":uniacid" => $value["uniacid"])
                    );
                    $order_price = $order_money["price"];
                    //计算出总金额之后然后查看赋予会员的等级是多少
                    $auto_level = pdo_fetch("select * from " .
                        tablename("tg_member_leave") .
                        " where :order_price >= money and uniacid = :uniacid order by money desc",
                        array(":uniacid" => $value["uniacid"], ":order_price" => $order_price));
                    if ($auto_level["id"] == intval($user_info["level"])) {
                        //不做变化
                    } elseif ($auto_level["id"] < intval($user_info["level"])) {
                        //不做变化
                    } else {
                        //升级会员等级
                        $data["level"] = $auto_level["id"];
                        $res = pdo_update("tg_member",
                            $data,
                            array("from_user" => $openid, "uniacid" => $value["uniacid"])
                        );
                    }

                }

            }
        }
        $content = "尊敬的" . $order["addname"] . "，您的订单" . $order['orderno'] . "已经核销成功";
        if ($order['g_id'] > 0) {
            $goodsname = $order["goodsname"];
        } else {
            $goodsname = "多商品";
        }

        $goodsum = $order['gnum'] . ' ' . $order['item'];
        $time = date("Y-m-d H:i:s") . "核销员:" . $hexiao_member['nickname'];
        $remaark = "";
        hex($openid, $content, $goodsname, $goodsum, $time, $remark, $url);
        //file_put_contents(TG_DATA . "hx.log", var_export(date("Y-m-d H:i:s") . "是否是核销员" . json_encode($res), true) . PHP_EOL, FILE_APPEND);
        hex($_W['openid'], $content, $goodsname, $goodsum, $time, $remark, '');

        /*
         *  核销自动打印
         */
        $print = pdo_fetch("select * from " . tablename('tg_print') . " where uniacid = '{$_W['uniacid']}' and merchant_id = '{$order['merchantid']}' and status = 1 and hexiao_print = 1");
        if (!empty($print)) {
            hexiao_print($order['id']);
        }
//        file_put_contents(TG_DATA . "hx.log", var_export(array('t'=>date("Y-m-d H:i:s") , 'o'=>$order , 'p'=>$print), true) . PHP_EOL, FILE_APPEND);
        wl_json(1);
    } else {
        wl_json(0, '核销失败，请重试！');
    }
}
include wl_template('order/check');
exit();