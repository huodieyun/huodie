<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/6/24
 * Time: 13:45
 */
global $_W, $_GPC;
if (!pdo_fieldexists('account_wechats', 'gwsjrz_image')) {
    pdo_query("ALTER TABLE " . tablename('account_wechats') . " ADD `gwsjrz_image` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '官网商家入驻背景图';");
}
$uniacid = pdo_fetch('select * from ' . tablename('account_wechats') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));

$uni_settings = pdo_fetch('select oauth from ' . tablename('uni_settings') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
$oauth = iunserializer($uni_settings['oauth']);
if (!empty($oauth['host'])) {
    $url = $oauth['host'] . '/web/index.php?c=user&a=shop_login&i=' . $_W['uniacid'];
} else {
    $url = "https://www.lexiangpingou.cn" . '/web/index.php?c=user&a=shop_login&i=' . $_W['uniacid'];
}
if (checksubmit()) {
    $uniacid = $_W['uniacid'];
    $data = array();
    $data['regbg'] = $_GPC['regbg'];
    $data['applycontent'] = $_GPC['applycontent'];
    $data['margin'] = $_GPC['margin'];
    $data['merchant_role'] = $_GPC['merchant_role'];
    $data['merchant_pay_time'] = $_GPC['merchant_pay_time'];
    $data['merchant_account_time'] = $_GPC['merchant_account_time'];
    $data['platform_logo'] = $_GPC['platform_logo'];
    $data['platform_title'] = $_GPC['platform_title'];
    $data['platform_background'] = $_GPC['platform_background'];
    $data['platform_style'] = $_GPC['platform_style'];
    $data['platform_left_img'] = $_GPC['platform_left_img'];
    $data['gwsjrz_image'] = $_GPC['gwsjrz_image'];
    pdo_update('account_wechats', $data, array('uniacid' => $uniacid));
    $tip = '保存成功';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('store/merchant_base') . "';</script>";
    exit;
}
include wl_template('store/merchant_base');
exit();
