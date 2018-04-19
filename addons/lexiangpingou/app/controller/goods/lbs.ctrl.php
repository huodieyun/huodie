<?php
error_reporting(0);

global $_W;
global $_GPC;
set_time_limit(0);
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'base';
if ($op == "base"){
    $uniacid = $_W["uniacid"];
    $res = pdo_fetchall("select id,lbs,map from ".tablename("tg_print")." where uniacid = :uniacid and status = 1",array(":uniacid"=>$uniacid));
    if (empty($res)){
        $ret = array("status"=>"error");
    }else{
        for ($i=0;$i<count($res);$i++){
            if (empty($res[$i]["map"])){
                unset($res[$i]);
                if (empty($res)){
                    $ret = array("status"=>"error");
                }
            }else{
                $res[$i]["lbs"] = unserialize($res[$i]["lbs"]);
                $res[$i]["map"] = unserialize($res[$i]["map"]);
            }
        }
        $ret = array("status"=>"success","data"=>$res);
    }
    die(json_encode($ret));
}