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
$_SESSION['type'] = NULL;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$notice = pdo_fetchall("SELECT * FROM " . tablename('tg_notices') . " WHERE enabled = 1 and uniacid = '{$_W['uniacid']}' ORDER BY id DESC");
//全民兼职  开始

$tid = 8167;

$tid = 8167;//

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





//ceshi

$tpl = 'goods/goods_list15';//多商户 商城

$member = member_get_by_params("from_user='" . $_W['openid'] . "'");
if (!$checkfunction['status'] || $member['parentid'] == -1) {
    $data = array('parentid' => 99);
    $params = array('from_user' => $_W['openid']);
    member_update_by_params($data, $params);
}
$pagetitle = !empty($config['tginfo']['sname']) ? $config['tginfo']['sname'] : '乐享拼购';
//全民兼职 结束
$shop = pdo_fetch("select * from " .tablename('account_wechats') ." where uniacid = :uniacid" , array(':uniacid' => $_W['uniacid']));
$con="";
if ( $shop['is_merchant'] == 1) {
$con=" and  activities_type=1 ";
}
$activities_type = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and enabled=1 and parentid=0 {$con} ORDER BY parentid ASC, displayorder DESC");
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and enabled=1 and parentid=0 and  activities_type=0  ORDER BY parentid ASC, displayorder DESC");
foreach($activities_type as &$item){
    if(empty($item['url'])){
        $item['url']=app_url('goods/list/fenlei_detail',array('gid'=>$item['id']));
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
        if (substr($adv['link'], 0, 5) != 'http:') {
            $adv['link'] = "http://" . $adv['link'];
        }
        unset($adv);
    }

    if ($cid > 0) {
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
    $cid = intval($_GPC['cid']);
    if ($cid == 0){
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

    $data = goods_get_list(array('usepage' => 1, 'ishows' => '1,3', 'page' => $page, 'pagesize' => $pagesize, 'lin' => 0, 'is_hunda' => $is_hunda, 'tuan' => $tuan, 'cid' => $cid, 'gname' => $keyword, 'is_member' => $is_member, 'orderby' => 'ORDER BY displayorder DESC'));
    foreach ($data['list'] as $key => $value) {
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$value['id']}' limit 0,3 ");
        $data['list'][$key]['params'] = $params;
        //规格及规格项
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

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
        $condition .= "  AND fk_typeid = '{$cid}' ";
    }
  //  if ($merchantid > 0) {
        $condition .= "   AND merchantid='{$merchantid}'";
  //  }

    $orderby = 'order by id desc';
    $page = !empty($_GPC['page']) ? intval($_GPC['page']) : 1;
    $pagesize = !empty($_GPC['pagesize']) ? intval($_GPC['pagesize']) : 10;
    $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    file_put_contents(TG_DATA .$_W['uniacid']. "merchantajax.log", var_export($sql, true) . PHP_EOL, FILE_APPEND);
    $list = pdo_fetchall($sql, $params);
//die(json_encode(array('bbb'=>$condition)));
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
        if ($value['one_group'] == 1) {
            $group = pdo_fetch("select * from " . tablename('tg_group') . ' where goodsid=:goodsid and uniacid=:uniacid and groupstatus=3 and neednum<>lacknum order by lacknum asc limit 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $value['id']));

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
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_GPC['uniacid'], ':openid' => $_GPC['openid'], ':g_id' => $value['id']));
        $data['list'][$key]['history_limit'] = intval($old_data['num']);
    }
    $goodses = $data;
    die(json_encode($goodses));
}
if ($op == 'ajax') {
    $page = $_GPC['page'];
    $pagesize = $_GPC['pagesize'];
    $cid = intval($_GPC['cid']);
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
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

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
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

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
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

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
        $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $value['id']));
        $data['list'][$key]['options'] = count($options);
        $old_data = pdo_fetch("select * from " . tablename('tg_goods_openid') . ' where uniacid=:uniacid and openid=:openid and g_id=:g_id', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']));

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

    $option = pdo_fetch("select * from " . tablename('tg_goods_option') . " where title=:id ", array(':id' => $strname));

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
    $allspecs = pdo_fetchall("select * from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(':id' => $id));
    foreach ($allspecs as &$s) {
        $s['items'] = pdo_fetchall("select * from " . tablename('tg_spec_item') . " where  `show`=1 and specid=:specid order by displayorder asc", array(":specid" => $s['id']));

    }

    unset($s);
    $options = pdo_fetchall("select id,title,thumb,marketprice,productprice,costprice,specs,stock,weight from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $id));
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
        $th = pdo_fetch('select thumb from ' . tablename('tg_spec_item') . ' where id=:id', array(':id' => $value['specs']));
        $options[$key]['thumb'] = tomedia($th['thumb']);
        $options[$key]['specs'] = $specs;
    }
    die(json_encode($options));
}

if ($op == 'add') {
    $id = $_GPC['id'];
    $str = $_GPC['str'];//规格
    $weight = $_GPC['weight'];//规格
    $kunum1 = pdo_fetch("SELECT productprice,stock,weight FROM " . tablename('tg_goods_option') . " WHERE   goodsid='{$id}' and title='{$str}'  ");
    $price = $kunum1['productprice'];
    $gnum = $kunum1['stock'];
    if (empty($weight)) {
        $weight = $kunum1['weight'];
    }
    if (empty($id)) {
        echo 0;
        exit;
    } else {
        $sql = 'SELECT oprice,supprices,storeid,credit,commissiontype,commission,merchantid,discount FROM ' . tablename('tg_goods') . ' WHERE id=:id and uniacid=:uniacid';
        $paramse = array(':id' => $id, ':uniacid' => $_W['uniacid']);
        $goods = pdo_fetch($sql, $paramse);
        if (empty($str)) {
            $price = $goods['oprice'];
        }

        //会员的openid
        $openid = $_W["openid"];
        //查询会员等级享受的折扣
        $res = pdo_fetch("select * from ".tablename("tg_member")." where uniacid=:uniacid and openid=:openid",array(":uniacid"=>$_W["uniacid"],":openid"=>$openid));
        $level = $res["level"];//会员等级
        //查询相应等级对应的权益是多少
        $level_rights = pdo_fetch("select * from ".tablename("tg_member_leave")." where uniacid=:uniacid and id=:id",array(":uniacid"=>$_W["uniacid"],':id'=>$level));
        //计算会员折扣
        $rights = $level_rights["rights"];
        $discount_num=10;
        if($goods['discount']==1)
        {
            if ((empty($rights) || !$rights)){
                //不打折计算原价
                $discount_num=10;
            }else{
                $price=$price*$rights/100;
                $discount_num=$rights;
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
            'discount_num' =>$discount_num,
            'sid' => $id
        );
        $logdata=array(
            'goods'=>json_encode($goods),
            'data'=>json_encode($data),
            'kunum1'=>$kunum1,
            'price'=>$price,
            'openid'=>$_W['openid'],
            'uniacid'=>$_W['uniacid']
        );
        file_put_contents(TG_DATA."collect_add.log", var_export($logdata, true).PHP_EOL, FILE_APPEND);

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
    $goods = pdo_fetchall("select id,name from " . tablename('tg_delivery_template') . " where uniacid=:uniacid and merchant_id=:merchantid and status=2", array(':uniacid' => $_W['uniacid'], ':merchantid' => $id));
    die(json_encode($goods));
}
if ($op == 'address_fee') {
    $goods = pdo_fetch("select * from " . tablename('tg_address') . "where openid = '{$_W['openid']}' and status = 1");
    die(json_encode($goods));
}
if ($op == 'search') {
    $keyword = $_GPC['keyword'];
    //$goods = goods_get_list(array('gname'=>$keyword));
    if ($acct['tpl']==8200){
        include wl_template('goods/goods_list14');
    }else{
        include wl_template($tpl);
    }
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
        if (substr($adv['link'], 0, 5) != 'http:') {
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
    $res = pdo_fetchall("select * from " . tablename("messikefu_cservice") . " where  merchant_id=" . $id . " and weid=" . $_W['uniacid']);
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
if ($op == "notices"){
    $pagetitle = !empty($config['tginfo']['sname']) ? '公告详情 - '.$config['tginfo']['sname'] : '公告详情';
    $id = intval($_GPC["id"]);
    if(!empty($id)){
        $notice = pdo_fetch("SELECT * FROM " . tablename('tg_notices') . " WHERE id = :id",array(":id"=>$id));
    }else{
        message('缺少参数，请返回首页！');
    }
    include wl_template('goods/notice_detail');
}
if($op == "up_num"){
    $play__url = $_GPC["id"];
    $num = pdo_fetch("select * from ".tablename("account_wechats")." where uniacid=:uniacid",array(":uniacid"=>$_W["uniacid"]));

    if (intval($num["play_num"]) == 0){
        $res = array("status"=>"error","data"=>"已经没有播放次数");
        die(json_encode($res));
    }
    $res3["play_num"] = $num["play_num"]-1;
    pdo_update("account_wechats",$res3,array(":uniacid"=>$_W["uniacid"]));
    $res = pdo_fetch("select * from ".tablename("tg_goods_video")." where id =  :id",array(":id"=>$play__url));
    $url = $res["media_url"];
    if ($res){
        $res2["play_num"] = intval($res["play_num"])+1;
        pdo_update("tg_goods_video",$res2,array(":media_url"=>'%'.$url.'%'));
        $res = array("status"=>"success","data"=>"");
        die(json_encode($res));
    }else{
        $res = array("status"=>"error","data"=>"已经没有播放次数");
        die(json_encode($res));
    }
}
exit();