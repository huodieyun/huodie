<?php
global $_W,$_GPC;
$fans=pdo_fetch('select follow from '.tablename('mc_mapping_fans').' where openid=:openid  ',array(':openid'=>$_W['openid']));
$code=$_GPC['huodie_code'];
$check_code=pdo_fetch('select * from '.tablename('tg_scan_code').' where code=:code',array(':code'=>$_GPC['huodie_code']));
$data=array();
$data['code']=$code;
$data['openid']=$_W['openid'];
$data['createtime']=TIMESTAMP;
if(empty($check_code)){
    pdo_insert('tg_scan_code',$data);
}

include wl_template('home/scan_code');
exit;