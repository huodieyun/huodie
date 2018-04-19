<?php
/**
 * Created by ป๐ต๛.
 * User: ย์าฯ
 * Date: 2017/3/31
 * Time: 15:12
 */
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and enabled=1 and parentid=0 ORDER BY parentid ASC, displayorder DESC");


include wl_template('goods/fenlei1');
exit();