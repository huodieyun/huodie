<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/20
 * Time: 9:50
 */
global $_W,$_GPC;
$since_store = pdo_fetchall("select id,storename from cm_tg_store WHERE `uniacid` = :uniacid", array(':uniacid' => $_W['uniacid']));
include wl_template('data/new_since_data');
exit();