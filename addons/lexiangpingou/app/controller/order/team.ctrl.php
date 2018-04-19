<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/3/29
 * Time: 11:02
 */
global $_GPC, $_W;
$member=pdo_fetch('select * from cm_tg_member where uniacid=:uniacid and openid=:openid',array(':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid']));
$total=pdo_fetch('select count(id) as num from cm_tg_member where parentid=:parentid ',array(':parentid'=>$member['id']));
$pagetitle=$member['nickname'].'的客户';
if($_GPC['b']=='list'){
    $page = !empty($_GPC['page'])? intval($_GPC['page']): 1;
    $pagesize = !empty($_GPC['pagesize'])? intval($_GPC['pagesize']): 10;
// LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize
    $list=pdo_fetchall('select * from cm_tg_member where parentid=:parentid order by intertime desc limit '.($page - 1) * $pagesize . ',' . $pagesize,array(':parentid'=>$member['id']));

    die(json_encode($list));
}
include wl_template('order/team');
exit();