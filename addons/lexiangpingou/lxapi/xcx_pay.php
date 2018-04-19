<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/1
 * Time: 14:26
 */


define('IN_API', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/framework/bootstrap.inc.php';
load()->model('reply');
load()->app('common');
load()->classs('wesession');

$op = $_GPC['op'];
if ($op == 'log') {
    file_put_contents("errMsg.log", var_export($_GPC['errMsg'], true) . PHP_EOL, FILE_APPEND);
}
if ($op == 'check_member') {
    //传入JSON字符串
    $obj = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
//    file_put_contents("errMsg.log", var_export($obj, true) . PHP_EOL, FILE_APPEND);
    $openid = $obj->openid;
    $uniacid = $_GPC['uniacid'];
    $xcx = 2;
    $fans = pdo_fetch('select avatar,nickname,xcx,id from ' . tablename('tg_member') . " where openid = :openid and uniacid=:uniacid", array(':uniacid' => $uniacid, ':openid' => $openid));
    $data = array(
        'uniacid' => $uniacid,
        'nickname' => $obj->nickname,
        'avatar' => $obj->avatar,
        'openid' => $obj->openid,
        'from_user' => $obj->openid,
        'xcx' => $xcx,
        'unionid'=>$obj->unionid
    );
    if (empty($fans)) {
        $data['parentid'] = 99;
        $data['intertime'] = TIMESTAMP;
        pdo_insert('tg_member', $data);
    } else {
        pdo_update('tg_member', $data, array('id' => $fans['id']));
    }
    $fans = pdo_fetch('select * from ' . tablename('tg_member') . " where openid = :openid and uniacid=:uniacid", array(':uniacid' => $uniacid, ':openid' => $openid));

    die(json_encode(array('status' => 1, 'member' => $fans)));

}
if ($op == 'app_pay') {
    //传入JSON字符串
    $obj = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);

    $package = array();
    $package['appid'] = $obj->appid;
    $package['mch_id'] = $obj->mch_id;
    $package['nonce_str'] = random(32);
    $package['body'] = $obj->body;
    $package['out_trade_no'] = $obj->out_trade_no;
    $package['total_fee'] = $obj->total_fee;
    $package['spbill_create_ip'] = CLIENT_IP;
    $package['notify_url'] = "https://min.lexiangpingou.cn/" . 'payment/wechat/notify.php';
    $package['trade_type'] = $obj->trade_type;
    $package['openid'] = $obj->openid;
    ksort($package, SORT_STRING);
    $string1 = '';

    foreach ($package as $key => $v) {
        if (empty($v)) {
            continue;
        }


        $string1 .= $key . '=' . $v . '&';
    }

    $string1 .= 'key=' . $obj->paykey;

    $package['sign'] = strtoupper(md5($string1));
    $dat = array2xml($package);
//    spbill_create_ip
    load()->func('communication');
    $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);

    if (is_error($response)) {
        die(json_encode(error(-1, $response['message'])));
    }


    $xml = @simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
//    file_put_contents("xml.log", var_export($response['content'], true) . PHP_EOL, FILE_APPEND);
    if (strval($xml->return_code) == 'FAIL') {
        die(json_encode(error(-2, strval($xml->return_msg))));
    }


    if (strval($xml->result_code) == 'FAIL') {
        die(json_encode(error(-3, strval($xml->err_code) . ': ' . strval($xml->err_code_des))));
    }

//appid="+appid+"&nonceStr=" + noncestr + "&package=prepay_id=wx2015041419450958e073ca4a0071648005&signType=MD5&timeStamp=" + timestamp + "&key="+key

    $prepayid = $xml->prepay_id;


    $wOpt['appid'] = strval($xml->appid);
    $wOpt['partnerid'] = strval($xml->mch_id);
    $wOpt['prepayid'] = strval($xml->prepay_id);
    $wOpt['noncestr'] = strval($xml->nonce_str);
    $wOpt['timestamp'] = TIMESTAMP;
    $wOpt['package'] = 'Sign=WXPay';
//    $wOpt['result_code'] = strval($xml->result_code);
//    $wOpt['return_code'] = strval($xml->return_code);
//    $wOpt['return_msg'] = strval($xml->return_msg);
//    $wOpt['trade_type'] = strval($xml->trade_type);
    ksort($wOpt, SORT_STRING);
//    file_put_contents("test.log", var_export(json_encode($wOpt), true) . PHP_EOL, FILE_APPEND);
    $string = '';
    foreach ($wOpt as $key => $v) {
        $string .= $key . '=' . $v . '&';
    }
    $string .= 'key=' . $obj->paykey;
//    file_put_contents("string.log", var_export($string, true) . PHP_EOL, FILE_APPEND);
    $wOpt['paySign'] = strtoupper(md5($string));
    $wOpt['sign'] = strtoupper(md5($string));
    unset($wOpt['appId']);

    $wOpt['json_str'] = json_encode($wOpt);
    // $wOpt['package'] = 'prepay_id=' . $prepayid;
    // $wOpt['signType'] = 'MD5';
    // $wOpt['timeStamp'] = TIMESTAMP;
//    file_put_contents("test1.log", var_export($wOpt, true) . PHP_EOL, FILE_APPEND);
//    file_put_contents("prepayid.log", var_export($prepayid, true).PHP_EOL, FILE_APPEND);
    die(json_encode($wOpt));

}
if ($op == 'wxpay') {

    $trade_type = 'JSAPI';
    if (!empty($_GPC['trade_type'])) {
        $trade_type = $_GPC['trade_type'];
    }
    $package = array();
    $package['appid'] = $_GPC['appid'];
    $package['mch_id'] = $_GPC['mch_id'];
    $package['nonce_str'] = random(32);
    $package['body'] = $_GPC['body'];
    $package['out_trade_no'] = $_GPC['out_trade_no'];
    $package['total_fee'] = $_GPC['total_fee'];
    $package['spbill_create_ip'] = CLIENT_IP;
    $package['notify_url'] = "https://min.lexiangpingou.cn/" . 'payment/wechat/notify.php';
    $package['trade_type'] = $trade_type;
    $package['openid'] = $_GPC['openid'];
//    file_put_contents("test111.log", var_export($package, true) . PHP_EOL, FILE_APPEND);
    ksort($package, SORT_STRING);
    $string1 = '';

    foreach ($package as $key => $v) {
        if (empty($v)) {
            continue;
        }


        $string1 .= $key . '=' . $v . '&';
    }

    $string1 .= 'key=' . $_GPC['paykey'];

    $package['sign'] = strtoupper(md5($string1));
    $dat = array2xml($package);

    load()->func('communication');
    $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);

    if (is_error($response)) {
        die(json_encode(error(-1, $response['message'])));
    }


    $xml = @simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);

    if (strval($xml->return_code) == 'FAIL') {
        die(json_encode(error(-2, strval($xml->return_msg))));
    }


    if (strval($xml->result_code) == 'FAIL') {
        die(json_encode(error(-3, strval($xml->err_code) . ': ' . strval($xml->err_code_des))));
    }


    $prepayid = $xml->prepay_id;

    $wOpt['appId'] = $_GPC['appid'];
    $wOpt['timeStamp'] = TIMESTAMP . '';
    $wOpt['nonceStr'] = random(32);
    $wOpt['package'] = 'prepay_id=' . $prepayid;
    $wOpt['signType'] = 'MD5';
    ksort($wOpt, SORT_STRING);
    $string = '';
    foreach ($wOpt as $key => $v) {
        $string .= $key . '=' . $v . '&';
    }
    $string .= 'key=' . $_GPC['paykey'];
    $wOpt['paySign'] = strtoupper(md5($string));
    unset($wOpt['appId']);
//    file_put_contents("test.log", var_export($wOpt, true) . PHP_EOL, FILE_APPEND);
    die(json_encode($wOpt));
}
if ($op == 'wx_query') {
    $package = array();
    $package['appid'] = $_GPC['appid'];
    $package['mch_id'] = $_GPC['mch_id'];
    $package['nonce_str'] = random(32);
    $package['out_trade_no'] = $_GPC['out_trade_no'];
    ksort($package, SORT_STRING);
    $string1 = '';
    file_put_contents("./errMsg.log", var_export($_GPC, true) . PHP_EOL, FILE_APPEND);
    foreach ($package as $key => $v) {
        if (empty($v)) {
            continue;
        }
        $string1 .= $key . '=' . $v . '&';
    }
    $string1 .= 'key=' . $_GPC['paykey'];
    $package['sign'] = strtoupper(md5($string1));
    $dat = array2xml($package);
    load()->func('communication');
    $response = ihttp_request('https://api.mch.weixin.qq.com/pay/orderquery', $dat);

    if (is_error($response)) {
        die(json_encode(error(-1, $response['message'])));
    }


    $xml = @simplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
    if (strval($xml->return_code) == 'FAIL') {
        die(json_encode(error(-2, strval($xml->return_msg))));
    }
    if (strval($xml->result_code) == 'FAIL') {
        die(json_encode(error(-3, strval($xml->err_code) . ': ' . strval($xml->err_code_des))));
    }
    $data = array();
    $data['result_code'] = strval($xml->result_code);
    $data['return_msg'] = strval($xml->return_msg);
    $data['transaction_id'] = strval($xml->transaction_id);
    $data['out_trade_no'] = strval($xml->out_trade_no);
    $data['time_end'] = strval($xml->time_end);
    $data['trade_state_desc'] = strval($xml->trade_state_desc);
    $data['total_fee'] = strval($xml->total_fee);
    $data['fee_type'] = strval($xml->fee_type);
    $data['trade_state'] = strval($xml->trade_state);
    die(json_encode($data));
}
if ($op == 'wx_login') {
    global $_GPC;
    global $_W;
    $code = trim($_GPC['code']);
    $appid = trim($_GPC['appid']);
    $appsecret = trim($_GPC['appsecret']);
    die(json_encode(array('status' => 0)));
    if (empty($code)) {
        //  app_error(AppError::$ParamsError);
    }


    $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $code . '&grant_type=authorization_code';
    load()->func('communication');
    $resp = ihttp_request($url);

    if (is_error($resp)) {
        die(json_encode(array('status' => 0, 'message' => $resp['message'])));
        // app_error(AppError::$SystemError, $resp['message']);
    }


    $arr = @json_decode($resp['content'], true);


    if (!(is_array($arr)) || !(isset($arr['openid']))) {
        die(json_encode(array('status' => 0, 'message' => $resp['message'])));
        // app_error(AppError::$WxAppLoginError);
    }


    die(json_encode($arr));
}
if ($op == 'address_edit') {
    //传入JSON字符串
    $obj = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
    $openid = $obj->openid;
    $id = $obj->id;
    $uniacid = $_GPC['uniacid'];

    if (!empty($id)) {
        $address = pdo_fetch('select * from ' . tablename('tg_address') . ' where uniacid=:uniacid and id=:id', array(':uniacid' => $uniacid, ':id' => $id));
    }
    $data = array(
        'openid' => $openid,
        'uniacid' => $uniacid,
        'cname' => $obj->myname,
        'tel' => $obj->myphone,
        'province' => $obj->province,
        'city' => $obj->city,
        'county' => $obj->county,
        'detailed_address' => $obj->detailed,
        'type' => $obj->ctype,
        'ctype' => 1,
        'status' => $obj->status,
        'addtime' => TIMESTAMP
    );
    if ($data['status'] == 1) {
        pdo_update('tg_address', array('status' => 0), array('status' => 1, 'openid' => $openid));
    }

    if (!empty($id)) {
        pdo_update('tg_address', $data, array('id' => $id));
    } else {
        pdo_insert('tg_address', $data);
    }
    die(json_encode(array('status' => 1)));
}
if ($op == 'sh_freight') {
    $uniacid = $_GPC['uniacid'];
    $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$uniacid}' and dispatchtype=2 and enabled=1 ORDER BY id asc");
    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
    $bprice = $_GPC['price'];
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
    die(json_encode(array('freight' => $cost)));
}
/*
 * 自提运费
 * min_cart_api.php?op=zt_freight
 * 传入 uniacid gid sid
 */
if ($op == 'zt_freight') {
    $sid = $_GPC['sid'];
    $uniacid = $_GPC['uniacid'];
    $scost = pdo_fetch("select cost from" . tablename('tg_store') . "where id ='{$sid}' and uniacid='{$uniacid}'");
    $freight = sprintf("%.2f", $scost['cost']);
    if (empty($_GPC['gid'])) {
        $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id =:id", array(':id' => $_GPC['id']));
        if ($goods['isfree'] == 1) {
            $freight = 0;
        }
    }
    die(json_encode(array('status' => 1, 'freight' => $freight)));
}
/*
 * 购物车运费
 * URL: min_cart_api.php?op=freight
 * 传入 uniacid openid province  city county tid
*/
if ($op == 'freight') {
    //传入JSON字符串
    $obj = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
    $uniacid = $_GPC['uniacid'];
    $openid = $obj->openid;
    $p = $obj->province;
    $c = $obj->city;
    $d = $obj->county;
    $tid = $obj->tid;
    $freight = 0;
    $weight = $obj->weight;
    $orderlist = pdo_fetchall("select * from" . tablename('tg_collect') . "where uniacid='{$uniacid}' and orderno=0 and openid='{$openid}'");
//    foreach ($orderlist as $key => $value) {
//        $weight=$weight+$value['num']*$value['weight'];
//    }
    $settings = pdo_fetch("select * from" . tablename('tg_setting') . "where uniacid={$uniacid} and cm_tg_setting.key='autoaddress'");
    $setting = unserialize($settings['value']);

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
    if (($checkfree_price <= 0.00 && $free > 0.00) || $weight == 0) {
        $freight = 0;
        $free = 0;
    }
    ////////
    $fdata = array('freight' => $freight, 'free' => $free, 'orderlist' => $orderlist, 'autoaddr' => $setting['autoaddr']);
    die(json_encode($fdata));
}

/*
 * 购物车提交
 * URL: min_cart_api.php?op=order_submit
 * 传入 uniacid openid province  city county address addressid senddate dispatchtype link_zt pay_price cname tel discount_fee
 * remark sendtime is_hexiao is_usecard ,freight
*/
if ($op == 'order_submit') {
    $obj = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
    $uniacid = $_GPC['uniacid'];
    $openid = $obj->openid;
    if (!empty($obj->senddate)) {
        $date1 = date('Y-m-d');
        if ($obj->senddate == 1) {
            $dtime = date('Y-m-d');
        } elseif ($obj->senddate == 2) {
            $dtime = date('Y-m-d', strtotime($date1 . "+1 day"));
        } elseif ($obj->senddate == 3) {
            $dtime = date('Y-m-d', strtotime($date1 . "+2 day"));
        }
    }
    $discount_fee = $obj->discount_fee;
    $pay_price = $obj->pay_price;
    $is_hexiao = $obj->is_hexiao;
    $is_usecard = $obj->is_usecard;
    $couponid = $obj->couponid;

    $data = array(
        'uniacid' => $uniacid,
        'gnum' => 0,
        'openid' => $openid,
        'ptime' => '',//支付成功时间
        'orderno' => $obj->orderno,
        'pay_price' => $pay_price,
        'goodsprice' => $pay_price + $discount_fee - $obj->freight,
        'freight' => $obj->freight,
        'first_fee' => 0,
        'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid' => 0,
        'addresstype' => $obj->addresstype,//1公司2家庭
        'dispatchtype' => $obj->dispatchtype,//配送方式
        'comadd' => $obj->store_id,//
        'addname' => $obj->cname,
        'mobile' => $obj->tel,
        'address' => $obj->province . $obj->city . $obj->county . $obj->address,
        'g_id' => 0,
        'tuan_id' => 0,
        'is_tuan' => 0,
        'tuan_first' => 0,
        'discount_fee' => $discount_fee,
        'starttime' => TIMESTAMP,
        'remark' => $obj->remark,
        'comtype' => 0,
        'senddate' => $dtime,
        'selltype' => 0,
        'sendtime' => $obj->sendtime,
        'commission' => 0,
        'commissiontype' => 0,
        'endtime' => 0,
        'is_hexiao' => $is_hexiao,
        'hexiaoma' => '',
        'couponid' => $couponid,
        'is_usecard' => $is_usecard,
        'createtime' => TIMESTAMP,
        'bdeltime' => ''
    );
    if (pdo_insert('tg_order', $data)) {
        pdo_update('tg_collect', array('orderno' => $data['orderno']), array('openid' => $openid, 'uniacid' => $uniacid, 'orderno' => 0));
        //更新优惠券状态
        if($data['couponid']){
            pdo_update('tg_coupon',array('use_time'=>time()),array('openid'=>$data['openid'],'id'=>$data['couponid']));
        }
    }
    die(json_encode(array('status' => 1)));
}

if ($op == 'order_corfim') {
    $obj = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
    $goods = pdo_fetch('select * from ' . tablename('tg_goods') . ' where id=:id', array(':id' => $obj->g_id));
    $tuan_id = intval($obj->tuan_id);
    $is_tuan = intval($obj->is_tuan);
    if ($tuan_id) {
        $tuan_first = 0;
    } elseif ($is_tuan) {
        $tuan_first = 1;
    }
    $data = array(
        'uniacid' => $_GPC['uniacid'],//公众号ID
        'gnum' => $obj->gnum,//购买数量
        'openid' => $obj->openid,//openid
        'ptime' => '',//支付成功时间
        'orderno' => $obj->orderno,//订单编号
        'pay_price' => $obj->pay_price,//支付金额（商品金额+运费-优惠金额-团长优惠）
        'goodsprice' => $obj->goodsprice,//商品金额（商品单价*购买数量）
        'goodsname' => $goods['gname'],//商品名称
        'freight' => $obj->freight,//运费
        'first_fee' => $obj->firstdiscount,//团长优惠
        'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid' => $obj->addressid,
        'addresstype' => $obj->addresstype,//1公司2家庭
        'dispatchtype' => $obj->dispatchtype,//配送方式
        'comadd' => $obj->store_id,//
        'addname' => $obj->cname,
        'mobile' => $obj->tel,
        'address' => $obj->province . $obj->city . $obj->county . $obj->address,
        'province' => $obj->province,
        'city' => $obj->city,
        'county' => $obj->county,
        'detailed_address' => $obj->address,
        'g_id' => $obj->g_id,
        'tuan_id' => $obj->tuan_id,
        'is_tuan' => $obj->is_tuan,//拼团为1，其它类型为0
        'tuan_first' => $tuan_first,
        'starttime' => TIMESTAMP,
        'senddate' => $obj->senddate,//送货日期
        'sendtime' => $obj->sendtime,//送货时间
        'remark' => $obj->remark,//订单备注
        'comtype' => 0,
        'commission' => '',//佣金金额
        'commissiontype' => '',//佣金计算类型
        'is_hexiao' => 0,
        'selltype' => $goods['selltype'],//团购类型
        'credits' => '',//积分
        'optionname' => $obj->optionname,//规格
        'optionid' => $obj->optionid,//规格ID
        'couponid' => $obj->couponid,//优惠券ID
        'is_usecard' => $obj->is_usecard,//使用优惠券为1，不使用为0
        'discount_fee' => $obj->discount_fee,//优惠券优惠金额
        'createtime' => TIMESTAMP,
        'bdeltime' => '',
        'issued' => 0,
        'origin' => $obj->origin,
        'couponsids' => $goods['couponsids']
    );
    pdo_insert('tg_order', $data);
    $orderid = pdo_insertid();
    //更新优惠券状态
    if($data['couponid']){
        pdo_update('tg_coupon',array('use_time'=>time()),array('openid'=>$data['openid'],'id'=>$data['couponid']));
    }

    if ($_GPC['dispatchtype'] == 3) {
        pdo_update('tg_member', array('addname' => $obj->cname, 'addmobile' => $obj->tel), array('openid' => $obj->openid, 'uniacid' => $_GPC['uniacid']));

    }
    /*
	if($typeid == 2 || $typeid==4){

		wl_load()->classs('qrcode');
		$createqrcode =  new creat_qrcode();
		$createqrcode->creategroupQrcode($data['orderno']);
	}
*/
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
                'uniacid' => $_GPC['uniacid'],
                'groupnumber' => $groupnumber,
                'groupstatus' => 3,//订单状态,1组团失败，2组团成功，3,组团中
                'goodsid' => $goods['id'],
                'goodsname' => $goods['gname'],
                'neednum' => $goods['groupnum'],
                'lacknum' => $goods['groupnum'],
                'starttime' => $starttime,
                'selltype' => $goods['selltype'],
                'endtime' => $endtime,
                'on_success' => $goods['on_success'],
                'price' => $goods['gprice'],
                'group_level' => $goods['group_level']
            );
            pdo_insert('tg_group', $data2);
            pdo_update('tg_order', array('tuan_id' => $orderid), array('id' => $orderid));
        }
    }
    die(json_encode(array('status' => 1)));
}

if ($op == 'group_list') {

    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $status = $_GPC['status'];
    $condition = ' and `uniacid` = :uniacid and openid=:openid and is_tuan=1';
    $params = array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid']);
    if ($_GPC['status'] >= 0) {
        $condition .= "  AND tuan_id in (select groupnumber from cm_tg_group where `uniacid` = :uniacid and groupstatus = {$status})";
    }
    $condition .= ' group by tuan_id';
    $orderby = 'order by id desc';
    $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
    $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
    $sql = "SELECT * FROM " . tablename('tg_order') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql, $params);

    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . "where 1 $condition ", $params);
    foreach ($list as $key => $order) {
        $goods = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . "WHERE id = '{$order['g_id']}'");
        $thistuan = pdo_fetch("SELECT * FROM " . tablename('tg_group') . "WHERE groupnumber = '{$order['tuan_id']}' $content");
        $orders[$key]['groupnumber'] = $order['tuan_id'];
        $orders[$key]['groupnum'] = $goods['groupnum'];
        if (!empty($thistuan['price'])) {
            $orders[$key]['gprice'] = $thistuan['price'];
        } else {
            $orders[$key]['gprice'] = $goods['gprice'];
        }
        $orders[$key]['unit'] = $goods['unit'];
        $orders[$key]['gid'] = $goods['id'];
        $orders[$key]['gname'] = $goods['gname'];
        $orders[$key]['gimg'] = tomedia($goods['gimg']);
        $orders[$key]['itemnum'] = $thistuan['lacknum'];
        $orders[$key]['groupstatus'] = $thistuan['groupstatus'];
        $orders[$key]['starttime'] = date('Y-m-d H:i:s', $thistuan['starttime']);

    }
    $data = array();
    $data['list'] = $orders;
    $data['total'] = $total;
    die(json_encode($data));
    //die($sql);

}



if ($op == 'cart_add') {
    $dat = json_decode(file_get_contents("php://input"));
    $uniacid = $_GPC['uniacid'];
    $openid = $dat->openid;
    $id = $dat->gid;
    $str = $dat->guige;//规格
    $weight = $dat->weight;//规格
    $num = $dat->num;//数量
//
//    $openid = $_GPC['openid'];
//    $id = $_GPC['gid'];
//    $str = $_GPC['guige'];//规格
//    $weight = $_GPC['weight'];//规格
//    $num = $_GPC['num'];//数量
    if (empty($num)) {
        $num = 1;
    }

    if (empty($id)) {
//        echo 0;
//        exit;
        die(json_encode(array('status' => 0, 'result' => '添加的商品id为空')));
    } else {
        $sql = 'SELECT oprice,supprices,storeid,credit,commissiontype,commission,merchantid,gname,gnum FROM ' . tablename('tg_goods') . ' WHERE id=:id and uniacid=:uniacid';
        $paramse = array(':id' => $id, ':uniacid' => $uniacid);
        $goods = pdo_fetch($sql, $paramse);
        if (empty($str)) {
            $price = $goods['oprice'];
            $gnum = $goods['gnum'];
            $tt = pdo_fetch("SELECT id,num FROM " . tablename('tg_collect') . " WHERE  uniacid = '{$uniacid}' and sid='{$id}'  and openid='{$openid}' and orderno='0'");
        }else{
            $kunum1 = pdo_fetch("SELECT productprice,stock FROM " . tablename('tg_goods_option') . " WHERE   goodsid='{$id}' and title='{$str}'  ");
            $price = $kunum1['productprice'];
            $gnum = $kunum1['stock'];
            $tt = pdo_fetch("SELECT id,num FROM " . tablename('tg_collect') . " WHERE  uniacid = '{$uniacid}' and sid='{$id}'  and openid='{$openid}' and item='{$str}'  and orderno='0'");

        }
        $discount = 10;
//        if ($goods['discount'] == 1) {
//
//            //证明是计算打折后的价钱 查询会员的等级和折扣详情
//            $level = pdo_fetch("select level from " . tablename("tg_member") . " where uniacid = :uniacid and openid = :openid", array(":uniacid" => $_GPC["uniacid"], ":openid" => $_GPC["openid"]));
//            if (!empty($level)) {
//                //获取会员的等级 匹配会员等级享受的折扣
//                $rights = pdo_fetch("select rights from " . tablename("tg_member_leave") . " where uniacid = :uniacid and id = :id", array(":uniacid" => $_GPC["uniacid"], ":id" => $level['level']));
//                $rights['rights'] = 5;
//                if (!empty($rights) || $rights['rights'] != 0) {
//                    $discount = $rights['rights'];
//                    $price = $price * $rights['rights'] / 10;
////                    计算价钱
//                }
//            }
//        }

        $data = array(
            'openid' => $openid,
            'uniacid' => $uniacid,
            'num' => $num,
            'oprice' => $price,
            'orderno' => 0,
            'applystatus' => 0,
            'optionid' => 0,
            'item' => $str,
            'weight' => $weight,
            'discount_num' => $discount,
            'supprices' => $goods['supprices'],
            'storeid' => $goods['storeid'],
            'credit' => $goods['credit'],
            'type' => $goods['commissiontype'],
            'commission' => $goods['commission'],
            'merchant_id' => $goods['merchantid'],
            'sid' => $id,
            'goodsname' => $goods['gname']
        );

//        $num=$tt['num']+1;

        if (empty($tt)) {
            if ($num > intval($gnum)) {
                $status = -1;
                die(json_encode(array('status' => -1, 'result' => $gnum,'sql'=>$sql)));
            }
            if (pdo_insert('tg_collect', $data)) {
                $status = 1;
            } else {
                $status = 0;
            }
        } else {
            $num = $num + $tt['num'];
            $data['num'] = $num;
            if ($num > intval($gnum)) {
                $status = -1;
                die(json_encode(array('status' => -2, 'result' => $gnum,'sql'=>$sql)));
            }
            pdo_update('tg_collect', array('num' => $num), array('id' => $tt['id']));
            $status = 1;
        }
    }
    die(json_encode(array('status' => $status, 'result' => '添加购物车成功')));
}
if ($op == 'tuan_add') {
    $dat = json_decode(file_get_contents("php://input"));
    $uniacid = $_GPC['uniacid'];
    $openid = $dat->openid;
    $id = $dat->gid;
    $str = $dat->guige;//规格
    $num = $dat->num;//数量
    if (empty($num)) {
        $num = 1;
    }
    if (empty($id)) {
        die(json_encode(array('status' => '-1', 'result' => '商品id不能为空')));
    } else {
        if (empty($str)) {
            $kunum = pdo_fetch("SELECT gnum FROM " . tablename('tg_goods') . " WHERE  uniacid = '{$uniacid}' and id='{$id}'  ");
            $gnum = $kunum['gnum'];
        }else{
            $kunum1 = pdo_fetch("SELECT productprice,stock FROM " . tablename('tg_goods_option') . " WHERE   goodsid='{$id}' and title='{$str}' ");
            $gnum = $kunum1['stock'];
        }
        if ($num > intval($gnum)) {
            $status = -1;
            die(json_encode(array('status' => '0','num'=>$num, 'result' => '库存不住')));
        }else{
            $status = 1;
            die(json_encode(array('status' => '1','num'=>$num,  'result' => '添加成功')));
        }
    }
}


if ($op == 'receipt') {
    $orderno = $_GPC['orderno'];
    $_W['uniacid'] = $_GPC['uniacid'];
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
                $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_collect") . " WHERE orderno = :id", array(":id" => $value['orderno']));
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
            pdo_update('tg_supply_collect' , array('supply_status' => 2 , 'receive_time' => TIMESTAMP) , array('orderid' => $orderno));
        }
        //查询当前订单是否拆单发货
        $c_order = pdo_fetchall("SELECT * FROM ".tablename('tg_order_child')." where orderno='{$value['orderno']}'");

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
        $auto_level = pdo_fetch("SELECT * FROM " . tablename("tg_member_leave") . " WHERE :order_price >= money AND uniacid = :uniacid ORDER BY money DESC", array(":uniacid" => $value["uniacid"], ":order_price" => $order_price));
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

        if ($value["selltype"] == 2 && $value["tuan_first"] == 1 && ($value["status"] == 1 || $value["status"] == 2) && $value["status"] != 9) {
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
                        $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id", array(":id" => $value['g_id']));
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
                        $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_collect") . " WHERE orderno = :id", array(":id" => $value['orderno']));
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
            die(json_encode(array('status' => '1','msg'=>'')));
        } else {
            die(json_encode(array('status' =>'0','msg'=>'确认收货失败！')));
        }
    } else {
        die(json_encode(array('status' => '0','msg'=>'缺少订单号')));
    }
}

function order_update_by_params($data,$params) {
    global $_W;
    $flag = pdo_update('tg_order',$data,$params);
    return $flag;
}




if ($op == 'cube') {
    $settings = pdo_fetch("select * from " . tablename('tg_setting') . " where uniacid = '{$_GPC['uniacid']}' and `key` = 'cube' ");
    $cubes = iunserializer($settings['value']);
    foreach ($cubes ? $cubes : array() as $k => $v) {
        $cubes[$k]['thumb'] = tomedia($v['thumb']);
        if (empty($v['thumb']) || $v['on'] == 0) {
            unset($cubes[$k]);
        }
    }
    die(json_encode(array('cubes' => $cubes)));
}

//app 核销二维码链接
if ($op == 'qrurl') {

    $uniacid = $_GPC['uniacid'];
    $orderno = $_GPC['orderno'];
    $order = pdo_fetch("select * from cm_tg_order where uniacid={$uniacid} and orderno='{$orderno}'");
    $sto = pdo_fetch("select * from cm_tg_store where id ='{$order['comadd']}' and uniacid='{$uniacid}'");
    $url = napp_url($uniacid, 'order/check', array('mid' => $orderno));

    die(json_encode(array('url' => $url,'sto'=>$sto)));

}

//团分享二维码
if ($op == 'tuan_qrcode') {

    $uniacid = $_GPC['uniacid'];
    $tuan_id = $_GPC['tuan_id'];

    $tourl = napp_url($uniacid, 'order/group', array('tuan_id' => $tuan_id));
    $png = createQrcode($uniacid, $tourl);
    die(json_encode(array('png' => $png)));
}

//app链接
function napp_url($uniacid, $segment, $params = array())
{
    list($do, $ac, $op) = explode('/', $segment);
    $roots = 'w9.huodiesoft.com';
    if ($uniacid != 53) {
        $roots = 'www.lexiangpingou.cn';
    }
    $wechat = pdo_fetch('SELECT * FROM ' . tableName('account_wechats') . ' WHERE uniacid=:uniacid', array(':uniacid' => $uniacid));
    $head_http = "http://";
    if ($wechat['is_https'] == 1) {
        $head_http = "https://";
    }
    $url = $head_http . $wechat['key'] . "." . $roots . "/" . 'app/index.php?i=' . $uniacid . '&c=entry&m=lexiangpingou&';
    if ($uniacid == 33) {
        $url = $head_http . $roots . "/" . 'app/index.php?i=' . $uniacid . '&c=entry&m=lexiangpingou&';
    }
    $uni_settings = pdo_fetch('SELECT oauth FROM ' . tablename('uni_settings') . ' WHERE uniacid=:uniacid', array(':uniacid' => $uniacid));
    $oauth = iunserializer($uni_settings['oauth']);
    if (!empty($oauth['host'])) {
        $url = $oauth['host'] . '/app/index.php?i=' . $uniacid . '&c=entry&m=lexiangpingou&';
    }
    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    if (!empty($op)) {
        $url .= "op={$op}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }
    return $url;

}

//生成二维码
function createQrcode($uniacid, $url)
{
    $path = $_SERVER['DOCUMENT_ROOT'] . '/addons/lexiangpingou/data/qrcode/' . $uniacid . '/';
    if (!is_dir($path)) {
        mkdir(dirname($path));
    }
    $file = md5(base64_encode($url)) . '.jpg';
    $qrcode_file = $path . $file;
    if (!is_file($qrcode_file)) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/framework/library/qrcode/phpqrcode.php';
        QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
    }
    $wechat = pdo_fetch('SELECT * FROM ' . tableName('account_wechats') . ' WHERE uniacid=:uniacid', array(':uniacid' => $uniacid));
    $head_http = "http://";
    if ($wechat['is_https'] == 1) {
        $head_http = "https://";
    }
    return $head_http . 'min.lexiangpingou.cn' . '/addons/lexiangpingou/data/qrcode/' . $uniacid . '/' . $file;
//    return $head_http . $_SERVER['SERVER_NAME'] . '/addons/lexiangpingou/data/qrcode/' . $uniacid . '/' . $file;
}

//生成合成图片
if ($op == 'share_image') {

    $uniacid = $_GPC['uniacid'];
    //传入JSON字符串
    $obj = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);
    $share_image = $obj->share_image;
    $tuan_id = $obj->tuan_id;
//    file_put_contents("errMsg.log", var_export($share_image, true) . PHP_EOL, FILE_APPEND);
//    $share_image = substr($share_image, 1, strlen($share_image) - 2);
//    file_put_contents("errMsg.log", var_export($share_image, true) . PHP_EOL, FILE_APPEND);
//    $base64_image_content = $_POST['imgBase64'];

//    die(json_encode($obj));
    //匹配出图片的格式
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $share_image, $result)) {
        $type = $result[2];
        $new_file = "./share_tuan/";
//        die(json_encode(array('png' => $new_file)));
        if (!file_exists($new_file)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($new_file, 0700);
        }
        $new_file = $new_file . $tuan_id . ".{$type}";
        if(file_exists($new_file)){
            $message = 'https://min.lexiangpingou.cn/addons/lexiangpingou/lxapi/share_tuan/'. $tuan_id . ".{$type}";
            die(json_encode(array('png' => $message)));
        }
//        die(json_encode(array('png' => $new_file)));
        if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $share_image)))) {
            $message = 'https://min.lexiangpingou.cn/addons/lexiangpingou/lxapi/share_tuan/'. $tuan_id . ".{$type}";
        } else {
            $message = '文件保存失败';
        }
        die(json_encode(array('png' => $message)));
    }else{
        die(json_encode(array('png' => '')));
    }
//    die(json_encode(array('png' => $message)));
}

//优惠券列表
if ($op == 'coupon_list') {

    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $status = intval($_GPC['status']);
    $condition = '';
    if ($status == 1) {//已使用
        $condition .= " AND `use_time` != 0 ";
    } elseif ($status == 2) {//未使用
        $condition .= " AND `use_time` = 0 AND `start_time` < " . TIMESTAMP . " AND `end_time` > " . TIMESTAMP;
    } elseif ($status == 3) {//已过期
        $condition .= " AND `use_time` = 0 AND `end_time` < " . TIMESTAMP;
    }
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_coupon') . " WHERE `openid` = '{$openid}' {$condition} ORDER BY `end_time` DESC ");

    foreach ($list as $key => $value) {
        $list[$key]['end_time'] = date('Y-m-d', $value['end_time']);
    }
    die(json_encode(array('status' => 1, 'list' => $list)));

}
//申请售后
if($op == 'applyserver'){
    $orderno = $_GPC['orderno'];
    $uniacid = $_GPC['uniacid'];

    order_update_by_params(array('servestype' => 1), array('orderno' => $orderno,'uniacid' =>$uniacid));

    $serves = pdo_fetch('SELECT * FROM ' . tablename('tg_order_service') . " WHERE orderno=:orderno and uniacid=:uniacid ", array(':orderno' => $_GPC['orderno'],':uniacid' => $uniacid));
//    if($uniacid==33){
//        print_r($serves);
//        die;
//    }
    if (empty($serves)) {
        $data = array(
            'orderno' => $orderno,
            'servicereson' => $_GPC['servicereson'],
            'serviceremark' => $_GPC['serviceremark'],
            'serversstatus' => 1,
            'servicetime' => TIMESTAMP,
            'feedtype'=>'待处理',
            'uniacid'=>$uniacid
        );
        pdo_insert('tg_order_service', $data);
        pdo_update('tg_order', array('servestype' => 1, 'servesupdatetime' => TIMESTAMP), array('orderno' => $orderno,'uniacid' =>$uniacid));

    } else {
        pdo_update('tg_order', array('servestype' => 1, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno'],'uniacid' =>$uniacid));
        if ($serves['feedtype'] == '退货') {

            pdo_update('tg_order_service', array('servicelastremark' => '同意退货', 'servicelasttime' => TIMESTAMP, 'feedbackexpress' => $_GPC['feedbackexpress'], 'feedbackexpresssn' => $_GPC['feedbackexpresssn']), array('orderno' => $serves['orderno'],'uniacid' =>$uniacid));

        } else {
            pdo_update('tg_order_service', array('servicelastremark' => $_GPC['servicelastremark'], 'servicelasttime' => TIMESTAMP), array('orderno' => $serves['orderno'],'uniacid' =>$uniacid));

        }
    }
    die(json_encode(array('status'=>'1','msg'=>'申请成功')));
}
//获取当前订单售后状态
if($op == 'getserver'){
    $orderno = $_GPC['orderno'];
    $uniacid = $_GPC['uniacid'];
    $serves = pdo_fetch('SELECT * FROM ' . tablename('tg_order_service') . " WHERE orderno=:orderno and  uniacid=:uniacid", array(':orderno' => $_GPC['orderno'],':uniacid' => $uniacid));
    if (empty($serves)) {
        die(json_encode(array('status'=>'0')));

    } else {
        die(json_encode(array('status'=>'1','msg'=>$serves)));
    }


}
//部分退款再申诉
if ($op == 'part_refund') {
    $orderno = $_GPC['orderno'];
    $uniacid = $_GPC['uniacid'];
    $serves = pdo_fetch('SELECT * FROM ' . tablename('tg_order_service') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));
    $order = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . " WHERE orderno=:orderno", array(':orderno' => $_GPC['orderno']));
    $feedbackfee = $serves['feedbackfee'];

    if ($order['pay_type'] == 9) {

        $res = balance_payment_refund($order['transid'], 2 , $order , $serves['servicefeedback'] , $feedbackfee);

        if ($res['status'] == 1) {
            pdo_update('tg_order_service', array('overtime' => TIMESTAMP, 'overfeedtype' => $serves['feedtype']), array('orderno' => $serves['orderno']));
            pdo_update('tg_order', array('servestype' => 3, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));
            die(json_encode(array('status'=>'1','msg'=>$res['message'])));
        } else {
            die(json_encode(array('status'=>'0','msg'=>$res['message'])));
        }
    } else {
        $rs = partrefund($orderno, 1, $feedbackfee,$uniacid);
        if ($rs == 'success') {
            pdo_update('tg_order_service', array('overtime' => TIMESTAMP, 'overfeedtype' => $serves['feedtype']), array('orderno' => $serves['orderno']));
            pdo_update('tg_order', array('servestype' => 3, 'servesupdatetime' => TIMESTAMP), array('orderno' => $serves['orderno']));
            die(json_encode(array('status'=>'1','msg'=>'退款成功，售后完成')));
        } else {
            die(json_encode(array('status'=>'0','msg'=>'退款失败，请联系客服处理','rs'=>$rs)));
        }
    }
}
//会员退款
function balance_payment_refund($id, $type, $order, $reason = '', $price = 0)
{

    global $_W, $_GPC;
    $bill = pdo_get('tg_member_billrecord', array('id' => $id));
    $member = pdo_get('tg_member', array('uniacid' => $bill['uniacid'], 'from_user' => $bill['openid']));
    if ($type == 1) {
        $price = floatval($bill['price']) - floatval($bill['refund']);
    } else {
        $price = floatval($price);
    }
    if ($bill['status'] == 1) {
        return array('status' => -1, 'message' => '非常抱歉！此订单已全额退款');
    } elseif (floatval($bill['price']) - floatval($bill['refund']) <= 0) {
        return array('status' => -1, 'message' => '非常抱歉！此订单已全额退款');
    } elseif (floatval($bill['price']) - floatval($bill['refund']) < $price) {
        return array('status' => -1, 'message' => '非常抱歉！此订单可退款金额不足');
    }
    if ($bill['type'] == 2) {
        $goodsname = '线下交易';
        $refundername = $member['name'];
        $refundermobile = $member['addmobile'];
        $uniacid = $member['uniacid'];
        $orderno = $bill['orderno'];
    } else {
        $goodsname = $order['goodsname'];
        $goodsid = $order['g_id'];
        if ($order['g_id'] == 0) {
            $coll = pdo_get('tg_collect', array('orderno' => $order['orderno']));
            $goodsname = $coll['goodsname'];
            $goodsid = $coll['sid'];
        }
        $refundername = $order['cname'] ? $order['cname'] : $order['addname'];
        if (!$refundername) {
            $refundername = $member['name'];
        }
        $refundermobile = $order['tel'] ? $order['tel'] : $order['moblie'];
        if (!$refundermobile) {
            $refundermobile = $member['addmobile'];
        }
        $uniacid = $order['uniacid'];
        $orderid = $order['id'];
        $merchantid = $order['merchantid'];
        $orderno = $order['orderno'];
        if ($type == 1) {
            pdo_update('tg_order', array('status' => 7), array('id' => $order['id']));
        } elseif (floatval($bill['price']) - floatval($bill['refund']) - $price == 0) {
            pdo_update('tg_order', array('status' => 7), array('id' => $order['id']));
            $type = 1;
        } elseif (floatval($bill['price']) - floatval($bill['refund']) - $price > 0) {
            pdo_update('tg_order', array('status' => 6), array('id' => $order['id']));
        }
    }

    pdo_update('tg_member', array('member_balance' => floatval($member['member_balance']) + floatval($price)), array('uniacid' => $bill['uniacid'], 'from_user' => $bill['openid']));
    $res = pdo_update('tg_member_billrecord', array('status' => $type, 'refund' => floatval($bill['refund']) + $price, 'refund_time' => TIMESTAMP), array('id' => $id));
    if ($res) {

        if (intval($_W['uid']) > 0) {
            $refund_id = intval($_W['uid']);
        } elseif ($_W['openid']) {
            $refund_id = $_W['openid'];
        } else {
            $refund_id = -1;
        }
        $data['type'] = 5;
        $data['goodsid'] = $goodsid;
        $data['payfee'] = floatval($bill['price']);
        $data['refundfee'] = $price;
        $data['transid'] = $id;
        $data['refund_id'] = $refund_id;
        $data['refundername'] = $refundername;
        $data['refundermobile'] = $refundermobile;
        $data['goodsname'] = $goodsname;
        $data['createtime'] = TIMESTAMP;
        $data['status'] = 1;
        $data['uniacid'] = $uniacid;
        $data['orderid'] = $orderid;
        $data['merchantid'] = $merchantid;
        $data['orderno'] = $orderno;
        pdo_insert('tg_refund_record', $data);

//        $url = app_url('member/member');
        $title = '会员消费退款';
        if ($reason) {
            $reason = '，因' . $reason;
        }
        $content = "尊敬的" . $member["name"] . $reason . "您消费的：￥" . $price . "已退回您的会员账户余额，请注意查收！";
        $remark = "";
//        result_type($bill['openid'], $title, $content, $remark, $url);
        $message = '退款成功';
    } else {
        $message = '非常抱歉！退款失败';
    }
    return array('status' => $res, 'message' => $message);

}


function partrefund($orderno, $type, $money,$uniacid)
{

    global $_W;
    include_once './WxPay.Api.php';
    load()->model('account');
    load()->func('communication');
//    print_r($orderno);
//    die;
//    wl_load()->model('setting');

    $WxPayApi = new WxPayApi();

    $input = new WxPayRefund();

    $accounts = uni_accounts();
    $acid = $uniacid;

    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/' . $uniacid . '/apiclient_cert.pem';
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/' . $uniacid . '/apiclient_key.pem';
    $account_info = pdo_fetch("select * from" . tablename('account_wechats') . "where uniacid={$uniacid}");
    $refund_order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderno}'");
    $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$refund_order['g_id']}'");
    $settings = setting_get_by_name('refund',$uniacid);

    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = TG_CERT . $uniacid . '/apiclient_cert.pem';
        $path_key = TG_CERT . $uniacid . '/apiclient_key.pem';
    }

    $refund_no = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    //$paylog=pdo_fetch("select uniontid from" . tablename('core_paylog') . "where tid ='{$orderno}'");
    $key = $settings['apikey'];
    $mchid = $settings['mchid'];
    $appid = $account_info['key'];
    $fee = $money * 100;
    $refundid = $refund_order['transid'];

    $input->SetAppid($appid);
    $input->SetMch_id($mchid);
    $input->SetOp_user_id($mchid);
    $input->SetRefund_fee($fee);
    $input->SetTotal_fee($refund_order['price'] * 100);
    $input->SetTransaction_id($refundid);
    //$input -> SetOut_trade_no($paylog['uniontid']);
    $input->SetOut_refund_no($refund_no);
    $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);

    $data = array('merchantid' => $refund_order['merchantid'], 'transid' => $refund_order['transid'], 'orderno' => $refund_order['orderno'], 'refund_id' => $result['refund_id'], 'createtime' => TIMESTAMP, 'status' => 0, 'type' => $type, 'goodsid' => $refund_order['g_id'], 'orderid' => $refund_order['id'], 'payfee' => $refund_order['price'], 'refundfee' => $fee * 0.01, 'refundername' => $refund_order['addname'], 'refundermobile' => $refund_order['mobile'], 'goodsname' => $goods['gname'], 'uniacid' => $uniacid);
    $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}'");
    if (empty($refund_check)) {
        pdo_insert('tg_refund_record', $data);
    }

    if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {

        pdo_update('tg_order', array('status' => 6), array('id' => $refund_order['id']));

        pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
        //pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
        pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
        return 'success';
    } else {
        $input->SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
        $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);

        if ($result['return_code'] == 'SUCCESS' && !empty($result['refund_id'])) {
            pdo_update('tg_order', array('status' => 6), array('id' => $refund_order['id']));

            pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
            //pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
            pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
            return 'success';
        } else {
            $logdata = array(
                'orderno' => $orderno,
                'log' => json_encode($result),
                'from' => $refund_order['uniacid'] . $result['err_code_des']
            );
            pdo_insert('tg_log', $logdata);
            if ($result['err_code'] != 'SYSTEMERROR') {
                pdo_update('account_wechats', array('refund_type' => 1, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));

            }
            return 'fail';
        }

    }
}

function setting_get_by_name($name='', $uniacid=''){
    global $_W;
    $setting = pdo_fetch("select * from".tablename('tg_setting')." where `key`  = '{$name}' and uniacid={$uniacid}");
    if($setting){
        $set = unserialize($setting['value']);
        return $set;
    }else{
        return FALSE;
    }
}


//获取火蝶用户信息
if($op == 'getUserInfo'){
    $weid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    
    $mem = pdo_fetch("SELECT * FROM " . tablename('tg_member') . "where from_user='{$openid}' and uniacid='{$weid}'");
    if($mem){
        die(json_encode(array('status'=>'1','msg'=>$mem)));
    }else{
        die(json_encode(array('status'=>'0','msg'=>'')));
    }

}

if($op == 'mycashorder'){
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $status = $_GPC['status'];
//    $config = tgsetting_load($uniacid);
//    print_r($config);
//    die;
    if (empty($uniacid)) {
        die(json_encode(array('status'=>'0','msg'=>'参数错误')));
    }
    if (empty($openid)) {
        die(json_encode(array('status'=>'0','msg'=>'参数错误')));
    }

    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
    $member['wallet'] = number_format($member['wallet'], 2, '.', '');
    $member1 = pdo_fetch("SELECT id,total,from_user,parentid FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and enable = 1  and uniacid = '{$uniacid}'");
    $member2 = pdo_fetch("SELECT id,total,from_user,parentid FROM " . tablename('tg_member') . " WHERE id = '{$_GPC['sharenum']}'  and uniacid = '{$uniacid}'");
    if(empty($member)){
        die(json_encode(array('status'=>'0','msg'=>'参数错误')));
    }
    if (empty($member1)) {
        $sharen = 0;
    } else {
        $sharen = $member1['id'];
    }
    $to_url = app_url('order/mycashorder',$uniacid) . "&sharenum=" . $sharen;
    $member['to_url'] = $to_url;
    $fans = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE openid = '{$openid}' and uniacid = '{$uniacid}' and follow = 1");
    if ((empty($member) || $member['parentid'] == -1) && !empty($_GPC['sharenum'])) {
        //message(intval($_GPC['sharenum']));
        if ($member['parentid'] == -1 && !empty($fans)) {
            $anum = 0;
        } else {
            $anum = intval($_GPC['sharenum']);
        }
        $data = array(
            'parentid' => $anum
        );
        pdo_update('tg_member', $data, array('id' => $member['id']));

    }
    if ($member['type'] == 1) {
        $anumb = $member['id'];
    } else {
        $anumb = $member['parentid'];
    }

    if (empty($_GPC['sharenum']) && $member['parentid'] == -1) {
        if ($member['parentid'] == -1 && !empty($fans)) {
            $anum = 0;
        } else {
            $anum = intval($_GPC['sharenum']);
        }
        $data = array(
            'parentid' => $anum
        );
        pdo_update('tg_member', $data, array('id' => $member['id']));
    }
    $_Session['btitle'] = $member['shopname'];
    $share_indexname = $_W['uniaccount']['name'] . "发布兼职招募令,速来!!!";
    $share_indexdesc = "呼朋唤友一起干,动动手指来收益!";
    $share_images = '';

    $op = intval($_GPC['op']); //op=0对应获取全部订单,op=1对应获取未结算订单,op=2对应获取已结算订单
    if (empty($_GPC['op'])) {
        $op = 1;
    }

    $weid = $uniacid;

//    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = '{$uniacid}' and from_user = '{$openid}'");
//die(json_encode($member));
    if (empty($member['addtime'])) {
        $member['addtime'] = time();
    }

    //获取当前用户全部订单信息
    if ($status == 0) {

        $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,addname,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' {$con} and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
        foreach ($orders1 as $ka) {
            $orders[] = $ka;
        }

    } elseif ($status == 1) {
        //获取当前用户未结算订单信息
        $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' {$con} AND comtype <> 1 and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
        foreach ($orders1 as $ka) {
            $orders[] = $ka;
        }

    } elseif ($status == 2) {
        //获取当前用户已结算订单信息 在数据表里status = 2代表待收货
        $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' {$con} AND comtype = 1 and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
        foreach ($orders1 as $ka) {
            $orders[] = $ka;
        }

    } else {
        message('获取订单信息失败.', app_url('order/mycashorder', array('op' => '0')), 'error');
    }
    $sumcom = pdo_fetchall("SELECT g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,gnum,comtype,freight FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' and status in (2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user from " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");

//file_put_contents(TG_DATA . "test11.log", var_export(json_encode($sumcom), true) . PHP_EOL, FILE_APPEND);
    $comp = 0;
    $nobillnum = 0;
    $billnum = 0;
    foreach ($sumcom as $key => $value) {
        $price = floatval($value['price']) - floatval($value['cost_fee']) - floatval($value['freight']);
        if ($value['g_id'] > 0) {
            if ($value['commissiontype'] == 2) {
                if ($value['comtype'] == 0) {
                    $nobillnum += $value['commission'] * $value['gnum'];
                } elseif ($value['comtype'] == 1) {
                    $billnum += $value['commission'] * $value['gnum'];
                }
            } else {
                if ($value['comtype'] == 0) {
                    $nobillnum += $price * $value['commission'] / 100;
                } elseif ($value['comtype'] == 1) {
                    $billnum += $price * $value['commission'] / 100;
                }
            }

        } else {
            $favoriteqqq = pdo_fetchall("SELECT oprice,num,commission,type,sid FROM " . tablename('tg_collect') . " WHERE uniacid = '{$uniacid}' AND orderno = '{$value['orderno']}' ");
            foreach ($favoriteqqq as $orderss) {
                if ($orderss['type'] == 2) {
                    if ($value['comtype'] == 0) {
                        $nobillnum += $orderss['commission'] * $orderss['num'];
                    } elseif ($value['comtype'] == 1) {
                        $billnum += $orderss['commission'] * $orderss['num'];
                    }
                } else {
                    if ($value['comtype'] == 0) {
                        $nobillnum += ($orderss['oprice'] * $orderss['num']) * $orderss['commission'] / 100;
                    } elseif ($value['comtype'] == 1) {
                        $billnum += ($orderss['oprice'] * $orderss['num']) * $orderss['commission'] / 100;
                    }
                }
            }
        }
    }
    $comp = $nobillnum + $billnum;
    $member['nobillnum'] = number_format($nobillnum, 2, '.', '');
    $member['billnum'] = number_format($billnum, 2, '.', '');
    die(json_encode(array('status'=>'1','member'=>$member)));
}

function app_url($segment,$uniacid, $params = array())
{
    global $_W;
    list($do, $ac, $op) = explode('/', $segment);
    $roots = 'w9.huodiesoft.com';
    if ($_W['uniacid'] != 53) {
        $roots = 'www.lexiangpingou.cn';
    }
    $wechat = pdo_fetch('SELECT * FROM ' . tableName('account_wechats') . ' WHERE uniacid=:uniacid', array(':uniacid' => $uniacid));
    $head_http = "http://";
    if ($wechat['is_https'] == 1) {
        $head_http = "https://";
    }
    $url = $head_http . $wechat['key'] . "." . $roots . "/" . 'app/index.php?i=' . $uniacid . '&c=entry&m=lexiangpingou&';
    if ($_W['uniacid'] == 33) {
        $url = $head_http . $roots . "/" . 'app/index.php?i=' . $uniacid . '&c=entry&m=lexiangpingou&';
    }
    $uni_settings = pdo_fetch('SELECT oauth FROM ' . tablename('uni_settings') . ' WHERE uniacid=:uniacid', array(':uniacid' => $uniacid));
    $oauth = iunserializer($uni_settings['oauth']);
    if (!empty($oauth['host'])) {
        $url = $oauth['host'] . '/app/index.php?i=' . $uniacid . '&c=entry&m=lexiangpingou&';
    }
    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    if (!empty($op)) {
        $url .= "op={$op}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }
    //message($url);
    return $url;
}

if($op == 'getSubordinates'){
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    if (empty($uniacid)) {
        die(json_encode(array('status'=>'0','msg'=>'参数错误')));
    }
    if (empty($openid)) {
        die(json_encode(array('status'=>'0','msg'=>'参数错误')));
    }
    $member=pdo_fetch('select * from cm_tg_member where uniacid=:uniacid and openid=:openid',array(':uniacid'=>$uniacid,':openid'=>$openid));
    $total=pdo_fetch('select count(id) as num from cm_tg_member where parentid=:parentid ',array(':parentid'=>$member['id']));
    $pagetitle=$member['nickname'].'的客户';
        $page = !empty($_GPC['page'])? intval($_GPC['page']): 1;
        $pagesize = !empty($_GPC['pagesize'])? intval($_GPC['pagesize']): 10;
// LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize
        $list=pdo_fetchall('select * from cm_tg_member where parentid=:parentid order by intertime desc limit '.($page - 1) * $pagesize . ',' . $pagesize,array(':parentid'=>$member['id']));
    die(json_encode(array('status'=>'1','msg'=>$list)));
}




if($op == 'mycashinfo'){
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $status = $_GPC['status'];
    if (empty($uniacid)) {
        die(json_encode(array('status'=>'0','msg'=>'参数错误')));
    }
    if (empty($openid)) {
        die(json_encode(array('status'=>'0','msg'=>'参数错误')));
    }

//$openid = 'oCKOnuIE12iqxV9Uacnb70o1vRvA';
//die(json_encode($openid));
    $member = pdo_fetch("SELECT id,total,from_user,parentid,type,shopname,enable,addtime FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
    $_Session['btitle'] = $member['shopname'];

    $op = intval($_GPC['op']); //op=0对应获取全部订单,op=1对应获取未结算订单,op=2对应获取已结算订单

    if (empty($member['addtime'])) {
        $member['addtime'] = time();
    }

    $starttime = $_GPC['starttime'];
    $endtime = $_GPC['endtime'];
    $con = '';
    if (!empty($starttime) && !empty($endtime)) {
        $starttime = strtotime($starttime);
        $endtime = strtotime($endtime);
        if ($starttime > $member['addtime']) {
            $con .= " and ptime > {$starttime} and ptime < {$endtime} ";
        } else {
            $con .= " and ptime > {$member['addtime']} and ptime < {$endtime} ";
        }

    }


//获取当前用户全部订单信息
    if ($status == 0) {

        $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,addname,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' {$con} and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
        foreach ($orders1 as $ka) {
            $orders[] = $ka;
        }

    } elseif ($status == 1) {
        //获取当前用户未结算订单信息
        $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' {$con} AND comtype <> 1 and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
        foreach ($orders1 as $ka) {
            $orders[] = $ka;
        }

    } elseif ($status == 2) {
        //获取当前用户已结算订单信息 在数据表里status = 2代表待收货
        $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' {$con} AND comtype = 1 and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
        foreach ($orders1 as $ka) {
            $orders[] = $ka;
        }

    } else {
        message('获取订单信息失败.', app_url('order/mycashorder', array('op' => '0')), 'error');
    }
//    $sumcom = pdo_fetchall("SELECT g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,gnum,comtype,freight FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' and status in (2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user from " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
//
////file_put_contents(TG_DATA . "test11.log", var_export(json_encode($sumcom), true) . PHP_EOL, FILE_APPEND);
//    $comp = 0;
//    $nobillnum = 0;
//    $billnum = 0;
//    foreach ($sumcom as $key => $value) {
//        $price = floatval($value['price']) - floatval($value['cost_fee']) - floatval($value['freight']);
//        if ($value['g_id'] > 0) {
//            if ($value['commissiontype'] == 2) {
//                if ($value['comtype'] == 0) {
//                    $nobillnum += $value['commission'] * $value['gnum'];
//                } elseif ($value['comtype'] == 1) {
//                    $billnum += $value['commission'] * $value['gnum'];
//                }
//            } else {
//                if ($value['comtype'] == 0) {
//                    $nobillnum += $price * $value['commission'] / 100;
//                } elseif ($value['comtype'] == 1) {
//                    $billnum += $price * $value['commission'] / 100;
//                }
//            }
//
//        } else {
//            $favoriteqqq = pdo_fetchall("SELECT oprice,num,commission,type,sid FROM " . tablename('tg_collect') . " WHERE uniacid = '{$uniacid}' AND orderno = '{$value['orderno']}' ");
//            foreach ($favoriteqqq as $orderss) {
//                if ($orderss['type'] == 2) {
//                    if ($value['comtype'] == 0) {
//                        $nobillnum += $orderss['commission'] * $orderss['num'];
//                    } elseif ($value['comtype'] == 1) {
//                        $billnum += $orderss['commission'] * $orderss['num'];
//                    }
//                } else {
//                    if ($value['comtype'] == 0) {
//                        $nobillnum += ($orderss['oprice'] * $orderss['num']) * $orderss['commission'] / 100;
//                    } elseif ($value['comtype'] == 1) {
//                        $billnum += ($orderss['oprice'] * $orderss['num']) * $orderss['commission'] / 100;
//                    }
//                }
//            }
//        }
//    }
//    $comp = $nobillnum + $billnum;

    foreach ($orders as &$order) {

        $comprice = 0;
        $price = floatval($order['price']) - floatval($order['cost_fee']) - floatval($order['freight']);
        $mem = pdo_fetch("SELECT nickname,avatar FROM " . tablename('tg_member') . " WHERE uniacid = " . $uniacid . " AND from_user = '{$order['openid']}' ");

        if ($order['g_id'] > 0) {
            $goods = pdo_fetch("SELECT id,gimg,share_gimg FROM " . tablename('tg_goods') . " WHERE id = '{$order['g_id']}' ");
            $order['goods'][0] = $goods;
            if (empty($goods['share_gimg'])) {
                $order['goods'][0]['gimg'] = tomedia($goods['gimg']);
            } else {
                $order['goods'][0]['gimg'] = tomedia($goods['share_gimg']);
            }
            if ($order['commissiontype'] == 2) {
                $comprice = $order['commission'] * $order['gnum'];
            } else {
                $comprice = $price * $order['commission'] / 100;
            }
            $order['price'] = $price;
            $order['comprice'] = $comprice;
            $order['avatar'] = tomedia($mem['avatar']);
            $order['nickname'] = $mem['nickname'];
            $list[] = $order;
        } else {
            $collect = pdo_fetchall("SELECT sid,oprice,num,commission,type,orderno FROM " . tablename('tg_collect') . " WHERE uniacid = '{$uniacid}' AND orderno = '{$order['orderno']}' ");
            $i = 0;
            foreach ($collect as &$qqq) {
                $goods = pdo_fetch("SELECT id,gimg,share_gimg FROM " . tablename('tg_goods') . " WHERE id = '{$qqq['sid']}' ");
                $qqq['goods'][$i] = $goods;
                $qqq['commissiontype'] = $qqq['type'];
                if ($qqq['commissiontype'] == 2) {
                    $comprice = $qqq['commission'] * $qqq['num'];
                } else {
                    $comprice = ($qqq['oprice'] * $qqq['num']) * $qqq['commission'] / 100;//佣金计算
                }
                if ($qqq['commissiontype'] != 0) {
                    $order['goods'][$i] = $goods;
                    if (empty($goods['share_gimg'])) {
                        $qqq['goods'][$i]['gimg'] = tomedia($goods['gimg']);
                    } else {
                        $qqq['goods'][$i]['gimg'] = tomedia($goods['share_gimg']);
                    }
                    $qqq['price'] = $qqq['oprice'] * $qqq['num'];
                    $qqq['comprice'] = $comprice;
                    $i++;
                    $qqq['avatar'] = tomedia($mem['avatar']);
                    $qqq['nickname'] = $mem['nickname'];
                    $qqq['createtime'] = $order['createtime'];
                    $list[] = $qqq;
                    unset($qqq);
                }

            }
        }
        unset($order);
        unset($mem);
    }
    if(empty($list)){
        die(json_encode(array('status'=>'0','msg'=>'未获取到相关信息')));
    }else{
        die(json_encode(array('status'=>'1','msg'=>$list)));
    }
}

//提现
if($op == 'withdrawals'){
    $weid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $status = $_GPC['status'];
    $mem = pdo_fetch("SELECT id,wallet,cash,from_user FROM " . tablename('tg_member')."where from_user='{$openid}' and uniacid='{$weid}'");
    if(empty($mem)){
        die(json_encode(array('status'=>'0','msg'=>'获取用户信息失败')));
    }
    if($status==1)
    {
        $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_cashrecord') . " WHERE uniacid ='{$weid}' and type=0 and openid='{$openid}'");

    }
    if($status==2)
    {
        $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_cashrecord') . " WHERE uniacid ='{$weid}' and type=1 and openid='{$openid}'");

    }
    $orders1 = pdo_fetch("SELECT sum(price) as tprice FROM " . tablename('tg_cashrecord') . " WHERE uniacid ='{$weid}' and type=0 and openid='{$openid}'");
    $pr=0;
    if($orders1['tprice']>0)
    {
        $pr=$orders1['tprice'];
    }
    $mem['pr'] = number_format($pr, 2, '.', '');
    $mem['wallet'] = number_format($mem['wallet'], 2, '.', '');
    $mem['cash'] = number_format($mem['cash'], 2, '.', '');
    die(json_encode(array('status'=>'1','msg'=>$mem)));
}
if($op == 'apply_withdrawals'){
    $weid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $status = $_GPC['status'];
    $mem = pdo_fetch("SELECT id,wallet,cash,from_user FROM " . tablename('tg_member')."where from_user='{$openid}' and uniacid='{$weid}'");
    $name = $_GPC['name'];
    $name = floatval($name);
    if(empty($name))
    {
        die(json_encode(array('status'=>'0','msg'=>'请填写提现金额')));
    }
    $wallet=floatval($mem['wallet']);
    $cash=floatval($mem['cash']);

    if($wallet<$name)
    {
        die(json_encode(array('status'=>'0','msg'=>'提现金额不能大于提成')));
    }
    if($name<1)
    {
        die(json_encode(array('status'=>'0','msg'=>'提现金额不能小于1元')));
    }
    pdo_update('tg_member',array('wallet'=>$wallet-$name),array('id'=>$mem['id']));
    $bdata = array(
        'uniacid' => $_GPC['uniacid'],
        'openid' => $openid,
        'type' => 0,
        'addtime' => TIMESTAMP,
        'price' => $name
    );
    if(pdo_insert('tg_cashrecord', $bdata)){
        die(json_encode(array('status'=>'1','msg'=>'申请成功')));
    }else{
        die(json_encode(array('status'=>'0','msg'=>'申请失败')));
    }
}
if($op == 'getshareimage'){

    $weid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $mem = pdo_fetch("SELECT id,wallet,cash,from_user FROM " . tablename('tg_member')."where from_user='{$openid}' and uniacid='{$weid}'");
    $roots='w9.huodiesoft.com';
    if($weid!=53)
    {
        $roots='https://min.lexiangpingou.cn';
    }
    $filename = 'share_qrcode_new_'.$mem['id'].'png';
    if(!file_exists($roots . '/addons/lexiangpingou/qrcode/' . $weid . '/' . $filename)){
        include '../core/class/qrcode.class.php';
        $code = new creat_qrcode();
        $url = $code->creatXCXshareQrcode($weid,$mem['id']);
    }else{
        $url = $roots . '/addons/lexiangpingou/qrcode/' . $weid . '/' . $filename;
    }
    $config = tgsetting_load($weid);
//    $setting = setting_get_by_name("jobsmscode");
    if (!empty($config['tginfo']['saler_img'])) {
        $aaurl =$config['tginfo']['saler_img'];
    }
    die(json_encode(array('status'=>'1','msg'=>'https://'.$url,'bg'=>$aaurl)));
}
function tgsetting_load($uniacid,$key = '') {

    $settings = pdo_fetch_many('tg_setting', array('uniacid' => $uniacid), array('key', 'value'), 'key');

    if (is_array($settings)) {
        foreach ($settings as $k => &$v) {
            $settings[$k] = iunserializer($v['value']);
        }
    } else {
        $settings = array();
    }
	return $settings;
}
function pdo_fetch_many($tablename, $params = array(), $fields = array(), $key = '', $after_where = ''){
    if (!empty($fields) && !empty($key) && !in_array($key, $fields)) {
        $fields[] = $key;
    }
    $result = pdo()->getall($tablename, $params, $fields, $key, $after_where);
    if (!is_array($result) || empty($result)) {
        return array();
    }
    return $result;
}

//团长佣金

if($op == 'apply_commander'){
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $data['name'] = $_GPC['name'];
    $data['addmobile'] = $_GPC['mobile'];
    $data['apply'] = 1;
    $data['apply_status'] = 0;
    $data['apply_time'] = TIMESTAMP;
    $res = pdo_update('tg_member' , $data , array('uniacid' => $uniacid , 'openid' => $openid));
    if ($res) {
        die(json_encode(array('status'=>'1','msg'=>'申请成功')));
    } else {
        die(json_encode(array('status'=>'0','msg'=>'申请失败')));
    }
}

if($op == 'commander'){
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $setting = setting_get_by_name("commander",$uniacid);
    $record = pdo_fetchall("select * from " .tablename('tg_commander_record') . " WHERE openid = '{$openid}' and uniacid = '{$uniacid}' and status = 0 ");
    if ($record) {
        $apply = 0.00;
        foreach ($record as $item) {
            $apply += $item['price'];
        }
        pdo_update('tg_member', array('commander_apply' => $apply), array('from_user' => $openid,'uniacid'=>$uniacid));
    }
    unset($record);
    $orders = pdo_fetchall("select * from " .tablename('tg_commander') ." where uniacid = '{$uniacid}' and openid = '{$openid}' order by id desc ");
    foreach ($orders as &$v) {
        $goods = pdo_fetch("select * from " .tablename('tg_goods') ." where id = '{$v['gid']}' ");
        $v['goods'] = $goods;
        if (empty($goods['share_image'])) {
            $v['goods']['gimg'] = tomedia($goods['gimg']);
        } else {
            $v['goods']['gimg'] = tomedia($goods['share_image']);
        }
        unset($v);
    }
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
    $member['withdrawals'] = number_format($member['commander_settled'] - $member['commander_withdraw'] - $member['commander_apply'],2);
    die(json_encode(array('status'=>'1','msg'=>$member,'orders'=>$orders,'setting'=>$setting)));
}


if ($op == 'my_cash') {
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
    $name = $_GPC['name'];
    $name = floatval($name);
    if (empty($name)) {
        die(json_encode(array('status'=>'0','msg'=>'请填写金额')));
    }
    $commander_settled = floatval($member['commander_settled']);
    $commander_apply = floatval($member['commander_apply']);
    $commander_withdraw = floatval($member['commander_withdraw']);
    $settle = $commander_settled - $commander_apply - $commander_withdraw;

    if ($settle < $name) {
        die(json_encode(array('status'=>'0','msg'=>'提现金额不能大于可提现金额')));
    }
    if ($name < 1) {
        die(json_encode(array('status'=>'0','msg'=>'提现金额不能小于1元')));
    }
    pdo_update('tg_member', array('commander_apply' => $commander_apply + $name), array('id' => $member['id']));
    $bdata = array(
        'uniacid' => $uniacid,
        'openid' => $openid,
        'status' => 0,
        'createtime' => TIMESTAMP,
        'price' => $name
    );
    pdo_insert('tg_commander_record', $bdata);
    die(json_encode(array('status'=>'0','msg'=>'申请成功')));
}

if ($op == 'withdraw'){
    $id = $_GPC['id'];
    $status = $_GPC['status'];
    $con = " where uniacid = '{$uniacid}' and openid = '{$openid}' ";
    if (!empty($id)){
        $con .= " and id = " .$id;
    }
    if ($status == 0){
        $con .= " and status = 0 ";
    }elseif ($status == 1){
        $con .= " and status = 1 ";
    }elseif ($status == -1){
        $con .= " and status = -1 ";
    }
    $list = pdo_fetchall("select * from " .tablename('tg_commander_record') .$con);
    die(json_encode(array('status'=>'1','msg'=>$list)));
}

if($op == 'area_delivery'){
    $uniacid = $_GPC["uniacid"];
    $_SESSION['type'] = 1;
    $res = pdo_fetch("select * from " . tablename("tg_delivery_distance") . " where uniacid = :uniacid and status = 1 ", array(":uniacid" => $uniacid));

    if ($res) {
        $map = unserialize($res['map']);
        $res['map'] = $map;
        $status = 1;
    } else {
        $status = 0;
        $map = "暂无数据！";
    }
    die(json_encode(array('status' => $status , 'list' => $map)));
}


if($op == 'custom_address'){
    $uniacid = $_GPC['uniacid'];
    $setting=setting_get_by_name("autoaddress",$uniacid);

    if($setting['autoaddr'] == 1){
        //开启自定义区域
        $autoaddress = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = ".$uniacid."  and parentid=0");
        foreach ($autoaddress as $index => $row) {
            $data['level']=$row['level'];
            $data['id']=$row['id'];
            $data['enabled']=$row['enabled'];
            $data['parentid']=$row['parentid'];
            $data['name']=$row['name'];
            $list[]=$data;
            $addr = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = ".$uniacid."  and parentid={$row['id']}");
            foreach ($addr as $indexb => $rowb) {
                $datab['level']=$rowb['level'];
                $datab['id']=$rowb['id'];
                $datab['enabled']=$rowb['enabled'];
                $datab['name']=$rowb['name'];
                $datab['parentid']=$rowb['parentid'];
                $list[]=$datab;
                $addr2 = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = ".$uniacid."  and parentid={$rowb['id']}");
                foreach ($addr2 as $indexb2 => $rowb2) {
                    $datab2['level']=$rowb2['level'];
                    $datab2['id']=$rowb2['id'];
                    $datab2['enabled']=$rowb2['enabled'];
                    $datab2['name']=$rowb2['name'];
                    $datab2['parentid']=$rowb2['parentid'];
                    $list[]=$datab2;
                }
            }
        }

        die(json_encode(array('status' => 1 ,'setting'=>$setting, 'address' =>$list)));
    }else{
        die(json_encode(array('status' => 1 ,'setting'=>$setting, 'address' => '')));
    }
}
