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
if ($op == "display"){
    $advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 order by displayorder DESC");
    foreach ($advs as &$adv) {
        if (substr($adv['link'], 0, 5) != 'http:') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
    unset($adv);
    include wl_template("news/list");

}
if ($op == "post"){

    $id = $_GPC["id"];
    if (empty($id)){
        if (empty($_W["uniacid"])){
            $_W["uniacid"]   = $_GPC["i"];
        }
        $page = intval($_GPC['page']);
        $pagesize = 20;
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_graphic_list') . "where uniacid=:uniacid",array(":uniacid"=>$_W["uniacid"]));
        $notice = pdo_fetchall("SELECT * FROM " . tablename('tg_graphic_list') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 ORDER BY id DESC limit  ".($page - 1) * $pagesize . ',' . $pagesize);
        die(json_encode(array("status"=>"success","data"=>$notice)));
    }else{
//    $notice = pdo_fetchall("SELECT * FROM " . tablename('tg_graphic_list') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 ORDER BY id DESC");
        $notice = pdo_get("tg_graphic_list",array("uniacid"=>$_W["uniacid"],"id"=>intval($id)));
//        die(json_encode(array("status"=>"success","data"=>$notice)));
        include wl_template("news/notice_detail");
    }
}
