<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * index.ctrl
 * 个人中心控制器
 */
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
wl_load()->model('functions');
$banquanfunction=checkfunc(8170);
$partjobfunction=checkfunc(8167);
wl_load()->model('setting');
$setting = setting_get_by_name("commander");
$wholesale = setting_get_by_name("wholesale");
$company = setting_get_by_name("company");
$company_join = setting_get_by_name('company_join');
$kaiguan = setting_get_by_name('kaiguan');
$hexiao = setting_get_by_name('hexiao');
$fans = setting_get_by_name("fans_data");
$score = setting_get_by_name("score");
$acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");

$pagetitle = '个人中心';
global $_W,$_GPC;
$uniacid = $_W["uniacid"];
$openid = $_W["openid"];
if($op =='display'){

    $saler = pdo_get('tg_saler' , array('uniacid'=>$uniacid,'openid'=>$openid,'status'=>1,'merchantid'=>0));
    $delivery = pdo_get('tg_delivery_man' , array('uniacid'=>$uniacid,'openid'=>$openid,'status'=>1,'merchantid'=>0));
    $commander = pdo_fetch("select id from " .tablename('tg_commander') ." where uniacid = '{$_W['uniacid']}' and openid = '{$openid}' ");
	$member = getMember($openid);
	if(!$member['credit1']){
		$member['credit1'] = '0.00';
	}
	if(!$member['credit2']){
		$member['credit2'] = '0.00';
	}
	$coupons=pdo_fetchall("select id from ".tablename('tg_coupon')." where openid=:openid and uniacid=:uniacid and use_time=0 and end_time>:end_time",array(':openid' => $openid, ':uniacid' => $_W['uniacid'],'end_time'=>TIMESTAMP));
    $openid = $_W["openid"];

    //查询用户是否为组长
    $leader = pdo_fetch("select * from " .tablename('tg_member_group') ." where openid = '" .$openid ."' ");

    //查询会员等级享受的折扣
    $res = pdo_fetch("select * from ".tablename("tg_member")." where uniacid=:uniacid and openid = :openid",array(":uniacid"=>$_W["uniacid"],":openid"=>$openid));
    $level = $res["level"];//会员等级
	//会员说明
	$member_level = pdo_fetch("select member_leave from ".tablename("account_wechats").' where uniacid=:uniacid',array("uniacid"=>$_W["uniacid"]));

	//最大金额
    $moneyMax = pdo_fetch("SELECT money  FROM ".tablename('tg_member_leave')." WHERE uniacid = :uniacid order by money desc", array(':uniacid' => $_W['uniacid']));
	//所有记录
	$resall = pdo_fetchall("SELECT * FROM ".tablename('tg_member_leave')." WHERE uniacid = :uniacid order by money asc", array(':uniacid' => $_W['uniacid']));


    //查询相应等级对应的权益是多少
    $level_rights = pdo_fetch("select * from ".tablename("tg_member_leave"),array("uniacid"=>$_W["uniacid"],'id'=>$level));//会员等级名字
	$tatal = count($coupons);
	//var_dump($resall);die();
//    file_put_contents(TG_DATA."data.log", var_export(array('partjobfunction' => $partjobfunction , 'banquanfunction' => $banquanfunction), true).PHP_EOL, FILE_APPEND);
    //获取会员权益开关 查看是否开启状态
    $level_set = pdo_fetch("select * from ".tablename("tg_member_level")." where uniacid = :uniacid",array(":uniacid"=>$_W["uniacid"]));
    $level_set["status"] = intval($level_set["status"]);

    if ($acct['tpl'] == '8209') {
        include wl_template('member/home1');
    }else{
        include wl_template('member/home');
    }
}
exit();
