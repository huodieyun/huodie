<?php
// 引入商品类
wl_load()->classs('webGoods');
wl_load()->classs('qrcode');
defined('IN_IA') or exit('Access Denied');
header('content-type:text/html;charset=utf-8');
load()->func("tpl");


$account_vip = pdo_get('account_vip' , array('uniacid' => $_W['uniacid']));

$ops = array('sendnotice', 'display', 'post', 'single_op', 'batch', 'setgoodsproperty', 'numx', 'fenleiTwo', 'category_childid', 'copy', 'parent', 'child', 'detail_info', 'qrcode');
$op_names = array('商品列表', '新增/修改商品', '上下架/售罄/删除/彻底删除', '批量设置', '设置商品属性');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'goods', 'goods', $ops[$key], '商品', '商品管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'display';

wl_load()->model('goods');
wl_load()->model('functions');
$coupon = pdo_fetchall("select * from ".tablename("tg_coupon_template")." where uniacid = :uniacid and enable = 1 and end_time > :end_time",array(":uniacid"=>$_W["uniacid"],":end_time"=>time()));

$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$con = "";
if ($_W['user']['merchant_id'] > 0) {

    $con .= " and  activities_type=0 ";
}
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid = 0 {$con} ORDER BY id DESC");

//二维码
if($op == 'qrcode'){
    $url = $_GPC['url'];
    $goodsid = $_GPC['goodsid'];
    $qr = new creat_qrcode();
    $qrcode = $qr->createGoodsQrcode($url,$goodsid);
    die(json_encode($qrcode));
}

if ($op == 'parent'){

    die(json_encode(array('category' => $category)));

}
if ($op == 'child'){

    $parentid =$_GPC['id'];
    $childs = pdo_fetchall("SELECT id,name FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid = {$parentid} and enabled = 1 ORDER BY displayorder DESC");

    die(json_encode(array('child' => $childs)));

}

//var_dump($category);
$functions = pdo_fetchall("select * from " . tablename('tg_function') . " where type = 1 and tuan = 0");
$wechats = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = " . $_W['uniacid']);
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

if ($op == 'display') {
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
    $functionlist = pdo_fetchall("select * from " . tablename('tg_function') . " where type = 2");
    foreach ($functionlist as $key => $value) {
        $functionlist[$key]['status'] = 0;
        $functionlist[$key]['endtime'] = 0;
        $function_detail = pdo_fetch("select * from " . tablename('tg_function_detail') . " where functionid=:functionid and  uniacid=:uniacid", array(':functionid' => $value['id'], ':uniacid' => $_W['uniacid']));

        if (!empty($function_detail) && $function_detail['endtime'] > time()) {
            $functionlist[$key]['status'] = 1;
            $functionlist[$key]['endtime'] = $function_detail['endtime'];
        }
        if (($wechats['vip'] == 1 && $wechats['endtime'] > time()) || $wechats['ordernum'] > 0) {
            $functionlist[$key]['status'] = 1;
            $functionlist[$key]['endtime'] = $wechats['endtime'];
            if ($wechats['ordernum'] > 0) {
                $functionlist[$key]['endtime'] = 1606492800;
            }
        }
        if ($wechats['tpl'] == $value['id']) {
            $functionlist[$key]['status'] = 2;
        }
    }
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
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $condition = " WHERE 1 and uniacid = {$_W['uniacid']} ";

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
    $condition .= " and selltype != 10 ";
    $condition.=' and supply_goodsid=0';
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
        $condition .= " and isshow in( " . $_GPC['status'] . " ) ";
    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " and  gname like '%{$_GPC['keyword']}%'";
    }


    $sqlTotal = pdo_sql_select_count_from('tg_goods') . $condition;
    $sqlData = pdo_sql_select_all_from('tg_goods') . $condition . ' ORDER BY ' . $sortname . ' ' . $sortasc . " , merchantid ";
    $goodses = pdo_pagination($sqlTotal, $sqlData, $params, 'id', $total, $page, $size);
    $pager = pagination($total, $page, $size);
//    $total = pdo_fetchcolumn("select COUNT(*) from " .tablename('tg_goods') .$condition . ' ORDER BY ' .$sortname. ' ' .$sortasc );
//    $goodses = pdo_fetchall("select * from " .tablename('tg_goods') .$condition . ' ORDER BY ' .$sortname. ' ' .$sortasc ." limit " .($page - 1) * $size ." , " .$size);
//	$pager = pagination($total, $page, $size);

    foreach ($goodses as $key => &$value) {
        $m = $value['merchantid'];
//        die(json_encode(array('m' => $m)));
        if ($m == 0) {
            $value['merchant_name'] = $_W['account']['name'];
        } else {
            $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
            $value['merchant_name'] = $name['name'];
        }
//        die(json_encode(array('m' => $value['merchant_name'])));
        $category = pdo_fetch("SELECT name FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and id={$value['fk_typeid']}");

        $value['typename'] = $category['name'];
    }
//    die(json_encode($goodses));
    $con = " where status=1 AND is_advert_img=1 ";
    $supply_goods = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods') . $con . ' ORDER BY displayorder desc');
    $shop_vip = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = '{$_W['uniacid']}'");
    include wl_template('goods/goods_display');
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
} elseif ($op == 'post') {
    $id = intval($_GPC['id']);
    $funlist = pdo_fetchall("select * from " . tablename('tg_function') . " where type=1 and tuan=0");

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
    $couponlist = pdo_fetchall("select * from " . tablename('tg_coupon_template') . " where uniacid=" . $_W['uniacid'] . " and end_time>" . TIMESTAMP . " and start_time<" . TIMESTAMP . " and enable=1");
    //$dispatch_list = pdo_fetchall("select * from".tablename('tg_delivery_template'));
    $store_list = pdo_fetchall("select storename,id from " . tablename("tg_store") . " WHERE status=1 and uniacid = {$_W['uniacid']}  order by id desc");
    $thisgoods = pdo_fetch("select hexiao_id,yunfei_id,selltype,has_store_stock from " . tablename("tg_goods") . " WHERE id='{$id}'");

    // INSERT
    $storesids = explode(",", $thisgoods['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select storename,id from" . tablename('tg_store') . "where id = '{$value}' ");
            $stores[$key]['gnum'] = pdo_fetchcolumn("select stock from" . tablename('tg_goods_store_stock') . "where storeid = '{$value}'  and goodsid={$id} ");
        }
    }
    unset($value);
    $yunfeiids = explode(",", $thisgoods['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select id,name from" . tablename('tg_delivery_template') . "where id ='{$value}' ");
        }
    }
    unset($value);
    $piclist = array();
    $goodsClass = new Goods();
    // 编辑商品
    if (!empty($id)) {
        // 获取商品服务
        $goodsServies = $goodsClass->getServies($id);
        $sql = 'SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id ';
        $paramse = array(':id' => $id);
        $goods = pdo_fetch($sql, $paramse);
        $goods['gdesc']=str_replace("http://www.lexiangpingou.cn/attachment/","https://res.lexiangpingou.cn/",$goods['gdesc']);
        $cates = pdo_fetch("SELECT * FROM " . tablename('tg_category') . " WHERE id = '{$goods['category_childid']}' ");

        if (!empty($goods['openid'])) {
            $saler = pdo_fetch('select * from ' . tablename('tg_member') . ' where openid=:openid', array(':openid' => $goods['openid']));
        }
        //阶梯团
        $param_level = unserialize($goods['group_level']);
        $wholesale_level = unserialize($goods['wholesale_level']);

        //获取当前图集
        $listt = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_atlas') . "WHERE g_id = '{$id}' ");

        if (is_array($listt)) {
            foreach ($listt as $p) {
                $piclist[] = $p['thumb'];
            }
            unset($p);
            unset($listt);
        }
        $allspecs = pdo_fetchall("select * from " . tablename('tg_spec') . " where goodsid=:id order by displayorder asc", array(":id" => $id));
        foreach ($allspecs as &$s) {
            $s['items'] = pdo_fetchall("select * from " . tablename('tg_spec_item') . " where specid=:specid order by displayorder asc", array(":specid" => $s['id']));
        }
        unset($s);
        //处理规格项
        $html = "";
        $options = pdo_fetchall("select * from " . tablename('tg_goods_option') . " where goodsid=:id order by id asc", array(':id' => $id));
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
            $a = '<a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_stock\');" ></a>';
            if($goods['supply_goodsid'] > 0){
                $readonly = "readonly";
                $a = '<a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" ></a>';
            }
            $html .= '<th class="danger" style="width:130px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">库存</div><div class="input-group"><input type="text" '.$readonly.' class="form-control option_stock_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_stock\');" ></a></span></div></div></th>';
            $html .= '<th class="success" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">拼团价格</div><div class="input-group"><input type="text" class="form-control option_marketprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_marketprice\');"></a></span></div></div></th>';
            $html .= '<th class="warning" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">单买价格</div><div class="input-group"><input type="text" class="form-control option_productprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_productprice\');"></a></span></div></div></th>';
            $html .= '<th class="danger" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">市场价格</div><div class="input-group"><input type="text" class="form-control option_mprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_mprice\');"></a></span></div></div></th>';
            $html .= '<th class="danger" style="width:150px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">重量（克）</div><div class="input-group"><input type="text" class="form-control option_weight_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_weight\');"></a></span></div></div></th>';
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
                $val = array("id" => "", "title" => "", "stock" => "", "mprice" => "", "productprice" => "", "marketprice" => "", "weight" => "");
                foreach ($options as $o) {
                    if ($ids === $o['specs']) {
                        $val = array(
                            "id" => $o['id'],
                            "title" => $o['title'],
                            "stock" => $o['stock'],
                            "mprice" => $o['mprice'],
                            "productprice" => $o['productprice'],
                            "marketprice" => $o['marketprice'],
                            "weight" => $o['weight']
                        );
                        break;
                    }
                }

                $hh .= '<td class="danger">';
                $hh .='<input name="option_stock_' . $ids . '[]" type="text" '.$readonly .' class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/>';
                $hh .= '<input name="option_id_' . $ids . '[]"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                $hh .= '<input name="option_ids[]"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '<input name="option_title_' . $ids . '[]"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';
                $hh .= '</td>';
//                $hh .= '<td class="success"><input name="option_stock_' . $ids . '[]" type="text" readonly class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/></td>';
                $hh .= '<td class="success"><input name="option_marketprice_' . $ids . '[]" type="text" class="form-control option_marketprice option_marketprice_' . $ids . '" value="' . $val['marketprice'] . '"/></td>';
                $hh .= '<td class="warning"><input name="option_productprice_' . $ids . '[]" type="text" class="form-control option_productprice option_productprice_' . $ids . '" " value="' . $val['productprice'] . '"/></td>';
                $hh .= '<td class="danger"><input name="option_mprice_' . $ids . '[]" type="text" class="form-control option_mprice option_mprice_' . $ids . '" " value="' . $val['mprice'] . '"/></td>';
                $hh .= '<td class="danger"><input name="option_weight_' . $ids . '[]" type="text" class="form-control option_weight option_weight_' . $ids . '" " value="' . $val['weight'] . '"/></td>';
                $hh .= '</tr>';
            }
            $html .= $hh;
            $html .= "</table>";
        }


        //处理门店规格库存
        $store_html = "";
        $store_stock = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_store_stock') . " WHERE goodsid=:id ORDER BY id ASC", array(':id' => $id));
        //排序好的specs
        $specs = array();
        //找出数据库存储的排列顺序
        if (count($store_stock) > 0) {
            $specitemids = explode("_", $store_stock[0]['specs']);
            foreach ($specitemids as $itemid) {
                foreach ($allspecs as $ss) {
                    $items = $ss['items'];
                    foreach ($items as $it) {
                        if ($it['id'] == $itemid) {
                            $ss['specid'] = $ss['id'];
                            $specs[] = $ss;

                            break;
                        }
                    }

                }
            }
            unset($ss);
            unset($itemid);
//            foreach($store_list as $key=>$value){
//                $nvalue['id'] = $value['id'];
//                $nvalue['title'] = $value['storename'];
//
//                $nvalue['items'] = $specs;
//                $nvalue['specid'] = $value['id'];
//                $nspecs[] = $nvalue;
//            }
            if($thisgoods['selltype'] == 0){
                array_unshift($specs,storeJoinSpecs($store_list));
            }else{
                array_unshift($specs,storeJoinSpecs($stores));
            }


            $store_html = '';
            $store_html .='<table class="table table-bordered table-condensed"><thead><tr class="active">';
            $len = count($specs);
            $newlen = 1; //多少种组合
            $h = array(); //显示表格二维数组
            $rowspans = array(); //每个列的rowspan
            for ($i = 0; $i < $len; $i++) {
                //表头
                $store_html .= "<th style='width:80px;'>" . $specs[$i]['title'] . "</th>";
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
            $a = '<a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_stock\');" ></a>';
            if($goods['supply_goodsid'] > 0){
                $readonly = "readonly";
                $a = '<a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" ></a>';
            }
            $store_html .='<th class="info" style="width:130px;"><div class=""><div style="padding-bottom:10px;text-align:center;font-size:16px;">库存</div><div class="input-group"><input type="text" class="form-control store_stock_all"VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-hand-o-down" title="批量设置" onclick="setCol(\'option_stock\');" ></a></span></div></div></th>';
            $store_html .= '</tr></thead>';
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

                foreach ($store_stock as $o) {
                    if ($ids === $o['storeid'].'_'.$o['specs']) {
                        $val = array(
                            "id" => $o['id'],
                            "stock" => $o['stock'],
                        );
                        break;
                    }
                }

                $hh .= '<td class="danger">';
                $hh .= '<input name="store_stock_id_' . $ids . '[]" type="text" class="form-control option_weight option_weight_' . $ids . '" " value="' . $val['stock'] . '"/>';
                $hh .= '<input name="store_id[]"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '</td>';
//                $hh .= '<td class="success"><input name="option_stock_' . $ids . '[]" type="text" readonly class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/></td>';
                $hh .= '</tr>';
            }
            $store_html .= $hh;
            $store_html .= "</table>";
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
        // 获取商品服务
        $goodsServies = $goodsClass->getServies($id);
    }
    // 提交校验方法
    if (checksubmit()) {
        // 处理商品服务
        $data = $_GPC['goods'];
        $store = $_GPC['store'];

        if ($data['com_type'] == 0) {
            $data['show_com'] = 0;
            $data['commission'] = 0;
            $data['commissiontype'] = 0;
            $data['is_commander_fee'] = 0;
        }
        if (($data['selltype'] == 0 || $data['selltype'] == 4 || $data['selltype'] == 7) && $data['com_type'] == 2) {
            $data['com_type'] = 0;
            $data['show_com'] = 0;
            $data['commissiontype'] = 0;
            $data['commission'] = 0;
            $data['is_commander_fee'] = 0;
        }
        if ($data['selltype'] == 2) {
            $data['is_commander_fee'] = 0;
        }

        // 判断是否已达发布上限
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
        // 编辑删除
        if (intval($id) > 0) {
            // 编辑删除规格
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
        // 添加自提时间
        if ($data['is_self_time'] == 1) {
            $selfTime = $_GPC['selfTime'];
            $data['self_time_start'] = strtotime($selfTime['start']);
            $data['self_time_end'] = strtotime($selfTime['end']);
        }
        if ($data['selltype'] == 3 || $data['selltype'] == 4) {
            $data['group_level_status'] = 2;
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

        //秒杀
        if (!$data['selltype'] && !$spike) {
            unset($data['hexiao_id']);
            unset($data['yunfei_id']);
        }

        if ($goods['supply_goodsid'] > 0) {
            unset($data['yunfei_id']);
        }
        $data['gdetaile'] = htmlspecialchars_decode($data['gdetaile']);
        $data['endtime'] = $_GPC['endtime'];
        $data['openid'] = $_GPC['openid'];
        //批发商品阶梯价
        $wholesale_level = array();
        if($data['wholesale_staircase'] == 1){
            $wholesale_level = $_POST['wholesale_level'];
        }
        $wholesale_level = serialize($wholesale_level);
        $data['wholesale_level'] = $wholesale_level;
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
        // TODO 改变团状态后 将数量团关闭
        if ($goods['is_amount'] == 1 && $data['selltype'] != 4 && $data['selltype'] != 7){
            $data['is_amount'] = 0;
        }

        //小程序接龙
        if ($data['is_applet'] == 1) {
            if ($data['jielong'] == 1) {
                $data['is_public'] = 0;
                $data['is_app'] = 0;
                $data['one_group'] = 1;
            }
        } else {
            $data['jielong'] = 0;
        }

        if ($data['selltype'] != 0 && $data['selltype'] != 1 && $data['selltype'] != 4) {
            $data['is_app'] = 0;
            $data['is_applet'] = 0;
        }
        // 添加商品
        if (empty($goods)) {
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $data['merchantid'] = $_W['user']['merchant_id'];
            $data["index_video"] = $_GPC["index_video"];
            $data["is_sendcoupon"] = $_GPC["send"];
            $data["coupon_id"] = $_GPC["coupon_id"];
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
            oplog($_W['user']['username'], "添加商品", web_url('goods/goods/post'), $oplogdata);
            if ($goimages) {
                $goimages = array_reverse($goimages);
                foreach ($goimages as $key => $value) {
                    $data1 = array('thumb' => $goimages[$key], 'g_id' => $id);
                    pdo_insert('tg_goods_atlas', $data1);
                }
            }
        } else {
            if ($data['selltype'] == 1) {
                $data['group_level_status'] = 1;
            }
            if ($data['selltype'] == 2) {
                $data['one_group'] = 0;
                $data['group_level_status'] = 1;
            }
            if ($data['selltype'] == 3) {
                $data['one_group'] = 0;
                $data['hasoption'] = 0;
                $data['one_limit'] = 0;
            }
            if ($data['selltype'] == 4) {
                $data['hasoption'] = 0;
                $data['is_discount'] = 2;
            }
            if ($data['selltype'] == 6) {
                $data['one_group'] = 0;
                $data['group_level_status'] = 1;
            }
            if ($data['selltype'] == 5) {
                $data['group_level_status'] = 1;
            }
            if ($data['selltype'] == 7) {
                $data['hasoption'] = 0;
                $data['on_success'] = 0;
                $data['one_group'] = 0;
                $data['is_discount'] = 2;
            }

            // if ($goimages) {
                pdo_delete('tg_goods_atlas', array('g_id' => $id));
                $goimages = array_reverse($goimages);
                foreach ($goimages as $key => $value) {
                    $data2 = array('thumb' => $goimages[$key], 'g_id' => $id);
                    pdo_insert('tg_goods_atlas', $data2);
                }
            // }
            $data["index_video"] = $_GPC["index_video"];
            $data["is_sendcoupon"] = $_GPC["send"];
            $data["coupon_id"] = $_GPC["coupon_id"];
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
            $oplog_id = oplog($_W['user']['username'], "更新商品", web_url('goods/goods/post'), $oplogdata);
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
        // 处理商品服务
        $servies = $goodsClass->getServies($id);
        $serverName = $_POST['goods_service_name'];
        $serverContent = $_POST['goods_service_content'];
        if (count($servies) > 0) {
            // 先删除所有
            foreach ($servies as $skey => $svalue) {
                $goodsClass->deleteServer($svalue['id']);
            }
        }
        // 新建服务
        foreach ($serverName as $skey => $svalue) {
            $goodsClass->createServer($id, $serverName[$skey], $serverContent[$skey]);
        }
        if (count($paramids) > 0) {
            pdo_query("delete from " . tablename('tg_goods_param') . " where goodsid=:id and id not in ( " . implode(',', $paramids) . ")", array(':id' => $id));
        } else {
            pdo_query("delete from " . tablename('tg_goods_param') . " where goodsid=:id", array(':id' => $id));
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
                "mprice" => $_GPC['option_mprice_' . $ids][0],
                "stock" => $_GPC['option_stock_' . $ids][0],
                "weight" => $_GPC['option_weight_' . $ids][0],
                "goodsid" => $id,
                "specs" => $newids
            );

            pdo_insert("tg_goods_option", $a);
            $option_id = pdo_insertid();

            $optionids[] = $option_id;
            $option[$k] = $a;
            //临时记录，用于保存多门店规格库存
            $aa['get_id'] = $ids;
            $aa['option_id'] = $option_id;
            $aa['specs'] = $newids;
            $optionidss[] = $aa;
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
            pdo_insert('tg_goods_modify', array('goods_option' => $option, 'goods_param' => $param, 'uid' => $_W['user']['uid'], 'goodsid' => $goods['id'] , 'oplog_id' => $oplog_id));
        }

        if($data['has_store_stock'] ==1){

            //门店规格库存保存
            $store_id = $_GPC['store_id'];

            if(!empty($store_id)){
                $gnum = 0;
                if ($id > 0) {
                    pdo_query("delete from " . tablename('tg_goods_store_stock') . " where uniacid={$_W['uniacid']} and goodsid={$id}");
                }
                pdo_update('tg_goods_option',array('stock'=>0),array('goodsid'=>$id));
                foreach ($store_id as $key=>$value){
                    //获取option_id
                    $store_ids = explode("_", $value);
                    $storeid = $store_ids[0];
                    array_shift($store_ids);
                    $str = implode('_',$store_ids);
                    foreach ($optionidss as $ok=>$ov){
                        if($str == $ov['get_id']){
                            $option_id = $ov['option_id'];
                            $specs = $ov['specs'];
                        }
                    }
                    $stock = $_GPC['store_stock_id_'.$value];
                    $gnum += $stock[0];
                    $option_stock = pdo_fetch("select stock from cm_tg_goods_option where goodsid={$id} and specs='{$specs}'");
                    $option_stock = $option_stock['stock'] + $stock[0];
                    pdo_update('tg_goods_option',array('stock'=>$option_stock),array('goodsid'=>$id,'specs'=>$specs));
                    pdo_insert('tg_goods_store_stock',array('specs'=>$specs,'uniacid'=>$_W['uniacid'],'goodsid'=>$id,'optionid'=>$option_id,'storeid'=>$storeid,'stock'=>$stock[0]));
                }
                pdo_update('tg_goods',array('gnum'=>$gnum),array('id'=>$id));
            }


            $store = $_GPC['store'];

            if(!empty($store)){
                $gnum = 0;
                if ($id > 0) {
                    pdo_query("delete from " . tablename('tg_goods_store_stock') . " where uniacid={$_W['uniacid']} and goodsid={$id}");
                }
                foreach ($store as $key=>$value){
                    $gnum +=$value['gnum'];
                    pdo_insert('tg_goods_store_stock',array('uniacid'=>$_W['uniacid'],'goodsid'=>$id,'storeid'=>$key,'stock'=>$value['gnum']));
                }
                pdo_update('tg_goods',array('gnum'=>$gnum),array('id'=>$id));
            }
        }

        $tip = '商品信息保存成功';
        $url = web_url('goods/goods/display');

        if($goods['supply_goodsid'] > 0){
            $url = web_url('goods/jxdp');
        }

        $isgo = intval($_GPC['isGo']);

        if ($isgo) {
            echo "<script>location.href='" .web_url('goods/goods/detail_info' , array('id' => $id)). "';</script>";
        } else {
            if (!is_null($_GPC['page'])) {
                $url .= "page=".$_GPC['page'];
            }
            echo "<script>alert('" . $tip . "');location.href='" . $url . "';</script>";
        }
        exit;

    }
    include wl_template('goods/goods_post');
    exit;
}elseif ($op == 'detail_info') {
    $id = $_GPC['id'];

    if (checksubmit()) {
        $data = $_GPC['goods'];
        $ret = pdo_update('tg_goods', $data, array('id' => $id));

        $tip = '商品信息保存成功';
        $url=web_url('goods/goods/display');
        echo "<script>alert('" . $tip . "');location.href='" .$url. "';</script>";
        exit;
    }
    $goods = pdo_fetch("select id,goodsdesc from " .tablename('tg_goods') ." where id = " .$id);
    include wl_template('goods/detail_info');
    exit;

} elseif ($op == 'batch') {
    $funcop = $_GPC['funcop'];
    $goods_ids = $_GPC['goods_ids'];
    $success = 0;
    $error = 0;
    $shenhe = 0;
    $shelve = 0;
    foreach ($goods_ids as $key => $id) {
        if ($funcop == 'on_shelves') {
            if ($_W['user']['merchant_id'] > 0) {
                $merchants = pdo_fetch("select goodsnum from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$_W['user']['merchant_id']}'");
                $mnum = $merchants['goodsnum'];
                $gnum = pdo_fetchcolumn("select COUNT(*) from " . tablename('tg_goods') . " where uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and isshow = 1");
                if ($mnum <= $gnum && $mnum > 0) {
                    die(json_encode(array("errno" => 1, 'message' => '由于达到商品发布上限，部分商品上架失败，请下架部分商品后在上架')));
                }
            }
            $goods = pdo_fetch("select shenhe from " . tablename('tg_goods') . " where id = '{$id}'");
            if ($goods['shenhe'] == 1) {
                $shenhe++;
                $error++;
            } elseif ($goods['shenhe'] == 2) {
                $shelve++;
                $error++;
            } elseif (goods_update_by_params(array('isshow' => 1), array('id' => $id))) {
                $success++;
            }
        }
        if ($funcop == 'off_shelves') {
            if (goods_update_by_params(array('isshow' => 2), array('id' => $id))) {
            } else {
            }

        }
        if ($funcop == 'delete') {
            if (goods_update_by_params(array('isshow' => 4), array('id' => $id))) {
            } else {
            }
        }
        if ($funcop == 'shenhe') {
            if (goods_update_by_params(array('shenhe' => 0), array('id' => $id))) {
            } else {
            }
        }
        if ($funcop == 'remove') {
            $delgoods = goods_get_by_params(" id={$id} ");
            if (goods_delete($id)) {
                $oplogdata = serialize($delgoods);
                oplog('admin', "删除商品", web_url('goods/goods/batch'), $oplogdata);
            } else {
            }
        }
    }
    if ($funcop == 'shenhe') {
        die(json_encode(array("errno" => 0, 'message' => '审核成功')));
    }
    if ($funcop == 'on_shelves') {
        $success .= "个";
        if ($error > 0) {
            $success .= "，失败" . $error . "个";
            if ($shenhe > 0) {
                $success .= "，" . $shenhe . "个商品处于待审核中";
            }
            if ($shelve > 0) {
                $success .= "，" . $shelve . "个商品处于强制下架中";
            }
        }
        die(json_encode(array("errno" => 0, 'message' => '上架成功' . $success)));
    }
    if ($funcop == 'off_shelves') {
        die(json_encode(array("errno" => 0, 'message' => '下架成功')));

    }
    if ($funcop == 'delete') {
        die(json_encode(array("errno" => 0, 'message' => '删除成功')));
    }
    if ($funcop == 'remove') {
        die(json_encode(array("errno" => 0, 'message' => '彻底删除成功')));
    }

} elseif ($op == 'single_op') {
    $funcop = $_GPC['funcop'];
    $id = $_GPC['id'];
    if ($funcop == 'on_shelves') {
        if ($_W['user']['merchant_id'] > 0) {
            $merchants = pdo_fetch("select goodsnum from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$_W['user']['merchant_id']}'");
            $mnum = $merchants['goodsnum'];
            $gnum = pdo_fetchcolumn("select COUNT(*) from " . tablename('tg_goods') . " where uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and isshow = 1");
            if ($mnum <= $gnum && $mnum > 0) {
                die(json_encode(array("errno" => 1, 'message' => '已达到商品发布上限，请下架部分商品后在上架此商品')));
//                message('已达到商品发布上限，请下架部分商品后在上架此商品', web_url('goods/goods'), 'error');
            }
        }
        $goods = pdo_fetch("select shenhe from " . tablename('tg_goods') . " where id = '{$id}'");
        if ($goods['shenhe'] == 1) {
            die(json_encode(array("errno" => 1, 'message' => '上架失败，该商品正在审核中！！！')));
        } elseif ($goods['shenhe'] == 2) {
            die(json_encode(array("errno" => 1, 'message' => '上架失败，该商品已被强制下架！！！')));
        } elseif (goods_update_by_params(array('isshow' => 1), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '上架成功')));
        }
    }
    if ($funcop == 'off_shelves') {
        if (goods_update_by_params(array('isshow' => 2), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '下架成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '下架成功')));
        }

    }
    if ($funcop == 'sellout') {
        if (goods_update_by_params(array('isshow' => 3), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '设置售罄成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '设置售罄失败')));
        }
    }
    if ($funcop == 'delete') {
        if (goods_update_by_params(array('isshow' => 4), array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '删除成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '删除失败')));
        }
    }
    if ($funcop == 'remove') {
        $flag = goods_delete($id);
        if ($flag) {
            $delgoods = goods_get_by_params(" id={$id} ");
            $oplogdata = serialize($delgoods);
            oplog('admin', "删除商品", web_url('goods/goods/single_op'), $oplogdata);
            die(json_encode(array("errno" => 0, 'message' => '彻底删除成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '彻底删除失败')));
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
} elseif ($op == 'copy') {
    $id = $_GPC['id'];

    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id = {$id}");
    $atlas = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_atlas') . " WHERE g_id = " . $id);
    if ($goods['is_hexiao'] == 3)
        $goods['is_hexiao'] = 0;
    $goods['id'] = '';
    $goods['displayorder'] = 0;
    $goods['isshow'] = 2;
    $goods['isjudgment'] = 0;
    $goods['createtime'] = TIMESTAMP;
    $goods['gname'] = $goods['gname'] . "-副本";
    if ($_W['user']['merchant_id'] > 0) {
        $goods['shenhe'] = 1;
        $goods['showindex'] = 1;
    }
    $re = pdo_insert("tg_goods", $goods);
    $g_id = pdo_insertid();
    foreach ($atlas as $item) {
        pdo_insert('tg_goods_atlas' , array('g_id' => $g_id , 'thumb' => $item['thumb'] , 'uniacid' => $_W['uniacid']));
        unset($item);
    }
    $params = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_param') . " WHERE goodsid = '{$id}' ");
    foreach ($params as $param) {
        $dat['goodsid'] = $g_id;
        $dat['title'] = $param['title'];
        $dat['value'] = $param['value'];
        $dat['displayorder'] = $param['displayorder'];
        pdo_insert('tg_goods_param', $dat);
        unset($dat);
    }

    if (intval($goods['hasoption'])) {
        //查询规格
        $spec = pdo_fetchall("SELECT * FROM " . tablename("tg_spec") . " WHERE goodsid = :id ORDER BY displayorder ASC ", array(":id" => $id));
        $ai = array();
        $au = array();

        foreach ($spec as $v1) {

            unset($ops);
            $ops["goodsid"] = $g_id;
            $ops["uniacid"] = $_W["uniacid"];
            $ops["title"] = $v1["title"];
            $ops["description"] = $v1["description"];
            $ops["displaytype"] = $v1["displaytype"];
            $ops["displayorder"] = $v1["displayorder"];
            $res_in2 = pdo_insert("tg_spec", $ops);
            $spec_id = pdo_insertid();
            $spid = pdo_insertid();

            $itemids = array();
            //查询分类对应的东西
            $spec_items = pdo_fetchall("SELECT * FROM " . tablename("tg_spec_item") . " WHERE specid = :id ORDER BY displayorder ASC ", array(":id" => $v1['id']));
            foreach ($spec_items as $v2) {
                unset($items);
                $items["uniacid"] = $_W["uniacid"];
                $items["specid"] = $spid;
                $items["title"] = $v2["title"];
                $items["thumb"] = $v2["thumb"];
                $items["show"] = $v2["show"];
                $items["displayorder"] = $v2["displayorder"];
                $res_in3 = pdo_insert("tg_spec_item", $items);
                $items_id = pdo_insertid();
                $itemids[] = $items_id;
                unset($items);
                $ai[] = $items_id;
                $au[] = $v2['id'];
            }

            pdo_update("tg_spec", array("content" => serialize($itemids)), array("id" => $spec_id));
            unset($ops);
            unset($v1);

        }

        $option = pdo_fetchall("SELECT * FROM " . tablename("tg_goods_option") . " WHERE goodsid = :id ORDER BY id ASC ", array(":id" => $id));

        foreach ($option as $val) {
            $idsarr = explode("_", $val['specs']);
            $newids = array();
            foreach ($idsarr as $ida) {
                for ($k = 0; count($au); $k++) {
                    if ($au[$k] == $ida) {
                        $newids[] = $ai[$k];
                        break;
                    }
                }
            }
            $newids = implode("_", $newids);
            $aa = array(
                "title" => $val['title'],
                "productprice" => $val['productprice'],
                "costprice" => $val['costprice'],
                "marketprice" => $val['marketprice'],
                "stock" => $val['stock'],
                "weight" => $val['weight'],
                "goodsid" => $g_id,
                "specs" => $newids
            );
            pdo_insert("tg_goods_option", $aa);
        }
    }
    if ($re > 0)
        die(json_encode(array('errno' => 0)));
    die(json_encode(array('errno' => 1)));
}



function storeJoinSpecs($arr) {
    $spec = array('id'=>111123123,'title'=>'门店');
    $items = array();
    foreach ($arr as $key=>$value){
        $item = array('id'=>$value['id'],'title'=>$value['storename'],'show'=>'1');
        $items[]=$item;
    }
    $spec['items'] = $items;
    return $spec;
}



exit();