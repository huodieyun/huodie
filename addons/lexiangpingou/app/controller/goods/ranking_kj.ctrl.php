<?php

defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
wl_load()->model('account');
wl_load()->model('activity');
wl_load()->model('setting');
load()->func('communication');
wl_load()->model('functions');
wl_load()->classs('qrcode');
global $_W,$_GPC;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'detail_kj';
if ($op == "detail_kj"){
    $kid  = $_GPC["kid"];
    $goodsinfo = pdo_get("tg_kj_log",array("id"=>$kid));
    $goods= pdo_get("tg_goods",array("id"=>$goodsinfo["goodsid"]));
    //查询砍价记录
    $kj_log = pdo_getall("tg_friend_kj",array("kid"=>$kid));
    //查询砍价的详情
    foreach ($kj_log as $v){
        $userinfo = pdo_get("tg_member",array("openid"=>$v["openid"]));
        //查询商品名称
        $goodsinfo = pdo_get("tg_kj_log",array("id"=>$v['id']));
        //查询商品信息

        $v["name"] = $userinfo["nickname"];
    }
    include wl_template("goods/ranking_kj");
}
exit();


?>