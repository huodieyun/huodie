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
$issendtime = checkfunc(8158);
$istuanyouhui = checkfunc(8159);//判定团长优惠

$sendtime_setting = setting_get_by_name("sendtime");
$ops = array('zt', 'sh', 'ck', 'display', 'post');
$op = in_array($op, $ops) ? $op : 'display';
$openid = $_W['openid'];
$pagetitle = '订单提交';
global $_W;
session_start();
if (empty($_SESSION['type'])) {
    $_SESSION['type'] = 0;
    $type = 0;
}
$type = $_SESSION['type'];
$id = intval($_GPC['id']);
$kj_info = pdo_get("tg_kj_log", array("id" => $_GPC["kid"]));

$_SESSION['goodsid'] = intval($_GPC['id']);
$tuan_id = $_SESSION['tuan_id'] = isset($_GPC['tuan_id']) ? intval($_GPC['tuan_id']) : $_SESSION['tuan_id'];
if (!empty($_GPC['tuan_id'])) {
    $tuan_id = $_GPC['tuan_id'];
}
//判断商品id
if (!empty($id)) {
    $goods = goods_get_by_params(" id = {$id} ");
} else {
    header("Location: " . app_url('goods/list'));
    exit;
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
if (intval($kj_info["optionid"]) == 0) {
    unset($optionid);
} else {
    $optionid = $kj_info["optionid"];
    $_SESSION['optionid'] = $kj_info["optionid"];
}
//echo "<pre>";
//var_dump($kj_info["optionid"]);die();
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
        $dispatch_list[$key] = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id ='{$value}' and uniacid='{$_W['uniacid']}' and status=2");
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
        if ($goods['selltype'] == 10) {
            $kid = $_GPC["kid"];
            //获取现在的砍价属性
            $kj_info = pdo_get("tg_kj_log", array("id" => $kid));
            $price = $kj_info["price"];
        }
        $option = pdo_fetch("select title,productprice,marketprice,stock,weight from" . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $kj_info["optionid"]));
    } else {
        //wl_message('抱歉出错了哦，请返回重试！');
    }
    $goods['gprice'] = $kj_info["price"];
    $goods['oprice'] = $kj_info["price"];
//    $goods['gprice'] = $option['marketprice'];
//    $goods['oprice'] = $option['productprice'];
    $goods['optionname'] = $option['title'];
    $goods['stock'] = $option['stock'];
    $goods['weight'] = $option['weight'];
//    file_put_contents(TG_DATA . "ceshi.log", var_export($goods, true) . PHP_EOL, FILE_APPEND);
}
$goods['gprice'] = $kj_info["price"];
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
//满包邮
$overfreemoney = $config['base']['over_free'];

$nowshopprice = $price * $num;
if ($overfreemoney <= $nowshopprice && $overfreemoney > 0 && $goods['one_limit'] > 1) {
    $overfree = 1;
}
$adress_fee = pdo_fetch("select * from " . tablename('tg_address') . "where openid = '$openid' and status = 1");

if ($tuan_id > 0 && $goods['selltype'] == 2) {

    $firstorder = order_get_by_params(" tuan_id = {$tuan_id} and tuan_first = 1");
    $addid = $firstorder['addressid'];
    $firstopenid = $firstorder['openid'];
    $first = member_get_by_params("openid='{$firstopenid}'");
    $adress_fee = pdo_fetch("select * from " . tablename('tg_address') . "where id = '{$addid}'  ");
    $adress_fee1 = pdo_fetch("select * from " . tablename('tg_address') . "where openid = '$openid' and status = 1");
    $adress_fee['cname'] = $adress_fee1['cname'];
    $adress_fee['tel'] = $adress_fee1['tel'];
}

if ($_GPC['op'] == 'at') {
    $ad = $_GPC['ad'];
    //送货时间
    $sendtimes = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid ='{$_W['uniacid']}'  and merchantid = '{$goods['merchantid']}' and status=1 order by starttime asc");
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
        $bprice = sprintf("%.2f", $goods['gprice']);
    }
    if ($goods['selltype'] == 0) {
        $bprice = sprintf("%.2f", $goods['oprice']);
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
///////
//支付价格
if ($goods['selltype'] == 10) {
    $kid = $_GPC["kid"];
    //获取现在的砍价属性
    $kj_info = pdo_get("tg_kj_log", array("id" => $kid));
    $price = $kj_info["price"];
}
$pay_price = sprintf("%.2f", $price * $num - $firstdiscount + $_GPC['freight']);

//获取可用优惠券
$coupon = coupon_goodscanuse($openid, $pay_price, $goods['id']);

//wl_debug($coupon);
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
    if ($tuan_id > 0 && $goods['selltype'] == 2) {
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
    if ($is_tuan == 0) {
        $goods['selltype'] = 0;
    }
    $data = array(
        'uniacid' => $_W['uniacid'],
        'gnum' => 1,
        'openid' => $openid,
        'ptime' => '',//支付成功时间
        'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
        'pay_price' => $pay_price,
        'goodsprice' => $pay_price,
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
        'commissiontype' => $goods['commissiontype'],
        'endtime' => $goods['endtime'],
        'is_hexiao' => $is_hexiao,
        'hexiaoma' => $str,
        'selltype' => 10,
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
        'supply_goodsid' => $goods['supply_goodsid']
    );

    $kid = intval($_GPC["kid"]);
    pdo_update('tg_kj_log' , array('status' => 2 , 'is_on' => 2) , array('id' => $kid));

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
                'selltype' => 10,
                'endtime' => $endtime,
                'on_success' => $goods['on_success'],
                'price' => $pay_price,
                'group_level' => $goods['group_level'],
                'no_num_status' => 1,
                'no_num_success' => strtotime("-" . $goods['no_num_success'] . " minute", $endtime),
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
include wl_template('order/kj_orderconfirm');
exit();
//include wl_template('order/confirm');