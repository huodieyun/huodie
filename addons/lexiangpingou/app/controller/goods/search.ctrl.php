<?php
/**
 * Created by 火蝶.
 * User: 蚂蚁
 * Date: 2017/3/31
 * Time: 15:37
 */
defined('IN_IA') or exit('Access Denied');

$pagetitle = !empty($config['tginfo']['sname']) ? $config['tginfo']['sname'] : '乐享拼购';

$config['tginfo']['slogo'] = tomedia($config['tginfo']['slogo']);

include wl_template('goods/search');
exit();