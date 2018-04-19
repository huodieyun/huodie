<?php
load()->func('tpl');
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$_W['page']['title'] = '自动推送黑名单管理';

//黑名单配置表
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_times` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uniacid` INT(10) UNSIGNED NOT NULL COMMENT '商家公众账号id',
  `blacktime` INT(11) NOT NULL COMMENT '自动添加黑名单次数',
  `status` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '是否开启自动添加黑名单',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

//黑名单表
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_blacklist` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uniacid` INT(10) UNSIGNED NOT NULL COMMENT '商家公众账号id',
  `openid` VARCHAR (100) NOT NULL COMMENT '微信会员openID',
  `nickname` VARCHAR (100) NOT NULL COMMENT '微信会员昵称',
  `status` TINYINT(3) NOT NULL DEFAULT 0 COMMENT '是否有效',
  `createtime` INT(11) NOT NULL DEFAULT 0 COMMENT '添加时间',
  `failtime` INT(11) NOT NULL DEFAULT 0 COMMENT '失效时间',
  `times` INT(11) NOT NULL DEFAULT 0 COMMENT '添加黑名单时未提货次数',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$uniacid = $_W['uniacid'];
if ($operation == 'display') {

    $size = 10;
    $page = !empty($_GPC['page']) ? $_GPC['page'] : 1;
    $sql = "SELECT * FROM " . tablename('tg_blacklist') . " WHERE uniacid = '{$uniacid}' order by status desc , id desc LIMIT " . ($page - 1) * $size . " , " . $size;
    $list = pdo_fetchall($sql);

    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_blacklist') . " WHERE uniacid = {$uniacid} ");

    $pager = pagination($total, $page, $size);

//    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_blacklist'));
//    foreach ($list as &$value) {
//        $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE openid=:openid", array(":openid" => $value['openid']));
//        $value['nickname'] = $member['nickname'];
//    }

    include wl_template('member/blacklist');

} elseif ($operation == 'post') {

//    $id = $_GPC['id'];
    $openid = $_GPC['openid'];
    $list = pdo_fetch("SELECT * FROM " . tablename('tg_blacklist') . " WHERE openid = '{$openid}' and status = 1 ");

    if (!empty($list)) {
//        pdo_update('tg_blacklist', $data, array('id' => $list['id']));
        message('该用户已在黑名单中!', web_url('member/blacklist', array('op' => '')), 'success');
    } else {
        $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE uniacid = '{$uniacid}' AND from_user = '" . $openid . "'");
//        die(json_encode(array('member' => $member)));
        $data = array(
            'uniacid' => $uniacid,
            'openid' => $openid,
            'status' => 1,
            'nickname' => $member['nickname'],
            'createtime' => TIMESTAMP
        );
        pdo_insert('tg_blacklist', $data);
        $id = pdo_insertid();
        message('添加黑名单成功！', web_url('member/blacklist', array('op' => '')), 'success');
    }
    include wl_template('member/blacklist');

} elseif ($operation == 'delete') {

    $funcop = $_GPC['funcop'];
    if (empty($funcop)) {
        $id = intval($_GPC['id']);
        $blacklist = pdo_fetch("SELECT * FROM " . tablename('tg_blacklist') . " WHERE id = '$id' ");
        if (empty($blacklist)) {
            message('抱歉，黑名单不存在或是已经被删除！', web_url('member/blacklist', array('op' => 'display')), 'error');
        }
        $openid = $blacklist['openid'];
        $rt = pdo_update('tg_order', array('black' => -1), array('uniacid' => $uniacid, 'openid' => $openid, 'black' => 1));
        pdo_update('tg_blacklist', array('status' => 0 , 'failtime' => TIMESTAMP) , array('id' => $id));
        message('黑名单删除成功！', web_url('member/blacklist', array('op' => 'display')), 'success');
    } elseif ($funcop == 'deleteAll') {
        $openid = pdo_fetchall("SELECT openid FROM " . tablename('tg_blacklist') ." where status = 1 ");
        foreach ($openid as &$value) {
            pdo_update('tg_order', array('black' => -1), array('uniacid' => $uniacid, 'openid' => $value['openid'], 'black' => 1));
        }
        pdo_update('tg_blacklist', array('status' => 0 , 'failtime' => TIMESTAMP) , array('status' => 1));
        message('清空黑名单成功！', web_url('member/blacklist', array('op' => 'display')), 'success');
    }
//    elseif($funcop == 'deleteCheck'){
//        $ids = $_GPC['ids'];
//        $blacklist = pdo_fetch("SELECT * FROM " . tablename('tg_blacklist') . " WHERE id = '$id'");
//        if (empty($blacklist)) {
//            message('抱歉，黑名单不存在或是已经被删除！', web_url('member/blacklist', array('op' => 'display')), 'error');
//        }
//        $openid = $blacklist['openid'];
//        pdo_update('tg_order' , array('status' => 9) , array('uniacid' => $uniacid , 'openid' => $openid , 'attend' => 0));
//        pdo_delete('tg_blacklist', array('id' => $ids));
//        message('批量删除黑名单成功！', web_url('member/blacklist', array('op' => 'display')), 'success');
//    }
    include wl_template('member/blacklist');

} elseif ($operation == 'set') {

    if (checksubmit('submit')) {
        $id = $_GPC['id'];
        $times = $_GPC['blacktime'];
        if ($times < 1){
            $times = 1;
        }
        $data = array(
            'uniacid' => $uniacid,
            'blacktime' => $times,
//            'activity' => $_GPC['activity'],
//            'activity_before' => $_GPC['activity_before'],
//            'activity_after' => $_GPC['activity_after'],
            'status' => $_GPC['status']
        );
        if (!empty($id)) {
            pdo_update('tg_times', $data, array('id' => $id));
            message('更新成功！', web_url('member/blacklist', array('op' => 'set')), 'success');
        } else {
            pdo_insert('tg_times', $data);
            $id = pdo_insertid();
            message('添加成功！', web_url('member/blacklist', array('op' => 'set')), 'success');
        }
    }
    $list = pdo_fetch("SELECT * FROM " . tablename('tg_times') . " WHERE uniacid = '{$uniacid}' ");
    include wl_template('member/blacklist');
} else {
    message('请求方式不存在');
}
exit();