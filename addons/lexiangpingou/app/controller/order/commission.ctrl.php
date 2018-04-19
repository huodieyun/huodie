<?php

global $_GPC, $_W;
wl_load()->model('setting');

$config = tgsetting_load();
$uniacid = $_W['uniacid'];
$openid = $_W['openid'];
if (empty($uniacid)) {
    $uniacid = $_GPC['uniacid'];
}
if (empty($openid)) {
    $openid = $_W['openid'] = $_GPC['openid'];
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
if ($op == 0) {

    $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,addname,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' {$con} and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
    foreach ($orders1 as $ka) {
        $orders[] = $ka;
    }

} elseif ($op == 1) {
    //获取当前用户未结算订单信息
    $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' {$con} AND comtype <> 1 and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
    foreach ($orders1 as $ka) {
        $orders[] = $ka;
    }

} elseif ($op == 2) {
    //获取当前用户已结算订单信息 在数据表里status = 2代表待收货
    $orders1 = pdo_fetchall("SELECT comtype,g_id,id,orderno,price,cost_fee,commission,tuan_id,createtime,commissiontype,openid,freight,gnum FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' {$con} AND comtype = 1 and status in(2,3,8) and (com_type = 1 or g_id = 0) and ptime > {$member['addtime']} and openid in (select from_user FROM " . tablename('tg_member') . " where parentid = {$member['id']}) order by createtime desc ");
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

foreach ($orders as &$order) {

    $comprice = 0;
    $price = floatval($order['price']) - floatval($order['cost_fee']) - floatval($order['freight']);
    $mem = pdo_fetch("SELECT nickname,avatar FROM " . tablename('tg_member') . " WHERE uniacid = " . $_W['uniacid'] . " AND from_user = '{$order['openid']}' ");

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
//die(json_encode($list));

include wl_template('order/commission');

exit();

?>