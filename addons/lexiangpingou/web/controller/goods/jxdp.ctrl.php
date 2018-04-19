<?php
defined('IN_IA') or exit('Access Denied');
header('content-type:text/html;charset=utf-8');
load()->func("tpl");
$ops = array('sendnotice', 'display', 'post', 'single_op', 'batch', 'setgoodsproperty', 'numx', 'fenleiTwo', 'category_childid', 'copy' , 'parent' , 'child');
$op_names = array('商品列表', '新增/修改商品', '上下架/售罄/删除/彻底删除', '批量设置', '设置商品属性');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'goods', 'goods', $ops[$key], '商品', '商品管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'display';

if (!pdo_fieldexists('tg_goods', 'spike')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `spike` int(1)  NOT NULL default 0 COMMENT '是否是秒杀';");
}
if (!pdo_fieldexists('tg_goods', 'spike_start')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `spike_start` int(11)  NOT NULL default 0 COMMENT '秒杀开始时间';");
}
if (!pdo_fieldexists('tg_goods', 'spike_end')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `spike_end` int(11)  NOT NULL default 0 COMMENT '秒杀结束时间';");
}
if (!pdo_fieldexists('tg_order', 'discount_num')) {
    pdo_query("ALTER TABLE " . tablename('tg_order') . " ADD `discount_num` decimal(10,2)  NOT NULL default 0 COMMENT '折扣';");
}
if (!pdo_fieldexists('tg_collect', 'discount_num')) {
    pdo_query("ALTER TABLE " . tablename('tg_collect') . " ADD `discount_num` decimal(10,2)  NOT NULL default 0 COMMENT '折扣';");
}
//是否开启团购限时
if (!pdo_fieldexists('tg_goods', 'spikeT')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `spikeT` int(1) NOT NULL default 0 COMMENT '是否开启团购限时';");
}
//添加小程序上架
if (!pdo_fieldexists('tg_goods', 'is_applet')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `is_applet` int(1) NOT NULL default 1 COMMENT '小程序上架';");
}
//添加公众号上架
if (!pdo_fieldexists('tg_goods', 'is_public')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `is_public` int(1) NOT NULL default 1 COMMENT '公众号上架';");
}
//添加阶梯订金团是否开启数量
if (!pdo_fieldexists('tg_goods', 'is_amount')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `is_amount` int(1) NOT NULL default 0 COMMENT '是否开启数量';");
}
pdo_query("
CREATE TABLE IF NOT EXISTS `cm_tg_goods_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `media_url` varchar(150) NOT NULL COMMENT '视频推流地址',
  `goods_id` int(11) NOT NULL COMMENT '商品id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

//商品修改信息
pdo_query("
CREATE TABLE IF NOT EXISTS `cm_tg_goods_modify` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `oplog_id` int(11) NOT NULL COMMENT 'tg_oplog表id',
  `goodsid` int(11) NOT NULL COMMENT '商品id',
  `goods_option` text COMMENT '商品规格',
  `goods_param` text COMMENT '商品属性',
  `uid` text COMMENT '修改人id',
  PRIMARY KEY (`id`),
  KEY `indx_oplog_id` (`oplog_id`),
  KEY `indx_goodsid` (`goodsid`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

wl_load()->model('goods');
wl_load()->model('functions');
//$coupon = pdo_getall("tg_coupon_template",array("uniacid"=>$_W["uniacid"],"end_time"=>" < ".time()));
$coupon = pdo_fetchall("select * from ".tablename("tg_coupon_template")." where uniacid = :uniacid and end_time > :end_time",array(":uniacid"=>$_W["uniacid"],":end_time"=>time()));
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$con = "";
if ($_W['user']['merchant_id'] > 0) {

    $con .= " and  activities_type=0 ";
}
$good_id = $_GPC["id"];
$kj_info = pdo_get("tg_goods_kj",array("g_id"=>$good_id));
$last_price = $kj_info["last_price"];
$category = pdo_fetchall("SELECT * FROM " . tablename('tg_category') . " WHERE uniacid = '{$_W['uniacid']}' and parentid = 0 {$con} ORDER BY id DESC");

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

    $condition .= " and selltype != 10 ";
    $condition.=' and supply_goodsid>0';
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
    if (!pdo_fieldexists('tg_goods', 'index_video')) {
        pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `index_video` varchar(100);");
    }
    if (!pdo_fieldexists('tg_goods', 'detail_video')) {
        pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `detail_video` varchar(100);");
    }
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
    $thisgoods = pdo_fetch("select hexiao_id,yunfei_id from " . tablename("tg_goods") . " WHERE id='{$id}'");
    $storesids = explode(",", $thisgoods['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select storename,id from" . tablename('tg_store') . "where id = '{$value}' ");
        }
    }
    unset($value);
    $yunfeiids = explode(",", $thisgoods['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select id,name from " . tablename('tg_delivery_template') . " where id = '{$value}' ");
        }
    }
    unset($value);
    $piclist = array();
    if (!empty($id)) {

        $sql = 'SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id ';
        $paramse = array(':id' => $id);
        $goods = pdo_fetch($sql, $paramse);
        $cates = pdo_fetch("SELECT * FROM " . tablename('tg_category') . " WHERE id = '{$goods['category_childid']}' ");

        if (!empty($goods['openid'])) {
            $saler = pdo_fetch('select * from ' . tablename('tg_member') . ' where openid=:openid', array(':openid' => $goods['openid']));
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
        $data = $_GPC['goods'];
        if (($data['selltype'] == 0 || $data['selltype'] == 4 || $data['selltype'] == 7) && $data['com_type'] == 2) {
            $data['com_type'] = 0;
            $data['show_com'] = 0;
            $data['commissiontype'] = 0;
        }
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
            $tg_spec_list = pdo_fetchall('select * from ' . tablename('tg_spec') . ' where uniacid=:uniacid and goodsid=:goodsid', array(':uniacid' => $_W['uniacid'], ':goodsid' => $id));
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
//        file_put_contents(TG_DATA."aa.log", var_export(array('data' => $data , 'time' => $time), true).PHP_EOL, FILE_APPEND);
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
            if ($goimages) {
                pdo_delete('tg_goods_atlas', array('g_id' => $id));
                $goimages = array_reverse($goimages);
                foreach ($goimages as $key => $value) {
                    $data2 = array('thumb' => $goimages[$key], 'g_id' => $id);
                    pdo_insert('tg_goods_atlas', $data2);
                }
            }
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
            pdo_insert('tg_goods_modify', array('goods_option' => $option, 'goods_param' => $param, 'uid' => $_W['user']['uid'], 'goodsid' => $goods['id'] , 'oplog_id' => $oplog_id));
        }
        $tip = '商品信息保存成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('goods/goods/display') . "';</script>";
        exit;

    }
    include wl_template('goods/goods_post2');
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
}
//elseif ($op == 'copy') {
//    $id = $_GPC['id'];
//
//    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id={$id}");
//    if ($goods['is_hexiao'] == 3)
//        $goods['is_hexiao'] = 0;
//    unset($goods['id']);
//    unset($goods['g_type']);
//    unset($goods['prize']);
//    $goods['isshow'] = 2;
//    $goods['hasoption'] = 0;
//    $goods['createtime'] = TIMESTAMP;
//    $goods['gname'] = $goods['gname'] . "-副本";
//    if ($_W['user']['merchant_id'] > 0) {
//        $goods['shenhe'] = 1;
//    }
//    if (pdo_insert("tg_goods", $goods))
//        die(json_encode(array('errno' => 0)));
//    die(json_encode(array('errno' => 1)));
//}
elseif ($op == 'copy') {
    $id = $_GPC['id'];
    $copy = intval($_GPC['copy']);

    $goods = pdo_fetch("select * from " . tablename('tg_goods') . " where id = {$id}");
    $atlas = pdo_fetchall("SELECT * FROM " . tablename('tg_goods_atlas') . " WHERE g_id = " . $id);
    if ($goods['is_hexiao'] == 3)
        $goods['is_hexiao'] = 0;
    $goods['id'] = '';
    $goods['displayorder'] = '';
    $goods['isshow'] = 2;
    $goods['createtime'] = TIMESTAMP;
    if ($copy == 1) {
        $goods['gname'] = $goods['gname'] . "-副本";
    } else {
        $res = pdo_get("tg_kj_log",array("goodsid"=>$id));
        if (empty($res)){
            $goods['selltype'] = 10;
            $goods['isshow'] = 1;
        }else{
            die(json_encode(array('errno' => 1)));
        }
    }
    if ($_W['user']['merchant_id'] > 0) {
        $goods['shenhe'] = 1;
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
//            file_put_contents(TG_DATA."aa.log", var_export(array('data' => $newids , 'time' => TIMESTAMP , 'line' => __LINE__), true).PHP_EOL, FILE_APPEND);
            pdo_insert("tg_goods_option", $aa);
        }
//        file_put_contents(TG_DATA."aa.log", var_export(array('data' => $option , 'time' => TIMESTAMP , 'line' => __LINE__), true).PHP_EOL, FILE_APPEND);
    }
    if ($re > 0)
        die(json_encode(array('errno' => 0)));
    die(json_encode(array('errno' => 1)));
}
exit();