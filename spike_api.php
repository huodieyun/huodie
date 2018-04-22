<?php
define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
load()->model('reply');
load()->app('common');
load()->classs('wesession');
load()->model('user');

if(!pdo_fieldexists('tg_goods', 'spike')) {
    pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `spike` int(1)  NOT NULL default 0 COMMENT '是否是秒杀';");
}
if(!pdo_fieldexists('tg_goods', 'spike_start')) {
    pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `spike_start` int(11)  NOT NULL default 0 COMMENT '秒杀开始时间';");
}
if(!pdo_fieldexists('tg_goods', 'spike_end')) {
    pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `spike_end` int(11)  NOT NULL default 0 COMMENT '秒杀结束时间';");
}

/*
 * 接口名字     spike_goods_view
 * 接口作用     查询限时秒杀的商品
 * 接口URL     spike_api.php?op=spike_goods_view
 * 传入参数     name  pwd   str
 * 回传结果     message  返回查询成功与否信息
 */
if ($op == 'spike_goods_view') {
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $sql = "select * from " .tablename('tg_goods') ." where uniacid = :uniacid and openid = :openid";
    $goods = pdo_fetchall($sql , array(':uniacid' => $uniacid , ':openid' => $openid));
    $goods = pdo_fetchall("select * from " .tablename('tg_goods') ." limit 0,10");
    die(json_encode(array('goods' => $goods)));
}