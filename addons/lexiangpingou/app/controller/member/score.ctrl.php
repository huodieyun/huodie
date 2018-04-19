<?php
wl_load()->model('member');
empty($_GPC['op']) ? $op = 'index' : $op = $_GPC['op'];

// 基本设置
if($op == 'index') {
    $pagetitle = '我的积分';
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$_W['openid']}' and uniacid = '{$_W['uniacid']}'");
    // 未结算
    $not = 0;
    $and = " AND (status=1 OR status=2 OR status=8 OR status=3)";
    $where = ' WHERE `uniacid` = '.$_W['uniacid'].' AND `openid` = "'.$_W['openid'].'" AND is_score = 1 AND comtype = 0'. $and;
    $notList = pdo_fetchall("SELECT score FROM ".tablename('tg_order') . $where);
    foreach ($notList as $i) {
        $not += $i['score'];
    }
    empty($_GPC['tab']) ? $tab = 'over' : $tab = $_GPC['tab'];
    $ajaxurl = app_url('member/score/list', array('tab' => $tab));
    include wl_template('member/score/index');
}

if ($op == 'list') {
    $page = max(1, intval($_GPC['page']));
    $size = max(1, intval($_GPC['pagesize']));
    // 已结算积分
    if ($_GPC['tab'] == 'over') {
        $where = ' WHERE `uniacid` = '.$_W['uniacid'].' AND `openid` = "'.$_W['openid'].'"';
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('tg_score_record') . $where);
        $list = pdo_fetchall("SELECT * FROM ".tablename('tg_score_record') . $where . ' ORDER BY id DESC LIMIT ' .($page - 1) * $size.','.$size);
    } else {
    // 未结算
        $and = " AND (status=1 OR status=2 OR status=8 OR status=3)";
        $where = ' WHERE `uniacid` = '.$_W['uniacid'].' AND `openid` = "'.$_W['openid'].'" AND is_score = 1 AND comtype = 0'. $and;
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('tg_order') . $where);
        $list = pdo_fetchall("SELECT * FROM ".tablename('tg_order') . $where . ' ORDER BY id DESC LIMIT ' .($page - 1) * $size.','.$size);
    }
    foreach ($list as $key => $value) {
        if (is_null($list[$key]['created_at'])) {
            $list[$key]['created_at'] = "未结算";
        } else {
            $list[$key]['created_at'] = date("Y-m-d H:i", $list[$key]['created_at']);
        }
        // 0待付款 1已付款 2待收货 8 待发货 3已签收 4已退款 7已退款(现在在用) 5没用上 6部分退款 10待退款 9 已取消
        switch ($list[$key]['status']) {
            case '0':
                $list[$key]['status'] = '待付款';
                break;
            case '1':
                $list[$key]['status'] = '已付款';
                break;
            case '2':
                $list[$key]['status'] = '待收货';
                break;
            case '8':
                $list[$key]['status'] = '待发货';
                break;
            case '3':
                $list[$key]['status'] = '已签收';
                break;
            case '4':
                $list[$key]['status'] = '已退款';
                break;
            case '7':
                $list[$key]['status'] = '已退款';
                break;
            case '5':
                $list[$key]['status'] = '没用上';
                break;
            case '6':
                $list[$key]['status'] = '部分退款';
                break;
            case '10':
                $list[$key]['status'] = '待退款';
                break;
            case '9':
                $list[$key]['status'] = '已取消';
                break;

            default:
                $list[$key]['status'] = '其他';
                break;
        }
    }
    die(json_encode(array('list' => $list , 'total' => $total)));
}

if($op == 'market') {
    $pagetitle = '积分商城';
    $params = array(':time' => TIMESTAMP, ':enable' => 1);
    $where = " WHERE uniacid = {$_W['uniacid']} AND start_time < :time AND end_time > :time AND enable = :enable AND score > 0";

    $list = pdo_fetchall("select * from".tablename('tg_coupon_template')." $where " ,$params);
    foreach ($list as $key => $value) {
        $list[$key]['score'] = floatval($value['score']);
    }
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$_W['openid']}' and uniacid = '{$_W['uniacid']}'");

    // $exchange_params = array(
    //     'openid' => $_W['openid'],
    //     'uniacid' => $_W['uniacid']
    // );
    $where = ' openid = "'.$_W['openid']. '" AND uniacid = '.$_W['uniacid'];
    // $exchanges = pdo_getall('tg_score_exchange', $exchange_params, array() , '' , 'created_at DESC', array());
    $exchanges = pdo_fetchall('SELECT * FROM '.tablename('tg_score_exchange').' WHERE '.$where.' ORDER BY created_at DESC');
    // pdo_getall('users', array('status' => 1), array() , '' , array('uid','groupid') , array(1,10));
    foreach ($exchanges as $key => $value) {
        $exchanges[$key]['score'] = floatval($value['score']);
    }
    include wl_template('member/score/market');
}
if ($op == 'exchange') {
    $member = getMember($_W['openid']);
    $coupon = pdo_get('tg_coupon_template', array('id' => $_GPC['id']));
    if ($coupon['score'] > 0 && $member['score_balance'] >= $coupon['score']) {
        // 减到优惠卷库存
        pdo_update('tg_coupon_template', array('quantity_used' => $coupon['quantity_used'] + 1), array('uniacid' => $_W['uniacid']));
        // 领取优惠券
        if ($coupon['is_random'] == 2) { // 优惠金额更改
            $coupon['value'] = mt_rand($coupon['value'], $coupon['value_to']);
        }
        $coupon_data = array(
            'coupon_template_id' => $coupon['id'],
            'name' => $coupon['name'],
            'cash' => $coupon['value'],
            'is_at_least' => $coupon['is_at_least'],
            'at_least' => $coupon['at_least'],
            'description' => $coupon['description'],
            'start_time' => $coupon['start_time'],
            'end_time' => $coupon['end_time'],
            'uid' => $coupon['uid'],
            'createtime' => TIMESTAMP,
            'use_time' => 0,
            'openid' => $_W['openid'],
            'uniacid' => $_W['uniacid'],
            'credits' => $_W['score'],
        );
        pdo_insert('tg_coupon', $coupon_data);
        $couponid = pdo_insertid();
        // 插入积分兑换记录
        $exchange_data = array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid'],
            'orderno' => date('YmdHis') . rand(10, 99),
            'score' => $coupon['score'],
            'couponid' => $couponid,
            'name' => $coupon['name'],
            'cash' => $coupon['value'],
            'end_time' => $coupon['end_time'],
            'created_at' => TIMESTAMP,
        );
        pdo_insert('tg_score_exchange', $exchange_data);
        // 用户剩余积分数
        $member_data = array(
            'score_balance' => $member['score_balance'] - $coupon['score'],
        );
        pdo_update('tg_member', $member_data, array('openid' => $_W['openid'], 'uniacid' => $_W['uniacid']));

        die(json_encode(array('status' => 1 , 'msg' => '兑换成功！')));
    } else {
        die(json_encode(array('status' => 0 , 'msg' => '不可兑换')));
    }
}
exit();
