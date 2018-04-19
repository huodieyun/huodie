<?php

header('Access-Control-Allow-Origin:*');//允许所有来源访问

header('Access-Control-Allow-Method:POST,GET');//允许访问的方式

global $_W, $_GPC;
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'base';
//权限控制
$tid = 8157;
//

session_start();
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$_W['page']['title'] = $checkfunction['name'];
if ($op == 'base') {
    include wl_template('store/selflogistics/base');
}
//截止
if ($op == 'display') {
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and dispatchtype = 2 ORDER BY id asc");
    include wl_template('store/selflogistics/base');
} elseif ($op == 'post') {
//            $res = pdo_fetch("select * from ".tablename("tg_map_test")." where uniacid = :uniacid and merchant_id = :merchant_id",array(":uniacid"=>$_W["uniacid"],":merchant_id"=>$_W["user"]['merchant_id']));
//            $res["map"] = unserialize($res["map"]);
//            $result = json_encode($res['map']);
    $id = $_GPC['id'];
    $dispatch = pdo_fetch("SELECT * FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and id = '{$id}'");
    if (!empty($dispatch)) {
        $dispatch_areas = unserialize($dispatch['areas']);
        $dispatch_carriers = unserialize($dispatch['carriers']);
        $dispatch_delivery = unserialize($dispatch['Deliverys']);
    }
    $areafile = IA_ROOT . "/addons/feng_fightgroups/static/area/areas";
    $areas = json_decode(@file_get_contents($areafile), true);
    if (!is_array($areas)) {
        require_once IA_ROOT . "/addons/feng_fightgroups/static/area/xml2json.php";
        $file = IA_ROOT . "/addons/feng_fightgroups/static/area/Area.xml";
        $content = file_get_contents($file);
        $json = xml2json:: transformXmlStringToJson($content);
        $areas = json_decode($json, true);
        file_put_contents($areafile, $json);
    }
    if (checksubmit('submit')) {
        $areas = array();
        $carrier = array();
        $delivery = array();
        $randoms = $_GPC['random'];
        $carriers = $_GPC['carriers'];
        $deliverys = $_GPC['deliverys'];
        $dispatch2 = pdo_fetch("SELECT * FROM " . tablename('tg_dispatch') . " WHERE uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' ");
        if (is_array($randoms)) {
            foreach ($randoms as $random) {
                $areas[] = array(
                    'citys' => $_GPC['citys'][$random],
                    'firstprice' => $_GPC['firstprice'][$random],
                    'firstweight' => $_GPC['firstweight'][$random],
                    'secondprice' => $_GPC['secondprice'][$random],
                    'secondweight' => $_GPC['secondweight'][$random]
                );
            }
        }
        if (is_array($carriers)) {
            foreach ($carriers as $random) {
                $carrier[] = array(
                    'address' => $_GPC['address'][$random],
                    'realname' => $_GPC['realname'][$random],
                    'mobile' => $_GPC['mobile'][$random],
                    'content' => $_GPC['content'][$random]
                );
            }
        }
        if (is_array($deliverys)) {
            foreach ($deliverys as $random) {
                $delivery[] = array('cart' => $_GPC['cart'][$random], 'cost' => $_GPC['cost'][$random]);
            }
        }
        //message(iserializer($carrier));
        $data = array(
            'uniacid' => $_W['uniacid'],
            'displayorder' => intval($_GPC['displayorder']),
            'dispatchtype' => 2,
            'dispatchname' => trim($_GPC['dispatchname']),
            'express' => trim($_GPC['express']),
            'firstprice' => trim($_GPC['default_firstprice']),
            'firstweight' => trim($_GPC['default_firstweight']),
            'secondprice' => trim($_GPC['default_secondprice']),
            'secondweight' => trim($_GPC['default_secondweight']),
            'areas' => iserializer($areas),
            'carriers' => iserializer($carrier),
            'deliverys' => iserializer($delivery),
            'enabled' => intval($_GPC['enabled']),
            'merchantid' => $_W['user']['merchant_id']
        );
        if (!empty($id)) {
            pdo_update('tg_dispatch', $data, array('id' => $id));
        } else {
            pdo_insert('tg_dispatch', $data);
        }
        echo "<script>alert('更新成功！');location.href='" . web_url('store/selflogistics') . "';</script>";
        exit;

    }
    include wl_template('store/selflogistics/base');
} elseif ($op == 'delete') {
    $id = intval($_GPC['id']);
    $dispatch = pdo_fetch("SELECT id,dispatchname FROM " . tablename('tg_dispatch') . " WHERE id = '$id' AND uniacid=" . $_W['uniacid'] . " and merchantid = '{$_W['user']['merchant_id']}' ");
    if (empty($dispatch)) {
        message('抱歉，派送方式不存在或是已经被删除！', $this->createWebUrl('area', array('op' => 'yunfei')), 'error');
    }
    pdo_delete('tg_dispatch', array('id' => $id));
    echo "<script>alert('删除成功！');location.href='" . web_url('store/selflogistics') . "';</script>";
    exit;

} else if ($op == 'tpl') {
    $limit = $_GPC['limit'];
    $random = random(16);
    ob_clean();
    ob_start();
    if ($limit == 'limit') {
        include $this->template('arealimit');
    } else {
        include $this->template('dispatch');
    }
    $contents = ob_get_contents();
    ob_clean();
    die(json_encode(array('random' => $random, 'html' => $contents)));
} else if ($op == 'tpl1') {
    $limit = $_GPC['limit'];
    $carriers = random(16);
    ob_clean();
    ob_start();
    if ($limit == 'limit') {
        include $this->template('arealimit');
    } else {
        include $this->template('carrier');
    }
    $contents = ob_get_contents();
    ob_clean();
    die(json_encode(array('carriers' => $carriers, 'html' => $contents)));
} else if ($op == 'tpl2') {

    $deliverys = random(16);
    ob_clean();
    ob_start();

    include wl_template('store/selflogistics/delivery');
    $contents = ob_get_contents();
    ob_clean();
    die(json_encode(array('deliverys' => $deliverys, 'html' => $contents)));
} else if ($op == "map") {
    $id = $_GPC["id"];
    if (empty($id)) {
        $ret = array("status" => "error", "data" => "无效值!");
        die(json_encode($ret));
    }
    $data["map"] = $_GPC["all_points"];
    $data["map"] = serialize($data["map"]);

    $res = pdo_update("tg_print", $data, array("id" => $id, "uniacid" => $_W["uniacid"]));
//    if (empty($res)){
//        $data["openid"] = $_W["openid"];
//        $data["uniacid"] = $_W["uniacid"];
//        $res = pdo_insert("tg_map_test",$data);
//    }else{
//        $res = pdo_update("tg_map_test",$data,array("uniacid"=>$_W["uniacid"],"merchant_id"=>$_W["merchant_id"]));
//    }
//    echo "<script>alert('上传成功,如果通信失败请重试');location.href=location.href;</script>";
    $dat = unserialize($data["map"]);
    $ret = array("status" => "success", "data" => $dat);
    die(json_encode($ret));
}

if ($op == 'delivery_man') {
    wl_load()->classs('qrcode');
    $createqrcode = new creat_qrcode();
    $opp = $_GPC['opp'] ? $_GPC['opp'] : 'display';
    if ($opp == 'display') {
        $con = " and merchantid = '{$_W['user']['merchant_id']}' ";
        $list = pdo_fetchall("select * from " . tablename('tg_delivery_man') . " where uniacid = '{$_W['uniacid']}' " .$con);
//die(json_encode($list));
    } elseif ($opp == 'post') {
        $id = $_GPC['id'];

        if ($id) {
            $saler = pdo_fetch("select * from " . tablename('tg_delivery_man') . " where uniacid='{$_W['uniacid']}' and id = '{$id}'");
        }
        if (checksubmit()) {
            wl_load()->model('member');
            $id = $_GPC['id'];
            $data = array(
                'uniacid' => $_W['uniacid'],
                'openid' => $_GPC['openid'],
                'status' => $_GPC['salerstatus'],
                'tel' => $_GPC['tel'],
                'merchantid' => $_W['user']['merchant_id']
            );

            if ($data['openid'] == '') {
                message('必须选择派送员！', referer(), 'error');
                exit;
            }
            $info = member_get_by_params(" openid = '{$data['openid']}'");
            $data['avatar'] = $info['avatar'];
            $data['nickname'] = $info['nickname'];
            if ($id) {
                $ret = pdo_update('tg_delivery_man', $data, array('id' => $id));
            } else {
                $data['createtime'] = TIMESTAMP;

                pdo_insert('tg_delivery_man', $data);
            }
            message('操作成功！', web_url('store/selflogistics/delivery_man'), 'success');
        }
    } elseif ($opp == 'delete') {
        $id = $_GPC['id'];
        pdo_delete('tg_delivery_man', array('id' => $id));
        message('删除成功！', referer(), 'success');
    }
    include wl_template('store/selflogistics/base');
} elseif ($op == 'selectsaler') {
    $con = "uniacid='{$_W['uniacid']}' ";
    $keyword = $_GPC['keyword'];
    if ($keyword != '') {
        $con .= " and nickname LIKE '%{$keyword}%'";
    }
    $ds = pdo_fetchall("select * from " . tablename('tg_member') . " where $con");
    include wl_template('store/selflogistics/query_saler');
    exit;
}

// 配送地址
if ($op == 'warehouse') {
    $store_distance = pdo_get('tg_delivery_distance' , array('uniacid' => $_W['uniacid']));
    if ($store_distance) {
        $result = unserialize($store_distance['map']);
        if ($result) {
            $result = json_encode($result);
        }
    }

    include wl_template('store/selflogistics/warehouse');
    die;
}

if ($op == 'newWarehouse') {
    include wl_template('store/selflogistics/warehouse');
}

if ($op == "store_distance") {

    $data["map"] = $_GPC["all_points"];
    $data["map"] = serialize($data["map"]);

    $store_distance = pdo_get('tg_delivery_distance' , array('uniacid' => $_W['uniacid']));
    if ($store_distance) {
        $res = pdo_update("tg_delivery_distance", $data, array("uniacid" => $_W["uniacid"]));
    } else {
        $data['uniacid'] = $_W['uniacid'];
        $data['createtime'] = TIMESTAMP;
        $data['status'] = 1;
        pdo_insert('tg_delivery_distance' , $data);
    }

    $dat = unserialize($data["map"]);
    if ($res) {
        $ret = array("status" => "success", "data" => $dat);
    } else {
        $ret = array("status" => "error", "data" => $dat);
    }

    die(json_encode($ret));
}

?>
