<?php

defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'setting';
load() -> func('tpl');
$uniacid= $_W['uniacid'];
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$allgoods = pdo_fetchall("select gname,id from".tablename('tg_goods')."where uniacid=:uniacid",array(':uniacid'=>$_W['uniacid']));
if ($operation == "setting"){
    //读取商家设置
    $res = pdo_fetch("select * from ".tablename("tg_tchorder")." where uniacid = :uniacid and 	merchant_id	 = :merchantid",array(":uniacid"=>$_W["uniacid"],"merchantid" => $_W["user"]["merchant_id"]));

    include wl_template("tchorder/setting");
}
if ($operation == "set_appkey"){
    //查询条件是否存在
    $is_in = pdo_fetch("select * from ".tablename("tg_tchorder")." where uniacid = :uniacid and merchant_id = :merchant_id",array(":uniacid"=>$_W["uniacid"],":merchant_id"=>$_W["user"]["merchant_id"]));

       if (!$is_in){
           $data["appkey"] = $_GPC["appkey"];
           $data["PayType"] = $_GPC["PayType"];
           $data["ExpType"] = $_GPC["ExpType"];
           $data["EBusinessID"] = $_GPC["EBusinessID"];
           $data["send_name"] = $_GPC["send_name"];
           $data["send_provinceName"] = $_GPC["send_provinceName"];
           $data["send_cityName"] = $_GPC["send_cityName"];
           $data["send_expAreaName"] = $_GPC["send_expAreaName"];
           $data["send_address"] = $_GPC["send_address"];
           $data["merchant_id"] = $_W["user"]["merchant_id"];
           $data["uniacid"] = $_W["uniacid"];
           $res = pdo_insert("tg_tchorder",$data);

       }else{
           $data["appkey"] = $_GPC["appkey"];
           $data["PayType"] = $_GPC["PayType"];
           $data["ExpType"] = $_GPC["ExpType"];
           $data["EBusinessID"] = $_GPC["EBusinessID"];
           $data["send_name"] = $_GPC["send_name"];
           $data["send_provinceName"] = $_GPC["send_provinceName"];
           $data["send_cityName"] = $_GPC["send_cityName"];
           $data["send_expAreaName"] = $_GPC["send_expAreaName"];
           $data["send_address"] = $_GPC["send_address"];
           $data["merchant_id"] = $_W["user"]["merchant_id"];
           $res = pdo_update("tg_tchorder",$data,array("merchant_id"=>$_W["user"]["merchant_id"],"uniacid"=>$_W["uniacid"]));

       }
    $tip = '更新成功';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('order/tchorder') . "';</script>";

        exit();
}
    if ($op == 'mimeograph') {

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = "  uniacid = :weid";
        $paras = array(':weid' => $_W['uniacid']);

        $status = $_GPC['status'];
        $keyword = $_GPC['keyword'];
        //商品ID
        $member = $_GPC['member'];
        //电话、姓名
        $time = $_GPC['time'];
        //下单时间

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  createtime >= :starttime AND  createtime <= :endtime ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
        if (!empty($_GPC['keyword'])) {
            $condition .= " AND  orderno like '%{$_GPC['keyword']}%'";
        }
        if(trim($_GPC['goodsid'])!=''){
            $condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
        }
        if(trim($_GPC['goodsid2'])!=''){
            $condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
        }
        if(trim($_GPC['address'])!=''){

            $condition .= " and address like '%{$_GPC['address']}%' ";
        }


        if (!empty($_GPC['addresstype'])) {
            $condition .= " AND  addresstype={$_GPC['addresstype']} ";
        }
        if (!empty($_GPC['merchantid'])) {
            $condition .= " AND  merchantid={$_GPC['merchantid']} ";
        }
        if (!empty($_GPC['nickname'])) {
            $condition .= " AND openid in(select * from ".tablename(tg_member)." where nickname LIKE '%{$_GPC['nickname']}%' and  uniacid='{$_W['uniacid']}')";
        }
        if (!empty($_GPC['member'])) {
            $condition .= " AND (addname LIKE '%{$_GPC['member']}%' or mobile LIKE '%{$_GPC['member']}%')";
        }
        if ($status!='') {
            if ($status == 1) {
                $condition .= " AND is_tuan=1 ";
            } else {
                $condition .= " AND is_tuan=0 ";
            }

        }
        $godluck = $_GPC['godluck'];
        if(!empty($godluck))
        {
            $condition .= " AND godluck={$_GPC['godluck']} ";
            $condition .= " AND status in (6,8) and expresssn is NULL";
        }
        if(empty($godluck))
        {
            $condition .= " AND status in (6,8) and expresssn is NULL  and dispatchtype<>3";
        }
        if ($_W['user']['merchant_id'] > 0) {
            $condition .= " and  merchantid = {$_W['user']['merchant_id']} ";
        }
        if ($_W['user']['merchant_id'] > 0) {
            $conditions .= " and  merchantid = {$_W['user']['merchant_id']} ";
        }
        $sql = "select  * from " . tablename('tg_order') . " where $condition  and mobile<>'虚拟'  ORDER BY createtime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);

        //echo "<pre>";print_r($list);exit;
        $paytype = array('0' => array('css' => 'default', 'name' => '未支付'), '1' => array('css' => 'info', 'name' => '余额支付'), '2' => array('css' => 'success', 'name' => '在线支付'), '3' => array('css' => 'warning', 'name' => '货到付款'));
        $orderstatus = array(
            '0' => array('css' => 'default', 'name' => '待付款'),
            '1' => array('css' => 'info', 'name' => '已付款'),
            '2' => array('css' => 'warning', 'name' => '待收货'),
            '3' => array('css' => 'success', 'name' => '已签收'),
            '4' => array('css' => 'success', 'name' => '已退款'),
            '5' => array('css' => 'success', 'name' => '强退款'),
            '6' => array('css' => 'danger', 'name' => '部分退款'),
            '7' => array('css' => 'success', 'name' => '已退款'),
            '8' => array('css' => 'success', 'name' => '待发货'),
            '9' => array('css' => 'success', 'name' => '已取消'),
            '10' => array('css' => 'success', 'name' => '待退款')
        );
        foreach ($list as &$value) {
            $s = $value['status'];
            $value['statuscss'] = $orderstatus[$value['status']]['css'];
            $value['status'] = $orderstatus[$value['status']]['name'];
            $value['css'] = $paytype[$value['pay_type']]['css'];
            if ($value['pay_type'] == 2) {
                if (empty($value['transid'])) {
                    $value['paytype'] = '微信支付';
                } else {
                    $value['paytype'] = '微信支付';
                }
            } else {
                $value['paytype'] = $paytype[$value['pay_type']]['name'];
            }
            $goodsss = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$value['g_id']}'");
            $value['gid'] = $goodsss['id'];
            $value['gname'] = $goodsss['gname'];
            $value['gimg'] = $goodsss['gimg'];
            $value['freight'] = $goodsss['freight'];
            $m = $value['merchantid'];
            if ($m == 0) {
                $value['merchant_name'] = $_W['account']['name'];
            } else {
                $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                $value['merchant_name'] = $name['name'];
            }
            //$options  = pdo_fetch("select title,productprice,marketprice from " . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $value['optionid']));
            //$value['optionname'] = $options['title'];
            $value['merchant'] = pdo_fetch("select name from" . tablename('tg_merchant') . "where id = '{$goodsss['merchantid']}' and uniacid={$_W['uniacid']}");
        }
        $total_choujian = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status in (6,8) and expresssn is NULL  and godluck=1 and mobile<>'虚拟'$conditions ", $paras);

        $total_tuan = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status in (6,8) and expresssn is NULL and is_tuan=1 and mobile<>'虚拟' and dispatchtype<>3 $conditions", $paras);
        $total_single = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status in (6,8) and expresssn is NULL and is_tuan=0 and mobile<>'虚拟' and dispatchtype<>3 $conditions", $paras);
        $total_all = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status in (6,8) and expresssn is NULL and mobile<>'虚拟' and dispatchtype<>3 $conditions");
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition and mobile<>'虚拟' and dispatchtype<>3 $conditions", $paras);
        $pager = pagination($total, $pindex, $psize);
        include wl_template('tchorder/import');exit();
    }
    //接受值
if ($operation == "print_post") {
    //电商ID
    defined('EBusinessID') or define('EBusinessID', '1295532');
    defined('ReqURL') or define('ReqURL', 'http://api.kdniao.cc/api/EOrderService');
//电商加密私钥，快递鸟提供，注意保管，不要泄漏
    defined('AppKey') or define('AppKey', '00dde72e-402c-4cd3-b732-36790fea390c');
    //接受到所有的值
    $all_thing = $_GPC["arr"];
    //查出发货属性
    $res = pdo_fetch("select * from " . tablename("tg_tchorder") . " where uniacid = :uniacid and merchant_id = :merchant_id", array(":uniacid" => $_W["uniacid"], ":merchant_id" => $_W["user"]["merchant_id"]));
    if (empty($res)) {
        $array = array("status" => "error", "data" => "请完善您的发货信息");
        die(json_encode($array));
    }
    $kd_type = $_GPC["kd_type"];
    //分批获取订单信息 拿到回调
    for ($i = 0; $i < count($all_thing); $i++) {
        //获取省份  月份 天
        $receiver = [];
        $receiver["Name"] = $all_thing[$i]['cname'];
        $receiver["Mobile"] = $all_thing[$i]["tel"];
        if (empty($all_thing[$i]["addressp"])) {
            $all_thing[$i]["addressp"] == "没有省";
        }
        $receiver["ProvinceName"] = $all_thing[$i]["addressp"] . "省";
        $receiver["CityName"] = $all_thing[$i]["addresss"] . "市";
        $receiver["ExpAreaName"] = $all_thing[$i]["addressq"] . "区";
        $receiver["Address"] = $all_thing[$i]["addressj"] ;

//        $Shippertype = $_GPC["type"];
        //构造电子面单提交信息
        $eorder = [];
        $eorder["ShipperCode"] = $kd_type;
        $eorder["CustomerName"] = $res["appkey"];
        $eorder["CustomerPwd"] = $res["EBusinessID"];
        $OrderCode =$all_thing[$i]["orderno"];//订单编号
        $eorder["OrderCode"] = $OrderCode;
        $eorder["PayType"] = 4;
        $eorder["ExpType"] = 1;

        $sender = [];
        $sender["Name"] = $res["send_name"];
        $sender["Mobile"] = $res["send_phone"];
        $sender["ProvinceName"] = $res["send_provinceName"];
        $sender["CityName"] = $res["send_cityName"];
        $sender["ExpAreaName"] = $res["send_expAreaName"];
        $sender["Address"] = $res["send_address"];
        $commodityOne = [];
        $commodityOne["GoodsName"] = "其他";
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;
        $eorder["IsReturnPrintTemplate"] = 1;

        //调用电子面单
        //调用电子面单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);
        $jsonResult = submitEOrder($jsonParam);
//var_dump($jsonResult);die();
        $jsonResult = json_decode($jsonResult, true);// 电子面单返回值

        //顺丰
       $data["status"] = 2;
        if ($jsonResult["Order"]["ShipperCode"] == "SF") {//顺丰的处理
            $data["express"] = "顺丰快递";
            $a = "<div style='padding-top:9em;height: 54.4em'>" . $jsonResult["PrintTemplate"] . "</div>";
        }
        if ($jsonResult["Order"]["ShipperCode"] == "ZTO") { //中通的处理
            $data["express"] = "中通快递";
            $a = "<div style='height: 53.64em;'>" . $jsonResult["PrintTemplate"] . "</div>";
        }
        if ($kd_type == "ZJS") { //中通的处理
            $data["express"] = "宅急送";

            $a = "<div style='padding-top:2em;height: 53.64em;'>" . $jsonResult["PrintTemplate"] . "</div>";
        }
        //todo 这里就对接了两家!
        //中通
        //$a =  "<div style='height: 53.64em'>".$jsonResult["PrintTemplate"]."</div>";
//        $a =  "<div style='padding-top:-6em;height:66em;'>".$jsonResult["PrintTemplate"]."</div>";

        if ($jsonResult["Order"]["LogisticCode"] != "" || !empty($jsonResult["Order"]["LogisticCode"])) {
//            var_dump($jsonResult);die();
            $kd_num= pdo_fetch("select kd_num from ".tablename("tg_order")." where orderno = :orderno",array('orderno' =>$all_thing[$i]["orderno"]));
//            var_dump($kd_num);die();
            $data["expresssn"] = $jsonResult["Order"]["LogisticCode"];
            $data["status"] = 2;
            $data["sendepressmessage"] = 1;
            $data["tchorder"] = 1;
            $data["kd_num"] = $kd_num["kd_num"]+1;
            $result = pdo_update('tg_order', $data, array('orderno' => $all_thing[$i]["orderno"]));
            file_put_contents(TG_DATA."payresult.log", var_export($result, true).PHP_EOL, FILE_APPEND);
//            if ($result){
//                //获取会员的openid
//                $openid_res = pdo_fetch("select openid from ".tablename("tg_order")." where orderno = :orderno",array('orderno' => $_GPC["orderno"]));
//                $openid = $openid_res["openid"];
//                //sendTplNotice($touser, $template_id, $postdata, $url = '', $topcolor = '#FF683F')
//                sendTplNotice($openid);
//            }
            $c .= $a . $b;
        } else {
            continue;
        }

    }
    echo $c;
    die();
}




/**
 * Json方式 调用电子面单接口
 */
function submitEOrder($requestData){
    $datas = array(
        'EBusinessID' => EBusinessID,
        'RequestType' => '1007',
        'RequestData' => urlencode($requestData) ,
        'DataType' => '2',
    );
    $datas['DataSign'] = encrypt($requestData, AppKey);
    $result=sendPost(ReqURL, $datas);
    //根据公司业务处理返回的信息......
    return $result;
}
function encrypt($data, $appkey) {
    return urlencode(base64_encode(md5($data.$appkey)));
}
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}
/**************************************************************
 *
 *  将数组转换为JSON字符串（兼容中文）
 *  @param  array   $array      要转换的数组
 *  @return string      转换得到的json字符串
 *  @access public
 *
 *************************************************************/
function JSON($array) {
    arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}
function sendPost($url, $datas) {
    $temps = array();
    foreach ($datas as $key => $value) {
        $temps[] = sprintf('%s=%s', $key, $value);
    }
    $post_data = implode('&', $temps);
    $url_info = parse_url($url);
    if(empty($url_info['port']))
    {
        $url_info['port']=80;
    }
    $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
    $httpheader.= "Host:" . $url_info['host'] . "\r\n";
    $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
    $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
    $httpheader.= "Connection:close\r\n\r\n";
    $httpheader.= $post_data;
    $fd = fsockopen($url_info['host'], $url_info['port']);
    fwrite($fd, $httpheader);
    $gets = "";
    $headerFlag = true;
    while (!feof($fd)) {
        if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
            break;
        }
    }
    while (!feof($fd)) {
        $gets.= fread($fd, 128);
    }
    fclose($fd);

    return $gets;
}