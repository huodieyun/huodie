<?php
defined('IN_IA') or exit('Access Denied');

$ops = array('display', 'post', 'delete');
$op = in_array($op, $ops) ? $op : 'display';

//创建热搜表
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_hot_search` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL COMMENT '公众账号id',
  `hot_word` varchar(100) NOT NULL COMMENT '热词',
  `times` int(11) NOT NULL COMMENT '搜索次数',
  `sort` int(11) NOT NULL COMMENT '搜索排序',
  PRIMARY KEY (`id`),
  KEY `idx_uniacid` (`uniacid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

if ($op == 'display') {
    /*
     * 热搜查询
     * 接口名 /min_shop_api.php?op=hot_search_view
     * 返回查询到的热词 以及状态status
     */
    $uniacid = $_W['uniacid'];
    $list = pdo_fetchall("select * from " .tablename('tg_hot_search') ." where uniacid = '" .$uniacid ."' order by sort desc");

} elseif ($op == 'post') {
    $id = intval($_GPC['id']);

    if (!empty($id)){
        $list = pdo_fetch("select * from " .tablename('tg_hot_search') ." where id = '" .$id ."'");
    }
	if (checksubmit('submit')) {
        $data['uniacid'] = $_W['uniacid'];
        $data['hot_word'] = $_GPC['hot_word'];
        $data['times'] = $_GPC['times'];
        $data['sort'] = $_GPC['sort'];

        if (empty($id)){
            if (pdo_insert('tg_hot_search' , $data)){
                message('添加热词成功', web_url('goods/hot_word' , array('op' => '')),  'success');
            }else{
                message('添加热词失败', web_url('goods/hot_word' , array('op' => 'post')),  'error');
            }

        }else{
            if (pdo_update('tg_hot_search' , $data , array('id' => $id))){
                message('更新热词成功', web_url('goods/hot_word' , array('op' => '')),  'success');
            }else{
                message('更新热词失败', web_url('goods/hot_word' , array('op' => 'post')),  'error');
            }
        }
    }

} elseif ($op == 'delete') {
	$id = intval($_GPC['id']);

	if (empty($id)) {
		message(error('1', '删除失败: 未指定热词.'));
	}else{
	    if (pdo_delete('tg_hot_search', array('id' => $id))){
            message(error('0', '成功删除!'));
        }else{
            message(error('1', '删除失败!'));
        }
    }
}

include wl_template('goods/hot_word');
exit();