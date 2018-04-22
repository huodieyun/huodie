<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * index.ctrl
 * index.ctrlddddd
 * 全部商品列表控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('adv');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('setting');
$intervals = setting_get_by_name('intervals');
if(!$intervals){
    $intervals = 3000;
}
$_SESSION['type'] = NULL;
global $_W, $_GPC;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$notice = pdo_fetchall("SELECT * FROM " . tablename('tg_notices') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 ORDER BY id DESC");
//全民兼职  开始

$tid = 8167;

$tid = 8167;//
$cubes = tgsetting_read('cube');
foreach ($cubes ? $cubes : array() as $k => $v) {
    if (empty($v['thumb']) || $v['on'] == 0) {
        unset($cubes[$k]);
    }
}
$advlist = pdo_fetchall("SELECT * FROM " . tablename('tg_banner') . " WHERE uniacid = '{$_W['uniacid']}' and enabled = 1 ORDER BY displayorder DESC");

//权限控制
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$miaoxianfunction = checkfunc(8169);
$banquanfunction = checkfunc(8170);
wl_load()->model('member');
$member = member_get_by_params("from_user='" . $_W['openid'] . "' and uniacid='" . $_W['uniacid'] . "'");
$acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
$checkhomeimg = checkfunc(8169);
if ($checkhomeimg['status'] == 0) {
    $acct['homeimg'] = '';
}


//sss

$tourl = app_url('goods/list');
if ($checkfunction['status'] && $member['enable'] == 1) {
    $tourl = app_url('goods/list') . "&mid=" . $member['id'];
}
if ($acct['tpl'] != '8146') {
    $tplfunction = checkfunc($acct['tpl']);
    if ($tplfunction['status'] == 0) {
        pdo_update('account_wechats', array('tpl' => 8146), array('uniacid' => $_W['uniacid']));
        $acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
    }
}

//ceshi

$tpl = 'goods/goods_list1';//默认拼团
$tpl1 = 'goods/goods_list2';//默认商团
if ($acct['tpl'] == '8146') {
    $tpl1 = 'goods/goods_list1';//蓝色单排
    $tpl = 'goods/goods_list1';
}
if ($acct['tpl'] == '8147') {
    $tpl1 = 'goods/goods_list1';
    $tpl = 'goods/goods_list2';//商团蓝色版
}
if ($acct['tpl'] == '8172') {
    $tpl1 = 'goods/goods_list1';
    $tpl = 'goods/goods_list3';//uu果业定制专署模板
}
if ($acct['tpl'] == '8174') {
    $tpl1 = 'goods/goods_list5';//拼团双栏模板
    $tpl = 'goods/goods_list5';
}
if ($acct['tpl'] == '8175') {
    $tpl1 = 'goods/goods_list5';
    $tpl = 'goods/goods_list4';//商团双栏模板

}
if ($acct['tpl'] == '8190') {
    $tpl1 = 'goods/goods_list6_keyword';//拼团红色单排模板
    $tpl = 'goods/goods_list6_keyword';

}
if ($acct['tpl'] == '8193') {
    $tpl1 = 'goods/goods_list7';//拼团红色双排模板
    $tpl = 'goods/goods_list7';

}
if ($acct['tpl'] == '8196') {
    $tpl1 = 'goods/goods_list8';//新雅侬定制专属模板
    $tpl = 'goods/goods_list8';

}
if ($acct['tpl'] == '8197') {
    $tpl1 = 'goods/goods_list7_keyword';//带搜索拼团红色双排
    $tpl = 'goods/goods_list7_keyword';

}
if ($acct['tpl'] == '8198') {
    $tpl1 = 'goods/goods_list9';//拼团，新雅农
    $tpl = 'goods/goods_list9';

}
if ($acct['tpl'] == '8199') {
    $tpl1 = 'goods/goods_list10';//单商户商团，二级分类
    $tpl = 'goods/goods_list10';

}
if ($acct['tpl'] == '8200') {
    $tpl1 = 'goods/goods_list11';//多商户商团,
    $tpl = 'goods/goods_list11';

}
if ($acct['tpl'] == '8201') {
    $tpl1 = 'goods/goods_list12';//拼点好，会员专区，秒杀
    $tpl = 'goods/goods_list12';

}
if ($acct['tpl'] == '8202') {
    $tpl1 = 'goods/goods_list13';//新义，会员专区，秒杀
    $tpl = 'goods/goods_list13';
}
if ($acct['tpl'] == '8203') {

    $tpl1 = 'goods/goods_list2_1_keyword';//商团红色单排
    $tpl = 'goods/goods_list2_1_keyword';
}
if ($acct['tpl'] == '8204') {
    $tpl1 = 'goods/goods_list7';//商团红色双排
    $tpl = 'goods/goods_list4_1';
}
if ($acct['tpl'] == '8205') {
    $tpl1 = 'goods/goods_list1';//吉之城 首页数量
    $tpl = 'goods/goods_list2_2';
}
if ($acct['tpl'] == '8206') {
    $tpl1 = 'goods/goods_list17';//新鲜人多商户商团,
    $tpl = 'goods/goods_list17';
}
if ($acct['tpl'] == '8207') {
    $tpl1 = 'goods/goods_list16';//单商户商团，二级分类 单排
    $tpl = 'goods/goods_list16';
}
if ($acct['tpl'] == '8208') {
    $tpl1 = 'goods/goods_list18';//果柜定制,仿有赞
    $tpl = 'goods/goods_list18';
}
if ($acct['tpl'] == '8209') {
    $tpl = 'goods/goods_list19';//商城//定制，仿武汉同城购
    $tpl1 = 'goods/store_list1';//拼团
}
if ($acct['tpl'] == '8210') {
    $tpl = 'goods/goods_list6_keyword';//拼团红色单排 明择定制
    $tpl1 = 'goods/goods_list6_keyword';
}
if ($acct['tpl'] == '8211') {
    $tpl1 = 'goods/goods_list18';//圣诞专版
    $tpl = 'goods/goods_list18';
}
if ($acct['tpl'] == '8213') {
    $tpl1 = 'goods/goods_list20';//仿饿了么
    $tpl = 'goods/goods_list20';
}
if ($checkfunction['status'] && intval($_GPC['sharenum']) != 0 && $member['parentid'] == -1) {

    $data = array('parentid' => $_GPC['sharenum']);
    $params = array('from_user' => $_W['openid']);
    member_update_by_params($data, $params);
}
$member = member_get_by_params("from_user='" . $_W['openid'] . "'");
if (!$checkfunction['status'] || $member['parentid'] == -1) {
    $data = array('parentid' => 99);
    $params = array('from_user' => $_W['openid']);
    member_update_by_params($data, $params);
}
$pagetitle = !empty($config['tginfo']['sname']) ? $config['tginfo']['sname'] : '火蝶云';
//全民兼职 结束
$shop = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
$con = "";
if ($shop['is_merchant'] == 1) {
    $con = " and activities_type = 1 ";
}
$g_id = $_GPC['gid'];
$cc = '';
if ($g_id > 0 && $acct['tpl'] == '8206') {
    $cate = pdo_fetch("SELECT selltype FROM " . tablename('tg_category') . " WHERE id = '{$g_id}' ");
    $cc .= " and selltype = '{$cate['selltype']}' ";
}

$activities_type = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and enabled=1 and parentid=0 {$con} ORDER BY parentid ASC, displayorder DESC");
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and enabled=1 and parentid=0 and  activities_type=0 {$cc} ORDER BY parentid ASC, displayorder DESC");
unset($g_id);
unset($cc);
foreach ($activities_type as &$item) {
    if (empty($item['url'])) {
        $item['url'] = app_url('goods/list/fenlei_detail', array('gid' => $item['id']));
    }
}
if ($op == 'fenleiTwo') {
    $id = $_GPC['id'];
    $list = pdo_fetchall("SELECT * FROM" . tablename('tg_category') . "WHERE parentid =$id ");
    foreach ($list as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
    }
    die(json_encode($list));
}
if ($op == 'display') {
    if ($_W['openid']) {
        puvindex($_W['openid']);
    }

    $cid = intval($_GPC['gid']);

    $advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 order by displayorder DESC");
    foreach ($advs as &$adv) {
        if (substr($adv['link'], 0, 4) != 'http') {
            $adv['link'] = "http://" . $adv['link'];
        }
        unset($adv);
    }

    if ($acct['tpl'] == '8209') {
        header("Content-type: text/html; charset=utf-8");

        $shop_county = intval($_GPC['shop_county']);
        $lat = floatval($_GPC['lat']);
        $lng = floatval($_GPC['lng']);
        $shop_search = trim($_GPC['shop_search']);
        $shop_name = trim($_GPC['shop_name']);

        $shop_storeid = intval($_GPC['shop_storeid']);
        $type = intval($_GPC['type']);
        $_W['shop_name'] = $shop_name;
        $shop_storename = '';
        if (!$shop_storeid) {
            $result = store_list($shop_county, $lat, $lng, $shop_search, $shop_name, $type);
            if ($result['status'] == 1) {
                $shop_storeid = intval($result['data'][0]['id']);
                $_W['shop_name'] = $result['data'][0]['county_name'];
                $shop_storename = $result['data'][0]['storename'];
            } else {
                $_W['shop_name'] = $result['data'];
            }
        } else {
            $shop_storename = pdo_getcolumn('tg_store' , array('id' => $shop_storeid) , 'storename');
        }
        $_W['shop_storename'] = $shop_storename;

        $_W['shop_storeid'] = intval($shop_storeid);

        include wl_template('goods/goods_list19');
    } else if ($cid > 0) {
        $cat = pdo_fetch("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and id='{$cid}'");
        if ($cat['selltype'] == 0) {
            include wl_template($tpl);
        } else {
            include wl_template($tpl1);
        }
    } else {
        include wl_template($tpl);
    }

}
if ($op == 'shopajax') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $usepage = $_GPC['usepage'];
    $cid = intval($_GPC['cid']);
    if ($cid == 0) {
        $cid = '';
    }


    if(!isset($usepage)){
        $usepage = 1;
    }
    $tuan = 0;
    if ($cid > 0) {
        $categ = pdo_fetch("SELECT * FROM " . tablename('tg_category') . " WHERE id = '{$cid}' ");
        $tuan = intval($categ['selltype']);
    }
    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    $is_hunda = intval($_GPC['is_hunda']);
    $is_member = intval($_GPC['is_member']);

    $data = goods_get_list(array('usepage' => $usepage, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'lin' => 0, 'is_hunda' => $is_hunda, 'tuan' => $tuan, 'cid' => $cid, 'gname' => $keyword, 'is_member' => $is_member, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

        $data['list'][$key]['history_limit'] = intval($old_data['num']);


    }
    $goodses = $data;
    die(json_encode($goodses));
}
if ($op == 'shopajax_dan') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['cid']);
    if ($cid == 0) {
        $cid = '';
    }
    $tuan = 0;
    if ($cid > 0) {
        $categ = pdo_fetch("SELECT * FROM " . tablename('tg_category') . " WHERE id = '{$cid}' ");
        $tuan = intval($categ['selltype']);
    }
    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    $is_hunda = intval($_GPC['is_hunda']);
    $is_member = intval($_GPC['is_member']);
    $shop_storeid = intval($_GPC['shop_storeid']);

    $data = goods_get_list_dan(array('usepage' => 1, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'shop_storeid' => $shop_storeid, 'lin' => 0, 'is_hunda' => $is_hunda, 'tuan' => $tuan, 'cid' => $cid, 'gname' => $keyword, 'is_member' => $is_member, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

        $data['list'][$key]['history_limit'] = intval($old_data['num']);


    }
    $goodses = $data;
    die(json_encode($goodses));
}
if ($op == 'shopajax_tuan') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['gid']);
    if ($cid == 0) {
        $cid = '';
    }
    $tuan = 1;
    if ($cid > 0) {
        $categ = pdo_fetch("SELECT * FROM " . tablename('tg_category') . " WHERE id = '{$cid}' ");
        $tuan = intval($categ['selltype']);
    }
    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    $is_hunda = intval($_GPC['is_hunda']);
    $is_member = intval($_GPC['is_member']);
    $storeid = intval($_GPC['storeid']);

    $data = goods_get_list_tuan(array('usepage' => 1, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'storeid' => $storeid, 'lin' => 0, 'is_hunda' => $is_hunda, 'tuan' => $tuan, 'cid' => $cid, 'gname' => $keyword, 'is_member' => $is_member, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_param') . " WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

        $data['list'][$key]['history_limit'] = intval($old_data['num']);


    }
    $goodses = $data;
    die(json_encode($goodses));
}
if ($op == 'merchantajax') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $merchantid = $_GPC['merchant'];
    $cid = intval($_GPC['cid']) || 0;
    $wechats = pdo_fetch("select tpl from " . tablename('account_wechats') . " where uniacid = '{$_GPC['uniacid']}'");
    $func = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$wechats['tpl']}'");

    $condition = ' and `uniacid` = :uniacid and shenhe=0';
    $params = array(':uniacid' => $_W['uniacid']);
    if ($cid > 0) {
        $condition .= "  AND fk_typeid = '{$cid}'  AND  isshow = 1";
    }
    //  if ($merchantid > 0) {
    $condition .= "   AND merchantid='{$merchantid}' AND  isshow = 1";
    //  }

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
        if (empty($value['mprice'])) {
            $value['mprice'] = $value['gprice'];
        }
        if (empty($value['unit'])) {
            $value['unit'] = "件";
        }
        $value['deliverytype'] = strval(intval($value['deliverytype']));
        if (empty($value['share_image'])) {
            $value['share_image'] = tomedia($value['gimg']);
        } else {
            $value['share_image'] = tomedia($value['share_image']);
        }
        if ($value['one_group'] == 1) {
            $group = pdo_fetch("SELECT * FROM " . tablename('tg_group') . ' WHERE goodsid=:goodsid AND uniacid=:uniacid AND groupstatus=3 AND neednum<>lacknum ORDER BY lacknum ASC LIMIT 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $value['id']));

            if (empty($group)) {
                $value['a'] = app_url('goods/detail/displays', array('id' => $value['id']));
            } else {
                $value['a'] = app_url('order/group', array('tuan_id' => $group['groupnumber']));
            }

        } else {
            $value['a'] = app_url('goods/detail/displays', array('id' => $value['id']));
        }
        $value['gimg'] = tomedia($value['gimg']);
        $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$value['id']}'");
        foreach ($advs as &$adv) {
            $adv['thumb'] = tomedia($adv['thumb']);
            unset($adv);
        }
        $data['list'][$key]['advs'] = $advs;
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid'], ':g_id' => $value['id']));
        $data['list'][$key]['history_limit'] = intval($old_data['num']);
    }
    $goodses = $data;
//    echo "<pre>";
//    var_dump($goodses);
//    die();
    die(json_encode($goodses));
}
if ($op == 'ajax') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $usepage = $_GPC['usepage'];
    $cid = intval($_GPC['cid']);

    if(!isset($usepage)){
        $usepage = 1;
    }
    $wechats = pdo_fetch("select tpl from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    $func = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$wechats['tpl']}'");
    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    //邻购
    $lin = checkfunc(8161);

    $is_hunda = intval($_GPC['ishunda']);
    $data = goods_get_list(array('usepage' => $usepage, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'lin' => $lin['status'], 'tuan' => 1, 'is_hunda' => $is_hunda, 'cid' => $cid, 'gname' => $keyword, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

        $data['list'][$key]['history_limit'] = intval($old_data['num']);
    }
    $goodses = $data;
    die(json_encode($goodses));
}

if ($op == 'm_ajax') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['cid']);
    $wechats = pdo_fetch("select tpl from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    $func = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$wechats['tpl']}'");
    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    //邻购
    $lin = checkfunc(8161);
    $tuan = $_GPC['is_tuan'];
    $usepage = $_GPC['usepage'];
    if(!isset($usepage)){
        $usepage = 1;
    }
    $is_hunda = 0;
    if ($tuan == 2) {
        $is_hunda = 1;
    }
    $data = goods_get_list(array('usepage' => $usepage, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'lin' => $lin['status'], 'is_hunda' => $is_hunda, 'm_ajax' => 1, 'tuan' => $tuan, 'cid' => $cid, 'gname' => $keyword, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));
        $data['list'][$key]['history_limit'] = intval($old_data['num']);
    }
    $goodses = $data;
    die(json_encode($goodses));
}

if ($op == 'spike_ajax') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['cid']);
    $type = intval($_GPC['type']);
    if ($type == 2) {
        $orderby = 'ORDER BY spike_end DESC';
    } else {
        $orderby = 'ORDER BY spike_start asc';
    }

    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    $data = goods_get_mlist(array('usepage' => 1, 'ishows' => '1,3', 'page' => $page, 'type' => $type, 'pagesize' => $pagesize, 'gname' => $keyword, 'orderby' => $orderby));
    foreach ($data['list'] as $key => &$value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

        $data['list'][$key]['history_limit'] = intval($old_data['num']);
        $data['list'][$key]['pnum'] = $data['list'][$key]['salenum'] / ($data['list'][$key]['gnum'] + $data['list'][$key]['salenum']) * 100;
        if (TIMESTAMP >= strtotime($value['spike_start']) && TIMESTAMP <= strtotime($value['spike_end'])) {

            $data['list'][$key]['spike_type'] = 1;
            if ($data['list'][$key]['gnum'] <= 0) {
                $data['list'][$key]['spike_type'] = 3;
            }
        }
        if (TIMESTAMP < strtotime($value['spike_start'])) {
            $data['list'][$key]['spike_type'] = 0;
        }
        if (TIMESTAMP > strtotime($value['spike_end'])) {
            $data['list'][$key]['spike_type'] = 2;
        }
    }
    $goodses = $data;
    die(json_encode($goodses));
}
//加载二级分类商品(刷新页面)
if ($op == 'ajaxEJ') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['ejid']);
    $wechats = pdo_fetch("select tpl from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    $func = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$wechats['tpl']}'");
    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    //邻购
    $lin = checkfunc(8161);

    $data = goods_get_list(array('usepage' => 1, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'lin' => $lin['status'], 'tuan' => 1, 'cid' => $cid, 'gname' => $keyword, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

        $data['list'][$key]['history_limit'] = intval($old_data['num']);
    }
    $goodses = $data;
    die(json_encode($goodses));
}
//加载二级分类商品
if ($op == 'ejHtml') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['id']);
    $wechats = pdo_fetch("select tpl from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    $func = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$wechats['tpl']}'");
    $keyword = !empty($_GPC['keyword']) ? $_GPC['keyword'] : '';
    //邻购
    $lin = checkfunc(8161);
    $is_hunda = $_GPC['ishunda'];
    $data = goods_get_list(array('usepage' => 1, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'lin' => $lin['status'], 'tuan' => 1, 'category_childid' => $cid, 'is_hunda' => $is_hunda, 'gname' => $keyword, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("SELECT * FROM " . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND openid=:openid AND g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

        $data['list'][$key]['history_limit'] = intval($old_data['num']);
    }
    $goodses = $data;
    die(json_encode($goodses));
}
if ($op == 'miaosha') {

    include wl_template('goods/miaosha');
}
if ($op == 'weight') {

    $strname = $_GPC['strname'];

    $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_option') . " WHERE title=:id ", array(':id' => $strname));

    die(json_encode($option));

}
if ($op == 'clear') {
    if (pdo_delete('tg_collect', array('openid' => $_W['openid'], 'orderno' => 0))) {
        echo 1;
        exit;
    } else {
        echo 0;
        exit;
    }
}
if ($op == 'specsajax') {
    $id = $_GPC['id'];
    $allspecs = pdo_fetchall("SELECT * FROM " . tablename('tg_spec') . " WHERE goodsid=:id ORDER BY displayorder ASC", array(':id' => $id));
    foreach ($allspecs as &$s) {
        $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('tg_spec_item') . " WHERE  `show`=1 AND specid=:specid ORDER BY displayorder ASC", array(":specid" => $s['id']));

    }

    unset($s);
    $options = pdo_fetchall("SELECT id,title,thumb,marketprice,productprice,costprice,specs,stock,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
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
    foreach ($options as $key => $value) {
        $th = pdo_fetch('SELECT thumb FROM ' . tablename('tg_spec_item') . ' WHERE id=:id', array(':id' => $value['specs']));
        $options[$key]['thumb'] = tomedia($th['thumb']);
        $options[$key]['specs'] = $specs;
    }
    $goods = goods_get_by_params("id = {$id}");
    if ($goods['selltype'] == 0 || $goods['selltype'] == 1) {
        //这里是随意团和单买的情况下计算一下是否享受会员权益

        if ($goods['discount'] == 1) {

            //证明是计算打折后的价钱 查询会员的等级和折扣详情
            $level = pdo_fetch("SELECT level FROM " . tablename("tg_member") . " WHERE uniacid = :uniacid AND openid = :openid", array(":uniacid" => $_W["uniacid"], ":openid" => $_W["openid"]));
            if (!empty($level["level"]) && $level["level"] != "0" && $level["level"] != 0) {
                //获取会员的等级 匹配会员等级享受的折扣
                $rights = pdo_fetch("SELECT rights FROM " . tablename("tg_member_leave") . " WHERE uniacid = :uniacid AND id = :id", array(":uniacid" => $_W["uniacid"], ":id" => $level['level']));

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


    die(json_encode($options));
}

if ($op == 'add') {
    $id = $_GPC['id'];
    $str = $_GPC['str'];//规格
    $weight = floatval($_GPC['weight']);//规格
    $kunum1 = pdo_fetch("SELECT productprice,stock,weight FROM " . tablename('tg_goods_option') . " WHERE   goodsid='{$id}' and title='{$str}'  ");
    $price = $kunum1['productprice'];
    $gnum = $kunum1['stock'];
    if ($weight == 0) {
        $weight = $kunum1['weight'];
    }

    if (empty($id)) {
        echo 0;
        exit;
    } else {
        $sql = 'SELECT oprice,supprices,storeid,credit,commissiontype,commission,merchantid,discount,gname,zititime,weight,com_type FROM ' . tablename('tg_goods') . ' WHERE id=:id AND uniacid=:uniacid';
        $paramse = array(':id' => $id, ':uniacid' => $_W['uniacid']);
        $goods = pdo_fetch($sql, $paramse);
        if (empty($str)) {
            $price = $goods['oprice'];
            if ($weight == 0) {
                $weight = $goods['weight'];
            }
        }

        //会员的openid
        $openid = $_W["openid"];
        //查询会员等级享受的折扣
        $res = pdo_fetch("SELECT * FROM " . tablename("tg_member") . " WHERE uniacid=:uniacid AND openid=:openid", array(":uniacid" => $_W["uniacid"], ":openid" => $openid));
        $level = $res["level"];//会员等级
        //查询相应等级对应的权益是多少
        $level_rights = pdo_fetch("SELECT * FROM " . tablename("tg_member_leave") . " WHERE uniacid=:uniacid AND id=:id", array(":uniacid" => $_W["uniacid"], ':id' => $level));
        //计算会员折扣
        $rights = $level_rights["rights"];
        $discount_num = 10;
        if ($goods['discount'] == 1) {
            if ((empty($rights) || !$rights)) {
                //不打折计算原价
                $discount_num = 10;
            } else {
                $price = $price * $rights / 10;
                $discount_num = $rights;
            }
        }
        $data = array(
            'openid' => $_W['openid'],
            'uniacid' => $_W['uniacid'],
            'num' => 1,
            'oprice' => $price,
            'orderno' => 0,
            'applystatus' => 0,
            'optionid' => 0,
            'item' => $_GPC['str'],
            'weight' => $weight,
            'supprices' => $goods['supprices'],
            'storeid' => $goods['storeid'],
            'credit' => $goods['credit'],
            'type' => $goods['commissiontype'],
            'commission' => $goods['commission'],
            'merchant_id' => $goods['merchantid'],
            'discount_num' => $discount_num,
            'goodsname' => $goods['gname'],
            'zititime' => $goods['zititime'],
            'com_type' => $goods['com_type'],
            'sid' => $id
        );
        $logdata = array(
            'goods' => json_encode($goods),
            'data' => json_encode($data),
            'kunum1' => $kunum1,
            'price' => $price,
            'openid' => $_W['openid'],
            'uniacid' => $_W['uniacid']
        );

        $tt = pdo_fetch("SELECT id,num FROM " . tablename('tg_collect') . " WHERE  uniacid = '{$_W['uniacid']}' and sid='{$id}'  and openid='{$_W['openid']}' and item='{$str}'  and orderno='0'");
        $kunum = pdo_fetch("SELECT gnum FROM " . tablename('tg_goods') . " WHERE  uniacid = '{$_W['uniacid']}' and id='{$id}'  ");
        if (intval($gnum) == 0 && empty($str)) {
            $gnum = $kunum['gnum'];
        }
        $num = $tt['num'] + 1;
        if ($num > intval($gnum)) {
            echo '-1';
            exit;
        }
        if (empty($tt)) {
            if (pdo_insert('tg_collect', $data)) {
                echo 1;
            } else {
                //

                echo 0;
                //
            }
        } else {
            pdo_update('tg_collect', array('num' => $num), array('id' => $tt['id']));
            echo 1;
        }
    }
    exit();
}
if ($op == 'remove') {
    $id = $_GPC['id'];
    $str = $_GPC['str'];//规格
    if (empty($id)) {
        echo 0;
        exit;
    } else {
        //
        $tua = pdo_fetch("SELECT num,id FROM " . tablename('tg_collect') . " WHERE  sid = {$id} and uniacid = '{$_W['uniacid']}' AND orderno = '0' and item='{$str}'  and openid='{$_W['openid']}'");
        $num = $tua['num'] - 1;
        if ($num > 0) {

            if (pdo_update('tg_collect', array('num' => $num), array('id' => $tua['id']))) {
                echo $num;
            }
        } else {
            if (pdo_delete('tg_collect', array('id' => $tua['id']))) {
                echo 0;
            }
        }
        //


    }
    exit();
}

if ($op == 'notice') {
    $id = $_GPC['id'];
    $type = $_GPC['type'];//规格
    $tua = pdo_fetch("SELECT id FROM " . tablename('tg_notice') . " WHERE  g_id = {$id} and uniacid = '{$_W['uniacid']}'  and openid='{$_W['openid']}' and type='{$type}'");
    if (empty($tua)) {
        pdo_insert('tg_notice', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'g_id' => $id, 'type' => $type));
        echo 1;
        exit;
    } else {
        echo 1;
        exit;
    }

}
if ($op == 'noticeremove') {
    $id = $_GPC['id'];
    $type = $_GPC['type'];//规格
    $tua = pdo_fetch("SELECT id FROM " . tablename('tg_notice') . " WHERE  g_id = {$id} and uniacid = '{$_W['uniacid']}'  and openid='{$_W['openid']}' and type='{$type}'");
    if (pdo_delete('tg_notice', array('id' => $tua['id']))) {
        echo 1;
    }

}
if ($op == 'ajaxFright') {
    $id = $_GPC['id'];
    $goods = pdo_fetchall("SELECT id,name FROM " . tablename('tg_delivery_template') . " WHERE uniacid=:uniacid AND merchant_id=:merchantid AND status=2", array(':uniacid' => $_W['uniacid'], ':merchantid' => $id));
    die(json_encode($goods));
}
if ($op == 'address_fee') {
    $goods = pdo_fetch("select * from " . tablename('tg_address') . "where openid = '{$_W['openid']}' and status = 1");
    die(json_encode($goods));
}
if ($op == 'search') {
    $keyword = $_GPC['keyword'];
    //$goods = goods_get_list(array('gname'=>$keyword));
    if ($acct['tpl'] == 8200) {
        include wl_template('goods/goods_list14');
    } elseif ($acct['tpl'] == 8206) {
        if (intval($_GPC['status']) == 1) {
            include wl_template('goods/goods_list17');
        } elseif (intval($_GPC['status']) == 0) {
            include wl_template('goods/goods_list15');
        } else {
            include wl_template('goods/goods_list17');
        }

    } else {
        include wl_template($tpl);
    }
}
if ($op == 'search1') {
    $keyword = $_GPC['keyword'];
    include wl_template('goods/goods_list19');
}
if ($op == 'search2') {
    $keyword = $_GPC['keyword'];
    include wl_template('goods/goods_list7_1');
}
if ($op == 'cart') {
    //$keyword = $_GPC['keyword'];
    //$goods = goods_get_list(array('gname'=>$keyword));
    include wl_template('goods/goods_cart');
}
if ($op == 'goods_store') {
    $id = $_GPC['merchant'];
    $advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id={$id} order by displayorder DESC");
    foreach ($advs as &$adv) {
        if (substr($adv['link'], 0, 4) != 'http') {
            $adv['link'] = "http://" . $adv['link'];
        }
        unset($adv);
    }
    $merchant = pdo_fetch("select * from" . tablename('tg_merchant') . "where id=:id", array(":id" => $id));
    $merchant['thumb'] = tomedia($merchant['thumb']);
    if (empty($merchant['thumb']) || !$merchant['thumb']) {
        $merchant['thumb'] = tomedia($config['tginfo']['slogo']);
    }

    $counta = pdo_fetch("select COUNT(*) as total from" . tablename('tg_goods') . "where merchantid={$id}");
    //$keyword = $_GPC['keyword'];
    //$goods = goods_get_list(array('gname'=>$keyword));
    //查询商家配备客服
    $res = pdo_fetchall("SELECT * FROM " . tablename("messikefu_cservice") . " WHERE  merchant_id=" . $id . " AND weid=" . $_W['uniacid']);
    if (empty($res)) {
        $url_kefu = "";
    } else {
        $rand = count($res);
        $rand = rand(0, $rand);
        $url_kefu = app_url('chat/chat', array('op' => 'doMobileChat', "toopenid" => $res[$rand]["content"], "id" => $goods['id']));
    }
    include wl_template('goods/goods_store');
}
if ($op == 'fenlei_detail') {
    $keyword = $_GPC['keyword'];
    $acct = pdo_fetch("select tpl,homeimg from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
    if ($acct['tpl'] == '8201') {
        include wl_template('goods/fenlei_detail12');
    } else {
        include wl_template('goods/fenlei_detail');
    }
    //$keyword = $_GPC['keyword'];
    //$goods = goods_get_list(array('gname'=>$keyword));

}

//小陈

if ($op == 'merchant') {
    $id = $_GPC['id'];
    $merchant = merchant_get_by_params("id = {$id}");
    include wl_template('goods/merchant_goods');
}
if ($op == 'merchant_ajax') {
    $id = $_GPC['id'];//商家id
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $data = goods_get_list(array('usepage' => 1, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'merchantid' => $id, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
    }
    $goodses = $data;
    die(json_encode($goodses));
}
if ($op == "notices") {
    $pagetitle = !empty($config['tginfo']['sname']) ? '公告详情 - ' . $config['tginfo']['sname'] : '公告详情';
    $id = intval($_GPC["id"]);
    if (!empty($id)) {
        $notice = pdo_fetch("SELECT * FROM " . tablename('tg_notices') . " WHERE id = :id", array(":id" => $id));
    } else {
        message('缺少参数，请返回首页！');
    }
    $config['share']['share_title'] = $pagetitle;
    $config['share']['share_desc'] = $notice['title'];
    $tourl = app_url('goods/list/notices', array('id' => $id));
    include wl_template('goods/notice_detail');
}
if ($op == "up_num") {
    $play__url = $_GPC["id"];
    $num = pdo_fetch("SELECT * FROM " . tablename("account_wechats") . " WHERE uniacid=:uniacid", array(":uniacid" => $_W["uniacid"]));

    if (intval($num["play_num"]) == 0) {
        $res = array("status" => "error", "data" => "已经没有播放次数");
        die(json_encode($res));
    }
    $res3["play_num"] = $num["play_num"] - 1;
    pdo_update("account_wechats", $res3, array(":uniacid" => $_W["uniacid"]));
    $res = pdo_fetch("SELECT * FROM " . tablename("tg_goods_video") . " WHERE id =  :id", array(":id" => $play__url));
    $url = $res["media_url"];
    if ($res) {
        $res2["play_num"] = intval($res["play_num"]) + 1;
        pdo_update("tg_goods_video", $res2, array(":media_url" => '%' . $url . '%'));
        $res = array("status" => "success", "data" => "");
        die(json_encode($res));
    } else {
        $res = array("status" => "error", "data" => "已经没有播放次数");
        die(json_encode($res));
    }
}
//if ($op == "goods_list19") {
//    include wl_template('goods/goods_list19');
//}
//if ($op == "list7") {
//    $advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 order by displayorder DESC");
//    foreach ($advs as &$adv) {
//        if (substr($adv['link'], 0, 5) != 'http:') {
//            $adv['link'] = "http://" . $adv['link'];
//        }
//        unset($adv);
//    }
//
//    include wl_template('goods/goods_list7_1');
//}
if ($op == "store_list1") {
//    $advs = pdo_fetchall("select * from " . tablename('tg_adv') . " where enabled = 1 and uniacid = '{$_W['uniacid']}' and merchant_id = 0 order by displayorder DESC");
//    foreach ($advs as &$adv) {
//        if (substr($adv['link'], 0, 5) != 'http:') {
//            $adv['link'] = "http://" . $adv['link'];
//        }
//        unset($adv);
//    }
    $merchantid = intval($_GPC['merchantid']);
    $county = pdo_getall('tg_store_address', array('uniacid' => $_W['uniacid'], 'merchantid' => $merchantid, 'status' => 1));

    include wl_template('goods/store_list1');
}

//加载门店列表
if ($op == 'list_store') {

    $store_county = intval($_GPC['store_county']);
    $lat = floatval($_GPC['lat']);
    $lng = floatval($_GPC['lng']);
    $search = trim($_GPC['search']);
    $county_name = trim($_GPC['countyname']);
    $type = intval($_GPC['type']);
    if ($type != 1) {
        $type = 2;
    }

    if ($type) {
        $result = store_list($store_county, $lat, $lng, $search, $county_name, $type);
    } else {
        $result = array('status' => 0, 'data' => "传入参数错误！");
    }
    die(json_encode($result));

}

if ($op == 'shop_store') {

    $shop_county = intval($_GPC['shop_county']);
    $lat = floatval($_GPC['lat']);
    $lng = floatval($_GPC['lng']);
    $shop_search = trim($_GPC['shop_search']);
    $shop_name = trim($_GPC['shop_name']);
    $shop_storeid = intval($_GPC['shop_storeid']);
    $type = 1;
    $_W['shop_storename'] = '';
    if (!$shop_storeid) {
        $result = store_list($shop_county, $lat, $lng, $shop_search, $shop_name, $type);
        if ($result['status'] == 1) {
            $shop_storeid = intval($result['data'][0]['id']);
            $_W['shop_storename'] = $result['data'][0]['storename'];
        } else {
            $_W['shop_name'] = $result['data'];
        }
    } else {
        $_W['shop_storename'] = pdo_getcolumn('tg_store' , array('id' => $shop_storeid) , 'storename');
    }

    $_W['shop_storeid'] = intval($shop_storeid);
    if ($shop_name) {
        $_W['shop_name'] = $shop_name;
    } else {
        $_W['shop_name'] = $result['data'][0]['county_name'];
    }

    die(json_encode(array('status' => $_W['shop_storeid'] , 'shop_storename' => $_W['shop_storename'])));
}

//加载门店商品列表
if ($op == 'store_goods') {

    $store_county = intval($_GPC['store_county']);
    $lat = floatval($_GPC['lat']);
    $lng = floatval($_GPC['lng']);
    $search = trim($_GPC['search']);
    $county_name = trim($_GPC['countyname']);
    $storeid = intval($_GPC['storeid']);
    $type = intval($_GPC['type']);

    if (!$storeid) {

        $result = store_list($store_county, $lat, $lng, $search, $county_name, $type);
        if ($result['status'] == 1) {
            $storeid = intval($result['data'][0]['id']);
            $_W['storename'] = $result['data'][0]['storename'];
        }
    } else {
        $_W['storename'] = pdo_getcolumn('tg_store' , array('id' => $storeid) , 'storename');
    }

    $_W['storeid'] = $storeid;

    if ($county_name) {
        $_W['county_name'] = $county_name;
    } else {
        $_W['county_name'] = $result['data'][0]['county_name'];
    }

    include wl_template('goods/goods_list7_1');
}


//武汉同城  自提运费
if ($_GPC['op'] == 'whtc') {

    $storeid = intval($_GPC['storeid']);
    $store = pdo_get('tg_store', array('id' => $storeid));
    $store['cost'] = number_format($store['cost'], 2);
    if ($goods['selltype'] == 2 && $is_tuan == 1 && $tuan_first == 1) {
        $store['cost'] = 0;
    }
    die(json_encode(array('status' => 1, 'data' => $store)));
}


/**
 *
 * @desc 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000; //approximate radius of earth in meters
    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;
    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;
    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}


function store_list($store_county, $lat, $lng, $search, $county_name, $type, $merchantid = 0)
{

    global $_W;
    $uniacid = $_W['uniacid'];

    $con = '';
    if ($store_county && $type != 1) {
        $con .= " and store_county = {$store_county} ";
    } elseif ($county_name) {
        $con .= " and store_county in ( select id from " . tablename('tg_store_address') . " where county = '{$county_name}' and status = 1 ) ";
    } elseif ($lat) {
        $con .= " and lat > " . ($lat - 0.01) . " and lat < " . ($lat + 0.01) . " and lng > " . ($lng - 0.01) . " and lng < " . ($lng + 0.01);
    } elseif ($search) {
        $con .= " and storename like '%{$search}%' ";
    } else {
        return (array('status' => 0, 'data' => '定位失败'));
    }

    $stores = pdo_fetchall("select * from " . tablename('tg_store') . " where uniacid = '{$uniacid}' and merchantid = {$merchantid} {$con} ");

    $store = array();
    foreach ($stores as $k => $val) {

        $dis = $distance = getDistance($lat, $lng, $val['lat'], $val['lng']);
        $stores[$k]['distance'] = $distance;

        if ($distance / 10000 > 1) {
            $stores[$k]['distance'] = number_format($distance / 1000) . "公里";
        } else {
            $stores[$k]['distance'] = $distance . "米";
        }
        $stores[$k]['image'] = tomedia($stores[$k]['image']);
        $stores[$k]['county_name'] = pdo_getcolumn('tg_store_address', array('id' => $val['store_county']), 'county');
        $store[$dis] = $stores[$k];

    }


    if ($type == 1) {
        wl_load()->model('setting');
        $hexiao = setting_get_by_name('base');
        $storesids = explode(",", $hexiao['hexiao_id']);
        $storesids = array_merge($storesids);
        foreach ($store as $key => $item) {
            if (!in_array($item['id'], $storesids)) {
                unset($store[$key]);
            }
        }

    }

    ksort($store);
    $store = array_merge($store);

    if ($store) {
        $status = 1;
    } else {
        $status = 0;
        $store = '此区域下暂无门店，请选择其他区域';
    }
    return array('status' => $status, 'data' => $store);
}


exit();