<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/6/6
 * Time: 11:39
 */
global $_W, $_GPC;
$acc=pdo_fetch('select * from '.tablename('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_GPC['i']));
$http_a='http:';
if($acc['is_https']==1)
{
    $http_a='https:';
}
$html = file_get_contents($http_a.$_GPC['shareurl']);
$str=preg_split ($pattern, $html);
echo $html;
exit();
