<?php
/**
 * Created by PhpStorm.
 * User: 10987
 * Date: 2017/10/8
 * Time: 11:35
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('adv');
wl_load()->model('goods');
wl_load()->model('merchant');
$_SESSION['type'] = NULL;
global $_W,$_GPC;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 order by displayorder DESC");
foreach ($advs as &$adv) {
    if (substr($adv['link'], 0, 5) != 'http:') {
        $adv['link'] = "http://" . $adv['link'];
    }
}
unset($adv);
$notice = pdo_fetchall("SELECT * FROM " . tablename('tg_graphic_list') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 ORDER BY id DESC");
echo "<pre>";
var_dump($notice);die();
$id = $_GPC["id"];
if (empty($id)){
    include wl_template("news/list");
}else{
//    $notice = pdo_fetchall("SELECT * FROM " . tablename('tg_graphic_list') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 ORDER BY id DESC");
    $notice = pdo_get("tg_graphic_list",array("uniacid"=>$_W["uniacid"],"id"=>intval($id)));
    include wl_template("news/list");
}