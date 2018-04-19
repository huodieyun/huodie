<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * paytype.ctrl
 * 支付方式控制器
 */

session_start();
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$pagetitle = '支付方式';
wl_load()->model("order");
wl_load()->model("member");
wl_load()->model("goods");
wl_load()->model('setting');
wl_load()->model('activity');
wl_load()->model('functions');
$banquanfunction=checkfunc(8170);
$orderno = $_GPC['orderno'];
$setting = setting_get_by_name("helpbuy");
$score = setting_get_by_name("score");
$order = order_get_by_params(" orderno = '{$orderno}' ");
$_SESSION['goodsid'] = $order['g_id'];

if(!empty($order['couponid']) && $order['is_usecard']==1){
	$res=coupon_handle($order['openid'],$order['couponid'],$order['goodsprice']);
	//message($res);
	if($res['errno']<0){
		
		order_update_by_params(array('pay_price'=>$order['pay_price']+$order['discount_fee'],'couponid'=>'','discount_fee'=>0,'is_usecard'=>0), array('orderno'=>$orderno));
	}
	$order = order_get_by_params(" orderno = '{$orderno}' ");
}

if($op =='display'){
	$helppay = FALSE;
    $err_status = 0;
    if ($order['tuan_id'] > 0) {
        checkpaygroup($order['tuan_id']);
    }
    if ($order['goodsprice'] <= 0){
        $err_status = 5;
    }
    if ($order['status'] != 0 && $order['status'] != 5) {
//		message("该订单已支付了.");
        $err_status = 1;
    }
    if ($order['tuan_id'] > 0) {
        $group = pdo_fetch("select lacknum,goodsid from " . tablename('tg_group') . " where groupnumber = '{$order['tuan_id']}'");
        $goods = pdo_fetch("select spikeT,spike_end,gnum,selltype,is_amount from " .tablename('tg_goods') ." where id = '{$group['goodsid']}'");
        if (!empty($goods)) {
            if ($goods['selltype'] == 4 && $order['gnum'] > $group['lacknum']){
//            message("该团已满，请勿参团！");
                $err_status = 2;
            }
            if ($goods['spikeT'] == 1 && $goods['spike_end'] < TIMESTAMP) {
//                message("非常抱歉！团购已结束");
                $err_status = 3;
            }
            if ($goods['gnum'] < 1) {
//                message("非常抱歉！库存不足");
                $err_status = 4;
            }
        }
    }
	if($order['g_id']>0)
	{
		$goods = goods_get_by_params(" id={$order['g_id']} ");
	}else{
		$goods=array();
		$goods['gname']=$order['orderno'];
		$goods['share_image'] = tomedia($config['share']['share_image']);
	}
	
	if($setting['helpbuy']==1){
		$helpbuy_message = pdo_fetch("select * from".tablename('tg_helpbuy')."where uniacid={$_W['uniacid']}  order by rand() limit 1");
		if(!empty($helpbuy_message)){
			$message=$helpbuy_message['name'];
		}else{
			$message='等待真爱路过...';
		} 
		$config['share']['share_title'] = "我想对你说:";
		$config['share']['share_desc'] = $message;
		$config['share']['share_url'] = app_url('pay/paytype', array('orderno'=>$orderno));
		$config['share']['share_image'] = $goods['gimg'];
		if($order['openid']!=$_W['openid']){
			$helppay = TRUE;
			$member = member_get_by_params(" openid='{$order['openid']}' ");
		}
	}
	include wl_template('pay/paytype');
}
if ($_W['isajax'] && $op =='ajax') {
	$res = coupon_handle($order['openid'],$order['couponid'],$order['pay_price']);
	wl_load()->model('group');
	$checkpay = $_GPC['checkpay'];
	if($checkpay=="8"){
		order_update_by_params(array('checkpay'=>$checkpay), array('orderno'=>$orderno));
		$order = order_get_by_params(" orderno = '{$orderno}' ");
	}
	$nowtuan = group_get_by_params(" groupnumber = '{$order['tuan_id']}'");
	if(!empty($nowtuan)){
		if ($nowtuan['groupstatus'] != 3) {
			die(json_encode(array('errno'=>1,'message'=>"此团已结束.","group"=>$nowtuan)));
		}
	}
	if(!empty($order['status'])){
		die(json_encode(array('errno'=>2,'message'=>"此订单已被代付.","group"=>$nowtuan)));
	}
	/*if($order['checkpay']=="9"){
		
		die(json_encode(array('errno'=>3,'message'=>"此订单正在支付,请稍等.","group"=>$nowtuan)));
	}*/
	order_update_by_params(array('message'=>$_GPC['remark'],'othername'=>$_GPC['othername'],'checkpay'=>$checkpay), array('orderno'=>$orderno));
    //积分计算
    if ($score['score_apply'] == 1 && floatval($order['score']) > 0) {
        $member = member_get_by_params(" openid='{$order['openid']}' ");
        if ($score['member_score_apply'] == 1) {
            if ($member['member_status'] == 1) {
                $record_data = array(
                    'openid' => $order['openid'],
                    'orderno' => $order['orderno'],
                    'order_status' => $order['status'],
                    'score' => $order['score'],
                    'status' => '0',
                    'created_at' => TIMESTAMP,
                    'updated_at' => TIMESTAMP,
                );
                pdo_insert('tg_score_record', $record_data);
            }
        } else {
            $record_data = array(
                'openid' => $order['openid'],
                'orderno' => $order['orderno'],
                'order_status' => $order['status'],
                'score' => $order['score'],
                'status' => '0',
                'created_at' => TIMESTAMP,
                'updated_at' => TIMESTAMP,
            );
            pdo_insert('tg_score_record', $record_data);
        }
    }
	die(json_encode(array('errno'=>0,'message'=>"支付成功.")));
}
exit();
