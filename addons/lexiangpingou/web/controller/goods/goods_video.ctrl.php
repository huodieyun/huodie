<?php
defined('IN_IA') or exit('Access Denied');
global $_W,$_GPC;
$operation = $_GPC["op"];
load()->func("tpl");
if (empty($operation) || $operation == ""){
    $list = pdo_fetchall("select * from ".tablename("tg_goods_video"));
    //for ($i=0;$i<count($list);$i++){
  //  }
    //直接映射页面
    include wl_template("goods/goods_video");
    die();
}
if ($operation == "check"){
    if ($_W["isfounder"] != true){
        $data = "您不是创始人!不能操作";
        $res = format_ret(0,$data);
        die(json_encode($res));
    }
    if ($_W["uniacid"] != 33){
        $data = "您不是创始人!不能操作";
        $res = format_ret(0,$data);
        die(json_encode($res));
    }
    $id = $_GPC["id"];

        $date["status"] =$_GPC["status"];
        $status = intval($date["status"]);
        if (empty($id) || empty($_GPC["status"])){
            $ret = format_ret(0,"参数不能为空");
            die(json_encode($ret));
        }
        $data["status"] = intval($data["status"]);
        $res = pdo_update("tg_goods_video",array("status"=>$status),array("id"=>$id));
        if ( $status ==1 ){
            $ret = format_ret(1,"审核通过");
        }elseif($status == 2){
            $ret = format_ret(1,"拒绝通过 ");
        }else{
            $ret = format_ret(1,"未操作" );
        }
        die(json_encode($ret));
}
//参数获取(状态，原因)
function format_ret ($status, $data='') {
    if ($status){
        //成功
        return array('status'=>'success', 'data'=>$data);
    }else{
        //验证失败
        return array('status'=>'error', 'data'=>$data);
    }
}