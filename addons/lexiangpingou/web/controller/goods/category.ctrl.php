<?php
defined('IN_IA') or exit('Access Denied');

$ops = array('display', 'post', 'delete', 'status');
$op_names = array('分类列表','新增/修改分类','删除分类','设置开启');
foreach($ops as$key=>$value){
	permissions('do', 'ac', 'op', 'goods', 'category', $ops[$key], '商品', '分类管理', $op_names[$key]);
}

if(!pdo_fieldexists('tg_category', 'activities_type')) {
    pdo_query("ALTER TABLE ".tablename('tg_category')." ADD `activities_type` int( 2 );");
}

if(!pdo_fieldexists('tg_category', 'image')) {
    pdo_query("ALTER TABLE ".tablename('tg_category')." ADD `image` varchar(255) COMMENT '官网分类主图' ;");
}
if(!pdo_fieldexists('tg_category', 'summary')) {
    pdo_query("ALTER TABLE ".tablename('tg_category')." ADD `summary` varchar(255) COMMENT '官网分类简介' ;");
}

$op = in_array($op, $ops) ? $op : 'display';
$functions=pdo_fetchall("select * from ". tablename('tg_function')." where type=1 and tuan=0");

$isshop=0;
foreach($functions as $key=>$value){
	$fundetail=pdo_fetch("select * from ". tablename('tg_function_detail')." where uniacid = '{$_W['uniacid']}' and functionid='{$value['id']}' ");
	if(!empty($fundetail)&&$fundetail['endtime']>time())
	{
		$isshop=1;
		break;
	}
}
$uni=pdo_fetch("select * from ".tablename('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
if(($uni['vip']==1&&$uni['endtime']>time())||$uni['ordernum']>0)
{		
	$isshop=1;
}
if ($op == 'display') {
	$_W['page']['title'] = '商品分类管理 - 商品分类';
	$cateTitle = '添加';
	
	if (checksubmit('submit')) {
		if (!empty($_GPC['add_parent_name'])) {
			foreach ($_GPC['add_parent_name'] as $key => $value) {
				if (!empty($value)) {
					$insert = array(
						'name' => $value,
						
						'description' => $_GPC['add_parent_description'][$key],
						'displayorder' => $_GPC['add_parent_displayorder'][$key]
					);
					pdo_insert('tg_category', $insert);
				}
			}
		}
		if (!empty($_GPC['add_name'])) {
			foreach ($_GPC['add_name'] as $key => $value) {
				if (!empty($value)) {
					$insert = array(
						'parentid' => $_GPC['add_pid'][$key],
						'name' => $value,
						'description' => $_GPC['add_description'][$key],
						'displayorder' => $_GPC['add_displayorder'][$key]
					);
					pdo_insert('tg_category', $insert);
				}
				
			}
		}
		foreach ($_GPC['displayorder'] as $id => $displayorder) {
			$update = array(
				'displayorder' => intval($displayorder),
				'name' => $_GPC['name'][$id],
				'description' => $_GPC['description'][$id]
			);
			pdo_update('tg_category', $update, array('id' => $id));
		}
		$tip='商品分类更新成功';
		echo "<script>alert('".$tip."');location.href='".web_url('goods/category')."';</script>";	
		exit;
		//message('商品分类更新成功', 'refresh', 'success');
	}
	$category = pdo_fetch_many('tg_category', array('uniacid'=>$_W['uniacid']), array(), '', 'ORDER BY `parentid`, `displayorder` DESC, id ASC');
	$children = array();
	if (!empty($category)) {
		foreach ($category as $index => $row) {
			if (!empty($row['parentid'])){
				$children[$row['parentid']][] = $row;
				unset($category[$index]);
			}
		}
	}

} elseif ($op == 'post') {
	$cateId = intval($_GPC['id']);
	$catePid =pdo_fetch_value('tg_category', 'parentid', array('id' => $cateId));
	$faterId = intval($_GPC['fatherid']);
	$cateTitle = empty($cateId) ? '添加' : '更新';
	$_W['page']['title'] = $cateTitle . '商品分类 - 商品分类';

	if (checksubmit('submit')) {
		if (empty($_GPC['name'])) {
			message('分类名称不能为空');
		}
		$data = array(
			'uniacid'       =>$_W['uniacid'],
			'name' 			=> $_GPC['name'],
			'activities_type' 			=> $_GPC['activities_type'],
			'url' 			=> $_GPC['url'],
			'selltype' => $_GPC['selltype'],
			'description' 	=> $_GPC['description'],
			'displayorder' 	=> $_GPC['displayorder'],
			'enabled' 		=> $_GPC['enabled'],
			'show_type' 		=> $_GPC['show_type'],
			'thumb' 		=> $_GPC['thumb'],
			'smallthumb' 		=> $_GPC['smallthumb'],
            'image' 		=> $_GPC['image'],
            'summary' 		=> $_GPC['summary'],
			'parentid' 		=> $catePid
		);
//		wl_debug($data);
		if (!empty($faterId)) {
			$data['parentid'] = $faterId;
		}

		if (empty($cateId) || !empty($faterId)) {
			pdo_insert('tg_category', $data);
			$cateId = pdo_insertid();
		} else {
			pdo_update('tg_category', $data, array('id' => $cateId));
		}
		$tip='商品分类更新成功';
		echo "<script>alert('".$tip."');location.href='".web_url('goods/category/post', array('id' => $cateId))."';</script>";
		exit;
	}

	$category = array();
	if (!empty($cateId)) {
		$category = pdo_fetch_one('tg_category', array('id' => $cateId));
	}

} elseif ($op == 'delete') {
	$category_id = intval($_GPC['cateid']);
	if ($category_id) {
		$category = pdo_select_count('tg_category', array('id' => $category_id));
	}
	if (empty($category)) {
		message(error('1', '删除失败: 未指定商品分类.'));
	}
	
	$pcatetotal = pdo_select_count('tg_goods', array('category_parentid' => $category_id));
	$ccatetotal = pdo_select_count('tg_goods', array('category_childid' => $category_id));
	if ($pcatetotal + $ccatetotal > 0) {
		message(error('1', '有商品在使用该分类, 不可删除'));
	}
	$child_category_count = pdo_select_count('tg_category', array('parentid' => $category_id));
	if ($child_category_count > 0) {
		message(error('1', '该分类有子分类, 请先删除子分类'));
	}
	pdo_delete('tg_category', array('id' => $category_id));
	
	message(error('0', '成功删除!'));
	
} elseif ($op == 'status') {
	$id = intval($_GPC['id']);
	$status = intval($_GPC['status']);
	pdo_update('tg_category', array('enabled' => $status), array('id' => $id));
	
	message(error('0', '设置成功!'));
}

include wl_template('goods/category');exit();