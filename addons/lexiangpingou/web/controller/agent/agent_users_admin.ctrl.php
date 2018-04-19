<?php
// 引入供应商类

defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

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
    $data['selluid'] = $_W['uid'];
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
    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note,selluid FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid));
    foreach ($status_list as $key => $value) {
        $sell = pdo_get('users', array('uid' => $value['selluid']), array('username'));
        $status_list[$key]['sell'] = $sell['username'] ? $sell['username'] : '无';
    }

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
if ($op == 'vip') {
    $uniacid = $_GPC['uniacid'];
    $endtime = pdo_fetch("SELECT endtime FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));
    $uid = pdo_fetch("SELECT uid FROM " . tablename('uni_account_users') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));

    $now_time = time();
    if (empty($endtime['endtime']) || $now_time >= $endtime['endtime']) {
        $newdata['endtime'] = strtotime("+1 year");
    } else {

        $new_endtime = date('Y', $endtime['endtime']) + 1 . '-' . date('m-d H:i:s', $endtime['endtime']);//一年后日期
        $newdata['endtime'] = strtotime($new_endtime);

    }
    $op_vip = pdo_query("UPDATE " . tablename('account_wechats') . " SET vip = :vip, endtime = :endtime WHERE uniacid = :uniacid", array(':vip' => 1, ':endtime' => $newdata['endtime'], ':uniacid' => $uniacid));
    $updete = pdo_update("users", array('referral_status' => 1, 'isout' => 4, 'lx' => 0), array('uid' => $uid));
    die(json_encode($op_vip));

}
if ($op == 'defined_vip') {

    $uniacid = $_GPC['uniacid'];
    $month = $_GPC['month'];
    $day = $_GPC['day'];
    $ordenum = $_GPC['ordernum'];
    $uid = pdo_fetch("SELECT uid FROM " . tablename('uni_account_users') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));
    if (empty($month)) {
        $month = 0;
    }
    if (empty($day)) {
        $day = 0;
    }
    $defined_data = pdo_fetch("SELECT endtime,ordernum FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));

    $now_time = time();
    if (empty($defined_data['endtime']) || $now_time >= $defined_data['endtime']) {
        $overdata_endtime = strtotime("+$day day +$month month");
    } else {
        $data_endtime = date('Y-m-d H:i:s', $defined_data['endtime']);
        $overdata_endtime = strtotime("$data_endtime +$day day +$month month");
    }
    $defined_ordernum = $defined_data['ordernum'] + $ordenum;

    $update_endtime = pdo_update('account_wechats', array('endtime' => $overdata_endtime, 'ordernum' => $defined_ordernum), array('uniacid' => $uniacid));
    if (!empty($month) || !empty($day)) {
        $update_vip = pdo_update('account_wechats', array('vip' => 1), array('uniacid' => $uniacid));
        pdo_update('users', array('active' => 1, 'isout' => 4), array('uid' => $uid));
    }
    if (!empty($ordenum)) {
        pdo_update('users', array('isout' => 3), array('uid' => $uid));
    }
    $updete = pdo_update("users", array('referral_status' => 1, 'lx' => 0), array('uid' => $uid));
    die(json_encode($update_endtime));
}
if ($op == 'display') {
    $status = intval($_GPC['status']);
    $lx = intval($_GPC['lx']);

    if ($status != 5) {
        $condition = " and isout = {$status} ";
    }

    if ($lx > 0) {
        $condition = " and lx = {$lx} ";
    }
    if ($status == 6) {
        $condition = " and ( isout = {$status} or active = 1 )";
    }
    $referral = pdo_fetch("select referral from " . tablename('account_wechats') . " where uniacid = :uniacid ", array(':uniacid' => $_W['uniacid']));

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;

    $allagent = pdo_fetchall("select referral,name from " . tablename('account_wechats') . " where referral <> '0' and referral <>'' ", array());

    $type = $_GPC['type'];
    if ($type == 1) {
        $time = $_GPC['time'];
        $join = $_GPC['join'];
    }
    if (!empty($time) || !empty($_GPC['keyword_gzname'])) {
        $condition .= " AND uid in (select uid from " . tablename('uni_account_users') . " where uniacid in (select uniacid from " . tablename('account_wechats') . " where 1 ";
    }

    if (!empty($time)) {
//        $starttime = TIMESTAMP;
//        if ($time == -1){
//            $condition .= " AND endtime < '{$starttime}' ";
//        }else{
//            $endtime = TIMESTAMP + $time * 24 * 60 * 60;
        $starttime = strtotime($time['start']);
        $endtime = strtotime($time['end']);
//        die(json_encode($time));
        $condition .= " AND endtime < '{$endtime}' and endtime > '{$starttime}' ";
//        }
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
//        die(json_encode($time));
        $condition .= " AND joindate < '{$endtime}' and joindate > '{$starttime}' ";
    }
//    if (!empty($_GPC['keyword_agent'])) {
//        $condition .= " AND referral in (select referral from " . tablename('account_wechats') . " where name LIKE '%{$_GPC['keyword_agent']}%') ";
//    }

//    if (!empty($_GPC['keyword_mobile'])) {
//        $condition .= " AND uid in (select uid from " . tablename('users_profile') . " where mobile LIKE '%{$_GPC['keyword_mobile']}%') ";
//    }

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
    $alluser = pdo_fetchall("select uid,username,joindate,referral,active,lx,FROM_UNIXTIME(lastvisit,'%Y-%m-%d') as lastvisit from " . tablename('users')
        . " where referral <> '0' and username <> 'admin' " . $condition . $orderby . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array());
//    die(json_encode($condition));
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
    $alluser2 = pdo_fetchall("select uid from " . tablename('users') . " where  referral != '0' " . $condition . " order by uid desc ", array());
    $total = count($alluser2);
    $pager = pagination($total, $pindex, $psize);

    include wl_template('agent/agent_users_admin');
}


if ($op == 'defined_vips') {

    $uniacid = intval($_GPC['uniacid']);
    $type = $_GPC['type'];
    $day = intval($_GPC['day']);
    $ordernum = intval($_GPC['ordernum']);
    $state = intval($_GPC['state']);

    $uid = pdo_fetch("SELECT uid FROM " . tablename('uni_account_users') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));

    $defined_data = pdo_fetch("SELECT name,endtime,ordernum,vip,expire_time,is_merchant FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid  ", array(':uniacid' => $uniacid));

    if ($ordernum) {
        $defined_ordernum = $defined_data['ordernum'] + $ordernum;
        pdo_update('users', array('isout' => 3), array('uid' => $uid));
        $status = pdo_update('account_wechats', array('endtime' => (TIMESTAMP + 20 * 365 * 24 * 60 * 60), 'vip' => 0, 'ordernum' => $defined_ordernum), array('uniacid' => $uniacid));
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
                if ($state == 1) {
                    $status = pdo_update('account_wechats', array('endtime' => $overdata_endtime, 'vip' => 1), array('uniacid' => $uniacid));
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
                if ($state == 1) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('applet_time' => $overdata_endtime, 'applet' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('applet_time' => $overdata_endtime, 'applet' => 1, 'uniacid' => $uniacid));
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
                if ($state == 1) {
                    $status = pdo_update('account_wechats', array('expire_time' => $overdata_endtime, 'is_merchant' => 1), array('uniacid' => $uniacid));
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
                if ($state == 1) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('app_time' => $overdata_endtime, 'app' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('app_time' => $overdata_endtime, 'app' => 1, 'uniacid' => $uniacid));
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
                if ($state == 1) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('art_time' => $overdata_endtime, 'art' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('art_time' => $overdata_endtime, 'art' => 1, 'uniacid' => $uniacid));
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
                if ($state == 1) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('erp_time' => $overdata_endtime, 'erp' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('erp_time' => $overdata_endtime, 'erp' => 1, 'uniacid' => $uniacid));
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
                if ($state == 1) {
                    if ($vip_id) {
                        $status = pdo_update('account_vip', array('offline_time' => $overdata_endtime, 'offline' => 1), array('id' => $vip_id));
                    } else {
                        $vip_id = $status = pdo_insert('account_vip', array('offline_time' => $overdata_endtime, 'offline' => 1, 'uniacid' => $uniacid));
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
    $record['agents_id'] = 0;
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

$province = pdo_getall('erp_area', array('level' => 1));

// 代理商开通页面
if ($op == 'agents') {

    $pindex = max(1, intval($_GPC['page'])); // 当前页
    $psize = 10; // 每页数
    $page = ($pindex - 1) * $psize;
    $com = '';
    $keyword = trim($_GPC['keyword']);
    $time = $_GPC['time'];
    $type = intval($_GPC['type']);
    if ($time && $type == 1) {
        $starttime = strtotime($time['start']);
        $endtime = strtotime($time['end']);
        $com .= " and createtime <= {$endtime} and createtime >= {$starttime} ";
    } else {
        $starttime = TIMESTAMP - 30 * 24 * 60 * 60;
        $endtime = TIMESTAMP;
    }
    if ($keyword) {
        $com .= " and ( name like '%{$keyword}%' or open_name like '%{$keyword}%' ) ";
    }

    $list = pdo_fetchall("select * from " . tablename('tg_agents') . " where status > -1 {$com} order by createtime desc limit {$page} , {$psize} ");
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_agents') . " where status > -1 {$com} ");
    $pager = pagination($total, $pindex, $psize);
    include wl_template('agent/agents');
    die();
}

//购买记录
if ($op == 'add_agent_record') {
    $pindex = max(1, intval($_GPC['page'])); // 当前页
    $psize = 10; // 每页数
    $page = ($pindex - 1) * $psize;
    $com = '';
    $keyword = trim($_GPC['keyword']);
    $time = $_GPC['time'];
    $type = intval($_GPC['type']);
    if ($time && $type == 1) {
        $starttime = strtotime($time['start']);
        $endtime = strtotime($time['end']);
        $com .= " and createtime <= {$endtime} and createtime >= {$starttime} ";
    } else {
        $starttime = TIMESTAMP - 30 * 24 * 60 * 60;
        $endtime = TIMESTAMP;
    }
    if ($keyword) {
        $com .= " and ( agents_id in ( select id from " . tablename('tg_agents') . " where name like '%{$keyword}%' or open_name like '%{$keyword}%' ) or open_name like '%{$keyword}%' ) ";
    }
    $list = pdo_fetchall("select * from " . tablename('tg_agents_records') . " where 1 {$com} order by createtime desc limit {$page} , {$psize} ");
    foreach ($list as &$item) {
        $agent = pdo_get('tg_agents', array('id' => $item['agents_id']));
        $item['name'] = $agent['name'];
        $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
        unset($item);
    }
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_agents_records') . " where 1 {$com} ");
    $pager = pagination($total, $pindex, $psize);
    include wl_template('agent/agents');
    die();
}

//VIP开通记录
if ($op == 'vip_record') {

    $pindex = max(1, intval($_GPC['page'])); // 当前页
    $psize = 10; // 每页数
    $page = ($pindex - 1) * $psize;

    $agent_id = pdo_getcolumn('tg_agents', array('uniacid' => $uniacid), 'id');

    $com = '';
    $keyword = trim($_GPC['keyword']);
    $time = $_GPC['time'];
    $type = intval($_GPC['type']);
    if ($time && $type == 1) {
        $starttime = strtotime($time['start']);
        $endtime = strtotime($time['end']);
        $com .= " and createtime <= {$endtime} and createtime >= {$starttime} ";
    } else {
        $starttime = TIMESTAMP - 30 * 24 * 60 * 60;
        $endtime = TIMESTAMP;
    }
    if ($keyword) {
        $com .= " and ( agents_id in ( select id from "
            . tablename('tg_agent') . " where name like '%{$keyword}%' or open_name like '%{$keyword}%' ) "
            . " or open_name like '%{$keyword}%' or open_uniacid in ( select uniacid from " . tablename('account_wechats') . " where name like '%{$keyword}%' ) ) ";
    }
    $list = pdo_fetchall("select * from " . tablename('account_vip_record') . " where 1 {$com} order by createtime desc limit {$page} , {$psize} ");
    foreach ($list as &$item) {
        if ($item['agents_id'] == 0) {
            $item['name'] = '管理员';
        } else {
            $agent_name = pdo_getcolumn('tg_agents', array('id' => $item['agents_id']), 'name');
            $item['name'] = $agent_name;
        }
        if (!$item['open_name']) {
            $open_name = pdo_getcolumn('account_wechats', array('uniacid' => $item['open_uniacid']), 'name');
            $item['open_name'] = $open_name;
        }
        $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
        $item['type'] = unserialize($item['type']);
        foreach ($item['type'] as $v) {
            if ($v == 1) {
                $item['public'] = 1;
            } else if ($v == 2) {
                $item['applet'] = 1;
            } else if ($v == 3) {
                $item['merchant'] = 1;
            } else if ($v == 4) {
                $item['app'] = 1;
            } else if ($v == 5) {
                $item['art'] = 1;
            } else if ($v == 6) {
                $item['erp'] = 1;
            } else if ($v == 7) {
                $item['offline'] = 1;
            }
        }

        unset($item);
    }
    $total = pdo_fetchcolumn("select count(id) from " . tablename('account_vip_record') . " where 1 {$com} ");
    $pager = pagination($total, $pindex, $psize);
//    die(json_encode($list));
    include wl_template('agent/agents');
    die();

}


if ($op == 'list_agent') {
    $id = intval($_GPC['id']);
    if ($id) {
        $agent = pdo_get('tg_agents', array('id' => $id));
        $status = 1;
        die(json_encode(array('status' => $status, 'data' => $agent)));
    } else {
        $status = 0;
        $message = '传入参数错误！';
        die(json_encode(array('status' => $status, 'message' => $message)));
    }
}

if ($op == 'add_agent') {

    $data = $_GPC['data'];
    $agent = pdo_get('tg_agents', array('uniacid' => $data['uniacid']));
    if ($agent) {
        $agent_id = $agent['id'];
        $dat['agents_id'] = $agent_id;
        $dat['open_name'] = $data['open_name'];
        $dat['public_num'] = $data['public_num'];
        $dat['applet_num'] = $data['applet_num'];
        $dat['app_num'] = $data['app_num'];
        $dat['art_num'] = $data['art_num'];
        $dat['erp_num'] = $data['erp_num'];
        $dat['offline_num'] = $data['offline_num'];
        $dat['merchant_num'] = $data['merchant_num'];
        $dat['createtime'] = TIMESTAMP;
        $status = pdo_insert('tg_agents_records', $dat);
        $update['public_num'] = intval($agent['public_num']) + intval($data['public_num']);
        $update['applet_num'] = intval($agent['applet_num']) + intval($data['applet_num']);
        $update['app_num'] = intval($agent['app_num']) + intval($data['app_num']);
        $update['art_num'] = intval($agent['art_num']) + intval($data['art_num']);
        $update['erp_num'] = intval($agent['erp_num']) + intval($data['erp_num']);
        $update['offline_num'] = intval($agent['offline_num']) + intval($data['offline_num']);
        $update['merchant_num'] = intval($agent['merchant_num']) + intval($data['merchant_num']);
        $status = pdo_update('tg_agents', $update, array('id' => $agent_id));
        if ($status) {
            $message = '新增成功！';
        } else {
            $message = '新增失败！';
        }
//        $status = 0;
//        $message = '温馨提示！此公众号已是代理商';
    } else {

        $referral = pdo_getcolumn('account_wechats' , array('uniacid' => $data['uniacid']) , 'referral');
        if (!$referral) {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $referral = "";
            for ($i = 0; $i < 6; $i++) {
                // 使用 substr 截取$chars中的任意一位字符；
                $referral .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            $referral = (string)$referral . (string)$data['uniacid'];
            pdo_update('account_wechats' , array('referral' => $referral) , array('uniacid' => $data['uniacid']));
        }
        $data['referral'] = $referral;
        $data['createtime'] = TIMESTAMP;
        $data['status'] = 1;
        $status = pdo_insert('tg_agents', $data);
        $agent_id = pdo_insertid();
        $dat['agents_id'] = $agent_id;
        $dat['open_name'] = $data['open_name'];
        $dat['public_num'] = $data['public_num'];
        $dat['applet_num'] = $data['applet_num'];
        $dat['app_num'] = $data['app_num'];
        $dat['art_num'] = $data['art_num'];
        $dat['erp_num'] = $data['erp_num'];
        $dat['offline_num'] = $data['offline_num'];
        $dat['merchant_num'] = $data['merchant_num'];
        $dat['createtime'] = TIMESTAMP;
        $status = pdo_insert('tg_agents_records', $dat);
        if ($status) {
            $message = '新增成功！';
        } else {
            $message = '新增失败！';
        }

    }
    die(json_encode(array('status' => $status, 'message' => $message)));
}

if ($op == 'edit_agent') {

    $id = intval($_GPC['id']);
    $data = $_GPC['data'];

    $status = pdo_update('tg_agents', $data, array('id' => $id));
    if ($status) {
        $message = '修改成功！';
    } else {
        $message = '修改失败！';
    }

    die(json_encode(array('status' => $status, 'message' => $message)));
}

if ($op == 'del_agent') {

    $id = intval($_GPC['id']);

    $status = pdo_update('tg_agents', array('status' => -1), array('id' => $id));
    if ($status) {
        $message = '删除成功！';
    } else {
        $message = '删除失败！';
    }

    die(json_encode(array('status' => $status, 'message' => $message)));
}


// 查询是否含有该公众号
if ($op == 'ajax') {
    $name = trim($_GPC['name']);
    $acc = pdo_fetchall("select name,uniacid from " . tablename('account_wechats') . " where `name` like '%{$name}%' ");
    foreach ($acc as $key => $item) {
        if (pdo_get('tg_agents', array('uniacid' => $item['uniacid']))) {
            unset($acc[$key]);
        }
    }

    $acc = array_merge($acc);
    if ($acc) {
        die(json_encode(array('status' => 1, 'data' => $acc)));
    } else {
        die(json_encode(array('status' => 0, 'message' => '非常抱歉！未查询到相关公众号或该公众号已是代理商')));
    }

}
exit();
