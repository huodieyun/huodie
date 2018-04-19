<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/7/13
 *
 * Time: 16:56
 */


// TODO 小程序专用


define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
load()->model('reply');
load()->app('common');
load()->classs('wesession');

$uniacid = intval($_GPC['uniacid']);
$openid = trim($_GPC['openid']);
$app = intval($_GPC['app']);

/*
 * 接口名 fenlei
 * 商品分类信息
 * url /min_shop_api.php?op=fenlei
 * 传入商家  uniacid
 * 返回一级分类信息 以及成功与否信息
 */
if ($op == 'fenlei') {
    $account_vip = pdo_get('account_vip', array('uniacid' => $uniacid));
    if ($app && $account_vip['app'] != 1) {
        die();
    }
    if (!$app && $account_vip['applet'] != 1) {
        die();
    }

    $setting = pdo_fetch("select value from " . tablename('tg_setting') . " where uniacid = '{$uniacid}' and `key` = 'tginfo' ");
    $set = unserialize($setting['value']);
    $logo = tomedia($set['slogo']);
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_GPC['uniacid']}' and enabled=1 and parentid=0 ORDER BY parentid ASC, displayorder DESC");
    foreach ($list as &$adv) {
        $thumb = $adv['thumb'];
        $adv['thumb'] = tomedia($adv['smallthumb']);
        $adv['smallthumb'] = tomedia($thumb);
    }
//    foreach ($list as &$value) {
//        $id=$value['id'];
//        $value['productList'] = pdo_fetchall("SELECT * FROM " . tablename('tg_category') .  " WHERE parentid = $id ");
//        foreach ($list as &$adv) {
//            $adv['thumb'] = tomedia($adv['thumb']);
//        }
//    }
    die(json_encode(array('menuList' => $list, 'status' => 1, 'logo' => $logo)));
}

/*
 * 接口名 fenleiTwo
 * 商品分类信息
 * url /min_shop_api.php?op=fenleiTwo
 * 传入商家  uniacid  大分类id
 * 返回二级分类信息 以及成功与否信息
 */
if ($op == 'fenleiTwo') {

    $id = $_GPC['cid'];
//    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_GPC['uniacid']}' and enabled=1 and parentid=0 ORDER BY parentid ASC, displayorder DESC");
//    foreach ($list as &$value) {
//        $id=$value['id'];
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE parentid = $id ");
    foreach ($list as &$adv) {
        $thumb = $adv['thumb'];
        $adv['thumb'] = tomedia($adv['smallthumb']);
        $adv['smallthumb'] = tomedia($thumb);
    }
//    }
    die(json_encode(array('productList' => $list, 'status' => 1)));
}

/*
 * 接口名 ajax
 * 根据分类id查询商品  带购物车
 * url /min_shop_api.php?op=ajax_collect
 * 传入商家  uniacid  openid  分类id
 * 返回一二级分类商品信息 以及成功与否信息
 */
if ($op == 'ajax_collect') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['cid']);

    $account_vip = pdo_get('account_vip', array('uniacid' => $uniacid));
    if ($app && $account_vip['app'] != 1) {
        die();
    }
    if (!$app && $account_vip['applet'] != 1) {
        die();
    }

    $condition = " where uniacid = " . $uniacid . "";
    $condition .= "  and isshow in (1,3)";

        $condition .= " and selltype in (0,1) ";

    //app上架商品
    if ($app == 1) {
        $condition .= "  and is_app = 1 ";
    }
    //小程序上架商品
    if ($app == 0) {
        $condition .= " and is_applet = 1 ";
    }
    if ($cid > 0) {
        $condition .= " and ( fk_typeid = '" . $cid . "' or category_childid = '" . $cid . "' )";
    } else {
        $condition .= " and showindex = 0 ";
    }

    $data = pdo_fetchall("select * from " . tablename('tg_goods') . $condition . " order by displayorder desc limit " . ($page - 1) * $pagesize . " , " . $pagesize);
    $total = pdo_fetchcolumn("select count(*) from " . tablename('tg_goods') . $condition);


    foreach ($data as &$value) {
//        if (!empty($value['share_image'])) {
//            $value['gimg'] = tomedia($value['share_image']);
//        } else {
//            $value['gimg'] = tomedia($value['gimg']);
//        }
        $value['gimg'] = tomedia($value['gimg']);
        $value['share_image'] = tomedia($value['share_image']);
        $params = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_param') . " WHERE goodsid = '{$value['id']}'");
        $value['params'] = $params;

        $value['deliverytype'] = strval(intval($value['deliverytype']));
        //得到图集
        $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$value['id']}'");
        foreach ($advs as &$adv) {
            $adv['thumb'] = tomedia($adv['thumb']);
            if (substr($adv['link'], 0, 5) != 'http:') {
                $adv['link'] = "http://" . $adv['link'];
            }
        }
        $value['advs'] = $advs;

        //是否单买
        if ($value['selltype'] == 0) {
            $value['odd'] = true;
            $collect = pdo_fetch("select num from " . tablename('tg_collect') . " where uniacid = '{$uniacid}' and openid = '{$openid}' and orderno = '0' and sid = '{$value['id']}' ");
//            var_dump($collect);
//            $value['collect']=$collect;
            $num = $collect['num'];
            $value['cart_num'] = intval($num);
        } else {
            $value['odd'] = false;
        }
        //规格及规格项
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid = :id order by id asc", array(':id' => $value['id']));
        $value['options'] = $options;
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $uniacid, ':openid' => $openid, ':g_id' => $value['id']));

        $value['history_limit'] = intval($old_data['num']);
    }
    $goodses['list'] = $data;
    $goodses['total'] = $total;
    die(json_encode($goodses));
}


/*
 * 接口名 ajax
 * 根据分类id查询商品
 * url /min_shop_api.php?op=ajax
 * 传入商家  uniacid  分类id
 * 返回一二级分类商品信息 以及成功与否信息
 */
if ($op == 'ajax') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['cid']);
    $app = intval($_GPC['app']);

    $account_vip = pdo_get('account_vip', array('uniacid' => $uniacid));
    if ($app && $account_vip['app'] != 1) {
        die();
    }
    if (!$app && $account_vip['applet'] != 1) {
        die();
    }
    $condition = " where uniacid = " . $uniacid . "";
    $condition .= "  and isshow in (1,3)";
    //app上架商品
    if ($app == 1) {
        $condition .= "  and is_app = 1 ";
    }
    //小程序上架商品
    if ($app == 0) {
        $condition .= " and is_applet = 1 ";
    }


    if ($cid > 0) {
        $condition .= " and ( fk_typeid = '" . $cid . "' or category_childid = '" . $cid . "' )";
    } else {
        $condition .= " and showindex = 0 ";
    }

    $data = pdo_fetchall("select * from " . tablename('tg_goods') . $condition . " order by displayorder desc limit " . ($page - 1) * $pagesize . " , " . $pagesize);
    $total = pdo_fetchcolumn("select count(*) from " . tablename('tg_goods') . $condition);

    foreach ($data as &$value) {
//        if (!empty($value['share_image'])) {
//            $value['gimg'] = tomedia($value['share_image']);
//        } else {
//            $value['gimg'] = tomedia($value['gimg']);
//        }
        $value['gimg'] = tomedia($value['gimg']);
        $value['share_image'] = tomedia($value['share_image']);
        $params = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_param') . " WHERE goodsid = '{$value['id']}'");
        $value['params'] = $params;

        $value['deliverytype'] = strval(intval($value['deliverytype']));
        //得到图集
        $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id='{$value['id']}'");
        foreach ($advs as &$adv) {
            $adv['thumb'] = tomedia($adv['thumb']);
            if (substr($adv['link'], 0, 5) != 'http:') {
                $adv['link'] = "http://" . $adv['link'];
            }
        }
        $value['advs'] = $advs;

        //是否单买
        if ($value['selltype'] == 0) {
            $value['odd'] = true;
        } else {
            $value['odd'] = false;
        }
        //规格及规格项
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid = :id order by id asc", array(':id' => $value['id']));
        $value['options'] = $options;
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $uniacid, ':openid' => $openid, ':g_id' => $value['id']));

        $value['history_limit'] = intval($old_data['num']);
    }
    $goodses['list'] = $data;
    $goodses['total'] = $total;
    die(json_encode($goodses));
}


/*
 * 接口名 goods_rand
 * 根据商品id查询商品详情
 * url /min_shop_api.php?op=goods_rand
 * 传入商家  uniacid  分类id
 * 返回一二级分类商品信息 以及成功与否信息
 */
if ($op == 'goods_rand') {

    $account_vip = pdo_get('account_vip', array('uniacid' => $uniacid));
    if ($app && $account_vip['app'] != 1) {
        die();
    }
    if (!$app && $account_vip['applet'] != 1) {
        die();
    }

    $condition = '';
    $app = intval($_GPC['app']);
    if ($app == 1) {
        $condition .= "  and is_app = 1 ";
    } else {
        $condition .= "  and is_applet = 1 ";
    }
    $list = pdo_fetchall('select id,gname,gprice,share_image,gimg from ' . tablename('tg_goods') . ' where uniacid = :uniacid and isshow = 1 and shenhe = 0 and selltype in (0 , 1) ' . $condition . ' order by rand() limit 9', array(':uniacid' => $_GPC['uniacid']));
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

/*
 * 商品模糊搜索
 * 接口名 /min_shop_api.php?op=search_goods
 * 传入商家uniacid 以及搜索条件 key
 * 返回查询到的商品信息 以及状态status
 */
if ($op == 'search_goods') {
    $uniacid = $_GPC['uniacid'];
    $key = $_GPC['key'];

    $account_vip = pdo_get('account_vip', array('uniacid' => $uniacid));
    if ($app && $account_vip['app'] != 1) {
        die();
    }
    if (!$app && $account_vip['applet'] != 1) {
        die();
    }

//    $list = pdo_fetchall("SELECT name FROM " . tablename('tg_category') . " WHERE uniacid = '{$_GPC['uniacid']}' and enabled=1 and parentid=0 ORDER BY parentid ASC, displayorder DESC");
//    foreach ($list as &$value) {
//        $id=$value['id'];
//        $value['name'] = pdo_fetchall("SELECT * FROM " . tablename('tg_category') .  " WHERE parentid = $id ");
//    }
//    die(json_encode(array('data' => $list , 'result' => $key)));

//    die(json_encode(array('key' => $key)));
    $condition = '';
    //app上架商品
    if ($app == 1) {
        $condition .= "  and is_app = 1 ";
    }
    //小程序上架商品
    if ($app == 0) {
        $condition .= " and is_applet = 1 ";
    }
    $sql = "select id , gname from " . tablename('tg_goods') . " where gname like '%" . $key . "%' " . $condition;
    $sql .= " order by createtime desc ";
    $page = !empty($_GPC['page']) ? $_GPC['page'] : 1;
    $pagesize = 10;
    $sql .= " limit " . ($page - 1) * $pagesize . ' , ' . $pagesize;

    $list = pdo_fetchall($sql);
    if (empty($list)) {
        $list['gname'] = '暂无该类商品';
    }
    die(json_encode(array('data' => $list, 'result' => $key)));
}

/*
 * 商品搜索
 * 接口名 /min_shop_api.php?op=search_goods_name
 * 传入商家uniacid 以及搜索条件 key
 * 返回查询到的商品信息 以及状态status
 */
if ($op == 'search_goods_name') {
    $uniacid = $_GPC['uniacid'];
    $key = $_GPC['key'];
//    if (empty($uniacid)){
//        $result = "商家为空";
//    }

    $account_vip = pdo_get('account_vip', array('uniacid' => $uniacid));
    if ($app && $account_vip['app'] != 1) {
        die();
    }
    if (!$app && $account_vip['applet'] != 1) {
        die();
    }

    $condition = '';
    //app上架商品
    if ($app == 1) {
        $condition .= "  and is_app = 1 ";
    }
    //小程序上架商品
    if ($app == 0) {
        $condition .= " and is_applet = 1 ";
    }
    $sql = "select * from " . tablename('tg_goods');
    $sql .= " where uniacid = " . $uniacid . " and isshow in (1,3) " . $condition;
//    if ($uniacid != 33){
    $sql .= " and selltype in (0,1) ";
//    }
    if (empty($key)) {
        $key = '';
    } else {
        $key = trim($key);
//        $result = array();
//        $array = array();
//        $strs = str_replace('，', ',', $key);
//        $strs = str_replace("。", ',', $strs);
//        $strs = str_replace("", ',', $strs);
//        $strs = str_replace("？", ',', $strs);
//        $strs = str_replace("！", ',', $strs);
//        $strs = str_replace(' ', ',', $strs);
//        $array = explode(',', $strs);
//
//        foreach ($array as $key => $value) {
//            if ('' != ($value = trim($value))) {
//                $result[] = $value;
//                $sql .= " or gname like '%" .$value ."%' ";
//            }
//        }
        $sql .= " and gname like '%" . $key . "%' ";
    }
    $sql .= " order by createtime desc ";
    $page = !empty($_GPC['page']) ? $_GPC['page'] : 1;
    $pagesize = $_GPC['pagesize'];
    $sql .= " limit " . ($page - 1) * $pagesize . ' , ' . $pagesize;

    $list = pdo_fetchall($sql);
    foreach ($list as &$value) {
        if (!empty($value['share_image'])) {
            $value['gimg'] = tomedia($value['share_image']);
        } else {
            $value['gimg'] = tomedia($value['gimg']);
        }
    }
    die(json_encode(array('data' => $list, 'result' => $result)));

}

/*
 * 商品详情
 * 接口名 /min_shop_api.php?op=search_goods_name
 * 传入商家uniacid 以及搜索条件 key
 * 返回查询到的商品信息 以及状态status
 */
if ($op == 'goods_detail') {

    $gid = $_GPC['gid'];
    if (!empty($id)) {
        $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id = '" . $gid . "'");
        //商品
        //    $goods = goods_get_by_params("id = {$id}");
        //    die(json_encode($goods));
        $result = array('statustype' => 0);
        $merchant = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$goods['merchantid']}");
        $counta = pdo_fetch("select COUNT(*) as total from " . tablename('tg_goods') . " where merchantid = {$goods['merchantid']}");
        if ($goods['selltype'] == 6) {
            $lists = pdo_fetch("select id from " . tablename('tg_order') . " where openid = '{$openid}' and uniacid = '{$uniacid}' and status in (1,2,3,8,10,7)  order by id asc limit 1");
            if (!empty($lists)) {
                $result = array('statustype' => 1);
            }
        }
        if ($goods['isshow'] == 2) {
            $tip = '该商品已下架';
            //        echo "<script>alert('".$tip."');location.href='".app_url('goods/list')."';</script>";
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
        $data = pdo_fetchall("select * from " . tablename('tg_order') . " where g_id = '{$gid}' and openid = '{$openid}' and status in (1,2,3,6,8)");
        $times = $data['total'];
        //商家
        //$merchant = merchant_get_by_params("id = {$goods['merchantid']}");

        //规格及规格项
        $allspecs = pdo_fetchall("select * from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $gid));

        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("select * from " . tablename('tg_spec_item') . " where `show` = 1 and specid = :specid order by displayorder asc", array(":specid" => $s['id']));
        }
        unset($s);

        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,stock from " . tablename('tg_goods_option') . " where goodsid = :id order by id asc", array(':id' => $gid));
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

        //得到图集
        $advs = pdo_fetchall("select * from " . tablename('tg_goods_atlas') . " where g_id = '{$gid}'");
        foreach ($advs as &$adv) {
            if (substr($adv['link'], 0, 5) != 'http:') {
                $adv['link'] = "http://" . $adv['link'];
            }
        }
        unset($adv);

        $params = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_param') . " WHERE goodsid = '{$gid}' ");
        //门店信息
        $storesids = explode(",", $goods['hexiao_id']);
        foreach ($storesids as $key => $value) {
            if ($value) {
                $stores[$key] = pdo_fetch("select * from " . tablename('tg_store') . " where id ='{$value}' and uniacid='{$uniacid}'");
            }
        }

        //    // 分享团数据
        //    if(empty($goods['is_share'])){
        //        if ($this->module['config']['sharestatus'] != 2) {
        //            $thistuan = pdo_fetchall("select * from ".tablename('tg_group')." where uniacid = '{$uniacid}' and goodsid = '{$id}' and groupstatus = 3 and lacknum<>neednum order by id asc ");
        //            if (!empty($thistuan)) {
        //                foreach ($thistuan as $key => $value) {
        //                    $tuan_first_order = order_get_by_params(" tuan_id = '{$value['groupnumber']}' and tuan_first = 1 ");
        //                    $userinfo=member_get_by_params(" openid = '{$tuan_first_order['openid']}'");
        //                    $thistuan[$key]['avatar'] = $userinfo['avatar'];
        //                    $thistuan[$key]['nickname'] = $userinfo['nickname'];
        //                    $thistuan[$key]['nownum']=$value['neednum']-$value['lacknum'];
        //                }
        //            }
        //        }
        //    }
        //
        //    $config['share']['share_title'] = !empty($goods['share_title']) ? $goods['share_title'] : $goods['gname'];
        //    $config['share']['share_desc'] = !empty($goods['share_desc']) ? $goods['share_desc'] : $config['share']['share_desc'];
        //    $config['share']['share_image'] = !empty($goods['share_image']) ? $goods['share_image'] : $goods['gimg'];


        $list = pdo_fetchall("select openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time from " . tablename('tg_judgment') . " where gid ='{$gid}' ORDER BY create_time desc LIMIT 0,2");
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . " where gid ='{$gid}'  ORDER BY create_time DESC ");
        die(json_encode());
    } else {
        $tip = '商品信息出错！';
    }

}

/*
 * 购物车列表
 * 接口名 /min_shop_api.php?op=cart_view
 * 传入uniacid 用户openid
 * 返回查询到的购物车信息 以及状态status
 */
if ($op == 'cart_view') {
//    global $_W,$_GPC;
    //会员的openid
    $openid = $_GPC["openid"];
//    $rights = 5;
    //查询会员等级享受的折扣
//    $res = pdo_fetch("select * from ".tablename("tg_member"),array("uniacid"=>$_GPC["uniacid"],"openid"=>$openid));
//    $level = $res["level"];//会员等级
//    //查询相应等级对应的权益是多少
//    $level_rights = pdo_fetch("select rights from ".tablename("tg_member_leave"),array("uniacid"=>$uniacid,'id'=>$level));
//    //计算会员折扣
//    $rights = $level_rights["rights"];
    if (empty($rights) || !$rights) {
        //不打折计算原价
        $discount = true;
    } else {
        $discount = false;
    }
    $list = pdo_fetchall('select id,sid,num,oprice,item,weight,discount_num from ' . tablename('tg_collect') . ' where uniacid=:uniacid and openid=:openid and orderno=:orderno', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid'], ':orderno' => 0));
    $id = $list['id'];
    foreach ($list as $key => $value) {

        $goods = pdo_fetch('select * from ' . tablename('tg_goods') . ' where id =:id', array(':id' => $value['sid']));
        $merchant = pdo_fetch('select name from ' . tablename('tg_merchant') . ' where id =:id', array(':id' => $goods['merchantid']));
        $list[$key]['img'] = tomedia($goods['gimg']);
        $list[$key]['price'] = doubleval($value['oprice']);
        $list[$key]['oprice'] = doubleval($value['oprice'] / ($value['discount_num'] / 10));
//        $list[$key]['price']= doubleval($list[$key]['oprice'] * $rights / 10);
        $list[$key]['title'] = $goods['gname'];
        $list[$key]['weight'] = $goods['weight'];
        $list[$key]['onelimit'] = $goods['one_limit'];
        $list[$key]['manylimit'] = $goods['many_limit'];
        $list[$key]['merchantid'] = $goods['merchantid'];
        $list[$key]['merchantname'] = $merchant['name'];
        $old_data = pdo_fetch("select num from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid'], ':g_id' => $value['sid']));
        $list[$key]['history_limit'] = intval($old_data['num']);
        $list[$key]['num'] = intval($value['num']);
        $list[$key]['sid'] = $goods['id'];
        $list[$key]['kucun'] = intval($goods['gnum']);
        $list[$key]['discount'] = $discount;
        if ($value['selected'] == 1) {
            $list[$key]['selected'] = true;
        } else {
            $list[$key]['selected'] = false;
        }

    }
    die(json_encode(array('list' => $list, 'status' => 1)));
}

/*
 * 购物车商品删除
 * 接口名 /min_shop_api.php?op=cart_del
 * 传入uniacid 用户openid  以及id
 * 返回状态status
 */
if ($op == 'cart_del') {
    $id = $_GPC['id'];
    pdo_delete('tg_collect', array('id' => $id));
    die(json_encode(array('status' => 1)));
}

/*
 * 购物车商品修改
 * 接口名 /min_shop_api.php?op=cart_edit
 * 传入uniacid 用户openid  以及id  商品gid数量num
 * 返回状态status
 */
if ($op == 'cart_edit') {
    $id = $_GPC['id'];
    $gid = $_GPC['gid'];
    $num = intval($_GPC['num']);

    $collect = pdo_fetch("select * from " . tablename('tg_collect') . " where id = '{$id}'");
    $str = $collect['item'];//规格
    $weight = $collect['weight'];//规格
    $kunum1 = pdo_fetch("SELECT productprice,stock FROM " . tablename('tg_goods_option') . " WHERE   goodsid = '{$gid}' and title = '{$str}' ");
    $price = $kunum1['productprice'];
    $gnum = $kunum1['stock'];

    $kunum = pdo_fetch("SELECT gnum FROM " . tablename('tg_goods') . " WHERE  uniacid = '{$_GPC['uniacid']}' and id = '{$gid}'  ");
    if (intval($gnum) == 0 && empty($str)) {
        $gnum = $kunum['gnum'];
    }
//    $num = $collect['num'] + 1;
    if ($num > intval($gnum)) {
        die(json_encode(array('status' => -1, 'result' => '库存不足')));
    }
    pdo_update('tg_collect', array('num' => $num), array('id' => $collect['id']));
    $status = 1;
    die(json_encode(array('status' => $status, 'result' => '修改购物车成功')));
}

/*
 * 购物车商品添加
 * 接口名 /min_shop_api.php?op=cart_add
 * 传入uniacid 用户openid  以及商品gid 商品规格 guige  weight
 * 返回状态status
 */
if ($op == 'cart_add') {

    $id = $_GPC['gid'];
    $str = $_GPC['guige'];//规格
    $num = intval($_GPC['num']);//数量
    if (empty($num)) {
        $num = 1;
    }
    $weight = floatval($_GPC['weight']);//重量
    $kunum1 = pdo_fetch("SELECT productprice,stock,weight FROM " . tablename('tg_goods_option') . " WHERE goodsid='{$id}' and title='{$str}'  ");
    $price = $kunum1['productprice'];
    $gnum = $kunum1['stock'];
    if ($weight == 0) {
        $weight = $kunum1['weight'];
    }
    if (empty($id)) {
//        echo 0;
//        exit;
        die(json_encode(array('status' => 0, 'result' => '添加的商品id为空')));
    } else {
        $sql = 'SELECT oprice,supprices,storeid,credit,commissiontype,commission,merchantid,gname,weight FROM ' . tablename('tg_goods') . ' WHERE id=:id and uniacid=:uniacid';
        $paramse = array(':id' => $id, ':uniacid' => $_GPC['uniacid']);
        $goods = pdo_fetch($sql, $paramse);
        if (empty($str)) {
            $price = $goods['oprice'];
            if ($weight == 0) {
                $weight = $goods['weight'];
            }
        }
        $discount = 10;
//        if ($goods['discount'] == 1) {
//
//            //证明是计算打折后的价钱 查询会员的等级和折扣详情
//            $level = pdo_fetch("select level from " . tablename("tg_member") . " where uniacid = :uniacid and openid = :openid", array(":uniacid" => $_GPC["uniacid"], ":openid" => $_GPC["openid"]));
//            if (!empty($level)) {
//                //获取会员的等级 匹配会员等级享受的折扣
//                $rights = pdo_fetch("select rights from " . tablename("tg_member_leave") . " where uniacid = :uniacid and id = :id", array(":uniacid" => $_GPC["uniacid"], ":id" => $level['level']));
//                $rights['rights'] = 5;
//                if (!empty($rights) || $rights['rights'] != 0) {
//                    $discount = $rights['rights'];
//                    $price = $price * $rights['rights'] / 10;
////                    计算价钱
//                }
//            }
//        }
        $data = array(
            'openid' => $_GPC['openid'],
            'uniacid' => $_GPC['uniacid'],
            'num' => $num,
            'oprice' => $price,
            'orderno' => 0,
            'applystatus' => 0,
            'optionid' => 0,
            'item' => $str,
            'weight' => $weight,
            'discount_num' => $discount,
            'supprices' => $goods['supprices'],
            'storeid' => $goods['storeid'],
            'credit' => $goods['credit'],
            'type' => $goods['commissiontype'],
            'commission' => $goods['commission'],
            'merchant_id' => $goods['merchantid'],
            'sid' => $id,
            'goodsname' => $goods['gname']
        );

        $tt = pdo_fetch("SELECT id,num FROM " . tablename('tg_collect') . " WHERE  uniacid = '{$_GPC['uniacid']}' and sid='{$id}'  and openid='{$_GPC['openid']}' and item='{$str}'  and orderno='0'");
        $kunum = pdo_fetch("SELECT gnum FROM " . tablename('tg_goods') . " WHERE  uniacid = '{$_GPC['uniacid']}' and id='{$id}'  ");
        if (intval($gnum) == 0 && empty($str)) {
            $gnum = $kunum['gnum'];
        }
//        $num=$tt['num']+1;
        if ($num > intval($gnum)) {
            $status = -1;
            die(json_encode(array('status' => -1, 'result' => '库存不足')));
        }
//        if ($num > 1){
//            die(json_encode(array('status' => -1 , 'result' => '库存不足')));
//        }
        if (empty($tt)) {
            if (pdo_insert('tg_collect', $data)) {
                $status = 1;
            } else {
                $status = 0;
            }
        } else {
            pdo_update('tg_collect', array('num' => $num), array('id' => $tt['id']));
            $status = 1;
        }
    }
    die(json_encode(array('status' => $status, 'result' => '添加购物车成功')));
}

/*
 * 购物车清空
 * 接口名 /min_shop_api.php?op=clear
 * 传入uniacid 用户openid
 * 返回状态status
 */
if ($op == 'clear') {
    if (pdo_delete('tg_collect', array('openid' => $openid, 'orderno' => 0))) {
        $status = 1;
    } else {
        $status = 0;
    }
    die(json_encode(array('status' => $status)));
}

/*
 * 订单提交
 * 接口名 /min_shop_api.php?op=order_submit
 * 传入uniacid 用户openid  以及商品gid 商品规格 guige  weight
 * 返回状态status
 */
if ($op == 'order_submit') {

    $typeid = intval($_GPC['typeid']);
    $couponid = intval($_GPC['couponid']);

    $str = '';
    $discount_fee = 0.00;

    if ($typeid == 1 || $typeid == 3) {
        $is_hexiao = 0;
    } elseif ($typeid == 2 || $typeid == 4) {
        $is_hexiao = 1;
        $chars = '0123456789';
        for ($i = 0; $i < 8; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        if (empty($_GPC['name'])) {
//            wl_json(0,"未填写提货人姓名");
            die(json_encode(array('status' => 0, 'result' => "未填写提货人姓名")));
        }
        if (empty($_GPC['mobile'])) {
//            wl_json(0,"未填写提货人电话");
            die(json_encode(array('status' => 0, 'result' => "未填写提货人电话")));
        }
        $adress_fee['cname'] = $_GPC['name'];
        $adress_fee['tel'] = $_GPC['mobile'];
        $bdeltime = strtotime($_GPC['gettime']);
        pdo_update('tg_member', array('addname' => $_GPC['name'], 'addmobile' => $_GPC['mobile']), array('openid' => $openid, 'uniacid' => $_GPC['uniacid']));

    }

    if ($couponid) {
        $coutp = coupon_handle($openid, $couponid, $pay_price);
        if (!is_array($coutp)) {
            $pay_price = currency_format($pay_price - $coutp);
            $is_usecard = 1;
            $discount_fee = $coutp;
        } else {
//            wl_json(0,$coutp['message']);
            die(json_encode(array('status' => 0, 'result' => $coutp['message'])));
        }
    }
    if (!empty($_GPC['senddate'])) {
        $date1 = date('Y-m-d');
        if ($_GPC['senddate'] == 1) {
            $dtime = date('Y-m-d');
        } elseif ($_GPC['senddate'] == 2) {
            $dtime = date('Y-m-d', strtotime($date1 . "+1 day"));
        } elseif ($_GPC['senddate'] == 3) {
            $dtime = date('Y-m-d', strtotime($date1 . "+2 day"));
        }
    }
    $data = array(
        'uniacid' => $_GPC['uniacid'],
        'gnum' => 0,
        'openid' => $openid,
        'ptime' => '',//支付成功时间
        'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
        'pay_price' => $pay_price,
        'goodsprice' => $pay_price + $discount_fee - $_GPC['freight'],
        'freight' => $_GPC['freight'],
        'first_fee' => 0,
        'status' => 0,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
        'addressid' => $adress_fee['id'],
        'addresstype' => $adress_fee['type'],//1公司2家庭
        'dispatchtype' => $_GPC['link_dispatchtype'],//配送方式
        'comadd' => $_GPC['link_zt'],//
        'addname' => $adress_fee['cname'],
        'mobile' => $adress_fee['tel'],
        'address' => $adress_fee['province'] . $adress_fee['city'] . $adress_fee['county'] . $adress_fee['detailed_address'],
        'g_id' => 0,
        'tuan_id' => 0,
        'is_tuan' => 0,
        'tuan_first' => 0,
        'discount_fee' => $discount_fee,
        'starttime' => TIMESTAMP,
        'remark' => $_GPC['remark'],
        'comtype' => 0,
        'senddate' => $dtime,
        'selltype' => 0,
        'sendtime' => $_GPC['sendtime'],
        'commission' => 0,
        'commissiontype' => 0,
        'endtime' => 0,
        'is_hexiao' => $is_hexiao,
        'hexiaoma' => $str,
        'couponid' => $couponid,
        'is_usecard' => $is_usecard,
        'createtime' => TIMESTAMP,
        'bdeltime' => $bdeltime
    );
    if (pdo_insert('tg_order', $data)) {
        pdo_update('tg_collect', array('orderno' => $data['orderno']), array('openid' => $openid, 'uniacid' => $_GPC['uniacid'], 'orderno' => 0));
    }
    if ($typeid == 2 || $typeid == 4) {
        /*二维码*/
//        wl_load()->classs('qrcode');
//        $createqrcode = new creat_qrcode();
//        $createqrcode->creategroupQrcode($data['orderno']);
    }
    /*
        $col_list=pdo_fetchall('select * from '.tablename('tg_collect')." where orderno=':orderno' ",array(':orderno'=>$data['orderno']));
        if(count($col_list)==0){
            pdo_update('tg_order',array('status'=>9),array('orderno'=>$data['orderno']));
            $neworder=pdo_fetch('select * from '.tablename('tg_order').' where uniacid=:uniacid and status=0 and openid=:openid and is_tuan=0 order by id desc  limit 1',array(':uniacid'=>$_W['uniacid'],':openid'=>$_W['openid']));
            if(!empty($neworder)){
                $data['orderno']=$neworder['orderno'];
                $data['pay_price']=$neworder['pay_price'];
            }
        }
    */
    $params['orderno'] = $data['orderno'];
    $params['pay_price'] = $data['pay_price'];
//	$params['status'] = $data['status'];

//    wl_json(1,$params);
    die(json_encode(array('status' => 1, 'result' => $params)));

}

/*
 * 热搜查询
 * 接口名 /min_shop_api.php?op=hot_search_view
 * 返回查询到的热词 以及状态status
 */
if ($op == 'hot_search_view') {
    $data = pdo_fetchall("select * from " . tablename('tg_hot_search') . " where uniacid = '" . $uniacid . "' order by sort desc limit 0 , 10");
    die(json_encode(array('list' => $data, 'status' => 1)));
}

/*
 * 添加热搜
 * 接口名 /min_shop_api.php?op=hot_search_add
 * 返回状态status
 */
if ($op == 'hot_search_add') {
    $data['uniacid'] = $_GPC['uniacid'];
    $data['hot_word'] = $_GPC['hot_word'];
    $data['times'] = $_GPC['times'];
    $data['sort'] = $_GPC['sort'];
    $status = pdo_insert('tg_hot_search', $data);
    die(json_encode(array('list' => $data, 'status' => 1)));
}

/*
 * 修改热搜
 * 接口名 /min_shop_api.php?op=hot_search_edit
 * 返回状态status
 */
if ($op == 'hot_search_edit') {
    $id = $_GPC['id'];
    $data['uniacid'] = $_GPC['uniacid'];
    $data['hot_word'] = $_GPC['hot_word'];
    $data['times'] = $_GPC['times'];
    $data['sort'] = $_GPC['sort'];
    $status = pdo_update('tg_hot_search', $data, array('id' => $id));
    die(json_encode(array('list' => $data, 'status' => $status)));
}

/*
 * 商品订单查询
 * 接口名 /min_shop_api.php?op=search_order
 * 返回查询到的订单信息 以及状态status
 */
if ($op == 'search_order') {

    $uniacid = $_GPC['uniacid'];
    $openid = $_GPC['openid'];
    $sql = "select * from " . tablename('tg_order') . " where uniacid = :uniacid and openid = :openid";
    $sql .= " order by createtime desc ";
    $page = !empty($_GPC['page']) ? $_GPC['page'] : 1;
    $pagesize = 10;
    $sql .= " limit " . ($page - 1) * $pagesize . ' , ' . $pagesize;

    $list = pdo_fetchall($sql, array(':uniacid' => $uniacid, ':openid' => $openid));

    die(json_encode(array('data' => $list, 'result' => $result)));
}

if ($op == 'order_list') {

    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $status = $_GPC['status'];
    $condition = ' and `uniacid` = :uniacid and openid = :openid';
    $params = array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid']);
    if ($_GPC['status'] >= 0) {
        $condition .= "  AND status = '{$status}'";
    }
    $orderby = 'order by id desc';
    $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
    $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
    $sql = "SELECT * FROM " . tablename('tg_order') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql, $params);

    foreach ($list as &$value) {
        if ($value['g_id'] == 0) {
            $collect = pdo_fetchall("select * from " . tablename('tg_collect') . " where orderno = '{$value['orderno']}' ");
            $value['collect'] = $collect;
        }
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . "where 1 $condition ", $params);

    $data = array();
    $data['condition'] = $condition;
    $data['list'] = $list;
    $data['total'] = $total;

    die(json_encode($data));
    //die($sql);

}


//团模式接龙
if ($op == 'detail_group') {
    $g_id = intval($_GPC['gid']);
    $group = pdo_fetch("select * from " . tablename('tg_group') . " where uniacid = '{$uniacid}' and goodsid = '{$g_id}' and groupstatus = 3 order by id desc ");
    $data['group'] = $group;
    $status = 1;
    if ($group) {
        $dat['uniacid'] = $uniacid;
        $dat['openid'] = $openid;
        $dat['tuan_id'] = $group['groupnumber'];
        $dat['goodsid'] = $g_id;
        $dat['createtime'] = TIMESTAMP;
        $tuan = pdo_fetch("select id from " . tablename('tg_puv_record_tuan') . " where uniacid = '{$uniacid}' and openid = '{$openid}' and tuan_id = '{$group['groupnumber']}' ");
        if (!$tuan) {
            pdo_insert('tg_puv_record_tuan', $dat);
        }
        $order = pdo_fetchall("select id,uniacid,openid,gnum,goodsname,ptime,optionname from " . tablename('tg_order') . " where uniacid = '{$uniacid}' and tuan_id = '{$group['groupnumber']}' and status <> 9 order by id ");
        foreach ($order as &$item) {
            $member = pdo_fetch("select nickname,avatar from " . tablename('tg_member') . " where uniacid = '{$item['uniacid']}' and from_user = '{$item['openid']}' ");
            $item['nickname'] = $member['nickname'];
            $item['avatar'] = $member['avatar'];
            $item['sbx'] = (TIMESTAMP - $item['ptime']) / 3600;
            unset($item);
        }
        $pv = pdo_fetchcolumn("select count(*) from " . tablename('tg_puv_record_tuan') . " where tuan_id = '{$group['groupnumber']}' ");
        $data['pv'] = intval($pv);
        $data['order'] = $order;
    } else {
        $status = 0;
    }
    die(json_encode(array('status' => $status, 'data' => $data)));
}

//商品模式接龙
if ($op == 'detail_goods') {
    $g_id = intval($_GPC['gid']);
    $group = pdo_fetch("select * from " . tablename('tg_group') . " where uniacid = '{$uniacid}' and goodsid = '{$g_id}' and groupstatus = 3 order by id desc ");
    $data['group'] = $group;
    $status = 1;
//    if ($group) {
    $dat['uniacid'] = $uniacid;
    $dat['openid'] = $openid;
    $dat['tuan_id'] = $group['groupnumber'];
    $dat['goodsid'] = $g_id;
    $dat['createtime'] = TIMESTAMP;
    $goods = pdo_fetch("select id from " . tablename('tg_puv_record_tuan') . " where uniacid = '{$uniacid}' and openid = '{$openid}' and goodsid = '{$g_id}' ");
    if (!$goods) {
        pdo_insert('tg_puv_record_tuan', $dat);
    }
    $order = pdo_fetchall("select id,uniacid,openid,gnum,goodsname,ptime,optionname from " . tablename('tg_order') . " where uniacid = '{$uniacid}' and g_id = '{$g_id}' and status in (1,2,3,8) order by id ");
    foreach ($order as &$item) {

        $member = pdo_fetch("select nickname,avatar from " . tablename('tg_member') . " where uniacid = '{$item['uniacid']}' and from_user = '{$item['openid']}' ");
        $item['nickname'] = $member['nickname'];
        $item['avatar'] = $member['avatar'];
        $item['sbx'] = (TIMESTAMP - $item['ptime']) / 3600;
        unset($item);
    }
    $pv = pdo_fetchcolumn("select count(*) from " . tablename('tg_puv_record_tuan') . " where goodsid = '{$g_id}' ");
    $data['pv'] = intval($pv);
    $data['order'] = $order;
//    } else {
//        $status = 0;
//    }
    die(json_encode(array('status' => $status, 'data' => $data)));
}
