<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * order.ctrl
 * 订单控制器
 */


defined('IN_IA') or exit('Access Denied');
wl_load()->model('order');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('functions');
wl_load()->classs('qrcode');
wl_load()->func('message');
$banquanfunction = checkfunc(8170);


$op = !empty($_GPC['op']) ? $_GPC['op'] : 'list';
$pagetitle = '我的订单';
if ($ip != '112.16.53.66') {
    if (empty($openid)) {
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
}
if ($op == 'list') {
    $status = ($_GPC['status'] != '') ? $_GPC['status'] : '';

    include wl_template('order/order_list');
}
if($op == 'childlist'){
    $oderno = $_GPC['orderno'];//主订单号
    $c_order = pdo_fetchall("SELECT * FROM ".tablename('tg_order_child')." where orderno=".$oderno.' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");//子订单列表
    $order = pdo_fetch("SELECT * FROM ".tablename('tg_order')." where orderno=".$oderno.' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");
    if($order['merchantid']==0){
        $merchants = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = '{$_W['uniacid']}'");
        $merchantname = $merchants['name'];
    }else{
        $merchants = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' AND id='{$order['merchantid']}'  ORDER BY id DESC");
        $merchantname = $merchants['name'];
    }

    foreach ($c_order as $key=>$val){
        $goods = pdo_fetchall("SELECT * FROM ".tablename('tg_child_collect')." where orderno='".$val['c_orderno']."'".' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");
        $a =  app_url('order/order/childdetail',array('id'=>$value['id']));
        $c_order[$key]['a'] = $a;
        $c_order[$key]['goods'] = $goods;
    }
    include wl_template('order/order_childlist');
}

if($op == 'childdetail'){
    //子订单详情
    $id = $_GPC['id'];//子订单ID
    $detail = pdo_fetch("SELECT * FROM ".tablename('tg_order_child')." where id=".$id.' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");//子订单详情
    $goods = pdo_fetchall("SELECT * FROM ".tablename('tg_child_collect')." where orderno='".$detail['c_orderno']."'".' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");
    $order = pdo_fetch("SELECT * FROM ".tablename('tg_order')." where orderno=".$detail['orderno'].' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");
    if($order['merchantid']==0){
        $merchants = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = '{$_W['uniacid']}'");
        $merchantname = $merchants['name'];
    }else{
        $merchants = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' AND id='{$order['merchantid']}'  ORDER BY id DESC");
        $merchantname = $merchants['name'];
    }
    include wl_template('order/order_childdetail');
    exit;
}
if($op == 'childconfirm'){
    //确认收货
    $orderno = $_GPC['orderno'];//主订单编号
    $id = $_GPC['id'];//子订单ID
    pdo_update('tg_order_child',['status'=>3],['id'=>$id]);//
    $s_gs = pdo_fetchall("SELECT * FROM ".tablename('tg_collect')." where orderno=".$orderno.' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");
    $up_flag = true;
    foreach ($s_gs as $key=>$value){
        if($value['is_send']==0){
            $up_flag = false;
        }
    }
    if($up_flag){
        $c_order = pdo_fetchall("SELECT * FROM ".tablename('tg_order_child')." where orderno=".$orderno.' and `uniacid` = '.$_W['uniacid']." and openid='".$_W['openid']."'");
        foreach ($c_order as $key=>$value){
            if($value['status'] == 2){
                die(json_encode(['errCode'=>1,'']));
            }
        }
        pdo_update('tg_order',['status'=>3],['orderno'=>$orderno]);//
    }
    die(json_encode(['errCode'=>1,'']));
}
if ($op == 'ajax') {
    wl_load()->model('setting');
    $setting = setting_get_by_name("kaiguan");
    if(empty($setting['is_hbhexiao'])){
        $is_hbhexiao = 2;
    }else{
        $is_hbhexiao = $setting['is_hbhexiao'];
    }
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $status = $_GPC['status'];
    $servestype = 1;
    $data = order_get_list(array('openid' => $_W['openid'], 'usepage' => 1, 'page' => $page, 'pagesize' => $pagesize, 'status' => $status));
    foreach ($data['list'] as $key => &$value) {
        $data['list'][$key]['is_hbhexiao'] = $is_hbhexiao;

        if ($data['list'][$key]['bukuanstatus'] == '2') {
            $bukuan = pdo_fetch("select * from " . tablename('tg_order') . " where status = 3 and master_orderno = '{$data['list'][$key]['orderno']}' ");
            $data['list'][$key]['bkprice'] = $bukuan['price'];
            $data['list'][$key]['realprice'] += $bukuan['price'];
        }

        switch ($value['status']) {
            case 0:
                $statusname = '待付款';
                break;
            case 1:
                $statusname = '已付款';
                break;
            case 2:
                $statusname = '待收货';
                break;
            case 3:
                $statusname = '已签收';
                break;
            case 4:
                $statusname = '已退款';
                break;
            case 5:
                $statusname = '强退款';
                break;
            case 6:
                $statusname = '部分退款';
                break;
            case 7:
                $statusname = '已退款';
                break;
            case 8:
                $statusname = '待发货';
                break;
            case 9:
                $statusname = '已取消';
                break;
            case 10:
                $statusname = '待退款';
                break;
            default:
                $statusname = '待付款';
        }
        if($value['issued'] == 0 && $value['status']=='8'&&$value['selltype']==5){
            $statusname = '未开奖';
        }
        if ($value['tuan_id'] > 0 && $value['status'] == 1 && $value['selltype'] != 7 && $value['selltype'] != 5) {
            $group = pdo_fetch("select * from " . tablename('tg_group') . " where groupnumber = '{$value['tuan_id']}' ");
            if ($group['groupstatus'] == 2) {
                $re = pdo_update('tg_order', array('status' => 8), array('uniacid' => $value['uniacid'], 'tuan_id' => $value['tuan_id'], 'status' => 1));
                if ($re > 0) {
                    group_success($value['tuan_id']);
                    $value = order_get_by_id($value['id']);
                }
            }
        }

        if ($value['status'] == 8 && $value['is_hexiao'] != 0) {
            $statusname = '到店自提';
        }
//        if ($value['status'] == 8 && $value['dispatchtype'] == 3 && $value['zititime'] > 0) {
//            if ($value['tuan_id'] == 0 && ($value['ptime'] + $value['zititime'] * 60 * 60) < TIMESTAMP){
//                pdo_update('tg_order', array('black' => 1), array('id' => $value['id'], 'black' => 0));
//            } elseif ($value['tuan_id'] > 0){
//                $gro = pdo_fetch("select successtime from " .tablename('tg_group') ." where groupnumber = '{$value['tuan_id']}' and groupstatus = 2 ");
//                if (($gro['successtime'] + $value['zititime'] * 60 * 60) < TIMESTAMP) {
//                    pdo_update('tg_order', array('black' => 1), array('id' => $value['id'], 'black' => 0));
//                }
//            }
//        }
        if ($value['g_id'] > 0) {
            $goods = goods_get_by_params(" id = {$value['g_id']} ");
            $value['gimg'] = $goods['share_image'];
            $value['name'] = $value['goodsname'];
            if (empty($value['name'])) {
                $value['name'] = '数据已删除';
            }
            $value['selltype'] = $goods['selltype'];
            if ($value['status'] == 0) {
                $count_order = pdo_fetchall('SELECT id FROM cm_tg_order WHERE tuan_id=:tuan_id AND openid=:openid AND status IN (1,2,3,7,8,10)', array(':tuan_id' => $value['tuan_id'], ':openid' => $_W['openid']));
                /*
                if(!empty($count_order))
                {
                    pdo_update('tg_order',array('status'=>9),array('orderno'=>$value['orderno']));
                    $value['status']=9;
                    $statusname='已取消';
                }
                */

            }
        } else {
            $value['name'] = $value['orderno'];
            $value['gimg'] = tomedia($config['share']['share_image']);
            $value['goods'] = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE  orderno=:orderno", array('orderno' => $value['orderno']));
            foreach ($value['goods'] as $k => &$v) {
                $gs = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE  id=:id", array('id' => $v['sid']));
                $v['gname'] = $gs['gname'];
            }
        }
        $value['service'] = 0;
        if ($value['status'] == 1 || $value['status'] == 2 || $value['status'] == 3 || $value['status'] == 6 || $value['status'] == 8) {
            $value['service'] = $config['base']['service'];
        }
        if ($value['servestype'] > 0) {
            $value['service'] = $config['base']['service'];
        }
        $value['ga'] = $goods['a'];
        $value['statusname'] = $statusname;
        if ($value['status'] != 5 && $value['status'] != 0 && $value['is_tuan'] == 1) {
            $value['ta'] = app_url('order/group') . "&tuan_id=" . $value['tuan_id'];
        }
        $value['sa'] = app_url('order/serves') . "&id=" . $value['id'];
        if ($value['merchantid']) {
            $merchant = merchant_get_by_id($value['merchantid']);
            $value['merchant_name'] = $merchant['name'];
        } else {
            $value['merchant_name'] = $_W['account']['name'];
        }
        if ($goods['hasoption'] == 1) {
            if ($value['optionid'] > 0) {
                $option = pdo_fetch("select title,productprice,marketprice,stock,weight from" . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $value['optionid']));
            }
            //$value['optionname'] = $option['title'];

        }

    }
    die(json_encode($data));
}
if($op == 'getlongurl'){
    wl_load()->model('goods');
    $uniacid = $_W['uniacid'];
    $openid = $_W['openid'];
    $ordernos = $_GPC['ordernos'];
    $str = implode('_',$ordernos);
    $orders = array();
    $i = 0;
    foreach ($ordernos as $key=>$orderno){

        $order = pdo_fetch("select * from " . tablename('tg_order') . " where uniacid = '{$uniacid}' and orderno = '{$orderno}'");

        if ($order['g_id'] > 0) {
            $goods = pdo_fetchall("select * from " . tablename('tg_goods') . " where id = '{$order['g_id']}' and uniacid = '{$uniacid}'");
            $goods['gnum'] = $order['gnum'];
        } else {
            $goods = pdo_fetchall("select id,goodsname as gname,oprice as gprice,sid,num as gnum,item from " . tablename('tg_collect') . " where orderno = '{$order['orderno']}'");
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
        $orders[$i]  = $order;
        $orders[$i]['store']  = $store;
        $orders[$i]['goods']  = $goods;
        $i++;
    }
    $longurl = app_url('order/check/' , array('mid' => $str));
    die(json_encode(array('status'=>'1','longurl'=>$longurl,'orders'=>$orders,'str'=>$str)));
}
if ($op == 'detail') {
    $id = intval($_GPC['id']);
    if ($id) {
        $order = order_get_by_id($id);
        if ($order['openid'] != $_W['openid']) {
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
        $res = pdo_fetchall("SELECT * FROM " . tablename("messikefu_cservice") . " WHERE  merchant_id = 0 AND weid = " . $_W['uniacid']);
        if (empty($res)) {
            $url_kefu = "";
        } else {
            $rand = count($res);
            $rand = rand(0, $rand);
            $url_kefu = app_url('chat/chat', array('op' => 'doMobileChat', "toopenid" => $res[$rand]["content"], "id" => $order['g_id']));
        }


        wl_load()->model('setting');
        $kaiguan = setting_get_by_name('kaiguan');

        if ($order['tuan_id'] > 0 && $order['status'] == 1 && $order['selltype'] != 7 && $order['selltype'] != 5) {
            $group = pdo_fetch("select * from " . tablename('tg_group') . " where groupnumber = '{$order['tuan_id']}' ");
            if ($group['groupstatus'] == 2) {
                $re = pdo_update('tg_order', array('status' => 8), array('uniacid' => $order['uniacid'], 'tuan_id' => $order['tuan_id'], 'status' => 1));
                if ($re > 0) {
                    group_success($order['tuan_id']);
                    $order = order_get_by_id($id);
                }
            }
        }
        if ($order['bukuanstatus'] == '2') {
            $bukuan = pdo_fetch("select * from " . tablename('tg_order') . " where status = 3 and master_orderno = '{$order['orderno']}' ");
            $order['bkprice'] = $bukuan['price'];
            $order['price'] += $bukuan['price'];
        }

        $order['longurl'] = app_url('order/check/' , array('mid' => $order['orderno']));
//        $png = changeurl(app_url('order/check/' , array('mid' => $order['orderno'])));
//        if ($png['errcode'] == 0) {
//            $order['short_url'] = $png['short_url'];
////            die(json_encode($order['short_url']));
//        }
        $goods = goods_get_by_params(" id = {$order['g_id']} ");
        if ($order['merchantid']) {
            $merchant = merchant_get_by_id($order['merchantid']);
            $order['merchant_name'] = $merchant['name'];
        } else {
            $order['merchant_name'] = $_W['account']['name'];
        }
        $sto = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$order['comadd']}' and uniacid='{$_W['uniacid']}'");
        if ($sto["lat"] == null || $sto['lng'] == null || empty($sto['lat']) || empty($sto['lng'])) {
            $sto['lat'] = "default";
            $sto['lng'] = "default";
        }
        if ($order['bukuanstatus'] == '2') {
            $bukuan = pdo_fetch("select * from " . tablename('tg_order') . " where status = 3 and master_orderno = '{$order['orderno']}' ");
            $order['bkprice'] = $bukuan['price'];
            $order['price'] += $bukuan['price'];
        }
        if ($goods['is_hexiao'] == 1) {
            //门店信息
            $storesids = explode(",", $goods['hexiao_id']);
            foreach ($storesids as $key => $value) {
                if ($value) {
                    $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
                }
            }
        }
        $sal = pdo_fetch("select * from" . tablename('tg_member') . " where from_user ='" . $order['veropenid'] . "'");
    }
    $new_endtime = date('Y-m-d H:i:s', $order['gettime']);
    $over_endtime = strtotime("$new_endtime +15 day");
    $now_time = time();
    if ($now_time >= $over_endtime || !$order['gettime']) {
        $notshow = 1;
    }
    if ($order['status'] == 0 || $order['status'] == 1 || $order['status'] == 8 || $order['status'] == 4 || $order['status'] == 5 || $order['status'] == 6 || $order['status'] == 7 || $order['status'] == 9) {
        $notshow = 1;
    }
    include wl_template('order/order_detail');
}


if ($op == 'cancel') {
    $orderno = $_GPC['orderno'];
    if ($orderno) {
        $order = order_update_by_params(array('status' => 9), array('orderno' => $orderno));
        if ($order) {
            //$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE orderno = :orderno", array(':orderno' => $orderno));
            //$goods = goods_get_by_params(" id={$item['g_id']} ");
            //cancelorder($_W['openid'], $item['price'], $goods['gname'], $orderno, '');
            wl_json(1);
        } else {
            wl_json(0, '取消订单失败！');
        }
    } else {
        wl_json(0, '缺少订单号');
    }
}
if ($op == 'tip') {
    $list = pdo_fetchall("SELECT openid FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} AND status in(1,2,3,8,6) AND openid<>'' and mobile<>'虚拟' order by ptime desc limit 10");
    $arr = array();
    foreach ($list as $key => &$vlaue) {
        $sql = 'SELECT nickname,avatar FROM ' . tablename('tg_member') . ' WHERE openid=:openid AND uniacid=:uniacid';
        $paramse = array(':openid' => $vlaue['openid'], ':uniacid' => $_W['uniacid']);
        $members = pdo_fetch($sql, $paramse);
        $vlaue['nickname'] = mb_substr($members['nickname'], 0, 3, 'utf-8');
        $vlaue['avatar'] = $members['avatar'];
        $citylist = pdo_fetch("select city from " . tablename('tg_address') . " where uniacid = {$_W['uniacid']} and openid='{$vlaue['openid']}'");
        if (!empty($citylist['city'])) {
            $vlaue['city'] = $citylist['city'];
        }


        $sec = rand(1, 20);
        $vlaue['sec'] = $sec;
    }
    die(json_encode($list));
}
if ($op == 'topay') {
    $orderno = $_GPC['orderno'];
    if ($orderno) {
        $order = order_get_by_params(" orderno = '{$orderno}' ");
        if ($order['g_id'] > 0) {
            $g_id = $order['g_id'];
            $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $g_id));
            $good_conf = pdo_get('tg_goods' , array('id' => $g_id) , 'many_limit');
            $num_ed = intval($old_data['num']);
            if ($good_conf["many_limit"] > 0) {
                if (intval($num_ed) + $num > intval($good_conf["many_limit"])) {
//                    pdo_update('tg_order' , array('status' => 9) , array('id' => $order['id']));
                    wl_json(-1, '已超出购买上限，无法继续支付');
                }
            }
        } else {
            $collect = pdo_getall('tg_collect' , array('orderno' => $orderno));
            foreach ($collect as $item) {
                $g_id = $item['sid'];
                $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $g_id));
                $good_conf = pdo_get('tg_goods' , array('id' => $g_id) , 'many_limit');
                $num_ed = intval($old_data['num']);
                if ($good_conf["many_limit"] > 0) {
                    if (intval($num_ed) + $num > intval($good_conf["many_limit"])) {
//                        pdo_update('tg_order' , array('status' => 9) , array('id' => $order['id']));
                        wl_json(-1, '存在超出购买上限商品，无法继续支付');
                    }
                }
                unset($g_id);
            }

        }
        if ($order['status'] == 0) {

            wl_json(1);
        } else {
            wl_json(0, '订单状态错误');
        }
    } else {
        wl_json(0, '缺少订单号');
    }
}

if ($op == 'receipt') {
    $orderno = $_GPC['orderno'];
    //佣金
    if ($orderno) {

        //佣金
        $value = pdo_fetch("select * from" . tablename('tg_order') . "where  uniacid=:uniacid and id= :id", array(":uniacid" => $_W["uniacid"], ":id" => $orderno));

        if (intval($value['comtype']) == 0 && $value['status'] == 2) {
            /**********************/
            //积分
            if ($value['g_id'] > 0) {
                $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id", array(":id" => $value['g_id']));
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
//                    pdo_update('tg_order', array('comtype' => 1), array('id' => $value['id']));
//                }
//
            }
            if ($value['g_id'] == 0) {

                $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='{$_W['uniacid']}'   and orderno='{$value['orderno']}'");
                $is_send = pdo_fetchall("select * from " . tablename("tg_collect") . " where orderno = ':id'", array(":id" => $value['orderno']));
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
//                    pdo_update('tg_order', array('comtype' => 1), array('id' => $value['id']));
//                }
//
            }

        }
        $order = order_update_by_params(array('status' => 3, 'over_time' => TIMESTAMP, 'gettime' => time()), array('id' => $orderno));//, 'comtype' => 1
        if ($order['supply_goodsid'] > 0) {
            pdo_update('tg_supply_collect', array('supply_status' => 2, 'receive_time' => TIMESTAMP), array('orderid' => $orderno));
        }
        //查询当前订单是否拆单发货
        $c_order = pdo_fetchall("SELECT * FROM ".tablename('tg_order_child')." where orderno=".$value['orderno']);

        if(count($c_order)>0){
            foreach ($c_order as $key=>$value){
                pdo_update('tg_order_child',['status'=>3],['id'=>$value['id']]);
            }
        }
        //获取当前会员的openid
        $openid = $value["openid"];
        //获取当前会员的等级
        $user_info = pdo_fetch("SELECT * FROM " .
            tablename("tg_member") .
            " WHERE from_user = :openid AND uniacid = :uniacid",
            array(":openid" => $openid, ":uniacid" => $value["uniacid"])
        );
        //查出来会员的总订单金额是多少
        $order_money = pdo_fetch("SELECT sum(price) AS price FROM " .
            tablename("tg_order") .
            " WHERE openid = :openid AND uniacid = :uniacid AND status = 3",
            array(":openid" => $openid, ":uniacid" => $value["uniacid"])
        );
        $order_price = $order_money["price"];
        //计算出总金额之后然后查看赋予会员的等级是多少
        $auto_level = pdo_fetch("SELECT * FROM " .
            tablename("tg_member_leave") .
            " WHERE :order_price >= money AND uniacid = :uniacid ORDER BY money DESC",
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
        if ($value["selltype"] == 3 && $value["tuan_first"] == 1 and $value["status"] == 1 || $value["status"] == 2 && $value["status"] != 9) {
            $tuan_list = pdo_fetchall("SELECT * FROM " . tablename("tg_order") . " WHERE status <>3 AND status<>9 AND tuan_id = :tuan_id AND tuan_first = 0", array(":tuan_id" => $value["tuan_id"]));
            foreach ($tuan_list as $v) {
//                pdo_update("tg_order",array("status"=>3),array("tuan_id"=>$order["tuan_id"],"status"=>2));
//                $sql = "update " . tablename('tg_order') . " set status = 3 where tuan_id = " . $order["tuan_id"] . ' and status <>3 and status <>9 and tuan_first = 0';
//                pdo_query($sql);
                pdo_update('tg_order', array('status' => 3, 'over_time' => TIMESTAMP), array('tuan_id' => $order["tuan_id"], 'tuan_first' => 0, 'status' => 2));
                if (intval($value['comtype']) == 0) {
                    /**********************/
                    //积分
                    if ($value['g_id'] > 0) {
                        $is_send = pdo_fetchall("select * from " . tablename("tg_goods") . " where g_id = :id", array(":id" => $value['g_id']));
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
                        $is_send = pdo_fetchall("select * from " . tablename("tg_goods") . " where orderno = :id", array(":id" => $value['orderno']));
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
                    $user_info = pdo_fetch("SELECT * FROM " .
                        tablename("tg_member") .
                        " WHERE from_user = :openid AND uniacid = :uniacid",
                        array(":openid" => $openid, ":uniacid" => $value["uniacid"])
                    );
                    //查出来会员的总订单金额是多少
                    $order_money = pdo_fetch("SELECT sum(price) AS price FROM " .
                        tablename("tg_order") .
                        " WHERE openid = :openid AND uniacid = :uniacid AND status = 3",
                        array(":openid" => $openid, ":uniacid" => $value["uniacid"])
                    );
                    $order_price = $order_money["price"];
                    //计算出总金额之后然后查看赋予会员的等级是多少
                    $auto_level = pdo_fetch("SELECT * FROM " .
                        tablename("tg_member_leave") .
                        " WHERE :order_price >= money AND uniacid = :uniacid ORDER BY money DESC",
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
        //确认是邻够团  确认是团长 确认状态是1||1

        if ($order["selltype"] == 2 && $order["tuan_first"] == 1 && ($order["status"] == 1 || $order["status"] == 2 || $order["status"] == 8)) {
            $goodsInfo = goods_get_by_params(' id = ' . $value['g_id']);
            if ($goodsInfo['is_daiqian']) {
            $tuan_list = pdo_fetchall("SELECT * FROM " . tablename("tg_order") . " WHERE status in (1,8) AND tuan_id = :tuan_id AND tuan_first = 0", array(":tuan_id" => $order["tuan_id"]));
            foreach ($tuan_list as $v) {
                pdo_update('tg_order', array('status' => 3, 'over_time' => TIMESTAMP), array('id' => $v["id"]));
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
                    $user_info = pdo_fetch("SELECT * FROM " .
                        tablename("tg_member") .
                        " WHERE from_user = :openid AND uniacid = :uniacid",
                        array(":openid" => $openid, ":uniacid" => $value["uniacid"])
                    );
                    //查出来会员的总订单金额是多少
                    $order_money = pdo_fetch("SELECT sum(price) AS price FROM " .
                        tablename("tg_order") .
                        " WHERE openid = :openid AND uniacid = :uniacid AND status = 3",
                        array(":openid" => $openid, ":uniacid" => $value["uniacid"])
                    );
                    $order_price = $order_money["price"];
                    //计算出总金额之后然后查看赋予会员的等级是多少
                    $auto_level = pdo_fetch("SELECT * FROM " .
                        tablename("tg_member_leave") .
                        " WHERE :order_price >= money AND uniacid = :uniacid ORDER BY money DESC",
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
        }
        if ($order) {
            for ($i = 0; $i < count($is_send); $i++) {
//                $is_send = pdo_fetch("select * from ".tablename("tg_goods")." where   id = :id",array(":id"=>$is_send[$i]["g_id"]));
                if ($is_send[$i]["is_sendcoupon"] == 1) {
                    $coupon_id = $is_send[$i]["coupon_id"];
                    //查询优惠券详情
                    $coupon_info = pdo_get("tg_coupon_template", array("id" => $coupon_id));
                    $data_xc["name"] = $coupon_info["name"];
                    $data_xc["coupon_template_id"] = $coupon_info["id"];
                    $data_xc["openid"] = $value["openid"];
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
            // 次卡
            $o = pdo_get('tg_order', array('id' => $orderno));
            if($o['is_once_card'] == 1){
                pdo_fetch("UPDATE " . tablename("tg_order_once_card_record") .
                    " SET sign_date = :sign_date WHERE orderid = :orderid ORDER BY id DESC limit 1",
                    array(":sign_date" => TIMESTAMP, ":orderid" => $orderno));
                if ((int)$o['once_card_ynum']+1 < (int)$o['once_card_num']) {

                    pdo_update('tg_order', array('status' => 8, 'delivery_time' => 0, 'gettime' => 0, 'once_card_ynum' => $o['once_card_ynum'] + 1,'over_time'=>'','expresssn'=>NULL), array('id' => $orderno));

                }
            }
            wl_json(1);
        } else {
            wl_json(0, '确认收货失败！');
        }
    } else {
        wl_json(0, '缺少订单号');
    }
}
if ($op == 'judgment_detail') {
    $id = intval($_GPC['id']);
    if ($id) {
        $order = order_get_by_id($id);
        $goods = goods_get_by_params(" id = {$order['g_id']} ");
        if ($order['merchantid']) {
            $merchant = merchant_get_by_id($order['merchantid']);
            $order['merchant_name'] = $merchant['name'];
        } else {
            $order['merchant_name'] = $_W['account']['name'];
        }
        $sto = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$order['checkstore']}' and uniacid='{$_W['uniacid']}'");
        if ($goods['is_hexiao'] == 1) {

            //门店信息
            $storesids = explode(",", $goods['hexiao_id']);
            foreach ($storesids as $key => $value) {
                if ($value) {
                    $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
                }
            }
        }
        $sal = pdo_fetch("select * from" . tablename('tg_member') . " where from_user ='" . $order['veropenid'] . "'");
    }
    $iswrite = pdo_fetch("select * from" . tablename('tg_judgment') . " where orderno ='{$order['orderno']}' ");

    include wl_template('order/judgment_detail');
}
if ($op == 'judement_submit') {
    $jud_date = $_GPC['jud_date'];
    $si = $_GPC['si'];

    for ($i = 0; $i < $si; $i++) {
        $openid = $jud_date[$i]['openid'];
        $orderno = $jud_date[$i]['orderno'];
        $gid = $jud_date[$i]['gid'];
        $content = $jud_date[$i]['content'];
        $uniacid = $jud_date[$i]['uniacid'];
        $item = $jud_date[$i]['item'];
        $gname = pdo_fetch("SELECT gname FROM " . tablename('tg_goods') . " WHERE id = " . $gid . "  ");
        $openname = pdo_fetch("SELECT nickname,avatar FROM " . tablename('tg_member') . " WHERE uniacid = " . $uniacid . " AND openid =  '" . $openid . "' ");

        $data_admin['judgment_id'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $data_admin['gname'] = $gname['gname'];
        $data_admin['gid'] = $gid;
        $data_admin['uniacid'] = $uniacid;
        $data_admin['openid'] = $openid;
        $data_admin['openname'] = $openname['nickname'];
        $data_admin['status'] = 0;
        $data_admin['create_time'] = TIMESTAMP;
        $data_admin['update_time'] = TIMESTAMP;
        $data_admin['avatar'] = $openname['avatar'];
        $data_admin['item'] = $item;
        $data_admin['orderno'] = $orderno;
        wl_load()->model('setting');
        $kaiguan = setting_get_by_name('kaiguan');
        if ($kaiguan['judgment'] == 1) {
            $data_admin['check_status'] = 0;
        }

        //评论属性
        $data_admin['iszhuijia'] = intval($jud_date[$i]['iszhuijia']);
        $data_admin['ishaoyong'] = intval($jud_date[$i]['ishaoyong']);
        $data_admin['iszhengpin'] = intval($jud_date[$i]['iszhengpin']);
        $data_admin['ispianyi'] = intval($jud_date[$i]['ispianyi']);
        $data_admin['iswuliu'] = intval($jud_date[$i]['iswuliu']);
        $data_admin['iszhiliang'] = intval($jud_date[$i]['iszhiliang']);
        $data_admin['isfuwu'] = intval($jud_date[$i]['isfuwu']);
        $data_admin['isqita'] = intval($jud_date[$i]['isqita']);
        //end

        $addjudgment = pdo_insert('tg_judgment', $data_admin);

        $data_content['judgment_id'] = $data_admin['judgment_id'];
        $data_content['content_id'] = $data_admin['judgment_id'];
        $data_content['content'] = $content;
        $data_content['update_time'] = TIMESTAMP;
        $data_content['who'] = 0;

        $addjudgment_content = pdo_insert('tg_judgment_content', $data_content);

//      $img_s = $jud_date[$i]['img_s'];
//      $xi = 0;
//      foreach($img_s as $key => $value) {
//          $data2 = array('img_url' => $value, 'content_id' => $data_content['content_id']);
//          pdo_insert('tg_judgment_img', $data2);
//      }
//      $file_base64 = preg_replace('/data:.*;base64,/i', '', $img_s[0]['img']);
//      $img = base64_decode($file_base64);
//      file_put_contents('./tex2t.jpg', $img);
//          $img_url = base64_decode($img_s[0]['img']);
//          $data2 = array('img_url' => $img_url, 'content_id' => $data_content['content_id']);
//          pdo_insert('tg_judgment_img', $data2);


    }
    die(json_encode($xi));
}
//if($op =='judement_img'){
//  $imgs = $_GPC['img'];
//  $uniacid = $_GPC['uniacid'];
//  $id =  date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
//  $file_base64 = preg_replace('/data:.*;base64,/i', '', $imgs);
//  $img = base64_decode($file_base64);
//  file_put_contents('../attachment/images/'.$uniacid.'/'.$id.'.jpg', $img);
//  $img_url='images/'.$uniacid.'/'.$id.'.jpg';
//  die(json_encode($img_url));
//}
if ($op == 'judement_again') {
    $jud_date = $_GPC['jud_date'];
    $si = $_GPC['si'];

    for ($i = 0; $i < $si; $i++) {

        $judgment_id = $jud_date[$i]['judgment_id'];
        $content = $jud_date[$i]['content'];

        $data_content['judgment_id'] = $judgment_id;
        $data_content['content_id'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $data_content['content'] = $content;
        $data_content['update_time'] = TIMESTAMP;
        $data_content['who'] = 0;

        $addjudgment_content = pdo_insert('tg_judgment_content', $data_content);

    }
    die(json_encode($judgment_id));
}

function changeurl($longurl) {
    load()->func('communication');
    $longurl = trim($longurl);
    $token = WeAccount::token(WeAccount::TYPE_WEIXIN);
    $url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token={$token}";
    $send = array();
    $send['action'] = 'long2short';
    $send['long_url'] = $longurl;
    $response = ihttp_request($url, json_encode($send));
    if(is_error($response)) {
        $result = error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
    }
    $result = @json_decode($response['content'], true);
    if(empty($result)) {
        $result =  error(-1, "接口调用失败, 元数据: {$response['meta']}");
    } elseif(!empty($result['errcode'])) {
        $result = error(-1, "访问微信接口错误, 错误代码: {$result['errcode']}, 错误信息: {$result['errmsg']}");
    }
    if(is_error($result)) {
        return array('errcode' => -1, 'errmsg' => $result['message']);
    }
    return $result;
}


if ($op=='qr'){
    require(IA_ROOT . '/framework/library/qrcode/phpqrcode.php');
    $errorCorrectionLevel = "L";
    $matrixPointSize = "5";
    return QRcode::png($_GPC['url'], false, $errorCorrectionLevel, $matrixPointSize);

}

exit();