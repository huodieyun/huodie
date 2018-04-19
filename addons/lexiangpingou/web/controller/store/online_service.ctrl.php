<?php
defined('IN_IA') or die('Access Denied');
define('BEST_SET', 'messikefu_set');
define('BEST_TPLMESSAGE_SENDLOG', 'messikefu_tplmessage_sendlog');
define('BEST_TPLMESSAGE_TPLLIST', 'messikefu_tplmessage_tpllist');
define('BEST_CHAT', 'messikefu_chat');
define('BEST_CSERVICE', 'messikefu_cservice');
define('BEST_CSERVICEGROUP', 'messikefu_cservicegroup');
load()->func('tpl');
load()->classs("account");
global $_W, $_GPC;
$condition="";
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'setting';
     if($operation == "setting")
    {
        global $_W;
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        include wl_template('store/set');
    }
//设置
if(trim($operation) == "doWebSetting")
    {
        global $_W, $_GPC;
            $id = intval($_GPC['id']);
            $data = array('weid' => $_W['uniacid'], 'title' => trim($_GPC['title']), 'istplon' => intval($_GPC['istplon']), 'unfollowtext' => trim($_GPC['unfollowtext']), 'followqrcode' => trim($_GPC['followqrcode']), 'sharetitle' => trim($_GPC['sharetitle']), 'sharedes' => trim($_GPC['sharedes']), 'sharethumb' => trim($_GPC['sharethumb']), 'kefutplminute' => intval($_GPC['kefutplminute']), 'bgcolor' => trim($_GPC['bgcolor']), 'defaultavatar' => trim($_GPC['defaultavatar']), 'fansauto' => trim($_GPC['fansauto']), 'kefuauto' => trim($_GPC['kefuauto']), 'issharemsg' => intval($_GPC['issharemsg']));
            if (!empty($id)) {
                pdo_update(BEST_SET, $data, array('id' => $id, 'weid' => $_W['uniacid']));
            } else {
                pdo_insert(BEST_SET, $data);
            }
            message('操作成功！', web_url('store/online_service', array('op' => 'setting')), 'success');
        } else {
            $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
//            include wl_template('store/set');/**/
        }

    //快速回复
   if ($operation == "doMobileGetquick")
    {
        global $_GPC, $_W;
        $openid = trim($_GPC['openid']);
        include wl_template('quick');
    }
    //聊天记录

        if ($operation == 'doWebChatlist') {
            global $_W, $_GPC;
			if($_W['user']['merchant_id']>0){
		$condition .= " and  merchant_id = {$_W['user']['merchant_id']} ";
	}
            $cservicelist = pdo_fetchall("SELECT content,name,thumb FROM " . tablename(BEST_CSERVICE) . " WHERE weid = '{$_W['uniacid']}' AND ctype = 1 {$condition} ORDER BY displayorder ASC");
            foreach ($cservicelist as $k => $v) {
                $lastest = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE weid = '{$_W['uniacid']}' AND toopenid = '{$v['content']}' GROUP BY openid");
                foreach ($lastest as $kk => $vv) {
                    $lastest[$kk]['lastchat'] = pdo_fetch("SELECT content,type FROM " . tablename(BEST_CHAT) . " WHERE weid = '{$_W['uniacid']}' AND toopenid = '{$v['content']}' AND openid = '{$vv['openid']}' ORDER BY time DESC");
                    $lastest[$kk]['chatlist'] = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE weid = '{$_W['uniacid']}' AND ((toopenid = '{$v['content']}' AND openid = '{$vv['openid']}') OR (openid = '{$v['content']}' AND toopenid = '{$vv['openid']}')) ORDER BY time ASC");
                }
                $cservicelist[$k]['lastest'] = $lastest;
                $cservicelist[$k]['num'] = $k;
            }
            include wl_template('store/chatlist');
        } elseif ($operation == 'chatdelete') {
            $toopenid = trim($_GPC['toopenid']);
            $openid = trim($_GPC['openid']);
            if (empty($toopenid) || empty($openid)) {
                message('抱歉，参数传入错误！', web_url('store/online_service', array('op' => 'setting')), 'error');
            }
            pdo_query("DELETE FROM " . tablename(BEST_CHAT) . " WHERE (openid = '{$openid}' AND toopenid = '{$toopenid}') OR ((openid = '{$toopenid}' AND toopenid = '{$openid}'))");
            message('删除聊天记录成功！', web_url('store/online_service', array('op' => 'setting')), 'success');
    }
    //客服排序
if ($operation == 'kfdisplay') {
    if (!empty($_GPC['displayorder'])) {
        foreach ($_GPC['displayorder'] as $id => $displayorder) {
            pdo_update(BEST_CSERVICE, array('displayorder' => $displayorder), array('id' => $id, 'weid' => $_W['uniacid']));
        }
        message('客服排序更新成功！',web_url('store/online_service', array('op' => 'setting')), 'success');
    }
	if($_W['user']['merchant_id']>0){
		$condition .= " and  merchant_id = {$_W['user']['merchant_id']} ";
	} 
    $cservicelist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = '{$_W['uniacid']}' {$condition} ORDER BY displayorder ASC");
    foreach ($cservicelist as $k => $v) {
        if ($v['ctype'] == 1) {
            $cservicelist[$k]['serviceurl'] = app_url('chat/chat', array('op' => 'doMobileChat',"toopenid"=>$v["content"]));

            $scripturl = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&openid=' . $v['content'] . '&do=getquick&m=cy163_customerservice';
            $scripthtml = '<script type="text/javascript" src="../addons/cy163_customerservice/static/mui/js/jquery.min.js"></script>
										<script type="text/javascript">
										$.ajax({   
											url:"' . $scripturl . '",   
											type:"post", 
											data:{},
											dataType:"html",
											success:function(data){  
												$("body").append(data);
											}
										});
										</script>';
            $cservicelist[$k]['scripthtml'] = htmlspecialchars($scripthtml);
        }
        if ($v['ctype'] == 2) {
            $cservicelist[$k]['serviceurl'] = "https://wpa.qq.com/msgrd?v=3&uin=" . $v['content'];
        }
        if ($v['ctype'] == 3 || $v['ctype'] == 4) {
            $cservicelist[$k]['serviceurl'] = "tel:" . $v['content'];
        }
    }
    $count = count($cservicelist);
//    die(json_encode($count));
    include wl_template('store/cservcice');
} elseif ($operation == 'kfpost') {
    $cservicegrouplist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE weid = '{$_W['uniacid']}' ORDER BY displayorder ASC");
    $id = intval($_GPC['id']);
    if (!empty($id)) {
        $cservice = pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE id = :id AND weid = :weid", array(':id' => $id, ':weid' => $_W['uniacid']));
        $con = "uniacid='{$_W['uniacid']}' ";
        $con .= " and openid ='{$cservice['content']}'";
        $keyword = $_GPC['keyword'];
        $saler = pdo_fetch("select * from" . tablename('tg_member') . "where $con");
    }
    if (checksubmit('submit')) {
        if (empty($_GPC['name'])) {
            message('抱歉，请输入客服名称！');
        }
        if (empty($_GPC['ctype'])) {
            message('抱歉，请选择客服类型！');
        }
        if (empty($_GPC['content'])) {
            message('抱歉，请输入客服内容！');
        }
        if (empty($_GPC['thumb'])) {
            message('抱歉，请上传客服头像！');
        }
        $ctype = intval($_GPC['ctype']);
        if ($ctype == 1) {
            $starthour = intval($_GPC['starthour']);
            $endhour = intval($_GPC['endhour']);
            $autoreply = trim($_GPC['autoreply']);
        } else {
            $starthour = 0;
            $endhour = 0;
            $autoreply = '';
        }
        $data = array('weid' => $_W['uniacid'], 'name' => trim($_GPC['name']), 'ctype' => $ctype, 'content' => trim($_GPC['content']), 'thumb' => $_GPC['thumb'], 'starthour' => $starthour, 'endhour' => $endhour, 'autoreply' => $autoreply, 'displayorder' => intval($_GPC['displayorder']),'merchant_id' => intval($_W['user']['merchant_id']), 'groupid' => intval($_GPC['groupid']));
        if (!empty($id)) {
            pdo_update(BEST_CSERVICE, $data, array('id' => $id, 'weid' => $_W['uniacid']));
        } else {
            pdo_insert(BEST_CSERVICE, $data);
        }
        message('操作成功！', web_url('store/online_service', array('op' => 'kfdisplay')), 'success');
    }
    include wl_template('store/cservcice');
} elseif ($operation == 'kfdelete') {
    $id = intval($_GPC['id']);
    $cservice = pdo_fetch("SELECT id,content,ctype FROM " . tablename(BEST_CSERVICE) . " WHERE id = {$id}");
    if (empty($cservice)) {
        message('抱歉，该客服信息不存在或是已经被删除！', web_url('store/online_service', array('op' => 'kfdelete')), 'error');
    }
    if ($cservice['ctype'] == 1) {
        pdo_delete(BEST_CHAT, array('openid' => $cservice['content']));
        pdo_delete(BEST_CHAT, array('toopenid' => $cservice['content']));
    }
    pdo_delete(BEST_CSERVICE, array('id' => $id));
    message('删除客服信息成功！', web_url('store/online_service', array('op' => 'kfdisplay')), 'success');
}
    //客服组
        if ($operation == 'display') {
            global $_W, $_GPC;
            if (!empty($_GPC['displayorder'])) {
                foreach ($_GPC['displayorder'] as $id => $displayorder) {
                    pdo_update(BEST_CSERVICEGROUP, array('displayorder' => $displayorder), array('id' => $id, 'weid' => $_W['uniacid']));
                }
                message('客服组排序更新成功！', web_url('store/online_service', array('op' => 'setting')), 'success');
            }
            $cservicegrouplist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE weid = '{$_W['uniacid']}' ORDER BY displayorder ASC");
            foreach ($cservicegrouplist as $k => $v) {
                $cservicegrouplist[$k]['servicegroupurl'] = app_url('chat/chat',array("op"=>"doMobileGroupchat","id"=>$v['id']));
            }
            include wl_template('store/cservicegroup');
        } elseif ($operation == 'post') {
            $id = intval($_GPC['id']);
            if (!empty($id)) {
                $cservicegroup = pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE id = :id AND weid = :weid", array(':id' => $id, ':weid' => $_W['uniacid']));
            }
            if (checksubmit('submit')) {
                if (empty($_GPC['name'])) {
                    message('抱歉，请输入客服组名称！');
                }
                $data = array('weid' => $_W['uniacid'], 'name' => trim($_GPC['name']), 'thumb' => $_GPC['thumb'], 'displayorder' => intval($_GPC['displayorder']));
                if (!empty($id)) {
                    pdo_update(BEST_CSERVICEGROUP, $data, array('id' => $id, 'weid' => $_W['uniacid']));
                } else {
                    pdo_insert(BEST_CSERVICEGROUP, $data);
                }
                message('操作成功！', web_url('store/online_service', array('op' => 'display')), 'success');
            }
            include wl_template('store/cservicegroup');
        } elseif ($operation == 'delete') {
            $id = intval($_GPC['id']);
            $cservicegroup = pdo_fetch("SELECT id FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE id = {$id}");
            if (empty($cservicegroup)) {
                message('抱歉，该客服组不存在或是已经被删除！', web_url('store/online_service', array('op' => 'setting')), 'error');
            }
            pdo_delete(BEST_CSERVICEGROUP, array('id' => $id));
            message('删除客服组成功！', web_url('store/online_service', array('op' => 'setting')), 'success');
        }
    //选择客服
    if ($operation == "doMobileChosekefu")
    {
        global $_W, $_GPC;
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        $setting['shareurl'] = web_url('store/online_service', array('op' => 'doMobileChosekefu'));
        $cservicelist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND groupid = 0 ORDER BY displayorder ASC");
        $cservicegrouplist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE weid = {$_W['uniacid']} ORDER BY displayorder ASC");
        include wl_template('chosekefu');
    }
    //客服组聊天
if ($operation == "doMobileGroupchat")
    {
        global $_W, $_GPC;
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        $id = intval($_GPC['id']);
        $setting['shareurl'] =web_url('store/online_service', array('op' => 'doMobileGroupchat'));
        $cservicelist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND groupid = {$id} ORDER BY displayorder ASC");
        $cservicegroup = pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE weid = {$_W['uniacid']} AND id = {$id}");
        include wl_template('groupchat');
    }
    //聊天界面
if ($operation == "doMobileChat")
    {
        global $_W, $_GPC;
        $openid = $_W['fans']['from_user'];
        if (empty($openid)) {
            message('请在微信浏览器中打开！', '', 'error');
        }
        $toopenid = trim($_GPC['toopenid']);
        if ($openid == $toopenid) {
            message('不能和自己聊天！', '', 'error');
        }
        $touser = pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND content = '{$toopenid}'");
        $chatcon = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE ((openid = '{$openid}' AND toopenid = '{$toopenid}') OR (openid = '{$toopenid}' AND toopenid = '{$openid}')) AND weid = {$_W['uniacid']} ORDER BY time ASC");
        foreach ($chatcon as $k => $v) {
            $chatcon[$k]['content'] = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $v['content']);
        }
        $timestamp = TIMESTAMP;
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        $fansauto = explode("|", $setting['fansauto']);
        $setting['shareurl'] = web_url('store/online_service', array('op' => 'doMobileChosekefu'));
        $dataup['hasread'] = 1;
        pdo_update(BEST_CHAT, $dataup, array('weid' => $_W['uniacid'], 'toopenid' => $openid, 'openid' => $toopenid, 'hasread' => 0));
        include wl_template("chat");
    }
    //手机服务
if ($operation == "doMobileServicechat")
    {
        global $_W, $_GPC;
        $openid = $_W['fans']['from_user'];
        if (empty($openid)) {
            message('请在微信浏览器中打开！', '', 'error');
        }
        $toopenid = trim($_GPC['toopenid']);
        $touser = pdo_fetch("SELECT nickname FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND toopenid = '{$openid}' AND openid = '{$toopenid}'");
        $nickname = empty($touser) ? '匿名用户' : $touser['nickname'];
        $chatcon = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE ((openid = '{$toopenid}' AND toopenid = '{$openid}') OR (openid = '{$openid}' AND toopenid = '{$toopenid}')) AND weid = {$_W['uniacid']} ORDER BY time ASC");
        foreach ($chatcon as $k => $v) {
            $chatcon[$k]['content'] = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $v['content']);
        }
        $timestamp = TIMESTAMP;
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        $kefuauto = explode("|", $setting['kefuauto']);
        $setting['shareurl'] = web_url('store/online_service', array('op' => 'doMobileChosekefu'));
        $dataup['hasread'] = 1;
        pdo_update(BEST_CHAT, $dataup, array('weid' => $_W['uniacid'], 'toopenid' => $openid, 'openid' => $toopenid, 'hasread' => 0));
        include wl_template("servicechat");
    }
    //分享

if ($operation == "doMobileAllshare")
    {
        global $_W, $_GPC;
        $openid = $_W['fans']['from_user'];
        if (empty($openid)) {
            message('请在微信浏览器中打开！', '', 'error');
        }
        $cservice = pdo_fetch("SELECT id FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND content = '{$openid}'");
        if (empty($cservice)) {
            message('您不是客服！', '', 'error');
        }
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        if ($setting['issharemsg'] == 0) {
            message('暂未开通客户记录共享功能，如需要请联系管理员在基本设置中开启！', '', 'error');
        }
        $toopenid = trim($_GPC['toopenid']);
        $touser = pdo_fetch("SELECT nickname FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND toopenid = '{$openid}'");
        $nickname = empty($touser) ? '匿名用户' : $touser['nickname'];
        $chatcon = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE (openid = '{$toopenid}' OR toopenid = '{$toopenid}') AND weid = {$_W['uniacid']} ORDER BY time ASC");
        foreach ($chatcon as $k => $v) {
            $chatcon[$k]['content'] = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $v['content']);
        }
        $setting['shareurl'] = $_W["siteroot"] . 'app/' . str_replace("./", "", $this->createMobileUrl('allshare', array('toopenid' => $toopenid)));
        include wl_template("allshare");
    }
    //刷新
if ($operation == "doMobileShuaxinchat")
    {
        global $_W, $_GPC;
        $openid = $_W['fans']['from_user'];
        $toopenid = trim($_GPC['toopenid']);
        $timestamp = intval($_GPC['timestamp']);
        $chatcon = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE ((openid = '{$toopenid}' AND toopenid = '{$openid}') OR (openid = '{$openid}' AND toopenid = '{$toopenid}')) AND weid = {$_W['uniacid']} AND time >= {$timestamp} ORDER BY time ASC");
        $html = '';
        if (!empty($chatcon)) {
            foreach ($chatcon as $k => $v) {
                $v['content'] = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $v['content']);
                if ($v['openid'] == $openid) {
                    $messageclass = 'message me';
                    $bubble = 'bubble bubble_primary right';
                    $nicknamediv = '<span class="time">' . date('Y-m-d H:i:s', $v['time']) . '</span>' . $v['nickname'];
                    $yuyin = '<img src="../addons/cy163_customerservice/static/voicelefton.png" class="voiceplay" />';
                } else {
                    $messageclass = 'message';
                    $bubble = 'bubble bubble_default left';
                    $nicknamediv = $v['nickname'] . '<span class="time">' . date('Y-m-d H:i:s', $v['time']) . '</span>';
                    $yuyin = '<img src="../addons/cy163_customerservice/static/voicerighton.png" class="voiceplay" />';
                }
                if ($v['type'] == 3 || $v['type'] == 4) {
                    $content = '<div class="plain" style="padding:2px;"><img src="' . $v['content'] . '" style="max-width:100%;" /></div>';
                } elseif ($v['type'] == 5 || $v['type'] == 6) {
                    $content = '<div class="plain" style="color:#900;width:2rem;height:0.4rem;" onclick="playvoice(\'' . $v['content'] . '\');">' . $yuyin . '</div>';
                } else {
                    $content = '<div class="plain">' . $v['content'] . '</div>';
                }
                $imgsrc = $v['avatar'];
                $html .= '<div class="' . $messageclass . '">' . '<img class="avatar" src="' . $imgsrc . '" />' . '<div class="content">' . '<div class="nickname">' . $nicknamediv . '</div>' . '<div class="' . $bubble . '">' . ' <div class="bubble_cont">' . $content . '</div>' . '</div>' . '</div>' . '</div>';
            }
            $resArr['error'] = 0;
            $resArr['msg'] = $html;
            $resArr['timestamp'] = TIMESTAMP;
            echo json_encode($resArr);
            die;
        } else {
            $resArr['error'] = 1;
            $resArr['msg'] = '';
            $resArr['timestamp'] = TIMESTAMP;
            echo json_encode($resArr);
            die;
        }
    }
    //新对话
if ($operation == "doMobileAddchat")
    {
        global $_W, $_GPC;
        include_once '../addons/cy163_customerservice/emoji/emoji.php';
        $chatcontent = trim($_GPC['content']);
        if (empty($chatcontent)) {
            $resArr['error'] = 1;
            $resArr['msg'] = '请输入对话内容！';
            echo json_encode($resArr);
            die;
        }
        $chatcontent = emoji_docomo_to_unified($chatcontent);
        $chatcontent = emoji_unified_to_html($chatcontent);
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        $data['openid'] = $_W['fans']['from_user'];
        $data['toopenid'] = trim($_GPC['toopenid']);
        $data['time'] = TIMESTAMP;
        $data['content'] = $chatcontent;
        $data['weid'] = $_W['uniacid'];
        $data['nickname'] = empty($_W['fans']['tag']['nickname']) ? '匿名用户' : $_W['fans']['tag']['nickname'];
        $data['avatar'] = empty($_W['fans']['tag']['avatar']) ? tomedia($setting['defaultavatar']) : $_W['fans']['tag']['avatar'];
        $type = intval($_GPC['type']);
        $data['type'] = $type;
        if ($type == 3 || $type == 4) {
            $tplcon = '咨询内容：粉丝发送了图片';
        } elseif ($type == 5 || $type == 6) {
            $tplcon = '咨询内容：粉丝发送了语音';
        } else {
            if (strpos($data['content'], 'span class=')) {
                $tplcon = '咨询内容：粉丝发送了表情';
            } else {
                $tplcon = '咨询内容：' . $data['content'];
            }
        }
        $hasliao = pdo_fetch("SELECT id,time FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND openid = '{$data['openid']}' AND toopenid = '{$data['toopenid']}' ORDER BY time DESC");
        $guotime = TIMESTAMP - $hasliao['time'];
        if ($setting['istplon'] == 1 && (empty($hasliao) || $guotime > $setting['kefutplminute'])) {
            $tpllist = pdo_fetch("SELECT id,tplbh FROM" . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE (tplbh = 'OPENTM202309749' OR tplbh = 'OPENTM207327169') AND uniacid = {$_W['uniacid']}");
            if (!empty($tpllist)) {
                if ($tpllist['tplbh'] == 'OPENTM202309749') {
                    $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '用户咨询提醒', 'firstcolor' => '#990000', 'keyword1' => $data['nickname'], 'keyword1color' => '', 'keyword2' => date("Y-m-d H:i:s", TIMESTAMP), 'keyword2color' => '', 'remark' => $tplcon, 'remarkcolor' => '', 'url' => $_W["siteroot"] . 'app/' . str_replace("./", "", $this->createMobileUrl("servicechat", array('toopenid' => $data['openid']))));
                } else {
                    $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '用户咨询提醒', 'firstcolor' => '#990000', 'keyword1' => date("Y-m-d H:i:s", TIMESTAMP), 'keyword1color' => '', 'keyword2' => 1, 'keyword2color' => '', 'remark' => $tplcon, 'remarkcolor' => '', 'url' => $_W["siteroot"] . 'app/' . str_replace("./", "", $this->createMobileUrl("servicechat", array('toopenid' => $data['openid']))));
                }
                sendtemmsg($tpllist['id'], $arrmsg);
            }
        }
        pdo_insert(BEST_CHAT, $data);
        $resArr['error'] = 0;
        $resArr['msg'] = '';
        echo json_encode($resArr);
        die;
    }
    //第二个窗口
if ($operation == "doMobileAddchat2")
    {
        global $_W, $_GPC;
        include_once '../addons/cy163_customerservice/emoji/emoji.php';
        $chatcontent = trim($_GPC['content']);
        if (empty($chatcontent)) {
            $resArr['error'] = 1;
            $resArr['msg'] = '请输入对话内容！';
            echo json_encode($resArr);
            die;
        }
        $chatcontent = emoji_docomo_to_unified($chatcontent);
        $chatcontent = emoji_unified_to_html($chatcontent);
        $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
        $data['openid'] = $_W['fans']['from_user'];
        $touser = pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND content = '{$data['openid']}'");
        $data['nickname'] = $touser['name'];
        $data['avatar'] = tomedia($touser['thumb']);
        $data['toopenid'] = trim($_GPC['toopenid']);
        $data['time'] = TIMESTAMP;
        $data['content'] = $chatcontent;
        $data['weid'] = $_W['uniacid'];
        $type = intval($_GPC['type']);
        $data['type'] = $type;
        if ($type == 3 || $type == 4) {
            $tplcon = '客服给您发送了图片';
        } elseif ($type == 5 || $type == 6) {
            $tplcon = '咨询内容：粉丝发送了语音';
        } else {
            if (strpos($data['content'], 'span class=')) {
                $tplcon = '客服给您发送了表情';
            } else {
                $tplcon = $data['content'];
            }
        }
        $hasliao = pdo_fetch("SELECT id,time FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND openid = '{$data['openid']}' AND toopenid = '{$data['toopenid']}' ORDER BY time DESC");
        $guotime = TIMESTAMP - $hasliao['time'];
        if ($setting['istplon'] == 1 && (empty($hasliao) || $guotime > $setting['kefutplminute'])) {
            $tpllist = pdo_fetch("SELECT id,tplbh FROM" . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE (tplbh = 'OPENTM205211081' OR tplbh = 'OPENTM202109783') AND uniacid = {$_W['uniacid']}");
            if (!empty($tpllist)) {
                if ($tpllist['tplbh'] == 'OPENTM205211081') {
                    $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '您好，您咨询客服的问题已回复', 'firstcolor' => '#990000', 'keyword1' => $data['nickname'], 'keyword1color' => '', 'keyword2' => date("Y-m-d H:i:s", TIMESTAMP), 'keyword2color' => '', 'keyword3' => $tplcon, 'keyword3color' => '', 'remark' => '', 'remarkcolor' => '', 'url' => $_W["siteroot"] . 'app/' . str_replace("./", "", $this->createMobileUrl("chat", array('toopenid' => $data['openid']))));
                } else {
                    $lastmsg = pdo_fetch("SELECT content,type FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND openid = '{$data['toopenid']}' AND toopenid = '{$data['openid']}' ORDER BY time DESC");
                    if ($lastmsg['type'] == 3 || $lastmsg['type'] == 4) {
                        $lastmsgcon = '图片消息';
                    } elseif ($lastmsg['type'] == 5 || $lastmsg['type'] == 6) {
                        $lastmsgcon = '语音消息';
                    } else {
                        $lastmsgcon = $lastmsg['content'];
                    }
                    $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '您好，您咨询客服的问题已回复', 'firstcolor' => '#990000', 'keyword1' => $lastmsgcon, 'keyword1color' => '', 'keyword2' => $tplcon, 'keyword2color' => '', 'remark' => '回复时间：' . date("Y-m-d H:i:s", TIMESTAMP), 'remarkcolor' => '', 'url' => $_W["siteroot"] . 'app/' . str_replace("./", "", $this->createMobileUrl("chat", array('toopenid' => $data['openid']))));
                }
                sendtemmsg($tpllist['id'], $arrmsg);
            }
        }
        pdo_insert(BEST_CHAT, $data);
        $resArr['error'] = 0;
        $resArr['msg'] = '';
        echo json_encode($resArr);
        die;
    }
    //发送声音
if ($operation == "doMobileGetmedia")
    {
        global $_W, $_GPC;
        $access_token = WeAccount::token();
        $media_id = $_GPC['media_id'];
        $url = "https://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $media_id;
        $updir = "../attachment/images/" . $_W['uniacid'] . "/" . date("Y", time()) . "/" . date("m", time()) . "/";
        if (!file_exists($updir)) {
            mkdir($updir, 511, true);
        }
        $randimgurl = "images/" . $_W['uniacid'] . "/" . date("Y", time()) . "/" . date("m", time()) . "/" . date('YmdHis') . rand(1000, 9999) . '.jpg';
        $targetName = "../attachment/" . $randimgurl;
        $ch = curl_init($url);
        $fp = fopen($targetName, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        if (file_exists($targetName)) {
            $resarr['error'] = 0;
            mkThumbnail($targetName, 640, 0, $targetName);
            if (!empty($_W['setting']['remote']['type'])) {
                load()->func('file');
                $remotestatus = file_remote_upload($randimgurl, true);
                if (is_error($remotestatus)) {
                    $resarr['error'] = 1;
                    $resarr['message'] = '远程附件上传失败，请检查配置并重新上传';
                    file_delete($randimgurl);
                    die(json_encode($resarr));
                } else {
                    file_delete($randimgurl);
                    $resarr['realimgurl'] = $randimgurl;
                    $resarr['imgurl'] = tomedia($randimgurl);
                    $resarr['message'] = '上传成功,';
                    die(json_encode($resarr));
                }
            }
            $resarr['realimgurl'] = $randimgurl;
            $resarr['imgurl'] = tomedia($randimgurl);
            $resarr['message'] = '上传成功';
        } else {
            $resarr['error'] = 1;
            $resarr['message'] = '上传失败';
        }
        echo json_encode($resarr, true);
        die;
    }
    //获取声音
if ($operation == "doMobileGetvoice")
    {
        global $_W, $_GPC;
        $access_token = WeAccount::token();
        $media_id = $_GPC['media_id'];
        $url = "https://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $media_id;
        $updir = "../attachment/audios/" . $_W['uniacid'] . "/" . date("Y", time()) . "/" . date("m", time()) . "/";
        if (!file_exists($updir)) {
            mkdir($updir, 511, true);
        }
        $randvoiceurl = "audios/" . $_W['uniacid'] . "/" . date("Y", time()) . "/" . date("m", time()) . "/" . date('YmdHis') . rand(1000, 9999) . '.amr';
        $targetName = "../attachment/" . $randvoiceurl;
        $ch = curl_init($url);
        $fp = fopen($targetName, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        if (file_exists($targetName)) {
            $resarr['error'] = 0;
            if (!empty($_W['setting']['remote']['type'])) {
                load()->func('file');
                $remotestatus = file_remote_upload($randvoiceurl, true);
                if (is_error($remotestatus)) {
                    $resarr['error'] = 1;
                    $resarr['message'] = '远程附件上传失败，请检查配置并重新上传';
                    file_delete($randvoiceurl);
                    die(json_encode($resarr));
                } else {
                    file_delete($randvoiceurl);
                    $resarr['realvoiceurl'] = $randvoiceurl;
                    $resarr['voiceurl'] = tomedia($randvoiceurl);
                    $resarr['message'] = '上传成功';
                    die(json_encode($resarr));
                }
            }
            $resarr['realvoiceurl'] = $randvoiceurl;
            $resarr['voiceurl'] = tomedia($randvoiceurl);
            $resarr['message'] = '上传成功';
        } else {
            $resarr['error'] = 1;
            $resarr['message'] = '上传失败';
        }
        echo json_encode($resarr, true);
        die;
    }
    //获取模板消息列表
if ($operation == "doWebTpllist")
    {
        global $_W;
        $list = pdo_fetchall("SELECT * FROM " . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE uniacid = {$_W['uniacid']} ORDER BY id ASC");
        include wl_template('store/tpllist');
        exit;
    }

    //创建模板
if ($operation == "doWebCreatetpl")
    {
        global $_GPC, $_W;
        $tplbh = trim($_GPC['tplbh']);
        $istplbh = pdo_fetch("SELECT * FROM " . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE uniacid = {$_W['uniacid']} AND tplbh = '{$tplbh}'");
        if (!empty($istplbh)) {
            message('您已添加该模板消息！', web_url("store/online_service",array('op'=>'doWebTpllist')), 'error');
        } else {
            $account_api = WeAccount::create();
            $token = $account_api->getAccessToken();
            if (is_error($token)) {
                message('获取access token 失败');
            }
            $url = "https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token={$token}";
            $postdata = array('template_id_short' => $tplbh);
            $response = ihttp_request($url, urldecode(json_encode($postdata)));
            $errarr = json_decode($response['content'], true);
            if ($errarr['errcode'] == 0) {
                $data = array('tplbh' => $tplbh, 'tpl_id' => $errarr['template_id'], 'uniacid' => $_W['uniacid']);
                pdo_insert(BEST_TPLMESSAGE_TPLLIST, $data);
                message('添加模板消息成功！', web_url("store/online_service",array('op'=>'doWebTpllist')), 'success');
                return;
            } else {
                message($errarr['errmsg'],web_url("store/online_service",array('op'=>'doWebTpllist')), 'error');
            }
        }
    }
    //
if ($operation == "doWebdeltpl")
    {
        global $_GPC, $_W;
        $tpl_id = trim($_GPC['tplid']);
//        message($tpl_id);
        $istplbh = pdo_fetch("SELECT * FROM " . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE uniacid = {$_W['uniacid']} AND tpl_id = '{$tpl_id}'");
        if (empty($istplbh)) {
            message('没有该模板消息！', $this->createWebUrl('Tpllist'), 'error');
        } else {
            if (empty($istplbh['tpl_key'])) {
                message('该该模板消息没有同步，不能删除！', $this->createWebUrl('Tpllist'), 'error');
            }
            $account_api = WeAccount::create();
            $token = $account_api->getAccessToken();
            if (is_error($token)) {
                message('获取access token 失败');
            }
            $url = "https://api.weixin.qq.com/cgi-bin/template/del_private_template?access_token={$token}";
            $postjson = '{"template_id":"' . $tpl_id . '"}';
            $response = ihttp_request($url, $postjson);
            $errarr = json_decode($response['content'], true);
            if ($errarr['errcode'] == 0) {
                pdo_delete(BEST_TPLMESSAGE_TPLLIST, array('tpl_id' => $tpl_id));
                message('删除模板消息成功！', $this->createWebUrl('Tpllist'), 'success');
                return;
            } else {
                message($errarr['errmsg'], $this->createWebUrl('Tpllist'), 'error');
            }
        }
    }
    //同步官网模板消息
if ($operation == "doWebUpdateTpl")
    {
        global $_W;
        $success = 0;
        $account_api = WeAccount::create();
        $token = $account_api->getAccessToken();
        if (is_error($token)) {
            message('获取access token 失败');
        }
        $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$token}";
        $response = ihttp_request($url, urldecode(json_encode($data)));
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        $list = json_decode($response['content'], true);
        if (empty($list['template_list'])) {
            message('访问公众平台接口失败, 错误: 模板列表返回为空');
        }
        foreach ($list['template_list'] as $k => $v) {
            $template_id = $v['template_id'];
            $data['tpl_title'] = $v['title'];
            preg_match_all('/{{(.*?)\.DATA}}/', $v['content'], $key);
            preg_match_all('/}}\n*(.*?){{/', $v['content'], $title);
            $keys = formatTplKey($key[1], $title[1]);
            $data['tpl_key'] = serialize($keys);
            $data['tpl_example'] = $v['example'];
            pdo_update(BEST_TPLMESSAGE_TPLLIST, $data, array('tpl_id' => $template_id));
        }
        message('更新完闭！', $this->createWebUrl('Tpllist'), 'success');
    }
    //格式化模板

    function formatTplKey($key, $title)
    {
        $keys = array();
        for ($i = 0; $i < count($key); $i++) {
            if (empty($key[$i])) {
                continue;
            }
            if ($i == 0) {
                $keys[$i]['title'] = "首标题：";
                $keys[$i]['key'] = $key[$i];
                continue;
            }
            if ($i == count($key) - 1) {
                $keys[$i]['title'] = "尾备注：";
                $keys[$i]['key'] = $key[$i];
                continue;
            }
            $keys[$i]['title'] = $title[$i - 1];
            $keys[$i]['key'] = $key[$i];
        }
        return $keys;
    }
    //单独发送
if ($operation == "doWebSendone")
    {
        global $_W, $_GPC;
        $tpllist = pdo_fetchall("SELECT * FROM " . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE uniacid = {$_W['uniacid']} ORDER BY id");
        if (empty($tpllist)) {
            message("请先同步模板！", $this->createWebUrl('Tpllist'), 'error');
            die;
        }
        $data['tplid'] = empty($_GPC['tplid']) ? $tpllist[0]['id'] : $_GPC['tplid'];
        $tpldetailed = pdo_fetch("SELECT * FROM " . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE id = {$data['tplid']} LIMIT 1");
        $tplkeys = unserialize($tpldetailed['tpl_key']);
        include wl_template('store/sendone');
    }else if($operation == "doWebSendOneSumbit")
    {
        
        global $_W, $_GPC;
        $openid = $_GPC['openid'];
        $account_api = WeAccount::create();
        $token = $account_api->getAccessToken();
        if (is_error($token)) {
            message('获取access token 失败');
        }

        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $token;

        $tpldetailed = pdo_fetch("SELECT * FROM " . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE id = {$_GPC['tplid']} LIMIT 1");
        $tplkeys = $tpldetailed['tpl_key'];
        $tplkeys = unserialize($tplkeys);

        $postData = array();
        $postData['template_id'] = $tpldetailed['tpl_id'];
        $postData['url'] = $_GPC['url'];
        $postData['topcolor'] = $_GPC['topcolor'];
        foreach ($tplkeys as $value) {
            $postData['data'][$value['key']]['value'] = $_GPC[$value['key']];
            $postData['data'][$value['key']]['color'] = $_GPC[$value['key'] . 'color'];
        }
        pdo_insert(BEST_TPLMESSAGE_SENDLOG, array('tpl_id' => $_GPC['tplid'], 'tpl_title' => $tpldetailed['tpl_title'], 'message' => serialize($postData), 'time' => time(), 'uniacid' => $_W['uniacid'], 'target' => implode(",", $_GPC['openid']), 'type' => 1));
        $tid = pdo_insertid();
        if ($tid <= 0) {
            message('抱歉,请求失败', 'referer', 'error');
        }
        $openid = $_GPC['openid'];
        $success = 0;
        $fail = 0;
        $error = "";
        foreach ($openid as $value) {
            $postData['touser'] = $value;
            $res = ihttp_post($url, json_encode($postData));
            $re = json_decode($res['content'], true);
            if ($re['errmsg'] == 'ok') {
                $success++;
            } else {
                $fail++;
                $error .= $value . ",";
            }
        }
        pdo_update(BEST_TPLMESSAGE_SENDLOG, array('success' => $success, 'fail' => $fail, 'error' => $error, 'status' => 1), array('id' => $tid));
        if ($success <= 0) {
            message("发送失败！", 'referer', 'error');
        }
        message("发送成功，总计：" . ($success + $fail) . "人，成功：{$success} 人，失败：{$fail} 人", $this->createWebUrl('SendOnelog'), 'success');
    }
    //发送个人的日志
if ($operation == "doWebSendOnelog")
    {
        global $_W, $_GPC;
        $page = empty($_GPC['page']) ? 1 : $_GPC['page'];
        $pagesize = 20;
        $total = pdo_fetch("SELECT COUNT(id) AS num FROM " . tablename(BEST_TPLMESSAGE_SENDLOG) . " WHERE type = 1 AND uniacid = {$_W['uniacid']} ");
        $list = pdo_fetchall("SELECT a.id,a.success,a.fail,a.time,a.target,a.status,a.tpl_title as title,a.error FROM " . tablename(BEST_TPLMESSAGE_SENDLOG) . " AS a WHERE a.type = 1 AND a.uniacid = {$_W['uniacid']} ORDER BY time DESC LIMIT " . ($page - 1) * $pagesize . "," . $pagesize);
        $pagination = pagination($total['num'], $page, $pagesize);
        include wl_template("store/sendonelog");
    }
    //发送信息
    function sendtemmsg($tplid, $arrmsg)
    {
        global $_W, $_GPC;
        $account_api = WeAccount::create();
        $token = $account_api->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $token;
        $tpldetailed = pdo_fetch("SELECT * FROM " . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE id = {$tplid} LIMIT 1");
        $tplkeys = unserialize($tpldetailed['tpl_key']);
        $postData = array();
        $postData['template_id'] = $tpldetailed['tpl_id'];
        $postData['url'] = $arrmsg['url'];
        $postData['topcolor'] = $arrmsg['topcolor'];
        foreach ($tplkeys as $value) {
            $postData['data'][$value['key']]['value'] = $arrmsg[$value['key']];
            $postData['data'][$value['key']]['color'] = $arrmsg[$value['key'] . 'color'];
        }
        pdo_insert(BEST_TPLMESSAGE_SENDLOG, array('tpl_id' => $tplid, 'tpl_title' => $tpldetailed['tpl_title'], 'message' => serialize($postData), 'time' => time(), 'uniacid' => $_W['uniacid'], 'target' => $arrmsg['openid'], 'type' => 1));
        $tid = pdo_insertid();
        $success = 0;
        $fail = 0;
        $error = "";
        $postData['touser'] = $arrmsg['openid'];
        $res = ihttp_post($url, json_encode($postData));
        $re = json_decode($res['content'], true);
        if ($re['errmsg'] == 'ok') {
            $success++;
        } else {
            $fail++;
            $error .= $openid;
        }
        pdo_update(BEST_TPLMESSAGE_SENDLOG, array('success' => $success, 'fail' => $fail, 'error' => $error, 'status' => 1), array('id' => $tid));
    }
    //移动聊天记录
if ($operation == "doMobileMychat")
    {
        global $_W, $_GPC;
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        if ($operation == 'display') {
            $mychatlist = pdo_fetchall("SELECT id,openid,toopenid FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND toopenid = '{$_W['fans']['from_user']}' ORDER BY hasread ASC,time DESC");
            $reschatlist = array();
            foreach ($mychatlist as $k => $v) {
                if (empty($reschatlist[$v['openid']])) {
                    $chantcon = pdo_fetch("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND openid = '{$v['openid']}' AND toopenid = '{$v['toopenid']}' ORDER BY time DESC");
                    $chantcon['content'] = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $chantcon['content']);
                    $reschatlist[$v['openid']]['chatlist'] = $chantcon;
                    $reschatlist[$v['openid']]['hasnotread'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND openid = '{$v['openid']}' AND toopenid = '{$v['toopenid']}' AND hasread = 0");
                }
            }
            $isajax = intval($_GPC['isajax']);
            if ($isajax == 1) {
                $html = '';
                foreach ($reschatlist as $kk => $vv) {
                    if ($vv['chatlist']['type'] == 2 || $vv['chatlist']['type'] == 3) {
                        $tores = '<a class="mui-btn mui-btn-yellow mui-icon mui-icon-compose" style="transform: translate(-0px, 0px);" href="' . $this->createMobileUrl('chat', array('toopenid' => $vv['chatlist']['openid'])) . '"></a>';
                    } else {
                        $tores = '<a class="mui-btn mui-btn-yellow mui-icon mui-icon-compose" style="transform: translate(-0px, 0px);" href="' . $this->createMobileUrl('servicechat', array('toopenid' => $vv['chatlist']['openid'])) . '"></a>';
                    }
                    if ($vv['hasnotread'] > 0) {
                        $readres = '<span class="mui-badge mui-badge-danger">' . $vv['hasnotread'] . '</span>';
                    } else {
                        $readres = '';
                    }
                    if ($vv['chatlist']['type'] == 3 || $vv['chatlist']['type'] == 4) {
                        $conmsg = '<span style="color:#900;">[图片消息]</span>';
                    } elseif ($vv['chatlist']['type'] == 5 || $vv['chatlist']['type'] == 6) {
                        $conmsg = '<span style="color:green;">[语音消息]</span>';
                    } else {
                        $conmsg = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $vv['chatlist']['content']);
                    }
                    $html .= '<li class="mui-table-view-cell">
												<div class="mui-slider-right mui-disabled">
													' . $tores . '
													<a class="mui-btn mui-btn-red mui-icon mui-icon-trash" style="transform: translate(-0px, 0px);" href="' . $this->createMobileUrl('mychat', array('op' => 'delete', 'toopenid' => $vv['chatlist']['openid'])) . '"></a>
												</div>
												<div class="mui-slider-handle">
													<div class="mui-row">
														<div class="mui-col-sm-2 mui-col-xs-2">
															<img src="' . $vv['chatlist']['avatar'] . '" style="width:80%;" />
														</div>
														<div class="mui-col-sm-9 mui-col-xs-9">
															<div><span class="mui-badge">' . $vv['chatlist']['nickname'] . '</span></div>
															<div style="margin-top:5px;font-size:15px;">' . $conmsg . '</div>
														</div>
														<div class="mui-col-sm-1 mui-col-xs-1">
															' . $readres . '
														</div>
													</div>
												</div>
											</li>';
                }
                echo $html;
                die;
            }
            include wl_template('mychat');
        } elseif ($operation == 'chatdelete') {
            $toopenid = trim($_GPC['toopenid']);
            $message = pdo_fetch("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND ((openid = '{$_W['fans']['from_user']}' AND toopenid = '{$toopenid}') OR (toopenid = '{$_W['fans']['from_user']}' AND openid = '{$toopenid}'))");
            if (empty($message)) {
                $resArr['error'] = 1;
                $resArr['message'] = "不存在该聊天记录！";
                echo json_encode($resArr);
                die;
            }
            pdo_delete(BEST_CHAT, array('openid' => $_W['fans']['from_user'], 'toopenid' => $toopenid));
            pdo_delete(BEST_CHAT, array('toopenid' => $_W['fans']['from_user'], 'openid' => $toopenid));
            $resArr['message'] = "恭喜您，删除聊天记录成功！";
            $resArr['error'] = 0;
            echo json_encode($resArr);
            die;
        }
    }
    //封装函数
    function mkThumbnail($src, $width = null, $height = null, $filename = null)
    {
        if (!isset($width) && !isset($height)) {
            return false;
        }
        if (isset($width) && $width <= 0) {
            return false;
        }
        if (isset($height) && $height <= 0) {
            return false;
        }
        $size = getimagesize($src);
        if (!$size) {
            return false;
        }
        list($src_w, $src_h, $src_type) = $size;
        $src_mime = $size['mime'];
        switch ($src_type) {
            case 1:
                $img_type = 'gif';
                break;
            case 2:
                $img_type = 'jpeg';
                break;
            case 3:
                $img_type = 'png';
                break;
            case 15:
                $img_type = 'wbmp';
                break;
            default:
                return false;
        }
        if (!isset($width)) {
            $width = $src_w * ($height / $src_h);
        }
        if (!isset($height)) {
            $height = $src_h * ($width / $src_w);
        }
        $imagecreatefunc = 'imagecreatefrom' . $img_type;
        $src_img = $imagecreatefunc($src);
        $dest_img = imagecreatetruecolor($width, $height);
        imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
        $imagefunc = 'image' . $img_type;
        if ($filename) {
            $imagefunc($dest_img, $filename);
        } else {
            header('Content-Type: ' . $src_mime);
            $imagefunc($dest_img);
        }
        imagedestroy($src_img);
        imagedestroy($dest_img);
        return true;
    }