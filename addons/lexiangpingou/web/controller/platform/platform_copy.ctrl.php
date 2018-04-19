<?php
global $_W, $_GPC;
$_W['page']['title'] = "极限单品 - 商品管理";
$op = $_GPC["op"];
if (!$op || empty($op)) {
    $op = "display";
}
if ($op == "display") {
    $id = $_GPC["id"];
    $copy = intval($_GPC["copy"]);

    $uniacid = $_W["uniacid"];
    $merchant_id = $_W["user"]["merchant_id"];
    //查询商品
    $goods = pdo_fetch("SELECT * FROM " . tablename("tg_supply_goods") . " WHERE id = :id", array(":id" => $id));
    //获取到商品之后获取商品的详情 然后插入到用户的商品表
    $data["gname"] = $goods["name"];//商品名称
    $data["oprice"] = $goods["oprice"];//单买价钱
    $data["mprice"] = $goods["oprice"];//市场价
    $data["gprice"] = $goods["oprice"];//团购价
    $data["groupnum"] = 10;//起团人数
    $data["endtime"] = 1;//组团限时
    $data["hasoption"] = $goods["hasoption"];//是否具有规格
    $data["type"] = 5;
    $data["selltype"] = 1;
    $data["isshowsend"] = 0;
    $data["unit"] = $goods["unit"];
    $data["gdesc"] = $goods["desc"];//描述
    $data["isshow"] = 2;
    $data["shenhe"] = 0;
    $data["salenum"] = 0;
    $data["createtime"] = mktime();
    $data["uniacid"] = $_W["uniacid"];
    $data["selltype"] = 1;
    $data["commission"] = 0;
    $data["merchantid"] = $_W["user"]["merchant_id"];
    $data["goodsdesc"] = $goods["desc"];
    $data["openid"] = "";
    $data["gimg"] = $goods["gimg"];
    $data["gnum"] = $goods["stock"];
    $data["is_discount"] = 0;

    $data["share_image"] = $goods["share_image"];
    $data["share_title"] = $goods["share_title"];
    $data["share_desc"] = $goods["share_desc"];
    $data["goodsdesc"] = $goods["goodsdesc"];
    $data["indexdesc"] = $goods["indexdesc"];
    $data["gdesc"] = $goods["gdesc"];
    $data["index_video"] = $goods["index_video"];
    $data["is_public"] = 0;
    $data["is_applet"] = 0;
    if (!$copy) {
        $data["supply_goodsid"] = $goods["id"];
        $data["isfree"] = $goods["isfree"];
        if ($data["isfree"] == 0) {
            $data["yunfei_id"] = $goods["yunfei_id"];
            $data["deliverytype"] = 4;
        }
    } else {
        $data["supply_goodsid"] = -$goods["id"];
    }

    $res_in1 = pdo_insert("tg_goods", $data);
    $sid = pdo_insertid();
    $img = unserialize($goods['img']);

    foreach ($img as $item) {
        $dd['g_id'] = $sid;
        $dd['thumb'] = $item;
        $dd['uniacid'] = $_W['uniacid'];
        pdo_insert('tg_goods_atlas', $dd);
        unset($dd);
    }
    $params = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_goods_param') . " WHERE goodsid = '{$id}' ");
    foreach ($params as $param) {
        $dat['goodsid'] = $sid;
        $dat['title'] = $param['title'];
        $dat['value'] = $param['value'];
        $dat['displayorder'] = $param['displayorder'];
        pdo_insert('tg_goods_param', $dat);
        unset($dat);
    }
    if (intval($goods["hasoption"]) == 1) {
        //查询规格
        $spec = pdo_fetchall("SELECT * FROM " . tablename("tg_supply_spec") . " WHERE goodsid = :id ORDER BY displayorder ASC ", array(":id" => $id));
        $ai = array();
        $au = array();

        foreach ($spec as $v1) {

            $ops["goodsid"] = $sid;
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
            $spec_items = pdo_fetchall("SELECT * FROM " . tablename("tg_supply_spec_item") . " WHERE specid = :id ORDER BY displayorder ASC ", array(":id" => $v1['id']));
            foreach ($spec_items as $v2) {
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

        }

        $option = pdo_fetchall("SELECT * FROM " . tablename("tg_goods_supply_option") . " WHERE goodsid = :id ORDER BY id ASC ", array(":id" => $id));

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
                "goodsid" => $sid,
                "specs" => $newids
            );

            pdo_insert("tg_goods_option", $aa);
        }

    }

    if ($copy == 0) {
        $message = '采纳';
    } else {
        $message = '批发';
    }

    $supplier = pdo_fetch("select openid from " .tablename('tg_platform_supplier') ." where uniacid = " .$goods['supply_id']);
    $dat['openid'] = $supplier['openid'];
    if ($dat['openid']) {
        $dat['first'] = '商品购买情况';
        $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        $dat['keyword2'] = '商品批发/采纳';
        $dat['keyword3'] = '火蝶云极限单品商品已被' . $message;
        $dat['keyword4'] = '商品' . $message;
        $dat['remark'] = '';
        pdo_insert('tg_service_process', $dat);
    }

    die(json_encode(array('status' => $sid)));
}