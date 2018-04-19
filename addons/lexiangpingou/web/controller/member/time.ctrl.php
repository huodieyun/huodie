<?php
defined('IN_IA') or exit('Access Denied');
header("Content-type:text/html;charset=utf-8");
load()->func("tpl");
load()->classs("account");
$access_token = WeAccount::token();

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'index';
if ($op == "index") {
    $pageindex = max(1, intval($_GPC['page']));
    $pagesize = 20;
    $where = ' WHERE uniacid=:uniacid';
    $params = array(':uniacid' => $_W['uniacid']);
    if (!empty($_GPC['title'])) {
        $where .= ' AND `title` LIKE :title';
        $params[':title'] = '%' . trim($_GPC['title']) . '%';
    }
    if (!empty($_GPC['mid'])) {
        $where .= ' AND `mid`=:mid';
        $params[':mid'] = trim($_GPC['mid']);
    }
    $order = ' ORDER BY `createtime` DESC,`id` DESC';
    $sql = 'SELECT COUNT(*) FROM ' . tablename('healer_tplmsg') . $where;
    $total = pdo_fetchcolumn($sql, $params);
    $pager = pagination($total, $pageindex, $pagesize);
    $sql = 'SELECT * FROM ' . tablename('healer_tplmsg') . $where . $order . ' LIMIT ' . ($pageindex - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql, $params);
    include wl_template("member/manager");
    exit();
}
if ($op == 'save') {
    if (checksubmit()) {
        if (empty($_GPC['title'])) {
            message('温馨提示：请填写标题！', '', 'error');
        }
        if (empty($_GPC['mid'])) {
            message('温馨提示：请填写模板ID！', '', 'error');
        }
        $value = array();
        foreach ($_GPC['key'] as $key => $v) {
            $value[$v] = array('value' => trim($_GPC['value'][$key]), 'color' => trim($_GPC['color'][$key]));
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'title' => trim($_GPC['title']),
            'mid' => trim($_GPC['mid']),
            'content' => trim($_GPC['content']),
            'data' => json_encode($value),
            'url' => trim($_GPC['url'])
        );
        if (pdo_insert('healer_tplmsg', $data)) {
            message('温馨提示：新增模板消息成功！', referer(), 'success');
        }
        message('温馨提示：新增模板消息失败！', '', 'error');
    }
    $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=" . $access_token;
    $tpl_list = https_request($url);

    $tpl_list = (json_decode($tpl_list, true));

    wl_load()->model('setting');

    $setting = setting_get_by_name('message');

//    die(json_encode($tpl_list));

    foreach ($tpl_list as $key => $item) {
        foreach ($item as $k => $it) {

            foreach ($setting as $set) {

                if ($it['template_id'] == $set) {
                    unset($tpl_list[$key][$k]);
                }
            }
        }
    }
    $template_list = $tpl_list['template_list'];
    $tpl_list['template_list'] = array_merge($template_list);
    $tpl_list = json_encode($tpl_list);

    include wl_template("member/manager");
    exit();
}
if ($op == 'update') {
    if (checksubmit()) {
        if (empty($_GPC['title'])) {
            message('温馨提示：请填写标题！', '', 'error');
        }
        if (empty($_GPC['mid'])) {
            message('温馨提示：请填写模板ID！', '', 'error');
        }
        $value = array();
        foreach ($_GPC['key'] as $key => $v) {
            $value[$v] = array('value' => trim($_GPC['value'][$key]), 'color' => trim($_GPC['color'][$key]));
        }
        $data = array(
            'title' => trim($_GPC['title']),
            'mid' => trim($_GPC['mid']),
            'content' => trim($_GPC['content']),
            'data' => json_encode($value),
            'url' => trim($_GPC['url'])
        );
        if (pdo_update('healer_tplmsg', $data, array('uniacid' => $_W['uniacid'], 'id' => intval($_GPC['id'])))) {
            message('温馨提示：编辑模板消息成功！', referer(), 'success');
        }
        message('温馨提示：编辑模板消息失败！', '', 'error');
    }
    $tplmsg = pdo_fetch('SELECT * FROM ' . tablename('healer_tplmsg') . ' WHERE `uniacid`=:uniacid AND `id`=:id', array(':uniacid' => $_W['uniacid'], ':id' => intval($_GPC['id'])));
    if (empty($tplmsg)) {
        message('温馨提示：该模板消息不存在！', '', 'error');
    }
    $tplmsg['data'] = json_decode($tplmsg['data'], true);
    $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=" . $access_token;
    $tpl_list = https_request($url);
    $tpl_list = json_decode($tpl_list, true);
    foreach ($tpl_list as $item) {
        foreach ($item as $it) {
            if ($tplmsg['mid'] == $it['template_id']) {
                $tplmsg['msgname'] = $it['title'];
            }
        }
    }

    include wl_template("member/manager");
    exit();
}
if ($op == 'del') {
    $tplmsg = pdo_fetch('SELECT * FROM ' . tablename('healer_tplmsg') . ' WHERE `uniacid`=:uniacid AND `id`=:id', array(':uniacid' => $_W['uniacid'], ':id' => intval($_GPC['id'])));
    if (empty($tplmsg)) {
        message('温馨提示：该模板消息不存在！', '', 'error');
    }
    if (pdo_delete('healer_tplmsg', array('uniacid' => $_W['uniacid'], 'id' => intval($_GPC['id'])))) {
        $url = "https://api.weixin.qq.com/cgi-bin/template/del_private_template?access_token=" . $access_token;
        $datas = array("template_id" => $tplmsg["mid"]);
        ihttp_request($url, json_encode($datas));
        message('温馨提示：删除模板消息成功！', referer(), 'success');
    }
    message('温馨提示：删除模板消息失败！', '', 'error');
}
if ($op == 'upsave') {
    $tplmsg = pdo_fetch('SELECT * FROM ' . tablename('healer_tplmsg') . ' WHERE `uniacid`=:uniacid AND `id`=:id', array(':uniacid' => $_W['uniacid'], ':id' => intval($_GPC['id'])));
    $tplmsg['data'] = json_decode($tplmsg['data'], true);
    load()->model('mc');
    $groups = mc_fans_groups(true);
    if (checksubmit()) {
        if (empty($_GPC['remark'])) {
            message('温馨提示：请填写备注！', '', 'error');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'mid' => intval($_GPC['mid']),
            'group' => intval($_GPC['type']) == 0 ? '-1' : trim($_GPC['group']),
            'remark' => trim($_GPC['remark']),
            'status' => 0,
            'type' => intval($_GPC['type']),
            'openids' => trim($_GPC['openids']),
            'createtime' => TIMESTAMP
        );
        if (pdo_insert('healer_tplmsg_bulk', $data)) {
            message('温馨提示：保存群发成功！即将进入群发任务……', web_url('member/time', array('op' => 'upsend', 'id' => pdo_insertid())), 'success');
        }
        message('温馨提示：保存群发失败！', '', 'error');
    }
    $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=" . $access_token;
    $tpl_list = https_request($url);
    $tpl_list = json_decode($tpl_list, true);
    foreach ($tpl_list as $item) {
        foreach ($item as $it) {
            if ($tplmsg['mid'] == $it['template_id']) {
                $tplmsg['msgname'] = $it['title'];
            }
        }
    }
    include wl_template("member/bulk");
    exit();
}
if ($op == 'upsend') {
    $id = intval($_GPC['id']);
    $bulk = pdo_fetch('SELECT * FROM ' . tablename('healer_tplmsg_bulk') . ' WHERE `uniacid`=:uniacid AND `id`=:id', array(':uniacid' => $_W['uniacid'], ':id' => intval($_GPC['id'])));
    if ($bulk['status'] == 0) {
        $account = WeAccount::create($_W['acid']);
        $tplmsg = pdo_fetch('SELECT * FROM ' . tablename('healer_tplmsg') . ' WHERE `uniacid`=:uniacid AND `id`=:id', array(':uniacid' => $_W['uniacid'], ':id' => $bulk['mid']));
        $tplmsg['data'] = json_decode($tplmsg['data'], true);
        $where = 'uniacid=:uniacid AND follow=:follow';
        $params = array(':uniacid' => $_W['uniacid'], ':follow' => 1);
        if ($bulk['type'] == 1) {
            $where .= ' AND `groupid`=:groupid';
            $params[':groupid'] = $bulk['group'];
        } else {
            if ($bulk['type'] == 2) {
                $sql = '';
                $openids = explode("\r\n", $bulk['openids']);
                $i = 0;
                while ($i < count($openids)) {
                    $i == 0 ? $sql .= '"' . $openids[$i] . '"' : ($sql .= ',"' . $openids[$i] . '"');
                    ++$i;
                }
                $where .= ' AND `openid` in (' . $sql . ')';
            }
        }
        $page = max(1, intval($_GPC['page']));
        $pagesize = 100;
        $fansCount = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('mc_mapping_fans') . ' WHERE ' . $where, $params);
        $total = ceil($fansCount / $pagesize);
        $fans = pdo_fetchall('SELECT * FROM ' . tablename('mc_mapping_fans') . ' WHERE ' . $where . ' LIMIT ' . ($page - 1) * $pagesize . ',' . $pagesize, $params);
        $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=" . $access_token;
        $tpl_list = https_request($url);
        $tpl_list = json_decode($tpl_list, true);
        foreach ($tpl_list as $item) {
            foreach ($item as $it) {
                if ($tplmsg['mid'] == $it['template_id']) {
                    $msgname = $it['title'];
                }
            }
        }
        foreach ($fans as $key => $fan) {
            $err_msg = $account->sendTplNotice($fan['openid'], $tplmsg['mid'], $tplmsg['data'], $tplmsg['url']);
            if (($err_msg['errno'] == 1)) {
                internal_log('/bluk/', array('name' => $msgname, 'mid' => $tplmsg['mid'], 'err' => $err_msg), __LINE__, __FILE__, '消息群发');
            }
        }
        if ($page < $total) {
            message('温馨提示：请不要关闭页面，群发任务正在进行中！（' . $page . '/' . $total . '）', web_url('member/time', array('op' => 'upsend', 'id' => $bulk['id'], 'page' => $page + 1)), 'error');
        } else {
            if ($page == $total) {
                if (pdo_update('healer_tplmsg_bulk', array('status' => 2), array('uniacid' => $_W['uniacid'], 'id' => $bulk['id']))) {
                    message('温馨提示：群发任务已完成！（' . $page . '/' . $total . '）', web_url('member/time', array('op' => 'index')), 'success');
                }
                message('温馨提示：群发任务出错！');
            } else {
                pdo_update('healer_tplmsg_bulk', array('status' => 2), array('uniacid' => $_W['uniacid'], 'id' => $bulk['id']));
                message('温馨提示：该群发任务已完成！', web_url('member/time'), 'error');
            }
        }
    } else {
        if ($bulk['status'] == 1) {
            message('温馨提示：该群发任务正在进行中！', '', 'error');
        } else {
            if ($bulk['status'] == 2) {
                message('温馨提示：该群发任务已完成！', '', 'error');
            }
        }
    }
}
if ($op == 'updel') {
    $bulk = pdo_fetch('SELECT * FROM ' . tablename('healer_tplmsg_bulk') . ' WHERE `uniacid`=:uniacid AND `id`=:id', array(':uniacid' => $_W['uniacid'], ':id' => intval($_GPC['id'])));
    if (empty($bulk)) {
        message('温馨提示：该群发任务不存在！', '', 'error');
    }
    if (pdo_delete('healer_tplmsg_bulk', array('uniacid' => $_W['uniacid'], 'id' => intval($_GPC['id'])))) {
        message('温馨提示：删除群发任务成功！', referer(), 'success');
    }
    message('温馨提示：删除群发任务失败！', '', 'error');
}
if ($op == "upindex") {
    $pageindex = max(1, intval($_GPC['page']));
    $pagesize = 20;
    $where = ' WHERE uniacid = :uniacid';
    $params = array(':uniacid' => $_W['uniacid']);
    if (!empty($_GPC['remark'])) {
        $where .= ' AND `remark` LIKE :remark';
        $params[':remark'] = '%' . trim($_GPC['remark']) . '%';
    }
    if (is_numeric($_GPC['status'])) {
        $where .= ' AND `status` = :status';
        $params[':status'] = trim($_GPC['status']);
    }
    $order = ' ORDER BY `createtime` DESC,`id` DESC';
    $sql = 'SELECT COUNT(*) FROM ' . tablename('healer_tplmsg_bulk') . $where;
    $total = pdo_fetchcolumn($sql, $params);
    $pager = pagination($total, $pageindex, $pagesize);
    $sql = 'SELECT * FROM ' . tablename('healer_tplmsg_bulk') . $where . $order . ' LIMIT ' . ($pageindex - 1) * $pagesize . ',' . $pagesize;
    $list = pdo_fetchall($sql, $params);
    include wl_template("member/bulk");
}
function https_request($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}
//if ($op == 'status') {
//    $id = intval($_GPC['id']);
//    $status = intval($_GPC['status']);
//    pdo_update('healer_tplmsg', array('status' => $status), array('id' => $id));
//    die(json_encode(array('errno' => 0, 'message' => "设置成功")));
//
//}