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
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'doMobileGroupchat';
//快速回复
if ($operation == "doMobileGetquick")
{
    global $_GPC, $_W;
    $openid = trim($_GPC['openid']);
    include wl_template('quick');
}
//选择客服
if ($operation == "doMobileChosekefu")
{
    global $_W, $_GPC;
    $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
    $setting['shareurl'] = app_url('chat/chat',array("op"=>"chosekefu"));
    $cservicelist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND groupid = 0 ORDER BY displayorder ASC");
    $cservicegrouplist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE weid = {$_W['uniacid']} ORDER BY displayorder ASC");
    include wl_template('chat/chosekefu');
}
//客服组聊天
if ($operation == "doMobileGroupchat")
{
    global $_W, $_GPC;
    $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
    $id = intval($_GPC['id']);
    $setting['shareurl'] =app_url('chat/chat',array("op"=>"groupchat"));

    $cservicelist = pdo_fetchall("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND groupid = {$id} ORDER BY displayorder ASC");
    $cservicegroup = pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICEGROUP) . " WHERE weid = {$_W['uniacid']} AND id = {$id}");
    include wl_template('chat/groupchat');
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
    //找到客服的位置
    $touser = pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND content = '{$toopenid}'");
    $tousers= pdo_fetch("SELECT * FROM " . tablename(BEST_CSERVICE) . " WHERE weid = {$_W['uniacid']} AND content = '{$toopenid}'");
    if (!$tousers){
        $tousers= pdo_fetch("SELECT * FROM " . tablename("tg_member") . " WHERE uniacid = {$_W['uniacid']} AND from_user = '{$toopenid}'");
        $tousers["name"] = $tousers["nickname"];
    }
    //找到聊天记录
    $chatcon = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE ((openid = '{$openid}' AND toopenid = '{$toopenid}') OR (openid = '{$toopenid}' AND toopenid = '{$openid}')) AND weid = {$_W['uniacid']} ORDER BY time ASC");
    //循环聊天记录
    foreach ($chatcon as $k => $v) {
        $chatcon[$k]['content'] = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $v['content']);
    }
    //当前时间
    $timestamp = TIMESTAMP;
    $setting = pdo_fetch("SELECT * FROM " . tablename(BEST_SET) . " WHERE weid = {$_W['uniacid']}");
    $fansauto = explode("|", $setting['fansauto']);
    $setting['shareurl'] = app_url("chat/chat",'chosekefu');
    $dataup['hasread'] = 1;
    pdo_update(BEST_CHAT, $dataup, array('weid' => $_W['uniacid'], 'toopenid' => $openid, 'openid' => $toopenid, 'hasread' => 0));
    //查询商品的top图片  商品的名称
    $shop_id = $_GET["id"];
    if (!empty($shop_id)) {
        //查询商品的logo图
        $shop_info = pdo_fetch("select * from " . tablename("tg_goods") . " where id=:id", array(":id" => $shop_id));//获取商品的详细信息
        //团购价是
        $shop["tprice"] = $shop_info["gprice"];
        //名字
        $shop["name"] = $shop_info["gname"];;
        //最低拼团人数
        $shop["last_number"] = $shop_info["groupnum"];
        //商品的logo图
        $shop["img"] = tomedia($shop_info["gimg"]);
        //拼接链接
        $shop['url'] = app_url("goods/detail", array("id" => $id));
    }else{
        unset($shop);
    }
  //  var_dump($shop);die();
    include wl_template('chat/newchat');
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
    $setting['shareurl'] = app_url("chat/chat",array('op'=>'chosekefu'));
    $dataup['hasread'] = 1;
    pdo_update(BEST_CHAT, $dataup, array('weid' => $_W['uniacid'], 'toopenid' => $openid, 'openid' => $toopenid, 'hasread' => 0));
    include wl_template("chat/servicechat");
}
//共享记录

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
    $setting['shareurl'] = app_url('doMobileAllshare', array('toopenid' => $toopenid));
    include wl_template("chat/allshare");
}
//刷新
if ($operation == "doMobileShuaxinchat")
{
    global $_W, $_GPC;
    $openid = $_W['fans']['from_user'];//粉丝的openid
    $toopenid = trim($_GPC['toopenid']);//自己的openid
    $timestamp = intval($_GPC['timestamp']);//时间戳
    $chatcon = pdo_fetchall("SELECT * FROM " . tablename(BEST_CHAT) . " WHERE ((openid = '{$toopenid}' AND toopenid = '{$openid}') OR (openid = '{$openid}' AND toopenid = '{$toopenid}')) AND weid = {$_W['uniacid']} AND time >= {$timestamp} ORDER BY time ASC");
    $html = '';
    //如果查询记录不为空
    if (!empty($chatcon)) {
        foreach ($chatcon as $k => $v) {
            //这个是粉丝发送的
            $v['content'] = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '[无法识别字符]', $v['content']);
            if ($v['openid'] == $openid) {
                $lORr='msg right clear';
                $messageclass = 'message me chat-msg';
                $bubble = 'bubble bubble_primary right';
                $nicknamediv = '<div class="time">' . date('Y-m-d H:i:s', $v['time']) . '</div>';// . $v['nickname']
                $yuyin = '<i class="a-icon"></i>';
            } else {
                //客服回复的
                $lORr='msg left clear';
                $messageclass = 'message chat-msg';
                $bubble = 'bubble bubble_default left';
                $nicknamediv =  '<div class="time">' . date('Y-m-d H:i:s', $v['time']) . '</div>';//$v['nickname'] .
                $yuyin = '<i class="a-icon"></i>';
            }
            if ($v['type'] == 3 || $v['type'] == 4) {
                $content = '<div class="txt-con" style="padding:2px;"><img src="' . $v['content'] . '" style="max-width:100%;" /></div>';
            } elseif ($v['type'] == 5 || $v['type'] == 6) {
                $content = '<div class="txt-con" style="color:#900;width:4.4rem;" onclick="playvoice(\'' . $v['content'] . '\');">' . $yuyin . '</div>';
            } else {
                $content = '<div class="txt-con">' . $v['content'] . '</div>';
            }
            $imgsrc = $v['avatar'];
            $html .= '<div class="' . $messageclass . '">' . $nicknamediv . '<div class="'.$lORr.'"><div class="nick-img"><img src="' . $imgsrc . '" /></div>' . '<div class="nick-text">' . '<div class="nickname"></div>' . '<div class="' . $bubble . '">' . ' <div class="bubble_cont">' . $content . '</div></div>' . '</div>' . '</div>' . '</div>';
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
    include_once '../addons/lexiangpingou/app/resource/emoji/emoji.php';
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
        $tplcon = '粉丝发送了图片';
    } elseif ($type == 5 || $type == 6) {
        $tplcon = '粉丝发送了语音';
    } else {
        if (strpos($data['content'], 'span class=')) {
            $tplcon = '粉丝发送了表情';
        } else {
            $tplcon = $data['content'];
        }
    }

    $hasliao = pdo_fetch("SELECT id,time FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND openid = '{$data['openid']}' AND toopenid = '{$data['toopenid']}' ORDER BY time DESC");
    $guotime = TIMESTAMP - $hasliao['time'];
    if ($setting['istplon'] == 1 && (empty($hasliao) || $guotime > $setting['kefutplminute'])) {
        $tpllist = pdo_fetch("SELECT id,tplbh FROM" . tablename(BEST_TPLMESSAGE_TPLLIST) . " WHERE (tplbh = 'OPENTM202309749' OR tplbh = 'OPENTM207327169' OR tplbh = 'OPENTM413387398') AND uniacid = {$_W['uniacid']}");
        if (!empty($tpllist)) {
            if ($tpllist['tplbh'] == 'OPENTM202309749') {
                $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '用户咨询提醒', 'firstcolor' => '#990000', 'keyword1' => $data['nickname'], 'keyword1color' => '', 'keyword2' => date("Y-m-d H:i:s", TIMESTAMP), 'keyword2color' => '', 'remark' => $tplcon, 'remarkcolor' => '', 'url' =>app_url("chat/chat", array('toopenid' => $data['openid'],"op"=>"doMobileChat")));
            } else if($tpllist['tplbh'] == 'OPENTM207327169') {
                $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '用户咨询提醒', 'firstcolor' => '#990000', 'keyword1' => date("Y-m-d H:i:s", TIMESTAMP), 'keyword1color' => '', 'keyword2' => 1, 'keyword2color' => '', 'remark' => $tplcon, 'remarkcolor' => '', 'url' => app_url("chat/chat", array('toopenid' => $data['openid'],"op"=>"doMobileChat")));
            }else{
                $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '用户咨询提醒', 'firstcolor' => '#990000', 'keyword1' => $data['nickname'], 'keyword1color' => '', 'keyword2' => date("Y-m-d H:i:s", TIMESTAMP), 'keyword2color' => '', 'remark' => $tplcon, 'remarkcolor' => '#990000', 'url' => app_url("chat/chat", array('toopenid' => $data['openid'],"op"=>"doMobileChat")));
            }
            sendtemmsg($tpllist['id'], $arrmsg);
        }
    }
    file_put_contents(TG_DATA."payresult2.log", var_export($tpllist, true).PHP_EOL, FILE_APPEND);
    pdo_insert(BEST_CHAT, $data);
    $resArr['error'] = 0;
    $resArr['msg'] = $chatcontent;
    echo json_encode($resArr);
    die;
}
//第二个窗口
if ($operation == "doMobileAddchat2")
{
    global $_W, $_GPC;
    include_once '../addons/lexiangpingou/app/resource/emoji/emoji.php';
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
                $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '您好，您咨询客服的问题已回复', 'firstcolor' => '#990000', 'keyword1' => $data['nickname'], 'keyword1color' => '', 'keyword2' => date("Y-m-d H:i:s", TIMESTAMP), 'keyword2color' => '', 'keyword3' => $tplcon, 'keyword3color' => '', 'remark' => '', 'remarkcolor' => '', 'url' => app_url("chat/chat", array('toopenid' => $data['openid'])));
            } else {
                $lastmsg = pdo_fetch("SELECT content,type FROM " . tablename(BEST_CHAT) . " WHERE weid = {$_W['uniacid']} AND openid = '{$data['toopenid']}' AND toopenid = '{$data['openid']}' ORDER BY time DESC");
                if ($lastmsg['type'] == 3 || $lastmsg['type'] == 4) {
                    $lastmsgcon = '图片消息';
                } elseif ($lastmsg['type'] == 5 || $lastmsg['type'] == 6) {
                    $lastmsgcon = '语音消息';
                } else {
                    $lastmsgcon = $lastmsg['content'];
                }
                $arrmsg = array('openid' => $data['toopenid'], 'topcolor' => '#980000', 'first' => '您好，您咨询客服的问题已回复', 'firstcolor' => '#990000', 'keyword1' => $lastmsgcon, 'keyword1color' => '', 'keyword2' => $tplcon, 'keyword2color' => '', 'remark' => '回复时间：' . date("Y-m-d H:i:s", TIMESTAMP), 'remarkcolor' => '', 'url' => app_url("chat/chat", array('toopenid' => $data['openid'])));
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
//图片
if ($operation == "doMobileGetmedia")
{
    global $_W, $_GPC;
    $access_token = WeAccount::token();
    $media_id = $_GPC['media_id'];
    $url = "https://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$access_token."&media_id=".$media_id;
    $updir = "../attachment/images/".$_W['uniacid']."/".date("Y",time())."/".date("m",time())."/";
    if (!file_exists($updir)) {
        mkdir($updir, 511, true);
    }
//    " . $_W['uniacid'] . "
    $randimgurl = "images/".$_W['uniacid']."/".date("Y",time())."/".date("m",time())."/".date('YmdHis').rand(1000,9999).'.jpg';
    $targetName = "../attachment/".$randimgurl;
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
if ($operation == "doMobileAddchat")
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
    $operations = !empty($_GPC['ops']) ? $_GPC['ops'] : 'display';
    if ($operations == 'display') {
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
                    $tores = '<a class="mui-btn mui-btn-yellow mui-icon mui-icon-compose" style="transform: translate(-0px, 0px);" href="' . app_url('chat/chat', array('toopenid' => $vv['chatlist']['openid'])) . '"></a>';
                } else {
                    $tores = '<a class="mui-btn mui-btn-yellow mui-icon mui-icon-compose" style="transform: translate(-0px, 0px);" href="' . app_url('chat/chat', array('toopenid' => $vv['chatlist']['openid'],"op"=>"doMobileServicechat")) . '"></a>';
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
													<a class="mui-btn mui-btn-red mui-icon mui-icon-trash" style="transform: translate(-0px, 0px);" href="' . app_url('chat/chat', array('op' => 'chatdelete', 'toopenid' => $vv['chatlist']['openid'])) . '"></a>
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
            message($html);
            die;
        }
        include wl_template('chat/mychat');
    } elseif ($operations == 'chatdelete') {
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