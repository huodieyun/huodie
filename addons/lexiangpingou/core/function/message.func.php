<?php
//打款验证
function send_platform_sms($content,  $mobile, $name)
{
    global $_W;
    $wechat = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid=:uniacid", array(':uniacid' => $_W['uniacid']));
    if (preg_match("/^1[34578]{1}\d{9}$/", trim($mobile))) {
        //获取今天 本手机号 发送次数
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $mobile, //接受短信的用户手机号码
            'tpl_id' => '47141', //尊敬的用户，您#orderno#请注意及时参与！--#app#
            'tpl_value' => '#jxname#=' . $name . '&#jxno#='.$content  //您设置的模板变量，根据实际情况修改
        );
        $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信
        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //$result['result']['fee']  扣除短信条数
                $nowsmsnum = $wechat['smsnum'] - $result['result']['fee'];
                pdo_update('account_wechats', array('smsnum' => $nowsmsnum), array('uniacid' => $_W['uniacid']));
                $data = array(
                    'uniacid' => 33,
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time()
                );
                $mob = pdo_fetch("SELECT * FROM " . tablename('tg_sms_mobile') . " WHERE uniacid=:uniacid AND mobile=:mobile", array(':uniacid' => $data['uniacid'], ':mobile' => $mobile));
                if (empty($mob)) {
                    pdo_insert('tg_sms_mobile', $data);
                }
                $Bdata = array(
                    'uniacid' => 33,
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time(),
                    'content' => $content,
                    'num' => $result['result']['fee'],
                    'status' => 2
                );
                pdo_insert('tg_sms_record', $Bdata);
            }
        }

//        internal_log('platformsms' ,
//            array(
//                'ip' => CLIENT_IP,
//                'op' => "打款验证",
//                'filepath' => __FILE__,
//                'line' => __LINE__,
//                'content' => $content,
//                'time' => date('Y-m-d H:i:s', TIMESTAMP)
//            ));
    }
}

//退款
function send_platform_refund_sms($mobile, $tkno , $name , $jxno)
{
    global $_W;
    $wechat = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid=:uniacid", array(':uniacid' => $_W['uniacid']));
    if (preg_match("/^1[34578]{1}\d{9}$/", trim($mobile))) {
        //获取今天 本手机号 发送次数
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $mobile, //接受短信的用户手机号码
            'tpl_id' => '49482', //您好，我司已将退款单号：#tkno#款项汇入账户名:#jxname#账户尾号:#jxno#，请查收。
            'tpl_value' => '#tkno#=' . $tkno .'#jxname#=' . $name . '&#jxno#='.$jxno  //您设置的模板变量，根据实际情况修改
        );
        $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信
        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //$result['result']['fee']  扣除短信条数
                $nowsmsnum = $wechat['smsnum'] - $result['result']['fee'];
                pdo_update('account_wechats', array('smsnum' => $nowsmsnum), array('uniacid' => $_W['uniacid']));
                $data = array(
                    'uniacid' => 33,
                    'tkno' => $tkno,
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time()
                );
                $mob = pdo_fetch("SELECT * FROM " . tablename('tg_sms_mobile') . " WHERE uniacid=:uniacid AND mobile=:mobile", array(':uniacid' => $data['uniacid'], ':mobile' => $mobile));
                if (empty($mob)) {
                    pdo_insert('tg_sms_mobile', $data);
                }
                $Bdata = array(
                    'uniacid' => 33,
                    'tkno' => $tkno,
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time(),
                    'content' => $content,
                    'num' => $result['result']['fee'],
                    'status' => 2
                );
                pdo_insert('tg_sms_record', $Bdata);
            }
        }

//        internal_log('platformsms' ,
//            array(
//                'ip' => CLIENT_IP,
//                'op' => "退款",
//                'filepath' => __FILE__,
//                'line' => __LINE__,
//                'tkno' => $tkno,
//                'name' => $name,
//                'mobile' => $mobile,
//                'content' => $content,
//                'time' => date('Y-m-d H:i:s', TIMESTAMP)
//            ));
    }
}

//结算
function send_platform_account_sms($mobile, $jsno , $name , $jxno)
{
    global $_W;
    $wechat = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid=:uniacid", array(':uniacid' => $_W['uniacid']));
    if (preg_match("/^1[34578]{1}\d{9}$/", trim($mobile))) {
        //获取今天 本手机号 发送次数
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $mobile, //接受短信的用户手机号码
            'tpl_id' => '49483', //您好，我司已将结算单号：#jsno#款项汇入账户名:#jxname#账户尾号:#jxno#，请查收
            'tpl_value' => '#jsno#=' . $jsno .'#jxname#=' . $name . '&#jxno#='.$jxno  //您设置的模板变量，根据实际情况修改
        );
        $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信
        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //$result['result']['fee']  扣除短信条数
                $nowsmsnum = $wechat['smsnum'] - $result['result']['fee'];
                pdo_update('account_wechats', array('smsnum' => $nowsmsnum), array('uniacid' => $_W['uniacid']));
                $data = array(
                    'uniacid' => 33,
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time()
                );
                $mob = pdo_fetch("SELECT * FROM " . tablename('tg_sms_mobile') . " WHERE uniacid=:uniacid AND mobile=:mobile", array(':uniacid' => $data['uniacid'], ':mobile' => $mobile));
                if (empty($mob)) {
                    pdo_insert('tg_sms_mobile', $data);
                }
                $Bdata = array(
                    'uniacid' => 33,
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time(),
                    'content' => $content,
                    'num' => $result['result']['fee'],
                    'status' => 2
                );
                pdo_insert('tg_sms_record', $Bdata);
            }
        }

//        internal_log('platformsms' ,
//            array(
//                'ip' => CLIENT_IP,
//                'op' => "结算",
//                'filepath' => __FILE__,
//                'line' => __LINE__,
//                'content' => $content,
//                'time' => date('Y-m-d H:i:s', TIMESTAMP)
//            ));
    }
}

//入驻
function send_platform_apply_sms($mobile, $code , $type)
{
    global $_W;
    $wechat = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid=:uniacid", array(':uniacid' => $_W['uniacid']));
    if (preg_match("/^1[34578]{1}\d{9}$/", trim($mobile))) {
        //获取今天 本手机号 发送次数
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        if ($type == 1) {
            $tpl_id = '49496';
        } else {
            $tpl_id = '49498';
        }
        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $mobile, //接受短信的用户手机号码
            'tpl_id' => $tpl_id, //欢迎您参与极限单品商家/供应商入驻，您的验证码是#code#，本验证码30分钟内有效。请勿告诉其他人员。
            'tpl_value' => '#code#=' . $code  //您设置的模板变量，根据实际情况修改
        );
        $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信
        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //$result['result']['fee']  扣除短信条数
                $nowsmsnum = $wechat['smsnum'] - $result['result']['fee'];
                pdo_update('account_wechats', array('smsnum' => $nowsmsnum), array('uniacid' => $_W['uniacid']));
                $data = array(
                    'uniacid' => 33,
                    'mobile' => $mobile,
                    'createtime' => time()
                );
                $mob = pdo_fetch("SELECT * FROM " . tablename('tg_sms_mobile') . " WHERE uniacid=:uniacid AND mobile=:mobile", array(':uniacid' => $data['uniacid'], ':mobile' => $mobile));
                if (empty($mob)) {
                    pdo_insert('tg_sms_mobile', $data);
                }
                $Bdata = array(
                    'uniacid' => 33,
                    'mobile' => $mobile,
                    'createtime' => time(),
                    'content' => $content,
                    'num' => $result['result']['fee'],
                    'status' => 2
                );
                pdo_insert('tg_sms_record', $Bdata);
            }
        }

//        internal_log('platformsms' ,
//            array(
//                'ip' => CLIENT_IP,
//                'op' => "入驻",
//                'filepath' => __FILE__,
//                'line' => __LINE__,
//                'content' => $content,
//                'time' => date('Y-m-d H:i:s', TIMESTAMP)
//            ));
    }
}

function sendsms($content, $m_type, $mobile, $name)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("sms");
    $m_daipay = $setting[$m_type];
    $wechat = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid=:uniacid", array(':uniacid' => $_W['uniacid']));
    if ($wechat['smsnum'] > 0 && $m_daipay == 'true' && preg_match("/^1[34578]{1}\d{9}$/", trim($mobile))) {
        //获取今天 本手机号 发送次数
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
        $smsConf = array(
            'key' => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile' => $mobile, //接受短信的用户手机号码
            'tpl_id' => '20283', //尊敬的用户，您#orderno#请注意及时参与！--#app#
            'tpl_value' => '#orderno#=' . $content . '&#app#=' . $_W['uniaccount']['name'] //您设置的模板变量，根据实际情况修改
        );
        $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信
        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //$result['result']['fee']  扣除短信条数
                $nowsmsnum = $wechat['smsnum'] - $result['result']['fee'];
                pdo_update('account_wechats', array('smsnum' => $nowsmsnum), array('uniacid' => $_W['uniacid']));
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time()
                );
                $mob = pdo_fetch("SELECT * FROM " . tablename('tg_sms_mobile') . " WHERE uniacid=:uniacid AND mobile=:mobile", array(':uniacid' => $data['uniacid'], ':mobile' => $mobile));
                if (empty($mob)) {
                    pdo_insert('tg_sms_mobile', $data);
                }
                $Bdata = array(
                    'uniacid' => $_W['uniacid'],
                    'name' => $name,
                    'mobile' => $mobile,
                    'createtime' => time(),
                    'content' => $content,
                    'num' => $result['result']['fee'],
                    'status' => 2
                );
                pdo_insert('tg_sms_record', $Bdata);
            }
        }

//        internal_log('sms' ,
//                array(
//                    'ip' => CLIENT_IP,
//                    'op' => "短信",
//                    'filepath' => __FILE__,
//                    'line' => __LINE__,
//                    'setting' => $setting,
//                    'wechat' => $wechat,
//                    'content' => $content,
//                    'time' => date('Y-m-d H:i:s', TIMESTAMP)
//                ));
    }
}

function nopay_success($openid, $price, $orderno, $goodsname, $time, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_daipay = $setting['m_nocash'];
    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：主人，我在购物车里都快发霉了，不仅要面对黑暗，还要遭受相思之苦，赶快付款把我带走吧，人家想死你啦！", "color" => "#4a5077"),
        "type" => array('value' => '商品', "color" => "#4a5077"),
        "e_title" => array('value' => $goodsname, "color" => "#4a5077"),
        "o_id" => array('value' => $orderno, "color" => "#4a5077"),
        "order_date" => array('value' => $time, "color" => "#4a5077"),
        "o_money" => array('value' => "￥" . $price, "color" => "#4a5077"),
        "remark" => array("value" => '库存有限，商品卖完就没有了，快快完成支付吧！【一键支付】>>', "color" => "#4a5077"),
    );
    $content = "您有一个订单未支付！订单编号：" . $orderno . "下单时间：" . $time . "价格：" . $price . "库存有限，商品卖完就没有了，快快完成支付吧！";
    sendsms($content, 'm_smsnocash', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_daipay, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "未支付",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'openid' => $openid,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'content' => $content,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        nopay_success($openid, $price, $orderno, $goodsname, $time, $url , $a);
    }

}

function daipay_success($openid, $price, $name, $orderno, $goodsname, $time, $message, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_daipay = $setting['m_daipay'];
    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：代付成功啦！！！", "color" => "#4a5077"),
        "keyword1" => array('value' => $orderno, "color" => "#4a5077"),
        "keyword2" => array('value' => $name, "color" => "#4a5077"),
        "keyword3" => array('value' => "￥" . $price . "[" . $goodsname . "]", "color" => "#4a5077"),
        "keyword4" => array('value' => $time, "color" => "#4a5077"),
        "keyword5" => array('value' => $message, "color" => "#4a5077"),
        "remark" => array("value" => '点击查看详情', "color" => "#4a5077"),
    );
    $content = "您的朋友为您代付成功啦！订单编号：" . $orderno . "下单时间：" . $time . "价格：" . $price;
    sendsms($content, 'm_smsdaipay', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_daipay, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "代付",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'content' => $content,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        daipay_success($openid, $price, $name, $orderno, $goodsname, $time, $message, $url , $a);
    }

}

function pro_change($openid, $title, $typename, $goodsname, $time, $message, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_change = $setting['m_change'];
//    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
//    if ($ordno['merchantid'] == 0){
//        $merchant = pdo_fetch("select name from " .tablename('account_wechats') ." where uniacid = '{$_W['uniacid']}'");
//    }else {
//        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
//    }
    $postdata = array(
        "first" => array("value" => $title, "color" => "#4a5077"),
        "keyword1" => array('value' => $typename, "color" => "#4a5077"),
        "keyword2" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword3" => array('value' => $time, "color" => "#4a5077"),
        "keyword4" => array('value' => $message, "color" => "#4a5077"),
        "remark" => array("value" => '点击查看详情', "color" => "#4a5077"),
    );
    //	$content=$title.$orderno."下单时间：".$time."价格：".$price;
    //$ordno=pdo_fetch('select mobile,addname from '.tablename('tg_order')." where orderno=':onrderno'",array(':orderno'=>$orderno));
    //sendsms($content,'m_change',$ordno['mobile'],$ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_change, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "下单",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $openid,
                'goodsname' => $goodsname,
                'postdata' => $postdata,
                'message' => $message,
                'url' => $url,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        pro_change($openid, $title, $typename, $goodsname, $time, $message, $url , $a);
    }

}

function pay_onesuccess($orderno, $openid, $price, $ptime, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_buy = $setting['m_buy'];

    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：您已成功付款!!!!!", "color" => "#4a5077"),
        "keyword1" => array('value' => $orderno, "color" => "#4a5077"),
        "keyword2" => array('value' => "￥" . $price, "color" => "#4a5077"),
        "keyword3" => array('value' => date('Y-m-d H:i:s', $ptime), "color" => "#4a5077"),

        "remark" => array("value" => '点击查看详情', "color" => "#4a5077"),
    );
    $content = "您已成功付款！订单编号：" . $orderno . "下单时间：" . date('Y-m-d H:i:s', $ptime) . "价格：" . $price;

    sendsms($content, 'm_smsbuy', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_buy, $postdata, $url);
    $a++;

    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "成功付款one",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'content' => $content,
                'err_msg' => $err_msg,
                'a' => $a,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        pay_onesuccess($orderno, $openid, $price, $ptime, $url , $a);
    }

}

function pay_success($orderno, $openid, $neednum, $price, $goodsname, $url, $message , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_pay = $setting['m_pay'];

    $ordno = pdo_fetch('select mobile,addname,merchantid,tuan_first,com_type,tuan_id,commission,commissiontype,g_id from ' . tablename('tg_order') . " where orderno='{$orderno}'");

    if ($ordno['com_type'] == 2 && $ordno['tuan_first'] == 1) {
        $commander = setting_get_by_name("commander");
        if ($commander['apply'] == 1) {
            $member = pdo_fetch("select apply_status from " . tablename('tg_member') . " where uniacid = '{$_W['uniacid']}' and from_user = '{$openid}' ");
        }
        $good = pdo_get('tg_goods', array('id' => $ordno['g_id']));
        if ($good['commander_check'] == 0) {
            $member['apply_status'] = 1;
        }
        if (intval($member['apply_status']) == 1) {

            $com = pdo_get('tg_commander', array('tuan_id' => $ordno['tuan_id']));

            if (!$com) {
                $dat['uniacid'] = $_W['uniacid'];
                $dat['openid'] = $openid;
                $dat['tuan_id'] = $ordno['tuan_id'];
                $dat['commissiontype'] = $ordno['commissiontype'];
                $dat['commission'] = $ordno['commission'];
                $dat['gid'] = $ordno['g_id'];
                $dat['createtime'] = TIMESTAMP;
                pdo_insert('tg_commander', $dat);
            }
        }
    }
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：您已成功付款", "color" => "#4a5077"),
        "keyword1" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword2" => array('value' => $orderno, "color" => "#4a5077"),
        "keyword3" => array('value' => $neednum, "color" => "#4a5077"),

        "remark" => array("value" => $message, "color" => "#4a5077"),
    );
    $content = "您已成功付款！订单编号：" . $orderno . "下单时间：" . date('Y-m-d H:i:s', time());

    sendsms($content, 'm_smspay', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_pay, $postdata, $url);

    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "成功付款",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'content' => $content,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        pay_success($orderno, $openid, $neednum, $price, $goodsname, $url, $message , $a);
    }

}

function choujian_success($orderno, $tuan_id, $openid, $goodsname, $url, $message , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_pay = $setting['m_tuan'];
    $tuan_first_order = pdo_fetch("select openid,g_id,address,mobile from" . tablename('tg_order') . "where godluck=1 and tuan_id='{$tuan_id}'");
    $profile = pdo_fetch("select nickname from" . tablename('mc_mapping_fans') . "where openid = '{$tuan_first_order['openid']}'");
    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：恭喜您获得本次活动产品,我们将尽快为您发货", "color" => "#4a5077"),
        "keyword1" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword2" => array('value' => $profile['nickname'], "color" => "#4a5077"),
        "remark" => array("value" => $message, "color" => "#4a5077"),
    );
    $content = "恭喜您获得本次活动产品" . $goodsname . ",我们将尽快为您发货";
    $ordno = pdo_fetch('select mobile,addname from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    sendsms($content, 'm_smstuan', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_pay, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "抽奖",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'content' => $content,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        choujian_success($orderno, $tuan_id, $openid, $goodsname, $url, $message , $a);
    }

}

function group_success($tuan_id, $url)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_tuan = $setting['m_tuan'];
    $alltuan = pdo_fetchall("select openid,id,tuan_first,mobile,address,dispatchtype,orderno,tuan_id,addname from" . tablename('tg_order') . "where tuan_id = '{$tuan_id}' and mobile<>'虚拟' and status in(1,2,3,8)");
    $tuan_first_order = pdo_fetch("select openid,g_id,address,mobile from" . tablename('tg_order') . "where tuan_first=1 and tuan_id='{$tuan_id}'");
    $profile = pdo_fetch("select nickname from" . tablename('mc_mapping_fans') . "where openid = '{$tuan_first_order['openid']}'");
    $goods = pdo_fetch("select gname,selltype from" . tablename('tg_goods') . "where id = '{$tuan_first_order['g_id']}'");
    if ($goods['selltype'] == 2) {
        $contentb .= "团员:";
        foreach ($alltuan as $num => $all) {
            $pname = pdo_fetch("SELECT nickname FROM " . tablename('tg_member') . " WHERE uniacid ='{$_W['uniacid']}' and from_user = '{$all['openid']}'");
            $item[$num] = $all['id'];
            if ($all['tuan_first'] != 1) {
                $contentb .= $pname['nickname'] . ",联系电话:" . $all['mobile'] . "    ";
            }
            if ($all['tuan_first'] == 1) {
                $tuan_firstopenid = $all['openid'];
                $tuan_add = $all['address'];
                $tuan_tel = $all['mobile'];
            }
        }
        $contenta = "团长:" . $profile['nickname'] . "<br>";
        $content1 = $contentb . "\n" . "取件地址:" . $tuan_first_order['address'] . "\n" . "团长电话:" . $tuan_first_order['mobile'];
    } else {
        $content1 = "对于各位大侠的帮助，团长" . $profile['nickname'] . "感激涕零,我们一起坐等通知";
    }
    foreach ($alltuan as $key => $value) {
        if ($value['dispatchtype'] == 3) {
            $contents = "请于本品有效期内到店提货,点击查看自提二维码";
        } else {
            $contents = $content1;
        }
        /*团成功通知*/
        $url = app_url('order/group', array('tuan_id' => $value['tuan_id']));
        if ($value['dispatchtype'] == 3) {
            $url = app_url('order/order/detail', array('id' => $value['id'], 'b' => 1));
        }
        $postdata = array(
            "first" => array("value" => "恭喜组团成功   ！！！", "color" => "#4a5077"),
            "keyword1" => array('value' => $goods['gname'], "color" => "#4a5077"),
            "keyword2" => array('value' => $profile['nickname'], "color" => "#4a5077"),
            "remark" => array("value" => $contents, "color" => "#4a5077"),
        );
        $content = "恭喜" . $goods['gname'] . "组团成功,我们将尽快为您发货！";
        sendsms($content, 'm_smstuan', $value['mobile'], $value['addname']);
        $err_msg = sendTplNotice($value['openid'], $m_tuan, $postdata, $url);

        if (($err_msg['errno'] == -1)) {
            internal_log('message',
                array(
                    'ip' => CLIENT_IP,
                    'op' => "组团成功",
                    'filepath' => __FILE__,
                    'line' => __LINE__,
                    'setting' => $setting,
                    'tuan_id' => $tuan_id,
                    'tuan' => $value,
                    'postdata' => $postdata,
                    'openid' => $value['openid'],
                    'content' => $content,
                    'err_msg' => $err_msg,
                    'time' => date('Y-m-d H:i:s', TIMESTAMP)
                ));
        }
    }
}

function hexiao_success($goodsname, $openid, $num, $hexiaotime, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_hexiao = $setting['m_hexiao'];
    $postdata = array(
        "first" => array("value" => "亲，您的商品已发货!!!", "color" => "#4a5077"),
        "keyword1" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword2" => array('value' => $num, "color" => "#4a5077"),
        "keyword3" => array("value" => date('Y-m-d H:i:s', $hexiaotime), "color" => "#4a5077"),
        "remark" => array("value" => "", "color" => "#4a5077"),
    );
    $err_msg = sendTplNotice($openid, $m_hexiao, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "核销发货",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'goodsname' => $goodsname,
                'num' => $num,
                'postdata' => $postdata,
                'openid' => $openid,
                'url' => $url,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        hexiao_success($goodsname, $openid, $num, $hexiaotime, $url , $a);
    }

}

function send_success($orderno, $openid, $express, $expressn, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_send = $setting['m_send'];
    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：亲，您的商品已发货!!!", "color" => "#4a5077"),
        "keyword1" => array('value' => $orderno, "color" => "#4a5077"),
        "keyword2" => array('value' => $express, "color" => "#4a5077"),
        "keyword3" => array("value" => $expressn, "color" => "#4a5077"),
        "remark" => array("value" => "", "color" => "#4a5077"),
    );
    $content = "亲，您的商品已发货！订单编号：" . $orderno . "快递公司：" . $express . "快递单号：" . $expressn . "请注意查收。";
    $ordno = pdo_fetch('select mobile,addname from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    sendsms($content, 'm_smssend', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_send, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "快递发货",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'content' => $content,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        send_success($orderno, $openid, $express, $expressn, $url , $a);
    }

}

function dispatch_success($gname, $orderno, $openid, $nickname, $mobile, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_dispatch = $setting['m_dispatch'];
    $ordno = pdo_fetch('select mobile,addname,merchantid,storeid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    $dispatch = pdo_fetch("select nickname,tel from " . tablename('tg_delivery_man') . " where id = '{$ordno['storeid']}' ");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $content = "亲！派送员将货物送至时，请将收货二维码给派送员扫码以确认收货【代收请将二维码截图，并告知派送员代收】";
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：亲，您的商品即将派送!!!", "color" => "#4a5077"),
        "keyword1" => array('value' => $gname, "color" => "#4a5077"),
        "keyword2" => array('value' => $orderno, "color" => "#4a5077"),
        "keyword3" => array("value" => $dispatch['nickname'], "color" => "#4a5077"),
        "keyword4" => array("value" => $dispatch['tel'], "color" => "#4a5077"),
        "remark" => array("value" => $content, "color" => "#4a5077"),
    );

    sendsms($content, 'm_smsdispatch', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_dispatch, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "订单派送",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'dispatch' => $dispatch,
                'content' => $content,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        dispatch_success($gname, $orderno, $openid, $nickname, $mobile, $url , $a);
    }

}

function refund_success($orderno, $goodsname, $openid, $price, $ttime, $url , $refund_res = '' , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_ref = $setting['m_ref'];
    $ordno = pdo_fetch('select mobile,addname,refund_res,selltype from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if (!$refund_res) {
        $refund_res = '组团失败退款';
        if ($ordno['selltype'] == 4) {
            $refund_res = '阶梯团差额返还';
        }
        if (!empty($ordno['refund_res'])) {
            $refund_res = $ordno['refund_res'];
        }
    }
    $profile = pdo_fetch("select nickname from" . tablename('mc_mapping_fans') . "where openid = '{$openid}'");

    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：您已退款成功,点击查看详情!", "color" => "#4a5077"),
        "keyword1" => array('value' => $orderno, "color" => "#4a5077"),
        "keyword2" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword3" => array('value' => $profile['nickname'], "color" => "#4a5077"),
        "keyword4" => array('value' => "￥" . $price, "color" => "#4a5077"),
        "keyword5" => array('value' => date('Y-m-d H:i:s', $ttime), "color" => "#4a5077"),
        "remark" => array("value" => '退款原因：' . $refund_res, "color" => "#4a5077"),
    );
    $content = "您已成退款成功！订单编号：" . $orderno . "订单价格：" . $price . "退款时间：" . date('Y-m-d H:i:s', $ttime) . "请注意查看微信零钱。";

    sendsms($content, 'm_smsref', $ordno['mobile'], $ordno['addname']);
    $err_msg = sendTplNotice($openid, $m_ref, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "组团失败退款",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'content' => $content,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        refund_success($orderno, $goodsname, $openid, $price, $ttime, $url , $refund_res , $a);
    }

}

//任务处理通知
function result_type($openid, $title, $message, $remark, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_ref = $setting['m_result'];
    $postdata = array(
        "first" => array("value" => $title, "color" => "#4a5077"),
        "keyword1" => array('value' => $title, "color" => "#4a5077"),
        "keyword2" => array('value' => $message, "color" => "#4a5077"),
        "remark" => array("value" => $remark, "color" => "#4a5077"),
    );
    $err_msg = sendTplNotice($openid, $m_ref, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "任务处理通知",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'postdata' => $postdata,
                'openid' => $openid,
                'message' => $message,
                'remark' => $remark,
                'url' => $url,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        result_type($openid, $title, $message, $remark, $url , $a);
    }

}

//补款通知
function bukuan_notice($openid, $title, $goodsname, $orderno, $datetime, $remark, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_ref = $setting['m_bukuan'];
    $ordno = pdo_fetch('select mobile,addname,merchantid from ' . tablename('tg_order') . " where orderno='{$orderno}'");
    if ($ordno['merchantid'] == 0) {
        $merchant = pdo_fetch("select name from " . tablename('account_wechats') . " where uniacid = '{$_W['uniacid']}'");
    } else {
        $merchant = pdo_fetch("select name from " . tablename('tg_merchant') . " where uniacid = '{$_W['uniacid']}' and id = '{$ordno['merchantid']}'");
    }
    $postdata = array(
        "first" => array("value" => "【" . $merchant['name'] . "】：" . $title, "color" => "#4a5077"),
        "keyword1" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword2" => array('value' => $orderno, "color" => "#4a5077"),
        "keyword3" => array('value' => $datetime, "color" => "#4a5077"),
        "remark" => array("value" => $remark, "color" => "#4a5077"),
    );

    $err_msg = sendTplNotice($openid, $m_ref, $postdata, $url);

    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "补款通知",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'ordno' => $ordno,
                'merchant' => $merchant,
                'postdata' => $postdata,
                'openid' => $openid,
                'url' => $url,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        bukuan_notice($openid, $title, $goodsname, $orderno, $datetime, $remark, $url , $a);
    }

}

/*
 * 参团人数不足通知
 * {{first.DATA}}
团购商品：{{keyword1.DATA}}
剩余拼团时间：{{keyword2.DATA}}
剩余拼团人数：{{keyword3.DATA}}
{{remark.DATA}}
 */
function no_num_success($openid, $title, $goodsname, $lasttime, $neednum, $remark, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_ref = $setting['m_no_num_success'];
    $postdata = array(
        "first" => array("value" => $title, "color" => "#e4393c"),
        "keyword1" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword2" => array('value' => $lasttime, "color" => "#4a5077"),
        "keyword3" => array('value' => $neednum, "color" => "#4a5077"),
        "remark" => array("value" => $remark, "color" => "#4a5077"),
    );
    $err_msg = sendTplNotice($openid, $m_ref, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "组团人数不足通知",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'm_ref' => $m_ref,
                'postdata' => $postdata,
                'openid' => $openid,
                'url' => $url,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        no_num_success($openid, $title, $goodsname, $lasttime, $neednum, $remark, $url , $a);
    }

}

/*function cancelorder($openid, $price, $goodsname, $orderno,  $url) {
    global $_W;
    wl_load() -> model('setting');
    $setting = setting_get_by_name("message");
    $m_cancle = $setting['m_cancle'];
    $content = "取消订单通知";
    $postdata  = array(
                "first"=>array( "value"=> "取消订单通知","color"=>"#4a5077"),
                "keyword5"=>array('value' => "￥".$price."[未支付]", "color" => "#4a5077"),
                "keyword3"=>array('value' => $goodsname, "color" => "#4a5077"),
                "keyword2"=>array("value"=>$_W['uniaccount']['name'], "color" => "#4a5077"),
                "keyword1"=>array("value"=>$orderno, "color" => "#4a5077"),
                "keyword4"=>array("value"=>"1", "color" => "#4a5077"),
                "remark"=>array("value"=>"", "color" => "#4a5077"),
            );
       sendTplNotice($openid, $m_cancle, $postdata,$url);

}*/
function hex($openid, $content, $goodsname, $goodsum, $time, $remark, $url , $a = 0)
{
    global $_W;
    $setting = setting_get_by_name("message");
    $m_ref = $setting['m_check'];
    $postdata = array(
        "first" => array("value" => $content, "color" => "#e4393c"),
        "keyword1" => array('value' => $goodsname, "color" => "#4a5077"),
        "keyword2" => array('value' => $goodsum, "color" => "#4a5077"),
        "keyword3" => array('value' => $time, "color" => "#4a5077"),
        "remark" => array("value" => $remark, "color" => "#4a5077")
    );
//		return $postdata;
    $err_msg = sendTplNotice($openid, $m_ref, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "核销",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'm_ref' => $m_ref,
                'postdata' => $postdata,
                'openid' => $openid,
                'url' => $url,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        hex($openid, $content, $goodsname, $goodsum, $time, $remark, $url , $a);
    }

    return $setting;
}
//服务进度通知
function m_service_process($openid, $first, $keyword1, $keyword2, $keyword3,$keyword4, $remark, $url , $a = 0)
{
    global $_W;
    wl_load()->model('setting');
    $setting = setting_get_by_name("message");
    $m_ref = $setting['m_service_process'];
    $postdata = array(
        "first" => array("value" => $first, "color" => "#e4393c"),
        "keyword1" => array('value' => $keyword1, "color" => "#4a5077"),
        "keyword2" => array('value' => $keyword2, "color" => "#4a5077"),
        "keyword3" => array('value' => $keyword3, "color" => "#4a5077"),
        "keyword4" => array('value' => $keyword4, "color" => "#4a5077"),
        "remark" => array("value" => $remark, "color" => "#4a5077")
    );
//		return $postdata;
    $err_msg = sendTplNotice($openid, $m_ref, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "服务进度通知",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'm_ref' => $m_ref,
                'postdata' => $postdata,
                'openid' => $openid,
                'url' => $url,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        m_service_process($openid, $first, $keyword1, $keyword2, $keyword3,$keyword4, $remark, $url , $a );
    }

    return $setting;
}

/**
 * 售后处理通知
 * @param  string  $openid   通知人员的openid
 * @param  string  $first    标题
 * @param  string  $keyword1 售后订单号
 * @param  string  $keyword2 售后状态
 * @param  string  $keyword3 申请时间
 * @param  string  $remark   备注
 * @param  string  $url      模版消息跳转链接
 * @param  integer $a        数字
 * @return array   $setting  返回数组
 */
function send_service($openid, $first, $keyword1, $keyword2, $keyword3, $remark, $url, $a = 0)
{
    $setting = setting_get_by_name("message");
    $m_send = $setting['m_after_sale'];
    $postdata = array(
        "first" => array("value" => $first, "color" => "#4a5077"),
        "keyword1" => array('value' => $keyword1, "color" => "#4a5077"),
        "keyword2" => array('value' => $keyword2, "color" => "#4a5077"),
        "keyword3" => array("value" => $keyword3, "color" => "#4a5077"),
        "remark" => array("value" => $remark, "color" => "#4a5077"),
    );
    $err_msg = sendTplNotice($openid, $m_send, $postdata, $url);
    $a++;
    if (($err_msg['errno'] == -1) && $a < 3){
        internal_log('message' ,
            array(
                'ip' => CLIENT_IP,
                'op' => "售后提醒",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'setting' => $setting,
                'postdata' => $postdata,
                'openid' => $openid,
                'err_msg' => $err_msg,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
        send_service($openid, $first, $keyword1, $keyword2, $keyword3, $remark, $url, $a );
    }

    return $setting;
}
?>
