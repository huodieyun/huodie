<?php

    defined('IN_IA') or exit('Access Denied');
    $op = !empty($_GPC['op']) ? $_GPC['op'] : 'unaudited';
    $uniacid = $_W['uniacid'];
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    wl_load()->model('setting');
    $setting = setting_get_by_name("wholesale");

    // 基本设置
    if ($op == 'base') {

        include wl_template('member/wholesale/base');
        exit();
    }
    if ($op == 'ajax') {

        $apply = intval($_GPC['apply']);
        $smscode = intval($_GPC['smscode']);
        if (empty($setting)) {
            if ($apply) {
                $value = array('apply' => 1);
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'key' => 'wholesale',
                    'value' => serialize($value)
                );
                setting_insert($data);
            } elseif ($smscode) {
                $value = array('smscode' => 1);
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'key' => 'wholesale',
                    'value' => serialize($value)
                );
                setting_insert($data);
            }
            die(json_encode(array('errno' => 0, 'message' => "保存成功")));
        } else {
            if ($apply) {
                $status = intval($setting['apply']) == 1 ? 2 : 1;
                $setting['apply'] = $status;
                setting_update_by_params(array('value' => serialize($setting)), array('key' => 'wholesale', 'uniacid' => $_W['uniacid']));
            } elseif ($smscode) {
                $status = intval($setting['smscode']) == 1 ? 2 : 1;
                $setting['smscode'] = $status;
                setting_update_by_params(array('value' => serialize($setting)), array('key' => 'wholesale', 'uniacid' => $_W['uniacid']));
            }

            die(json_encode(array('errno' => 0, 'message' => "保存成功")));
        }
    }
    // 全部记录
    if ($op == 'record') {
        /*搜索条件*/
        $status = intval($_GPC['groupstatus']);
        $type = intval($_GPC['type']);
        $time = $_GPC['time'];
        if (!$type && (empty($starttime) || empty($endtime))) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']) + 86399;
            $condition .= " AND  wholesaler_apply_time >= {$starttime} AND wholesaler_apply_time <= {$endtime} ";
        }


    //    $condition .= " and wholesaler_status in (1,2) ";
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            $condition .= " AND ( nickname like '%{$keyword}%' or name like '%{$keyword}%' ) ";
        }
        $list = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ORDER BY wholesaler_apply_time asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ");
        $pager = pagination($total, $pindex, $psize);
        include wl_template('member/wholesale/record');
        exit();
    }
    // 未审核
    if ($op == 'unaudited') {
        /*搜索条件*/
        $status = intval($_GPC['groupstatus']);
        $type = intval($_GPC['type']);
        $time = $_GPC['time'];
        if (!$type && (empty($starttime) || empty($endtime))) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']) + 86399;
            $condition .= " AND  wholesaler_apply_time >= {$starttime} AND wholesaler_apply_time <= {$endtime} ";
        }


        $condition .= " and wholesaler_status = 0 ";
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            $condition .= " AND ( nickname like '%{$keyword}%' or name like '%{$keyword}%' ) ";
        }
        $list = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ORDER BY wholesaler_apply_time asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ");
        $pager = pagination($total, $pindex, $psize);
        include wl_template('member/wholesale/unaudited');
        exit();
    }
    // 已审核
    if ($op == 'audited') {
        /*搜索条件*/
        $status = intval($_GPC['groupstatus']);
        $type = intval($_GPC['type']);
        $time = $_GPC['time'];
        if (!$type && (empty($starttime) || empty($endtime))) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']) + 86399;
            $condition .= " AND  wholesaler_apply_time >= {$starttime} AND wholesaler_apply_time <= {$endtime} ";
        }


        $condition .= " and wholesaler_status = 1 ";
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            $condition .= " AND ( nickname like '%{$keyword}%' or name like '%{$keyword}%' ) ";
        }
        $list = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ORDER BY wholesaler_apply_time asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ");
        $pager = pagination($total, $pindex, $psize);
        include wl_template('member/wholesale/audited');
        exit();
    }
    // 取消
    if ($op == 'cancel') {
        /*搜索条件*/
        $status = intval($_GPC['groupstatus']);
        $type = intval($_GPC['type']);
        $time = $_GPC['time'];
        if (!$type && (empty($starttime) || empty($endtime))) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']) + 86399;
            $condition .= " AND  wholesaler_apply_time >= {$starttime} AND wholesaler_apply_time <= {$endtime} ";
        }


        $condition .= " and wholesaler_status = 2 ";
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            $condition .= " AND ( nickname like '%{$keyword}%' or name like '%{$keyword}%' ) ";
        }
        $list = pdo_fetchall("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ORDER BY wholesaler_apply_time asc LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_member') . " WHERE uniacid = {$_W['uniacid']} AND wholesaler_apply = 1 $condition ");
        $pager = pagination($total, $pindex, $psize);
        include wl_template('member/wholesale/cancel');
        exit();
    }
    if ($op == 'checked') {
        $id = intval($_GPC['id']);
    //    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
        pdo_update('tg_member', array('wholesaler_status' => 1, 'wholesaler_check_time' => TIMESTAMP), array('id' => $id));

        $tip = '审核成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/wholesale/unaudited') . "';</script>";
        exit;
    }

    if ($op == 'unchecked') {
        $id = intval($_GPC['id']);
    //    $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE id = {$id}");
        pdo_update('tg_member', array('wholesaler_status' => 2, 'wholesaler_check_time' => TIMESTAMP), array('id' => $id));

        internal_log('/commander/' , array('username'=>$_W['username']) , __LINE__ , __FILE__);
        $tip = '取消成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/wholesale/unaudited') . "';</script>";
        exit;
    }
?>
