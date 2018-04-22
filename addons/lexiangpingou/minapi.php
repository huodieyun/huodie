<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
load()->model('reply');
load()->app('common');
load()->classs('wesession');

if($op=='goods_rand'){
	$list=pdo_fetchall('select id,gname,gprice,share_image,gimg from '.tablename('tg_goods').' where uniacid=:uniacid and isshow=1 order by rand() limit 9',array(':uniacid'=>$_GPC['uniacid']));
	foreach ($list as $key => $value) {
		if(!empty($value['share_image']))
		{
			$list[$key]['gimg']= tomedia($value['share_image']);
		}
		else{
			$list[$key]['gimg']= tomedia($value['gimg']);
		}
	}
	$data = array();
	$data['list'] = $list;
	$data['status'] = false;
	if($config['base']['guess']==1)
	{
		$data['status'] = true;
	}
	
	
	die(json_encode($data));
	//echo json_encode($list);
}

if($op=='goods_list')
{
	$page = $_GPC['page'];
	$pagesize = $_GPC['pagesize'];
	$cid = intval($_GPC['cid']);
	$wechats = pdo_fetch("select tpl from " . tablename('account_wechats') . " where uniacid = '{$_GPC['uniacid']}'");
	$func = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$wechats['tpl']}'");
	
	$condition = ' and `uniacid` = :uniacid';
	$params = array(':uniacid' => $_GPC['uniacid']);
	if($cid>0)
	{
		$condition .= "  AND fk_typeid = '{$cid}'";
	}
	$condition.=' and selltype=1 and isshow=1';
	$orderby = 'order by id desc';	
	$page = !empty($_GPC['page'])? intval($_GPC['page']): 1;
	$pagesize = !empty($_GPC['pagesize'])? intval($_GPC['pagesize']): 10;
	$sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
	$list=pdo_fetchall($sql,$params);

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('tg_goods')."where 1 $condition ",$params);
		
		$data = array();
		$data['list'] = $list;
		$data['total'] = $total;
	foreach ($data['list'] as $key => &$value) {
		$value['deliverytype']=strval(intval($value['deliverytype']));
		if(empty($value['share_image']))
			{
				$value['share_image'] = tomedia($value['gimg']);
			}else{
				$value['share_image'] = tomedia($value['share_image']);
			}
			
			$value['gimg'] = tomedia($value['gimg']);
		$advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$value['id']}'");
		foreach ($advs as &$adv) {
			$adv['thumb']=tomedia($adv['thumb']);
			
		}
		$data['list'][$key]['advs'] = $advs;
		$params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') .  "WHERE goodsid = '{$value['id']}' limit 0,3 ");
		$data['list'][$key]['params'] = $params;
		//规格及规格项
	$options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
	$data['list'][$key]['options']=count($options);
	$old_data=pdo_fetch("select * from ".tablename('tg_goods_openid').' where uniacid=:uniacid and openid=:openid and g_id=:g_id',array(':uniacid'=>$_GPC['uniacid'],':openid'=>$_GPC['openid'],':g_id'=>$value['id']));
	$data['list'][$key]['history_limit'] =intval($old_data['num']);
	}
	$goodses = $data;
	die(json_encode($goodses));
	//die($sql);
}
//幻灯片
if($op=='banner')
{
	$advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_GPC['uniacid']}' order by displayorder DESC");
	foreach ($advs as &$adv) {
		$adv['thumb']=tomedia($adv['thumb']);
		if (substr($adv['link'], 0, 5) != 'http:') {
			$adv['link'] = "http://" . $adv['link'];
		}
	}
die(json_encode($advs));
}
//分类
if($op=='category')
{
	$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_GPC['uniacid']}' and enabled=1 and parentid=0 ORDER BY parentid ASC, displayorder DESC");
	foreach ($category as &$adv) {
		$adv['thumb']=tomedia($adv['thumb']);
		$adv['smallthumb']=tomedia($adv['smallthumb']);
	}
	die(json_encode($category));
}
//商品详情
if($op=='goods_detail'){
$id=intval($_GPC['id']);
//$goods = goods_get_by_params("id = {$id}");
$goods=pdo_fetch("select * from ".tablename('tg_goods')." where id='{$id}'");
$goods['gimg']=tomedia($goods['gimg']);
	$goods['deliverytype']=strval(intval($goods['deliverytype']));
//得到图集
	$advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$id}'");
    foreach ($advs as &$adv) {
		$adv['thumb']=tomedia($adv['thumb']);
    	if (substr($adv['link'], 0, 5) != 'http:') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
	$goods['advs']=$advs;
	$params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') .  "WHERE goodsid = '{$id}' ");
	$goods['params']=$params;
die(json_encode($goods));
}
/*
 * 申请兼职
 */
if($op=='update_member'){
	pdo_update('tg_member',array('name'=>$_GPC['name'],'mobile'=>$_GPC['mobile'],'type'=>1,'addtime'=>TIMESTAMP,'weixinnumber'=>$_GPC['weixinnumber'],'shopname'=>$_GPC['shopname']),array('id'=>$_GPC['id']));
	die(json_encode(array('status'=>1)));
}
if($op=='test')
{

	/*
	$logdata=array(
		'orderno'=>'openid',
		'log'=>$_GPC['openid'],
		'from'=>''
		);
		pdo_insert('tg_log', $logdata);*/
		die(json_encode(array('ip'=>getClientIP())));
}
if($op=='sendtime')
{
	$ad=$_GPC['ad'];
	$uniacid=$_GPC['uniacid'];
	$sendtimes=pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid ='{$uniacid}'  and status=1 order by starttime asc");
	$hour=0;
	if($ad=='今天')
	{
		$dtime=date('Y-m-d');
		$hour=date('H');
	}elseif($ad=='明天'){$dtime=time() + (1 * 24 * 60 * 60); }
	elseif($ad=='后天'){$dtime=time() + (2 * 24 * 60 * 60);}
	$ttime=array();
	foreach ($sendtimes as $k => $v) {
		$valtime=$v['starttime'].":00-".$v['endtime'].":59";
		$psql1 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' and sendtime='{$valtime}' and senddate='{$dtime}' and status not in (0,4,5,9)");
		$numa1=count($psql1);

		if($v['total']>$numa1&&$v['starttime']>$hour)
		{
			$ttime[$k]['name']=$valtime;
		}

	}
	die(json_encode($ttime));
}
if($op=='store'){
	$uniacid=$_GPC['uniacid'];
	$gid=$_GPC['gid'];
	$goods=pdo_fetch('select hexiao_id from '.tablename('tg_goods').' where uniacid=:uniacid and id=:id',array(':uniacid'=>$uniacid,':id'=>$gid));
	$storesids = explode(",", $goods['hexiao_id']);
	foreach($storesids as$key=>$value){
		if($value){
			$stores[$key] =  pdo_fetch("select * from".tablename('tg_store')."where id ='{$value}' and uniacid='{$uniacid}'");
		}
	}
	die(json_encode($stores));
}
if($op=='address')
{
	$uniacid=$_GPC['uniacid'];
	$openid=$_GPC['openid'];
	$address=pdo_fetch('select * from '.tablename('tg_address').' where uniacid=:uniacid and openid=:openid and status=1',array(':uniacid'=>$uniacid,':openid'=>$openid));
	die(json_encode($address));
}
if($op=='addresslist')
{
	$uniacid=$_GPC['uniacid'];
	$openid=$_GPC['openid'];
	$address=pdo_fetchall('select * from '.tablename('tg_address').' where uniacid=:uniacid and openid=:openid ',array(':uniacid'=>$uniacid,':openid'=>$openid));
	die(json_encode($address));
}
if($op=='address_del'){
	$id=$_GPC['id'];
	pdo_delete('tg_address',array('id' => $id ));
	die(json_encode(array('status'=>1)));
}
if($op=='address_edit')
{
	$id=$_GPC['id'];
	$uniacid=$_GPC['uniacid'];
	$openid=$_GPC['openid'];
	if(!empty($id)){
		$address=pdo_fetch('select * from '.tablename('tg_address').' where uniacid=:uniacid and id=:id',array(':uniacid'=>$uniacid,':id'=>$id));
	}
	$data=array(
			'openid'           => $openid,
			'uniacid'          => $uniacid,
			'cname'            => $_GPC['myname'],
			'tel'              => $_GPC['myphone'],
			'province'         => $_GPC['province'],
			'city'             => $_GPC['city'],
			'county'           => $_GPC['county'],
			'detailed_address' => $_GPC['detailed'],
			'type'             => $_GPC['ctype'],
			'ctype'             => 1,
			'status'             => $_GPC['status'],
			'addtime'          => TIMESTAMP
	);
	if($_GPC['status']==1)
	{
		pdo_update('tg_address',array('status' => 0),array('status' =>1,'openid'=>$openid));
	}

	if(!empty($id)){
		pdo_update('tg_address',$data,array('id' => $id));
	}else{
		pdo_insert('tg_address',$data);
	}
	die(json_encode(array('status'=>1)));
}
if($op=='order_corfim')
{
	$goods=pdo_fetch('select * from '.tablename('tg_goods').'where id=:id',array(':id'=>$_GPC['g_id']));
	$tuan_id=$_GPC['tuan_id'];
	$data = array(
			'uniacid'     => $_GPC['uniacid'],//公众号ID
			'gnum'        => $_GPC['gnum'],//购买数量
			'openid'      => $_GPC['openid'],//openid
			'ptime'       => '',//支付成功时间
			'orderno'     => $_GPC['orderno'],//订单编号
			'pay_price'   => $_GPC['pay_price'],//支付金额（商品金额+运费-优惠金额-团长优惠）
			'goodsprice'  => $_GPC['goodsprice'],//商品金额（商品单价*购买数量）
			'goodsname'		=>$goods['gname'],//商品名称
			'freight'     => $_GPC['freight'],//运费
			'first_fee'   => $_GPC['firstdiscount'],//团长优惠
			'status'      => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
			'addressid'   => $_GPC['addressid'],
			'addresstype' => $_GPC['addresstype'],//1公司2家庭
			'dispatchtype'=> $_GPC['dispatchtype'],//配送方式
			'comadd'	  =>$_GPC['store_id'],//
			'addname'     => $_GPC['cname'],
			'mobile'      => $_GPC['tel'],
			'address'     => $_GPC['address'],
			'g_id'        => $_GPC['g_id'],
			'tuan_id'     => $_GPC['tuan_id'],
			'is_tuan'     => $_GPC['is_tuan'],//拼团为1，其它类型为0
			'tuan_first'  => '',
			'starttime'   => TIMESTAMP,
			'senddate'   => $_GPC['senddate'],//送货日期
			'sendtime'=>$_GPC['sendtime'],//送货时间
			'remark'      => $_GPC['remark'],//订单备注
			'comtype'     =>0,
			'commission' => '',//佣金金额
			'commissiontype' => '',//佣金计算类型
			'is_hexiao'   => 0,
			'selltype'    => $goods['selltype'],//团购类型
			'credits'     => '',//积分
			'optionname'  => $_GPC['optionname'],//规格
			'optionid'    => $_GPC['$optionid'],//规格ID
			'couponid'    => $_GPC['$couponid'],//优惠券ID
			'is_usecard'  => $_GPC['is_usecard'],//使用优惠券为1，不使用为0
			'discount_fee'  => $_GPC['discount_fee'],//优惠券优惠金额
			'createtime'  => TIMESTAMP,
			'bdeltime'    => '',
			'issued'    => 0,
			'origin'    => $_GPC['origin'],
			'couponsids'    => $goods['couponsids']
	);
	pdo_insert('tg_order', $data);
	$orderid = pdo_insertid();
	if($_GPC['dispatchtype']==3){
		pdo_update('tg_member',array('addname' => $_GPC['cname'],'addmobile'=>$_GPC['tel']), array('openid' => $_GPC['openid'],'uniacid'=>$_GPC['uniacid']));

	}
/*
	if($typeid == 2 || $typeid==4){

		wl_load()->classs('qrcode');
		$createqrcode =  new creat_qrcode();
		$createqrcode->creategroupQrcode($data['orderno']);
	}
*/
	if(empty($tuan_id)){
		$groupnumber = $orderid;
		if($data['is_tuan']==1&&$orderid!=0){
			$starttime = time();
			$endtime = $starttime + $goods['endtime']*3600;
			$selltype=$goods['selltype'];
			if($selltype==7)
			{
				$goods['on_success']=1;
			}
			$data2 = array(
					'uniacid'     => $_GPC['uniacid'],
					'groupnumber' => $groupnumber,
					'groupstatus' => 3,//订单状态,1组团失败，2组团成功，3,组团中
					'goodsid'     => $goods['id'],
					'goodsname'   => $goods['gname'],
					'neednum'     => $goods['groupnum'],
					'lacknum'     => $goods['groupnum'],
					'starttime'   => $starttime,
					'selltype'    => $goods['selltype'],
					'endtime'     => $endtime,
					'on_success'     => $goods['on_success'],
					'price'       => $goods['gprice'],
					'group_level' =>  $goods['group_level']
			);
			pdo_insert('tg_group', $data2);
			pdo_update('tg_order',array('tuan_id' => $orderid), array('id' => $orderid));
		}
	}
	die(json_encode(array('status'=>1)));
}
if($op=='check_payresult')
{
	$orderno=$_GPC['orderno'];
	$transid=$_GPC['transid'];
	$fee=$_GPC['fee'];
	$order_out=pdo_fetch('select * from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));
	if(!empty($transid))
	{
		//插入paylog
		$paylog_data=array(
			'tid'=>$orderno,
			'uniontid'=>$orderno,
			'tag'=>$transid,
			'type'=>'wechat',
			'uniacid'=>$order_out['uniacid'],
			'openid'=>$order_out['openid']
		);
		pdo_insert('core_paylog', $paylog_data);
		/*按订单量使用 减少订单量*/
		$acct = pdo_fetch("select * from " . tablename('account_wechats') . " where  uniacid =:uniacid",array(':uniacid'=>$order_out['uniacid']));
		if($acct['vip']==0&&$acct['ordernum']>0)
		{
			pdo_update('account_wechats',array('ordernum'=>$acct['ordernum']-1), array('uniacid' =>$order_out['uniacid']));
		}
		/*按订单量使用 减少订单量*/
		pdo_update('tg_order',array('status' => 1,'pay_type'=>2,'transid'=>$transid,'ptime'=>TIMESTAMP,'price'=>$fee), array('orderno' => $orderno));
		//组团逻辑
		if($order_out['is_tuan']==1)
		{
			$group=pdo_fetch('select * from '.tablename('tg_group').' where groupnumber=:tuan_id',array(':tuan_id'=>$order_out['tuan_id']));
			$nowtuannum=pdo_fetchcolumn('select count(id) from '.tablename('tg_order').' where tuan_id=:tuan_id and status in(1,2,3,8)',array(':tuan_id'=>$order_out['tuan_id']));
			if($group['neednum']>$nowtuannum){
				pdo_update('tg_group', array('lacknum' => $group['neednum'] - $nowtuannum,'nownum'=>$nowtuannum), array('groupnumber' => $order_out['tuan_id']));
			}
			if($group['neednum']==$nowtuannum)
			{
				pdo_update('tg_order',array('status'=>8),array('tuan_id'=>$order_out['tuan_id'],'status'=>1));
				pdo_update('tg_group',array('groupstatus'=>2,'lacknum' => $group['neednum'] - $nowtuannum,'nownum'=>$nowtuannum),array('groupnumber'=>$order_out['tuan_id']));
			}
			if($group['neednum']<$nowtuannum)
			{
				pdo_update('tg_order',array('status'=>10),array('orderno'=>$orderno));
				pdo_update('tg_group',array('groupstatus'=>2),array('groupnumber'=>$order_out['tuan_id']));

			}
			$tuan_id=$order_out['tuan_id'];
		}else{
			$tuan_id='';
			//单买逻辑
			pdo_update('tg_order',array('status'=>8),array('orderno'=>$order_out['orderno']));
		}
		if($order_out['g_id']>0)
		{
			$goodsInfo=pdo_fetch('select * from '.tablename('tg_goods').'where id=:id',array(':id'=>$order_out['g_id']));
			if ($goodsInfo['gnum'] >=$order_out['gnum']) {
				pdo_update('tg_goods',array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
			}elseif(!empty($goodsInfo['gnum'])){
				pdo_update('tg_goods',array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
			}
		}


		/*增加历史购买数量*/
		$old_data=pdo_fetch("select * from ".tablename('tg_goods_openid').' where uniacid=:uniacid and openid=:openid and g_id=:g_id',array(':uniacid'=>$order_out['uniacid'],':openid'=>$order_out['openid'],':g_id'=>$order_out['g_id']));
		if(empty($old_data))
		{
			$logdata=array(
					'g_id'=>$order_out['g_id'],
					'openid'=>$order_out['openid'],
					'num'=>$order_out['gnum'],
					'uniacid'=>$order_out['uniacid']
			);
			pdo_insert('tg_goods_openid', $logdata);
		}else{
			pdo_update('tg_goods_openid', array('num' => $old_data['num'] +$order_out['gnum']), array('id' => $old_data['id']));

		}
		/*增加历史购买数量*/
		die(json_encode(array('status'=>1,'tuan_id'=>$tuan_id,'order_id'=>$order_out['id'])));
	}
	die(json_encode(array('status'=>0)));
}
if($op=='order_detail')
{
	$openid=$_GPC['openid'];
	$id=$_GPC['id'];
	$order=pdo_fetch('select * from '.tablename('tg_order').' where openid=:openid and id=:id',array(':openid'=>$openid,':id'=>$id));
	$order['createtime']=date('Y-m-d H:i:s',$order['createtime']);
	if(!empty($order['ptime']))
	{
		$order['ptime']=date('Y-m-d H:i:s',$order['ptime']);
	}
	if(!empty($order['hexiaotime']))
	{
		$order['hexiaotime']=date('Y-m-d H:i:s',$order['hexiaotime']);
	}else{
		$order['hexiaotime']='';
	}
	$order['salename']='';
	$order['checkstore']='';
	if(!empty($order['veropenid']))
	{
		$saler=pdo_fetch('select * from '.tablename('tg_saler').' where openid=:openid',array(':openid'=>$order['veropenid']));
		$order['salename']=$saler['nickname'];
		$checkstore=pdo_fetch('select * from '.tablename('tg_store'). ' where id=:id',array(':id'=>$order['checkstore']));
		$order['checkstore']=$checkstore['storename'];
	}
	die(json_encode($order));

}
if($op=='group_detail'){
	$tuan_id=$_GPC['tuan_id'];
	$order_list=pdo_fetchall('select openid,address,addname,createtime from '.tablename('tg_order').' where tuan_id=:tuan_id and status in (1,2,3,4,5,6,7,8,10)',array(':tuan_id'=>$tuan_id));
	$member_list=array();
	$i=0;
	foreach($order_list as $key =>$value){
		$i+=1;
		$member_list[$key]['xuhao']=$i;
		$member_list[$key]['create_date']=date('Y-m-d',$value['createtime']);
		$member_list[$key]['create_time']=date('H:i:s',$value['createtime']);
		$member_list[$key]['openid']=$value['openid'];
		if($value['address']=='虚拟'){
			$member_list[$key]['avatar'] = str_replace("..","https://min.lexiangpingou.cn",$value['openid']);
			$member_list[$key]['nickname'] =  $value['addname'];
		}else{
			$fans = pdo_fetch('select avatar,nickname from '.tablename('tg_member')." where openid = '{$value['openid']}'");
			if (!empty($fans)) {
				$avatar = $fans['avatar'];
				$nickname=$fans['nickname'];
			}
			$member_list[$key]['avatar'] = $avatar;
			$member_list[$key]['nickname'] = $nickname;
		}
	}
	$group=pdo_fetch('select * from '.tablename('tg_group').'where groupnumber=:groupnumber',array(':groupnumber'=>$tuan_id));
	if($group['selltype']!=7)
	{
		$group['nownum']=$group['neednum']-$group['lacknum'];
	}
	$goods=pdo_fetch('select gimg,unit,deliverytype from '.tablename('tg_goods').'where id=:id',array(':id'=>$group['goodsid']));
	$group['img']=tomedia($goods['gimg']);
	$group['unit']=$goods['unit'];
	$group['deliverytype']=intval($goods['deliverytype']);
	$list=array();
	$list['member_list']=$member_list;
	$list['group']=$group;
	die(json_encode($list));
}
if($op=='order_list'){

	$page = $_GPC['page'];
	$pagesize = $_GPC['pagesize'];
	$status=$_GPC['status'];
	$condition = ' and `uniacid` = :uniacid and openid=:openid';
	$params = array(':uniacid' => $_GPC['uniacid'],':openid' => $_GPC['openid']);
	if($_GPC['status']>=0)
	{
		$condition .= "  AND status = '{$status}'";
	}
	$orderby = 'order by id desc';
	$page = !empty($_GPC['page'])? intval($_GPC['page']): 1;
	$pagesize = !empty($_GPC['pagesize'])? intval($_GPC['pagesize']): 10;
	$sql = "SELECT * FROM " . tablename('tg_order') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
	$list=pdo_fetchall($sql,$params);

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('tg_order')."where 1 $condition ",$params);

	$data = array();
	$data['condition'] = $condition;
	$data['list'] = $list;
	$data['total'] = $total;

	die(json_encode($data));
	//die($sql);

}
if($op=='group_list'){

	$page = $_GPC['page'];
	$pagesize = $_GPC['pagesize'];
	$status=$_GPC['status'];
	$condition = ' and `uniacid` = :uniacid and openid=:openid and is_tuan=1';
	$params = array(':uniacid' => $_GPC['uniacid'],':openid' => $_GPC['openid']);
	if($_GPC['status']>=0)
	{
		$condition .= "  AND tuan_id in (select groupnumber from cm_tg_group where `uniacid` = :uniacid and groupstatus = {$status})";
	}
	$condition.=' group by tuan_id';
	$orderby = 'order by id desc';
	$page = !empty($_GPC['page'])? intval($_GPC['page']): 1;
	$pagesize = !empty($_GPC['pagesize'])? intval($_GPC['pagesize']): 10;
	$sql = "SELECT * FROM " . tablename('tg_order') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
	$list=pdo_fetchall($sql,$params);

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('tg_order')."where 1 $condition ",$params);
	foreach ($list as $key => $order) {
		$goods = pdo_fetch("SELECT * FROM ".tablename('tg_goods')."WHERE id = '{$order['g_id']}'");
		$thistuan = pdo_fetch("SELECT * FROM ".tablename('tg_group')."WHERE groupnumber = '{$order['tuan_id']}' $content");
		$orders[$key]['groupnumber'] = $order['tuan_id'];
			$orders[$key]['groupnum'] = $goods['groupnum'];
			if(!empty($thistuan['price'])){
				$orders[$key]['gprice'] = $thistuan['price'];
			}else{
				$orders[$key]['gprice'] = $goods['gprice'];
			}
			$orders[$key]['unit'] = $goods['unit'];
			$orders[$key]['gid'] = $goods['id'];
			$orders[$key]['gname'] = $goods['gname'];
			$orders[$key]['gimg'] = tomedia($goods['gimg']);
			$orders[$key]['itemnum'] = $thistuan['lacknum'];
			$orders[$key]['groupstatus'] = $thistuan['groupstatus'];
			$orders[$key]['starttime']=date('Y-m-d H:i:s',$thistuan['starttime']);

	}
	$data = array();
	$data['list'] = $orders;
	$data['total'] = $total;
	die(json_encode($data));
	//die($sql);

}
if($op=='cancal'){
	$orderno=$_GPC['orderno'];
	pdo_update('tg_order',array('status' => 9),array('status' =>0,'orderno'=>$orderno));
	//$order=pdo_fetch('select status from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));
	$data=array('status'=>1);
	die(json_encode($data));
}
if($op=='freight')
{
	$uniacid=$_GPC['uniacid'];
	$tid=$_GPC['tid'];
	$p = $_GPC['province'];
	$c = $_GPC['city'];
	$d = $_GPC['county'];
	$weight=$_GPC['weight'];
	$freight=0;
	$province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
	$city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
	$district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");
	if(!empty($province_fee['first_fee'])){

		$free = sprintf("%.2f",$province_fee['free']);
		$first_fee = sprintf("%.2f",$province_fee['first_fee']);
		$first_weight = sprintf("%.2f",$province_fee['first_weight']);
		$second_fee = sprintf("%.2f",$province_fee['second_fee']);
		$second_weight = sprintf("%.2f",$province_fee['second_weight']);
	}
	if(!empty($city_fee['first_fee'])){

		$free = sprintf("%.2f",$city_fee['free']);
		$first_fee = sprintf("%.2f",$city_fee['first_fee']);
		$first_weight = sprintf("%.2f",$city_fee['first_weight']);
		$second_fee = sprintf("%.2f",$city_fee['second_fee']);
		$second_weight = sprintf("%.2f",$city_fee['second_weight']);
	}
	if(!empty($district_fee['first_fee'])){
		$free = sprintf("%.2f",$district_fee['free']);
		$first_fee = sprintf("%.2f",$district_fee['first_fee']);
		$first_weight = sprintf("%.2f",$district_fee['first_weight']);
		$second_fee = sprintf("%.2f",$district_fee['second_fee']);
		$second_weight = sprintf("%.2f",$district_fee['second_weight']);
	}
	if($weight>$first_weight)
	{
		$freight=sprintf("%.2f",$first_fee+($weight-$first_weight)/$second_weight*$second_fee);
	}else{$freight=$first_fee;}
	die(json_encode(array('status'=>1,'freight'=>$freight)));
}
if($op=='dispatch_list')
{
	$id=$_GPC['g_id'];
	$goods=pdo_fetch('select uniacid,yunfei_id from '.tablename('tg_goods').'where id=:id',array(':id'=>$id));
	$yunfeiids = explode(",", $goods['yunfei_id']);
	foreach($yunfeiids as$key=>$value){
		if($value){
			$dispatch_list[$key] =  pdo_fetch("select id,name from".tablename('tg_delivery_template')."where id ='{$value}' and uniacid='{$goods['uniacid']}' and status=2");
		}
	}
	$status=1;
	if(empty($dispatch_list))
	{
		$status=2;
	}
	$list=array();
	$list['status']=$status;
	$list['data']=$dispatch_list;
	die(json_encode($list));
	//die(json_encode($dispatch_list));
}
if($op=='check_member'){
	$openid=$_GPC['openid'];
	$uniacid=$_GPC['uniacid'];
	$fans = pdo_fetch('select avatar,nickname from '.tablename('tg_member')." where openid = :openid and uniacid=:uniacid",array(':uniacid'=>$uniacid,':openid'=>$openid));
	if(empty($fans))
	{
		$data=array(
			'uniacid'=>$uniacid,
			'nickname'=>$_GPC['nickname'],
			'avatar'=>$_GPC['avatar'],
			'openid'=>$_GPC['openid'],
			'from_user'=>$_GPC['openid'],
			'addtime'=>TIMESTAMP,
			'parentid'=>99
		);
		pdo_insert('tg_member', $data);
	}
	$fans = pdo_fetch('select * from '.tablename('tg_member')." where openid = :openid and uniacid=:uniacid",array(':uniacid'=>$uniacid,':openid'=>$openid));

	die(json_encode(array('status'=>1,'member'=>$fans)));

}
if($op=='goods_guige'){
	$id=$_GPC['id'];
	//规格及规格项
	$allspecs = pdo_fetchall("select title,id from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $id));
	foreach ($allspecs as &$s) {
		$s['items'] = pdo_fetchall("select title,thumb from " . tablename('tg_spec_item') . " where  `show`=1 and specid=:specid order by displayorder asc", array(":specid" => $s['id']));
		foreach ($s['items'] as &$t) {
			$t['thumb']=tomedia($t['thumb']);
		}
		$s['count']=count($s['items']);
	}

	//unset($s);
	$options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,stock,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $id));
	$specs = array();
	$specs['allspecs']=$allspecs;
	$specs['options']=$options;
	die(json_encode($specs));
}
if($op=='storelist'){
	global $_GPC;
	$uniacid=$_GPC['uniacid'];
	$g_id=$_GPC['g_id'];

	$goods = pdo_fetch("select hexiao_id from".tablename('tg_goods')."where id = '{$g_id}' and uniacid='{$uniacid}'");

	if(!empty($goods['hexiao_id']))
	{
		$store_ids = explode(',',substr($goods['hexiao_id'],0,strlen($goods['hexiao_id'])-1));
		$storelist=array();
		foreach($store_ids as $key=> $value){
			$store=pdo_fetch('select * from '.tablename('tg_store').' where id=:id',array(':id'=>$value));
			$storelist[$key]=$store;
		}
		die(json_encode($storelist));
	}else{
		die(json_encode(array('status'=>0)));
	}

}
function getClientIP()  
{  
    global $ip;  
    if (getenv("HTTP_CLIENT_IP"))  
        $ip = getenv("HTTP_CLIENT_IP");  
    else if(getenv("HTTP_X_FORWARDED_FOR"))  
        $ip = getenv("HTTP_X_FORWARDED_FOR");  
    else if(getenv("REMOTE_ADDR"))  
        $ip = getenv("REMOTE_ADDR");  
    else $ip = "Unknow";  
    return $ip;  
}
//订单数据自动统计
if($op=='run_data_order'){
	global $_GPC;
	$day=intval($_GPC['day']);
	$now=TIMESTAMP-60*60*24*$day;
	$sql="SELECT COUNT(id) as num,uniacid from cm_tg_puv_record WHERE  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') group by uniacid ";
	$data_list=pdo_fetchall($sql);

	 foreach($data_list as $item)
	 {
		 $base_data=pdo_fetch('select id from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d')",array(':uniacid'=>$item['uniacid']));
		 $sql="SELECT SUM(pay_price) as total_price,uniacid,COUNT(id) as order_num,SUM(price)/COUNT(id) as customer_price  from cm_tg_order WHERE uniacid=:uniacid and  date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and mobile<>'虚拟'";
		 $list=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));

		   //查询今日总订单量
		 $sql="SELECT COUNT(id) as order_num  from cm_tg_order WHERE  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid and mobile<>'虚拟'";
		 $order_num=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
		 //查询今日开团总数
		 $sql="SELECT COUNT(id) as order_num  from cm_tg_group WHERE  date_format(from_UNIXTIME(`starttime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid";
		 $group_total=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
		 $sql="SELECT COUNT(id) as order_num  from cm_tg_group WHERE  date_format(from_UNIXTIME(`successtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid and groupstatus=2";
		 $group_success=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
		 $sql="SELECT COUNT(id) as order_num  from cm_tg_group WHERE  date_format(from_UNIXTIME(`endtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid and groupstatus=1";
		 $group_fail=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
		 //查询成交金额
		 $sql="SELECT SUM(pay_price) as total_price  from cm_tg_order WHERE  date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid and status in (2,3,8) and mobile<>'虚拟'";
		 $success_price=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
		 //查询退款数据
		 $sql="SELECT SUM(refundfee) as total_price,COUNT(id) as num from cm_tg_refund_record WHERE  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid and status=1";
		 $refund_price=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));

		 //查询pv
		// $sql="SELECT COUNT(id) as num from cm_tg_puv_record WHERE  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid ";
		// $pv=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
		 //查询uv
		 $sql="SELECT id from cm_tg_puv_record WHERE  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid group by openid ";
		 $uv=pdo_fetchall($sql,array(':uniacid'=>$item['uniacid']));
		 //查询新增粉丝
		 $sql="SELECT COUNT(*) as num from cm_mc_mapping_fans WHERE  date_format(from_UNIXTIME(`followtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid  ";
		 $fans=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
		 $data=array(
			 'uniacid'=>$item['uniacid'],
			 'addtime'=>$now,
			 'order_total'=>$order_num['order_num'],
			 'order_pay'=>$list['order_num'],
			 'pay_price'=>$list['total_price'],
			 'refund_price'=>$refund_price['total_price'],
			 'order_refund'=>$refund_price['num'],
			 'pv'=>$item['num'],
			 'uv'=>count($uv),
			 'add_fans'=>$fans['num'],
			 'group_total'=>$group_total['order_num'],
			 'group_success'=>$group_success['order_num'],
			 'group_fail'=>$group_fail['order_num'],
			 'success_price'=>$success_price['total_price'],
			 'customer_price'=>$list['customer_price']
		 );
		 if(empty($base_data)){
			 pdo_insert('tg_data_base',$data);
		 }else{
			 pdo_update('tg_data_base',$data,array('id'=>$base_data['id']));
		 }
	 }
	$list=array();
	$list['now']=$now;
	$list['data']=$data_list;
	die(json_encode($list));
}
if($op=='order_recive_update')
{

}
//自动签收
if($op=='order_recive'){
	$allnogettime = pdo_fetchall("select id,orderno,openid,g_id,commission,cost_fee,price,commissiontype,sendtime,status,uniacid,gnum from ".tablename('tg_order')." where    comtype=0 and status=3 order by comtype asc  limit 50");


	foreach($allnogettime as $key =>$value){

			//积分
			if($value['g_id']>0){
				$goodsInfo = pdo_fetch("SELECT credit FROM " . tablename('tg_goods') . " WHERE uniacid ='{$value['uniacid']}' and id = '{$value['g_id']}'");
				$fans = pdo_fetch("SELECT uid FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$value['uniacid']}' and openid = '{$value['openid']}'");
				$mencredit=pdo_fetch("SELECT credit1 FROM " . tablename('mc_members') . " WHERE uniacid ='{$value['uniacid']}' and uid = '{$fans['uid']}'");
				$creditnum=$mencredit['credit1']+$goodsInfo['credit'];
				pdo_update('mc_members',array('credit1'=>$creditnum),array('uid'=>$fans['uid']));
				//佣金
				$ud = pdo_fetch("select parentid from".tablename('tg_member')."where  uniacid='{$value['uniacid']}' and from_user='{$value['openid']}'");
				if(intval($ud['parentid'])>0)
				{
					$parent=pdo_fetch("select billing,wallet,id,from_user from".tablename('tg_member')."where  uniacid='{$value['uniacid']}' and id={$ud['parentid']}");
					if($value['commissiontype']==2)
					{
						$price1=$value['commission']*$value['gnum'];
					}
					else{
						$price1=($value['price']-$value['cost_fee'])*$value['commission']*$value['gnum']/100;//佣金计算
					}
					$billing=$parent['billing']+$price1;//已结算佣金
					$wallet=$parent['wallet']+$price1;//钱包总佣金
					//$nobilling=$parent['nobilling']-$price;//未结算佣金
					pdo_update('tg_member',array('billing'=>$billing,'wallet'=>$wallet),array('id'=>$parent['id']));
					//佣金结算记录
					$bdata = array(
							'uniacid' => $value['uniacid'],
							'openid' => $parent['from_user'],
							'orderno' => $value['orderno'],
							'billdate' => TIMESTAMP,
							'price' => $price1
					);
					pdo_insert('tg_billrecord', $bdata);

				}
				pdo_update('tg_order',array('comtype'=>1),array('id'=>$value['id']));
//
			}
			if($value['g_id']==0)
			{

				$favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='{$value['uniacid']}'   and orderno='{$value['orderno']}'");
				$fans = pdo_fetch("SELECT uid FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$value['uniacid']}' and openid = '{$values['openid']}'");
				$mencredit=pdo_fetch("SELECT credit1 FROM " . tablename('mc_members') . " WHERE uniacid ='{$value['uniacid']}' and uid = '{$fans['uid']}'");
				$creditnum=$mencredit['credit1']+$goodsInfo['credit'];
				$price1=0;
				foreach ($favoriteqqq as $key => $orderss)
				{
					$gs= pdo_fetch("select * from ".tablename('tg_goods')."where uniacid='{$value['uniacid']}' and id={$orderss['sid']} ");
					if(!empty($gs['credit'])&&$gs['credit']!=0)
					{
						$creditnum+=$gs['credit']*$orderss['num'];
					}
					if($gs['commissiontype']==2){$price1+=$gs['commission']*$orderss['num'];}
					else{
						$price1+=($orderss['oprice']*$orderss['num'])*$orderss['commission']/100;//佣金计算
					}
				}
				pdo_update('mc_members',array('credit1'=>$creditnum),array('uid'=>$fans['uid']));
				//积分
				//佣金
				$ud = pdo_fetch("select parentid from".tablename('tg_member')."where  uniacid='{$value['uniacid']}' and from_user='{$value['openid']}'");
				if(intval($ud['parentid'])>0)
				{
					$parent=pdo_fetch("select billing,wallet,id,from_user from".tablename('tg_member')."where  uniacid='{$value['uniacid']}' and id={$ud['parentid']}");

					$billing=$parent['billing']+$price1;//已结算佣金
					$wallet=$parent['wallet']+$price1;//钱包总佣金
					//$nobilling=$parent['nobilling']-$price;//未结算佣金
					pdo_update('tg_member',array('billing'=>$billing,'wallet'=>$wallet),array('id'=>$parent['id']));
					//佣金结算记录
					$bdata = array(
							'uniacid' => $value['uniacid'],
							'openid' => $parent['from_user'],
							'orderno' => $value['orderno'],
							'billdate' => TIMESTAMP,
							'price' => $price1
					);
					pdo_insert('tg_billrecord', $bdata);

				}
				pdo_update('tg_order',array('comtype'=>1),array('id'=>$value['id']));

			}

	}
	exit();
}
//组团人数不足，发送模板消息
/*OPENTM406281958
 * {{first.DATA}}
团购商品：{{keyword1.DATA}}
剩余拼团时间：{{keyword2.DATA}}
剩余拼团人数：{{keyword3.DATA}}
{{remark.DATA}}
*/

if($op=='group_status3')
{

	$group_list=pdo_fetchall("select * from cm_tg_group where groupstatus=3 and no_num_status=1 and no_num_success<:endtime limit 20",array(':endtime'=>TIMESTAMP));
	foreach($group_list as $list){
		$order_list=pdo_fetchall('select openid from cm_tg_order where tuan_id=:tuan_id and ptime>0',array(':tuan_id'=>$list['groupnumber']));
		$goods=pdo_fetch('select no_num_success from cm_tg_goods where id=:id',array(':id'=>$list['goodsid']));
		foreach($order_list as $value)
		{

			$remark='点击此处邀请小伙伴参团~';
			/*if($list['selltype']==4||$list['selltype']==7)
			{
				$remark='在规定时间内凑满人数才能达到最后一个阶梯价，点击此处邀请小伙伴参团！';
			}*/
			$bdata = array(
					'uniacid' => $list['uniacid'],
					'openid' => $value['openid'],
					'goodsname' => $list['goodsname'],
					'lasttime' => $goods['no_num_success'].'分钟',
					'groupnumber' => $list['groupnumber'],
					'neednum' => $list['lacknum'].'人',
					'remark'=>$remark
			);
			pdo_insert('tg_task', $bdata);

		}
		pdo_update('tg_group',array('no_num_status'=>0),array('id'=>$list['id']));
	}
	die(json_encode(array('nowtime'=>$nowtime,'count'=>count($group_list))));

}
if($op=='time_test'){
	echo strtotime("+10 minute",1522111801);
	exit;
}
//成团人数对比
/*
 * 接口地址:http://www.lexiangpingou.cn/minapi.php?op=group_num_data&uniacid=259
 * uniacid 为当前公众号ID
 */
if($op=='group_num_data')
{
	$uniacid=$_GPC['uniacid'];
	$data_list=pdo_fetchall('SELECT count(*) as total ,neednum FROM cm_tg_group where uniacid=:uniacid and groupstatus=2 group by neednum',array(':uniacid'=>$uniacid));
	$total=0;
	foreach($data_list as &$item) {
		if($item['neednum']==1000000000){
			$item['neednum']='定金团';
		}
		$total+=$item['total'];
	}
	$status=2;
	if(empty($data_list))
	{
		$status=1;
	}
	$list=array();
	$list['status']=$status;
	$list['data']=$data_list;
	$list['total']=$total;
	die(json_encode($list));
}
/*
 * 购买模式对比
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=buy_model_data&uniacid=259
 */
if($op=='buy_model_data')
{
	$uniacid=$_GPC['uniacid'];
	$data_list=pdo_fetchall('SELECT count(*) as total ,SUM(price) as total_price ,selltype FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8)  and ptime>0 and mobile<>\'虚拟\' group by selltype',array(':uniacid'=>$uniacid));
	$total=0;
	foreach($data_list as &$item) {

		switch(intval($item['selltype'])){
			case 1:
				$item['selltype']='随意团';break;
			case 2:
				$item['selltype']='邻购团';break;
			case 3:
				$item['selltype']='自选团';break;
			case 4:
				$item['selltype']='阶梯团';break;
			case 5:
				$item['selltype']='抽奖团';break;
			case 6:
				$item['selltype']='新专团';break;
			case 7:
				$item['selltype']='订金团';break;
			default:
				$item['selltype']='购物车';break;

		}
		$total+=$item['total'];

	}
	$status=2;
	if(empty($data_list))
	{
		$status=1;
	}
	$list=array();
	$list['status']=$status;
	$list['data']=$data_list;
	$list['total']=$total;
	die(json_encode($list));
}
/*
 * 门店核销对比
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=store_check_data&uniacid=259
 */
if($op=='store_check_data')
{
	$uniacid=$_GPC['uniacid'];
	$data_list=pdo_fetchall('SELECT count(*) as total ,checkstore FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and dispatchtype=3 and checkstore<>"" and mobile<>"虚拟" group by checkstore',array(':uniacid'=>$uniacid));
	$total=0;
	foreach($data_list as &$item) {
		$store=pdo_fetch('select storename from cm_tg_store where id=:id',array(':id'=>$item['checkstore']));
		$item['storename']=$store['storename'];
		$total+=$item['total'];
	}
	$status=2;
	if(empty($data_list))
	{
		$status=1;
	}
	$list=array();
	$list['status']=$status;
	$list['data']=$data_list;
	$list['total']=$total;
	die(json_encode($list));
}
/*
 * 配送方式对比
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=dispatch_data&uniacid=259
 */
if($op=='dispatch_data')
{
	$uniacid=$_GPC['uniacid'];
	$data_list=pdo_fetchall('SELECT count(*) as total ,dispatchtype FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and mobile<>"虚拟"  group by dispatchtype',array(':uniacid'=>$uniacid));
	$total=0;
	foreach($data_list as &$item) {
		switch($item['dispatchtype']){
			case 1:
				$item['dispatchtype']='送货上门';break;
			case 2:
				$item['dispatchtype']='快递';break;
			case 3:
				$item['dispatchtype']='自提';break;
			default:
				$item['selltype']='无';break;

		}
		$total+=$item['total'];
	}
	$status=2;
	if(empty($data_list))
	{
		$status=1;
	}
	$list=array();
	$list['status']=$status;
	$list['data']=$data_list;
	$list['total']=$total;
	die(json_encode($list));
}
/*
 * 下单门店核销门店准确率
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=store_real_data&uniacid=259
 * store_order:下单门店
 * store_real_order:核销门店
 */
if($op=='store_real_data')
{
	$uniacid=$_GPC['uniacid'];
	$data_list=pdo_fetchall('SELECT id,storename FROM `cm_tg_store` where uniacid=:uniacid',array(':uniacid'=>$uniacid));
	$total=0;
	foreach($data_list as &$item) {
		$stores=pdo_fetchall('SELECT comadd,checkstore  FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and dispatchtype=3 and mobile<>"虚拟" ',array(':uniacid'=>$uniacid));
		$comadd_tatol=0;
		$checkstore_tatol=0;
		foreach($stores as $val){
			if($val['comadd']==$item['id']){
				$comadd_tatol+=1;
			}
			if($val['checkstore']==$item['id']){
				$checkstore_tatol+=1;
			}
		}
		//$real_stores=pdo_fetch('SELECT count(*) as total  FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and dispatchtype=3 and checkstore=:comadd ',array(':uniacid'=>$uniacid,':comadd'=>$item['id']));
		$item['store_order']=$comadd_tatol;
		$item['store_real_order']=$checkstore_tatol;
		$total+=$stores['total'];
	}
	$status=2;
	if(empty($data_list))
	{
		$status=1;
	}
	$list=array();
	$list['status']=$status;
	$list['data']=$data_list;
	$list['total']=$total;
	die(json_encode($list));
}
/*
 * 数据概况
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=sjgk_data&uniacid=259
*/
if($op=='sjgk_data'){
	$uniacid=$_GPC['uniacid'];
	$now=TIMESTAMP;
	$etime = strtotime(date('Y-m-d'))+86400;
	$stime=strtotime(date('Y-m-d'));
	$day0_data=pdo_fetch('select * from '.tablename('tg_data_base')." where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d')",array(':uniacid'=>$uniacid));
	$now=TIMESTAMP-60*60*24*1;
	$day1_data=pdo_fetch('select * from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d')",array(':uniacid'=>$uniacid));
	$now=TIMESTAMP-60*60*24*6;
	$day7_data=pdo_fetch('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(group_total) as group_total,sum(group_success) as group_success,sum(group_fail) as group_fail,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,sum(customer_price)/7 as customer_price,sum(add_fans) as add_fans,sum(pv) as pv,sum(uv) as uv from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$now}),'%Y-%m-%d')",array(':uniacid'=>$uniacid));
	$now=TIMESTAMP-60*60*24*29;
	$day30_data=pdo_fetch('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(group_total) as group_total,sum(group_success) as group_success,sum(group_fail) as group_fail,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,sum(customer_price)/30 as customer_price,sum(add_fans) as add_fans,sum(pv) as pv,sum(uv) as uv from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$now}),'%Y-%m-%d')",array(':uniacid'=>$uniacid));
	$max_data=pdo_fetch('select max(order_total) as order_total,max(order_pay) as order_pay,max(order_refund) as order_refund,max(group_total) as group_total,max(group_success) as group_success,max(group_fail) as group_fail,max(pay_price) as pay_price,max(refund_price) as refund_price,max(success_price) as success_price,max(customer_price) as customer_price,max(add_fans) as add_fans,max(pv) as pv,max(uv) as uv from '.tablename('tg_data_base')."where uniacid=:uniacid ",array(':uniacid'=>$uniacid));

	$list=array();
	$list['今日实时']=$day0_data;
	$list['昨日概况']=$day1_data;
	$list['7日概况']=$day7_data;
	$list['30日概况']=$day30_data;
	$list['历史峰值']=$max_data;
	die(json_encode($list));
}
/*
 * 商城转化率
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=sczhl_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if($op=='sczhl_data')
{
	$uniacid=$_GPC['uniacid'];
	$etime = strtotime($_GPC['etime']);
	$stime=strtotime($_GPC['stime']);
	$cle = $etime - $stime; //得出时间戳差值
	$d = $cle/3600/24+1;
	$day7_data=pdo_fetch('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(group_total) as group_total,sum(group_success) as group_success,sum(group_fail) as group_fail,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,sum(customer_price) as customer_price,sum(add_fans) as add_fans,sum(pv) as pv,sum(uv) as uv from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d')",array(':uniacid'=>$uniacid));

	if($d>0)
	{

		$day7_data['customer_price']=round($day7_data['customer_price']/$d,2);
	}else{

		$day7_data['customer_price']=round($day7_data['customer_price'],2);
	}

	//下单人
	$order_buy=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟' and  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d')  group by openid",array(':uniacid'=>$uniacid));
	$day7_data['order_buy_openid']=count($order_buy);
	//下单金额
	$order_buy_price=pdo_fetch("select sum(pay_price) as total_price from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟' and  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d')  ",array(':uniacid'=>$uniacid));
	$day7_data['order_buy_price']=round($order_buy_price['total_price'],2);
	//购买人
	$order_pay=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟'  and  date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d')  group by openid",array(':uniacid'=>$uniacid));
	$day7_data['order_pay_openid']=count($order_pay);
	//成功购买人
	$order_success=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟' and status in (2,3,8)  and  date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d')  group by openid",array(':uniacid'=>$uniacid));
	$day7_data['order_success_openid']=count($order_success);
	//成功订单数
	$order_success_num=pdo_fetch("select count(openid) as total from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟' and status in (2,3,8)  and  date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d') ",array(':uniacid'=>$uniacid));
	$day7_data['order_success_num']=$order_success_num['total'];
	$list=array();
	$list['d']=$d;
	$list['data']=$day7_data;
	die(json_encode($list));
}
/*
 * 商城指标趋势
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=order_count_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if($op=='order_count_data')
{
	$uniacid=$_GPC['uniacid'];
	$etime = strtotime($_GPC['etime']);
	$stime=strtotime($_GPC['stime']);
	$cle = $etime - $stime; //得出时间戳差值
	/*
	$alist=array();
	for($i=0;$i<$cle;$i++)
	{
		$ntime=strtotime($_GPC['stime'])+86400*$i;
		$d_list=pdo_fetch('select * from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$ntime}),'%Y-%m-%d') ",array(':uniacid'=>$uniacid));
		$alist[$i]['add_fans']=intval($d_list['add_fans']);
		$alist[$i]['addtime']=date('Y-m-d',$item['addtime']);intval($d_list['addtime']);
		$alist[$i]['customer_price']=round($d_list['customer_price'],2);
		$alist[$i]['group_fail']=intval($d_list['group_fail']);
		$alist[$i]['group_success']=intval($d_list['group_success']);
		$alist[$i]['group_total']=intval($d_list['group_total']);
		$alist[$i]['order_pay']=intval($d_list['order_pay']);
		$alist[$i]['order_receive']=intval($d_list['order_receive']);
		$alist[$i]['order_refund']=intval($d_list['order_refund']);
		$alist[$i]['order_total']=intval($d_list['order_total']);
		$alist[$i]['pay_price']=round($d_list['pay_price'],2);
		$alist[$i]['pv']=intval($d_list['pv']);
		$alist[$i]['refund_price']=round($d_list['refund_price'],2);
		$alist[$i]['success_price']=round($d_list['success_price'],2);
		$alist[$i]['uv']=intval($d_list['uv']);
		//已签收
		$order_receive=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and status=3 and mobile<>'虚拟' and date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$ntime}),'%Y-%m-%d') ",array(':uniacid'=>$uniacid));
		$alist[$i]['order_receive']=count($order_receive);

	}*/

	$day7_data=pdo_fetchall('select * from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d') order by addtime  asc ",array(':uniacid'=>$uniacid));

	 foreach($day7_data as &$item)
	{

		//下单金额
		 $order_price=pdo_fetch("select sum(pay_price) as total_price from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟' and  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$item['addtime']}),'%Y-%m-%d')   ",array(':uniacid'=>$uniacid));
		$item['order_price']=round($order_price['total_price'],2);
		//已签收
		$order_receive=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and status=3 and mobile<>'虚拟' and date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$item['addtime']}),'%Y-%m-%d') ",array(':uniacid'=>$uniacid));
		$item['order_receive']=count($order_receive);
		$item['addtime']=date('Y-m-d',$item['addtime']);
	}

	$list=array();
	$list['data']=$day7_data;
	die(json_encode($list));
}
/*
 * 商城访问趋势
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=scgk_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if($op=='scgk_data')
{
	$uniacid=$_GPC['uniacid'];
	$etime = strtotime($_GPC['etime']);
	$stime=strtotime($_GPC['stime']);
	$cle = $etime - $stime; //得出时间戳差值

	$day7_data=pdo_fetchall('select pv,uv,addtime from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d') order by addtime asc",array(':uniacid'=>$uniacid));

	foreach($day7_data as &$item)
	{
		$item['addtime']=date('Y-m-d',$item['addtime']);
	}

	$list=array();
	$list['data']=$day7_data;
	die(json_encode($list));
}
/*
 * 商城转化率统计
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=sczhl_count_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if($op=='sczhl_count_data')
{
	$uniacid=$_GPC['uniacid'];
	$etime = strtotime($_GPC['etime']);
	$stime=strtotime($_GPC['stime']);
	$cle = $etime - $stime; //得出时间戳差值

	$day7_data=pdo_fetchall('select id, pv,uv,addtime from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') >= date_format(from_UNIXTIME({$stime}),'%Y-%m-%d') and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') <= date_format(from_UNIXTIME({$etime}),'%Y-%m-%d') order by addtime asc",array(':uniacid'=>$uniacid));
	foreach($day7_data as &$item)
	{

		//成功购买人
		$order_success=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟' and status in (2,3,8)  and  date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$item['addtime']}),'%Y-%m-%d')   group by openid",array(':uniacid'=>$uniacid));
		$item['order_success_openid']=count($order_success);;

		//下单人
		$order_buy=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟' and  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$item['addtime']}),'%Y-%m-%d')   group by openid",array(':uniacid'=>$uniacid));
		$item['order_buy_openid']=count($order_buy);

		//购买人
		$order_pay=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and mobile<>'虚拟'  and  date_format(from_UNIXTIME(`ptime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$item['addtime']}),'%Y-%m-%d')   group by openid",array(':uniacid'=>$uniacid));
		$item['order_pay_openid']=count($order_pay);

		$item['sczhl']=($item['order_success_openid']/$item['uv'])*100;//商城转化率
		$item['xdzhl']=($item['order_buy_openid']/$item['uv'])*100;//访问到下单转化率
		$item['fkzhl']=($item['order_pay_openid']/$item['order_buy_openid'])*100;//下单到付款转化率
		$item['cgzhl']=($item['order_success_openid']/$item['order_pay_openid'])*100;//下单到成功转化率
		$item['addtime']=date('Y-m-d',$item['addtime']);
	}

	$list=array();
	$list['data']=$day7_data;
	die(json_encode($list));
}
/*
 * 用户区域分布
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=order_area_data&uniacid=259&keyword=county
 * keyword:province 省  city  市  county 县
*/
if($op=='order_area_data')
{
	$uniacid=$_GPC['uniacid'];
	$keyword=$_GPC['keyword'];
	$total=0;
	if($keyword=='province')
	{
		$day7_data=pdo_fetchall('SELECT count(id) as num,province as area FROM `cm_tg_address` where uniacid=:uniacid and province<>"" group by province',array(':uniacid'=>$uniacid));

	}
	if($keyword=='city')
	{
		$day7_data=pdo_fetchall('SELECT count(id) as num,city as area FROM `cm_tg_address` where uniacid=:uniacid and city<>"" group by city',array(':uniacid'=>$uniacid));

	}
	if($keyword=='county')
	{
		$day7_data=pdo_fetchall('SELECT count(id) as num,county as area FROM `cm_tg_address` where uniacid=:uniacid and county<>"" group by county',array(':uniacid'=>$uniacid));

	}
	foreach($day7_data as &$item)
	{
		$total+=$item['num'];
	}

	$list=array();
	$list['data']=$day7_data;
	$list['total']=$total;
	die(json_encode($list));
}
/*
 * 核销员核销金额排行榜
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=hexiao_data&uniacid=259

*/
if($op=='hexiao_data')
{
	$uniacid=$_GPC['uniacid'];

	$day7_data=pdo_fetchall('select veropenid,sum(price) as total_price from cm_tg_order where uniacid=:uniacid  and veropenid<>"" and mobile<>"虚拟" group by veropenid order by total_price desc',array(':uniacid'=>$uniacid));

	foreach($day7_data as &$item)
	{
		$member=pdo_fetch('select nickname,avatar,name from cm_tg_member where openid=:openid and uniacid=:uniacid',array(':openid'=>$item['veropenid'],':uniacid'=>$uniacid));
		$item['nickname']=$member['nickname'];
		$item['avatar']=$member['avatar'];
		$item['name']=$member['name'];
	}
	$list=array();
	$list['data']=$day7_data;

	die(json_encode($list));
}