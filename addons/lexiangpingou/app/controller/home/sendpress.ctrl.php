<?php
error_reporting(0);

global $_W, $_GPC;
set_time_limit(0);
$order_list = pdo_fetchall('select orderno,gnum from' . tablename('tg_order') . 'where tuan_id=:tuan_id  and  status=8  order by id asc ', array(':tuan_id' => '874398'));
$i = 0;
foreach ($order_list as $item) {
    $data1 = array('orderno' => $item['orderno'], 'status' => -1, 'refundprice' => 4*$item['gnum']);
    pdo_insert('tg_order_level_refund', $data1);
    $i += 1;

}
echo $i;
exit;
?>
	 