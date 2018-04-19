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
wl_load()->model('address');
$config = tgsetting_load();
$issendtime = checkfunc(8158);
$sendtime_setting = setting_get_by_name("sendtime");
$kaiguan = setting_get_by_name("kaiguan");
$selfTime = setting_get_by_name("self_time");
$ops = array('zt', 'sh', 'ck', 'display', 'post' , 'whtc');
$op = in_array($op, $ops) ? $op : 'display';

$openid = $_W['openid'];
$pagetitle = '订单提交';
if (empty($_W['openid'])) {
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
session_start();
$type = $_SESSION['type'];
if (empty($_SESSION['type'])) {

    switch (intval($config['base']['deliverytype'])){
        case 1 :
            $type = 2;
            break;
        case 2 :
            $type = 2;
            break;
        case 3 :
            $type = 3;
            break;
        case 4 :
            $type = 2;
            break;
        case 5 :
            $type = 1;
            break;
        case 6 :
            $type = 3;
            break;
        case 7 :
            $type = 2;
            break;
        default :
            $type = 0;
            break;
    }
    $_SESSION['type'] = $type;

}
$orderlist = pdo_fetchall("select * from" . tablename('tg_collect') . "where uniacid='{$_W['uniacid']}' and orderno='0' and openid='{$_W['openid']}'");
if (empty($orderlist)) {
    header("Location: " . app_url('goods/list'));
    exit;
}
foreach ($orderlist as $ok=>$ov){
    $g = pdo_fetch("SELECT * FROM".tablename('tg_goods')." where id=".$ov['sid']);
    $orderlist[$ok]['is_wholesale'] = $g['is_wholesale'];
    $wholesale_level = unserialize($g['wholesale_level']);
    $n_wholesale_level = array();
    foreach ($wholesale_level as $key=>$value){
        $n_wholesale_level[$value['pifanum']]=$value;
    }
    ksort($n_wholesale_level);
    $n_wholesale_level = array_merge($n_wholesale_level);
    $orderlist[$ok]['wholesale_level'] = $n_wholesale_level;
    $orderlist[$ok]['wholesale_num'] = $g['wholesale_num'];
    $orderlist[$ok]['gimg'] = tomedia($g['gimg']);
    $orderlist[$ok]['wholesale_staircase'] =$g['wholesale_staircase'];

}
$gg_orderlist = $orderlist;
$sorderlist = json_encode($orderlist);

$stores = pdo_fetchall("select * from" . tablename('tg_store') . "where uniacid='{$_W['uniacid']}' and status=1 and merchantid = '{$orderlist[0]['merchant_id']}' ");

$memb = member_get_by_params("openid='{$openid}' and uniacid='{$_W['uniacid']}'");
if (empty($_GPC['num'])) {
    if ($_SESSION['num']) {
        $_GPC['num'] = $_SESSION['num'];
    } else {
        $_GPC['num'] = 1;
    }
}
$_SESSION['num'] = $num = isset($_GPC['num']) ? intval($_GPC['num']) : $_SESSION['num'];
$addrid = isset($_GPC['addrid']) ? intval($_GPC['addrid']) : 0;
$is_usecard = 0;


$storesids = explode(",", $config['base']['hexiao_id']);
if ($storesids) {
    unset($stores);
    $stores = array();
}
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

//门店列表 带距离
if ($_GPC['op'] == 'storelist_distance') {
    $store = array();
    if ($kaiguan['zitidingwei'] == 1) {
        foreach ($stores as $k => $val) {
            $lat = $_GPC['lat'];
            $lng = $_GPC['lng'];
            $dis = $distance = getDistance($lat, $lng, $val['lat'], $val['lng']);
            $stores[$k]['distance'] = $distance;

            if ($distance / 10000 > 1) {
                $stores[$k]['distance'] = number_format($distance / 1000) . "公里";
            } else {
                $stores[$k]['distance'] = $distance . "米";
            }

            $store[$dis] = $stores[$k];

        }
        ksort($store);
        $store = array_merge($store);
    } else {
        $store = $stores;
    }
    die(json_encode($store));
}

/**
 *
 * @desc 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000; //approximate radius of earth in meters
    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;
    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;
    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
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
$orderlist = pdo_fetchall("select num,oprice,goodsname,sid,id,merchant_id from" . tablename('tg_collect') . "where uniacid='{$_W['uniacid']}' and orderno='0' and openid='{$_W['openid']}'");
foreach ($orderlist as $key => $value) {
    $nowshopprice = $nowshopprice + $value['num'] * $value['oprice'];
    if (empty($value['goodsname'])) {
        $nowgoods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id=" . $value['sid']);
        pdo_update('tg_collect', array('goodsname' => $nowgoods['gname']), array('id' => $value['id']));
    }
}
if ($overfreemoney <= $nowshopprice && $overfreemoney > 0) {
    $overfree = 1;
}
if ($_GPC['op'] == 'at') {
    $ad = $_GPC['ad'];
    //送货时间
    $sendtimes = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid ='{$_W['uniacid']}'  and status=1 and merchantid = '{$orderlist[0]['merchant_id']}' order by starttime asc");
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
        $dtime = time();
    } elseif ($ad == 2) {
        $dtime = time() + (1 * 24 * 60 * 60);
    } elseif ($ad == 3) {
        $dtime = time() + (2 * 24 * 60 * 60);
    }
    $ttime = array();
    $dtime = date("Y-m-d", $dtime);
    foreach ($sendtimes as $k => $v) {
        if (strlen($v['starttime']) <= 2) {
            $v['starttime'] = $v['starttime'] . ":00";
        }
        if (strlen($v['endtime']) <= 2) {
            $v['endtime'] = $v['endtime'] . ":59";
        }
        $valtime = $v['starttime'] . "-" . $v['endtime'];
        $da1 = date('Y-m-d');
        $da2 = date('Y-m-d', $nexttime);
        $da3 = date('Y-m-d', $lasttime);
        $psql1 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and sendtime='{$valtime}'  and senddate='{$dtime}' and status not in (0,4,5,9)");
        $numa1 = count($psql1);
        if ($v['total'] > $numa1) {
            $ttime[$k]['name'] = $valtime;
        }

    }
    $ttime = array_values($ttime);
    die(json_encode($ttime));

//送货时间
}
if ($_GPC['op'] == 'zt') {
    $sid = $_GPC['sid'];
    $scost = pdo_fetch("select cost from" . tablename('tg_store') . "where id ='{$sid}' and uniacid='{$_W['uniacid']}' and merchantid = '{$orderlist[0]['merchant_id']}' ");
    $_SESSION['freight'] = sprintf("%.2f", $scost['cost']);;
    $_SESSION['type'] = 3;

    echo $_SESSION['freight'];
    exit;
}
if ($_GPC['op'] == 'sh') {
    $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and dispatchtype=2 and enabled=1 and merchantid = '{$orderlist[0]['merchant_id']}' ORDER BY id asc");
    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
    $_SESSION['type'] = 1;
    $_SESSION['freight'] = 0;


    $orderlist = pdo_fetchall("select num,oprice from" . tablename('tg_collect') . "where uniacid='{$_W['uniacid']}' and orderno='0' and openid='{$_W['openid']}'");
    foreach ($orderlist as $key => $value) {
        $bprice = $bprice + $value['num'] * $value['oprice'];
    }
    $cost = 0;
    $temp = 0;
    $cout = count($dispatch_delivery);
    foreach ($dispatch_delivery as $key => $item) {
        $i = $i + 1;
        if ($bprice >= $temp && $bprice <= $item['cart']) {
            $cost = $item['cost'];
            break;
        }
        $temp = $item['cart'];

        if ($i == $cout && $bprice > $item['cart']) {
            $cost = $item['cost'];
            break;
        }
    }
    $_SESSION['freight'] = $cost;
    if ($goods['isfree'] == 1 || $firstfree == 1) {
        $cost = 0;
        $_SESSION['freight'] = 0;
    }


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
    $orderlist = pdo_fetchall("select num,weight from" . tablename('tg_collect') . "where uniacid='{$_W['uniacid']}' and orderno='0' and openid='{$_W['openid']}'");
    foreach ($orderlist as $key => $value) {
        $weight = $weight + $value['num'] * $value['weight'];
    }
    $setting = setting_get_by_name("autoaddress");
    if ($setting['autoaddr'] == 0) {
        $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
        $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
        $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");

    } else {
        if ($setting['addrtype'] == 0) {
            $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
            $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");

        }
        if ($setting['addrtype'] == 1) {
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}'  and template_id={$tid}");
            $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}' and district='{$c}' and template_id={$tid}");

        }
        if ($setting['addrtype'] == 2) {
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}'  and template_id={$tid}");

        }
    }
    if (!empty($province_fee)) {

        $free = sprintf("%.2f", $province_fee['free']);
        $first_fee = sprintf("%.2f", $province_fee['first_fee']);
        $first_weight = sprintf("%.2f", $province_fee['first_weight']);
        $second_fee = sprintf("%.2f", $province_fee['second_fee']);
        $second_weight = sprintf("%.2f", $province_fee['second_weight']);
    }
    if (!empty($city_fee)) {

        $free = sprintf("%.2f", $city_fee['free']);
        $first_fee = sprintf("%.2f", $city_fee['first_fee']);
        $first_weight = sprintf("%.2f", $city_fee['first_weight']);
        $second_fee = sprintf("%.2f", $city_fee['second_fee']);
        $second_weight = sprintf("%.2f", $city_fee['second_weight']);
    }
    if (!empty($district_fee)) {
        $free = sprintf("%.2f", $district_fee['free']);
        $first_fee = sprintf("%.2f", $district_fee['first_fee']);
        $first_weight = sprintf("%.2f", $district_fee['first_weight']);
        $second_fee = sprintf("%.2f", $district_fee['second_fee']);
        $second_weight = sprintf("%.2f", $district_fee['second_weight']);
    }

    if ($weight > $first_weight) {
        if ($second_weight > 0) {
            $freight = sprintf("%.2f", $first_fee + ($weight - $first_weight) / $second_weight * $second_fee);
        } else {
            $freight = sprintf("%.2f", $first_fee);
        }
    } else {
        $freight = $first_fee;
    }
    $checkfree_price = sprintf("%.2f", $free - $nowshopprice);

    if (intval($goods['isfree']) == 1 || $firstfree == 1 || ($checkfree_price <= 0.00 && $free > 0.00)) {
        $freight = 0;
    }
    ////////
    $_SESSION['freight'] = $freight;
    $fdata = array('freight' => $freight, 'free' => $free);
    echo json_encode($fdata);
    exit;
}
///////
//支付价格
foreach ($gg_orderlist as $key => $value) {
    if($value['is_wholesale'] == 1 && $value['wholesale_staircase'] !=0){
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
$pay_price = sprintf("%.2f", $allprice + $_GPC['freight']);
//获取可用优惠券
//$coupon = coupon_canuse($openid, $pay_price);
if ($_GPC['op'] == 'couponajax') {
    $coupon = coupon_canuse($openid, $_GPC['productprice']);
    echo json_encode($coupon);
    exit;
}

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
        pdo_update('tg_member', array('addname' => $_GPC['name'], 'addmobile' => $_GPC['mobile']), array('openid' => $openid, 'uniacid' => $_W['uniacid']));

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
    if (!empty($_GPC['senddate'])) {
        $date1 = date('Y-m-d');
        if ($_GPC['senddate'] == 1) {
            $dtime = date('Y-m-d');
        } elseif ($_GPC['senddate'] == 2) {
            $dtime = date('Y-m-d', strtotime($date1 . "+1 day"));
        } elseif ($_GPC['senddate'] == 3) {
            $dtime = date('Y-m-d', strtotime($date1 . "+2 day"));
        }
    }
    $collect = pdo_fetchall("select zititime,sid from " . tablename('tg_collect') . " where uniacid = '{$_W['uniacid']}' and openid = '{$openid}' and orderno = 0 ");
    $zititime = 0;
    $score_num_amount = 0;// 总积分
    foreach ($collect as $val) {
        $good = pdo_fetch("select is_wholesale,score_num from " .tablename('tg_goods') ." where id = '{$val['sid']}' ");
        $is_wholesale = $good['is_wholesale'];
        // 积分开关处理
        $score = setting_get_by_name("score");
        if ($score['score_apply'] == 1 && floatval($good['score_num']) > 0) {
            if ($score['member_score_apply'] == 1) {
                $member = member_get_by_params(" openid='{$openid}' and uniacid='{$_W['uniacid']}'");
                if ($member['member_status'] == "1") {
                    $score_num_amount = $score_num_amount + $good['score_num'];
                }
            } else {
                $score_num_amount = $score_num_amount + $good['score_num'];
            }
        }
        if ($zititime == 0) {
            $zititime = $val['zititime'];
        } elseif ($val['zititime'] > 0 && $val['zititime'] < $zititime) {
            $zititime = $val['zititime'];
        }
    }
    $is_score = $score_num_amount > 0 ? '1' : '0';
    //批发判定处理，添加订单类型is_wholesale

    unset($collect);
    $data = array(
        'uniacid' => $_W['uniacid'],
        'gnum' => 0,
        'openid' => $openid,
        'ptime' => '',//支付成功时间
        'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
        'pay_price' => $pay_price,
        'goodsprice' => $pay_price + $discount_fee - $_GPC['freight'],
        'freight' => $_GPC['freight'],
        'first_fee' => 0,
        'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid' => $adress_fee['id'],
        'addresstype' => $adress_fee['type'],//1公司2家庭
        'dispatchtype' => $_GPC['link_dispatchtype'],//配送方式
        'comadd' => $_GPC['link_zt'],//
        'addname' => $adress_fee['cname'],
        'mobile' => $adress_fee['tel'],
        'address' => $adress_fee['province'] . $adress_fee['city'] . $adress_fee['county'] . $adress_fee['detailed_address'],
        'province' => $adress_fee['province'],
        'city' => $adress_fee['city'],
        'county' => $adress_fee['county'],
        'detailed_address' => $adress_fee['detailed_address'],
        'g_id' => 0,
        'tuan_id' => 0,
        'is_tuan' => 0,
        'tuan_first' => 0,
        'discount_fee' => $discount_fee,
        'starttime' => TIMESTAMP,
        'remark' => $_GPC['remark'],
        'comtype' => 0,
        'senddate' => $dtime,
        'selltype' => 0,
        'sendtime' => $_GPC['sendtime'],
        'commission' => 0,
        'commissiontype' => 0,
        'endtime' => 0,
        'is_hexiao' => $is_hexiao,
        'hexiaoma' => $str,
        'couponid' => $couponid,
        'is_usecard' => $is_usecard,
        'createtime' => TIMESTAMP,
        'zititime' => $zititime,
        'print_id' => $_GPC["print_id"],
        'bdeltime' => $bdeltime,
        'is_wholesale'=>$is_wholesale,
        'score' => $score_num_amount,
        'is_score' => $is_score,
        'self_time' => strtotime($_GPC['self_time'])
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
    /*
        $col_list=pdo_fetchall('select * from '.tablename('tg_collect')." where orderno=':orderno' ",array(':orderno'=>$data['orderno']));
        if(count($col_list)==0){
            pdo_update('tg_order',array('status'=>9),array('orderno'=>$data['orderno']));
            $neworder=pdo_fetch('select * from '.tablename('tg_order').' where uniacid=:uniacid and status=0 and openid=:openid and is_tuan=0 order by id desc  limit 1',array(':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid']));
            if(!empty($neworder)){
                $data['orderno']=$neworder['orderno'];
                $data['pay_price']=$neworder['pay_price'];
            }
        }
    */
    $params['orderno'] = $data['orderno'];
    $params['pay_price'] = $data['pay_price'];
//	$params['status'] = $data['status'];

    wl_json(1, $params);
}

include wl_template('order/shoporder_confirm');
exit();
//include wl_template('order/confirm');