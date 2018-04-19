<?php 

defined('IN_IA') or exit('Access Denied');

$ops = array('ajax','list', 'create', 'edit', 'delete');
$op_names = array('设置开启','代付留言列表', '创建留言', '编辑留言','删除留言');
foreach($ops as$key=>$value){
	permissions('do', 'ac', 'op', 'application', 'helpbuy', $ops[$key], '应用与营销', '代付管理', $op_names[$key]);
}
$op = in_array($op, $ops) ? $op : 'status';
wl_load()->model('setting');
//权限控制
$tid=8170;
//权限控制
wl_load()->model('functions');
$checkfunction=checkfunc(8170);
$_W['page']['title'] = $checkfunction['name'];
include wl_template('store/copyright');
exit();