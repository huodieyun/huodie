<?php

$_W['page']['title'] = "极限单品 - 商品管理";
$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}
global $_W, $_GPC;
load()->func("tpl");
wl_load()->model('setting');
$province = pdo_getall('erp_area', array('level' => 1));

if ($op == "display") {

    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $con = ' where 1=1 ';
    $keyword = $_GPC['keyword'];
    if (!empty($keyword)) {
        if ($_GPC['con_type'] == 1) {
            $con .= " and name like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 2) {
            $sid = intval(str_replace('JXDP', '', $keyword));
            $con .= " and id = {$sid} ";
        }
        if ($_GPC['con_type'] == 3) {
            $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
        }
    }
    if (!is_null($_GPC['ctype'])) {
        $con .= " and ctype = {$_GPC['ctype']} ";
    }
    $status = $_GPC["status"];
    $status = intval($status);
    $supplier = pdo_fetch("SELECT id FROM " . tablename('tg_platform_supplier') . " WHERE uniacid = " . $_W['uniacid']);
    if ($_W['uniacid'] != 33) {
        $con .= " and supply_id = '{$_W['uniacid']}' ";
    }

    if ($status != 5) {
        if ($status == 3) {
            $con .= " AND status in (3,-3) ";
        } else {
            $con .= " AND status = '{$status}' ";
        }
    }

    $sortname = 'createtime';
    $sortasc = 'desc';

    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_supply_goods') . $con);
    $goodses = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods') . $con . ' ORDER BY displayorder desc , ' . $sortname . ' ' . $sortasc . " LIMIT " . ($page - 1) * $size . " , " . $size);
    $pager = pagination($total, $page, $size);
    foreach ($goodses as &$goods) {
        $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $goods['supply_id']);
        $goods['supply_name'] = $ac['name'];
        $goods['on_shelf'] = pdo_fetchcolumn("select COUNT(id) from " .tablename('tg_goods') . " where supply_goodsid = :id and isshow = 1 " , array(':id' => $goods['id']));
        if (!$goods['on_shelf']) {
            $goods['on_shelf'] = pdo_fetchcolumn("select COUNT(id) from " . tablename('tg_goods') . " where supply_goodsid = :id and isshow = 1 ", array(':id' => -$goods['id']));
        }

        $my_goods = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$_W['uniacid']}' and supply_goodsid = :id ", array(':id' => $goods['id']));//零售
        $my_good = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$_W['uniacid']}' and supply_goodsid = :id ", array(':id' => -$goods['id']));//批发

        $goodsnum = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_goods') . " WHERE supply_goodsid = :id ", array(':id' => $goods['id']));
        $goodnum = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_goods') . " WHERE supply_goodsid = :id ", array(':id' => -$goods['id']));
        $goods['turn_down'] = 0;
        if ($goodsnum == 0 && $goodnum == 0) {
            $goods['turn_down'] = 1;
        }

        if ($my_good) {
            $goods['copy'] = 1;
        }
        $goods['is_copy'] = 0;
        if (!empty($my_goods)) {
            $goods['is_copy'] = 1;
        }

        $goods['pici'] = "JXDP" . sprintf("%04d", $goods['id']);
        $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $goods['id']));
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

        $yunfeiids = explode(",", $goods['yunfei_id']);
        foreach ($yunfeiids as $key => $value) {
            if ($value) {
                $dispatch_list[$key] = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id ='{$value}'  and status=2");
            }
        }
        $goods['dispatch_list'] = $dispatch_list;
        $goods['options'] = $options;
        unset($items);
        unset($specs);
        unset($specitemids);
        unset($options);
        unset($goods);
        unset($ac);
    }

    include wl_template("platform/goods_display");
    exit();
}

//修改排序
if ($op == 'numx') {
    $orid = $_GPC['oridd'];
    $paixv = $_GPC['paixvv'];
    pdo_update("tg_supply_goods", array('displayorder' => $paixv), array('id' => $orid));
    die(json_encode($paixv));
}

//到期续期
if ($op == 'delay') {
    $id = intval($_GPC['id']);
    $delaytime = strtotime($_GPC['activetime']);
    $r = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = {$id} ");
    $activetime = $r['activetime'];
    if ($activetime == $delaytime) {
        die(json_encode(2));
    }
    $rs = pdo_update('tg_supply_goods', array('activetime' => $delaytime), array('id' => $id));
    die(json_encode($rs));

}
//我的采纳
if ($op == "buy_goods_list") {

    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $con = ' where 1=1 ';
    $keyword = $_GPC['keyword'];
    if (!empty($keyword)) {
        if ($_GPC['con_type'] == 1) {
            $con .= " and name like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 2) {
            $sid = intval(str_replace('JXDP', '', $keyword));
            $con .= " and id = {$sid} ";
        }
        if ($_GPC['con_type'] == 3) {
            $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
        }
    }
    if (!is_null($_GPC['ctype'])) {
        $con .= " and ctype = {$_GPC['ctype']} ";
    }
    $con .= " and id in (select supply_goodsid from cm_tg_goods where uniacid=" . $_W['uniacid'] . ") ";
    $status = $_GPC["status"];
    $status = intval($status);
    $supplier = pdo_fetch("SELECT id FROM " . tablename('tg_platform_supplier') . " WHERE uniacid = " . $_W['uniacid']);
    $con .= " AND status = 1 ";
    $sortname = 'createtime';
    $sortasc = 'asc';

    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_supply_goods') . $con);
    $goodses = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods') . $con . ' ORDER BY displayorder desc , ' . $sortname . ' ' . $sortasc . " LIMIT " . ($page - 1) * $size . " , " . $size);
    $pager = pagination($total, $page, $size);
    foreach ($goodses as &$goods) {
        $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $goods['supply_id']);
        $goods['supply_name'] = $ac['name'];
        $goods['on_shelf'] = pdo_fetchcolumn("select COUNT(id) from " .tablename('tg_goods') . " where supply_goodsid = :id and isshow = 1 " , array(':id' => $goods['id']));
        if (!$goods['on_shelf']) {
            $goods['on_shelf'] = pdo_fetchcolumn("select COUNT(id) from " . tablename('tg_goods') . " where supply_goodsid = :id and isshow = 1 ", array(':id' => -$goods['id']));
        }

        $my_goods = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$_W['uniacid']}' and supply_goodsid = :id ", array(':id' => $goods['id']));//零售
        $my_good = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$_W['uniacid']}' and supply_goodsid = :id ", array(':id' => -$goods['id']));//批发

        $goodsnum = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_goods') . " WHERE supply_goodsid = :id ", array(':id' => $goods['id']));
        $goodnum = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_goods') . " WHERE supply_goodsid = :id ", array(':id' => -$goods['id']));
        $goods['turn_down'] = 0;
        if ($goodsnum == 0 && $goodnum == 0) {
            $goods['turn_down'] = 1;
        }

        if ($my_good) {
            $goods['copy'] = 1;
        }
        $goods['is_copy'] = 0;
        if (!empty($my_goods)) {
            $goods['is_copy'] = 1;
        }

        $goods['pici'] = "JXDP" . sprintf("%04d", $goods['id']);
        $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $goods['id']));
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

        $yunfeiids = explode(",", $goods['yunfei_id']);
        foreach ($yunfeiids as $key => $value) {
            if ($value) {
                $dispatch_list[$key] = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id ='{$value}'  and status=2");
            }
        }
        $goods['dispatch_list'] = $dispatch_list;
        $goods['options'] = $options;
        unset($items);
        unset($specs);
        unset($specitemids);
        unset($options);
        unset($goods);
        unset($ac);
    }

    include wl_template("platform/goods_display");
    exit();
}
//货源市场
if ($op == "supply_goods_list") {

    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $con = ' where 1=1 ';
    $keyword = $_GPC['keyword'];
    if (!empty($keyword)) {
        if ($_GPC['con_type'] == 1) {
            $con .= " and name like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 2) {
            $sid = intval(str_replace('JXDP', '', $keyword));
            $con .= " and id = {$sid} ";
        }
        if ($_GPC['con_type'] == 3) {
            $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
        }
    }
    if (!is_null($_GPC['ctype'])) {
        $con .= " and ctype = {$_GPC['ctype']} ";
    }
    $status = $_GPC["status"];
    $status = intval($status);
    $supplier = pdo_fetch("SELECT id FROM " . tablename('tg_platform_supplier') . " WHERE uniacid = " . $_W['uniacid']);
    $con .= " AND status = 1 ";
    $sortname = 'createtime';
    $sortasc = 'asc';

    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('tg_supply_goods') . $con);
    $goodses = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods') . $con . ' ORDER BY displayorder desc , ' . $sortname . ' ' . $sortasc . " LIMIT " . ($page - 1) * $size . " , " . $size);
    $pager = pagination($total, $page, $size);
    foreach ($goodses as &$goods) {
        $ac = pdo_fetch("SELECT name FROM " . tablename('account_wechats') . " WHERE uniacid = " . $goods['supply_id']);
        $goods['supply_name'] = $ac['name'];
        $goods['on_shelf'] = pdo_fetchcolumn("select COUNT(id) from " .tablename('tg_goods') . " where supply_goodsid = :id and isshow = 1 " , array(':id' => $goods['id']));
        if (!$goods['on_shelf']) {
            $goods['on_shelf'] = pdo_fetchcolumn("select COUNT(id) from " . tablename('tg_goods') . " where supply_goodsid = :id and isshow = 1 ", array(':id' => -$goods['id']));
        }

        $my_goods = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$_W['uniacid']}' and supply_goodsid = :id ", array(':id' => $goods['id']));//零售
        $my_good = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE uniacid = '{$_W['uniacid']}' and supply_goodsid = :id ", array(':id' => -$goods['id']));//批发

        $goodsnum = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_goods') . " WHERE supply_goodsid = :id ", array(':id' => $goods['id']));
        $goodnum = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_goods') . " WHERE supply_goodsid = :id ", array(':id' => -$goods['id']));
        $goods['turn_down'] = 0;
        if ($goodsnum == 0 && $goodnum == 0) {
            $goods['turn_down'] = 1;
        }

        if ($my_good) {
            $goods['copy'] = 1;
        }
        $goods['is_copy'] = 0;
        if (!empty($my_goods)) {
            $goods['is_copy'] = 1;
        }

        $goods['pici'] = "JXDP" . sprintf("%04d", $goods['id']);
        $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $goods['id']));
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

        $yunfeiids = explode(",", $goods['yunfei_id']);
        foreach ($yunfeiids as $key => $value) {
            if ($value) {
                $dispatch_list[$key] = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id ='{$value}'  and status=2");
            }
        }
        $goods['dispatch_list'] = $dispatch_list;
        $goods['options'] = $options;
        unset($items);
        unset($specs);
        unset($specitemids);
        unset($options);
        unset($goods);
        unset($ac);
    }

    include wl_template("platform/goods_display");
    exit();
}
//货源商品详情
if ($op == "supply_goods_detail") {
    $supplier = pdo_get('tg_platform_shop', array('uniacid' => $_W['uniacid']));
    if (!$supplier || $supplier['status'] != 1) {
        header("Location: ". web_url('platform/platform_shop'));
    }
    $id = $_GPC["id"];
    $goods = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = :id", array(":id" => $id));
    $goods['gimg'] = tomedia($goods['gimg']);
    $goods['share_image'] = tomedia($goods['share_image']);
    $yunfeiids = explode(",", $goods['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select id,name from " . tablename('tg_delivery_template') . " where id = '{$value}' ");
        }
    }
    $goods['dispatch_list'] = $dispatch_list;
    unset($dispatch_list);
    $params = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = '{$id}' ");
    $goods['params'] = $params;
    if ($goods["hasoption"] == 1) {
        //查询分类和分类信息

        $allspecs = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec') . " WHERE supply_id = :supply_id AND goodsid = :goodsid ORDER BY displayorder ASC", array("supply_id" => $goods["supply_id"], "goodsid" => $id));
        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec_item') . " WHERE supply_id = :supply_id AND specid = :specid ORDER BY displayorder ASC", array(":supply_id" => $goods["supply_id"], ":specid" => $s['id']));
        }
        unset($s);

        //处理规格项
        $html = "";
        $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
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
            $html .= '<th class="danger" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">供应价格</div><div class="input-group"><input type="text" class="form-control option_costprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_costprice\');"></a></span></div></div></th>';
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
    }
    $goods['guige'] = $html;

    include wl_template("platform/supply_goods_detail");
    exit();
}
// 上下架广告轮播图
if ($op == "up_down") {
    $goods = pdo_update('tg_supply_goods', array('is_advert_img' => $_GPC['is_advert_img']), array('id' => $_GPC['goodsid']));
    die(json_encode($goods));
}

if ($op == 'options') {
    $id = $_GPC['id'];
    $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
    $allspecs = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec') . " WHERE goodsid=:id ORDER BY displayorder ASC", array(':id' => $id));
    foreach ($allspecs as &$s) {
        $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec_item') . " WHERE  `show`=1 AND specid=:specid ORDER BY displayorder ASC", array(":specid" => $s['id']));
    }
    unset($s);
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
    $data = array('options' => $options, 'allspecs' => $allspecs);
    die(json_encode($data));
}
//上传协议
if ($op == 'xieyi_up') {
    $id = $_GPC["id"];
    $xieyi_img = $_GPC["xieyi_img"];
    $res = pdo_update("tg_supply_goods", array('xieyi_img' => $xieyi_img), array("id" => intval($id)));
    die(json_encode(array("status" => 1)));
}
//审核
if ($op == "check") {
    $id = $_GPC["id"];
    $status = $_GPC["status"];
    $reason = $_GPC["reason"];
    $data["status"] = intval($status);
    $data["reason"] = $reason;
    $data["has_subsidy"] = $_GPC["has_subsidy"];

    $res = pdo_update("tg_supply_goods", $data, array("id" => intval($id)));
    $goods = pdo_fetch("SELECT supply_id,name,mprice,stock FROM " . tablename('tg_supply_goods') . " WHERE id = " . $id);
    if ($status == 1) {

        $setting = setting_get_by_name('subsidy');
        $percent = $setting['percent'];

        $message = '审核成功';
        $shops = pdo_fetchall("SELECT openid FROM " . tablename('tg_platform_shop') . " WHERE status = 1 ");
        $dat['first'] = '新品上架';
        $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $dat['keyword2'] = '火蝶云极限单品供应商上架新品';
        $dat['keyword3'] = '火蝶云极限单品有供应商上架名称：【' .$goods['name'] .'】价格：¥' . $goods['mprice'] .'，库存：' .$goods['stock'] .'，的新品';
        $dat['keyword4'] = '等候您采纳,上架即享'.$percent.'%补贴，采多少补多少！';
        if($goods['ctype']==0){
            $dat['remark'] = '模式：一件代发';
        }else{
            $dat['remark'] = '模式：批发';
        }
        foreach ($shops as $shop) {
            $dat['openid'] = $shop['openid'];
            if ($dat['openid']) {
                pdo_insert('tg_service_process', $dat);
            }
        }
    } else {
        $message = '审核失败';
        $reason = '审核失败' . "(原因:" . $reason . ") 请前往【极限单品】【货源管理】【我的供应】中修改提交审核";
    }

    $supplier = pdo_fetch("SELECT openid FROM " . tablename('tg_platform_supplier') . " WHERE uniacid = " . $goods['supply_id']);
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '极限单品供应商商品审核进度';
        $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $dat['keyword2'] = '极限单品商品审核结果';
        $dat['keyword3'] = '火蝶云极限单品供应商商品【' .$goods['name'] .'】审核' . $reason;
        $dat['keyword4'] = $message;
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }

    die(json_encode(array("status" => "success")));
}
if($op == 'set_subsidy'){
    $id = $_GPC["id"];
    $data["has_subsidy"] = $_GPC["has_subsidy"];
    $res = pdo_update("tg_supply_goods", $data, array("id" => intval($id)));
    die(json_encode(array("status" => "success")));
}
//商品下架
if ($op == 'down') {
    $id = intval($_GPC['id']);
    $reason = trim($_GPC['reason']);
    $data['stock'] = 0;

    $goods = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $id);
    if ($_W['uniacid'] == 33) {
        $data['status'] = -3;
        $data['platform_reason'] = $reason;
        $message = '平台强制下架';
    } elseif ($_W['uniacid'] == $goods['supply_id']) {
        $data['status'] = 3;
        $data['supply_reason'] = $reason;
        $message = '供应商下架';
    }
    $res = pdo_update('tg_supply_goods', $data, array('id' => $id));
    if ($goods['hasoption'] == 1) {
        pdo_update('tg_goods_supply_option', array('stock' => 0), array('goodsid' => $id));
    }
    $god = pdo_fetchall("SELECT * FROM " . tablename('tg_goods') . " WHERE supply_goodsid = " . $id);
    foreach ($god as $item) {
        if ($goods['hasoption'] == 1) {
            pdo_update('tg_goods_option', array('stock' => 0), array('goodsid' => $item['id']));
        }
        pdo_update('tg_goods', array('gnum' => 0), array('id' => $item['id']));
    }

    $dat['first'] = '极限单品货源管理';
    $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $dat['keyword2'] = '极限单品商品下架';
    $dat['keyword3'] = '火蝶云极限单品供应商商品【' . $goods['name'] . "】已被" . $message . "(原因:" . $reason . ")";
    $dat['keyword4'] = $message;
    $dat['remark'] = '';

    if ($_W['uniacid'] == 33) {
        $supplier = pdo_fetch("SELECT openid FROM " . tablename('tg_platform_supplier') . " WHERE uniacid = " . $goods['supply_id']);
        $dat['openid'] = $supplier['openid'];
        if ($dat['openid']) {
            pdo_insert('tg_service_process', $dat);
        }
    }

    $shop = pdo_fetchall("select openid from " . tablename('tg_platform_shop') . " where uniacid in ( select uniacid from " . tablename('tg_goods') . " where supply_goodsid = {$id} ) ");
    foreach ($shop as &$it) {
        $dat['openid'] = $it['openid'];
        if ($dat['openid']) {
            pdo_insert('tg_service_process', $dat);
        }
    }

    die(json_encode(array('status' => $res, 'message' => '下架成功')));
}
//编辑
if ($op == "edit") {
    //查询这个商品.
    $id = $_GPC["id"];

    if (!empty($id)) {
        //查询商品详情
        $goods = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = :id", array(":id" => $id));
        $goods["gnum"] = $goods["stock"];
        $yunfeiids = explode(",", $goods['yunfei_id']);
        foreach ($yunfeiids as $key => $value) {
            if ($value) {
                $dispatch_list[$key] = pdo_fetch("select id,name from " . tablename('tg_delivery_template') . " where id = '{$value}' ");
            }
        }
        unset($value);

        $piclist = unserialize($goods["img"]);
        $params = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = '{$id}' ");
        if ($goods["hasoption"] == 1) {
            //查询分类和分类信息

            $allspecs = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec') . " WHERE supply_id = :supply_id AND goodsid = :goodsid ORDER BY displayorder ASC", array("supply_id" => $goods["supply_id"], "goodsid" => $id));
            foreach ($allspecs as &$s) {
                $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec_item') . " WHERE supply_id = :supply_id AND specid = :specid ORDER BY displayorder ASC", array(":supply_id" => $goods["supply_id"], ":specid" => $s['id']));
            }
            unset($s);

            //处理规格项
            $html = "";
            $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
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
                $html .= '<th class="danger" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">供应价格</div><div class="input-group"><input type="text" class="form-control option_costprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_costprice\');"></a></span></div></div></th>';
                $html .= '<th class="primary" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">市场价格</div><div class="input-group"><input type="text" class="form-control option_mprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_mprice\');"></a></span></div></div></th>';
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
                    $val = array("id" => "", "title" => "", "stock" => "", "costprice" => "", "productprice" => "", "marketprice" => "","mprice"=>"", "weight" => "");
                    foreach ($options as $o) {
                        if ($ids === $o['specs']) {
                            $val = array(
                                "id" => $o['id'],
                                "title" => $o['title'],
                                "stock" => $o['stock'],
                                "costprice" => $o['costprice'],
                                "productprice" => $o['productprice'],
                                "marketprice" => $o['marketprice'],
                                "mprice" => $o['mprice'],
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
                    $hh .= '<td class="primary"><input name="option_mprice_' . $ids . '[]" type="text" class="form-control option_mprice option_mprice_' . $ids . '" " value="' . $val['mprice'] . '"/></td>';
                    $hh .= '<td class="info"><input name="option_weight_' . $ids . '[]" type="text" class="form-control option_weight option_weight_' . $ids . '" " value="' . $val['weight'] . '"/></td>';
                    $hh .= '</tr>';
                }
                $html .= $hh;
                $html .= "</table>";
            }
            unset($hh);

        }
//        die(json_encode(array('s' => $allspecs)));
        if (checksubmit()) {

            //删除所有的分类
//            $spec = pdo_fetchall("SELECT * FROM " . tablename("tg_supply_spec") . " WHERE supply_id = :uniacid AND goodsid = :goodsid", array(":uniacid" => $goods["supply_id"], ":goodsid" => $id));
//            for ($i = 0; $i < count($spec); $i++) {
//                pdo_delete("tg_supply_spec_item", array("specid" => $spec[$i]["id"], "supply_id" => $goods["supply_id"]));
//            }
//            pdo_delete("tg_supply_spec", array("goodsid" => $id, "supply_id" => $goods["supply_id"]));

            $supply_id = $goods["supply_id"];

            $goods = $_GPC["goods"];
            //运费ID
            $dispatchs = $_GPC['dispatchs'];
            $str1 = '';
            if ($dispatchs) {
                foreach ($dispatchs as $key => $value) {
                    $str1 .= $value . ",";
                }

            }
            unset($dispatchs);
            $data["name"] = $goods["gname"];//name
            $data["hasoption"] = $_GPC["hasoption"];//name
            $data["activetime"] = strtotime($_GPC["activetime"]);//name
            $data["img"] = serialize($_GPC['img']);//name
            $data["unit"] = $goods["unit"];//单位
            $data["stock"] = $goods["gnum"];//库存
            $data["taxrate"] = $goods["taxrate"];//费率
            $data["createtime"] = time();//上架时间
            $data["salenum"] = $goods["salenum"];//销量
            $data["oprice"] = $goods["oprice"];//售价
            $data["mprice"] = $goods["mprice"];//原价
            $data["weight"] = $goods["weight"];//重量
            $data["gimg"] = $goods["gimg"]; // 首页图
            $data["advert_img"] = $goods["advert_img"]; // 广告图
            $data["issharedesc"] = $goods["issharedesc"];
            $data['yunfei_id'] = $str1;
            $data["isfree"] = $goods["isfree"];//是否包邮
            $data["goods_freight"] = $goods["goods_freight"];//邮费
            $data["isshowsend"] = $goods["isshowsend"];//显示发货记录;
            $data["isshow"] = $goods["isshow"];//是否上架
            $data["share_image"] = $goods["share_image"];//分享图片
            $data["share_title"] = $goods["share_title"];//分享名字
            $data["share_desc"] = $goods["share_desc"];//分享描述
            $data["goodsdesc"] = $goods["goodsdesc"];//详情简介
            $data["indexdesc"] = $goods["indexdesc"];//首页简介
            $data["gdesc"] = $goods["gdesc"];//商品详情
            $data["share_title"] = $goods['share_title'];//分享标题
            $data["index_video"] = $_GPC["index_video"];//首页视频
            $data["goods_style"] = $goods["goods_style"];//商品性质
            $data["ctype"] = $goods["ctype"];//批发零售
            $data["min_num"] = $goods["min_num"];//起订量
//            $data = array_filter($data);

            $res = pdo_update("tg_supply_goods", $data, array("id" => $id));

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
                    pdo_insert("tg_supply_goods_param", $a);
                    $param_id = pdo_insertid();
                } else {
                    pdo_update("tg_supply_goods_param", $a, array('id' => $get_param_id));
                    $param_id = $get_param_id;
                }
                $paramids[] = $param_id;
                $param[$k] = $a;
            }
            $param = serialize($param);
            if (count($paramids) > 0) {
                pdo_query("DELETE FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = :id AND id NOT IN ( " . implode(',', $paramids) . ")", array(':id' => $id));
            } else {
                pdo_query("DELETE FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = :id", array(':id' => $id));
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
                    "supply_id" => $supply_id,
                    "goodsid" => $id,
                    "displayorder" => $k,
                    "title" => $spec_titles[$get_spec_id]
                );

                pdo_insert("tg_supply_spec", $a);
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
                        "supply_id" => $supply_id,
                        "specid" => $spec_id,
                        "displayorder" => $n,
                        "title" => trim($spec_item_titles[$n]),
                        "show" => $spec_item_shows[$n],
                        "thumb" => $spec_item_thumbs[$n]
                    );
                    $f = "spec_item_thumb_" . $get_item_id;

                    pdo_insert("tg_supply_spec_item", $d);
                    $item_id = pdo_insertid();
                    $itemids[] = $item_id;
                    //临时记录，用于保存规格项
                    $d['get_id'] = $get_item_id;
                    $d['id'] = $item_id;
                    $spec_items[] = $d;
                }
                //删除其他的

                if (count($itemids) > 0) {
                    pdo_query("delete from " . tablename('tg_supply_spec_item') . " where supply_id = {$supply_id} and specid = $spec_id and id not in (" . implode(",", $itemids) . ")");
                } else {
                    pdo_query("delete from " . tablename('tg_supply_spec_item') . " where supply_id = {$supply_id} and specid = $spec_id");
                }

                //更新规格项id
                pdo_update("tg_supply_spec", array("content" => serialize($itemids)), array("id" => $spec_id));
                $specids[] = $spec_id;
            }
            //删除其他的

            if (count($specids) > 0) {
                pdo_query("delete from " . tablename('tg_supply_spec') . " where supply_id = {$supply_id} and goodsid = $id and id not in (" . implode(",", $specids) . ")");
            } else {
                pdo_query("delete from " . tablename('tg_supply_spec') . " where supply_id = {$supply_id} and goodsid = $id");
            }

            if ($id > 0) {
                pdo_query("delete from " . tablename('tg_goods_supply_option') . " where uniacid = {$supply_id} and goodsid = {$id}");

            }

            //保存规格
            $option_idss = $_POST['option_ids'];
            $option_productprices = $_POST['option_productprice'];
            $option_marketprices = $_POST['option_marketprice'];
            $option_costprices = $_POST['option_costprice'];
            $option_mprice = $_POST['option_mprice'];
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
                    "mprice" => $_GPC['option_mprice_' . $ids][0],
                    "stock" => $_GPC['option_stock_' . $ids][0],
                    "weight" => $_GPC['option_weight_' . $ids][0],
                    "goodsid" => $id,
                    "uniacid" => $supply_id,
                    "specs" => $newids
                );

                pdo_insert("tg_goods_supply_option", $a);
                $option_id = pdo_insertid();

                $optionids[] = $option_id;
                $option[$k] = $a;
            }
            $option = serialize($option);
            if ($id > 0) {
                if (count($optionids) > 0) {
                    pdo_query("delete from " . tablename('tg_goods_supply_option') . " where goodsid={$id} and id not in ( " . implode(',', $optionids) . ")");
                } else {
                    pdo_query("delete from " . tablename('tg_goods_supply_option') . " where goodsid={$id}");
                }
            }

            $tip = '商品信息保存成功';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('platform/goods_list', array('status' => 5)) . "';</script>";
            exit;

        }

    } else {

        if (checksubmit()) {
            $goods = $_GPC["goods"];
            //运费ID
            $dispatchs = $_GPC['dispatchs'];
            $str1 = '';
            if ($dispatchs) {
                foreach ($dispatchs as $key => $value) {
                    $str1 .= $value . ",";
                }

            }
            unset($dispatchs);
            $data["name"] = $goods["gname"];//name
            $data["hasoption"] = $_GPC["hasoption"];//name
            $data["activetime"] = strtotime($_GPC["activetime"]);//name
            $data["img"] = serialize($_GPC['img']);//name
            $data["unit"] = $goods["unit"];//单位
            $data["stock"] = $goods["gnum"];//库存
            $data["taxrate"] = $goods["taxrate"];//费率
            $data["createtime"] = time();//上架时间
            $data["salenum"] = $goods["salenum"];//销量
            $data["oprice"] = $goods["oprice"];//售价
            $data["mprice"] = $goods["mprice"];//原价
            $data["weight"] = $goods["weight"];//重量
            $data["gimg"] = $goods["gimg"]; // 首页图
            $data["advert_img"] = $goods["advert_img"]; // 广告图
            $data["issharedesc"] = $goods["issharedesc"];
            $data["isfree"] = $goods["isfree"];//是否包邮
            $data["goods_freight"] = $goods["goods_freight"];//邮费
            $data["isshowsend"] = $goods["isshowsend"];//显示发货记录;
            $data['yunfei_id'] = $str1;
            $data["isshow"] = $goods["isshow"];//是否上架
            $data["share_image"] = $goods["share_image"];//分享图片
            $data["share_title"] = $goods["share_title"];//分享名字
            $data["share_desc"] = $goods["share_desc"];//分享描述
            $data["goodsdesc"] = $goods["goodsdesc"];//详情简介
            $data["indexdesc"] = $goods["indexdesc"];//首页简介
            $data["gdesc"] = $goods["gdesc"];//商品详情
            $data["supply_id"] = $_W['uniacid'];//商家id
            $data["share_title"] = $goods['share_title'];//分享标题
            $data["index_video"] = $_GPC["index_video"];//首页视频
            $data["goods_style"] = $goods["goods_style"];//商品性质
            $data["ctype"] = $goods["ctype"];//批发零售
            $data["min_num"] = $goods["min_num"];//起订量
            pdo_insert("tg_supply_goods", $data);
            $id = pdo_insertid();

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
                    pdo_insert("tg_supply_goods_param", $a);
                    $param_id = pdo_insertid();
                } else {
                    pdo_update("tg_supply_goods_param", $a, array('id' => $get_param_id));
                    $param_id = $get_param_id;
                }
                $paramids[] = $param_id;
                $param[$k] = $a;
            }
            $param = serialize($param);
            if (count($paramids) > 0) {
                pdo_query("DELETE FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = :id AND id NOT IN ( " . implode(',', $paramids) . ")", array(':id' => $id));
            } else {
                pdo_query("DELETE FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = :id ", array(':id' => $id));
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
                    "supply_id" => $_W['uniacid'],
                    "goodsid" => $id,
                    "displayorder" => $k,
                    "title" => $spec_titles[$get_spec_id]
                );

                pdo_insert("tg_supply_spec", $a);
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
                        "supply_id" => $_W['uniacid'],
                        "specid" => $spec_id,
                        "displayorder" => $n,
                        "title" => trim($spec_item_titles[$n]),
                        "show" => $spec_item_shows[$n],
                        "thumb" => $spec_item_thumbs[$n]
                    );
                    $f = "spec_item_thumb_" . $get_item_id;

                    pdo_insert("tg_supply_spec_item", $d);
                    $item_id = pdo_insertid();
                    $itemids[] = $item_id;
                    //临时记录，用于保存规格项
                    $d['get_id'] = $get_item_id;
                    $d['id'] = $item_id;
                    $spec_items[] = $d;
                }

                pdo_update("tg_supply_spec", array("content" => serialize($itemids)), array("id" => $spec_id));
                $specids[] = $spec_id;
            }

            if ($id > 0) {
                pdo_query("delete from " . tablename('tg_goods_supply_option') . " where uniacid={$_W['uniacid']} and goodsid={$id}");

            }

            //保存规格
            $option_idss = $_POST['option_ids'];
            $option_productprices = $_POST['option_productprice'];
            $option_marketprices = $_POST['option_marketprice'];
            $option_costprices = $_POST['option_costprice'];
            $option_mprice = $_POST['option_mprice'];
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
                    "mprice" => $_GPC['option_mprice_' . $ids][0],
                    "stock" => $_GPC['option_stock_' . $ids][0],
                    "weight" => $_GPC['option_weight_' . $ids][0],
                    "goodsid" => $id,
                    "uniacid" => $_W['uniacid'],
                    "specs" => $newids
                );

                pdo_insert("tg_goods_supply_option", $a);
                $option_id = pdo_insertid();

                $optionids[] = $option_id;
                $option[$k] = $a;
            }
            $option = serialize($option);
            if ($id > 0) {
                if (count($optionids) > 0) {
                    pdo_query("delete from " . tablename('tg_goods_supply_option') . " where goodsid = {$id} and id not in ( " . implode(',', $optionids) . ")");
                } else {
                    pdo_query("delete from " . tablename('tg_goods_supply_option') . " where goodsid = {$id}");
                }
            }

            $tip = '商品信息保存成功';
            echo "<script>alert('" . $tip . "');location.href='" . web_url('platform/goods_list', array('status' => 5)) . "';</script>";
            exit;
        }
    }

    include wl_template("platform/platform_goods_list");
}

//未采纳驳回
if ($op == 'turn_down') {

    $id = intval($_GPC['id']);
    $reason = trim($_GPC['reason']);

    $data["status"] = 0;
    $data["reason"] = $reason;
    $res = pdo_update("tg_supply_goods", $data, array("id" => intval($id)));
    if ($res) {
        $message = '驳回成功！';
        $reason = "(原因:" . $reason . ") 请前往【极限单品】【货源管理】【我的供应】中修改提交审核";

        $goods = pdo_fetch("SELECT supply_id,name FROM " . tablename('tg_supply_goods') . " WHERE id = " . $id);
        $supplier = pdo_fetch("SELECT openid FROM " . tablename('tg_platform_supplier') . " WHERE uniacid = " . $goods['supply_id']);
        $dat['openid'] = $supplier['openid'];
        if ($dat['openid']) {
            $dat['first'] = '极限单品供应商商品审核进度';
            $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $dat['keyword2'] = '极限单品商品审核结果';
            $dat['keyword3'] = '火蝶云极限单品供应商商品【' .$goods['name'] .'】被驳回' . $reason;
            $dat['keyword4'] = $message;
            $dat['remark'] = '';
            pdo_insert('tg_service_process', $dat);
        }
    } else {
        $message = '驳回失败！';
    }
    die(json_encode(array('status' => $res , 'message' => $message)));
}
//运费详情
if ($op == 'yunfei_detail') {
    $id = intval($_GPC['id']);
    $dispatch = pdo_fetch("select * from " . tablename('tg_delivery_template') . " where id = {$id} ");
    $info['region'] = json_decode($dispatch['region'], true);
    $info['id'] = $dispatch['id'];
    $info['name'] = $dispatch['name'];
    $info['code'] = $dispatch['code'];
    $info['updatetime'] = $dispatch['updatetime'];
    $info['status'] = $dispatch['status'];
    $info['displayorder'] = $dispatch['displayorder'];
    $info['region'] = array_filter($info['region']);
    $info['region'] = array_merge($info['region']);
    $prices = array_filter($info);
    die(json_encode(array('status' => 1, 'data' => $prices)));
}
//商品详情
if ($op == 'detail') {
    $id = $_GPC["id"];
    $goods = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = :id", array(":id" => $id));
    $goods['gimg'] = tomedia($goods['gimg']);
    $goods['share_image'] = tomedia($goods['share_image']);
    $yunfeiids = explode(",", $goods['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select id,name from " . tablename('tg_delivery_template') . " where id = '{$value}' ");
        }
    }
    $goods['dispatch_list'] = $dispatch_list;
    unset($dispatch_list);
    $params = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = '{$id}' ");
    $goods['params'] = $params;
    if ($goods["hasoption"] == 1) {
        //查询分类和分类信息

        $allspecs = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec') . " WHERE supply_id = :supply_id AND goodsid = :goodsid ORDER BY displayorder ASC", array("supply_id" => $goods["supply_id"], "goodsid" => $id));
        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_spec_item') . " WHERE supply_id = :supply_id AND specid = :specid ORDER BY displayorder ASC", array(":supply_id" => $goods["supply_id"], ":specid" => $s['id']));
        }
        unset($s);

        //处理规格项
        $html = "";
        $options = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
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
            $html .= '<th class="danger" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">供应价格</div><div class="input-group"><input type="text" class="form-control option_costprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_costprice\');"></a></span></div></div></th>';
            $html .= '<th class="primary" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">市场价格</div><div class="input-group"><input type="text" class="form-control option_mprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_mprice\');"></a></span></div></div></th>';

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
                            "mprice" => $o['mprice'],
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
                $hh .= '<td class="primary"><input name="option_mprice_' . $ids . '[]" type="text" class="form-control option_mprice option_mprice_' . $ids . '" " value="' . $val['mprice'] . '"/></td>';
                $hh .= '<td class="info"><input name="option_weight_' . $ids . '[]" type="text" class="form-control option_weight option_weight_' . $ids . '" " value="' . $val['weight'] . '"/></td>';
                $hh .= '</tr>';
            }
            $html .= $hh;
            $html .= "</table>";
        }
        unset($hh);
    }
    $goods['guige'] = $html;
    die(json_encode($goods));
}
//提交审核
if ($op == "check_goods") {
    $id = $_GPC["id"];
    $xieyi_img = $_GPC["xieyi_img"];
    $res = pdo_update("tg_supply_goods", array('status' => -1, 'xieyi_img' => $xieyi_img), array("id" => intval($id)));
    $goods = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $id);

    if ($res) {
        $acceptor = pdo_getall('tg_platform_acceptor', array('status' => 1));
        if ($acceptor) {
            $accept = array();
            $shop = pdo_get('tg_platform_supplier', array('uniacid' => $goods['supply_id']));
            $accept['first'] = '供应商商品上架审核进度';
            $accept['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $accept['keyword2'] = '供应商商品上架';
            $accept['keyword3'] = "火蝶云极限单品供应商【" . $shop['name'] . '】商品【' . $goods['name'] . '】上架申请提交成功，等候平台审核';
            $accept['keyword4'] = '审核中';
            $accept['remark'] = '';
            foreach ($acceptor as $item) {
                $accept['openid'] = $item['openid'];
                pdo_insert('tg_service_process', $accept);
            }
        }
    }
    die(json_encode(array("status" => $res)));
}

if ($op == 'yunfei') {
    $id = $_GPC["id"];
    $goods = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = :id", array(":id" => $id));
    $yunfeiids = explode(",", $goods['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id ='{$value}' and status=2");
        }
    }
    die(json_encode(array("dispatch_list" => $dispatch_list)));
}
//批发计算运费
if ($op == 'freight') {
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
//批发订单提交
if ($op == 'shop_submit') {

    $id = intval($_GPC['id']);
    $optionname = trim($_GPC['optionname']);
    $aid = $id * (-1);
    $g_all = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE supply_goodsid = :id', array(':id' => $aid));

    // 极限单品库存判断
    $supply = pdo_fetch("select stock,hasoption,weight from " . tablename('tg_supply_goods') . " where id = '{$id}' ");
    if ($supply['hasoption']) {
        $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $id, ':title' => $optionname));
        $supply['stock'] = $option['stock'];
        $supply['weight'] = $option['weight'];
    }
    if ($supply['stock'] < intval($_GPC['gnum'])) {
        die(json_encode(array('status' => 0, 'message' => '库存不足')));
    }

    $tid = $_GPC['tid'];
    $p = $_GPC['province'];
    $c = $_GPC['city'];
    $d = $_GPC['county'];
    $weight = floatval($_GPC['gnum']) * floatval($supply['weight']);
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


    $goods = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = :id", array(":id" => $id));
    $data = array();
    $data['singleno'] = $singleno = 'S' . date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $data['uniacid'] = $_W['uniacid'];
    $data['supply_goodsid'] = $id;
    $data['supply_id'] = $goods['supply_id'];
    $price = 0.0;
//    $freight = floatval($_GPC['freight']);
    $collect['supply_id'] = $goods['supply_id'];
    $collect['uniacid'] = $_W['uniacid'];
    //$collect['goodsid'] = $id;
    $collect['goodsname'] = $goods['name'];
    $collect['supply_goodsid'] = $id;
    $collect['singleno'] = $data['singleno'];
    $collect['optionname'] = $_GPC['optionname'];
    $collect['num'] = intval($_GPC['gnum']);
    $collect['freight'] = $freight;
    if (!empty($_GPC['optionname'])) {
        $option = pdo_fetch("select * from " . tablename('tg_goods_supply_option') . " where goodsid = '{$data['supply_goodsid']}' and title = '{$_GPC['optionname']}' ");
        $collect['oprice'] = $option['costprice'];
        $collect['price'] = $option['costprice'] * $collect['num'] + $freight;
        $collect['pay_price'] = $option['costprice'] * $collect['num'];
        $price += $option['costprice'] * $collect['num'];
    } else {
        $collect['oprice'] = $goods['mprice'];
        $collect['price'] = $goods['mprice'] * $collect['num'] + $freight;
        $collect['pay_price'] = $goods['mprice'] * $collect['num'];
        $price += $goods['mprice'] * $collect['num'];
    }
    $collect['realname'] = $_GPC['addname'];
    $collect['mobile'] = $_GPC['mobile'];
    $collect['province'] = $_GPC['province'];
    $collect['city'] = $_GPC['city'];
    $collect['county'] = $_GPC['county'];
    $collect['address'] = $_GPC['address'];
    $collect['supply_status'] = 0;

    pdo_insert('tg_supply_collect', $collect);


    $data['gname'] = $goods['name'];
    $data['price'] = $price + $freight;
    $data['createtime'] = TIMESTAMP;
    pdo_insert('tg_supply_order', $data);
    $order_id = pdo_insertid();
    $copy = 0;//不需要拷贝
    if (empty($g_all)) {
        $copy = 1;
    }
    pdo_update('tg_supply_collect', array('supply_orderid' => $order_id), array('singleno' => $singleno));

    die(json_encode(array('status' => 1, 'order_id' => $order_id, 'message' => '下单成功，请申请发货！', 'copy' => $copy, 'money' => $data['price'])));
//    echo "<script>alert(" .$tip .");window.location.href=" .web_url('platform/shop_order' , array('op' => 'order' , 'order_id' => $order_id)) .";</script>";
    exit();
}
//批发成功回调
if ($op == 'success') {
    $id = intval($_GPC['id']);
    $order_out = pdo_fetch("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE supply_orderid = :id", array(":id" => $id));
    $goodsInfo = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = :id", array(":id" => $order_out['supply_goodsid']));
    //极限单品减库存加销量
    if ($goodsInfo['id'] > 0) {
        $go = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $goodsInfo['id']);
        if ($go['stock'] >= $order_out['gnum']) {
            pdo_update('tg_supply_goods', array('stock' => $go['stock'] - $order_out['num'], 'salenum' => $go['salenum'] + $order_out['num']), array('id' => $goodsInfo['supply_goodsid']));
        }
        if (!empty($go['optionname'])) {
            $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $goodsInfo['supply_goodsid'], ':title' => $order_out['optionname']));
            pdo_update('tg_goods_supply_option', array('stock' => $option['stock'] - $order_out['num']), array('goodsid' => $goodsInfo['supply_goodsid'], 'title' => $order_out['optionname']));
        }
    }
    die(json_encode(array('status' => 1)));
}
//加载打印页
if ($op == 'print1') {
    include wl_template('platform/print1');
    exit;
}
