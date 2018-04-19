<?php
/**
 * [weliam] Copyright (c) 2016/3/26
 * 商城系统设置控制器
 */
defined('IN_IA') or exit('Access Denied');
global $_W;
global $_GPC;
load()->func('communication');

$op = $_GPC['op'];
$op = !empty($op) ? $op : 'copyright';
wl_load()->model('setting');

$_W['page']['title'] = $checkfunction['name'];

//商家编辑

$id = $_GPC['id'];
if (!empty($id)) {
    $sql = 'SELECT * FROM ' . tablename('tg_merchant') . ' WHERE id=:id AND uniacid=:uniacid LIMIT 1';
    $params = array(':id' => $id, ':uniacid' => $_W['uniacid']);
    $merchant = pdo_fetch($sql, $params);
    $linkman_mobile = pdo_fetch("select mobile from " . tablename('users_profile') . " where uid = '{$merchant['uid']}'");
    $merchant['linkman_mobile'] = $linkman_mobile['mobile'];
    $saler = pdo_fetch("select * from " . tablename('tg_member') . " where uniacid={$_W['uniacid']} and openid='{$merchant['openid']}'");
    if (!empty($merchant['messageopenid'])) {
        $messagesaler = pdo_fetch("select * from " . tablename('tg_member') . " where uniacid={$_W['uniacid']} and from_user='{$merchant['messageopenid']}'");
    }
    if (empty($merchant)) {
        message('商家信息不存在', web_url('store/user', array('op' => 'display')), 'success');
    }
}

if (checksubmit()) {
    $data = $_GPC['merchant']; // 获取打包值
    $data['detail'] = htmlspecialchars_decode($data['detail']);
    $data['openid'] = $_GPC['openid'];
    $data['messageopenid'] = $_GPC['messageopenid'];
    if (empty($merchant)) {
        $data['uniacid'] = $_W['uniacid'];
        $data['createtime'] = TIMESTAMP;
        $data['status'] = 0;

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
                $user['lastvisit'] = TIMESTAMP;
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
                $m['type'] = 'feng_merchants';
                $m['permission'] = 'all';
                $result = pdo_insert('users_permission', $m);
                /*添加操作员*/
//                pdo_insert('uni_account_users', array('uniacid' => $_W['uniacid'], 'uid' => $uid, 'role' => 'operator'));
                //插入手机号
                pdo_insert('users_profile', array('uid' => $uid, 'createtime' => TIMESTAMP, 'realname' => $data['linkman_name'], 'mobile' => $data['linkman_mobile']));
            }
        }
        $ret = pdo_insert('tg_merchant', $data);
        if ($ret) {
            message('添加商家成功！', web_url('store/user', array('op' => 'edit', 'id' => $id)), 'success');
        } else {
            message('添加商家失败！');
            exit;
        }

    } else {
        $m = pdo_update('tg_merchant', $data, array('id' => $data['id']));
        $user = pdo_fetch("select * from " . tablename("users") . " where uid = :id ", array(':id' => $merchant['uid']));

        $opwd = trim($_GPC['opwd']);
        $npwd = trim($_GPC['npwd']);
        if (empty($npwd)) {
            $npwd = $data['password'];
        }
        $tpwd = trim($_GPC['tpwd']);

        if ($data['open'] == 2) {
            $ret = pdo_update('users', array('status' => 1), array('uid' => $user['uid']));
        } else {
            if (empty($opwd) || empty($npwd) || empty($tpwd)) {
//                message('必须输入密码！');exit;
            } else {
                if ($opwd != $merchant['password']) {
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
                $ret = pdo_update('users', array('password' => $p, 'status' => 2), array('uid' => $merchant['uid']));
            }
        }
//        file_put_contents(TG_DATA."aa.log", var_export(array('m' => $m , 'ret' => $ret , 'npwd' => $npwd), true).PHP_EOL, FILE_APPEND);
        $profile = pdo_fetch("select id from " . tablename('users_profile') . " where uid = " . $merchant['uid']);
        if (empty($profile)) {
            //插入手机号
            pdo_insert('users_profile', array('uid' => $merchant['uid'], 'createtime' => TIMESTAMP, 'realname' => $data['linkman_name'], 'mobile' => $data['linkman_mobile']));
        } else {
            pdo_update('users_profile', array('realname' => $merchant['linkman_name'], 'mobile' => $merchant['linkman_mobile']), array('uid' => $merchant['uid']));
        }

        message('商家信息变更成功', web_url('store/user', array('op' => 'edit', 'id' => $id)), 'success');
        exit;
    }
}
if ($op == 'check') {
    $status = $_GPC['status'];
    if ($status == 1) {
        $merchant = pdo_fetchcolumn("select COUNT(*) from " . tablename('tg_merchant') . "  where uniacid = '{$_W['uniacid']}' and status = 1");
        $account = pdo_fetch("select merchant_num from " . tablename('account_wechats') . "  where uniacid = '{$_W['uniacid']}'");
        $num = $account['merchant_num'];
        if ($merchant >= $num) {
            die(json_encode(array("errno" => 1, 'message' => '商家数量已达到上限!')));
        }

        $batch = pdo_fetchall("select * from " . tablename('tg_merchant_batch') . " where uniacid = '{$_W['uniacid']}' order by createtime asc ");
        foreach ($batch as &$value) {
            if ($value['merchant_stock'] > 0) {
                $res = pdo_update('tg_merchant', array('status' => $status, 'check_time' => TIMESTAMP, 'merchant_batch' => $value['merchant_batch']), array('id' => $_GPC['id']));
                $re = pdo_update('tg_merchant_batch' , array('merchant_stock' => $value['merchant_stock'] - 1) , array('id' => $value['id']));
                break;
            }else{
                continue;
            }
        }

        if ($res && $re) {
            die(json_encode(array("errno" => 0, 'message' => '审核成功!')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '审核失败!')));
        }
    } elseif ($status == 2) {
        $res = pdo_update('tg_merchant', array('status' => $status, 'check_time' => TIMESTAMP), array('id' => $_GPC['id']));
        if ($res) {
            die(json_encode(array("errno" => 0, 'message' => '拒绝入驻成功!')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '拒绝入驻失败!')));
        }
    }
    die(json_encode(array("errno" => 1, 'message' => '请求出错!')));
}
include wl_template('store/user');

exit();