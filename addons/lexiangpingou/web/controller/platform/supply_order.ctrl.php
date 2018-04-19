<?php

$_W['page']['title'] = "极限单品 - 订单管理";
$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}
global $_W, $_GPC;
load()->func("tpl");
$uniacid = $_W['uniacid'];
$supply = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:uniacid', array(':uniacid' => $uniacid));
$province = pdo_getall('erp_area', array('level' => 1));

if ($op == "display") {
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $keyword = $_GPC['keyword'];
    $con = '';
    if (!empty($keyword)) {
        if ($_GPC['con_type'] == 1) {
            $con .= " and gname like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 2) {
            $sid = intval(str_replace('JXDP', '', $keyword));
            $con .= " and supply_id = {$sid} ";
        }
        if ($_GPC['con_type'] == 3) {
            $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
        }
    }
    $goodses = pdo_fetchall("select * from " . tablename('tg_supply_goods') . " where supply_id = '{$uniacid}' and status = 1 " . $con . " limit " . ($page - 1) * $size . " , " . $size);
    foreach ($goodses as &$item) {
        $item['pici'] = "JXDP" . sprintf("%04d", $item['id']);
        unset($item);
    }
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_goods') . " where supply_id = '{$uniacid}' and status = 1 " . $con);
    $pager = pagination($total, $page, $size);
}

if ($op == 'supply') {
    $id = intval($_GPC['id']);
    if ($id > 0) {
        $order = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where supply_goodsid = '{$id}' ");
        die(json_encode($order));
    } else {
        $order = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where supply_id = '{$uniacid}' ");
        die(json_encode($order));
    }

}
if ($op == 'supply_refund') {
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $keyword = $_GPC['keyword'];
    $con = '';
    if (!empty($keyword)) {
        if ($_GPC['con_type'] == 1) {
            $con .= " and singleno like '%{$keyword}%' ";
        }
        if ($_GPC['con_type'] == 2) {
            $con .= " and uni_payno like '%{$keyword}%' ";
        }
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']);
        $con .= " AND createtime >= {$starttime} AND  createtime <= {$endtime} ";
    }
//    $con .= " and status=0";
    $list = pdo_fetchall("select * from " . tablename('tg_platform_pay_supply_refund') . " where supply_id = '{$_W['uniacid']}' " . $con . " limit " . ($page - 1) * $size . " , " . $size);
    foreach ($list as &$item) {
        $item['uni_payimg'] = tomedia($item['uni_payimg']);
        if ($item['status'] == 0) {
            $item['statusname'] = '供货商待打款';
        } elseif ($item['status'] == 1) {
            $item['statusname'] = '供货商已打款';
        } elseif ($item['status'] == 2) {
            $item['statusname'] = '平台已确认收款';
        }
        unset($item);
    }
    $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_platform_pay_supply_refund') . " where supply_id = '{$_W['uniacid']}' " . $con);
    $pager = pagination($total, $page, $size);

}

//退款单详情
if ($op == 'refund_detail') {
    $id = intval($_GPC['id']);
    $list = pdo_get('tg_platform_pay_supply_refund', array('id' => $id));
    if ($list['status'] == 0) {
        $list['status'] = '供货商待打款';
    } elseif ($list['status'] == 1) {
        $list['status'] = '供货商已打款';
    } elseif ($list['status'] == 2) {
        $list['status'] = '平台已确认收款';
    }
    if ($list['paytime'] > 0) {
        $list['paytime'] = date('Y-m-d H:i:s', $list['paytime']);
    }
    if ($list['check_paytime'] > 0) {
        $list['check_paytime'] = date('Y-m-d H:i:s', $list['check_paytime']);
    }
    $list['uni_payimg'] = tomedia($list['uni_payimg']);

    die(json_encode(array('data' => $list, 'status' => 1)));
}

if ($op == 'supply_pay_check_receive') {
    $id = intval($_GPC['id']);

    $res = pdo_update('tg_platform_pay_supply_refund', array('status' => 2, 'check_paytime' => TIMESTAMP), array('id' => $id));
    if ($res) {
        $message = '收款成功';
        $refund = pdo_get('tg_platform_pay_supply_refund', array('id' => $id));
        $supplier = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:id', array(':id' => $refund['supply_id']));
        $dat['openid'] = $supplier['openid'];
        if ($dat['openid']) {
            $dat['first'] = '平台申请退款';
            $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $dat['keyword2'] = '平台申请退款确认';
            $dat['keyword3'] = '火蝶云极限单品平台向供应商申请退款，平台收款成功';
            $dat['keyword4'] = $message;
            $dat['remark'] = '';
            pdo_insert('tg_service_process', $dat);
        }
    } else {
        $message = '收款失败';
    }
    die(json_encode(array('status' => $res, 'message' => $message)));
}

//供应商打退款单的款给平台
if ($op == 'supply_refund_check_pay') {
    $id = $_GPC['id'];
    $data['status'] = 1;
    $data['uni_payno'] = $_GPC['uni_payno'];
    $data['uni_payimg'] = $_GPC['uni_payimg'];
    $data['paytime'] = TIMESTAMP;
    $res = pdo_update('tg_platform_pay_supply_refund', $data, array('id' => $id));
    if ($res) {
        $refund = pdo_get('tg_platform_pay_supply_refund', array('id' => $id));
        $supplier = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:id', array(':id' => $refund['supply_id']));
        $dat['openid'] = $supplier['openid'];
        if ($dat['openid']) {
            $dat['first'] = '平台申请退款';
            $dat['keyword1'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $dat['keyword2'] = '供货商打款';
            $dat['keyword3'] = '火蝶云极限单品平台向供应商申请退款，供货商已打款';
            $dat['keyword4'] = '供货商打款成功';
            $dat['remark'] = '';
            pdo_insert('tg_service_process', $dat);
        }
    }
    die(json_encode(array('status' => $res)));
}
if ($op == 'order') {

    $id = intval($_GPC['id']);
    if ($id > 0) {
        //platform_status 1:审核通过 0：待审核 -1：不通过 
        $list = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where supply_goodsid = {$id} and platform_status = 1 order by id desc ");
        foreach ($list as &$item) {
            $item['uni_payimg'] = tomedia($item['uni_payimg']);
            unset($item);
        }
//        die(json_encode(array('list' => $list)));
    } else {

        $page = max(1, intval($_GPC['page']));
        $size = 10;
        $keyword = $_GPC['keyword'];
        $con = '';
        $status = intval($_GPC['status']);
        if ($status > 0) {
            $status -= 1;
            $con .= " and supply_status = '{$status}'";
        }
        if (!empty($keyword)) {
            if ($_GPC['con_type'] == 1) {
                $con .= " and gname like '%{$keyword}%' ";
            }
            if ($_GPC['con_type'] == 2) {
                $con .= " and singleno like '%{$keyword}%' ";
            }
            if ($_GPC['con_type'] == 3) {
                $con .= " and supply_id in (select uniacid from cm_tg_platform_supplier where name like '%{$keyword}%') ";
            }
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $con .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";
        }
//        var_dump($con);
        $list = pdo_fetchall("select * from " . tablename('tg_supply_order') . " where supply_id = {$uniacid} and platform_status = 1 " . $con . " order by id desc limit " . ($page - 1) * $size . " , " . $size);
        $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_order') . " where supply_id = {$uniacid} and platform_status = 1 " . $con);
        foreach ($list as &$item) {
            $item['uni_payimg'] = tomedia($item['uni_payimg']);
            unset($item);
        }
        $pager = pagination($total, $page, $size);
//        die(json_encode(array('list' => $list)));
    }


}
//子单列表
if ($op == 'collect') {
    $order_id = intval($_GPC['collect_id']);
    if ($order_id > 0) {

        $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where id = {$order_id} ");
        $order['pici'] = "JXDP" . sprintf("%04d", $order['supply_goodsid']);
        $page = max(1, intval($_GPC['page']));
        $size = 10;
        $keyword = $_GPC['keyword'];
        $con = '';
        if (!empty($keyword)) {
            $con .= " and gname like '%{$keyword}%' ";
        }
        $list = pdo_fetchall("select * from " . tablename('tg_supply_collect') . " where supply_orderid = {$order_id} " . $con . " order by id desc limit " . ($page - 1) * $size . " , " . $size);
        foreach ($list as &$item) {
            $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$item['orderid']}'");
            $item['orderno'] = $o['orderno'];

            $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $item['supply_goodsid']));
            $item['taxrate'] = floatval($goods['taxrate']) * 0.01 * floatval($item['oprice']) * floatval($item['num']);

            unset($item);
        }
        $total = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_collect') . " where supply_orderid = {$order_id} " . $con);
        $pager = pagination($total, $page, $size);
//        die(json_encode($list));
    } else {

    }

}

//订单详情
//status=1成功 data详细数据
//0失败 data错误信息
//
if ($op == 'detail') {
//    $id = intval($_GPC['id']);
//    if ($id > 0) {
//
//        $page = max(1, intval($_GPC['page']));
//        $size = 10;
//        $keyword = $_GPC['keyword'];
//        $con = '';
//        if (!empty($keyword)) {
//            $con .= " and gname like '%{$keyword}%' ";
//        }
//        $list = pdo_fetch("select * from " . tablename('tg_supply_collect') . " where id = '{$id}' ");
//        $o = pdo_fetch("select orderno,status from " .tablename('tg_order') ." where id = '{$list['orderid']}'");
//        $list['status'] = $o['status'];
//        $list['orderno'] = $o['orderno'];
//        $list['refund_imgs'] = unserialize($list['refund_imgs']);
//        die(json_encode(array('data' => $list , 'status' => 1)));
//    }else{
//        die(json_encode(array('data' => '传入参数错误！' , 'status' => 0)));
//    }
    $id = intval($_GPC['id']);
    if ($id > 0) {

        $page = max(1, intval($_GPC['page']));
        $size = 10;
        $keyword = $_GPC['keyword'];
        $con = '';
        if (!empty($keyword)) {
            $con .= " and gname like '%{$keyword}%' ";
        }
        $list = pdo_fetch("select * from " . tablename('tg_supply_collect') . " where id = {$id} ");
        $o = pdo_fetch("select orderno,uniacid from " . tablename('tg_order') . " where id = {$list['orderid']}");
        $order = pdo_fetch("select * from " . tablename('tg_supply_order') . " where singleno = '{$list['singleno']}'");
        $account = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = {$order['uniacid']}");
        if (!empty($order['uni_payimg'])) {
            $order['uni_payimg'] = tomedia($order['uni_payimg']);
        }

        $goods = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_goods') . ' WHERE id=:id', array(':id' => $list['supply_goodsid']));
        $list['taxrate'] = floatval($goods['taxrate']) * 0.01 * $list['oprice'] * $list['num'];

        $list['order'] = $order;
        $list['shop_accountname'] = $account['name'];
        $list['orderno'] = $o['orderno'];
        $list['refund_imgs'] = unserialize($list['refund_imgs']);
        die(json_encode(array('data' => $list, 'status' => 1)));
    } else {
        die(json_encode(array('data' => '传入参数错误！', 'status' => 0)));
    }
}

//批量发货
if ($op == 'ship') {
    $supply = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
    $supplyid = $supply['uniacid'];
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $singleno = $_GPC['singleno'];
    $goodsid = $_GPC['goodsid'];
    $con = '';
    if (!empty($singleno)) {
        $con .= " and singleno like '%{$singleno}%' ";
    }
    if (!empty($goodsid)) {
        $con .= " and supply_goodsid = '{$goodsid}' ";
    }
    $con .= " and refund = 0 ";
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE platform_status = 1 and supply_status = 0 and supply_id = {$supplyid}" . $con . " LIMIT " . ($page - 1) * $size . " , " . $size);
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_collect') . " WHERE platform_status = 1 and supply_status = 0 and supply_id={$supplyid}" . $con);
    foreach ($list as &$item) {
        $g = pdo_fetch("select gimg from " . tablename('tg_supply_goods') . " where id = '{$item['supply_goodsid']}'");
        $order = pdo_fetch('SELECT * FROM ' . tablename('tg_supply_order') . ' WHERE id=:id', array(':id' => $item['supply_orderid']));
        if ($item['orderid'] > 0) {
            $fans_order = pdo_fetch('SELECT * FROM ' . tablename('tg_order') . ' WHERE id=:id', array(':id' => $item['orderid']));
            $mem = pdo_fetch('SELECT * FROM ' . tablename('tg_member') . ' WHERE openid=:openid', array(':openid' => $fans_order['openid']));
            $item['nickname'] = $mem['nickname'];
        }
        $item['order'] = $order;
        $item['gimg'] = $g['gimg'];
        unset($item);
    }
    $pager = pagination($total, $page, $size);
    $allgoods = pdo_fetchall("select id,name from " . tablename('tg_supply_goods') . " where supply_id = '{$_W['uniacid']}' ");
//var_dump($list);

}

//单发
if ($op == 'ship_one') {
    $id = $_GPC['id'];
    $expressName = $_GPC['express'];
    $expressOrder = $_GPC['expressno'];
    if ($id) {
        $order = pdo_get('tg_supply_collect', array('id' => $id));
        if ($order['supply_status'] == 0) {
            $res = pdo_update('tg_supply_collect', array('supply_status' => 1, 'expresssno' => $expressOrder, 'expresss' => $expressName, 'supply_time' => TIMESTAMP), array('id' => $id));
            $message = '';
            if ($res) {
                /*记录操作*/
                $oplogdata = serialize($order);
                oplog($_W['username'], "极限单品后台单件发货", web_url('platform/supply_order/ship_one'), $oplogdata);

                if (intval($order['orderid']) > 0) {
                    $res = pdo_update('tg_order', array('status' => 2, 'sendepressmessage' => 1, 'express' => $expressName, 'expresssn' => $expressOrder, 'delivery_time' => TIMESTAMP), array('id' => $order['orderid'], 'status' => 8));
                    if ($res) {
                        $message = '发货成功';
                    } else {
                        $message = '发货失败';
                    }
                } else {
                    $message = '发货成功';
                }

                //子单全部发货 更改主单发货状态
                $count = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_collect') . " where supply_orderid = {$order['supply_orderid']} and refund = 0 and supply_status = 0 ");
                if (!$count) {
                    pdo_update('tg_supply_order', array('supply_status' => 1, 'supply_time' => TIMESTAMP), array('id' => $order['supply_orderid']));
                }

            } else {
                $message = '发货失败';
            }
            die(json_encode(['status' => $res, 'message' => $message]));
        } else {
            die(json_encode(['status' => 0, 'message' => '抱歉！此订单已发货，请勿重复发货']));
        }
    } else {
        die(json_encode(['status' => 0, 'message' => '传入参数错误']));
    }
}

if ($op == 'output') {
    $supply = pdo_fetch('SELECT * FROM ' . tablename('tg_platform_supplier') . ' WHERE uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
    $supplyid = $supply['uniacid'];
    $singleno = $_GPC['singleno'];
    $goodsid = $_GPC['goodsid'];
    $con = '';
    if (!empty($singleno)) {
        $con .= " and singleno like '%{$singleno}%' ";
    }
    if (!empty($goodsid)) {
        $con .= " and supply_goodsid = '{$goodsid}' ";
    }
    $con .= "and supply_id = {$supplyid}";
    $con .= " and refund = 0 ";
    $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_collect') . " WHERE 1 AND platform_status = 1 AND supply_status = 0 {$con} ");

    $str = '待发货订单_' . time();

    /* 输入到CSV文件 */
    $html = "\xEF\xBB\xBF";
    /* 输出表头 */
    $filter = array('a1' => '极限单品发货单编号', 'aa' => '极限单品单号', 'bb' => '商品名称', 'cc' => '商品规格', 'dd' => '购买数量', 'ee' => '供应价', 'ff' => '发货状态', 'gg' => '姓名', 'pp' => '电话', 'h1' => '省', 'h2' => '市', 'h3' => '区', 'h4' => '详细地址', 'jj' => '快递单号', 'kk' => '快递名称');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    $html .= "\n";

    foreach ($orders as $k => $v) {

        $thistatus = '待发货';
        if ($v['supply_status'] == 1) {
            $thistatus = '已发货';
        }

        $orders[$k]['a1'] = $v['id'];
        $orders[$k]['aa'] = $v['singleno'];
        $orders[$k]['bb'] = $v['goodsname'];
        $orders[$k]['cc'] = $v['optionname'];
        $orders[$k]['dd'] = $v['num'];
        $orders[$k]['ee'] = $v['oprice'];
        $orders[$k]['ff'] = $thistatus;
        $orders[$k]['gg'] = $v['realname'];
        $orders[$k]['pp'] = $v['mobile'];
        $orders[$k]['h1'] = $v['province'];
        $orders[$k]['h2'] = $v['city'];
        $orders[$k]['h3'] = $v['county'];
        $orders[$k]['h4'] = trim($v['address']);
        $orders[$k]['jj'] = $v['expresssno'];
        $orders[$k]['kk'] = $v['express'];

        foreach ($filter as $key => $title) {
            $html .= $orders[$k][$key] . "\t,";
        }
        $html .= "\n";

    }
    /* 输出CSV文件 */
    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename={$str}.csv");
    echo $html;
    exit();
}

if ($op == 'import') {
    $file = $_FILES['fileName'];
    $max_size = "2000000";
    $fname = $file['name'];
    $ftype = strtolower(substr(strrchr($fname, '.'), 1));
    //文件格式
    $uploadfile = $file['tmp_name'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (is_uploaded_file($uploadfile)) {
            if ($file['size'] > $max_size) {
                message("导入的文件过大！");
//                echo "Import file is too large";
//                exit;
            }
            if ($ftype == 'csv') {
                if (empty ($uploadfile)) {
                    message("请选择要导入的CSV文件！");
//                    echo '请选择要导入的CSV文件！';
//                    exit;
                }
                $handle = fopen($uploadfile, 'r');
                $n = 0;
                while ($data = fgetcsv($handle, 10000)) {
                    $num = count($data);
                    for ($i = 0; $i < $num; $i++) {
                        $out[$n][$i] = $data[$i];
                    }
                    $n++;
                }
                $result = $out; //解析csv
                $len_result = count($result);
                if ($len_result == 0) {
                    message("没有任何数据！");
//                    echo '没有任何数据！';
//                    exit;
                }
                $succ_result = 0;
                $error_result = 0;

                $orderid = array();

                for ($i = 1; $i < $len_result; $i++) { //循环获取各字段值
                    $collectid = trim(iconv('gb2312', 'utf-8', $result[$i][0])); //中文转码
                    $orderNo = trim(iconv('gb2312', 'utf-8', $result[$i][1])); //中文转码
                    if ($orderNo == '') {
                        continue;
                    }

                    $expressOrder = trim(iconv('gb2312', 'utf-8', $result[$i][13]));
                    $expressName = trim(iconv('gb2312', 'utf-8', $result[$i][14]));

                    if (!empty($expressOrder) && !empty($expressName)) {
                        $single = pdo_fetch("select * from " . tablename('tg_supply_collect') . " where id = {$collectid} ");

                        if ($orderid != $single['supply_orderid']) {
                            $orderid[] = $single['supply_orderid'];
                        }

                        if (intval($single['orderid']) == 0) {
                            pdo_update('tg_supply_collect', array('supply_status' => 1, 'supply_time' => TIMESTAMP, 'expresss' => $expressName, 'expresssno' => $expressOrder,), array('id' => $single['id']));
                            $succ_result += 1;
                        } else {
                            $res = pdo_update('tg_order', array('status' => 2, 'sendepressmessage' => 1, 'express' => $expressName, 'expresssn' => $expressOrder, 'delivery_time' => TIMESTAMP), array('id' => $single['orderid'], 'status' => 8));
                            if ($res) {
                                $url = app_url('order/order/detail', array('id' => $single['orderid']));
                                pdo_update('tg_supply_collect', array('supply_status' => 1, 'supply_time' => TIMESTAMP, 'expresss' => $expressName, 'expresssno' => $expressOrder,), array('id' => $single['id']));
                                $succ_result += 1;
                            } else {
                                $error_result += 1;
                            }
                            unset($res);
                        }
                        unset($single);
                    } else {
                        $error_result += 1;
                    }

                    foreach ($orderid as $it) {

                        //子单全部发货 更改主单发货状态
                        $count = pdo_fetchcolumn("select count(id) from " . tablename('tg_supply_collect') . " where supply_orderid = {$it} and refund = 0 and supply_status = 0 ");
                        if (!$count) {
                            pdo_update('tg_supply_order', array('supply_status' => 1, 'supply_time' => TIMESTAMP), array('id' => $it));
                        }

                    }

                    unset($orderNo);
                    unset($expressOrder);
                    unset($expressName);
                    unset($collectid);

                }
                fclose($handle); //关闭指针
            } else {
//                die(json_encode(array('status' => 1, 'message' => "文件后缀格式必须为csv")));
                message("文件后缀格式必须为csv!");
                exit;
            }
        } else {
//            die(json_encode(array('status' => 1, 'message' => "文件名不能为空!")));
            message("文件名不能为空!");
            exit;
        }
    }
//    die(json_encode(array('status' => 1, 'message' => '导入发货订单操作成功！成功' . $succ_result . '条，失败' . $error_result . '条')));
    message('导入发货订单操作成功！成功' . $succ_result . '条，失败' . $error_result . '条', web_url('platform/supply_order/ship'), 'success');
}

include wl_template("platform/supply_order");
exit();