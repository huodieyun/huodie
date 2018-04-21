<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2017/11/29
 * Time: 9:35
 */
define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

// header("Access-Control-Allow-Origin: https://w9.huodiesoft.com"); // 允许w9.huodiesoft.com发起的跨域请求
// //如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
// //header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
// header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
// header('Access-Control-Allow-Method:POST,GET');//允许访问的方式
//
//$ydelivery = pdo_get('erp_sale_delivery' , array('id' => 25));
//$ydocno = explode("_", $ydelivery['docno']);
//$ydocno = $ydocno[0] .'_' . (intval($ydocno[1]) + 1);
//die(json_encode($ydocno));



if ($op == 'submit') {
    $request = [
        'saleId'=>$_GPC['saleId'],
        'itemData' => $_POST['itemData'],
        'status'=>$_GPC['status']
    ];
    die(json_encode($_POST));
}

if ($op == 'display') {
    echo emotion("/::)");
    include "./test.html";

    exit();
}

if ($op == 'info') {
    phpinfo();
}


//$map = 'a:1:{i:0;s:1:"2";}';
//$map = unserialize($map);
//die(json_encode($map));
//
//$num = $_GPC["num"];
//$num = intval($num);
//$card = (explode("+",'1+100.00'));
//$card_id = $card[0];
//$card_fee = $card[1];
//echo intval('1111111111111111111111x')."<pre>";
//if (!empty($num)) {
//    echo 1;
//} else {
//    echo var_dump($num);
//}