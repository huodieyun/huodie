<?php

defined('IN_IA') or exit('Access Denied');
$_W['page']['title'] = '短信管理';
$ops = array('buy','send','list','display','sendsms','import','output','ajax','clear','clearmobile');
$op=$_GPC['op'];
$op = in_array($op, $ops) ? $op : 'display';
$wechat=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=:uniacid",array(':uniacid'=>$_W['uniacid']));
if($op=='list')
{
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	$condition = "  uniacid = {$_W['uniacid']}";
	$status=$_GPC['status'];
	$time = $_GPC['time'];
	$mobile=$_GPC['mobile'];
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}

	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end'])+86400 ;
		$condition .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
	}
	if(!empty($status))
	{
		$condition.=" and status={$status}";
	}
	if(!empty($mobile))
	{
		$condition .= " AND  mobile LIKE '%{$_GPC['mobile']}%'";
	}
	$sql = "select  * from " . tablename('tg_sms_record') . " where $condition  ORDER BY createtime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql);
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_sms_record') . " WHERE $condition ");
	$pager = pagination($total, $pindex, $psize);
}
if($op=='buy')
{
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	$condition = "  buyuniacid = {$_W['uniacid']}";
	$status=1;
	$time = $_GPC['time'];
	$mobile=$_GPC['mobile'];
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}

	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end'])+86400 ;
		$condition .= " AND  ptime >= {$starttime} AND  ptime <= {$endtime} ";
	}
	if(!empty($status))
	{
		$condition.=" and status={$status}";
	}
	if(!empty($mobile))
	{
		$condition .= " AND  mobile LIKE '%{$_GPC['mobile']}%'";
	}
	$condition.=" and functionid>=8178 and functionid<=8182";
	$sql = "select  * from " . tablename('tg_function_order') . " where $condition  ORDER BY ptime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql);
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_function_order') . " WHERE $condition ");
	$pager = pagination($total, $pindex, $psize);
}
if($op=='send')
{
	$telnum=pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_sms_mobile') . " WHERE uniacid=:uniacid ",array(':uniacid'=>$_W['uniacid']));
	if(!empty($_GPC['smscontent']))
	{
		$ytime=strtotime(date("Y-m-d",strtotime("-1 day")));
		$ttime=strtotime(date("Y-m-d",strtotime("+1 day")));
		
		$adds=pdo_fetchall("select * from ".tablename('tg_sms_mobile'). " WHERE uniacid=:uniacid ",array(':uniacid'=>$_W['uniacid']));
		$bnum=0;
		foreach($adds as $key=>$da)
		{			
			$rec=pdo_fetchcolumn("select COUNT(*) from ".tablename('tg_sms_record'). " where mobile=:mobile and status in (1,2) and createtime>=:ytime and createtime<:ttime",array(':mobile'=>$da['mobile'],':ytime'=>$ytime,':ttime'=>$ttime));
			if($rec<4)
			{
				$data=array(
				'uniacid'=>$da['uniacid'],
				'name'=>$da['name'],
				'mobile'=>$da['mobile'],
				'createtime'=>time(),
				'content'=>$_GPC['smscontent'],
				'status'=>1
				);
				pdo_insert('tg_sms_record', $data);
				$bnum+=1;
			}
			 
		}
		$bdata=array(
		'errno'=>0,
		'message'=>'即将发送'.$bnum.'条短信,请不要关闭当前页面'
		);
	die(json_encode($bdata));
		exit();
		//message('即将发送'.$telnum.'条短信');
	}
	
/*
	if(!empty($_GPC['smscontent']))
{
	
		
}
*/	
	
	
	/*
	$adds=pdo_fetchall("select uniacid,addname,mobile from ".tablename('tg_order'));
	foreach($adds as $key=>$da)
	{
		if(preg_match("/^1[34578]{1}\d{9}$/",trim($da['mobile']))){ 
		 $data=array(
			'uniacid'=>$da['uniacid'],
			'name'=>$da['addname'],
			'mobile'=>$da['mobile'],
			'createtime'=>time()
			);
		 $mob=pdo_fetch("select * from ".tablename('tg_sms_mobile')." where uniacid=:uniacid and mobile=:mobile",array(':uniacid'=>$da['uniacid'],':mobile'=>$da['mobile']));
		 if(empty($mob))
		 {
			 pdo_insert('tg_sms_mobile', $data);
		 }
		}
	}*/
}
if($op=='sendsms')
{
	//查询账户剩余短信条数
	
	if($wechat['smsnum']>0)
	{
		$sendnum=pdo_fetch("select * from".tablename('tg_sms_record')." where status=1 and uniacid=:uniacid order by createtime asc limit 1",array(':uniacid'=>$_W['uniacid']));
		if(!empty($sendnum))
		{
			//获取今天 本手机号 发送次数
			$sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
			
			
			if($_W['uniacid']=='481')
			{
				$smsConf = array(
				'key'   => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
					'mobile'    => $sendnum['mobile'], //接受短信的用户手机号码
					'tpl_id'    => '20748', //尊敬的用户，您#orderno#请注意及时参与！--#app#
					'tpl_value' =>'#orderno#='.$sendnum['content'] //您设置的模板变量，根据实际情况修改
				);
			}else{
				$smsConf = array(
				'key'   => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
					'mobile'    => $sendnum['mobile'], //接受短信的用户手机号码
					'tpl_id'    => '20283', //尊敬的用户，您#orderno#请注意及时参与！--#app#
					'tpl_value' =>'#orderno#='.$sendnum['content'].'&#app#='.$_W['uniaccount']['name'] //您设置的模板变量，根据实际情况修改
				);
			}
			
			$content = juhecurl($sendUrl,$smsConf,1); //请求发送短信
			if($content){
			$result = json_decode($content,true);
			$error_code = $result['error_code'];
			if($error_code == 0){
				//$result['result']['fee']  扣除短信条数
				$nowsmsnum=$wechat['smsnum']-$result['result']['fee'];
				pdo_update('account_wechats', array('smsnum'=>$nowsmsnum), array('uniacid' => $_W['uniacid']));
				pdo_update('tg_sms_record', array('status'=>2,'num'=>$result['result']['fee']), array('id' => $sendnum['id']));
				$telnum=pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_sms_record') . " WHERE uniacid=:uniacid and status=1",array(':uniacid'=>$_W['uniacid']));
				$nowwechat=pdo_fetch("select * from ".tablename('account_wechats')." where uniacid=:uniacid",array(':uniacid'=>$_W['uniacid']));

					$bdata=array(
					'errno'=>0,
					'message'=>'您剩余的短信条数'.$nowsmsnum.'条,还有'.$telnum.'待发送'
					);
					die(json_encode($bdata));
			}else{
				//状态非0，说明失败
				$msg = $result['reason'];
				//echo "短信发送失败(".$error_code.")：".$msg;
				$bdata=array(
					'errno'=>3,
					'message'=>$sendnum['mobile']."短信发送失败".$error_code.$msg
					);
					die(json_encode($bdata));
			}
			}else{
				//返回内容异常，以下可根据业务逻辑自行修改
				echo "请求发送短信失败";
			}
		}else{
			$bdata=array(
					'errno'=>2,
					'message'=>'本次群发已完毕'
					);
					die(json_encode($bdata));
		}
		
	}else{
		$bdata=array(
		'errno'=>1,
		'message'=>'您剩余的短信条数'.$wechat['smsnum'].'条,请购买后继续发送'
		);
		die(json_encode($bdata));
		exit();
	}
	
}
if($op=='import')
{
	$file = $_FILES['fileName'];
	$max_size = "2000000";
	$fname = $file['name'];
	$ftype = strtolower(substr(strrchr($fname, '.'), 1));
	//文件格式
	$uploadfile = $file['tmp_name'];
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (is_uploaded_file($uploadfile)) {
			if ($file['size'] > $max_size) {
				echo "Import file is too large";
				exit ;
			}
			if ($ftype == 'xls') {
				require_once '../framework/library/phpexcel/PHPExcel.php';
				$objReader = PHPExcel_IOFactory::createReader('Excel5');
				$objPHPExcel = $objReader -> load($uploadfile);
				$sheet = $objPHPExcel -> getSheet(0);
				$highestRow = $sheet -> getHighestRow();
				$succ_result = 0;
				$error_result = 0;
				for ($j = 2; $j <= $highestRow; $j++) {
					$name = trim($objPHPExcel -> getActiveSheet() -> getCell("A$j") -> getValue());
					$mobile = trim($objPHPExcel -> getActiveSheet() -> getCell("B$j") -> getValue());
					
					if (!empty($mobile)) {
						if(preg_match("/^1[34578]{1}\d{9}$/",trim($mobile))){ 
							 $data=array(
								'uniacid'=>$_W['uniacid'],
								'name'=>$name,
								'mobile'=>$mobile,
								'createtime'=>time()
								);
							 $mob=pdo_fetch("select * from ".tablename('tg_sms_mobile')." where uniacid=:uniacid and mobile=:mobile",array(':uniacid'=>$_W['uniacid'],':mobile'=>$mobile)); if(empty($mob))
							 {
								 pdo_insert('tg_sms_mobile', $data);
							 }
						}
					} 
				}
			}elseif ($ftype == 'csv') {
			    if (empty ($uploadfile)) { 
			        echo '请选择要导入的CSV文件！'; 
			        exit; 
			    } 
			    $handle = fopen($uploadfile, 'r'); 
				$n = 0; 
			    while ($data = fgetcsv($handle, 10000)) { 
			        $num = count($data); 
			        for ($i = 0; $i < $num; $i++) { 
			            $out[$n][$i] = $data[$i]; 
			        } 
			        $n++; 
			    } 
			    $result = $out; //解析csv 
			    $len_result = count($result); 
			    if($len_result==0){ 
			        echo '没有任何数据！'; 
			        exit; 
			    } 
				$succ_result = 0;
				$error_result = 0;
			    for ($i = 1; $i < $len_result; $i++) { //循环获取各字段值 
			        $name = trim(iconv('gb2312', 'utf-8', $result[$i][0])); //中文转码 
			        if($name==''){			
			        	continue;
			        }
			      
			        $mobile = trim(iconv('gb2312', 'utf-8', $result[$i][1])); 
			       
					
					if (!empty($mobile)) {
						if(preg_match("/^1[34578]{1}\d{9}$/",trim($mobile))){ 
							 $data=array(
								'uniacid'=>$_W['uniacid'],
								'name'=>$name,
								'mobile'=>$mobile,
								'createtime'=>time()
								);
							 $mob=pdo_fetch("select * from ".tablename('tg_sms_mobile')." where uniacid=:uniacid and mobile=:mobile",array(':uniacid'=>$_W['uniacid'],':mobile'=>$mobile));
							 if(empty($mob))
							 {
								 pdo_insert('tg_sms_mobile', $data);
							 }
						}
					}
			    } 
			    fclose($handle); //关闭指针 
			}else{
				echo "文件后缀格式必须为xls或csv";
				exit ;
			}
		} else {
			echo "文件名不能为空!";
			exit ;
		}
	}
	message('导入手机号操作成功！', referer(), 'success');
}
if($op == 'output'){
	/* 输入到CSV文件 */
	$html = "\xEF\xBB\xBF";
	/* 输出表头 */
	$filter = array('aa' => '姓名','ll'=>'电话号码');
	foreach ($filter as $key => $title) {
		$html .= $title . "\t,";
	}
	$html .= "\n";
	/* 输出CSV文件 */
	header("Content-type:text/csv");
	header("Content-Disposition:attachment; filename=mobile.csv");
	echo $html;
	exit();
}

if ($op == 'ajax') {
	wl_load()->model('setting');
	$setting=setting_get_by_name("sms");
	if($_GPC['isajax'])
	{
		$value = array(
			'm_smspay'=>$_GPC['m_smspay'],
			'm_smsbuy'=>$_GPC['m_smsbuy'],
            'm_smschange'=>$_GPC['m_smschange'],
            'm_smsnocash'=>$_GPC['m_smsnocash'],
            'm_smsref'=>$_GPC['m_smsref'],
            'm_smssend'=>$_GPC['m_smssend'],
			'm_smstuan'=>$_GPC['m_smstuan']
		);
	if(empty($setting)){
		
		$data = array(
			'uniacid'=>$_W['uniacid'],
			'key'=>'sms',
			'value'=>serialize($value)
		);
		setting_insert($data);
		die(json_encode(array('errno'=>0,'message'=>"保存成功")));
	}else{
		
		setting_update_by_params(array('value'=>serialize($value)), array('key'=>'sms','uniacid'=>$_W['uniacid']));
		die(json_encode(array('errno'=>0,'message'=>"保存成功")));
	}
	}
	
}
if($op=='clear')
{
	$result = pdo_delete('tg_sms_record', array('uniacid' => $_W['uniacid'],'status'=>1));
	die(json_encode(array('errno'=>0,'message'=>"清理成功")));
}
if($op=='clearmobile')
{
	$result = pdo_delete('tg_sms_mobile', array('uniacid' => $_W['uniacid']));
	die(json_encode(array('errno'=>0,'message'=>"清理成功")));
}
include wl_template('member/smsbuy');
exit();