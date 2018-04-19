<?php
/**
 * [weliam] Copyright (c) 2016/4/4
 * 配送方式
 */
$ops = array('display', 'post', 'editstatus', 'delete');
$op_names = array('配送列表', '新增/编辑', '是否启用', '删除');
$_W['page']['title'] = '运费模板';
$tid = 8155;
//权限控制
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'order', 'delivery', $ops[$key], '订单', '运费模板', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'base';
if ($op == 'base') {
    include wl_template('store/deliverylist');
}
if ($op == 'display') {
//	if($_W['user']['merchant_id']>0){
    $condition .= " and  merchant_id = {$_W['user']['merchant_id']} ";
//	}
    $template = pdo_fetchall("select * from" . tablename('tg_delivery_template') . " where  uniacid='{$_W['uniacid']}' {$condition}");
    $prices = array();

    foreach ($template as $key => $value) {
        if (empty($value)) {
            continue;
        }
        $info['region'] = json_decode($value['region'], true);
        $info['id'] = $value['id'];
        $info['name'] = $value['name'];
        $info['code'] = $value['code'];
        $info['updatetime'] = $value['updatetime'];
        $info['status'] = $value['status'];
        $info['displayorder'] = $value['displayorder'];
        $prices[$key] = $info;
    }
    unset($template);
    if (checksubmit('submit')) {
        $displayorder = $_GPC['displayorder'];
        if (!empty($displayorder)) {
            foreach ($displayorder as $id => $displayorder) {
                pdo_update('tg_delivery_template', array('displayorder' => $displayorder), array('id' => $id));
            }
        }
        message('更新成功', 'refresh', 'success');
    }

    include wl_template('store/deliverylist');
}
if ($op == 'post') {
    $id = intval($_GPC['id']);

    if ($_W['ispost']) {
        $name = trim($_GPC['name']);
        $code = trim($_GPC['code']);
        if (empty($name)) {
            message(error(1, '模板名称不能为空'), '', 'ajax');
        }
        $insert['uniacid'] = $_W['uniacid'];
        $insert['name'] = $name;
        $insert['code'] = $code;
        $insert['merchant_id'] = $_W['user']['merchant_id'];
        $insert['data'] = urldecode(trim($_GPC['data']));
        $insert['region'] = urldecode(trim($_GPC['seri']));
        $insert['updatetime'] = TIMESTAMP;
        if ($id > 0) {
            pdo_update('tg_delivery_template', $insert, array('id' => $id));
        } else {
            pdo_insert('tg_delivery_template', $insert);
            $id = pdo_insertid();
        }
        $data = json_decode($insert['data'], true);
        $region = json_decode($insert['region'], true);
        if (!empty($id)) {
            pdo_delete('tg_delivery_price', array('template_id' => $id));
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    foreach ($value as $key1 => $value1) {
                        if (empty($value1['hasChild'])) {
                            $insertdata['template_id'] = $id;
                            $insertdata['province'] = $value1['title'];
                            $insertdata['free'] = $region[$key]['free'];
                            $insertdata['first_fee'] = $region[$key]['first_fee'];
                            $insertdata['first_weight'] = $region[$key]['first_weight'];
                            $insertdata['second_weight'] = $region[$key]['second_weight'];
                            $insertdata['second_fee'] = $region[$key]['second_fee'];
                            pdo_insert('tg_delivery_price', $insertdata);
                            $insertdata = array();
                        } else {
                            foreach ($value1['cities'] as $key2 => $value2) {
                                if (empty($value2['hasChild'])) {
                                    $insertdata['template_id'] = $id;
                                    $insertdata['province'] = $value1['title'];
                                    $insertdata['city'] = $value2['title'];
                                    $insertdata['free'] = $region[$key]['free'];
                                    $insertdata['first_fee'] = $region[$key]['first_fee'];
                                    $insertdata['first_weight'] = $region[$key]['first_weight'];
                                    $insertdata['second_weight'] = $region[$key]['second_weight'];
                                    $insertdata['second_fee'] = $region[$key]['second_fee'];
                                    pdo_insert('tg_delivery_price', $insertdata);
                                    $insertdata = array();
                                } else {
                                    foreach ($value2['districts'] as $key3 => $value3) {
                                        $insertdata['template_id'] = $id;
                                        $insertdata['province'] = $value1['title'];
                                        $insertdata['city'] = $value2['title'];
                                        $insertdata['district'] = $value3;
                                        $insertdata['free'] = $region[$key]['free'];
                                        $insertdata['first_fee'] = $region[$key]['first_fee'];
                                        $insertdata['first_weight'] = $region[$key]['first_weight'];
                                        $insertdata['second_weight'] = $region[$key]['second_weight'];
                                        $insertdata['second_fee'] = $region[$key]['second_fee'];
                                        pdo_insert('tg_delivery_price', $insertdata);
                                        $insertdata = array();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        die(json_encode(array('result' => 'success')));
    }
    if ($id > 0) {
        $item = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id={$id}");
        if (!empty($item)) {
            $item['serialize'] = json_decode($item['region'], true);
        }
    }
    include wl_template('store/area');
}
if ($op == 'editstatus') {
    $id = intval($_GPC['id']);
    $status = intval($_GPC['status']);
    pdo_update('tg_delivery_template', array('status' => $status), array('id' => $id));
    die(json_encode(array('result' => 'success')));
}
if ($op == 'delete') {
    $id = intval($_GPC['id']);
    pdo_delete('tg_delivery_template', array('id' => $id));
    pdo_delete('tg_delivery_price', array('template_id' => $id));
    die(json_encode(array('result' => 'success')));
}
exit();