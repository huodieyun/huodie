<?php

defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$uniacid = $_W['uniacid'];

$agent = pdo_get('tg_agents', array('uniacid' => $uniacid));


if ($op == 'display') {
    $status = intval($_GPC['status']); // isout(状态) -1:110； 0:待沟通；1：沟通中；2:重点跟进；3:订单套餐；4:VIP套餐；5:开通VIP；6:活动套餐；7:代理商 -3:淘汰库；

    $lx = intval($_GPC['lx']); // lx(联系) 1:及时联系；2:及时续费

    if ($status != 5) { // 不是开通VIP状态；
        $condition = " and isout = {$status} ";
    }

    if ($lx > 0) { // 重写筛选；
        $condition = " and lx = {$lx} ";
    }
    if ($status == 6) { // 重写活动套餐；
        $condition = " and ( isout = 6 or active = 1 )";
        // $condition = " and ( isout = {$status} or active = 1 )";
    }

    // 找到该登陆公众号，获得推荐码
    $referral = pdo_fetch("select referral from " . tablename('tg_agents') . " where uniacid = :uniacid ", array(':uniacid' => $_W['uniacid']));
    // 分页
    $pindex = max(1, intval($_GPC['page'])); // 页码
    $psize = 10;


    // 编辑
    $type = $_GPC['type'];
    if ($type == 1) {
        $time = $_GPC['time'];
        $join = $_GPC['join'];
    }
    if (!empty($time) || !empty($_GPC['keyword_gzname'])) {
        $condition .= " AND uid in (select uid from " . tablename('uni_account_users') . " where uniacid in (select uniacid from " . tablename('account_wechats') . " where 1 ";
    }

    if (!empty($time)) {
        $starttime = strtotime($time['start']);
        $endtime = strtotime($time['end']);
        $condition .= " AND endtime < '{$endtime}' and endtime > '{$starttime}' ";
    }

    if (!empty($_GPC['keyword_gzname'])) {
        $condition .= " and name LIKE '%{$_GPC['keyword_gzname']}%' ";
    }

    if (!empty($time) || !empty($_GPC['keyword_gzname'])) {
        $condition .= " order by endtime asc  )) ";
    }

    if (!empty($_GPC['keyword_user'])) {
        $keyword_user = trim($_GPC['keyword_user']);
        $condition .= " AND ( username LIKE '%{$keyword_user}%' or uid in ( select uid from " . tablename('users_profile')
            . " where realname LIKE '%{$keyword_user}%' or workerid LIKE '%{$keyword_user}%' or savor LIKE '%{$keyword_user}%' or mobile LIKE '%{$keyword_user}%' ) ) ";
    }

    if (!empty($join)) {
        $starttime = strtotime($join['start']);
        $endtime = strtotime($join['end']);
        $condition .= " AND joindate < '{$endtime}' and joindate > '{$starttime}' ";
    }
    // 排序
    $orderby = " order by ";
    $order_by = intval($_GPC['order_by']);
    $joindate = " joindate desc ";
    $lastvisit = " lastvisit ";
    if ($order_by == 0) {
        $orderby .= $joindate . " , " . $lastvisit;
    } elseif ($order_by == 1) {
        $orderby .= $lastvisit . " desc , " . $joindate;
    } elseif ($order_by == 2) {
        $orderby .= $lastvisit . " asc , " . $joindate;
    }

    $boom = 0;
    // 获取含有该登陆公众号推荐码的所有用户
    $alluser = pdo_fetchall("select uid,username,joindate,referral,active,lx,FROM_UNIXTIME(lastvisit,'%Y-%m-%d') as lastvisit from " . tablename('users')
        . " where referral = :referral " . $condition . $orderby . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array(':referral' => $referral['referral']));

    foreach ($alluser as &$user) {

        //及时联系
        if ($user['isout'] == 0 || $user['isout'] == 1 || $user['isout'] == 2) {
            $note = pdo_fetch("select * from " . tablename('tg_agent_users_note') . " where uid = '{$user['uid']}' order by addtime DESC ");

            if (!empty($note['addtime']) && $note['addtime'] < TIMESTAMP - 3 * 24 * 60 * 60) {
                pdo_update('users', array('lx' => 1), array('uid' => $user['uid']));
            }
            $user['note'] = $note;
        }

        $detail = pdo_fetch("SELECT realname,mobile,qq,wechat,savor,workerid FROM " . tablename('users_profile') . " WHERE uid = :uid  ", array(':uid' => $user['uid']));

        $user['realname'] = $detail['realname'];
        $user['mobile'] = $detail['mobile'];
        $user['qq'] = $detail['qq'];
        $user['wechat'] = $detail['wechat'];
        $user['workerid'] = $detail['workerid'];
        $user['savor'] = unserialize($detail['savor']);

        $agent_name = pdo_fetch("SELECT name,uniacid FROM " . tablename('account_wechats') . " WHERE referral = :referral  ", array(':referral' => $user['referral']));
        $user['agent_name'] = $agent_name['name'];

        $uniacid = pdo_fetch("SELECT uniacid FROM " . tablename('uni_account_users') . " WHERE uid = :uid  ", array(':uid' => $user['uid']));
        if (empty($uniacid['uniacid'])) {
            $user['gzname'] = "未接入公众号";
        } else {
            $gzname = pdo_fetch("SELECT name,endtime,ordernum,vip,is_merchant,is_applet,is_https,expire_time FROM "
                . tablename('account_wechats') . " WHERE uniacid =:uniacid  ", array(':uniacid' => $uniacid['uniacid']));
            $user['gzname'] = $gzname['name'];
            $user['endtime'] = $gzname['endtime'];
            $user['ordernum'] = $gzname['ordernum'];
            $user['vip'] = $gzname['vip'];
            $user['is_merchant'] = $gzname['is_merchant'];
            $user['is_applet'] = $gzname['is_applet'];
            $user['is_https'] = $gzname['is_https'];

            $vip = pdo_get('account_vip', array('uniacid' => $uniacid['uniacid']));
            if ($vip) {
                if ($vip['applet'] == 1) {
                    $user['applet'] = $vip['applet'];
                    $user['applet_time'] = date('Y-m-d H:i', $vip['applet_time']);
                }
                if ($vip['app'] == 1) {
                    $user['app'] = $vip['app'];
                    $user['app_time'] = date('Y-m-d H:i', $vip['app_time']);
                }
                if ($vip['art'] == 1) {
                    $user['art'] = $vip['art'];
                    $user['art_time'] = date('Y-m-d H:i', $vip['art_time']);
                }
                if ($vip['erp'] == 1) {
                    $user['erp'] = $vip['erp'];
                    $user['erp_time'] = date('Y-m-d H:i', $vip['erp_time']);
                }
                if ($vip['offline'] == 1) {
                    $user['offline'] = $vip['offline'];
                    $user['offline_time'] = date('Y-m-d H:i', $vip['offline_time']);
                }
            }

            if (!empty($gzname['expire_time'])) {
                $user['expire_time'] = date('Y-m-d H:i', $gzname['expire_time']);
            }

//            及时续费
            if ($status == 3 || $status == 4) {
                //包年客户时间1个月后到期放入及时续费
                if ($user['vip'] == 1 && $user['endtime'] < TIMESTAMP - 30 * 24 * 60 * 60) {
                    pdo_update('users', array('lx' => 2), array('uid' => $user['uid']));
                }
                //订单套餐不足100单放入及时续费
                if ($user['ordernum'] < 100 && $user['ordernum'] > 0) {
                    pdo_update('users', array('lx' => 2), array('uid' => $user['uid']));
                }
            }

            $log = pdo_fetchall("SELECT FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,refund_cash FROM " . tablename('tg_distributor_cash') . " WHERE parent_uniacid = :parent_uniacid and uniacid =:uniacid  ", array(':parent_uniacid' => $agent_name['uniacid'], ':uniacid' => $uniacid['uniacid']));
            foreach ($log as &$vs) {
                $user['sum_cash'] += $vs['cash'];
                $user['sum_refund_cash'] += $vs['refund_cash'];
            }

            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_distributor_cash') . " WHERE parent_uniacid = :parent_uniacid and uniacid =:uniacid  ", array(':parent_uniacid' => $agent_name['uniacid'], ':uniacid' => $uniacid['uniacid']));
            $lasttime = pdo_fetch("SELECT FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime FROM " . tablename('tg_distributor_cash') . " WHERE parent_uniacid = :parent_uniacid and uniacid =:uniacid  order by ptime desc", array(':parent_uniacid' => $agent_name['uniacid'], ':uniacid' => $uniacid['uniacid']));

            $user['total'] = $total;
            $user['lasttime'] = $lasttime['ptime'];
            $user['uniacid'] = $uniacid['uniacid'];
            $buy = pdo_fetchall("select * from " . tablename('account_wechats_merchant') . " where uniacid = '{$user['uniacid']}'");
//            if (!empty($user['lastvisit'])) {
//                $user['lastvisit'] = date('Y-m-d', $user['lastvisit']) . "<br/>" . date('H:i:s', $user['lastvisit']);
//            }

        }
        if (!empty($user['endtime'])) {

//            if ($user['endtime'] < TIMESTAMP) {
//                pdo_update('users', array('active' => 0), array('uid' => $user['uid']));
//            }
            $user['endtime'] = date('Y-m-d H:i', $user['endtime']);
        }
//        if ($user['joindate'] > time() - 3 * 24 * 60 * 60){
//            $user['endtime'] = date('Y-m-d' , $user['joindate'] + 3 * 24 * 60 * 60);
//            pdo_update('users' , array('isout' => 6 , 'active' => 1) , array('uid' => $user['uid']));
//        }
        $user['joindate'] = date('Y-m-d H:i', $user['joindate']);
        if ($user['lx'] != 0) {
            $boom = 1;
        }
    }
    $alluser2 = pdo_fetchall("select uid from " . tablename('users') . " where referral = :referral " . $condition . " order by uid desc ", array(':referral' => $referral['referral']));

    $total = count($alluser2);
    $pager = pagination($total, $pindex, $psize);

}

if ($op == 'det') {
    $uniacid = $_GPC['uniacid'];

    $log = pdo_fetchall("SELECT orderno,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,discount,refund_cash FROM " . tablename('tg_distributor_cash') . " WHERE uniacid =:uniacid  order by ptime desc ", array(':uniacid' => $uniacid));
    foreach ($log as &$vs) {
        $orderno = pdo_fetch("SELECT functionid FROM " . tablename('tg_function_order') . " WHERE orderno = :orderno  ", array(':orderno' => $vs['orderno']));
        $ordername = pdo_fetch("SELECT name,type FROM " . tablename('tg_function') . " WHERE id = :functionid  ", array(':functionid' => $orderno['functionid']));

        $vs['discount'] = $vs['discount'] * 100;
        if ($ordername['type'] == "3") {
            $vs['name'] = "短信购买";
        } else if ($ordername['type'] == "4") {
            $vs['name'] = "订单购买";
        } else {
            $vs['name'] = $ordername['name'];
        }

    }

    $buy = pdo_fetchall("select * from " . tablename('account_wechats_merchant') . " where uniacid = '{$uniacid}'");
    foreach ($buy as &$value) {
        $value['smsnum'] = $value['smsnum'] - $value['old_smsnum'];
        $value['merchant_num'] = $value['merchant_num'] - $value['old_merchant_num'];
        $value['day'] = $value['month'] * 30 + $value['day'];
    }

    die(json_encode(array('log' => $log, 'buy' => $buy)));
}
if ($op == 'sendstatus') {
    die(json_encode($result));
}
if ($op == 'show_status') {
    $uid = $_GPC['uid'];
    $isout = pdo_update("users", array('isout' => -1), array('uid' => $uid));
    die(json_encode($isout));
}
if ($op == 'addstatus') {
    $uid = $_GPC['uid'];
    $note = $_GPC['note'];
    $data['uid'] = $uid;
    $data['note'] = $note;
    $data['addtime'] = TIMESTAMP;

    $storage = pdo_insert('tg_agent_users_note', $data);

    if (empty($storage)) {
        $result['message'] = "添加失败";
    } else {
        $result['message'] = "添加成功";
        pdo_update('users', array('lx' => 0, 'lastvisit' => TIMESTAMP), array('uid' => $uid));
    }
    die(json_encode($result));
}
if ($op == 'status_list') {
    $uid = $_GPC['uid'];

    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid));

    die(json_encode($status_list));
}
if ($op == 'detstatus') {
    $id = $_GPC['id'];

    $lists = pdo_fetch("select id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note from" . tablename('tg_agent_users_note') . "where id = :id order by addtime desc ", array(':id' => $id));
    $uid = pdo_fetch("select uid from" . tablename('tg_agent_users_note') . "where id = :id ", array(':id' => $id));
    $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $uid['uid']));
    $lists['name'] = $name['username'];
    die(json_encode($lists));
}
if ($op == 'update_status') {
    $id = $_GPC['id'];
    $note = $_GPC['note'];

    $updete = pdo_update("tg_agent_users_note", array('note' => $note), array('id' => $id));

    $uid = pdo_fetch("select uid from" . tablename('tg_agent_users_note') . "where id = :id ", array(':id' => $id));
    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid['uid']));
    $result['list'] = $status_list;

    $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $uid['uid']));
    $result['name'] = $name['username'];
    if (empty($updete)) {

        $result['message'] = "修改成功";
    } else {
        $result['message'] = "修改失败";
    }
    die(json_encode($result));
}
if ($op == 'detail_status') {
    $id = $_GPC['id'];

    $uid = pdo_fetch("select uid from" . tablename('tg_agent_users_note') . "where id = :id ", array(':id' => $id));
    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid['uid']));
    $result['list'] = $status_list;

    $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $uid['uid']));
    $result['name'] = $name['username'];
    if (empty($updete)) {

        $result['message'] = "撤销成功";
    } else {
        $result['message'] = "撤销失败";
    }
    die(json_encode($result));
}
if ($op == 'delete_status') {
    $id = $_GPC['id'];
    $uid = pdo_fetch("select uid from" . tablename('tg_agent_users_note') . "where id = :id ", array(':id' => $id));

    $delete = pdo_query("delete from " . tablename('tg_agent_users_note') . " where id= '{$id}'");

    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid['uid']));
    $result['list'] = $status_list;
    $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $uid['uid']));
    $result['name'] = $name['username'];
    if (empty($delete)) {
        $result['message'] = "修改成功";
    } else {
        $result['message'] = "修改失败";
    }
    die(json_encode($result));
}
if ($op == 'change_status') {
    $uid = $_GPC['uid'];
    $status = intval($_GPC['status']);
    pdo_update('users', array('isout' => intval($_GPC['status'])), array('uid' => $uid));
    //pdo_update("users",array('isout'=>$status),array('uid'=>$uid));
    //message($isout);
    //die(1);
    echo $status;
    exit;
}
//批量更改属性
if ($op == 'change_statuss') {
    $uid = $_GPC['uid'];
    $status = intval($_GPC['status']);
    $array = explode(',', $uid);
    $i = 0;
    if ($status == 0) {
        foreach ($array as $value) {
            $res = pdo_update('users', array('isout' => $status, 'lx' => 0, 'referral' => ''), array('uid' => $value));
            if ($res) {
                $i++;
            }
        }
    } else {
        foreach ($array as $value) {
            $res = pdo_update('users', array('isout' => $status, 'lx' => 0), array('uid' => $value));
            if ($res) {
                $i++;
            }
        }
    }

    die(json_encode($i));
    exit;
}
if ($op == 'out') {
    $uid = $_GPC['uid'];
    $isout = pdo_update("users", array('isout' => -1), array('uid' => $uid));
    die(json_encode($isout));
}
if ($op == 'reout') {
    $uid = $_GPC['uid'];
    $isout = pdo_update("users", array('isout' => 0), array('uid' => $uid));
    die(json_encode($isout));
}
if ($op == 'show_referral') {
    $uid = $_GPC['uid'];
    $rshow = pdo_fetch("select uid,referral,username from" . tablename('users') . "where uid = :uid ", array(':uid' => $uid));

    die(json_encode($rshow));

}
if ($op == 'update_referral') {
    $uid = $_GPC['uid'];
    $referral = $_GPC['referral'];

    $updete = pdo_update("users", array('referral' => $referral), array('uid' => $uid));
    echo "<script>location.href='" . web_url('agent/agent_users_admin', array('page' => $_GPC['page'])) . "';</script>";
    exit();

}
//批量更改从属
if ($op == 'update_referrals') {
    $uid = $_GPC['uid'];
    $array = explode(',', $uid);
    $referral = $_GPC['referral'];
    $i = 0;
    foreach ($array as $value) {
        $res = pdo_update("users", array('referral' => $referral, 'lx' => 0, 'lastvisit' => TIMESTAMP), array('uid' => $value));
        if ($res) {
            $i++;
        }
    }
    die(json_encode($i));

}

if ($op == 'agent_detail') {
    $agent = pdo_get('tg_agents', array('uniacid' => $_W['uniacid'], 'status' => 1));
    if ($agent) {
        $status = 1;
        $data = $agent;
        if (intval($agent['public_num']) - intval($agent['public_used_num']) == 0
            && intval($agent['applet_num']) - intval($agent['applet_used_num']) == 0
            && intval($agent['app_num']) - intval($agent['app_used_num']) == 0
            && intval($agent['merchant_num']) - intval($agent['merchant_used_num']) == 0
            && intval($agent['art_num']) - intval($agent['art_used_num']) == 0
            && intval($agent['erp_num']) - intval($agent['erp_used_num']) == 0
            && intval($agent['offline_num']) - intval($agent['offline_used_num']) == 0) {
            $status = 0;
            $data = '已无可使用VIP套数';
        }
    } else {
        $status = -1;
        $data = '非常抱歉！您还不是代理商或已被管理员禁用';
    }
    die(json_encode(array('status' => $status, 'data' => $data)));
}

if ($op == 'defined_vips') {

    $uniacid = intval($_GPC['uniacid']);
    $type = $_GPC['type'];
    $day = 365;
    $ordernum = 0;
    $state = intval($_GPC['state']);
    $agent = pdo_get('tg_agents', array('uniacid' => $_W['uniacid'], 'status' => 1));
    $uid = pdo_fetch("SELECT uid FROM " . tablename('uni_account_users') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));

    $defined_data = pdo_fetch("SELECT name,endtime,ordernum,vip,expire_time,is_merchant FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));

    if ($ordernum) {
        $defined_ordernum = $defined_data['ordernum'] + $ordernum;
        pdo_update('users', array('isout' => 3), array('uid' => $uid));
        $status = pdo_update('account_wechats', array('endtime' => (TIMESTAMP + 20 * 365 * 24 * 60 * 60), 'vip' => 2, 'ordernum' => $defined_ordernum), array('uniacid' => $uniacid));
        if ($status) {
            $message = '开通成功';
        } else {
            $message = '开通失败';
        }
    } else {

        $now_time = time();
        $vip = pdo_get('account_vip', array('uniacid' => $uniacid));
        if ($vip) {
            $vip_id = $vip['id'];
        } else {
            $vip_id = 0;
        }
        foreach ($type as $item) {

            if ($item == 1) {
                if (empty($defined_data['endtime']) || $now_time >= $defined_data['endtime'] || $defined_data['vip'] != 1) {
                    $overdata_endtime = strtotime("+$day day");
                } else {
                    $data_endtime = date('Y-m-d H:i:s', $defined_data['endtime']);
                    $overdata_endtime = strtotime("$data_endtime +$day day");
                }
                if ($state == 1 && intval($agent['public_num']) - intval($agent['public_used_num']) > 0) {
                    $status = pdo_update('account_wechats', array('endtime' => $overdata_endtime, 'vip' => 1), array('uniacid' => $uniacid));
                    if ($status) {
                        pdo_update('tg_agents', array('public_num' => $agent['public_num'], 'public_used_num' => $agent['public_used_num'] + 1), array('id' => $agent['id']));
                    }
                } else if ($state == 2) {
                    $status = pdo_update('account_wechats', array('endtime' => TIMESTAMP, 'vip' => 0), array('uniacid' => $uniacid));
                }

            }
            if ($item == 2) {
                if (intval($vip['applet_time']) <= $now_time) {
                    $overdata_endtime = strtotime("+$day day");
                } else {
                    $data_endtime = date('Y-m-d H:i:s', $vip['applet_time']);
                    $overdata_endtime = strtotime("$data_endtime +$day day");
                }
                if ($state == 1 && intval($agent['applet_num']) - intval($agent['applet_used_num']) > 0) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('applet_time' => $overdata_endtime, 'applet' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('applet_time' => $overdata_endtime, 'applet' => 1, 'uniacid' => $uniacid));
                    }
                    if ($status) {
                        pdo_update('tg_agents', array('applet_num' => $agent['applet_num'], 'applet_used_num' => $agent['applet_used_num'] + 1), array('id' => $agent['id']));
                    }
                } else if ($state == 2) {
                    $status = pdo_update('account_vip', array('applet_time' => TIMESTAMP, 'applet' => 0), array('uniacid' => $uniacid));
                }
            }
            if ($item == 3) {
                if ($now_time >= $defined_data['expire_time']) {
                    $overdata_endtime = strtotime("+$day day");
                } else {
                    $data_endtime = date('Y-m-d H:i:s', $defined_data['expire_time']);
                    $overdata_endtime = strtotime("$data_endtime +$day day");
                }
                if ($state == 1 && intval($agent['merchant_num']) - intval($agent['merchant_used_num']) > 0) {
                    $status = pdo_update('account_wechats', array('expire_time' => $overdata_endtime, 'is_merchant' => 1), array('uniacid' => $uniacid));
                    if ($status) {
                        pdo_update('tg_agents', array('merchant_num' => $agent['merchant_num'], 'merchant_used_num' => $agent['merchant_used_num'] + 1), array('id' => $agent['id']));
                    }
                } else if ($state == 2) {
                    $status = pdo_update('account_wechats', array('expire_time' => TIMESTAMP, 'is_merchant' => 0), array('uniacid' => $uniacid));
                }
            }
            if ($item == 4) {
                if (intval($vip['app_time']) <= $now_time) {
                    $overdata_endtime = strtotime("+$day day");
                } else {
                    $data_endtime = date('Y-m-d H:i:s', $vip['app_time']);
                    $overdata_endtime = strtotime("$data_endtime +$day day");
                }
                if ($state == 1 && intval($agent['app_num']) - intval($agent['app_used_num']) > 0) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('app_time' => $overdata_endtime, 'app' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('app_time' => $overdata_endtime, 'app' => 1, 'uniacid' => $uniacid));
                    }
                    if ($status) {
                        pdo_update('tg_agents', array('app_num' => $agent['app_num'], 'app_used_num' => $agent['app_used_num'] + 1), array('id' => $agent['id']));
                    }
                } else if ($state == 2) {
                    $status = pdo_update('account_vip', array('app_time' => TIMESTAMP, 'app' => 0), array('uniacid' => $uniacid));
                }

            }
            if ($item == 5) {
                if (intval($vip['art_time']) <= $now_time) {
                    $overdata_endtime = strtotime("+$day day");
                } else {
                    $data_endtime = date('Y-m-d H:i:s', $vip['art_time']);
                    $overdata_endtime = strtotime("$data_endtime +$day day");
                }
                if ($state == 1 && intval($agent['art_num']) - intval($agent['art_used_num']) > 0) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('art_time' => $overdata_endtime, 'art' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('art_time' => $overdata_endtime, 'art' => 1, 'uniacid' => $uniacid));
                    }
                    if ($status) {
                        pdo_update('tg_agents', array('art_num' => $agent['art_num'], 'art_used_num' => $agent['art_used_num'] + 1), array('id' => $agent['id']));
                    }
                } else if ($state == 2) {
                    $status = pdo_update('account_vip', array('art_time' => TIMESTAMP, 'art' => 0), array('uniacid' => $uniacid));
                }
            }
            if ($item == 6) {
                if (intval($vip['erp_time']) <= $now_time) {
                    $overdata_endtime = strtotime("+$day day");
                } else {
                    $data_endtime = date('Y-m-d H:i:s', $vip['erp_time']);
                    $overdata_endtime = strtotime("$data_endtime +$day day");
                }
                if ($state == 1 && intval($agent['erp_num']) - intval($agent['erp_used_num']) > 0) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('erp_time' => $overdata_endtime, 'erp' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('erp_time' => $overdata_endtime, 'erp' => 1, 'uniacid' => $uniacid));
                    }
                    if ($status) {
                        pdo_update('tg_agents', array('erp_num' => $agent['erp_num'], 'erp_used_num' => $agent['erp_used_num'] + 1), array('id' => $agent['id']));
                    }
                } else if ($state == 2) {
                    $status = pdo_update('account_vip', array('erp_time' => TIMESTAMP, 'erp' => 0), array('uniacid' => $uniacid));
                }
            }
            if ($item == 7) {
                if (intval($vip['offline_time']) <= $now_time) {
                    $overdata_endtime = strtotime("+$day day");
                } else {
                    $data_endtime = date('Y-m-d H:i:s', $vip['offline_time']);
                    $overdata_endtime = strtotime("$data_endtime +$day day");
                }
                if ($state == 1 && intval($agent['offline_num']) - intval($agent['offline_used_num']) > 0) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('offline_time' => $overdata_endtime, 'offline' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('offline_time' => $overdata_endtime, 'offline' => 1, 'uniacid' => $uniacid));
                    }
                    if ($status) {
                        pdo_update('tg_agents', array('offline_num' => $agent['offline_num'], 'offline_used_num' => $agent['offline_used_num'] + 1), array('id' => $agent['id']));
                    }
                } else if ($state == 2) {
                    $status = pdo_update('account_vip', array('offline_time' => TIMESTAMP, 'offline' => 0), array('uniacid' => $uniacid));
                }
            }
            pdo_update('users', array('isout' => 4), array('uid' => $uid));
        }

        if ($state == 1) {
            if ($status) {
                $message = '开通成功';
            } else {
                $message = '开通失败';
            }
        } else if ($state == 2) {
            if ($status) {
                $message = '到期成功';
            } else {
                $message = '到期失败';
            }
        } else {
            $message = '传入参数错误！';
        }
    }
    $record['uid'] = $_W['uid'];
    $record['agents_id'] = $agent['id'];
    $record['username'] = $_W['username'];
    $record['uniacid'] = $_W['uniacid'];
    $record['open_uniacid'] = $uniacid;
    $record['open_name'] = $defined_data['name'];
    $record['type'] = serialize($type);
    $record['state'] = $state;
    $record['day'] = $day;
    $record['ordernum'] = $ordernum;
    $record['createtime'] = TIMESTAMP;
    pdo_insert('account_vip_record', $record);

    $updete = pdo_update("users", array('referral_status' => 1, 'lx' => 0), array('uid' => $uid));
    die(json_encode(array('status' => $status, 'message' => $message)));
}


include wl_template('agent/proxy_user');
exit();
