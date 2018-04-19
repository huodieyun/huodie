<?php

defined('IN_IA') or exit('Access Denied');

global $_W;
global $_GPC;

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

if ($op == "display") {

    $uniacid = $_W["uniacid"];
    $_SESSION['type'] = 1;
    $res = pdo_fetch("select * from " . tablename("tg_delivery_distance") . " where uniacid = :uniacid and status = 1 ", array(":uniacid" => $uniacid));

    if ($res) {
        $map = unserialize($res['map']);
        $res['map'] = $map;
        $status = 1;
    } else {
        $status = 0;
        $map = "暂无数据！";
    }
    die(json_encode(array('status' => $status , 'list' => $map)));
}