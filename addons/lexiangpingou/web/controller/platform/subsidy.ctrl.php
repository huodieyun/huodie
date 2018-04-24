<?php
$_W['page']['title'] = "极限单品 - 商家补贴设置";

global $_W, $_GPC;
load()->func("tpl");
$uniacid = $_W['uniacid'];
wl_load()->model('setting');
$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}
if($op == 'display'){
    $setting = setting_get_by_name('subsidy');
    if(checksubmit()){
        if (empty($setting)) {
            $value = array('percent' => $_GPC['percent']);
            $data = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'subsidy',
                'value' => serialize($value)
            );
            setting_insert($data);
//            die(json_encode(array('errno' => 0, 'message' => "保存成功")));
        } else {
            $setting['percent'] = $_GPC['percent'];
            setting_update_by_params(array('value' => serialize($setting)), array('key' => 'subsidy', 'uniacid' => $_W['uniacid']));
//            die(json_encode(array('errno' => 0, 'message' => "保存成功")));
        }
    }
}
if($op == 'post'){
    $setting = setting_get_by_name('subsidy');

}
include wl_template('platform/subsidy');

exit();