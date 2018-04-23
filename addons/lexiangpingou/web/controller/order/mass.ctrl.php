<?php

global $_W, $_GPC;
$action = $_GPC['op'];
$tableName = "tg_options";
if ($_W['uniacid'] == "") {
    die("acount id error");
}


if (checksubmit('submit'))
{

		$threadCount = intval($_GPC['thread_count']) or 10;
        $saveData = array();
        $returnArray = array();
        $saveData['uniacid'] = $_W['uniacid'];
        $saveData['weid'] = $_W['uniacid'];
        $saveData['add_time'] = time();
        $saveData['type'] = $_GPC['msgtype'];
        $saveData['thread_count'] = intval($threadCount);
        $saveData['options'] = htmlspecialchars_decode($_GPC['options']);

        //按条件查询退款订单
        $condition = "  uniacid = :uniacid";
        $paras = array(':uniacid' => $_W['uniacid']);

        $status = $_GPC['status'];
        $transid = $_GPC['transid'];
        $pay_type = $_GPC['pay_type'];
        $keyword = $_GPC['keyword'];
        $member = $_GPC['member'];
        $time = $_GPC['time'];

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['time'])) {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= " AND  createtime >= :starttime AND  createtime <= :endtime ";
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
        if (trim($_GPC['goodsid']) != '') {
            $condition .= " and g_id like '%{$_GPC['goodsid']}%' ";
        }
        if (trim($_GPC['goodsid2']) != '') {
            $condition .= " and g_id like '%{$_GPC['goodsid2']}%' ";
        }
        if (!empty($_GPC['transid'])) {
            $condition .= " AND  transid =  '{$_GPC['transid']}'";
        }
        if (!empty($_GPC['pay_type'])) {
            $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
        } elseif ($_GPC['pay_type'] === '0') {
            $condition .= " AND  pay_type = '{$_GPC['pay_type']}'";
        }
        if (!empty($_GPC['keyword'])) {
            $condition .= " AND  orderno LIKE '%{$_GPC['keyword']}%'";
        }
        if (!empty($_GPC['member'])) {
            $condition .= " AND (addname LIKE '%{$_GPC['member']}%' or mobile LIKE '%{$_GPC['member']}%')";
        }
        $condition .= " AND  status = 10 and merchantid = '{$_W['user']['merchant_id']}' ";
        $condition .=" and pay_type<>9";
        $sql = "select  * from " . tablename('tg_order') . " where $condition and mobile<>'虚拟' order by tuan_id asc " ;
        $fansList = pdo_fetchall($sql, $paras);



//        $fansList = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status=10 order by tuan_id asc");
        $saveData['total'] = count($fansList);
        if (count($fansList) <= 0) {  
		
			echo "<script>alert('当前没有需要退款的订单');location.href='".web_url('order/mass')."';</script>";		
			//message("当前没有需要退款的订单");
            exit();
        }
		if (pdo_insert("tg_options", $saveData)) {
            $insertId = pdo_insertid();
            $returnArray['state'] = 1;
            $returnArray['optionId'] = $insertId;
            $threadId = 1;
            $fileData = array();
            $threadDataCount = 0;
            $timeFlag = time();
            pdo_update($tableName, array('cache_name' => $timeFlag), array('id' => $insertId));
            foreach ($fansList as $index => $val) {
                //$val['openid'] = 'od8tRt2J8fp2QppgJcgSu2FLbblE'; // 测试
                if ($index < count($fansList) / $threadCount * $threadId) {
                    $fileData['list'][] = $val['orderno'];
                    $threadDataCount++;
                } else {
                    $fileData['count'] = $threadDataCount;
                    $insertData = array(
                        "weid" => $_W['uniacid'],
                        "tid" => $threadId,
                        "add_time" => $timeFlag,
                        "option_id" => $insertId,
                        "options" => json_encode($fileData),
                        "success_count" => 0,
                        "total" => $threadDataCount,
                    );
                    pdo_insert("tg_thread_cache", $insertData);
                    $fileData['list'] = array($val['orderno']);
                    $threadId++;
                    $threadDataCount = 1;
                }
                if ($index == count($fansList) - 1) {
                    $fileData['count'] = $threadDataCount;
                    $insertData = array(
                        "weid" => $_W['uniacid'],
                        "tid" => $threadId,
                        "add_time" => $timeFlag,
                        "option_id" => $insertId,
                        "options" => json_encode($fileData),
                        "success_count" => 0,
                        "total" => $threadDataCount,
                    );
                    pdo_insert("tg_thread_cache", $insertData);
                }
            }
        }

		echo "<script>location.href='".web_url('order/refundprocess',array('oid' => $insertId))."';</script>";
		exit;
		//message(count($fansList));
}
if ($action == "setMassOption") {

    if ($_W['isajax']) {
        if ($_GPC['uniacid'] == "") {
            die("acount id error");
        }
        $threadCount = intval($_GPC['thread_count']) or 10;
        $saveData = array();
        $returnArray = array();
        $saveData['uniacid'] = $_W['uniacid'];
        $saveData['weid'] = $_W['uniacid'];
        $saveData['add_time'] = time();
        $saveData['type'] = $_GPC['msgtype'];
        $saveData['thread_count'] = intval($threadCount);
        $saveData['options'] = htmlspecialchars_decode($_GPC['options']);
        $fansList = pdo_fetchall("SELECT openid FROM " . tablename("hsh_tools_interaction_time") . " where update_times >= unix_timestamp(now())-48*3600 and weid = :weid order by update_times asc", array(":weid" => $_GPC['uniacid']));
        $saveData['total'] = count($fansList);
        if (count($fansList) <= 0) {
            $returnArray['state'] = 0;
            $returnArray['msg'] = "当前无48小时内交互粉丝.";
            returnJSON($returnArray, "none");
            exit();
        }
        if (pdo_insert($this->modulename . "_options", $saveData)) {
            $insertId = pdo_insertid();
            $returnArray['state'] = 1;
            $returnArray['optionId'] = $insertId;
            $threadId = 1;
            $fileData = array();
            $threadDataCount = 0;
            $timeFlag = time();
            pdo_update($tableName, array('cache_name' => $timeFlag), array('id' => $insertId));
            foreach ($fansList as $index => $val) {
                //$val['openid'] = 'od8tRt2J8fp2QppgJcgSu2FLbblE'; // 测试
                if ($index < count($fansList) / $threadCount * $threadId) {
                    $fileData['list'][] = $val['openid'];
                    $threadDataCount++;
                } else {
                    $fileData['count'] = $threadDataCount;
                    $insertData = array(
                        "weid" => $_GPC['uniacid'],
                        "tid" => $threadId,
                        "add_time" => $timeFlag,
                        "option_id" => $insertId,
                        "options" => json_encode($fileData),
                        "success_count" => 0,
                        "total" => $threadDataCount,
                    );
                    pdo_insert("zjl_mass_custom_msg_thread_cache", $insertData);
                    $fileData['list'] = array($val['openid']);
                    $threadId++;
                    $threadDataCount = 1;
                }
                if ($index == count($fansList) - 1) {
                    $fileData['count'] = $threadDataCount;
                    $insertData = array(
                        "weid" => $_GPC['uniacid'],
                        "tid" => $threadId,
                        "add_time" => $timeFlag,
                        "option_id" => $insertId,
                        "options" => json_encode($fileData),
                        "success_count" => 0,
                        "total" => $threadDataCount,
                    );
                    pdo_insert("zjl_mass_custom_msg_thread_cache", $insertData);
                }
            }
        } else {
            $returnArray['state'] = 0;
            $returnArray['msg'] = "insert data error";
        }
        returnJSON($returnArray, "none");
    }

}

load()->func('tpl');
$accounts = uni_accounts($_W['uniacid']);
if (!empty($accounts)) {
    $accdata = array();
    foreach ($accounts as $account) {
        if ($account['type'] == 1 && $account['type'] > 0) {
            $fansCount = pdo_fetch("SELECT count(id) as count FROM " . tablename("hsh_tools_interaction_time") . " where update_times >= unix_timestamp(now())-48*3600 and weid = :weid", array(":weid" => $account['uniacid']));
            $accdata[] = array('uniacid' => $account['uniacid'], 'name' => $account['name'], 'count' => $fansCount['count']);
        }
    }
}
include wl_template('order/mass');

//include $this->template('mass_test');



