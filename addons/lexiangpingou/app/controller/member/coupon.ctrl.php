<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * coupon.ctrl
 * 优惠券控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('activity');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$openid = $_W['openid'];
if ($op == 'display') {

    $where1 = "SELECT * FROM " . tablename('tg_coupon') . ' WHERE `openid` = :openid AND `use_time` != 0  ORDER BY `end_time` DESC ';
    $params1 = array(
        ':openid' => $openid
    );
    //已过期
    $where2 = "SELECT * FROM " . tablename('tg_coupon') . ' WHERE `openid` = :openid AND `use_time` = 0 AND `end_time` < :time ORDER BY `end_time` DESC ';
    $params2 = array(
        ':openid' => $openid,
        ':time' => TIMESTAMP
    );
    //未使用
    $where3 = "SELECT * FROM " . tablename('tg_coupon') . ' WHERE `openid` = :openid AND `use_time` = :use_time AND `start_time` < :now1 AND `end_time` > :now2 ORDER BY `end_time` DESC ';
    $params3 = array(
        ':openid' => $openid,
        ':use_time' => 0,
        ':now1' => TIMESTAMP,
        ':now2' => TIMESTAMP
    );
    $pagetitle = '优惠券列表';
    $coupon1 = pdo_fetchall($where1, $params1);
    if ($coupon1) {
        foreach ($coupon1 as $key1 => $value1) {
            $coupon1[$key1]['end_time'] = date('Y-m-d', $value1['end_time']);
        }
    }
    $coupon2 = pdo_fetchall($where2, $params2);
    if ($coupon2) {
        foreach ($coupon2 as $key2 => $value2) {
            $coupon2[$key2]['end_time'] = date('Y-m-d H:i:s', $value2['end_time']);
        }
    }
    $coupon3 = pdo_fetchall($where3, $params3);

    if ($coupon3) {
        foreach ($coupon3 as $key3 => $value3) {
            $coupon3[$key3]['end_time'] = date('Y-m-d H:i:s', $value3['end_time']);
        }
    }
    include wl_template('member/coupon_list');
}

if ($op == 'detail') {
    $id = intval($_GPC['id']);
    if ($id) {
        $coupon = coupon($id);
        $pagetitle = $coupon['name'];
    } else {
        $tip = '优惠券不存在！';
        echo "<script>alert('" . $tip . "');</script>";


    }
    include wl_template('member/coupon_detail');
}

if ($op == 'get') {
    $id = intval($_GPC['id']);
    $code = $_GPC['code'];
//  获取二维码使用状态
    if($code){
        $codestatus = pdo_fetch("SELECT * FROM cm_tg_coupon_qrcode WHERE template_id={$id} AND code={$code}");
        if($codestatus['is_used']==2){
            $tip = '二维码已失效！';
            echo "<script>alert('" . $tip . "');</script>";
        }
    }

    if ($id) {
        $coupon = coupon_template($id);
        $pagetitle = $coupon['name'];
    } else {
        $tip = '优惠券不存在！';
        echo "<script>alert('" . $tip . "');</script>";

    }

    include wl_template('member/coupon_get');
}
if ($op == 'group') {
    $id = intval($_GPC['id']);
    if($id==4546)
    {
        echo "<script>alert('本优惠券已领完！');location.href='" . app_url('goods/list') . "';</script>";
        exit();
    }
    if ($id) {
        $group = pdo_fetch('select * from ' . tablename('tg_coupon_group') . ' where id=:id', array(':id' => $id));
        $record = pdo_fetch('select * from ' . tablename('tg_coupon_group_record') . ' where group_id=:id and openid=:openid and uniacid=:uniacid', array(':id' => $id, ':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
        if (empty($record)) {
            $check = 1;
            $cash = 0;
            $arr = explode(",", $group['couponsids']);
            foreach ($arr as $u) {
                $coupon_template = pdo_fetch("select * from " . tablename('tg_coupon_template') . " where id=" . $u);

                if (intval($coupon_template['total']) > intval($coupon_template['quantity_issue'])) {
                    $cash += $coupon_template['value'];
                    $coupon_data = array(
                        'uniacid' => $coupon_template['uniacid'],
                        'coupon_template_id' => $coupon_template['id'],
                        'name' => $coupon_template['name'],
                        'cash' => $coupon_template['value'],
                        'is_at_least' => $coupon_template['is_at_least'],
                        'at_least' => $coupon_template['at_least'],
                        'description' => $coupon_template['description'],
                        'start_time' => $coupon_template['start_time'],
                        'end_time' => $coupon_template['end_time'],
                        'use_time' => 0,
                        'openid' => $_W['openid'],
                        'createtime' => TIMESTAMP
                    );
                    pdo_insert('tg_coupon', $coupon_data);
                    $sql = 'UPDATE '.tablename('tg_coupon_template').' SET `quantity_issue` = `quantity_issue` + :quantity WHERE id=:id';
                    $params = array(
                        ':id' => $coupon_template['id'],
                        ':quantity' => 1
                    );
                    pdo_query($sql, $params);
                } else {
                    file_put_contents(TG_DATA . "conpon.log", var_export($u, true) . PHP_EOL, FILE_APPEND);
                    $error = 1;
                    break;
                }

            }

            if ($error == 1) {
                echo "<script>alert('本优惠券已领完！');location.href='" . app_url('goods/list') . "';</script>";
                exit();
            } else {
                $data = array('uniacid' => $_W['uniacid'],
                    'openid' => $_W['openid'],
                    'group_id' => $id,
                    'createtime' => TIMESTAMP
                );
                pdo_insert('tg_coupon_group_record', $data);
            }

        } else {

            echo "<script>alert('您已经领取过本优惠券，请不要重复领取');location.href='" . app_url('goods/list') . "';</script>";
            exit();
        }

    }

    include wl_template('member/coupon_group');
}
if ($op == 'post') {
    $id = intval($_GPC['id']);
    $code = $_GPC['code'];
    if ($id) {
        $coupon = coupon_grant($openid, $id,$code);
        if ($coupon['errno'] != 1) {
            wl_json(1);
        } else {
            wl_json(0, $coupon['message']);
        }
    } else {
        wl_json(0, '缺少优惠券id参数');
    }
}
exit();