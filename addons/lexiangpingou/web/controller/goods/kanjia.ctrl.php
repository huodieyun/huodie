<?php
defined('IN_IA') or exit('Access Denied');
load()->func("tpl");
wl_load()->model('goods');
wl_load()->model('functions');
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$con = "";
if ($_W['user']['merchant_id'] > 0) {
    $con .= " and  activities_type=0 ";
}
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid = 0 {$con} ORDER BY id DESC");

if ($op == 'parent') {
    die(json_encode(array('category' => $category)));
}
if ($op == 'child') {
    $parentid = $_GPC['id'];
    $childs = pdo_fetchall("SELECT id,name FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid = {$parentid} and enabled = 1 ORDER BY displayorder DESC");
    die(json_encode(array('child' => $childs)));
}

//var_dump($category);
$functions = pdo_fetchall("SELECT * FROM " . tablename('tg_function') . " WHERE type = 1 AND tuan = 0");
$wechats = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = " . $_W['uniacid']);
$isshop = 0;
foreach ($functions as $key => $value) {
    $fundetail = pdo_fetch("select * from " . tablename('tg_function_detail') . " where uniacid = '{$_W['uniacid']}' and functionid='{$value['id']}' ");
    if ((!empty($fundetail) && $fundetail['endtime'] > time()) || ($wechats['vip'] == 1 && $wechats['endtime'] > time()) || $wechats['ordernum'] > 0) {
        $isshop = 1;
        break;
    }
}
$childs = array();

foreach ($category as $key => $value) {
    $category_childs = pdo_fetchall("SELECT id,name FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid = {$value['id']} and enabled = 1 ORDER BY displayorder DESC");
    $childs[$value['id']] = $category_childs;
}
$op = $_GPC["op"];
if (empty($op) || !$op) {
    $op = "display";
    if (!pdo_fieldexists('tg_goods', 'iskj')) {
        pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `iskj` INT(1)  NOT NULL DEFAULT 0;");
    }
}
if ($op == "display") {
    //查询所有的砍价商品
    $kangoods = pdo_fetch("SELECT * FROM " . tablename("tg_ksetting") . " WHERE uniacid = :uniacid", array(":uniacid" => $_W["uniacid"]));
    if (checksubmit("submit")) {
        $data = array(
            "title" => $_GPC["announcement"],
            "banner" => $_GPC["img"],
            "banner_url" => $_GPC["banner_url"],
            "share_title" => $_GPC["share_title"],
            "share_img" => $_GPC["share_icon"],
            "share_content" => $_GPC["share_content"],
            "uniacid" => $_W["uniacid"]
        );
        $res = pdo_fetch("SELECT * FROM " . tablename("tg_ksetting") . " WHERE uniacid = :uniacid", array(":uniacid" => $_W["uniacid"]));
        if ($res) {
            $data = array_filter($data);
            pdo_update("tg_ksetting", $data, array("uniacid" => $_W["uniacid"]));
        } else {
            pdo_insert("tg_ksetting", $data);
        }
    }
}
if ($op == "list") {
    if (empty($_GPC['sortname'])) {
        $sortname = 'displayorder';
    } else {
        $sortname = $_GPC['sortname'];
    }
    if (empty($_GPC['sortasc'])) {
        $sortasc = 'desc';
    } else {
        if ($sortasc == 'desc') {
            $sortasc = 'asc';
        }
        if ($sortasc == 'asc') {
            $sortasc = 'desc';
        }
    }
    if (checksubmit()) {
        $display = $_GPC['display'];
        $ids = $_GPC['ids'];
        for ($i = 0; $i < count($ids); $i++) {
            pdo_update("tg_goods", array('displayorder' => $display[$i]), array('id' => $ids[$i]));
        }
        $tip = '商品排序保存成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('goods/kanjia/list') . "';</script>";
        exit;

    }
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $condition = " WHERE uniacid = {$_W['uniacid']} ";

    if (!isset($_GPC['status'])) {
        $_GPC['status'] = 1;
    }
//	if (isset($_GPC['status'])&&$_GPC['status']==5) {
//		$condition .= " and isshow in(".$_GPC['status'].") ";
//	}
//	if($_GPC['status']==5){
//		$condition .= " and shenhe = 1 ";
//	}

    if (!empty($_GPC['category']['parentid'])) {
        $condition .= " and category_parentid = {$_GPC['category']['parentid']} ";
    }
    if (!empty($_GPC['category']['childid'])) {
        $condition .= " and category_childid = {$_GPC['category']['childid']} ";
    }
    $condition .= " and merchantid = {$_W['user']['merchant_id']} ";
    if ($_W['user']['merchant_id'] > 0) {

        if ($_GPC['status'] == 1) {
            $condition .= " and isshow = 1 and shenhe = 0 ";
        } elseif ($_GPC['status'] == 2) {
            $condition .= " and ( isshow = 2 or shenhe = 1 )";
        } elseif ($_GPC['status'] == 4) {
            $condition .= " and ( isshow = 4 or shenhe = 2 )";
        }

        if ($_GPC['status'] == 3 && $_W['user']['merchant_id'] > 0) {
            $condition .= " and isshow in(" . $_GPC['status'] . ") ";
        }
    } elseif (isset($_GPC['status'])) {
        $condition .= " and isshow = " . $_GPC['status'];
    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " and  gname like '%{$_GPC['keyword']}%'";
    }
    $condition .= " AND iskj = 1 and selltype = 10 ";

    $sqlTotal = pdo_sql_select_count_from('tg_goods') . $condition;
    $sqlData = pdo_sql_select_all_from('tg_goods') . $condition . ' ORDER BY ' . $sortname . ' ' . $sortasc;
    $klist = pdo_pagination($sqlTotal, $sqlData, $params, 'id', $total, $page, $size);
    $pager = pagination($total, $page, $size);
//    $klist = pdo_fetchall("SELECT * FROM " . tablename("tg_goods") . " WHERE uniacid = :uniacid AND iskj = 1", array(":uniacid" => $_W["uniacid"]));
    foreach ($klist as &$value) {
        $category = pdo_fetch("SELECT name FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and id = {$value['fk_typeid']}");

        $value['typename'] = $category['name'];
    }
}
if ($op == "post") {
    $id = intval($_GPC['id']);
    //查询砍价详情
    $kj_arr = pdo_getall("tg_goods_kj", array("g_id" => $id, "uniacid" => $_W["uniacid"]));
    $last_price = $kj_arr[0]["last_price"];
    $funlist = pdo_fetchall("SELECT * FROM " . tablename('tg_function') . " WHERE type=1 AND tuan=0");
    $istuan = 1;//1代表拼团 0代表商团
    foreach ($funlist as $key => &$value) {
        $checkfunction = checkfunc($value['id']);
        if ($checkfunction['status'] == 1) {
            $istuan = 0;
            break;
        }

    }
    unset($value);
    unset($funlist);
    //  die(json_encode(array('goods' => $funlist , 'id' => $id)));

    $ispartjob = checkfunc(8167);//分享团控制
    $ison = checkfunc(8165);//分享团控制
    $islin = checkfunc(8161);//邻购控制
    $iscost = checkfunc(8159);//团长优惠
    $isonesbuy = checkfunc(8163);//单次购买次数设置
    $ismorebuy = checkfunc(8164);//最多购买次数设置
    $isoption = checkfunc(8160);//规格
    $isdeliverytype = checkfunc(8155);//运费模板
    $issh = checkfunc(8157);//送货
    $iszt = checkfunc(8156);//自提
    $isjieti = checkfunc(8173);//阶梯团
    $ischoujian = checkfunc(8177);//抽奖
    $isjudgment = checkfunc(8194);//评价
    $isjieti2 = checkfunc(8195);//判定阶梯
    $merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
    $muban_array = array();
    $couponlist = pdo_fetchall("SELECT * FROM " . tablename('tg_coupon_template') . " WHERE uniacid=" . $_W['uniacid'] . " AND end_time>" . TIMESTAMP . " AND start_time<" . TIMESTAMP . " AND enable=1");
    //$dispatch_list = pdo_fetchall("select * from".tablename('tg_delivery_template'));
    $store_list = pdo_fetchall("select storename,id from " . tablename("tg_store") . " WHERE status=1 and uniacid = {$_W['uniacid']}  order by id desc");
    $thisgoods = pdo_fetch("select hexiao_id,yunfei_id from " . tablename("tg_goods") . " WHERE id='{$id}'");
    $storesids = explode(",", $thisgoods['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select storename,id from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
        }
    }
    unset($value);
    $yunfeiids = explode(",", $thisgoods['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select id,name from" . tablename('tg_delivery_template') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
        }
    }
    unset($value);
    $piclist = array();
    if (!empty($id)) {
        $sql = 'SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id ';
        $paramse = array(':id' => $id);
        $goods = pdo_fetch($sql, $paramse);
        $cates = pdo_fetch("SELECT * FROM " . tablename('tg_category') . " WHERE id = '{$goods['category_childid']}' ");

        if (!intval($goods['spike_start'])) {
            $goods['spike_start'] = strtotime(date('Y-m-d', TIMESTAMP));
            $goods['spike_end'] = strtotime(date('Y-m-d', TIMESTAMP) . ' +1 day');
        }

        if (!empty($goods['openid'])) {
            $saler = pdo_fetch('SELECT * FROM ' . tablename('tg_member') . ' WHERE openid=:openid', array(':openid' => $goods['openid']));
        }
        //阶梯团
        $param_level = unserialize($goods['group_level']);
        //获取当前图集
        $listt = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_atlas') . "WHERE g_id = '{$id}' ");

        if (is_array($listt)) {
            foreach ($listt as $p) {
                $piclist[] = $p['thumb'];
            }
            unset($p);
            unset($listt);
        }
        $allspecs = pdo_fetchall("SELECT * FROM " . tablename('tg_spec') . " WHERE goodsid=:id ORDER BY displayorder ASC", array(":id" => $id));
        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('tg_spec_item') . " WHERE specid=:specid ORDER BY displayorder ASC", array(":specid" => $s['id']));
        }
        unset($s);
        //处理规格项
        $html = "";
        $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
        //排序好的specs
        $specs = array();
        //找出数据库存储的排列顺序
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
            unset($ss);
            unset($itemid);
            $html = '';
            $html .= '<table class="table table-bordered table-condensed">';
            $html .= '<thead>';
            $html .= '<tr class="active">';
            $len = count($specs);
            $newlen = 1; //多少种组合
            $h = array(); //显示表格二维数组
            $rowspans = array(); //每个列的rowspan
            for ($i = 0; $i < $len; $i++) {
                //表头
                $html .= "<th style='width:80px;'>" . $specs[$i]['title'] . "</th>";
                //计算多种组合
                $itemlen = count($specs[$i]['items']);
                if ($itemlen <= 0) {
                    $itemlen = 1;
                }
                $newlen *= $itemlen;
                //初始化 二维数组
                $h = array();
                for ($j = 0; $j < $newlen; $j++) {
                    $h[$i][$j] = array();
                }
                //计算rowspan
                $l = count($specs[$i]['items']);
                $rowspans[$i] = 1;
                for ($j = $i + 1; $j < $len; $j++) {
                    $rowspans[$i] *= count($specs[$j]['items']);
                }
            }
            $html .= '<th class="info" style="width:130px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">库存</div><div class="input-group"><input type="text" class="form-control option_stock_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
            $html .= '<th class="success" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">拼团价格</div><div class="input-group"><input type="text" class="form-control option_marketprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_marketprice\');"></a></span></div></div></th>';
            $html .= '<th class="warning" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">单买价格</div><div class="input-group"><input type="text" class="form-control option_productprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_productprice\');"></a></span></div></div></th>';
            $html .= '<th class="danger" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">市场价格</div><div class="input-group"><input type="text" class="form-control option_costprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_costprice\');"></a></span></div></div></th>';
            $html .= '<th class="info" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">重量（克）</div><div class="input-group"><input type="text" class="form-control option_weight_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_weight\');"></a></span></div></div></th>';
            $html .= '</tr></thead>';
            for ($m = 0; $m < $len; $m++) {
                $k = 0;
                $kid = 0;
                $n = 0;
                for ($j = 0; $j < $newlen; $j++) {
                    $rowspan = $rowspans[$m];
                    if ($j % $rowspan == 0) {
                        $h[$m][$j] = array("html" => "<td rowspan='" . $rowspan . "'>" . $specs[$m]['items'][$kid]['title'] . "</td>", "id" => $specs[$m]['items'][$kid]['id']);
                    } else {
                        $h[$m][$j] = array("html" => "", "id" => $specs[$m]['items'][$kid]['id']);
                    }
                    $n++;
                    if ($n == $rowspan) {
                        $kid++;
                        if ($kid > count($specs[$m]['items']) - 1) {
                            $kid = 0;
                        }
                        $n = 0;
                    }
                }
            }
            $hh = "";
            for ($i = 0; $i < $newlen; $i++) {
                $hh .= "<tr>";
                $ids = array();
                for ($j = 0; $j < $len; $j++) {
                    $hh .= $h[$j][$i]['html'];
                    $ids[] = $h[$j][$i]['id'];
                }
                $ids = implode("_", $ids);
                $val = array("id" => "", "title" => "", "stock" => "", "costprice" => "", "productprice" => "", "marketprice" => "", "weight" => "");
                foreach ($options as $o) {
                    if ($ids === $o['specs']) {
                        $val = array(
                            "id" => $o['id'],
                            "title" => $o['title'],
                            "stock" => $o['stock'],
                            "costprice" => $o['costprice'],
                            "productprice" => $o['productprice'],
                            "marketprice" => $o['marketprice'],
                            "weight" => $o['weight']
                        );
                        break;
                    }
                }
                $hh .= '<td class="info">';
                $hh .= '<input name="option_stock_' . $ids . '[]"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/></td>';
                $hh .= '<input name="option_id_' . $ids . '[]"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                $hh .= '<input name="option_ids[]"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '<input name="option_title_' . $ids . '[]"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';
                $hh .= '</td>';
                $hh .= '<td class="success"><input name="option_marketprice_' . $ids . '[]" type="text" class="form-control option_marketprice option_marketprice_' . $ids . '" value="' . $val['marketprice'] . '"/></td>';
                $hh .= '<td class="warning"><input name="option_productprice_' . $ids . '[]" type="text" class="form-control option_productprice option_productprice_' . $ids . '" " value="' . $val['productprice'] . '"/></td>';
                $hh .= '<td class="danger"><input name="option_costprice_' . $ids . '[]" type="text" class="form-control option_costprice option_costprice_' . $ids . '" " value="' . $val['costprice'] . '"/></td>';
                $hh .= '<td class="info"><input name="option_weight_' . $ids . '[]" type="text" class="form-control option_weight option_weight_' . $ids . '" " value="' . $val['weight'] . '"/></td>';
                $hh .= '</tr>';
            }
            $html .= $hh;
            $html .= "</table>";
        }
        unset($hh);
        $params = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_param') . "WHERE goodsid = '{$id}' ");
        if (empty($goods)) {
            $tip = '商品排序保存成功';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('goods/goods') . "';</script>";
            exit;

        }
        /*
        $orders = pdo_fetchall("SELECT * FROM" . tablename('tg_order') . "WHERE g_id = '{$id}'");
        $arr = array();
        foreach ($orders as $key => $value) {
            $arr['endtime'] = $value['endtime'];
        }

        unset($orders);
        unset($value);
        $endtime = $arr['endtime'];
        */
    }

    if (checksubmit()) {

        if ($_GPC["goods"]["selltype"] != 10) {
            exit("非砍价商品拒绝通过本接口");
        }
        $rule_price = $_GPC["rule_pice"];
        $rule_price = $_GPC["rule_start"];
        $rule_price = $_GPC["rule_end"];
        $last_price = $_GPC["last_price"];
        //入砍价的库

        $data = $_GPC['goods'];
        $data['iskj'] = 1;
        $data['selltype'] = 10;
        if (empty($id)) {
            if ($_W['user']['merchant_id'] == 0) {
                $data['shenhe'] = 0;
            } else {
                $merchants = pdo_fetch("select goodsnum from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$_W['user']['merchant_id']}'");
                $mnum = $merchants['goodsnum'];
                $gnum = pdo_fetchcolumn("select COUNT(*) from " . tablename('tg_goods') . " where uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and isshow = 1");
                if ($mnum <= $gnum && $mnum > 0) {
                    message('已达到商品发布上限，请下架部分商品后在发布新商品', web_url('goods/goods'), 'error');
                }
                $data['shenhe'] = 1;
            }


        }
        if (intval($id) > 0) {
            $tg_spec_list = pdo_fetchall('SELECT * FROM ' . tablename('tg_spec') . ' WHERE uniacid=:uniacid AND goodsid=:goodsid', array(':uniacid' => $_W['uniacid'], ':goodsid' => $id));
            foreach ($tg_spec_list as $ite) {
                pdo_query("delete from " . tablename('tg_spec_item') . " where uniacid={$_W['uniacid']} and specid={$ite['id']}");
            }
            pdo_query("delete from " . tablename('tg_spec') . " where uniacid={$_W['uniacid']} and goodsid={$id}");
            unset($tg_spec_list);
        }
        //核销ID
        $store = $_GPC['storeids'];
        $str = '';
        if ($store) {
            foreach ($store as $key => $value) {
                $str .= $value . ",";
            }
        }
        //运费ID
        $dispatchs = $_GPC['dispatchs'];
        $str1 = '';
        if ($dispatchs) {
            foreach ($dispatchs as $key => $value) {
                $str1 .= $value . ",";
            }

        }
        unset($dispatchs);

        //获取图集
        $goimages = $_GPC['img'];


        // TODO 如果是秒杀就添加秒杀的字段属性
        $spike = $_GPC['spike'];
        $spikeT = $_GPC['spikeT'];
        $data['spike'] = $spike;
        $data['spikeT'] = $spikeT;
        if ($spike == 1 || $spikeT == 1) {
            $time = $_GPC['time'];
            $data['spike_start'] = strtotime($time['start']);
            $data['spike_end'] = strtotime($time['end']);
        }
        if (empty($data['fk_typeid'])) {
            $data['fk_typeid'] = $data['category_parentid'] = $_GPC['category']['parentid'];
        } else {
            $data['category_parentid'] = $data['fk_typeid'];
        }
        //$data['category_childid'] = $_GPC['category']['childid'];
        //$data['category_childid'] = $data['category_childid'];
        //message($_GPC['category']['childid'].'ej');

        $data['hexiao_id'] = $str;
        $data['yunfei_id'] = $str1;
        $data['gdetaile'] = htmlspecialchars_decode($data['gdetaile']);
        $data['endtime'] = $_GPC['endtime'];
        $data['openid'] = $_GPC['openid'];
        //阶梯团
        $param_groupnum = $_POST['param_groupnum'];
        $param_groupprice = $_POST['param_groupprice'];
        unset($_POST['param_groupnum']);
        unset($_POST['param_groupprice']);
        $maxprice = 0;
        $maxgroupnum = 0;
        $group_level = array();
        for ($i = 0; $i < count($param_groupnum); $i++) {
            $group_level[$i]['groupnum'] = $param_groupnum[$i];
            $group_level[$i]['groupprice'] = $param_groupprice[$i];
            if ($param_groupnum[$i] > $maxgroupnum) {
                $maxgroupnum = $param_groupnum[$i];
            }
            if ($param_groupprice[$i] > $maxprice) {
                $maxprice = $param_groupprice[$i];
            }
        }
        $group_level = serialize($group_level);
        $data['group_level'] = $group_level;
        $data['couponsids'] = $_GPC['functionValue'];
        //message($_GPC['functionValue']);
        if ($data['group_level_status'] == 2 && $isjieti['status']) {
            $data['hasoption'] = 0;
            $data['groupnum'] = $maxgroupnum;
            $data['gprice'] = $maxprice;
        } else {
            $data['hasoption'] = intval($_GPC['hasoption']);
        }
        //message($data['mprice']);
        if ($data['hasoption'] == 1) {
            $data['group_level_status'] = 1;
        }
        wl_load()->func('global');
        $data['share_image'] = saveImage(tomedia($data['share_image']));
        $oplog_id = 0;

        if (empty($goods)) {
            $data['selltype'] = 10;
            $data['iskj'] = 1;
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $data['merchantid'] = $_W['user']['merchant_id'];
            $data["index_video"] = $_GPC["index_video"];
            $ret = pdo_insert('tg_goods', $data);
            $id = pdo_insertid();
            $url = $_GPC["url"];
            $data["index_video"] = $_GPC["index_video"];
            //            $datas["media_url"] = $url;
            //            $datas["goods_id"] = $id;
            //            $res = pdo_fetch("select * from ".tablename("tg_goods_video")." where goods_id = :id",array(":id"=>$id));
            //            if (empty($res["media_url"]) || $res){
            //                $res =  pdo_insert("tg_goods_video", $datas);
            //            }else{
            //                unset($data["goods_id"]);
            //                pdo_update("tg_goods_video",$datas,array("goods_id"=>$id));
            //            }


            /*if (!empty($ret)) {
                $lastgoods=pdo_fetch("select id from ".tablename('tg_goods')." where uniacid=".$_W['uniacid']." order by id desc limit 1");
                $id = $lastgoods['id'];
            }
            */
            $oplogdata = serialize($data);
            oplog($_W['user']['username'], "添加砍价商品", web_url('goods/kanjia/edit'), $oplogdata);
            if ($goimages) {
                $goimages = array_reverse($goimages);
                foreach ($goimages as $key => $value) {
                    $data1 = array('thumb' => $goimages[$key], 'g_id' => $id);
                    pdo_insert('tg_goods_atlas', $data1);
                }
            }
        } else {
            if ($goimages) {
                pdo_delete('tg_goods_atlas', array('g_id' => $id));
                $goimages = array_reverse($goimages);
                foreach ($goimages as $key => $value) {
                    $data2 = array('thumb' => $goimages[$key], 'g_id' => $id);
                    pdo_insert('tg_goods_atlas', $data2);
                }
            }
            $data["index_video"] = $_GPC["index_video"];
            $ret = pdo_update('tg_goods', $data, array('id' => $id));
            //获取视频id 和视频url
            //            $url = $_GPC["url"];
            //            $datas["media_url"] = $url;
            //            $datas["goods_id"] = $id;
            //            $res = pdo_fetch("select * from ".tablename("tg_goods_video")." where goods_id = :id",array(":id"=>$id));
            //            if (empty($res["media_url"]) || !$res){
            //                $res =  pdo_insert("tg_goods_video", $datas);
            //            }else{
            //                unset($data["goods_id"]);
            //                pdo_update("tg_goods_video",$datas,array("goods_id"=>$id));
            //            }
            $oplogdata = serialize($data);
            $oplog_id = oplog($_W['user']['username'], "更新砍价商品", web_url('goods/kanjia/edit'), $oplogdata);
        }
        //处理自定义参数
        $param_ids = $_POST['param_id'];
        $param_titles = $_POST['param_title'];
        $param_values = $_POST['param_value'];
        $param_displayorders = $_POST['param_displayorder'];
        $len = count($param_ids);
        $paramids = array();
        for ($k = 0; $k < $len; $k++) {
            $param_id = "";
            $get_param_id = $param_ids[$k];
            $a = array("title" => $param_titles[$k], "value" => $param_values[$k], "displayorder" => $k, "goodsid" => $id,);
            if (!is_numeric($get_param_id)) {
                pdo_insert("tg_goods_param", $a);
                $param_id = pdo_insertid();
            } else {
                pdo_update("tg_goods_param", $a, array('id' => $get_param_id));
                $param_id = $get_param_id;
            }
            $paramids[] = $param_id;
            $param[$k] = $a;
        }
        $param = serialize($param);
        if (count($paramids) > 0) {
            pdo_query("DELETE FROM " . tablename('tg_goods_param') . " WHERE goodsid=:id AND id NOT IN ( " . implode(',', $paramids) . ")", array(':id' => $id));
        } else {
            pdo_query("DELETE FROM " . tablename('tg_goods_param') . " WHERE goodsid=:id", array(':id' => $id));
        }
        //处理商品规格
        $files = $_FILES;
        $spec_ids = $_POST['spec_id'];
        $spec_titles = $_POST['spec_title'];
        $specids = array();
        $len = count($spec_ids);
        $specids = array();
        $spec_items = array();
        for ($k = 0; $k < $len; $k++) {
            $spec_id = "";
            $get_spec_id = $spec_ids[$k];
            $a = array(
                "uniacid" => $_W['uniacid'],
                "goodsid" => $id,
                "displayorder" => $k,
                "title" => $spec_titles[$get_spec_id]
            );
            /*
            if (is_numeric($get_spec_id)) {
                pdo_update("tg_spec", $a, array("id" => $get_spec_id));
                $spec_id = $get_spec_id;
            } else {
                pdo_insert("tg_spec", $a);
                $spec_id = pdo_insertid();
            }*/
            pdo_insert("tg_spec", $a);
            $spec_id = pdo_insertid();
            //子项
            $spec_item_ids = $_POST["spec_item_id_" . $get_spec_id];
            $spec_item_titles = $_POST["spec_item_title_" . $get_spec_id];
            $spec_item_shows = $_POST["spec_item_show_" . $get_spec_id];
            $spec_item_thumbs = $_POST["spec_item_thumb_" . $get_spec_id];
            $spec_item_oldthumbs = $_POST["spec_item_oldthumb_" . $get_spec_id];
            $itemlen = count($spec_item_ids);
            $itemids = array();
            for ($n = 0; $n < $itemlen; $n++) {
                $item_id = "";
                $get_item_id = $spec_item_ids[$n];
                $d = array(
                    "uniacid" => $_W['uniacid'],
                    "specid" => $spec_id,
                    "displayorder" => $n,
                    "title" => trim($spec_item_titles[$n]),
                    "show" => $spec_item_shows[$n],
                    "thumb" => $spec_item_thumbs[$n]
                );
                $f = "spec_item_thumb_" . $get_item_id;
                /*
                if (is_numeric($get_item_id)) {
                    pdo_update("tg_spec_item", $d, array("id" => $get_item_id));
                    $item_id = $get_item_id;
                } else {
                    pdo_insert("tg_spec_item", $d);
                    $item_id = pdo_insertid();
                }*/
                pdo_insert("tg_spec_item", $d);
                $item_id = pdo_insertid();
                $itemids[] = $item_id;
                //临时记录，用于保存规格项
                $d['get_id'] = $get_item_id;
                $d['id'] = $item_id;
                $spec_items[] = $d;
            }
            //删除其他的
            /*
                        if(count($itemids)>0){
                            pdo_query("delete from " . tablename('tg_spec_item') . " where uniacid={$_W['uniacid']} and specid=$spec_id and id not in (" . implode(",", $itemids) . ")");
                        }
                        else{
                            pdo_query("delete from " . tablename('tg_spec_item') . " where uniacid={$_W['uniacid']} and specid=$spec_id");
                        }
            */
            //更新规格项id
            pdo_update("tg_spec", array("content" => serialize($itemids)), array("id" => $spec_id));
            $specids[] = $spec_id;
        }
        //删除其他的
        /*
        if( count($specids)>0){
            pdo_query("delete from " . tablename('tg_spec') . " where uniacid={$_W['uniacid']} and goodsid=$id and id not in (" . implode(",", $specids) . ")");
        }
        else{
            pdo_query("delete from " . tablename('tg_spec') . " where uniacid={$_W['uniacid']} and goodsid=$id");
        }
        */
        if ($id > 0) {
            pdo_query("delete from " . tablename('tg_goods_option') . " where uniacid={$_W['uniacid']} and goodsid={$id}");

        }

        //保存规格
        $option_idss = $_POST['option_ids'];
        $option_productprices = $_POST['option_productprice'];
        $option_marketprices = $_POST['option_marketprice'];
        $option_costprices = $_POST['option_costprice'];
        $option_stocks = $_POST['option_stock'];
        $option_weights = $_POST['option_weight'];
        $len = count($option_idss);
        $optionids = array();
        for ($k = 0; $k < $len; $k++) {
            $option_id = "";
            $get_option_id = $_GPC['option_id_' . $ids][0];

            $ids = $option_idss[$k];
            $idsarr = explode("_", $ids);
            $newids = array();
            foreach ($idsarr as $key => $ida) {
                foreach ($spec_items as $it) {
                    if ($it['get_id'] == $ida) {
                        $newids[] = $it['id'];
                        break;
                    }
                }
            }
            $newids = implode("_", $newids);
            $a = array(
                "title" => $_GPC['option_title_' . $ids][0],
                "productprice" => $_GPC['option_productprice_' . $ids][0],
                "costprice" => $_GPC['option_costprice_' . $ids][0],
                "marketprice" => $_GPC['option_marketprice_' . $ids][0],
                "stock" => $_GPC['option_stock_' . $ids][0],
                "weight" => $_GPC['option_weight_' . $ids][0],
                "goodsid" => $id,
                "specs" => $newids
            );

            pdo_insert("tg_goods_option", $a);
            $option_id = pdo_insertid();

            $optionids[] = $option_id;
            $option[$k] = $a;
        }
        $option = serialize($option);
        if ($id > 0) {
            if (count($optionids) > 0) {
                pdo_query("delete from " . tablename('tg_goods_option') . " where goodsid={$id} and id not in ( " . implode(',', $optionids) . ")");
            } else {
                pdo_query("delete from " . tablename('tg_goods_option') . " where goodsid={$id}");
            }
        }
        if (!empty($goods)) {
            pdo_insert('tg_goods_modify', array('goods_option' => $option, 'goods_param' => $param, 'uid' => $_W['user']['uid'], 'goodsid' => $goods['id'], 'oplog_id' => $oplog_id));
        }

        pdo_delete("tg_goods_kj", array("g_id" => $id));
        unset($data);
        $rule_pice_arr = $_GPC["rule_pice"];
        $rule_start_arr = $_GPC["rule_start"];
        $rule_end_arr = $_GPC["rule_end"];
        $last_price = $_GPC["last_price"];
//        $last_price = $_GPC["last_price"];
        for ($i = 0; $i < count($rule_pice_arr); $i++) {
            $data["g_id"] = $id;
            $data["rule_price"] = $rule_pice_arr[$i];
            $data["rule_end"] = $rule_end_arr[$i];
            $data["rule_start"] = $rule_start_arr[$i];
            $data["last_price"] = $last_price;
            $data["uniacid"] = $_W["uniacid"];
            $res = pdo_insert("tg_goods_kj", $data);
            unset($data);
        }

        $tip = '商品信息保存成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('goods/kanjia/list') . "';</script>";
        exit;

    }

    if (!$goods['spike_start']) {
        $goods['spike_start'] = TIMESTAMP;
        $goods['spike_end'] = TIMESTAMP + 3 * 24 * 60 * 60;
    }

    include wl_template("goods/goods_kanjia");
    exit;
}

include wl_template("goods/kanjia");