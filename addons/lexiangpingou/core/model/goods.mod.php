<?php

/**
 * [weliam] Copyright (c) 2016/3/23
 * 商品model
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('functions');
/**
 * 函数getGoodsList，按检索条件检索出所有商品
 * $params : 类型：array
 *
 */
function goods_get_list($args)
{
    global $_W;
    $condition = ' and `uniacid` = :uniacid and shenhe=0';
    $params = array(':uniacid' => $_W['uniacid']);
    $setting = setting_get_by_name("wholesale");
    if (is_array($args)) {
        $usepage = !empty($args['usepage']) ? $args['usepage'] : false;
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $orderby = !empty($args['orderby']) ? $args['orderby'] : 'order by id desc';

        $ishows = !empty($args['ishows']) ? trim($args['ishows']) : '';
        $condition = ' and `uniacid` = :uniacid and shenhe=0';
        $params = array(':uniacid' => $_W['uniacid']);

        //    批发限制
        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];
        if($setting['apply'] == 1){
//        获取当前用户是否批发会员
            $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
            if ($member['wholesaler_apply'] == 0){
                $condition .= " and is_wholesale = 0";
            }
            if ($member['wholesaler_apply'] == 1 && $member['wholesaler_status'] != 1){
                $condition .= " and is_wholesale  = 0";
            }

        }

        if (!empty($ishows)) {
            $condition .= " and isshow in ( " . $ishows . ")";
        }
        $isnew = !empty($args['isnew']) ? 1 : 0;
        if (!empty($isnew)) {
            $condition .= " and isnew=1";
        }
        $ishot = !empty($args['ishot']) ? 1 : 0;
        if (!empty($ishot)) {
            $condition .= " and ishot=1";
        }
        $group_level_status = $args['group_level_status'];
        if ($group_level_status != '') {
            $condition .= " and group_level_status in ({$group_level_status}) ";
        }
        $isrecommand = !empty($args['isrecommand']) ? 1 : 0;
        if (!empty($isrecommand)) {
            $condition .= " and isrecommand=1";
        }
        $isdiscount = !empty($args['isdiscount']) ? 1 : 0;
        if (!empty($isdiscount)) {
            $condition .= " and isdiscount=1";
        }
        $gname = !empty($args['gname']) ? $args['gname'] : '';
        if (!empty($gname)) {
            $condition .= "  AND gname like '%{$gname}%' ";
        }
        $category_childid = $args['category_childid'];
        if (!empty($category_childid)) {
            $condition .= "  AND category_childid = '{$category_childid}'";
        }
        $cid = $args['cid'];
        if (!empty($cid)) {
            $condition .= "  AND fk_typeid = '{$cid}'";
        }
        if (empty($cid) && empty($category_childid) && empty($gname)) {
            $condition .= "  AND showindex = 0";
        }

        $lin = $args['lin'];
        $tuan = $args['tuan'];
        $is_hunda = intval($args['is_hunda']);
        $m_ajax = $args['m_ajax'];
        if ($is_hunda == 0) {
            if (empty($gname)) {
                if ($tuan == 0) {
                    $condition .= "  AND selltype = 0 ";
                }
                if ($tuan == 1) {
                    $condition .= "  AND selltype != 0 ";
                }
            } elseif ($m_ajax == 1) {
                if ($tuan == 0) {
                    $condition .= "  AND selltype = 0 ";
                }
                if ($tuan == 1) {
                    $condition .= "  AND selltype != 0 ";
                }
            }
        }
        if (empty($gname)) {
            $is_member = intval($args['is_member']);
            if ($is_member == 1) {
                $condition .= "  AND discount = 1 ";
            } else {
                $condition .= "  AND discount = 0 ";
            }
        }

        /*
        if($tuan==0||$lin==1)
        {
            if($tuan==0&&$lin==0)
            {
                $condition .= "  AND selltype = 0 ";
            }
            if($tuan==1&&$lin==0)
            {
                $condition .= "  AND ( selltype = 1)";
            }
            if($tuan==0&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 or selltype = 0)";
            }
            if($tuan==1&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 )";
            }
        }*/
    } else {
        $condition .= $args;
        $orderby = '';
    }
    if (empty($gname)) {
        $condition .= " AND spike = 0 ";
    }
    $condition .= " and is_public = 1 ";
    if ($usepage) {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    } else {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby}";
    }
    $list = pdo_fetchall($sql, $params);
    $istuanyouhui = checkfunc(8159);//判定规格
    $isguige = checkfunc(8160);//判定规格
    $islingou = checkfunc(8161);//判定邻购
    $isonelimit = checkfunc(8163);//单次购买数量上限
    $ismanylimit = checkfunc(8164);//每人购买总数量上限
    $isjieti = checkfunc(8173);//判定阶梯
    $isjudgment = checkfunc(8194);//判定评价
    $isjieti2 = checkfunc(8195);//判定阶梯
    foreach ($list as $key => &$value) {
        if (empty($value['share_image'])) {
            $value['share_image'] = tomedia($value['gimg']);
        } else {
            $value['share_image'] = tomedia($value['share_image']);
        }
        $params_openid = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']);
        $history_buy = pdo_fetch('SELECT num FROM ' . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND g_id=:g_id AND openid=:openid', $params_openid);

        $value['gimg'] = tomedia($value['gimg']);
        if ($value['one_group'] == 1) {
            $group = pdo_fetch("SELECT * FROM " . tablename('tg_group') . ' WHERE goodsid=:goodsid AND uniacid=:uniacid AND groupstatus=3 AND neednum<>lacknum ORDER BY lacknum ASC LIMIT 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $value['id']));

            if (empty($group)) {
                $value['a'] = app_url('goods/detail/displays', array('id' => $value['id']));
            } else {
                $value['a'] = app_url('order/group', array('tuan_id' => $group['groupnumber']));
            }

        } else {
            $value['a'] = app_url('goods/detail/displays', array('id' => $value['id'], 'historylimit' => intval($history_buy['num'])));
        }

        if ($istuanyouhui['status'] == 0) {
            $value['is_discount'] = 0;
        }
        if ($isguige['status'] == 0) {
            $value['hasoption'] = 0;
        }
        if ($islingou['status'] == 0 && $value['selltype'] == 2) {
            $value['selltype'] = 1;
        }
        if ($isjieti['status'] == 0) {
            if ($value['selltype'] == 3 || $value['selltype'] == 4) {
                $value['selltype'] = 1;
            }
            $value['group_level_status'] = 0;
        }
        if ($ismanylimit['status'] == 0) {
            $value['many_limit'] = 0;
        }
        if ($isonelimit['status'] == 0) {
            $value['one_limit'] = 0;
        }
        if ($isjudgment['status'] == 0) {
            $value['isjudgment'] = 0;
        }
        if ($value['selltype'] == 4) {
            $group_level = unserialize($value['group_level']);

            $gprice = $group_level[0]['groupprice'];
            foreach ($group_level as $item) {
                if ($gprice > $item['groupprice']) {
                    $gprice = $item['groupprice'];
                }
            }
            $value['gprice'] = $gprice;
        }
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_goods') . "where 1 $condition ", $params);

    $data = array();
    $data['list'] = $list;
    $data['total'] = $total;
    return $data;
}

function goods_get_list_dan($args)
{
    global $_W;
    $condition = ' and `uniacid` = :uniacid and shenhe=0';
    $params = array(':uniacid' => $_W['uniacid']);

    $setting = setting_get_by_name("wholesale");

    if (is_array($args)) {
        $usepage = !empty($args['usepage']) ? $args['usepage'] : false;
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $orderby = !empty($args['orderby']) ? $args['orderby'] : 'order by id desc';

        $ishows = !empty($args['ishows']) ? trim($args['ishows']) : '';
        $condition = ' and `uniacid` = :uniacid and shenhe=0';
        $params = array(':uniacid' => $_W['uniacid']);
        //    批发限制
        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];
        if($setting['apply'] == 1){
//        获取当前用户是否批发会员
            $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
            if ($member['wholesaler_apply'] == 0){
                $condition .= " and is_wholesale = 0";
            }
            if ($member['wholesaler_apply'] == 1 && $member['wholesaler_status'] != 1){
                $condition .= " and is_wholesale  = 0";
            }

        }

        if (!empty($ishows)) {
            $condition .= " and isshow in ( " . $ishows . ")";
        }
        $isnew = !empty($args['isnew']) ? 1 : 0;
        if (!empty($isnew)) {
            $condition .= " and isnew=1";
        }
        $ishot = !empty($args['ishot']) ? 1 : 0;
        if (!empty($ishot)) {
            $condition .= " and ishot=1";
        }
        $group_level_status = $args['group_level_status'];
        if ($group_level_status != '') {
            $condition .= " and group_level_status in ({$group_level_status}) ";
        }
        $isrecommand = !empty($args['isrecommand']) ? 1 : 0;
        if (!empty($isrecommand)) {
            $condition .= " and isrecommand=1";
        }
        $isdiscount = !empty($args['isdiscount']) ? 1 : 0;
        if (!empty($isdiscount)) {
            $condition .= " and isdiscount=1";
        }
        $gname = !empty($args['gname']) ? $args['gname'] : '';
        if (!empty($gname)) {
            $condition .= "  AND gname like '%{$gname}%' ";
        }
        $category_childid = $args['category_childid'];
        if (!empty($category_childid)) {
            $condition .= "  AND category_childid = '{$category_childid}'";
        }
        $cid = $args['cid'];
        if (!empty($cid)) {
            $condition .= "  AND fk_typeid = '{$cid}'";
        }
        if (empty($cid) && empty($category_childid) && empty($gname)) {
            $condition .= "  AND showindex = 0";
        }

        $shop_storeid = $args['shop_storeid'];

        $lin = $args['lin'];
        $tuan = $args['tuan'];
        $is_hunda = intval($args['is_hunda']);
        $m_ajax = $args['m_ajax'];
        if ($is_hunda == 0) {
            if (empty($gname)) {
                if ($tuan == 0) {
                    $condition .= "  AND selltype = 0 ";
                }
                if ($tuan == 1) {
                    $condition .= "  AND selltype != 0 ";
                }
            } elseif ($m_ajax == 1) {
                if ($tuan == 0) {
                    $condition .= "  AND selltype = 0 ";
                }
                if ($tuan == 1) {
                    $condition .= "  AND selltype != 0 ";
                }
            }
        }
        if (empty($gname)) {
            $is_member = intval($args['is_member']);
            if ($is_member == 1) {
                $condition .= "  AND discount = 1 ";
            } else {
                $condition .= "  AND discount = 0 ";
            }
        }

        /*
        if($tuan==0||$lin==1)
        {
            if($tuan==0&&$lin==0)
            {
                $condition .= "  AND selltype = 0 ";
            }
            if($tuan==1&&$lin==0)
            {
                $condition .= "  AND ( selltype = 1)";
            }
            if($tuan==0&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 or selltype = 0)";
            }
            if($tuan==1&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 )";
            }
        }*/
    } else {
        $condition .= $args;
        $orderby = '';
        //    批发限制
        $uniacid = $_W['uniacid'];
        $openid = $_W['openid'];
        if($setting['apply'] == 1){
//        获取当前用户是否批发会员
            $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
            if ($member['wholesaler_apply'] == 0){
                $condition .= " and is_wholesale = 0";
            }
            if ($member['wholesaler_apply'] == 1 && $member['wholesaler_status'] != 1){
                $condition .= " and is_wholesale  = 0";
            }

        }
    }
    $condition .= " AND selltype = 0 ";
    if (empty($gname)) {
        $condition .= " AND spike = 0 and is_public = 1";
    }

    if ($usepage) {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    } else {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby}";
    }

    $list = pdo_fetchall($sql, $params);
    $istuanyouhui = checkfunc(8159);//判定规格
    $isguige = checkfunc(8160);//判定规格
    $islingou = checkfunc(8161);//判定邻购
    $isonelimit = checkfunc(8163);//单次购买数量上限
    $ismanylimit = checkfunc(8164);//每人购买总数量上限
    $isjieti = checkfunc(8173);//判定阶梯
    $isjudgment = checkfunc(8194);//判定评价
    $isjieti2 = checkfunc(8195);//判定阶梯
    foreach ($list as $key => &$value) {
        if (empty($value['share_image'])) {
            $value['share_image'] = tomedia($value['gimg']);
        } else {
            $value['share_image'] = tomedia($value['share_image']);
        }
        $params_openid = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']);
        $history_buy = pdo_fetch('SELECT num FROM ' . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND g_id=:g_id AND openid=:openid', $params_openid);

        $value['gimg'] = tomedia($value['gimg']);
        if ($value['one_group'] == 1) {
            $group = pdo_fetch("SELECT * FROM " . tablename('tg_group') . ' WHERE goodsid=:goodsid AND uniacid=:uniacid AND groupstatus=3 AND neednum<>lacknum ORDER BY lacknum ASC LIMIT 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $value['id']));

            if (empty($group)) {
                $value['a'] = app_url('goods/detail/displays', array('id' => $value['id'] , 'shop_storeid' => $shop_storeid));
            } else {
                $value['a'] = app_url('order/group', array('tuan_id' => $group['groupnumber'] , 'shop_storeid' => $shop_storeid));
            }

        } else {
            $value['a'] = app_url('goods/detail/displays', array('id' => $value['id'], 'historylimit' => intval($history_buy['num']) , 'shop_storeid' => $shop_storeid));
        }

        if ($istuanyouhui['status'] == 0) {
            $value['is_discount'] = 0;
        }
        if ($isguige['status'] == 0) {
            $value['hasoption'] = 0;
        }
        if ($islingou['status'] == 0 && $value['selltype'] == 2) {
            $value['selltype'] = 1;
        }
        if ($isjieti['status'] == 0) {
            if ($value['selltype'] == 3 || $value['selltype'] == 4) {
                $value['selltype'] = 1;
            }
            $value['group_level_status'] = 0;
        }
        if ($ismanylimit['status'] == 0) {
            $value['many_limit'] = 0;
        }
        if ($isonelimit['status'] == 0) {
            $value['one_limit'] = 0;
        }
        if ($isjudgment['status'] == 0) {
            $value['isjudgment'] = 0;
        }
        if ($value['selltype'] == 4) {
            $group_level = unserialize($value['group_level']);

            $gprice = $group_level[0]['groupprice'];
            foreach ($group_level as $item) {
                if ($gprice > $item['groupprice']) {
                    $gprice = $item['groupprice'];
                }
            }
            $value['gprice'] = $gprice;
        }
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_goods') . "where 1 $condition ", $params);

    $data = array();
    $data['list'] = $list;
    $data['total'] = $total;
    return $data;
}

function goods_get_list_tuan($args)
{
    global $_W;
    $condition = ' and `uniacid` = :uniacid and shenhe=0';
    $params = array(':uniacid' => $_W['uniacid']);
    //    批发限制
    $uniacid = $_W['uniacid'];
    $openid = $_W['openid'];
    $setting = setting_get_by_name("wholesale");
    if($setting['apply'] == 1){
//        获取当前用户是否批发会员
        $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
        if ($member['wholesaler_apply'] == 0){
            $condition .= " and is_wholesale = 0";
        }
        if ($member['wholesaler_apply'] == 1 && $member['wholesaler_status'] != 1){
            $condition .= " and is_wholesale  = 0";
        }

    }
    if (is_array($args)) {
        $usepage = !empty($args['usepage']) ? $args['usepage'] : false;
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $orderby = !empty($args['orderby']) ? $args['orderby'] : 'order by id desc';

        $ishows = !empty($args['ishows']) ? trim($args['ishows']) : '';
        $condition = ' and `uniacid` = :uniacid and shenhe=0';
        $params = array(':uniacid' => $_W['uniacid']);

        if (!empty($ishows)) {
            $condition .= " and isshow in ( " . $ishows . ")";
        }
        $isnew = !empty($args['isnew']) ? 1 : 0;
        if (!empty($isnew)) {
            $condition .= " and isnew=1";
        }
        $ishot = !empty($args['ishot']) ? 1 : 0;
        if (!empty($ishot)) {
            $condition .= " and ishot=1";
        }
        $group_level_status = $args['group_level_status'];
        if ($group_level_status != '') {
            $condition .= " and group_level_status in ({$group_level_status}) ";
        }
        $isrecommand = !empty($args['isrecommand']) ? 1 : 0;
        if (!empty($isrecommand)) {
            $condition .= " and isrecommand=1";
        }
        $isdiscount = !empty($args['isdiscount']) ? 1 : 0;
        if (!empty($isdiscount)) {
            $condition .= " and isdiscount=1";
        }
        $gname = !empty($args['gname']) ? $args['gname'] : '';
        if (!empty($gname)) {
            $condition .= "  AND gname like '%{$gname}%' ";
        }
        $category_childid = $args['category_childid'];
        if (!empty($category_childid)) {
            $condition .= "  AND category_childid = '{$category_childid}'";
        }
        $cid = $args['cid'];
        if (!empty($cid)) {
            $condition .= "  AND fk_typeid = '{$cid}'";
        }
        if (empty($cid) && empty($category_childid) && empty($gname)) {
            $condition .= "  AND showindex = 0";
        }

        $storeid = $args['storeid'];
        if ($storeid) {
            $condition .= "  AND FIND_IN_SET('{$storeid}',hexiao_id) ";
//            $condition .= "  AND hexiao_id like '%{$storeid},%' ";
        }

        $lin = $args['lin'];
        $tuan = $args['tuan'];
        $is_hunda = intval($args['is_hunda']);
        $m_ajax = $args['m_ajax'];
        if ($is_hunda == 0) {
            if (empty($gname)) {
                if ($tuan == 0) {
                    $condition .= "  AND selltype = 0 ";
                }
                if ($tuan == 1) {
                    $condition .= "  AND selltype != 0 ";
                }
            } elseif ($m_ajax == 1) {
                if ($tuan == 0) {
                    $condition .= "  AND selltype = 0 ";
                }
                if ($tuan == 1) {
                    $condition .= "  AND selltype != 0 ";
                }
            }
        }
        if (empty($gname)) {
            $is_member = intval($args['is_member']);
            if ($is_member == 1) {
                $condition .= "  AND discount = 1 ";
            } else {
                $condition .= "  AND discount = 0 ";
            }
        }

        /*
        if($tuan==0||$lin==1)
        {
            if($tuan==0&&$lin==0)
            {
                $condition .= "  AND selltype = 0 ";
            }
            if($tuan==1&&$lin==0)
            {
                $condition .= "  AND ( selltype = 1)";
            }
            if($tuan==0&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 or selltype = 0)";
            }
            if($tuan==1&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 )";
            }
        }*/
    } else {
        $condition .= $args;
        $orderby = '';
    }
    $condition .= "  AND selltype > 0 ";
    if (empty($gname)) {
        $condition .= "  AND spike = 0 ";
    }
    $condition .= " and is_public = 1 ";
    if ($usepage) {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    } else {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby}";
    }
    $list = pdo_fetchall($sql, $params);
    $istuanyouhui = checkfunc(8159);//判定规格
    $isguige = checkfunc(8160);//判定规格
    $islingou = checkfunc(8161);//判定邻购
    $isonelimit = checkfunc(8163);//单次购买数量上限
    $ismanylimit = checkfunc(8164);//每人购买总数量上限
    $isjieti = checkfunc(8173);//判定阶梯
    $isjudgment = checkfunc(8194);//判定评价
    $isjieti2 = checkfunc(8195);//判定阶梯
    foreach ($list as $key => &$value) {
        if (empty($value['share_image'])) {
            $value['share_image'] = tomedia($value['gimg']);
        } else {
            $value['share_image'] = tomedia($value['share_image']);
        }
        $params_openid = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']);
        $history_buy = pdo_fetch('SELECT num FROM ' . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND g_id=:g_id AND openid=:openid', $params_openid);

        $value['gimg'] = tomedia($value['gimg']);
        if ($value['one_group'] == 1) {
            $group = pdo_fetch("SELECT * FROM " . tablename('tg_group') . ' WHERE goodsid=:goodsid AND uniacid=:uniacid AND groupstatus=3 AND neednum<>lacknum ORDER BY lacknum ASC LIMIT 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $value['id']));

            if (empty($group)) {
                $value['a'] = app_url('goods/detail/displays', array('id' => $value['id'] , 'storeid' => $storeid));
            } else {
                $value['a'] = app_url('order/group', array('tuan_id' => $group['groupnumber'] , 'storeid' => $storeid));
            }

        } else {
            $value['a'] = app_url('goods/detail/displays', array('id' => $value['id'], 'historylimit' => intval($history_buy['num']) , 'storeid' => $storeid));
        }

        if ($istuanyouhui['status'] == 0) {
            $value['is_discount'] = 0;
        }
        if ($isguige['status'] == 0) {
            $value['hasoption'] = 0;
        }
        if ($islingou['status'] == 0 && $value['selltype'] == 2) {
            $value['selltype'] = 1;
        }
        if ($isjieti['status'] == 0) {
            if ($value['selltype'] == 3 || $value['selltype'] == 4) {
                $value['selltype'] = 1;
            }
            $value['group_level_status'] = 0;
        }
        if ($ismanylimit['status'] == 0) {
            $value['many_limit'] = 0;
        }
        if ($isonelimit['status'] == 0) {
            $value['one_limit'] = 0;
        }
        if ($isjudgment['status'] == 0) {
            $value['isjudgment'] = 0;
        }
        if ($value['selltype'] == 4) {
            $group_level = unserialize($value['group_level']);

            $gprice = $group_level[0]['groupprice'];
            foreach ($group_level as $item) {
                if ($gprice > $item['groupprice']) {
                    $gprice = $item['groupprice'];
                }
            }
            $value['gprice'] = $gprice;
        }
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_goods') . " where 1 $condition ", $params);

    $data = array();
    $data['list'] = $list;
    $data['total'] = $total;
    return $data;
}

function goods_get_mlist($args)
{
    global $_W;
    $condition = ' and `uniacid` = :uniacid and shenhe=0';
    $params = array(':uniacid' => $_W['uniacid']);
    //    批发限制
    $uniacid = $_W['uniacid'];
    $openid = $_W['openid'];
    $setting = setting_get_by_name("wholesale");
    if($setting['apply'] == 1){
//        获取当前用户是否批发会员
        $member = pdo_fetch("SELECT * FROM " . tablename('tg_member') . " WHERE from_user = '{$openid}' and uniacid = '{$uniacid}'");
        if ($member['wholesaler_apply'] == 0){
            $condition .= " and is_wholesale = 0";
        }
        if ($member['wholesaler_apply'] == 1 && $member['wholesaler_status'] != 1){
            $condition .= " and is_wholesale  = 0";
        }

    }
    if (is_array($args)) {
        $usepage = !empty($args['usepage']) ? $args['usepage'] : false;
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $orderby = !empty($args['orderby']) ? $args['orderby'] : 'order by id desc';

        $ishows = !empty($args['ishows']) ? trim($args['ishows']) : '';
        $condition = ' and `uniacid` = :uniacid and shenhe=0';
        $params = array(':uniacid' => $_W['uniacid']);

        if (!empty($ishows)) {
            $condition .= " and isshow in ( " . $ishows . ")";
        }
        $isnew = !empty($args['isnew']) ? 1 : 0;
        if (!empty($isnew)) {
            $condition .= " and isnew=1";
        }
        $ishot = !empty($args['ishot']) ? 1 : 0;
        if (!empty($ishot)) {
            $condition .= " and ishot=1";
        }
        $group_level_status = $args['group_level_status'];
        if ($group_level_status != '') {
            $condition .= " and group_level_status in ({$group_level_status}) ";
        }
        $isrecommand = !empty($args['isrecommand']) ? 1 : 0;
        if (!empty($isrecommand)) {
            $condition .= " and isrecommand=1";
        }
        $isdiscount = !empty($args['isdiscount']) ? 1 : 0;
        if (!empty($isdiscount)) {
            $condition .= " and isdiscount=1";
        }
        $gname = !empty($args['gname']) ? $args['gname'] : '';
        if (!empty($gname)) {
            $condition .= "  AND gname like '%{$gname}%' ";
        }
        $cid = $args['cid'];
        if (!empty($cid)) {
            $condition .= "  AND fk_typeid = '{$cid}'";
        }
        if (empty($cid)) {
            $condition .= "  AND showindex = 0";
        }
        $category_childid = $args['category_childid'];
        if (!empty($category_childid)) {
            $condition .= "  AND category_childid = '{$category_childid}'";
        }
        $lin = $args['lin'];
        $tuan = $args['tuan'];
        $is_hunda = intval($args['is_hunda']);
        if ($is_hunda == 0) {
            if (empty($gname)) {
                if ($tuan == 0) {
                    $condition .= "  AND selltype = 0 ";
                }
                if ($tuan == 1) {
                    $condition .= "  AND selltype != 0 ";
                }
            }
        }
        $type = $args['type'];
        if ($type == 0) {
            $condition .= "  AND spike_start <= " . TIMESTAMP . " AND spike_end>=" . TIMESTAMP;
        } else if ($type == 1) {
            $condition .= "  AND spike_start > " . TIMESTAMP;
        } else if ($type == 2) {
            $condition .= "  AND spike_end < " . TIMESTAMP;
        }
        /*
        if($tuan==0||$lin==1)
        {
            if($tuan==0&&$lin==0)
            {
                $condition .= "  AND selltype = 0 ";
            }
            if($tuan==1&&$lin==0)
            {
                $condition .= "  AND ( selltype = 1)";
            }
            if($tuan==0&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 or selltype = 0)";
            }
            if($tuan==1&&$lin==1)
            {
                $condition .= "  AND (selltype = 2 or selltype = 1 )";
            }
        }*/
    } else {
        $condition .= $args;
        $orderby = '';
    }

    $condition .= "  AND spike = 1 and is_public = 1";

    //message($condition);
    if ($usepage) {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
    } else {
        $sql = "SELECT * FROM " . tablename('tg_goods') . " where 1 {$condition} {$orderby}";
    }
    $list = pdo_fetchall($sql, $params);
    $istuanyouhui = checkfunc(8159);//判定规格
    $isguige = checkfunc(8160);//判定规格
    $islingou = checkfunc(8161);//判定邻购
    $isonelimit = checkfunc(8163);//单次购买数量上限
    $ismanylimit = checkfunc(8164);//每人购买总数量上限
    $isjieti = checkfunc(8173);//判定阶梯
    $isjudgment = checkfunc(8194);//判定评价
    $isjieti2 = checkfunc(8195);//判定阶梯
    foreach ($list as $key => &$value) {
        $value['spike_start'] = date('Y-m-d H:i:s', $value['spike_start']);
        $value['spike_end'] = date('Y-m-d H:i:s', $value['spike_end']);
        if (empty($value['share_image'])) {
            $value['share_image'] = tomedia($value['gimg']);
        } else {
            $value['share_image'] = tomedia($value['share_image']);
        }
        $params_openid = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':g_id' => $value['id']);
        $history_buy = pdo_fetch('SELECT num FROM ' . tablename('tg_goods_openid') . ' WHERE uniacid=:uniacid AND g_id=:g_id AND openid=:openid', $params_openid);

        $value['gimg'] = tomedia($value['gimg']);
        if ($value['one_group'] == 1) {
            $group = pdo_fetch("SELECT * FROM " . tablename('tg_group') . ' WHERE goodsid=:goodsid AND uniacid=:uniacid AND groupstatus=3 AND neednum<>lacknum ORDER BY lacknum ASC LIMIT 1', array(':uniacid' => $_W['uniacid'], ':goodsid' => $value['id']));

            if (empty($group)) {
                $value['a'] = app_url('goods/detail/displays', array('id' => $value['id']));
            } else {
                $value['a'] = app_url('order/group', array('tuan_id' => $group['groupnumber']));
            }

        } else {
            $value['a'] = app_url('goods/detail/displays', array('id' => $value['id'], 'historylimit' => intval($history_buy['num'])));
        }

        if ($istuanyouhui['status'] == 0) {
            $value['is_discount'] = 0;
        }
        if ($isguige['status'] == 0) {
            $value['hasoption'] = 0;
        }
        if ($islingou['status'] == 0 && $value['selltype'] == 2) {
            $value['selltype'] = 1;
        }
        if ($isjieti['status'] == 0) {
            if ($value['selltype'] == 3 || $value['selltype'] == 4) {
                $value['selltype'] = 1;
            }
            $value['group_level_status'] = 0;
        }
        if ($ismanylimit['status'] == 0) {
            $value['many_limit'] = 0;
        }
        if ($isonelimit['status'] == 0) {
            $value['one_limit'] = 0;
        }
        if ($isjudgment['status'] == 0) {
            $value['isjudgment'] = 0;
        }
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_goods') . "where 1 $condition ", $params);

    $data = array();
    $data['list'] = $list;
    $data['total'] = $total;
    return $data;
}

/**
 * 函数getGoodsByParams，按检索条件检索出指定商品
 * $params : 类型：字符串
 *
 */

function goods_get_by_params($params = '')
{
    global $_W;
    if (!empty($params)) {
        $params = ' where ' . $params;
    }
    $istuanyouhui = checkfunc(8159);//判定规格
    $isguige = checkfunc(8160);//判定规格
    $islingou = checkfunc(8161);//判定邻购
    $isonelimit = checkfunc(8163);//单次购买数量上限
    $ismanylimit = checkfunc(8164);//每人购买总数量上限
    $isjieti = checkfunc(8173);//判定阶梯
    $isjudgment = checkfunc(8194);//判定评价
    $isjieti2 = checkfunc(8195);//判定阶梯
    $sql = "SELECT * FROM " . tablename('tg_goods') . $params;
    $goods = pdo_fetch($sql);
    $goods['gimg'] = tomedia($goods['gimg']);
    $goods['share_gimg'] = tomedia($goods['share_gimg']);
    $goods['share_image'] = tomedia($goods['share_image']);
    if ($isjudgment['status'] == 0) {
        $goods['isjudgment'] = 0;
    }
    if ($istuanyouhui['status'] == 0) {
        $goods['is_discount'] = 0;
    }
    if ($isguige['status'] == 0) {
        $goods['hasoption'] = 0;
    }
    if ($islingou['status'] == 0 && $goods['selltype'] == 2) {
        $goods['selltype'] = 1;
    }
    if ($isjieti['status'] == 0) {
        if ($goods['selltype'] == 3 || $goods['selltype'] == 4) {
            $goods['selltype'] = 1;
        }
        $goods['group_level_status'] = 0;
    }
    if ($ismanylimit['status'] == 0) {
        $goods['many_limit'] = 0;
    }
    if ($isonelimit['status'] == 0) {
        $goods['one_limit'] = 0;
    }
    if ($isjieti2['status'] == 0) {
        if ($goods['selltype'] == 3 || $goods['selltype'] == 4) {
            $goods['selltype'] = 1;
        }
        $goods['group_level_status'] = 0;
    }
    $goods['luckcontent'] = htmlspecialchars_decode($goods['luckcontent']);
    $goods['a'] = app_url('goods/detail/display', array('id' => $goods['id']));
    return $goods;
}

/**
 * 函数getGoodsByParams，按条件检索更新商品
 * $data : 类型：array ; $params 类型：array
 *
 */
function goods_update_by_params($data, $params)
{
    global $_W;
    $flag = pdo_update('tg_goods', $data, $params);
    return $flag;
}

/**
 * 函数insertGoods，插入新商品
 * $data : 类型：array
 * 返回值：插入ID
 */
function goods_insert($data)
{
    global $_W;
    $flag = pdo_insert('tg_goods', $data);
    if ($flag) {
        $insertid = pdo_insertid();
    } else {
        $insertid = FALSE;
    }
    return $insertid;
}

/**
 * 函数deleteGoods，删除商品
 * $id : 类型：int
 * 返回值：
 */
function goods_delete($id)
{
    global $_W;
    $flag = pdo_delete('tg_goods', array('id' => $id));
    return $flag;
}