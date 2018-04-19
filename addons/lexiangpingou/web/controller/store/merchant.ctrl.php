<?php
	wl_load()->model('merchant');
	$ops = array('display','edit', 'delete', 'order','account','record','data','permissions','search');
	$op_names = array('商家列表','编辑/添加商家', '删除', '商家中心管理','商家结算中心','结算记录','订单统计','权限管理','查找粉丝');
	foreach($ops as$key=>$value){
		permissions('do', 'ac', 'op', 'store', 'merchant', $ops[$key], '商家管理', '商家结算', $op_names[$key]);
	}
	$op = in_array($op, $ops) ? $op : 'account';

    //结算
	if ($op == 'account') {
		$id = $_GPC['id'];
		$order = pdo_fetchall("SELECT * FROM ".tablename('tg_order')." WHERE uniacid = {$_W['uniacid']} and id={$id}");
		$merchant = pdo_fetch("SELECT * FROM ".tablename('tg_merchant')." WHERE uniacid = {$_W['uniacid']} and id={$id}");
		$account =  pdo_fetch("SELECT amount,no_money FROM ".tablename('tg_merchant_account')." WHERE uniacid = {$_W['uniacid']} and merchantid={$id}");
		if (checksubmit('submit')) {
			$money = $_GPC['money'];
			$mm = $money;
			if(!empty($merchant['percent'])){
				$money = (1-$merchant['percent']*0.01)*$money;
			}else{
				$money = $money;
			}
			$no_money = merchant_get_no_money($id);
			if(is_numeric($money)){
				if($money<1){
					message('到账金额需要大于1元！', referer(), 'error');
					exit;
				}
			if($no_money<$money){
				message('您没有足够的可结算金额！', referer(), 'error');exit;
			}
			$result = finance($merchant['openid'], 1, $money * 100, '', '商家提现');
			if($result){
					$res=merchant_update_no_money(0-$mm, $id);
					if($res){
						pdo_insert("tg_merchant_record",array('merchantid'=>$id,'money'=>$mm,'uid'=>$_W['uid'],'createtime'=>TIMESTAMP,'uniacid'=>$_W['uniacid']));
					}
				}
				if (is_error($result)) {
						message('微信钱包提现失败: ' . $result['message'], '', 'error');exit;
				}
			}else{
				message('结算金额输入错误！', referer(), 'error');
				return false;
			}
			message('结算成功！', referer(), 'success');
		}
		include wl_template('store/merchant/account');
	}

	//结算记录
	if($op == 'record') {
		$id = $_GPC['id'];
		$merchant = pdo_fetch("SELECT thumb,name,openid FROM ".tablename('tg_merchant')." WHERE uniacid = {$_W['uniacid']} and id={$id}");
		$list = pdo_fetchall("select * from".tablename('tg_merchant_record')."where merchantid='{$id}' and uniacid={$_W['uniacid']} ");
		include wl_template('store/merchant/account');
	}

	exit();
?>