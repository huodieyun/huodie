<?php
$_W['page']['title'] = "提现管理";

	$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
	
	$groupstatus = intval($_GPC['groupstatus']);
	
	
  if ($op == 'display') {
	
	$selltype = $_GPC['selltype'];
	
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	/*搜索条件*/
	$condition = "uniacid = {$_W['uniacid']}";
	$time = $_GPC['time'];
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}
	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end']) + 86399;
		$condition .= " AND  addtime >= {$starttime} AND  addtime <= {$endtime} ";

	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " AND openid  in (select from_user from " . tablename('tg_member') . " where nickname LIKE '%{$_GPC['keyword']}%') ";
	}
	if ($groupstatus!=2) {
		$condition .= " AND type ='{$groupstatus}'";
	}
	
	
	
//message($condition);
	/*搜索条件*/
	$alltuan = pdo_fetchall("select * from" . tablename('tg_cashrecord') . "where $condition order by id desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
      foreach ($alltuan as &$item) {
          if ($item['web_cash'] == 1) {
              $item['web_cash'] = '线下打款';
          } else {
              $item['web_cash'] = '线上发放';
          }
          unset($item);
      }
	$alltuan2 = pdo_fetchall("select * from" . tablename('tg_cashrecord') . "where $condition order by id desc ");
	$total = count($alltuan2);
	$pager = pagination($total, $pindex, $psize);
	
  }else if($op == 'sendcash')
  {
	 load()->func('communication');
		load()->model('account');
		wl_load()->model('setting');
		
	 $id = intval($_GPC['id']);
	 $set = setting_get_by_name('refund');
	 
	
	$m=pdo_fetch("SELECT * FROM ".tablename('tg_cashrecord')." WHERE id = {$id}");
	 $member=pdo_fetch("SELECT * FROM ".tablename('tg_member')." WHERE from_user = '{$m['openid']}' and uniacid='{$_W['uniacid']}'");
	 $pri=$m['price']+$member['cash'];
	 
	 $url5 = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
	 $apikey=$set['apikey'];
				   $pars = array();
				   $pars['mch_appid'] =$_W['account']['key'];//身份标识（appid）				 
				   $pars['mchid'] =$set['mchid'];//微信支付商户号(mchid)
				   $pars['nonce_str'] = random(32);
				   $pars['partner_trade_no'] = random(10). date('Ymd') . random(3);
				   $pars['openid'] =$m['openid'];
				   $pars['check_name'] = "NO_CHECK";				   
				   $pars['amount'] =$m['price']*100;
				   $pars['desc'] =$_W['account']['name']."佣金发放";
				   $pars['spbill_create_ip'] ="121.43.108.152";
				   ksort($pars, SORT_STRING);
					$string1 = '';
					foreach($pars as $k => $v) {
						$string1 .= "{$k}={$v}&";
					}
					$string1 .= "key={$apikey}";//商户支付秘钥（API秘钥) 
					$pars['sign'] = strtoupper(md5($string1));
					$xml = array2xml($pars);
					$extras = array();
					$extras['CURLOPT_CAINFO'] =IA_ROOT.'/addons/lexiangpingou/cert/'.$_W['uniacid'].'/rootca.pem';
					$extras['CURLOPT_SSLCERT'] =  IA_ROOT.'/addons/lexiangpingou/cert/'.$_W['uniacid'].'/apiclient_cert.pem';//证书路径
					$extras['CURLOPT_SSLKEY'] = IA_ROOT.'/addons/lexiangpingou/cert/'.$_W['uniacid'].'/apiclient_key.pem';//证书路径

					
					$procResult = null; 
					$resp = ihttp_request($url5, $xml, $extras);
					
					if(is_error($resp)) {
						$procResult = $resp;
					} else {
						$xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
						$dom = new DOMDocument();
						if($dom->loadXML($xml)) {
							$xpath = new DOMXPath($dom);
							$code = $xpath->evaluate('string(//xml/return_code)');
							$ret = $xpath->evaluate('string(//xml/result_code)');
							if(strtolower($code) == 'success' && strtolower($ret) == 'success') {
								 pdo_update('tg_member', array('cash' => $pri), array('id' => $member['id']));
								 pdo_update('tg_cashrecord', array('type' => 1,'ptime'=>TIMESTAMP), array('id' => $id));
								 $tip='发放成功';
		echo "<script>alert('".$tip."');location.href='".web_url('member/cash')."';</script>";
			exit;
								
							} else {
								 $tip='付款失败,请检查商户余额1';
		
								
								$error = $xpath->evaluate('string(//xml/err_code_des)');
								$procResult = error(-2, $error);
								echo "<script>alert('".$error."');location.href='".web_url('member/cash')."';</script>";
			exit;
							}
						} else {
							   $tip='付款失败,请检查商户余额2';
		echo "<script>alert('".$tip."');location.href='".web_url('member/cash')."';</script>";
			exit;
								
							$procResult = error(-1, 'error response');
						}
					}
	 
  }

if ($op == 'web_cash') {

    $record = pdo_get('tg_cashrecord' , array('id' => $id));
    $saler = pdo_get('tg_member' , array('uniacid' => $record['uniacid'] , 'from_user' => $record['openid']));
    $ds = $saler;

}

if ($op == 'cash_add') {

    $id = $_GPC['id'];
    $record = pdo_get('tg_cashrecord', array('id' => $id));
    if ($record['type'] == 1) {
        $res = 0;
        $tip = '非常抱歉！该佣金已发放';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('member/cash') . "';</script>";
        exit;
    }
    $member = pdo_get('tg_member', array('openid' => $record['openid'],'uniacid'=>$record['uniacid']));
    $res = pdo_update('tg_member', array(
        'cash' => floatval($member['cash']) + floatval($record['price'])
    ), array('id' => $member['id']));
    $res = pdo_update('tg_cashrecord', array('type' => 1, 'web_cash' => 1, 'ptime' => TIMESTAMP), array('id' => $id));
    if ($res) {
        $tip = '发放成功';
    } else {
        $tip = '发放失败';
    }
    echo "<script>alert('" . $tip . "');location.href='" . web_url('member/cash') . "';</script>";
    exit;
    die(json_encode(array('status' => $res , 'message' => $tip)));
}


if ($op == 'query_member') {
    $con = " uniacid = '{$_W['uniacid']}' AND enable = 1 ";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and nickname LIKE '%{$keyword}%'";
    }
    $ds = pdo_fetchall("select * from " . tablename('tg_member') . " where {$con} ");
    include wl_template('store/query_saler');
    exit;
}

include wl_template('member/cash');
exit();
?>