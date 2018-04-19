<?php 
load()->func('tpl');
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'base';
$_W['page']['title'] = '自定义设置';
wl_load()->model('setting');
//权限控制
$tid=8154;
//权限控制
wl_load()->model('functions');
$checkfunction=checkfunc(8154);
$setting=setting_get_by_name("autoaddress");

if ($operation == 'ajax') {
	if(empty($setting)){
		$value = array('autoaddress'=>1);
		$data = array(
			'uniacid'=>$_W['uniacid'],
			'key'=>'autoaddress',
			'value'=>serialize($value)
		);
		setting_insert($data);
		die(json_encode(array('errno'=>0,'message'=>"保存成功")));
	}else{
		$status = $setting['autoaddress']==1?2:1;
		setting_update_by_params(array('value'=>serialize(array('autoaddress'=>$status))), array('key'=>'autoaddress','uniacid'=>$_W['uniacid']));
		die(json_encode(array('errno'=>0,'message'=>"保存成功")));
	}
}
if($operation == 'base'){
	if (checksubmit('submit')&&$checkfunction) {
		$autoaddr=$_GPC['autoaddr'];
		$addrtype=$_GPC['addrtype'];
		$value = array('autoaddr'=>intval($autoaddr),'addrtype'=>intval($addrtype));
			if(empty($setting)){
		
		$data = array(
			'uniacid'=>$_W['uniacid'],
			'key'=>'autoaddress',
			'value'=>serialize($value)
		);
		setting_insert($data);
			
		}else{			
			setting_update_by_params(array('value'=>serialize($value)), array('key'=>'autoaddress','uniacid'=>$_W['uniacid']));
			
		}
		echo "<script>alert('保存成功');location.href='".web_url('store/autoaddress')."';</script>";
		exit;
				
}
	include wl_template('store/autoaddress');
}
if ($operation == 'display') {			
			$children = array();
			$autoaddress = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = '{$_W['uniacid']}'  and parentid=0");
			foreach ($autoaddress as $index => $row) {
				$data['level']=$row['level'];
				$data['id']=$row['id'];
				$data['enabled']=$row['enabled'];
				$data['parentid']=$row['parentid'];
				$data['name']=$row['name'];
				$list[]=$data;
				$addr = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = '{$_W['uniacid']}'  and parentid={$row['id']}");
				foreach ($addr as $indexb => $rowb) {
					$datab['level']=$rowb['level'];
					$datab['id']=$rowb['id'];
					$datab['enabled']=$rowb['enabled'];
					$datab['name']=$rowb['name'];
					$datab['parentid']=$rowb['parentid'];
					$list[]=$datab;
					$addr2 = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = '{$_W['uniacid']}'  and parentid={$rowb['id']}");
				foreach ($addr2 as $indexb2 => $rowb2) {
					$datab2['level']=$rowb2['level'];
					$datab2['id']=$rowb2['id'];
					$datab2['enabled']=$rowb2['enabled'];
					$datab2['name']=$rowb2['name'];
					$datab2['parentid']=$rowb2['parentid'];
					$list[]=$datab2;
				}
				}
			}
			
include wl_template('store/autoaddress');
			
		} elseif ($operation == 'post') {
			$parentid = intval($_GPC['parentid']);
			$id = intval($_GPC['id']);
			
			if($id!=0)
			{
				$auto = pdo_fetch("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = '{$_W['uniacid']}'  and id='{$id}'");
			}
			
			$autoaddress = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = '{$_W['uniacid']}'  and parentid=0");
			foreach ($autoaddress as $index => $row) {
				$data['level']=$row['level'];
				$data['id']=$row['id'];
				$data['parentid']=$row['parentid'];
				$data['name']=$row['name'];
				$list[]=$data;
				$addr = pdo_fetchall("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = '{$_W['uniacid']}'  and parentid={$row['id']}");
				foreach ($addr as $indexb => $rowb) {
					$datab['level']=$rowb['level'];
					$datab['id']=$rowb['id'];
					$datab['name']=$rowb['name'];
					$datab['parentid']=$rowb['parentid'];
					$list[]=$datab;
				}
			}
			
			if (checksubmit('submit')) {
				if (empty($_GPC['catename'])) {
					message('抱歉，请输入区域名称！');
				}
				
				$pid=0;
				$lev=0;
				if($_GPC['mode']==2)
				{
					$pid=$_GPC['provice2'];
					
					$paddr = pdo_fetch("SELECT * FROM " . tablename('tg_provice') . " WHERE weid = '{$_W['uniacid']}'  and id='{$pid}'");
					$lev=$paddr['level'];
				}
				$data = array(
					'weid' => $_W['uniacid'],
					'parentid' => $pid,
					'name' => $_GPC['catename'],
					'level' => $lev+1,
					'enabled' => $_GPC['enabled']
				);
				if (!empty($id)) {
					unset($data['parentid']);
					pdo_update('tg_provice', $data, array('id' => $id));
					
				} else {
					pdo_insert('tg_provice', $data);
					$id = pdo_insertid();
				}
		echo "<script>alert('更新区域成功！');location.href='".web_url('store/autoaddress',array('op'=>'display'))."';</script>";
		exit;
				
			}
			include wl_template('store/autoaddress');
			
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$autoaddress = pdo_fetch("SELECT id FROM " . tablename('tg_provice') . " WHERE id = '$id'");
			if (empty($autoaddress)) {
				echo "<script>alert('抱歉，区域不存在或是已经被删除！');location.href='".web_url('store/autoaddress',array('op'=>'display'))."';</script>";
		exit;
				
			}
			pdo_delete('tg_provice', array('id' => $id), 'OR');
		
			echo "<script>alert('区域删除成功！');location.href='".web_url('store/autoaddress',array('op'=>'display'))."';</script>";
		exit;
		}
		exit();