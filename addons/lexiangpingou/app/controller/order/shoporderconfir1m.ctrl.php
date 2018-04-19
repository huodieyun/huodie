<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 商品详情控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
wl_load()->model('account');
wl_load()->model('activity');
wl_load()->model('setting');
load()->func('communication');
wl_load()->model('functions');

$config = tgsetting_load();
$issendtime = checkfunc(8158);
$sendtime_setting = setting_get_by_name("sendtime");
$ops = array('zt', 'sh', 'ck', 'display', 'post');
$op = in_array($op, $ops) ? $op : 'display';

$pagetitle = '订单提交';
session_start();
$type = $_SESSION['type'];
if (empty($_SESSION['type'])) {
    $_SESSION['type'] = 0;
    $type = 0;
}
$orderlist = pdo_fetchall("select * from" . tablename('tg_collect') . "where uniacid='{$_W['uniacid']}' and orderno='0' and openid='{$_W['openid']}'");

$stores = pdo_fetchall("select * from" . tablename('tg_store') . "where uniacid='{$_W['uniacid']}' and status=1");

if (empty($_GPC['num'])) {
    if ($_SESSION['num']) {
        $_GPC['num'] = $_SESSION['num'];
    } else {
        $_GPC['num'] = 1;
    }
}
foreach ($orderlist as $ok=>$ov){
    $g = pdo_fetch("SELECT * FROM".tablename('tg_goods')." where id=".$ov['sid']);
    $orderlist[$ok]['is_wholesale'] = $g['is_wholesale'];
    $orderlist[$ok]['wholesale_level'] = unserialize($g['wholesale_level']);
    $orderlist[$ok]['wholesale_num'] = $g['wholesale_num'];
    $orderlist[$ok]['gimg'] = tomedia($g['gimg']);
}
$sorderlist = json_encode($orderlist);
$_SESSION['num'] = $num = isset($_GPC['num']) ? intval($_GPC['num']) : $_SESSION['num'];
$addrid = isset($_GPC['addrid']) ? intval($_GPC['addrid']) : 0;
$is_usecard = 0;




$storesids = explode(",", $config['base']['hexiao_id']);
foreach ($storesids as $key => $value) {
    if ($value) {
        $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
    }
}
$yunfeiids = explode(",", $config['base']['yunfei_id']);
foreach ($yunfeiids as $key => $value) {
    if ($value) {
        $dispatch_list[$key] = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
    }
}


$adress_fee = pdo_fetch("select * from " . tablename('tg_address') . "where openid = '$openid' and status = 1");

//开启首单免运费
if ($config['base']['firstfree'] == 1) {
    $firstfree = 1;
    $oldbuy = pdo_fetchall("select id from " . tablename('tg_order') . ' where openid=:openid and status  in (1,2,3,8)', array(':openid' => $openid));
    if (count($oldbuy) >= 1) {
        $firstfree = 0;
    }
}
//满包邮
$overfreemoney = $config['base']['over_free'];
$nowshopprice = 0;
$orderlist = pdo_fetchall("select num,oprice from" . tablename('tg_collect') . "where uniacid='{$_W['uniacid']}' and orderno=0 and openid='{$_W['openid']}'");
foreach ($orderlist as $key => $value) {
    $nowshopprice = $nowshopprice + $value['num'] * $value['oprice'];
}
if ($overfreemoney <= $nowshopprice && $overfreemoney > 0) {
    $overfree = 1;
}
if ($_GPC['op'] == 'at') {
    $ad = $_GPC['ad'];
    //送货时间
    $sendtimes = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid ='{$_W['uniacid']}'  and status=1 order by starttime asc");
    $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and dispatchtype=2 and enabled=1 ORDER BY id asc");
    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
    $_SESSION['type'] = 1;
    $_SESSION['freight'] = 0;
    $id = intval($_GPC['g_id']);
    $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = $id");
    if ($goods['selltype'] == 1 || $goods['selltype'] == 2) {
        $bprice = $goods['gprice'];
    }
    if ($goods['selltype'] == 0) {
        $bprice = $goods['oprice'];
    }
    $cost = 0;
    $temp = 0;
    $cout = count($dispatch_delivery);
    foreach ($dispatch_delivery as $key => $item) {
        $i = $i + 1;
        if ($bprice >= $temp && $bprice <= $item['cart']) {
            $cost = $item['cost'];
        }
        $temp = $item['cart'];

        if ($i == $cout && $bprice > $item['cart']) {
            $cost = $item['cost'];
        }
    }
    if ($goods['isfree'] == 1) {
        $cost = 0;
        $_SESSION['freight'] = 0;
    }

    $_SESSION['freight'] = $cost;
    if ($ad == 1) {
        $dtime = date('Y-m-d');
    } elseif ($ad == 2) {
        $dtime = time() + (1 * 24 * 60 * 60);
    } elseif ($ad == 3) {
        $dtime = time() + (2 * 24 * 60 * 60);
    }
    $ttime = array();
    foreach ($sendtimes as $k => $v) {
        $valtime = $v['starttime'] . ":00-" . $v['endtime'] . ":59";
        $da1 = date('Y-m-d');
        $da2 = date('Y-m-d', $nexttime);
        $da3 = date('Y-m-d', $lasttime);
        $psql1 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and sendtime='{$valtime}' and senddate='{$dtime}' and status not in (0,4,5,9)");
        $numa1 = count($psql1);

        if ($v['total'] > $numa1) {
            $ttime[$k]['name'] = $valtime;
        }

    }
    die(json_encode($ttime));

//送货时间
}
if ($_GPC['op'] == 'zt') {
    $sid = $_GPC['sid'];
    $scost = pdo_fetch("select cost from" . tablename('tg_store') . "where id ='{$sid}' and uniacid='{$_W['uniacid']}'");
    $_SESSION['freight'] = intval($scost['cost']);
    $_SESSION['type'] = 3;

    echo $_SESSION['freight'];
    exit;
}
if ($_GPC['op'] == 'sh') {
    $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and dispatchtype=2 and enabled=1 ORDER BY id asc");
    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
    $_SESSION['type'] = 1;
    $_SESSION['freight'] = 0;

    if ($goods['selltype'] == 1 || $goods['selltype'] == 2) {
        $bprice = $goods['gprice'];
    }
    if ($goods['selltype'] == 0) {
        $bprice = $goods['oprice'];
    }
    $cost = 0;
    $temp = 0;
    $cout = count($dispatch_delivery);
    foreach ($dispatch_delivery as $key => $item) {
        $i = $i + 1;
        if ($bprice >= $temp && $bprice <= $item['cart']) {
            $cost = $item['cost'];
        }
        $temp = $item['cart'];

        if ($i == $cout && $bprice > $item['cart']) {
            $cost = $item['cost'];
        }
    }
    if ($goods['isfree'] == 1 || $firstfree == 1 || $overfree == 1) {
        $cost = 0;
        $_SESSION['freight'] = 0;
    }

    $_SESSION['freight'] = $cost;

    echo $cost;
    exit;
}
if ($_GPC['op'] == 'ck') {
    $_SESSION['type'] = 2;
    $tid = $_GPC['tid'];
    /*判断地区邮费*/
    $freight = 0;
    $_SESSION['freight'] = 0;
    $p = $adress_fee['province'];
    $c = $adress_fee['city'];
    $d = $adress_fee['county'];
    $weight = 0;
    $orderlist = pdo_fetchall("select num,weight from" . tablename('tg_collect') . "where uniacid='{$_W['uniacid']}' and orderno=0 and openid='{$_W['openid']}'");
    foreach ($orderlist as $key => $value) {
        $weight = $weight + $value['num'] * $value['weight'];
    }

    $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
    $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
    $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");
    if (!empty($province_fee['first_fee'])) {
        $first_fee = $province_fee['first_fee'];
        $first_weight = $province_fee['first_weight'];
        $second_fee = $province_fee['second_fee'];
        $second_weight = $province_fee['second_weight'];
    }
    if (!empty($city_fee['first_fee'])) {
        $first_fee = $city_fee['first_fee'];
        $first_weight = $city_fee['first_weight'];
        $second_fee = $city_fee['second_fee'];
        $second_weight = $city_fee['second_weight'];
    }
    if (!empty($district_fee['first_fee'])) {
        $first_fee = $district_fee['first_fee'];
        $first_weight = $district_fee['first_weight'];
        $second_fee = $district_fee['second_fee'];
        $second_weight = $district_fee['second_weight'];
    }

    if ($weight > $first_weight) {
        $freight = $first_fee + ($weight - $first_weight) / $second_weight * $second_fee;
    } else {
        $freight = $first_fee;
    }
    if (intval($goods['isfree']) == 1 || $firstfree == 1 || $overfree == 1) {
        $freight = 0;
    }
    ////////
    $_SESSION['freight'] = $freight;

    echo $freight;
    exit;
}
///////
//支付价格
foreach ($orderlist as $key => $value) {
    if($value['is_wholesale'] == 1 && $value['wholesale_staircase'] == 1){
        $num = $value['num'];
        $wholesale_level = $value['wholesale_level'];
        foreach ($wholesale_level as $lk=>$lv){
            if($num>=$lv['pifanum']){
                $price = $lv['pifaprice'];
            }
        }
        $allprice = $allprice + $price * $num;
    }else{
        $allprice = $allprice + $value['oprice'] * $value['num'];
    }
}
$pay_price = sprintf("%.2f", $allprice + $_SESSION['freight']);

//获取可用优惠券
$coupon = coupon_canuse($openid, $pay_price);


if ($_W['isajax']) {

    $typeid = intval($_GPC['typeid']);
    $couponid = intval($_GPC['couponid']);
    $str = '';
    $discount_fee = 0.00;

    if ($typeid == 1 || $typeid == 3) {
        $is_hexiao = 0;
    } elseif ($typeid == 2 || $typeid == 4) {
        $is_hexiao = 1;
        $chars = '0123456789';
        for ($i = 0; $i < 8; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        if (empty($_GPC['name'])) {
            wl_json(0, "未填写提货人姓名");
        }
        if (empty($_GPC['mobile'])) {
            wl_json(0, "未填写提货人电话");
        }
        $adress_fee['cname'] = $_GPC['name'];
        $adress_fee['tel'] = $_GPC['mobile'];
        $bdeltime = strtotime($_GPC['gettime']);

    }

    if ($couponid) {
        $coutp = coupon_handle($openid, $couponid, $pay_price);
        if (!is_array($coutp)) {
            $pay_price = currency_format($pay_price - $coutp);
            $is_usecard = 1;
            $discount_fee = $coutp;
        } else {
            wl_json(0, $coutp['message']);
        }
    }
    $collect = pdo_fetchall("select zititime from " .tablename('tg_collect') ." where uniacid = '{$_W['uniacid']}' and openid = '{$openid}' and orderno = 0 ");
    $zititime = 0;
    foreach ($collect as $val) {
//        $good = pdo_fetch("select zititime from " .tablename('tg_goods') ." where id = '{$item['sid']}' ");
        $good = pdo_fetch("select is_wholesale from " .tablename('tg_goods') ." where id = '{$val['sid']}' ");
        $is_wholesale = $good['is_wholesale'];
        if ($zititime == 0) {
            $zititime = $val['zititime'];
        } elseif ($val['zititime'] > 0 && $val['zititime'] < $zititime){
            $zititime = $val['zititime'];
        }
    }

    $data = array(
        'uniacid' => $_W['uniacid'],
        'gnum' => 0,
        'openid' => $openid,
        'ptime' => '',//支付成功时间
        'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
        'pay_price' => $pay_price,
        'goodsprice' => $pay_price,
        'freight' => $_SESSION['freight'],
        'first_fee' => 0,
        'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid' => $adress_fee['id'],
        'addresstype' => $adress_fee['type'],//1公司2家庭
        'dispatchtype' => $_SESSION['type'],//配送方式
        'comadd' => $_GPC['link_zt'],//
        'addname' => $adress_fee['cname'],
        'mobile' => $adress_fee['tel'],
        'address' => $adress_fee['province'] . $adress_fee['city'] . $adress_fee['county'] . $adress_fee['detailed_address'],
        'g_id' => 0,
        'tuan_id' => 0,
        'is_tuan' => 0,
        'tuan_first' => 0,
        'starttime' => TIMESTAMP,
        'remark' => $_GPC['remark'],
        'comtype' => 0,
        'commission' => 0,
        'commissiontype' => 0,
        'endtime' => 0,
        'is_hexiao' => $is_hexiao,
        'hexiaoma' => $str,
        'couponid' => $couponid,
        'is_usecard' => $is_usecard,
        'createtime' => TIMESTAMP,
        'bdeltime' => $bdeltime,
        'is_wholesale'=>$is_wholesale
    );
    if (pdo_insert('tg_order', $data)) {
        pdo_update('tg_collect', array('orderno' => $data['orderno']), array('openid' => $openid, 'uniacid' => $_W['uniacid'], 'orderno' => 0));
    }
    if ($typeid == 2 || $typeid == 4) {
        /*二维码*/
//        wl_load()->classs('qrcode');
//        $createqrcode = new creat_qrcode();
//        $createqrcode->creategroupQrcode($data['orderno']);
    }
    $params['orderno'] = $data['orderno'];
    $params['pay_price'] = $data['pay_price'];
    wl_json(1, $params);
}
include wl_template('order/shoporder_confirm');
//include wl_template('order/confirm');