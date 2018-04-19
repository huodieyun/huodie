<?php
/**
 * [weliam] Copyright (c) 2016/3/26
 * 商城系统设置控制器
 */
defined('IN_IA') or exit('Access Denied');
load()->func("tpl");
load()->classs("account");
$access_token = WeAccount::token();
load()->func('communication');
$ops = array('base', 'display', 'qr', 'ajax', 'copyright', 'tpl_add', 'tpl_post');
$op_names = array('系统设置');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'store', 'setting', $ops[$key], '订单', '参数设置', $op_names[$key]);
}
$op = $_GPC['op'];
$op = in_array($op, $ops) ? $op : 'base';
wl_load()->model('setting');
//权限控制
$tid = 8166;
//
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$_W['page']['title'] = $checkfunction['name'];

if ($op == 'base') {
    include wl_template('store/notice');
}
//添加模板 自动 点击操作即可
if ($op == "tpl_add") {
    $setting = setting_get_by_name("message");
    $tplbh = $_GPC["tplbh"];
    $name = $_GPC["name"];
    $status = $_GPC["status"];
    $status = intval($status);
    if($status == 0){
        unset($status);
    }
    if (!$status){
        $setting[$name] = null;
        $pdoshare = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'message',
            'value' => serialize($setting)
        );
		$res = setting_update_by_params($pdoshare, array('key' => 'message', 'uniacid' => $_W['uniacid']));
        $url= "https://api.weixin.qq.com/cgi-bin/template/del_private_template?access_token=" . $access_token;
        $postdata = array("template_id"=>$tplbh);
        $postdata = json_encode($postdata);
       
        $response = http_request($url, $postdata);
        
        if ($res){
            $result = '关闭成功！';
        }else{
            $result = '关闭失败！';
        }
        die(json_encode(array('errno' => $res , 'message' => $result)));
    }else {
        if (is_error($access_token)) {
            die(json_encode(array('errno' => 0, 'message' => '获取access token 失败')));
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token=' . $access_token;
        
        $postdata = '{"template_id_short":"' . $tplbh . '"}';
        $response = ihttp_request($url, $postdata);
        $errarr = @json_decode($response['content'], true);
        if ($errarr['errcode'] == 0) {
            if (!empty($setting)) {
                $message = $setting;
            }
            $message[$name] = $errarr['template_id'];
            $pdoshare = array(
                'uniacid' => $_W['uniacid'],
                'key' => 'message',
                'value' => serialize($message)
            );
            if (empty($setting)) {
                $res = setting_insert($pdoshare);
            } else {
                $res = setting_update_by_params($pdoshare, array('key' => 'message', 'uniacid' => $_W['uniacid']));
            }
            if ($res){
                $result = '开启成功！';
            }else{
                $result = '开启失败！';
            }
            die(json_encode(array('errno' => $res , 'message' => $result)));
        } else {
            die(json_encode(array('errno' => 0, 'message' => "微信错误代码 ：" .$errarr['errcode'])));
        }
    }
}

//吊起微信添加失败时手动输入模板ID添加
if ($op == 'tpl_post') {

    $setting = setting_get_by_name("message");
    $tplbh = $_GPC["tplbh"];
    $name = $_GPC["name"];
    if (!empty($setting)) {
        $message = $setting;
    }
    $message[$name] = $tplbh;
    $pdoshare = array(
        'uniacid' => $_W['uniacid'],
        'key' => 'message',
        'value' => serialize($message)
    );
    if (empty($setting)) {
        $res = setting_insert($pdoshare);
    } else {
        $res = setting_update_by_params($pdoshare, array('key' => 'message', 'uniacid' => $_W['uniacid']));
    }
    if ($res){
        $result = '开启成功！';
    }else{
        $result = '开启失败！';
    }
    die(json_encode(array('errno' => $res , 'message' => $result)));

}

//截止
if ($op == 'display') {
    $_W['page']['title'] = '商城信息设置 - 系统管理';
    $set = setting_get_list();
    if (empty($set)) {
        $settings = $this->module['config'];
    } else {
        $settings = array();
        foreach ($set as $key => $value) {
            $settingarray = $value['value'];
            foreach ($settingarray as $k => $v) {
                $settings[$k] = $v;
            }
        }
    }
    $functionlist = pdo_fetchall("SELECT * FROM " . tablename('tg_function') . " WHERE type=1");
    $wechats = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid=" . $_W['uniacid']);
    foreach ($functionlist as $key => $value) {
        $functionlist[$key]['status'] = 0;
        $functionlist[$key]['endtime'] = 0;
        $function_detail = pdo_fetch("SELECT * FROM " . tablename('tg_function_detail') . " WHERE functionid=:functionid AND  uniacid=:uniacid", array(':functionid' => $value['id'], ':uniacid' => $_W['uniacid']));

        if (!empty($function_detail) && $function_detail['endtime'] > time()) {
            $functionlist[$key]['status'] = 1;
            $functionlist[$key]['endtime'] = $function_detail['endtime'];
        }
        if ($wechats['tpl'] == $value['id']) {
            $functionlist[$key]['status'] = 2;
        }
    }
//	wl_debug($settings);m_no_num_success
    if (checksubmit('submit')) {
        load()->func('file');

        $message = array(
            'm_bukuan' => $_GPC['m_bukuan'],
            'm_result' => $_GPC['m_result'],
            'm_daipay' => $_GPC['m_daipay'],
            'pay_suc' => $_GPC['pay_suc'],
            'm_pay' => $_GPC['m_pay'],
            'm_tuan' => $_GPC['m_tuan'],
            'l_suc' => $_GPC['l_suc'],
            'l_tuan' => $_GPC['l_tuan'],
            'm_nocash' => $_GPC['m_nocash'],
            'm_cancle' => $_GPC['m_cancle'],
            'm_ref' => $_GPC['m_ref'],
            'm_no_num_success' => $_GPC['m_no_num_success'],
            'm_hexiao' => $_GPC['m_hexiao'],
            'm_send' => $_GPC['m_send'],
            'm_buy' => $_GPC['m_buy'],
            'm_change' => $_GPC['m_change'],
            'm_feedback' => $_GPC['m_feedback'],
            'm_check' => $_GPC['m_check'],
            'm_service' => $_GPC['m_service']
        );

        $pdomessage = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'message',
            'value' => serialize($message)
        );


        $ifmessage = setting_get_by_name('message');


        if (!empty($ifmessage)) {
            setting_update_by_params($pdomessage, array('key' => 'message', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdomessage);
        }

        message('更新设置成功！', web_url('store/notice/display'));
    }
    include wl_template('store/notice');
}
function http_request($url, $data = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
// post数据
    curl_setopt($ch, CURLOPT_POST, 1);
// 把post的变量加上
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
exit();

