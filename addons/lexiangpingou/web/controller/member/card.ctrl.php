<?php
/**
 * Created by PhpStorm.
 * User: 10987
 * Date: 2017/9/27
 * Time: 14:39
 */
defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'base';

$pindex = max(1, intval($_GPC['page']));
$psize = 10;

wl_load()->model('setting');
$setting = setting_get_by_name("kaiguan");

if ($op == 'base') {

    if (checksubmit()) {
        $member = setting_get_by_name("member");
        $member_rules = $_GPC['member_rules'];

        if (empty($member)) {
            if ($member_rules) {
                $value = array('member_rules' => $member_rules);
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'key' => 'member',
                    'value' => serialize($value)
                );
                setting_insert($data);
            }
            $tip = '保存成功';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('member/card') . "';</script>";
        } else {
            if ($member_rules) {
                $member['member_rules'] = $member_rules;
                setting_update_by_params(array('value' => serialize($member)), array('key' => 'member', 'uniacid' => $_W['uniacid']));
            }
            $tip = '保存成功';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('member/card') . "';</script>";
        }
    }
    $member = setting_get_by_name("member");
}

if ($op == 'ajax') {

    $member_apply = intval($_GPC['member_apply']);
    $member_smscode = intval($_GPC['member_smscode']);
    if (empty($setting)) {
        if ($member_apply) {
            $value = array('member_apply' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'kaiguan',
                'value' => serialize($value)
            );
            setting_insert($data);
        } elseif ($member_smscode) {
            $value = array('member_smscode' => 1);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'kaiguan',
                'value' => serialize($value)
            );
            setting_insert($data);
        }
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    } else {
        if ($member_apply) {
            $status = intval($setting['member_apply']) == 1 ? 2 : 1;
            $setting['member_apply'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'kaiguan', 'uniacid' => $_W['uniacid']));
        } elseif ($member_smscode) {
            $status = intval($setting['member_smscode']) == 1 ? 2 : 1;
            $setting['member_smscode'] = $status;
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'kaiguan', 'uniacid' => $_W['uniacid']));
        }

        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    }
}

if ($op == 'display') {

    /*搜索条件*/
    $status = intval($_GPC['status']);

    $condition = '';

    $time = $_GPC['time'];
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND member_apply_time >= {$starttime} AND member_apply_time <= {$endtime} ";

    }
    $condition .= " and member_status = 1 ";

//    if ($setting['member_apply'] == 1) {
//
//        $time = $_GPC['time'];
//        if (empty($starttime) || empty($endtime)) {
//            $starttime = strtotime('-1 month');
//            $endtime = time();
//        }
//        if (!empty($_GPC['time'])) {
//            $starttime = strtotime($_GPC['time']['start']);
//            $endtime = strtotime($_GPC['time']['end']) + 86399;
//            $condition .= " AND member_apply_time >= {$starttime} AND member_apply_time <= {$endtime} ";
//
//        }
//
//        $condition .= " AND member_apply = 1 ";
//        if ($status == -1) {
//            $condition .= " and member_status = -1 ";
//        }
//        if ($status == 0) {
//            $condition .= " and member_status = 0 ";
//        }
//        if ($status == 1) {
//            $condition .= " and member_status = 1 ";
//        }
//    } else {
//        $condition .= " and member_status = 1 ";
//    }

    if (!empty($_GPC['keyword'])) {
        $keyword = $_GPC['keyword'];
        $condition .= " AND ( name like '%{$keyword}%' or nickname like '%{$keyword}%' ) ";
    }

    $members = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} $condition ORDER BY member_apply_time desc , id asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} $condition");
    $pager = pagination($total, $pindex, $psize);

}

//添加会员
if ($op == 'member_add') {

    $opp = $_GPC['opp'];
    if ($opp == 'submit') {
        $openid = $_GPC['openid'];

        $member = pdo_get('tg_member' , array('uniacid' => $_W['uniacid'] , 'from_user' => $openid));
        if ($member['member_status'] == 1) {
            $message = '抱歉！该用户已是本店会员';
        } else {
            $data['name'] = $_GPC['name'];
            $data['addmobile'] = $_GPC['mobile'];
            $birthday = $_GPC['birthday'];
            $data['birthday'] = strtotime($birthday);
            $data['member_status'] = 1;
            $data['member_apply'] = 1;
            $data['member_apply_time'] = TIMESTAMP;
            $data['member_check_time'] = TIMESTAMP;
            $res = pdo_update('tg_member', $data, array('uniacid' => $_W['uniacid'], 'from_user' => $openid));
            if ($res) {
                $message = '添加成功！';
            } else {
                $message = '添加失败！';
            }
        }
        die(json_encode(array('status' => intval($res) , 'message' => $message)));
    }
}

//审核通过会员
if ($op == 'checked') {
    $id = intval($_GPC['id']);

    $res = pdo_update('tg_member', array('member_status' => 1, 'member_check_time' => TIMESTAMP), array('id' => $id));

    if ($res) {
        $tip = '审核成功';
    } else {
        $tip = '审核失败';
    }
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/card/display') . "';</script>";
    exit;
}

//取消会员
if ($op == 'unchecked') {
    $id = intval($_GPC['id']);
    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
    $res = pdo_update('tg_member', array('member_status' => -1, 'member_check_time' => TIMESTAMP), array('id' => $id));

    internal_log('/card/', $member, __LINE__, __FILE__, '取消会员');

    if ($res) {
        $tip = '取消成功';
    } else {
        $tip = '取消失败';
    }
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/card/display') . "';</script>";
    exit;
}

//在线充值
if ($op == 'recharge') {
    $list = pdo_getall('tg_member_recharge' , array('uniacid' => $_W['uniacid'] , 'status <>' => 5));
    foreach ($list as &$item) {
        if ($item['status'] == 1) {
            $item['status'] = '上架';
        } elseif ($item['status'] == 2) {
            $item['status'] = '下架';
        } elseif ($item['status'] == 5) {
            $item['status'] = '删除';
        }
        unset($item);
    }
//    die(json_encode($list));
}

//在线充值编辑
if ($op == 'recharge_post') {

    $id = intval($_GPC['id']);
    $data['uniacid'] = $_W['uniacid'];
    $data['member_amount'] = floatval($_GPC['member_amount']);
    $data['member_selling'] = floatval($_GPC['member_selling']);
    $data['status'] = intval($_GPC['status']);

    if (!$id) {
        $res = pdo_insert('tg_member_recharge', $data);
        if ($res) {
            $message = '添加成功';
        } else {
            $message = '添加失败';
        }
    } else {
        $res = pdo_update('tg_member_recharge' , $data , array('id' => $id));
        if ($res) {
            $message = '修改成功';
        } else {
            $message = '修改失败';
        }
    }

    die(json_encode(array('status' => $res , 'message' => $message)));

}

//在线充值假删除
if ($op == 'recharge_delete') {

    $id = intval($_GPC['id']);

    if ($id) {
        $res = pdo_update('tg_member_recharge', array('status' => 5) , array('id' => $id));
        if ($res) {
            $message = '删除成功';
        } else {
            $message = '删除失败';
        }
    } else {
        $res = -1;
        $message = '传入参数错误';
    }

    die(json_encode(array('status' => $res , 'message' => $message)));

}

//充值记录
if ($op == 'recharge_record') {

    $id = $_GPC['id'];
    $member = pdo_get('tg_member' , array('id' => $id));
    $orderno = trim($_GPC['keyword']);
    $time = $_GPC['time'];
    $condition = '';
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND createtime >= {$starttime} AND createtime <= {$endtime} ";

    }
    if ($orderno) {
        $condition .= " AND orderno like '%{$orderno}%' ";
    }

    $list = pdo_fetchall("select * from " .tablename('tg_member_recharge_record') ." where uniacid = {$member['uniacid']} and openid = '{$member['from_user']}' {$condition} ORDER BY createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $total = pdo_fetchcolumn("select count(id) from " .tablename('tg_member_recharge_record') ." where uniacid = {$member['uniacid']} and openid = '{$member['from_user']}' {$condition} ");
    $pager = pagination($total, $pindex, $psize);
    foreach ($list as &$item) {
        $item['nickname'] = $member['nickname'];
        $item['name'] = $member['name'];
        $item['createtime'] = date('Y-m-d H:i:s' , $item['createtime']);
        unset($item);
    }

}

//消费记录
if ($op == 'expenses_record') {

    $id = $_GPC['id'];
    $member = pdo_get('tg_member' , array('id' => $id));
    $time = $_GPC['time'];
    $condition = '';
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND createtime >= {$starttime} AND createtime <= {$endtime} ";

    }
    $orderno = trim($_GPC['keyword']);
    if ($orderno) {
        $condition .= " AND orderno like '%{$orderno}%' ";
    }
    $list = pdo_fetchall("select * from " .tablename('tg_member_billrecord') ." where uniacid = {$member['uniacid']} and openid = '{$member['from_user']}' {$condition} ORDER BY createtime desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    $total = pdo_fetchcolumn("select count(id) from " .tablename('tg_member_billrecord') ." where uniacid = {$member['uniacid']} and openid = '{$member['from_user']}' {$condition} ");
    $pager = pagination($total, $pindex, $psize);
    foreach ($list as &$item) {
        $item['nickname'] = $member['nickname'];
        $item['name'] = $member['name'];
        $item['createtime'] = date('Y-m-d H:i:s' , $item['createtime']);
        if ($item['type'] == 1) {
            $item['type'] = '线上商城';
        } elseif ($item['type'] == 2) {
            $item['type'] = '线下交易';
        }
        unset($item);
    }

}

//会员退款
if ($op == 'refund') {
    $id = intval($_GPC['id']);
    $data = balance_payment_refund($id, 1);

    die(json_encode($data));
}

//会员订单退款
if ($op == 'order_refund') {

    $id = $_GPC['id'];
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    $orderno = $item['orderno'];
    pdo_update('tg_order', array('refund_res' => $_GPC['refundres']), array('id' => $id));
    if ($_GPC['refundall'] == 1) {
        $res = balance_payment_refund($item['transid'], 1  , $item ,$_GPC['refundres']);

        if ($item['selltype'] == 7 && $item['bukuanstatus'] == 2) {
            $master = pdo_get('tg_order', array('master_orderno' => $item['orderno']));
            $res = balance_payment_refund($master['transid'], 1 , $item ,$_GPC['refundres']);
        }
    } else {
        $rfee = pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id AND status = 1 ", array(':id' => $item['transid']));
        $resultfee = 0;
        if (!empty($item['transid'])) {
            foreach ($rfee as $rf => $rforder) {
                $resultfee += $rforder['refundfee'];
            }
        }
        $mfee = $item['price'] - $resultfee;
        $res = balance_payment_refund($item['transid'], 2 , $item , $_GPC['refundres'] , $_GPC['refundnum']);
    }

    if ($res['status'] > 1) {
        $oplogdata = serialize($item);
        oplog('admin', "后台订单详情退款", web_url('member/card/order_refund'), $oplogdata);
    }
    die(json_encode($res));
}

// 删除会员
if ($op == 'delete') {
    $item = pdo_update('tg_member', array('member_apply' => 0, 'member_status' => 0), array('id' => $_GPC['id']));
    die(json_encode($item));
}

include wl_template('member/card');
