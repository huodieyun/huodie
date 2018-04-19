<?php
/**
 * [weliam] Copyright (c) 2016/4/4
 * 优惠券
 */

defined('IN_IA') or exit('Access Denied');
$ops = array('group', 'create', 'edit', 'disable');
$op_names = array('优惠券列表', '添加优惠券', '编辑优惠券', '使优惠券失效');
foreach($ops as$key=>$value){
	permissions('do', 'ac', 'op', 'application', 'activity', $ops[$key], '应用与营销', '优惠券', $op_names[$key]);
}
if(empty($_GPC['op']))
{
	$op='group';
}
$do = in_array($op, $ops) ? $op : 'group';
wl_load()->model('activity');
if ($do == 'group') {
	$_W['page']['title'] = '组优惠券';
	$opp=$_GPC['opp'];
	if (empty($_GPC['opp'])) {
		$opp = 'all';
	}
	$where = " WHERE uniacid = {$_W['uniacid']}";
	$params = array();
	$keyword = trim($_GPC['keyword']);
	if (!empty($keyword)) {
		$where .= " AND name LIKE :name";
		$params[':name'] = "%{$keyword}%";
	}

	$size = 10;
	$page = !empty($_GPC['page'])?$_GPC['page']:1;
	$sql = "select * from".tablename('tg_coupon_group')." $where LIMIT " . ($page - 1) * $size . " , " . $size;
	$tg_coupon_templates = pdo_fetchall($sql,$params);

	$total = pdo_fetchall("select id from".tablename('tg_coupon_group')." WHERE uniacid = {$_W['uniacid']}");
	$total = count($total);


	$pager = pagination($total, $page, $size);
	include wl_template('goods/activity/coupon_group_list');
}
if ($do == 'create' || $do == 'edit') {
	$allgoods = pdo_fetchall("select gname,id from".tablename('tg_goods')."where uniacid=:uniacid ",array(':uniacid'=>$_W['uniacid']));
	$insert = $do == 'create';
	$_W['page']['title'] = ' 组优惠券';
	$id = intval($_GPC['id']);
	if ($id) {
		$coupon_template = pdo_fetch('select * from '.tablename('tg_coupon_group').' where id=:id',array(':id'=>$id));

	}
	$couponlist=pdo_fetchall("select * from ".tablename('tg_coupon_template')." where uniacid=".$_W['uniacid']." and end_time>".TIMESTAMP." and start_time<".TIMESTAMP." and enable=1");

	if (checksubmit('submit')) {

		$tg_coupon_template_data = array(
			'name' => trim($_GPC['name']),
			'couponsids' => $_GPC['functionValue'],

			'createtime' => TIMESTAMP,

			'uniacid' => $_W['uniacid']
		);
//		wl_debug($tg_coupon_template_data);
		if (empty($id)) {
			pdo_insert('tg_coupon_group', $tg_coupon_template_data);
			$id = pdo_insertid();
		} else {
			pdo_update('tg_coupon_group', $tg_coupon_template_data, array('id' => $id));
		}
		message('编辑成功', web_url('goods/coupon/group'), 'success');
	}



	include wl_template('goods/activity/coupon_group_edit');
}

exit();

