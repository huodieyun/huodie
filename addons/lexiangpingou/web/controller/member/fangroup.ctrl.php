<?php
/**
 * [WeEngine System] Copyright (c) 2014 lexiangpingou.cn
 * WeEngine is NOT a free software, it under the license terms, visited http://www.lexiangpingou.cn/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
//uni_user_permission_check('mc_fangroup');
$dos = array('post', 'display', 'del');
$op = !empty($_GPC['op']) && in_array($op, $dos) ? $op : 'display';

if($op == 'display') {
	$account = WeAccount::create($_W['acid']);
	$groups = $account->fetchFansGroups();
	if(is_error($groups)) {
		//message($groups['message'], url('home/welcome/mc'), 'error');
		echo "<script>alert('".$groups['message']."');location.href='".url('account/post',array('uniacid'=>$_W['uniacid']))."';</script>";
		exit;
	} else {
		$exist = pdo_fetch('SELECT * FROM ' . tablename('mc_fans_groups') . ' WHERE uniacid = :uniacid AND acid = :acid', array(':uniacid' => $_W['uniacid'], ':acid' => $_W['acid']));
		if(empty($exist)) {
			if(!empty($groups['groups'])) {
				$groups_tmp = array();
				foreach($groups['groups'] as $da) {
					$groups_tmp[$da['id']] = $da;
				}
				$data = array('acid' => $_W['acid'], 'uniacid' => $_W['uniacid'], 'groups' => iserializer($groups_tmp));
				pdo_insert('mc_fans_groups', $data);
			}
		} else {
			if(!empty($groups['groups'])) {
				$groups_tmp = array();
				foreach($groups['groups'] as $da) {
					$groups_tmp[$da['id']] = $da;
				}
				$data = array('groups' => iserializer($groups_tmp));
				pdo_update('mc_fans_groups', $data, array('uniacid' => $_W['uniacid'], 'acid' => $_W['acid']));
			}
		}
	}
}

if($op == 'post') {
	
	$account = WeAccount::create($_W['acid']);
	if(!empty($_GPC['groupname'])) {
		foreach($_GPC['groupname'] as $key => $value) {
			if(empty($value)) {
				continue;
			} else {
				$data = array('id' => $_GPC['groupid'][$key], 'name' => $value);
				$state = $account->editFansGroupname($data);
				if(is_error($state)) {
					$tip=$state['message'];
		echo "<script>alert('".$tip."');location.href='".url('mc/fangroup/')."';</script>";
			exit;
								
					
				
				}
			}
		}
	}
	if(!empty($_GPC['group_add'])) {
		foreach($_GPC['group_add'] as $value) {
			if(empty($value)) {
				continue;
			} else {
				$value = trim($value);
				$state = $account->addFansGroup($value);
				if(is_error($state)) {
				message($state['message'], url('mc/fangroup/'), 'error');
					
				}
			}
		}
	}
		echo "<script>alert('保存分组名称成功');location.href='".web_url('member/fangroup')."';</script>";
		exit;
	//message('保存分组名称成功', url('mc/fangroup/'), 'success');
}

if($op == 'del') {
	$groupid = intval($_GPC['id']);
	$account = WeAccount::create($_W['acid']);
	$groups = $account->delFansGroup($groupid);
	if(!is_error($groups)) {
				pdo_update('mc_mapping_fans', array('groupid' => 0), array('acid' => $_W['acid'], 'groupid' => $groupid));
		message(array('errno' => 0), '', 'ajax');
	} else {
		message($groups, '', 'ajax');
	}
}
include wl_template('member/fangroup');
exit();