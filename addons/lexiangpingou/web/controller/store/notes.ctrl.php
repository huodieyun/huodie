<?php
load()->func('tpl');
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
global $_W, $_GPC;
//if(!pdo_fieldexists('tg_notices', 'merchant_id')) {
//    pdo_query("ALTER TABLE cm_tg_notices ADD `merchant_id` int(11) NOT NULL default 0;");
//}
if ($operation == "display"){
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_notices') . " WHERE uniacid = '{$_W['uniacid']}' and merchant_id = '{$_W['user']['merchant_id']}' ORDER BY id DESC");
}
if ($operation == 'post') {
    $id = intval($_GPC['id']);
    if (checksubmit('submit')) {
        $data = array(
            'uniacid' => $_W['uniacid'],
            'title' => $_GPC['title'],
            'content' => htmlspecialchars_decode($_GPC['content']),
            'enabled' => intval($_GPC['enabled']),
            'merchant_id'=>$_W['user']['merchant_id'],
            'createtime'=> time()
        );
        if (!empty($id)) {
            pdo_update('tg_notices', $data, array('id' => $id));
        } else {
            pdo_insert('tg_notices', $data);
            $id = pdo_insertid();
        }
        message('更新公告成功！', web_url('store/notes', array('op' => 'display')), 'success');
    }
    $adv = pdo_fetch("select * from " . tablename('tg_notices') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
}
if ($operation == 'delete') {
    $id = intval($_GPC['id']);
    $adv = pdo_fetch("SELECT id FROM " . tablename('tg_notices') . " WHERE id = '$id' AND uniacid = " . $_W['uniacid'] . "");
    if (empty($adv)) {
        message('抱歉，首页公告不存在或是已经被删除！', web_url('store/notes', array('op' => 'display')), 'error');
    }
    pdo_delete('tg_notices', array('id' => $id));
    message('公告删除成功！', web_url('store/notes', array('op' => 'display')), 'success');
}
include wl_template('store/notes');