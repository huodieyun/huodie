<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * ��Ʒ���������
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
global $_W, $_GPC;
//随机 猜你喜欢
if ($op == 'goods_rand') {
    $id = intval($_GPC['id']);
    $goods = pdo_fetch('SELECT selltype FROM ' . tablename('tg_goods') . ' where id = :id', array(':id' => $id));
    $list = pdo_fetchall('SELECT id,gname,gprice,share_image,gimg,oprice,selltype,preprice FROM ' . tablename('tg_goods') . ' WHERE uniacid=:uniacid and shenhe = 0 AND isshow=1 ORDER BY rand() LIMIT 9', array(':uniacid' => $_W['uniacid']));
    foreach ($list as $key => &$value) {
        $list[$key]['url'] = app_url('goods/detail', array('id' => $value['id']));
        if (!empty($value['share_image'])) {
            $list[$key]['gimg'] = tomedia($value['share_image']);
        } else {
            $list[$key]['gimg'] = tomedia($value['gimg']);
        }
        if (in_array($value['selltype'] , [0 , 10])) {
            $value['gprice'] = $value['oprice'];
        }
        if (in_array($value['selltype'] , [7])) {
            $value['gprice'] = $value['preprice'];
        }
    }
    $data = array();
    $data['list'] = $list;
    $data['status'] = false;
    if ($config['base']['guess'] == 1) {
        $data['status'] = true;
    }


    die(json_encode($data));
    //echo json_encode($list);
}
// 某一商品所有服务
if ($op == 'goods_servies') {
    $id = intval($_GPC['id']);
    $goodsServies = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_services') .  " WHERE `cm_tg_goods_id` = :id",array(':id'=>$id));
    die(json_encode($goodsServies));
}
//商城提交页优惠券AJAX
if ($op == 'couponajax') {
    wl_load()->model('activity');
    $coupon = coupon_canuse($_W['openid'], $_GPC['productprice']);
    echo json_encode($coupon);
    exit;
}
if ($op == 'collect') {
    global $_W, $_GPC;
    //会员的openid
    $openid = $_W["openid"];
    $setting = setting_get_by_name("base");
    $setting = $setting["deliverytype"];
    //查询会员等级享受的折扣
    $res = pdo_fetch("SELECT * FROM " . tablename("tg_member") . " WHERE uniacid=:uniacid AND openid=:openid", array(":uniacid" => $_W["uniacid"], ":openid" => $openid));
    $level = $res["level"];//会员等级
    //查询相应等级对应的权益是多少
    $level_rights = pdo_fetch("SELECT * FROM " . tablename("tg_member_leave") . " WHERE uniacid=:uniacid AND id=:id", array(":uniacid" => $_W["uniacid"], ':id' => $level));
    //计算会员折扣
    $rights = $level_rights["rights"];
    if (empty($rights) || !$rights) {
        //不打折计算原价
    }
    $list = pdo_fetchall('SELECT a.* FROM ' . tablename('tg_collect') . ' a left join cm_tg_goods b on a.sid=b.id  WHERE a.uniacid=:uniacid AND a.openid=:openid AND a.orderno=:orderno and b.gnum>0 and b.isshow=1', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':orderno' => 0));
    foreach ($list as $key => $value) {
        $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' where id = :id', array(':id' => $value['sid']));
        $merchant = pdo_fetch('SELECT * FROM ' . tablename('tg_merchant') . ' where id = :id', array(':id' => $goods['merchantid']));
        $list[$key]['img'] = tomedia($goods['gimg']);
        $list[$key]['price'] = doubleval($value['oprice']);
//		$list[$key]['price']= $list[$key]['price']*$rights/10;
        // $list[$key]['oprice']= doubleval($value['oprice'] / ($value['discount_num'] / 10));
        $list[$key]['title'] = $goods['gname'];
        $list[$key]['weight'] = $value['weight'];
        $list[$key]['onelimit'] = $goods['one_limit'];
        $list[$key]['manylimit'] = $goods['many_limit'];
        $list[$key]['merchantid'] = $goods['merchantid'];
        $list[$key]['merchantname'] = $merchant['name'];
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['sid']));
        $list[$key]['history_limit'] = intval($old_data['num']);
        $list[$key]['num'] = $value['num'];
        $list[$key]['id'] = $goods['id'];
        $list[$key]['kucun'] = $goods['gnum'];
        $list[$key]['deliverytype'] = $merchant['deliverytype'];
        if ($goods['merchantid'] == 0) {
            $list[$key]['deliverytype'] = $setting;
        }
        $list[$key]['is_wholesale'] = $goods['is_wholesale'];
        $list[$key]['wholesale_level'] = unserialize($goods['wholesale_level']);
        $list[$key]['wholesale_num'] = $goods['wholesale_num'];
    }

    die(json_encode($list));
}
//商品添加
if ($op == 'add') {
    $id = $_GPC['id'];
    $str = $_GPC['guige'];//规格
    $weight = floatval($_GPC['weight']);//重量
    $kunum1 = pdo_fetch("SELECT productprice,stock,weight FROM " . tablename('tg_goods_option') . " WHERE   goodsid='{$id}' and title='{$str}'  ");
    $price = $kunum1['productprice'];
    $gnum = $kunum1['stock'];
    if ($weight == 0) {
        $weight = $kunum1['weight'];
    }
    if (empty($id)) {
        if (in_array($uniacid_tpl['tpl'] , [8147])) {
            die(json_encode(['status' => 0]));
        }
        echo 0;
        exit;
    } else {
//        $sql = 'SELECT oprice,supprices,storeid,credit,commissiontype,commission,merchantid,zititime,gname,weight,com_type FROM ' . tablename('tg_goods') . ' WHERE id=:id AND uniacid=:uniacid';
        $sql = 'SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id AND uniacid=:uniacid';
        $paramse = array(':id' => $id, ':uniacid' => $_W['uniacid']);
        $goods = pdo_fetch($sql, $paramse);
        $goods['wholesale_level'] = unserialize($goods['wholesale_level']);
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

        $data = array(
            'openid' => $_W['openid'],
            'uniacid' => $_W['uniacid'],
            'num' => 1,
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
        $num = $tt['num'] + 1;
        if ($num > intval($gnum)) {
            if (in_array($uniacid_tpl['tpl'] , [8147])) {
                die(json_encode(['status' => -1 , 'goods' => $goods]));
            }
            echo '-1';
            exit;
        }
        if (empty($tt)) {
            if (pdo_insert('tg_collect', $data)) {
                if (in_array($uniacid_tpl['tpl'] , [8147])) {
                    die(json_encode(['status' => 1 , 'goods' => $goods]));
                }
                echo 1;
            } else {
                //
                if (in_array($uniacid_tpl['tpl'] , [8147])) {
                    die(json_encode(['status' => 0 , 'goods' => $goods]));
                }
                echo 0;
                //
            }
        } else {
            pdo_update('tg_collect', array('num' => $num), array('id' => $tt['id']));
            if (in_array($uniacid_tpl['tpl'] , [8147])) {
                die(json_encode(['status' => 1 , 'goods' => $goods]));
            }
            echo 1;
            exit;
        }
    }
}
if ($op == 'remove') {
    $id = $_GPC['id'];
    $str = $_GPC['guige'];//规格
    if (empty($id)) {
        echo 0;
        exit;
    } else {
        //
        $tua = pdo_fetch("SELECT num,id FROM " . tablename('tg_collect') . " WHERE  sid = {$id} and uniacid = '{$_W['uniacid']}' AND orderno = '0' and item='{$str}'  and openid='{$_W['openid']}'");
        $num = $tua['num'] - 1;
        if ($num > 0) {

            if (pdo_update('tg_collect', array('num' => $num), array('id' => $tua['id']))) {
                echo 1;
                exit;
            }
        } else {
            if (pdo_delete('tg_collect', array('id' => $tua['id']))) {
                echo 1;
                exit;
            }
        }
        //


    }
}
//幻灯片
if ($op == 'banner') {
    $advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_W['uniacid']}' order by displayorder DESC");
    foreach ($advs as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
        if (substr($adv['link'], 0, 5) != 'http:') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
    die(json_encode($advs));
}
//分类
if ($op == 'category') {
    $category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and enabled=1 and parentid=0 ORDER BY parentid ASC, displayorder DESC");
    foreach ($category as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
        $adv['smallthumb'] = tomedia($adv['smallthumb']);
    }
    die(json_encode($category));
}
//商品详情
if ($op == 'goods_detail') {
    $id = intval($_GPC['id']);
    $goods = goods_get_by_params("id = {$id}");
//得到图集
    $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$id}'");
    foreach ($advs as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
        if (substr($adv['link'], 0, 5) != 'http:') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
    $goods['advs'] = $advs;
    $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$id}' ");
    $goods['params'] = $params;
    die(json_encode($goods));
}
if ($op == 'storelist') {
    global $_GPC;
    $uniacid = $_GPC['uniacid'];
    $g_id = $_GPC['g_id'];
    wl_load()->model('setting');
    $setting = setting_get_by_name('kaiguan');
    $goods = pdo_fetch("select hexiao_id from" . tablename('tg_goods') . "where id = '{$g_id}' and uniacid='{$uniacid}'");

    if (!empty($goods['hexiao_id'])) {
        $store_ids = explode(',', substr($goods['hexiao_id'], 0, strlen($goods['hexiao_id']) - 1));
        $storelist = array();
        if ($setting['zitidingwei'] == 1) {
            foreach ($store_ids as $k1 => $v1) {
                $store = pdo_fetch('SELECT * FROM ' . tablename('tg_store') . ' WHERE id=:id', array(':id' => $v1));
                $lat = $_GPC['lat'];
                $lng = $_GPC['lng'];

                $dis = $distance = getDistance($lat, $lng, $store['lat'], $store['lng']);
                $stock = pdo_fetchall("select * from cm_tg_goods_store_stock where uniacid={$uniacid} and goodsid={$g_id} and storeid={$v1}");
                $stores[$k1] = $store;
                $stores[$k1]['stock'] = $stock;
                $stores[$k1]['distance'] = $distance;

                if ($distance / 10000 > 1) {
                    $stores[$k1]['distance'] = number_format($distance / 1000) . "公里";
                } else {
                    $stores[$k1]['distance'] = $distance . "米";
                }

                $storelist[$dis] = $stores[$k1];

            }
            ksort($storelist);
            $storelist = array_merge($storelist);
        } else {
            foreach ($store_ids as $k2 => $v2) {
                $store = pdo_fetch('SELECT * FROM ' . tablename('tg_store') . ' WHERE id=:id', array(':id' => $v2));
                $stock = pdo_fetchall("select * from cm_tg_goods_store_stock where uniacid={$uniacid} and goodsid={$g_id} and storeid={$v2}");
                $storelist[$k2] = $store;
                $storelist[$k2]['stock'] = $stock;

            }
        }
        die(json_encode($storelist));
    } else {
        die(json_encode(array('status' => 0)));
    }
    die(json_encode(array('status' => 0)));
}
if ($op == 'merchant_list') {
    global $_GPC;
    $uniacid = $_GPC['uniacid'];
    $merchant_id = $_GPC['merchant_id'];

    $store = pdo_fetchall("select * from " . tablename('tg_store') . " where merchantid = '{$merchant_id}' and uniacid = '{$uniacid}' ");
    foreach ($store as &$item) {
        if (empty($item['cost'])) {
            $item['cost'] = 0;
        }
        $item['cost'] = sprintf("%.2f", $item['cost']);
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

?>