<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 团详情控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
wl_load()->model('functions');
$checkfunction=checkfunc(8167);
$tuan_id = intval($_GPC['tuan_id']);
$member=member_get_by_params("from_user='".$_W['openid']."' and uniacid='".$_W['uniacid']."'");
$tourl = app_url('order/group', array('tuan_id'=>$tuan_id));

$group = pdo_fetch("SELECT neednum,lacknum,groupstatus FROM " . tablename('tg_group') . " where groupnumber='{$tuan_id}' ");
$count = pdo_fetch("SELECT COUNT(*) as num FROM " . tablename('tg_order') . " where tuan_id='{$tuan_id}' and status in (1,2,3,8) ");

if($count['num']<$group['neednum']){
	$num = $group['neednum']-$count['num'];//需要人数-已由人数求得实际所需人数
	pdo_update('tg_group',array('lacknum'=>$num),array('groupnumber'=>$tuan_id));
}else if($count['num']=$group['neednum']){
	pdo_update('tg_group',array('groupstatus'=> 2 ),array('groupnumber'=>$tuan_id));
}

if($checkfunction['status']&&$member['enable']==1)
{
	$tourl=app_url('order/group', array('tuan_id'=>$tuan_id,'sharenum'=>$member['id']));
	
}

if($checkfunction['status']&&intval($_GPC['sharenum'])!=0&&$member['parentid']==-1)
{
	
	$data=array('parentid'=>$_GPC['sharenum']);
	$params=array('from_user'=>$_W['openid']);
	member_update_by_params($data,$params);	
}
$member=member_get_by_params("from_user='".$_W['openid']."'");
if(!$checkfunction['status']||$member['parentid']==-1)
{
	$data=array('parentid'=>99);
	$params=array('from_user'=>$_W['openid']);
	member_update_by_params($data,$params);
}

if(!empty($tuan_id)){
    //取得该团所有订单
	$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " where is_tuan in(1,3) and status in(1,2,3,4,5,6,7,8,10) and tuan_id='{$tuan_id}' and uniacid = {$_W['uniacid']}");

    //$data = order_get_list(array('is_tuan'=>'1,3','status'=>' 1,2,3,4,5,6,7,8','tuan_id'=>$tuan_id));
	//$orders = $data['list'];
	//message(count($orders));
    foreach($orders as $key =>$value){
    	if($value['address']=='虚拟'){
    		$orders[$key]['avatar'] = $value['openid'];
			$orders[$key]['nickname'] =  $value['addname'];
    	}else{
			$fans = member_get_by_params(" openid = '{$value['openid']}'");
			if (!empty($fans)) {
				$avatar = $fans['avatar'];
				$nickname=$fans['nickname'];
			}
    		$orders[$key]['avatar'] = $avatar;
			$orders[$key]['nickname'] = $nickname;
    	}
    }

    //取团长订单$order
    $order = order_get_by_params(" tuan_id = {$tuan_id} and tuan_first = 1 ");
	if($order['address']=='虚拟'){
    		$order['avatar'] = $order['openid'];
			$order['nickname'] =  $order['addname'];
    	}else{
			$fans = member_get_by_params(" openid = '{$order['openid']}'");
			if (!empty($fans)) {
				$avatar = $fans['avatar'];
				$nickname=$fans['nickname'];
			}
    		$order['avatar'] = $avatar;
			$order['nickname'] = $nickname;
    	}
		// 查出该商品的所有团购订单的团购ID
				$sql4  = "select * from".tablename('tg_group')."where goodsId=:g_id and groupstatus=2 limit 0,10";
				$params4  = array(':g_id' => $order['g_id']);
				$allorder = pdo_fetchall($sql4,$params4);
				foreach ($allorder as $key => $value) 
				{
					$data['content']="恭喜团单号:".$value['groupnumber']."团组团成功，我们尽快安排发货<br>";
					$sendadd[]=$data;
				}
   //自己的订单，若没有参团则$myorder为空
    $myorder = order_get_by_params(" openid = '{$_W['openid']}' and tuan_id = {$tuan_id} and status in(1,2,3,4,5,6,7,8,10) ");
  	//团状态
  	$tuaninfo = group_get_by_params(" groupnumber = {$tuan_id} ");
  	$num_arr = array();
  	for($i=0;$i<$tuaninfo['lacknum'];$i++){
  		$num_arr[$i] = $i; 
  	}
  	if (empty($order['g_id'])) {
  		echo "<script>alert('组团信息不存在！');location.href='".app_url('home/index')."';</script>";
  		exit;
  	}else{
  		$goods = goods_get_by_params(" id = {$order['g_id']} ");
		//阶梯团
		if($goods['group_level_status']==2){
			$param_level = unserialize($tuaninfo['group_level']);
			for($i=0;$i<count($param_level)-1;$i++){
				for($j=0;$j<count($param_level)-$i-1;$j++){
					if($param_level[$j]['groupnum']<$param_level[$j+1]['groupnum']){
						$temp=$param_level[$j]; 
						$param_level[$j] = $param_level[$j+1];
						$param_level[$j+1]= $temp;
					}
				}
			}
			if($param_level){
				$num= round(((100-count($param_level)*2)/count($param_level)));
			}
			$goods['p'] = $param_level[0]['groupprice'];
			foreach($param_level as $item)
			{
				$numPerson.=",".$item['groupnum'];
				$numPrices.=",".$item['groupprice'];
			}
			$numGroup=$tuaninfo['neednum'];
		}
	    $endtime = $tuaninfo['endtime'];
	    $time = time(); /*当前时间*/
	    $lasttime2 = $endtime - $time;//剩余时间（秒数）
	    $lasttime = $goods['endtime'];
		//规格及规格项
		$allspecs = pdo_fetchall("select * from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $goods['id']));
		foreach ($allspecs as &$s) {
			$s['items'] = pdo_fetchall("select * from " . tablename('tg_spec_item') . " where  `show`=1 and specid=:specid order by displayorder asc", array(":specid" => $s['id']));
		}
		unset($s);
		$options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,stock from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $goods['id']));
		$specs = array();
		if (count($options) > 0) {
			$specitemids = explode("_", $options[0]['specs'] );
			foreach($specitemids as $itemid){
				foreach($allspecs as $ss){
					$items = $ss['items'];
					foreach($items as $it){
						if($it['id']==$itemid){
							$specs[] = $ss;
							break;
						}
					}
				}
			}
		}
  	}
	/*判断购买次数*/
	$data1 = order_get_list(array('g_id'=>$goods['id'],'openid'=>$_W['openid'],'status'=>'1,2,3,6,8'));
	$times = $data1['total'];
	if(empty($goods['share_desc']))
	{
		$share_desc=$config['share']['share_desc'];
	}else{$share_desc=$goods['share_desc'];}
	if($tuaninfo['lacknum']>0)
	{
		$config['share']['share_title'] = "【差".$tuaninfo['lacknum']."人】我参加了".$_W['uniaccount']['name'].$goods['gname']."的拼团";
	}
	else{
		$config['share']['share_title'] = "【已成团】我参加了".$_W['uniaccount']['name'].$goods['gname']."的拼团";
	}
	
	
	// app_url('order/group', array('tuan_id'=>$tuan_id))
/*	$ch=curl_init();
	curl_setopt($ch,CURLOPT_URL,"http://dwz.cn/create.php");
	curl_setopt($ch,CURLOPT_POST,true);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$data=array('url'=>app_url('order/group', array('tuan_id'=>$tuan_id)));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
	$strRes=curl_exec($ch);
	curl_close($ch);
	$arrResponse=json_decode($strRes,true);
	if($arrResponse['status']==0)
	{
	
	echo iconv('UTF-8','GBK',$arrResponse['err_msg'])."\n";
	}
*/
	//
	$config['share']['share_desc'] =$goods['share_desc'];
	$config['share']['share_url'] = app_url('order/group', array('tuan_id'=>$tuan_id));
	$config['share']['share_image'] = $goods['share_image'];
	$pagetitle = $goods['gname'];
	
	session_start();
	$_SESSION['goodsid'] = $goods['id'];
	$_SESSION['tuan_id'] = $tuan_id;
	$_SESSION['groupnum'] = $tuaninfo['neednum'];
	
	$lists = pdo_fetch("select id from".tablename('tg_order')."where openid = '{$_W['openid']}' and uniacid = '{$_W['uniacid']}'");
	if(!empty($lists))
	{
		$result=array('statustype'=>1);
	}
	else{
		$result=array('statustype'=>0);
	}
	if($goods['selltype']==2)
	{
		include wl_template('order/lingroup');
	}else{
		include wl_template('order/group');
	}
  	
}else{
	echo "<script>alert('参数错误');location.href='".app_url('home/index')."';</script>";
}
