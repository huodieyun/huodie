<?php
defined('IN_IA') or exit('Access Denied');
$op = $_GPC['op'];
$op = !empty($op) ? $op : 'display';

if(!pdo_fieldexists('workform', 'inner_work')) {
    pdo_query("ALTER TABLE ".tablename('workform')." ADD `inner_work` int(3) not null default 0 COMMENT '是否内部提交工单';");
}
if(!pdo_fieldexists('workform', 'group')) {
    pdo_query("ALTER TABLE ".tablename('workform')." ADD `group` varchar(25) COMMENT '开发分组';");
}
if(!pdo_fieldexists('workform', 'start_time')) {
    pdo_query("ALTER TABLE ".tablename('workform')." ADD `start_time` int(11) COMMENT '开发开始时间';");
}
if(!pdo_fieldexists('workform', 'end_time')) {
    pdo_query("ALTER TABLE ".tablename('workform')." ADD `end_time` int(11) COMMENT '开发结束时间';");
}

if ($op == 'display') {

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;

    $condition = " where inner_work = 1 "; //
    $status = $_GPC['status'];
    if ($status == '-1') {
        $condition .= " and status <> 2 and status <> -2";
    }
    if ($status == '2') {
        $condition .= " and status in (2,-2) ";
    }

    $allform = pdo_fetchall("select * from "
        . tablename('workform') . "  " . $condition . " order by create_time desc " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

    $allform2 = pdo_fetchall("select * from "
        . tablename('workform') . "  " . $condition . " order by create_time desc");
    $total = count($allform2);
    $pager = pagination($total, $pindex, $psize);
    include wl_template('service/internal');
}

if ($op == 'insert') {
    $acct = pdo_fetch("select name,uniacid,vip from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");

    if (checksubmit()) {
//        die(json_encode(1));
        $data_admin['form_id'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $data_admin['uniacid'] = $_W['uniacid'];
        $data_admin['create_time'] = TIMESTAMP;
        $data_admin['update_time'] = $data_admin['create_time'];
        $data_admin['name'] = $_W['user']['username'];
        if ($_GPC['type'] == 2 && $acct['vip'] == 0) {
            $data_admin['status'] = -3;
        } else {
            $data_admin['status'] = -1;
        }
        $data_admin['type'] = $_GPC['type'];
        $data_admin['vip'] = $acct['vip'];
        $data_admin['title'] = $_GPC['title'];
        $data_admin['inner_work'] = 1;
        $storage = pdo_insert('workform', $data_admin);

        $data_content['form_id'] = $data_admin['form_id'];
        $data_content['content_id'] = $data_admin['form_id'];
        $data_content['content'] = $_GPC['content'];
        $data_content['update_time'] = $data_admin['create_time'];
        $data_content['who'] = $_W['user']['uid'];
        $storage = pdo_insert('workform_content', $data_content);

        $goimages = $_GPC['img'];
        $goimages = array_reverse($goimages);
        foreach ($goimages as $key => $value) {
            $data2 = array('img_url' => $goimages[$key], 'content_id' => $data_content['content_id']);
            pdo_insert('workform_img', $data2);
        }
    }
    include wl_template('service/internal');
}

if ($op == 'detail') {
    $form_id = $_GPC['form_id'];
    $form_admin = pdo_fetch("select vip,type,title,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i') as create_time,uniacid,status from "
        . tablename('workform') . " where form_id = :form_id order by create_time asc", array(':form_id' => $form_id));
    $allcontent = pdo_fetchall("select content_id,content,FROM_UNIXTIME(update_time,'%Y-%m-%d %H:%i') as update_time,who from "
        . tablename('workform_content') . " where form_id = :form_id order by update_time asc", array(':form_id' => $form_id));
    $internal = 1;
    include wl_template('service/workform_detail_admin');
}

if($op=='develop_start'){
    $form_id = $_GPC['form_id'];

    $data = array(
        'develop_status' => 1,
        'group' => $_GPC['group'],
        'start_time' => TIMESTAMP
    );
    $result_start = pdo_update('workform' , $data , array('form_id' => $form_id));
    die(json_encode($result_start));
}

if($op=='develop_over'){
    $form_id = $_GPC['form_id'];
    $nowtime = TIMESTAMP;
    $result_over = pdo_update('workform', array('develop_status' => 2 , 'status' => 2 ,'end_time' => $nowtime), array('form_id' => $form_id));
    die(json_encode($result_over));
}

exit();
?>