<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/3/31
 * Time: 15:12
 */
defined('IN_IA') or exit('Access Denied');
$pagetitle = !empty($config['tginfo']['sname']) ? $config['tginfo']['sname'] : '火蝶云';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and enabled=1 and parentid=0 and  activities_type=0  ORDER BY parentid ASC, displayorder DESC");
foreach ($category as &$adv) {
    $adv['thumb']=tomedia($adv['thumb']);
}
//die(json_encode(array('data' => $category)));
if($op =='duoshanghu'){
    include wl_template('goods/fenlei10');

    exit();
}
if($op =='fenlei17'){
    include wl_template('goods/fenlei17');

    exit();
}


include wl_template('goods/fenlei');
exit();