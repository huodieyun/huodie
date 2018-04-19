<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * ��Ʒ���������
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'add';
global $_W, $_GPC;

if ($op == 'add') {
    $id = $_GPC['id'];//商品id
    $str = $_GPC['guige'];//规格
    $weight = floatval($_GPC['weight']);//规格
    //接受数量
    $num = $_GPC["num"];
    $num = intval($num);
    if (!empty($num)) {
        //获取商品的详情情况
        $good_conf = pdo_fetch("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id AND uniacid = :uniacid ", array(":id" => $id, ":uniacid" => $_W["uniacid"]));//商品详情
        //查看是否限购
        if (intval($good_conf["one_limit"]) == 0) {
            //0为不限购
        } else {
            //如果数量大于 限购数量是拒绝的
            if ($num > intval($good_conf["one_limit"])) {
                $ret = array("status" => "error", "data" => "单次购买数量超出限制");
                die(json_encode($ret));
            }
//            $cert = pdo_fetch("SELECT sum(num) AS cert_nums FROM " . tablename("tg_collect") . " WHERE openid = :openid AND sid = :id AND uniacid = :uniacid and orderno = '0' ", array(":openid" => $_W["openid"], ":id" => $id, ":uniacid" => $_W["uniacid"]));
//            $cert = intval($cert['cert_nums']);
//            if ($cert + $num > intval($good_conf["one_limit"])) {
//                $ret = array("status" => "error", "data" => "单次购买数量超出限制");
//                die(json_encode($ret));
//            }
            //判断总量
            //判断购买总量
            $order_num = pdo_fetch("SELECT count(*) AS num FROM " . tablename("tg_order") . " WHERE openid = :openid AND g_id = :id AND uniacid = :uniacid", array(":openid" => $_W["openid"], ":id" => $id, ":uniacid" => $_W["uniacid"]));
            $num_ed = $order_num["num"];
            if ($good_conf["many_limit"] == 0) {
                //不限购
            } else {
                if (intval($num_ed) + $num > intval($good_conf["many_limit"])) {
                    $ret = array("status" => "error", "data" => "购买总数量超出限制");
                    die(json_encode($ret));
                }
                //判断购物车里面是否大于
                $cert_num = pdo_fetch("SELECT sum(num) AS cert_nums FROM " . tablename("tg_collect") . " WHERE openid = :openid AND sid = :id AND uniacid = :uniacid", array(":openid" => $_W["openid"], ":id" => $id, ":uniacid" => $_W["uniacid"]));
                $cert_num = intval($cert_num['cert_nums']);
                if ($cert_num + $num > intval($good_conf["many_limit"])) {
                    $ret = array("status" => "error", "data" => "购买总数量超出限制");
                    die(json_encode($ret));
                }
            }
        }
    } else {
        $num = 1;
        $good_conf = pdo_fetch("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id AND uniacid = :uniacid", array(":id" => $id, ":uniacid" => $_W["uniacid"]));
        if (intval($good_conf["one_limit"]) == 0) {

        } else {
            if ($num > intval($good_conf["one_limit"])) {
                $ret = array("status" => "error", "data" => "单次购买数量超出限制");
                die(json_encode($ret));
            }
//            $cert = pdo_fetch("SELECT sum(num) AS cert_nums FROM " . tablename("tg_collect") . " WHERE openid = :openid AND sid = :id AND uniacid = :uniacid and orderno = '0' ", array(":openid" => $_W["openid"], ":id" => $id, ":uniacid" => $_W["uniacid"]));
//            $cert = intval($cert['cert_nums']);
//            if ($cert + $num > intval($good_conf["one_limit"])) {
//                $ret = array("status" => "error", "data" => "单次购买数量超出限制");
//                die(json_encode($ret));
//            }

        }
        //判断购买总量

        if (intval($good_conf["many_limit"] == 0)) {

        } else {
            $order_num = pdo_fetch("SELECT count(*) AS num FROM " . tablename("tg_order") . " WHERE openid = :openid AND g_id = :id", array(":openid" => $_W["openid"], ":id" => $id));
            $num_ed = $order_num["num"];
            if (intval($num_ed) + $num > $good_conf["many_limit"]) {
                $ret = array("status" => "error", "data" => "购买总数量超出限制");
                die(json_encode($ret));
            }
            //判断购物车里面的数量是否大于总数量
            $cert_num = pdo_fetch("SELECT sum(num) AS cert_nums FROM " . tablename("tg_collect") . " WHERE openid = :openid AND sid = :id AND uniacid = :uniacid", array(":openid" => $_W["openid"], ":id" => $id, ":uniacid" => $_W["uniacid"]));
            $cert_num = intval($cert_num['cert_num']);
            if ($cert_num + $num > $good_conf["many_limit"]) {
                $ret = array("status" => "error", "data" => "购买总数量超出限制");
                die(json_encode($ret));
            }
        }
    }

    $kunum1 = pdo_fetch("SELECT productprice,stock,weight FROM " . tablename('tg_goods_option') . " WHERE   goodsid='{$id}' and title='{$str}'  ");
    $price = $kunum1['productprice'];
    $gnum = $kunum1['stock'];
    if ($weight == 0) {
        $weight = $kunum1['weight'];
    }
    if (empty($id)) {
        echo 0;
        exit;
    } else {
        $sql = 'SELECT oprice,supprices,storeid,credit,commissiontype,commission,merchantid,weight,com_type FROM ' . tablename('tg_goods') . ' WHERE id=:id AND uniacid=:uniacid';
        $paramse = array(':id' => $id, ':uniacid' => $_W['uniacid']);
        $goods = pdo_fetch($sql, $paramse);
        if (empty($str)) {
            $price = $goods['oprice'];
            if ($weight == 0) {
                $weight = $goods['weight'];
            }
        }
        $discount = 10;
        if ($goods['discount'] == 1) {

            //证明是计算打折后的价钱 查询会员的等级和折扣详情
            $level = pdo_fetch("SELECT level FROM " . tablename("tg_member") . " WHERE uniacid = :uniacid AND openid = :openid", array(":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"]));
            if (!empty($level)) {
                //获取会员的等级 匹配会员等级享受的折扣
                $rights = pdo_fetch("SELECT rights FROM " . tablename("tg_member_leave") . " WHERE uniacid = :uniacid AND id = :id", array(":uniacid" => $_W["uniacid"], ":id" => $level['level']));

                if (!empty($rights) || $rights['rights'] != 0) {
                    $discount = $rights['rights'];
                    $price = $price * $rights['rights'] / 10;
                    //计算价钱
                }
            }
        }
    }
    $data = array(
        'openid' => $_W['openid'],
        'uniacid' => $_W['uniacid'],
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
        'goodsname' => $goods['gname'],
        'zititime' => $goods['zititime'],
        'com_type' => $goods['com_type'],
        'sid' => $id
    );

    $tt = pdo_fetch("SELECT id,num FROM " . tablename('tg_collect') . " WHERE  uniacid = '{$_W['uniacid']}' and sid='{$id}'  and openid='{$_W['openid']}' and item='{$str}'  and orderno='0'");
    $kunum = pdo_fetch("SELECT gnum FROM " . tablename('tg_goods') . " WHERE  uniacid = '{$_W['uniacid']}' and id='{$id}'  ");
    if (intval($gnum) == 0 && empty($str)) {
        $gnum = $kunum['gnum'];
    }

    $num = $tt['num'] + $num;
    if ($num > intval($gnum)) {
        echo '-1';
        exit;
    }
    if (empty($tt)) {
        if (pdo_insert('tg_collect', $data)) {
            echo 1;
        } else {
            //

            echo 0;
            //
        }
    } else {
        pdo_update('tg_collect', array('num' => $num), array('id' => $tt['id']));
        echo 1;
        exit;
    }
}