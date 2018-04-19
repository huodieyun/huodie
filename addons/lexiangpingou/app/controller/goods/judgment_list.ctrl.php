<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 商品详情控制器
 */
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'judgment_list';
$gid = $_GPC['gid'];
$totalnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and check_status = 1 ");
$totaliszhuijianum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and iszhuijia = 1 and check_status = 1 ");
$totalishaoyongnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and ishaoyong = 1 and check_status = 1 ");
$totaliszhengpinnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and iszhengpin = 1 and check_status = 1 ");
$totalispianyinum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and ispianyi = 1 and check_status = 1 ");
$totaliswuliunum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and iswuliu = 1 and check_status = 1 ");
$totaliszhiliangnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and iszhiliang = 1 and check_status = 1 ");
$totalisfuwunum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and isfuwu = 1 and check_status = 1 ");
$totalisqitanum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and isqita = 1 and check_status = 1 ");
	
if($op=='judgment_list'){
	$gid = $_GPC['gid'];
    $list = pdo_fetchall("select openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time,iszhuijia,ishaoyong,iszhengpin,ispianyi,iswuliu,iszhiliang,isfuwu,isqita from " . tablename('tg_judgment') . " where gid = '{$gid}' and check_status = 1 ORDER BY create_time desc limit 0,10");
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and check_status = 1 ORDER BY create_time DESC ");
	include wl_template('goods/goods_detail_judgment');
}
	
if($op =='ajax'){
	$page = $_GPC['page'];
	$pagesize = $_GPC['pagesize'];
	$gid = $_GPC['gid'];	
	$pindex = max(1, intval($page));
	$con=" gid ='{$gid}'";
	if(!empty($_GPC['name'])){
		if($_GPC['name']=='iszhuijia')
		{
			$con.=" and iszhuijia=1";
		}
		if($_GPC['name']=='ishaoyong')
		{
			$con.=" and ishaoyong=1";
		}
		if($_GPC['name']=='iszhengpin')
		{
			$con.=" and iszhengpin=1";
		}
		if($_GPC['name']=='ispianyi')
		{
			$con.=" and ispianyi=1";
		}
		if($_GPC['name']=='iswuliu')
		{
			$con.=" and iswuliu=1";
		}
		if($_GPC['name']=='iszhiliang')
		{
			$con.=" and iszhiliang=1";
		}
		if($_GPC['name']=='isfuwu')
		{
			$con.=" and isfuwu=1";
		}
		if($_GPC['name']=='isqita')
		{
			$con.=" and isqita=1";
		}
	}
    $list = pdo_fetchall("select openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time,iszhuijia,ishaoyong,iszhengpin,ispianyi,iswuliu,iszhiliang,isfuwu,isqita from " . tablename('tg_judgment') . " where {$con} and check_status = 1 ORDER BY create_time desc LIMIT " . ($pindex - 1) * $pagesize . ',' . $pagesize);
	foreach($list as &$li) {
		$main_content = pdo_fetch("select * from ".tablename('tg_judgment_content')." where  judgment_id=:judgment_id order by update_time asc ",array('judgment_id'=>$li['judgment_id']));	
		$li['main_content'] = $main_content['content'];
		$allcontent = pdo_fetchall("select who,content,FROM_UNIXTIME(update_time,'%Y-%m-%d %H:%i:%s') as time from ".tablename('tg_judgment_content')." where  judgment_id=:judgment_id and content_id <> '". $main_content['content_id'] ."'  order by update_time asc ",array('judgment_id'=>$li['judgment_id']));	
		$li['contents'] = $allcontent;
	}
	die(json_encode($list));

}
exit();