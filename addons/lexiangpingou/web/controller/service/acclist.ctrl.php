<?php
defined('IN_IA') or exit('Access Denied');
if(!pdo_fieldexists('tg_function_order', 'day')) {
    pdo_query("ALTER TABLE ".tablename('tg_function_order')." ADD `day` int(11) NOT NULL default 0 COMMENT '购买使用天数';");
}
$ops = array('ajax','display', 'post', 'delete', 'status');
$op_names = array('分类列表','新增/修改分类','删除分类','设置开启');
foreach($ops as$key=>$value){
	permissions('do', 'ac', 'op', 'goods', 'category', $ops[$key], '商品', '分类管理', $op_names[$key]);
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
if ($op == 'display') {
	$_W['page']['title'] = '功能购买记录管理 - 列表';
	$cateTitle = '添加';
	$page = max(1, intval($_GPC['page']));
	$size = 10;
	$condition = " WHERE 1 ";
	
	if (!isset($_GPC['status'])) {
		$_GPC['status'] = 1;
	}
	
	
	if (!empty($_GPC['keyword'])) {
		$condition .= " and  gname like '%{$_GPC['keyword']}%'";
	}
	$sqlTotal = pdo_sql_select_count_from('tg_function_detail') . $condition;
	$sqlData = pdo_sql_select_all_from('tg_function_detail') . $condition . ' ORDER BY  id DESC ';
	$goodses = pdo_pagination($sqlTotal, $sqlData, $params, 'id', $total, $page, $size);	
	$pager = pagination($total, $page, $size);
	
	foreach($goodses as$key=>&$value){
        $account = pdo_fetch("select name from " . tablename('account_wechats') . " where  uniacid = '{$value['uniacid']}'");
		$value['gname']=$account['name'];
		$func = pdo_fetch("select name from " . tablename('tg_function') . " where  id = '{$value['functionid']}'");
		$value['functionname']=$func['name'];
		$value['endtime']=date('y-m-d',$value['endtime']);
	}

} elseif ($op == 'post') {
	
	$funclist=pdo_fetchall('select * from '.tablename('tg_function'));
	if (checksubmit('submit')) {
		$uniacid = $_GPC['publicNum'];
		$month = intval($_GPC['month']);
		$day = intval($_GPC['day']);
		$salename = $_GPC['salename'];
		$functions = $_GPC['functionValue'];
		
		$strarr = explode(",",$functions);
		foreach($strarr as $newstr){ 
		
			$func = pdo_fetch("select * from " . tablename('tg_function') . " where  id = '{$newstr}'");
				
			$data = array(
				'uniacid'     => '33',
				'buyuniacid'     => $uniacid,
				'num'        => $month,
				'day'        => $day,
				'functionid'      => $newstr,
				'openid'      => '666666',
				'ptime'       => time(),//支付成功时间
				'orderno'     => date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99)),
				'price'   => $func['price'],
				'status'=>1
			);
			pdo_insert('tg_function_order', $data);
			$func_detail = pdo_fetch("select * from " . tablename('tg_function_detail') . " where  functionid = '{$newstr}' and uniacid='{$uniacid}'");
			if(empty($func_detail))
			{
				$endtime = strtotime(date('Y-m-d', strtotime('+'.$month." month".' +'.$day." day")));
				//更改公众号功能
				$data = array(
					'uniacid'     => $uniacid,				
					'functionid'      => $newstr,				
					'endtime'       =>$endtime
				);
				pdo_insert('tg_function_detail', $data);
				
			}else{				
				$nowtime=time();
				if($nowtime>$func_detail['endtime'])
				{
					$endtime = strtotime(date('Y-m-d', strtotime('+'.$month." month".' +'.$day." day")));
				}else{
					$data1=date('Y-m-d',$func_detail['endtime']);
					$endtime = strtotime(date('Y-m-d',strtotime($data1.'+'.$month."month".' +'.$day." day")));
				}
				pdo_update('tg_function_detail', array('endtime'=>$endtime), array('id' =>$func_detail['id']));
				
			}
		}	
	}

	

}elseif($op == 'ajax')
{
	$id=$_GPC['id'];
	//$acc = pdo_fetch("select name from " . tablename('account_wechats') . " where  uniacid = '{$id}'");
	$acc = pdo_fetch_one('account_wechats', array('uniacid' => $id));
	//die(json_encode(array("name" =>$acc['name'])));	
	echo $acc['name'];
	exit;
}

include wl_template('service/acclist');exit();