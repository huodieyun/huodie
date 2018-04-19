<?php
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'base';
//权限控制
$tid = 8153;

if(!pdo_fieldexists('tg_print', 'lbs')) {
    pdo_query("ALTER TABLE ".tablename('tg_print')." ADD `lbs` varchar(100) COMMENT '经纬度';");
}
if(!pdo_fieldexists('tg_print', 'map')) {
    pdo_query("ALTER TABLE ".tablename('tg_print')." ADD `map` text COMMENT '区域设置';");
}
if(!pdo_fieldexists('tg_print', 'hexiao_print')) {
    pdo_query("ALTER TABLE ".tablename('tg_print')." ADD `hexiao_print` int(11) NOT NULL default 0 COMMENT '核销自动打印';");
}
if(!pdo_fieldexists('tg_print', 'merchant_id')) {
    pdo_query("ALTER TABLE ".tablename('tg_print')." ADD `merchant_id` int(11) NOT NULL default 0;");
}
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$_W['page']['title'] = $checkfunction['name'];
if ($op == 'base') {
    include wl_template('store/print');
}
//截止
$id = intval($_GPC['id']);//订单ID

if($op == 'post') {
    if($id > 0) {
        $item = pdo_fetch('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :uniacid AND id = :id', array(':uniacid' => $_W['uniacid'], ':id' => $id));
        $item["lbs"] = unserialize($item["lbs"]);
        $result = unserialize($item["map"]);
        $result = json_encode($result);
        if (empty($result)){
            $result = false;
        }
//                echo "<pre>";
//                var_dump(json_encode($result));die();
        /*var_dump($item);die();*/
    }
    if (empty($item)) {
        $item = array('status' => 1, 'print_nums' => 1);
    }
    if (checksubmit('submit')) {
        $data['status'] = intval($_GPC['status']);
        $data['mode'] = 1;
        $data["lbs"] = serialize($_GPC["map"]);
        $data['name'] = !empty($_GPC['name']) ? trim($_GPC['name']) : message('打印机名称不能为空', '', 'error');
        $data['print_no'] = !empty($_GPC['print_no']) ? trim($_GPC['print_no']) : message('机器号不能为空', '', 'error');
        $data['member_code'] = $_GPC['member_code'];
        $data['key'] = !empty($_GPC['key']) ? trim($_GPC['key']) : message('打印机key不能为空', '', 'error');
        $data['print_nums'] = intval($_GPC['print_nums']) ? intval($_GPC['print_nums']) : 1;
        $data['hexiao_print'] = intval($_GPC['hexiao_print']);
        $data['since_print'] = intval($_GPC['since_print']);
        $data['merchant_id'] = intval($_W['user']['merchant_id']);
        if (!empty($_GPC['qrcode_link']) && (strexists($_GPC['qrcode_link'], 'http://') || strexists($_GPC['qrcode_link'], 'https://'))) {
            $data['qrcode_link'] = trim($_GPC['qrcode_link']);
        }
        $data['uniacid'] = $_W['uniacid'];
        $data['sid'] = $sid;
        if (!empty($item) && $id) {
            pdo_update('tg_print', $data, array('uniacid' => $_W['uniacid'], 'id' => $id));
        } else {
            pdo_insert('tg_print', $data);
        }
        echo "<script>alert('更新打印机设置成功');location.href='" . web_url('store/print') . "';</script>";
        exit;
    }
    include wl_template('store/print');
} elseif ($op == 'display') {

    $data = pdo_fetchall("SELECT * FROM " . tablename('tg_print') . " WHERE uniacid = :uniacid  and merchant_id = '{$_W['user']['merchant_id']}' ", array(':uniacid' => $_W['uniacid']));

    include wl_template('store/print');
} elseif ($op == 'del') {
    $id = intval($_GPC['id']);
    pdo_delete('tg_print', array('uniacid' => $_W['uniacid'], 'id' => $id));

    echo "<script>alert('删除打印机成功');location.href='" . web_url('store/print') . "';</script>";
    exit;
}
exit();