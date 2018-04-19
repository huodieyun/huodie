<?php

$_W['page']['title'] = "极限单品 - 商品管理";
$op = $_GPC["op"];
if (!$op){
    $op = "display";
}
if ($op == "display"){
    //查询所有的商品
$id = $_GPC["id"];
if (empty($id)){
    if (checksubmit()){
        $goods = $_GPC["goods"];
        $data["name"] = $goods["gname"];//name
        $data["hasoption"] = $_GPC["hasoption"];//name
        $data["img"] = serialize($_GPC['img']);//name
        $data["unit"] = $goods["unit"];//单位
        $data["stock"] = $goods["gnum"];//库存
        $data["taxrate"] = $goods["taxrate"];//费率
        $data["time"] = time();//上架时间
        $data["salenum"] = $goods["salenum"];//销量
        $data["oprice"] = $goods["oprice"];//售价
        $data["mprice"] = $goods["mprice"];//原价
        $data["weight"] = $goods["weight"];//重量
        $data["gimg"] = $goods["gimg"];
        $data["issharedesc"] = $goods["issharedesc"];
        $data["isfree"] = $goods["isfree"];//是否包邮
        $data["goods_freight"] = $goods["goods_freight"];//邮费
        $data["isshowsend"] = $_GPC["isshowsend"];//显示发货记录;
        $data["isshow"] = $goods["isshow"];//是够显示
        $data["share_image"] = $goods["share_image"];//分享图片
        $daTta["share_title"] = $goods["share_title"];//分享名字
        $data["share_desc"]  = $goods["share_desc"];//分享描述
        $data["goodsdesc"] = $goods["goodsdesc"];//详情简介
        $data["indexdesc"] = $goods["indexdesc"];//首页简介
        $data["gdesc"] = $goods["gdesc"];//商品详情
        $dzta["index_video"] = $goods["index_video"];//首页视频
        pdo_insert("tg_supply_goods",$data);
        $id = pdo_insertid();

        //处理商品规格
        $files = $_FILES;
        $spec_ids = $_POST['spec_id'];//收到id
        $spec_titles = $_POST['spec_title'];//收到规格名字
        $specids = array();
        $len = count($spec_ids);//算出来一共几个id
        $specids = array();
        $spec_items = array();
        //循环id 入库
        for ($k = 0; $k < $len; $k++) {
            $spec_id = "";
            $get_spec_id = $spec_ids[$k];
            $a = array(
                "supply_id" => $_W['uniacid'],
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
            pdo_insert("tg_supply_spec", $a);
            $spec_id = pdo_insertid();//获取id
//            var_dump($spec_id);die();
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
                /*
                if (is_numeric($get_item_id)) {
                    pdo_update("tg_spec_item", $d, array("id" => $get_item_id));
                    $item_id = $get_item_id;
                } else {
                    pdo_insert("tg_spec_item", $d);
                    $item_id = pdo_insertid();
                }*/
                pdo_insert("tg_supply_spec_item", $d);
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
                "marketprice" => $_GPC['option_marketprice_\' . $ids][0],
                "stock" => $_GPC[\'option_stock_' . $ids][0],
                "weight" => $_GPC['option_weight_' . $ids][0],
                "goodsid" => $id,
                "specs" => $newids
            );
            pdo_insert("tg_goods_option", $a);
            $option_id = pdo_insertid();

            $optionids[] = $option_id;
        }
        if ($id > 0) {
            if (count($optionids) > 0) {
                pdo_query("delete from " . tablename('tg_goods_option') . " where goodsid={$id} and id not in ( " . implode(',', $optionids) . ")");
            } else {
                pdo_query("delete from " . tablename('tg_goods_option') . " where goodsid={$id}");
            }
        }

        echo "<script>alert('上传成功,如果通信失败请重试');location.href=location.href;</script>";
    }
}else{
    $id = $_GPC["id"];
    if (empty($id)) {
        $status = array("status" => "error", "data" => "invaild value");
        die(json_encode($status));
    }

    if (checksubmit()){
        //删除所有的分类
        $spec = pdo_fetchall("select * from " . tablename("tg_supply_spec") . " where supply_id = :uniacid and goodsid = :goodsid", array(":uniacid" => $_W["uniacid"], ":goodsid" => $id));
        for ($i = 0; $i < count($spec); $i++) {
            pdo_delete("tg_supply_spec_item", array("specid" => $spec[$i]["id"], "supply_id" => $_W["uniacid"]));
        }
        pdo_delete("tg_supply_spec", array("goodsid" => $id, "supply_id" => $_W["uniacid"]));
        $goods = $_GPC["goods"];
        $data["name"] = $goods["gname"];//name
        $data["hasoption"] = $_GPC["hasoption"];//name
        $data["img"] = serialize($_GPC['img']);//name
        $data["unit"] = $goods["unit"];//单位
        $data["stock"] = $goods["gnum"];//库存
        $data["taxrate"] = $goods["taxrate"];//费率
        $data["time"] = time();//上架时间
        $data["salenum"] = $goods["salenum"];//销量
        $data["oprice"] = $goods["oprice"];//售价
        $data["mprice"] = $goods["mprice"];//原价
        $data["weight"] = $goods["weight"];//重量
        $data["gimg"] = $goods["gimg"];
        $data["issharedesc"] = $goods["issharedesc"];
        $data["isfree"] = $goods["isfree"];//是否包邮
        $data["goods_freight"] = $goods["goods_freight"];//邮费
        $data["isshowsend"] = $_GPC["isshowsend"];//显示发货记录;
        $data["isshow"] = $goods["isshow"];//是够显示
        $data["share_image"] = $goods["share_image"];//分享图片
        $daTta["share_title"] = $goods["share_title"];//分享名字
        $data["share_desc"]  = $goods["share_desc"];//分享描述
        $data["goodsdesc"] = $goods["goodsdesc"];//详情简介
        $data["indexdesc"] = $goods["indexdesc"];//首页简介
        $data["gdesc"] = $goods["gdesc"];//商品详情
        $dzta["index_video"] = $goods["index_video"];//首页视频
        pdo_update("tg_supply_goods",array("id"=>$id),$data);
        //处理商品规格
        $files = $_FILES;
        $spec_ids = $_POST['spec_id'];//收到id
        $spec_titles = $_POST['spec_title'];//收到规格名字
        $specids = array();
        $len = count($spec_ids);//算出来一共几个id
        $specids = array();
        $spec_items = array();
        //循环id 入库
        for ($k = 0; $k < $len; $k++) {
            $spec_id = "";
            $get_spec_id = $spec_ids[$k];
            $a = array(
                "supply_id" => $_W['uniacid'],
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
            pdo_insert("tg_supply_spec", $a);
            $spec_id = pdo_insertid();//获取id
//            var_dump($spec_id);die();
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
                /*
                if (is_numeric($get_item_id)) {
                    pdo_update("tg_spec_item", $d, array("id" => $get_item_id));
                    $item_id = $get_item_id;
                } else {
                    pdo_insert("tg_spec_item", $d);
                    $item_id = pdo_insertid();
                }*/
                pdo_insert("tg_supply_spec_item", $d);
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
                "marketprice" => $_GPC['option_marketprice_\' . $ids][0],
                "stock" => $_GPC[\'option_stock_' . $ids][0],
                "weight" => $_GPC['option_weight_' . $ids][0],
                "goodsid" => $id,
                "specs" => $newids
            );
            pdo_insert("tg_goods_option", $a);
            $option_id = pdo_insertid();

            $optionids[] = $option_id;
        }
        if ($id > 0) {
            if (count($optionids) > 0) {
                pdo_query("delete from " . tablename('tg_goods_option') . " where goodsid={$id} and id not in ( " . implode(',', $optionids) . ")");
            } else {
                pdo_query("delete from " . tablename('tg_goods_option') . " where goodsid={$id}");
            }
        }

        echo "<script>alert('上传成功,如果通信失败请重试');location.href=location.href;</script>";
    }
}

}
include wl_template("platform/platform_goods_list");
