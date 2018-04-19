<?php
$_W['page']['title'] = "分组管理";

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';


pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_member_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(50) DEFAULT '',
  `uniacid` int(11) DEFAULT '0',
  `name` varchar(50) DEFAULT '',
  `nameid` int(11) DEFAULT '0',
  `groupname` varchar(50) DEFAULT '',
  `commission` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_openid` (`openid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_member_group_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(50) DEFAULT '',
  `uniacid` int(11) DEFAULT '0',
  `name` varchar(50) DEFAULT '',
  `nameid` int(11) DEFAULT '0',
  `groupname` varchar(50) DEFAULT '',
  `parentid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
    KEY `idx_uniacid` (`uniacid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

//添加组长组员区分字段
if(!pdo_fieldexists('tg_member_group', 'fail')) {
    pdo_query("ALTER TABLE ".tablename('tg_member_group')." ADD `fail` int(1) NOT NULL default 0 COMMENT '组长是否失效';");
}
//添加组长组员是否失效字段
if(!pdo_fieldexists('tg_member_group_detail', 'fail')) {
    pdo_query("ALTER TABLE ".tablename('tg_member_group_detail')." ADD `fail` int(1) NOT NULL default 0 COMMENT '组员是否失效';");
}

$uniacid = $_W['uniacid'];

if ($op == 'display'){

    if (checksubmit('submit')) {

        $zuzhang = $_GPC['zuzhang'];
        $groupname = $_GPC['groupname'];
        $commission = $_GPC['commission'];
        $zuyuan = $_GPC['zuyuan'];

        $data1 = array(
            'uniacid' => $_W['uniacid'],
            'groupname' => $groupname,
            'commission' => $commission,
            'openid' => $zuzhang
        );

        $group = pdo_fetch("select * from " .tablename('tg_member_group') ." where uniacid = '{$uniacid}' and openid = '{$zuzhang}' and fail = 0 ");
        if (empty($group)) {
            pdo_update('tg_member', array('group_status' => 1), array('openid' => $zuzhang));
            $res = pdo_insert('tg_member_group', $data1);
            $res_id = pdo_insertid();

            foreach ($zuyuan as $key => $value) {
                $data2 = array(
                    'parentid' => $res_id,
                    'uniacid' => $_W['uniacid'],
                    'groupname' => $groupname,
                    'openid' => $value
                );
                pdo_insert('tg_member_group_detail', $data2);
                pdo_update('tg_member', array('group_status' => 2), array('openid' => $value));
            }
        }else {
            foreach ($zuyuan as $key => $value) {
                $re = pdo_fetch("select id from " .tablename('tg_member_group_detail') ." where uniacid = '{$uniacid}' and openid = '{$value}' and fail = 0 ");
                if (empty($re)){
                    $data2 = array(
                        'parentid' => $group['id'],
                        'uniacid' => $_W['uniacid'],
                        'groupname' => $groupname,
                        'openid' => $value
                    );
                    pdo_insert('tg_member_group_detail', $data2);
                    pdo_update('tg_member', array('group_status' => 2), array('openid' => $value));
                }
            }
        }
        message('添加成功', web_url('member/group' , array('op' => 'display')), 'success');
    }

    $status = $_GPC['status'];
    if (empty($status)){
        $keyword = $_GPC['keyword'];
        $group_list = pdo_fetchall("SELECT * FROM " .tablename('tg_member') ." where uniacid = '{$uniacid}' and enable = 1 and group_status <> 2 ");
        $list = pdo_fetchall("SELECT * FROM " .tablename('tg_member') ." where uniacid = '{$uniacid}' and enable = 1 and group_status = 0 ");
        for ($i = 0 ; $i < count($list) ; $i++) {
            if (!empty($keyword)){
                if ($keyword == $list[$i]['openid']){
                    unset($list[$i]);
                }
            }else{
                if ($group_list[0]['group_status'] != 1){
                    unset($list[0]);
                }
            }
        }
//        die(json_encode(array('group_list' => $group_list , 'list' => $list)));

    } elseif ($status == 1){
        $group = pdo_fetchall("select * from " .tablename('tg_member_group') ." where uniacid = '{$uniacid}' and fail = 0 ");
        foreach ($group as &$value) {
            $member = pdo_fetch("select * from " .tablename('tg_member') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}'");
            $value['nickname'] = $member['nickname'];
//            $value['account'] = pdo_fetchcolumn("select SUM(price) from " . tablename('tg_cashrecord')
//                ." where uniacid = '{$uniacid}' and account = 0 and openid in ( select openid from "
//                . tablename('tg_member_group_detail') ." where uniacid = '{$uniacid}' and fail = 0 and parentid = ( select id from "
//                .tablename('tg_member_group') ." where openid = '{$value['openid']}' and fail = 0 ) ) ");
//            $value['account'] = $value['account'] * $value['commission'] * 0.01;
//            $value['apply'] = pdo_fetchcolumn("select SUM(apply) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}'");
//            $value['give'] = pdo_fetchcolumn("select SUM(give) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}'");

            $apply = pdo_fetchcolumn("select SUM(sell_total) from " . tablename('tg_member')
                . " where uniacid = '{$uniacid}' and openid in ( select openid from "
                . tablename('tg_member_group_detail') . " where uniacid = '{$uniacid}' and parentid = ( select id from "
                .tablename('tg_member_group') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}' and fail = 0 ) ) ");
            $value['apply'] = ($apply - $member['balance_sell_total'] + $member['sell_total']) * $value['commission'] * 0.01;
            $value['account'] = pdo_fetchcolumn("select SUM(cm_tg_member_group_commission.get) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}' and status = 1");
            $value['get'] = pdo_fetchcolumn("select SUM(cm_tg_member_group_commission.get) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}'  and status = 2");
            $value['give'] = pdo_fetchcolumn("select SUM(give) from " .tablename('tg_member_group_commission') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}'");

        }

    }elseif($status == 2){
        $id = $_GPC['id'];
        $page = $_GPC['page'];
        $size = 10;
        $page = !empty($page) ? intval($_GPC['page']) : 1;
        $orderby = ' order by id';
        $list = pdo_fetchall("select * from " .tablename('tg_member_group_detail') ." where parentid = '{$id}' and fail = 0 " .$orderby ." limit " .($page - 1) * $size ." , " .$size);
        $total = pdo_fetchcolumn("select count(*) from " .tablename('tg_member_group_detail') ." where parentid = '{$id}' and fail = 0 ");
        $pager = pagination($total, $page, $size);
        foreach ($list as &$value) {
            $value['member'] = pdo_fetch("select * from " .tablename('tg_member') ." where uniacid = '{$uniacid}' and openid = '{$value['openid']}'");
        }

    }

}

if ($op == 'check'){
    $keyword = $_GPC['openid'];
    $list = pdo_fetchall("SELECT * FROM " .tablename('tg_member') ." where uniacid = '{$uniacid}' and enable = 1 and group_status = 0 and openid <> '{$keyword}'");
    die(json_encode(array('list' => $list)));
}

if ($op == 'delete'){
    $funcop = $_GPC['funcop'];
    if ($funcop == 'delete') {
        $id = $_GPC['id'];
        if (!empty($id)) {
            $group = pdo_fetch("select * from " .tablename('tg_member_group_detail') ." where id = " .$id);
            pdo_update('tg_member' , array('group_status' => 0) , array('uniacid' => $uniacid , 'from_user' => $group['openid']));
            pdo_update('tg_member_group_detail' , array('fail' => 1) , array('id' => $id , 'uniacid' => $uniacid));
            die(json_encode(array("errno" => 0, 'message' => '删除成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '删除失败')));
        }
    }elseif ($funcop == 'deleteAll') {
        $id = $_GPC['id'];
        if (!empty($id)) {
            $group_detail = pdo_fetchall("select * from " .tablename('tg_member_group_detail') ." where parentid = " .$id);
            foreach ($group_detail as &$value) {
                pdo_update('tg_member' , array('group_status' => 0) , array('from_user' => $value['openid']));
            }
            pdo_update('tg_member_group_detail' , array('fail' => 1) , array('uniacid' => $uniacid , 'parentid' => $id));
            $group = pdo_fetch("select * from " .tablename('tg_member_group') ." where id = " .$id);
            pdo_update('tg_member' , array('group_status' => 0) , array('uniacid' => $uniacid , 'from_user' => $group['openid']));
            pdo_update('tg_member_group' , array('fail' => 1) , array('id' => $id , 'uniacid' => $uniacid));
            die(json_encode(array("errno" => 0, 'message' => '删除成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '删除失败')));
        }
    }
}

if ($op == 'submit') {

    $zuzhang = $_GPC['zuzhang'];
    $groupname = $_GPC['groupname'];
    $commission = $_GPC['commission'];
    $zuyuan = $_GPC['zuyuan'];

    $data1 = array(
        'uniacid' => $_W['uniacid'],
        'groupname' => $groupname,
        'commission' => $commission,
        'openid' => $zuzhang
    );

    $group = pdo_fetch("select * from " .tablename('tg_member_group') ." where uniacid = '{$uniacid}' and openid = '{$zuzhang}' and fail = 0 ");
    if (empty($group)) {
        pdo_update('tg_member', array('group_status' => 1), array('openid' => $zuzhang));
        $res = pdo_insert('tg_member_group', $data1);
        $res_id = pdo_insertid();

        foreach ($zuyuan as $key => $value) {
            $data2 = array(
                'parentid' => $res_id,
                'uniacid' => $_W['uniacid'],
                'groupname' => $groupname,
                'openid' => $value
            );
            pdo_insert('tg_member_group_detail', $data2);
            pdo_update('tg_member', array('group_status' => 2), array('openid' => $value));
        }
    }else {
        foreach ($zuyuan as $key => $value) {
            $re = pdo_fetch("select id from " .tablename('tg_member_group_detail') ." where uniacid = '{$uniacid}' and openid = '{$value}' and fail = 0 ");
            if (empty($re)){
                $data2 = array(
                    'parentid' => $group['id'],
                    'uniacid' => $_W['uniacid'],
                    'groupname' => $groupname,
                    'openid' => $value
                );
                pdo_insert('tg_member_group_detail', $data2);
                pdo_update('tg_member', array('group_status' => 2), array('openid' => $value));
            }
        }
    }

    die(json_encode(array('status' => 1)));
}

include wl_template('member/group');
exit();
?>