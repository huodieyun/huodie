<?php
defined('IN_IA') or exit('Access Denied');
global $_W,$_GPC;
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'setting';
if ($operation == "setting"){
    if ($_POST) {
        $appkey = $_GPC["appkey"];
        $EBusinessID = $_GPC["EBusinessID"];
        //接受到appkey和EBusinessID
        $date["uniacid"] = $_W["uniacid"];
        $data["mechant_id"] = $_W["user"]["merchantid"];
        $res = pdo_insert("tg_waybill",$data);
        if (!$res || empty($res)){
            //执行更新
            if (!empty($_W["user"]["merchantid"]) || !$_W["user"]["merchantid"])
            $me_res = pdo_fetch("select * from ".tablename("tg_waybill")." where uniacid=:uniacid and mechant_id = :mechant_id",array(":uniacid"=>$_W["uniacid"],":mechant_id"=>$_W["user"]["merchantid"]));
            show_json(1,"更新成功");
        }else{
            show_json(1,"添加成功");
        }
    }
    include wl_template("waybill/setting");
}
