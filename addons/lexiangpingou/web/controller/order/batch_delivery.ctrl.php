<?php
$ops = array('display', 'output', 'import');
$op_names = array('发货列表', '导出订单', '导入订单');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'order', 'import', $ops[$key], '订单', '批量发货', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'display';
load()->func('tpl');
$uniacid = $_W['uniacid'];
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$allgoods = pdo_fetchall("select gname,id from " . tablename('tg_goods') . " where uniacid = :uniacid and merchantid = '{$_W['user']['merchant_id']}'", array(':uniacid' => $_W['uniacid']));

if ($op == 'display') {

    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $condition = " uniacid = :weid";
    $paras = array(':weid' => $_W['uniacid']);

    $status = $_GPC['status'];
    $keyword = $_GPC['keyword'];
    //商品ID
    $member = $_GPC['member'];
    //电话、姓名
    $time = $_GPC['time'];
    //下单时间

    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']);
        $condition .= " AND  createtime >= :starttime AND  createtime <= :endtime ";
        $paras[':starttime'] = $starttime;
        $paras[':endtime'] = $endtime;
    }
    $gid = intval($_GPC['goodsid2']);
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND orderno like '%{$_GPC['keyword']}%'";
    } elseif ($gid > 0) {
        $condition .= " and ( g_id = {$gid} or orderno in ( select orderno from " .tablename('tg_collect') ." where sid = {$gid} ) ) ";
    }
//    if (trim($_GPC['goodsid']) != '') {
//        $condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
//    }
//    if (trim($_GPC['goodsid2']) != '') {
//        $condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
//    }
    if (trim($_GPC['address']) != '') {

        $condition .= " and address like '%{$_GPC['address']}%' ";
    }


    if (!empty($_GPC['addresstype'])) {
        $condition .= " AND  addresstype={$_GPC['addresstype']} ";
    }
    if (!empty($_GPC['nickname'])) {
        $condition .= " AND openid in(select from_user from " . tablename('tg_member') . " where nickname LIKE '%{$_GPC['nickname']}%' and  uniacid='{$_W['uniacid']}')";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND (addname LIKE '%{$_GPC['member']}%' or mobile LIKE '%{$_GPC['member']}%')";
    }
    if ($status != '') {
        if ($status == 1) {
            $condition .= " AND is_tuan=1 ";
        } else {
            $condition .= " AND is_tuan=0 ";
        }

    }
    $godluck = $_GPC['godluck'];
    if (!empty($godluck)) {
        $condition .= " AND godluck={$_GPC['godluck']} ";
        $condition .= " AND status in (6,8) and expresssn is NULL";
    }
    if (empty($godluck)) {
        $condition .= " AND status in (6,8) ";
    }
//    if ($_W['user']['merchant_id'] > 0) {
    $condition .= " and merchantid = {$_W['user']['merchant_id']} ";
    $condition .= ' and supply_goodsid = 0 and dispatchtype = 1';
//    }
    $sql = "select  * from " . tablename('tg_order') . " where $condition  and mobile<>'虚拟'  ORDER BY createtime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
    $list = pdo_fetchall($sql, $paras);
//die(json_encode($sql));
    //echo "<pre>";print_r($list);exit;
    $paytype = array('0' => array('css' => 'default', 'name' => '未支付'), '1' => array('css' => 'info', 'name' => '余额支付'), '2' => array('css' => 'success', 'name' => '在线支付'), '3' => array('css' => 'warning', 'name' => '货到付款'));
    $orderstatus = array(
        '0' => array('css' => 'default', 'name' => '待付款'),
        '1' => array('css' => 'info', 'name' => '已付款'),
        '2' => array('css' => 'warning', 'name' => '待收货'),
        '3' => array('css' => 'success', 'name' => '已签收'),
        '4' => array('css' => 'success', 'name' => '已退款'),
        '5' => array('css' => 'success', 'name' => '强退款'),
        '6' => array('css' => 'danger', 'name' => '部分退款'),
        '7' => array('css' => 'success', 'name' => '已退款'),
        '8' => array('css' => 'success', 'name' => '待发货'),
        '9' => array('css' => 'success', 'name' => '已取消'),
        '10' => array('css' => 'success', 'name' => '待退款'),
        '11' => array('css' => 'success', 'name' => '退款异常')
    );
    foreach ($list as &$value) {
        $s = $value['status'];
        $value['statuscss'] = $orderstatus[$value['status']]['css'];
        $value['status'] = $orderstatus[$value['status']]['name'];
        $value['css'] = $paytype[$value['pay_type']]['css'];
        if ($value['pay_type'] == 2) {
            if (empty($value['transid'])) {
                $value['paytype'] = '微信支付';
            } else {
                $value['paytype'] = '微信支付';
            }
        } else {
            $value['paytype'] = $paytype[$value['pay_type']]['name'];
        }
        $goodsss = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$value['g_id']}'");
        $value['gid'] = $goodsss['id'];
        $value['gname'] = $goodsss['gname'];
        $value['gimg'] = $goodsss['gimg'];
        $value['freight'] = $goodsss['freight'];
        $m = $value['merchantid'];
        if ($m == 0) {
            $value['merchant_name'] = $_W['account']['name'];
        } else {
            $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
            $value['merchant_name'] = $name['name'];
        }
        //$options  = pdo_fetch("select title,productprice,marketprice from " . tablename("tg_goods_option") . " where id=:id limit 1", array(":id" => $value['optionid']));
        //$value['optionname'] = $options['title'];
        $value['merchant'] = pdo_fetch("select name from" . tablename('tg_merchant') . "where id = '{$goodsss['merchantid']}' and uniacid={$_W['uniacid']}");
    }
    $total_choujian = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition and godluck=1 and mobile<>'虚拟' ", $paras);

    $total_tuan = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition and is_tuan=1 and mobile<>'虚拟' ", $paras);
    $total_single = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition and is_tuan=0 and mobile<>'虚拟' ", $paras);
    $total_all = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition and mobile<>'虚拟' and master_orderno is NULL ", $paras);
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_order') . " WHERE $condition and mobile<>'虚拟' ", $paras);
    $pager = pagination($total, $pindex, $psize);

} elseif ($op == 'import') {
    $load_type = $_GPC['load_type'];
    $file = $_FILES['fileName'];
    $max_size = "2000000";
    $fname = $file['name'];
    $ftype = strtolower(substr(strrchr($fname, '.'), 1));
    //文件格式
    $uploadfile = $file['tmp_name'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (is_uploaded_file($uploadfile)) {
            if ($file['size'] > $max_size) {
                message("导入文件太大");
                exit;
            }
            if ($ftype == 'csv') {
                if (empty ($uploadfile)) {
                    message('请选择要导入的CSV文件！');
                    exit;
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
                    message('没有任何数据！');
                    exit;
                }
                $succ_result = 0;
                $error_result = 0;
                for ($i = 1; $i < $len_result; $i++) { //循环获取各字段值
                    $orderNo = trim(iconv('gb2312', 'utf-8', $result[$i][0])); //中文转码
                    if ($orderNo == '') {
                        continue;
                    }

                    $expressName = trim(iconv('gb2312', 'utf-8', $result[$i][22]));
                    if ($load_type == 'kd') {
                        $expressOrder = trim(iconv('gb2312', 'utf-8', $result[$i][20]));
                    } else {
                        $expressOrder = trim(iconv('gb2312', 'utf-8', $result[$i][21]));
                    }


                    if (!empty($expressOrder)) {
                        $order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderNo}'");

                        if ($order['dispatchtype'] == 1) {
                            //插入次卡记录
                            if ($order['is_once_card'] == 1) {
                                $dat['orderid'] = $order['id'];
                                $dat['orderno'] = $order['orderno'];
                                $dat['orderno_child'] = date('Ymd') . mt_rand(100, 999);
                                $dat['openid'] = $order['openid'];
                                $dat['order_status'] = $order['dispatchtype'];
                                // 配送时间
                                switch ($order['once_card_json']) {
                                    case 'once_card_mon':
                                        $dat['send_date'] = '星期一';
                                        break;
                                    case 'once_card_tues':
                                        $dat['send_date'] = '星期二';
                                        break;
                                    case 'once_card_wed':
                                        $dat['send_date'] = '星期三';
                                        break;
                                    case 'once_card_thur':
                                        $dat['send_date'] = '星期四';
                                        break;
                                    case 'once_card_fir':
                                        $dat['send_date'] = '星期五';
                                        break;
                                    case 'once_card_sat':
                                        $dat['send_date'] = '星期六';
                                        break;
                                    case 'once_card_sun':
                                        $dat['send_date'] = '星期天';
                                        break;
                                    case 'once_card_half_month ':
                                        $dat_send_date = '半个月';
                                        break;
                                    case 'once_card_month':
                                        $dat_send_date = '1个月';
                                        break;
                                    default:
                                        $dat['send_date'] = '未选择';
                                        break;
                                }
                                // $dat['send_date'] = $item['send_date'];
                                $dat['delivery_date'] = TIMESTAMP;
                                $dat['express'] = $expressName;
                                $dat['expresssn'] = $expressOrder;
                                $dat['mobile'] = $order['mobile'];
                                $dat['province'] = $order['province'];
                                $dat['city'] = $order['city'];
                                $dat['county'] = $order['county'];
                                $dat['detailed_address'] = $order['detailed_address'];
                                $dat['comadd'] = $order['comadd'];
                                $dat['uniacid'] = $_W['uniacid'];
                                pdo_insert('tg_order_once_card_record', $dat);
                            }
                            if ($load_type == 'kd' && !empty($expressName)) {
                                $res = pdo_update('tg_order', array('status' => 2, 'sendepressmessage' => 1, 'express' => $expressName, 'expresssn' => $expressOrder, 'delivery_time' => TIMESTAMP), array('orderno' => $orderNo, 'status' => 8));
                            } else {
                                $res = pdo_update('tg_order', array('status' => 2, 'sendepressmessage' => 1, 'express' => $expressName, 'storeid' => $expressOrder, 'delivery_time' => TIMESTAMP), array('orderno' => $orderNo, 'status' => 8));
                            }
                            if ($res) {
                                $succ_result += 1;
                            } else {
                                $error_result += 1;
                            }
                        }
                    } else {
                        $error_result += 1;
                    }
                }
                fclose($handle); //关闭指针
            } else {
                message("文件后缀格式必须为csv");
                exit;
            }
        } else {
            message("文件名不能为空!");
            exit;
        }
    }
    message('导入派送订单操作成功！成功' . $succ_result . '条，失败' . $error_result . '条', referer(), 'success');
} elseif ($op == 'output') {
    wl_load()->model('member');
    $status = $_GPC['status'];

    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];

    $starttime = $_GPC['starttime'];
    $endtime = $_GPC['endtime'];
    $condition = " uniacid={$_W['uniacid']}";
    if ($status != '') {
        if ($status == 1) {
            $condition .= " AND is_tuan=1 ";
        } else {
            $condition .= " AND is_tuan=0 ";
        }
    }
    $godluck = $_GPC['godluck'];

    if (!empty($godluck)) {
        $condition .= " AND godluck={$_GPC['godluck']} ";
        $condition .= " AND status in (6,8)  ";
    }
    if (empty($godluck)) {
        $condition .= " AND status in (6,8) ";
    }
    $gid = intval($_GPC['goodsid2']);
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND orderno like '%{$_GPC['keyword']}%'";
    }
    if ($gid > 0) {
        $condition .= " and ( g_id = {$gid} or orderno in ( select orderno from " .tablename('tg_collect') ." where sid = {$gid} ) ) ";
    }
//    if (trim($_GPC['goodsid']) != '') {
//        $condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
//    }
//    if (trim($_GPC['goodsid2']) != '') {
//        $condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
//    }
    if (!empty($_GPC['merchantid'])) {
        $condition .= " AND  merchantid={$_GPC['merchantid']} ";
    }

    if (trim($_GPC['address']) != '') {

        $condition .= " and address like '%{$_GPC['address']}%' ";
    }
    if (!empty($_GPC['addresstype'])) {
        $condition .= " AND  addresstype={$_GPC['addresstype']} ";
    }
    if (!empty($member)) {
        $condition .= " AND (addname LIKE '%{$member}%' or mobile LIKE '%{$member}%')";
    }
    if (!empty($time)) {
        $condition .= " AND  createtime >= $starttime AND  createtime <= $endtime  ";
    }
    $condition .= " AND merchantid = '{$_W['user']['merchant_id']}'";
    $condition .= ' and supply_goodsid = 0 and dispatchtype = 1 ';
//	$orders = pdo_fetchall("select * from" . tablename('tg_order') . "where $condition and status=2");
    $orders = pdo_fetchall("select * from " . tablename('tg_order') . " where $condition and mobile<>'虚拟' order by tuan_id asc,ptime asc");
    if ($status == '0') {
        $str = '单独购买待派送订单_' . time();
    }
    if ($status == '1') {
        $str = '团购成功待派送订单_' . time();
    }
    if ($status == '') {
        $str = '全部待派送订单' . time();
    }
    if ($godluck == 1) {
        $str = '中奖待派送订单' . time();
    }
    /* 输入到CSV文件 */
    $html = "\xEF\xBB\xBF";
    /* 输出表头 */
    $filter = array('aa' => '订单编号', 'sj' => '所属商家', 'll' => '团ID', 'bb' => '姓名', 'cc' => '电话', 'm2' => '运费', 'dd' => '总价(元)', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'pp' => '商品规格', 'oo' => '购买数量', 'mm' => '买家留言', 'h1' => '省', 'h2' => '市', 'h3' => '区', 'h4' => '详细地址', 'm6' => '地址类型', 'm7' => '商家备注', 'ii' => '微信订单号', 'jj' => '快递单号', 'ps' => '派送员标志id', 'kk' => '快递/派送公司名称', 'm1' => '配送方式', 'mz' => '组团成功时间');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    $html .= "\n";
    //message($condition);
    foreach ($orders as $k => $v) {

        $thistatus = '待发货';
        $time = date('Y-m-d H:i:s', $v['createtime']);
        $options = pdo_fetch("SELECT title,productprice,marketprice FROM " . tablename("tg_goods_option") . " WHERE id=:id LIMIT 1", array(":id" => $v['optionid']));
        $mermber = member_get_by_params(" openid='{$v['openid']}' ");
        if (empty($options['title'])) {
            $options['title'] = '不限';
        }
        if ($v['dispatchtype'] == 0) {
            $disname = '无';
        }
        if ($v['dispatchtype'] == 1) {
            $disname = '送货上门';
        }
        if ($v['dispatchtype'] == 2) {
            $disname = '快递';
        }
        if ($v['dispatchtype'] == 3) {
            $disname = '自提';

        }
        $add = pdo_fetch("select * from" . tablename('tg_address') . "where id = '{$v['addressid']}' ");
        if ($v['addresstype'] == 1) {
            $addresstype = '公司';
        } else {
            $addresstype = '家庭';
        }
        $gname = "";
        $gopname = "";
        if ($v['g_id'] > 0) {
            $gname = $v['goodsname'];
            $gopname = $v['optionname'];
            $orders[$k]['aa'] = $v['orderno'];
            $m = $v['merchantid'];
            if ($m == 0) {
                $orders[$k]['merchant_name'] = $_W['account']['name'];
            } else {
                $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                $orders[$k]['merchant_name'] = $name['name'];
            }
            $orders[$k]['sj'] = $orders[$k]['merchant_name'];
            $orders[$k]['ll'] = $v['tuan_id'];

            $orders[$k]['bb'] = $v['addname'];
            $orders[$k]['cc'] = $v['mobile'];
            $orders[$k]['m2'] = $v['freight'];
            $orders[$k]['dd'] = $v['pay_price'];
            $orders[$k]['ee'] = $thistatus;
            $orders[$k]['ff'] = $time;
            $orders[$k]['gg'] = $gname;
            $orders[$k]['pp'] = $gopname;
            $orders[$k]['oo'] = $v['gnum'];
            $orders[$k]['mm'] = $v['remark'];
            if (!empty($v['province'])) {
                $orders[$k]['h1'] = $v['province'];
                $orders[$k]['h2'] = $v['city'];
                $orders[$k]['h3'] = $v['county'];
                $orders[$k]['h4'] = $v['detailed_address'];
            } else {
                $orders[$k]['h1'] = $add['province'];
                $orders[$k]['h2'] = $add['city'];
                $orders[$k]['h3'] = $add['county'];
                $orders[$k]['h4'] = trim($add['detailed_address']);
            }

            $orders[$k]['m6'] = $addresstype;
            $orders[$k]['m7'] = $v['buyremark'];
            $orders[$k]['hh'] = $v['address'];
            $orders[$k]['ii'] = $v['transid'];
            $orders[$k]['jj'] = $v['expresssn'];
            $orders[$k]['ps'] = $v['storeid'];
            $orders[$k]['kk'] = $v['express'];
            $orders[$k]['m1'] = $disname;


            if (!empty($v['successtime'])) {
                $v['successtime'] = date('Y-m-d H:i:s', $v['successtime']);
            }
            $orders[$k]['mz'] = $v['successtime'];

            foreach ($filter as $key => $title) {
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
        } else {
            $col = pdo_fetchall("SELECT * FROM " . tablename('tg_collect') . " WHERE  orderno=:orderno", array('orderno' => $v['orderno']));
            $orders[$k]['aa'] = $v['orderno'];
            $m = $v['merchantid'];
            if ($m == 0) {
                $orders[$k]['merchant_name'] = $_W['account']['name'];
            } else {
                $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                $orders[$k]['merchant_name'] = $name['name'];
            }
            $orders[$k]['sj'] = $orders[$k]['merchant_name'];
            $orders[$k]['ll'] = $v['tuan_id'];

            $orders[$k]['bb'] = $v['addname'];
            $orders[$k]['cc'] = $v['mobile'];
            $orders[$k]['m2'] = $v['freight'];
            $orders[$k]['dd'] = $v['price'];
            $orders[$k]['ee'] = $thistatus;
            $orders[$k]['ff'] = $time;
            $orders[$k]['gg'] = $gname;
            $orders[$k]['pp'] = $gopname;
            $orders[$k]['oo'] = $v['gnum'];
            $orders[$k]['mm'] = $v['remark'];
            if (!empty($v['province'])) {
                $orders[$k]['h1'] = $v['province'];
                $orders[$k]['h2'] = $v['city'];
                $orders[$k]['h3'] = $v['county'];
                $orders[$k]['h4'] = $v['detailed_address'];
            } else {
                $orders[$k]['h1'] = $add['province'];
                $orders[$k]['h2'] = $add['city'];
                $orders[$k]['h3'] = $add['county'];
                $orders[$k]['h4'] = trim($add['detailed_address']);
            }
            $orders[$k]['m6'] = $addresstype;
            $orders[$k]['m7'] = $v['buyremark'];
            $orders[$k]['hh'] = $v['address'];
            $orders[$k]['ii'] = $v['transid'];
            $orders[$k]['jj'] = $v['expresssn'];
            $orders[$k]['ps'] = $v['storeid'];
            $orders[$k]['kk'] = $v['express'];
            $orders[$k]['m1'] = $disname;


            if (!empty($v['successtime'])) {
                $v['successtime'] = date('Y-m-d H:i:s', $v['successtime']);
            }
            $orders[$k]['mz'] = $v['successtime'];

            foreach ($filter as $key => $title) {
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
            foreach ($col as $c => $cc) {
                $gs = pdo_fetch("SELECT gname FROM " . tablename('tg_goods') . " WHERE  id=:id", array('id' => $cc['sid']));
                $gname = $gs['gname'];
                $gopname = $cc['item'];
                $orders[$k]['aa'] = '';
                $m = $v['merchantid'];
                if ($m == 0) {
                    $orders[$k]['merchant_name'] = $_W['account']['name'];
                } else {
                    $name = pdo_fetch("select * from " . tablename('tg_merchant') . " where id = {$m}");
                    $orders[$k]['merchant_name'] = $name['name'];
                }
                $orders[$k]['sj'] = $orders[$k]['merchant_name'];
                $orders[$k]['ll'] = '';

                $orders[$k]['bb'] = '';
                $orders[$k]['cc'] = '';
                $orders[$k]['dd'] = $cc['oprice'];
                $orders[$k]['ee'] = '';
                $orders[$k]['ff'] = $time;
                $orders[$k]['gg'] = $gname;
                $orders[$k]['pp'] = $gopname;
                $orders[$k]['oo'] = $cc['num'];

                $orders[$k]['h1'] = '';
                $orders[$k]['h2'] = '';
                $orders[$k]['h3'] = '';
                $orders[$k]['h4'] = '';
//                $orders[$k]['hh'] = '';
                $orders[$k]['ii'] = '';
                $orders[$k]['jj'] = '';
                $orders[$k]['ps'] = '';
                $orders[$k]['kk'] = '';
                $orders[$k]['m1'] = '';
                $orders[$k]['m2'] = '';
                $orders[$k]['m6'] = '';
                $orders[$k]['m7'] = '';
                $orders[$k]['mz'] = '';
                $orders[$k]['mm'] = '';

                foreach ($filter as $key => $title) {
                    $html .= $orders[$k][$key] . "\t,";
                }
                $html .= "\n";
            }
        }


    }
    /* 输出CSV文件 */
    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename={$str}.csv");
    echo $html;
    exit();
}
include wl_template('order/batch_delivery');
exit();
?>