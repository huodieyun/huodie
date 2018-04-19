<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/20
 * Time: 9:50
 */
global $_W,$_GPC;
$uniacid = $_W['uniacid'];
//$year = $_GPC['year'];
$year = 2018;
$data = pdo_fetch("select * from cm_annual_data where uniacid = {$uniacid} and FROM_UNIXTIME(addtime,'%Y')='{$year}'");
if(!empty($data)){
    $nam1 = pdo_fetch("select * from cm_tg_goods where id={$data['goods_top']}");
    $nam2 = pdo_fetch("select * from cm_tg_goods where id={$data['goods_sale_top']}");
    $data['goods_top_name'] = $nam1['gname'];
    $data['goods_sale_top_name'] = $nam2['gname'];
    $arr = json_encode($data);
}
$order = pdo_fetchall("select *, FROM_UNIXTIME(time,'%m') as mon from cm_annual_data_order where uniacid = {$uniacid} and FROM_UNIXTIME(time,'%Y')='2017' ORDER BY time asc");
if(count($order)<12){
    $newOrder = [];
    foreach ($order as $key=>$value){
        $newOrder[intval($value['mon'])] = $value;
    }
    for ($i=1;$i<=12;$i++){
        if(!$newOrder[$i]){
            $newOrder[$i] = ['order_num'=>0,'pay'=>0];
        }
    }
    ksort($newOrder);
    $newOrder = array_merge($newOrder);
    $order = json_encode($newOrder);
}else{
    $order = json_encode($order);

}


$fans = pdo_fetchall("select *, FROM_UNIXTIME(time,'%m') as mon from cm_annual_data_fans where uniacid = {$uniacid} and FROM_UNIXTIME(time,'%Y')='2017' ORDER BY time asc");
if(count($fans)<12){
    $newFans = [];
    foreach ($fans as $key=>$value){
        $newFans[intval($value['mon'])] = $value;
    }
    for ($i=1;$i<=12;$i++){
        if(!$newFans[$i]){
            $newFans[$i] = ['fans_num'=>0];
        }
    }
    ksort($newFans);
    $newFans = array_merge($newFans);
    $fans = json_encode($newFans);
}else{
    $fans = json_encode($fans);
}
$customer_price = pdo_fetchall("select *, FROM_UNIXTIME(time,'%m') as mon from cm_annual_data_customer_price where uniacid = {$uniacid} and FROM_UNIXTIME(time,'%Y')='2017' ORDER BY time asc");
if(count($customer_price)<12){
    $newCus = [];
    foreach ($customer_price as $key=>$value){
        $newCus[intval($value['mon'])] = $value;
    }
    for ($i=1;$i<=12;$i++){
        if(!$newCus[$i]){
            $newCus[$i] = ['customer_price'=>0];
        }
    }
    ksort($newCus);
    $newCus = array_merge($newCus);
    $customer_price = json_encode($newCus);
}else{
    $customer_price = json_encode($customer_price);

}
wl_load()->model('setting');
$setting = setting_get_by_name("tginfo");
$logo = $setting['slogo'];
$logo = tomedia($logo);
$name = $setting['sname'];
include wl_template('data/annual_data');
exit();