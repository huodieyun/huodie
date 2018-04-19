<?php

define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
load()->model('reply');
load()->app('common');
load()->classs('wesession');

if ($op == 'goods_rand') {

    $app = intval($_GPC['app']);
    $condition = '';
    if ($app == 1) {
        $condition .= " and is_app = 1 ";
    } else {
        $condition .= " and is_applet = 1 ";
    }

    $list = pdo_fetchall("SELECT id,gname,gprice,oprice,share_image,gimg,selltype FROM " . tablename('tg_goods') . " WHERE uniacid = :uniacid AND isshow = 1 AND selltype < 2 {$condition} ORDER BY rand() LIMIT 9 ", array(':uniacid' => $_GPC['uniacid']));
    foreach ($list as $key => $value) {
        if (!empty($value['share_image'])) {
            $list[$key]['gimg'] = tomedia($value['share_image']);
        } else {
            $list[$key]['gimg'] = tomedia($value['gimg']);
        }
    }
    $data = array();
    $data['list'] = $list;
    $data['status'] = false;
    if ($config['base']['guess'] == 1) {
        $data['status'] = true;
    }


    die(json_encode($data));
    //echo json_encode($list);
}
//app是否开启微信登陆
if($op == 'wx_login_on'){
    $uniacid = $_GPC['uniacid'];
    //获取参数
    $sys = pdo_fetch("SELECT * FROM cm_tg_setting WHERE `key`='kaiguan' and uniacid={$uniacid}");
    $sys = unserialize($sys['value']);
    foreach ($sys as $key=>$value){
        $arr[] = $key;
    }
    if(in_array('wechat_login',$arr)){
        if($sys['wechat_login'] == 1){
            //开启
            $data['status'] = '1';
        }else{
            $data['status'] = '2';
        }
    }else{
        $data['status'] = '1';
    }
    die(json_encode($data));
}


if ($op == 'wx_login') {
    global $_GPC;
    global $_W;
    $code = trim($_GPC['code']);
    $appid = trim($_GPC['appid']);
    $appsecret = trim($_GPC['secret']);

    if (empty($code)) {
        //  app_error(AppError::$ParamsError);
    }


    $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $code . '&grant_type=authorization_code';
    load()->func('communication');
    $resp = ihttp_request($url);

    if (is_error($resp)) {
        die(json_encode(array('status' => 0, 'message' => $resp['message'])));
        // app_error(AppError::$SystemError, $resp['message']);
    }


    $arr = @json_decode($resp['content'], true);


    if (!(is_array($arr)) || !(isset($arr['openid']))) {
        die(json_encode(array('status' => 0, 'message' => $resp['message'])));
        // app_error(AppError::$WxAppLoginError);
    }

    die(json_encode($arr));

}
if ($op == 'goods_list') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['cid']);
    $wechats = pdo_fetch("select tpl from " . tablename('account_wechats') . " where uniacid = '{$_GPC['uniacid']}'");
    $func = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$wechats['tpl']}'");

    $condition = ' and `uniacid` = :uniacid';
    $params = array(':uniacid' => $_GPC['uniacid']);
    if ($cid > 0) {
        $condition .= "  AND fk_typeid = '{$cid}'";
    }
    $condition .= ' and selltype=1 and isshow=1';
    $orderby = 'order by id desc';
    $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
    $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
    $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql, $params);

    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_goods') . "where 1 $condition ", $params);

    $data = array();
    $data['list'] = $list;
    $data['total'] = $total;
    foreach ($data['list'] as $key => &$value) {
        $value['deliverytype'] = strval(intval($value['deliverytype']));
        if (empty($value['share_image'])) {
            $value['share_image'] = tomedia($value['gimg']);
        } else {
            $value['share_image'] = tomedia($value['share_image']);
        }

        $value['gimg'] = tomedia($value['gimg']);
        $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$value['id']}'");
        foreach ($advs as &$adv) {
            $adv['thumb'] = tomedia($adv['thumb']);

        }
        $data['list'][$key]['advs'] = $advs;
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid'], ':g_id' => $value['id']));
        $data['list'][$key]['history_limit'] = intval($old_data['num']);
    }
    $goodses = $data;
    die(json_encode($goodses));
    //die($sql);
}
//幻灯片
if ($op == 'banner') {
    $advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where uniacid = {$_GPC['uniacid']} and enabled = 1 and merchant_id = 0 order by displayorder DESC");
    foreach ($advs as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
        if (substr($adv['link'], 0, 4) != 'http') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
    die(json_encode($advs));
}
//分类
if ($op == 'category') {
    $category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_GPC['uniacid']}' and enabled=1 and parentid=0 ORDER BY parentid ASC, displayorder DESC");
    foreach ($category as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
        $adv['smallthumb'] = tomedia($adv['smallthumb']);
    }
    die(json_encode($category));
}
//商品详情  旧版 勿删
if ($op == 'goods_detail') {
    $id = intval($_GPC['id']);
//$goods = goods_get_by_params("id = {$id}");
    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id='{$id}'");
    $goods['gimg'] = tomedia($goods['gimg']);
    $goods['deliverytype'] = strval(intval($goods['deliverytype']));
    $goods['gdesc'] = str_replace("http://www.lexiangpingou.cn/attachment/", "https://res.lexiangpingou.cn/", $goods['gdesc']);

//得到图集
    $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$id}'");
    foreach ($advs as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
        if (substr($adv['link'], 0, 5) != 'http:') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
    $storesids = explode(",", $goods['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$goods['uniacid']}'");
        }
    }
    if (empty($goods['goodsdesc'])) {
        $goods['goodsdesc'] = '';
    }

    $goods['advs'] = $advs;
    $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$id}' ");
    $goods['params'] = $params;
    $goods['stores'] = $stores;
    die(json_encode($goods));
}

//商品详情优化版
if ($op == 'goods_detail_yh') {
    $id = intval($_GPC['id']);
    $guige = trim($_GPC['guige']);

    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id='{$id}'");
    $goods['gimg'] = tomedia($goods['gimg']);
    $goods['deliverytype'] = strval(intval($goods['deliverytype']));
    $goods['gdesc'] = str_replace("http://www.lexiangpingou.cn/attachment/", "https://res.lexiangpingou.cn/", $goods['gdesc']);

    //拼团玩法
    $setting = pdo_fetch("select `value` from " .tablename('tg_setting') ." where uniacid = {$goods['uniacid']} and `key` = 'kaiguan' ");
    $setting = unserialize($setting['value']);
    $goods['group_rule_switch'] = strval($setting['group_rule_switch']) ;

//得到图集
    $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$id}'");
    foreach ($advs as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
        if (substr($adv['link'], 0, 4) != 'http') {
            $adv['link'] = "http://" . $adv['link'];
        }
        unset($adv);
    }
    $storesids = explode(",", $goods['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$goods['uniacid']}'");
        }
    }
    if (empty($goods['goodsdesc'])) {
        $goods['goodsdesc'] = '';
    }

    if ($guige) {
        $kunum1 = pdo_fetch("SELECT productprice,stock,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid = '{$id}' and title = '{$guige}' ");
        $goods['weight'] = $kunum1['weight'];
    }

    //APP的分享团
    if ($goods['ison'] == 1) {
//        if ($this->module['config']['sharestatus'] != 2) {
        $thistuan = pdo_fetchall("select * from " . tablename('tg_group') . " where uniacid='{$goods['uniacid']}' and goodsid = '{$id}' and groupstatus=3 and lacknum<>neednum order by id asc ");
        if (!empty($thistuan)) {
            foreach ($thistuan as $key => $value) {
                $tuan_first_order = order_get_by_params(" tuan_id = '{$value['groupnumber']}' and tuan_first=1 ");
                $userinfo = member_get_by_params(" openid = '{$tuan_first_order['openid']}'");
                $thistuan[$key]['avatar'] = $userinfo['avatar'];
                $thistuan[$key]['nickname'] = $userinfo['nickname'];
                $thistuan[$key]['nownum'] = $value['neednum'] - $value['lacknum'];
            }
        }
//        }
        $goods['thistuan'] = $thistuan;
    }
    $goods['param_level_info'] = array();
    if ($goods['group_level_status'] == 2) {
        $param_level = unserialize($goods['group_level']);
        for ($i = 0; $i < count($param_level) - 1; $i++) {
            for ($j = 0; $j < count($param_level) - $i - 1; $j++) {
                if ($param_level[$j]['groupnum'] > $param_level[$j + 1]['groupnum']) {
                    $temp = $param_level[$j];
                    $param_level[$j] = $param_level[$j + 1];
                    $param_level[$j + 1] = $temp;
                }
            }
        }
        $goods['param_level_info'] = $param_level;
        if ($param_level) {
            $num = round(((100 - count($param_level) * 2) / count($param_level)));
        }
        $goods['p'] = $param_level[0]['groupprice'];
        foreach ($param_level as $item) {
            $numPerson .= "," . $item['groupnum'];
            $numPrices .= "," . $item['groupprice'];
        }

        $numGroup = $goods['groupnum'];
    }
    $goods['advs'] = $advs;
    $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$id}' ");
    $goods['params'] = $params;
    $goods['stores'] = $stores;
    die(json_encode($goods));
}


function order_get_by_params($params = '') {
    global $_W;
    if(!empty($params)){
        $params = ' where '. $params;
    }
    $sql = "SELECT * FROM " . tablename('tg_order') . " {$params} ";
    $order = pdo_fetch($sql);
    return $order;
}

function member_get_by_params($params = ''){
    if(!empty($params)){
        $params = ' where '. $params;
    }
    $sql = "SELECT * FROM " . tablename('tg_member') . $params;
    $member = pdo_fetch($sql);
    return $member;
}

/*
 * 申请兼职
 */
if ($op == 'update_member') {

    pdo_update('tg_member',
        array(
            'name' => $_GPC['name'],
            'mobile' => $_GPC['mobile'],
            'addmobile' => $_GPC['mobile'],
            'type' => 1,
            'addtime' => TIMESTAMP,
            'shopname' => $_GPC['shopname']
        ), array('id' => $_GPC['id']));
    $member = pdo_fetch("select uid from " . tablename('tg_member') . " where id = '{$_GPC['id']}'");
    pdo_update('mc_members', array('mobile' => $_GPC['mobile']), array('uid' => $member['uid']));
    die(json_encode(array('status' => 1)));
}
if ($op == 'test') {

    /*
	$logdata=array(
		'orderno'=>'openid',
		'log'=>$_GPC['openid'],
		'from'=>''
		);
		pdo_insert('tg_log', $logdata);*/
    die(json_encode(array('ip' => getClientIP())));
}
if ($op == 'sendtime') {
    $ad = $_GPC['ad'];
    $uniacid = $_GPC['uniacid'];
    $sendtimes = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid ='{$uniacid}'  and status=1 order by starttime asc");
    $hour = 0;
    if ($ad == '今天') {
        $dtime = date('Y-m-d');
        $hour = date('H');
    } elseif ($ad == '明天') {
        $dtime = time() + (1 * 24 * 60 * 60);
    } elseif ($ad == '后天') {
        $dtime = time() + (2 * 24 * 60 * 60);
    }
    $ttime = array();
    foreach ($sendtimes as $k => $v) {
        $valtime = $v['starttime'] . ":00-" . $v['endtime'] . ":59";
        $psql1 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' and sendtime='{$valtime}' and senddate='{$dtime}' and status not in (0,4,5,9)");
        $numa1 = count($psql1);

        if ($v['total'] > $numa1 && $v['starttime'] > $hour) {
            $ttime[$k]['name'] = $valtime;
        }

    }

    die(json_encode($ttime));
}


//小程序送货上门运费

if ($op == 'xcxsendtime') {
    $ad = $_GPC['ad'];
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $sendtimes = pdo_fetchall("SELECT * FROM " . tablename('tg_sendtime') . " WHERE uniacid ='{$uniacid}'  and status=1 order by starttime asc");
    $hour = 0;
    if ($ad == '今天') {
        $dtime = date('Y-m-d');
        $hour = date('H');
    } elseif ($ad == '明天') {
        $dtime = time() + (1 * 24 * 60 * 60);
    } elseif ($ad == '后天') {
        $dtime = time() + (2 * 24 * 60 * 60);
    }
    $ttime = array();
    foreach ($sendtimes as $k => $v) {
        $valtime = $v['starttime'] . ":00-" . $v['endtime'] . ":59";
        $psql1 = pdo_fetchall("SELECT id FROM " . tablename('tg_order') . " WHERE uniacid = '{$uniacid}' and sendtime='{$valtime}' and senddate='{$dtime}' and status not in (0,4,5,9)");
        $numa1 = count($psql1);

        if ($v['total'] > $numa1 && $v['starttime'] > $hour) {
            $ttime[$k]['name'] = $valtime;
        }

    }


    $is_tuan = intval($_GPC['is_tuan']);

    $merchant_id = intval($_GPC['merchantid']);
    $delivery = pdo_fetch("SELECT Deliverys,dispatchname FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$uniacid}' and dispatchtype = 2 and enabled = 1 and merchantid = '{$merchant_id}' ORDER BY id asc");
    $dispatch_delivery = unserialize($delivery['Deliverys']);//送货上门运费
    if ($is_tuan) {

        $gid = intval($_GPC['gid']);
        $istuan = intval($_GPC['istuan']);
        if ($gid) {
            $goods = pdo_get('tg_goods', array('id' => $gid));

            if ($istuan) {
                $bprice = sprintf("%.2f", $goods['gprice']);
            } else {
                $bprice = sprintf("%.2f", $goods['oprice']);
            }
            $cost = 0;
            $temp = 0;
            $i = 0;
            $cout = count($dispatch_delivery);

            foreach ($dispatch_delivery as $key => $item) {
                $cart = sprintf("%.2f", $item['cart']);
                $i = $i + 1;
                if ($bprice >= $temp && $bprice <= $cart) {
                    $cost = $item['cost'];
                }
                $temp = sprintf("%.2f", $item['cart']);

                if ($i == $cout && $bprice > $temp && $i > 1) {
                    $cost = $item['cost'];
                }
            }
            if ($goods['isfree'] == 1 || $firstfree == 1) {
                $cost = 0;
            }
        } else {
            die(json_encode(array('status' => 0, 'message' => '传入参数错误')));
        }
        die(json_encode(array('status' => 1, 'ttime' => $ttime, 'cost' => $cost)));
    } else {

        $bprice = 0;
        $orderlist = pdo_fetchall("select num,oprice from " . tablename('tg_collect') . " where uniacid = '{$uniacid}' and orderno = '0' and openid = '{$openid}'");
        foreach ($orderlist as $key => $value) {
            $bprice = $bprice + $value['num'] * $value['oprice'];
        }
        $cost = 0;
        $temp = 0;
        $cout = count($dispatch_delivery);
        foreach ($dispatch_delivery as $key => $item) {
            $i = $i + 1;
            if ($bprice >= $temp && $bprice <= $item['cart']) {
                $cost = $item['cost'];
                break;
            }
            $temp = $item['cart'];

            if ($i == $cout && $bprice > $item['cart']) {
                $cost = $item['cost'];
                break;
            }
        }
        if ($goods['isfree'] == 1 || $firstfree == 1) {
            $cost = 0;
        }
        die(json_encode(array('status' => 1, 'ttime' => $ttime, 'cost' => $cost)));
    }


}


if ($op == 'store') {
    $uniacid = $_GPC['uniacid'];
    $gid = $_GPC['gid'];
    $goods = pdo_fetch('select hexiao_id from ' . tablename('tg_goods') . ' where uniacid=:uniacid and id=:id', array(':uniacid' => $uniacid, ':id' => $gid));
    $storesids = explode(",", $goods['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$uniacid}'");
        }
    }
    die(json_encode($stores));
}
if ($op == 'address') {
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $address = pdo_fetch('select * from ' . tablename('tg_address') . ' where uniacid=:uniacid and openid=:openid and status=1', array(':uniacid' => $uniacid, ':openid' => $openid));
    die(json_encode($address));
}
if ($op == 'addresslist') {
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $address = pdo_fetchall('select * from ' . tablename('tg_address') . ' where uniacid=:uniacid and openid=:openid ', array(':uniacid' => $uniacid, ':openid' => $openid));
    die(json_encode($address));
}
if ($op == 'address_del') {
    $id = $_GPC['id'];
    pdo_delete('tg_address', array('id' => $id));
    die(json_encode(array('status' => 1)));
}
if ($op == 'address_edit') {
    $id = $_GPC['id'];
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    if (!empty($id)) {
        $address = pdo_fetch('select * from ' . tablename('tg_address') . ' where uniacid=:uniacid and id=:id', array(':uniacid' => $uniacid, ':id' => $id));
    }
    $data = array(
        'openid' => $openid,
        'uniacid' => $uniacid,
        'cname' => $_GPC['myname'],
        'tel' => $_GPC['myphone'],
        'province' => $_GPC['province'],
        'city' => $_GPC['city'],
        'county' => $_GPC['county'],
        'detailed_address' => $_GPC['detailed'],
        'type' => $_GPC['ctype'],
        'ctype' => 1,
        'status' => $_GPC['status'],
        'addtime' => TIMESTAMP
    );
    if ($_GPC['status'] == 1) {
        pdo_update('tg_address', array('status' => 0), array('status' => 1, 'openid' => $openid));
    }

    if (!empty($id)) {
        pdo_update('tg_address', $data, array('id' => $id));
    } else {
        pdo_insert('tg_address', $data);
    }
    die(json_encode(array('status' => 1)));
}
if ($op == 'order_corfim') {
    $goods = pdo_fetch('select * from ' . tablename('tg_goods') . 'where id=:id', array(':id' => $_GPC['g_id']));
    $tuan_id = intval($_GPC['tuan_id']);
    $tuan_first = 0;
    if (!$tuan_id && intval($_GPC['is_tuan']) == 1) {
        $tuan_first = 1;
    }
    $data = array(
        'uniacid' => $_GPC['uniacid'],//公众号ID
        'gnum' => $_GPC['gnum'],//购买数量
        'openid' => $_GPC['openid'],//openid
        'ptime' => '',//支付成功时间
        'orderno' => $_GPC['orderno'],//订单编号
        'pay_price' => $_GPC['pay_price'],//支付金额（商品金额+运费-优惠金额-团长优惠）
        'goodsprice' => $_GPC['goodsprice'],//商品金额（商品单价*购买数量）
        'goodsname' => $goods['gname'],//商品名称
        'freight' => $_GPC['freight'],//运费
        'first_fee' => $_GPC['firstdiscount'],//团长优惠
        'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid' => $_GPC['addressid'],
        'addresstype' => $_GPC['addresstype'],//1公司2家庭
        'dispatchtype' => $_GPC['dispatchtype'],//配送方式
        'comadd' => $_GPC['store_id'],//
        'addname' => $_GPC['cname'],
        'mobile' => $_GPC['tel'],
        'address'     => $_GPC['province'].$_GPC['city'].$_GPC['county'].$_GPC['address'],
        'province'    => $_GPC['province'],
        'city'        => $_GPC['city'],
        'county'      => $_GPC['county'],
        'detailed_address' => $_GPC['address'],
        'g_id' => $_GPC['g_id'],
        'tuan_id' => $_GPC['tuan_id'],
        'is_tuan' => $_GPC['is_tuan'],//拼团为1，其它类型为0
        'tuan_first' => $tuan_first,
        'starttime' => TIMESTAMP,
        'senddate' => $_GPC['senddate'],//送货日期
        'sendtime' => $_GPC['sendtime'],//送货时间
        'remark' => $_GPC['remark'],//订单备注
        'comtype' => 0,
        'commission' => $goods['commission'],//佣金金额
        'com_type' => $goods['com_type'],
        'commissiontype' => $goods['commissiontype'],//佣金计算类型
        'is_hexiao' => 0,
        'selltype' => $goods['selltype'],//团购类型
        'credits' => '',//积分
        'optionname' => $_GPC['optionname'],//规格
        'optionid' => $_GPC['$optionid'],//规格ID
        'couponid' => $_GPC['$couponid'],//优惠券ID
        'is_usecard' => $_GPC['is_usecard'],//使用优惠券为1，不使用为0
        'discount_fee' => $_GPC['discount_fee'],//优惠券优惠金额
        'createtime' => TIMESTAMP,
        'bdeltime' => '',
        'issued' => 0,
        'origin' => $_GPC['origin'],
        'couponsids' => $goods['couponsids'],
        'supply_goodsid' => $goods['supply_goodsid'],
        'is_score'=>$goods['score_num']>0?1:0,
        'score'=>$goods['score_num']
    );
    pdo_insert('tg_order', $data);
    $orderid = pdo_insertid();
    if ($_GPC['dispatchtype'] == 3) {
        pdo_update('tg_member', array('addname' => $_GPC['cname'], 'addmobile' => $_GPC['tel']), array('openid' => $_GPC['openid'], 'uniacid' => $_GPC['uniacid']));

    }
    /*
	if($typeid == 2 || $typeid==4){

		wl_load()->classs('qrcode');
		$createqrcode =  new creat_qrcode();
		$createqrcode->creategroupQrcode($data['orderno']);
	}
*/
    if (empty($tuan_id)) {
        $groupnumber = $orderid;
        if ($data['is_tuan'] == 1 && $orderid != 0) {
            $starttime = time();
            $endtime = $starttime + $goods['endtime'] * 3600;
            $selltype = $goods['selltype'];
            if ($selltype == 7) {
                $goods['on_success'] = 1;
            }
            $data2 = array(
                'uniacid' => $_GPC['uniacid'],
                'groupnumber' => $groupnumber,
                'groupstatus' => 3,//订单状态,1组团失败，2组团成功，3,组团中
                'goodsid' => $goods['id'],
                'openid' => $_GPC['openid'],//openid
                'goodsname' => $goods['gname'],
                'neednum' => $goods['groupnum'],
                'lacknum' => $goods['groupnum'],
                'starttime' => $starttime,
                'selltype' => $goods['selltype'],
                'endtime' => $endtime,
                'on_success' => $goods['on_success'],
                'price' => $goods['gprice'],
                'group_level' => $goods['group_level']
            );
            pdo_insert('tg_group', $data2);
            pdo_update('tg_order', array('tuan_id' => $orderid), array('id' => $orderid));
        }
    }
    die(json_encode(array('status' => 1, 'message' => '订单提交成功')));
}
if ($op == 'check_payresult') {
    $orderno = $_GPC['orderno'];
    $transid = $_GPC['transid'];
    $fee = $_GPC['fee'];
    $order_out = pdo_fetch('select * from ' . tablename('tg_order') . ' where orderno=:orderno', array(':orderno' => $orderno));
    $uniacid = $order_out['uniacid'];
    $openid = $order_out['openid'];
    if (!empty($transid)) {
        //插入paylog
        $paylog_data = array(
            'tid' => $orderno,
            'uniontid' => $orderno,
            'tag' => $transid,
            'type' => 'wechat',
            'uniacid' => $order_out['uniacid'],
            'openid' => $order_out['openid']
        );
        pdo_insert('core_paylog', $paylog_data);
        /*按订单量使用 减少订单量*/
        $acct = pdo_fetch("select * from " . tablename('account_wechats') . " where  uniacid =:uniacid", array(':uniacid' => $order_out['uniacid']));
        if ($acct['vip'] == 0 && $acct['ordernum'] > 0) {
            pdo_update('account_wechats', array('ordernum' => $acct['ordernum'] - 1), array('uniacid' => $order_out['uniacid']));
        }
        /*按订单量使用 减少订单量*/
        pdo_update('tg_order', array('status' => 1, 'pay_type' => 2, 'transid' => $transid, 'ptime' => TIMESTAMP, 'price' => $fee), array('orderno' => $orderno));
        //组团逻辑
        if ($order_out['is_tuan'] == 1) {
            $group = pdo_fetch('select * from ' . tablename('tg_group') . ' where groupnumber=:tuan_id', array(':tuan_id' => $order_out['tuan_id']));
            $nowtuannum = pdo_fetchcolumn('select count(id) from ' . tablename('tg_order') . ' where tuan_id=:tuan_id and status in(1,2,3,8)', array(':tuan_id' => $order_out['tuan_id']));
            if ($group['neednum'] > $nowtuannum) {
                pdo_update('tg_group', array('lacknum' => $group['neednum'] - $nowtuannum, 'nownum' => $nowtuannum), array('groupnumber' => $order_out['tuan_id']));
            }

            if ($group['neednum'] == $nowtuannum) {
                pdo_update('tg_order', array('status' => 8), array('tuan_id' => $order_out['tuan_id'], 'status' => 1));
                pdo_update('tg_group', array('groupstatus' => 2, 'lacknum' => $group['neednum'] - $nowtuannum, 'nownum' => $nowtuannum), array('groupnumber' => $order_out['tuan_id']));
                //删除分享图片
                unlink("../addons/lexiangpingou/lxapi/share_tuan/".$order_out['tuan_id'].'.jpeg');
                $all = pdo_fetchall("select id,g_id,gnum,orderno,optionname,price,mobile,status,freight,couponid from " . tablename('tg_order') . " where tuan_id='{$order_out['tuan_id']}' and status in (3,8,1,7,10,6)");
                if ($group['is_amount'] == 1 && ($group['selltype'] == 4 || $group['selltype'] == 7)) {
                    $allnum = pdo_fetchcolumn("select SUM(gnum) from " . tablename('tg_order') . " where tuan_id='{$order_out['tuan_id']}' and status in (3,8,1,7,10,6)");
                } else {
                    $allnum = count($all);
                }
                //如果是阶梯团，插入退款记录
                if ($group['selltype'] == 4) {
                    $param_level = unserialize($group['group_level']);
                    $mininum = $param_level[0]['groupnum'];
                    $miniprice = 0;
                    $maxnum = 0;
                    $maxprice = 0;
                    for ($i = 0; $i < count($param_level); $i++) {


                        for ($j = 0; $j < count($param_level) - $i - 1; $j++) {
                            if ($param_level[$j]['groupnum'] < $param_level[$j + 1]['groupnum']) {
                                $temp = $param_level[$j];
                                $param_level[$j] = $param_level[$j + 1];
                                $param_level[$j + 1] = $temp;
                            }
                            if ($param_level[$j]['groupnum'] >= $maxnum) {
                                $maxnum = $param_level[$j]['groupnum'];
                                $maxprice = $param_level[$j]['groupprice'];//人数最多时  对应的设定价格
                            }
                            if ($param_level[$j]['groupnum'] <= $mininum) {
                                $mininum = $param_level[$j]['groupnum'];
                                $miniprice = $param_level[$j]['groupprice'];//人数最少时  对应的设定价格
                            }
                        }
                    }

                    for ($i = 0; $i < count($param_level); $i++) {
                        //$bdata=array('from'=>$param_level[$i]['groupnum'],'log'=>$allnum,'orderno'=>$i);
                        //pdo_insert('tg_log', $bdata);
                        if ($param_level[$i]['groupnum'] > $allnum && $param_level[$i + 1]['groupnum'] <= $allnum) {
                            $tempprice = $param_level[$i + 1]['groupprice'];
                        }
                        if ($param_level[$i]['groupnum'] == $allnum) {
                            $tempprice = $param_level[$i]['groupprice'];
                        }
                        $groupprice = $param_level[$i]['groupprice'];
                    }
                    foreach ($all as $row) {
                        //插入退款记录
                        if ($group['selltype'] == 4) {
                            $refundprice = round((round($row['price'], 2) - round($tempprice, 2) * $row['gnum']), 2) - round($row['freight'], 2);
                            //优惠券折扣
                            if ($row['couponid']) {
                                $coupon = pdo_fetch("SELECT cash FROM cm_tg_coupon WHERE id={$row['couponid']}");
                                $refundprice = round($coupon['cash'], 2) + round((round($row['price'], 2) - round($tempprice, 2) * $row['gnum']), 2) - round($row['freight'], 2);
                            }
                            if ($refundprice > 0 && $refundprice != $row['price'] && $row['mobile'] <> '虚拟' && $row['status'] <> 7 && $row['status'] <> 10) {
                                $data1 = array('orderno' => $row['orderno'], 'status' => 1, 'refundprice' => $refundprice);
                                pdo_insert('tg_order_level_refund', $data1);
                            }
                        }
                    }

                }
            }
            if ($group['neednum'] < $nowtuannum) {
                pdo_update('tg_order', array('status' => 10), array('orderno' => $orderno));
                pdo_update('tg_group', array('groupstatus' => 2), array('groupnumber' => $order_out['tuan_id']));
                //删除分享图片
                unlink("../addons/lexiangpingou/lxapi/share_tuan/".$order_out['tuan_id'].'.jpeg');
            }
            $tuan_id = $order_out['tuan_id'];
        } else {
            $tuan_id = '';
            //单买逻辑
            pdo_update('tg_order', array('status' => 8), array('orderno' => $order_out['orderno']));
        }
        if ($order_out['g_id'] > 0) {
            $goodsInfo = pdo_fetch('select * from ' . tablename('tg_goods') . 'where id=:id', array(':id' => $order_out['g_id']));
            if ($goodsInfo['gnum'] >= $order_out['gnum']) {
                pdo_update('tg_goods', array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
            } elseif (!empty($goodsInfo['gnum'])) {
                pdo_update('tg_goods', array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
            }
        }
        if ($order_out['tuan_first'] == 1) {
            $dat['uniacid'] = $uniacid;
            $dat['openid'] = $openid;
            $dat['tuan_id'] = $tuan_id;
            $dat['commissiontype'] = $order_out['commissiontype'];
            $dat['commission'] = $order_out['commission'];
            $dat['gid'] = $order_out['g_id'];
            $dat['createtime'] = TIMESTAMP;
            pdo_insert('tg_commander', $dat);
        }


        /*增加历史购买数量*/
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $order_out['uniacid'], ':openid' => $order_out['openid'], ':g_id' => $order_out['g_id']));
        if (empty($old_data)) {
            $logdata = array(
                'g_id' => $order_out['g_id'],
                'openid' => $order_out['openid'],
                'num' => $order_out['gnum'],
                'uniacid' => $order_out['uniacid']
            );
            pdo_insert('tg_goods_openid', $logdata);
        } else {
            pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $order_out['gnum']), array('id' => $old_data['id']));

        }
        /*增加历史购买数量*/
        die(json_encode(array('status' => 1, 'tuan_id' => $tuan_id, 'order_id' => $order_out['id'], 'g_id' => $order_out['g_id'], 'orderno' => $order_out['orderno'])));
    }
    die(json_encode(array('status' => 0)));
}
//隐私条款获取公众号信息
if($op == 'terms'){
    $uniacid = $_GPC['uniacid'];
    $info = pdo_fetch("SELECT * FROM cm_account_wechats WHERE uniacid={$uniacid}");
    $info['platform_logo'] = tomedia($info['platform_logo']);
    $info['homeimg'] = tomedia($info['homeimg']);
    $info['regbg'] = tomedia($info['regbg']);
    $setting = pdo_fetch("select * from".tablename('tg_setting')." where `key`  = 'tginfo' and uniacid={$uniacid}");

    if(!empty($setting)){
        $set = unserialize($setting['value']);
        $set['slogo'] = tomedia($set['slogo']);
        $paysetting = pdo_fetch("select * from".tablename('uni_settings')." where uniacid={$uniacid}");
        if(!empty($paysetting)){
            $pay = unserialize($paysetting['payment']);
            die(json_encode(array('info' => $set,'pay'=>$pay)));
        }else{
            die(json_encode(array('info' => '','pay'=>'')));
        }

    }else{
        die(json_encode(array('info' => '','pay'=>'')));
    }
}



//优惠券
//获取优惠券
if($op == 'get_coupon_list'){
    $where = " WHERE uniacid = {$_GPC['uniacid']}";
    $where .= " AND start_time < :time AND end_time > :time AND enable = :enable";
    $params[':time'] = time();
    $params[':enable'] = 1;
    $size = 5;
    $page = !empty($_GPC['page'])?$_GPC['page']:1;
    $sql = "select * from".tablename('tg_coupon_template')." $where LIMIT " . ($page - 1) * $size . " , " . $size;
    $tg_coupon_templates = pdo_fetchall($sql,$params);
    if(!empty($tg_coupon_templates)){

        $total = count($tg_coupon_templates);
        $sql = "SELECT `coupon_template_id`, COUNT(DISTINCT `uid`) as 'count_receive_person', COUNT('id') as 'count_receive_num' FROM " .tablename('tg_coupon'). " GROUP BY `coupon_template_id`";
        $coupon_count = pdo_fetchall($sql, array(), 'coupon_template_id');
        foreach ($tg_coupon_templates as &$tg_coupon_template) {
            $tg_coupon_template['cash'] = $tg_coupon_template['value'];
            if ($tg_coupon_template['end_time'] < time()) {
                pdo_update('tg_coupon_template', array('enable' => 0), array('id' => $tg_coupon_template['id']));
            }
            $totalused=pdo_fetchall("select id from ".tablename('tg_coupon').' where coupon_template_id=:id and use_time>0',array(':id'=>$tg_coupon_template['id']));
            $totalallused=pdo_fetchall("select id from ".tablename('tg_coupon').' where coupon_template_id=:id',array(':id'=>$tg_coupon_template['id']));

            $tg_coupon_template['stock'] = $tg_coupon_template['total'] - $tg_coupon_template['quantity_issue'];
            $tg_coupon_template['count_receive_num'] = count($totalallused);
            $tg_coupon_template['count_receive_person'] = count($totalused);
            $tg_coupon_template['end_time'] = date('Y-m-d', $tg_coupon_template['end_time']);
        }
        die(json_encode(array('list' => $tg_coupon_templates)));
    }else{
        die(json_encode(array('list' => array())));
    }

}

if($op == 'get_coupon_detail'){
    $id = $_GPC['id'];
    $code = $_GPC['code'];
    $uniacid = $_GPC['uniacid'];
//  获取二维码使用状态
    if($code){
        $codestatus = pdo_fetch("SELECT * FROM cm_tg_coupon_qrcode WHERE template_id={$id} AND code={$code}");
        if($codestatus['is_used']==2){
            die(json_encode(array('info' => '二维码已失效')));
        }
    }
    if ($id) {
        $coupon = coupon_template($id,$uniacid);
        $coupon['end_time'] = date('Y-m-d', $coupon['end_time']);
        die(json_encode(array('info' =>$coupon)));
    } else {
        die(json_encode(array('info' => '优惠券不存在')));
    }
}
function coupon_template($coupon_template_id,$uniacid) {
    $coupon_template_all = coupon_template_all($uniacid);
    return $coupon_template_all[$coupon_template_id];
}
function coupon_template_all($uniacid) {
    $coupon_template_all = pdo_fetch_many('tg_coupon_template', array('uniacid' => $uniacid), array(), 'id', 'ORDER BY `id` DESC');
//    cache_write($cache_key, $coupon_template_all);
    return $coupon_template_all;
}
function pdo_fetch_many($tablename, $params = array(), $fields = array(), $key = '', $after_where = ''){
    if (!empty($fields) && !empty($key) && !in_array($key, $fields)) {
        $fields[] = $key;
    }
    $result = pdo()->getall($tablename, $params, $fields, $key, $after_where);
    if (!is_array($result) || empty($result)) {
        return array();
    }
    return $result;
}
//领取优惠券
if($op == 'coupon_grant'){
    $id = intval($_GPC['id']);
    $code = $_GPC['code'];
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    if ($id) {
        $coupon = coupon_grant($openid, $id,$code,$uniacid);
        if ($coupon['errno'] != 1) {
//            wl_json(1);
            die(json_encode(array('status' =>1)));
        } else {
//            wl_json(0, $coupon['message']);
            die(json_encode(array('status' =>0,'message'=>$coupon['message'])));
        }
    } else {
//        wl_json(0, '缺少优惠券id参数');
        die(json_encode(array('status' =>0,'message'=>'缺少优惠券id参数')));
    }
}
function coupon_grant($openid, $coupon_template_id,$code,$uniacid){
    if (!activity_enable('coupon')) {
        return error(1, '优惠券未开启');
    }
    $coupon_template = coupon_template($coupon_template_id,$uniacid);
    $tatal = coupon_check($openid, $coupon_template_id,$uniacid);
    if (empty($coupon_template)) {
        return error(1, '优惠券不存在或已删除');
    }
    if ($coupon_template['quota'] > 0 && $tatal >= $coupon_template['quota']) {
        return error(1, '超过领取数量，小调皮不要贪心哦');
    }
    if ($coupon_template['total'] <= $coupon_template['quantity_issue']) {
        return error(1, '优惠券已发完');
    }
    if ($coupon_template['enable'] != 1) {
        return error(1, '商家停止发放优惠券');
    }
    if ($coupon_template['is_random'] == 2) {
        $cash = $coupon_template['value'] + mt_rand() / mt_getrandmax() * ($coupon_template['value_to'] - $coupon_template['value']);

        //$cash = mt_rand($coupon_template['value'], $coupon_template['value_to']);
    } else {
        $cash = $coupon_template['value'];
    }
    $coupon_data = array(
        'uniacid' => $uniacid,
        'coupon_template_id' => $coupon_template['id'],
        'name' => $coupon_template['name'],
        'cash' => $cash,
        'is_at_least' => $coupon_template['is_at_least'],
        'at_least' => $coupon_template['at_least'],
        'description' => $coupon_template['description'],
        'start_time' => $coupon_template['start_time'],
        'goodsid' => $coupon_template['goodsid'],
        'end_time' => $coupon_template['end_time'],
        'use_time' => 0,
        'openid' => $openid,
        'createtime' => TIMESTAMP
    );
    pdo_update('tg_coupon_qrcode',['is_used'=>2],['template_id'=>$coupon_template['id'],'code'=>$code]);
    pdo_insert('tg_coupon', $coupon_data);
    $coupon_data['id'] = pdo_insertid();

    coupon_quantity_issue_increase($coupon_template['id'], 1);

    return $coupon_data;
}
function activity_enable($activity_name){
    $activities = array('reward','present','coupon');
    return in_array($activity_name, $activities);
}
function coupon_check($openid, $coupon_template_id,$uniacid){
    global $_W;
    $tatal = pdo_select_count('tg_coupon',array('openid' => $openid, 'coupon_template_id' => $coupon_template_id, 'uniacid' =>$uniacid));
    return $tatal;
}
function pdo_select_count($tablename, $params = array()){
    $condition = wl_implode($params, 'AND');
    $sql = 'SELECT COUNT(*) AS total FROM cm_'.$tablename . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '') . " LIMIT 1";
    $result = pdo()->fetch($sql, $condition['params']);

    if (empty($result)) {
        return 0;
    }

    return intval($result['total']);
}
function wl_implode($params, $glue = ',') {
    $result = array('fields' => ' 1 ', 'params' => array());
    $split = '';
    $suffix = '';
    if (in_array(strtolower($glue), array('and', 'or'))) {
        $suffix = '__';
    }
    if (!is_array($params)) {
        $result['fields'] = $params;
        return $result;
    }
    if (is_array($params)) {
        $result['fields'] = '';
        foreach ($params as $fields => $value) {
            if (is_array($value)) {
                $result['fields'] .= $split . "`$fields` IN ('".implode("','", $value)."')";
                $split = ' ' . $glue . ' ';
            } else {
                $result['fields'] .= $split . "`$fields` =  :{$suffix}$fields";
                $split = ' ' . $glue . ' ';
                $result['params'][":{$suffix}$fields"] = is_null($value) ? '' : $value;
            }
        }
    }
    return $result;
}

function coupon_quantity_issue_increase($coupon_template_id, $quantity) {
    $sql = 'UPDATE cm_tg_coupon_template SET `quantity_issue` = `quantity_issue` + :quantity WHERE id=:id';
    $params = array(
        ':id' => $coupon_template_id,
        ':quantity' => $quantity
    );
    pdo_query($sql, $params);

    return true;
}
//拥有的优惠券
if($op == 'coupon_list'){
//    $openid = $_GPC['openid'];
//
//    //已使用
//    $where1 = "SELECT * FROM cm_tg_coupon WHERE `openid` = :openid AND `use_time` != 0  ORDER BY `end_time` DESC ";
//    $params1 = array(
//        ':openid' => $openid
//    );
//    //已过期
//    $where2 = 'SELECT * FROM cm_tg_coupon WHERE `openid` = :openid AND `use_time` = 0 AND `end_time` < :time ORDER BY `end_time` DESC ';
//    $params2 = array(
//        ':openid' => $openid,
//        ':time' => time()
//    );
//    //未使用
//    $where3 = 'SELECT * FROM cm_tg_coupon WHERE `openid` = :openid AND `use_time` = :use_time AND `start_time` < :now1 AND `end_time` > :now2 ORDER BY `end_time` DESC ';
//    $params3 = array(
//        ':openid' => $openid,
//        ':use_time' => 0,
//        ':now1' => time(),
//        ':now2' => time()
//    );
//    $pagetitle = '优惠券列表';
//    $coupon1 = pdo_fetchall($where1, $params1);
//    if ($coupon1) {
//        foreach ($coupon1 as $key1 => $value1) {
//            $coupon1[$key1]['end_time'] = date('Y-m-d', $value1['end_time']);
//        }
//    }
//    $coupon2 = pdo_fetchall($where2, $params2);
//    if ($coupon2) {
//        foreach ($coupon2 as $key2 => $value2) {
//            $coupon2[$key2]['end_time'] = date('Y-m-d', $value2['end_time']);
//        }
//    }
//    $coupon3 = pdo_fetchall($where3, $params3);
//
//    if ($coupon3) {
//        foreach ($coupon3 as $key3 => $value3) {
//            $coupon3[$key3]['end_time'] = date('Y-m-d', $value3['end_time']);
//        }
//    }
//    die(json_encode(array('usedcoupon' =>$coupon1,'overduecoupon'=>$coupon2,'unusecoupon'=>$coupon3)));




    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $status = intval($_GPC['status']);
    $condition = '';
    if ($status == 1) {//已使用
        $condition .= " AND `use_time` != 0 ";
    } elseif ($status == 2) {//未使用
        $condition .= " AND `use_time` = 0 AND `start_time` < " . TIMESTAMP . " AND `end_time` > " . TIMESTAMP;
    } elseif ($status == 3) {//已过期
        $condition .= " AND `use_time` = 0 AND `end_time` < " . TIMESTAMP;
    }
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_coupon') . " WHERE `openid` = '{$openid}' {$condition} ORDER BY `end_time` DESC ");

    foreach ($list as $key => $value) {
        $list[$key]['end_time'] = date('Y-m-d', $value['end_time']);
    }
    die(json_encode(array('status' => 1, 'list' => $list)));
}


if ($op == 'order_detail') {
    $openid = $_GPC['openid'];
    $id = $_GPC['id'];
    $order = pdo_fetch('select * from ' . tablename('tg_order') . ' where openid=:openid and id=:id', array(':openid' => $openid, ':id' => $id));
    $order['createtime'] = date('Y-m-d H:i:s', $order['createtime']);
    if (!empty($order['ptime'])) {
        $order['ptime'] = date('Y-m-d H:i:s', $order['ptime']);
    }
    if (!empty($order['hexiaotime'])) {
        $order['hexiaotime'] = date('Y-m-d H:i:s', $order['hexiaotime']);
    } else {
        $order['hexiaotime'] = '';
    }
    $order['salename'] = '';
    $order['checkstore'] = '';
    if (!empty($order['veropenid'])) {
        $saler = pdo_fetch('select * from ' . tablename('tg_saler') . ' where openid=:openid', array(':openid' => $order['veropenid']));
        $order['salename'] = $saler['nickname'];
        $checkstore = pdo_fetch('select * from ' . tablename('tg_store') . ' where id=:id', array(':id' => $order['checkstore']));
        $order['checkstore'] = $checkstore['storename'];
    }
    if ($order['g_id'] == 0) {
        $collect = pdo_fetchall("select * from " . tablename('tg_collect') . " where orderno = '{$order['orderno']}' ");
        $order['collect'] = $collect;
        $order['collect_list'] = $collect;
    }
    die(json_encode($order));

}
if ($op == 'group_detail') {
    $tuan_id = $_GPC['tuan_id'];
    $order_list = pdo_fetchall('select openid,address,addname,createtime from ' . tablename('tg_order') . ' where tuan_id=:tuan_id and status in (1,2,3,4,5,6,7,8,10)', array(':tuan_id' => $tuan_id));
    $member_list = array();
    $i = 0;
    foreach ($order_list as $key => $value) {
        $i += 1;
        $member_list[$key]['xuhao'] = $i;
        $member_list[$key]['create_date'] = date('Y-m-d', $value['createtime']);
        $member_list[$key]['create_time'] = date('H:i:s', $value['createtime']);
        $member_list[$key]['openid'] = $value['openid'];
        if ($value['address'] == '虚拟') {
            $member_list[$key]['avatar'] = str_replace("..", "https://min.lexiangpingou.cn", $value['openid']);
            $member_list[$key]['nickname'] = $value['addname'];
        } else {
            $fans = pdo_fetch('select avatar,nickname from ' . tablename('tg_member') . " where openid = '{$value['openid']}'");
            if (!empty($fans)) {
                $avatar = $fans['avatar'];
                $nickname = $fans['nickname'];
            }
            $member_list[$key]['avatar'] = $avatar;
            $member_list[$key]['nickname'] = $nickname;
        }
    }
    $group = pdo_fetch('select * from ' . tablename('tg_group') . 'where groupnumber=:groupnumber', array(':groupnumber' => $tuan_id));
    if ($group['selltype'] != 7) {
        $group['nownum'] = $group['neednum'] - $group['lacknum'];
    }
    $goods = pdo_fetch('select gimg,unit,deliverytype from ' . tablename('tg_goods') . 'where id=:id', array(':id' => $group['goodsid']));
    $group['img'] = tomedia($goods['gimg']);
    $group['unit'] = $goods['unit'];
    $group['deliverytype'] = intval($goods['deliverytype']);

    $uniacid = $_GPC['uniacid'];
    $wechat = pdo_fetch('SELECT * FROM ' . tableName('account_wechats') . ' WHERE uniacid=:uniacid', array(':uniacid' => $uniacid));
    if ($group['lacknum'] > 0 && $group['groupstatus'] != 2) {
        $group['share_title'] = "【差" . $group['lacknum'] . "人】我参加了" . $wechat['name'] . $goods['gname'] . "的团";
    } else {
        $group['share_title'] = "【已成团】我参加了" . $wechat['name'] . $goods['gname'] . "的团";
    }

    $list = array();
    $list['member_list'] = $member_list;
    $list['group'] = $group;
    die(json_encode($list));
}
if ($op == 'order_list') {

    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $status = $_GPC['status'];
    $condition = ' and `uniacid` = :uniacid and openid=:openid';
    $params = array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid']);
    if ($_GPC['status'] >= 0) {
        $condition .= "  AND status = '{$status}'";
    }
    $orderby = 'order by id desc';
    $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
    $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
    $sql = "SELECT * FROM " . tablename('tg_order') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql, $params);

    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . "where 1 $condition ", $params);

    $data = array();
    $data['condition'] = $condition;
    $data['list'] = $list;
    $data['total'] = $total;

    die(json_encode($data));
    //die($sql);

}
if ($op == 'group_list') {

    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $status = $_GPC['status'];
    $condition = ' and `uniacid` = :uniacid and openid=:openid and is_tuan=1';
    $params = array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid']);
    if ($_GPC['status'] >= 0) {
        $condition .= "  AND tuan_id in (select groupnumber from cm_tg_group where `uniacid` = :uniacid and groupstatus = {$status})";
    }
    $condition .= ' group by tuan_id';
    $orderby = 'order by id desc';
    $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
    $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
    $sql = "SELECT * FROM " . tablename('tg_order') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql, $params);

    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . "where 1 $condition ", $params);
    foreach ($list as $key => $order) {
        $goods = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . "WHERE id = '{$order['g_id']}'");
        $thistuan = pdo_fetch("SELECT * FROM " . tablename('tg_group') . "WHERE groupnumber = '{$order['tuan_id']}' $content");
        $orders[$key]['groupnumber'] = $order['tuan_id'];
        $orders[$key]['groupnum'] = $goods['groupnum'];
        if (!empty($thistuan['price'])) {
            $orders[$key]['gprice'] = $thistuan['price'];
        } else {
            $orders[$key]['gprice'] = $goods['gprice'];
        }
        $orders[$key]['unit'] = $goods['unit'];
        $orders[$key]['gid'] = $goods['id'];
        $orders[$key]['gname'] = $goods['gname'];
        $orders[$key]['gimg'] = tomedia($goods['gimg']);
        $orders[$key]['itemnum'] = $thistuan['lacknum'];
        $orders[$key]['groupstatus'] = $thistuan['groupstatus'];
        $orders[$key]['starttime'] = date('Y-m-d H:i:s', $thistuan['starttime']);

        //app无团长时的判断
//        if ($_GPC['openid'] == 'oZeVNwplUg1xjxSye3_ThVaWMvmQ') {
        if ($thistuan['neednum'] == $thistuan['lacknum']) {
            $orders[$key]['incomplete_group'] = '1';
        } else {
            $orders[$key]['incomplete_group'] = '0';
        }
//        }

    }
    $data = array();
    $data['list'] = $orders;
    $data['total'] = $total;
    die(json_encode($data));
    //die($sql);

}
if ($op == 'cancal') {
    $orderno = $_GPC['orderno'];
    pdo_update('tg_order', array('status' => 9), array('status' => 0, 'orderno' => $orderno));
    $order = pdo_fetch("SELECT openid,couponid,is_usecard FROM cm_tg_order WHERE orderno='{$orderno}'");
    if($order['is_usecard'] == 1){
        pdo_update('tg_coupon',array('use_time'=>0),array('id'=>$order['couponid'],'openid'=>$order['openid']));
    }
    //$order=pdo_fetch('select status from '.tablename('tg_order').' where orderno=:orderno',array(':orderno'=>$orderno));
    $data = array('status' => 1);
    die(json_encode($data));
}
if ($op == 'freight') {
    $uniacid = $_GPC['uniacid'];
    $tid = $_GPC['tid'];
    $p = $_GPC['province'];
    $c = $_GPC['city'];
    $d = $_GPC['county'];
    $weight = $_GPC['weight'];
    $freight = 0;
    $province_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and template_id={$tid}");
    $city_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}'  and template_id={$tid}");
    $district_fee = pdo_fetch("select * from " . tablename("tg_delivery_price") . " WHERE  province = '{$p}' and  city = '{$c}' and district='{$d}' and template_id={$tid}");
    if (!empty($province_fee['first_fee'])) {
        $item = $first_fee;
        $free = sprintf("%.2f", $province_fee['free']);
        $first_fee = sprintf("%.2f", $province_fee['first_fee']);
        $first_weight = sprintf("%.2f", $province_fee['first_weight']);
        $second_fee = sprintf("%.2f", $province_fee['second_fee']);
        $second_weight = sprintf("%.2f", $province_fee['second_weight']);
    }
    if (!empty($city_fee['first_fee'])) {
        $item = 2;
        $free = sprintf("%.2f", $city_fee['free']);
        $first_fee = sprintf("%.2f", $city_fee['first_fee']);
        $first_weight = sprintf("%.2f", $city_fee['first_weight']);
        $second_fee = sprintf("%.2f", $city_fee['second_fee']);
        $second_weight = sprintf("%.2f", $city_fee['second_weight']);
    }
    if (!empty($district_fee['first_fee'])) {
        $item = 3;
        $free = sprintf("%.2f", $district_fee['free']);
        $first_fee = sprintf("%.2f", $district_fee['first_fee']);
        $first_weight = sprintf("%.2f", $district_fee['first_weight']);
        $second_fee = sprintf("%.2f", $district_fee['second_fee']);
        $second_weight = sprintf("%.2f", $district_fee['second_weight']);
    }
    if ($weight > $first_weight) {
        $freight = sprintf("%.2f", $first_fee + ($weight - $first_weight) / $second_weight * $second_fee);
    } else {
        $freight = $first_fee;
    }
    die(json_encode(array('status' => 1, 'freight' => $freight, 'province_fee' => $province_fee, 'city_fee' => $city_fee, 'district_fee' => $district_fee)));
}
if ($op == 'dispatch_list') {
    $id = $_GPC['g_id'];
    $goods = pdo_fetch('select uniacid,yunfei_id from ' . tablename('tg_goods') . 'where id=:id', array(':id' => $id));
    $yunfeiids = explode(",", $goods['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select id,name from" . tablename('tg_delivery_template') . "where id ='{$value}' and uniacid='{$goods['uniacid']}' and status=2");
        }
    }
    $status = 1;
    if (empty($dispatch_list)) {
        $status = 2;
    }
    $list = array();
    $list['status'] = $status;
    $list['data'] = $dispatch_list;
    die(json_encode($list));
    //die(json_encode($dispatch_list));
}
if ($op == 'check_member') {
    $openid = $_GPC['openid'];
    $uniacid = $_GPC['uniacid'];
//    $unionid = $_GPC['unionid'];
    $xcx = intval($_GPC['xcx']);
    //小程序用户信息解密
    $obj['appid'] = $_GPC['appid'];
    $obj['sessionKey']= $_GPC['session_key'];
    $obj['encryptedData'] = $_GPC['encryptedData'];
    $obj['iv'] = $_GPC['iv'];
    $info = xcx_decode($obj);//
    $info = json_decode($info,true);
    $unionid = $info['unionId'];
    $fans = pdo_fetch('SELECT id,avatar,nickname,xcx FROM ' . tablename('tg_member') . " WHERE from_user = :openid AND uniacid = :uniacid", array(':uniacid' => $uniacid, ':openid' => $openid));
    if($unionid){
        $s1 = pdo_fetch("SELECT * FROM " . tablename('tg_member') . "where unionid='{$unionid}' and uniacid='{$uniacid}'");
    }
    if(empty($s1) && empty($fans)){
        $parentid = $_GPC['mid'];
    }else{
        $parentid =99;
    }

    if (empty($fans)) {
        $data = array(
            'uniacid' => $uniacid,
            'nickname' => $_GPC['nickname'],
            'avatar' => $_GPC['avatar'],
            'openid' => $_GPC['openid'],
            'from_user' => $_GPC['openid'],
            'intertime' => TIMESTAMP,
            'xcx' => $xcx,
            'parentid' => $parentid,
            'unionid'=>$info['unionId']
        );
        pdo_insert('tg_member', $data);
    } else {
        $data = array(
            'nickname' => $_GPC['nickname'],
            'avatar' => $_GPC['avatar'],
            'xcx' => $xcx,
            'unionid'=>$info['unionId']
        );
        pdo_update('tg_member', $data, array('id' => $fans['id']));
    }

    $fans = pdo_fetch('SELECT * FROM ' . tablename('tg_member') . " WHERE from_user = :openid AND uniacid = :uniacid", array(':uniacid' => $uniacid, ':openid' => $openid));

    die(json_encode(array('status' => 1, 'member' => $fans,'info'=>$info)));

}

function xcx_decode($obj){
    $appid = $obj['appid'];
    $sessionKey = $obj['sessionKey'];
    $encryptedData=$obj['encryptedData'];
    $iv = $obj['iv'];
    $errCode = decryptData($appid,$sessionKey,$encryptedData, $iv, $data );
    if ($errCode == 0) {
        return $data;
    } else {
        return $errCode;
    }
}


function decryptData($appid, $sessionKey, $encryptedData, $iv, &$data ){
    if (strlen($sessionKey) != 24) {
        return -41001;
    }
    $aesKey=base64_decode($sessionKey);


    if (strlen($iv) != 24) {
        return -41002;
    }
    $aesIV=base64_decode($iv);

    $aesCipher=base64_decode($encryptedData);

    $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

    $dataObj=json_decode( $result );
    if( $dataObj  == NULL )
    {
        return -41003;
    }
    if( $dataObj->watermark->appid != $appid )
    {
        return -41003;
    }
    $data = $result;
    return 0;
}



if ($op == 'goods_guige') {
    $id = $_GPC['id'];
    //规格及规格项
    $allspecs = pdo_fetchall("select title,id from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $id));
    foreach ($allspecs as &$s) {
        $s['items'] = pdo_fetchall("select title,thumb from " . tablename('tg_spec_item') . " where  `show`=1 and specid=:specid order by displayorder asc", array(":specid" => $s['id']));
        foreach ($s['items'] as &$t) {
            $t['thumb'] = tomedia($t['thumb']);
        }
        $s['count'] = count($s['items']);
    }

    //unset($s);
    $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,stock,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $id));
    $specs = array();
    $specs['allspecs'] = $allspecs;
    $specs['options'] = $options;
    die(json_encode($specs));
}
//自定义排序
function my_sort($a, $b)
{
//    if ($a['count'] == $b['count'])
//        return 0;
    return ($a['count'] < $b['count']) ? -1 : 1;
}

//app专用
if ($op == 'goods_guiges') {
    $id = $_GPC['id'];
    //规格及规格项
    $allspecs = pdo_fetchall("SELECT title,id FROM " . tablename('tg_spec') . " WHERE goodsid=:id ORDER BY displayorder ASC", array(':id' => $id));
//    $all = array();
    foreach ($allspecs as &$s) {
        $s['items'] = pdo_fetchall("SELECT title,thumb FROM " . tablename('tg_spec_item') . " WHERE  `show`=1 AND specid=:specid ORDER BY displayorder ASC", array(":specid" => $s['id']));
        foreach ($s['items'] as &$t) {
            $t['thumb'] = tomedia($t['thumb']);
        }
        $s['count'] = count($s['items']);
//        $s[] = count($s['items']);
        unset($s);
//        $all[] = count($s['items']);
    }

    usort($allspecs, "my_sort");

//    $cs = sort($all);
//    $cs = sort($allspecs);
//    die(json_encode(array('c'=>$c,'all'=>$all,'cs'=>$cs,'al'=>$allspecs)));
    //unset($s);
    $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,stock,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
    $specs = array();
    $specs['allspecs'] = $allspecs;
    $specs['options'] = $options;
    die(json_encode($specs));
}
if ($op == 'storelist') {
    global $_GPC;
    $uniacid = $_GPC['uniacid'];
    $g_id = $_GPC['g_id'];

    $goods = pdo_fetch("select hexiao_id from" . tablename('tg_goods') . "where id = '{$g_id}' and uniacid='{$uniacid}'");

    if (!empty($goods['hexiao_id'])) {
        $store_ids = explode(',', substr($goods['hexiao_id'], 0, strlen($goods['hexiao_id']) - 1));
        $storelist = array();
        foreach ($store_ids as $key => $value) {
            $store = pdo_fetch('select * from ' . tablename('tg_store') . ' where id=:id', array(':id' => $value));
            $storelist[$key] = $store;
        }
        die(json_encode($storelist));
    } else {
        die(json_encode(array('status' => 0)));
    }

}
function getClientIP()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR");
    else $ip = "Unknow";
    return $ip;
}


//团长免单
if ($op == 'check_newtuan') {
    $openid = $_GPC['openid'];
    $uniacid = $_GPC['uniacid'];
    $total_price = 0;
    $orderno = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $adress_fee = pdo_fetch("select * from " . tablename('tg_member') . " where openid = '{$openid}' and uniacid={$uniacid}");
    $gid = intval($_GPC['gid']);
    if ($gid) {
        $goods = pdo_get('tg_goods', array('id' => $gid));
        $data = array(
            'uniacid' => $uniacid,
            'gnum' => 1,
            'openid' => $openid,
            'ptime' => '',
            'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
            'price' => 0,
            'status' => 0,
            'addressid' => 0,
            'addname' => $adress_fee['nickname'],
            'mobile' => '虚拟',
            'address' => '',
            'pay_price' => 0,
            'goodsprice' => $goods['oprice'],
            'goodsname' => $goods['gname'],
            'freight' => 0,
            'first_fee' => 0,
            'addresstype' => 0,//1公司2家庭
            'dispatchtype' => 0,//配送方式
            'comadd' => 0,//
            'g_id' => $gid,
            'tuan_id' => 0,
            'is_tuan' => 1,
            'tuan_first' => 1,
            'starttime' => TIMESTAMP,
            'senddate' => 0,
            'sendtime' => 0,
            'remark' => 0,
            'comtype' => 1,
            'commission' => $goods['commission'],
            'com_type' => $goods['com_type'],
            'commissiontype' => $goods['commissiontype'],
            'endtime' => $goods['endtime'],
            'selltype' => $goods['selltype'],
            'credits' => $goods['credits'],
            'optionname' => $goods['optionname'],
            'optionid' => 0,
            'discount_fee' => 0,
            'createtime' => TIMESTAMP,
            'bdeltime' => 0,
            'issued' => 0,
            'print_id' => 0,
            'couponsids' => $goods['couponsids'],
            'merchantid' => $goods['merchantid'],
            'zititime' => $goods['zititime'],
            'supply_goodsid' => $goods['supply_goodsid'],
            'idcard' => ''
        );
        pdo_insert('tg_order', $data);
        $orderid = pdo_insertid();

        $groupnumber = $orderid;
        $starttime = time();
        $endtime = $starttime + $goods['endtime'] * 3600;
        $selltype = $goods['selltype'];
        $groupnum = $goods['groupnum'];
        if ($selltype == 7) {
            $goods['on_success'] = 1;
            $groupnum = 1000000000;
        }
        $data2 = array(
            'uniacid' => $uniacid,
            'groupnumber' => $groupnumber,
            'groupstatus' => 3,//订单状态,1组团失败，2组团成功，3,组团中
            'goodsid' => $goods['id'],
            'goodsname' => $goods['gname'],
            'neednum' => $groupnum,
            'lacknum' => $groupnum,
            'starttime' => $starttime,
            'selltype' => $selltype,
            'endtime' => $endtime,
            'on_success' => $goods['on_success'],
            'price' => 0,
            'group_level' => $goods['group_level'],
            'no_num_status' => 1,
            'no_num_success' => strtotime("-" . $goods['no_num_success'] . " minute", $endtime),
            'supply_goodsid' => $goods['supply_goodsid'],
            'merchantid' => $goods['merchantid']
        );
        pdo_insert('tg_group', $data2);
        pdo_update('tg_order', array('tuan_id' => $orderid), array('id' => $orderid));

        die(json_encode(array('status' => 1, 'data' => $data, 'tuan_id' => $groupnumber)));
    } else {
        die(json_encode(array('status' => 0, 'message' => '传入参数错误！')));
    }

}

//免费通道
if ($op == 'check_newpayresult') {
    $orderno = $_GPC['orderno'];
    $transid = $_GPC['transid'];
    $fee = $_GPC['fee'];
    $order_out = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . ' WHERE orderno=:orderno', array(':orderno' => $orderno));
    $uniacid = $order_out['uniacid'];
    $openid = $order_out['openid'];

    if (!empty($transid)) {
        //插入paylog
        $paylog_data = array(
            'tid' => $orderno,
            'uniontid' => $orderno,
            'tag' => $transid,
            'type' => 'wechat',
            'uniacid' => $order_out['uniacid'],
            'openid' => $order_out['openid']
        );
        pdo_insert('core_paylog', $paylog_data);
        /*按订单量使用 减少订单量*/
//        $acct = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE  uniacid =:uniacid", array(':uniacid' => $order_out['uniacid']));
//        if ($acct['vip'] == 0 && $acct['ordernum'] > 0) {
//            pdo_update('account_wechats', array('ordernum' => $acct['ordernum'] - 1), array('uniacid' => $order_out['uniacid']));
//        }
        /*按订单量使用 减少订单量*/
        pdo_update('tg_order', array('status' => 3, 'pay_type' => 2, 'transid' => $transid, 'ptime' => TIMESTAMP, 'price' => $fee), array('orderno' => $orderno));
        //组团逻辑
        if ($order_out['is_tuan'] == 1) {
            $group = pdo_fetch('SELECT * FROM ' . tablename('tg_group') . ' WHERE groupnumber=:tuan_id', array(':tuan_id' => $order_out['tuan_id']));
            $nowtuannum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('tg_order') . ' WHERE tuan_id=:tuan_id AND status IN(1,2,3,8)', array(':tuan_id' => $order_out['tuan_id']));
            if ($group['neednum'] > $nowtuannum) {
                pdo_update('tg_group', array('lacknum' => $group['neednum'] - $nowtuannum, 'nownum' => $nowtuannum), array('groupnumber' => $order_out['tuan_id']));
            }
            if ($group['neednum'] == $nowtuannum) {
                pdo_update('tg_order', array('status' => 8), array('tuan_id' => $order_out['tuan_id'], 'status' => 1));
                pdo_update('tg_group', array('groupstatus' => 2, 'lacknum' => $group['neednum'] - $nowtuannum, 'nownum' => $nowtuannum), array('groupnumber' => $order_out['tuan_id']));
            }
            if ($group['neednum'] < $nowtuannum) {
                pdo_update('tg_order', array('status' => 10), array('orderno' => $orderno));
                pdo_update('tg_group', array('groupstatus' => 2), array('groupnumber' => $order_out['tuan_id']));

            }
            $tuan_id = $order_out['tuan_id'];

        } else {
            $tuan_id = '';
            //单买逻辑
            pdo_update('tg_order', array('status' => 8), array('orderno' => $order_out['orderno']));
        }
        if ($order_out['g_id'] > 0) {
            $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' where id = :id', array(':id' => $order_out['g_id']));
            if ($goodsInfo['gnum'] >= $order_out['gnum']) {
                pdo_update('tg_goods', array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
            } elseif (!empty($goodsInfo['gnum'])) {
                pdo_update('tg_goods', array('gnum' => $goodsInfo['gnum'] - $order_out['gnum'], 'salenum' => $goodsInfo['salenum'] + $order_out['gnum']), array('id' => $order_out['g_id']));
            }
        }

        if ($order_out['tuan_first'] == 1) {
            $dat['uniacid'] = $uniacid;
            $dat['openid'] = $openid;
            $dat['tuan_id'] = $tuan_id;
            $dat['commissiontype'] = $order_out['commissiontype'];
            $dat['commission'] = $order_out['commission'];
            $dat['gid'] = $order_out['g_id'];
            $dat['createtime'] = TIMESTAMP;
            pdo_insert('tg_commander', $dat);
        }

        /*增加历史购买数量*/
//        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $order_out['uniacid'], ':openid' => $order_out['openid'], ':g_id' => $order_out['g_id']));
//        if (empty($old_data)) {
//            $logdata = array(
//                'g_id' => $order_out['g_id'],
//                'openid' => $order_out['openid'],
//                'num' => $order_out['gnum'],
//                'uniacid' => $order_out['uniacid']
//            );
//            pdo_insert('tg_goods_openid', $logdata);
//        } else {
//            pdo_update('tg_goods_openid', array('num' => $old_data['num'] + $order_out['gnum']), array('id' => $old_data['id']));
//
//        }
        /*增加历史购买数量*/
        die(json_encode(array('status' => 1, 'tuan_id' => $tuan_id, 'order_id' => $order_out['id'], 'orderno' => $order_out['orderno'])));
    }
    die(json_encode(array('status' => 0)));
}


//订单数据自动统计
if ($op == 'run_data_order') {
    global $_GPC;
    $day = intval($_GPC['day']);
    if($_GPC['uniacid']){
        $condition = ' and `uniacid`='.$_GPC['uniacid'];
    }
    $nowdate = date('Ymd', TIMESTAMP);
    $now = strtotime($nowdate . " +1 day")- 60 * 60 * 24 * $day;
    $before = strtotime($nowdate) - 60 * 60 * 24 * $day;
    $hour=time()-3600;
    $sql = "SELECT COUNT(id) as num,uniacid from cm_tg_puv_record WHERE `createtime` >= {$hour} AND `createtime` <= {$now} ".$condition." group by uniacid ";
    $data_list = pdo_fetchall($sql);

    foreach ($data_list as $item) {
        $base_data = pdo_fetch('select id from ' . tablename('tg_data_base') . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} ", array(':uniacid' => $item['uniacid']));
        $sql = "SELECT SUM(pay_price) as total_price,uniacid,COUNT(id) as order_num,SUM(price)/COUNT(id) as customer_price  from cm_tg_order WHERE uniacid = :uniacid and `ptime` >= {$before} AND `ptime` <= {$now} and mobile <> '虚拟' ";
        $list = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));

        //查询今日总订单量
        $sql = "SELECT COUNT(id) as order_num  from cm_tg_order WHERE `createtime` >= {$before} AND `createtime` <= {$now} and uniacid = :uniacid and mobile <> '虚拟' ";
        $order_num = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        //查询单买数量
        //查询今日总订单量
        $sql = "SELECT COUNT(id) as order_num  from cm_tg_order WHERE `createtime` >= {$before} AND `createtime` <= {$now} and uniacid = :uniacid and mobile <> '虚拟' and is_tuan=0";
        $dan_total = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        //查询今日开团总数
        $sql = "SELECT COUNT(id) as order_num  from cm_tg_group WHERE `starttime` >= '{$before}' AND `starttime` <= '{$now}' and uniacid = :uniacid ";
        $group_total = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        $sql = "SELECT COUNT(id) as order_num  from cm_tg_group WHERE `successtime` >= {$before} AND `successtime` <= {$now} and uniacid = :uniacid and groupstatus = 2 ";
        $group_success = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        $sql = "SELECT COUNT(id) as order_num  from cm_tg_group WHERE `endtime` >= '{$before}' AND `endtime` <= '{$now}' and uniacid = :uniacid and groupstatus = 1 ";
        $group_fail = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        //查询签收金额
        $sql = "SELECT SUM(pay_price) as sign_price  from cm_tg_order WHERE `gettime` >= '{$before}' AND `gettime` <= '{$now}' and uniacid = :uniacid and status = 3 and mobile <> '虚拟' ";
        $sign_price = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        //查询退款数据
        $sql = "SELECT SUM(refundfee) as total_price,COUNT(id) as num from cm_tg_refund_record WHERE `createtime` >= {$before} AND `createtime` <= {$now} and uniacid = :uniacid and status = 1 ";
        $refund_price = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));


        $uniacid = $item['uniacid'];
        //下单人
        $order_buy = pdo_fetchall("select distinct (openid) from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `createtime` >= {$before} AND `createtime` <= {$now} ", array(':uniacid' => $uniacid));
        //下单金额
        $order_buy_price = pdo_fetch("select sum(pay_price) as total_price from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `createtime` >= {$before} AND `createtime` <= {$now} ", array(':uniacid' => $uniacid));
        //付款人数
        $order_pay = pdo_fetchall("select distinct (openid) from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `ptime` >= {$before} AND `ptime` <= {$now} group by openid", array(':uniacid' => $uniacid));
        //成交人数
        $order_success = pdo_fetchall("select distinct (openid) from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and status in (2,3,8) and `ptime` >= {$before} AND `ptime` <= {$now} ", array(':uniacid' => $uniacid));

        //成交订单
        $order_success_num = pdo_fetch("select count(id) as num,sum(pay_price) as success_price from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and status in (2,3,8) and `ptime` >= {$before} AND `ptime` <= {$now} ", array(':uniacid' => $uniacid));
        //查询pv
        // $sql="SELECT COUNT(id) as num from cm_tg_puv_record WHERE  date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$now}),'%Y-%m-%d') and uniacid=:uniacid ";
        // $pv=pdo_fetch($sql,array(':uniacid'=>$item['uniacid']));
        $sql = "SELECT count(id) as pv_num from cm_tg_puv_record WHERE `createtime` >= {$before} AND `createtime` <= {$now} and uniacid = :uniacid  ";
        $pv = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        //查询uv
        $sql = "SELECT count(DISTINCT(openid)) as uv_num from cm_tg_puv_record WHERE `createtime` >= {$before} AND `createtime` <= {$now} and uniacid = :uniacid  ";
        $uv = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));


        //查询新增粉丝
        $sql = "SELECT COUNT(*) as num from cm_mc_mapping_fans WHERE `followtime` >= {$before} AND `followtime` <= {$now} and uniacid = :uniacid  ";
        $fans = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
        $data = array(
            'uniacid' => $item['uniacid'],
            'addtime' => TIMESTAMP - 60 * 60 * 24 * $day,
            'order_total' => $order_num['order_num'],
            'order_pay' => $list['order_num'],
            'pay_price' => $list['total_price'],
            'refund_price' => $refund_price['total_price'],
            'order_refund' => $refund_price['num'],
            'pv' => $pv['pv_num'],
            'uv' => $uv['uv_num'],
            'add_fans' => $fans['num'],
            'dan_total' => $dan_total['order_num'],
            'group_total' => $group_total['order_num'],
            'group_success' => $group_success['order_num'],
            'group_fail' => $group_fail['order_num'],
            'success_price' => $order_success_num['success_price'],
            'sign_price' => $sign_price['sign_price'],
            'customer_price' => $list['customer_price'],
            'order_buy_openid'=>count($order_buy),
            'order_buy_price'=>round($order_buy_price['total_price'], 2),
            'order_pay_openid'=>count($order_pay),
            'order_success_openid'=>count($order_success),
            'order_success_num'=>$order_success_num['num'],
        );
        if (empty($base_data)) {
            pdo_insert('tg_data_base', $data);
        } else {
            pdo_update('tg_data_base', $data, array('id' => $base_data['id']));
        }
    }
    $list = array();
    $list['errCode'] = 1;
    $list['now'] = $before;
    $list['data'] = $data_list;
    die(json_encode($list));
}
//用户时间曲线
if ($op == 'run_data_puv') {
    global $_GPC;
    $day = intval($_GPC['day']);
    if ($_GPC['uniacid']) {
        $condition = ' and `uniacid`=' . $_GPC['uniacid'];
    }
    $nowdate = date('Ymd', TIMESTAMP);
    $now = strtotime($nowdate . " +1 day") - 60 * 60 * 24 * $day;
    $before = strtotime($nowdate) - 60 * 60 * 24 * $day;
    $hour=date("H",time());
    //例如现在11:40 $nowhour指10:00
    if($hour<1){
        $nowhour=strtotime($nowdate)+60 * 60 * ($hour);
    }else{
        $nowhour=strtotime($nowdate)+60 * 60 * ($hour-1);
    }
    $sql = "SELECT uniacid from cm_tg_puv_record WHERE `createtime` >= {$nowhour} AND `createtime` <= {$now} " . $condition . " group by uniacid ";
    $data_list = pdo_fetchall($sql);
    foreach ($data_list as $item) {
        //查询今日是否已有数据
        //查询uv
        $uv_base_data = pdo_fetch('select id from ' . tablename('tg_data_uv') . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} ", array(':uniacid' => $item['uniacid']));
        $uvsql = "SELECT  count(DISTINCT(openid)) as num,HOUR(FROM_UNIXTIME(createtime)) as dayhour  from cm_tg_puv_record WHERE uniacid = :uniacid and  `createtime` >= {$nowhour} AND `createtime` <= {$now} group by HOUR(FROM_UNIXTIME(createtime))";
        $uvlist = pdo_fetchall($uvsql, array(':uniacid' => $item['uniacid']));
        $uvdata = array(
            'uniacid' => $item['uniacid'],
            'addtime' => TIMESTAMP - 60 * 60 * 24 * $day,

        );
        foreach ($uvlist as $key => $value) {
            $dayhour = 'hour_' . $value['dayhour'];
            $uvdata[$dayhour] = $value['num'];

        }
        if (empty($uv_base_data)) {
            pdo_insert('tg_data_uv', $uvdata);
        } else {
            pdo_update('tg_data_uv', $uvdata, array('id' => $uv_base_data['id']));
        }
        //查询pv
        $pv_base_data = pdo_fetch('select id from ' . tablename('tg_data_pv') . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} ", array(':uniacid' => $item['uniacid']));
        $pvsql = "SELECT count(id) as num,HOUR(FROM_UNIXTIME(createtime)) as dayhour  from cm_tg_puv_record WHERE uniacid = :uniacid and  `createtime` >= {$nowhour} AND `createtime` <= {$now} group by HOUR(FROM_UNIXTIME(createtime))";
        $pvlist = pdo_fetchall($pvsql, array(':uniacid' => $item['uniacid']));
        $pvdata = array(
            'uniacid' => $item['uniacid'],
            'addtime' => TIMESTAMP - 60 * 60 * 24 * $day,

        );
        foreach ($pvlist as $key => $value) {
            $dayhour = 'hour_' . $value['dayhour'];
            $pvdata[$dayhour] = $value['num'];

        }

        if (empty($pv_base_data)) {
            pdo_insert('tg_data_pv', $pvdata);
        }else{
            pdo_update('tg_data_pv', $pvdata, array('id' => $pv_base_data['id']));
        }


    }
    $list = array();
    $list['errCode'] = 1;
    $list['now'] = $before;
    die(json_encode($list));
}
//自提点报表统计

if ($op == 'run_since_data') {
    global $_GPC;
    $day = intval($_GPC['day']);
    if($_GPC['uniacid']){
        $condition = ' and `uniacid`='.$_GPC['uniacid'];
    }

    $nowdate = date('Ymd', TIMESTAMP);
    $now = strtotime($nowdate . " +1 day") - 60 * 60 * 24 * $day;
    $before = strtotime($nowdate) - 60 * 60 * 24 * $day;
    $hour=time()-3600;
    //自提订单dispatchtype=3
    $sql = "SELECT uniacid from cm_tg_order WHERE dispatchtype=3 and `createtime` >= {$hour} AND `createtime` <= {$now}".$condition." group by uniacid ";
    $data_list = pdo_fetchall($sql);

    foreach ($data_list as $item) {
//        获取自提点
        $since_store = pdo_fetchall("select id from cm_tg_store WHERE `uniacid` = :uniacid", array(':uniacid' => $item['uniacid']));
        //获取各自提点总数据

        foreach ($since_store as $key=>$value){
            $base_data = pdo_fetch('select id from ' . tablename('tg_since_data') . " where `uniacid` = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} AND `store_id`={$value['id']}", array(':uniacid' => $item['uniacid']));
            $sql = "SELECT SUM(pay_price) as total_price,COUNT(id) as order_num from cm_tg_order WHERE `uniacid` = :uniacid and `ptime` >= {$before} AND `ptime` <= {$now} and mobile <> '虚拟' AND dispatchtype=3 AND comadd='{$value['id']}'";
            $list = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
            //查询今日总订单量
            $sql = "SELECT COUNT(id) as order_num  from cm_tg_order WHERE `createtime` >= {$before} AND `createtime` <= {$now} and `uniacid` = :uniacid and mobile <> '虚拟' AND dispatchtype=3 AND comadd='{$value['id']}'";
            $order_num = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
            //查询签收金额
            $sql = "SELECT SUM(pay_price) as total_price  from cm_tg_order WHERE `gettime` >= '{$before}' AND `gettime` <= '{$now}' and `uniacid` = :uniacid and status = 3 and mobile <> '虚拟' AND dispatchtype=3 AND comadd='{$value['id']}'";
            $success_price = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
            //查询退款数据
            $sql = "SELECT SUM(refundfee) as total_price,COUNT(id) as num from cm_tg_refund_record WHERE `createtime` >= {$before} AND `createtime` <= {$now} and `uniacid` = :uniacid and status = 1 ";
            $refund_price = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
            $data = array(
                'uniacid' => $item['uniacid'],
                'addtime' => TIMESTAMP - 60 * 60 * 24 * $day,
                'order_total' => $order_num['order_num'],
                'order_pay' => $list['order_num'],
                'order_refund' => $refund_price['num'],
                'pay_price' => $list['total_price'],
                'refund_price' => $refund_price['total_price'],
                'success_price' => $success_price['total_price'],
                'store_id' => $value['id']
            );
            if (empty($base_data)) {
                pdo_insert('tg_since_data', $data);
            } else {
                pdo_update('tg_since_data', $data, array('id' => $base_data['id']));
            }

//            //商品售卖统计
            //统计order gid>0
//            SELECT sum(a.pay_price),a.g_id+ifnull(b.sid,0),sum(ifnull(a.gnum,0)+ifnull(b.num,0)) from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno where  a.uniacid =53 and a.`createtime` >= 1512057600 AND a.`createtime` <= 1513993035  and a.mobile <> '虚拟' AND a.dispatchtype=3 AND a.comadd=23  group by (a.g_id+ifnull(b.sid,0))

            $sql = "SELECT a.g_id+ifnull(b.sid,0) as g_id,sum(ifnull(a.gnum,0)+ifnull(b.num,0)) as order_num from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno WHERE a.`uniacid` = :uniacid and a.`createtime` >= {$before} AND a.`createtime` <= {$now} and a.mobile <> '虚拟' AND a.dispatchtype=3 AND a.comadd='{$value['id']}' GROUP BY (a.g_id+ifnull(b.sid,0))";
            $c_list = pdo_fetchall($sql, array(':uniacid' => $item['uniacid']));
            foreach ($c_list as $kk=>$vv){
                $base_data = pdo_fetch('select id from ' . tablename('tg_since_data_info') . " where `uniacid` = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} AND `store_id`={$value['id']} and `g_id`={$vv['g_id']}", array(':uniacid' => $item['uniacid']));
                //支付
                $sql = "SELECT sum(ifnull(b.oprice,a.pay_price)) as total_price,sum(ifnull(a.gnum,0)+ifnull(b.num,0)) as order_num from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno WHERE a.uniacid = :uniacid and a.`ptime` >= {$before} AND a.`ptime` <= {$now} and a.mobile <> '虚拟' AND a.dispatchtype=3 AND a.comadd='{$value['id']}' and (a.g_id+ifnull(b.sid,0))={$vv['g_id']}";
                $p_list = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
                //查询签收金额
                $sql = "SELECT sum(ifnull(b.oprice,a.pay_price)) as total_price,sum(ifnull(a.gnum,0)+ifnull(b.num,0)) as order_num from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno WHERE a.uniacid = :uniacid and a.status = 3 and a.`gettime` >= '{$before}' AND a.`gettime` <= '{$now}' and a.mobile <> '虚拟' AND a.dispatchtype=3 AND a.comadd='{$value['id']}' and (a.g_id+ifnull(b.sid,0))={$vv['g_id']}";
                $g_list = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
                $data = array(
                    'uniacid' => $item['uniacid'],
                    'addtime' => TIMESTAMP - 60 * 60 * 24 * $day,
                    'order_total' => $vv['order_num'],

                    'order_pay' => $p_list['order_num'],
//                    'order_refund' => $refund_price['num'],
                    'pay_price' => $p_list['total_price'],
                    'success_num' =>$g_list['order_num'],
                    'success_price' => $g_list['total_price'],
                    'store_id' => $value['id'],
                    'g_id'=>$vv['g_id']
                );
                if (empty($base_data)) {
                    pdo_insert('tg_since_data_info', $data);
                } else {

                    pdo_update('tg_since_data_info', $data, array('id' => $base_data['id']));
                }
            }
        }




    }
    $list = array();
    $list['now'] = $before;
    $list['data'] = $data_list;
    die(json_encode(['errCode'=>1,'list'=>$list]));
}


if ($op == 'run_new_since_data') {
    //自提商品日报表
    global $_GPC;
    $day = intval($_GPC['day']);
    if($_GPC['uniacid']){
        $condition = ' and `uniacid`='.$_GPC['uniacid'];
    }

    $nowdate = date('Ymd', TIMESTAMP);
    $now = strtotime($nowdate . " +1 day") - 60 * 60 * 24 * $day;
    $before = strtotime($nowdate) - 60 * 60 * 24 * $day;
    //自提订单dispatchtype=3
    $hour=time()-3600;
//    $hour=$before;
    $sql = "SELECT uniacid from cm_tg_order WHERE dispatchtype=3 and `createtime` >= {$hour} AND `createtime` <= {$now}".$condition." group by uniacid ";
    $data_list = pdo_fetchall($sql);

    foreach ($data_list as $item) {
//        获取自提点
        $since_store = pdo_fetchall("select id,storename from cm_tg_store WHERE `uniacid` = :uniacid", array(':uniacid' => $item['uniacid']));
        //获取各自提点总数据

        foreach ($since_store as $key=>$value){
            $sql = "SELECT a.comadd,c.gname,a.g_id+ifnull(b.sid,0) as g_id,sum(ifnull(a.gnum,0)+ifnull(b.num,0)) as order_num,a.optionid+ifnull(b.optionid,0) as optionid,CASE (a.optionid+ifnull(b.optionid,0)) WHEN 0 THEN c.unit ELSE CONCAT(a.optionname,ifnull(b.item,'')) END as optionname,a.price,b.oprice from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno LEFT JOIN cm_tg_goods c ON a.g_id+ifnull(b.sid,0)=c.id WHERE a.`uniacid` = :uniacid and a.`createtime` >= {$before} AND a.`createtime` <= {$now} and a.mobile <> '虚拟' AND a.dispatchtype=3 AND a.comadd='{$value['id']}' GROUP BY a.g_id+ifnull(b.sid,0),a.optionid+ifnull(b.optionid,0)";
            $c_list = pdo_fetchall($sql, array(':uniacid' => $item['uniacid']));
            if(!empty($c_list)){

                foreach ($c_list as $kk=>$vv){

                    $base_data = pdo_fetch('select id from ' . tablename('tg_new_since_data_info') . " where `uniacid` = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} AND `store_id`={$value['id']} and `g_id`={$vv['g_id']}", array(':uniacid' => $item['uniacid']));
                    //支付

                    $sql = "SELECT sum(ifnull(b.oprice*b.num,a.pay_price)) as total_price,count(a.id) as order_num from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno WHERE a.uniacid = :uniacid and a.`ptime` >= {$before} AND a.`ptime` <= {$now} and a.mobile <> '虚拟' AND a.dispatchtype=3 AND a.comadd='{$value['id']}' and (a.g_id+ifnull(b.sid,0))={$vv['g_id']}";
                    $p_list = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
                    //成团数量
                    $sql = "SELECT COUNT(*) as success_tuan FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.uniacid = :uniacid and a.`ptime` >= {$before} AND a.`ptime` <= {$now} and a.mobile <> '虚拟' AND a.dispatchtype=3 AND a.comadd='{$value['id']}' and a.g_id={$vv['g_id']} and b.groupstatus=2";
                    $success_tuan = pdo_fetch($sql, array(':uniacid' => $item['uniacid']));
                    if(!$vv['oprice']){
                        $oprice = pdo_fetch("SELECT * FROM cm_tg_goods WHERE uniacid={$item['uniacid']} and id={$vv['g_id']}");
                        $oprice = $oprice['oprice'];
                    }else{
                        $oprice = $vv['oprice'];
                    }
                    $data = array(
                        'uniacid' => $item['uniacid'],
                        'addtime' => TIMESTAMP - 60 * 60 * 24 * $day,
                        'g_id' => $vv['g_id'],
                        'g_name' => $vv['gname'],
                        'g_spec' => $vv['optionname'],
                        'g_price'=>$oprice,
                        'success_tuan'=>$success_tuan['success_tuan'],
                        'pay_num' =>$p_list['order_num'],
                        'pay_price' => $p_list['total_price'],
                        'store_id' => $value['id'],
                        'store_name'=>$value['storename'],
                        'option_id'=>$vv['optionid']
                    );
                    if (empty($base_data)) {
                        pdo_insert('tg_new_since_data_info', $data);
                    } else {
                        pdo_update('tg_new_since_data_info', $data, array('id' => $base_data['id']));
                    }
                }
            }

        }




    }
    $list = array();
    $list['now'] = $before;
    $list['data'] = $data_list;
    die(json_encode(['errCode'=>1,'list'=>$list]));
}



if ($op == 'run_annual_data') {
//总订单量 支付过的 月峰值 日峰值
    $uniacid = $_GPC['uniacid'];
    $year = $_GPC['year'];
    $nowdate = date('Ymd', TIMESTAMP);
    $nowday = strtotime($nowdate);
    $now = strtotime($nowdate . " +1 day");
    $before = strtotime($nowdate);
    if($_GPC['uniacid']){
        $ra = $_GPC['uniacid'];
        $range = $_GPC['uniacid'];
    }
    $sql = "SELECT uniacid from cm_tg_order ".$condition." group by uniacid ";
    $data_list = pdo_fetchall($sql);
    $max = $_GPC['max'];
    if($max==1){
        $ra = 0;
        $range = 300;
    }
    if($max==2){
        $ra = 301;
        $range = 600;
    }
    if($max==3){
        $ra = 601;
        $range = 900;
    }
    if($max==4){
        $ra = 901;
        $range = 1200;
    }
    if($max==5){
        $ra = 1201;
        $range = 1500;
    }
    if($max==6){
        $ra = 1501;
        $range = 1800;
    }
    if($max==7){
        $ra = 1801;
        $range = 2100;
    }
    if($max==8){
        $ra = 2101;
        $range = 2400;
    }
    for ($x=$ra; $x<=$range; $x++) {
        $uniacid = $x;
        $big_tuan = pdo_fetch('select max(neednum-lacknum) as num,goodsname from ' . tablename('tg_group') . " where uniacid = :uniacid and FROM_UNIXTIME(starttime,'%Y')='{$year}' and groupstatus=2", array(':uniacid' => $uniacid));
        $annual_data = pdo_fetch('select * from ' . tablename('tg_data_base') . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} ", array(':uniacid' => $uniacid));
        $max_data = pdo_fetch('SELECT max(order_total) AS order_total,max(order_pay) AS order_pay,max(order_refund) AS order_refund,max(group_total) AS group_total,max(group_success) AS group_success,max(group_fail) AS group_fail,max(pay_price) AS pay_price,max(refund_price) AS refund_price,max(success_price) AS success_price,max(customer_price) AS customer_price,max(add_fans) AS add_fans,max(pv) AS pv,max(uv) AS uv FROM ' . tablename('tg_data_base') . " WHERE uniacid = :uniacid ", array(':uniacid' => $uniacid));
        //总支付金额 支付过的 月峰值 日峰值
        $total_pay = pdo_fetch("SELECT sum(pay_price) as pay_price,count(id) as num,FROM_UNIXTIME(ptime,'%Y')  from cm_tg_order where uniacid=:uniacid and  FROM_UNIXTIME(ptime,'%Y')='{$year}' and mobile <> '虚拟' ", array(':uniacid' => $uniacid));
        $mon_pay = pdo_fetchall("SELECT sum(pay_price) as pay_price,count(id) as num,FROM_UNIXTIME(ptime,'%Y-%m') as time  from cm_tg_order where uniacid=:uniacid and  FROM_UNIXTIME(ptime,'%Y')='{$year}' and mobile <> '虚拟'  group by FROM_UNIXTIME(ptime,'%Y-%m')  order by sum(pay_price) desc", array(':uniacid' => $uniacid));
        $day_pay = pdo_fetch("SELECT sum(pay_price) as pay_price,count(id) as num,FROM_UNIXTIME(ptime,'%Y-%m-%d')  from cm_tg_order where uniacid=:uniacid and  FROM_UNIXTIME(ptime,'%Y')='{$year}' and mobile <> '虚拟'  group by FROM_UNIXTIME(ptime,'%Y-%m-%d')  order by sum(pay_price) desc LIMIT 1", array(':uniacid' => $uniacid));

        //新增粉丝总数 月峰值 日峰值
        $fans = pdo_fetch("SELECT COUNT(*) as num, FROM_UNIXTIME(followtime,'%Y') from cm_mc_mapping_fans WHERE FROM_UNIXTIME(followtime,'%Y')='{$year}' and uniacid = :uniacid", array(':uniacid' => $uniacid));
        $mon_fans = pdo_fetchall("SELECT COUNT(*) as num, FROM_UNIXTIME(followtime,'%Y-%m') as time from cm_mc_mapping_fans WHERE FROM_UNIXTIME(followtime,'%Y')='{$year}' and uniacid = :uniacid  group by FROM_UNIXTIME(followtime,'%Y-%m') order by num desc", array(':uniacid' => $uniacid));
        $day_fans = pdo_fetch("SELECT COUNT(*) as num, FROM_UNIXTIME(followtime,'%Y-%m-%d') from cm_mc_mapping_fans WHERE FROM_UNIXTIME(followtime,'%Y')='{$year}' and uniacid = :uniacid  group by FROM_UNIXTIME(followtime,'%Y-%m-%d') order by num desc LIMIT 1", array(':uniacid' => $uniacid));

        //年度平均客单价 月峰值 日峰值
        $customer_price = pdo_fetch("SELECT SUM(price)/COUNT(id) as customer_price,FROM_UNIXTIME(ptime,'%Y') from cm_tg_order WHERE uniacid = :uniacid and FROM_UNIXTIME(ptime,'%Y')='{$year}' and mobile <> '虚拟'", array(':uniacid' => $uniacid));
        $mon_customer_price = pdo_fetchall("SELECT SUM(price)/COUNT(id) as customer_price,FROM_UNIXTIME(ptime,'%Y-%m') as time from cm_tg_order WHERE uniacid = :uniacid and FROM_UNIXTIME(ptime,'%Y')='{$year}' and mobile <> '虚拟' group by FROM_UNIXTIME(ptime,'%Y-%m') order by SUM(price)/COUNT(id) desc", array(':uniacid' => $uniacid));
        $day_customer_price = pdo_fetch("SELECT SUM(price)/COUNT(id) as customer_price,FROM_UNIXTIME(ptime,'%Y-%m-%d') from cm_tg_order WHERE uniacid = :uniacid and FROM_UNIXTIME(ptime,'%Y')='{$year}' and mobile <> '虚拟' group by FROM_UNIXTIME(ptime,'%Y-%m-%d') order by SUM(price)/COUNT(id) desc LIMIT 1", array(':uniacid' => $uniacid));
        //PV日峰值 UV日峰值 支付转化率日峰值
        $pv_top = pdo_fetch("SELECT uniacid,count(openid) as num ,FROM_UNIXTIME(createtime,'%Y-%m-%d') from cm_tg_puv_record WHERE uniacid = :uniacid  and FROM_UNIXTIME(createtime,'%Y')='{$year}' and openid<>''  group by FROM_UNIXTIME(createtime,'%Y-%m-%d')  order by num desc LIMIT 1", array(':uniacid' => $uniacid));
        $uv_top = pdo_fetch("SELECT uniacid,count(distinct (openid)) as num ,FROM_UNIXTIME(createtime,'%Y-%m-%d') from cm_tg_puv_record WHERE uniacid = :uniacid  and FROM_UNIXTIME(createtime,'%Y')='{$year}' and openid<>''  group by FROM_UNIXTIME(createtime,'%Y-%m-%d')  order by num desc LIMIT 1", array(':uniacid' => $uniacid));
        $order_pay = pdo_fetchall("select distinct (openid) from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `ptime` >= {$before} AND `ptime` <= {$now} group by openid", array(':uniacid' => $uniacid));
        //$zhl_top =
        $zhl_top = pdo_fetch("SELECT (order_pay_openid/uv*100) as zhl ,FROM_UNIXTIME(addtime,'%Y-%m-%d') from cm_tg_data_base WHERE uniacid = :uniacid  and FROM_UNIXTIME(addtime,'%Y')='{$year}' group by FROM_UNIXTIME(addtime,'%Y-%m-%d')  order by zhl desc LIMIT 1", array(':uniacid' => $uniacid));
        //Number(data.order_pay_openid) / Number(data.uv) * 100
        //我的排行榜 销售量最好的商品 销售金额最大的商品 开过最大的团 PV日峰值 UV日峰值 支付转化率日峰值
        $sql = "SELECT a.g_id+ifnull(b.sid,0) as g_id,sum(ifnull(b.oprice,a.pay_price)) as total_price,sum(ifnull(a.gnum,0)+ifnull(b.num,0)) as order_num from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno WHERE a.`uniacid` = :uniacid and FROM_UNIXTIME(a.ptime,'%Y')='{$year}' and a.mobile <> '虚拟'  GROUP BY (a.g_id+ifnull(b.sid,0)) order by sum(ifnull(a.gnum,0)+ifnull(b.num,0)) desc limit 1";
        $goods_top = pdo_fetch($sql, array(':uniacid' => $uniacid));
        $sql = "SELECT a.g_id+ifnull(b.sid,0) as g_id,sum(ifnull(b.oprice,a.pay_price)) as total_price,sum(ifnull(a.gnum,0)+ifnull(b.num,0)) as order_num from cm_tg_order a LEFT JOIN cm_tg_collect b ON a.orderno=b.orderno WHERE a.`uniacid` = :uniacid and FROM_UNIXTIME(a.ptime,'%Y')='{$year}' and a.mobile <> '虚拟'  GROUP BY (a.g_id+ifnull(b.sid,0)) order by sum(ifnull(b.oprice,a.pay_price)) desc limit 1";
        $goods_sale_top = pdo_fetch($sql, array(':uniacid' => $uniacid));
        $data = array(
            'uniacid' => $uniacid,
            'addtime' => TIMESTAMP - 60 * 60 * 24 * $day,
            'total_order' => $total_pay['num'],
            'mon_order' => $mon_pay[0]['num'],
            'day_order' => $day_pay['num'],
            'total_pay' => $total_pay['pay_price'],
            'mon_pay' => $mon_pay[0]['pay_price'],
            'day_pay' => $day_pay['pay_price'],
            'fans' => $fans['num'],
            'mon_fans' => $mon_fans[0]['num'],
            'day_fans' => $day_fans['num'],
            'customer_price' => $customer_price['customer_price'],
            'mon_customer_price' => $mon_customer_price[0]['customer_price'],
            'day_customer_price' => $day_customer_price['customer_price'],
            'big_tuan'=>$big_tuan['num'],
            'goods_top' => $goods_top['g_id'],
            'num1'=>$goods_top['order_num'],
            'goods_sale_top' => $goods_sale_top['g_id'],
            'num2'=>$goods_sale_top['total_price'],
            'pv_top' => $pv_top['num'],
            'uv_top' => $uv_top['num'],
            'zhl_top'=>$zhl_top['zhl'],
        );
        $base_data = pdo_fetch('select id from ' . tablename('annual_data') . " where `uniacid` = :uniacid ", array(':uniacid' => $uniacid));
        if (empty($base_data)) {
            pdo_insert('annual_data', $data);
        } else {

            pdo_update('annual_data', $data, array('id' => $base_data['id']));
        }
        foreach ($mon_pay as $key=>$value){
            $date = $value['time'];
            $data = array(
                'uniacid' => $uniacid,
                'time' => strtotime($date),
                'order_num' => $value['num'],
                'pay' => $value['pay_price']
            );
            $base_data = pdo_fetch('select id from ' . tablename('annual_data_order') . " where `uniacid` = :uniacid and time=".strtotime($date), array(':uniacid' => $uniacid));
            if (empty($base_data)) {
                pdo_insert('annual_data_order', $data);
            } else {

                pdo_update('annual_data_order', $data, array('id' => $base_data['id']));
            }

        }
        foreach ($mon_fans as $key=>$value){
            $date = $value['time'];
            $data = array(
                'uniacid' => $uniacid,
                'time' => strtotime($date),
                'fans_num' => $value['num']
            );
            $base_data = pdo_fetch('select id from ' . tablename('annual_data_fans') . " where `uniacid` = :uniacid and time=".strtotime($date), array(':uniacid' => $uniacid));
            if (empty($base_data)) {
                pdo_insert('annual_data_fans', $data);
            } else {

                pdo_update('annual_data_fans', $data, array('id' => $base_data['id']));
            }
        }
        foreach ($mon_customer_price as $key=>$value){
            $date = $value['time'];
            $data = array(
                'uniacid' => $uniacid,
                'time' => strtotime($date),
                'customer_price' => $value['customer_price']
            );
            $base_data = pdo_fetch('select id from ' . tablename('annual_data_customer_price') . " where `uniacid` = :uniacid and time=".strtotime($date), array(':uniacid' => $uniacid));
            if (empty($base_data)) {
                pdo_insert('annual_data_customer_price', $data);
            } else {

                pdo_update('annual_data_customer_price', $data, array('id' => $base_data['id']));
            }
        }
    }
    die(json_encode(['errCode'=>1]));
}


if($op == 'run_fans_data'){
    $uniacid = $_GPC['uniacid'];
//    $year = $_GPC['year'];
//    $nowdate = date('Ymd', TIMESTAMP);
//    $nowday = strtotime($nowdate);
    $now = 1514736000;
    $before = 1483200000;
//    if($_GPC['uniacid']){
//        $condition = ' AND `uniacid`='.$_GPC['uniacid'];
//    }
//    $limit = pdo_fetch("SELECT * FROM cm_fans_data_limit");
//    if(!$limit){
//        $limit = 0;
//        $sql = "SELECT uniacid from cm_tg_order WHERE `ptime` >= {$before} AND `ptime` < {$now}".$condition." group by uniacid order by uniacid limit 0,1";
//        pdo_insert('fans_data_limit',['limit'=>$limit+1]);
//    }else{
//        $num = $limit['limit'];
//        if($num > 372){
//            die(json_encode(['errCode'=>1,'msg'=>'超出限制']));
//        }
//        $sql = "SELECT uniacid from cm_tg_order WHERE `ptime` >= {$before} AND `ptime` < {$now}".$condition." group by uniacid order by uniacid limit {$num},1";
//        $num = $num+1;
//        pdo_update('fans_data_limit', ['limit'=>$num], array('id' => $limit['id']));
//    }
//    $sql = "SELECT uniacid from cm_tg_order WHERE `ptime` >= {$before} AND `ptime` < {$now}".$condition;
//    $vv = pdo_fetch($sql);
//    $max = $_GPC['max'];
//    $uniacid = $vv['uniacid'];
    $limit = intval($_GPC['limit']);
    $limit_l = ($limit-1)*8000;
    $openids = pdo_fetchall("SELECT openid,id FROM cm_tg_order WHERE `uniacid`={$uniacid} AND mobile<>'虚拟' AND `ptime` >= {$before} AND `ptime` < {$now} GROUP BY openid order by id desc limit {$limit_l},8000");
    foreach ($openids as $value){
        $t1 = microtime();
        $total = pdo_fetch("SELECT sum(pay_price) as total_pay,COUNT(id) as total_order FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now}");
        echo microtime()-$t1;
        $eager_type = pdo_fetch("SELECT COUNT(id) as num,is_tuan FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND is_tuan in (0,1,2) GROUP BY is_tuan ORDER BY num DESC LIMIT 1");
        $eager_tuan_num = pdo_fetch("SELECT COUNT(b.id) as total, (b.neednum-b.lacknum) as num FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2) GROUP BY num ORDER BY total desc LIMIT 1");
        $tuan = pdo_fetch("SELECT (b.neednum-b.lacknum) as num,b.groupnumber FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2) ORDER BY num desc LIMIT 1");
        $in_tuan = pdo_fetch("SELECT COUNT(b.id) as in_tuan FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2)");
        $success_tuan = pdo_fetch("SELECT COUNT(b.id) as success_tuan FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2) AND b.groupstatus=2");
        //凌晨
        $consumption_time_aam = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='00' AND FROM_UNIXTIME(ptime,'%H')<'06'");
        //早上
        $consumption_time_am = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='06' AND FROM_UNIXTIME(ptime,'%H')<'12'");
        if($consumption_time_am['num']>=$consumption_time_aam['num']){
            $consumption_time = 'am';
            $counttime = $consumption_time_am['num'];
        }else{
            $consumption_time = 'aam';
            $counttime = $consumption_time_aam['num'];
        }
        //下午
        $consumption_time_pm = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='12' AND FROM_UNIXTIME(ptime,'%H')<'18'");
        if($consumption_time_pm['num']>=$counttime){
            $consumption_time = 'pm';
            $counttime = $consumption_time_pm['num'];
        }
        //晚上
        $consumption_time_nm = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='18' AND FROM_UNIXTIME(ptime,'%H')<'24'");
        if($consumption_time_nm['num']>=$counttime){
            $consumption_time = 'nm';
            $counttime = $consumption_time_nm['num'];
        }

        $data = array(
            'uniacid' => $uniacid,
            'openid' => $value['openid'],
            'total_pay' => $total['total_pay'],
            'total_order' => $total['total_order'],
            'eager_type' =>$eager_type['is_tuan'],
            'eager_tuan_num' =>$eager_tuan_num['num'],
            'big_tuan'=>$tuan['groupnumber'],
            'big_tuan_num'=>$tuan['num'],
            'tuan' =>$in_tuan['in_tuan'],
            'success_tuan'=>$success_tuan['success_tuan'],
            'consumption_time'=>$consumption_time
        );
        $base_data = pdo_fetch('select id from ' . tablename('fans_data') . " where `uniacid` = :uniacid and openid='{$value['openid']}'", array(':uniacid' => $uniacid));
        if (empty($base_data)) {
            pdo_insert('fans_data', $data);
        } else {
            pdo_update('fans_data', $data, array('id' => $base_data['id']));
        }
    }
    die(json_encode(['errCode'=>1,'uniacid'=>$uniacid]));
}
if($op == 'update_fans_data'){
    $limit = intval($_GPC['limit']);
    $limit_l = ($limit-1)*20;
    $uniacids = pdo_fetchall("SELECT uniacid FROM cm_fans_data GROUP BY uniacid ORDER BY uniacid limit {$limit_l},20");
    foreach ($uniacids as $value){
        $list = pdo_fetchall("SELECT * FROM cm_fans_data WHERE `uniacid`={$value['uniacid']} ORDER BY total_pay DESC");
        foreach ($list as $k=>$v){
            $v['sort'] = $k+1;
            $sort_part = (count($list)-$k-1)/count($list);
            $v['sort_part'] = round($sort_part,5);
            pdo_update('fans_data', $v, array('id' => $v['id']));

        }
        echo $value['uniacid'];
    }
}

if ($op == 'run_spring_fans_data') {
    $uniacid = $_GPC['uniacid'];
//    $year = $_GPC['year'];
//    $nowdate = date('Ymd', TIMESTAMP);
//    $nowday = strtotime($nowdate);
    $now = 1518624000;
    $before = 1485532800;
    $limit = pdo_fetch("SELECT * FROM cm_fans_data_limit");
    if($_GPC['uniacid']){
        $condition = ' AND `uniacid`='.$_GPC['uniacid'];
        $limit = intval($_GPC['limit']);
        if($limit){
            $limit_l = ($limit - 1) * 8000;
            $limit = "  limit {$limit_l},8000";
            $openids = pdo_fetchall("SELECT openid,id FROM cm_tg_order WHERE `uniacid`={$uniacid} AND mobile<>'虚拟' AND `ptime` >= {$before} AND `ptime` < {$now} GROUP BY openid order by id desc ".$limit);
        }else{
            $openids = pdo_fetchall("SELECT openid,id FROM cm_tg_order WHERE `uniacid`={$uniacid} AND mobile<>'虚拟' AND `ptime` >= {$before} AND `ptime` < {$now} GROUP BY openid order by id desc");
        }
    }else{

        if(!$limit){
            $limit = 0;
            $sql = "SELECT uniacid from cm_tg_order WHERE `ptime` >= {$before} AND `ptime` < {$now}".$condition." group by uniacid order by uniacid limit 0,1";
            pdo_insert('fans_data_limit',['limit'=>$limit+1]);
        }else{
            $num = $limit['limit'];
            if($num > 372){
                die(json_encode(['errCode'=>1,'msg'=>'超出限制']));
            }
            $sql = "SELECT uniacid from cm_tg_order WHERE `ptime` >= {$before} AND `ptime` < {$now}".$condition." group by uniacid order by uniacid limit {$num},1";
            $num = $num+1;
            pdo_update('fans_data_limit', ['limit'=>$num], array('id' => $limit['id']));
        }
        $sql = "SELECT uniacid from cm_tg_order WHERE `ptime` >= {$before} AND `ptime` < {$now}".$condition;
        $vv = pdo_fetch($sql);
        $max = $_GPC['max'];
        $uniacid = $vv['uniacid'];
        $openids = pdo_fetchall("SELECT openid,id FROM cm_tg_order WHERE `uniacid`={$uniacid} AND mobile<>'虚拟' AND `ptime` >= {$before} AND `ptime` < {$now} GROUP BY openid order by id desc");
    }

    foreach ($openids as $value) {
        $t1 = microtime();
        $total = pdo_fetch("SELECT sum(pay_price) as total_pay,COUNT(id) as total_order FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now}");
        echo microtime() - $t1;
        $eager_type = pdo_fetch("SELECT COUNT(id) as num,is_tuan FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND is_tuan in (0,1,2) GROUP BY is_tuan ORDER BY num DESC LIMIT 1");
        $eager_tuan_num = pdo_fetch("SELECT COUNT(b.id) as total, (b.neednum-b.lacknum) as num FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2) GROUP BY num ORDER BY total desc LIMIT 1");
        $tuan = pdo_fetch("SELECT (b.neednum-b.lacknum) as num,b.groupnumber FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2) ORDER BY num desc LIMIT 1");
        $in_tuan = pdo_fetch("SELECT COUNT(b.id) as in_tuan FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2)");
        $success_tuan = pdo_fetch("SELECT COUNT(b.id) as success_tuan FROM cm_tg_order a LEFT JOIN cm_tg_group b ON a.tuan_id=b.groupnumber WHERE a.`uniacid`={$uniacid} AND a.openid = '{$value['openid']}' AND a.`ptime` >= {$before} AND a.`ptime` < {$now} AND a.is_tuan in (1,2) AND b.groupstatus=2");
        //凌晨
        $consumption_time_aam = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='00' AND FROM_UNIXTIME(ptime,'%H')<'06'");
        //早上
        $consumption_time_am = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='06' AND FROM_UNIXTIME(ptime,'%H')<'12'");
        if ($consumption_time_am['num'] >= $consumption_time_aam['num']) {
            $consumption_time = 'am';
            $counttime = $consumption_time_am['num'];
        } else {
            $consumption_time = 'aam';
            $counttime = $consumption_time_aam['num'];
        }
        //下午
        $consumption_time_pm = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='12' AND FROM_UNIXTIME(ptime,'%H')<'18'");
        if ($consumption_time_pm['num'] >= $counttime) {
            $consumption_time = 'pm';
            $counttime = $consumption_time_pm['num'];
        }
        //晚上
        $consumption_time_nm = pdo_fetch("SELECT COUNT(id) as num FROM cm_tg_order WHERE `uniacid`={$uniacid} AND openid = '{$value['openid']}' AND `ptime` >= {$before} AND `ptime` < {$now} AND FROM_UNIXTIME(ptime,'%H')>='18' AND FROM_UNIXTIME(ptime,'%H')<'24'");
        if ($consumption_time_nm['num'] >= $counttime) {
            $consumption_time = 'nm';
            $counttime = $consumption_time_nm['num'];
        }
        echo microtime() - $t1;
        $data = array(
            'uniacid' => $uniacid,
            'openid' => $value['openid'],
            'total_pay' => $total['total_pay'],
            'total_order' => $total['total_order'],
            'eager_type' => $eager_type['is_tuan'],
            'eager_tuan_num' => $eager_tuan_num['num'],
            'big_tuan' => $tuan['groupnumber'],
            'big_tuan_num' => $tuan['num'],
            'tuan' => $in_tuan['in_tuan'],
            'success_tuan' => $success_tuan['success_tuan'],
            'consumption_time' => $consumption_time
        );
        $base_data = pdo_fetch('select id from ' . tablename('fans_data_spring') . " where `uniacid` = :uniacid and openid='{$value['openid']}'", array(':uniacid' => $uniacid));
        if (empty($base_data)) {
            pdo_insert('fans_data_spring', $data);
        } else {
            pdo_update('fans_data_spring', $data, array('id' => $base_data['id']));
        }
    }
    die(json_encode(['errCode' => 1, 'uniacid' => $uniacid]));
}
if ($op == 'update_spring_fans_data') {
    $uniacid = $_GPC['uniacid'];
    if($uniacid){
        $list = pdo_fetchall("SELECT * FROM cm_fans_data_spring WHERE `uniacid`={$uniacid} ORDER BY total_pay DESC");
        foreach ($list as $k => $v) {
            $v['sort'] = $k + 1;
            $sort_part = (count($list) - $k - 1) / count($list);
            $v['sort_part'] = round($sort_part, 5);
            pdo_update('fans_data_spring', $v, array('id' => $v['id']));

        }
        echo $value['uniacid'];
    }else{
        $limit = intval($_GPC['limit']);
        $limit_l = ($limit - 1) * 50;
        $uniacids = pdo_fetchall("SELECT uniacid FROM cm_fans_data_spring GROUP BY uniacid ORDER BY uniacid limit {$limit_l},50");
        foreach ($uniacids as $value) {
            $list = pdo_fetchall("SELECT * FROM cm_fans_data_spring WHERE `uniacid`={$value['uniacid']} ORDER BY total_pay DESC");
            foreach ($list as $k => $v) {
                $v['sort'] = $k + 1;
                $sort_part = (count($list) - $k - 1) / count($list);
                $v['sort_part'] = round($sort_part, 5);
                pdo_update('fans_data_spring', $v, array('id' => $v['id']));

            }
            echo $value['uniacid'];
        }
    }
}





if ($op == 'order_recive_update') {

}
//自动签收
if ($op == 'order_recive') {
//    $allnogettime = pdo_fetchall("SELECT id,orderno,openid,g_id,commission,cost_fee,price,commissiontype,sendtime,status,uniacid,gnum,over_time,com_type,tuan_id,tuan_first FROM " . tablename('tg_order') . " WHERE comtype=0 AND status=3 and over_time>0 ORDER BY comtype ASC , over_time ASC LIMIT 50");


//    foreach ($allnogettime as $key => $value) {
//
//        // file_put_contents("123.log", var_export($openid."金额".$order_price."等级ID".$auto_level['id'], true).PHP_EOL, FILE_APPEND);
//        //根据用户设置的时间来确定佣金结算时间
//        $acc = pdo_fetch("SELECT commission_time FROM " . tablename('account_wechats') . " WHERE uniacid = " . $value['uniacid']);
//        $over_time = TIMESTAMP - intval($acc['commission_time']) * 24 * 60 * 60;
////        die(json_encode($value['id']));
////        if ($value['uniacid'] == '53')
////            die(json_encode($value['over_time'] > $over_time));
//        if ($value['over_time'] > $over_time) {
//            break;
//        }
//        unset($acc);
//        //积分
//        if ($value['g_id'] > 0) {
//            $goodsInfo = pdo_fetch("SELECT credit FROM " . tablename('tg_goods') . " WHERE uniacid ='{$value['uniacid']}' and id = '{$value['g_id']}'");
//            $is_send = pdo_fetchall("select * from " . tablename("tg_goods") . " where id = :id", array(":id" => $value['g_id']));
//            $fans = pdo_fetch("SELECT uid FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$value['uniacid']}' and openid = '{$value['openid']}'");
//            $mencredit = pdo_fetch("SELECT credit1 FROM " . tablename('mc_members') . " WHERE uniacid ='{$value['uniacid']}' and uid = '{$fans['uid']}'");
//            $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
//            pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
//            if ($value['com_type'] == 1) {
//                //佣金
//                $ud = pdo_fetch("select parentid from" . tablename('tg_member') . "where  uniacid='{$value['uniacid']}' and from_user='{$value['openid']}'");
//                if (intval($ud['parentid']) > 0) {
//                    $parent = pdo_fetch("select billing,wallet,id,from_user from" . tablename('tg_member') . "where  uniacid='{$value['uniacid']}' and id={$ud['parentid']}");
//                    if ($value['commissiontype'] == 2) {
//                        $price1 = $value['commission'] * $value['gnum'];
//                    } else {
//                        $price1 = ($value['price'] - $value['cost_fee']) * $value['commission'] * $value['gnum'] / 100;//佣金计算
//                    }
//                    $billing = $parent['billing'] + $price1;//已结算佣金
//                    $wallet = $parent['wallet'] + $price1;//钱包总佣金
//                    //$nobilling=$parent['nobilling']-$price;//未结算佣金
//                    pdo_update('tg_member', array('billing' => $billing, 'wallet' => $wallet), array('id' => $parent['id']));
//                    //佣金结算记录
//                    $bdata = array(
//                        'uniacid' => $value['uniacid'],
//                        'openid' => $parent['from_user'],
//                        'orderno' => $value['orderno'],
//                        'billdate' => TIMESTAMP,
//                        'price' => $price1
//                    );
//                    pdo_insert('tg_billrecord', $bdata);
//                    //添加佣金模板消息
//                    // result_type($openid, $title, $message, $remark, $url , $a = 0)
//                    $postData['openid'] = $parent['from_user'];
//                    $postData['orderid'] = $value['id'];
//                    $postData['title'] = "您有一笔新的佣金结算，请注意查收";
//                    $postData['message'] = "当前结算金额：￥" . $price1;
//                    $postData['remark'] = "未提现总金额：￥" . $wallet;
//                    $postData_res = serialize($postData);
//                    $xc["content"] = $postData_res;
//                    $xc["uniacid"] = $value['uniacid'];
//                    $xc["openid"] = $parent['from_user'];
//                    $xc["mess_tpl"] = 'result_commission';
//                    pdo_insert("tg_message_log", $xc);
//                }
//            } elseif ($value['com_type'] == 2) {
//                $commander = pdo_fetch("select * from " . tablename('tg_commander') . " where tuan_id = '{$value['tuan_id']}' ");
//                if ($commander['commissiontype'] == 1) {
//                    $withdraw = ($value['price'] - $value['cost_fee']) * $value['commission'] * 0.01;
//                } elseif ($commander['commissiontype'] == 2) {
//                    $withdraw = $commander['commission'] * $value['gnum'];
//                }
//                pdo_update('tg_commander', array('success_num' => $commander['success_num'] + 1, 'withdraw' => $commander['withdraw'] + $withdraw), array('tuan_id' => $value['tuan_id']));
////                if ($value['tuan_first']) {
////                    pdo_update('tg_member' , array('commander_settled +=' => $withdraw) , array('uniacid' => $value['uniacid'] , 'openid' => $value['openid']));
////                } else {
////                    $me = pdo_fetch("select openid from " .tablename('tg_order') ." where uniacid = '{$value['uniacid']}' and tuan_id = '{$value['tuan_id']}' and tuan_first = 1 ");
////                    pdo_update('tg_member' , array('commander_settled +=' => $withdraw) , array('uniacid' => $value['uniacid'] , 'openid' => $me['openid']));
////                }
//                $me = pdo_fetch("select openid from " . tablename('tg_order') . " where uniacid = '{$value['uniacid']}' and tuan_id = '{$value['tuan_id']}' and tuan_first = 1 ");
//                pdo_update('tg_member', array('commander_settled +=' => $withdraw), array('uniacid' => $value['uniacid'], 'openid' => $me['openid']));
//                //添加佣金模板消息
//                // result_type($openid, $title, $message, $remark, $url , $a = 0)
//                $postData['openid'] = $me['openid'];
//                $postData['orderid'] = $value['id'];
//                $postData['title'] = "您有一笔新的佣金结算，请注意查收";
//                $postData['message'] = "当前结算金额：￥" . $withdraw;
//                $postData['remark'] = "未提现总金额：￥" . $commander['commander_settled'] - $commander['commander_apply'] - $commander['commander_withdraw'];
//                $postData_res = serialize($postData);
//                $xc["content"] = $postData_res;
//                $xc["uniacid"] = $value['uniacid'];
//                $xc["openid"] = $me['openid'];
//                $xc["mess_tpl"] = 'result_commission';
//                pdo_insert("tg_message_log", $xc);
//
//            }
//            pdo_update('tg_order', array('comtype' => 1), array('id' => $value['id']));
////
//        }
//        if ($value['g_id'] == 0) {
//
//            $favoriteqqq = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE uniacid ='{$value['uniacid']}'   and orderno='{$value['orderno']}'");
//            $is_send = pdo_fetchall("select * from " . tablename("tg_collect") . " where orderno = :id", array(":id" => $value['orderno']));
//            $fans = pdo_fetch("SELECT uid FROM " . tablename('mc_mapping_fans') . " WHERE uniacid ='{$value['uniacid']}' and openid = '{$values['openid']}'");
//            $mencredit = pdo_fetch("SELECT credit1 FROM " . tablename('mc_members') . " WHERE uniacid ='{$value['uniacid']}' and uid = '{$fans['uid']}'");
//            $creditnum = $mencredit['credit1'] + $goodsInfo['credit'];
//            $price1 = 0;
//            foreach ($favoriteqqq as $key => $orderss) {
//                $gs = pdo_fetch("select * from " . tablename('tg_goods') . "where uniacid='{$value['uniacid']}' and id={$orderss['sid']} ");
//                if (!empty($gs['credit']) && $gs['credit'] != 0) {
//                    $creditnum += $gs['credit'] * $orderss['num'];
//                }
//                if ($gs['commissiontype'] == 2) {
//                    $price1 += $gs['commission'] * $orderss['num'];
//                } else {
//                    $price1 += ($orderss['oprice'] * $orderss['num']) * $orderss['commission'] / 100;//佣金计算
//                }
//            }
//            pdo_update('mc_members', array('credit1' => $creditnum), array('uid' => $fans['uid']));
//            //积分
//            //佣金
//            $ud = pdo_fetch("select parentid from" . tablename('tg_member') . "where  uniacid='{$value['uniacid']}' and from_user='{$value['openid']}'");
//            if (intval($ud['parentid']) > 0) {
//                $parent = pdo_fetch("select billing,wallet,id,from_user from" . tablename('tg_member') . "where  uniacid='{$value['uniacid']}' and id={$ud['parentid']}");
//
//                $billing = $parent['billing'] + $price1;//已结算佣金
//                $wallet = $parent['wallet'] + $price1;//钱包总佣金
//                //$nobilling=$parent['nobilling']-$price;//未结算佣金
//                pdo_update('tg_member', array('billing' => $billing, 'wallet' => $wallet), array('id' => $parent['id']));
//                //佣金结算记录
//                $bdata = array(
//                    'uniacid' => $value['uniacid'],
//                    'openid' => $parent['from_user'],
//                    'orderno' => $value['orderno'],
//                    'billdate' => TIMESTAMP,
//                    'price' => $price1
//                );
//                //添加佣金模板消息
//                // result_type($openid, $title, $message, $remark, $url , $a = 0)
//                $bdata = array(
//                    'uniacid' => $value['uniacid'],
//                    'openid' => $parent['from_user'],
//                    'orderno' => $value['orderno'],
//                    'billdate' => TIMESTAMP,
//                    'price' => $price1
//                );
////                $postData['topcolor'] = "blue";
////                $postData["data"]["first"] = "您的订单已经签收";
////                $postData["data"]["keyword1"] = "您的订单已经签收";
////                $postData["data"]["keyword2"] = "点击查看详情";
////                $postData["data"]["remark"] = "点击查看详情";
////                $postData_res = serialize($postData);
////                $xc["content"] = $postData_res;
////                $xc["uniacid"] = $value['uniacid'];
////                $xc["openid"] = $parent['from_user'];
////                $xc["mess_tpl"] = "result_type";
////                pdo_insert("tg_message_log", $xc);
//
//                $postData['openid'] = $parent['from_user'];
//                $postData['orderid'] = $value['id'];
//                $postData['title'] = "您有一笔新的佣金结算，请注意查收";
//                $postData['message'] = "当前结算金额：￥" . $price1;
//                $postData['remark'] = "未提现总金额：￥" . $wallet;
//                $postData_res = serialize($postData);
//                $xc["content"] = $postData_res;
//                $xc["uniacid"] = $value['uniacid'];
//                $xc["openid"] = $parent['from_user'];
//                $xc["mess_tpl"] = 'result_commission';
//                pdo_insert("tg_message_log", $xc);
//                pdo_insert('tg_billrecord', $bdata);
//
//            }
//            pdo_update('tg_order', array('comtype' => 1), array('id' => $value['id']));
//
//        }
//        for ($i = 0; $i < count($is_send); $i++) {
////        $is_send = pdo_fetch("select * from ".tablename("tg_goods")." where 	id = :id",array(":id"=>$is_send[$i]["g_id"]));
//            if ($is_send[$i]["is_sendcoupon"] == 1) {
//                $coupon_id = $is_send[$i]["coupon_id"];
//                //查询优惠券详情
//                $coupon_info = pdo_get("tg_coupon_template", array("id" => $coupon_id));
//                $data_xc["name"] = $coupon_info["name"];
//                $data_xc["coupon_template_id"] = $coupon_info["id"];
//                $data_xc["openid"] = $value["openid"];
//                $data_xc["description"] = $coupon_info["description"];
//                $data_xc["start_time"] = time();
//                $data_xc["end_time"] = time() + 1 * 24 * 60 * 60;
//                $data_xc["at_least"] = $coupon_info["at_least"];
//                $data_xc["uniacid"] = $coupon_info["uniacid"];
//                $data_xc["cash"] = $coupon_info["value"];
//                $data_xc["createtime"] = time();
//                pdo_insert("tg_coupon", $data_xc);
//            }
//        }
//        // load()->classs("account");
//        // $account_api = WeAccount::create();
//        // $token = $account_api->getAccessToken();
//        // $postData = array();
//        //$postData['template_id'] ="zd84jV-u7Fd5uQlw2FHRCIELBhx8rTwmOpM1GaTzNtw";
//        //$url = $_W['siteroot'] . 'app/index.php?c=site&a=entry&m=lexiangpingou&ac=order&';
//        // $postData['url'] = $url;
//        // $postData['topcolor'] = "blue";
//        // $postData["data"]["first"] = "您的订单已经签收";
//        // $postData["data"]["keyword1"] = "您的订单已经签收";
//        // $postData["data"]["keyword2"] = "点击查看详情";
//        // $postData["data"]["remark"] = "点击查看详情";
//        // $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $token;
//        // $postData['touser'] = $parent['from_user'];
//        // $res = ihttp_post($url, json_encode($postData));
//
//
////获取当前会员的openid
//        $openid = $value["openid"];
//        //获取当前会员的等级
//        $user_info = pdo_fetch("SELECT * FROM " .
//            tablename("tg_member") .
//            " WHERE from_user = :openid AND uniacid = :uniacid",
//            array(":openid" => $openid, ":uniacid" => $value["uniacid"])
//        );
//        //查出来会员的总订单金额是多少
//        $order_money = pdo_fetch("SELECT sum(price) AS price FROM " .
//            tablename("tg_order") .
//            " WHERE openid = :openid AND uniacid = :uniacid AND status = 3",
//            array(":openid" => $openid, ":uniacid" => $value["uniacid"])
//        );
//        $order_price = $order_money["price"];
//        //计算出总金额之后然后查看赋予会员的等级是多少
//        $auto_level = pdo_fetch("SELECT * FROM " .
//            tablename("tg_member_leave") .
//            " WHERE :order_price >= money AND uniacid = :uniacid ORDER BY money DESC",
//            array(":uniacid" => $value["uniacid"], ":order_price" => $order_price));
//        if ($auto_level["id"] == intval($user_info["level"])) {
//            //不做变化
//        } elseif ($auto_level["id"] < intval($user_info["level"])) {
//            //不做变化
//        } else {
//            //升级会员等级
//            $data["level"] = $auto_level["id"];
//            $res = pdo_update("tg_member",
//                $data,
//                array("from_user" => $openid, "uniacid" => $value["uniacid"])
//            );
//        }
//    }
    exit();
}
//组团人数不足，发送模板消息
/*OPENTM406281958
 * {{first.DATA}}
团购商品：{{keyword1.DATA}}
剩余拼团时间：{{keyword2.DATA}}
剩余拼团人数：{{keyword3.DATA}}
{{remark.DATA}}
*/

if ($op == 'group_status3') {

    $group_list = pdo_fetchall("select * from cm_tg_group where groupstatus=3 and no_num_status=1 and no_num_success<:endtime limit 20", array(':endtime' => TIMESTAMP));
    foreach ($group_list as $list) {
        $order_list = pdo_fetchall('select openid from cm_tg_order where tuan_id=:tuan_id and ptime>0', array(':tuan_id' => $list['groupnumber']));
        $goods = pdo_fetch('select no_num_success from cm_tg_goods where id=:id', array(':id' => $list['goodsid']));
        foreach ($order_list as $value) {

            $remark = '点击此处邀请小伙伴参团~';
            /*if($list['selltype']==4||$list['selltype']==7)
			{
				$remark='在规定时间内凑满人数才能达到最后一个阶梯价，点击此处邀请小伙伴参团！';
			}*/
            $bdata = array(
                'uniacid' => $list['uniacid'],
                'openid' => $value['openid'],
                'goodsname' => $list['goodsname'],
                'lasttime' => $goods['no_num_success'] . '分钟',
                'groupnumber' => $list['groupnumber'],
                'neednum' => $list['lacknum'] . '人',
                'remark' => $remark
            );
            pdo_insert('tg_task', $bdata);

        }
        pdo_update('tg_group', array('no_num_status' => 0), array('id' => $list['id']));
    }
    die(json_encode(array('nowtime' => $nowtime, 'count' => count($group_list))));

}
if ($op == 'time_test') {
    echo strtotime("+10 minute", 1522111801);
    exit;
}
//成团人数对比
/*
 * 接口地址:http://www.lexiangpingou.cn/minapi.php?op=group_num_data&uniacid=259
 * uniacid 为当前公众号ID
 */
if ($op == 'group_num_data') {
    $uniacid = $_GPC['uniacid'];
    $data_list = pdo_fetchall('SELECT count(*) as total ,neednum FROM cm_tg_group where uniacid=:uniacid and groupstatus=2 group by neednum', array(':uniacid' => $uniacid));
    $total = 0;
    foreach ($data_list as &$item) {
        if ($item['neednum'] == 1000000000) {
            $item['neednum'] = '定金团';
        }
        $total += $item['total'];
        unset($item);
    }
    $status = 2;
    if (empty($data_list)) {
        $status = 1;
    }
    $list = array();
    $list['status'] = $status;
    $list['data'] = $data_list;
    $list['total'] = $total;
    die(json_encode($list));
}
/*
 * 购买模式对比
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=buy_model_data&uniacid=259
 */
if ($op == 'buy_model_data') {
    $uniacid = $_GPC['uniacid'];
    $data_list = pdo_fetchall('SELECT count(*) as total ,SUM(price) as total_price ,selltype FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8)  and ptime>0 and mobile<>\'虚拟\' group by selltype', array(':uniacid' => $uniacid));
    $total = 0;
    foreach ($data_list as &$item) {

        switch (intval($item['selltype'])) {
            case 1:
                $item['selltype'] = '随意团';
                break;
            case 2:
                $item['selltype'] = '邻购团';
                break;
            case 3:
                $item['selltype'] = '自选团';
                break;
            case 4:
                $item['selltype'] = '阶梯团';
                break;
            case 5:
                $item['selltype'] = '抽奖团';
                break;
            case 6:
                $item['selltype'] = '新专团';
                break;
            case 7:
                $item['selltype'] = '订金团';
                break;
            default:
                $item['selltype'] = '购物车';
                break;

        }
        $total += $item['total'];
        unset($item);
    }
    $status = 2;
    if (empty($data_list)) {
        $status = 1;
    }
    $list = array();
    $list['status'] = $status;
    $list['data'] = $data_list;
    $list['total'] = $total;
    die(json_encode($list));
}
/*
 * 门店核销对比
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=store_check_data&uniacid=259
 */
if ($op == 'store_check_data') {
    $uniacid = $_GPC['uniacid'];
    $data_list = pdo_fetchall('SELECT count(*) as total ,checkstore FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and dispatchtype=3 and checkstore<>"" and mobile<>"虚拟" group by checkstore', array(':uniacid' => $uniacid));
    $total = 0;
    foreach ($data_list as &$item) {
        $store = pdo_fetch('select storename from cm_tg_store where id=:id', array(':id' => $item['checkstore']));
        $item['storename'] = $store['storename'];
        $total += $item['total'];
    }
    $status = 2;
    if (empty($data_list)) {
        $status = 1;
    }
    $list = array();
    $list['status'] = $status;
    $list['data'] = $data_list;
    $list['total'] = $total;
    die(json_encode($list));
}
/*
 * 配送方式对比
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=dispatch_data&uniacid=259
 */
if ($op == 'dispatch_data') {
    $uniacid = $_GPC['uniacid'];
    $data_list = pdo_fetchall('SELECT count(*) as total ,dispatchtype FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and mobile<>"虚拟"  group by dispatchtype', array(':uniacid' => $uniacid));
    $total = 0;
    foreach ($data_list as &$item) {
        switch ($item['dispatchtype']) {
            case 1:
                $item['dispatchtype'] = '送货上门';
                break;
            case 2:
                $item['dispatchtype'] = '快递';
                break;
            case 3:
                $item['dispatchtype'] = '自提';
                break;
            default:
                $item['selltype'] = '无';
                break;

        }
        $total += $item['total'];
        unset($item);
    }
    $status = 2;
    if (empty($data_list)) {
        $status = 1;
    }
    $list = array();
    $list['status'] = $status;
    $list['data'] = $data_list;
    $list['total'] = $total;
    die(json_encode($list));
}
/*
 * 下单门店核销门店准确率
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=store_real_data&uniacid=259
 * store_order:下单门店
 * store_real_order:核销门店
 */
if ($op == 'store_real_data') {
    $uniacid = $_GPC['uniacid'];
    $data_list = pdo_fetchall('SELECT id,storename FROM `cm_tg_store` where uniacid=:uniacid', array(':uniacid' => $uniacid));
    $total = 0;
    foreach ($data_list as &$item) {
        $stores = pdo_fetchall('SELECT comadd,checkstore  FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and dispatchtype=3 and mobile<>"虚拟" ', array(':uniacid' => $uniacid));
        $comadd_tatol = 0;
        $checkstore_tatol = 0;
        foreach ($stores as $val) {
            if ($val['comadd'] == $item['id']) {
                $comadd_tatol += 1;
            }
            if ($val['checkstore'] == $item['id']) {
                $checkstore_tatol += 1;
            }
        }
        //$real_stores=pdo_fetch('SELECT count(*) as total  FROM cm_tg_order where uniacid=:uniacid and status in (1,2,3,8) and dispatchtype=3 and checkstore=:comadd ',array(':uniacid'=>$uniacid,':comadd'=>$item['id']));
        $item['store_order'] = $comadd_tatol;
        $item['store_real_order'] = $checkstore_tatol;
        $total += $stores['total'];
        unset($item);
    }
    $status = 2;
    if (empty($data_list)) {
        $status = 1;
    }
    $list = array();
    $list['status'] = $status;
    $list['data'] = $data_list;
    $list['total'] = $total;
    die(json_encode($list));
}
/*
 * 数据概况
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=sjgk_data&uniacid=259
*/
if ($op == 'sjgk_data') {
    $uniacid = $_GPC['uniacid'];

    $nowdate = date('Ymd', TIMESTAMP);
    $nowday = strtotime($nowdate);
    $now = strtotime($nowdate . " +1 day");
    $before = strtotime($nowdate);


    $day0_data = pdo_fetch('select * from ' . tablename('tg_data_base') . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} ", array(':uniacid' => $uniacid));
    $before = strtotime($nowdate) - 60 * 60 * 24 * 1;
    $day1_data = pdo_fetch('select * from ' . tablename('tg_data_base') . " where uniacid=:uniacid and `addtime` >= {$before} AND `addtime` <= {$nowday} ", array(':uniacid' => $uniacid));
    $before = strtotime($nowdate) - 60 * 60 * 24 * 6;
    $day7_data = pdo_fetch('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(group_total) as group_total,sum(group_success) as group_success,sum(group_fail) as group_fail,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,sum(customer_price)/7 as customer_price,sum(add_fans) as add_fans,sum(pv) as pv,sum(uv) as uv from ' . tablename('tg_data_base')
        . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} ", array(':uniacid' => $uniacid));
    $before = strtotime($nowdate) - 60 * 60 * 24 * 29;
    $day30_data = pdo_fetch('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(group_total) as group_total,sum(group_success) as group_success,sum(group_fail) as group_fail,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,sum(customer_price)/30 as customer_price,sum(add_fans) as add_fans,sum(pv) as pv,sum(uv) as uv from ' . tablename('tg_data_base')
        . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} ", array(':uniacid' => $uniacid));
    $max_data = pdo_fetch('SELECT max(order_total) AS order_total,max(order_pay) AS order_pay,max(order_refund) AS order_refund,max(group_total) AS group_total,max(group_success) AS group_success,max(group_fail) AS group_fail,max(pay_price) AS pay_price,max(refund_price) AS refund_price,max(success_price) AS success_price,max(customer_price) AS customer_price,max(add_fans) AS add_fans,max(pv) AS pv,max(uv) AS uv FROM ' . tablename('tg_data_base') . " WHERE uniacid = :uniacid ", array(':uniacid' => $uniacid));

    $list = array();
    $list['今日实时'] = $day0_data;
    $list['昨日概况'] = $day1_data;
    $list['7日概况'] = $day7_data;
    $list['30日概况'] = $day30_data;
    $list['历史峰值'] = $max_data;
    die(json_encode($list));
}

if ($op == 'since_data') {
    $uniacid = $_GPC['uniacid'];
    $now = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $before = strtotime($_GPC['stime']);
    $day7_data = pdo_fetchall('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,store_id from ' . tablename('tg_since_data')
        . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} group by store_id", array(':uniacid' => $uniacid));
    foreach ($day7_data as $key=>$value){
        $store = pdo_fetch("select * from cm_tg_store WHERE id={$value['store_id']}");
        $day7_data[$key]['store_name'] = $store['storename'];
    }
    die(json_encode($day7_data));
}

if ($op == 'new_since_data') {
    $uniacid = $_GPC['uniacid'];
    $store_id = $_GPC['store_id'];
    if($store_id){
        $condition = " and store_id in {$store_id}";
    }
    $now = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $before = strtotime($_GPC['stime']);
    $since_store = pdo_fetchall("select id,storename from cm_tg_store WHERE `uniacid` = :uniacid", array(':uniacid' => $item['uniacid']));
    $data = pdo_fetchall(" SELECT uniacid,g_id,g_name,g_spec,sum(g_price) as g_price ,sum(success_tuan) as success_tuan,sum(pay_num) as pay_num,sum(pay_price) as pay_price,store_id,store_name,option_id FROM cm_tg_new_since_data_info WHERE uniacid={$uniacid} and addtime>={$before} and addtime<={$now} {$condition} group by store_id,g_id ORDER BY store_id ");
    $count = count($data);
    if($count>=5000){
        die(json_encode(['status'=>0]));
    }else{
        die(json_encode(['status'=>1,'data'=>$data]));
    }
}

//团长佣金报表
if ($op == 'commander_data') {
    $uniacid = $_GPC['uniacid'];
    $tuan_id = $_GPC['tuan_id'];
    $membername = $_GPC['membername'];
    $gname = $_GPC['gname'];
    if($tuan_id){
        $condition = " and a.tuan_id like '%{$tuan_id}%' ";
    }
    if($membername){
        $condition =$condition. " and c.name like '%{$membername}%' ";
    }
    if($gname){
        $condition =$condition. " and b.gname like '%{$gname}%' ";
    }
    $now = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $before = strtotime($_GPC['stime']);

    $data = pdo_fetchall("SELECT a.uniacid,FROM_UNIXTIME(a.createtime) as createtime,a.tuan_id,a.success_num,a.commissiontype,a.commission,a.withdraw,b.gname,c.`name` from cm_tg_commander a LEFT JOIN cm_tg_goods b on a.gid=b.id  LEFT JOIN cm_tg_member c on a.openid=c.from_user LEFT JOIN cm_tg_group d on a.tuan_id=d.groupnumber  where d.groupstatus=2 and a.uniacid={$uniacid} and a.uniacid=b.uniacid and a.uniacid=c.uniacid and a.uniacid=d.uniacid and a.createtime>={$before} and a.createtime<={$now}  {$condition}  order by a.createtime DESC");
    foreach ($data as $key=>$value){
        $order_num=pdo_fetch("SELECT count(*) as order_num from cm_tg_order where tuan_id={$value['tuan_id']} and uniacid={$uniacid} and status in (1,2,8,3) and mobile <>'虚拟'");
        $goods_num=pdo_fetch("SELECT sum(gnum) as goods_num ,sum(price) as order_price from cm_tg_order where tuan_id={$value['tuan_id']} and uniacid={$uniacid} and status in (1,2,8,3) and mobile <>'虚拟'");
        $data[$key]['order_num']=$order_num['order_num'];
        $data[$key]['goods_num']=$goods_num['goods_num'];
        $data[$key]['order_price']=round($goods_num['order_price'],2);

    }
    $count = count($data);
    if($count>=5000){
        die(json_encode(['status'=>0]));
    }else{
        die(json_encode(['status'=>1,'data'=>$data]));
    }
}

if ($op == 'new_since_data_all') {
    $uniacid = $_GPC['uniacid'];
    $store_id = $_GPC['store_id'];
    if($store_id){
        $condition = " and store_id in {$store_id}";
    }
    $now = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $before = strtotime($_GPC['stime']);
    $since_store = pdo_fetchall("select id,storename from cm_tg_store WHERE `uniacid` = :uniacid", array(':uniacid' => $item['uniacid']));
    $data = pdo_fetchall("SELECT *,sum(pay_num) as `all_pay_num`,sum(pay_price) as `all_pay_price` FROM cm_tg_new_since_data_info WHERE uniacid={$uniacid} and addtime>={$before} and addtime<={$now} {$condition} GROUP BY g_id,g_spec");
    $count = count($data);
    if($count>=5000){
        die(json_encode(['status'=>0]));
    }else{
        die(json_encode(['status'=>1,'data'=>$data]));
    }
}

if ($op == 'since_data_info') {
    $uniacid = $_GPC['uniacid'];
    $store_id = $_GPC['store_id'];
    $now = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $before = strtotime($_GPC['stime']);
    $day7_data = pdo_fetchall('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,store_id,g_id from ' . tablename('tg_since_data_info')
        . " where uniacid = :uniacid and `addtime` >= {$before} AND `addtime` <= {$now} and store_id={$store_id} group by g_id", array(':uniacid' => $uniacid));
    foreach ($day7_data as $key=>$value){
        $store = pdo_fetch("select * from cm_tg_goods WHERE id={$value['g_id']}");
        $day7_data[$key]['gname'] = $store['gname'];
    }
    die(json_encode($day7_data));
}

/*
 * 商城转化率
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=sczhl_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if ($op == 'sczhl_data') {
    $uniacid = $_GPC['uniacid'];
    $etime = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $stime = strtotime($_GPC['stime']);
    $cle = $etime - $stime; //得出时间戳差值
    $d = $cle / 3600 / 24;
    $day7_data = pdo_fetch('select sum(order_total) as order_total,sum(order_pay) as order_pay,sum(order_refund) as order_refund,sum(group_total) as group_total,sum(group_success) as group_success,sum(group_fail) as group_fail,sum(pay_price) as pay_price,sum(refund_price) as refund_price,sum(success_price) as success_price,sum(customer_price) as customer_price,sum(add_fans) as add_fans,sum(pv) as pv,sum(uv) as uv,sum(order_buy_openid) as order_buy_openid,sum(order_pay_openid) as order_pay_openid,sum(order_success_openid) as order_success_openid,sum(order_success_num) as order_success_num,sum(order_buy_price) as order_buy_price from '
        . tablename('tg_data_base') . " where uniacid = :uniacid and `addtime` >= {$stime} AND `addtime` <= {$etime} ", array(':uniacid' => $uniacid));
    if ($d > 0) {

        $day7_data['customer_price'] = round($day7_data['customer_price'] / $d, 2);
    } else {

        $day7_data['customer_price'] = round($day7_data['customer_price'], 2);
    }

//    //下单人
//    $order_buy = pdo_fetchall("select openid from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `createtime` >= {$stime} AND `createtime` <= {$etime} group by openid", array(':uniacid' => $uniacid));
//    $day7_data['order_buy_openid'] = count($order_buy);
//    //下单金额
//    $order_buy_price = pdo_fetch("select sum(pay_price) as total_price from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `createtime` >= {$stime} AND `createtime` <= {$etime} ", array(':uniacid' => $uniacid));
//    $day7_data['order_buy_price'] = round($order_buy_price['total_price'], 2);
//    //购买人
//    $order_pay = pdo_fetchall("select openid from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `ptime` >= {$stime} AND `ptime` <= {$etime} group by openid", array(':uniacid' => $uniacid));
//    $day7_data['order_pay_openid'] = count($order_pay);
//    //成功购买人
//    $order_success = pdo_fetchall("select openid from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and status in (2,3,8) and `ptime` >= {$stime} AND `ptime` <= {$etime} group by openid", array(':uniacid' => $uniacid));
//    $day7_data['order_success_openid'] = count($order_success);
//    //成功订单数
//    $order_success_num = pdo_fetch("select count(openid) as total from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and status in (2,3,8) and `ptime` >= {$stime} AND `ptime` <= {$etime} ", array(':uniacid' => $uniacid));
//    $day7_data['order_success_num'] = $order_success_num['total'];
    $list = array();
    $list['d'] = $d;
    $list['data'] = $day7_data;
    die(json_encode($list));
}

/*
 * 商城指标趋势
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=order_count_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if ($op == 'order_count_data') {
    $uniacid = $_GPC['uniacid'];
    $etime = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $stime = strtotime($_GPC['stime']);
    $cle = $etime - $stime; //得出时间戳差值
    /*
	$alist=array();
	for($i=0;$i<$cle;$i++)
	{
		$ntime=strtotime($_GPC['stime'])+86400*$i;
		$d_list=pdo_fetch('select * from '.tablename('tg_data_base')."where uniacid=:uniacid and date_format(from_UNIXTIME(`addtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$ntime}),'%Y-%m-%d') ",array(':uniacid'=>$uniacid));
		$alist[$i]['add_fans']=intval($d_list['add_fans']);
		$alist[$i]['addtime']=date('Y-m-d',$item['addtime']);intval($d_list['addtime']);
		$alist[$i]['customer_price']=round($d_list['customer_price'],2);
		$alist[$i]['group_fail']=intval($d_list['group_fail']);
		$alist[$i]['group_success']=intval($d_list['group_success']);
		$alist[$i]['group_total']=intval($d_list['group_total']);
		$alist[$i]['order_pay']=intval($d_list['order_pay']);
		$alist[$i]['order_receive']=intval($d_list['order_receive']);
		$alist[$i]['order_refund']=intval($d_list['order_refund']);
		$alist[$i]['order_total']=intval($d_list['order_total']);
		$alist[$i]['pay_price']=round($d_list['pay_price'],2);
		$alist[$i]['pv']=intval($d_list['pv']);
		$alist[$i]['refund_price']=round($d_list['refund_price'],2);
		$alist[$i]['success_price']=round($d_list['success_price'],2);
		$alist[$i]['uv']=intval($d_list['uv']);
		//已签收
		$order_receive=pdo_fetchall("select openid from cm_tg_order where uniacid=:uniacid and status=3 and mobile<>'虚拟' and date_format(from_UNIXTIME(`createtime`),'%Y-%m-%d') = date_format(from_UNIXTIME({$ntime}),'%Y-%m-%d') ",array(':uniacid'=>$uniacid));
		$alist[$i]['order_receive']=count($order_receive);

	}*/

    $day7_data = pdo_fetchall('select * from ' . tablename('tg_data_base') . " where uniacid = :uniacid and `addtime` >= {$stime} AND `addtime` <= {$etime} order by addtime asc ", array(':uniacid' => $uniacid));

    foreach ($day7_data as &$item) {

        $newdate = date("Y-m-d", $item['addtime']);
        $sdate = strtotime($newdate);
        $edate = strtotime($newdate . " +1 day");

        //下单金额
        $order_price = pdo_fetch("select sum(pay_price) as total_price from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `createtime` >= {$sdate} AND `createtime` <= {$edate} ", array(':uniacid' => $uniacid));
        $item['order_price'] = round($order_price['total_price'], 2);
        //已签收
        $order_receive = pdo_fetchall("select openid from cm_tg_order where uniacid = :uniacid and status = 3 and mobile <> '虚拟' and `createtime` >= {$sdate} AND `createtime` <= {$edate} ", array(':uniacid' => $uniacid));
        $item['order_receive'] = count($order_receive);
        $item['addtime'] = date('Y-m-d', $item['addtime']);
        unset($item);
    }

    $list = array();
    $list['data'] = $day7_data;
    die(json_encode($list));
}
/*
 * 商城访问趋势
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=scgk_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if ($op == 'scgk_data') {
    $uniacid = $_GPC['uniacid'];
    $etime = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $stime = strtotime($_GPC['stime']);
    $cle = $etime - $stime; //得出时间戳差值

    $day7_data = pdo_fetchall('select pv,uv,addtime from ' . tablename('tg_data_base') . " where uniacid = :uniacid and `addtime` >= {$stime} AND `addtime` <= {$etime} order by addtime asc", array(':uniacid' => $uniacid));

    foreach ($day7_data as &$item) {
        $item['addtime'] = date('Y-m-d', $item['addtime']);
        unset($item);
    }

    $list = array();
    $list['data'] = $day7_data;
    die(json_encode($list));
}
/*
 * 商城转化率统计
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=sczhl_count_data&uniacid=259&stime=2017-03-03&etime=2017-03-11
*/
if ($op == 'sczhl_count_data') {
    $uniacid = $_GPC['uniacid'];
    $etime = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $stime = strtotime($_GPC['stime']);
    $cle = $etime - $stime; //得出时间戳差值

    $day7_data = pdo_fetchall('select id, pv,uv,addtime from ' . tablename('tg_data_base') . " where uniacid = :uniacid and `addtime` >= {$stime} AND `addtime` <= {$etime} order by addtime asc ", array(':uniacid' => $uniacid));
    foreach ($day7_data as &$item) {

        $newdate = date("Y-m-d", $item['addtime']);
        $sdate = strtotime($newdate);
        $edate = strtotime($newdate . " +1 day");

        //成功购买人
        $order_success = pdo_fetchall("select openid from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and status in (2,3,8) and `ptime` >= {$sdate} AND `ptime` <= {$edate} group by openid", array(':uniacid' => $uniacid));
        $item['order_success_openid'] = count($order_success);;

        //下单人
        $order_buy = pdo_fetchall("select openid from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `createtime` >= {$sdate} AND `createtime` <= {$edate} group by openid", array(':uniacid' => $uniacid));
        $item['order_buy_openid'] = count($order_buy);

        //购买人
        $order_pay = pdo_fetchall("select openid from cm_tg_order where uniacid = :uniacid and mobile <> '虚拟' and `ptime` >= {$sdate} AND `ptime` <= {$edate} group by openid", array(':uniacid' => $uniacid));
        $item['order_pay_openid'] = count($order_pay);

        $item['sczhl'] = ($item['order_success_openid'] / $item['uv']) * 100;//商城转化率
        $item['xdzhl'] = ($item['order_buy_openid'] / $item['uv']) * 100;//访问到下单转化率
        $item['fkzhl'] = ($item['order_pay_openid'] / $item['order_buy_openid']) * 100;//下单到付款转化率
        $item['cgzhl'] = ($item['order_success_openid'] / $item['order_pay_openid']) * 100;//下单到成功转化率
        $item['addtime'] = date('Y-m-d', $item['addtime']);
        unset($item);
    }

    $list = array();
    $list['data'] = $day7_data;
    die(json_encode($list));
}
/*
 * 用户区域分布
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=order_area_data&uniacid=259&keyword=county
 * keyword:province 省  city  市  county 县
*/
if ($op == 'order_area_data') {
    $uniacid = $_GPC['uniacid'];
    $keyword = $_GPC['keyword'];
    $total = 0;
    if ($keyword == 'province') {
        $day7_data = pdo_fetchall('SELECT count(id) AS num,province AS area FROM `cm_tg_address` WHERE uniacid=:uniacid AND province<>"" GROUP BY province', array(':uniacid' => $uniacid));

    }
    if ($keyword == 'city') {
        $day7_data = pdo_fetchall('SELECT count(id) AS num,city AS area FROM `cm_tg_address` WHERE uniacid=:uniacid AND city<>"" GROUP BY city', array(':uniacid' => $uniacid));

    }
    if ($keyword == 'county') {
        $day7_data = pdo_fetchall('SELECT count(id) AS num,county AS area FROM `cm_tg_address` WHERE uniacid=:uniacid AND county<>"" GROUP BY county', array(':uniacid' => $uniacid));

    }
    foreach ($day7_data as &$item) {
        $total += $item['num'];
        unset($item);
    }

    $list = array();
    $list['data'] = $day7_data;
    $list['total'] = $total;
    die(json_encode($list));
}
/*
 * 核销员核销金额排行榜
 * 接口地址：http://www.lexiangpingou.cn/minapi.php?op=hexiao_data&uniacid=259

*/
if ($op == 'hexiao_data') {
    $uniacid = $_GPC['uniacid'];

    $day7_data = pdo_fetchall('SELECT veropenid,sum(price) AS total_price FROM cm_tg_order WHERE uniacid=:uniacid  AND veropenid<>"" AND mobile<>"虚拟" GROUP BY veropenid ORDER BY total_price DESC', array(':uniacid' => $uniacid));

    foreach ($day7_data as &$item) {
        $member = pdo_fetch('SELECT nickname,avatar,name FROM cm_tg_member WHERE openid=:openid AND uniacid=:uniacid', array(':openid' => $item['veropenid'], ':uniacid' => $uniacid));
        $item['nickname'] = $member['nickname'];
        $item['avatar'] = $member['avatar'];
        $item['name'] = $member['name'];
        unset($item);
    }
    $list = array();
    $list['data'] = $day7_data;

    die(json_encode($list));
}
/*
 * 多商户基本设置
 * URL minapi.php?op=merchant_base
 * 传入uniacid
 */
if ($op == 'merchant_base') {
    $uniacid = $_GPC['uniacid'];
    $data = array();
    $data['regbg'] = $_GPC['regbg'];
    $data['applycontent'] = $_GPC['applycontent'];
    $data['payrate'] = $_GPC['payrate'];
    $data['merchant_role'] = $_GPC['merchant_role'];
    $data['merchant_pay_time'] = $_GPC['merchant_pay_time'];
    pdo_update('account_wechats', $data, array('uniacid' => $uniacid));
    die(json_encode(array('status' => 1)));
}

/*
 * 用户列表
 * URL minapi.php?op=user_list
 * uniacid
 */
/*
 * 商户申请状态
 * URL minapi.php?op=merchant_status
 * 传入 uniacid openid
 * 返回 status 1  成功 0 等待审核 -1  未申请 2审核不通过
 */
if ($op == 'merchant_status') {
    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $mer = pdo_fetch('select * from ' . tablename('tg_merchant') . ' where uniacid=:uniacid and openid=:openid', array(':uniacid' => $uniacid, ':openid' => $openid));
    if (empty($mer)) {
        $status = -1;
    } else {
        $status = $mer['status'];
    }
    die(json_encode(array('status' => $status)));
}
/*
 * 商户申请
 * URL minapi.php?op=merchant_add
 * 传入 uniacid userName:（用户名）,pwd:（密码）,sjName:（商家名）,address:（商家地址）
 * 返回 status 1  成功 0 失败
 */
if ($op == 'merchant_add') {
    load()->model('user');
    $uniacid = $_GPC['uniacid'];
    $data = array();
    $data['uniacid'] = $uniacid;
    $data['uname'] = $_GPC['userName'];
    $data['password'] = $_GPC['pwd'];
    $data['openid'] = $_GPC['openid'];
    $data['name'] = $_GPC['sjName'];
    $data['address'] = $_GPC['address'];
    $data['linkman_mobile'] = $_GPC['mobile'];
    $mobile = $_GPC['mobile'];
    $code = $_GPC['code'];
//    file_put_contents("aa.log", var_export(array('s' => "商户入口" , 'time' => TIMESTAMP), true).PHP_EOL, FILE_APPEND);
    if (!preg_match(REGULAR_USERNAME, $data['uname'])) {

        die(json_encode(array('status' => 0, 'message' => '必须输入用户名，格式为 3-15 位字符，可以包括汉字、字母（不区分大小写）、数字、下划线和句点。')));
    }
    if (user_check(array('username' => $data['uname']))) {

        die(json_encode(array('status' => 0, 'message' => '此用户名已被使用，请更换注册名称！')));
    }
    $ret = pdo_insert('tg_merchant', $data);
    $merchant_id = pdo_insertid();
    $user = array();
    $user['salt'] = random(8);
    $user['username'] = $data['uname'];
    $user['password'] = user_hash($data['password'], $user['salt']);
    $user['groupid'] = 1;
    $user['merchant_id'] = $merchant_id;
    $user['joinip'] = CLIENT_IP;
    $user['joindate'] = TIMESTAMP;
    $user['lastip'] = CLIENT_IP;
    $user['lastvisit'] = TIMESTAMP;
    if (empty($user['status'])) {
        $user['status'] = 2;
    }
    $result = pdo_insert('users', $user);
    $uid = pdo_insertid();
    $data['uid'] = $uid;
    $m = array();
    $m['uniacid'] = $uniacid;
    $m['uid'] = $uid;
    $m['type'] = 'lexiangpingou';
    $m['permission'] = 'all';
    $result = pdo_insert('users_permission', $m);
    pdo_insert('uni_account_users', array('uniacid' => $uniacid, 'uid' => $uid, 'role' => 'operator'));
    pdo_update('tg_merchant', array('uid' => $uid), array('id' => $merchant_id));
    pdo_insert('users_profile', array('uid' => $uid, 'createtime' => TIMESTAMP, 'realname' => $data['uname'], 'mobile' => $mobile));
//    file_put_contents("aa.log", var_export(array('s' => "商户结束" , 'time' => TIMESTAMP), true).PHP_EOL, FILE_APPEND);
    die(json_encode(array('status' => 1, 'message' => '申请成功，稍后客服会联系您！')));
}
/*
 * 接口名字     up_leave
 * 接口作用     等级编辑
 * 接口URL     member/parms?op=up_leave
 * 传入参数     id name rights money
 * 回传结果     message  返回注册成功与否信息
 */

if ($op == "up_leave") {
    global $_W, $_GPC;
    //获取传过来的id和获取传过来的id和unicaid
    $uniacid = $_GPC['uniacid'];
    $id = $_GPC["_id"];//id
    $name = $_GPC["_vip"];//会员名称
    $right = $_GPC["_off"];//享受折扣
    $money = $_GPC["_upgrade"];//升级条件
    $img = $_GPC["_img"];//会员图片
    //更新这个表里面的数据
    file_put_contents("payresult.log", var_export($unicaid . "---" . $id . "---" . $name . "---" . $right . "---" . $money, true) . PHP_EOL, FILE_APPEND);
    $res = pdo_update('tg_member_leave', array('name' => $name, 'rights' => $right, 'uniacid' => $uniacid, 'money' => $money, 'img' => $img), array('id' => $id));
    if (!empty($res)) {
        die(json_encode(array('status' => 1)));
    } else {
        die(json_encode(array('status' => 0)));
    }
}
/*
 * 接口名字     insert_leave
 * 接口作用     等级添加
 * 接口URL     member/parms?op=insert_leave
 * 传入参数     name rights money
 * 回传结果     message  返回注册成功与否信息
 */
if ($op == "insert_leave") {
    global $_W, $_GPC;
    //获取传过来的id和获取传过来的id和unicaid
    $uniacid = $_GPC['uniacid'];
    //$id = $_GPC["_id"];//id
    $name = $_GPC["_vip"];//会员名称
    $right = $_GPC["_off"];//享受折扣
    $money = $_GPC["_upgrade"];//升级条件
    $img = $_GPC["_img"];//会员图片
    //插入这个表里面的数据
    file_put_contents("payresult.log", var_export($unicaid . "---" . $id . "---" . $name . "---" . $right . "---" . $money, true) . PHP_EOL, FILE_APPEND);
    $res = pdo_insert('tg_member_leave', array('name' => $name, 'rights' => $right, 'uniacid' => $uniacid, 'money' => $money, 'img' => $img));
    //$res = pdo_update('tg_member_leave',array('name'=>$name,'rights'=>$right,'uniacid'=>$unicaid,'money'=>$money),array('id'=>$id));
    if (!empty($res)) {
        die(json_encode(array('status' => 1)));
    } else {
        die(json_encode(array('status' => 0)));
    }
}
/*
 * 接口名字     del_leave
 * 接口作用     等级删除
 * 接口URL     member/parms?op=del_leave
 * 传入参数     id
 * 回传结果     message  返回注册成功与否信息
 */
if ($op == "del_leave") {
    $id = $_GPC["id"];
    //todo 修改数据库member表中的所有关联数据
    $res = pdo_delete('tg_member_leave', array('id' => $id));
    if ($res) {
        die(json_encode(array('status' => 1)));
    } else {
        die(json_encode(array('status' => 0)));
    }
}


/*
 * 获取手机验证码
 */
if ($op == 'code') {
//    file_put_contents("aa.log", var_export(array('s' => "入口1" , 'time' => TIMESTAMP), true).PHP_EOL, FILE_APPEND);
    $username = trim($_GPC['mobile']);
    $uniacid = $_GPC['uniacid'];
    pdo_query('DELETE FROM ' . tablename('users_failed_login') . ' WHERE lastupdate < :timestamp', array(':timestamp' => TIMESTAMP - 1800));
    $failed = pdo_get('users_failed_login', array('username' => $username, 'ip' => CLIENT_IP));
    if ($failed['count'] >= 3) {
        $result = '30分钟内获取验证码次数超过3次，请在30分钟后重试';
        die(json_encode(array('result' => $result)));
    }
    session_start();
    if ($_GPC['retrieve'] == 1) {
        $num = pdo_fetch("SELECT smsnum FROM " . tablename('account_wechats') . " WHERE uniacid = '{$uniacid}'");
        $num = $num['smsnum'];
        if ($num <= 0) {
            $result = '抱歉，短信发送上限，请联系管理员';
            die(json_encode(array('result' => $result)));
        }

        header('content-type:text/html;charset=utf-8');

        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $_SESSION['smscode'] = mt_rand(100000, 999999);

//        $returnstatus = array('status' => 1, 'code' => $_SESSION['smscode']);
//        die(json_encode($returnstatus));
//        file_put_contents("aa.log", var_export(array('s' => "入口" , 'time' => TIMESTAMP), true).PHP_EOL, FILE_APPEND);
        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $_GPC['mobile'], //接受短信的用户手机号码
            'tpl_id' => '18930', //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => '#code#=' . $_SESSION['smscode'] //您设置的模板变量，根据实际情况修改
        );

        $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信

        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //状态为0，说明短信发送成功
                //添加一次获取验证码信息
                if (empty($failed)) {
                    pdo_insert('users_failed_login', array('ip' => CLIENT_IP, 'username' => $username, 'count' => '1', 'lastupdate' => TIMESTAMP));
                } else {
                    pdo_update('users_failed_login', array('count' => $failed['count'] + 1, 'lastupdate' => TIMESTAMP), array('id' => $failed['id']));
                }
                $returnstatus = array('status' => 1, 'code' => $_SESSION['smscode']);
                pdo_update('account_wechats', array('smsnum' => $num - 1), array('uniacid' => $uniacid));
                echo json_encode($returnstatus);
                exit();
            } else {
                $returnstatus = array('status' => 0, 'reason' => $result['reason']);
                //状态非0，说明失败
                $msg = $result['reason'];
                echo json_encode($returnstatus);
                exit();
            }
        } else {
            //返回内容异常，以下可根据业务逻辑自行修改
            echo 3;
            exit();
        }
    }
    if ($_GPC['retrieve'] == 2) {
        if ($_GPC['num'] == $_SESSION['smscode']) {
            echo 1;
            exit();
        } else {
            echo $_SESSION['smscode'];
            exit();
        }
    }
//    file_put_contents("aa.log", var_export(array('s' => "结束" , 'time' => TIMESTAMP), true).PHP_EOL, FILE_APPEND);
    die(json_encode(array('data' => $returnstatus)));
}

/**
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function juhecurl($url, $params = false, $ispost = 0)
{
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    $response = curl_exec($ch);
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}