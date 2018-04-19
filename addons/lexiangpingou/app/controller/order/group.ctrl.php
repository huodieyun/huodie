<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 团详情控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
wl_load()->model('functions');
wl_load()->classs('qrcode');
$setting = pdo_fetch("select `value` from " .tablename('tg_setting') ." where uniacid = {$_W['uniacid']} and `key` = 'kaiguan' ");
$setting = unserialize($setting['value']);
//header("Access-Control-Allow-Origin: http://a.com"); // 允许a.com发起的跨域请求
//如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
//header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
//header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');

$createqrcode = new creat_qrcode();
//if($ip=='112.16.53.66'){
//    $_W['openid'] = 'oU5kJwn9z3qiPTScagjcJuEDRVLE';
//}
$checkfunction = checkfunc(8167);
$tuan_id = intval($_GPC['tuan_id']);
$member = member_get_by_params("from_user='" . $_W['openid'] . "' and uniacid='" . $_W['uniacid'] . "'");
$tourl = app_url('order/group', array('tuan_id' => $tuan_id));

$group = pdo_fetch("SELECT neednum,lacknum,groupstatus,goodsid,selltype,nownum FROM " . tablename('tg_group') . " where groupnumber='{$tuan_id}' ");

if ($group['groupstatus'] == 3) {

    $acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
    $order = pdo_get('tg_order' , array('tuan_id' => $tuan_id , 'tuan_first' => 1 , 'status' => 1));
    if ($acct['tpl'] == 8209 && $order['comadd']) {
        $storeid = $order['comadd'];
    }
}

$total_num = $group['nownum'];
if ($group['selltype'] == 7) {
    header("Location: " . app_url('goods/detail', array('tuan_id' => $tuan_id, 'id' => $group['goodsid'], 'g_detail' => 1)));
    exit;
}
if ($_W['openid']) {
    puv($_W['openid'], $group['goodsid']);
}
$g = pdo_fetch("select is_amount,selltype from " . tablename('tg_goods') . " where id = '{$group['goodsid']}'");
if ($g['is_amount'] == 1 && ($g['selltype'] == 4 || $g['selltype'] == 7)) {
    $count = pdo_fetch("SELECT SUM(gnum) as num FROM " . tablename('tg_order') . " where tuan_id='{$tuan_id}' and status in (1,2,3,6,7,10,8) ");
} else {
    $count = pdo_fetch("SELECT COUNT(*) as num FROM " . tablename('tg_order') . " where tuan_id='{$tuan_id}' and status in (1,2,3,6,7,10,8) ");
}
$noworder = pdo_fetch('select id from ' . tablename('tg_order') . ' where openid=:openid and tuan_id=:tuan_id and status in (1,2,3,6,7,10,8)', array(':openid' => $_W['openid'], ':tuan_id' => $tuan_id));
if (!empty($noworder)) {
    $count_order = pdo_fetch('select count(id) as total from ' . tablename('tg_order') . ' where id<:id and tuan_id=:tuan_id and status in (1,2,3,6,7,10,8)', array(':id' => $noworder['id'], ':tuan_id' => $tuan_id));
    $sortnum = $count_order['total'] + 1;
}

if ($count['num'] < $group['neednum']) {
    $num = $group['neednum'] - $count['num'];//需要人数-已由人数求得实际所需人数
    pdo_update('tg_group', array('lacknum' => $num), array('groupnumber' => $tuan_id));
} else if ($count['num'] = $group['neednum']) {
    pdo_update('tg_group', array('groupstatus' => 2, 'lacknum' => 0), array('groupnumber' => $tuan_id));
}

if ($checkfunction['status'] && $member['enable'] == 1) {

    $tourl = app_url('order/group', array('tuan_id' => $tuan_id, 'sharenum' => $member['id']));

}

if ($checkfunction['status'] && intval($_GPC['sharenum']) != 0 && $member['parentid'] == -1) {

    $data = array('parentid' => $_GPC['sharenum']);
    $params = array('from_user' => $_W['openid']);
    member_update_by_params($data, $params);
}
$member = member_get_by_params("from_user='" . $_W['openid'] . "'");
if (!$checkfunction['status'] || $member['parentid'] == -1) {
    $data = array('parentid' => 99);
    $params = array('from_user' => $_W['openid']);
    member_update_by_params($data, $params);
}

if (!empty($tuan_id)) {
    //取得该团所有订单

    if ($group['neednum'] > 300) {
        $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " where is_tuan in(1,3) and status in(1,2,3,4,5,6,7,8,10) and tuan_id='{$tuan_id}' and uniacid = {$_W['uniacid']} order by createtime asc LIMIT 150");
        $nownum = 150 - count($orders);
        $num_arrs = array();
        for ($i = 0; $i < $nownum; $i++) {
            $num_arrs[$i] = $i;
        }
    } else {
        $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " where is_tuan in(1,3) and status in(1,2,3,4,5,6,7,8,10) and tuan_id='{$tuan_id}' and uniacid = {$_W['uniacid']} order by createtime asc");
    }
    //$data = order_get_list(array('is_tuan'=>'1,3','status'=>' 1,2,3,4,5,6,7,8','tuan_id'=>$tuan_id));
    //$orders = $data['list'];
    //message(count($orders));
    foreach ($orders as $key => $value) {
        if ($value['address'] == '虚拟') {
            $orders[$key]['avatar'] = $value['openid'];
            $orders[$key]['nickname'] = $value['addname'];
        } else {
            $fans = member_get_by_params(" openid = '{$value['openid']}'");
            if (!empty($fans)) {
                $avatar = $fans['avatar'];
                $nickname = $fans['nickname'];
            }
            $orders[$key]['avatar'] = $avatar;
            $orders[$key]['nickname'] = $nickname;
        }
        unset($avatar);
        unset($nickname);
    }

    //取团长订单$order
    $order = order_get_by_params(" tuan_id = {$tuan_id} and tuan_first = 1 ");
    if ($order['address'] == '虚拟') {
        $order['avatar'] = $order['openid'];
        $order['nickname'] = $order['addname'];
    } else {
        $fans = member_get_by_params(" openid = '{$order['openid']}'");
        if (!empty($fans)) {
            $avatar = $fans['avatar'];
            $nickname = $fans['nickname'];
        }
        $order['avatar'] = $avatar;
        $order['nickname'] = $nickname;
    }
    // 查出该商品的所有团购订单的团购ID
    $sql4 = "select * from" . tablename('tg_group') . "where goodsId=:g_id and groupstatus=2 limit 0,10";
    $params4 = array(':g_id' => $order['g_id']);
    $allorder = pdo_fetchall($sql4, $params4);
    foreach ($allorder as $key => $value) {
        $data['content'] = "恭喜团单号:" . $value['groupnumber'] . "团组团成功，我们尽快安排发货<br>";
        $sendadd[] = $data;
    }
    //自己的订单，若没有参团则$myorder为空
    $myorder = order_get_by_params(" openid = '{$_W['openid']}' and tuan_id = {$tuan_id} and status in(1,2,3,4,5,6,7,8,10) ");
    //团状态
    $tuaninfo = group_get_by_params(" groupnumber = {$tuan_id} ");
    $tuan_lacknum = $tuaninfo['lacknum'];
    $shownum = count($orders);
    if ($group['neednum'] > 300) {
        if ($tuaninfo['groupstatus'] == 2) {
            $lastorder = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " where is_tuan in(1,3) and status in(1,2,3,4,5,6,7,8,10) and tuan_id='{$tuan_id}' and uniacid = {$_W['uniacid']} order by ptime desc  limit 3");
        } else {
            $tuan_lacknum = 5;
        }
    }
    $num_arr = array();
    for ($i = 0; $i < $tuan_lacknum; $i++) {
        $num_arr[$i] = $i;
    }
    if (empty($order['g_id'])) {
        echo "<script>alert('组团信息不存在！');location.href='" . app_url('home/index') . "';</script>";
        exit;
    } else {
        $goods = goods_get_by_params(" id = {$order['g_id']} ");
        $goods['goodsdesc'] = str_replace("\n", "", $goods['goodsdesc']);
        $goods['goodsdesc'] = str_replace("\r", "", $goods['goodsdesc']);
        $goods['goodsdesc'] = str_replace("\t", "", $goods['goodsdesc']);
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = :goodsid ", array(':goodsid' => $order['g_id']));
        $subscribe = $goods['subscribe'];
        //阶梯团
        if ($goods['group_level_status'] == 2) {
            $param_level = unserialize($tuaninfo['group_level']);
            for ($i = 0; $i < count($param_level) - 1; $i++) {
                for ($j = 0; $j < count($param_level) - $i - 1; $j++) {
                    if ($param_level[$j]['groupnum'] > $param_level[$j + 1]['groupnum']) {
                        $temp = $param_level[$j];
                        $param_level[$j] = $param_level[$j + 1];
                        $param_level[$j + 1] = $temp;
                    }
                }
            }

            if ($param_level) {
                $num = round(((100 - count($param_level) * 2) / count($param_level)));
            }
            $goods['p'] = $param_level[0]['groupprice'];
            foreach ($param_level as $item) {
                $numPerson .= "," . $item['groupnum'];
                $numPrices .= "," . $item['groupprice'];
            }
            $numGroup = $tuaninfo['neednum'];
        }
        $endtime = $tuaninfo['endtime'];
        $time = time(); /*当前时间*/
        $lasttime2 = $endtime - $time;//剩余时间（秒数）
        $lasttime = $goods['endtime'];
        //规格及规格项
        $allspecs = pdo_fetchall("select * from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $goods['id']));
        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("select * from " . tablename('tg_spec_item') . " where  `show`=1 and specid=:specid order by displayorder asc", array(":specid" => $s['id']));
        }
        unset($s);
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,stock from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $goods['id']));
        $specs = array();
        if (count($options) > 0) {
            $specitemids = explode("_", $options[0]['specs']);
            foreach ($specitemids as $itemid) {
                foreach ($allspecs as $ss) {
                    $items = $ss['items'];
                    foreach ($items as $it) {
                        if ($it['id'] == $itemid) {
                            $specs[] = $ss;
                            break;
                        }
                    }
                }
            }
        }
    }
    /*判断购买次数*/
    $data1 = order_get_list(array('g_id' => $goods['id'], 'openid' => $_W['openid'], 'status' => '1,2,3,6,8'));
    $times = $data1['total'];
    if (empty($goods['share_desc'])) {
        $share_desc = $config['share']['share_desc'];
    } else {
        $share_desc = $goods['share_desc'];
    }
    if ($tuaninfo['lacknum'] > 0 && $tuaninfo['groupstatus'] != 2) {
        $share_title = "【差" . $tuaninfo['lacknum'] . "人】我参加了" . $_W['uniaccount']['name'] . $goods['gname'] . "的团";

        $config['share']['share_title'] = "【差" . $tuaninfo['lacknum'] . "人】我参加了" . $_W['uniaccount']['name'] . $goods['gname'] . "的团";
    } else {
        $config['share']['share_title'] = "【已成团】我参加了" . $_W['uniaccount']['name'] . $goods['gname'] . "的团";
    }
    if ($_W['uniacid'] == 557) {
        $config['share']['share_title'] = !empty($goods['share_title']) ? $goods['share_title'] : $share_title;

    }

    if ($uniacid_tpl['tpl'] == 8210) {

        if ($tuaninfo['neednum'] > 4) {
            $orders = array_slice($orders,0,3);
        }
        if (count($orders) > 2) {
            $num_arrs = [];
        } else if (count($orders) == 2) {
            $num_arrs = [1];
        } else if (count($orders) == 1) {
            if ($tuaninfo['neednum'] == 1) {
                $num_arrs = [];
            } else if ($tuaninfo['neednum'] == 2) {
                $num_arrs = [1];
            } else if ($tuaninfo['neednum'] == 3) {
                $num_arrs = [1, 2];
            } else if ($tuaninfo['neednum'] == 4) {
                $num_arrs = [1,2,3];
            } else if ($tuaninfo['neednum'] > 4) {
                $num_arrs = [1,2];
            }
        }

        //得到图集
        $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$goods['id']}'");
        foreach ($advs as &$adv) {
            if (substr($adv['link'], 0, 4) != 'http') {
                $adv['link'] = "https://" . $adv['link'];
            }
            unset($adv);
        }
        $params = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_param') . " WHERE goodsid = '{$goods['id']}' ");

        $play_num = pdo_fetch("SELECT play_num FROM " . tablename("account_wechats") . " WHERE uniacid=:uniacid", array(":uniacid" => $_W["uniacid"]));
        if ($play_num["play_num"] == 0) {
            unset($goods["index_video"]);
        } else {
            $video_res = pdo_fetch("SELECT * FROM " . tablename("tg_goods_video") . " WHERE media_url LIKE :media_url", array(":media_url" => '%' . $paly_url . '%'));
            if ($video_res) {
                $video_id = $video_res["id"];
            } else {
                $video_id = "";
            }
        }
        unset($video_res);
        unset($play_num);
    }

    //
    $config['share']['share_desc'] = $goods['share_desc'];
    $config['share']['share_url'] = $tourl;
    $config['share']['share_image'] = $goods['share_image'];
    $pagetitle = $goods['gname'];
    $list = pdo_fetchall("select openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time from " . tablename('tg_judgment') . " where gid = '{$goods['id']}' and check_status = 1 ORDER BY create_time desc LIMIT 0,2");
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . " where gid = '{$goods['id']}' and check_status = 1 ORDER BY create_time DESC ");

    session_start();
    $_SESSION['goodsid'] = $goods['id'];
    $_SESSION['tuan_id'] = $tuan_id;
    $_SESSION['groupnum'] = $tuaninfo['neednum'];
    $result = array('statustype' => 0);
    if ($tuaninfo['selltype'] == 6 && empty($myorder)) {
        $lists = pdo_fetch("select id from" . tablename('tg_order') . "where openid = '{$_W['openid']}' and uniacid = '{$_W['uniacid']}' and status in (1,2,3,8,10,7)  order by id asc limit 1");
        if (!empty($lists)) {
            $result = array('statustype' => 1);
        }
    }
    // 修改单次开团bug
    if ($goods['one_group'] == 1) { // 为单次开团商品
        $one_group_list = pdo_fetch("SELECT * from " . tablename('tg_group') . " where goodsid='{$goods['id']}' AND `groupstatus` = 3 AND uniacid = '{$_W['uniacid']}'");
        // 有进行中的团
        if (count($one_group_list) > 0 && $tuaninfo['groupstatus'] == 2) {
            $tuaninfo['one_group_number'] = $one_group_list['groupnumber'];
        }
    }

    if ($goods['selltype'] == 2) {
        include wl_template('order/lingroup');
    } else {
        if ($uniacid_tpl['tpl'] == 8210) {
//        if ($_W['uniacid'] == 33) {
            include wl_template('order/group8');
        } else {
            include wl_template('order/group');
        }
    }

} else {
    echo "<script>alert('参数错误');location.href='" . app_url('home/index') . "';</script>";
}
exit();