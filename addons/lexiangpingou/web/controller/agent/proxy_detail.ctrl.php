<?php

defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'add_agent_record';

$uniacid = $_W['uniacid'];

$agent_detail = pdo_get('tg_agents', array('uniacid' => $uniacid));
//die(json_encode($agent_detail));


//购买记录
if ($op == 'add_agent_record') {
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
        $com .= " and ( agents_id in ( select id from " . tablename('tg_agents') . " where name like '%{$keyword}%' or open_name like '%{$keyword}%' ) or open_name like '%{$keyword}%' ) ";
    }
    $list = pdo_fetchall("select * from " . tablename('tg_agents_records') . " where agents_id = {$agent_id} {$com} order by createtime limit {$page} , {$psize} ");
    foreach ($list as &$item) {
        $agent_name = pdo_getcolumn('tg_agents', array('id' => $item['agents_id']),'name');
        $item['name'] = $agent_name;
        $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
        unset($item);
    }
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_agents_records') . " where agents_id = {$agent_id} {$com} ");
    $pager = pagination($total, $pindex, $psize);
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
    $list = pdo_fetchall("select * from " . tablename('account_vip_record') . " where agents_id = {$agent_id} {$com} order by createtime limit {$page} , {$psize} ");
    foreach ($list as &$item) {
        if ($item['agents_id'] == 0) {
            $item['name'] = '管理员';
        } else {
            $agent_name = pdo_getcolumn('tg_agents', array('id' => $item['agents_id']),'name');
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
    $total = pdo_fetchcolumn("select count(id) from " . tablename('account_vip_record') . " where agents_id = {$agent_id} {$com} ");
    $pager = pagination($total, $pindex, $psize);
//    die(json_encode($list));

}


include wl_template('agent/proxy_detail');

exit();
