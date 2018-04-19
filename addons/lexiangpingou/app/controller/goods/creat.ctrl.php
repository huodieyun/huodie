<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * goods.ctrl
 * ��Ʒ���������
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('goods');
wl_load()->model('merchant');
wl_load()->model('order');
wl_load()->model('account');
wl_load()->model('activity');
wl_load()->model('setting');
load()->func('communication');
wl_load()->model('functions');
wl_load()->classs('qrcode');
$autoorder = pdo_fetch('select orderno from' . tablename('tg_order') . "  WHERE status in (1,8) and hexiaoma<>'' and adminremark<>'set1' and uniacid=:uniacid  limit 1", array(':uniacid' => $_W['uniacid']));
if (empty($autoorder)) {
    message('执行成功');
} else {
    pdo_update('tg_order', array('adminremark' => 'set1'), array('orderno' => $autoorder['orderno']));
//	$createqrcode =  new creat_qrcode();
//	$createqrcode->creategroupQrcode($autoorder['orderno']);
    echo("<script type=\"text/javascript\">");
    echo("function fresh_page()");
    echo("{");
    echo("window.location.reload();");
    echo("}");
    echo("setTimeout('fresh_page()',1000);");
    echo("</script>");
}


?>