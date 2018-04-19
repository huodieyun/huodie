<?php
defined('IN_IA') or exit('Access Denied');
wl_load()->model('merchant');
wl_load()->func('print');
$ops = array('modifyPrice','refunds','dayin','update','summary','received','detail','output','remark','address','confrimpay','confirmsend','cancelsend','refund');
$op_names = array('退款','打印','修改价格','订单概况','订单列表','订单详情','导出','卖家备注','修改收货地址','确认付款','发货','取消发货','退款');
foreach($ops as$key=>$value){
	permissions('do', 'ac', 'op', 'order', 'order', $ops[$key], '订单', '订单管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'summary';
$gettime = $this -> module['config']['gettime'];//自动签收时间
$uniacid = $_W['uniacid'];
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$allgoods = pdo_fetchall("select gname,id from".tablename('tg_goods')."where uniacid=:uniacid and isshow=:isshow",array(':uniacid'=>$_W['uniacid'],':isshow'=>1));
session_start();

if(!empty($_GPC['dispatchtype']))
{
	$_SESSION['dispatchtype']=$_GPC['dispatchtype'];
	$dispatchtype=$_SESSION['dispatchtype'];
}
if ($op == 'dayin') {
	$id = intval($_GPC['id']);
	 tuan_print($id);
		
	
	message('打印成功');
		
}
if ($op == 'update') {
	 $id = intval($_GPC['id']);
	$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
	if(!empty($_GPC['price']))
	{
		
		pdo_update('tg_order', array('price' => $_GPC['price']), array('id' => $id));
		
	}
 }
 if($op=='modifyPrice')
 {
	 $id = intval($_GPC['id']);
	$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
	if(!empty($_GPC['price']))
	{		
		pdo_update('tg_order', array('pay_price' => $_GPC['price']), array('id' => $id));		
	}
	echo 1;
	exit;
 }
if($op == 'summary' ){
	$seven_orders =  0;
	$seven_nocash_orders =  0;
	$obligations =  0;
	$undelivereds =  0;
	$incomes =  0;
	$yesterday_orders =  0;
	$yesterday_payorder =  0;
	$yesterday_obligation = 0;
	$stime = strtotime(date('Y-m-d')) - 6 * 86400;
	
	$etime = strtotime(date('Y-m-d'))+86400;
	
	$ytime = strtotime(date('Y-m-d')) -  86400;
	$seven_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟'   and createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	$seven_nocash_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status=0   and createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	$obligations = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status in (1,2,3,4,5,6,7,8)   and createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	$undelivereds = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_order') . " WHERE uniacid = {$_W['uniacid']} and mobile<>'虚拟' and status in (4,6,7)   and createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	
	$seven = pdo_fetchall("select pay_price  from" . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' and status in(1,2,3,4,5,6,7,8,10)  AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	foreach($seven as$key=>$value){
		$incomes += $value['pay_price'];
	}
	
	$yesterday_orders=pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟'   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $ytime, ':endtime' => $etime));
	$yesterday_payorder = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' and status in(1,2,3,4,6,7)   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $ytime, ':endtime' => $etime));
	$yesterday_obligation = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_order') . " WHERE uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' and status = 3   AND sendtime >= :createtime AND sendtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $ytime, ':endtime' => $etime));

	$con =  "uniacid = '{$_W['uniacid']}' and mobile<>'虚拟' "  ;
	$starttime = empty($_GPC['time']['start']) ? $stime : strtotime($_GPC['time']['start']);
	$endtime = empty($_GPC['time']['end']) ? $etime : strtotime($_GPC['time']['end'])+86400;
	$s = $starttime;
	$e = $endtime;
	$list = array();
	$j=0;
	
	while($e >= $s){
		$listone = pdo_fetchall("SELECT id  FROM " . tablename('tg_order') . " WHERE $con   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $e-86400, ':endtime' => $e));
		$status1 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $con and status in (1,2,3,4,5,6,7,8,10)   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $e-86400, ':endtime' => $e));
		$status4 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $con and status=3   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $e-86400, ':endtime' => $e));
		$status2 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $con and status in (4,5,6,7,10)   AND createtime >= :createtime AND createtime <= :endtime ORDER BY createtime ASC", array( ':createtime' => $e-86400, ':endtime' => $e));
		
		$list[$j]['gnum'] = count($listone);
		$list[$j]['status4'] = $status4;
		$list[$j]['status1'] = $status1;
		$list[$j]['status2'] = $status2;
		$list[$j]['createtime'] =  $e-86400;
		$j++;
		$e = $e-86400;
	}
	
	$day = $hit = $status4 = $status1= $status2  = array();
	if (!empty($list)) {
		foreach ($list as $row) {
			$day[] = date('m-d', $row['createtime']);
			$hit[] = intval($row['gnum']);
			$status4[] = intval($row['status4']);
			$status1[] = intval($row['status1']);
			$status2[] = intval($row['status2']);
		}
	}
	
	for ($i = 0; $i = count($hit) < 2; $i++) {
		$day[] = date('m-d', $endtime);
		$hit[] = $day[$i] == date('m-d', $endtime) ? $hit[0] : '0';
	}
	include wl_template('order/summary');
	exit;
}

if ($op == 'received') {
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$condition = "  uniacid = {$_W['uniacid']}";
	$paras = array(':uniacid' => $_W['uniacid']);

	$status = $_GPC['status'];
	$transid = $_GPC['transid'];
	$pay_type = $_GPC['pay_type'];
	$keyword = $_GPC['keyword'];
	$member = $_GPC['member'];
	$time = $_GPC['time'];

	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}

	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end'])+86400 ;
		$condition .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
		$paras[':starttime'] = $starttime;
		$paras[':endtime'] = $endtime;
	}
	if(trim($_GPC['goodsid'])!=''){
		$condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
	}
	if(trim($_GPC['goodsid2'])!=''){
		$condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
	}
	if(trim($_GPC['address'])!=''){
		
		$condition .= " and address like '%{$_GPC['address']}%' ";
	}
	if (!empty($_GPC['merchantid'])) {
		$condition .= " AND  merchantid={$_GPC['merchantid']} ";
	}
	if (!empty($_GPC['transid'])) {
		$condition .= " AND  transid  like '%{$_GPC['transid']}%' ";
	}
	if (!empty($_GPC['pay_type'])) {
		$condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
	} elseif ($_GPC['pay_type'] === '0') {
		$condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
	}
	if (!empty($_GPC['member'])) {
		$condition .= " AND (addname LIKE '%{$_GPC['member']}%' or mobile LIKE '%{$_GPC['member']}%')";
	}
	if(!empty($dispatchtype)&&$dispatchtype!=0)
	{
		$condition .= " AND  dispatchtype = '{$_SESSION['dispatchtype']}'";
	}
		if (!empty($_GPC['addresstype'])) {
		$condition .= " AND  addresstype={$_GPC['addresstype']} ";
	}
	if ($status != '') {
				if($status == 1){
					$condition .= " AND status = '" . intval($status) . "' ";
				
				}
				else if($status==8)
				{
					$condition .= " AND status=8 ";
					
				}
				
				else if($status==4)
				{
					$condition .= " AND status in (4,7)";
					
				}
				else if($status==7)
				{
					$condition .= " AND status in (4,5,6,7)";
					
				}
				else if($status==17)
				{
					$condition .= " AND is_tuan=0";
					$table='tg_order';
				}
				else{
					$condition .= " AND status = '" . intval($status) . "'";
					
				}
				
			}
	//message($condition );
	$sql = "select  * from " . tablename('tg_order') . " where $condition   and mobile<>'虚拟'  ORDER BY createtime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql, $paras);
	$paytype = array('0' => array('css' => 'default', 'name' => '未支付'), '1' => array('css' => 'info', 'name' => '余额支付'), '2' => array('css' => 'success', 'name' => '在线支付'), '3' => array('css' => 'warning', 'name' => '货到付款'));
	$orderstatus = array(
	'0' => array('css' => 'default', 'name' => '待付款'), 
	'1' => array('css' => 'info', 'name' => '已付款'),
	'2' => array('css' => 'warning', 'name' => '待收货'),
	'3' => array('css' => 'success', 'name' => '已签收'), 
	'4' => array('css' => 'success', 'name' => '已退款'),
	'5' => array('css' => 'success', 'name' => '强退款'),
	'6' => array('css' => 'danger', 'name' => '部分退款'),
	'7' => array('css' => 'success', 'name' => '已退款'),
	'8' => array('css' => 'success', 'name' => '待发货'),
	'9' => array('css' => 'success', 'name' => '已取消'),
	'10' => array('css' => 'success', 'name' => '待退款')
	);
	foreach ($list as $key => $value) {
		$options  = pdo_fetch("select title,productprice,marketprice from " . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $value['optionid']));
		//$list[$key]['optionname'] = $options['title'];
		$s = $value['status'];
		$list[$key]['statuscss'] = $orderstatus[$value['status']]['css'];
		$list[$key]['status'] = $orderstatus[$value['status']]['name'];
		$list[$key]['css'] = $paytype[$value['pay_type']]['css'];
		if ($value['pay_type'] == 2) {
			if (empty($value['transid'])) {
				$list[$key]['paytype'] = '微信支付';
			} else {
				$list[$key]['paytype'] = '微信支付';
			}
		} else {
			$list[$key]['paytype'] = $paytype[$value['pay_type']]['name'];
		}
		$goodsss = pdo_fetch("select id,gname,gimg,merchantid,unit from" . tablename('tg_goods') . "where id = '{$value['g_id']}'");
		$list[$key]['unit'] = $goodsss['unit'];
		$list[$key]['gid'] = $goodsss['id'];
		$list[$key]['gname'] = $goodsss['gname'];
		$list[$key]['gimg'] = $goodsss['gimg'];
		$list[$key]['merchant'] = pdo_fetch("select name from" . tablename('tg_merchant') . "where id = '{$value['merchantid']}' and uniacid={$_W['uniacid']}");
	}
	
	
	//$alldan = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and is_tuan=0 and  uniacid='{$_W['uniacid']}' and mobile<>'虚拟'  ");
	
	//
	if($dispatchtype!=0)
	{
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition and dispatchtype='{$dispatchtype}' and mobile<>'虚拟' ", $paras);
	$pager = pagination($total, $pindex, $psize);
		$all = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and  uniacid='{$_W['uniacid']}' and mobile<>'虚拟'  ");
		$status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=0 and mobile<>'虚拟' ");//待付款
	$status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟'  ");//已付款
	$status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=2 and mobile<>'虚拟' ");//待收货
	$status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=3 and mobile<>'虚拟' ");//已签收
	$status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status in (4,5,6,7) and mobile<>'虚拟' ");//已退款
	$status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=5 and mobile<>'虚拟' ");//强退款
	$status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' ");//部分退款
	$status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=7 and mobile<>'虚拟' ");//团长免单
	$status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=8 and mobile<>'虚拟' ");//待发货
	$status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=9 and mobile<>'虚拟' ");//已取消
	$status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' ");//已关闭
	$status17 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE dispatchtype='{$dispatchtype}' and uniacid='{$_W['uniacid']}' and is_tuan=0");//单买单

	}else{
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition  and mobile<>'虚拟' ", $paras);
	$pager = pagination($total, $pindex, $psize);
		$all = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and mobile<>'虚拟'");
		$status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=0 and mobile<>'虚拟' ");//待付款
	$status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟' ");//已付款
	$status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=2 and mobile<>'虚拟' ");//待收货
	$status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=3 and mobile<>'虚拟' ");//已签收
	$status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status in (4,5,6,7) and mobile<>'虚拟' ");//已退款
	$status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=5 and mobile<>'虚拟' ");//强退款
	$status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' ");//部分退款
	$status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=7 and mobile<>'虚拟' ");//团长免单
	$status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=8 and mobile<>'虚拟' ");//待发货
	$status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=9 and mobile<>'虚拟' ");//已取消
	$status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and status=10 and mobile<>'虚拟' ");//已关闭
	$status17 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_order') . " WHERE  uniacid='{$_W['uniacid']}' and is_tuan=0");//单买单

	}
	
	} 

if ($op == 'detail') {
	wl_load()->model('activity');
	wl_load()->model('goods');
	load()->model('mc');
	$id = intval($_GPC['id']);
	$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
	$rfee=pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id and status=1 ", array(':id' => $item['transid']));
	$resultfee=0;
	if(!empty($item['transid'])){
			foreach ($rfee as $rf => $rforder) 
			{
				$resultfee+= $rforder['refundfee'];				
			}
 }
			$mfee=$item['price']-$resultfee;
	$coupon_template = coupon_template($item['couponid']);
	$option  = pdo_fetch("select title,productprice,marketprice from " . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $item['optionid']));
	if ($item['status'] == 7) {
		$refund_record = pdo_fetch("SELECT * FROM " . tablename('tg_refund_record') . " WHERE orderid = :id", array(':id' => $id));
	}
	if (empty($item)) {
		message("抱歉，订单不存在!", referer(), "error");
	}
	$saler=pdo_fetch("select nickname from" . tablename('tg_member') . " where from_user ='".$item['veropenid']."'");
	$uid = mc_openid2uid($item['openid']);
	$result = mc_fansinfo($uid, $_W['acid'], $_W['uniacid']);
	$item['fanid'] = $result['fanid'];
	$item['user'] = pdo_fetch("SELECT * FROM " . tablename('tg_address') . " WHERE id = {$item['addressid']}");
	$goods = goods_get_by_params(" id={$item['g_id']} ");
	$item['goods'] = $goods;
	if($item['status']==4||$item['status']==6||$item['status']==7 ||$item['status']==5){
		$refund = pdo_fetch("select createtime from".tablename('tg_refund_record')."where orderid={$item['id']}");
		$refund_time = $refund['createtime'];
	}
	
	
	$store=pdo_fetch("select * from" . tablename('tg_store') . " where id ='".$item['comadd']."'");
	include wl_template('order/order_detail');
	exit;
} 
if($op=='confrimpay'){
		$id = $_GPC['id'];
		$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
		pdo_update('tg_order', array('status' => 1, 'pay_type' => 2,'ptime'=>TIMESTAMP), array('id' => $id));
		$oplogdata = serialize($item);
		oplog('admin', "后台确认付款", web_url('order/order/confrimpay'), $oplogdata);
		message('确认订单付款操作成功！', referer(), 'success');
}
if($op=='confirmsend'){
	$id = $_GPC['id'];
	
	$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
	$r=pdo_update('tg_order', array('status' => 2, 'express' => $_GPC['express'], 'expresssn' => $_GPC['expresssn'], 'sendtime' => TIMESTAMP), array('id' => $id));
	if($r){
		/*更新可结算金额*/
		if(!empty($item['merchantid'])){merchant_update_no_money($item['price'], $item['merchantid']);}
		/*记录操作*/
		$oplogdata = serialize($item);
		oplog('admin', "后台确认发货", web_url('order/order/confirmsend'), $oplogdata);
		/*发货成功消息提醒*/
		$url = app_url('order/order/detail', array('id' => $item['id']));
		send_success($item['orderno'], $item['openid'], $_GPC['express'], $_GPC['expresssn'], $url);
		message('发货操作成功！', referer(), 'success');
	}else{
		message('发货操作失败！', referer(), 'error');
	}
	
}
if($op=='cancelsend'){
	$id = $_GPC['id'];
	$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
	$r=pdo_update('tg_order', array('status' => 8, 'express' => '', 'expresssn' => '', 'sendtime' => ''), array('id' => $id));
	if($r){
		/*更新可结算金额*/
		if(!empty($item['merchantid'])){merchant_update_no_money(0-$item['price'], $item['merchantid']);}
		/*记录操作*/
		$oplogdata = serialize($item);
		oplog('admin', "后台取消发货", web_url('order/order/cancelsend'), $oplogdata);
		message('取消发货操作成功！', referer(), 'success');
	}else{
		message('取消发货操作失败！', referer(), 'error');
	}
	
}
if($op=='refund'){
	$id = $_GPC['id'];	
	$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
	$orderno = $item['orderno'];
	if($_GPC['refundall']==1)
	{
		$res=refund($orderno,2);
	}else{
		$rfee=pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id and status=1 ", array(':id' => $item['transid']));
			$resultfee=0;
			if(!empty($item['transid'])){
					foreach ($rfee as $rf => $rforder) 
					{
						$resultfee+= $rforder['refundfee'];				
					}
		 }
		$mfee=$item['price']-$resultfee;
		$res=partrefund($orderno,2,$_GPC['refundnum']);
	}
	
	if($res=='success'){
		$oplogdata = serialize($item);
		oplog('admin', "后台订单详情退款", web_url('order/order/refund'), $oplogdata);
		/*退款成功消息提醒*/
		$url = app_url('order/order/detail', array('id' => $item['id']));
		$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$item['g_id']}'");
		refund_success($item['orderno'],$goods['gname'],$item['openid'], $item['price'],time(),$url);
		
		message('退款成功了！', referer(), 'success');
	} else {
		message('退款失败，服务器正忙，请稍等等！', referer(), 'fail');
	}
}
if($op == 'output'){
	$condition = "  uniacid = :uniacid";
	$paras = array(':uniacid' => $_W['uniacid']);
	$status = $_GPC['status'];
	$transid = $_GPC['transid'];
	$pay_type = $_GPC['pay_type'];
	$keyword = $_GPC['keyword'];
	$member = $_GPC['member'];
	$time = $_GPC['time'];

	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}
	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end']) ;
		$condition .= " AND  createtime >= :starttime AND  createtime <= :endtime ";
		$paras[':starttime'] = $starttime;
		$paras[':endtime'] = $endtime;
	}
	if(trim($_GPC['goodsid'])!=''){
		$condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
	}
	if(trim($_GPC['goodsid2'])!=''){
		$condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
	}
	if(trim($_GPC['address'])!=''){
		
		$condition .= " and address like '%{$_GPC['address']}%' ";
	}
	if (!empty($_GPC['merchantid'])) {
		$condition .= " AND  merchantid={$_GPC['merchantid']} ";
	}
	if (!empty($_GPC['transid'])) {
		$condition .= " AND  transid LIKE  '%{$_GPC['transid']}%'";
	}
		if (!empty($_GPC['addresstype'])) {
		$condition .= " AND  addresstype={$_GPC['addresstype']} ";
	}
	if (!empty($_GPC['pay_type'])) {
		$condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
	} elseif ($_GPC['pay_type'] === '0') {
		$condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
	}
	if (!empty($_GPC['member'])) {
		$condition .= " AND (addname LIKE '%{$_GPC['member']}%' or mobile LIKE '%{$_GPC['member']}%')";
	}
	if(!empty($dispatchtype)&&$dispatchtype!=0)
	{
		$condition .= " AND  dispatchtype = '{$_SESSION['dispatchtype']}'";
	}
	if ($status != '') {
				if($status == 1){
					$condition .= " AND status = '" . intval($status) . "' ";
					
				}
				else if($status==8)
				{
					$condition .= " AND status=8 ";
					
				}
				
				else if($status==4)
				{
					$condition .= " AND status in (4,7)";
					$condition .= " AND is_tuan=1";
				}
				else if($status==7)
				{
					$condition .= " AND status in (4,5,6,7)";
					
				}
				else if($status==17)
				{
					$condition .= " AND is_tuan=0";
					$table='tg_order';
				}
				else{
					$condition .= " AND status = '" . intval($status) . "'";
					
				}
				
			}
			//message($condition);
			$sql="select * from" . tablename('tg_order') . "where $condition and mobile<>'虚拟' order by successtime asc";
				$orders = pdo_fetchall($sql, $paras);
	//$orders = pdo_fetchall("select * from" . tablename('tg_order') . "where $condition and mobile<>'虚拟' order by successtime asc");
	switch($status){
		case NULL: 
		$str = '全部订单_' . time();
		break;
		case 1: 
		$str = '已支付订单_' . time();
		break;
		case 8: 
		$str = '待发货订单' . time();
		break;
		case 2: 
		$str = '待收货订单' . time();
		break;
		case 3: 
		$str = '已签收订单' . time();
		break;
		case 4:
		$str = '已退款订单' . time();
		break;
		case 5:
		$str = '强退款订单' . time();
		break;
		case 6: 
		$str = '部分退款订单' . time();
		break;
		case 7:
		$str = '已退款订单' . time();
		break;
		case 9:
		$str = '已取消订单' . time();
		break;
		case 10:
		$str = '待退款订单' . time();
		break;
		default:
		$str = '待支付订单' . time();break;
	}
	/* 输入到CSV文件 */
	$html = "\xEF\xBB\xBF";
	/* 输出表头 */
	$filter = array('aa' => '订单编号','ll'=>'团ID', 'bb' => '姓名', 'cc' => '电话','m2'=>'运费', 'dd' => '总价(元)', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称','pp'=>'商品规格','oo'=>'购买数量','mm'=>'买家留言', 'h1' => '省','h2' => '市','h3' => '区','h4' => '详细地址','hh' => '收货地址','m6'=>'地址类型', 'ii' => '微信订单号', 'jj' => '快递单号', 'kk' => '快递名称','m1'=>'配送方式','mz'=>'组团成功时间','m3'=>'核销员','m4'=>'核销门店','m5'=>'客选门店');
	foreach ($filter as $key => $title) {
		$html .= $title . "\t,";
	}
	$html .= "\n";
	foreach ($orders as $k => $v) {
		if ($v['status'] == '0') {
			$thistatus = '未支付';
		}
		if ($v['status'] == '1') {
			$thistatus = '已支付';
		}
		if ($v['status'] == '2') {
			$thistatus = '待收货';
		}
		if ($v['status'] == '3') {
			$thistatus = '已签收';
		}
		if ($v['status'] == '4') {
			$thistatus = '已退款';
		}
		if ($v['status'] == '5') {
			$thistatus = '强退款';
		}
		if ($v['status'] == '6') {
			$thistatus = '部分退款';
		}
		if ($v['status'] == '7') {
			$thistatus = '已退款';
		}
		if ($v['status'] == '8') {
			$thistatus = '待发货';
		}
		if ($v['status'] == '9') {
			$thistatus = '已取消';
		}
		if ($v['status'] == '10') {
			$thistatus = '待退款';
		}
		if ($v['status'] == '') {
			$thistatus = '全部订单';
		}
		if($v['dispatchtype']==0){$disname='无';}
			if($v['dispatchtype']==1){$disname='送货上门';}
			if($v['dispatchtype']==2){$disname='快递';}
			if($v['dispatchtype']==3){
				$disname='自提';
				
			}
			wl_load()->model('member');
		$mermber = member_get_by_params(" openid='{$v['openid']}' ");
		if(!empty($v['addressid']))
		{
			$add=pdo_fetch("select * from" . tablename('tg_address') . "where id = '{$v['addressid']}' ");
		}
		
		$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$v['g_id']}' and uniacid='{$_W['uniacid']}'");
		$time = date('Y-m-d H:i:s', $v['createtime']);
		if($v['addresstype']==1){
			$addresstype='公司';
		}else{
			$addresstype='家庭';
		}
		if(!empty($v['veropenid']))
		{
			$saler=pdo_fetch("select nickname from" . tablename('tg_member') . " where from_user ='".$v['veropenid']."'");
		}
		if(!empty($v['checkstore']))
		{
			$realstore=pdo_fetch("select storename from" . tablename('tg_store') . " where id ='".$v['checkstore']."'");
		}
		
		if(!empty($v['comadd']))
		{
			$com=pdo_fetch("select storename from" . tablename('tg_store') . " where id ='".$v['comadd']."'");
		}
		if(!empty($v['hexiaotime']))
		{
			$hexiaotime=date('Y-m-d H:i:s', $v['hexiaotime']);
			
		}
		$options  = pdo_fetch("select title,productprice,marketprice from " . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $v['optionid']));
		if(empty($options['title'])){
			$options['title'] = '不限';
		}
		if($v['g_id']>0)
			{
				$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$v['g_id']}' and uniacid='{$_W['uniacid']}'");
				$gname=$goods['gname'];
				$gopname=$v['optionname'];
				$orders[$k]['aa'] = $v['orderno'];
				$orders[$k]['ll'] = $v['tuan_id'];
				//$orders[$k]['qq'] = $mermber['nickname'];
			$orders[$k]['bb'] = $v['addname'];
			$orders[$k]['cc'] = $v['mobile'];
			$orders[$k]['m2'] = $v['freight'];
			$orders[$k]['dd'] = $v['pay_price'];
			$orders[$k]['ee'] = $thistatus;
			$orders[$k]['ff'] = $time;
			$orders[$k]['gg'] = $gname;
			$orders[$k]['h1'] = $add['province'];
			$orders[$k]['h2'] = $add['city'];
			$orders[$k]['h3'] = $add['county'];
			$orders[$k]['h4'] = $add['detailed_address'];
			$orders[$k]['hh'] = $v['address'];
			$orders[$k]['ii'] = $v['transid'];
			$orders[$k]['jj'] = $v['expresssn'];
			$orders[$k]['kk'] = $v['express'];
			$orders[$k]['m1'] = $disname;
			
			
			$orders[$k]['mz'] = date('Y-m-d H:i:s', $v['successtime']);
			$orders[$k]['mm'] = $v['remark'];
			$orders[$k]['m3'] = $saler['nickname'];
			$orders[$k]['m4'] = $realstore['storename'];
			$orders[$k]['m5'] = $com['storename'];
			$orders[$k]['m6'] = $addresstype;
			$orders[$k]['oo'] = $v['gnum'];
			$orders[$k]['pp'] = $gopname;
			
			foreach ($filter as $key => $title) {
				$html .= $orders[$k][$key] . "\t,";
			}
			$html .= "\n";
			}else{
				$col=pdo_fetchall("select * from ".tablename('tg_collect')." where  orderno=:orderno",array('orderno'=>$v['orderno']));
			$orders[$k]['aa'] = $v['orderno'];
			$orders[$k]['bb'] = $v['addname'];
			$orders[$k]['cc'] = $v['mobile'];
			$orders[$k]['dd'] = $v['price'];
			$orders[$k]['ee'] = $thistatus;
			$orders[$k]['ff'] = $time;
			$orders[$k]['gg'] = '';
			$orders[$k]['h1'] = $add['province'];
			$orders[$k]['h2'] = $add['city'];
			$orders[$k]['h3'] = $add['county'];
			$orders[$k]['h4'] = $add['detailed_address'];
			$orders[$k]['hh'] = $v['address'];
			$orders[$k]['ii'] = $v['transid'];
			$orders[$k]['jj'] = $v['expresssn'];
			$orders[$k]['kk'] = $v['express'];
			$orders[$k]['m1'] = $disname;
			$orders[$k]['m2'] = $v['freight'];
			$orders[$k]['m3'] = $saler['nickname'];
			$orders[$k]['m4'] = $realstore['storename'];
			$orders[$k]['m5'] = $com['storename'];
			$orders[$k]['m6'] = $addresstype;
			$orders[$k]['ll'] = $v['tuan_id'];
			$orders[$k]['mz'] = date('Y-m-d H:i:s', $v['successtime']);
			$orders[$k]['mm'] = $v['remark'];
			$orders[$k]['oo'] = '';
			$orders[$k]['pp'] = '';
		
				foreach ($filter as $key => $title) {
				$html .= $orders[$k][$key] . "\t,";
			}
			$html .= "\n";
				foreach ($col as $c => $cc) {
					$gs=pdo_fetch("select gname from ".tablename('tg_goods')." where  id=:id",array('id'=>$cc['sid']));
					$gname=$gs['gname'];
					$gopname=$cc['item'];
					$orders[$k]['aa'] = '';
					$orders[$k]['bb'] = '';
					$orders[$k]['cc'] = '';
					$orders[$k]['dd'] = $cc['oprice'];
					$orders[$k]['ee'] = '';
					$orders[$k]['ff'] = $time;
					$orders[$k]['gg'] = $gname;
					$orders[$k]['h1'] = '';
					$orders[$k]['h2'] = '';
					$orders[$k]['h3'] = '';
					$orders[$k]['h4'] = '';
					$orders[$k]['hh'] = '';
					$orders[$k]['ii'] = '';
					$orders[$k]['jj'] = '';
					$orders[$k]['kk'] = '';
					$orders[$k]['m1'] = '';
					$orders[$k]['m2'] = '';
					$orders[$k]['m3'] = '';
					$orders[$k]['m4'] = '';
					$orders[$k]['m5'] = '';
					$orders[$k]['m6'] = $addresstype;
					$orders[$k]['ll'] = '';
					$orders[$k]['mz'] = '';
					$orders[$k]['mm'] = '';
					$orders[$k]['oo'] = $cc['num'];
					$orders[$k]['pp'] = $gopname;
				
					foreach ($filter as $key => $title) {
				$html .= $orders[$k][$key] . "\t,";
			}
			$html .= "\n";
				}
			}
	}
	/* 输出CSV文件 */
	header("Content-type:text/csv");
	header("Content-Disposition:attachment; filename={$str}.csv");
	echo $html;
	exit();
}
if ($op == 'remark') {
	$orderid = intval($_GPC['id']);
	$remark = $_GPC['remark'];
	if (pdo_update('tg_order', array('adminremark' => $remark),array('id'=>$orderid))) {
		die(json_encode(array('errno'=>0)));
	} else {
		die(json_encode(array('errno'=>1)));
	}
}
if ($op == 'address') {
	$orderid = intval($_GPC['id']);
	$address = $_GPC['address'];
	$realname = $_GPC['realname'];
	$mobile = $_GPC['mobile'];
	$item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $orderid));
	if (pdo_update('tg_order', array('address' => $address,'addname'=>$realname,'mobile'=>$mobile),array('id'=>$orderid))) {
		$oplogdata = serialize($item);
		oplog('admin', "后台修改地址", web_url('order/order/confrimpay'), $oplogdata);
		die(json_encode(array('errno'=>0)));
	} else {
		die(json_encode(array('errno'=>1)));
	}
}
include wl_template('order/order');
