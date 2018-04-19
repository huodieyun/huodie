<?php

//门店区域地址
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_platform_acceptor` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uniacid` INT(10) UNSIGNED NOT NULL COMMENT '公众账号id',
  `openid` VARCHAR(50) COMMENT '',
  `nickname` VARCHAR(50) COMMENT '',
  `avatar` VARCHAR(50) COMMENT '',
  `status` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '启用状态',
  `createtime` INT(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_openid` (`openid`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$opp = !empty($_GPC['opp']) ? $_GPC['opp'] : 'display';

if ($opp == 'display') {

    $list = pdo_fetchall("select * from " . tablename('tg_platform_acceptor') . " where uniacid = '{$_W['uniacid']}' and status > -1 order by status desc , id desc ");

}

if ($op == 'sub_check') {

    $data = array();
    $data['createtime'] = TIMESTAMP;
    $data['status'] = 1;
    $check_code = pdo_fetch('select * from ' . tablename('tg_scan_code') . " where code = '{$_GPC['code']}' ");
    $acceptor = pdo_fetch('select * from ' . tablename('tg_platform_acceptor') . " where uniacid = 33 and openid = '{$check_code['openid']}' and status > -1 ");
    if ($acceptor) {
        die(json_encode(array('errno' => -1, 'message' => '非常抱歉！您已是平台的消息接受员，请勿重复申请！')));
    }
    if (!empty($check_code)) {

        $fans = pdo_fetch('SELECT uid,follow FROM ' . tablename('mc_mapping_fans') . ' WHERE openid = :openid  ', array(':openid' => $check_code['openid']));
        $members = pdo_fetch('SELECT avatar,nickname FROM ' . tablename('mc_members') . ' WHERE uid = :openid  ', array(':openid' => $fans['uid']));
        if ($fans['follow'] == 1) {
            $data['uniacid'] = $_W['uniacid'];
            $data['openid'] = $check_code['openid'];
            $data['avatar'] = $members['avatar'];
            $data['nickname'] = $members['nickname'];

            $res = pdo_insert('tg_platform_acceptor', $data);

            if ($res) {
                $dat['openid'] = $data['openid'];
                if ($dat['openid']) {
                    $dat['first'] = '申请平台消息接受员';
                    $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
                    $dat['keyword2'] = '申请平台消息接受员';
                    $dat['keyword3'] = '申请平台消息接受员申请成功';
                    $dat['keyword4'] = '申请成功';
                    $dat['remark'] = '';
                    pdo_insert('tg_service_process', $dat);
                }
                die(json_encode(array('errno' => 1, 'message' => '申请成功！')));
            } else {
                die(json_encode(array('errno' => -1, 'message' => '申请失败！')));
            }
        } else {
            die(json_encode(array('errno' => -1, 'message' => '您还未关注乐想拼购公众号，请立即关注此公众号以便及时接收平台信息！！')));
        }
    } else {
        die(json_encode(array('errno' => 0, 'message' => '通信失败' . $_GPC['code'])));
    }

}

if ($opp == 'change') {

    $id = intval($_GPC['id']);
    $status = intval($_GPC['status']);
//    die(json_encode($status));
    if ($id) {
        if ($status == -1) {
            $message = '删除';
        } else {
            $message = '修改';
        }
        $res = pdo_update('tg_platform_acceptor', array('status' => $status), array('id' => $id));
        if ($res) {
            $message .= '成功！';
        } else {
            $message .= '失败！';
        }
        die(json_encode(array('status' => $res, 'message' => $message)));
    } else {
        die(json_encode(array('status' => 0, 'message' => '传入参数错误！')));
    }
}

include wl_template('platform/platform_acceptor');

exit();