<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/7/25
 * Time: 20:01
 */
global $_W, $_GPC;
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_goods_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT '0',
  `merchantid` int(11) DEFAULT '0',
  `category_id` int(11) DEFAULT '0',
  `goods_id` int(11) DEFAULT '0',
  `status` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT '0',
  `updatetime` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
if (!pdo_fieldexists('tg_goods_activities', 'category_id')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods_activities') . " ADD `category_id` int(11)  NOT NULL;");
}
if (!pdo_fieldexists('tg_goods_activities', 'old_category_id')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods_activities') . " ADD `old_category_id` int(11)  NOT NULL;");
}
if (!pdo_fieldexists('tg_goods_activities', 'old_category_id')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods_activities') . " ADD `old_category_id` int(11)  NOT NULL;");
}
$op = empty($_GPC['op']) ? 'display' : $_GPC['op'];
if ($op == 'display') {
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $sortname = "status";
    $sortasc = "asc";
    $condition = " WHERE 1 and uniacid = {$_W['uniacid']} and status > -1 ";
    if ($_W['user']['merchant_id'] > 0){
        $condition .= " and merchantid = '{$_W['user']['merchant_id']}'";
    }
    $keyword = $_GPC['keyword'];
    if (!empty($keyword)){
        $condition .= " and goods_id in (select id from " .tablename('tg_goods') ." where gname like '%{$keyword}%')";
    }

    $sqlTotal = pdo_sql_select_count_from('tg_goods_activities') . $condition;
    $sqlData = pdo_sql_select_all_from('tg_goods_activities') . $condition . ' ORDER BY ' . $sortname . ' ' . $sortasc;
    $goodses = pdo_pagination($sqlTotal, $sqlData, $params, 'id', $total, $page, $size);
    $pager = pagination($total, $page, $size);
    foreach ($goodses as &$value) {
        if (empty($value['merchantid'])){
            $value['merchant_name'] = $_W['account']['name'];
        }else{
            $merchant = pdo_fetch("select name from " .tablename('tg_merchant') ." where uniacid = '{$_W['uniacid']}' and id = '{$value['merchantid']}'");
            $value['merchant_name'] = $merchant['name'];
        }
        $category = pdo_fetch("select name from " .tablename('tg_category') ." where uniacid = '{$_W['uniacid']}' and id = '{$value['category_id']}'");
        $value['cate_name'] = $category['name'];
        $old_category = pdo_fetch("select name from " .tablename('tg_category') ." where uniacid = '{$_W['uniacid']}' and id = '{$value['old_category_id']}'");
        $value['old_cate_name'] = $old_category['name'];
        $good = pdo_fetch("select gname from " .tablename('tg_goods') ." where uniacid = '{$_W['uniacid']}' and id = '{$value['goods_id']}'");
        $value['goods_name'] = $good['gname'];
    }
    unset($condition);
//    var_dump("中国");
//    die(json_encode($goodses));
}

if ($op == 'post') {
//    $category = pdo_fetch_many('tg_category', array('uniacid' => $_W['uniacid']), array(), '', 'ORDER BY `parentid`, `displayorder` DESC, id ASC');
    $category = pdo_fetchall("select * from " . tablename('tg_category') . " where uniacid = '{$_W['uniacid']}' and activities_type = 1 ORDER BY `parentid`, `displayorder` DESC, id ASC");

}

if ($op == 'change'){

    $fun = $_GPC['funcop'];
    $id = $_GPC['id'];
    if ($fun == 'check'){
        $displayorder = $_GPC['displayorder'];
        if (pdo_update('tg_goods_activities' , array('status' => 1), array('id' => $id))) {
            $active = pdo_fetch("select * from " .tablename('tg_goods_activities') ." where uniacid = '{$_W['uniacid']}' and id = '{$id}'");
            if ($active['category_id'] == -1){
                pdo_update('tg_goods' , array('showindex' => 0 , 'displayorder' => $displayorder) , array('id' => $active['goods_id']));
            }else{
                pdo_update('tg_goods' , array('fk_typeid' => $active['category_id'] , 'displayorder' => $displayorder) , array('id' => $active['goods_id']));
            }
            die(json_encode(array("errno" => 0, 'message' => '审核成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '审核失败')));
        }
    } elseif ($fun == 'pass'){
        if (pdo_update('tg_goods_activities' , array('status' => 2), array('id' => $id))) {
            $active = pdo_fetch("select * from " .tablename('tg_goods_activities') ." where uniacid = '{$_W['uniacid']}' and id = '{$id}'");
            pdo_update('tg_goods' , array('fk_typeid' => $active['old_category_id']) , array('id' => $active['goods_id']));
            die(json_encode(array("errno" => 0, 'message' => '下架成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '下架失败')));
        }
    }elseif ($fun == 'delete'){
        if (pdo_update('tg_goods_activities' , array('status' => -1), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '删除成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '删除失败')));
        }
    }


}

if ($op == 'submit') {
    $data['uniacid'] = $_W['uniacid'];
    $data['merchantid'] = $_W['user']['merchant_id'];
    $data['category_id'] = $_GPC['category_id'];
    $data['old_category_id'] = $_GPC['category_parentid'];
    $data['goods_id'] = $_GPC['goodsid'];
    $data['status'] = 0;
    $data['createtime'] = TIMESTAMP;
    pdo_insert('tg_goods_activities', $data);

    die(json_encode(array('status' => 1, 'data' => $data)));
}

if ($op == 'ajax') {
    $condition = "";
    if (!empty($_GPC['keyword'])) {
        $condition = " and gname like '%" . $_GPC['keyword'] . "%'";
    }
    $goods = pdo_fetchall('select id,gimg,gname,category_parentid from ' . tablename('tg_goods') . ' where uniacid=:uniacid and merchantid=:merchantid and isshow=1 ' . $condition, array(':uniacid' => $_W['uniacid'], ':merchantid' => $_W['user']['merchant_id']));
    include wl_template('goods/query_goods');
    exit();
}

include wl_template('goods/goods_activities');

exit();
