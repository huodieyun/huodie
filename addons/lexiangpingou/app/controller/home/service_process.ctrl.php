<?php

defined('IN_IA') or exit('Access Denied');

global $_W,$_GPC;

wl_load()->func('message');

$message = pdo_fetchall("select * from " .tablename('tg_service_process')." order by id limit 10 ");

foreach ($message as $item) {
    m_service_process($item['openid'] , $item['first'] , $item['keyword1'],$item['keyword2'],$item['keyword3'],$item['keyword4'],$item['remark']);
    pdo_delete('tg_service_process' , array('id' => $item['id']));

}

exit;

