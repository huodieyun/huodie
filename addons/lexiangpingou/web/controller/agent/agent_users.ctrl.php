<?php
defined('IN_IA') or exit('Access Denied');

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if ($op == 'det') {
    $uniacid = $_GPC['uniacid'];

    $log = pdo_fetchall("SELECT orderno,FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,discount,refund_cash FROM " . tablename('tg_distributor_cash') . " WHERE parent_uniacid = :parent_uniacid and uniacid =:uniacid  order by ptime desc ", array(':parent_uniacid' => $_W['uniacid'], ':uniacid' => $uniacid));
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
    foreach ($status_list as &$list) {
        $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $list['selluid']));
        $list['uname'] = $name['username'] ? $name['username'] : '无';
    }
    die(json_encode($status_list));
}
if ($op == 'detstatus') {
    $id = $_GPC['id'];
    $lists = pdo_fetch("select id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note,selluid from" . tablename('tg_agent_users_note') . "where id = :id order by addtime desc ", array(':id' => $id));
    $uid = pdo_fetch("select uid from" . tablename('tg_agent_users_note') . "where id = :id ", array(':id' => $id));
    $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $uid['uid']));
    $lists['name'] = $name['username'];
    $uname = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $lists['selluid']));
    $lists['uname'] = $uname['username'];
    die(json_encode($lists));
}
if ($op == 'update_status') {
    $id = $_GPC['id'];
    $note = $_GPC['note'];

    $updete = pdo_update("tg_agent_users_note", array('note' => $note, 'selluid' => $_W['uid']), array('id' => $id));

    $uid = pdo_fetch("select uid from" . tablename('tg_agent_users_note') . "where id = :id ", array(':id' => $id));
    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note,selluid FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid['uid']));
    foreach ($status_list as &$list) {
        $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $list['selluid']));
        $list['uname'] = $name['username'];
    }
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
    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note,selluid FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid['uid']));
    foreach ($status_list as &$list) {
        $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $list['selluid']));
        $list['uname'] = $name['username'];
    }
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

    $status_list = pdo_fetchall("SELECT id,FROM_UNIXTIME(addtime,'%Y-%m-%d %H:%i') as addtime,note,selluid FROM " . tablename('tg_agent_users_note') . " WHERE uid = :uid order by addtime desc ", array(':uid' => $uid['uid']));
    foreach ($status_list as &$list) {
        $name = pdo_fetch("select username from" . tablename('users') . "where uid = :uid ", array(':uid' => $list['selluid']));
        $list['uname'] = $name['username'];
    }
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
if ($op == 'update_referral') {
    $uid = $_GPC['uid'];
    $referral = $_GPC['referral'];

    $updete = pdo_update("users", array('referral' => $referral), array('uid' => $uid));
    echo "<script>location.href='" . web_url('agent/agent_users', array('page' => $_GPC['page'])) . "';</script>";
    exit();

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
            $res = pdo_update('users', array('isout' => $status, 'lx' => 0, 'lastvisit' => TIMESTAMP), array('uid' => $value));
            if ($res) {
                $i++;
            }
        }
    }

    die(json_encode($i));
    exit;
}

//批量更改从属
if ($op == 'update_referrals') {
    $uid = $_GPC['uid'];
    $array = explode(',', $uid);
    $referral = $_GPC['referral'];
    $i = 0;
    foreach ($array as $value) {
        $res = pdo_update("users", array('referral' => $referral , 'lx' => 0 , 'lastvisit' => TIMESTAMP), array('uid' => $value));
        if ($res){
            $i++;
        }
    }
    die(json_encode($i));

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


//	if (!empty($_GPC['keyword_username'])) {
//		$condition .= " AND username LIKE '%{$_GPC['keyword_username']}%' ";
//	}
//	if (!empty($_GPC['keyword_gzname'])) {
//		$condition .= " AND uid in (select uid from " . tablename('uni_account_users') . " where uniacid in (select uniacid from " . tablename('account_wechats') . " where name LIKE '%{$_GPC['keyword_gzname']}%')) ";
//	}
//	if (!empty($_GPC['keyword_mobile'])) {
//		$condition .= " AND uid in (select uid from " . tablename('users_profile') . " where mobile LIKE '%{$_GPC['keyword_mobile']}%') ";
//	}

    $type = $_GPC['type'];
    if ($type == 1){
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
        . " where referral = :referral " . $condition . $orderby . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array(':referral' => $referral['referral']));
    foreach ($alluser as &$user) {
        //及时联系
        if ($user['isout'] == 0 || $user['isout'] == 1 || $user['isout'] == 2) {
            $note = pdo_fetch("select addtime from " . tablename('tg_agent_users_note') . " where uid = '{$user['uid']}' order by addtime DESC ");
            if (!empty($note['addtime']) && $note['addtime'] < TIMESTAMP - 3 * 24 * 60 * 60) {

                pdo_update('users', array('lx' => 1), array('uid' => $user['uid']));
            }
        }

        $detail = pdo_fetch("SELECT realname,mobile,qq,wechat,savor,workerid FROM " . tablename('users_profile') . " WHERE uid = :uid  ", array(':uid' => $user['uid']));

        $user['realname'] = $detail['realname'];
        $user['mobile'] = $detail['mobile'];
        $user['qq'] = $detail['qq'];
        $user['wechat'] = $detail['wechat'];
        $user['workerid'] = $detail['workerid'];
        $user['savor'] = unserialize($detail['savor']);

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

            $log = pdo_fetchall("SELECT FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime,cash,refund_cash FROM "
                . tablename('tg_distributor_cash') . " WHERE parent_uniacid = :parent_uniacid and uniacid =:uniacid  ", array(':parent_uniacid' => $_W['uniacid'], ':uniacid' => $uniacid['uniacid']));
            foreach ($log as &$vs) {
                $user['sum_cash'] += $vs['cash'];
                $user['sum_refund_cash'] += $vs['refund_cash'];
            }

            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_distributor_cash') . "WHERE parent_uniacid = :parent_uniacid and uniacid =:uniacid  ", array(':parent_uniacid' => $_W['uniacid'], ':uniacid' => $uniacid['uniacid']));
            $lasttime = pdo_fetch("SELECT FROM_UNIXTIME(ptime,'%Y-%m-%d %H:%i') as ptime FROM " . tablename('tg_distributor_cash') . " WHERE parent_uniacid = :parent_uniacid and uniacid =:uniacid  order by ptime desc", array(':parent_uniacid' => $_W['uniacid'], ':uniacid' => $uniacid['uniacid']));

            $user['total'] = $total;
            $user['lasttime'] = $lasttime['ptime'];
            $user['uniacid'] = $uniacid['uniacid'];
            $user['ordernum'] = $gzname['ordernum'];
//			$user['endtime']='--';
            if (!empty($gzname['endtime'])) {
                $user['endtime'] = date('Y-m-d', $gzname['endtime']);
            }
            if ($user['lx'] != 0) {
                $boom = 1;
            }
        }
        $user['joindate'] = date('Y-m-d H:i', $user['joindate']);
    }
    $alluser2 = pdo_fetchall("select uid from " . tablename('users') . " where referral = :referral " . $condition . " order by uid desc ", array(':referral' => $referral['referral']));

    $total = count($alluser2);
    $pager = pagination($total, $pindex, $psize);

    include wl_template('agent/agent_users');
}
exit();
	
