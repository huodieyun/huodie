<?php
/**
 * 函数getGoodsList，按检索条件检索出所有商品
 * $params : 类型：array
 * 
 */
 function activity_enable($activity_name){
	$activities = array('reward','present','cash_code');
	return in_array($activity_name, $activities);
}
 
function activity_get_list($args = array()) {
	global $_W;
	$usepage = !empty($args['usepage'])? $args['usepage'] : false;
	$page = !empty($args['page'])? intval($args['page']): 1;
	$pagesize = !empty($args['pagesize'])? intval($args['pagesize']): 10;
	$orderby = !empty($args['orderby'])? $args['orderby'] : 'order by id desc';
	
	$condition = ' and `uniacid` = :uniacid';
	$params = array(':uniacid' => $_W['uniacid']);
	if ($usepage) {
		$sql = "SELECT * FROM " . tablename('tg_cash_code_template') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
	} else {
		$sql = "SELECT * FROM " . tablename('tg_cash_code_template') . " where 1 {$condition} ";
	} 
	$list = pdo_fetchall($sql, $params);
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('tg_cash_code_template')."where 1 $condition ",$params);
	$data = array();
	$data['list'] = $list;
	$data['total'] = $total;
	return $data;
} 

function coupon_template_all() {
	global $_W;
	$coupon_template_all = pdo_fetch_many('tg_cash_code_template', array('uniacid' => $_W['uniacid']), array(), 'id', 'ORDER BY `id` DESC');
	cache_write($cache_key, $coupon_template_all);
	return $coupon_template_all;
}


function coupon_template($coupon_template_id) {
	$coupon_template_all = coupon_template_all();
	return $coupon_template_all[$coupon_template_id];
}

function coupon_check($openid, $coupon_template_id){
	global $_W;
	$tatal = pdo_select_count('tg_cash_code',array('openid' => $openid, 'coupon_template_id' => $coupon_template_id, 'uniacid' => $_W['uniacid']));
	return $tatal;
}

//拼团优惠券
function coupon_goodscanuse($openid, $payprice,$goodsid){
	global $_W;
	$where3 = "SELECT * FROM ".tablename('tg_cash_code').' WHERE `openid` = :openid AND `use_time` = :use_time AND `start_time` < :now1 AND `end_time` > :now2  and goodsid in(0,:goodsid)  ORDER BY `end_time` DESC ';
	$params3 = array(
		':openid' => $openid,
		':goodsid' => $goodsid,
		':use_time' => 0,
		':now1' => TIMESTAMP,
		':now2' => TIMESTAMP
	);
	$coupon3 = pdo_fetchall($where3, $params3);
	
	if(empty($coupon3)){
		return;
	}
	foreach ($coupon3 as $key3 => $value3) {
		$ps=pdo_fetch('select enable from '.tablename('tg_cash_code_template') .' where id=:id',array(':id'=>$value3['coupon_template_id']));
		if($ps['enable']==0)
		{
			unset($coupon3[$key3]);
			continue;
		}
		if($value3['cash'] > $payprice){
			unset($coupon3[$key3]);
			continue;
		}
		if($value3['is_at_least'] == 2 && $value3['at_least'] > $payprice){
		
			unset($coupon3[$key3]);
			continue;
		}
		$coupon3[$key3]['end_time'] = date('Y-m-d', $value3['end_time']);
	}
	if($coupon3){
		$i=0;
		foreach($coupon3 as $key=>$value){
			$coupon[$i] = $value;
			$i+=1;
		}
	}
	
	return $coupon;
}
//拼团优惠券  当前可用优惠券
function coupon_canuses($openid, $payprice){
	global $_W;
	$where3 = "SELECT * FROM ".tablename('tg_cash_code').' WHERE `openid` = :openid AND `use_time` = :use_time AND `start_time` < :now1 AND `end_time` > :now2 and `at_least`<=:at_least and `cash`<:at_least and `is_at_least` =2  ORDER BY `cash` DESC limit 1';
	$params3 = array(
		':openid' => $openid,
		':use_time' => 0,
		':at_least' => $payprice,
		':now1' => TIMESTAMP,
		':now2' => TIMESTAMP
	);
	$coupon3 = pdo_fetchall($where3, $params3);
	

	return $coupon3;
}
//拼团优惠券
function coupon_canuse($openid, $payprice){
	global $_W;
	$where3 = "SELECT * FROM ".tablename('tg_cash_code').' WHERE `openid` = :openid AND `use_time` = :use_time AND `start_time` < :now1 AND `end_time` > :now2  ORDER BY `cash` DESC ';
	$params3 = array(
		':openid' => $openid,
		':use_time' => 0,
		':now1' => TIMESTAMP,
		':now2' => TIMESTAMP
	);
	$coupon3 = pdo_fetchall($where3, $params3);
	
	if(empty($coupon3)){
		return;
	}
	foreach ($coupon3 as $key3 => $value3) {
		if($value3['cash'] > $payprice){
			unset($coupon3[$key3]);
			continue;
		}
		$ps=pdo_fetch('select enable from '.tablename('tg_cash_code_template') .' where id=:id',array(':id'=>$value3['coupon_template_id']));
		if($ps['enable']==0)
		{
			unset($coupon3[$key3]);
			continue;
		}
		if($value3['is_at_least'] == 2 && $value3['at_least'] > $payprice){
		
			unset($coupon3[$key3]);
			continue;
		}
		$coupon3[$key3]['end_time'] = date('Y-m-d', $value3['end_time']);
	}
	if($coupon3){
		$i=0;
		foreach($coupon3 as $key=>$value){
			$coupon[$i] = $value;
			$i+=1;
		}
	}
	
	return $coupon;
}
function coupon_grant($openid, $coupon_template_id,$code){
	global $_W;
	if (!activity_enable('coupon')) {
		return error(1, '优惠券未开启');
	}
	$coupon_template = coupon_template($coupon_template_id);
	$tatal = coupon_check($openid, $coupon_template_id);
	if (empty($coupon_template)) {
		return error(1, '优惠券不存在或已删除');
	}
	if ($coupon_template['quota'] > 0 && $tatal >= $coupon_template['quota']) {
		return error(1, '超过领取数量，小调皮不要贪心哦');
	}
	if ($coupon_template['total'] <= $coupon_template['quantity_issue']) {
		return error(1, '优惠券已发完');
	}
	if ($coupon_template['enable'] != 1) {
		return error(1, '商家停止发放优惠券');
	}
	if ($coupon_template['is_random'] == 2) {
		$cash = $coupon_template['value'] + mt_rand() / mt_getrandmax() * ($coupon_template['value_to'] - $coupon_template['value']);

		//$cash = mt_rand($coupon_template['value'], $coupon_template['value_to']);
	} else {
		$cash = $coupon_template['value'];
	}
	$coupon_data = array(
		'uniacid' => $_W['uniacid'],
		'coupon_template_id' => $coupon_template['id'],
		'name' => $coupon_template['name'],
		'cash' => $cash,
		'is_at_least' => $coupon_template['is_at_least'],
		'at_least' => $coupon_template['at_least'],
		'description' => $coupon_template['description'],
		'start_time' => $coupon_template['start_time'],
		'goodsid' => $coupon_template['goodsid'],
		'end_time' => $coupon_template['end_time'],
		'use_time' => 0,
		'openid' => $openid,
		'createtime' => TIMESTAMP
	);
    pdo_update('tg_cash_code_qrcode',['is_used'=>2],['template_id'=>$coupon_template['id'],'code'=>$code]);
	pdo_insert('tg_cash_code', $coupon_data);
	$coupon_data['id'] = pdo_insertid();
	
	coupon_quantity_issue_increase($coupon_template['id'], 1);
	
	return $coupon_data;
}


function coupon($coupon_id){
	$coupon = pdo_fetch_one('tg_cash_code', array('id' => $coupon_id));
	return $coupon;
}


function coupon_quantity_issue_increase($coupon_template_id, $quantity) {
	$sql = 'UPDATE '.tablename('tg_cash_code_template').' SET `quantity_issue` = `quantity_issue` + :quantity WHERE id=:id';
	$params = array(
		':id' => $coupon_template_id,
		':quantity' => $quantity
	);
	pdo_query($sql, $params);
	
	return true;
}


function coupon_quantity_used_increase($coupon_template_id, $quantity) {
	$sql = 'UPDATE '.tablename('tg_cash_code_template').' SET `quantity_used`=`quantity_used`+:quantity WHERE id=:id';
	$params = array(
		':id' => $coupon_template_id,
		':quantity' => $quantity
	);
	pdo_query($sql, $params);
	
	return true;
}


function coupon_handle($openid, $coupon_id, $payprice) {
	$errmsg = '无法使用优惠券: ';
	if (!activity_enable('cash_code')) {
		return error(-1, $errmsg.'现金券功能未启用');
	}
	$coupon = coupon($coupon_id);
	if (empty($coupon) || $coupon['openid'] != $openid) {
		return error(-6, $errmsg.'不存在或已删除');
	}
	if ($coupon['start_time'] > TIMESTAMP ) {
		return error(-4, $errmsg.'未生效');
	}
	if ($coupon['end_time'] < TIMESTAMP) {
		return error(-3, $errmsg.'已失效');
	}
	if ($coupon['use_time'] != 0) {
		return error(-2, $errmsg.'已使用');
	}
	if ($coupon['is_at_least'] == 2 && intval($payprice)< intval($coupon['at_least'])) {
		
		return error(-5, $errmsg.'不满足使用条件,商品总价应达到'.currency_format($coupon['at_least']).'元');
	}
	return $coupon['cash'];
}
