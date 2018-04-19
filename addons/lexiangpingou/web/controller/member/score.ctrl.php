<?php

wl_load()->model('setting');
$setting = setting_get_by_name("score");
empty($_GPC['op']) ? $op = 'base' : $op = $_GPC['op'];

// 基本设置
if($op == 'base') {
    include wl_template('member/socre/base');
}
// 开关（接口）
if ($op == 'ajax') {
    $score_apply = intval($_GPC['score_apply']);
    $member_score_apply = intval($_GPC['member_score_apply']);
    if (empty($setting)) {
        if ($score_apply) {
            $value = array('score_apply' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'score',
                'value' => serialize($value)
            );
            setting_insert($data);
        } elseif ($member_score_apply) {
            $value = array('member_score_apply' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'score',
                'value' => serialize($value)
            );
            setting_insert($data);
        }
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    } else {
        if ($score_apply) {
            $status = intval($setting['score_apply']) == 1 ? 2 : 1;
            $setting['score_apply'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'score', 'uniacid' => $_W['uniacid']));
        } elseif ($member_score_apply) {
            $status = intval($setting['member_score_apply']) == 1 ? 2 : 1;
            $setting['member_score_apply'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'score', 'uniacid' => $_W['uniacid']));
        }

        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    }
}
// 全部记录
if ($op == "records") {
    $page = max(1, intval($_GPC['page']));
    $size = 20;
    $where = ' WHERE `uniacid` = '.$_W['uniacid'];
    if (!is_null($_GPC['keyword'])) {
        $where .= ' AND nickname LIKE "%'.$_GPC['keyword'].'%"';
    }
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('tg_member') . $where);
    $list = pdo_fetchall("SELECT * FROM ".tablename('tg_member') . $where . ' ORDER BY score_amount DESC LIMIT ' .($page - 1) * $size.','.$size);
    $pager = pagination($total, $page, $size);
    include wl_template('member/socre/records');
}
// 积分明细
if ($op == "record_detail") {
    empty($_GPC['opp']) ? $opp = 'over' : $opp = $_GPC['opp'];
    $openid = $_GPC['openid'];
    $page = max(1, intval($_GPC['page']));
    $size = 20;
    // 已结算
    if ($opp == 'over') {
        $where = ' WHERE `uniacid` = '.$_W['uniacid'].' AND `openid` = "'.$_GPC['openid'].'"';
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('tg_score_record') . $where);
        $list = pdo_fetchall("SELECT * FROM ".tablename('tg_score_record') . $where . ' ORDER BY id DESC LIMIT ' .($page - 1) * $size.','.$size);
        $pager = pagination($total, $page, $size);
    } else {
    // 未结算
        $and = " AND (status=1 OR status=2 OR status=8 OR status=3)";
        $where = ' WHERE `uniacid` = '.$_W['uniacid'].' AND `openid` = "'.$_GPC['openid'].'" AND is_score = 1 AND comtype = 0'. $and;
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('tg_order') . $where);
        $list = pdo_fetchall("SELECT * FROM ".tablename('tg_order') . $where . ' ORDER BY id DESC LIMIT ' .($page - 1) * $size.','.$size);
        $pager = pagination($total, $page, $size);
    }
    include wl_template('member/socre/record_detail');
}
// 兑换记录
if ($op == "exchange_detail") {
    $params = array(
        'openid' => $_GPC['openid'],
        'uniacid' => $_W['uniacid']
    );
    $list = pdo_getall('tg_score_exchange', $params);
    include wl_template('member/socre/exchange_detail');
}

exit;
