<?php
/**
 * [huodieyun System] Copyright (c) 2015 lexiangpingou.CN
 * huodieyun is NOT a free software, it under the license terms, visited http://www.lexiangpingou.cn/ for more details.
 */
require './framework/bootstrap.inc.php';
$host = $_SERVER['HTTP_HOST'];
$clientip = $_W['clientip'];
if (!empty($host)) {
    $bindhost = pdo_fetch("SELECT * FROM " . tablename('site_multi') . " WHERE bindhost = :bindhost", array(':bindhost' => $host));
    if (!empty($bindhost)) {
        header("Location: " . $_W['siteroot'] . 'app/index.php?i=' . $bindhost['uniacid'] . '&t=' . $bindhost['id']);
        exit;
    }
}
if ($_W['os'] == 'mobile' && (!empty($_GPC['i']) || !empty($_SERVER['QUERY_STRING']))) {
    header('Location: ./app/index.php?' . $_SERVER['QUERY_STRING']);
} else {
    //echo "<script>alert('".$host."');</script>";
    //	exit;
    $istrue = false;
//    if ($host == 'www.xinxianren.cc' && $_SERVER['REQUEST_URI'] == '/') {
//        header('Location: ./gw/index.php?i=779');
//        exit;
//    }

    if (($host == 'www.pinhaochi.com' || $host == 'www.pinhaochi.net' || $host == 'www.pinhaochi.cn') && $_SERVER['REQUEST_URI'] == '/') {
        header('Location: ./gw/index.php?i=1743');
        exit;
    }

    if ($host == 'w9.huodiesoft.com') {
        $istrue = true;
    }
    if ($host == 'pt.huodiesoft.com') {
        echo "<script>window.location.href='http://www.lexiangpingou.cn';</script>";
        exit();

    }
    if ($host == 'www.huodiesoft.com') {
        $istrue = true;
    }
    if ($host == 'www.lexiangpingou.cn') {
        $istrue = true;
    }
    if ($host == 'www.afcme.cn') {
        $istrue = true;
    }
    if ($host == 'www.huodieyun.top') {
        $istrue = true;
    }
    if ($host == 'www.huodieyun.com') {
        $istrue = true;
    }
    if ($host == 'www.huodieyun.cn') {
        $istrue = true;
    }
    if ($host == 'www.huodieyun.cc') {
        $istrue = true;
    }
    if ($host == 'www.huodieyun.net') {
        $istrue = true;
    }
    //$istrue=true;

    if (!$istrue) {
        die("<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                    <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                </head>
                <body>
                <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>请在微信客户端打开链接</h4></div></div></div>
                </body>
            </html>");

    } else {
        header('Location: ./web/index.php?' . $_SERVER['QUERY_STRING']);
    }


}