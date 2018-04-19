<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/16
 * Time: 13:59
 */
global $_W,$_GPC;
$ops = array('output');
if ($op == 'output') {
    set_time_limit(0);
    $str = '团长佣金报表_' . time();
    $uniacid = $_W['uniacid'];
    $tuan_id = $_GPC['tuan_id'];
    $membername = $_GPC['membername'];
    $gname = $_GPC['gname'];
    if($tuan_id){
        $condition = " and a.tuan_id like '%{$tuan_id}%'";
    }
    if($membername){
        $condition =$condition. " and c.name like '%{$membername}%' ";
    }
    if($gname){
        $condition =$condition. " and b.gname like '%{$gname}%' ";
    }

    $now = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $before = strtotime($_GPC['stime']);
//    $now=1521250361;
//    $before=1514736000;

    $data = pdo_fetchall("SELECT a.uniacid,FROM_UNIXTIME(a.createtime) as createtime,a.tuan_id,a.success_num,a.commissiontype,a.commission,a.withdraw,b.gname,c.`name` from cm_tg_commander a LEFT JOIN cm_tg_goods b on a.gid=b.id  LEFT JOIN cm_tg_member c on a.openid=c.from_user LEFT JOIN cm_tg_group d on a.tuan_id=d.groupnumber  where d.groupstatus=2 and a.uniacid={$uniacid} and a.uniacid=b.uniacid and a.uniacid=c.uniacid and a.createtime>={$before} and a.createtime<={$now}  {$condition}  order by a.createtime desc");
    foreach ($data as $key=>$value){
        $order_num=pdo_fetch("SELECT count(*) as order_num from cm_tg_order where tuan_id={$value['tuan_id']} and uniacid={$uniacid} and status in (1,2,8,3) and mobile <>'虚拟'");
        $goods_num=pdo_fetch("SELECT sum(gnum) as goods_num ,sum(price) as order_price from cm_tg_order where tuan_id={$value['tuan_id']} and uniacid={$uniacid} and status in (1,2,8,3) and mobile <>'虚拟'");
        $data[$key]['order_num']=$order_num['order_num'];
        $data[$key]['goods_num']=$goods_num['goods_num'];
        $data[$key]['order_price']=round($goods_num['order_price'],2);

    }



    /* 输入到CSV文件 */
    $html = "\xEF\xBB\xBF";
    /* 输出表头 */
    $filter = array('aa' => '下单时间', 'bb' => '团号', 'cc' => '团长姓名', 'dd' => '商品名称', 'ee' => '团人数', 'ff' => '商品数量', 'gg' => '销售总额', 'hh' => '已签收人数', 'jj' => '已结算佣金');
    foreach ($filter as $key => $title) {
        $html .= $title . "\t,";
    }
    $html .= "\n";
    foreach ($data as $k => $v) {

        $orders[$k]['aa'] = $v['createtime'];
        $orders[$k]['bb'] = $v['tuan_id'];
        $orders[$k]['cc'] = $v['name'];
        $orders[$k]['dd'] = $v['gname'];
        $orders[$k]['ee'] = $v['order_num'];
        $orders[$k]['ff'] = $v['goods_num'];
        $orders[$k]['gg'] = $v['order_price'];
        $orders[$k]['hh'] = $v['success_num'];
        $orders[$k]['jj'] = $v['withdraw'];
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
include wl_template('data/commander_data');
exit();
