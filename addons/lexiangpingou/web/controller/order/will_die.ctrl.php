<?php
defined('IN_IA') or exit('Access Denied');

$ops = array('will_die','base','print','refundgroup','sendgroup','summary','all','group_detail','autogroup','output');
$op_names = array('全团打印','全团退款','强制发货','团购概况','订单列表','团详情','后台核销','导出');
foreach($ops as$key=>$value){
	permissions('do', 'ac', 'op', 'order', 'group', $ops[$key], '订单', '团管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'base';
wl_load()->model('goods');
$tid=8171;
//权限控制
wl_load()->model('functions');
$checkfunction=checkfunc($tid);
if($op=='base')
{
	include wl_template('order/will_die');exit;
}
if ($op == 'sendgroup') {
	 $groupnumber = intval($_GPC['groupnumber']);
	 pdo_update('tg_group', array('sendtype' => 1), array('groupnumber' => $groupnumber));
	 pdo_update('tg_order', array('status' => 8), array('tuan_id' => $groupnumber,'status'=>1));
	 message('更改发货状态成功',referer(), 'success');
 }
 if ($op == 'refundgroup') {
	
	$groupnumber = intval($_GPC['groupnumber']);
	//指定团的id
	$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' and status=1 ");
	$success_num=0;
	$fail_num=0;
	//message(count($orders));
	foreach ($orders as $k => $value) {
			$refund_ids = pdo_fetch("select * from".tablename('tg_order')."where id='{$value['id']}'");
			//message(1);
					$res = $this->refund($refund_ids['orderno'],'',2);
					if($res == 'success'){
						$success_num+=1;
					}else{
						$fail_num+=1;
					}
	}
	message('全团退款操作成功！成功' . $success_num . '人,失败' . $fail_num . '人', referer(), 'success');
} 
if ($op == 'print') {
	 $groupnumber = intval($_GPC['groupnumber']);
	 $allgroup= pdo_fetchall('SELECT * FROM ' . tablename('tg_order') . ' WHERE uniacid = :aid AND tuan_id = :id and status not in (0,5,9)', array(':aid' => $_W['uniacid'],':id' => $groupnumber));
	 $groupmember="";
	 foreach($allgroup as $row) {
		 if(empty($groupmember))
		 {
			 $groupmember=$row['addname'].":".$row['mobile'];
		 }
		 else{
			  $groupmember.=",".$row['addname'].":".$row['mobile'];
		 }
		
	 }
	 $prints = pdo_fetchall('SELECT * FROM ' . tablename('tg_print') . ' WHERE uniacid = :aid AND status = 1', array(':aid' => $_W['uniacid']));
	   				if(!empty($prints)) {
						 require_once IA_ROOT . '/addons/feng_fightgroups/wprint.class.php';		 
		   				//include_once 'wprint.class.php';
		   				//遍历所有打印机
		   				foreach($prints as $li) {
		   					if(!empty($li['print_no']) && !empty($li['key'])) {
		   						$wprint = new wprint();
								if ($li['mode']==1) {
				   					
					   			foreach($allgroup as $row) {
								$goodsInfo= pdo_fetch('SELECT gname FROM ' . tablename('tg_goods') . ' WHERE id = :id ', array(':id' => $row['g_id']));	
										if($row['dispatchtype']==0)
										{
											$btype="快递";
										}
										if($row['dispatchtype']==1)
										{
											$btype="自提";
										}
										if($row['dispatchtype']==2)
										{
											$btype="送货上门";
										}
				
										if($row['pay_type']==2)
											{
												$bt="微信支付";
												$tt="实付金额";
											}
											if($row['pay_type']==3)
											{
												$bt="货到付款";
												$tt="待付金额";
											}
										$ptime=date('Y-m-d',$row['ptime']);
										$orderinfo="";
										$orderinfo .= "<CB>组团成功</CB><BR>";										
										$orderinfo .= "团ID：{$row['tuan_id']}<BR>";	
										$orderinfo .= "配送类型：{$btype}<BR>";
										$orderinfo .= "支付方式：{$bt}<BR>";
										$orderinfo .= "付款时间：{$ptime}<BR>";											
										if($row['dispatchtype']==0)
										{
											$orderinfo .= "快递公司：{$row['express']}<BR>";
										}
										$orderInfo .= "商品信息：<BR>";
										$orderinfo .= '------------------------------<BR>';
										$orderinfo .= "商品名称：{$goodsInfo['gname']}<BR>";
										$orderinfo .= '------------------------------<BR>';
										$orderinfo .= "订单金额：￥{$row['price']}<BR>优惠金额：￥{$row['pay_fee']}<BR>{$tt}：￥{$row['card_fee']}<BR>";
										$orderinfo .= '------------------------------<BR>';
										$orderinfo .= "用户信息：<BR>";
										$orderinfo .= '------------------------------<BR>';
					   					$user = pdo_fetch("select * from".tablename('tg_address')."where id='{$row['addressid']}'");
					   					$orderinfo .= "用户名：{$row['addname']}<BR>";
					   					$orderinfo .= "手机号：{$row['mobile']}<BR>";
					   					$orderinfo .= "地址：{$row['address']}<BR>";	
										$orderinfo .= '------------------------------<BR>';		
										if($row['selltype']==2)
										{
										$orderinfo .= "领购团员：{$groupmember}<BR>";	
										}
					   					$orderinfo .= '------------------------------<BR>';	
										$orderinfo .= "备注：{$row['buyremark']}<BR>";	
										
										if(strpos($row['address'],$li['name']) !== false||$li['name']=='全国')			
										{
											
										$status = $wprint->StrPrint($li['print_no'], $li['key'], $orderinfo, $li['print_nums']);
										}
								}
									
								}else{
				   					
					   				foreach ($alltuan as $row) {
										$orderinfo .= "组团成功";
										$orderInfo .= "商品信息：";
										$orderinfo .= '------------------------------';
										$orderinfo .= "商品名称：{$goodsInfo['gname']}";
										$orderinfo .= '------------------------------';
										$orderinfo .= "用户信息：";
										$orderinfo .= '------------------------------';
					   					$user = pdo_fetch("select * from".tablename('tg_address')."where id='{$row['addressid']}'");
					   					$orderinfo .= "用户名：{$user['cname']}";
					   					$orderinfo .= "手机号：{$user['tel']}";
					   					$orderinfo .= "地址：{$user['province']}{$user['city']}{$user['county']}{$user['detailed_address']}";
										
					   					$orderinfo .= '------------------------------';
										$status = $wprint->testSendFreeMessage($li['member_code'], $li['print_no'], $li['key'], $orderinfo);
					   				}
		   							
		   						}
		   						if(!is_error($status)) {
		   							$i++;
		   							$data = array(
		   									'uniacid' => $_W['uniacid'],
		   									'sid' => $sid,
		   									'pid' => $li['id'],
		   									'oid' => $id, //订单id
		   									'status' => 1,
		   									'foid' => $status,
		   									'addtime' => TIMESTAMP
		   								);
		   							pdo_insert('tg_order_print', $data);
		   						}
		   					}
		   				}
	   				}
					 message('打印成功',referer(), 'success');
 }
if($op == 'summary' ){
	$seven_orders =  0;
	$obligations =  0;
	$succ_list =  0;
	$undelivereds =  0;
	$incomes =  0;
	$yesterday_orders =  0;
	$yesterday_payorder =  0;
	$yesterday_obligation = 0;
	$yesterday_succ_list = 0;
	$stime = strtotime(date('Y-m-d')) - 6 * 86400;
	$etime = strtotime(date('Y-m-d'))+86400;
	$ytime = strtotime(date('Y-m-d')) -  86400;
	$seven_orders = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename('tg_group') . " WHERE uniacid = {$_W['uniacid']}    and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	$obligations = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=1 and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	
	$succ_list = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=2 and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	$undelivereds = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=3 and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	//$seven = pdo_fetchall("select pay_price  from" . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus in(1,2,3,4,6)  AND starttime >= :createtime AND endtime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $stime, ':endtime' => $etime));
	//foreach($seven as$key=>$value){
	//	$incomes += $value['pay_price'];
	//}
	
	$yesterday_orders=pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'    and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $ytime, ':endtime' => $etime));
	$yesterday_payorder = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=1 and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $ytime, ':endtime' => $etime));
	$yesterday_obligation = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=3 and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $ytime, ':endtime' => $etime));
	$yesterday_succ_list = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_group') . " WHERE uniacid = '{$_W['uniacid']}'  and groupstatus=2 and starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $ytime, ':endtime' => $etime));

	$con =  "uniacid = '{$_W['uniacid']}'  "  ;
	$starttime = empty($_GPC['time']['start']) ? strtotime(date('Y-m-d')) - 7 * 86400 : strtotime($_GPC['time']['start']);
	$endtime = empty($_GPC['time']['end']) ? strtotime(date('Y-m-d'))+86400 : strtotime($_GPC['time']['end'])+86400;
	$s = $starttime;
	$e = $endtime;
	$list = array();
	$j=0;
	
	while($e >= $s){
		$listone = pdo_fetchall("SELECT id  FROM " . tablename('tg_group') . " WHERE $con   AND starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $e-86400, ':endtime' => $e));
		$status1 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE $con and groupstatus=1   AND starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $e-86400, ':endtime' => $e));
		$status4 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_group') . " WHERE $con and groupstatus=2   AND starttime >= :createtime AND starttime <= :endtime ORDER BY starttime ASC", array( ':createtime' => $e-86400, ':endtime' => $e));
		$list[$j]['gnum'] = count($listone);
		$list[$j]['status4'] = $status4;
		$list[$j]['status1'] = $status1;
		$list[$j]['createtime'] =  $e-86400;
		$j++;
		$e = $e-86400;
	}
	
	$day = $hit = $status4 = $status1 = array();
	if (!empty($list)) {
		foreach ($list as $row) {
			$day[] = date('m-d', $row['createtime']);
			$hit[] = intval($row['gnum']);
			$status4[] = intval($row['status4']);
			$status1[] = intval($row['status1']);
		}
	}
	
	for ($i = 0; $i = count($hit) < 2; $i++) {
		$day[] = date('m-d', $endtime);
		$hit[] = $day[$i] == date('m-d', $endtime) ? $hit[0] : '0';
	}
	include wl_template('order/tuansummary');
	exit;
}
if ($op == 'all') {
	//更新团状态
	$groupstatus = $_GPC['groupstatus'];
	$will_die = $_GPC['will_die'];
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	/*搜索条件*/
	$allgoods = pdo_fetchall("select gname from".tablename('tg_goods')."where uniacid=:uniacid and isshow=:isshow",array(':uniacid'=>$_W['uniacid'],':isshow'=>1));
	$condition = "uniacid = {$_W['uniacid']}";
	if ($_W['user']['merchant_id'] > 0) {
		$condition .= " and  merchantid = {$_W['user']['merchant_id']} ";
	}
	$time = $_GPC['time'];
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}
	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end']);
		$condition .= " AND  starttime >= {$starttime} AND  starttime <= {$endtime} ";

	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " AND groupnumber LIKE '%{$_GPC['keyword']}%'";
	}
	if (!empty($_GPC['goods'])) {
		$condition .= " AND goodsname LIKE '%{$_GPC['goods']}%'";
	}
	if (!empty($_GPC['goods2'])) {
		$condition .= " AND (goodsname LIKE '%{$_GPC['goods2']}%' or goodsid LIKE '%{$_GPC['goods2']}%') ";
	}
	if (!empty($groupstatus)) {
		$condition .= " AND groupstatus ='{$groupstatus}'";
	}
	if (!empty($will_die)) {
		$endhour = intval($_GPC['endhour']);
		$lacknumber = intval($_GPC['lacknumber']);
		if (!empty($_GPC['goods3'])) {
			$condition .= " AND (goodsname LIKE '%{$_GPC['goods3']}%' or goodsid LIKE '%{$_GPC['goods3']}%') ";
		}
		if ($endhour) {
			$nowtime = time();
			$endtime_tuan = $nowtime + 3600;
			$condition .= " AND endtime <= '{$endtime_tuan}' ";
		}
		if ($lacknumber) {
				$condition .= " AND lacknum = {$_GPC['lacknumber']} ";
		}
	}
	/*搜索条件*/
	
	$alltuan = pdo_fetchall("select * from" . tablename('tg_group') . "where $condition  AND lacknum <>neednum order by id desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
	$nowtime = time();
	foreach ($alltuan as $key => $value) {
		$goods = goods_get_by_params(" id={$value['goodsid']} ");
		$alltuan[$key]['goods'] = $goods;
		$refund_orders = pdo_fetchall("select * from" . tablename('tg_order') . "where tuan_id='{$value['groupnumber']}' and uniacid='{$_W['uniacid']}' and status=7");
		$send_orders = pdo_fetchall("select * from" . tablename('tg_order') . "where tuan_id='{$value['groupnumber']}' and uniacid='{$_W['uniacid']}' and status in(3,4)");
		$alltuan[$key]['lasttime'] = $value['endtime'] - $nowtime;
		$alltuan[$key]['refundnum'] = count($refund_orders);
		$alltuan[$key]['sendnum'] = count($send_orders);
        $m = $value['merchantid'];
        if ($m == 0) {
            $alltuan[$key]['merchant_name'] = $_W['account']['name'];
        } else {
            $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
            $alltuan[$key]['merchant_name'] = $name['name'];
        }
	}
	$alltuan2 = pdo_fetchall("select * from" . tablename('tg_group') . "where $condition order by id desc ");
	$total = count($alltuan2);
	$pager = pagination($total, $pindex, $psize);
	
	$all = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE uniacid='{$_W['uniacid']}' AND lacknum <>neednum");
	$status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE uniacid='{$_W['uniacid']}' and groupstatus=2 AND lacknum <>neednum");
	$status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE uniacid='{$_W['uniacid']}' and groupstatus=1 AND lacknum <>neednum");
	$status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_group') . " WHERE uniacid='{$_W['uniacid']}' and groupstatus=3 AND lacknum <>neednum");
	
} elseif ($op == 'group_detail') {
	$groupnumber = intval($_GPC['groupnumber']);
	//指定团的id
	$thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' ORDER BY createtime desc");
	$goods = goods_get_by_params(" id='{$thistuan['goodsid']}' ");
} elseif ($op == 'delete') {
	$groupnumber = intval($_GPC['id']);
	//要删除的商品的id
	if (empty($groupnumber)) {
		message('未找到指定的团');
	}
	$result1 = pdo_delete('tg_group', array('groupnumber' => $groupnumber, 'uniacid' => $_W['uniacid']));
	$result = pdo_delete('tg_order', array('tuan_id' => $groupnumber, 'uniacid' => $_W['uniacid']));
	if (intval($result1) == 1) {
		message('删除团成功.', referer(), 'success');
	} else {
		message('删除团失败.');
	}
} elseif ($op == 'autogroup') {
	set_time_limit(0);
// require_once IA_ROOT . '/addons/lexiangpingou/wprint.class.php';	
	$will_die = $_GPC['will_die'];
	$filename = TG_WEB."resource/nickname.text";
	//$url=TG_WEB.'resource/images/head_imgs';
	$url='../addons/lexiangpingou/web/resource/images/head_imgs';
	$groupnumber = intval($_GPC['groupnumber']);
	//指定团的id
	$thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$orders2 = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$thistuan['goodsid']}'");
	
	//虚拟订单
	$t = time();
	$init = $orders2[0]['createtime'];
	$num = array();
	$lacknum = $thistuan['lacknum'];
	$lack = $thistuan['lacknum'];
	$head_imgs_array = get_head_img($url, $lack);
	$nickname_array = get_nickname($filename,$lack);
	$time_array = get_randtime($init,$t,$lack);
	for ($i = 0; $i < $lacknum; $i++) {
			$lack = $lack - 1;
			$data = array(
			 'uniacid' => $_W['uniacid'],
			 'gnum' => 1,
			 'openid' => $head_imgs_array[$i], 
			 'ptime' => '',
			 'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
			 'price' => 0,
			 'status' => 3,
			 'addressid' => 0, 
			 'addname' => $nickname_array[$i]['nickname'],
			 'mobile' => '虚拟', 
			 'address' => '虚拟', 
			 'g_id' => $thistuan['goodsid'], 
			 'tuan_id' => $thistuan['groupnumber'], 
			 'is_tuan' => 1, 
			 'tuan_first' => 0, 
			 'starttime' => TIMESTAMP, 
			 'createtime' => $time_array[$i]
			 );
			pdo_insert('tg_order', $data);
	}
	pdo_update('tg_group', array('lacknum' => $lack), array('groupnumber' => $thistuan['groupnumber']));
	$nowthistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	if ($nowthistuan['lacknum'] == 0) {
		pdo_update('tg_group', array('groupstatus' => 2), array('groupnumber' => $nowthistuan['groupnumber']));
		$orders3 = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟' ");
		foreach ($orders3 as $key => $value) {
			pdo_update('tg_order', array('status' => 8), array('id' => $value['id']));
		}

	}
	$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	
} elseif ($op == 'output') {
	$groupstatus = $_GPC['groupstatus'];
	if ($groupstatus == 1) {
		$str = '团购失败订单_' . time();
	}
	if ($groupstatus == 2) {
		$str = '团购成功订单_' . time();
	}
	if ($groupstatus == 3) {
		$str = '组团中订单_' . time();
	}
	if (empty($groupstatus)) {
		$str = '所有团订单_' . time();
	}
	$con = "uniacid = {$_W['uniacid']}";
	if (!empty($_GPC['goods'])) {
		$con .= " AND goodsname LIKE '%{$_GPC['goods']}%'";
	}
	if (!empty($_GPC['goods2'])) {
		$con .= " AND (goodsname LIKE '%{$_GPC['goods2']}%' or goodsid LIKE '%{$_GPC['goods2']}%') ";
	}
	if (!empty($groupstatus)) {
		$con .= " and groupstatus='{$groupstatus}' ";
	}
	if (!empty($_GPC['keyword'])) {
		$con .= " AND groupnumber LIKE '%{$_GPC['keyword']}%'";
	}
	if (!empty($_GPC['starttime'])) {
		$con .= " and starttime >='{$_GPC['starttime']}' ";
	}
	if (!empty($_GPC['endtime'])) {
		$con .= " and starttime <='{$_GPC['endtime']}' ";
	}
	$groups = pdo_fetchall("select * from" . tablename('tg_group') . "where $con ");

	$html = "\xEF\xBB\xBF";
	$filter = array('ll' => '团编号', 'mm' => '团状态', 'aa' => '订单编号', 'bb' => '姓名', 'cc' => '电话', 'dd' => '总价(元)', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'hh' => '收货地址', 'ii' => '微信订单号', 'jj' => '快递单号', 'kk' => '快递名称');
	foreach ($filter as $key => $title) {
		$html .= $title . "\t,";
	}
	//					$html .= "\n";
	foreach ($groups as $k => $v) {
		$html .= "\n";
		$orders = pdo_fetchall("select * from" . tablename('tg_order') . "where tuan_id='{$v['groupnumber']}' and uniacid='{$_W['uniacid']}'");
		if ($v['groupstatus'] == 1) {
			$tuanstatus = '团购失败';
		}
		if ($v['groupstatus'] == 2) {
			$tuanstatus = '团购成功';
		}
		if ($v['groupstatus'] == 3) {
			$tuanstatus = '组团中';
		}
		foreach ($orders as $kk => $vv) {
			if ($vv['status'] == 0) {
				$thistatus = '待付款';
			}
			if ($vv['status'] == 1) {
				$thistatus = '已支付';
			}
			if ($vv['status'] == 2) {
				$thistatus = '待收货';
			}
			if ($vv['status'] == 3) {
				$thistatus = '已签收';
			}
			if ($vv['status'] == 4) {
				$thistatus = '已退款';
			}
			if ($vv['status'] == 5) {
				$thistatus = '强退款';
			}
			if ($vv['status'] == 6) {
				$thistatus = '部分退款';
			}
			if ($vv['status'] == 7) {
				$thistatus = '已退款';
			}
			if ($vv['status'] == 8) {
				$thistatus = '待发货';
			}
			$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$vv['g_id']}' and uniacid='{$_W['uniacid']}'");
			$time = date('Y-m-d H:i:s', $vv['createtime']);
			$orders[$kk]['ll'] = $v['groupnumber'];
			$orders[$kk]['mm'] = $tuanstatus;
			$orders[$kk]['aa'] = $vv['orderno'];
			$orders[$kk]['bb'] = $vv['addname'];
			$orders[$kk]['cc'] = $vv['mobile'];
			$orders[$kk]['dd'] = $vv['price'];
			$orders[$kk]['ee'] = $thistatus;
			$orders[$kk]['ff'] = $time;
			$orders[$kk]['gg'] = $goods['gname'];
			$orders[$kk]['hh'] = $vv['address'];
			$orders[$kk]['ii'] = $vv['transid'];
			$orders[$kk]['jj'] = $vv['expresssn'];
			$orders[$kk]['kk'] = $vv['express'];
			foreach ($filter as $key => $title) {
				$html .= $orders[$kk][$key] . "\t,";
			}
			$html .= "\n";
		}

	}
	/* 输出CSV文件 */
	header("Content-type:text/csv");
	header("Content-Disposition:attachment; filename={$str}.csv");
	echo $html;
	exit();
}
include wl_template('order/will_die');exit();