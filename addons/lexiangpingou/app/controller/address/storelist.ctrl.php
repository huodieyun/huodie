<?php
defined('IN_IA') or exit('Access Denied');
global $_W,$_GPC;
wl_load()->func("tpl");
$op = $_GPC["op"];
if(empty($op)){
    $op = "display";
}
if($op == "display"){
    //门店列表
    $id = $_GPC["id"];
    $item = pdo_fetch("select * from ".tablename("tg_store")." where id=:id",array(":id"=>$id));
    if(empty($store_list["lat"])){
        $store_list["lat"] = 'default';
    }
    if(empty($store_list["lng"])){
        $store_list["lng"] = 'default';
    }
    include wl_template("address/storelist");
}