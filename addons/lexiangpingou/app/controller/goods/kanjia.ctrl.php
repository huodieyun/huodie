<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * ��Ʒ���������
 */
defined('IN_IA') or exit('Access Denied');
load()->func("tpl");
global $_W, $_GPC;
wl_load()->model('goods');
wl_load()->model('functions');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'detail_kj';
/************************************************手机*********************************/
/**
 * author: 小陈
 * 发起砍价
 */

if (!$_W['openid']) {
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

if ($op == "createkj") {
    $data["goodsid"] = $_GPC["goodsid"];
    $data["optionid"] = $_GPC["optionid"];
    $data["uniacid"] = $_W["uniacid"];
    $data["openid"] = $_W["openid"];
    $data["status"] = 1;
    $goods = pdo_get('tg_goods', array('id' => $data['goodsid']));
    $res = pdo_get("tg_kj_log", $data);
    if (!$res) {
        $data["price"] = $_GPC["oprice"];
        if (intval($data['optionid']) > 0) {
            $data["price"] = pdo_getcolumn("tg_goods_option", array("id" => $data['optionid']), 'productprice');
        }
        $res = pdo_insert("tg_kj_log", $data);
        $id = pdo_insertid();
    }
    echo $id;
}
/**
 * author: 小陈
 * 砍价页面
 */
if ($op == "detail_kj") {
    $kid = $_GPC["id"];
    //查询砍价
    $kj_res = pdo_get("tg_kj_log", array("id" => $kid));
    //获取价钱
    $price = $kj_res["price"];
    //获取价钱对应的砍价数额
    $kj_price = pdo_getall("tg_goods_kj", array("g_id" => $kj_res["goodsid"], "uniacid" => $_W["uniacid"]));

    $gid = intval($kj_res["goodsid"]);
    $goods = goods_get_by_params("id = {$gid}");

    if ($goods['spikeT'] && $goods['spike_end'] < TIMESTAMP) {
        $kj_res["is_on"] = 0;
        $kj_res["status"] = 0;
    }

    if ($price <= $kj_price[0]["last_price"] && $kj_res['type'] == 0) {

        if ($kj_res['openid']) {

            $postData['openid'] = $kj_res['openid'];
            $postData['kid'] = $kj_res['id'];
            $postData['title'] = "您有一个砍价商品已被砍至最低价了，请及时下单";
            $postData['message'] = "砍价商品：【" . $goods['gname'] . "】价格：￥" . $price;
            $postData['remark'] = '砍价进度提醒';
            $postData_res = serialize($postData);
            $xc["content"] = $postData_res;
            $xc["uniacid"] = $kj_res['uniacid'];
            $xc["openid"] = $kj_res['openid'];
            $xc["mess_tpl"] = 'result_kanjia';
            $re = pdo_insert("tg_message_log", $xc);
            if ($re) {
                pdo_update('tg_kj_log', array('type' => 1), array('id' => $kj_res['id']));
            }
        }
    }

    $config['share']['share_title'] = $goods['share_title'];
    $config['share']['share_desc'] = $goods['share_desc'];
    $config['share']['share_image'] = tomedia($goods['share_image']);
    $tourl = app_url('goods/kanjia/detail_kj', array('id' => $kid));

    $option = pdo_fetch("SELECT title , productprice FROM " . tablename("tg_goods_option") . " WHERE id=:id", array("id" => $kj_res['optionid']));
    if ($option) {
        $title = $option['title'];
        $goods['oprice'] = $option['productprice'];
    }
    //查询砍价记录
    $kj_log = pdo_getall("tg_friend_kj", array("kid" => $kid));
    foreach ($kj_log as &$v) {
        $userinfo = pdo_get("tg_member", array("openid" => $v["openid"]));
        //查询商品名称
        $goodsinfo = pdo_get("tg_kj_log", array("id" => $v['kid']));

        //查询商品信息
        $v["goods"] = pdo_get("tg_goods", array("id" => $goodsinfo['goodsid']));
        $v["name"] = $userinfo["nickname"];
        $v["headimg"] = $userinfo["avatar"];
    }

    include wl_template("goods/ranking_kj");
}
/**
 * author: 小陈
 * 砍价
 */
if ($op == "kjajax") {
    $id = $_GPC["id"];
    //获取价钱
    $price_arr = pdo_get("tg_kj_log", array("id" => $id, "uniacid" => $_W["uniacid"]));

    $price = $price_arr["price"];
    //获取价钱对应的砍价数额
    $kj_list = pdo_fetchall("select * from " . tablename('tg_goods_kj') . " where g_id = {$price_arr["goodsid"]} and uniacid = {$_W["uniacid"]} order by id asc ");
    $kj_price = array();
    foreach ($kj_list as $k => $val) {
        $rule_price[] = $dis = floatval($val['rule_price']);
        $kj_price[$dis] = $val;
        unset($dis);

    }

    krsort($kj_price);
    $kj_price = array_merge($kj_price);

    if ($price <= $kj_price[0]["last_price"]) {
        $ret = array("status" => "error", "data" => "价钱已经最低了!");
        echo json_encode($ret);
        die();
    }
    $log = pdo_get("tg_friend_kj", array("uniacid" => $_W["uniacid"], "kid" => $id, "openid" => $_W["openid"]));
    if ($log) {
        $ret = array("status" => "error", "data" => "您已经砍过价了!");
        echo json_encode($ret);
        die();
    }

    $i = 0;
    for ($i = 0; $i < count($kj_price); $i++) {
        if (floatval($kj_price[$i]["rule_price"]) <= floatval($price)) {
            break;
        }
    }

    $kjs_price = rand(floatval($kj_price[$i]['rule_start']) * 100, floatval($kj_price[$i]['rule_end']) * 100) * 0.01;
    $rand = floatval($price) - floatval($kjs_price);
    if ($rand < floatval($kj_price[$i]["last_price"])) {
        $kjs_price = floatval($price) - floatval($kj_price[$i]["last_price"]);
    }

    $rand = floatval($price) - floatval($kjs_price);
    if ($rand < floatval($kj_price[$i]["last_price"])) {
        $kprice_kj = floatval($price) - floatval($kj_price[$i]["last_price"]);
        //执行砍价入库操作
        $data["time"] = time();
        $data["kid"] = $id;
        $data["openid"] = $_W["openid"];
        $data["uniacid"] = $_W["uniacid"];
        $data["kj_price"] = $kjs_price;
        $data["price"] = $rand;
        $price_kj["price"] = $rand;
        $res_1 = pdo_update("tg_kj_log", array("price" => $data["price"]), array("id" => $id));
        if ($res_1) {
        } else {
            $ret = array("status" => "error", "data" => "数据错误!");
//            pdo_delete('tg_kj_log', array("id" => $id));
            echo json_encode($ret);
            die();
        }
    } else {
        $data["time"] = time();
        $data["kid"] = $id;
        $data["openid"] = $_W["openid"];
        $data["uniacid"] = $_W["uniacid"];
        $data["kj_price"] = $kjs_price;
        $price_kj["price"] = $rand;
        $data["price"] = $rand;
        $res_1 = pdo_update("tg_kj_log", array("price" => $data["price"]), array("id" => $id));
        if ($res_1) {
        } else {
            $ret = array("status" => "error", "data" => "数据错误!");
//            pdo_delete('tg_kj_log', array("id" => $id));
            echo json_encode($ret);
            die();
        }
    }
    $in_res = pdo_insert("tg_friend_kj", $data);
    if ($in_res) {
        $ret = array("status" => "success", "data" => $kjs_price);
        echo json_encode($ret);
        die();
    }
}
if ($op == "list") {
    $uniacid = $_W["uniacid"];
    $openid = $_W["openid"];
    $res = pdo_fetchall("select * from " . tablename("tg_kj_log") . " where openid = '{$openid}' and uniacid = {$uniacid} order by id desc ");
    for ($i = 0; $i < count($res); $i++) {

        $kj_price = pdo_getall("tg_goods_kj", array("g_id" => $res[$i]["goodsid"], "uniacid" => $_W["uniacid"]));
//        //查询商品详情
        $res[$i]["goods_detail"] = pdo_get("tg_goods", array("uniacid" => $res[$i]['uniacid'], "id" => $res[$i]["goodsid"]));
        $option = pdo_fetchall("SELECT title  AS title  FROM " . tablename("tg_goods_option") . " WHERE id=:id", array("id" => $res[$i]['optionid']));
        $title = "";
        for ($j = 0; $j < count($option); $j++) {
            if (empty($option[$j]["title"])) {

            } else {
                $title = $option[$j]["title"] . "+";
            }
        }
        $title = substr($title, 0, -1);
        $res[$i]["option"] = $title;
//        die(json_encode($kj_price));
        $res[$i]["last_price"] = $kj_price[0]["last_price"];
    }

    include wl_template("goods/goods_list_kj");
}

if ($op == "cancel") {
    $uniacid = $_W["uniacid"];
    $openid = $_W["openid"];
    $id = $_GPC["id"];

    $res = pdo_update("tg_kj_log", array("status" => 0, "is_on" => 0), array("uniacid" => $uniacid, "openid" => $openid, "is_on" => 1, 'id' => $id));
    if ($res) {
        $message = '取消成功！';
    } else {
        $message = '取消失败！';
    }
    die(json_encode(array('status' => $res, 'message' => $message)));
}

if ($op == 'limit_buy') {

    $uniacid = intval($_GPC['uniacid']);
    $openid = trim($_GPC['openid']);
    $kid = intval($_GPC['id']);
    $many_limit = pdo_getcolumn('tg_goods', array('id' => $kid), 'many_limit');
    $status = 1;
    $message = $many_limit;
    if (intval($many_limit) > 0) {
        $buy_num = pdo_fetchcolumn("select sum(gnum) from " . tablename('tg_order') . " where uniacid = {$uniacid} and openid = '{$openid}' and g_id = {$kid} and status in (0,1,2,3,8) ");
        if (intval($buy_num) + 1 > intval($many_limit)) {
            $status = 0;
            $message = '本商品总限购' . $many_limit . '件';
        }
    }
    die(json_encode(['status' => $status, 'message' => $message]));
}
