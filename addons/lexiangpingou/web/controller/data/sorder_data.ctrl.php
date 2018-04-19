<?php
defined('IN_IA') or exit('Access Denied');
wl_load()->model('merchant');
wl_load()->func('print');

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

if($op =='display'){
	$condition = "  uniacid = :uniacid and isshow != 4";
	$condition1 = " and  uniacid = :uniacid";
	$condition2 = "  uniacid = :uniacid";
	$paras = array(':uniacid' => $_W['uniacid']);
	$time = $_GPC['time'];
	
	$allgoods = pdo_fetchall("select gname,id from".tablename('tg_goods')."where uniacid=:uniacid ",array(':uniacid'=>$_W['uniacid']));
	$allstore = pdo_fetchall("select storename,id from".tablename('tg_store')."where uniacid=:uniacid ",array(':uniacid'=>$_W['uniacid']));
	//商品名称
	if(trim($_GPC['goodsid2'])!=''){
		$condition .= " and id ='{$_GPC['goodsid2']}' ";
	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " and  gname like '%{$_GPC['keyword']}%'";
	}
	//判断订单类型
	if(!empty($_GPC['dispatchtype']))
	{
		$_SESSION['dispatchtype']=$_GPC['dispatchtype'];
		$dispatchtype=$_SESSION['dispatchtype'];
	}
	if(!empty($dispatchtype)&&$dispatchtype!=0&&$dispatchtype!=-1)
	{
		$condition1 .= " AND  dispatchtype = '{$_GPC['dispatchtype']}'";
		$condition2 .= " AND  dispatchtype = '{$_GPC['dispatchtype']}'";
	}
	if($dispatchtype==-1)
	{
		$condition1 .= " AND  selltype = 5 ";
		$condition2 .= " AND  selltype = 5 ";
	}
	//时间初始值
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 week');
		$endtime = time();
	}
	//判断是否选择已提
	if(trim($_GPC['status'])==''){
		$condition3 = " status in (1,2,3,8) ";
	}
	if(trim($_GPC['status'])!=''){
		if ($_GPC['status']==3) {
		$condition1 .= " AND  status = {$_GPC['status']} ";
		$condition2 .= " AND  status = {$_GPC['status']} ";
		$condition3 = "   status = {$_GPC['status']} ";
		}
		else {
		$condition1 .= " AND  status in (1,2,8) ";
		$condition2 .= " AND  status in (1,2,8) ";
		$condition3 = "   status in (1,2,8)  ";
		}
	}
	//判断是否有自提点筛选
	if(trim($_GPC['storeid'])!=''){
		$condition1 .= " AND  comadd = {$_GPC['storeid']} ";
		$condition2 .= " AND  comadd = {$_GPC['storeid']} ";
		$condition3 .= " AND  comadd = {$_GPC['storeid']}";
	}
	//时间有值时的添加
	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end'])+86400 ;
		$condition1 .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
		$condition2 .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
	}
	//分页设置
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_goods') . " where ".$condition ."  ORDER BY createtime DESC ", $paras);
	$pager = pagination($total, $pindex, $psize);
	
	$sql = "select  gname,id,gprice,oprice,salenum from " . tablename('tg_goods') . " where ".$condition."  ORDER BY createtime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql, $paras);
	//根据产品id遍历order表
	foreach($list as &$v)
	{
		$paras=array(':uniacid'=>$_W['uniacid']);
		$sqls = "select g_id,orderno,status from " . tablename('tg_order') . " where ".$condition2."    " ;
		$lists = pdo_fetchALL($sqls, $paras);
		foreach($lists as &$vs)
		{
			//订单类型判断分别查询两表统计售货数目
			if ($vs['g_id']>0){
				$condition3 =" orderno = '". $vs['orderno'] ."' and g_id=" . $v['id'] ." ";
				$sqlyn = "select price * gnum / price as gnums from " . tablename('tg_order') . " where " . $condition3 . "  ".$condition1." ".$condition4."   " ;
				if ($vs['status']==3){
					$condition4 =" and status = 3 ";
					$listyt = pdo_fetch($sqlyn, $paras);
					$v['yt']+=$listyt['gnums'];
				}
				else if($vs['status']==1 or $vs['status']==2 or $vs['status']==8){
					$condition4 =" and status in (1,2,8) ";
					$listwt = pdo_fetch($sqlyn, $paras);
					$v['wt']+=$listwt['gnums'];
				}
				$condition4 =" and status in(1,2,3,8) ";
				$sqlso = "select gnum, price * gnum as price,price * gnum / price as gnums from " . tablename('tg_order') . " where " . $condition3 . "  ".$condition1." ".$condition4."  " ;
				$listso = pdo_fetch($sqlso, $paras);
				$v['gumsy']+=$listso['gnums'];
				$v['pricey']+=$listso['price'];
			}
			else if($vs['g_id']==0){
				$sqlyn = "select sum(num) as num from " . tablename('tg_collect') . " where orderno = '". $vs['orderno'] ."' and sid=" . $v['id'] ." and uniacid = :uniacid ";
				if ($vs['status']==3){
					$listyt = pdo_fetch($sqlyn, $paras);	
					$v['yt']+=$listyt['num'];
				}
				else if($vs['status']==1 or $vs['status']==2 or $vs['status']==8){
					$listwt = pdo_fetch($sqlyn, $paras);	
					$v['wt']+=$listwt['num'];
				}
				if ($vs['status']==1 or $vs['status']==2 or $vs['status']==3 or $vs['status']==8){
					$sqlsc = "select sum(num) as num, oprice * num as price from " . tablename('tg_collect') . " where orderno = '". $vs['orderno'] ."' and sid=" . $v['id'] ." and uniacid = :uniacid  " ;
					$listsc = pdo_fetch($sqlsc, $paras);
					$v['gumsg']+=$listsc['num'];
					$v['priceg']+=$listsc['price'];
				}
				
			}
			$v['sumorder']=$v['gumsy']+$v['gumsg'];
			$v['sumprice']=$v['pricey']+$v['priceg'];
		}
			
	}		
	include wl_template('data/sorder_data');
}

	
if($op == 'output'){
	$condition = "  uniacid = :uniacid and isshow != 4";
	$condition1 = "and uniacid = :uniacid";
	$condition2 = "  uniacid = :uniacid";
	$paras = array(':uniacid' => $_W['uniacid']);

	if(trim($_GPC['goodsid2'])!=''){
		$condition .= " and id ='{$_GPC['goodsid2']}' ";
	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " and  gname like '%{$_GPC['keyword']}%'";
	}
	//判断订单类型
	if(!empty($_GPC['dispatchtype']))
	{
		$_SESSION['dispatchtype']=$_GPC['dispatchtype'];
		$dispatchtype=$_SESSION['dispatchtype'];
	}
	if(!empty($dispatchtype)&&$dispatchtype!=0&&$dispatchtype!=-1)
	{
		$condition1 .= " AND  dispatchtype = '{$_GPC['dispatchtype']}'";
		$condition2 .= " AND  dispatchtype = '{$_GPC['dispatchtype']}'";
	}
	if($dispatchtype==-1)
	{
		$condition1 .= " AND  selltype = 5 ";
		$condition2 .= " AND  selltype = 5 ";
	}
	//时间初始值
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 week');
		$endtime = time();
	}
	//判断是否选择已提
	if(trim($_GPC['status'])==''){

	}
	if(trim($_GPC['status'])!=''){
		if ($_GPC['status']==3) {
		$condition1 .= " AND  status = {$_GPC['status']} ";
		$condition2 .= " AND  status = {$_GPC['status']} ";

		}
		else {
		$condition1 .= " AND  status in (1,2,8) ";
		$condition2 .= " AND  status in (1,2,8) ";

		}
	}
	//判断是否有自提点筛选
	if(trim($_GPC['storeid'])!=''){
		$condition1 .= " AND  comadd = {$_GPC['storeid']} ";
		$condition2 .= " AND  comadd = {$_GPC['storeid']} ";

	}
	//时间有值时的添加
	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end'])+86400 ;
		$condition1 .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
		$condition2 .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
	}

	$sql = "select  gname,id,gprice,oprice,salenum from " . tablename('tg_goods') . " where ".$condition."  ORDER BY createtime DESC  "  ;
	$list = pdo_fetchall($sql, $paras);
	//根据产品id遍历order表
	foreach($list as &$list_v)
	{
		$paras=array(':uniacid'=>$_W['uniacid']);
		$sqls = "select g_id,orderno,status from " . tablename('tg_order') . " where ".$condition2."    " ;
		$lists = pdo_fetchALL($sqls, $paras);
		foreach($lists as &$list_vs)
		{
			//订单类型判断分别查询两表统计售货数目
			if ($list_vs['g_id']>0){
				$condition3 =" orderno = '". $list_vs['orderno'] ."' and g_id=" . $list_v['id'] ." ";
				$sqlyn = "select price * gnum / price as gnums from " . tablename('tg_order') . " where " . $condition3 . "  ".$condition1." ".$condition4."   " ;
				if ($list_vs['status']==3){
					$condition4 =" and status = 3 ";
					$listyt = pdo_fetch($sqlyn, $paras);
					$list_v['yt']+=$listyt['gnums'];
				}
				else if($list_vs['status']==1 or $list_vs['status']==2 or $list_vs['status']==8){
					$condition4 =" and status in (1,2,8) ";
					$listwt = pdo_fetch($sqlyn, $paras);
					$list_v['wt']+=$listwt['gnums'];
				}
				$condition4 =" and status in(1,2,3,8) ";
				$sqlso = "select gnum, price * gnum as price,price * gnum / price as gnums from " . tablename('tg_order') . " where " . $condition3 . "  ".$condition1." ".$condition4."  " ;
				$listso = pdo_fetch($sqlso, $paras);
				$list_v['gumsy']+=$listso['gnums'];
				$list_v['pricey']+=$listso['price'];
			}
			else if($list_vs['g_id']==0){
				$condition5 =" orderno = '". $list_vs['orderno'] ."' and sid=" . $list_v['id'] ." and uniacid = :uniacid ";
				$sqlyn = "select sum(num) as num from " . tablename('tg_collect') . " where " . $condition5 . " ";
				if ($list_vs['status']==3){
					$listyt = pdo_fetch($sqlyn, $paras);	
					$list_v['yt']+=$listyt['num'];
				}
				else if($list_vs['status']==1 or $list_vs['status']==2 or $list_vs['status']==8){
					$listwt = pdo_fetch($sqlyn, $paras);	
					$list_v['wt']+=$listwt['num'];
				}
				if ($list_vs['status']==1 or $list_vs['status']==2 or $list_vs['status']==3 or $list_vs['status']==8){
					$sqlsc = "select sum(num) as num, oprice * num as price from " . tablename('tg_collect') . " where " . $condition5 . "  " ;
					$listsc = pdo_fetch($sqlsc, $paras);
					$list_v['gumsg']+=$listsc['num'];
					$list_v['priceg']+=$listsc['price'];
				}
				
			}
			$list_v['sumorder']=$list_v['gumsy']+$list_v['gumsg'];
			$list_v['sumprice']=$list_v['pricey']+$list_v['priceg'];
		}
			
	}	
	

			//message($condition);

	/* 输入到CSV文件 */
		$html = "\xEF\xBB\xBF";
	/* 输出表头 */
	$filter = array('aa' => '商品名', 'bb' => '团购价', 'cc' => '单买价', 'dd' => '销售数量', 'ee' => '销售金额', 'ff' => '已提', 'gg' => '未提');
	foreach ($filter as $key => $title) {
		$html .= $title . "\t,";
	}
	$html .= "\n";
	
	foreach ($list as $k => $v_out) {
			$list[$k]['aa'] = $v_out['gname'];
			$list[$k]['bb'] = $v_out['gprice'];
			$list[$k]['cc'] = $v_out['oprice'];
			$list[$k]['dd'] = $v_out['sumorder'];
			$list[$k]['ee'] = $v_out['sumprice'];
			$list[$k]['ff'] = $v_out['yt'];
			$list[$k]['gg'] = $v_out['wt'];

			
			foreach ($filter as $key => $title) {
				$html .= $list[$k][$key] . "\t,";
			}
			$html .= "\n";
	}
	
	switch($_GPC['dispatchtype']){
		case NULL: 
		$str = '商品销售单-全部订单_' . time();
		break;
		case 1: 
		$str = '商品销售单-送货上门订单_' . time();
		break;
		case -1: 
		$str = '商品销售单-抽奖订单_' . time();
		break;
		case 2: 
		$str = '商品销售单-快递订单_' . time();
		break;
		case 3: 
		$str = '商品销售单-自提订单_' . time();
		break;

	}
	/* 输出CSV文件 */
	header("Content-type:text/csv");
	header("Content-Disposition:attachment; filename={$str}.csv");
	echo $html;
	exit();
	
}
exit();