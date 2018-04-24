<?php
/**
 * [weliam] Copyright (c) 2016/4/4
 * 优惠券
 */

defined('IN_IA') or exit('Access Denied');
$ops = array('list', 'create', 'edit', 'disable','createQrcode');
$op_names = array('现金券列表', '添加现金券', '编辑现金券', '使现金券失效');
foreach($ops as$key=>$value){
	permissions('do', 'ac', 'op', 'application', 'activity', $ops[$key], '应用与营销', '现金券', $op_names[$key]);
}
$do = in_array($op, $ops) ? $op : 'list';
wl_load()->model('cash_code');
wl_load()->classs('qrcode');
if ($do == 'list') {
	$_W['page']['title'] = '应用和营销  - 现金券列表';
	$opp=$_GPC['opp'];
	if (empty($_GPC['opp'])) {
		$opp = 'on';
	}
	$where = " WHERE uniacid = {$_W['uniacid']}";
	$params = array();
	$keyword = trim($_GPC['keyword']);
	if (!empty($keyword)) {
		$where .= " AND name LIKE :name";
		$params[':name'] = "%{$keyword}%";
	}
	if ($opp == 'future') {
		$where .= " AND start_time > :time AND enable = :enable";
		$params[':time'] = TIMESTAMP;
		$params[':enable'] = 1;
	} elseif ($opp == 'on') {
		$where .= " AND start_time < :time AND end_time > :time AND enable = :enable";
		$params[':time'] = TIMESTAMP;
		$params[':enable'] = 1;
	} elseif ($opp == 'end') {
		$where .= " AND enable = :enable";
		$params[':enable'] = 0;
	}
	if($opp == 'record'){
        //获取核销记录
        $record_size = 10;
        $record_page = !empty($_GPC['page'])?$_GPC['page']:1;
        $record_con = '';
        if($_GPC['code']){
            $code = $_GPC['code'];
            $record_con .= ' and a.code like "%'.$code.'%"';
        }
        if($_GPC['cash']){
            $cash = $_GPC['cash'];
            $record_con .= ' and a.cash='.$cash;
        }
        if($_GPC['name']){
            $name = $_GPC['name'];
            $record_con .= ' and a.name like "%'.$name.'%"';
        }

        $record = pdo_fetchall("select a.*,b.nickname from cm_tg_cash_code a LEFT JOIN cm_tg_member b on a.openid=b.openid AND a.uniacid=b.uniacid WHERE a.uniacid={$_W['uniacid']} {$record_con} order by use_time DESC limit ".($record_page-1)*$record_size." , " . $record_size);
        $total = pdo_fetchall("select a.* from cm_tg_cash_code a WHERE a.uniacid={$_W['uniacid']} {$record_con}");
        $record_total = count($total);
        $record_pager = pagination($record_total, $record_page, $record_size);
	}
	$size = 10;
	$page = !empty($_GPC['page'])?$_GPC['page']:1;
	$sql = "select * from".tablename('tg_cash_code_template')." $where LIMIT " . ($page - 1) * $size . " , " . $size;
//	print_r($sql);
//	print_r($params);
	$tg_coupon_templates = pdo_fetchall($sql,$params);
//	print_r($tg_coupon_templates);
//	die;
	$total = pdo_fetchall("select id from".tablename('tg_cash_code_template')." $where " ,$params);
	$total = count($total);

	$sql = "SELECT `coupon_template_id`, COUNT(DISTINCT `uid`) as 'count_receive_person', COUNT('id') as 'count_receive_num' FROM " .tablename('tg_cash_code'). " GROUP BY `coupon_template_id`";
	$coupon_count = pdo_fetchall($sql, array(), 'coupon_template_id');
	foreach ($tg_coupon_templates as &$tg_coupon_template) {
		if ($tg_coupon_template['end_time'] < TIMESTAMP) {
			pdo_update('tg_coupon_template', array('enable' => 0), array('id' => $tg_coupon_template['id']));
		}
		$totalused=pdo_fetchall("select id from ".tablename('tg_cash_code').' where coupon_template_id=:id and use_time>0',array(':id'=>$tg_coupon_template['id']));
		$totalallused=pdo_fetchall("select id from ".tablename('tg_cash_code').' where coupon_template_id=:id',array(':id'=>$tg_coupon_template['id']));

		$tg_coupon_template['stock'] = $tg_coupon_template['total'] - $tg_coupon_template['quantity_issue'];
		$tg_coupon_template['count_receive_num'] = count($totalallused);
		$tg_coupon_template['count_receive_person'] = count($totalused);
	}
	$pager = pagination($total, $page, $size);
	include wl_template('goods/cash_code/cash_code_template_list');
}


if ($do == 'create' || $do == 'edit') {
//	$allgoods = pdo_fetchall("select gname,id from".tablename('tg_goods')."where uniacid=:uniacid ",array(':uniacid'=>$_W['uniacid']));
	$insert = $do == 'create';
	$_W['page']['title'] = ' 现金券';
	$id = intval($_GPC['id']);
	if ($id) {
		$coupon_template = coupon_template($id);
		if (empty($coupon_template)) {
			$tip='非法访问：访问记录不存在';
		echo "<script>alert('".$tip."');location.href='".url('goods/cash_code/list')."';</script>";
			exit;
		}
        $storesids = explode(",", $coupon_template['store_id']);
        foreach ($storesids as $key => $value) {
            if ($value) {
                $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
            }
        }

	}
//	//获取门店列表
//    $stores = pdo_fetchall("select * from cm_tg_store where uniacid={$_W['uniacid']} and status = 1");
//    $stores = json_encode($stores);


	if (checksubmit('submit')) {
	    $store = $_GPC['storeids'];

	    $str_store = implode(',',$store);
		$tg_coupon_template_data = array(
			'name' => trim($_GPC['name']),
			'total' => intval(trim($_GPC['total'])),
			'value' => currency_format($_GPC['value']),
			'value_to' => currency_format($_GPC['value_to']),
			'is_random' => $_GPC['is_random'] ? $_GPC['is_random'] : 0,
			'is_at_least' => $_GPC['is_at_least'],
			'at_least' => currency_format($_GPC['at_least']),
			'user_level' => $_GPC['user_level'],
			'quota' => intval($_GPC['quota']),
			'goodsid' => intval($_GPC['goodsid2']),
			'start_time' => strtotime($_GPC['start_time'] . ' 00:00:00'),
			'end_time' => strtotime($_GPC['end_time'] . ' 23:59:59'),
			'range_type' => $_GPC['range_type'],
			'description' => trim($_GPC['description']),
			'createtime' => TIMESTAMP,
			'enable' => 1,
			'uid' => $_W['uid'],
			'uniacid' => $_W['uniacid'],
			'score' => $_GPC['score'],
            'store_id' => $str_store
		);
		// wl_debug($tg_coupon_template_data);
		if (empty($id)) {
			pdo_insert('tg_cash_code_template', $tg_coupon_template_data);
			$id = pdo_insertid();
		} else {
			if ($coupon_template['enable'] == 0) {
				$tip='已失效不可编辑';
				echo "<script>alert('".$tip."');window.location.reload(true);</script>";

				//message('已失效不可编辑', referer(), 'warning');
			}
			$tg_coupon_template_update = array(
				'name' => trim($_GPC['name']),
				'total' => intval(trim($_GPC['total'])),
				'description' => trim($_GPC['description']),
				'score' => $_GPC['score'],
                'store_id' => $str_store
			);
			pdo_update('tg_cash_code_template', $tg_coupon_template_update, array('id' => $id));
		}
		message($insert ? '添加成功' : '编辑成功', web_url('goods/cash_code/cash_code_template/edit', array('id' => $id)), 'success');
	}

	if (!$insert) {
		$coupon_template_id = intval($_GPC['id']);
		$coupon = coupon_template($tg_coupon_template_id);
	}

	include wl_template('goods/cash_code/cash_code_template_edit');
}

if ($do == 'disable') {
	$coupon_template_id = $_GPC['id'];
	$coupon_template = coupon_template($coupon_template_id);

	if (empty($coupon_template)) {
		message(error(1,'现金券不存在或已删除'));
	}
	pdo_update('tg_cash_code_template', array('enable' => 0), array('id' => $coupon_template_id));
	message(error(0, '处理失效成功!'));
}

if ($do == 'createQrcode') {
    $template_id = $_GPC['template_id'];
    $num = $_GPC['num'];
    $url = $_GPC['url'];
    web_app_url('member/cash_code/get',array('id'=>$template_id));
    $qr = new creat_qrcode();
    for ($i=0;$i<$num;$i++){
        $data['template_id'] = $template_id;
        $data['uniacid'] = $_W['uniacid'];
        //生成二维码
        $code = $template_id. rand(10000, 99999) . time().$i;
        $data['code'] = $code;
        $content = $url.'&code='.$code;
        $qrcode = $qr->createCashCodeQrcode($content,$template_id);

        $files[$i]['url'] = $qrcode['url'];
        $files[$i]['name'] = $qrcode['name'];
        if(!pdo_insert('tg_cash_code_qrcode', $data)){
            message(error(0, '生成二维码失败!'));
        }
    }
    //压缩文件
    $zipurl = $qr->zipCashCodeQrcode($files,$template_id);

    $data = array(
        'qrcode_url' => $zipurl,
        'have_qrcode' =>1
    );
    pdo_update('tg_cash_code_template',$data,['id'=>$template_id]);
    message(error(0, $zipurl));

}
if ($do == 'get_all') {
//	ajax_only();
//	$sql = "SELECT `id`, `name` FROM " . tablename('tg_coupon_template') . " WHERE `enable` = :enable AND `end_time` > :this_time ORDER BY `id` DESC";
//	$tg_coupon_templates = pdo_fetchall($sql, array(':enable' => ON, ':this_time' => TIMESTAMP), 'id');
//	if (empty($tg_coupon_templates)) {
//		message(error(1,'无可用优惠券'));
//	}
//	message($tg_coupon_templates);
}
exit();
