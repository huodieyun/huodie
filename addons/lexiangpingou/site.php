<?php
defined('IN_IA') or exit('Access Denied');

require IA_ROOT . '/addons/lexiangpingou/core/common/defines.php';
require TG_CORE . 'class/wlloader.class.php';
wl_load()->func('global');
wl_load()->func('pdo');
wl_load()->func('tpl');
wl_load()->func('message');
wl_load()->func('print');

class lexiangpingouModuleSite extends WeModuleSite
{

    public function __call($name, $arguments)
    {
        global $_W;

        $isWeb = stripos($name, 'doWeb') === 0;
        $isMobile = stripos($name, 'doMobile') === 0;
        if ($isWeb || $isMobile) {
            $dir = IA_ROOT . '/addons/' . $this->modulename . '/';
            if ($isWeb) {
                $dir .= 'web/';
                $controller = strtolower(substr($name, 5));
            }
            if ($isMobile) {
                $dir .= 'app/';
                $controller = strtolower(substr($name, 8));
            }
            $file = $dir . 'index.php';
            if (file_exists($file)) {
                require $file;
                exit;
            }
        }
        trigger_error("访问的方法 {$name} 不存在.", E_USER_WARNING);
        return null;
    }

    public function do_mencrypt($input, $key)
    {
        $input = str_replace("n", "", $input);
        $input = str_replace("t", "", $input);
        $input = str_replace("r", "", $input);
        $key = substr(md5($key), 0, 24);
        $td = mcrypt_module_open('tripledes', '', 'ecb', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $encrypted_data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return trim(chop(base64_encode($encrypted_data)));
    }


    /*支付结果返回*/
    public function payResult($params)
    {
        global $_W, $_GPC;
        wl_load()->model('goods');
        wl_load()->model('merchant');
        wl_load()->model('order');
        wl_load()->model('group');
        load()->model('mc');
        load()->model('account');
        $sleeptime = rand(0.01, 0.5);
        sleep($sleeptime);
        /*写入文件*/
        $pay_log = $params;
        $pay_log['createtime'] = date("Y-m-d H:i:s", TIMESTAMP);
        //file_put_contents(TG_DATA."payresult.log", var_export($pay_log, true).PHP_EOL, FILE_APPEND);
        $success = FALSE;
        //多商户购物车
        if (checkstr($params['tid'], 'Ms')) {

            if ($params['result'] == 'success' && ($params['from'] == 'notify' || $params['type'] == 'balance_payment')) {
                $acct = pdo_fetch("select * from " . tablename('account_wechats') . " where  uniacid =:uniacid", array(':uniacid' => $_W['uniacid']));
                if ($acct['vip'] != 1 && $acct['ordernum'] > 0) {
                    pdo_update('account_wechats', array('ordernum' => $acct['ordernum'] - 1), array('uniacid' => $_W['uniacid']));
                }
                if ($params['is_usecard'] == 1) {
                    $fee = $params['card_fee'];
                    $data['is_usecard'] = 1;
                } else {
                    $fee = $params['fee'];
                }
                $li = pdo_fetch('select * from ' . tablename('tg_merchant_order') . ' where uniacid=:uniacid and orderno=:orderno', array(':uniacid' => $_W['uniacid'], ':orderno' => $params['tid']));
                pdo_update('tg_merchant_order', array('status' => 1, 'transid' => $params['tag']['transaction_id'], 'price' => $fee), array('uniacid' => $_W['uniacid'], 'orderno' => $params['tid']));//2->8
                $order_list = explode("|", $li['list']);
                //  file_put_contents(TG_DATA."test.log", var_export($li['list'], true).PHP_EOL, FILE_APPEND);
                foreach ($order_list as $tt) {
                    //   file_put_contents(TG_DATA."test.log", var_export($tt, true).PHP_EOL, FILE_APPEND);

                    $order_out = pdo_fetch('select * from ' . tablename('tg_order') . ' where uniacid=:uniacid and orderno=:orderno', array(':uniacid' => $_W['uniacid'], ':orderno' => $tt));
                    tuan_print($order_out['id']);
                    $url = app_url('order/order/detail', array('id' => $order_out['id']));
                    if ($order_out['dispatchtype'] == 3) {
                        $url = app_url('order/order/detail', array('id' => $order_out['id'], 'b' => 1));
                    }
                    $pay_type = 2;
                    if ($params['type'] == 'balance_payment') {
                        $pay_type = 9;
                    }
                    pdo_update('tg_order', array('status' => 8, 'pay_type' => $pay_type, 'transid' => $params['tag']['transaction_id'], 'ptime' => TIMESTAMP, 'price' => $order_out['pay_price']), array('orderno' => $order_out['orderno']));//2->8
                    pay_onesuccess($order_out['orderno'], $order_out['openid'], $order_out['pay_price'], TIMESTAMP, $url);
                    $sende = "select  * from " . tablename('tg_collect') . " where   orderno= '" . $order_out['orderno'] . "'  ";
                    $sendelist = pdo_fetchall($sende);
                    foreach ($sendelist as $key => $value) {
                        $goodsInfos = pdo_fetch("select id,gnum,salenum from" . tablename('tg_goods') . " where id=" . $value['sid']);
                        pdo_update('tg_goods', array('gnum' => $goodsInfos['gnum'] - $value['num'], 'salenum' => $goodsInfos['salenum'] + $value['num']), array('id' => $value['sid']));

                        /*增加历史购买数量*/
                        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $value['uniacid'], ':openid' => $value['openid'], ':g_id' => $value['sid']));
                        if (empty($old_data)) {
                            $logdata = array(
                                'g_id' => $value['sid'],
                                'openid' => $value['openid'],
                                'num' => $value['num'],
                                'uniacid' => $value['uniacid']
                            );
                            pdo_insert('tg_goods_openid', $logdata);
                        } else {
                            pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $value['num']), array('id' => $old_data['id']));

                        }

                        /*增加历史购买数量*/

                        /*更改规格库存*/
                        if (!empty($value['item'])) {
                            $stocks = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $value['sid'], ':title' => $value['item']));
                            pdo_update('tg_goods_option', array('stock' => $stocks['stock'] - $value['num']), array('goodsid' => $value['sid'], 'title' => $value['item']));
                        }
                    }
                }
            }
            if ($params['result'] == 'success' && $params['from'] == 'return') {
                echo "<script>  location.href='" . app_url('pay/ms_success', array('orderno' => $params['tid'])) . "';</script>";
                exit;
            }

        } else {
            $order_out = order_get_by_params(" orderno = '{$params['tid']}'");

            $goodsInfo = goods_get_by_params(" id = {$order_out['g_id']}");


            $nowtuan = group_get_by_params(" groupnumber = '{$order_out['tuan_id']}'");

            $merchan = merchant_get_by_params(" id = {$order_out['merchantid']}");
            if (empty($order_out['status'])) {
                $data = array('status' => $params['result'] == 'success' ? 1 : 0);
                if ($params['is_usecard'] == 1) {
                    $fee = $params['card_fee'];
                    $data['is_usecard'] = 1;
                } else {
                    $fee = $params['fee'];
                }
                $pay_type = 2;
                if ($params['type'] == 'balance_payment') {
                    $pay_type = 9;
                }
                $paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 2, 'delivery' => 3 , 'balance_payment' => 9);
                $data['pay_type'] = $pay_type;
                $data['transid'] = $params['tag']['transaction_id'];
                $data['ptime'] = TIMESTAMP;
                $data['price'] = $fee;
                $data['starttime'] = TIMESTAMP;
                if (!empty($nowtuan)) {
                    if ($nowtuan['lacknum'] == 0) {
                        $success = TRUE;
                    }
                }
                /*后台通知，修改状态*/
                if ($params['result'] == 'success' && ($params['from'] == 'notify' || $params['type'] == 'balance_payment')) {
                    /*按订单量使用 减少订单量*/
                    $acct = pdo_fetch("select * from " . tablename('account_wechats') . " where  uniacid =:uniacid", array(':uniacid' => $_W['uniacid']));
                    if ($acct['vip'] != 1 && $acct['ordernum'] > 0) {
                        pdo_update('account_wechats', array('ordernum' => $acct['ordernum'] - 1), array('uniacid' => $_W['uniacid']));
                    }
                    /*按订单量使用 减少订单量*/
                    //订单通知店主

                    if (!empty($goodsInfo['openid'])) {
//                        file_put_contents(TG_DATA.$_W['uniacid']."shopmessage.log", var_export($goodsInfo['openid'], true).PHP_EOL, FILE_APPEND);
                        $member = pdo_fetch('select nickname from ' . tablename('tg_member') . ' where openid=:openid ', array(':openid' => $order_out['openid']));
                        $type = '';
                        if ($order_out['is_tuan'] == 1) {
                            $type = '拼团订单';
                        } else {
                            $type = '购物车订单';
                        }
                        $notice_message = '下单人：【' . $member['nickname'] . '】订单金额：【 ' . $order_out['pay_price'] . '】';
                        result_type($goodsInfo['openid'], '您有一笔' . $order_out['goodsname'] . '的新订单', $type, $notice_message, '');

                    }
                    if (!empty($order_out['master_orderno'])) {
                        //补款程序
                        $data['status'] = 3;
                        $master_order = order_get_by_params(" orderno = '{$order_out['master_orderno']}'");

                        pdo_update('tg_order', array('status' => 8, 'bukuanstatus' => 2), array('orderno' => $order_out['master_orderno']));
                        pdo_update('tg_order', $data, array('orderno' => $order_out['orderno']));
                        $url = app_url('order/order/detail', array('id' => $master_order['id']));
                        result_type($master_order['openid'], $order_out['master_orderno'] . '补款', $order_out['master_orderno'] . '尾款已补，我们将尽快发货', '', $url);
                        tuan_print($master_order['id']);


                    } else {
                        //file_put_contents(TG_DATA."params.log", var_export($pay_log, true).PHP_EOL, FILE_APPEND);


                        /*按订单量使用 减少订单量*/
                        /*增加预售团 参与人数*/
                        if ($nowtuan['selltype'] == 7) {
                            pdo_update('tg_group', array('nownum' => $nowtuan['nownum'] + $order_out['gnum']), array('groupnumber' => $nowtuan['groupnumber']));
                        }

                        /*增加预售团 参与人数*/
                        /*处理优惠券*/
                        $is_usecard = $order_out['is_usecard'];
                        if ($is_usecard == 1) {
                            wl_load()->model('activity');
                            $coupon_id = $order_out['couponid'];
                            pdo_update('tg_coupon', array('use_time' => $params['paytime']), array('coupon_template_id' => $coupon_id));
                            coupon_quantity_issue_increase($coupon_id, 1);
                        }
                        /*处理代付*/
                        if ($order_out['openid'] != $params['user']) {
                            pdo_update('tg_order', array('ordertype' => 1, 'helpbuy_opneid' => $params['user']), array('orderno' => $params['tid']));
                            $time = date("Y-m-d H:i:s", $params['paytime']);
                            $url = app_url('order/order/detail', array('id' => $order_out['id']));
                            daipay_success($order_out['openid'], $fee, $order_out['othername'], $params['tid'], $goodsInfo['gname'], $time, $order_out['message'], $url);
                            daipay_success($params['user'], $fee, $order_out['othername'], $params['tid'], $goodsInfo['gname'], $time, $order_out['message'], $url);
                        }
                        if ($order_out['couponid'] > 0) {
                            pdo_update('tg_coupon', array('uid' => 2, 'use_time' => TIMESTAMP), array('id' => $order_out['couponid']));
                        }
                        /*更新订单状态*/
                        pdo_update('tg_order', $data, array('orderno' => $params['tid']));

                        if (!$success) {
                            /*支付成功通知*/
                            $url = app_url('order/group', array('tuan_id' => $order_out['tuan_id']));
                            if ($order_out['is_tuan'] == 0) {

                                tuan_print($order_out['id']);
                                $url = app_url('order/order/detail', array('id' => $order_out['id']));
                                if ($order_out['dispatchtype'] == 3) {
                                    $url = app_url('order/order/detail', array('id' => $order_out['id'], 'b' => 1));
                                }
                                pdo_update('tg_order', array('status' => 8), array('orderno' => $params['tid']));//2->8
                                pay_onesuccess($order_out['orderno'], $order_out['openid'], $fee, TIMESTAMP, $url);
                                /*更改库存*/
                                if ($order_out['g_id'] > 0) {
                                    if ($goodsInfo['gnum'] >= $order_out['gnum']) {
                                        goods_update_by_params(array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
                                    } elseif (!empty($goodsInfo['gnum'])) {
                                        goods_update_by_params(array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
                                    }
                                    //极限单品减库存加销量
                                    if ($goodsInfo['supply_goodsid'] > 0) {
                                        $go = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $goodsInfo['supply_goodsid']);
                                        if ($go['stock'] >= $order_out['gnum']) {
                                            pdo_update('tg_supply_goods', array('stock' => $go['stock'] - $order_out['gnum'], 'salenum' => $go['salenum'] + $order_out['gnum']), array('id' => $goodsInfo['supply_goodsid']));
                                        }
                                        if (!empty($go['optionname'])) {
                                            $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $goodsInfo['supply_goodsid'], ':title' => $order_out['optionname']));
                                            pdo_update('tg_goods_supply_option', array('stock' => $option['stock'] - $order_out['gnum']), array('goodsid' => $goodsInfo['supply_goodsid'], 'title' => $order_out['optionname']));
                                        }
                                    }
                                    /*增加历史购买数量*/
                                    $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $order_out['uniacid'], ':openid' => $order_out['openid'], ':g_id' => $order_out['g_id']));
                                    if (empty($old_data)) {
                                        $logdata = array(
                                            'g_id' => $order_out['g_id'],
                                            'openid' => $order_out['openid'],
                                            'num' => $order_out['gnum'],
                                            'uniacid' => $order_out['uniacid']
                                        );
                                        pdo_insert('tg_goods_openid', $logdata);
                                    } else {
                                        pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $order_out['gnum']), array('id' => $old_data['id']));

                                    }

                                    /*增加历史购买数量*/
                                    /*更改规格库存*/
                                    if (!empty($order_out['optionname'])) {
                                        $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $order_out['g_id'], ':title' => $order_out['optionname']));
                                        pdo_update('tg_goods_option', array('stock' => $stock['stock'] - $order_out['gnum']), array('goodsid' => $order_out['g_id'], 'title' => $order_out['optionname']));
                                    }

                                    //更新门店库存
                                    if($goodsInfo['has_store_stock'] == 1){

                                        if (!empty($order_out['optionname'])) {
                                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $order_out['g_id'], ':storeid' => $order_out['comadd'],':optionid'=>$order_out['optionid'],':uniacid'=>$order_out['uniacid']));
                                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order_out['gnum']),array('id'=>$store_stock['id']));
                                        }else{
                                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $order_out['g_id'], ':storeid' => $order_out['comadd'],':uniacid'=>$order_out['uniacid']));
                                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order_out['gnum']),array('id'=>$store_stock['id']));
                                        }


                                    }


                                } else {

                                    $sende = "select  * from " . tablename('tg_collect') . " where   orderno= '" . $order_out['orderno'] . "'  ";
                                    $sendelist = pdo_fetchall($sende);
                                    foreach ($sendelist as $key => $value) {
                                        $goodsInfos = pdo_fetch("select id,gnum,salenum,openid from" . tablename('tg_goods') . " where id=" . $value['sid']);
                                        pdo_update('tg_goods', array('gnum' => $goodsInfos['gnum'] - $value['num'], 'salenum' => $goodsInfos['salenum'] + $value['num']), array('id' => $value['sid']));

                                        /*增加历史购买数量*/
                                        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $value['uniacid'], ':openid' => $value['openid'], ':g_id' => $value['sid']));
                                        if (empty($old_data)) {
                                            $logdata = array(
                                                'g_id' => $value['sid'],
                                                'openid' => $value['openid'],
                                                'num' => $value['num'],
                                                'uniacid' => $value['uniacid']
                                            );
                                            pdo_insert('tg_goods_openid', $logdata);
                                        } else {
                                            pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $value['num']), array('id' => $old_data['id']));

                                        }

                                        if (!empty($goodsInfos['openid'])) {
//                                            file_put_contents(TG_DATA.$_W['uniacid']."shopmessage.log", var_export($goodsInfos['openid'], true).PHP_EOL, FILE_APPEND);
                                            $member = pdo_fetch('select nickname from ' . tablename('tg_member') . ' where openid=:openid ', array(':openid' => $order_out['openid']));
                                            $type = '';
                                            if ($order_out['is_tuan'] == 1) {
                                                $type = '拼团订单';
                                            } else {
                                                $type = '购物车订单';
                                            }
                                            $notice_message = '下单人：【' . $member['nickname'] . '】订单金额：【 ' . $order_out['pay_price'] . '】';
                                            result_type($goodsInfos['openid'], '您有一笔' . $order_out['goodsname'] . '的新订单', $type, $notice_message, '');

                                        }
                                        /*增加历史购买数量*/

                                        /*更改规格库存*/
                                        if (!empty($value['item'])) {
                                            $stocks = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $value['sid'], ':title' => $value['item']));
                                            pdo_update('tg_goods_option', array('stock' => $stocks['stock'] - $value['num']), array('goodsid' => $value['sid'], 'title' => $value['item']));

                                        }
//                                        //更新门店库存
//                                        if($goodsInfo['has_store_stock'] == 1){
//                                            if (!empty($order_out['optionname'])) {
//                                                $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $order_out['g_id'], ':storeid' => $order_out['comadd'],':optionid'=>$order_out['optionid'],':uniacid'=>$order_out['uniacid']));
//                                                pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order_out['gnum']),array('id'=>$store_stock['id']));
//                                            }else{
//                                                $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and uniacid=:uniacid", array(':goodsid' => $order_out['g_id'], ':storeid' => $order_out['comadd'],':uniacid'=>$order_out['uniacid']));
//                                                pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order_out['gnum']),array('id'=>$store_stock['id']));
//                                            }
//
//
//                                        }
                                    }

                                }
                            } else {
                                /*增加历史购买数量*/
                                $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $order_out['uniacid'], ':openid' => $order_out['openid'], ':g_id' => $order_out['g_id']));
//                                if ($order_out['mobile'] != '虚拟') {
                                if (empty($old_data)) {
                                    $logdata = array(
                                        'g_id' => $order_out['g_id'],
                                        'openid' => $order_out['openid'],
                                        'num' => $order_out['gnum'],
                                        'uniacid' => $order_out['uniacid']
                                    );
                                    pdo_insert('tg_goods_openid', $logdata);
                                } else {
                                    pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $order_out['gnum']), array('id' => $old_data['id']));

                                }
                                /*更改规格库存*/
                                if (!empty($order_out['optionname'])) {
                                    $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $order_out['g_id'], ':title' => $order_out['optionname']));
//                                    pdo_update('tg_goods_option', array('stock' => $stock['stock'] - $order_out['gnum']), array('goodsid' => $order_out['g_id'], 'title' => $order_out['optionname']));
                                    //更新门店库存
//                                    if($goodsInfo['has_store_stock'] == 1){
//                                        if (!empty($order_out['optionname'])) {
//                                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $order_out['g_id'], ':storeid' => $order_out['comadd'],':optionid'=>$order_out['optionid'],':uniacid'=>$order_out['uniacid']));
//                                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order_out['gnum']),array('id'=>$store_stock['id']));
//                                        }else{
//                                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and uniacid=:uniacid", array(':goodsid' => $order_out['g_id'], ':storeid' => $order_out['comadd'],':uniacid'=>$order_out['uniacid']));
//                                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $order_out['gnum']),array('id'=>$store_stock['id']));
//                                        }
//
//
//                                    }
                                }
//                                }
                                /*增加历史购买数量*/
                                if (intval($nowtuan['lacknum']) > 0) {
                                    $g = goods_get_by_params(" id = {$nowtuan['goodsid']}");
                                    if ($g['is_amount'] == 1 && ($g['selltype'] == 4 || $g['selltype'] == 7)) {
                                        pdo_update('tg_group', array('lacknum' => intval($nowtuan['lacknum']) - intval($order_out['gnum'])), array('groupnumber' => intval($order_out['tuan_id'])));
                                    } else {
                                        pdo_update('tg_group', array('lacknum' => intval($nowtuan['lacknum']) - 1), array('groupnumber' => intval($order_out['tuan_id'])));
                                    }
                                }
                                $nowtuan = group_get_by_params(" groupnumber = '{$order_out['tuan_id']}'");
                                if ($order_out['tuan_first'] == 1) {
                                    $content = "恭喜您荣升团长，点击详情【分享朋友圈】，邀请小伙伴来参团，成团率99%的哟！";
                                } else {
                                    if ($nowtuan['lacknum'] == 0) {
                                        $content = "恭喜您组团成功，我们将尽快为您发货,请注意查收";
                                    } else {
                                        $content = "恭喜您支付成功，还差{$nowtuan['lacknum']}个小伙伴,组团成功才会享受优惠哦";
                                    }
                                }
                                if ($order_out['selltype'] == 7) {
                                    $url = app_url('goods/detail', array('id' => $order_out['g_id']));
                                    result_type($order_out['openid'], '支付成功', '请等待组团成功，补齐尾款', '', $url);

                                } else {
                                    pay_success($order_out['orderno'], $order_out['openid'], $nowtuan['neednum'], $fee, $goodsInfo['gname'], $url, $content);

                                }
                            }


                            /*更改商家销售量*/
                            $merchant = merchant_get_by_params(" id = '{$goodsInfo['merchantid']}' ");
                            if ($merchant) {
                                merchant_update_amount($fee, $merchant['id']);
                                merchant_update_by_params(array('salenum' => $merchant['salenum'] + $order_out['gnum']), array('id' => $merchant['id']));
                            }
                            $now = group_get_by_params(" groupnumber = '{$order_out['tuan_id']}'");
                            if (!empty($now) && $now['lacknum'] == 0) {

                                /*团成功通知*/
                                $url = app_url('order/group', array('tuan_id' => $order_out['tuan_id']));
                                if ($order_out['dispatchtype'] == 3) {
                                    $url = app_url('order/order/detail', array('id' => $order_out['id'], 'b' => 1));
                                }

                                group_update_by_params(array('groupstatus' => 2, 'successtime' => TIMESTAMP), array('groupnumber' => $now['groupnumber']));
                                order_update_by_params(array('status' => 8, 'successtime' => TIMESTAMP), array('tuan_id' => $now['groupnumber'], 'status' => 1));//2->8
                                group_success($order_out['tuan_id'], $url);
                                $all = pdo_fetchall("select * from " . tablename('tg_order') . " where tuan_id='{$order_out['tuan_id']}' and status in (3,8,1,7,10,6)");
                                $g = pdo_fetch("select is_amount,selltype from " . tablename('tg_goods') . " where id = '{$now['goodsid']}' ");
                                if ($g['is_amount'] == 1 && ($g['selltype'] == 4 || $g['selltype'] == 7)) {
                                    $allnum = pdo_fetchcolumn("select SUM(gnum) from " . tablename('tg_order') . " where tuan_id='{$order_out['tuan_id']}' and status in (3,8,1,7,10,6)");
                                } else {
                                    $allnum = count($all);
                                }
                                //如果是阶梯团，插入退款记录
                                if ($nowtuan['selltype'] == 4) {

                                    $param_level = unserialize($nowtuan['group_level']);
                                    $mininum = $param_level[0]['groupnum'];
                                    $miniprice = 0;
                                    $maxnum = 0;
                                    $maxprice = 0;
                                    for ($i = 0; $i < count($param_level); $i++) {


                                        for ($j = 0; $j < count($param_level) - $i - 1; $j++) {
                                            if ($param_level[$j]['groupnum'] < $param_level[$j + 1]['groupnum']) {
                                                $temp = $param_level[$j];
                                                $param_level[$j] = $param_level[$j + 1];
                                                $param_level[$j + 1] = $temp;
                                            }
                                            if ($param_level[$j]['groupnum'] >= $maxnum) {
                                                $maxnum = $param_level[$j]['groupnum'];
                                                $maxprice = $param_level[$j]['groupprice'];//人数最多时  对应的设定价格
                                            }
                                            if ($param_level[$j]['groupnum'] <= $mininum) {
                                                $mininum = $param_level[$j]['groupnum'];
                                                $miniprice = $param_level[$j]['groupprice'];//人数最少时  对应的设定价格
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
                                        $groupprice = $param_level[$i]['groupprice'];
                                    }
//                                    if ($allnum >= $maxnum) {
//                                        $tempprice = $maxprice;
//                                    }
//                                    if ($allnum < $mininum) {
//                                        $tempprice = $nowtuan['price'];
//                                    }
//                                    $refundprice = round($nowtuan['price'], 2) - round($tempprice, 2);

                                }
                                foreach ($all as $row) {
                                    if ($row['status'] == 8) {
                                        tuan_print($row['id']);
                                    }
                                    /*更改库存*/
                                    $goodsInfo = goods_get_by_params(" id = {$row['g_id']}");
                                    //插入退款记录
                                    if ($nowtuan['selltype'] == 4) {
                                        $refundprice = round((round($row['price'], 2) - round($tempprice, 2) * $row['gnum']),2) - round($row['freight'],2);
                                        //优惠券折扣
                                        if($row['couponid']){
                                            $coupon = pdo_fetch("SELECT cash FROM cm_tg_coupon WHERE id={$row['couponid']}");
                                            $refundprice = round($coupon['cash'],2)+round((round($row['price'], 2) - round($tempprice, 2) * $row['gnum']),2) - round($row['freight'],2);
                                        }
//                                        $refundprice = round($row['price'], 2) - round($tempprice, 2) -  round($row['freight'], 2);
                                        if ($refundprice > 0 && $refundprice != $row['price'] && $row['mobile'] <> '虚拟' && $row['status'] <> 7 && $row['status'] <> 10) {
                                            $data1 = array('orderno' => $row['orderno'], 'status' => 1, 'refundprice' => $refundprice);
                                            pdo_insert('tg_order_level_refund', $data1);
                                        }
                                    }
                                    if ($row['mobile'] <> '虚拟') {
//                                        file_put_contents(TG_DATA . "sell.log", var_export(array('no' => $row['orderno'] , 'gnum' =>$goodsInfo['gnum'] ), true) . PHP_EOL, FILE_APPEND);
                                        if ($goodsInfo['gnum'] >= $row['gnum']) {
                                            goods_update_by_params(array('gnum' => $goodsInfo['gnum'] - $row['gnum'], 'salenum' => $goodsInfo['salenum'] + $row['gnum']), array('id' => $row['g_id']));
                                        } elseif (!empty($goodsInfo['gnum'])) {
                                            goods_update_by_params(array('gnum' => $goodsInfo['gnum'] - $row['gnum'], 'salenum' => $goodsInfo['salenum'] + $row['gnum']), array('id' => $row['g_id']));
                                        }
                                        //更新门店库存
                                        $row_goods = goods_get_by_params(" id = {$row['g_id']}");
                                        if($row_goods['has_store_stock'] == 1){
                                            if (!empty($row['optionname'])) {
                                                $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $row['g_id'], ':storeid' => $row['comadd'],':optionid'=>$row['optionid'],':uniacid'=>$row['uniacid']));
                                                pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $row['gnum']),array('id'=>$store_stock['id']));
                                            }else{
                                                $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $row['g_id'], ':storeid' => $row['comadd'],':uniacid'=>$row['uniacid']));
                                                pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] - $row['gnum']),array('id'=>$store_stock['id']));
                                            }


                                        }

                                        //极限单品减库存加销量
                                        if ($goodsInfo['supply_goodsid'] > 0) {
                                            $go = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $goodsInfo['supply_goodsid']);
                                            if ($go['stock'] >= $order_out['gnum']) {
                                                pdo_update('tg_supply_goods', array('salenum' => $go['salenum'] + $row['gnum'],'stock' => $go['stock'] - $row['gnum']), array('id' => $goodsInfo['supply_goodsid']));
                                            }
                                            if (!empty($go['optionname'])) {
                                                $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $goodsInfo['supply_goodsid'], ':title' => $row['optionname']));
                                                pdo_update('tg_goods_supply_option', array('stock' => $option['stock'] - $row['gnum']), array('goodsid' => $goodsInfo['supply_goodsid'], 'title' => $row['optionname']));
                                            }
                                        }

                                    }
                                }
                            }
                        } else {
                            order_update_by_params(array('status' => 10, 'is_tuan' => 2), array('orderno' => $params['tid']));
                        }
//                       
                    }
                }

            }
            /*前台通知*/
            if ($params['result'] == 'success' && $params['from'] == 'return') {
                if (!empty($order_out['master_orderno'])) {
                    //https://lx.huodiesoft.com/app/index.php?i=826&c=entry&m=lexiangpingou&do=goods&ac=frame&url=//www.lexiangpingou.cn/app/index.php?i=33&c=entry&eid=379&wxref=mp.weixin.qq.com
                    echo "<script>  location.href='" . app_url('pay/success', array('orderno' => $order_out['orderno'])) . "';</script>";
                    exit;
                    //补款成功 页面
                }
                if ($nowtuan['selltype'] == 7) {
                    echo "<script>  location.href='" . app_url('goods/detail', array('id' => $nowtuan['goodsid'])) . "';</script>";
                    exit;
                }
                if ($order_out['is_tuan'] == 2) {
                    echo "<script>location.href='" . app_url('pay/success', array('orderno' => $order_out['orderno'], 'errno' => 2)) . "';</script>";
                    exit;
                } elseif ($order_out['is_tuan'] == 1) {
                    /*
                    if($_W['uniacid']==33)
                    {
                        $aurl=base64_encode(app_url('order/group', array('tuan_id' => $order_out['tuan_id'])));
                        echo "<script>  location.href='http://lx.huodiesoft.com/app/index.php?i=826&c=entry&m=lexiangpingou&do=goods&ac=frame&url=" .$aurl . "';</script>";
                        exit ;
                    }else{
                        echo "<script>  location.href='" .app_url('order/group', array('tuan_id' => $order_out['tuan_id'])) . "';</script>";
                        exit ;
                    }
                    */
                    echo "<script>  location.href='" . app_url('order/group', array('tuan_id' => $order_out['tuan_id'])) . "';</script>";
                    exit;

                } elseif ($order_out['is_tuan'] == 0) {
                    echo "<script>  location.href='" . app_url('pay/success', array('orderno' => $order_out['orderno'])) . "';</script>";
                    exit;
                }
            }
        }

    }
}
