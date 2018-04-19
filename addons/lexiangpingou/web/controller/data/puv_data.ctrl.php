<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/4/7
 * Time: 13:28
 */
global $_W, $_GPC;
$ops = array('sczhl_count_data');
if ($op == 'sczhl_count_data') {
    $uniacid = $_W['uniacid'];
    $etime = strtotime($_GPC['etime']) + 60 * 60 * 24;
    $stime = strtotime($_GPC['stime']);

    $pv = pdo_fetch('select sum(hour_0) as hour_0,sum(hour_1) as hour_1,sum(hour_2) as hour_2,sum(hour_3) as hour_3,sum(hour_4) as hour_4,sum(hour_5) as hour_5,sum(hour_6) as hour_6,sum(hour_7) as hour_7,sum(hour_8) as hour_8,sum(hour_9) as hour_9,sum(hour_10) as hour_10,sum(hour_11) as hour_11,sum(hour_12) as hour_12,sum(hour_13) as hour_13,sum(hour_14) as hour_14,sum(hour_15) as hour_15,sum(hour_16) as hour_16,sum(hour_17) as hour_17,sum(hour_18) as hour_18,sum(hour_19) as hour_19,sum(hour_20) as hour_20,sum(hour_21) as hour_21,sum(hour_22) as hour_22,sum(hour_23) as hour_23  from ' . tablename('tg_data_pv') . " where uniacid = :uniacid and `addtime` >= {$stime} AND `addtime` <= {$etime} order by addtime asc ", array(':uniacid' => $uniacid));
    $uv = pdo_fetch('select sum(hour_0) as hour_0,sum(hour_1) as hour_1,sum(hour_2) as hour_2,sum(hour_3) as hour_3,sum(hour_4) as hour_4,sum(hour_5) as hour_5,sum(hour_6) as hour_6,sum(hour_7) as hour_7,sum(hour_8) as hour_8,sum(hour_9) as hour_9,sum(hour_10) as hour_10,sum(hour_11) as hour_11,sum(hour_12) as hour_12,sum(hour_13) as hour_13,sum(hour_14) as hour_14,sum(hour_15) as hour_15,sum(hour_16) as hour_16,sum(hour_17) as hour_17,sum(hour_18) as hour_18,sum(hour_19) as hour_19,sum(hour_20) as hour_20,sum(hour_21) as hour_21,sum(hour_22) as hour_22,sum(hour_23) as hour_23 from ' . tablename('tg_data_uv') . " where uniacid = :uniacid and `addtime` >= {$stime} AND `addtime` <= {$etime} order by addtime asc ", array(':uniacid' => $uniacid));

    $data = array();
    $list = array();
    for($i=0;$i<=23;$i++){
        $dayhour = 'hour_' . $i;
        $data['addtime'] =$i;
        $data['sczhl']=floatval($pv[$dayhour]);
        $data['xdzhl']=floatval($uv[$dayhour]);
        $list['data'][$i] = $data;
    }
    die(json_encode($list));

}

include wl_template('data/puv_data');
exit();
