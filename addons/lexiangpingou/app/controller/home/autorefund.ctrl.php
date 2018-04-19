<?php
error_reporting(0);

	global $_W;
	set_time_limit(0);

//抽奖团

$cjallrefund=pdo_fetchall("select orderno,openid,id,g_id,price,couponsids from ".tablename('tg_order')." where selltype=5  and status =8 and issued=1 and godluck=0  and uniacid=:uniacid limit 2",array(':uniacid'=>$_W['uniacid']));

foreach($cjallrefund as $key=>$value){
	$arr = explode(",",$value['couponsids']);
		foreach($arr as $u){
			if(intval($u)!=0)
			{			
		$coupon_template=pdo_fetch("select * from ".tablename('tg_coupon_template')." where id=".intval($u));			
		$coupon_data = array(
		'uniacid' => $coupon_template['uniacid'],
		'coupon_template_id' => $coupon_template['id'],
		'name' => $coupon_template['name'],
		'cash' => $coupon_template['value'],
		'is_at_least' => $coupon_template['is_at_least'],
		'at_least' => $coupon_template['at_least'],
		'description' => $coupon_template['description'],
		'start_time' => $coupon_template['start_time'],
		'end_time' => $coupon_template['end_time'],
		'use_time' => 0,
		'openid' => $value['openid'],
		'createtime' => TIMESTAMP
		);
			pdo_insert('tg_coupon', $coupon_data);
			}
		}
	pdo_update('tg_order', array('issued'=>2,'status'=>10), array('orderno' => $value['orderno']));
	
}

//抽奖结果通知cj_message

$cjmessage=pdo_fetchall("select g_id,id,orderno,tuan_id,openid,uniacid from ".tablename('tg_order')." where cj_message=2 and uniacid=:uniacid  limit 2",array(':uniacid'=>$_W['uniacid']));
foreach($cjmessage as $key=>$value){			
			 wl_load()->func('message');
				$url = app_url('order/order/detail', array('id' => $value['id']));
				$goods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id='{$value['g_id']}'");
				choujian_success($value['orderno'],$value['tuan_id'], $value['openid'], $goods['gname'], $url,'');
			pdo_update('tg_order', array('cj_message'=>3), array('orderno' => $value['orderno']));
									
}

//echo $vs;
//puvindex($openid);
$uniacid=$_W['uniacid'];

//送货模版消息
$sende = "select id,orderno,openid,express,expresssn from " . tablename('tg_order') . " where uniacid = {$uniacid} and sendepressmessage = 1 and dispatchtype in (0,2) order by id asc limit 2";
$sendelist = pdo_fetchall($sende);
foreach ($sendelist as $key => $value) {
    $url = app_url('order/order/detail', array('id' => $value['id']));
    send_success($value['orderno'], $value['openid'], $value['express'], $value['expresssn'], $url);
    pdo_update('tg_order', array('sendepressmessage' => 2), array('orderno' => $value['orderno']));

}

//派送模版消息
$sende = "select id,orderno,openid,express,expresssn,goodsname,storeid from " . tablename('tg_order') . " where uniacid = {$uniacid} and sendepressmessage = 1 and dispatchtype = 1 order by id asc limit 2";
$sendelist = pdo_fetchall($sende);
foreach ($sendelist as $key => $value) {
    $url = app_url('order/order/detail', array('id' => $value['id']));
    if ($value['g_id'] > 0) {
        $gname = $value['gname'];
    } else {
        $collect = pdo_fetchall("SELECT goodsname FROM " . tablename('tg_collect') . " WHERE orderno = " . $value['orderno']);
        $gname = '';
        foreach ($collect as $val) {
            $gname .= $val['goodsname'] . "\r";
        }
    }
    if ($value['storeid']) {
        dispatch_success($gname, $value['orderno'], $value['openid'], '', '', $url);
    } else {
        send_success($value['orderno'], $value['openid'], $value['express'], $value['expresssn'], $url);
    }
    pdo_update('tg_order', array('sendepressmessage' => 2), array('orderno' => $value['orderno']));

}


//未支付提醒

$cancle_time = !empty($config['base']['cancle_time'])?$config['base']['cancle_time']:5;
$noprice_time = !empty($config['base']['noprice_time'])?$config['base']['noprice_time']:1;
$now_cancle_time=TIMESTAMP-$cancle_time*3600;
$now_noprice_time=TIMESTAMP-($noprice_time*3600);
$sql = "select  createtime,orderno from " . tablename('tg_order') . " where  uniacid = {$uniacid} and status = 0 and nopricestatus=1 and `createtime`<{$now_cancle_time} order by id asc limit 1";
$list1 = pdo_fetch($sql);
pdo_update('tg_order', array('status'=>9), array('orderno' => $list1['orderno']));

$sql2 = "select  createtime,orderno,openid,g_id,pay_price,goodsname from " . tablename('tg_order') . " where  uniacid = {$uniacid} and status = 0 and  nopricestatus=0 and `createtime`<{$now_noprice_time} order by id asc limit 10";

$list2 = pdo_fetchall($sql2);
foreach($list2 as $item)
{
	pdo_update('tg_order', array('nopricestatus'=>1), array('orderno' => $item['orderno']));
	//插入未支付提醒模板消息
	$url = app_url('order/order/list',array('status'=>''));
	nopay_success($item['openid'], $item['pay_price'],$item['orderno'], $item['goodsname'],date("Y-m-d H:i:s", $item['createtime']) ,$url);

}

$group_list = pdo_fetchall("SELECT * FROM cm_tg_group WHERE groupstatus=3 AND no_num_status=1 AND no_num_success<:endtime LIMIT 20", array(':endtime' => TIMESTAMP));
foreach ($group_list as $list) {
	$order_list = pdo_fetchall('select openid from cm_tg_order where tuan_id=:tuan_id and ptime>0', array(':tuan_id' => $list['groupnumber']));
	$goods = pdo_fetch('select no_num_success from cm_tg_goods where id=:id', array(':id' => $list['goodsid']));
	foreach ($order_list as $value) {

		$remark = '点击此处邀请小伙伴参团~';
		/*if($list['selltype']==4||$list['selltype']==7)
        {
            $remark='在规定时间内凑满人数才能达到最后一个阶梯价，点击此处邀请小伙伴参团！';
        }*/
		$bdata = array(
				'uniacid' => $list['uniacid'],
				'openid' => $value['openid'],
				'goodsname' => $list['goodsname'],
				'lasttime' => $goods['no_num_success'] . '分钟',
				'groupnumber' => $list['groupnumber'],
				'neednum' => $list['lacknum'] . '人',
				'remark' => $remark
		);
		pdo_insert('tg_task', $bdata);

	}
	pdo_update('tg_group', array('no_num_status' => 0), array('id' => $list['id']));
}
	exit();
?>
	 