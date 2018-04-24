<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * coupon.ctrl
 * 优惠券控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('cash_code');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$openid = $_W['openid'];
$uniacid = $_W['uniacid'];
if (!$openid) {
    die("<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                    <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                </head>
                <body>
                <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>请在微信客户端打开链接</h4></div></div></div>
                </body>
            </html>");
}
if ($op == 'get') {
    $id = intval($_GPC['id']);
    $code = $_GPC['code'];
//  获取二维码使用状态
    $show = true;
    if($code){
        $codestatus = pdo_fetch("SELECT * FROM cm_tg_cash_code_qrcode WHERE template_id={$id} AND code={$code}");
        if($codestatus['is_used']==2 ||empty($codestatus)){
            $show = false;
            $str = "二维码已失效!";
        }
    }

    if ($id) {
        $coupon = coupon_template($id);

        if($coupon['enable']==0){
            $show = false;
            $str = "现金券已失效!";
        }
        $coupon['starttime'] = date("Y-m-d H:i:s",$coupon['start_time']);
        $coupon['endtime'] = date("Y-m-d H:i:s",$coupon['end_time']);
        $pagetitle = $coupon['name'];

    } else {
        $show = false;
        $str = "现金券不存在!";
    }

    //*判断是否是核销人员*/
    $hexiao_member = pdo_fetch("select * from " . tablename('tg_saler') . " where uniacid = :uniacid and openid = :openid and status = 1 ", array(":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"]));
    $cash_store_id = $coupon['store_id'];
    if($coupon['store_id'] ==''){
        $storelist = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid = '{$uniacid}' and status = 1 ");//公众号对应的店铺
        foreach ($storelist as $key => $value) {
            $coupon['store_id'] .= $value['id'] . ",";
        }
        $coupon['store_id'] = substr($coupon['store_id'], 0, strlen($coupon['store_id'])-1);
    }
    $store_ids = explode(',', $coupon['store_id']);

    $is_hexiao_member = false;
    if (!empty($hexiao_member)) { //如果是核销员


        if ($hexiao_member['storeid'] == '') {//如果这个数组中的店铺id是空的话
            $is_hexiao_member = TRUE;
            $storelist = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid = '{$uniacid}' and status = 1 ");//公众号对应的店铺
            foreach ($storelist as $key => $value) {
                $hexiao_member['storeid'] .= $value['id'] . ",";
            }

            $hexiao_member['storeid'] = substr($hexiao_member['storeid'], 0, strlen($hexiao_member['storeid']) - 1);
        } else {

            $hexiao_ids = explode(',', $hexiao_member['storeid']);

            foreach ($hexiao_ids as $value) {
                if (in_array($value, $store_ids)) {
                    $is_hexiao_member = TRUE;
                    break;
                }
            }
        }
//        if($cash_store_id ==''){
//            $storesids = explode(",", $hexiao_member['storeid']);
//        }else{
//            $storesids = explode(",", $coupon['store_id']);
//        }
        $storesids = array_intersect(explode(',', $hexiao_member['storeid']),explode(',', $coupon['store_id']));
        foreach ($storesids as $key => $value) {
            if ($value) {
                $st = pdo_fetch("select * from " . tablename('tg_store') . " where id = '{$value}'  and uniacid = '{$_W['uniacid']}'");
                if (!empty($st)) {
                    $stores[$key] = $st;
                }
            }
        }


    }else{
        $tip = '你没有权限进行此操作！';
        echo "<script>alert('" . $tip . "');</script>";
    }



    include wl_template('member/cashcode_get');
}

if ($op == 'post') {
    $id = intval($_GPC['id']);
    $code = $_GPC['code'];
    if ($id) {
        $coupon_template = coupon_template($id);
        $coupon_data = array(
            'uniacid' => $_W['uniacid'],
            'coupon_template_id' => $coupon_template['id'],
            'name' => $coupon_template['name'],
            'cash' => $coupon_template['value'],
            'is_at_least' => $coupon_template['is_at_least'],
            'at_least' => $coupon_template['at_least'],
            'description' => $coupon_template['description'],
            'start_time' => $coupon_template['start_time'],
            'goodsid' => $coupon_template['goodsid'],
            'end_time' => $coupon_template['end_time'],
            'use_time' => time(),
            'openid' => $openid,
            'createtime' => TIMESTAMP,
            'store_id'=>$_GPC['actualAdress'],
            'code'=>$code
        );
        pdo_insert('tg_cash_code', $coupon_data);

        pdo_update('tg_cash_code_qrcode',['is_used'=>2],['template_id'=>$coupon_template['id'],'code'=>$code]);

        $sql = 'UPDATE '.tablename('tg_cash_code_template').' SET `quantity_used` = `quantity_used` + :quantity WHERE id=:id';
        $params = array(
            ':id' => $id,
            ':quantity' => 1
        );
        pdo_query($sql, $params);
        wl_json(0, '核销成功');
    } else {
        wl_json(0, '缺少优惠券id参数');
    }
}
exit();