<?php
/**
 * [weliam] Copyright (c) 2016/3/26
 * 商城系统设置控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->func('global');
load()->func('communication');
wl_load()->model('setting');
$config = tgsetting_load();

$ops = array('qr', 'ajax', 'copyright', 'template_query', 'template_update');
$op_names = array('系统设置');
foreach ($ops as $key => $value) {
    permissions('do', 'ac', 'op', 'store', 'setting', $ops[$key], '订单', '参数设置', $op_names[$key]);
}
$op = $_GPC['op'];
$op = in_array($op, $ops) ? $op : 'copyright';
wl_load()->model('setting');
$acct = pdo_fetch("select * from " . tablename('account_wechats') . " where  uniacid = '{$_W['uniacid']}'");
//权限控制
$tid = 8169;
//
wl_load()->model('functions');
$checkfunction = checkfunc($tid);
$isdeliverytype = checkfunc(8155);//运费模板
$issh = checkfunc(8157);//送货
$iszt = checkfunc(8156);//自提
$_W['page']['title'] = $checkfunction['name'];
if ($op == 'qr') {
    $functionid = $_GPC['id'];
    $num = $_GPC['num'];
    $orderno = $_GPC['orderno'];
    $uniacid = $_GPC['uniacid'];
    /*二维码*/
    wl_load()->classs('qrcode');
    $createqrcode = new creat_qrcode();
    $createqrcode->createfunctionpayQrcode($functionid, $num, $orderno, $uniacid);
    die(json_encode(array('errno' => 0, 'message' => $functionid)));
}
if ($op == 'ajax') {
    $id = $_GPC['id'];
    $name = $_GPC['name'];
    pdo_update("account_wechats", array('tpl' => $id), array('uniacid' => $_W['uniacid']));
    die(json_encode(array('errno' => 0, 'message' => $name . "已成功启用")));
}
if ($op == 'copyright') {
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
    $functionlist = pdo_fetchall("select * from " . tablename('tg_function') . " where type=1");
    $wechats = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid=" . $_W['uniacid']);
    foreach ($functionlist as $key => $value) {
        $functionlist[$key]['status'] = 0;
        $functionlist[$key]['endtime'] = 0;
        $functionlist[$key]['img'] = tomedia($value['img']);
        $functionlist[$key]['yimg'] = tomedia($value['yimg']);
        $function_detail = pdo_fetch("select * from " . tablename('tg_function_detail') . " where functionid=:functionid and  uniacid=:uniacid", array(':functionid' => $value['id'], ':uniacid' => $_W['uniacid']));

        if (!empty($function_detail) && $function_detail['endtime'] > time()) {
            $functionlist[$key]['status'] = 1;
            $functionlist[$key]['endtime'] = $function_detail['endtime'];
        }
        if ((($acct['vip'] == 1 && $acct['endtime'] > time()) || $acct['ordernum'] > 0) && $value['isdingzhi'] == 0) {
            $functionlist[$key]['status'] = 1;
            $functionlist[$key]['endtime'] = $acct['endtime'];
            if ($acct['ordernum'] > 0) {
                $functionlist[$key]['endtime'] = 1606492800;
            }
        }
        if ($wechats['tpl'] == $value['id']) {
            $functionlist[$key]['status'] = 2;
        }
    }
    $storesids = explode(",", $settings['hexiao_id']);
    foreach ($storesids as $key => $value) {
        if ($value) {
            $stores[$key] = pdo_fetch("select * from" . tablename('tg_store') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
        }
    }
    $yunfeiids = explode(",", $settings['yunfei_id']);
    foreach ($yunfeiids as $key => $value) {
        if ($value) {
            $dispatch_list[$key] = pdo_fetch("select * from" . tablename('tg_delivery_template') . "where id ='{$value}' and uniacid='{$_W['uniacid']}'");
        }
    }
//	wl_debug($settings);
    if (checksubmit('submit')) {
        load()->func('file');
        $r = mkdirs(IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid']);
        $r2 = mkdirs(TG_CERT . $_W['uniacid']);
        if (!empty($_GPC['cert'])) {
            $ret = file_put_contents(IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_cert.pem', trim($_GPC['cert']));
            $ret2 = file_put_contents(TG_CERT . $_W['uniacid'] . '/apiclient_cert.pem', trim($_GPC['cert']));
            $r = $r && $ret;
        }
        if (!empty($_GPC['key'])) {
            $ret = file_put_contents(IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_key.pem', trim($_GPC['key']));
            $ret2 = file_put_contents(TG_CERT . $_W['uniacid'] . '/apiclient_key.pem', trim($_GPC['key']));
            $r = $r && $ret;
        }
        if (!empty($_GPC['rootca'])) {
            $ret = file_put_contents(IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_rootca.pem', trim($_GPC['rootca']));
            $ret2 = file_put_contents(TG_CERT . $_W['uniacid'] . '/apiclient_rootca.pem', trim($_GPC['rootca']));
            $r = $r && $ret;
        }
        if (!$r) {
            message('证书保存失败, 请保证该目录可写');
        }
        if (intval($_GPC['order_alert_time']) == 0) {
            $order_alert_time = 5;
        } else {
            $order_alert_time = intval($_GPC['order_alert_time']);
        }
        //核销ID
        $store = $_GPC['storeids'];
        $str = '';
        if ($store) {
            foreach ($store as $key => $value) {
                $str .= $value . ",";
            }
        }
        //运费ID
        $dispatchs = $_GPC['dispatchs'];
        $str1 = '';
        if ($dispatchs) {
            foreach ($dispatchs as $key => $value) {
                $str1 .= $value . ",";
            }
        }

        $base = array(
            'bukuan_status' => $_GPC['bukuan_status'],
            'guanzhu' => $_GPC['guanzhu'],
            'goodstip' => $_GPC['goodstip'],
            'sharestatus' => $_GPC['sharestatus'],
            'order_alert' => $_GPC['order_alert'],
            'iszhe' => $_GPC['iszhe'],
            'ismarketprice' => $_GPC['ismarketprice'],
            'issell' => $_GPC['issell'],
            'order_alert_time' => $order_alert_time,
            'service' => $_GPC['service'],
            'job' => $_GPC['job'],
            'guess' => $_GPC['guess'],
            'group_style' => $_GPC['group_style'],
            'firstfree' => $_GPC['firstfree'],
            'over_free' => $_GPC['over_free'],
            'search' => $_GPC['search'],
            'meiqia' => $_GPC['meiqia'],
            'deliverytype' => $_GPC['deliverytype'],
            'gettime' => $_GPC['gettime'],
            'jpgettime' => $_GPC['jpgettime'],
            'hexiao_id' => $str,
            'yunfei_id' => $str1,
            'cancle_time' => $_GPC['cancle_time'],
            'noprice_time' => $_GPC['noprice_time'],
            'share_type' => $_GPC['share_type']
        );
        $share = array(
            'share_title' => $_GPC['share_title'],
            'share_image' => $_GPC['share_image'],
            'share_desc' => $_GPC['share_desc']
        );
        $refund = array(
            'mchid' => $_GPC['mchid'],
            'apikey' => $_GPC['apikey'],
            'auto_refund' => $_GPC['auto_refund']
        );
        $acctdata = array(
            'homeimg' => $_GPC['homeimg']
        );
        if (!empty($_GPC['saler_img'])) {
            $_GPC['saler_img'] = saveImage(tomedia($_GPC['saler_img']));
        }
        $tginfo = array(
            'sname' => $_GPC['sname'],
            'slogo' => $_GPC['slogo'],
            'saler_img' => $_GPC['saler_img'],
            'guanzhu' => $_GPC['guanzhu'],
            'qrcode' => $_GPC['qrcode'],
            'kefu_image' => $_GPC['kefu_image'],
            'followed_image' => $_GPC['followed_image'],
            'kftel' => $_GPC['kftel'],
            'sjtel' => $_GPC['sjtel'],
            'gsname' => $_GPC['gsname'],
            'gwbqym' => $_GPC['gwbqym'],
            'icp' => $_GPC['icp'],
            'gwbah' => $_GPC['gwbah'],
            'gwbalj' => $_GPC['gwbalj'],
            'content' => htmlspecialchars_decode($_GPC['content']),
            'service_openid' => $_GPC['service_openid'],
            'service_nickname' => $_GPC['service_nickname']
        );
        $tip = array(
            'tag4' => $_GPC['tag4'],
            'tag3' => $_GPC['tag3'],
            'tag2' => $_GPC['tag2'],
            'tag1' => $_GPC['tag1']
        );

        $pdobase = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'base',
            'value' => serialize($base)
        );
        $pdoshare = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'share',
            'value' => serialize($share)
        );
        $pdorefund = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'refund',
            'value' => serialize($refund)
        );
        $pdomessage = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'message',
            'value' => serialize($message)
        );
        $pdotginfo = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'tginfo',
            'value' => serialize($tginfo)
        );
        $pdotip = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'tip',
            'value' => serialize($tip)
        );
        $ifbase = setting_get_by_name('base');
        $ifshare = setting_get_by_name('share');
        $ifrefund = setting_get_by_name('refund');
        $ifmessage = setting_get_by_name('message');
        $iftginfo = setting_get_by_name('tginfo');
        $iftip = setting_get_by_name('tip');

        //招商加盟
        $company_join = array(
            'company_join' => $_GPC['company_join']
        );
        $pdocompany_join = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'company_join',
            'value' => serialize($company_join)
        );
        $ifcompany_join = setting_get_by_name('company_join');
        if (!empty($ifcompany_join)) {
            setting_update_by_params($pdocompany_join, array('key' => 'company_join', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdocompany_join);
        }

        //公司简介
        $company = array(
            'company_profile' => $_GPC['company_profile'],
        );
        $pdocompany = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'company',
            'value' => serialize($company)
        );
        $ifcompany = setting_get_by_name('company');
        if (!empty($ifcompany)) {
            setting_update_by_params($pdocompany, array('key' => 'company', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdocompany);
        }

        $ifkaiguan = setting_get_by_name('kaiguan');
        if (!empty($ifkaiguan)) {
            $kaiguan = $ifkaiguan;
        }
        //状态开关
        $kaiguan['judgment'] = $_GPC['judgment'];
        $kaiguan['is_village'] = $_GPC['is_village'];
        $kaiguan['is_hbhexiao'] = $_GPC['hbhexiao'];
        $kaiguan['zitidingwei'] = $_GPC['zitidingwei'];
        $kaiguan['area_delivery'] = $_GPC['area_delivery'];
        $kaiguan['hexiaotuikuan'] = $_GPC['hexiaotuikuan'];
        $kaiguan['group_rule_switch'] = $_GPC['group_rule_switch'];
        $kaiguan['wechat_login'] = $_GPC['wechat_login'];
        $kaiguan['cj_isallow'] = $_GPC['cj_isallow'];
        $pdokaiguan = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'kaiguan',
            'value' => serialize($kaiguan)
        );
        $fans_data = array(
            'fans_data_apply' => $_GPC['fans_data_apply'],
            'fans_data_image' =>$_GPC['fans_data_image'],
            'fans_data_spring'=>$_GPC['fans_data_spring']
        );
        $pdofans_data = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'fans_data',
            'value' => serialize($fans_data)
        );

        $iffans_data = setting_get_by_name("fans_data");
        if (!empty($iffans_data)) {
            setting_update_by_params($pdofans_data, array('key' => 'fans_data', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdofans_data);
        }


        if (!empty($ifkaiguan)) {
            setting_update_by_params($pdokaiguan, array('key' => 'kaiguan', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdokaiguan);
        }

        if (!empty($ifbase)) {
            setting_update_by_params($pdobase, array('key' => 'base', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdobase);
        }
        if (!empty($ifshare)) {
            setting_update_by_params($pdoshare, array('key' => 'share', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdoshare);
        }
        if (!empty($ifrefund)) {
            setting_update_by_params($pdorefund, array('key' => 'refund', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdorefund);
        }

        if (!empty($iftginfo)) {
            setting_update_by_params($pdotginfo, array('key' => 'tginfo', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdotginfo);
        }
        if (!empty($iftip)) {
            setting_update_by_params($pdotip, array('key' => 'tip', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdotip);
        }
        if (!empty($_GPC['homeimg'])) {
            pdo_update('account_wechats', $acctdata, array('uniacid' => $_W['uniacid']));
        }
        if (!empty($_GPC['secret'])) {
            pdo_update('account_wechats', array('secret' => $_GPC['secret']), array('uniacid' => $_W['uniacid']));
        }
        // 自提时间
        $selfTime = array(
            'is_self_time' => $_GPC['is_self_time'],
            'self_time_start' => $_GPC['selfTime']['start'],
            'self_time_end' => $_GPC['selfTime']['end']
        );
        $pdoSelfTime = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'self_time',
            'value' => serialize($selfTime)
        );
        $self_time = setting_get_by_name("self_time");
        if (!empty($self_time)) {
            setting_update_by_params($pdoSelfTime, array('key' => 'self_time', 'uniacid' => $_W['uniacid']));
        } else {
            setting_insert($pdoSelfTime);
        }

        $tip = '更新设置成功！';
        echo "<script>alert('" . $tip . "');location.href='" . web_url('store/setting/copyright') . "';</script>";
        exit;
    }
}

//获取模板的信息
if ($op == 'template_query') {

    $id = $_GPC['id'];
    $function = pdo_fetch("select * from " . tablename('tg_function') . " where id = '{$id}' ");
    $function['img'] = tomedia($function['img']);
    $function['yimg'] = tomedia($function['yimg']);
    die(json_encode(array('data' => $function)));

}

if ($op == 'template_update') {

    $data = $_GPC['data'];

    $res = pdo_update('tg_function', $data, array('id' => $data['id']));
    die(json_encode(array('status' => $res)));
}

//die(json_encode(array('data' => $_W['account']['platform_logo'])));
include wl_template('store/setting');
exit();