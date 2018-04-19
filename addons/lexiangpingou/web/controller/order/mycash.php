<?
       global $_GPC, $_W;
       $op= !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        $weid = $_W['uniacid'];
        $openid = $_W['openid'];
        $mem = pdo_fetch("SELECT id,wallet,cash,from_user FROM " . tablename('tg_member')."where from_user='{$openid}' and uniacid='{$weid}'");
    	if (checksubmit('submit')) { 
            if(empty($_GPC['name']))
			{
				message('请填写金额', referer(), 'error');
			}
			$wallet=number_format($mem['wallet'],2);
			$cash=number_format($mem['cash'],2);
			if($wallet<$_GPC['name'])
			{
				message('提现金额不能大于提成', referer(), 'error');
			}
			if($_GPC['name']<1)
			{
				message('提现金额不能小于1元', referer(), 'error');
			}
			pdo_update('tg_member',array('wallet'=>$wallet-$_GPC['name']),array('id'=>$mem['id']));
			$bdata = array(
					'uniacid' => $_W['uniacid'],
					'openid' => $openid,
					'type' => 0,
					'addtime' => TIMESTAMP,
					'price' => $_GPC['name']
					);
					pdo_insert('tg_cashrecord', $bdata);
					message('申请成功', referer(), 'success');
        }
		if($op==1)
		{
			$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_cashrecord') . " WHERE uniacid ='{$_W['uniacid']}' and type=0 and openid='{$_W['openid']}'");

		}
		if($op==2)
		{
			$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_cashrecord') . " WHERE uniacid ='{$_W['uniacid']}' and type=1 and openid='{$_W['openid']}'");

		}
		$orders1 = pdo_fetch("SELECT sum(price) as tprice FROM " . tablename('tg_cashrecord') . " WHERE uniacid ='{$_W['uniacid']}' and type=0 and openid='{$_W['openid']}'");
		$pr=0;
		if($orders1['tprice']>0)
		{
			$pr=$orders1['tprice'];
		}
        include $this->template('cash');
    exit();
 ?>