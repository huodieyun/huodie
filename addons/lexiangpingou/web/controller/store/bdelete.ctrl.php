<?php

$ops = array('hx_entry', 'store', 'saler', 'selectsaler', 'selectstore', 'county' , 'selectcounty');
$op_names = array('核销入口', '门店管理', '核销员', '选择粉丝', '选择门店');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'application', 'bdelete', $ops[$key], '应用与营销', '核销管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'base';
$opp = !empty($_GPC['opp']) ? $_GPC['opp'] : 'display';
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
wl_load()->classs('qrcode');
$createqrcode = new creat_qrcode();
//权限控制
$tid = 8156;
//
wl_load()->model('functions');
wl_load()->model('setting');
$setting = setting_get_by_name("content_set");
$checkfunction = checkfunc($tid);
$_W['page']['title'] = $checkfunction['name'];
if ($op == 'base') {
    $url = web_url("store/bdelete");

    if ($_W["ispost"]) {
        $pdobase = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'content_set',
            'value' => serialize($_GPC["content"])
        );
        if (!empty($setting)) {
            setting_update_by_params($pdobase, array('key' => 'content_set', 'uniacid' => $_W['uniacid']));

        } else {
            setting_insert($pdobase);
        }
        echo "<script>alert('更新成功');window.location.href='{$url}'</script>";
        exit();

    }
    $setting = setting_get_by_name("content_set");
    include wl_template('store/bdelete');
}

if ($op == 'county') {
    $opp = $_GPC['opp'] ? $_GPC['opp'] : 'display';
    if ($opp == 'city') {
        $p_id = $_GPC['sid'];
        $city = pdo_getall('erp_area', array('parentid' => $p_id));
        die(json_encode(array('status' => 1, 'data' => $city)));
    } elseif ($opp == 'county') {
        $c_id = $_GPC['cid'];
        $county = pdo_getall('erp_area', array('parentid' => $c_id));
        die(json_encode(array('status' => 1, 'data' => $county)));
    }
    if ($opp == 'display') {
        $con = " and merchantid = '{$_W['user']['merchant_id']}' ";
        $list = pdo_fetchall("select * from " . tablename('tg_store_address') . " where uniacid='{$_W['uniacid']}' and status = 1 " . $con);
        foreach ($list as $key => &$value) {
            $value['merchant'] = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' and id = {$value['merchantid']}");
        }
        $province = pdo_getall('erp_area', array('level' => 1));
    } elseif ($opp == 'post') {
        $id = $_GPC['id'];
        if ($id) {
            $address = pdo_fetch("select * from " . tablename('tg_store_address') . " where uniacid = '{$_W['uniacid']}' and id = '{$id}'");
        }

        $data = array(
            'uniacid' => $_W['uniacid'],
            'provinceid' => $_GPC['provinceid'],
            'province' => $_GPC['province'],
            'cityid' => $_GPC['cityid'],
            'city' => $_GPC['city'],
            'countyid' => $_GPC['countyid'],
            'county' => $_GPC['county'],
            'status' => 1,
            'merchantid' => $_W['user']['merchant_id']
        );
        if (empty($id)) {
            $data['createtime'] = TIMESTAMP;
        }
        if (trim($data['county']) == '') {
            die(json_encode(array('status' => 0, 'message' => '必须填写门店所属区域！')));
            exit;
        }
        if ($id) {
            $res = pdo_update('tg_store_address', $data, array('id' => $id));
            if ($res) {
                $message = '更新成功';
            } else {
                $message = '更新失败';
            }
        } else {
            $old = pdo_fetch("select id from " .tablename('tg_store_address') ." where uniacid = {$_W['uniacid']} and countyid = '{$data['countyid']}' and status > -1 ");
            if ($old) {
                $res = 0;
                $message = '已存在该区域，请勿重复添加！';
            } else {
                $res = pdo_insert('tg_store_address', $data);
                if ($res) {
                    $message = '添加成功';
                } else {
                    $message = '添加失败';
                }
            }
        }
        die(json_encode(array('status' => $res, 'message' => $message)));

    } elseif ($opp == 'delete') {
        $id = $_GPC['id'];
        $res = pdo_update('tg_store_address', array('status' => -1), array('id' => $id));
        if ($res) {
            $message = '删除成功';
        } else {
            $message = '删除失败';
        }
        die(json_encode(array('status' => $res, 'message' => $message)));
    }
    include wl_template('store/bdelete');
}

//截止
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
        message('核销关键字设置成功!', referer(), 'success');
    }
    include wl_template('store/bdelete');
} elseif ($op == 'store') {
    if ($opp == 'display') {
        $con = " and merchantid = '{$_W['user']['merchant_id']}' ";
        $list = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid='{$_W['uniacid']}' " . $con);
        foreach ($list as $key => &$value) {
            $value['merchant'] = pdo_fetch("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}' and id={$value['merchantid']}");
        }
    } elseif ($opp == 'post') {
        $id = $_GPC['id'];
        if ($id) {
            $item = pdo_fetch("select * from " . tablename('tg_store') . " where uniacid = '{$_W['uniacid']}' and id = '{$id}'");
        }
        $county = pdo_getall('tg_store_address' , array('uniacid' => $_W['uniacid'] , 'merchantid' => $_W['user']['merchant_id'] , 'status' => 1));
        if (checksubmit('storesubmit')) {
//            var_dump($_GPC);die();
            $id = $_GPC['id'];
            $data = array(
                'uniacid' => $_W['uniacid'],
                'storename' => myTrim($_GPC['storename']),
                'address' => myTrim($_GPC['address']),
                'tel' => myTrim($_GPC['tel']),
                'cost' => myTrim($_GPC['cost']),
                'lng' => myTrim($_GPC['map']['lng']),
                'lat' => myTrim($_GPC['map']['lat']),
                'status' => myTrim($_GPC['qiyongstatus']),
                'business' => myTrim($_GPC['business']),
                'image' => myTrim($_GPC['image']),
                'introduction' => myTrim($_GPC['introduction']),
                'store_county' => myTrim($_GPC['store_county']),
                'merchantid' => $_W['user']['merchant_id']
            );
            if (empty($id)) {
                $data['uid'] = $_W['user']['uid'];
            }
            if (trim($data['storename']) == '') {
                message('必须填写门店名称！', referer(), 'error');
                exit;
            }
            $st = pdo_get('tg_store', ['address' => myTrim($_GPC['address'])]);
            if ($id) {
                $yst = pdo_get('tg_store', ['id' => $id]);
                if ($yst['address'] != myTrim($_GPC['address'])) {
                    if ($st) {
                        message($_GPC['address'].'：地址重复！', referer(), 'error');
                        exit;
                    }
                }
                pdo_update('tg_store', $data, array(
                    'id' => $id
                ));
            } else {
                if ($st) {
                    message($_GPC['address'].'：地址重复！', referer(), 'error');
                    exit;
                }
                pdo_insert('tg_store', $data);
            }
            message('操作成功！', web_url('store/bdelete/store'), 'success');
        }
    } elseif ($opp == 'delete') {
        $id = $_GPC['id'];
        pdo_update('tg_store', array('status' => 0), array('id' => $id));
        message('禁用成功！', referer(), 'success');
    }
    include wl_template('store/bdelete');
} elseif ($op == 'saler') {
    if ($opp == 'display') {
        $con = " and merchantid = '{$_W['user']['merchant_id']}' ";
        $list = pdo_fetchall("select * from" . tablename('tg_saler') . "where uniacid='{$_W['uniacid']}' " .$con);
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
                'merchantid' => $_W['user']['merchant_id'],
                'open' => $_GPC['open'],
                'uname' => $_GPC['uname'],
                'password' => $_GPC['password']
            );


            if ($data['openid'] == '') {
                message('必须选择核销员！', referer(), 'error');
                exit;
            }
            $info = member_get_by_params(" openid = '{$data['openid']}'");
            $data['avatar'] = $info['avatar'];
            $data['nickname'] = $info['nickname'];
            if ($id) {


                $user = pdo_fetch("select * from" . tablename("users") . "where uid=:uid", array(':uid' => $saler['uid']));
                $opwd = trim($_GPC['opwd']);
                $npwd = trim($_GPC['npwd']);
                $tpwd = trim($_GPC['tpwd']);
                if ($data['open'] == 2) {
                    $ret = pdo_update('users', array('status' => 1), array('uid' => $saler['uid']));
                } else {
                    if (empty($opwd) || empty($npwd) || empty($tpwd)) {

                    } else {
                        if ($opwd != $saler['password']) {
                            message('原密码错误！');
                            exit;
                        } else {
                            if ($npwd != $tpwd) {
                                message('两次密码输入不一致！');
                                exit;
                            }
                        }
                        if (istrlen($npwd) < 8) {
                            message('必须输入密码，且密码长度不得低于8位。');
                            exit;
                        }
                        $p = user_hash($npwd, $user['salt']);
                        $ret = pdo_update('users', array('password' => $p, 'status' => 2), array('uid' => $saler['uid']));
                    }

                }
                $ret = pdo_update('tg_saler', $data, array(
                    'id' => $id
                ));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['createtime'] = TIMESTAMP;

                if ($data['open'] == 1) {
                    load()->model('user');
                    if (!preg_match(REGULAR_USERNAME, $data['uname'])) {
                        message('必须输入用户名，格式为 3-15 位字符，可以包括汉字、字母（不区分大小写）、数字、下划线和句点。');
                    }
                    if (user_check(array('username' => $data['uname']))) {
                        message('非常抱歉，此用户名已经被注册，你需要更换注册名称！');
                    } else {
                        $tpwd = trim($_GPC['tpwd']);
                        $data['password'] = trim($data['password']);
                        if (empty($data['password']) || empty($tpwd)) {
                            message('密码不能为空！');
                            exit;
                        }
                        if ($data['password'] != $tpwd) {
                            message('两次密码输入不一致！');
                            exit;
                        }
                        if (istrlen($data['password']) < 8) {
                            message('必须输入密码，且密码长度不得低于8位。');
                            exit;
                        }
                        /*生成用户*/
                        $user = array();
                        $user['salt'] = random(8);
                        $user['username'] = $data['uname'];
                        $user['password'] = user_hash($data['password'], $user['salt']);
                        $user['groupid'] = 1;
                        $user['joinip'] = CLIENT_IP;
                        $user['joindate'] = TIMESTAMP;
                        $user['lastip'] = CLIENT_IP;
                        //$user['lastvisit'] = TIMESTAMP;
                        if (empty($user['status'])) {
                            $user['status'] = 2;
                        }
                        $result = pdo_insert('users', $user);
                        $uid = pdo_insertid();
                        $data['uid'] = $uid;
                        /*分配模块*/
                        $m = array();
                        $m['uniacid'] = $_W['uniacid'];
                        $m['uid'] = $uid;
                        $m['type'] = 'lexiangpingou';
                        $m['permission'] = 'all';
                        $result = pdo_insert('users_permission', $m);
                        /*添加操作员*/
                        pdo_insert('uni_account_users', array('uniacid' => $_W['uniacid'], 'uid' => $uid, 'role' => 'manager'));
                    }
                }
                pdo_insert('tg_saler', $data);
            }
            message('操作成功！', web_url('store/bdelete/saler'), 'success');
        }
    } elseif ($opp == 'delete') {
        $id = $_GPC['id'];
        pdo_delete('tg_saler', array(
            'id' => $id
        ));
        message('删除成功！', referer(), 'success');
    }
    include wl_template('store/bdelete');
} elseif ($op == 'selectstore') {
    $con = "uniacid='{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and status = 1 ";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and storename LIKE '%{$keyword}%' ";
    }
    $ds = pdo_fetchall("select * from" . tablename('tg_store') . "where $con");
    include wl_template('store/query_store');
    exit;
} elseif ($op == 'selectsaler') {
    $con = "uniacid='{$_W['uniacid']}' ";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and nickname LIKE '%{$keyword}%'";
    }
    $ds = pdo_fetchall("select * from" . tablename('tg_member') . "where $con");
    include wl_template('store/query_saler');
    exit;
}

if ($op == 'selectcounty') {

    $merchant_id = $_W['user']['merchant_id'];
    $store = pdo_getall('tg_store' , array('uniacid' => $_W['uniacid'] , 'merchantid' => $merchant_id , 'status' => 1));
    $county = pdo_getall('tg_store_address' , array('uniacid' => $_W['uniacid'] , 'merchantid' => $merchant_id , 'status' => 1));
    $list = array();
    foreach ($store as $item) {
        $i = 0;
        foreach ($county as $k => $it) {
            if ($item['store_county'] == $it['id']) {
                $item['countyname'] = $it['county'];
                $list[$k+1]['store'][] = $item;
                $list[$k+1]['countyname'] = $it['county'];
                continue;
            }
            $i++;
        }
        if ($i == count($county)) {
            $item['countyname'] = '未划分区域';
            $list[0]['store'][] = $item;
            $list[0]['countyname'] = '未划分区域';
        }
    }
    $list = array_merge($list);
    die(json_encode(array('status' => 1 , 'data' => $list)));
}

// 过滤空格等
function myTrim($str)
{
    $search = array(" ","　","\n","\r","\t");
    $replace = array("","","","","");
    return str_replace($search, $replace, $str);
}

exit();