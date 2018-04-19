<?php 
load()->func('tpl');
global $_W, $_GPC;
$kwd = trim($_GPC['keyword']);
$params = array();
$params[':uniacid'] = $_W['uniacid'];
$condition = " and uniacid=:uniacid";
if (!empty($kwd)) {
	$condition .= " AND ( `nickname` LIKE :keyword )";
	$params[':keyword'] = "%{$kwd}%";
}
$ds = pdo_fetchall('SELECT id,avatar,nickname,from_user FROM ' . tablename('tg_member') . " WHERE 1 {$condition} order by id desc", $params);
include wl_template('store/query');exit();