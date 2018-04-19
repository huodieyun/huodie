<?php

$_W['page']['title'] = "极限单品 - 供应商入驻";

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$uniacid = $_W['uniacid'];
$manage_cate = pdo_fetchall('select * from ' . tablename('tg_manage_cate') . ' where 1=1');
if ($op == 'display') {
    $step = intval($_GPC['step']);

    if (checksubmit()) {
        $data = $_GPC['platform'];
//        die(json_encode($step));
//        if ($step == 4) {
//            $tip = '提交成功！';
//            echo "<script>alert(" .$tip .");location.href='" . web_url('platform/platform_list') . "';</script>";
//            exit;
//        }
        if (empty($data['id'])) {
            $data['createtime'] = time();
            $data['status'] = -1;
            $data['uniacid'] = $uniacid;
            $data['name'] = $_W['account']['name'];
            pdo_insert('tg_platform_supplier', $data);
            $tip = '保存成功！请进行二次确认提交并提交审核绑定微信，以方便后续消息接受';
            echo "<script>location.href='" . web_url('platform/platform_apply/display', array('step' => $step)) . "';</script>";
            exit;
        } else {
            $data['updatetime'] = TIMESTAMP;

            $openid = pdo_fetch("select openid,financial_price,financial_orderno from " . tablename('tg_platform_supplier') . " where id = " . $data['id']);

            if (($openid['openid'])) {
                $data['status'] = 0;
                $data['type'] = 0;
            }

            if (!($openid['openid'])) {
                $data['status'] = -1;
            }

            if ($openid['financial_orderno']) {
                $data['financial_price'] = 0;
                $data['financial_orderno'] = '';
            }

            pdo_update('tg_platform_supplier', $data, array('id' => $data['id']));
            $tip = '提交成功！';
            echo "<script>location.href='" . web_url('platform/platform_apply/display', array('step' => $step)) . "';</script>";
            exit;
        }

    }
    $supplier = pdo_fetch("select * from " . tablename('tg_platform_supplier') . " where uniacid = '{$uniacid}' ");
}

if ($op == 'xy') {
    $data['id'] = $_GPC['id'];
    $data['id_card_xy'] = $_GPC['imgurl'];
    $data['updatetime'] = TIMESTAMP;
    $data['status'] = 0;
    $data['type'] = 1;
    $openid = pdo_fetch("select openid,financial_price from " . tablename('tg_platform_supplier') . " where id = " . $data['id']);
    if (!empty($openid['openid'])) {
        $data['type'] = 0;
    }
    if (floatval($openid['financial_price']) > 0) {
        $data['type'] = 1;
    }

    $res = pdo_update('tg_platform_supplier', $data, array('id' => $data['id']));
    if ($res) {
        $message = '提交成功';
    } else {
        $message = '提交失败';
    }
    die(json_encode(array('status' => $res, 'message' => $message)));
}

if ($op == 'qr') {
    /*二维码*/
    wl_load()->classs('qrcode');
    $code = "c" . $_W['uniacid'] . random(4) . TIMESTAMP . random(4);
//    $url="http://wx866e0a731d303c29.w9.huodiesoft.com/app/index.php?i=53&c=entry&do=home&ac=scan_code&m=lexiangpingou&code=".$code;
    $url = "https://www.lexiangpingou.cn/app/index.php?i=33&c=entry&do=home&ac=scan_code&m=lexiangpingou&huodie_code=" . $code;
    $createqrcode = new creat_qrcode();
    $img_url = $createqrcode->createQrcode($url);
    die(json_encode(array('errno' => 0, 'url' => $img_url, 'code' => $code, 'to_url' => $url)));
}
if ($op == 'detail') {
    $id = $_GPC['id'];
    $supplier = pdo_fetch("select * from " . tablename('tg_platform_supplier') . " where id = '{$id}' ");
    die(json_encode($supplier));
}
if ($op == 'print') {
    include wl_template('platform/print');
    exit;
}

/*
 * 获取手机验证码
 */
if ($op == 'code') {
//    file_put_contents("aa.log", var_export(array('s' => "入口1", 'time' => TIMESTAMP), true) . PHP_EOL, FILE_APPEND);
    $username = trim($_GPC['mobile']);
    $uniacid = $_GPC['uniacid'];
    pdo_query('DELETE FROM ' . tablename('users_failed_login') . ' WHERE lastupdate < :timestamp', array(':timestamp' => TIMESTAMP - 1800));
    $failed = pdo_get('users_failed_login', array('username' => $username, 'ip' => CLIENT_IP));
    if ($failed['count'] >= 3) {
        $result = '30分钟内获取验证码次数超过3次，请在30分钟后重试';
        die(json_encode(array('result' => $result)));
    }
    session_start();
    if ($_GPC['retrieve'] == 1) {
//        $num = pdo_fetch("SELECT smsnum FROM " . tablename('account_wechats') . " WHERE uniacid = '{$uniacid}'");
//        $num = $num['smsnum'];
//        if ($num <= 0) {
//            $result = '抱歉，短信发送上限，请联系管理员';
//            die(json_encode(array('result' => $result)));
//        }

        header('content-type:text/html;charset=utf-8');

        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $_SESSION['smscode'] = mt_rand(100000, 999999);

//        $returnstatus = array('status' => 1, 'code' => $_SESSION['smscode']);
//        die(json_encode($returnstatus));
//        file_put_contents("aa.log", var_export(array('s' => "入口", 'time' => TIMESTAMP), true) . PHP_EOL, FILE_APPEND);
        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $_GPC['mobile'], //接受短信的用户手机号码
            'tpl_id' => '49498', //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => '#code#=' . $_SESSION['smscode'] //您设置的模板变量，根据实际情况修改
        );

        $content = codecurl($sendUrl, $smsConf, 1); //请求发送短信

        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //状态为0，说明短信发送成功
                //添加一次获取验证码信息
                if (empty($failed)) {
                    pdo_insert('users_failed_login', array('ip' => CLIENT_IP, 'username' => $username, 'count' => '1', 'lastupdate' => TIMESTAMP));
                } else {
                    pdo_update('users_failed_login', array('count' => $failed['count'] + 1, 'lastupdate' => TIMESTAMP), array('id' => $failed['id']));
                }
                $returnstatus = array('status' => 1, 'code' => $_SESSION['smscode']);
                pdo_update('account_wechats', array('smsnum' => $num - 1), array('uniacid' => $uniacid));
                echo json_encode($returnstatus);
                exit();
            } else {
                $returnstatus = array('status' => 0, 'reason' => $result['reason']);
                //状态非0，说明失败
                $msg = $result['reason'];
                echo json_encode($returnstatus);
                exit();
            }
        } else {
            //返回内容异常，以下可根据业务逻辑自行修改
            echo 3;
            exit();
        }
    }
    if ($_GPC['retrieve'] == 2) {
        if ($_GPC['num'] == $_SESSION['smscode']) {
            echo 1;
            exit();
        } else {
            echo $_SESSION['smscode'];
            exit();
        }
    }
//    file_put_contents("aa.log", var_export(array('s' => "结束", 'time' => TIMESTAMP), true) . PHP_EOL, FILE_APPEND);
    die(json_encode(array('data' => $returnstatus)));
}

/**
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function codecurl($url, $params = false, $ispost = 0)
{
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    $response = curl_exec($ch);
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}


include wl_template('platform/platform_apply');
exit();
?>