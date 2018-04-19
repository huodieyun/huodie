<?php

$op = $_GPC['op'] ? $_GPC['op'] : 'display';
load()->func('tpl');
$uniacid = $_W['uniacid'];
$merchants = pdo_fetchall("SELECT * FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$_W['uniacid']}'  ORDER BY id DESC");
$allgoods = pdo_fetchall("select gname,id from " . tablename('tg_goods') . " where uniacid = :uniacid and merchantid = {$_W['user']['merchant_id']} ", array(':uniacid' => $_W['uniacid']));

if ($op == 'display') {

    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $condition = " b.uniacid = :weid";
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
        $condition .= " AND  b.createtime >= :starttime AND  b.createtime <= :endtime ";
        $paras[':starttime'] = $starttime;
        $paras[':endtime'] = $endtime;
    }
    $gid = intval($_GPC['goodsid2']);
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND b.orderno like '%{$_GPC['keyword']}%'";
    } elseif ($gid > 0) {
        $condition .= " and b.orderno in ( select orderno from " . tablename('tg_collect') . " where sid = {$gid} ) ";
    }
//    if (trim($_GPC['goodsid']) != '') {
//        $condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
//    }

    if (trim($_GPC['address']) != '') {

        $condition .= " and b.address like '%{$_GPC['address']}%' ";
    }


    if (!empty($_GPC['addresstype'])) {
        $condition .= " AND b.addresstype = {$_GPC['addresstype']} ";
    }
    if (!empty($_GPC['nickname'])) {
        $condition .= " AND b.openid in(select from_user from " . tablename('tg_member') . " where nickname LIKE '%{$_GPC['nickname']}%' and  uniacid='{$_W['uniacid']}')";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND (b.addname LIKE '%{$_GPC['member']}%' or b.mobile LIKE '%{$_GPC['member']}%')";
    }
        if ($status == 1) {
            $condition .= " AND b.is_partsend=2 ";
        } else {
            $condition .= " AND b.is_partsend  =1 ";
        }
    $godluck = $_GPC['godluck'];
    if (!empty($godluck)) {
        $condition .= " AND b.godluck={$_GPC['godluck']} ";
        $condition .= " AND b.status in (6,8) and b.expresssn is NULL";
    }
    if (empty($godluck)) {
        $condition .= " AND b.status in (6,8) and b.expresssn is NULL ";
    }
//    if ($_W['user']['merchant_id'] > 0) {
    $condition .= " and b.merchantid = {$_W['user']['merchant_id']} ";
    $condition .= ' and b.supply_goodsid = 0 and b.dispatchtype in (0,2) and b.g_id = 0 ';
//    }
    $sql = "SELECT * from cm_tg_collect a LEFT JOIN  cm_tg_order b on a.orderno=b.orderno where ".$condition." and b.mobile<>'虚拟' group by a.orderno HAVING count(a.orderno)>1 ORDER BY b.createtime DESC LIMIT ".($pindex - 1) * $psize . ',' . $psize;
//    print_r('sql');
//    die;
    //    $sql = "select  * from " . tablename('tg_order') . " where $condition  and mobile<>'虚拟' ORDER BY createtime DESC " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
    $list = pdo_fetchall($sql, $paras);
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
//    print_r('foreach');
//    die;
    $total_all = pdo_fetchall('SELECT COUNT(*) FROM ' . tablename('tg_collect') . " a LEFT JOIN cm_tg_order b on a.orderno=b.orderno WHERE b.uniacid = :weid AND b.is_partsend = 1 AND b.status in (6,8) and b.expresssn is NULL and b.merchantid = 0 and b.supply_goodsid = 0 and b.dispatchtype in (0,2) and b.g_id = 0 and b.mobile<>'虚拟'  group by a.orderno HAVING count(a.orderno)>1", $paras);
    $total_all = count($total_all);
    $total_is_partsend = pdo_fetchall('SELECT COUNT(*) FROM ' . tablename('tg_collect') . " a LEFT JOIN cm_tg_order b on a.orderno=b.orderno WHERE b.uniacid = :weid AND b.is_partsend =2 AND b.status in (6,8) and b.expresssn is NULL and b.merchantid = 0 and b.supply_goodsid = 0 and b.dispatchtype in (0,2) and b.g_id = 0 and b.mobile<>'虚拟' group by a.orderno HAVING count(a.orderno)>1", $paras);
    $total_is_partsend = count($total_is_partsend);
    $pager = pagination($total_all, $pindex, $psize);
}

if ($op == 'detail') {
    wl_load()->model('activity');
    wl_load()->model('goods');
    load()->model('mc');
    $province = pdo_getall('erp_area', array('level' => 1));
    $id = intval($_GPC['id']);
    unset($item);
    $item = pdo_fetch("SELECT * FROM " . tablename('tg_order') . " WHERE id = :id", array(':id' => $id));
    if (empty($item)) {
        message("抱歉，订单不存在!", referer(), "error");
    }

    $coll = pdo_fetch("select remind_times from " . tablename('tg_order_collect') . " where orderno = '{$item['orderno']}' and uniacid = '{$item['uniacid']}' and openid = '{$item['openid']}' ");
    $item['remind_times'] = intval($coll['remind_times']);
    $group = '';
    if ($item['pay_type'] != 9) {
        $group = ' GROUP BY refund_id ';
    }
    $rfee = pdo_fetchall("SELECT * FROM " . tablename('tg_refund_record') . " WHERE transid = :id AND status = 1 AND refund_id <> '' {$group} ", array(':id' => $item['transid']));
    $resultfee = 0;
    if (!empty($item['transid'])) {
        foreach ($rfee as $rf => $rforder) {
            $resultfee += $rforder['refundfee'];
        }
    }
    if ($item['bukuanstatus'] == 2) {
        $bukuan = pdo_fetch("select price from " . tablename('tg_order') . " where master_orderno = '{$item['orderno']}' AND status = 3 ");
        $item['price'] += $bukuan['price'];
    }
    $mfee = $item['price'] - $resultfee;

    $coupon_template = coupon_template($item['couponid']);
    $option = pdo_fetch("SELECT title,productprice,marketprice FROM " . tablename("tg_goods_option") . " WHERE id=:id LIMIT 1", array(":id" => $item['optionid']));
    if ($item['status'] == 7) {
        $refund_record = pdo_fetch("SELECT * FROM " . tablename('tg_refund_record') . " WHERE orderid = :id", array(':id' => $id));
    }

    unset($saler);
    if ($item['veropenid']) {
        $saler = pdo_fetch("select nickname from " . tablename('tg_member') . " where uniacid = '{$_W['uniacid']}' and from_user = '" . $item['veropenid'] . "'");
    }
    $uid = mc_openid2uid($item['openid']);
    $result = mc_fansinfo($uid, $_W['acid'], $_W['uniacid']);
    $item['fanid'] = $result['fanid'];
    $item['user'] = pdo_fetch("SELECT * FROM " . tablename('tg_address') . " WHERE id = {$item['addressid']}");
    $goods = goods_get_by_params(" id={$item['g_id']} ");
    $item['goods'] = $goods;
    if ($item['status'] == 4 || $item['status'] == 6 || $item['status'] == 7 || $item['status'] == 5) {
        $refund = pdo_fetch("select createtime from" . tablename('tg_refund_record') . "where orderid={$item['id']}");
        $refund_time = $refund['createtime'];
    }

    if ($item['dispatchtype'] == 1) {
        $delivery_man = pdo_fetchall("select * from " . tablename('tg_delivery_man') . " where uniacid = '{$_W['uniacid']}' and merchantid = '{$_W['user']['merchant_id']}' and status = 1 ");
    }

    if ($item['senddate'] == '0000-00-00') {
        $item['senddate'] = null;
    }
    if (!(strpos($item['sendtime'], '-') !== false)) {
        $item['sendtime'] = date('Y-m-d H:i', $item['sendtime']);
    }
    $store = pdo_fetch("select * from" . tablename('tg_store') . " where id ='" . $item['comadd'] . "'");
    $c_order = pdo_fetchall("SELECT * FROM ".tablename('tg_order_child')." where orderno='".$item['orderno']."'");
    include wl_template('order/partsend_detail');
    exit;
}

if ($op == 'confirmsend') {
    //验证是否全选商品

//    拆单默认快递
    $collect_id = $_GPC['collect_id'];
    foreach ($collect_id as $key=>$value){
        $r = pdo_fetch("SELECT * FROM ".tablename('tg_collect')." where id=".$value);
        if($r['is_send'] == 1){
            die;
        }
    }
    $s_order = pdo_fetch("SELECT * FROM ".tablename('tg_order')." where id=".$_GPC['id']);
    $count = pdo_fetchcolumn("SELECT count(*) FROM ".tablename('tg_order_child')." where orderno='".$s_order['orderno']."'");
    $s_order['c_orderno'] = $s_order['orderno'].'_'.($count+1);
    $s_order['express'] = $_GPC['express'];
    $s_order['expresssn'] = $_GPC['expresssn'];
    unset($s_order['id']);
    $s_order['status'] = 2;
    $table_field = pdo_fetchall('SHOW FIELDS FROM '.tablename('tg_order_child'));
    $fields = [];
    $fields_key = [];
    foreach ($table_field as $k=>$v){
        $fields[$v['Field']]=$v;
        $fields_key[] = $v['Field'];
    }
    foreach ($s_order as $key=>$value){
        if(!in_array($key,$fields_key)){
            unset($s_order[$key]);
        }
    }

    $rs = pdo_insert("tg_order_child",$s_order);
    $c_orderid = pdo_insertid();
    if(!$rs){
        echo 0;
        die;
    }else{
        //更新主订单
        pdo_update('tg_order',['status'=>8,'is_partsend'=>2],['orderno'=>$s_order['orderno']]);
        //
        $num = 0;
        $price = 0;
        foreach ($collect_id as $key=>$value){
//        子订单详情
            $g = pdo_fetch("SELECT * FROM ".tablename('tg_collect')." where id=".$value);
            $g['orderno'] = $s_order['c_orderno'];
            $price += $g['oprice']*$g['num'];
            $num += $g['num'];
            unset($g['id']);
            unset($g['is_send']);
            unset($g['com_type']);
//            无效字段筛选

                $table_field = pdo_fetchall('SHOW FIELDS FROM '.tablename('tg_child_collect'));
                $fields = [];
                $fields_key = [];
                foreach ($table_field as $k=>$v){
                    $fields[$v['Field']]=$v;
                    $fields_key[] = $v['Field'];
                }
                foreach ($g as $kk=>$vv){
                    if(!in_array($kk,$fields_key)){
                        unset($g[$kk]);
                    }
                }
            $r = pdo_insert('tg_child_collect',$g);
            if(!$r){
                echo 0;
                die;
            }
            //更新状态为已拆单发货
            pdo_update('tg_collect',['is_send'=>1],['id'=>$value]);
        }
        pdo_update('tg_order_child',['price'=>$price,'goodsprice'=>$price],['id'=>$c_orderid]);
        $s_gs = pdo_fetchall("SELECT * FROM ".tablename('tg_collect')." where orderno='".$s_order['orderno']."'");
        $up_flag = true;
        foreach ($s_gs as $key=>$value){
            if($value['is_send']==0){
                $up_flag = false;
            }
        }
        if($up_flag){
            //更新
            pdo_update('tg_order',['status'=>2,'is_partsend'=>2],['orderno'=>$s_order['orderno']]);
        }
        $url = app_url('order/order/childdetail', array('id' => $c_orderid));
        send_success($s_order['c_orderno'],$s_order['openid'],$_GPC['express'],$_GPC['expresssn'],$url);
        echo 1;
        die;
    }
}

if($op == 'children'){
    //子订单列表
    $s_id = $_GPC['id'];//主订单ID
    $item = pdo_fetch("SELECT * FROM ".tablename('tg_order')." where id=".$s_id);
    $c_order = pdo_fetchall("SELECT * FROM ".tablename('tg_order_child')." where orderno='".$item['orderno']."'");
    include wl_template('order/partsend_children');
    exit;
}
if($op == 'childdetail'){
    //子订单详情
    $id = $_GPC['id'];//子订单ID
    $item = pdo_fetch("SELECT * FROM ".tablename('tg_order_child')." where id=".$id);
    include wl_template('order/partsend_childdetail');
    exit;
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
                message("导入文件太大");
                exit;
            }
            if ($ftype == 'csv') {
                if (empty ($uploadfile)) {
                    message("请选择要导入的CSV文件!");
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
                    message("没有任何数据!");
                    exit;
                }
                $succ_result = 0;
                $error_result = 0;
                for ($i = 1; $i < $len_result; $i++) { //循环获取各字段值
                    $orderNo = trim(iconv('gb2312', 'utf-8', $result[$i][0])); //中文转码
                    if ($orderNo == '') {
                        continue;
                    }

                    $expressOrder = trim(iconv('gb2312', 'utf-8', $result[$i][20]));
                    $expressName = trim(iconv('gb2312', 'utf-8', $result[$i][22]));

                    if (!empty($expressOrder) && !empty($expressName)) {
                        $order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderNo}'");

                        if ($order['dispatchtype'] == 2 || $order['dispatchtype'] == 0) {
                            $res = pdo_update('tg_order', array('status' => 2, 'sendepressmessage' => 1, 'express' => $expressName, 'expresssn' => $expressOrder, 'delivery_time' => TIMESTAMP), array('orderno' => $orderNo, 'status' => 8));
                            if ($res) {
                                $url = app_url('order/order/detail', array('id' => $order['id']));
                                //send_success($order['orderno'], $order['openid'], $expressName, $orderNo, $url);
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
    message('导入发货订单操作成功！成功' . $succ_result . '条，失败' . $error_result . '条', referer(), 'success');
}

if ($op == 'output') {
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
        $condition .= " AND status in (6,8) and dispatchtype<>3";
    }
    $gid = intval($_GPC['goodsid2']);
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND orderno like '%{$_GPC['keyword']}%'";
    } elseif ($gid > 0) {
        $condition .= " and orderno in ( select orderno from " . tablename('tg_collect') . " where sid = {$gid} ) ";
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
    if ($keyword != '') {
        $condition .= " AND g_id = '{$keyword}'";
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
    $condition .= " AND merchantid = '{$_W['user']['merchant_id']}' ";
    $condition .= ' and supply_goodsid = 0 and dispatchtype in (0,2) and g_id = 0 ';
//	$orders = pdo_fetchall("select * from" . tablename('tg_order') . "where $condition and status=2");
    $orders = pdo_fetchall("select * from " . tablename('tg_order') . " where $condition and mobile<>'虚拟' and expresssn is NULL order by tuan_id asc,ptime asc");
    if ($status == '0') {
        $str = '单独购买待发货订单_' . time();
    }
    if ($status == '1') {
        $str = '团购成功待发货订单_' . time();
    }
    if ($status == '') {
        $str = '全部待发货订单' . time();
    }
    if ($godluck == 1) {
        $str = '中奖待发货订单' . time();
    }
    /* 输入到CSV文件 */
    $html = "\xEF\xBB\xBF";
    /* 输出表头 */
    $filter = array('aa' => '订单编号', 'sj' => '所属商家', 'll' => '团ID', 'bb' => '姓名', 'cc' => '电话', 'm2' => '运费', 'dd' => '总价(元)', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'pp' => '商品规格', 'oo' => '购买数量', 'mm' => '买家留言', 'h1' => '省', 'h2' => '市', 'h3' => '区', 'h4' => '详细地址', 'm6' => '地址类型', 'm7' => '商家备注', 'ii' => '微信订单号', 'jj' => '快递单号', 'ps' => '派送员标志id', 'kk' => '快递名称', 'm1' => '配送方式', 'mz' => '组团成功时间');
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

include wl_template('order/partsend');
exit();
?>
