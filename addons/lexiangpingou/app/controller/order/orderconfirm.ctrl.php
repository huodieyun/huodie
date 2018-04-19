<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 商品详情控制器
 */
defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
wl_load()->model('account');
wl_load()->model('activity');
wl_load()->model('setting');
load()->func('communication');
wl_load()->model('functions');
$kaiguan = setting_get_by_name("kaiguan");
$issendtime = checkfunc(8158);
$istuanyouhui = checkfunc(8159);//判定团长优惠
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
$sendtime_setting = setting_get_by_name("sendtime");
$ops = array('zt', 'sh', 'ck', 'display', 'post');
$op = in_array($op, $ops) ? $op : 'display';
$openid = $_W['openid'];
$pagetitle = '订单提交';

$acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");

session_start();

$type = $_SESSION['type'];
$id = $_SESSION['goodsid'] = isset($_GPC['id']) ? intval($_GPC['id']) : $_SESSION['goodsid'];
$_SESSION['goodsid'] = intval($_GPC['id']);
$tuan_id = $_SESSION['tuan_id'] = isset($_GPC['tuan_id']) ? intval($_GPC['tuan_id']) : $_SESSION['tuan_id'];

//判断商品id
if (!empty($id)) {
    $goods = goods_get_by_params(" id = {$id} ");
} else {
    header("Location: " . app_url('goods/list'));
    exit;
}
//抽奖团限制
if($goods['selltype'] == 5){
    $cjsetting = setting_get_by_name('kaiguan');
    if($cjsetting['cj_isallow'] == 1){
        //开启
        $hasbuy = pdo_fetchall("select * from cm_tg_goods_openid where g_id={$id} and openid='{$openid}' and uniacid={$_W['uniacid']}");
        if(count($hasbuy)>0){
            $is_allow = 0;
        }else{
            $is_allow = 1;
        }
    }else{
        //查询该用户是否中奖
        $haszj = pdo_fetchall("select * from cm_tg_order where g_id={$id} and openid='{$openid}' and uniacid={$_W['uniacid']} and godluck=1");
        if(count($haszj)>0){
            $is_allow = 0;
        }else{
            $is_allow = 1;
        }
    }

}

if (empty($_SESSION['type'])) {

    switch (intval($goods['deliverytype'])) {
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
if ($_GPC['newtuan'] == 'newtuan') {

    $tuan_id = '';
    $_SESSION['tuan_id'] = NULL;

}
if ($goods['one_group'] == 1 && !empty($_GPC['tuan_id'])) {
    $group = pdo_fetch("SELECT * FROM " . tablename('tg_group') . ' WHERE goodsid=:goodsid AND uniacid=:uniacid AND groupstatus=3 ORDER BY lacknum ASC LIMIT 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $goods['id']));
    if (!empty($group)) {
        $tuan_id = $group['groupnumber'];
    }
}

$goods['deliverytype'] = intval($goods['deliverytype']);
$groupnum = $_SESSION['groupnum'] = isset($_GPC['groupnum']) ? intval($_GPC['groupnum']) : $_SESSION['groupnum'];

$optionid = $_SESSION['optionid'] = isset($_GPC['optionid']) ? intval($_GPC['optionid']) : $_SESSION['optionid'];
$storelist = pdo_fetchall("select * from" . tablename('tg_store') . "where uniacid='{$_W['uniacid']}' and status=1");
if ($goods['selltype'] == 7) {
    $goods['gprice'] = $goods['preprice'];
    $groupnum = 1000000000;
}
//wl_debug($_SESSION);
if (empty($_GPC['num'])) {
    if ($_SESSION['num']) {
        $_GPC['num'] = $_SESSION['num'];
    } else {
        $_GPC['num'] = 1;
    }
}

$_SESSION['num'] = $num = isset($_GPC['num']) ? intval($_GPC['num']) : $_SESSION['num'];

if ($num == 0) {
    $num = 1;
}
$addrid = isset($_GPC['addrid']) ? intval($_GPC['addrid']) : 0;

$is_usecard = 0;

$memb = member_get_by_params("openid='{$openid}'");
if ($goods['selltype'] != 4 && $goods['selltype'] != 7) {
    //查询这个团是否支付成功参加
    if ($tuan_id) {
        $nowtuan = group_get_by_params(" groupnumber = '{$tuan_id}'");
        if ($nowtuan['groupstatus'] != 3) {
            $tuan_id = '';
            $_SESSION['tuan_id'] = NULL;
        }
        if ($nowtuan['selltype'] == 3) {
            $goods['gprice'] = $nowtuan['price'];
        }
        if (!empty($tuan_id)) {
            $myorder = order_get_by_params(" tuan_id = {$tuan_id} and openid = '{$openid}' and status in(1,2,3,4,6,7) ");
            if (!empty($myorder)) {
                $tuan_id = '';
            }
        }

    }
}
//开启首单免运费
if ($config['base']['firstfree'] == 1) {
    $firstfree = 1;
    $oldbuy = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . ' WHERE openid=:openid AND status  IN (1,2,3,8)', array(':openid' => $openid));
    if (count($oldbuy) >= 1) {
        $firstfree = 0;
    }
}


$storesids = explode(",", $goods['hexiao_id']);
foreach ($storesids as $key => $value) {
    if ($value) {
        $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
    }
}
$yunfeiids = explode(",", $goods['yunfei_id']);
foreach ($yunfeiids as $key => $value) {
    if ($value) {
        $dispatch_list[$key] = pdo_fetch("select * from " . tablename('tg_delivery_template') . " where id = {$value} and status = 2");
    }
}
//阶梯团和规格的判断
if ($goods['group_level_status'] == 2 && $goods['selltype'] == 3) {
    $param_level = unserialize($goods['group_level']);
    foreach ($param_level as $k => $v) {
        if ($groupnum == $v['groupnum']) {
            $goods['gprice'] = $v['groupprice'];
            break;
        }
    }
} elseif ($goods['hasoption'] == 1) {

    if (!empty($optionid)) {
        $option = pdo_fetch("select title,productprice,marketprice,stock,weight,id from" . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $optionid));
    } else {
        //wl_message('抱歉出错了哦，请返回重试！');
    }
    $goods['gprice'] = $option['marketprice'];
    $goods['oprice'] = $option['productprice'];
    $goods['optionname'] = $option['title'];
    $goods['optionid'] = $option['id'];
    $goods['stock'] = $option['stock'];
    $goods['weight'] = $option['weight'];

}

//判断团长优惠
if ($goods['is_discount'] == 1 && $goods['selltype'] != 4) {
    if (empty($goods['firstdiscount'])) {
        $goods['firstdiscount'] = 0;
    }
    $firstdiscount = $goods['firstdiscount'];
}
$price = $goods['gprice'];
if (!empty($_GPC['groupnum'])) {
    if ($_GPC['groupnum'] > 1) {
        $price = $goods['gprice'];
        $is_tuan = 1;
        if (empty($tuan_id)) {
            $tuan_first = 1;
        } else {
            $tuan_first = 0;
            $firstdiscount = 0;
        }
    } else {
        $price = $goods['oprice'];
        $is_tuan = 0;
        $tuan_first = 0;
        $firstdiscount = 0;
    }
} else {
    //$price = $goods['gprice'];
    //wl_message('抱歉出错了哦，请返回重试！');
}
if ($goods['selltype'] == 0 || $goods['selltype'] == 1) {
//会员的openid
    $openid = $_W["openid"];
//查询会员等级享受的折扣
    $res = pdo_fetch("SELECT * FROM " . tablename("tg_member") . " WHERE uniacid=:uniacid AND openid=:openid", array(":uniacid" => $_W["uniacid"], ":openid" => $openid));
    $level = $res["level"];//会员等级
//查询相应等级对应的权益是多少
    $level_rights = pdo_fetch("SELECT * FROM " . tablename("tg_member_leave") . " WHERE uniacid=:uniacid AND id=:id", array(":uniacid" => $_W["uniacid"], ':id' => $level));
//计算会员折扣
    $rights = $level_rights["rights"];
    $discount_num = 10;
    if ($goods['discount'] == 1) {
        if ((empty($rights) || !$rights)) {
            //不打折计算原价
            $discount_num = 10;
        } else {
            $price = $price * $rights / 10;
            $discount_num = $rights;
        }
    }
}


//满包邮
$overfreemoney = $config['base']['over_free'];

$nowshopprice = $price * $num;
if ($overfreemoney <= $nowshopprice && $overfreemoney > 0 && $goods['one_limit'] > 1) {
    $overfree = 1;
}
$adress_fee = pdo_fetch("select * from " . tablename('tg_address') . " where openid = '$openid' and status = 1");

if ($tuan_id > 0 && $goods['selltype'] == 2) {

    $firstorder = order_get_by_params(" tuan_id = {$tuan_id} and tuan_first = 1");
    $addid = $firstorder['addressid'];
    $firstopenid = $firstorder['openid'];
    $first = member_get_by_params("openid='{$firstopenid}'");
    $adress_fee_tuan = pdo_fetch("select * from " . tablename('tg_address') . "where id = '{$addid}'  ");
    $adress_fee1 = pdo_fetch("select * from " . tablename('tg_address') . "where openid = '$openid' and status = 1");
    $adress_fee_tuan['cname'] = $adress_fee1['cname'];
    $adress_fee_tuan['tel'] = $adress_fee1['tel'];
}

if ($_GPC['op'] == 'at') {
    $ad = $_GPC['ad'];
    //送货时间
    $sendtimes = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid ='{$_W['uniacid']}' and merchantid = '{$goods['merchantid']}' and status=1 order by starttime asc");
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
    if ($goods['isfree'] == 1 || $firstfree == 1 || $overfree == 1) {
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
    $dtime = date("Y-m-d", $dtime);
    $ttime = array();
    foreach ($sendtimes as $k => $v) {
        $valtime = $v['starttime'] . ":00-" . $v['endtime'] . ":59";
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
        $psql1 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and sendtime='{$valtime}' and senddate='{$dtime}'   and merchantid = '{$goods['merchantid']}' and status not in (0,4,5,9)");
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
    $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id =:id", array(':id' => $_GPC['id']));
    $sid = $_GPC['sid'];
    $scost = pdo_fetch("select * from" . tablename('tg_store') . "where id =:id and uniacid=:uniacid and merchantid=:merchantid", array(':id' => $sid, ':uniacid' => $_W['uniacid'], ':merchantid' => $goods['merchantid']));
    $_SESSION['freight'] = sprintf("%.2f", $scost['cost']);
    if (empty($scost['cost'])) {
        $_SESSION['freight'] = 0;
    }
    $_SESSION['type'] = 3;
    if ($goods['isfree'] == 1) {
        $cost = 0;
        $_SESSION['freight'] = 0;
    }
    $_SESSION['ziti'] = $sid;
    echo $_SESSION['freight'];
    exit;
}
if ($_GPC['op'] == 'sh') {
    $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and merchantid='{$goods['merchantid']}' and dispatchtype=2 and enabled=1 ORDER BY id asc");
    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
    $_SESSION['type'] = 1;
    $_SESSION['freight'] = 0;

    if ($goods['selltype'] == 1 || $goods['selltype'] == 2) {
        $bprice = sprintf("%.2f", floatval($goods['gprice']) * floatval($num));
    }
    if ($goods['selltype'] == 0) {
        $bprice = sprintf("%.2f", floatval($goods['oprice']) * floatval($num));
    }
    $cost = 0;
    $temp = 0;
    $i = 0;
    $cout = count($dispatch_delivery);

    foreach ($dispatch_delivery as $key => $item) {
        $cart = sprintf("%.2f", $item['cart']);
        $i = $i + 1;
        if ($bprice >= $temp && $bprice <= $cart) {
            $cost = $item['cost'];
        }
        $temp = sprintf("%.2f", $item['cart']);

        if ($i == $cout && $bprice > $temp && $i > 1) {
            $cost = $item['cost'];
        }
    }
    if ($goods['isfree'] == 1 || $firstfree == 1) {
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
    $weight = $num * $goods['weight'];
    $setting = setting_get_by_name("autoaddress");
    $akb = 0;
    $akbs = 0;
    if (intval($setting['autoaddr']) == 0) {
        $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
        $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
        $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");
        $akb = 1;
    } else {
        if ($setting['addrtype'] == 0) {
            $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
            $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");
            $akb = 2;
        }
        if ($setting['addrtype'] == 1) {
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}'  and template_id={$tid}");
            $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}' and district='{$c}' and template_id={$tid}");
            $akb = 3;
        }
        if ($setting['addrtype'] == 2) {
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}'  and template_id={$tid}");
            $akb = 4;
        }
    }
    if (!empty($province_fee)) {
        $akbs = 1;
        $free = sprintf("%.2f", $province_fee['free']);
        $first_fee = sprintf("%.2f", $province_fee['first_fee']);
        $first_weight = sprintf("%.2f", $province_fee['first_weight']);
        $second_fee = sprintf("%.2f", $province_fee['second_fee']);
        $second_weight = sprintf("%.2f", $province_fee['second_weight']);
    }
    if (!empty($city_fee)) {
        $akbs = 2;
        $free = sprintf("%.2f", $city_fee['free']);
        $first_fee = sprintf("%.2f", $city_fee['first_fee']);
        $first_weight = sprintf("%.2f", $city_fee['first_weight']);
        $second_fee = sprintf("%.2f", $city_fee['second_fee']);
        $second_weight = sprintf("%.2f", $city_fee['second_weight']);
    }
    if (!empty($district_fee)) {
        $akbs = 3;
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

    $logdata = array(
        'orderno' => 2 * $num,
        'log' => $akbs,
        'from' => "select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}"
    );

    //file_put_contents(TG_DATA."yunfei2.log", var_export($logdata, true).PHP_EOL, FILE_APPEND);
    if (intval($goods['isfree']) == 1 || $firstfree == 1 || ($checkfree_price <= 0.00 && $free > 0.00)) {
        $freight = 0;
    }
    $_SESSION['ziti'] = $tid;
    ////////
    $_SESSION['freight'] = $freight;

    echo $freight;
    exit;
}

//武汉同城  自提运费
if ($_GPC['op'] == 'tc') {

    $storeid = intval($_GPC['storeid']);
    $store = pdo_get('tg_store' , array('id' => $storeid));
    $store['cost'] = number_format($store['cost'] , 2);
    if ($goods['selltype'] == 2 && $is_tuan == 1 && $tuan_first == 1) {
        $store['cost'] = 0;
    }
    die(json_encode(array('status' => 1 , 'data' => $store)));
}

///////
//支付价格

$pay_price = sprintf("%.2f", $price * $num - $firstdiscount + $_GPC['freight']);

//获取可用优惠券
$coupon = coupon_goodscanuse($openid, $pay_price, $goods['id']);

$is_commander = 0;
if ($goods['is_commander_fee'] == 1 && empty($tuan_id) && $is_tuan == 1) {
    $commander = pdo_get('tg_member' , array('uniacid' => $_W['uniacid'] , 'from_user' => $_W['openid']));
    if ($commander['apply_status'] == 1) {
        $is_commander = 1;
    }
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
    $link_zt = intval($_SESSION['ziti']);
    if ($tuan_id > 0 && $goods['selltype'] == 2 && $goods['community'] == 1) {
        $addressid = $_GPC['addrid'];
        $adress_fee = pdo_fetch("select * from " . tablename('tg_address') . " where openid = '$openid' and id={$addressid}");
        $firstorder = order_get_by_params(" tuan_id = {$tuan_id} and tuan_first = 1");
        $addid = $firstorder['addressid'];
        $firstopenid = $firstorder['openid'];
        $first = member_get_by_params("openid='{$firstopenid}'");
        $adress_fee_tuan = pdo_fetch("select * from " . tablename('tg_address') . "where id = '{$addid}'  ");
        if($adress_fee['village'] != $adress_fee_tuan['village']|| $adress_fee['province'] != $adress_fee_tuan['province'] || $adress_fee['county'] != $adress_fee_tuan['county'] || $adress_fee['city'] != $adress_fee_tuan['city']){
            wl_json(0, "您的小区与团长所在小区不符，请核对后下单");
        }

    }elseif($tuan_id > 0 && $goods['selltype'] == 2 && $goods['community'] == 0){
        if (empty($_GPC['tname'])) {
            wl_json(0, "未填写提货人姓名");
        }
        if (empty($_GPC['ttel'])) {
            wl_json(0, "未填写提货人电话");
        }
        $adress_fee['cname'] = $_GPC['tname'];
        $adress_fee['tel'] = $_GPC['ttel'];
        $_SESSION['type'] = $firstorder['dispatchtype'];
        $link_zt = $firstorder['comadd'];
        pdo_update('tg_member', array('addname' => $_GPC['tname'], 'addmobile' => $_GPC['ttel']), array('openid' => $openid, 'uniacid' => $_W['uniacid']));
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
    if (!$tuan_id && $is_tuan == 0) {
        $goods['selltype'] = 0;
    }
    if ($tuan_id > 0 && !$is_tuan) {
        $is_tuan = 1;
    }

    // 极限单品库存判断
    $supply = pdo_fetch("select stock,hasoption from " . tablename('tg_supply_goods') . " where id = '{$goods['supply_goodsid']}' ");
    if ($supply) {
        $_SESSION['type'] = 2;
        if ($supply['hasoption']) {
            $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $goods['supply_goodsid'], ':title' => $goods['optionname']));
            $supply['stock'] = $option['stock'];
        }
        if ($supply['stock'] < $num) {
            $result = array('result' => '库存不足');
            wl_json(-1, $result);
        }
    }

    if ($acct['tpl'] == '8209') {
        $link_zt = intval($_GPC['link_zt']);
    }
    // 积分计算
    $score = setting_get_by_name("score");
    $score_num = 0;
    if ($score['score_apply'] == 1 && floatval($goods['score_num']) > 0) {
        if ($score['member_score_apply'] == 1) {
            $member = member_get_by_params(" openid='{$openid}' and uniacid='{$_W['uniacid']}'");
            if ($member['member_status'] == "1") {
                $score_num = floatval($goods['score_num']);
            }
        } else {
            $score_num = floatval($goods['score_num']);
        }
    }
    $is_score = $score_num > 0 ? '1' : '0';
    $data = array(
        'uniacid' => $_W['uniacid'],
        'gnum' => $num,
        'openid' => $openid,
        'ptime' => '',//支付成功时间
        'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
        'pay_price' => $pay_price,
        'goodsprice' => $price * $num,
        'goodsname' => $goods['gname'],
        'freight' => $_GPC['freight'],
        'first_fee' => $firstdiscount,
        'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid' => $adress_fee['id'],
        'addresstype' => $adress_fee['type'],//1公司2家庭
        'dispatchtype' => $_SESSION['type'],//配送方式
        'comadd' => $link_zt,//
        'addname' => $adress_fee['cname'],
        'mobile' => $adress_fee['tel'],
        'address' => $adress_fee['province'] . $adress_fee['city'] . $adress_fee['county'] . $adress_fee['detailed_address'],
        'province' => $adress_fee['province'],
        'city' => $adress_fee['city'],
        'county' => $adress_fee['county'],
        'detailed_address' => $adress_fee['detailed_address'],
        'g_id' => $id,
        'tuan_id' => $tuan_id,
        'is_tuan' => $is_tuan,
        'tuan_first' => $tuan_first,
        'starttime' => TIMESTAMP,
        'senddate' => $dtime,
        'sendtime' => $_GPC['sendtime'],
        'remark' => $_GPC['remark'],
        'comtype' => 0,
        'commission' => $goods['commission'],
        'com_type' => $goods['com_type'],
        'commissiontype' => $goods['commissiontype'],
        'endtime' => $goods['endtime'],
        'is_hexiao' => $is_hexiao,
        'hexiaoma' => $str,
        'selltype' => $goods['selltype'],
        'credits' => $goods['credits'],
        'optionname' => $goods['optionname'],
        'optionid' => $optionid,
        'couponid' => $couponid,
        'is_usecard' => $is_usecard,
        'discount_fee' => $discount_fee,
        'createtime' => TIMESTAMP,
        'bdeltime' => $bdeltime,
        'issued' => 0,
        'print_id' => $_GPC["print_id"],
        'couponsids' => $goods['couponsids'],
        'merchantid' => $goods['merchantid'],
        'zititime' => $goods['zititime'],
        'supply_goodsid' => $goods['supply_goodsid'],
        'idcard' => $_GPC['idcard'],
        'score' => $score_num,
        'is_score' => $is_score,
        'is_once_card' => $goods['is_once_card'],
        'once_card_day' => $goods['once_card_day'],
        'once_card_num' => $goods['once_card_num'],
        'once_card_json' => $_GPC['once_card'],
        'self_time' => strtotime($_GPC['self_time'])
    );
    if ($goods['selltype'] == 4) {
        if (empty($tuan_id)) {
            $group_level = unserialize($goods['group_level']);
            $maxnum = 0;
            foreach ($group_level as $value) {
                if ($maxnum < $value['groupnum']) {
                    $maxnum = $value['groupnum'];
                }
            }
            if ($data['gnum'] > $maxnum) {
                $result = array('result' => '超过最大购买数量');
                wl_json(-1, $result);
            }
        } else {
            $tuan = pdo_fetch("select * from " . tablename('tg_group') . " where groupnumber = '{$tuan_id}'");
            //校验延时
            $sleeptime = rand(0.01, 0.5);
            sleep($sleeptime);
            if ($tuan['lacknum'] < $data['gnum']) {
                $result = array('result' => '已无参团数量');
                wl_json(-1, $result);
            }
        }
    }
    pdo_insert('tg_order', $data);
    $orderid = pdo_insertid();

    if ($typeid == 2 || $typeid == 4) {
        /*二维码*/
//        wl_load()->classs('qrcode');
//        $createqrcode = new creat_qrcode();
//        $createqrcode->creategroupQrcode($data['orderno']);
    }

    if (empty($tuan_id)) {
        $groupnumber = $orderid;
        if ($data['is_tuan'] == 1 && $orderid != 0) {
            $starttime = time();
            $endtime = $starttime + $goods['endtime'] * 3600;
            $selltype = $goods['selltype'];
            if ($selltype == 7) {
                $goods['on_success'] = 1;
            }
            $data2 = array(
                'uniacid' => $_W['uniacid'],
                'groupnumber' => $groupnumber,
                'groupstatus' => 3,//订单状态,1组团失败，2组团成功，3,组团中
                'goodsid' => $goods['id'],
                'goodsname' => $goods['gname'],
                'neednum' => $groupnum,
                'lacknum' => $groupnum,
                'starttime' => $starttime,
                'selltype' => $selltype,
                'endtime' => $endtime,
                'on_success' => $goods['on_success'],
                'price' => $price,
                'group_level' => $goods['group_level'],
                'no_num_status' => 1,
                'no_num_success' => strtotime("-" . $goods['no_num_success'] . " minute", $endtime),
                'supply_goodsid' => $goods['supply_goodsid'],
                'merchantid' => $goods['merchantid']
            );
            pdo_insert('tg_group', $data2);
            pdo_update('tg_order', array('tuan_id' => $orderid), array('id' => $orderid));
        }
    }

    $params['orderno'] = $data['orderno'];
    $params['pay_price'] = $data['pay_price'];
    $_SESSION['isshop'] = NULL;
    wl_json(1, $params);
}

if ($acct['tpl'] == '8209') {
    include wl_template('order/order_confirm1');
} else {
    include wl_template('order/order_confirm');
}
exit();
//include wl_template('order/confirm');