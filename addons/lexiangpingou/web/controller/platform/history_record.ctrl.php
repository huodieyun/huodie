<?php

$_W['page']['title'] = "极限单品 - 历史打款记录";
$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}
global $_W, $_GPC;
load()->func("tpl");
$uniacid = $_W['uniacid'];

if ($op == "display") {

    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $keyword = $_GPC['keyword'];
    $con = '';
    if (!empty($keyword)) {
        $con .= " and gname like '%{$keyword}%' ";
    }
    if ($uniacid != 33) {
        $con .= " and supply_id = '{$uniacid}' ";
    }
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_platform_pay_supply') . " WHERE 1 {$con} LIMIT " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_platform_pay_supply') . " WHERE 1 {$con} ");
    foreach ($list as &$item) {
        $item['uni_payimg'] = tomedia($item['uni_payimg']);

        unset($item);
    }
    $pager = pagination($total, $page, $size);
    include wl_template("platform/history_record");
    exit();
}
if ($op == 'detail') {
    $id = intval($_GPC['id']);
    $pay_supply = pdo_get('tg_platform_pay_supply' , array('id'=>$id));
    if ($pay_supply) {
        $pay_supply['uni_payimg'] = tomedia($pay_supply['uni_payimg']);
        $list = pdo_fetchall('SELECT * FROM ' . tablename('tg_supply_collect') . ' WHERE platform_supplier_orderid=:platform_supplier_orderid', array(':platform_supplier_orderid' => $id));
    } else {
        message('非常抱歉！暂无数据' , '' , 'error');
    }

    include wl_template("platform/history_record_detail");
    exit();
}