<?php
defined('IN_IA') or exit('Access Denied');
load()->func('tpl');
$ops = array('display', 'post');
$op_names = array('采集页面', '采集');
$op = in_array($op, $ops) ? $op : 'display';
if (!pdo_fieldexists('tg_goods', 'copy_url')) {
    pdo_query("ALTER TABLE " . tablename('tg_goods') . " ADD `copy_url` varchar( 255 )  NOT NULL;");
}
if ($op == 'display') {
    include wl_template('goods/taobao_display');
    exit;
} elseif ($op == 'post') {
    $href = $_REQUEST['link'];
    if (empty($href)) {
        message('采集链接不可以为空', web_url('goods/taobao'), 'error');
    }
    $goods_id = 0;
    preg_match('/id\=(\d+)/i', $href, $matches);
    if (isset($matches[1])) {
        $goods_id = $matches[1];
    }
    if (empty($goods_id)) {
        message('未获取到商品id', web_url('goods/taobao'), 'error');
    }
    $url = 'http://hws.m.taobao.com/cache/wdetail/5.0/?id=' . $goods_id;
    load()->func('communication');
    $response = ihttp_get($url);
    if (!(isset($response['content']))) {
        message('未从淘宝获取到商品信息', web_url('goods/taobao'), 'error');
    }
    $content = $response['content'];
    if (strexists($response['content'], 'ERRCODE_QUERY_DETAIL_FAIL')) {
        message('宝贝不存在!', web_url('goods/taobao'), 'error');
    }
    $arr = json_decode($content, true);
    $data = $arr['data'];
    $itemInfoModel = $data['itemInfoModel'];
    $stack = $data['apiStack'][0]['value'];
    $value = json_decode($stack, true);
    $data1 = $value['data'];
    $itemInfoModel1 = $data1['itemInfoModel'];
    $mprice = 0;
    foreach ($itemInfoModel1['priceUnits'] as $p) {
        if ($p['display'] == 1) {
            $mprice = $p['price'];
        }
    }
    if (!$mprice) {
        $mprice = $itemInfoModel1['priceUnits'][0]['price'];
    }
    $url = 'http://hws.m.taobao.com/cache/wdesc/5.0/?id=' . $goods_id;
    $response = ihttp_get($url);
    $response['content'] = preg_replace('/ (?:width)=(\'|").*?\\1/', ' width="100%"', $response['content']);
    $response['content'] = preg_replace('/ (?:height)=(\'|").*?\\1/', ' ', $response['content']);

    preg_match('/tfsContent : \\\'(.*)\\\'/', $response['content'], $html);
    $html = iconv('GBK', 'UTF-8', $html[1]);
     file_put_contents(TG_DATA."采集.log", var_export($itemInfoModel, true).PHP_EOL, FILE_APPEND);
    if ($_W['user']['merchant_id'] > 0){
        $shenhe = 1;
    }else{
        $shenhe = 0;
    }
    $goods = array(
        "uniacid" => $_W['uniacid'],
        "gname" => $itemInfoModel['title'],
        "oprice" => $mprice,
        "gimg" => $itemInfoModel['picsPath'][0],
        "share_image" => $itemInfoModel['picsPath'][0],
        "isshow" => 2,
        "is_applet" => 0,
        "selltype" => 0,
        "fk_typeid" => 0,
        "mprice" => 0,
        "gprice" => 0,
        "freight" => 0,
        "salenum" => 0,
        "displayorder" => 0,
        "hasoption" => 1,
        "gdetaile" => $html,
        "merchantid" => $_W['user']['merchant_id'],
        "shenhe" => $shenhe,
        "gdesc" => $html,
        "copy_url" => $href,
        "createtime" => TIMESTAMP,
    );
    pdo_insert("tg_goods", $goods);
    $goodsid = pdo_insertid();
    //插入图集
    foreach ($itemInfoModel['picsPath'] as $key => $value) {
        $data2 = array('thumb' => $itemInfoModel['picsPath'][$key], 'g_id' => $goodsid);
        pdo_insert('tg_goods_atlas', $data2);
    }
    $specs = array();
    $options = array();
    if (isset($data['skuModel'])) {
        $skuModel = $data['skuModel'];
        if (isset($skuModel['skuProps'])) {
            $skuProps = $skuModel['skuProps'];
            foreach ($skuProps as $prop) {
                $spec_items = array();
                foreach ($prop['values'] as $spec_item) {
                    $spec_items[] = array('valueId' => $spec_item['valueId'], 'title' => $spec_item['name'], 'thumb' => $spec_item['imgUrl']);
                }
                $spec = array('propId' => $prop['propId'], 'title' => $prop['propName'], 'items' => $spec_items);
                $specs[] = $spec;
            }
        }
        if (isset($skuModel['ppathIdmap'])) {
            $ppathIdmap = $skuModel['ppathIdmap'];
            foreach ($ppathIdmap as $key => $skuId) {
                $option_specs = array();
                $m = explode(';', $key);
                foreach ($m as $v) {
                    $mm = explode(':', $v);
                    $option_specs[] = array('propId' => $mm[0], 'valueId' => $mm[1]);
                }
                $options[] = array('option_specs' => $option_specs, 'skuId' => $skuId, 'stock' => 0, 'marketprice' => 0, 'specs' => '');
            }
        }
    }
    if (isset($data1['skuModel'])) {
        $skuModel1 = $data1['skuModel'];
        if (isset($skuModel1['skus'])) {
            $skus = $skuModel1['skus'];
            foreach ($skus as $key => $val) {
                $sku_id = $key;
                foreach ($options as &$o) {
                    if ($o['skuId'] == $sku_id) {
                        $o['stock'] = $val['quantity'];
                        foreach ($val['priceUnits'] as $p) {
                            if ($p['display'] == 1) {
                                $o['marketprice'] = $p['price'];
                            }
                        }
                        if (!$o['marketprice']) {
                            $o['marketprice'] = $val['priceUnits'][0]['price'];
                        }
                        $titles = array();
                        foreach ($o['option_specs'] as $osp) {
                            foreach ($specs as $sp) {
                                if ($sp['propId'] == $osp['propId']) {
                                    foreach ($sp['items'] as $spitem) {
                                        if ($spitem['valueId'] == $osp['valueId']) {
                                            $titles[] = $spitem['title'];
                                        }
                                    }
                                }
                            }
                        }
                        $o['title'] = $titles;
                    }
                }
                unset($o);
            }
        }
    }

    $displayorder = 0;
    $specids = array();
    $newspecs = array();
    foreach ($specs as $spec) {
        //添加规格
        $spec1 = array(
            "uniacid" => $_W['uniacid'],
            "goodsid" => $goodsid,
            "displayorder" => $displayorder,
            "title" => $spec['title']
        );
        pdo_insert("tg_spec", $spec1);
        $specid = pdo_insertid();
        $spec['id'] = $specid;
        $specids[] = $specid;
        ++$displayorder;
        $spec_itemids = array();
        $newspecitems = array();
        $displayorder_item = 0;
        $spec_items = $spec['items'];
        foreach ($spec_items as $spec_item) {
            $spec_item1 = array(
                "uniacid" => $_W['uniacid'],
                "specid" => $specid,
                "displayorder" => $displayorder_item,
                "title" => $spec_item['title'],
                "show" => 1,
                "thumb" => $spec_item['thumb']
            );
            pdo_insert("tg_spec_item", $spec_item1);
            $spec_item_id = pdo_insertid();
            $spec_itemids[] = $spec_item_id;
            ++$displayorder_item;
            $spec_item['id'] = $spec_item_id;
            $newspecitems[] = $spec_item;
        }
        $spec['items'] = $newspecitems;
        $newspecs[] = $spec;
        //更新规格
        pdo_update("tg_spec", array("content" => serialize($spec_itemids)), array("id" => $specid));
    }

    $zstock = 0;
    foreach ($options as $o) {
        $option_specs = $o['option_specs'];
        $ids = array();
        $valueIds = array();
        foreach ($option_specs as $os) {
            foreach ($newspecs as $nsp) {
                foreach ($nsp['items'] as $nspitem) {
                    if ($nspitem['valueId'] == $os['valueId']) {
                        $ids[] = $nspitem['id'];
                        $valueIds[] = $nspitem['valueId'];
                    }
                }
            }
        }
        $ids = implode('_', $ids);
        $valueIds = implode('_', $valueIds);
        $tmp = array();
        $tmp['goodsid'] = $goodsid;
        $tmp['title'] = implode('+', $o['title']);
        $tmp['productprice'] = $o['marketprice'];
        $tmp['marketprice'] = $tmp['productprice'];
        $tmp['costprice'] = $tmp['productprice'];
        $tmp['stock'] = $o['stock'];
        $zstock += intval($tmp['stock']);
        $tmp['specs'] = $ids;
        pdo_insert("tg_goods_option", $tmp);
    }
    pdo_update("tg_goods", array("gnum" => $zstock), array("id" => $goodsid));
    echo "<script>alert('采集成功,该商品已在下架产品中!');location.href='".web_url("goods/taobao")."';</script>";
    exit();
}