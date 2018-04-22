<?php
define('IN_API', true);
require_once './framework/bootstrap.inc.php';
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
load()->model('reply');
load()->app('common');
load()->classs('wesession');
load()->model('user');

/*
 * 获取手机验证码
 */
if ($op == 'code') {
    $username = trim($_GPC['mobile']);
    $uniacid = $_GPC['i'];
    $sms_tpl = '41305';
    pdo_query('DELETE FROM ' . tablename('users_failed_login') . ' WHERE lastupdate < :timestamp', array(':timestamp' => TIMESTAMP - 1800));
    $failed = pdo_get('users_failed_login', array('username' => $username, 'ip' => CLIENT_IP));
    if ($failed['count'] >= 3) {
        $result = '30分钟内获取验证码次数超过3次，请在30分钟后重试';
        die(json_encode(array('result' => $result)));
    }
    session_start();
    if ($_GPC['retrieve'] == 1) {

        $num = pdo_fetch("SELECT smsnum FROM " . tablename('account_wechats') . " WHERE uniacid = '{$uniacid}'");
        $num = $num['smsnum'];
        if ($num <= 0){
            $result = '抱歉，短信发送上限，请联系管理员';
            die(json_encode(array('result' => $result)));
        }

        header('content-type:text/html;charset=utf-8');
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $_SESSION['smscode'] = mt_rand(100000, 999999);

//        $returnstatus = array('status' => 1, 'code' => $_SESSION['smscode']);
//        die(json_encode($returnstatus));

        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $_GPC['mobile'], //接受短信的用户手机号码
            'tpl_id' => $sms_tpl, //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => '#code#=' . $_SESSION['smscode'] //您设置的模板变量，根据实际情况修改
        );

        $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信

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

    die(json_encode(array('data' => $returnstatus)));
}

if ($op == 'display') {
    $member = array();
    $member['username'] = trim($_GPC['username']);
    if (!preg_match(REGULAR_USERNAME, $member['username'])) {
        $result = '必须输入用户名。';
        die(json_encode(array('result' => $result, 'status' => -1)));
    }
    $password = $_GPC['password'];
    if (istrlen($password) < 8) {
        $result = '必须输入密码，且密码长度不得低于8位。';
        die(json_encode(array('result' => $result, 'status' => -1)));
    }
    $sql = "SELECT * FROM " . tablename('users') . " where username = '" . $member['username'] . "' ";
    $user = pdo_fetch($sql);
    $sql = "SELECT mobile FROM " . tablename('users_profile') . " where uid = '" . $user['uid'] . "' ";
    $profile = pdo_fetch($sql);
    $mobile = $_GPC['mobile'];
    if (empty($user)) {
        $result = '非常抱歉，无此用户名，请检查用户名是否正确！';
        die(json_encode(array('result' => $result, 'status' => -1)));
    } elseif ($profile['mobile'] != $mobile) {
        $result = '非常抱歉，此用户名与注册时保存的手机号码不同，请填写正确的手机号！';
        die(json_encode(array('result' => $result, 'status' => -1)));
    }

    $password = user_hash($password, $user['salt']);
    pdo_update('users', array('password' => $password), array('uid' => $user['uid']));
    $result = '密码重置成功！';
    pdo_delete('users_failed_login', array('username' => $mobile));
    die(json_encode(array('result' => $result , 'status' => 1)));

}

if ($op == 'change_logo'){
    $list = pdo_fetch("select platform_logo,platform_left_img from " .tablename('account_wechats') ." where uniacid = '{$_GPC['uniacid']}' ");
    $list['platform_logo'] = tomedia($list['platform_logo']);
    $list['platform_left_img'] = tomedia($list['platform_left_img']);
    die(json_encode($list));

}

/**
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function juhecurl($url, $params = false, $ispost = 0)
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

exit();
