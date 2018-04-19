<?php
$_W['page']['title'] = "提成管理";

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

$groupstatus = intval($_GPC['groupstatus']);

$uniacid = $_W['uniacid'];

pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_member_group_reason` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(10) unsigned NOT NULL COMMENT '公众账号id',
  `group_id` int(10) unsigned NOT NULL COMMENT '组长id',
  `cash_id` int(10) unsigned NOT NULL COMMENT '提现表id',
  `openid` int(10) unsigned NOT NULL COMMENT '组长openid',
  `uni_reason` text COMMENT '公众号理由',
  `group_reason` text COMMENT '组长理由',
  `status` int(10) NOT NULL COMMENT '提现状态',
  `updatetime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_cash_id` (`cash_id`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_openid` (`openid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
//添加组长提成表
pdo_query("CREATE TABLE IF NOT EXISTS `cm_tg_member_group_commission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_no` varchar(50) NOT NULL COMMENT '提现单号',
  `uniacid` int(10) unsigned NOT NULL COMMENT '公众账号id',
  `group_id` int(10) unsigned NOT NULL COMMENT '组长id',
  `openid` varchar(50) COMMENT '组长openid',
  `cash_id` text COMMENT '组员提现id',
  `commission` int(11) COMMENT '组长提成比',
  `apply` DOUBLE(10,2) NOT NULL DEFAULT 0.00 COMMENT '申请提现金额',
  `createtime` int(11) COMMENT '申请时间',
  `get` DOUBLE(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际可提现金额',
  `give` DOUBLE(10,2) NOT NULL DEFAULT 0.00 COMMENT '已发放金额',
  `give_time` int(11) COMMENT '发放时间',
  `status` int(1) NOT NULL DEFAULT 1 COMMENT '提现状态',
  `single` VARCHAR(100) COMMENT '银行流水单号',
  `thumb` VARCHAR(255) COMMENT '银行流水单',
  `updatetime` int(11) COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `idx_group_no` (`group_no`),
  KEY `idx_uniacid` (`uniacid`),
  KEY `idx_openid` (`openid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists('tg_cashrecord', 'account')) {
    pdo_query("ALTER TABLE ".tablename('tg_cashrecord')." ADD `account` int(1) NOT NULL default 0 COMMENT '组长是否结算';");
}
if(!pdo_fieldexists('tg_cashrecord', 'account_time')) {
    pdo_query("ALTER TABLE ".tablename('tg_cashrecord')." ADD `account_time` int(11) COMMENT '组长结算时间';");
}
if(!pdo_fieldexists('tg_cashrecord', 'commission_id')) {
    pdo_query("ALTER TABLE ".tablename('tg_cashrecord')." ADD `commission_id` int(11) COMMENT '组长提现表id';");
}
if(!pdo_fieldexists('tg_cashrecord', 'get_status')) {
    pdo_query("ALTER TABLE ".tablename('tg_cashrecord')." ADD `get_status` int(1) NOT NULL default 0 COMMENT '审核状态';");
}
if(!pdo_fieldexists('tg_cashrecord', 'uni_status')) {
    pdo_query("ALTER TABLE ".tablename('tg_cashrecord')." ADD `uni_status` int(1) NOT NULL default 0 COMMENT '最终状态';");
}

if ($op == 'display') {

    $selltype = $_GPC['selltype'];

    $pindex = max(1, intval($_GPC['page']));
    $psize = 10;
    /*搜索条件*/
    $condition = "uniacid = {$_W['uniacid']}";
    $time = $_GPC['time'];
    if (empty($starttime) || empty($endtime)) {
        $starttime = strtotime('-1 month');
        $endtime = time();
    }
    if (!empty($_GPC['time'])) {
        $starttime = strtotime($_GPC['time']['start']);
        $endtime = strtotime($_GPC['time']['end']) + 86399;
        $condition .= " AND  createtime >= {$starttime} AND  createtime <= {$endtime} ";

    }
    if (!empty($_GPC['keyword'])) {
        $condition .= " AND openid in (select from_user from " . tablename('tg_member') . " where nickname LIKE '%{$_GPC['keyword']}%') ";
    }
    $groupstatus = intval($_GPC['groupstatus']);
    $status = $groupstatus + 1;
    $condition .= " AND status = '{$status}'";


    //message($condition);
    /*搜索条件*/
    $alltuan = pdo_fetchall("select * from " . tablename('tg_member_group_commission') . " where $condition order by id desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

    $alltuan2 = pdo_fetchall("select * from " . tablename('tg_member_group_commission') . " where $condition order by id desc ");
    $total = count($alltuan2);
    $pager = pagination($total, $pindex, $psize);

}

//提现列表
if($op == 'record') {
    $id = $_GPC['id'];

    $page = $_GPC['page'];
    $size = 10;
    $page = !empty($page) ? intval($_GPC['page']): 1;
    $orderby = ' order by id';

    $commission = pdo_fetch("select * from " .tablename('tg_member_group_commission') ." where id = " .$id);

    $sql = " where uniacid = '{$uniacid}' ";
    $sql .= " and openid in ( select openid from " . tablename('tg_member_group_detail') . " where uniacid = '{$uniacid}' and parentid = '{$commission['group_id']}' ) ";
    $total = pdo_fetchcolumn("select count(*) from " . tablename('tg_cashrecord') .$sql);

    $sql .= $orderby . " limit " . ($page - 1) * $size . " , " . $size;
    $list = pdo_fetchall("select * from " . tablename('tg_cashrecord') .$sql);

    $pager = pagination($total, $page, $size);

    foreach ($list as &$value) {
        $value['commission'] = $commission['commission'];
        $value['commission_price'] = $value['price'] * $commission['commission'] * 0.01;
    }

//    提现审核通过
    if (checksubmit('submit')) {
        $id = $_GPC['id'];
        $data['status'] = 2;
        $data['updatetime'] = TIMESTAMP;

        pdo_update('tg_member_group_commission' , $data , array('id' => $id));

        $list = pdo_fetchall("select * from " .tablename('tg_cashrecord') ." where commission_id = " .$id ." and account = 1 and get_status = 0");
        foreach ($list as &$value) {
            pdo_update('tg_cashrecord' , array('get_status' => 2) ,array('id' => $value['id']));
        }

        $account = pdo_fetch("SELECT * FROM " . tablename('tg_member_group_commission') . " where id = " .$id);
        message('确认成功！将直接跳转到打款页面！', web_url('member/commission' , array('op' => 'display' , 'groupstatus' => 2)),  'success');

    }
    include wl_template('member/commission');
}

if($op == 'play') {
    $id = $_GPC['id'];

    $account = pdo_fetch("select * from " .tablename('tg_member_group_commission') ." where id = " .$id);

//    提现
    if (checksubmit('submit')) {
        $id = $_GPC['id'];
        $data = $_GPC['account']; // 获取打包值
        $data['status'] = 3;
        $data['updatetime'] = TIMESTAMP;
        $data['give'] = $account['get'];
        $data['give_time'] = TIMESTAMP;
        pdo_update('tg_member_group_commission' , $data , array('id' => $id));

        foreach ($list as &$value) {
            if ($value['get_status'] == 0){
                pdo_update('tg_cashrecord' , array('get_status' => 1) , array('id' => $value['id']));
            }
        }

        message('打款成功！', web_url('member/commission' , array('op' => 'display' , 'groupstatus' => 3)),  'success');
    }
}

//线上打款
if($op == 'sendcash') {
    load()->func('communication');
    load()->model('account');
    wl_load()->model('setting');

    $id = intval($_GPC['id']);
    $set = setting_get_by_name('refund');


    $m=pdo_fetch("SELECT * FROM ".tablename('tg_member_group_commission')." WHERE id = {$id}");
    $member=pdo_fetch("SELECT * FROM ".tablename('tg_member')." WHERE from_user = '{$m['openid']}' and uniacid='{$_W['uniacid']}'");
    $pri=$m['get'];

    $url5 = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    $apikey=$set['apikey'];
    $pars = array();
    $pars['mch_appid'] =$_W['account']['key'];//身份标识（appid）
    $pars['mchid'] =$set['mchid'];//微信支付商户号(mchid)
    $pars['nonce_str'] = random(32);
    $pars['partner_trade_no'] = random(10). date('Ymd') . random(3);
    $pars['openid'] =$m['openid'];
    $pars['check_name'] = "NO_CHECK";
    $pars['amount'] =$m['get']*100;
    $pars['desc'] =$_W['account']['name']."佣金提成发放";
    $pars['spbill_create_ip'] ="121.43.108.152";
    ksort($pars, SORT_STRING);
    $string1 = '';
    foreach($pars as $k => $v) {
        $string1 .= "{$k}={$v}&";
    }
    $string1 .= "key={$apikey}";//商户支付秘钥（API秘钥)
    $pars['sign'] = strtoupper(md5($string1));
    $xml = array2xml($pars);
    $extras = array();
    $extras['CURLOPT_CAINFO'] =IA_ROOT.'/addons/lexiangpingou/cert/'.$_W['uniacid'].'/rootca.pem';
    $extras['CURLOPT_SSLCERT'] =  IA_ROOT.'/addons/lexiangpingou/cert/'.$_W['uniacid'].'/apiclient_cert.pem';//证书路径
    $extras['CURLOPT_SSLKEY'] = IA_ROOT.'/addons/lexiangpingou/cert/'.$_W['uniacid'].'/apiclient_key.pem';//证书路径


    $procResult = null;
    $resp = ihttp_request($url5, $xml, $extras);

    if(is_error($resp)) {
        $procResult = $resp;
    } else {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
        $dom = new DOMDocument();
        if($dom->loadXML($xml)) {
            $xpath = new DOMXPath($dom);
            $code = $xpath->evaluate('string(//xml/return_code)');
            $ret = $xpath->evaluate('string(//xml/result_code)');
            if(strtolower($code) == 'success' && strtolower($ret) == 'success') {
                pdo_update('tg_member_group_commission', array('status' => 3,'give_time'=>TIMESTAMP , 'give' => $pri), array('id' => $id));
                $tip='发放成功';

                //TODO 创建打款日志
                $path = Log_DATA."/".$_W['uniacid']."/play/";
                //首先判断目录存在否
                if (!is_dir($path)){
                    //第3个参数“true”意思是能创建多级目录，iconv防止中文目录乱码
                    $res = mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
                }
                $date = date('y-m-d' , TIMESTAMP);
                file_put_contents($path.$date.".log", var_export(
                        array(
                            'uid' => $_W['user']['uid'] ,
                            'username' => $_W['user']['username'] ,
                            'ip' => CLIENT_IP ,
                            'uniacid' => $_W['uniacid'] ,
                            'price' => $pri ,
                            'filepath' => __FILE__ ,
                            'line' => __LINE__ ,
                            'time' => date('y-m-d h:i:s' , TIMESTAMP)
                        ),
                        true) .PHP_EOL, FILE_APPEND);

                echo "<script>alert('".$tip."');location.href='".web_url('member/commission')."';</script>";
                exit;

            } else {
                $tip='付款失败,请检查商户余额1';


                $error = $xpath->evaluate('string(//xml/err_code_des)');
                $procResult = error(-2, $error);
                echo "<script>alert('".$error."');location.href='".web_url('member/commission')."';</script>";
                exit;
            }
        } else {
            $tip='付款失败,请检查商户余额2';
            echo "<script>alert('".$tip."');location.href='".web_url('member/commission')."';</script>";
            exit;

            $procResult = error(-1, 'error response');
        }
    }

}

if ($op == 'change') {
    $funcop = $_GPC['funcop'];
    if($funcop == 'c'){
        $data['cash_id'] = $_GPC['id'];
        $data['uni_reason'] = $_GPC['val'];
        $id = $_GPC['id'];

        if (pdo_update('tg_cashrecord' , array('get_status' => -1) , array('id' => $id))) {
            $record = pdo_fetch("select * from " .tablename('tg_cashrecord') ." where id = " .$id);
            $account = pdo_fetch("select * from " .tablename('tg_member_group_commission') ." where id = " .$record['commission_id']);
            pdo_update('tg_member_group_commission' , array('get' => $account['get'] - $record['price']) , array('id' => $record['commission_id']));
            $data['uniacid'] = $record['uniacid'];
            $data['group_id'] = $record['commission_id'];
            $data['status'] = $record['get_status'];
            $data['openid'] = $record['openid'];
            $data['updatetime'] = TIMESTAMP;
            pdo_insert('tg_member_group_reason' , $data);
            die(json_encode(array("errno" => 0, 'message' => '拒绝成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '拒绝失败')));
        }
    } elseif ($funcop == 'detail'){

        $id = $_GPC['id'];
        $record = pdo_fetch("select * from " .tablename('tg_cashrecord') ." where id = " .$id);
        $data['cash_id'] = $_GPC['id'];
        $data['uni_reason'] = $_GPC['val'];
        $data['uniacid'] = $record['uniacid'];
        $data['group_id'] = $record['commission_id'];
        $data['status'] = $record['get_status'];
        $data['openid'] = $record['openid'];
        $data['updatetime'] = TIMESTAMP;
        pdo_insert('tg_member_group_reason' , $data);
        die(json_encode(array("errno" => 0, 'message' => '提交成功')));
    }elseif ($funcop == 'reason'){
        $id = $_GPC['id'];
        $record = pdo_fetch("select * from " .tablename('tg_cashrecord') ." where id = " .$id);
        $data['record_id'] = $_GPC['id'];
        $data['group_reason'] = $_GPC['val'];
        $data['uniacid'] = $record['uniacid'];
        $data['group_id'] = $record['commission_id'];
        $data['status'] = $record['get_status'];
        $data['openid'] = $record['openid'];
        $data['updatetime'] = TIMESTAMP;
        pdo_insert('tg_member_group_reason' , $data);
        die(json_encode(array("errno" => 0, 'message' => '提交成功')));
    }elseif ($funcop == 'final'){
        $id = $_GPC['id'];
        if (pdo_update('tg_cashrecord' , array('get_status' => -2) , array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '拒绝成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '拒绝失败')));
        }
    }elseif ($funcop == 'freed'){
        $id = $_GPC['id'];
        if (pdo_update('tg_cashrecord' , array('get_status' => 0 , 'uni_status' => 1 , 'account' => 0) , array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '重审成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '重审失败')));
        }
    }elseif ($funcop == 'shenhe'){
        $id = $_GPC['id'];
        if (pdo_update('tg_member_group_commission' , array('status' => 2) , array('id' => $id))) {
            die(json_encode(array("errno" => 0, 'message' => '审核成功')));
        } else {
            die(json_encode(array("errno" => 1, 'message' => '审核失败')));
        }
    }
}

if ($op == 'get'){
    $id = $_GPC['id'];
    $reason = pdo_fetchall("select * from " .tablename('tg_member_group_reason') ." where cash_id = " .$id ." order by updatetime");
    $i = 0;
    $j = 0;
    foreach ($reason as &$value) {
        if (!empty($value['uni_reason'])){
            $uni_reason[$i++] = $value['uni_reason'];
        }elseif (!empty($value['merchant_reason'])){
            $merchant_reason[$j++] = $value['group_reason'];
        }
    }
    die(json_encode(array('uni' => $uni_reason , 'merchant' => $merchant_reason)));
//    die(json_encode(array('reason' => $res)));
}

include wl_template('member/commission');
exit();
?>