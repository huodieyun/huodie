<?php
define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
load()->model('reply');
load()->app('common');
load()->classs('wesession');
load()->model('user');

/*
 * 接口名字     user_del
 * 接口作用     操作员删除
 * 接口URL     shop_api.php?op=user_del
 * 传入参数     uniacid uid
 * 回传结果     message  返回注册成功与否信息
 */
if ($op == 'user_del') {
    $id = $_GPC['uid'];
    $uniacid = $_GPC['uniacid'];

    $uni = pdo_fetch("select * from " . tablename('uni_account_users') . " where uid = '{$id}'");
    if ($uni['role'] != 'owner') {
        pdo_delete('users', array('uid' => $id));
        pdo_delete('users_permission', array('uid' => $id, 'uniacid' => $uniacid));
        pdo_delete('uni_account_users', array('uniacid' => $uniacid, 'uid' => $uid));
        pdo_delete('users_profile', array('uid' => $id));
        die(json_encode(array('status' => 1)));
    } else {
        die(json_encode(array('status' => 0)));
    }
}

/*
 * 接口名字     user_add
 * 接口作用     用户注册
 * 接口URL     shop_api.php?op=user_add
 * 传入参数     name  pwd   str
 * 回传结果     message  返回注册成功与否信息
 */
if ($op == 'user_add') {
    $id = $_GPC['uid'];
    $name = $_GPC['name'];
    $uniacid = $_GPC['uniacid'];
    $password = $_GPC['pwd'];
    $mobile = $_GPC['mobile'];
    $str = $_GPC['str'];
//    $base = $_GPC['base'];
//    $add = $_GPC['add'];
//    $edit = $_GPC['edit'];
//    $del = $_GPC['del'];
//    $list = $_GPC['list'];
//    $account = $_GPC['accountList'];
//    $all = $_GPC['accountALLList'];
    $username = pdo_fetch('select username from ' . tablename('users') . ' where username = :name', array(':name' => $name));
    if (empty($username)) {
        $user = array();
        $user['salt'] = random(8);
        $user['username'] = $name;
        $user['password'] = user_hash($password, $user['salt']);
        $user['perms'] = $str;
        $user['groupid'] = 1;
        $user['joinip'] = CLIENT_IP;
        $user['joindate'] = TIMESTAMP;
        $user['lastip'] = CLIENT_IP;
        $user['lastvisit'] = TIMESTAMP;
        if (empty($user['status'])) {
            $user['status'] = 2;
        }
        $result = pdo_insert('users', $user);
        $uid = pdo_insertid();
        $data['uid'] = $uid;

        $m = array();
        $m['uniacid'] = $uniacid;
        $m['uid'] = $uid;
        $m['type'] = 'lexiangpingou';
        $m['permission'] = 'all';
        $result = pdo_insert('users_permission', $m);
        pdo_insert('uni_account_users', array('uniacid' => $uniacid, 'uid' => $uid, 'role' => 'operator'));
        pdo_insert('users_profile', array('uid' => $uid, 'mobile' => $mobile, 'createtime' => TIMESTAMP, 'realname' => $name));
        $message = "注册成功！";
        $status = 1;
    } else {
        if (empty($id)) {
            $message = "注册失败！用户名已存在";
            $status = 0;
        } else {
            $id = intval($id);
//            $users = pdo_fetch("select * from " .tablename('users') ." where uid = :id" , array(':id' => $id));
            $user = array();
            $user['username'] = $name;
            if (!empty($password)) {
                $user['salt'] = random(8);
                $user['password'] = user_hash($password, $user['salt']);
            }
            $user['perms'] = $str;

//            if ($base || $add || $edit || $del || $list){
//                $is_merchant = 1;
//            }

//            die(json_encode(array('account' => $_W['account'])));
            pdo_update('users', $user, array('uid' => $id));
            $profile = pdo_fetch("select uid from " . tablename('users_profile') . " where uid = " . $id);
            if (empty($profile)) {
                pdo_insert('users_profile', array('uid' => $id, 'mobile' => $mobile, 'createtime' => TIMESTAMP, 'realname' => $name));
            } else {
                pdo_update('users_profile', array('mobile' => $mobile, 'createtime' => TIMESTAMP, 'realname' => $name), array('uid' => $id));
            }

            $users = pdo_fetch("select * from " . tablename('users') . " where uid = :id", array(':id' => $id));

            $message = "更新成功！";
            $status = 1;
        }

    }
    die(json_encode(array('status' => $status, 'message' => $message, 'name' => $name, 'password' => user_hash($password, $user['salt']), 'params' => $str, 'IP' => CLIENT_IP)));
}
/*
 * 接口名字     user_list
 * 接口作用     用户列表
 * 接口URL     shop_api.php?op=user_list
 * 传入参数     uniacid page pagesize
 * 回传结果     message  返回注册成功与否信息
 */
if ($op == 'user_list') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
//    $sql = "SELECT * FROM " . tablename('uni_account_users') . " where  uniacid=:uniacid LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    $uniacid = $_GPC['uniacid'];
    $sql = "SELECT * FROM " . tablename('users') . " WHERE merchant_id = 0 AND uid IN ( SELECT uid FROM " . tablename('uni_account_users') . " WHERE  uniacid = " . $uniacid . " AND role <> 'owner' ) LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql);
    unset($list['password']);
//    $list=pdo_fetchall($sql,array(':uniacid'=>$_GPC['uniacid']));
//    foreach($list as &$item){
//        $item['user']=pdo_fetch('select * from '.tablename('users').' where uid=:uid',array(':uid'=>$item['uid']));
//        if ($item['user']['merchant_id'] == 0){
//            unset($item);
//        }
//    }
    die(json_encode(array('status' => 1, 'list' => $list)));
}
/*
 * 接口名字     user_edit
 * 接口作用     用户编辑
 * 接口URL     shop_api.php?op=user_edit
 * 传入参数      uid
 * 回传结果     message  返回注册成功与否信息
 */
if ($op == 'user_edit') {
    $sql = "SELECT * FROM " . tablename('users') . " where   uid=:uid";
    $list = pdo_fetch($sql, array(':uid' => $_GPC['uid']));
    unset($list['password']);
    die(json_encode(array('status' => 1, 'user' => $list)));
}
/*
 * 接口名字     collect_list
 * 接口作用     多商户购物车
 * 接口URL     shop_api.php?op=collect_list
 * 传入参数     openid,uniacid
 * 回传结果     按商家 分组 购物车
 */
if ($op == 'collect_list') {
    $col_list = pdo_fetchall('select * from ' . tablename('tg_collect') . ' where uniacid=:uniacid and openid=:openid and orderno=0', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid']));

}
/*
 * 接口名字   submits_collect
 * 接口作用   多商户订单提交
 * 接口URL   shop_api.php?op=submits_collect
 * 传入参数   uniacid  openid
 *  name:
    tel:
    province:
    city:
    county:
    merchantid:（数组）
    废弃
 */
if ($op == 'submits_collect') {
    $data = array();
    $data['uniacid'] = $_GPC['uniacid'];
    $data['openid'] = $_GPC['openid'];
    $data['name'] = $_GPC['name'];
    $data['tel'] = $_GPC['tel'];
    $data['province'] = $_GPC['province'];
    $data['city'] = $_GPC['city'];
    $data['county'] = $_GPC['county'];
    $data['address'] = $_GPC['adds'];
    //$data['str']=$_GPC['str'];
    $ordernos = "";
    $total_price = 0;
    $weight_t = 0;
    foreach ($_GPC['str'] as $item) {
        // $ss.=$item['tid']; id
        $col_list = pdo_fetchall('select * from ' . tablename('tg_collect') . ' where uniacid=:uniacid and openid=:openid and orderno=0 and merchant_id=:merchant_id', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid'], ':merchant_id' => $item['id']));
        //统计商品重量计算运费
        $weight = 0;
        $price = 0;
        foreach ($col_list as $value) {
            $weight += $value['weight'] * $value['num'];
            $price += $value['oprice'] * $value['num'];

        }
        $weight_t += $weight;
        //计算运费
        $freight = collect_freight($item['tid'], $data['province'], $data['city'], $data['county'], $weight);
        $orderno = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        if (!empty($ordernos)) {
            $ordernos .= "|";
        }
        $ordernos .= $orderno;
        //添加ORDER
        $datas = array(
            'uniacid' => $_GPC['uniacid'],
            'gnum' => 0,
            'openid' => $_GPC['openid'],
            'ptime' => '',//支付成功时间
            'orderno' => $orderno,
            'pay_price' => $freight + $price,
            'goodsprice' => $price,
            'freight' => $freight,
            'first_fee' => 0,
            'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
            'addressid' => '',
            'addresstype' => 2,//1公司2家庭
            'dispatchtype' => 2,//配送方式
            'comadd' => $item['tid'],//
            'addname' => $data['name'],
            'mobile' => $data['tel'],
            'address' => $_GPC['province'] . $_GPC['city'] . $_GPC['county'] . $_GPC['address'],
            'g_id' => 0,
            'tuan_id' => 0,
            'is_tuan' => 0,
            'tuan_first' => 0,
            'discount_fee' => 0,
            'starttime' => TIMESTAMP,
            'remark' => '',
            'comtype' => 0,
            'senddate' => '',
            'selltype' => 0,
            'sendtime' => '',
            'commission' => 0,
            'commissiontype' => 0,
            'endtime' => 0,
            'is_hexiao' => '',
            'hexiaoma' => '',
            'couponid' => '',
            'is_usecard' => '',
            'merchantid' => $item['id'],
            'createtime' => TIMESTAMP,
            'bdeltime' => ''
        );
        if (pdo_insert('tg_order', $datas)) {
            pdo_update('tg_collect', array('orderno' => $datas['orderno']), array('openid' => $_GPC['openid'], 'uniacid' => $_GPC['uniacid'], 'orderno' => 0, 'merchant_id' => $item['id']));
        }
        $total_price += $freight + $price;
    }
    $data['price'] = $total_price;
    $data['orderno'] = "Ms" . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $data['list'] = $ordernos;
    pdo_insert('tg_merchant_order', $data);
    die(json_encode(array('price' => $data['price'], 'orderno' => $data['orderno'], 'weight_t' => $weight_t)));
}

/*
 * 接口名字   submits_collects
 * 接口作用   多商户订单提交
 * 接口URL   shop_api.php?op=submits_collects
 * 传入参数   uniacid  openid
 *  name:
    tel:
    province:
    city:
    county:
    merchantid:（数组）
 */
if ($op == 'submits_collects') {
    $data = array();
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $data['uniacid'] = $_GPC['uniacid'];
    $data['openid'] = $_GPC['openid'];
    $data['name'] = $_GPC['name'];
    $data['tel'] = $_GPC['tel'];
    $data['province'] = $_GPC['province'];
    $data['city'] = $_GPC['city'];
    $data['county'] = $_GPC['county'];
    $data['address'] = $_GPC['adds'];
//    $data['str']=$_GPC['str'];
//    die(json_encode($data));
    $ordernos = "";
    $total_price = 0;
    $weight_t = 0;
    $score_num_amount = 0;// 总积分
    foreach ($_GPC['str'] as $item) {
        // $ss.=$item['tid']; id
        $col_list = pdo_fetchall('select * from ' . tablename('tg_collect') . ' where uniacid = :uniacid and openid = :openid and orderno = 0 and merchant_id = :merchant_id', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid'], ':merchant_id' => $item['id']));
        //统计商品重量计算运费
        $weight = 0;
        $price = 0;
        if ($item['type'] == '快递') {
            foreach ($col_list as $value) {
                $weight += $value['weight'] * $value['num'];
                $price += $value['oprice'] * $value['num'];

            }
            $dispatchtype = 2;
            $weight_t += $weight;
            //计算运费
            $freight = collect_freight($item['tid'], $data['province'], $data['city'], $data['county'], $weight);
        } elseif($item['type'] == '自提') {
            foreach ($col_list as $value) {
                $price += $value['oprice'] * $value['num'];

            }
            $dispatchtype = 3;
            //计算运费
            $store = pdo_fetch("select * from " . tablename('tg_store') . " where uniacid = '{$_GPC['uniacid']}' and status = 1 and merchantid = '{$item['id']}' and id = '{$item['tid']}' ");
            $freight = floatval($store['cost']);
        } elseif($item['type'] == '送货上门') {
            $dispatchtype = 1;
            $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$uniacid}' and merchantid = '{$item['id']}' and dispatchtype = 2 and enabled = 1 ORDER BY id asc");
            $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
            $col_list['cost'] = 0;
            foreach ($col_list as $value) {
                $price += $value['oprice'] * $value['num'];

                $bprice = sprintf("%.2f", $value['oprice']);

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
                $value['cost'] += $cost;
            }
            $freight = $col_list['cost'];
            if ($item['tdata'] == '今天'){
                $senddate = date('Y-m-d' , TIMESTAMP);
            }elseif ($item['tdata'] == '明天'){
                $senddate = date('Y-m-d' , (TIMESTAMP + 24 * 60 * 60));
            }elseif ($item['tdata'] == '后天'){
                $senddate = date('Y-m-d' , (TIMESTAMP + 2 * 24 * 60 * 60));
            }
            $sendtime = $item['ttime'];
        }
        $orderno = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        if (!empty($ordernos)) {
            $ordernos .= "|";
        }
        $ordernos .= $orderno;
        //添加ORDER
        $zititime = 0;
        if ($item['id'] == 0) {
            $collect = pdo_fetchall("select zititime from " . tablename('tg_collect') . " where uniacid = '{$uniacid}' and openid = '{$openid}' and orderno = 0 and merchant_id = 0 ");
            foreach ($collect as $val) {
//            $good = pdo_fetch("select zititime from " .tablename('tg_goods') ." where id = '{$val['sid']}' ");
                if ($zititime == 0) {
                    $zititime = $val['zititime'];
                } elseif ($val['zititime'] > 0 && $val['zititime'] < $zititime) {
                    $zititime = $val['zititime'];
                }
            }
            unset($collect);
        }
        $datas = array(
            'uniacid' => $_GPC['uniacid'],
            'gnum' => 0,
            'openid' => $_GPC['openid'],
            'ptime' => '',//支付成功时间
            'orderno' => $orderno,
            'pay_price' => $freight + $price,
            'goodsprice' => $price,
            'freight' => $freight,
            'first_fee' => 0,
            'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
            'addressid' => '',
            'addresstype' => 2,//1公司2家庭
            'dispatchtype' => $dispatchtype,//配送方式
            'comadd' => $item['tid'],//
            'addname' => $data['name'],
            'mobile' => $data['tel'],
            'address' => $_GPC['province'] . $_GPC['city'] . $_GPC['county'] . $_GPC['address'],
            'province' => $_GPC['province'],
            'city' => $_GPC['city'],
            'county' => $_GPC['county'],
            'detailed_address' => $_GPC['address'],
            'g_id' => 0,
            'tuan_id' => 0,
            'is_tuan' => 0,
            'tuan_first' => 0,
            'discount_fee' => 0,
            'starttime' => TIMESTAMP,
            'remark' => '',
            'comtype' => 0,
            'senddate' => $senddate,
            'selltype' => 0,
            'sendtime' => $sendtime,
            'commission' => 0,
            'commissiontype' => 0,
            'endtime' => 0,
            'is_hexiao' => '',
            'hexiaoma' => '',
            'couponid' => '',
            'is_usecard' => '',
            'merchantid' => $item['id'],
            'createtime' => TIMESTAMP,
            'zititime' => $zititime,
            'print_id' => $_GPC["print_id"],
            'bdeltime' => '',
            'score' => $score_num_amount, // 积分数
            'is_score' => $score_num_amount > 0 ? '1' : '0' // 是否有积分
        );
        if (pdo_insert('tg_order', $datas)) {
            pdo_update('tg_collect', array('orderno' => $datas['orderno']), array('openid' => $_GPC['openid'], 'uniacid' => $_GPC['uniacid'], 'orderno' => 0, 'merchant_id' => $item['id']));
        }
        $total_price += $freight + $price;
    }
    $data['price'] = $total_price;
    $data['orderno'] = "Ms" . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $data['list'] = $ordernos;
    pdo_insert('tg_merchant_order', $data);
    die(json_encode(array('price' => $data['price'], 'orderno' => $data['orderno'], 'weight_t' => $weight_t)));
}

/*
 * 接口名字   shop_delivery_cart
 * 接口作用   多商户购物车送货上门
 * 接口URL   shop_api.php?op=shop_delivery_cart
 * 传入参数   uniacid openid merchantid
 * 返回       运费
 */
if ($op == 'shop_delivery_cart') {
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $merchantid = $_GPC['merchantid'];
    $delivery = pdo_fetch("SELECT * FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$uniacid}' and merchantid = '{$merchantid}' and dispatchtype = 2 and enabled = 1 ORDER BY id asc");
    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费

    $collect = pdo_fetchall("select * from " . tablename('tg_collect') . " where uniacid = '{$uniacid}' and openid = '{$openid}' and orderno = 0 and merchant_id = '{$merchantid}' ");
    $bprice = 0;
    foreach ($collect as $value) {
        $bprice += sprintf("%.2f", $value['oprice']);
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

    die(json_encode(array('status' => 1, 'cost' => $cost)));
}

/*
 * 接口名字   shop_sendtime
 * 接口作用   多商户购物车送货上门时间
 * 接口URL   shop_api.php?op=shop_sendtime
 * 传入参数   uniacid ad id
 * 返回       送货上门时间
 */
if ($op == 'shop_sendtime') {
    $uniacid = $_GPC['uniacid'];
    $ad = $_GPC['ad'];
    $id = $_GPC['id'];
    //送货时间
    $sendtimes = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid = '{$uniacid}' and merchantid = '{$id}' and status = 1 order by starttime asc");

//    $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and merchantid = '{$id}' and dispatchtype = 2 and enabled = 1 ORDER BY id asc");
//    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
//    $_SESSION['type'] = 1;
//    $_SESSION['freight'] = 0;
//    $id = intval($_GPC['g_id']);
//    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id = $id");
//    if ($goods['selltype'] == 1 || $goods['selltype'] == 2) {
//        $bprice = $goods['gprice'];
//    }
//    if ($goods['selltype'] == 0) {
//        $bprice = $goods['oprice'];
//    }
//    $cost = 0;
//    $temp = 0;
//    $cout = count($dispatch_delivery);
//    foreach ($dispatch_delivery as $key => $item) {
//        $i = $i + 1;
//        if ($bprice >= $temp && $bprice <= $item['cart']) {
//            $cost = $item['cost'];
//        }
//        $temp = $item['cart'];
//
//        if ($i == $cout && $bprice > $item['cart']) {
//            $cost = $item['cost'];
//        }
//    }
//    if ($goods['isfree'] == 1 || $firstfree == 1 || $overfree == 1) {
//        $cost = 0;
//        $_SESSION['freight'] = 0;
//    }
//
//    $_SESSION['freight'] = $cost;
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
        $psql1 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' and sendtime = '{$valtime}' and senddate = '{$dtime}' and status not in (0,4,5,9)");
        $numa1 = count($psql1);

        if ($v['total'] > $numa1) {
            $ttime[$k]['name'] = $valtime;
        }

    }
    die(json_encode($ttime));

}

function collect_freight($tid, $province, $city, $county, $weight)
{
    $p = $province;
    $c = $city;
    $d = $county;
    $freight = 0;
    $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
    $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
    $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");
    if (!empty($province_fee['first_fee'])) {
        $free = sprintf("%.2f", $province_fee['free']);
        $first_fee = sprintf("%.2f", $province_fee['first_fee']);
        $first_weight = sprintf("%.2f", $province_fee['first_weight']);
        $second_fee = sprintf("%.2f", $province_fee['second_fee']);
        $second_weight = sprintf("%.2f", $province_fee['second_weight']);
    }
    if (!empty($city_fee['first_fee'])) {

        $free = sprintf("%.2f", $city_fee['free']);
        $first_fee = sprintf("%.2f", $city_fee['first_fee']);
        $first_weight = sprintf("%.2f", $city_fee['first_weight']);
        $second_fee = sprintf("%.2f", $city_fee['second_fee']);
        $second_weight = sprintf("%.2f", $city_fee['second_weight']);
    }
    if (!empty($district_fee['first_fee'])) {
        $free = sprintf("%.2f", $district_fee['free']);
        $first_fee = sprintf("%.2f", $district_fee['first_fee']);
        $first_weight = sprintf("%.2f", $district_fee['first_weight']);
        $second_fee = sprintf("%.2f", $district_fee['second_fee']);
        $second_weight = sprintf("%.2f", $district_fee['second_weight']);
    }
    if ($weight > $first_weight) {
        $freight = sprintf("%.2f", $first_fee + ($weight - $first_weight) / $second_weight * $second_fee);
    } else {
        $freight = $first_fee;
    }
    return $freight;
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

function member_get_by_params($params = '') {
    if(!empty($params)){
        $params = ' where '. $params;
    }
    $sql = "SELECT * FROM " . tablename('tg_member') . $params;
    $member = pdo_fetch($sql);
    return $member;
}
