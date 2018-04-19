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

$member = pdo_fetch("SELECT id,total,from_user,parentid,enable FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
$member1 = pdo_fetch("SELECT id,total,from_user,parentid FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and enable = 1  and uniacid = '{$uniacid}'");
$member2 = pdo_fetch("SELECT id,total,from_user,parentid FROM " . tablename('tg_member') . " WHERE id = '{$_GPC['sharenum']}'  and uniacid = '{$uniacid}'");
if (empty($member1)) {
    $sharen = 0;
} else {
    $sharen = $member1['id'];
}

$to_url = app_url('order/mycashorder') . "&sharenum=" . $sharen;
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
$member = pdo_fetch("SELECT id,total,from_user,parentid,type,shopname FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}' ");
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
$share_images = tomedia($config['share']['share_image']);

$op = intval($_GPC['op']); //op=0对应获取全部订单,op=1对应获取未结算订单,op=2对应获取已结算订单
if (empty($_GPC['op'])) {
    $op = 1;
}

$weid = $uniacid;

$member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = '{$uniacid}' and from_user = '{$openid}'");
//die(json_encode($member));
if (empty($member['addtime'])) {
    $member['addtime'] = time();
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

if ($member['enable'] != 1) {

    header("location: " . app_url('order/infojob'));
    exit;
} else {
    include wl_template('order/mycashorder');
}


exit();

?>