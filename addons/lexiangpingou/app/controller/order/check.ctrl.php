<?php

wl_load()->func("message");
wl_load()->func("print");

wl_load()->model('setting');
wl_load()->model('goods');
$kaiguan = setting_get_by_name('kaiguan');
$ordernos = $_GPC['mid'];
$result = $_GPC['result'];
$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
if (!$openid) {
    die("<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                    <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                </head>
                <body>
                <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>请在微信客户端打开链接</h4></div></div></div>
                </body>
            </html>");
}
$ordernos = explode('_',$ordernos);
$orders = array();
foreach ($ordernos as $kkkk=>$orderno){
    $order = pdo_fetch("select * from " . tablename('tg_order') . " where uniacid = '{$uniacid}' and orderno = '{$orderno}'");
    if ($order['g_id'] > 0) {
        $goods = pdo_fetchall("select * from " . tablename('tg_goods') . " where id = '{$order['g_id']}' and uniacid = '{$uniacid}'");
        $goods[0]['gnum'] = $order['gnum'];
    } else {
        $goods = pdo_fetchall("select id,goodsname as gname,oprice,sid,num as gnum,item from " . tablename('tg_collect') . " where orderno = '{$order['orderno']}'");
    }

    if ($order['selltype'] == 7) {
        $master_order = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . ' WHERE uniacid = :uniacid AND master_orderno = :orderno AND status = 3', array(':uniacid' => $uniacid, ':orderno' => $orderno));
        $order['price'] += $master_order['price'];
    }
    $is_hexiao_member = FALSE;
    if ($order['dispatchtype'] == 1) {
        $hexiao_member = pdo_fetch("select * from " . tablename('tg_delivery_man') . " where uniacid = '{$uniacid}' and status = 1 and merchantid = '{$order['merchantid']}' and openid = '{$openid}' ");
        if (!empty($hexiao_member)) {
            $is_hexiao_member = TRUE;
            $store = pdo_fetch("select * from " . tablename('tg_delivery_man') . " where id = '{$order['storeid']}' ");

        }
    } else {

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

                $storelist = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid = '{$uniacid}' and status = 1 ");//公众号对应的店铺
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
                $st = pdo_fetch("select * from " . tablename('tg_store') . " where id = '{$value}' and merchantid = '{$order['merchantid']}' and uniacid = '{$uniacid}'");
                if (!empty($st)) {
                    $stores[$key] = $st;
                }
            }
        }

        $store = pdo_fetch("select * from " . tablename('tg_store') . " where id = '{$order['comadd']}'");
    }

    $orders[$kkkk]  = $order;
    $orders[$kkkk]['goods']  = $goods;
    $orders[$kkkk]['store']  = $store;
}
if ($_W['isajax']) {

    $retreat = $_GPC['retreat'];
    foreach ($orders as $order){
        if ($retreat) {

            if ($order['status'] != 3 && $order['dispatchtype'] != 3) {
                wl_json(0, '该订单已核销完毕，请勿重复核销！');
            }
            $dat = array();
            $dat['uniacid'] = $uniacid;
            $dat['checkopenid'] = $openid;
            $dat['checknickname'] = $hexiao_member['nickname'];
            $dat['restoreid'] = $_GPC['id'];
            $dat['orderno'] = $order['orderno'];
            $dat['openid'] = $order['openid'];
            $dat['addname'] = $order['addname'];
            $dat['type'] = intval($_GPC['type']);
            $dat['price'] = 0;
            $url = app_url('order/order/detail', array('id' => $order['id']));
            if ($dat['type'] == 0) {
                $dat['price'] = $_GPC['price'];
                if ($order['pay_type'] == 9) {
                    balance_payment_refund($order['transid'], 2, $order,  '到店退货', $dat['price']);
                } else {
                    partrefund($order['orderno'], 1, $_GPC['price']);
                    refund_success($order['orderno'], $order['goodsname'], $order['openid'], $_GPC['price'], TIMESTAMP, $url, '到店退货');
                }
            } elseif ($dat['type'] == 1 && $order['selltype'] == 7) {
                $dat['price'] = $order['price'];
                if ($master_order) {
                    $dat['price'] += $master_order['price'];
                    refund($master_order['orderno'], 1);
                    refund_success($master_order['orderno'], $order['goodsname'], $order['openid'], $master_order['price'], TIMESTAMP, $url, '补款退回');
                }
                refund($order['orderno'], 1);
                refund_success($order['orderno'], $order['goodsname'], $order['openid'], $order['price'], TIMESTAMP, $url, '到店退货');
            } elseif ($dat['type'] == 2) {
                $dat['price'] = $order['price'];
                if ($order['pay_type'] == 9) {
                    balance_payment_refund($order['transid'], 1, $order,  '到店退货', $dat['price']);
                } else {
                    refund($order['orderno'], 1);
                    refund_success($order['orderno'], $order['goodsname'], $order['openid'], $order['price'], TIMESTAMP, $url, '到店退货');
                }
            } elseif ($dat['type'] == 3) {
                $dat['price'] = $master_order['price'];
                refund($master_order['orderno'], 1);
                refund_success($master_order['orderno'], $order['goodsname'], $order['openid'], $master_order['price'], TIMESTAMP, $url, '补款退回');
            } elseif ($dat['type'] == 1 && $order['selltype'] != 7) {
                $dat['price'] = $order['price'];
                if ($order['pay_type'] == 9) {
                    balance_payment_refund($order['transid'], 1, $order,  '到店退货', $dat['price']);
                } else {
                    refund($order['orderno'], 1);
                    refund_success($order['orderno'], $order['goodsname'], $order['openid'], $order['price'], TIMESTAMP, $url, '到店退货');
                }
            }

            //核销全额退款记录
            wl_load()->model('setting');
            $setting = setting_get_by_name("hexiao");
            $hexiao_recoed = pdo_get('tg_hexiao', array('oid' => $order['id']));
            if ($hexiao_recoed['createtime'] > TIMESTAMP - $setting['saler_time'] * 24 * 60 * 60) {

                $hexiao_refund['refund'] = 1;
                $hexiao_refund['refund_storeid'] = intval($_GPC['id']);
                $hexiao_refund['refund_openid'] = $hexiao_member['openid'];
                $hexiao_refund['refund_nickname'] = $hexiao_member['nickname'];
                $hexiao_refund['refundtime'] = TIMESTAMP;
                if ($hexiao_recoed) {
                    pdo_update('tg_hexiao', $hexiao_refund, array('oid' => $order['id']));
                } else {
                    if ($order['g_id'] > 0) {
                        pdo_insert('tg_hexiao', $hexiao_refund);
                    } else {
                        $coll = pdo_getall('tg_collect' , array('orderno'=>$order['orderno']));
                        foreach ($coll as $val) {
                            $hexiao_refund['oid'] = intval($order['id']);
                            $hexiao_refund['cid'] = intval($val['id']);
                            $hexiao_refund['gid'] = intval($val['sid']);
                            pdo_insert('tg_hexiao' , $hexiao_refund);
                        }
                    }
                }
            }

            if ($order['g_id'] > 0) {
                $dat['gname'] = $order['goodsname'];
            } else {
                $dat['gname'] = '购物车汇总';
            }
            $dat['createtime'] = TIMESTAMP;
            pdo_insert('tg_ziti_refund', $dat);

            wl_json(1, '退款成功！');
        }
        $url = app_url('order/order/detail', array('id' => $order['id']));
        // hexiao_success($goods['gname'], $order['openid'], $order['gnum'], TIMESTAMP, $url);
        $checkorder = pdo_fetch("select checkstore,status,servestype,dispatchtype from " . tablename('tg_order') . " where orderno='{$orderno}' ");
        if ($order['servestype'] >= 1) {
            wl_json(0, '该订单已申请售后，请勿重复发货！');
        }
        if ($order['status'] == 2 && $order['dispatchtype'] != 1) {
            wl_json(0, '该订单已发货，请勿重复发货！');

        }
        if (!empty($order['checkstore'])) {
            wl_json(0, '该订单已核销完毕，请勿重复核销！');

        }

        if (pdo_update('tg_order', array('status' => 3, 'over_time' => TIMESTAMP, 'is_hexiao' => 2, 'veropenid' => $openid, 'hexiaotime' => TIMESTAMP, 'gettime' => TIMESTAMP, 'checkstore' => $_GPC['id']), array('orderno' => $order['orderno']))) {
            //佣金
            if (intval($order['comtype']) == 0) {
                /**********************/
                //积分
                if ($order['g_id'] > 0) {
                    $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id ", array(":id" => $order['g_id']));
                    $goodsInfo = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$uniacid}' and id = '{$order['g_id']}'");
                    $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid = '{$uniacid}' and openid = '{$order['openid']}'");
                    $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid = '{$uniacid}' and uid = '{$fans['uid']}'");
                    $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
                    pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                }
                if ($order['g_id'] == 0) {
                    $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid = '{$uniacid}' and orderno='{$order['orderno']}'");
                    $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid = '{$uniacid}' and openid = '{$order['openid']}'");
                    $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid = '{$uniacid}' and uid = '{$fans['uid']}'");
                    $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
//                $price1 = 0;
                    foreach ($favoriteqqq as $key => $orderss) {
                        $gs = pdo_fetch("select * from " . tablename('tg_goods') . " where uniacid='{$uniacid}' and id = {$orderss['sid']} ");
                        $is_send[] = $gs;
                        if (!empty($gs['credit']) && $gs['credit'] != 0) {
                            $creditnum += $gs['credit'] * $orderss['num'];
                        }
                        unset($orderss);
                    }
                    pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                }

                //赠送优惠券
                for ($i = 0; $i < count($is_send); $i++) {
                    if ($is_send[$i]["is_sendcoupon"] == 1) {
                        $coupon_id = $is_send[$i]["coupon_id"];
                        //查询优惠券详情
                        $coupon_info = pdo_get("tg_coupon_template", array("id" => $coupon_id));
                        $data_xc["name"] = $coupon_info["name"];
                        $data_xc["coupon_template_id"] = $coupon_info["id"];
                        $data_xc["openid"] = $order["openid"];
                        $data_xc["description"] = $coupon_info["description"];
                        $data_xc["start_time"] = time();
                        $data_xc["end_time"] = time() + 1 * 24 * 60 * 60;
                        $data_xc["at_least"] = $coupon_info["at_least"];
                        $data_xc["uniacid"] = $coupon_info["uniacid"];
                        $data_xc["cash"] = $coupon_info["value"];
                        $data_xc["createtime"] = time();
                        pdo_insert("tg_coupon", $data_xc);
                    }
                }

                //获取当前会员的openid
                //获取当前会员的等级
                $user_info = pdo_fetch("SELECT * FROM " . tablename("tg_member") . " WHERE from_user = :openid AND uniacid = :uniacid", array(":openid" => $order['openid'], ":uniacid" => $order["uniacid"]));
                //查出来会员的总订单金额是多少
                $order_money = pdo_fetch("SELECT sum(price) AS price FROM " . tablename("tg_order") . " WHERE openid = :openid AND uniacid = :uniacid AND status = 3", array(":openid" => $order['openid'], ":uniacid" => $order["uniacid"]));
                $order_price = $order_money["price"];
                //计算出总金额之后然后查看赋予会员的等级是多少
                $auto_level = pdo_fetch("SELECT * FROM " . tablename("tg_member_leave") . " WHERE :order_price >= money AND uniacid = :uniacid ORDER BY money DESC", array(":uniacid" => $order["uniacid"], ":order_price" => $order_price));
                if ($auto_level["id"] == intval($user_info["level"])) {
                    //不做变化
                } elseif ($auto_level["id"] < intval($user_info["level"])) {
                    //不做变化
                } else {
                    //升级会员等级
                    $data["level"] = $auto_level["id"];
                    $res = pdo_update("tg_member", $data, array("from_user" => $order['openid'], "uniacid" => $order["uniacid"]));
                }

            }


            //确认是邻够团  确认是团长 确认状态是1||1

            if ($order["selltype"] == 2 && $order["tuan_first"] == 1 && ($order["status"] == 8 || $order["status"] == 2)) {

                $goodsInfo = goods_get_by_params(' id = ' . $order['g_id']);

                if ($goodsInfo['is_daiqian']) {

                    $tuan_list = pdo_fetchall("SELECT * FROM " . tablename("tg_order") . " WHERE status IN (1,2,8) AND tuan_id = :tuan_id AND tuan_first = 0 ", array(":tuan_id" => $order["tuan_id"]));

                    foreach ($tuan_list as $v) {
                        pdo_update('tg_order', array('status' => 3, 'over_time' => TIMESTAMP), array('id' => $v["id"]));
                        if (intval($v['comtype']) == 0) {
                            /**********************/
                            //积分
                            if ($v['g_id'] > 0) {
                                $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id ", array(":id" => $v['g_id']));
                                $goodsInfo = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$uniacid}' and id = '{$v['g_id']}'");
                                $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid = '{$uniacid}' and openid = '{$v['openid']}'");
                                $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid = '{$uniacid}' and uid = '{$fans['uid']}'");
                                $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
                                pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                            }
                            if ($order['g_id'] == 0) {
                                $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid = '{$uniacid}'   and orderno = '{$v['orderno']}'");
                                $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid = '{$uniacid}' and openid = '{$v['openid']}'");
                                $mencredit = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid = '{$uniacid}' and uid = '{$fans['uid']}'");
                                $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
//                        $price1 = 0;
                                foreach ($favoriteqqq as $key => $orderss) {
                                    $gs = pdo_fetch("select * from " . tablename('tg_goods') . " where uniacid='{$uniacid}' and id = {$orderss['sid']} ");
                                    $is_send[] = $gs;
                                    if (!empty($gs['credit']) && $gs['credit'] != 0) {
                                        $creditnum += $gs['credit'] * $orderss['num'];
                                    }
                                    unset($orderss);
                                }
                                pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
                            }

                            //赠送优惠券
                            for ($i = 0; $i < count($is_send); $i++) {
                                if ($is_send[$i]["is_sendcoupon"] == 1) {
                                    $coupon_id = $is_send[$i]["coupon_id"];
                                    //查询优惠券详情
                                    $coupon_info = pdo_get("tg_coupon_template", array("id" => $coupon_id));
                                    $data_xc["name"] = $coupon_info["name"];
                                    $data_xc["coupon_template_id"] = $coupon_info["id"];
                                    $data_xc["openid"] = $order["openid"];
                                    $data_xc["description"] = $coupon_info["description"];
                                    $data_xc["start_time"] = time();
                                    $data_xc["end_time"] = time() + 1 * 24 * 60 * 60;
                                    $data_xc["at_least"] = $coupon_info["at_least"];
                                    $data_xc["uniacid"] = $coupon_info["uniacid"];
                                    $data_xc["cash"] = $coupon_info["value"];
                                    $data_xc["createtime"] = time();
                                    pdo_insert("tg_coupon", $data_xc);
                                }
                            }

                            //获取当前会员的openid
                            //获取当前会员的等级
                            $user_info = pdo_fetch("SELECT * FROM " . tablename("tg_member") . " WHERE from_user = :openid AND uniacid = :uniacid ", array(":openid" => $v['openid'], ":uniacid" => $v["uniacid"]));
                            //查出来会员的总订单金额是多少
                            $order_money = pdo_fetch("SELECT sum(price) AS price FROM " . tablename("tg_order") . " WHERE openid = :openid AND uniacid = :uniacid AND status = 3", array(":openid" => $v['openid'], ":uniacid" => $v["uniacid"]));
                            $order_price = $order_money["price"];
                            //计算出总金额之后然后查看赋予会员的等级是多少
                            $auto_level = pdo_fetch("SELECT * FROM " . tablename("tg_member_leave") . " WHERE :order_price >= money AND uniacid = :uniacid ORDER BY money DESC", array(":uniacid" => $v["uniacid"], ":order_price" => $order_price));
                            if ($auto_level["id"] == intval($user_info["level"])) {
                                //不做变化
                            } elseif ($auto_level["id"] < intval($user_info["level"])) {
                                //不做变化
                            } else {
                                //升级会员等级
                                $data["level"] = $auto_level["id"];
                                $res = pdo_update("tg_member", $data, array("from_user" => $v['openid'], "uniacid" => $v["uniacid"]));
                            }

                        }

                    }
                }
            }
            $content = "尊敬的" . $order["addname"] . "，您的订单" . $order['orderno'] . "已经核销成功";

            //添加核销记录
            $hexiao['uniacid'] = intval($_W['uniacid']);
            if ($order['dispatchtype'] == 3) {
                $hexiao['sid'] = intval($hexiao_member['id']);
            } else if ($order['dispatchtype'] == 1) {
                $hexiao['did'] = intval($hexiao_member['id']);
            }
            $hexiao['storeid'] = intval($_GPC['id']);
            $hexiao['openid'] = $hexiao_member['openid'];
            $hexiao['nickname'] = $hexiao_member['nickname'];
            $hexiao['oid'] = intval($order['id']);

            if ($order['g_id'] > 0) {
                $goodsname = $order["goodsname"];

                $hexiao['gid'] = intval($order['g_id']);
                if ($order['com_type'] == 3) {
                    $hexiao['commissiontype'] = intval($order['commissiontype']);
                    $hexiao['commission'] = floatval($order['commission']);
                    if ($order['commissiontype'] == 1) {
                        $hexiao['price'] = (floatval($order['price']) - floatval($order['cost_fee']) - floatval($order['freight'])) * floatval($order['commission']) * 0.01;
                    } else if ($order['commissiontype'] == 2) {
                        $hexiao['price'] = floatval($order['commission']) * intval($order['gnum']);
                    }
                    $hexiao['status'] = 1;
                }
                $hexiao['createtime'] = TIMESTAMP;
                pdo_insert('tg_hexiao', $hexiao);

            } else {
                $goodsname = "多商品";
                $collect = pdo_getall('tg_collect', array('orderno' => $order['orderno']));
                $remark = '商品明细：';

                foreach ($collect as $it) {
                    $remark .= "【" . $it['goodsname'] . " * " . $it['num'] . "】";

                    $hexiao['cid'] = intval($it['id']);
                    $hexiao['gid'] = intval($it['sid']);
                    if ($it['com_type'] == 3) {
                        $hexiao['commissiontype'] = intval($it['type']);
                        $hexiao['commission'] = floatval($it['commission']);
                        if ($it['type'] == 1) {
                            $hexiao['price'] = floatval($it['oprice']) * floatval($it['commission']) * intval($it['num']) * 0.01;
                        } else if ($it['type'] == 2) {
                            $hexiao['price'] = floatval($it['commission']) * intval($it['num']);
                        }
                        $hexiao['status'] = 1;
                    }
                    $hexiao['createtime'] = TIMESTAMP;
                    pdo_insert('tg_hexiao', $hexiao);

                }
            }

            $goodsum = $order['gnum'] . ' ' . $order['item'];
            $time = date("Y-m-d H:i:s") . "核销员:" . $hexiao_member['nickname'];
            $remaark = "";
            hex($order["openid"], $content, $goodsname, $goodsum, $time, $remark, $url);
            //file_put_contents(TG_DATA . "hx.log", var_export(date("Y-m-d H:i:s") . "是否是核销员" . json_encode($res), true) . PHP_EOL, FILE_APPEND);
            hex($hexiao_member['openid'], $content, $goodsname, $goodsum, $time, $remark, '');

            /*
             *  核销自动打印
             */
            $print = pdo_fetch("select * from " . tablename('tg_print') . " where uniacid = '{$uniacid}' and merchant_id = '{$order['merchantid']}' and status = 1 and hexiao_print = 1");
            if (!empty($print)) {
                hexiao_print($order['id']);
            }
            // 次卡
            if ($order['is_once_card'] == 1 && (int)$order['once_card_num'] > (int)$order['once_card_ynum'] ) {
                // 配送时间

                if ($order['dispatchtype'] == 1) { // 1送货上门
                    pdo_fetch("UPDATE " . tablename("tg_order_once_card_record") .
                        " SET sign_date = :sign_date WHERE orderid = :orderid ORDER BY id DESC limit 1",
                        array(":sign_date" => TIMESTAMP, ":orderid" => $order['id']));
                } elseif ($order['dispatchtype'] == 3) { // 3自提,未达到核销数
                    switch ($order['once_card_json']) {
                        case 'once_card_mon':
                            $dat_send_date = '星期一';
                            break;
                        case 'once_card_tues':
                            $dat_send_date = '星期二';
                            break;
                        case 'once_card_wed':
                            $dat_send_date = '星期三';
                            break;
                        case 'once_card_thur':
                            $dat_send_date = '星期四';
                            break;
                        case 'once_card_fir':
                            $dat_send_date = '星期五';
                            break;
                        case 'once_card_sat':
                            $dat_send_date = '星期六';
                            break;
                        case 'once_card_sun':
                            $dat_send_date = '星期天';
                            break;
                        case 'once_card_half_month ':
                            $dat_send_date = '半个月';
                            break;
                        case 'once_card_month':
                            $dat_send_date = '1个月';
                            break;
                        default:
                            $dat_send_date = '未选择';
                            break;
                    }
                    $dat = array(
                        'orderid' => $order['id'],
                        'orderno' => $order['orderno'],
                        'orderno_child' => date('Ymd') . mt_rand(100, 999),
                        'openid' => $order['openid'],
                        'order_status' => $order['dispatchtype'],
                        'send_date' => $dat_send_date,
                        'delivery_date' => TIMESTAMP,
                        'sign_date' => TIMESTAMP,
                        'hexiao_date' =>TIMESTAMP,
                        'veropenid' => $openid,
                        'hexiao_man' => pdo_get('tg_member', array('openid' => $openid), array('nickname'))['nickname'],
                        'mobile' => $order['mobile'],
                        'province' => $order['province'],
                        'city' => $order['city'],
                        'county' => $order['county'],
                        'detailed_address' => $order['detailed_address'],
                        'comadd' => $order['comadd'],
                        'checkstore' => $_GPC['id'],
                        'ziti_address' => pdo_get('tg_store', array('id' => $_GPC['id']), array('storename'))['storename'],
                        'uniacid' => $_W['uniacid'],
                    );
                    pdo_insert('tg_order_once_card_record', $dat);
                }else { // 2快递(不会有快递进来)
                    wl_json(0,'快递');
                }
                if((int)$order['once_card_ynum']+1 < (int)$order['once_card_num']){
                    pdo_update('tg_order', array('status' => 8, 'delivery_time' => 0, 'gettime' => 0, 'checkstore' => NULL, 'once_card_ynum' => $order['once_card_ynum'] + 1,'veropenid'=>'','over_time'=>''), array('id' => $order['id']));

                }else{
                    pdo_update('tg_order', array('once_card_ynum'=>$order['once_card_ynum']+1), array('id' => $order['id']));
                }
            }

        } else {
            wl_json(0, '核销失败，请重试！');
        }
    }
    wl_json(1);

}
include wl_template('order/check');
exit();
