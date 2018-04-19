<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 商品详情控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('order');
wl_load()->model('account');
wl_load()->model('activity');
wl_load()->model('setting');
load()->func('communication');
wl_load()->model('functions');
$openid=$_W['openid'];
$pagetitle = '补交尾款';
$master_order=order_get_by_params(" orderno = '{$_GPC['master_orderno']}'");
$master_orderdetail=order_get_by_params(" master_orderno = '{$master_order['orderno']}'");
if($master_order['status']!=1&&$master_orderdetail['status']==3)
{
	echo "<script>  location.href='" . app_url('pay/success',array('orderno'=>$master_orderdetail['orderno'])) . "';</script>";
	exit ;
}
//$order=order
$group=pdo_fetch('select * from '.tablename('tg_group').' where groupnumber=:groupnumber and uniacid=:uniacid',array(':groupnumber'=>$master_order['tuan_id'],':uniacid'=>$_W['uniacid']));
$param_level = unserialize($group['group_level']);
$allnum=$group['nownum'];
for($i=0;$i<count($param_level)-1;$i++){
	for($j=0;$j<count($param_level)-$i-1;$j++){
		if($param_level[$j]['groupnum']>$param_level[$j+1]['groupnum']){
			$temp=$param_level[$j];
			$param_level[$j] = $param_level[$j+1];
			$param_level[$j+1]= $temp;
		}
	}
}

for($i=0;$i<count($param_level)-1;$i++){

	if($param_level[$i]['groupnum']<=$allnum&&$param_level[$i+1]['groupnum']>$allnum)
	{
		$tempprice=$param_level[$i]['groupprice'];		
		break;
	}
	
}
if($param_level[0]['groupnum']>$allnum)
{
	$goods=pdo_fetch('select oprice from '.tablename('tg_goods').' where id=:id',array(':id'=>$master_order['g_id']));
	$tempprice=$goods['oprice'];

}
if($param_level[count($param_level)-1]['groupnum']<=$allnum)
{
	$tempprice=$param_level[count($param_level)-1]['groupprice'];

}
$pay_price =sprintf("%.2f", $master_order['gnum'] * $tempprice-$master_order['price'] + round($master_order['freight'],2));
if($group['groupnumber']=='447197')
{
	$pay_price=sprintf("%.2f", '26.9');
}

	if ($_W['isajax']) {


	$data = array(
		'uniacid'     => $_W['uniacid'],
		'gnum'        => 1,
		'openid'      => $_W['openid'],
        'ptime'       => '',//支付成功时间
		'orderno'     => date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99)),
		'pay_price'   => $pay_price,
		'goodsprice'  => '',
		'goodsname'		=>$master_order['goodsname'],
		'freight'     => '',
		'first_fee'   => '',
		'status'      => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
		'addressid'   => '',
		'addresstype' => '',//1公司2家庭
		'dispatchtype'=> '',//配送方式
		'comadd'	  =>'',//
		'addname'     => '',
		'mobile'      =>'',
		'address'     => '',
		'g_id'        => $master_order['g_id'],
		'tuan_id'     => '',
		'is_tuan'     => '',
		'tuan_first'  => '',
		'starttime'   => TIMESTAMP,
		'senddate'   => '',
		'sendtime'=>'',
		'remark'      => '',
		'comtype'     =>'',
		'commission' =>'',
		'commissiontype' =>'',
		'endtime'     =>'',
		'is_hexiao'   =>'',
		'hexiaoma'    =>'',
		'selltype'    => $master_order['selltype'],
		'credits'     =>'',
		'optionname'  => '',
		'optionid'    =>'',
		'couponid'    =>'',
		'is_usecard'  => '',
		'discount_fee'  => '',
		'createtime'  => TIMESTAMP,
		'bdeltime'    => '',
		'issued'    => '',
		'couponsids'    =>'',
		'master_orderno'  =>$master_order['orderno']
	);

	if(empty($master_orderdetail))
	{
		pdo_insert('tg_order', $data);
		$params['orderno'] = $data['orderno'];
	}else{
		$params['orderno'] = $master_orderdetail['orderno'];
		$pay_log=pdo_fetch('select * from '.tablename('core_paylog').'where tid=:tid',array(':tid'=>$master_orderdetail['orderno']));
		$moduleid = pdo_fetchcolumn("SELECT mid FROM ".tablename('modules')." WHERE name = :name", array(':name' => $pay_log['module']));
		$moduleid = empty($moduleid) ? '000000' : sprintf("%06d", $moduleid);
		pdo_update('core_paylog',array('uniontid'=>date('YmdHis').$moduleid.random(8,1)),array('tid'=>$master_orderdetail['orderno']));
	}
	$params['pay_price'] =strval($pay_price) ;

	wl_json(1,$params);

}
include wl_template('order/master_orderconfirm');
exit();