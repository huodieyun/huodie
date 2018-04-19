<?php
load()->func('tpl');
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$ops = array('display','post','delete');



if ($operation == 'display') {
    pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_banner` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '唯一标识',
        `uniacid` int(11) NOT NULL,
        `link` varchar(255) NOT NULL,
        `thumb` varchar(255) NOT NULL,
        `displayorder` int(11) NOT NULL,
        `enabled` int(11) NOT NULL,
        `visible_level` int(11) NOT NULL,
        `merchantid` INT( 11 ) NOT NULL COMMENT  '商户id',
        PRIMARY KEY (`id`),
        KEY `indx_displayorder` (`displayorder`)
      )  ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
	$list = pdo_fetchall("SELECT * FROM " . tablename('tg_banner') . " WHERE uniacid = '{$_W['uniacid']}'  and merchantid = :merchantid ORDER BY displayorder DESC",array(":merchantid"=>$_W["user"]["merchant_id"]));
} elseif ($operation == 'post') {
	$id = intval($_GPC['id']);
	if (checksubmit('submit')) {
		$data = array(
            "merchantid"=>$_W["user"]["merchant_id"],
			'uniacid' => $_W['uniacid'],
			'link' => $_GPC['link'],
			'enabled' => intval($_GPC['enabled']),
			'displayorder' => intval($_GPC['displayorder']),
			'thumb'=>$_GPC['thumb']
		);
		if (!empty($id)) {
			pdo_update('tg_banner', $data, array('id' => $id));
		} else {
			pdo_insert('tg_banner', $data);
			$id = pdo_insertid();
		}
		message('更新广告成功！', web_url('store/banner', array('op' => 'display')), 'success');
	}
	$adv = pdo_fetch("select * from " . tablename('tg_banner') . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $_W['uniacid']));
} 

if ($operation == 'delete') {
	$id = intval($_GPC['id']);
	$adv = pdo_fetch("SELECT id FROM " . tablename('tg_banner') . " WHERE id = '$id' AND uniacid = " . $_W['uniacid'] . "");
	if (empty($adv)) {
		message('抱歉，首页广告不存在或是已经被删除！', web_url('store/banner', array('op' => 'display')), 'error');
	}
	pdo_delete('tg_banner', array('id' => $id));
	message('广告删除成功！', web_url('store/banner', array('op' => 'display')), 'success');
}
if ($op == 'setgoodsproperty') {
	$id = intval($_GPC['id']);
	$adv = pdo_fetch("SELECT visible_level FROM " . tablename('tg_banner') . " WHERE id = {$id}");
	$type = $adv['visible_level']=='1'?'2':'1';
	pdo_update("tg_banner", array("visible_level"  => $type), array("id" => $id));
	die(json_encode(array("result" => 1)));	
}
include wl_template('store/banner');
