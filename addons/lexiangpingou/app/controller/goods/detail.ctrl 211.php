<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 商品详情控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
$id = $_GPC['id'];
puv($_W['openid'],$id);
$pagetitle = '商品详情';
$_SESSION['type']=NULL;
$_SESSION['freight']=NULL;
$_SESSION['tuan_id']=NULL;
//全民兼职  开始
$tid=8167;
//权限控制
wl_load()->model('functions');
$checkfunction=checkfunc($tid);
$acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
$checkhomeimg=checkfunc(8169);
if($checkhomeimg['status']==0){
	$acct['homeimg']='';
}
$tpl='goods/goods_detail';
if($acct['tpl']=='8146')
{
	
	$tpl1='goods/goods_detail';
}
if($acct['tpl']=='8147')
{	
	$tpl1='goods/goods_detail_2';
}
if($acct['tpl']=='8172')
{	
	$tpl1='goods/goods_detail_3';
}
if($acct['tpl']=='8174')
{
	$tpl1='goods/goods_detail';
}
if($acct['tpl']=='8175')
{
	$tpl1='goods/goods_detail_2';
	
}
wl_load()->model('member');
$member=member_get_by_params("from_user='".$_W['openid']."' and uniacid='".$_W['uniacid']."'");
$tourl=app_url('goods/detail')."&id=".$id;
if($checkfunction['status']&&$member['enable']==1)
{
	$tourl=$tourl=app_url('goods/detail')."&id=".$id."&sharenum=".$member['id'];
}
//message($checkfunction['status']."kkk".intval($_GPC['sharenum'])."dddd".$member['parentid']);
if($checkfunction['status']&&intval($_GPC['sharenum'])!=0&&$member['parentid']==-1)
{
	$data=array('parentid'=>$_GPC['sharenum']);
	$params=array('from_user'=>$_W['openid']);
	member_update_by_params($data,$params);
	
}
$member=member_get_by_params("from_user='".$_W['openid']."'");
if($checkfunction['status']==0||$member['parentid']==-1)
{
	$data=array('parentid'=>99);
	$params=array('from_user'=>$_W['openid']);
	member_update_by_params($data,$params);
}
//全民兼职 结束
session_start();
if(!empty($_GPC['id'])){
	$_SESSION['goodsid'] = $_GPC['id'];
}
load()->model('mc');
if(!empty($id)){
	//商品
	$goods = goods_get_by_params("id = {$id}");
	if($goods['isshow']==2){
		$tip='该商品已下架';
		echo "<script>alert('".$tip."');location.href='".app_url('goods/list')."';</script>";
		
		exit;
	}
	if(empty($goods['unit'])){
		$goods['unit'] = '件';
	}
	
	//阶梯团
	if($goods['group_level_status']==2){
		$param_level = unserialize($goods['group_level']);
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
		
		$numGroup=$goods['groupnum'];
	}
	
	/*判断购买次数*/
	$data = order_get_list(array('g_id'=>$goods['id'],'openid'=>$_W['openid'],'status'=>'1,2,3,6,8'));
	$times = $data['total'];
	
	//商家
	$merchant = merchant_get_by_params("id = {$goods['merchantid']}");
	
	//规格及规格项
	$allspecs = pdo_fetchall("select * from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $id));
	foreach ($allspecs as &$s) {
		$s['items'] = pdo_fetchall("select * from " . tablename('tg_spec_item') . " where  `show`=1 and specid=:specid order by displayorder asc", array(":specid" => $s['id']));
	}
	unset($s);
	$options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,stock from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $id));
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
	
	//得到图集
	$advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$id}'");
    foreach ($advs as &$adv) {
    	if (substr($adv['link'], 0, 5) != 'http:') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
    unset($adv);
	
	$params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') .  "WHERE goodsid = '{$id}' ");
	//门店信息
	$storesids = explode(",", $goods['hexiao_id']);
	foreach($storesids as$key=>$value){
		if($value){
			$stores[$key] =  pdo_fetch("select * from".tablename('tg_store')."where id ='{$value}' and uniacid='{$_W['uniacid']}'");
		}
	}
	
	// 分享团数据
	if(empty($goods['is_share'])){
		if ($this->module['config']['sharestatus'] != 2) {
			$thistuan = pdo_fetchall("select * from".tablename('tg_group')."where uniacid='{$_W['uniacid']}' and goodsid = '{$id}' and groupstatus=3 and lacknum<>neednum order by id asc ");
			if (!empty($thistuan)) {
				foreach ($thistuan as $key => $value) {
					$tuan_first_order = order_get_by_params(" tuan_id = '{$value['groupnumber']}' and tuan_first=1 ");
					$userinfo=member_get_by_params(" openid = '{$tuan_first_order['openid']}'");
					$thistuan[$key]['avatar'] = $userinfo['avatar'];
					$thistuan[$key]['nickname'] = $userinfo['nickname'];
					$thistuan[$key]['nownum']=$value['neednum']-$value['lacknum'];
				}
			}
		}
	}
	
	$config['share']['share_title'] = !empty($goods['share_title']) ? $goods['share_title'] : $goods['gname'];
	$config['share']['share_desc'] = !empty($goods['share_desc']) ? $goods['share_desc'] : $config['share']['share_desc'];
	$config['share']['share_image'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['gimg'];
}else{
	$tip='商品信息出错！';
	echo "<script>alert('".$tip."');location.href='".app_url('goods/list')."';</script>";
	
	exit;
}
if($goods['selltype']==0)
{
	include wl_template($tpl1);
}
if($goods['selltype']==1)
{
	include wl_template($tpl);
}
if($goods['selltype']==2)
{
	include wl_template('goods/goods_lindetail');
}
if($goods['selltype']==3)
{
	include wl_template('goods/goods_detailjtt');
}
if($goods['selltype']==4)
{
	include wl_template('goods/goods_detailjtt2');
}
if($goods['selltype']==5)
{
	include wl_template('goods/goods_luck');
}
if($goods['selltype']==6)
{
	$lists = pdo_fetch("select id from".tablename('tg_order')."where openid = '{$_W['openid']}' and uniacid = '{$_W['uniacid']}'");
	if(!empty($lists))
	{
		$result=array('statustype'=>1);
	}
	else{
		$result=array('statustype'=>0);
	}
	include wl_template('goods/goods_detail_6');
}