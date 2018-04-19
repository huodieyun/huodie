<?php
/**
 * Created by ���.
 * User: ����
 * Date: 2017/7/13
 * Time: 16:56
 */
define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
load()->model('reply');
load()->app('common');
load()->classs('wesession');

$uniacid=$_GPC['uniacid'];
$openid=$_GPC['openid'];
/*
 * 当前公众号配置
 * URL: min_cart_api.php?op=uniacid_base&uniacid=33
 * 传入 key
*/
if($op=='uniacid_base'){
    $key=$_GPC['key'];
    $settings=pdo_fetch("select * from".tablename('tg_setting')."where uniacid={$uniacid} and cm_tg_setting.key='base'");
    $set= unserialize($settings['value']);

    $yunfeiids = explode(",", $set['yunfei_id']);
    foreach($yunfeiids as$key=>$value){
        if($value){
            $set['dispatch_list'][$key] =  pdo_fetch("select * from".tablename('tg_delivery_template')."where id ='{$value}' and uniacid='{$uniacid}'");
        }
//        else{
//            $set['dispatch_list'][$key] = array();
//        }
    }
    $storesids = explode(",", $set['hexiao_id']);
    foreach($storesids as$key=>$value){
        if($value){
            $set['stores'][$key] =  pdo_fetch("select * from".tablename('tg_store')."where id ='{$value}' and uniacid='{$uniacid}'");
        }
    }

    die(json_encode($set));
}
/*
 * 当前公众号配置
 * URL: min_cart_api.php?op=uniacid_shop&uniacid=33
*/
if($op=='uniacid_shop'){
    $key=$_GPC['key'];
    $settings=pdo_fetch("select * from".tablename('tg_setting')."where uniacid={$uniacid} and cm_tg_setting.key='tginfo'");
    $set= unserialize($settings['value']);
    die(json_encode($set));
}
/*
 * 自提运费
 * min_cart_api.php?op=zt_freight
 * 传入 uniacid gid sid
 */
if($op=='zt_freight'){
    $sid=$_GPC['sid'];
    $scost=pdo_fetch("select cost from".tablename('tg_store')."where id ='{$sid}' and uniacid='{$uniacid}'");
    $freight=sprintf("%.2f",$scost['cost']);
    if(empty($_GPC['gid'])){
        $goods = pdo_fetch("select * from".tablename('tg_goods')."where id =:id",array(':id'=>$_GPC['id']));
        if($goods['isfree'] == 1)
        {
            $freight=0;
        }
    }
    die(json_encode(array('status'=>1,'freight'=>$freight)));
}
/*
 * 购物车运费
 * URL: min_cart_api.php?op=freight
 * 传入 uniacid openid province  city county tid
*/
if ($op == 'freight') {
    $p = $_GPC['province'];
    $c = $_GPC['city'];
    $d = $_GPC['county'];
    $tid = $_GPC['tid'];
    $freight = 0;
    $weight = 0;

    $config = pdo_get('tg_setting' , array('uniacid'=>$uniacid,'key'=>'base'));
    $config = unserialize($config['value']);

    //开启首单免运费
    if ($config['firstfree'] == 1) {
        $firstfree = 1;
        $oldbuy = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . ' WHERE openid=:openid AND status IN (1,2,3,8)', array(':openid' => $openid));
        if (count($oldbuy) >= 1) {
            $firstfree = 0;
        }
    }

    $overfreemoney = $config['over_free'];
    $nowshopprice = $_GPC['nowshopprice'];
    if(!$nowshopprice){
        $nowshopprice = 0;
    }
    //是否通过购物车is_collect  不走购物车时传1 走购物车传0
    if($_GPC['is_collect']!=1){
        $orderlist = pdo_fetchall("select num,weight,oprice from " . tablename('tg_collect') . " where uniacid='{$uniacid}' and orderno='0' and openid='{$openid}'");
        foreach ($orderlist as $key => $value) {
            $weight = $weight + $value['num'] * $value['weight'];
            $nowshopprice = $nowshopprice + floatval($value['num']) * floatval($value['oprice']);

        }
    }
    if (floatval($overfreemoney) <= $nowshopprice && floatval($overfreemoney) > 0) {
        $overfree = 1;
    }

    $settings = pdo_fetch("select * from" . tablename('tg_setting') . "where uniacid={$uniacid} and cm_tg_setting.key='autoaddress'");
    $setting = unserialize($settings['value']);
    if ($setting['autoaddr'] == 0) {
        $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
        $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
        $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");

    } else {
        if ($setting['addrtype'] == 0) {
            $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
            $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");

        }
        if ($setting['addrtype'] == 1) {
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}'  and template_id={$tid}");
            $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}' and district='{$c}' and template_id={$tid}");

        }
        if ($setting['addrtype'] == 2) {
            $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE    city = '{$p}'  and template_id={$tid}");

        }
    }
    if (!empty($province_fee)) {

        $free = sprintf("%.2f", $province_fee['free']);
        $first_fee = sprintf("%.2f", $province_fee['first_fee']);
        $first_weight = sprintf("%.2f", $province_fee['first_weight']);
        $second_fee = sprintf("%.2f", $province_fee['second_fee']);
        $second_weight = sprintf("%.2f", $province_fee['second_weight']);
    }
    if (!empty($city_fee)) {

        $free = sprintf("%.2f", $city_fee['free']);
        $first_fee = sprintf("%.2f", $city_fee['first_fee']);
        $first_weight = sprintf("%.2f", $city_fee['first_weight']);
        $second_fee = sprintf("%.2f", $city_fee['second_fee']);
        $second_weight = sprintf("%.2f", $city_fee['second_weight']);
    }
    if (!empty($district_fee)) {
        $free = sprintf("%.2f", $district_fee['free']);
        $first_fee = sprintf("%.2f", $district_fee['first_fee']);
        $first_weight = sprintf("%.2f", $district_fee['first_weight']);
        $second_fee = sprintf("%.2f", $district_fee['second_fee']);
        $second_weight = sprintf("%.2f", $district_fee['second_weight']);
    }


    if ($weight > $first_weight) {
        if ($second_weight > 0) {
            $freight = sprintf("%.2f", $first_fee + ($weight - $first_weight) / $second_weight * $second_fee);
        } else {
            $freight = sprintf("%.2f", $first_fee);
        }
    } else {
        $freight = $first_fee;
    }
    $checkfree_price = sprintf("%.2f", $free - $nowshopprice);

    if ($checkfree_price <= 0.00 && $free > 0.00) {
        $freight = 0;
    }
//    print_r($freight);
//    die;
    ////////
    $fdata = array('freight' => $freight, 'free' => $free);
    die(json_encode($fdata));
}
/*
 * 购物车提交
 * URL: min_cart_api.php?op=order_confirm
 * 传入 uniacid openid province  city county address addressid senddate dispatchtype link_zt pay_price cname tel discount_fee
 * remark sendtime is_hexiao is_usecard ,freight
*/
if($op=='order_confirm'){
    if(!empty($_GPC['senddate']))
    {
        $date1=date('Y-m-d');
        if($_GPC['senddate']==1)
        {
            $dtime=date('Y-m-d');
        }elseif($_GPC['senddate']==2){$dtime=date('Y-m-d',strtotime($date1 ."+1 day")); }
        elseif($_GPC['senddate']==3){$dtime=date('Y-m-d',strtotime($date1 ."+2 day"));}
    }
    $discount_fee=$_GPC['discount_fee'];
    $pay_price=$_GPC['pay_price'];
    $is_hexiao=$_GPC['is_hexiao'];
    $is_usecard=$_GPC['is_usecard'];

    $data = array(
        'uniacid'     => $uniacid,
        'gnum'        => 0,
        'openid'      => $openid,
        'ptime'       => '',//支付成功时间
        'orderno'     =>$_GPC['orderno'],
        'pay_price'   => $pay_price,
        'goodsprice'  => $pay_price+$discount_fee-$_GPC['freight'],
        'freight'     => $_GPC['freight'],
        'first_fee'   => 0,
        'status'      => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid'   => 0,
        'addresstype' =>  $_GPC['addresstype'],//1公司2家庭
        'dispatchtype'=> $_GPC['dispatchtype'],//配送方式
        'comadd'	  => $_GPC['store_id'],//
        'addname'     => $_GPC['cname'],
        'mobile'      => $_GPC['tel'],
        'address'     => $_GPC['province'].$_GPC['city'].$_GPC['county'].$_GPC['address'],
        'province'    => $_GPC['province'],
        'city'        => $_GPC['city'],
        'county'      => $_GPC['county'],
        'detailed_address' => $_GPC['address'],
        'g_id'        => 0,
        'tuan_id'     => 0,
        'is_tuan'     => 0,
        'tuan_first'  => 0,
        'discount_fee'  =>$discount_fee,
        'starttime'   => TIMESTAMP,
        'remark'      => $_GPC['remark'],
        'comtype'     =>0,
        'senddate'   => $dtime,
        'selltype'   => 0,
        'sendtime'	=>$_GPC['sendtime'],
        'commission' => 0,
        'commissiontype' => 0,
        'endtime'     => 0,
        'is_hexiao'   => $is_hexiao,
        'hexiaoma'    => '',
        'couponid'    => $couponid,
        'is_usecard'  => $is_usecard,
        'createtime'  => TIMESTAMP,
        'bdeltime'    => ''
    );
    if(pdo_insert('tg_order', $data))
    {
        pdo_update('tg_collect',array('orderno' => $data['orderno']), array('openid' => $openid,'uniacid' => $uniacid,'orderno'=>0));
    }
    die(json_encode(array('status'=>1)));
}

if($op=='check_payresult')
{
    $orderno=$_GPC['orderno'];
	$uniontid=$_GPC['uniontid'];
    $transid=$_GPC['transid'];
    $fee=$_GPC['fee'];
    $order_out=pdo_fetch('select * from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));
    if(!empty($transid))
    {
        //插入paylog
        $paylog_data=array(
            'tid'=>$orderno,
            'uniontid'=>$uniontid,
            'tag'=>$transid,
            'type'=>'wechat',
            'uniacid'=>$order_out['uniacid'],
            'openid'=>$order_out['openid']
        );
        pdo_insert('core_paylog', $paylog_data);
        /*按订单量使用 减少订单量*/
        $acct = pdo_fetch("select * from " . tablename('account_wechats') . " where  uniacid =:uniacid",array(':uniacid'=>$order_out['uniacid']));
        if($acct['vip']==0&&$acct['ordernum']>0)
        {
            pdo_update('account_wechats',array('ordernum'=>$acct['ordernum']-1), array('uniacid' =>$order_out['uniacid']));
        }
        /*按订单量使用 减少订单量*/
        pdo_update('tg_order',array('status' => 8,'pay_type'=>2,'transid'=>$transid,'ptime'=>TIMESTAMP,'price'=>$fee), array('orderno' => $orderno));
        $sende = "select  * from " . tablename('tg_collect') . " where   orderno= '" . $order_out['orderno'] . "'  ";
        $sendelist = pdo_fetchall($sende);
        foreach ($sendelist as $key => $value) {
            $goodsInfos = pdo_fetch("select id,gnum,salenum from" . tablename('tg_goods') . " where id=" . $value['sid']);
            pdo_update('tg_goods', array('gnum' => $goodsInfos['gnum'] - $value['num'], 'salenum' => $goodsInfos['salenum'] + $value['num']), array('id' => $value['sid']));

            /*增加历史购买数量*/
            $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $value['uniacid'], ':openid' => $value['openid'], ':g_id' => $value['sid']));
            if (empty($old_data)) {
                $logdata = array(
                    'g_id' => $value['sid'],
                    'openid' => $value['openid'],
                    'num' => $value['num'],
                    'uniacid' => $value['uniacid']
                );
                pdo_insert('tg_goods_openid', $logdata);
            } else {
                pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $value['num']), array('id' => $old_data['id']));

            }

            /*增加历史购买数量*/

            /*更改规格库存*/
            if (!empty($value['item'])) {
                $stocks = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $value['sid'], ':title' => $value['item']));
                pdo_update('tg_goods_option', array('stock' => $stocks['stock'] - $value['num']), array('goodsid' => $value['sid'], 'title' => $value['item']));
            }
        }
        /*增加历史购买数量*/
        die(json_encode(array('status'=>1,'tuan_id'=>$tuan_id,'order_id'=>$order_out['id'])));
    }
    die(json_encode(array('status'=>0)));
}