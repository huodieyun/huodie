<?php
defined('IN_IA') or exit('Access Denied');
global $_W;
global $_GPC;
load()->model('mc');
load()->func('tpl');
$op = $_GPC["op"];
$op = isset($op) ? $op : "setting";


if(!pdo_fieldexists('account_wechats', 'member_leave')) {
  pdo_query("ALTER TABLE ".tablename('account_wechats')." ADD `member_leave` VARCHAR( 500 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
}

//会员等级说明
if ($op == 'leave_textarea') {

        $textarea = $_GPC["member_level"];
        $res = pdo_update('account_wechats', array('member_leave' => $textarea), array('uniacid' => $_W['uniacid']));

    echo "<script>alert('保存成功');location.href='".web_url('member/parms', array('op' => 'setting'))."';</script>";
    exit;
}
if ($op == "setting"){
    //默认页面直接查询所有的信息
    //获取公众号id
    $unicaid = $_W["uniacid"];
    // echo $unicaid;
    //查出来对应的会员等级信息
    $res = pdo_fetchall("SELECT *  FROM ".tablename('tg_member_leave')." WHERE uniacid = :uniacid", array(':uniacid' => $unicaid));
    //获取会员等级说明
    $member_leave = pdo_fetch("SELECT member_leave FROM ".tablename('account_wechats')." WHERE uniacid = :uniacid", array(':uniacid' => $unicaid));
    $check_on = pdo_fetch("select * from ".tablename("tg_member_level")." where merchant_id = :merchant_id and uniacid = :uniacid",array(":merchant_id"=>$_W["user"]["merchant_id"],":uniacid"=>$_W["uniacid"]));
    include wl_template('member/parms');
    exit();
}
if ($op = "check_on"){
    $status = $_GPC["status"];
    $merchant_id = $_W["user"]["merchant_id"];
    $uniacid = $_W["uniacid"];
    $res = pdo_fetch("select * from ".tablename("tg_member_level")." where merchant_id=:merchant_id and uniacid = :uniacid",array(":merchant_id"=>$merchant_id,":uniacid"=>$uniacid));
    if (!empty($res)){
        $data["status"] = $status;
        pdo_update("tg_member_level",$data);
        if ($status == 0){
            $da["discount"] = 0;
            pdo_update("tg_goods",$da,array("uniacid"=>$uniacid,"discount" =>1));
        }
        echo "<script>alert('保存成功');location.href='".web_url('member/parms', array('op' => 'setting'))."';</script>";
        exit;
    }else{
        $data["merchant_id"] = $_W["user"]["merchant_id"];
        $data["uniacid"] = $_W["uniacid"];
        $data["status"] = $status;
        pdo_insert("tg_member_level",$data);
        if ($status == 0){
           $da["discount"] = 0;
            pdo_update("tg_goods",$da,array("uniacid"=>$uniacid,"discount" =>1));
        }
        echo "<script>alert('保存成功');location.href='".web_url('member/parms', array('op' => 'setting'))."';</script>";
        exit;
    }
}