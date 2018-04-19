<?php

$ops = array('selectdispatch', 'hx_entry', 'store', 'saler', 'selectsaler', 'selectstore');
$op_names = array('核销入口', '门店管理', '核销员', '选择粉丝', '选择门店');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'application', 'bdelete', $ops[$key], '应用与营销', '核销管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'hx_entry';
$opp = !empty($_GPC['opp']) ? $_GPC['opp'] : 'display';
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
//商品展示
if ($op == 'hx_entry') {
    $rule = pdo_fetch("select id from " . tablename('rule') . 'where uniacid=:uniacid and module=:module and name=:name', array(
        ':uniacid' => $_W['uniacid'],
        ':module' => 'lexiangpingou',
        ':name' => "拼团核销入口"
    ));
    if ($rule) {
        $set = pdo_fetch("select content from " . tablename('rule_keyword') . 'where uniacid=:uniacid and rid=:rid', array(
            ':uniacid' => $_W['uniacid'],
            ':rid' => $rule['id']
        ));
    }
    if (checksubmit('keysubmit')) {
        $keyword = empty($_GPC['keyword']) ? '核销' : $_GPC['keyword'];
        if (empty($rule)) {
            $rule_data = array(
                'uniacid' => $_W['uniacid'],
                'name' => '拼团核销入口',
                'module' => 'lexiangpingou',
                'displayorder' => 0,
                'status' => 1
            );
            pdo_insert('rule', $rule_data);
            $rid = pdo_insertid();
            $keyword_data = array(
                'uniacid' => $_W['uniacid'],
                'rid' => $rid,
                'module' => 'lexiangpingou',
                'content' => trim($keyword),
                'type' => 1,
                'displayorder' => 0,
                'status' => 1
            );
            pdo_insert('rule_keyword', $keyword_data);
        } else {
            pdo_update('rule_keyword', array(
                'content' => trim($keyword)
            ), array(
                'rid' => $rule['id']
            ));
        }
        $tip = '核销关键字设置成功!';
        echo "<script>alert('" . $tip . "');</script>";

        //message('核销关键字设置成功!', referer(), 'success');
    }
    include wl_template('application/bdelete');
} elseif ($op == 'store') {
    if ($opp == 'display') {
        $list = pdo_fetchall("select * from" . tablename('tg_store') . "where uniacid='{$_W['uniacid']}'");
        foreach ($list as $key => &$value) {
            $value['merchant'] = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' and id={$value['merchantid']}");
        }
    } elseif ($opp == 'post') {
        $id = $_GPC['id'];
        if ($id) {
            $item = pdo_fetch("select * from" . tablename('tg_store') . "where uniacid='{$_W['uniacid']}' and id = '{$id}'");
        }
        if (checksubmit('storesubmit')) {
            $id = $_GPC['id'];
            $data = array(
                'uniacid' => $_W['uniacid'],
                'storename' => $_GPC['storename'],
                'address' => $_GPC['address'],
                'tel' => $_GPC['tel'],
                'lng' => $_GPC['map']['lng'],
                'lat' => $_GPC['map']['lat'],
                'status' => $_GPC['qiyongstatus'],
                'merchantid' => $_GPC['merchantid']
            );
            if (trim($data['storename']) == '') {
                $tip = '必须填写门店名称！';
                echo "<script>alert('" . $tip . "');window.location.reload();</script>";
                //message('必须填写门店名称！', referer(), 'error');
                exit;
            }
            if ($id) {
                pdo_update('tg_store', $data, array(
                    'id' => $id
                ));
            } else {
                pdo_insert('tg_store', $data);
            }
            $tip = '操作成功！';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('application/bdelete/store') . "';</script>";
            exit;
            //message('操作成功！', web_url('application/bdelete/store'), 'success');
        }
    } elseif ($opp == 'delete') {
        $id = $_GPC['id'];
        pdo_delete('tg_store', array(
            'id' => $id
        ));
        $tip = '删除成功！';
        echo "<script>alert('" . $tip . "');</script>";

    }
    include wl_template('application/bdelete');
} elseif ($op == 'saler') {
    if ($opp == 'display') {
        $list = pdo_fetchall("select * from" . tablename('tg_saler') . "where uniacid='{$_W['uniacid']}'");
        foreach ($list as $key => $value) {
            $storeid_arr = explode(',', $value['storeid']);
            $storename = '';
            foreach ($storeid_arr as $k => $v) {
                if ($v) {
                    $store = pdo_fetch("select * from" . tablename('tg_store') . "where id='{$v}'");
                    $storename .= $store['storename'] . "/";
                }
            }
            $storename = substr($storename, 0, strlen($storename) - 1);
            $list[$key]['storename'] = $storename;
            $list[$key]['merchant'] = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' and id={$value['merchantid']}");
        }
    } elseif ($opp == 'post') {
        $id = $_GPC['id'];
        if ($id) {
            $saler = pdo_fetch("select * from" . tablename('tg_saler') . "where uniacid='{$_W['uniacid']}' and id = '{$id}'");
            $storeid_arr = explode(',', $saler['storeid']);
            $storename = '';
            foreach ($storeid_arr as $k => $v) {
                if ($v) {
                    $stores[$k] = pdo_fetch("select * from" . tablename('tg_store') . "where id='{$v}' and uniacid='{$_W['uniacid']}'");
                }
            }
        }
        if (checksubmit('salersubmit')) {
            wl_load()->model('member');
            $id = $_GPC['id'];
            $str = '';
            $storeids = $_GPC['storeids'];
            if ($storeids) {
                foreach ($storeids as $key => $value) {
                    if ($value) {
                        $str .= $value . ",";
                    }
                }
            }
            $data = array(
                'uniacid' => $_W['uniacid'],
                'openid' => $_GPC['openid'],
                'storeid' => $str,
                'status' => $_GPC['salerstatus'],
                'merchantid' => $_GPC['merchantid']
            );
            if ($data['openid'] == '') {
                message('必须选择核销员！', referer(), 'error');
                exit;
            }
            $info = member_get_by_params(" openid = '{$data['openid']}'");
            $data['avatar'] = $info['avatar'];
            $data['nickname'] = $info['nickname'];
            if ($id) {
                pdo_update('tg_saler', $data, array(
                    'id' => $id
                ));
            } else {
                pdo_insert('tg_saler', $data);
            }
            message('操作成功！', web_url('application/bdelete/saler'), 'success');
        }
    } elseif ($opp == 'delete') {
        $id = $_GPC['id'];
        pdo_delete('tg_saler', array(
            'id' => $id
        ));
        message('删除成功！', referer(), 'success');
    }
    include wl_template('application/bdelete');
} elseif ($op == 'selectstore') {
    $con = "uniacid='{$_W['uniacid']}' and status=1 and merchantid = '{$_W['user']['merchant_id']}'";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and storename LIKE '%{$keyword}%' ";
    }
    $ds = pdo_fetchall("select * from" . tablename('tg_store') . "where $con");
    include wl_template('application/query_store');
    exit;
} elseif ($op == 'selectdispatch') {
    $con = "uniacid='{$_W['uniacid']}' and status=2 and merchant_id = '{$_W['user']['merchant_id']}'";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and name LIKE '%{$keyword}%' ";
    }
    $ds = pdo_fetchall("select * from" . tablename('tg_delivery_template') . "where $con");
    include wl_template('application/query_dispatch');
    exit;
} elseif ($op == 'selectsaler') {
    $con = "uniacid='{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}'";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and nickname LIKE '%{$keyword}%'";
    }
    $ds = pdo_fetchall("select * from" . tablename('tg_member') . "where $con");
    include wl_template('application/query_saler');
    exit;
}
exit();