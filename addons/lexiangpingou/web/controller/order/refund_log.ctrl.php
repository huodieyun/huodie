<?php

$op = $_GPC["op"] ;
if(empty($op)){
    $op= 'display';
}
//message($_GPC["op"]);
if($op=='display'){
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $condition = "  uniacid = :uniacid";
    $paras = array(':uniacid' => $_W['uniacid']);

    $goodsid = $_GPC['goodsid'];
    $transid = $_GPC['transid'];
    $pay_type = $_GPC['pay_type'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];

    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND  createtime >= :starttime AND  createtime <= :endtime ";
        $paras[':starttime'] = $starttime;
        $paras[':endtime'] = $endtime;
    }
    if (!empty($_GPC['transid'])) {

        $condition .= " AND  transid =  '{$_GPC['transid']}'";
    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND  transid in (select transid from cm_tg_order where orderno LIKE '%{$_GPC['keyword']}%' and uniacid='{$_W['uniacid']}') ";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND (refundername LIKE '%{$_GPC['member']}%' or refundermobile LIKE '%{$_GPC['member']}%')";
    }
    if ($goodsid != '') {
        $condition .= " AND  goodsid = '" . intval($goodsid) . "'";
    }
    //message($condition);
    $sql = "select  * from " . tablename('tg_refund_record') . " where $condition group by refund_id ORDER BY createtime DESC  " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
    $list = pdo_fetchall($sql, $paras);
    $sql_1="select  COUNT(id) from " . tablename('tg_refund_record') . " where $condition group by refund_id ORDER BY createtime DESC  ";
    $total=pdo_query($sql_1,$paras);
    //	$total = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('tg_refund_record') . " WHERE $condition group by refund_id", $paras);
    $pager = pagination($total, $pindex, $psize);
    // var_dump($total);
    include wl_template('order/refund_log');
}
if($op == 'output'){
    $condition = "  uniacid = :uniacid";
    $paras = array(':uniacid' => $_W['uniacid']);

    $goodsid = $_GPC['goodsid'];
    $transid = $_GPC['transid'];
    $pay_type = $_GPC['pay_type'];
    $keyword = $_GPC['keyword'];
    $member = $_GPC['member'];
    $time = $_GPC['time'];

    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND  createtime >= :starttime AND  createtime <= :endtime ";
        $paras[':starttime'] = $starttime;
        $paras[':endtime'] = $endtime;
    }
    if (!empty($_GPC['transid'])) {

        $condition .= " AND  transid =  '{$_GPC['transid']}'";
    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND  transid in (select transid from cm_tg_order where orderno LIKE '%{$_GPC['keyword']}%' and uniacid='{$_W['uniacid']}') ";
    }
    if (!empty($_GPC['member'])) {
        $condition .= " AND (refundername LIKE '%{$_GPC['member']}%' or refundermobile LIKE '%{$_GPC['member']}%')";
    }
    if ($goodsid != '') {
        $condition .= " AND  goodsid = '" . intval($goodsid) . "'";
    }
    $log_sql =  "select  * from " . tablename('tg_refund_record') . " where $condition group by refund_id ORDER BY createtime DESC  ";
    $log_list = pdo_fetchall($log_sql,$paras);
    // echo "<pre>";
    // var_dump($log_list);
    // die();
    $html = "\xEF\xBB\xBF";
    $filter = array('aa' => '系统订单', 'bb' => '姓名', 'cc' => '电话', 'dd' => '退款金额', 'ee' => '状态', 'gg' => '商品名称', 'hh' => '支付金额', 'ii' => '微信订单号','mm'=>'退款时间',"nn"=> "支付时间",'tt'=>'支付方式');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    $html .= "\n";
    foreach ($log_list as $k => $v) {
        if ($v['type'] == 1) $thisstatus = '已退款';
        $goods = pdo_fetch("select gname from ".tablename("tg_goods")." where id=:id",array(":id"=>$v['g_id']));
        //model_goods::getSingleGoods($v['g_id'], 'gname');
        $time = date('Y-m-d H:i:s', $v['createtime']);


        $log_list[$k]['aa'] = $v['orderno'];
        $log_list[$k]['bb'] = $v['refundername'];
        $log_list[$k]['cc'] = $v['refundermobile'];
        $log_list[$k]['dd'] = $v['refundfee'];
        $log_list[$k]['ee'] = $thisstatus;
        $log_list[$k]['gg'] = $v['goodsname'];
        $log_list[$k]['hh'] = $v['payfee'];
        $log_list[$k]['ii'] =$v['transid'];
        $res = pdo_fetch("select * from ".tablename("tg_order")." where orderno = :orderno",array(":orderno"=>$v['orderno']));
        $log_list[$k]['mm'] = date("Y-m-d H:i:s",$v['createtime']);
        $log_list[$k]['nn'] = date("Y-m-d H:i:s",$res['ptime']);
        $log_list[$k]['tt'] = "微信支付";
        foreach ($filter as $key => $title) {
            $html .= $log_list[$k][$key] . "\t,";
        }
        $html .= "\n";
    }
    $str = date("YmdHis",time());
    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename={$str}.csv");
    echo $html;
    exit();
}
exit();