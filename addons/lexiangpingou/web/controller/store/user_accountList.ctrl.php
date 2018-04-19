<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/6/22
 * Time: 9:37
 */
global $_W, $_GPC;
$op=((!(empty($_GPC['op'])) ? $_GPC['op'] : 'display'));
if(!pdo_fieldexists('users', 'perms')) {
    pdo_query("ALTER TABLE ".tablename('users')." ADD `perms` text ;");
}
if(!pdo_fieldexists('users', 'merchant_id')) {
    pdo_query("ALTER TABLE ".tablename('users')." ADD `merchant_id` int(11) default '0' ;");
}
if(!pdo_fieldexists('account_wechats', 'is_merchant')) {
    pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `is_merchant` int(11) default '0' ;");
}
if(!pdo_fieldexists('account_wechats', 'merchant_num')) {
    pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `merchant_num` int(11) default '0' ;");
}
if(!pdo_fieldexists('tg_merchant', 'allsalenum')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `allsalenum` int(11) ;");
}
if(!pdo_fieldexists('tg_merchant', 'falsenum')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `falsenum` int(11) ;");
}
if(!pdo_fieldexists('tg_merchant', 'percent')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `percent` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant_record', 'orderno')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant_record')." ADD `orderno`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_merchant_record', 'commission')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant_record')." ADD `commission`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_merchant_record', 'percent')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant_record')." ADD `percent`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_merchant_record', 'get_money')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant_record')." ADD `get_money` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci;");
}
if(!pdo_fieldexists('account_wechats', 'regbg')) {
    pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `regbg` varchar(1000) default '' ;");
}
if(!pdo_fieldexists('account_wechats', 'applycontent')) {
    pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `applycontent` text ;");
}
if(!pdo_fieldexists('account_wechats', 'payrate')) {
    pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `payrate` text ;");
}
if(!pdo_fieldexists('account_wechats', 'merchant_role')) {
    pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `merchant_role` text ;");
}
if(!pdo_fieldexists('account_wechats', 'merchant_pay_time')) {
    pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `merchant_pay_time` int(11) default '1' ;");
}
if(!pdo_fieldexists('tg_category', 'url')) {
    pdo_query("ALTER TABLE ".tablename('tg_category')." ADD `url` text ;");
}
if(!pdo_fieldexists('tg_category', 'show_type')) {
    pdo_query("ALTER TABLE ".tablename('tg_category')." ADD `show_type` int(2) ;");
}
if(!pdo_fieldexists('tg_merchant', 'status')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `status` int(2) default '0' ;");
}
if(!pdo_fieldexists('tg_merchant', 'tag')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." add `tag` text");
}
if(!pdo_fieldexists('tg_merchant', 'lng')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." add `lng` VARCHAR(145);");
}
if(!pdo_fieldexists('tg_merchant', 'lat')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." add `lat` VARCHAR(145);");
}

if(!pdo_fieldexists('tg_delivery_template', 'merchant_id')) {
    pdo_query("ALTER TABLE ".tablename('tg_delivery_template')." add `merchant_id` int(11) default '0';");
}
if(!pdo_fieldexists('tg_collect', 'merchant_id')) {
    pdo_query("ALTER TABLE ".tablename('tg_collect')." add `merchant_id` int(11) default '0';");
}
if(!pdo_fieldexists('tg_order', 'm_status')) {
    pdo_query("ALTER TABLE ".tablename('tg_order')." add `m_status` int(11) default '0';");
}
if(!pdo_fieldexists('tg_merchant_record', 'get_status')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant_record')." add `get_status` int(11) default '0';");
}
if(!pdo_fieldexists('tg_adv', 'merchant_id')) {
    pdo_query("ALTER TABLE ".tablename('tg_adv')." add `merchant_id` int(11) default '0';");
}
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_merchant_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT '0',
  `openid` varchar(50) DEFAULT '',
  `name` varchar(50) DEFAULT '',
  `tel` int(11) DEFAULT '0',
  `province`  varchar(250),
  `city`  varchar(250),
  `county`  varchar(250),
  `address` varchar(250) DEFAULT '',
  `price` decimal(10,2) DEFAULT '0.00',
  `orderno` varchar(250) DEFAULT '',
  `list` text,
  `status` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
if(!pdo_fieldexists('tg_merchant_order', 'transid')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant_order')." add `transid` text default '';");
}
if(!pdo_fieldexists('tg_goods', 'discount')) {
    pdo_query("ALTER TABLE ".tablename('tg_goods')." add `discount` int default '0';");
}
if(!pdo_fieldexists('tg_goods', 'shenhe')) {
    pdo_query("ALTER TABLE ".tablename('tg_goods')." add `shenhe` int default '0';");
}
if(!pdo_fieldexists('tg_goods', 'kefu_url')) {
    pdo_query("ALTER TABLE ".tablename('tg_goods')." add `kefu_url` text default '';");
}
if(!pdo_fieldexists('messikefu_cservice', 'merchant_id')) {
    pdo_query("ALTER TABLE ".tablename('messikefu_cservice')." add `merchant_id` int default '0';");
}
if(!pdo_fieldexists('tg_merchant', 'back_type')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `back_type` varchar(50);");
}
if(!pdo_fieldexists('tg_merchant', 'back_account')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `back_account` varchar(25) ;");
}
if(!pdo_fieldexists('tg_merchant', 'back_name')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `back_name` varchar(20);");
}
if(!pdo_fieldexists('tg_merchant', 'back_phone')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `back_phone` varchar(15);");
}
if(!pdo_fieldexists('tg_merchant', 'bank')) {
    pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `bank` int(1)  NOT NULL default 1;");
}
include wl_template('store/user_accountList');
exit();
