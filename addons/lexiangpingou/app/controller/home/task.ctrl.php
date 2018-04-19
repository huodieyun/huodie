<?php
//自动签收
global $_W;

wl_load()->model('setting');
$set = setting_get_list();
$settings = array();
foreach($set as$key=>$value){
    $settingarray= $value['value'];
    foreach($settingarray as $k=>$v){
        $settings[$k] = $v;
    }
}

$gettime = $settings['gettime'];//自动签收时间
if(empty($gettime)){
    $gettime = 5;
}

$nowtime=TIMESTAMP-$gettime*24*3600;

//自动签收
$recevid_order=pdo_fetchall("select id,sendtime from " .tablename('tg_order')." where uniacid={$_W['uniacid']}  and sendtime<{$nowtime} limit 50");
//var_dump("select id,sendtime from " .tablename('tg_order')." where uniacid={$_W['uniacid']}  and sendtime<{$nowtime} limit 50");

var_dump($recevid_order);
?>
	 