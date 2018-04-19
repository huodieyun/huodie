<?php
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

wl_load()->model('setting');
$kaiguan = setting_get_by_name('kaiguan');

if ($op == 'ajax_list') {
    $gid = $_GPC['gid'];
    $orderno = $_GPC['orderno'];
    $jud_list = pdo_fetchall("select id,gid,gname,uniacid,id,openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time from" . tablename('tg_judgment') . " where gid ='{$gid}' and orderno='{$orderno}'  and uniacid = {$_W['uniacid']} ORDER BY create_time desc");
    foreach ($jud_list as &$li) {
        $main_content = pdo_fetch("SELECT * FROM " . tablename('tg_judgment_content') . " WHERE  judgment_id=:judgment_id ORDER BY update_time ASC ", array('judgment_id' => $li['judgment_id']));
        $li['main_content'] = $main_content['content'];
        $allcontent = pdo_fetchall("SELECT who,content,FROM_UNIXTIME(update_time,'%Y-%m-%d %H:%i:%s') AS time FROM " . tablename('tg_judgment_content') . " WHERE  judgment_id=:judgment_id AND content_id <> '" . $main_content['content_id'] . "'  ORDER BY update_time ASC ", array('judgment_id' => $li['judgment_id']));
        $li['contents'] = $allcontent;
    }

    die(json_encode($jud_list));
}
if ($op == 'ajax_reply') {
    $id = $_GPC['id'];
    $jud = pdo_fetch("select orderno,gid,gname,uniacid,id,openname,openid,avatar,status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as time from" . tablename('tg_judgment') . " where id ='{$id}' and uniacid = {$_W['uniacid']} ORDER BY create_time desc");

    $content = $_GPC['con'];
    $data_content['judgment_id'] = $jud['judgment_id'];
    $data_content['content_id'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $data_content['content'] = $content;
    $data_content['update_time'] = TIMESTAMP;
    $data_content['who'] = 1;

    $addjudgment_content = pdo_insert('tg_judgment_content', $data_content);

    die(json_encode($jud));
}
if ($op == 'display') {
    if (!empty($_GPC['keyword_form_id'])) {
        $condition .= " and gname like '%{$_GPC['keyword_form_id']}%' ";
    }
    $status = intval($_GPC['status']);
    $con = '';
    if ($status) {
        if ($status == 2) {
            $status = 0;
        }
        $con .= " and check_status = '{$status}' ";
    }

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . " where uniacid = {$_W['uniacid']} {$con} and status != 99 and gid in ( select id from cm_tg_goods where merchantid = {$_W['user']['merchant_id']} ) ORDER BY create_time DESC ");
    $pager = pagination($total, $pindex, $psize);

    $list = pdo_fetchall("select gname,uniacid,id,openname,openid,avatar,status,check_status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d ') as time from " . tablename('tg_judgment') . " where uniacid = {$_W['uniacid']} {$con} and status != 99 and gid in ( select id from cm_tg_goods where  merchantid = {$_W['user']['merchant_id']} ) " . $condition . " ORDER BY create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array());

    include wl_template('order/judgment_admin');
}
if ($op == 'replay') {
    $judgment_id = $_GPC['jid'];
    $content = $_GPC['content'];

    $data_content['judgment_id'] = $judgment_id;
    $data_content['content_id'] = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $data_content['content'] = $content;
    $data_content['update_time'] = TIMESTAMP;
    $data_content['who'] = 1;

    $addjudgment_content = pdo_insert('tg_judgment_content', $data_content);

    $goimages = $_GPC['img'];
    $goimages = array_reverse($goimages);
    foreach ($goimages as $key => $value) {
        $data2 = array('img_url' => $goimages[$key], 'content_id' => $data_content['content_id']);
        pdo_insert('tg_judgment_img', $data2);
    }

    echo "<script>alert('回复成功');location.href='" . web_url('order/judgment_admin', array('op' => 'display')) . "';</script>";
    exit();
}
if ($op == 'delete') {
    pdo_query("delete from " . tablename('tg_judgment') . " where id= {$_GPC['id']}");
    $tip = '删除成功';
    echo "<script>alert('" . $tip . "');location.href='" . web_url('order/judgment_admin', array('op' => 'display')) . "';</script>";
    exit();
}

//审核显示
if ($op == 'check') {
    $id = intval($_GPC['id']);
    $status = intval($_GPC['status']);
    $re = pdo_update('tg_judgment' , array('check_status' => $status) , array('id' => $id));
    if ($re) {
        $tip = '修改成功';
    } else {
        $tip = '修改失败';
    }
    echo "<script>alert('" . $tip . "');location.href='" . web_url('order/judgment_admin', array('op' => 'display')) . "';</script>";
    exit();
}

//商品列表
if ($op == 'goods_list') {
    $gname = $_GPC['keyword'];
    if (!empty($gname)) {
        $condition .= " and gname like '%{$gname}%' ";
    }
    $list = pdo_fetchall("SELECT gname,id,gimg FROM " . tablename('tg_goods') . " WHERE uniacid = " . $_W['uniacid'] ." and isjudgment = '1' and isshow = '1' and merchantid = '{$_W['user']['merchant_id']}' {$condition} order by `createtime` desc , `id` desc ");
    foreach ($list as &$item) {
        $item['gimg'] = tomedia($item['gimg']);
        unset($item);
    }
    include wl_template('order/goods_list');
    exit;
}

//新增虚拟评价
if ($op == 'add_virtual') {

    if (checksubmit()) {
        $goodsid = $_GPC['gid'];
        $gname = $_GPC['gname'];
        set_time_limit(0);
        $filename = TG_WEB . "resource/nickname.text";
        $url = '../addons/lexiangpingou/web/resource/images/head_imgs';

        //虚拟评价
        $head_imgs_array = get_head_img($url, 1);
        $nickname_array = get_nickname($filename, 1);

        $data = array(
            'uniacid' => $_W['uniacid'],
            'gname' => $gname,
            'gid' => $goodsid,
            'openid' => $head_imgs_array[0],
            'openname' => $nickname_array[0]['nickname'],
            'avatar' => tomedia($head_imgs_array[0]),
            'judgment_id' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)),
            'create_time' => TIMESTAMP,
            'update_time' => TIMESTAMP,
            'status' => 99,
            'item' => '',
            //评论属性
            'iszhuijia' => intval($_GPC['iszhuijia'] == 'on' ? 1 : 0),
            'ishaoyong' => intval($_GPC['ishaoyong'] == 'on' ? 1 : 0),
            'iszhengpin' => intval($_GPC['iszhengpin'] == 'on' ? 1 : 0),
            'ispianyi' => intval($_GPC['ispianyi'] == 'on' ? 1 : 0),
            'iswuliu' => intval($_GPC['iswuliu'] == 'on' ? 1 : 0),
            'iszhiliang' => intval($_GPC['iszhiliang'] == 'on' ? 1 : 0),
            'isfuwu' => intval($_GPC['isfuwu'] == 'on' ? 1 : 0),
            'isqita' => intval($_GPC['isqita'] == 'on' ? 1 : 0)
        );
        $judgment = pdo_insert('tg_judgment', $data);
        $judement_content = $_GPC['judement_content'];
        $data_content['judgment_id'] = $data['judgment_id'];
        $data_content['content_id'] = $data['judgment_id'];
        $data_content['content'] = $judement_content;
        $data_content['update_time'] = TIMESTAMP;
        $data_content['who'] = 0;
        $judgment_content = pdo_insert('tg_judgment_content', $data_content);
        $img = $_GPC['img'];
        foreach ($img as $item) {
            $judgment_img['content_id'] = $data_content['content_id'];
            $judgment_img['img_url'] = $item;
            pdo_insert('tg_judgment_img', $judgment_img);
        }

//        die(json_encode(array('data' => $data , 'h' => $data_content , 'i' => $judgment_img)));
        $tip = '添加商品评价成功';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('order/judgment_admin/add_virtual') . "';</script>";
        exit;
    }

    include wl_template('order/judgment_admin');
}

//虚拟评价列表
if ($op == 'list_virtual') {
    if (!empty($_GPC['keyword_form_id'])) {
        $condition .= " and gname like '%{$_GPC['keyword_form_id']}%' ";
    }

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('tg_judgment') . " where uniacid = {$_W['uniacid']} and status = 99 and gid in ( select id from cm_tg_goods where  merchantid = {$_W['user']['merchant_id']} ) ORDER BY create_time DESC ");
    $pager = pagination($total, $pindex, $psize);

    $list = pdo_fetchall("select gname,uniacid,id,openname,openid,avatar,status,check_status,judgment_id,item,FROM_UNIXTIME(create_time,'%Y-%m-%d ') as time from " . tablename('tg_judgment') . " where uniacid = {$_W['uniacid']} and status = 99 and gid in ( select id from cm_tg_goods where  merchantid = {$_W['user']['merchant_id']} ) " . $condition . " ORDER BY create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array());

    include wl_template('order/judgment_admin');
}

//删除虚拟评价
if ($op == 'add_virtual') {

}

exit();