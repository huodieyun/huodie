<?php
defined('IN_IA') or exit('Access Denied');
$_W['page']['title'] = '进销存年度套餐';
$ops = array('buy','send','list','display','sendsms','import','output','ajax','clear','clearmobile');
$wechat=pdo_fetch("select erp_time from ".tablename('account_vip')." where uniacid=:uniacid",array(':uniacid'=>$_W['uniacid']));

include wl_template('service/jinxiaochun');
exit();
?>