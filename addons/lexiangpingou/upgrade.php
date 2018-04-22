<?php
if(!pdo_fieldexists('tg_order', 'goodsprice')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `goodsprice` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'pay_price')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `pay_price` VARCHAR( 45 ) NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'freight')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `freight` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'yunfei_id')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `yunfei_id` int( 11 )  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'is_discount')) {
  pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `is_discount` int( 11 )  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'credits')) {
  pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `credits` int( 11 )  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'credits')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `credits` int( 11 )  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'is_usecard')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `is_usecard` int( 11 )  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'is_hexiao')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `is_hexiao` int( 2 )  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'hexiao_id')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `hexiao_id` VARCHAR( 225 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'is_hexiao')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `is_hexiao` int( 2 )  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'hexiaoma')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `hexiaoma` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'veropenid')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `veropenid` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'is_share')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `is_share` int(2)  NOT NULL;");
}

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_dispatch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT '0',
  `dispatchname` varchar(50) DEFAULT '',
  `dispatchtype` int(11) DEFAULT '0',
  `displayorder` int(11) DEFAULT '0',
  `firstprice` decimal(10,2) DEFAULT '0.00',
  `secondprice` decimal(10,2) DEFAULT '0.00',
  `firstweight` int(11) DEFAULT '0',
  `secondweight` int(11) DEFAULT '0',
  `express` varchar(250) DEFAULT '',
  `areas` text,
  `carriers` text,
  `enabled` int(11) DEFAULT '0',
  `merchantid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_displayorder` (`displayorder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL COMMENT '公众账号id',
  `openid` varchar(100) NOT NULL COMMENT '微信会员openID',
  `nickname` varchar(50) NOT NULL COMMENT '昵称',
  `avatar` varchar(255) NOT NULL COMMENT '头像',
  `tag` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_arealimit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL,
  `enabled` int(11) NOT NULL,
  `arealimitname` varchar(56) NOT NULL,
  `areas` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_saler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storeid` varchar(225) DEFAULT '',
  `uniacid` int(11) DEFAULT '0',
  `openid` varchar(255) DEFAULT '',
  `nickname` varchar(145) NOT NULL,
  `avatar` varchar(225) NOT NULL,
  `status` tinyint(3) DEFAULT '0',
  `merchantid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_storeid` (`storeid`),
  KEY `idx_uniacid` (`uniacid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT '0',
  `storename` varchar(255) DEFAULT '',
  `address` varchar(255) DEFAULT '',
  `tel` varchar(255) DEFAULT '',
  `lat` varchar(255) DEFAULT '',
  `lng` varchar(255) DEFAULT '',
  `status` tinyint(3) DEFAULT '0',
  `merchantid` int(11) DEFAULT '0',
  `createtime` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

//4.0
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(145) NOT NULL,
  `logo` varchar(225) NOT NULL,
  `industry` varchar(45) NOT NULL,
  `address` varchar(115) NOT NULL,
  `linkman_name` varchar(145) NOT NULL,
  `linkman_mobile` varchar(145) NOT NULL,
  `uniacid` int(11) NOT NULL,
  `createtime` varchar(115) NOT NULL,
  `thumb` varchar(255) NOT NULL,
  `detail` varchar(1222) NOT NULL,
  `salenum` int(11) NOT NULL COMMENT '商家销量',
  `open` int(11) NOT NULL COMMENT '是否分配商家权限',
  `uname` varchar(45) NOT NULL,
  `password` varchar(145) NOT NULL,
  `uid` int(11) NOT NULL COMMENT '用户id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_goods_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goodsid` int(10) DEFAULT '0',
  `title` varchar(50) DEFAULT '',
  `thumb` varchar(60) DEFAULT '',
  `productprice` decimal(10,2) DEFAULT '0.00',
  `marketprice` decimal(10,2) DEFAULT '0.00',
  `costprice` decimal(10,2) DEFAULT '0.00',
  `stock` int(11) DEFAULT '0',
  `weight` decimal(10,2) DEFAULT '0.00',
  `displayorder` int(11) DEFAULT '0',
  `specs` text,
  PRIMARY KEY (`id`),
  KEY `indx_goodsid` (`goodsid`),
  KEY `indx_displayorder` (`displayorder`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_spec` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `weid` int(10) unsigned NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `displaytype` tinyint(3) unsigned NOT NULL,
  `content` text NOT NULL,
  `goodsid` int(11) DEFAULT '0',
  `displayorder` int(11) DEFAULT '0',
  `merchantid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_spec_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weid` int(11) DEFAULT '0',
  `specid` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT '',
  `thumb` varchar(255) DEFAULT '',
  `show` int(11) DEFAULT '0',
  `displayorder` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indx_weid` (`weid`),
  KEY `indx_specid` (`specid`),
  KEY `indx_show` (`show`),
  KEY `indx_displayorder` (`displayorder`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_merchant_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchantid` int(11) NOT NULL COMMENT '商家ID',
  `uniacid` int(11) NOT NULL,
  `uid` int(11) NOT NULL COMMENT '操作员id',
  `amount` decimal(10,2) NOT NULL COMMENT '交易总金额',
  `updatetime` varchar(45) NOT NULL COMMENT '上次结算时间',
  `no_money` decimal(10,2) NOT NULL COMMENT '目前未结算金额',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;");
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_merchant_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchantid` int(11) NOT NULL COMMENT '商家id',
  `money` varchar(45) NOT NULL COMMENT '本次结算金额',
  `uid` int(11) NOT NULL COMMENT '操作员id',
  `createtime` varchar(45) NOT NULL COMMENT '结算时间',
  `uniacid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;");


if(!pdo_fieldexists('tg_goods', 'group_level')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `group_level` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'group_level_status')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `group_level_status` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'merchantid')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `merchantid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'hasoption')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `hasoption` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'share_title')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `share_title` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'share_image')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `share_image` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'share_desc')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `share_desc` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'one_limit')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `one_limit` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'many_limit')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `many_limit` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'firstdiscount')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `firstdiscount` DECIMAL( 10, 2 ) NOT NULL;");
}
if(!pdo_fieldexists('tg_group', 'merchantid')) {
	pdo_query("ALTER TABLE ".tablename('tg_group')." ADD `merchantid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_group', 'price')) {
	pdo_query("ALTER TABLE ".tablename('tg_group')." ADD `price` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'uname')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `uname` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'password')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'uid')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `uid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'open')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `open` int(2)  NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'messageopenid')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `messageopenid` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'openid')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `openid` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'goodsnum')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `goodsnum` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'merchantid')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `merchantid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'optionid')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `optionid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'optionname')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `optionname` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_refund_record', 'merchantid')) {
	pdo_query("ALTER TABLE ".tablename('tg_refund_record')." ADD `merchantid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_saler', 'merchantid')) {
	pdo_query("ALTER TABLE ".tablename('tg_saler')." ADD `merchantid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_store', 'merchantid')) {
	pdo_query("ALTER TABLE ".tablename('tg_store')." ADD `merchantid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_dispatch', 'merchantid')) {
	pdo_query("ALTER TABLE ".tablename('tg_dispatch')." ADD `merchantid` int(11)  NOT NULL;");
}
/*4.5*/
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `merchantid` int(11) NOT NULL,
  `nodes` text NOT NULL,
  `uniacid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
");
if(!pdo_fieldexists('tg_goods', 'category_childid')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `category_childid` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'category_parentid')) {
	pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `category_parentid` int(11)  NOT NULL;");
}
if(pdo_fieldexists('tg_group', 'price')) {
	pdo_query("ALTER TABLE ".tablename('tg_group')." modify column price varchar(11);");

}
/*4.6*/
if(!pdo_fieldexists('tg_order', 'issettlement')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `issettlement` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'message')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `message`  TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  '代付留言';");
}
if(!pdo_fieldexists('tg_order', 'ordertype')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `ordertype` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'othername')) {
	pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `othername`  VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_merchant', 'percent')) {
	pdo_query("ALTER TABLE ".tablename('tg_merchant')." ADD `percent` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}


/*5.0*/
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_puv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` varchar(45) NOT NULL,
  `pv` varchar(20) DEFAULT NULL COMMENT '总浏览人次',
  `uv` varchar(50) NOT NULL COMMENT '总浏览人数',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=411 DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_puv_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` varchar(45) NOT NULL,
  `openid` varchar(145) NOT NULL,
  `goodsid` int(11) NOT NULL COMMENT '商品id',
  `createtime` varchar(120) DEFAULT NULL COMMENT '访问时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_credit_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL,
  `openid` varchar(245) NOT NULL,
  `num` varchar(30) NOT NULL,
  `createtime` varchar(145) NOT NULL,
  `transid` varchar(145) NOT NULL,
  `status` int(11) NOT NULL,
  `paytype` int(2) NOT NULL COMMENT '1微信2后台',
  `ordersn` varchar(145) NOT NULL,
  `type` int(2) NOT NULL COMMENT '1积分2余额',
  `remark` varchar(145) NOT NULL,
  `table` tinyint(4) DEFAULT NULL COMMENT '1微擎2tg',
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_user_node` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT NULL,
  `name` varchar(20) NOT NULL,
  `url` varchar(300) NOT NULL,
  `do` varchar(255) NOT NULL,
  `ac` varchar(32) DEFAULT NULL,
  `op` varchar(32) DEFAULT NULL,
  `ac_id` int(11) DEFAULT NULL,
  `do_id` int(6) unsigned NOT NULL,
  `remark` varchar(255) NOT NULL,
  `displayorder` tinyint(3) unsigned NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `pid` (`do_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL,
  `key` varchar(200) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_delivery_price` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL,
  `province` varchar(12) NOT NULL,
  `city` varchar(12) NOT NULL,
  `district` varchar(12) NOT NULL,
  `first_weight` varchar(20) NOT NULL,
  `first_fee` varchar(20) NOT NULL,
  `additional_weight` varchar(20) NOT NULL,
  `additional_fee` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tid` (`template_id`),
  KEY `district` (`province`,`city`,`district`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_delivery_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `region` longtext NOT NULL,
  `data` longtext NOT NULL,
  `updatetime` int(10) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `displayorder` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_helpbuy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` varchar(45) NOT NULL,
  `name` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_coupon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_template_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `cash` varchar(20) NOT NULL,
  `is_at_least` tinyint(3) unsigned NOT NULL,
  `at_least` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `start_time` int(10) unsigned NOT NULL,
  `end_time` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `createtime` int(10) unsigned NOT NULL,
  `use_time` int(10) unsigned NOT NULL,
  `openid` varchar(100) NOT NULL,
  `uniacid` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_coupon_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '优惠券名称',
  `value` varchar(50) NOT NULL COMMENT '最小面值',
  `value_to` varchar(50) NOT NULL COMMENT '最大面值',
  `is_random` tinyint(3) unsigned NOT NULL COMMENT '是否随机',
  `is_at_least` tinyint(3) unsigned NOT NULL COMMENT '是否存在最低消费',
  `at_least` varchar(20) NOT NULL COMMENT '最低消费',
  `is_sync_weixin` tinyint(11) unsigned NOT NULL,
  `user_level` tinyint(11) unsigned DEFAULT NULL,
  `quota` tinyint(10) unsigned NOT NULL COMMENT '领取限制',
  `start_time` int(10) unsigned NOT NULL COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL COMMENT '结束时间',
  `fans_tag` int(10) unsigned NOT NULL,
  `expire_notice` tinyint(4) unsigned NOT NULL,
  `is_share` tinyint(3) unsigned NOT NULL,
  `range_type` tinyint(3) unsigned NOT NULL,
  `is_forbid_preference` tinyint(3) unsigned NOT NULL,
  `description` varchar(255) NOT NULL COMMENT '描述',
  `createtime` int(10) unsigned NOT NULL COMMENT '创建时间',
  `enable` tinyint(3) unsigned NOT NULL COMMENT '优惠券状态，1正常',
  `total` int(10) unsigned NOT NULL COMMENT '优惠券总量',
  `quantity_issue` int(10) unsigned NOT NULL,
  `quantity_used` int(10) unsigned NOT NULL COMMENT '已使用数量',
  `uid` int(10) unsigned NOT NULL,
  `uniacid` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");


if(pdo_fieldexists('tg_category', 'weid')) {
  pdo_query("ALTER TABLE".tablename('tg_category')."change weid uniacid  int(11) not null;");
}
if(pdo_fieldexists('tg_adv', 'weid')) {
  pdo_query("ALTER TABLE".tablename('tg_adv')."change weid uniacid  int(11) not null;");
}
if(pdo_fieldexists('tg_spec', 'weid')) {
  pdo_query("ALTER TABLE".tablename('tg_spec')."change weid uniacid  int(11) not null;");
}
if(pdo_fieldexists('tg_spec_item', 'weid')) {
  pdo_query("ALTER TABLE".tablename('tg_spec_item')."change weid uniacid  int(11) not null;");
}


if(!pdo_fieldexists('tg_goods', 'pv')) {
  pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `pv` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_goods', 'uv')) {
  pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `uv` int(11)  NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'successtime')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `successtime`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'adminremark')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `adminremark`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'discount_fee')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `discount_fee`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'first_fee')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `first_fee`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'couponid')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `couponid`  int(11) NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'bdeltime')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `bdeltime`  int(11) NOT NULL;");
}
if(!pdo_fieldexists('tg_group', 'successtime')) {
  pdo_query("ALTER TABLE ".tablename('tg_group')." ADD `successtime`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}
if(!pdo_fieldexists('tg_member', 'uid')) {
  pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `uid`  int(11) NOT NULL;");
}
/*5.0.4*/
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_oplog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT NULL,
  `describe` varchar(225) DEFAULT NULL COMMENT '',
  `view_url` varchar(225) DEFAULT NULL COMMENT '',
  `ip` varchar(32) DEFAULT NULL COMMENT 'IP',
  `data` varchar(1024) DEFAULT NULL COMMENT '',
  `createtime` varchar(32) DEFAULT NULL COMMENT '',
  `user` varchar(32) DEFAULT NULL COMMENT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
if(!pdo_fieldexists('tg_member', 'credit1')) {
  pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `credit1`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_member', 'credit2')) {
  pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `credit2`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_member', 'address')) {
  pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `address`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_member', 'realname')) {
  pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `realname`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_member', 'mobile')) {
  pdo_query("ALTER TABLE ".tablename('tg_member')." ADD `mobile`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_goods', 'unit')) {
  pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `unit`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
/*5.0.7*/
if(!pdo_fieldexists('tg_goods', 'goodstab')) {
  pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `goodstab`  VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_coupon', 'openid')) {
  pdo_query("ALTER TABLE ".tablename('tg_coupon')." ADD `openid`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
if(!pdo_fieldexists('tg_coupon', 'uniacid')) {
  pdo_query("ALTER TABLE ".tablename('tg_coupon')." ADD `uniacid` int(11) NOT NULL;");
}
if(!pdo_fieldexists('tg_coupon_template', 'uniacid')) {
  pdo_query("ALTER TABLE ".tablename('tg_coupon_template')." ADD `uniacid` int(11) NOT NULL;");
}
if(!pdo_fieldexists('tg_order', 'helpbuy_opneid')) {
  pdo_query("ALTER TABLE ".tablename('tg_order')." ADD `helpbuy_opneid`  VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci ;");
}
pdo_query("CREATE TABLE IF NOT EXISTS `ims_tg_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `params` longtext NOT NULL,
  `html` longtext NOT NULL,
  `click_pv` varchar(10) NOT NULL,
  `click_uv` varchar(10) NOT NULL,
  `enter_pv` varchar(10) NOT NULL,
  `enter_uv` varchar(10) NOT NULL,
  `type` tinyint(1) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  `createtime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
?>
