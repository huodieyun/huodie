<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * 商品详情控制器
 */

defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
$id = $_GPC['id'];
if ($_W['openid']) {
    puv($_W['openid'], $id);
}
$pagetitle = '商品详情';
$_SESSION['type'] = NULL;
$_SESSION['freight'] = NULL;
$_SESSION['tuan_id'] = NULL;

$_W['storeid'] = $_GPC['storeid'];

$op = $_GPC["op"];
if($op == 'ajaxlist'){
    $page = $_GPC['page'];

    $pagesize = 30;
    $id = $_GPC['id'];
    // 分享团数据
    $goods = goods_get_by_params("id = {$id}");
    if (empty($goods['is_share'])) {
        if ($this->module['config']['sharestatus'] != 2) {
            $thistuan = pdo_fetchall("select * from " . tablename('tg_group') . " where uniacid='{$_W['uniacid']}' and goodsid = '{$id}' and groupstatus=3 and lacknum<>neednum  order by id asc limit ".($page-1)*$pagesize.','.$page*$pagesize);
            if (!empty($thistuan)) {
                foreach ($thistuan as $key => $value) {
                    $tuan_first_order = order_get_by_params(" tuan_id = '{$value['groupnumber']}' and tuan_first=1 ");
                    $userinfo = member_get_by_params(" openid = '{$tuan_first_order['openid']}'");
                    $thistuan[$key]['avatar'] = $userinfo['avatar'];
                    $thistuan[$key]['nickname'] = $userinfo['nickname'];
                    $thistuan[$key]['nownum'] = $value['neednum'] - $value['lacknum'];
                }
            }
        }
    }
    die(json_encode(array('list'=>$thistuan)));
}

if ($op == "up_num") {
    $play__url = $_GPC["id"];
    $num = pdo_fetch("select * from " . tablename("account_wechats") . " where uniacid=:uniacid", array(":uniacid" => $_W["uniacid"]));
    if ($num["play_num"] == 0) {
        $res = array("status" => "error", "data" => "已没有播放次数");
        die(json_encode($res));
    }
    $res3["play_num"] = $num["play_num"] - 1;
    pdo_update("account_wechats", $res3, array("uniacid" => $_W["uniacid"]));
    $res = pdo_fetch("SELECT * FROM " . tablename("tg_goods_video") . " WHERE id =:play__url", array(":play__url" => $play__url));
    $res2["play_num"] = intval($res["play_num"]) + 1;
    pdo_update("tg_goods_video", $res2, array("id" => $play__url));
    $res = array("status" => "success", "data" => "成功");
    die(json_encode($res));

}
//全民兼职  开始
$tid = 8167;
//权限控制
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
//message($acct['tpl']);
$checkhomeimg = checkfunc(8169);
if ($checkhomeimg['status'] == 0) {
    $acct['homeimg'] = '';
}
$tpl = 'goods/goods_detail';
if ($acct['tpl'] == '8146') {
    $tpl1 = 'goods/goods_detail';
}
if ($acct['tpl'] == '8147') {
    $tpl1 = 'goods/goods_detail_2';
}
if ($acct['tpl'] == '8172') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8174') {
    $tpl1 = 'goods/goods_detail';
}
if ($acct['tpl'] == '8175') {
    $tpl1 = 'goods/goods_detail_2';
}
if ($acct['tpl'] == '8196') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8198') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8199') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8200') {
    $tpl1 = 'goods/goods_detail_11';
}
if ($acct['tpl'] == '8201') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8202') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8203') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8204') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8205') {
    $tpl1 = 'goods/goods_detail_2_1';
}
if ($acct['tpl'] == '8206') {
    $tpl1 = 'goods/goods_detail_11';
}
if ($acct['tpl'] == '8207') {
    $tpl1 = 'goods/goods_detail_3';
}
if ($acct['tpl'] == '8208') {
    $tpl1 = 'goods/goods_detail_3_1';//单买详情页
    $tpl = 'goods/goods_detail_12';//随意团详情页
}
if ($acct['tpl'] == '8209') {
    $tpl1 = 'goods/goods_detail_3';//单买详情页
    $tpl = 'goods/goods_detail';//随意团详情页
}
if ($acct['tpl'] == '8210') {
    $tpl1 = 'goods/goods_detail_3';
    $tpl = 'goods/goods_detail8';//随意团详情页
}
if ($acct['tpl'] == '8211') {
    $tpl1 = 'goods/goods_detail_3_1';//圣诞专版
    $tpl = 'goods/goods_detail_12';//随意团详情页
}
if ($acct['tpl'] == '8213') {
    $tpl1 = 'goods/goods_detail_3_1';//仿饿了么
    $tpl = 'goods/goods_detail_12';
}
wl_load()->model('member');
$member = member_get_by_params("from_user='" . $_W['openid'] . "' and uniacid='" . $_W['uniacid'] . "'");
$tourl = app_url('goods/detail') . "&id=" . $id;


if ($checkfunction['status'] && $member['enable'] == 1) {
    $tourl = $tourl = app_url('goods/detail') . "&id=" . $id . "&mid=" . $member['id'];
}
//message($checkfunction['status']."kkk".intval($_GPC['sharenum'])."dddd".$member['parentid']);
if ($checkfunction['status'] && intval($_GPC['sharenum']) != 0 && $member['parentid'] == -1) {
    $data = array('parentid' => $_GPC['sharenum']);
    $params = array('from_user' => $_W['openid']);
    member_update_by_params($data, $params);

}
$member = member_get_by_params("from_user='" . $_W['openid'] . "'");
if ($checkfunction['status'] == 0 || $member['parentid'] == -1) {
    $data = array('parentid' => 99);
    $params = array('from_user' => $_W['openid']);
    member_update_by_params($data, $params);
}
//全民兼职 结束

//评价统计
$totalnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and check_status = 1 ");
$totaliszhuijianum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and iszhuijia = 1 and check_status = 1 ");
$totalishaoyongnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and ishaoyong = 1 and check_status = 1 ");
$totaliszhengpinnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and iszhengpin = 1 and check_status = 1 ");
$totalispianyinum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and ispianyi = 1 and check_status = 1 ");
$totaliswuliunum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and iswuliu = 1 and check_status = 1 ");
$totaliszhiliangnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and iszhiliang = 1 and check_status = 1 ");
$totalisfuwunum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and isfuwu = 1 and check_status = 1 ");
$totalisqitanum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_judgment') . " where gid ='{$id}' and isqita = 1 and check_status = 1 ");
if ($acct['tpl'] == 8209) {
    $tourl .= "&storeid=" . $_GPC['storeid'];
}
//评价统计
session_start();
if (!empty($_GPC['id'])) {
    $_SESSION['goodsid'] = $_GPC['id'];
}
load()->model('mc');
if (!empty($id)) {
    //商品
    //拼团玩法
    $setting = pdo_fetch("select `value` from " .tablename('tg_setting') ." where uniacid = {$_W['uniacid']} and `key` = 'kaiguan' ");
    $setting = unserialize($setting['value']);
    $goods = goods_get_by_params("id = {$id}");
    $group_level = unserialize($goods['group_level']);
    $wholesale_level = unserialize($goods['wholesale_level']);
    $goods['gdesc'] = str_replace("http://www.lexiangpingou.cn/attachment/", "https://res.lexiangpingou.cn/", $goods['gdesc']);
    $goods['goodsdesc'] = str_replace("\n", "", $goods['goodsdesc']);

    if ($goods['selltype'] == 4) {

        $gprice = $group_level[0]['groupprice'];
        foreach ($group_level as $item) {
            if ($gprice > $item['groupprice']) {
                $gprice = $item['groupprice'];
            }
        }
        $goods['gprice'] = $gprice;
    }

    //message($goods['goodsdesc']);
    //多商户自营

    if ($acct['tpl'] == '8206' && $goods["merchantid"] == 0) {
        $goods['gname'] = "<span style='color: #fff;background: #e4393c;padding: 0px 4px;display: inline-block;border-radius:5px;margin-right: 3px;font-size: 12px'>自营</span>" . $goods['gname'];
    }


    if ($goods['spike'] == 1 || $goods['spikeT'] == 1) {
        if (TIMESTAMP >= $goods['spike_start'] && TIMESTAMP <= $goods['spike_end']) {

            $goods['spike_type'] = 1;
            if ($goods['gnum'] <= 0) {
                $goods['spike_type'] = 3;
            }
        }
        if (TIMESTAMP < $goods['spike_start']) {
            $goods['spike_type'] = 0;
        }
        if (TIMESTAMP > $goods['spike_end']) {
            $goods['spike_type'] = 2;
        }
    }

    $num = pdo_fetch("SELECT * FROM " . tablename("account_wechats") . " WHERE uniacid=:uniacid", array(":uniacid" => $_W["uniacid"]));
    if ($num["play_num"] == 0) {
        unset($goods["index_video"]);
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $goods["index_video"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 200);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_NOBODY, FALSE);
#curl_setopt( $ch, CURLOPT_POSTFIELDS, "username=".$username."&password=".$password );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            $goods["index_video"] = "";

        } else {
            $video_res = pdo_fetch("SELECT * FROM " . tablename("tg_goods_video") . " WHERE media_url LIKE :media_url", array(":media_url" => '%' . $paly_url . '%'));
            if ($video_res) {
                $video_id = $video_res["id"];
            } else {
                $video_id = "";
            }
        }
    }
    $goods['selltype'] = intval($goods['selltype']);
//	$video = pdo_fetch("select * from ".tablename("tg_goods_video")." where goods_id = :id",array("id"=>$id));
    if ($goods['selltype'] == 0 || $goods['selltype'] == 1) {
        //这里是随意团和单买的情况下计算一下是否享受会员权益
        if ($goods['discount'] == 1) {
            //证明是计算打折后的价钱 查询会员的等级和折扣详情
            $level = pdo_fetch("select level from " . tablename("tg_member") . " where uniacid = :uniacid and openid = :openid", array(":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"]));
            if (!empty($level) || $level == "0" || $level == 0) {
                //获取会员的等级 匹配会员等级享受的折扣
                $rights = pdo_fetch("select rights from " . tablename("tg_member_leave") . " where uniacid = :uniacid and id = :id", array(":uniacid" => $_W["uniacid"], ":id" => $level['level']));
                if (!empty($rights) || $rights != 0 || $rights) {
                    //计算价钱
                    $goods['gprice'] = $goods['gprice'] * $rights['rights'] / 10;
                    $goods['oprice'] = $goods['oprice'] * $rights['rights'] / 10;
                }
            }
        }
    }
    $merchant_id = intval($goods["merchantid"]);
    // $merchant_id = 0;
    //查询商家配备客服
    $res = pdo_fetchall("select * from " . tablename("messikefu_cservice") . " where  merchant_id=" . $merchant_id . " and weid=" . $_W['uniacid']);
    //file_put_contents(TG_DATA."rizi.log", var_export("1", true).PHP_EOL, FILE_APPEND);
    if (empty($res)) {
        $url_kefu = "";
    } else {
        $rand = count($res) - 1;
        $rand = rand(0, $rand);
        $url_kefu = app_url('chat/chat', array('op' => 'doMobileChat', "toopenid" => $res[$rand]["content"], "id" => $goods['id']));
    }
    //file_put_contents(TG_DATA."rizi.log", var_export("2", true).PHP_EOL, FILE_APPEND);
    $result = array('statustype' => 0);
    $merchant = pdo_fetch("select * from" . tablename('tg_merchant') . "where id={$merchant_id}");
    $counta = pdo_fetch("select COUNT(*) as total from" . tablename('tg_goods') . "where merchantid={$merchant_id} and shenhe=0 and isshow=1");
    if ($goods['selltype'] == 6) {
        $lists = pdo_fetch("select id from" . tablename('tg_order') . "where openid = '{$_W['openid']}' and uniacid = '{$_W['uniacid']}' and status in (1,2,3,8,10,7)  order by id asc limit 1");
        if (!empty($lists)) {
            $result = array('statustype' => 1);
        }
    }
    //file_put_contents(TG_DATA."rizi.log", var_export("3", true).PHP_EOL, FILE_APPEND);
    if ($goods['isshow'] == 2) {
        $tip = '该商品已下架';
        echo "<script>alert('" . $tip . "');location.href='" . app_url('goods/list') . "';</script>";

        exit;
    }
    if (empty($goods['unit'])) {
        $goods['unit'] = '件';
    }
    $subscribe = $goods['subscribe'];
    //阶梯团
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

    /*判断购买次数*/
//    $data = order_get_list(array('g_id' => $goods['id'], 'openid' => $_W['openid'], 'status' => '1,2,3,6,8'));
//    $times = $data['total'];
    $times = pdo_fetchcolumn("select sum(gnum) from " . tablename('tg_order') . " where uniacid = {$_W['uniacid']} and g_id = {$goods['id']} and openid = '{$_W['openid']}' and status in (1,2,3,6,8) ");
    if (!$times) {
        $times = pdo_fetchcolumn("select sum(num) from " . tablename('tg_collect') . " where uniacid = {$_W['uniacid']} and sid = {$goods['id']} and openid = '{$_W['openid']}' ");
    }
    //商家
    //$merchant = merchant_get_by_params("id = {$goods['merchantid']}");

    //规格及规格项
    $allspecs = pdo_fetchall("select * from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $id));
    foreach ($allspecs as &$s) {
        $s['items'] = pdo_fetchall("select * from " . tablename('tg_spec_item') . " where  `show`=1 and specid=:specid order by displayorder asc", array(":specid" => $s['id']));
    }
    unset($s);
    $options = pdo_fetchall("select * from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $id));
    $specs = array();
    if (count($options) > 0) {
        $specitemids = explode("_", $options[0]['specs']);
        foreach ($specitemids as $itemid) {
            foreach ($allspecs as $ss) {
                $items = $ss['items'];
                foreach ($items as $it) {
                    if ($it['id'] == $itemid) {
                        $specs[] = $ss;
                        break;
                    }
                }
            }
        }
    }
    foreach ($specs[0]['items'] as $skey => $svalue) {
        if ($svalue['thumb'] == "") {
            $specs[0]['items'][$skey]['thumb'] = $goods['share_image'];
        } else {
            $specs[0]['items'][$skey]['thumb'] = tomedia($svalue['thumb']);
        }
    }
    if ($goods['selltype'] == 0 || $goods['selltype'] == 1) {
        //这里是随意团和单买的情况下计算一下是否享受会员权益

        if ($goods['discount'] == 1) {

            //证明是计算打折后的价钱 查询会员的等级和折扣详情
            $level = pdo_fetch("select level from " . tablename("tg_member") . " where uniacid = :uniacid and openid = :openid", array(":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"]));
            if (!empty($level["level"]) && $level["level"] != "0" && $level["level"] != 0) {
                //获取会员的等级 匹配会员等级享受的折扣
                $rights = pdo_fetch("select rights from " . tablename("tg_member_leave") . " where uniacid = :uniacid and id = :id", array(":uniacid" => $_W["uniacid"], ":id" => $level['level']));

                if (!empty($rights) || $rights['rights'] != 0) {

                    //计算价钱
                    //	die(json_encode($options));
                    foreach ($options as &$itemss) {
                        $itemss['marketprice'] = $itemss['marketprice'] * $rights['rights'] / 10;
                        $itemss['productprice'] = $itemss['productprice'] * $rights['rights'] / 10;
                    }
                }
            }

        }
    }

    //得到图集
    $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$id}'");
    foreach ($advs as &$adv) {
        if (substr($adv['link'], 0, 4) != 'http') {
            $adv['link'] = "http://" . $adv['link'];
        }
    }
    unset($adv);

    $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$id}' ");
    //门店信息
    $storesids = explode(",", $goods['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
        }
    }

    $page = $_GPC['page'];
    if(!$page){
        $page = 1;
    }
    $pagesize = 30;

    // 分享团数据
    if (empty($goods['is_share'])) {
        if ($this->module['config']['sharestatus'] != 2) {
            $thistuan = pdo_fetchall("select * from " . tablename('tg_group') . " where uniacid='{$_W['uniacid']}' and goodsid = '{$id}' and groupstatus=3 and lacknum<>neednum  order by id asc limit ".($page-1)*$pagesize.','.$page*$pagesize);
            if (!empty($thistuan)) {
                foreach ($thistuan as $key => $value) {
                    $tuan_first_order = order_get_by_params(" tuan_id = '{$value['groupnumber']}' and tuan_first=1 ");
                    $userinfo = member_get_by_params(" openid = '{$tuan_first_order['openid']}'");
                    $thistuan[$key]['avatar'] = $userinfo['avatar'];
                    $thistuan[$key]['nickname'] = $userinfo['nickname'];
                    $thistuan[$key]['nownum'] = $value['neednum'] - $value['lacknum'];
                    if($goods['selltype'] == 2){
                        $addid = $tuan_first_order['addressid'];
                        $adress_fee_tuan = pdo_fetch("select * from " . tablename('tg_address') . "where id = '{$addid}'  ");
                        $thistuan[$key]['address'] = $adress_fee_tuan;
                    }
                }
            }
        }
    }
    $gname = $goods['gname'];
    if ($acct['tpl'] == '8206' && $goods["merchantid"] == 0) {
        $gname = str_replace("<span style='color: #fff;background: #e4393c;padding: 0px 4px;display: inline-block;border-radius:5px;margin-right: 3px;font-size: 12px'>自营</span>", "", $goods['gname']);
    }

    $config['share']['share_title'] = !empty($goods['share_title']) ? $goods['share_title'] : $gname;
    $config['share']['share_desc'] = !empty($goods['share_desc']) ? $goods['share_desc'] : $config['share']['share_desc'];
    $config['share']['share_image'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['gimg'];


    $list = pdo_fetchall("select openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time from " . tablename('tg_judgment') . " where gid = '{$id}' and check_status = 1 ORDER BY create_time desc LIMIT 0,2");
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . " where gid = '{$id}' and check_status = 1 ORDER BY create_time DESC ");
} else {
    $tip = '商品信息出错！';
    echo "<script>alert('" . $tip . "');location.href='" . app_url('goods/list') . "';</script>";

    exit;
}
if ($goods['is_sendcoupon'] == 1) {
    $coupon_id = $goods["coupon_id"];
    //查询优惠券详情
    $coupon_info = pdo_get("tg_coupon_template", array("id" => $coupon_id));
    $goods['coupon'] = $coupon_info;
}
if ($goods['one_group'] == 1) {
    $nowgroup = pdo_fetch("select * from " . tablename('tg_group') . " where uniacid=:uniacid and goodsid=:goodsid and groupstatus=3  order by id, lacknum limit 1 ", array(':uniacid' => $_W['uniacid'], ':goodsid' => $id));
    if (!empty($nowgroup)) {
        header("Location: " . app_url('order/group', array('tuan_id' => $nowgroup['groupnumber'])));
        exit;
    }
}
//message($goods['selltype']);
if ($goods['selltype'] == 0) {
    if ($goods['spike'] == 0) {
        include wl_template($tpl1);
    } else {
        include wl_template('goods/goods_miaosha');
    }
}
if ($goods['selltype'] == 1) {
    include wl_template($tpl);
}
if ($goods['selltype'] == 2) {
    include wl_template('goods/goods_lindetail');
}
if ($goods['selltype'] == 3) {
    include wl_template('goods/goods_detailjtt');
}
if ($goods['selltype'] == 4) {
    include wl_template('goods/goods_detailjtt2');
}
if ($goods['selltype'] == 5) {
    include wl_template('goods/goods_luck');
}
if ($goods["selltype"] == 10) {
    $kj_res = pdo_get("tg_kj_log", array("goodsid" => $id, "openid" => $_W["openid"], "uniacid" => $_W["uniacid"]));
    include wl_template('goods/goods_detail_kj');

}
if ($goods['selltype'] == 6) {
    $lists = pdo_fetch("select id from" . tablename('tg_order') . "where openid = '{$_W['openid']}' and uniacid = '{$_W['uniacid']}'");
    if (!empty($lists)) {
        $result = array('statustype' => 1);
    } else {
        $result = array('statustype' => 0);
    }
    include wl_template('goods/goods_detail_6');
}
if ($goods['selltype'] == 7) {
    //查询当前团ID
    if (empty($_GPC['tuan_id'])) {
        $nowgroup = pdo_fetch("select * from " . tablename('tg_group') . " where uniacid=:uniacid and goodsid=:goodsid and groupstatus=3 and neednum<>lacknum and selltype=7 order by id asc limit 1 ", array(':uniacid' => $_W['uniacid'], ':goodsid' => $id));

    } else {
        $nowgroup = pdo_fetch("select * from " . tablename('tg_group') . " where uniacid=:uniacid and goodsid=:goodsid and groupnumber=:groupnumber and selltype=7 order by id asc limit 1 ", array(':uniacid' => $_W['uniacid'], ':goodsid' => $id, ':groupnumber' => $_GPC['tuan_id']));

    }
    $config['share']['share_title'] = "【已" . $nowgroup['nownum'] . "人参团】" . $config['share']['share_title'];
    $checkbuy = 1;
    if ($goods['many_limit'] > 0) {
        $params_openid = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $goods['id']);
        $history_buy = pdo_fetch('select num from ' . tablename('tg_goods_openid') . ' where uniacid=:uniacid and g_id=:g_id and openid=:openid', $params_openid);
        if ($goods['many_limit'] <= $history_buy['num']) {
            $checkbuy = 0;
        }
    }
    $nownum = intval($nowgroup['nownum']);
    $maurl = app_url('order/orderconfirm', array('id' => $id));
    if (empty($nowgroup)) {
        $param_level = unserialize($goods['group_level']);
    } else {
        $first_tuan = pdo_fetch("select openid from" . tablename('tg_order') . ' where uniacid=:uniacid and tuan_id=:tuan_id and tuan_first=1', array(':uniacid' => $_W['uniacid'], ':tuan_id' => $nowgroup['groupnumber']));
        $first_member = pdo_fetch("select * from " . tablename('tg_member') . ' where uniacid=:uniacid and openid=:openid', array(':uniacid' => $_W['uniacid'], ':openid' => $first_tuan['openid']));
        $param_level = unserialize($nowgroup['group_level']);
        $maurl = app_url('order/orderconfirm', array('id' => $id, 'tuan_id' => $nowgroup['groupnumber']));
    }

    for ($i = 0; $i < count($param_level) - 1; $i++) {
        for ($j = 0; $j < count($param_level) - $i - 1; $j++) {
            if ($param_level[$j]['groupnum'] > $param_level[$j + 1]['groupnum']) {
                $temp = $param_level[$j];
                $param_level[$j] = $param_level[$j + 1];
                $param_level[$j + 1] = $temp;
            }
        }
    }

    include wl_template('goods/goods_detail_7');
}
if ($op == 'judgment_list') {
    $gid = $_GPC['gid'];
    $jud_list = pdo_fetchall("select openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time from " . tablename('tg_judgment') . " where gid = '{$gid}' and check_status = 1 ORDER BY create_time desc");
    $jud_total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . " where gid = '{$gid}' and check_status = 1 ORDER BY create_time DESC ");
    include wl_template('goods/goods_detail_judgment');
    exit();
}

exit();