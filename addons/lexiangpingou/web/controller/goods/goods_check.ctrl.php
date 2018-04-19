<?php
defined('IN_IA') or exit('Access Denied');
$ops = array('sendnotice', 'display', 'post', 'single_op', 'batch', 'setgoodsproperty', 'numx', 'fenleiTwo', 'category_childid' , 'detail');
$op = in_array($op, $ops) ? $op : 'display';

wl_load()->model('goods');

if(!pdo_fieldexists('tg_goods', 'reason')) {
    pdo_query("ALTER TABLE ".tablename('tg_goods')." ADD `reason` varchar(255) COMMENT '强制下架原因';");
}

$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid=0  ORDER BY id DESC");
$functions = pdo_fetchall("select * from " . tablename('tg_function') . " where type=1 and tuan=0");
$wechats = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid=" . $_W['uniacid']);
$isshop = 0;
foreach ($functions as $key => $value) {
    $fundetail = pdo_fetch("select * from " . tablename('tg_function_detail') . " where uniacid = '{$_W['uniacid']}' and functionid='{$value['id']}' ");

    if ((!empty($fundetail) && $fundetail['endtime'] > time()) || ($wechats['vip'] == 1 && $wechats['endtime'] > time()) || $wechats['ordernum'] > 0) {
        $isshop = 1;
        break;
    }
}
$childs = array();
wl_load()->model('functions');
foreach ($category as $key => $value) {
    $category_childs = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid={$value['id']} and enabled=1 ORDER BY displayorder DESC");
    $childs[$value['id']] = $category_childs;
}

if ($op == 'display') {
    if (checksubmit()) {
        $display = $_GPC['display'];
        $ids = $_GPC['ids'];
        for ($i = 0; $i < count($ids); $i++) {
            pdo_update("tg_goods", array('displayorder' => $display[$i]), array('id' => $ids[$i]));
        }
        $tip = '商品排序保存成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('goods/goods') . "';</script>";
        exit;

    }
    $status = intval($_GPC['status']);

    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $condition = " WHERE 1 and uniacid = {$_W['uniacid']} ";

    $condition .= " and  merchantid > 0 ";

    if ($status == 1) {
        $condition .= " and shenhe = 1 ";
    } elseif ($status == 2) {
        $condition .= " and shenhe = 2 ";
    } elseif ($status == 3) {
        $condition .= " and shenhe = 0 ";
    }

//    $merchant_id = $_GPC['merchantid'];
    $keyword = $_GPC['keyword'];
//    file_put_contents(TG_DATA."aa.log", var_export(array('id' => $merchant_id,'name'=>$keyword), true).PHP_EOL, FILE_APPEND);

    if (!empty($keyword)){
        $condition .= " and gname like '%{$keyword}%' ";
    }
    $goodses = pdo_fetchall("select * from " . tablename('tg_goods') . $condition . " limit " . (($page - 1) * $size . "," . $size));
    $total = pdo_fetchcolumn("select count(*) from " . tablename('tg_goods') . $condition);
    $pager = pagination($total, $page, $size);

    foreach ($goodses as $key => &$value) {
        $m = $value['merchantid'];
        if ($m == 0) {
            $value['merchant_name'] = $_W['account']['name'];
        } else {
            $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
            $value['merchant_name'] = $name['name'];
        }
        $category = pdo_fetch("SELECT name FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and id={$value['fk_typeid']}");

        $value['typename'] = $category['name'];
    }
    include wl_template('goods/goods_check');
    exit;
} elseif ($op == 'sendnotice') {
    wl_load()->func('message');
    $id = intval($_GPC['id']);
    $type = intval($_GPC['type']);
    if ($type == 1) {
        $title = '上架通知';
        $title1 = '上架';
    }
    if ($type == 2) {
        $title = '降价通知';
        $title1 = '降价';
    }
    $goods = pdo_fetch("select gname,gprice,oprice from " . tablename('tg_goods') . " where id='{$id}'");
    $goodsname = $goods['gname'];
    $time = date("Y-m-d H:i:s ", time());
    $noticelist = pdo_fetchall("select * from " . tablename('tg_notice') . " where type='{$type}' and g_id='{$id}' and uniacid='{$_W['uniacid']}'");

    $url = app_url('goods/detail', array('id' => $id));
    foreach ($noticelist as $key => &$value) {
        $openid = $value['openid'];
        $mem = pdo_fetch("select nickname from " . tablename('tg_member') . " where from_user='{$openid}'");
        $typename = $goodsname . $title;
        $message = '尊敬的' . $mem['nickname'] . '您好，您关注的产品【' . $goodsname . '】已' . $title1 . '，当前拼团价格为￥' . $goods['gprice'] . '，单买价格为￥' . $goods['oprice'];
        pro_change($openid, $title, $typename, $goodsname, $time, $message, $url);
    }
    $tip = $title . '发送完毕';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('goods/goods') . "';</script>";
    exit;

} elseif ($op == 'batch') {
    $funcop = $_GPC['funcop'];
    $goods_ids = $_GPC['goods_ids'];
    foreach ($goods_ids as $key => $id) {
        if ($funcop == 'on_shelves') {
            if (goods_update_by_params(array('shenhe' => 0), array('id' => $id))) {
            } else {
            }
        }
        if ($funcop == 'delete') {
            if (goods_update_by_params(array('shenhe' => 2), array('id' => $id))) {
            } else {
            }
        }
        if ($funcop == 'shenhe') {
            if (goods_update_by_params(array('shenhe' => 0), array('id' => $id))) {
            } else {
            }
        }
    }
    if ($funcop == 'shenhe') {
        die(json_encode(array("errno" => 0, 'message' => '审核成功')));
    }
    if ($funcop == 'on_shelves') {
        die(json_encode(array("errno" => 0, 'message' => '上架成功')));
    }
    if ($funcop == 'delete') {
        die(json_encode(array("errno" => 0, 'message' => '强制下架成功')));
    }

} elseif ($op == 'single_op') {
    $funcop = $_GPC['funcop'];
    $id = $_GPC['id'];
    if ($funcop == 'on_shelves') {
        if (goods_update_by_params(array('shenhe' => 0), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '上架成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '上架失败')));
        }
    }
    if ($funcop == 'delete') {
        $reason = $_GPC['text'];
//        die(json_encode($reason));
        if (goods_update_by_params(array('shenhe' => 2 , 'reason' => $reason), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '强制下架成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '强制下架失败')));
        }
    }
    if ($funcop == 'shenhe') {
        if (goods_update_by_params(array('shenhe' => 0), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '审核成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '审核失败')));
        }
    }

} elseif ($op == 'setgoodsproperty') {
    $id = intval($_GPC['id']);
    $type = $_GPC['type'];
    $data = intval($_GPC['data']);
    if (in_array($type, array('new', 'hot', 'recommand', 'discount'))) {
        $data = ($data == 1 ? '0' : '1');
        pdo_update("tg_goods", array("is" . $type => $data), array("id" => $id, "uniacid" => $_W['uniacid']));
        die(json_encode(array("result" => 1, "data" => $data)));
    }
    if (in_array($type, array('isshow'))) {
        $data = ($data == 1 ? '0' : '1');
        pdo_update("tg_goods", array($type => $data), array("id" => $id, "uniacid" => $_W['uniacid']));
        die(json_encode(array("result" => 1, "data" => $data)));
    }
    if ($type == 'isshow2') {
        $data = ($data == 1 ? '3' : '1');
        pdo_update("tg_goods", array("isshow" => $data), array("id" => $id, "uniacid" => $_W['uniacid']));
        die(json_encode(array("result" => 1, "data" => $data)));
    }
    die(json_encode(array("result" => 0)));
} elseif ($op == 'numx') {
    $orid = $_GPC['oridd'];
    $paixv = $_GPC['paixvv'];
    pdo_update("tg_goods", array('displayorder' => $paixv), array('id' => $orid));
    die(json_encode($paixv));
} elseif ($op == 'fenleiTwo') {
    $id = $_GPC['id'];
    $list = pdo_fetchall("SELECT * FROM" . tablename('tg_category') . "WHERE parentid =$id ");
    foreach ($list as &$adv) {
        $adv['thumb'] = tomedia($adv['thumb']);
    }
    die(json_encode($list));
}elseif($op == 'detail'){
    $id = $_GPC["id"];
    $goods = pdo_fetch("SELECT * FROM " . tablename('tg_goods') . " WHERE id = :id", array(":id" => $id));
    $goods['gimg'] = tomedia($goods['gimg']);
    $goods['share_image'] = tomedia($goods['share_image']);
//    $yunfeiids = explode(",", $goods['yunfei_id']);
//    foreach ($yunfeiids as $key => $value) {
//        if ($value) {
//            $dispatch_list[$key] = pdo_fetch("select id,name from " . tablename('tg_delivery_template') . " where id = '{$value}' ");
//        }
//    }
    $goods['dispatch_list'] = $dispatch_list;
    unset($dispatch_list);
    $params = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_param') . " WHERE goodsid = '{$id}' ");
    $goods['params'] = $params;
    if ($goods["hasoption"] == 1) {
        //查询分类和分类信息

        $allspecs = pdo_fetchall("SELECT * FROM " . tablename('tg_spec') . " WHERE  goodsid = :goodsid ORDER BY displayorder ASC", array("goodsid" => $id));
        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("SELECT * FROM " . tablename('tg_spec_item') . " WHERE specid = :specid ORDER BY displayorder ASC", array(":specid" => $s['id']));
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
    die(json_encode($goods));
}
exit();