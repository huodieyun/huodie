<?php 
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$directory_f = pdo_fetchall("select id,first_directory,sort from" . tablename('workbook_directory_first') . "  order by  sort desc , id asc",array());
foreach($directory_f as &$f){

	$s = pdo_fetchall("select id,second_directory,sort from" . tablename('workbook_directory_second') . " where first_id = :first_id  order by  sort desc , id asc ",array(':first_id' =>$f['id']));
	$f['second'] = $s;
	
}
if($op == 'add_first'){
	$data['first_directory'] = $_GPC['first_directory'];
	
	$storage = pdo_insert('workbook_directory_first', $data);
	$tip='添加成功';
	echo "<script>alert('".$tip."');location.href='".web_url('service/workbook_submit',array('op' =>'display'))."';</script>";
	exit();
}
if($op == 'add_second'){
	$data['first_id'] = $_GPC['first_id'];
	$data['second_directory'] = $_GPC['second_directory'];
	
	$storage = pdo_insert('workbook_directory_second', $data);
	$tip='添加成功';
	echo "<script>alert('".$tip."');location.href='".web_url('service/workbook_submit',array('op' =>'display'))."';</script>";
	exit();
}
if($op == 'delete_first'){
	
	pdo_query("delete from " . tablename('workbook_directory_first') . " where id= '{$_GPC['id']}'");
	echo "<script>location.href='".web_url('service/workbook_submit',array('op' =>'display'))."';</script>";
	exit();
}
if($op == 'delete_second'){

	pdo_query("delete from " . tablename('workbook_directory_second') . " where id= '{$_GPC['id']}'");
	echo "<script>location.href='".web_url('service/workbook_submit',array('op' =>'display'))."';</script>";
	exit();
}
if($op =='edit'){
	$content = pdo_fetch("select id,second_directory,content from".tablename('workbook_directory_second')."where id = :id ",array(':id' => $_GPC['id']));
}
if($op =='edit_first'){
	$first = pdo_fetch("select id,first_directory,content from".tablename('workbook_directory_first')."where id = :id ",array(':id' => $_GPC['id']));
}
if($op =='publish'){
	$sid = $_GPC['id'];
	$new_content = $_GPC['content'];
	pdo_update("workbook_directory_second",array('content'=>$new_content),array('id'=>$sid));
	$tip='页面内容已更新';
	echo "<script>alert('".$tip."');location.href='".web_url('service/workbook_submit',array('op' =>'display'))."';</script>";
	exit();
}
if($op =='publish_first'){
	$sid = $_GPC['id'];
	$new_content = $_GPC['content'];
	pdo_update("workbook_directory_first",array('content'=>$new_content),array('id'=>$sid));
	$tip='页面内容已更新';
	echo "<script>alert('".$tip."');location.href='".web_url('service/workbook_submit',array('op' =>'display'))."';</script>";
	exit();
}
if($op =='sort_first'){
	$id = $_GPC['id'];
	$sort = $_GPC['sort'];
	$result_sort_f = pdo_update('workbook_directory_first', array('sort' => $sort), array('id' => $id));
	die(json_encode($result_sort_f));
}
if($op =='sort_second'){
	$id = $_GPC['id'];
	$sort = $_GPC['sort'];
	$result_sort_s = pdo_update('workbook_directory_second', array('sort' => $sort), array('id' => $id));
	die(json_encode($result_sort_s));
}
if($op =='changename_first'){
	$id = $_GPC['id'];
	$name = $_GPC['name'];
	$result_name_f = pdo_update('workbook_directory_first', array('first_directory' => $name), array('id' => $id));
	die(json_encode($result_name_f));
}
if($op =='changename_second'){
	$id = $_GPC['id'];
	$name = $_GPC['name'];
	$result_name_s = pdo_update('workbook_directory_second', array('second_directory' => $name), array('id' => $id));
	die(json_encode($result_name_s));
}
if($op =='change_father'){
	$id = $_GPC['id'];
	$fid = $_GPC['fid'];
	$result_name_s = pdo_update('workbook_directory_second', array('first_id' => $fid), array('id' => $id));
	die(json_encode($result_name_s));
}
include wl_template('service/workbook_submit');
exit();