<?php
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'base';
wl_load()->model('setting');

//权限控制
$tid = 8158;
if (!pdo_fieldexists('tg_sendtime', 'merchantid')) {
    pdo_query("ALTER TABLE " . tablename('tg_sendtime') . " ADD `merchantid` int(11) NOT NULL default 0;");
}
global $_W, $_GPC;
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$_W['page']['title'] = $checkfunction['name'];

$setting = setting_get_by_name("sendtime");
if ($op == 'base') {
    $dispatch = pdo_fetch("select * from " . tablename("tg_setting") . " where cm_tg_setting.key like :key and uniacid = :uniacid", array(':key' => '%dispatch%', ':uniacid' => $_W['uniacid']));
    if ($_POST) {
//        $tip["content"] = $_POST["goods"]["content"];
        $tip["time"] = $_POST["goods"]["time"];
        if (!empty($dispatch)) {
            $datas["value"] = serialize($tip);
            pdo_query("update cm_tg_setting set cm_tg_setting.value='" . $datas["value"] . "' where cm_tg_setting.key = 'dispatch' and uniacid = " . $_W["uniacid"]);
        } else {
            $data["uniacid"] = $_W["uniacid"];
            $data["key"] = "dispatch";
            $data["value"] = serialize($tip);
            pdo_insert("tg_setting", $data);
        }
        $tip = '更新成功！';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('store/sendtime') . "';</script>";
        exit;
    }
    $parms = unserialize($dispatch["value"]);
    include wl_template('store/sendtime');
}
if ($op == 'ajax') {
    if (empty($setting)) {
        $value = array('sendtime' => 1);
        $data = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'sendtime',
            'value' => serialize($value)
        );
        setting_insert($data);
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    } else {
        $status = $setting['sendtime'] == 1 ? 2 : 1;
        setting_update_by_params(array('value' => serialize(array('sendtime' => $status))), array('key' => 'sendtime', 'uniacid' => $_W['uniacid']));
        die(json_encode(array('errno' => 0, 'message' => "保存成功")));
    }
}
//截止

if ($op == 'display') {
    $children = array();
    $sendtime = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' ");
    include wl_template('store/sendtime');

} elseif ($op == 'post') {
    $parentid = intval($_GPC['parentid']);
    $id = intval($_GPC['id']);
    if (!empty($id)) {
        $sendtime = pdo_fetch("SELECT * FROM " . tablename('tg_sendtime') . " WHERE id = '$id' and merchantid = '{$_W['user']['merchant_id']}'");
    } else {
        $sendtime = array(
            'displayorder' => 0,
        );
    }

    if (checksubmit('submit')) {
        if (empty($_GPC['starttime'])) {
            message('抱歉，请输入起始时间！');
        }
        if (empty($_GPC['endtime'])) {
            message('抱歉，请输入截止时间！');
        }
        if (empty($_GPC['total']) || intval($_GPC['total']) == 0) {
            message('抱歉，请输入承载量！');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'total' => intval($_GPC['total']),
            'sendtime' => $_GPC['catename'],
            'starttime' => $_GPC['starttime'],
            'endtime' => $_GPC['endtime'],
            'status' => intval($_GPC['status']),
            'merchantid' => $_W['user']['merchant_id']
        );
        if (!empty($id)) {
            unset($data['parentid']);
            pdo_update('tg_sendtime', $data, array('id' => $id));

        } else {
            pdo_insert('tg_sendtime', $data);
            $id = pdo_insertid();
        }
        echo "<script>alert('更新送货时间成功!');location.href='" . web_url('store/sendtime') . "';</script>";
        exit;

    }
    include wl_template('store/sendtime');
} elseif ($op == 'delete') {
    $id = intval($_GPC['id']);
    $sendtime = pdo_fetch("SELECT id FROM " . tablename('tg_sendtime') . " WHERE id = '{$id}' and merchantid = '{$_W['user']['merchant_id']}' ");
    if (empty($sendtime)) {
        message('抱歉，送货时间不存在或是已经被删除！', $this->createWebUrl('sendtime', array('op' => 'display')), 'error');
    }
    pdo_delete('tg_sendtime', array('id' => $id), 'OR');
    echo "<script>alert('送货时间删除成功!');location.href='" . web_url('store/sendtime') . "';</script>";
    exit;

}
exit();