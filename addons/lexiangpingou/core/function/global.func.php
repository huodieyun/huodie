<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * 火蝶云公共方法
 */
defined('IN_IA') or exit('Access Denied');
function saveImage($path)
{
    global $_W;
    if (!preg_match('/\/([^\/]+\.[a-z]{3,4})$/i', $path, $matches))
        die('Use image please');
    $image_name = strToLower($matches[1]);
    $ch = curl_init($path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    $img = curl_exec($ch);
    curl_close($ch);
    $path2 = IA_ROOT . "/addons/lexiangpingou/data/share/";
    $fp = fopen($path2 . $image_name, 'w');
    fwrite($fp, $img);
    fclose($fp);
    //file_put_contents(TG_DATA."test5.log", var_export($img, true).PHP_EOL, FILE_APPEND);
    return $_W['siteroot'] . 'addons/lexiangpingou/data/share/' . $image_name;
}

/********************web页面跳转************************/
function web_url($segment, $params = array())
{
    global $_W;
    list($do, $ac, $op) = explode('/', $segment);

    $url = $_W['siteroot'] . 'web/index.php?c=site&a=entry&m=lexiangpingou&';


    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    if (!empty($op)) {
        $url .= "op={$op}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }

    return $url;
}

/********************app页面跳转************************/
function web_app_url($segment, $params = array())
{
    global $_W;
    list($do, $ac, $op) = explode('/', $segment);
    $roots = 'w9.huodiesoft.com';
    if ($_W['uniacid'] != 53) {
        $roots = 'www.lexiangpingou.cn';
    }
    $wechat = pdo_fetch('select * from ' . tableName('account_wechats') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
    $head_http = "http://";
    if ($wechat['is_https'] == 1) {
        $head_http = "https://";
    }
    $url = $head_http . $wechat['key'] . "." . $roots . "/" . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';
    $uni_settings = pdo_fetch('select oauth from ' . tablename('uni_settings') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
    $oauth = iunserializer($uni_settings['oauth']);
    if (!empty($oauth['host'])) {
        $url = $oauth['host'] . '/app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';
    }

    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    if (!empty($op)) {
        $url .= "op={$op}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }
    return $url;
}

function app_url($segment, $params = array())
{
    global $_W;
    list($do, $ac, $op) = explode('/', $segment);
    $roots = 'w9.huodiesoft.com';
    if ($_W['uniacid'] != 53) {
        $roots = 'www.lexiangpingou.cn';
    }
    $wechat = pdo_fetch('select * from ' . tableName('account_wechats') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
    $head_http = "http://";
    if ($wechat['is_https'] == 1) {
        $head_http = "https://";
    }
    $url = $head_http . $wechat['key'] . "." . $roots . "/" . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';
    if ($_W['uniacid'] == 33) {
        $url = $head_http . $roots . "/" . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';
    }
    $uni_settings = pdo_fetch('select oauth from ' . tablename('uni_settings') . ' where uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
    $oauth = iunserializer($uni_settings['oauth']);
    if (!empty($oauth['host'])) {
        $url = $oauth['host'] . '/app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';
    }
    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    if (!empty($op)) {
        $url .= "op={$op}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }
    //message($url);
    return $url;
}

function new_app_url($segment, $params = array())
{
    global $_W;
    list($do, $ac, $op) = explode('/', $segment);

    $url = $_W['siteroot'] . '/app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&';

    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($ac)) {
        $url .= "ac={$ac}&";
    }
    if (!empty($op)) {
        $url .= "op={$op}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }
    //message($url);
    return $url;
}

function mobile_mask($mobile)
{
    return substr($mobile, 0, 3) . '****' . substr($mobile, 7);
}


function wl_debug($value)
{
    echo "<br><pre>";
    print_r($value);
    echo "</pre>";
    exit;
}


function wl_log($message, $data = '')
{
    if ($data) {
        pdo_insert('core_text', array('content' => iserializer($data)));
        $text_id = pdo_insertid();
    }
    $log = array(
        'errno' => 0,
        'message' => $message,
        'text_id' => intval($text_id),
        'createtime' => TIMESTAMP,
        'ip' => CLIENT_IP
    );
    pdo_insert('core_error_log', $log);
}

function mayi_log($s, $orderno, $from)
{

    $log = array(
        's' => $s,
        'orderno' => $orderno,
        'from' => $from
    );
    pdo_insert('tg_log', $log);
}

function api_log($message, $data = '')
{
    if (DEVELOPMENT && ((CURRENT_IP && CURRENT_IP == CLIENT_IP) || CURRENT_IP == '')) {
        if ($data) {
            $message .= ' -> ';
            if (is_resource($data)) {
                $message .= '资源文件';
            } elseif (gettype($data) == 'object' || is_array($data)) {
                $message .= iserializer($data);
            } else {
                $message .= $data;
            }
        }
        $filename = IA_ROOT . '/data/logs/api-log-' . date('Ymd', TIMESTAMP) . '.' . $_GET['platform'] . '.txt';
        if (!file_exists($filename)) {
            load()->func('file');
            mkdirs(dirname($filename));
        }
        file_put_contents($filename, $message . PHP_EOL . PHP_EOL, FILE_APPEND);
    }
}


function pwd_hash($password, $salt)
{
    return md5("{$password}-{$salt}-{$GLOBALS['_W']['config']['setting']['authkey']}");
}


function ajax_post_only()
{
    global $_W;
    if (empty($_W['isajax']) || empty($_W['ispost'])) {
        access_denied('ajax && post only');
    }
}

function is_weixin()
{
    if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false && strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') === false) {
        return false;
    }
    return true;
}

function removeEmoji($text)
{
    $clean_text = "";
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $clean_text = preg_replace($regexEmoticons, '', $text);
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $clean_text = preg_replace($regexSymbols, '', $clean_text);
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $clean_text = preg_replace($regexTransport, '', $clean_text);
    $regexMisc = '/[\x{2600}-\x{26FF}]/u';
    $clean_text = preg_replace($regexMisc, '', $clean_text);
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    $clean_text = preg_replace($regexDingbats, '', $clean_text);

    return $clean_text;
}

function wl_template($filename, $flag = '')
{
    global $_W;
    $name = 'lexiangpingou';
    if (defined('IN_SYS')) {
        $template = $_W['tgsetting']['webview'];
        if (empty($template)) {
            $template = "default";
        }
        $source = IA_ROOT . "/addons/{$name}/web/view/{$template}/{$filename}.html";
        $compile = IA_ROOT . "/data/tpl/web/{$name}/web/view/{$template}/{$filename}.tpl.php";
        if (!is_file($source)) {
            $source = IA_ROOT . "/addons/{$name}/web/view/default/{$filename}.html";
        }
    } else {
        $template = $_W['tgsetting']['appview'];
        if (empty($template)) {
            $template = "default";
        }
        $source = IA_ROOT . "/addons/{$name}/app/view/{$template}/{$filename}.html";
        $compile = IA_ROOT . "/data/tpl/app/{$name}/app/view/{$template}/{$filename}.tpl.php";
        if (!is_file($source)) {
            $source = IA_ROOT . "/addons/{$name}/app/view/default/{$filename}.html";
        }
    }
    if (!is_file($source)) {
        exit("Error: template source '{$filename}' is not exist!!!");
    }
    if (!is_file($compile) || filemtime($source) > filemtime($compile)) {
        wl_template_compile($source, $compile, true);

    }
    if ($flag == TEMPLATE_FETCH) {
        extract($GLOBALS, EXTR_SKIP);
        ob_end_flush();
        ob_clean();
        ob_start();
        include $compile;
        $contents = ob_get_contents();
        ob_clean();
        return $contents;
    }
    return $compile;
}

/********************app端自定义页面加载************************/
function wl_template_page($id, $flag = TEMPLATE_DISPLAY)
{
    global $_W;
    $page = wl_page($id);
    if (empty($page)) {
        return error(1, 'Error: Page is not found');
    }
    if (empty($page['html'])) {
        return '';
    }
    $compile = IA_ROOT . "/data/tpl/app/{$id}.default.tpl.php";
    $path = dirname($compile);
    if (!is_dir($path)) {
        load()->func('file');
        mkdirs($path);
    }
    $content = wl_template_parse($page['html']);
    file_put_contents($compile, $content);
    switch ($flag) {
        case TEMPLATE_DISPLAY:
        default:
            extract($GLOBALS, EXTR_SKIP);
            include wl_template('common/app_header');
            include $compile;
            include wl_template('common/app_footer');
            break;
        case TEMPLATE_FETCH:
            extract($GLOBALS, EXTR_SKIP);
            ob_clean();
            ob_start();
            include $compile;
            $contents = ob_get_contents();
            ob_clean();
            return $contents;
            break;
        case TEMPLATE_INCLUDEPATH:
            return $compile;
            break;
    }
}

/********************web端自定义页面加载************************/
function wl_tpl_wappage_editor($editorparams = '', $editormodules = array())
{
    global $_GPC;
    $content = '';
    load()->func('file');
    $filetree = file_tree(IA_ROOT . '/addons/lexiangpingou/web/view/default/wapeditor');
    if (!empty($filetree)) {
        foreach ($filetree as $file) {
            if (strexists($file, 'widget-')) {
                $fileinfo = pathinfo($file);
                $_GPC['iseditor'] = false;
                $display = wl_template('wapeditor/' . $fileinfo['filename'], TEMPLATE_FETCH);
                $_GPC['iseditor'] = true;
                $editor = wl_template('wapeditor/' . $fileinfo['filename'], TEMPLATE_FETCH);
                $content .= "<script type=\"text/ng-template\" id=\"{$fileinfo['filename']}-display.html\">" . str_replace(array("\r\n", "\n", "\t"), '', $display) . "</script>";
                $content .= "<script type=\"text/ng-template\" id=\"{$fileinfo['filename']}-editor.html\">" . str_replace(array("\r\n", "\n", "\t"), '', $editor) . "</script>";
            }
        }
    }
    return $content;
}

/********************web端自定义页面加载************************/
function wl_tpl_ueditor($id, $value = '')
{
    $s = '';
    if (!defined('TPL_INIT_UEDITOR')) {
        $s .= '<script type="text/javascript" src="../addons/lexiangpingou/web/resource/components/ueditor/ueditor.config.js"></script><script type="text/javascript" src="../addons/lexiangpingou/web/resource/components/ueditor/ueditor.all.min.js"></script><script type="text/javascript" src="../addons/lexiangpingou/web/resource/components/ueditor/lang/zh-cn/zh-cn.js"></script>';
    }
    $s .= !empty($id) ? "<textarea id=\"{$id}\" name=\"{$id}\" type=\"text/plain\" style=\"height:300px;\">{$value}</textarea>" : '';
    $s .= '<script type="text/javascript">var ueditoroption={autoClearinitialContent:!1,toolbars:[["fullscreen","source","preview","|","bold","italic","underline","strikethrough","forecolor","backcolor","|","justifyleft","justifycenter","justifyright","|","insertorderedlist","insertunorderedlist","blockquote","emotion","insertvideo","link","removeformat","|","rowspacingtop","rowspacingbottom","lineheight","indent","paragraph","fontsize","|","inserttable","deletetable","insertparagraphbeforetable","insertrow","deleterow","insertcol","deletecol","mergecells","mergeright","mergedown","splittocells","splittorows","splittocols","|","anchor","map","print","drafts"]],elementPathEnabled:!1,initialFrameHeight:500,focus:!1,maximumWords:9999999999999,autoFloatEnabled:!1,imageScaleEnabled:!1};UE.registerUI("myinsertimage",function(a,b){a.registerCommand(b,{execCommand:function(){require(["uploader"],function(b){b.show(function(b){if(0!=b.length)if(b.url)a.execCommand("insertimage",{src:b.url,_src:b.url,width:"100%",alt:b.filename});else{var c=[];for(i in b)c.push({src:b[i].url,_src:b[i].url,width:"100%",alt:b[i].filename});a.execCommand("insertimage",c)}})})}});var c=new UE.ui.Button({name:"插入图片",title:"插入图片",cssRules:"background-position: -726px -77px",onclick:function(){a.execCommand(b)}});return a.addListener("selectionchange",function(){var d=a.queryCommandState(b);-1==d?(c.setDisabled(!0),c.setChecked(!1)):(c.setDisabled(!1),c.setChecked(d))}),c},19)' . (!empty($id) ? ',$(function(){var a=UE.getEditor("' . $id . '",ueditoroption);$("#' . $id . '").data("editor",a),$("#' . $id . '").parents("form").submit(function(){a.queryCommandState("source")&&a.execCommand("source")})});' : ';') . "</script>";
    return $s;
}

function wl_template_compile($from, $to, $inmodule = false)
{
    $path = dirname($to);
    if (!is_dir($path)) {
        load()->func('file');
        mkdirs($path);
    }
    $content = wl_template_parse(file_get_contents($from), $inmodule);
    if (IMS_FAMILY == 'x' && !preg_match('/(footer|header)+/', $from)) {
        $content = str_replace('火蝶云', '系统', $content);
    }
    file_put_contents($to, $content);
}

function wl_template_parse($str, $inmodule = false)
{
    $str = preg_replace('/<!--{(.+?)}-->/s', '{$1}', $str);
    $str = preg_replace('/{template\s+(.+?)}/', '<?php (!empty($this) && $this instanceof WeModuleSite || ' . intval($inmodule) . ') ? (include $this->template($1, TEMPLATE_INCLUDEPATH)) : (include template($1, TEMPLATE_INCLUDEPATH));?>', $str);

    $str = preg_replace('/{php\s+(.+?)}/', '<?php $1?>', $str);
    $str = preg_replace('/{if\s+(.+?)}/', '<?php if($1) { ?>', $str);
    $str = preg_replace('/{else}/', '<?php } else { ?>', $str);
    $str = preg_replace('/{else ?if\s+(.+?)}/', '<?php } else if($1) { ?>', $str);
    $str = preg_replace('/{\/if}/', '<?php } ?>', $str);
    $str = preg_replace('/{loop\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2) { ?>', $str);
    $str = preg_replace('/{loop\s+(\S+)\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2 => $3) { ?>', $str);
    $str = preg_replace('/{\/loop}/', '<?php } } ?>', $str);
    $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)}/', '<?php echo $1;?>', $str);
    $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\[\]\'\"\$]*)}/', '<?php echo $1;?>', $str);
    $str = preg_replace('/{url\s+(\S+)}/', '<?php echo url($1);?>', $str);
    $str = preg_replace('/{url\s+(\S+)\s+(array\(.+?\))}/', '<?php echo url($1, $2);?>', $str);
    $str = preg_replace_callback('/<\?php([^\?]+)\?>/s', "template_addquote", $str);
    $str = preg_replace('/{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}/s', '<?php echo $1;?>', $str);
    $str = str_replace('{##', '{', $str);
    $str = str_replace('##}', '}', $str);
    $str = "<?php defined('IN_IA') or exit('Access Denied');?>" . $str;
    return $str;
}

function wl_template_addquote($matchs)
{
    $code = "<?php {$matchs[1]}?>";
    $code = preg_replace('/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\](?![a-zA-Z0-9_\-\.\x7f-\xff\[\]]*[\'"])/s', "['$1']", $code);
    return str_replace('\\\"', '\"', $code);
}

$my_scenfiles = array();
function my_scandir($dir)
{
    global $my_scenfiles;
    if ($handle = opendir($dir)) {
        while (($file = readdir($handle)) !== false) {
            if ($file != ".." && $file != ".") {
                if (is_dir($dir . "/" . $file)) {
                    my_scandir($dir . "/" . $file);
                } else {
                    $my_scenfiles[] = $dir . "/" . $file;
                }
            }
        }
        closedir($handle);
    }
}

function currency_format($currency, $decimals = 2)
{
    $currency = floatval($currency);
    if (empty($currency)) {
        return '0.00';
    }
    $currency = number_format($currency, $decimals);
    $currency = str_replace(',', '', $currency);
    return $currency;
}

function finance($openid = '', $paytype = 0, $money = 0, $trade_no = '', $desc = '')
{
    global $_W;
    if (empty($openid)) {
        return error(-1, 'openid不能为空');
    }
    $setting = uni_setting($_W['uniacid'], array('payment'));
    if (!is_array($setting['payment'])) {
        return error(1, '没有设定支付参数');
    }
    $wechat = $setting['payment']['wechat'];
    $sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid limit 1';
    $row = pdo_fetch($sql, array(':uniacid' => $_W['uniacid']));
    $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    $pars = array();
    $pars['mch_appid'] = $row['key'];
    $pars['mchid'] = $wechat['mchid'];
    $pars['nonce_str'] = random(32);
    $pars['partner_trade_no'] = empty($trade_no) ? time() . random(4, true) : $trade_no;
    $pars['openid'] = $openid;
    $pars['check_name'] = 'NO_CHECK';
    $pars['amount'] = $money;
    $pars['desc'] = empty($desc) ? '商家佣金提现' : $desc;
    $pars['spbill_create_ip'] = gethostbyname($_SERVER["HTTP_HOST"]);
    ksort($pars, SORT_STRING);
    $string1 = '';
    foreach ($pars as $k => $v) {
        $string1 .= "{$k}={$v}&";
    }
    $string1 .= "key=" . $wechat['apikey'];
    $pars['sign'] = strtoupper(md5($string1));
    $xml = array2xml($pars);
    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_cert.pem';
    //证书路径
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_key.pem';
    //证书路径
    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = IA_ROOT . '/addons/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_cert.pem';
        $path_key = IA_ROOT . '/addons/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_key.pem';
    }
    $extras = array();
    $extras['CURLOPT_SSLCERT'] = $path_cert;
    $extras['CURLOPT_SSLKEY'] = $path_key;
//			$extras['CURLOPT_CAINFO'] = IA_ROOT . str_replace("../", "/", $pay['weixin_root']);
    load()->func('communication');
    $resp = ihttp_request($url, $xml, $extras);

    if (empty($resp['content'])) {
        return error(-2, '网络错误');
    } else {
        $arr = json_decode(json_encode((array)simplexml_load_string($resp['content'])), true);
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
        $dom = new \DOMDocument();
        if ($dom->loadXML($xml)) {
            $xpath = new \DOMXPath($dom);
            $code = $xpath->evaluate('string(//xml/return_code)');
            $ret = $xpath->evaluate('string(//xml/result_code)');
            if (strtolower($code) == 'success' && strtolower($ret) == 'success') {
                return true;
            } else {
                $error = $xpath->evaluate('string(//xml/err_code_des)');
                return error(-2, $error);
            }
        } else {
            return error(-1, '未知错误');
        }
    }

}

/*创建或更新浏览量*/
function puv($openid = '', $goodsid = '')
{
    global $_W;

    if (!empty($goodsid)) {
        $goods = pdo_fetch("select pv,uv from" . tablename('tg_goods') . "where uniacid={$_W['uniacid']} and id={$goodsid}");
        $mygp = pdo_fetch("select openid from" . tablename('tg_puv_record') . "where uniacid={$_W['uniacid']} and openid='{$openid}' and goodsid={$goodsid}");
        pdo_insert('tg_puv_record', array('uniacid' => $_W['uniacid'], 'openid' => $openid, 'goodsid' => $goodsid, 'createtime' => TIMESTAMP));
        if (!empty($mygp)) {
            pdo_update('tg_goods', array('pv' => $goods['pv'] + 1), array('id' => $goodsid));
        } else {
            pdo_update('tg_goods', array('pv' => $goods['pv'] + 1, 'uv' => $goods['uv'] + 1), array('id' => $goodsid));
        }
    }
}

function puvindex($openid = '')
{
    global $_W;

    $pu = pdo_fetch("select * from" . tablename('tg_puv') . "where uniacid={$_W['uniacid']} limit 1");
    if (empty($pu)) {
        pdo_insert('tg_puv', array('uniacid' => $_W['uniacid'], 'pv' => 0, 'uv' => 0));
        $pu = pdo_fetch("select * from" . tablename('tg_puv') . "where uniacid={$_W['uniacid']} limit 1");
    }
    $myp = pdo_fetch("select openid from" . tablename('tg_puv_record') . "where uniacid={$_W['uniacid']} and openid='{$openid}' ");
    pdo_insert('tg_puv_record', array('uniacid' => $_W['uniacid'], 'openid' => $openid, 'goodsid' => 0, 'createtime' => TIMESTAMP));
    if (!empty($myp)) {
        pdo_update('tg_puv', array('pv' => $pu['pv'] + 1), array('id' => $pu['id']));
    } else {
        pdo_update('tg_puv', array('pv' => $pu['pv'] + 1, 'uv' => $pu['uv'] + 1), array('id' => $pu['id']));
    }

}

function aotorefund()
{
    global $_W, $_GPC;
    $tableName = "tg_options";
    $threadCount = 5;
    $saveData = array();
    $returnArray = array();
    $saveData['uniacid'] = $_W['uniacid'];
    $saveData['weid'] = $_W['uniacid'];
    $saveData['add_time'] = time();
    $saveData['type'] = $_GPC['msgtype'];
    $saveData['thread_count'] = intval($threadCount);
    $saveData['options'] = htmlspecialchars_decode($_GPC['options']);
    $fansList = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE uniacid='{$_W['uniacid']}' and status=10 order by tuan_id asc");
    $saveData['total'] = count($fansList);
    if (count($fansList) <= 0) {

        echo "<script>alert('当前没有需要退款的订单');location.href='" . web_url('order/mass') . "';</script>";
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
        ihttp_request($_W['siteroot'] . 'web/index.php?c=site&a=entry&m=lexiangpingou&do=order&ac=autorefund', NULL, NULL, 1);
        //ihttp_request('http://w9.huodiesoft.com/web/index.php?c=site&a=entry&m=lexiangpingou&do=order&ac=refundProcess&',array('oid' => $insertId),null,1);

    }
}

function getClientIP()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR");
    else $ip = "Unknow";
    return $ip;
}

function wl_message($msg, $redirect = '', $type = '')
{
    global $_W;
    if ($redirect == 'refresh') {
        $redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'];
    } elseif (!empty($redirect) && !strexists($redirect, 'http://')) {
        $urls = parse_url($redirect);
        $redirect = $_W['siteroot'] . 'app/index.php?' . $urls['query'];
    }
    if ($redirect == '') {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql')) ? $type : 'info';
    } else {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql')) ? $type : 'success';
    }
    if ($_W['isajax'] || $type == 'ajax') {
        $vars = array();
        $vars['message'] = $msg;
        $vars['redirect'] = $redirect;
        $vars['type'] = $type;
        exit(json_encode($vars));
    }
    if (empty($msg) && !empty($redirect)) {
        header('location: ' . $redirect);
    }
    $label = $type;
    if ($type == 'error') {
        $label = 'danger';
    }
    if ($type == 'ajax' || $type == 'sql') {
        $label = 'warning';
    }
    if (defined('IN_API')) {
        exit($msg);
    }
    include wl_template('common/message', TEMPLATE_INCLUDEPATH);
    exit();
}

function wl_json($status = 1, $return = null)
{
    $ret = array('status' => $status);
    if ($return) {
        $ret['result'] = $return;
    }
    die(json_encode($ret));
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

function refund($orderno, $type)
{
    global $_W;
    include_once TG_CERT . 'WxPay.Api.php';
    load()->model('account');
    load()->func('communication');
    wl_load()->model('setting');
    $WxPayApi = new WxPayApi();
    $input = new WxPayRefund();
    $accounts = uni_accounts();
    $acid = $_W['uniacid'];
    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_cert.pem';
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_key.pem';
    $account_info = pdo_fetch("select * from" . tablename('account_wechats') . "where uniacid={$_W['uniacid']}");
    $refund_order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderno}'");

    if($refund_order['g_id'] >0){
//        $goods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id='{$refund_order['g_id']}'");
    }else{
        $ss = pdo_fetch("select * from cm_tg_order a left join cm_tg_collect b on a.orderno=b.orderno");
        $refund_order['g_id'] = $ss['sid'];
        $refund_order['gnum'] = $ss['num'];
    }
    $goods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id='{$refund_order['g_id']}'");
    $settings = setting_get_by_name('refund');
    if ($refund_order['paytype'] == 0) {
        checkpaytransid_orderno($orderno);
    }
    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = TG_CERT . $_W['uniacid'] . '/apiclient_cert.pem';
        $path_key = TG_CERT . $_W['uniacid'] . '/apiclient_key.pem';
    }
    $refund_no = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $paylog = pdo_fetch("select uniontid from" . tablename('core_paylog') . "where tid ='{$orderno}'");
    $key = $settings['apikey'];
    $mchid = $settings['mchid'];
    $appid = $account_info['key'];
    $level_refund = pdo_fetch("select * from " . tableName('tg_order_level_refund') . " where orderno='{$orderno}' and status=2");
    $fee = $refund_order['price'] * 100;
    if (!empty($level_refund)) {
        $fee = ($refund_order['price'] - $level_refund['refundprice']) * 100;
    }
    $refundid = $refund_order['transid'];
    $input->SetAppid($appid);
    $input->SetMch_id($mchid);
    $input->SetOp_user_id($mchid);
    $input->SetRefund_fee($fee);
    $input->SetTotal_fee($refund_order['price'] * 100);
    $input->SetTransaction_id($refundid);
    //  $input->SetOut_trade_no($paylog['uniontid']);
    $input->SetOut_refund_no($refund_no);
    //$input -> SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
    $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);

    if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {
        $data = array('merchantid' => $refund_order['merchantid'],
            'transid' => $refund_order['transid'],
            'refund_id' => $result['refund_id'],
            'createtime' => TIMESTAMP,
            'status' => 0,
            'type' => $type,
            'goodsid' => $refund_order['g_id'],
            'orderid' => $refund_order['id'],
            'orderno' => $refund_order['orderno'],
            'payfee' => $refund_order['pay_price'],
            'refundfee' => $fee * 0.01,
            'refundername' => $refund_order['addname'],
            'refundermobile' => $refund_order['mobile'],
            'goodsname' => $goods['gname'],
            'uniacid' => $_W['uniacid']);
        $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}' and uniacid={$_W['uniacid']}");
        if (empty($refund_check)) {
            pdo_insert('tg_refund_record', $data);
        }
        if ($type == 3) {
            pdo_update('tg_order', array('status' => 4, 'is_tuan' => 2), array('id' => $refund_order['id']));
        } else {
            pdo_update('tg_order', array('status' => 7), array('id' => $refund_order['id']));
        }
        //
        if ($refund_order['tuan_id'] > 0 ) {
            //message(1);
            $groups = pdo_fetch("select * from " . tableName('tg_group') . " where groupnumber=" . $refund_order['tuan_id']);
            $gnum = $groups['neednum'] - $groups['lacknum'];
            if ($gnum == 1) {
                pdo_update('tg_group', array('groupstatus' => 1), array('groupnumber' => $refund_order['tuan_id']));
            }
            $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id', array(':id' => $refund_order['g_id']));

            if ($groups['groupstatus'] == 2) {
                pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+{$refund_order['gnum']} where id = '{$refund_order['g_id']}'");
                /*更改规格库存*/
                if (!empty($refund_order['optionname'])) {
                    $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $refund_order['g_id'], ':title' => $refund_order['optionname']));
                    pdo_update('tg_goods_option', array('stock' => $stock['stock'] + $refund_order['gnum']), array('goodsid' => $refund_order['g_id'], 'title' => $refund_order['optionname']));
                }
                //更新门店库存
                if($goodsInfo['has_store_stock'] == 1){

                    if (!empty($refund_order['optionname'])) {
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':optionid'=>$refund_order['optionid'],':uniacid'=>$refund_order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                    }else{
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':uniacid'=>$refund_order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                    }


                }
            }
            //极限单品减库存加销量
            if ($goodsInfo['supply_goodsid'] > 0) {
                $go = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $goodsInfo['supply_goodsid']);
                if ($go['stock'] >= $refund_order['gnum']) {
                    pdo_update('tg_supply_goods', array('stock' => $go['stock'] + $refund_order['gnum'], 'salenum' => $go['salenum'] - $refund_order['gnum']), array('id' => $goodsInfo['supply_goodsid']));
                }
                if (!empty($go['optionname'])) {
                    $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $goodsInfo['supply_goodsid'], ':title' => $refund_order['optionname']));
                    pdo_update('tg_goods_supply_option', array('stock' => $option['stock'] + $refund_order['gnum']), array('goodsid' => $goodsInfo['supply_goodsid'], 'title' => $refund_order['optionname']));
                }
            }


        }

        //
        pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
        if ($refund_order['tuan_id'] == 0) {
            if($refund_order['g_id'] == 0){
                //单买购物车
                $sende = "select  * from " . tablename('tg_collect') . " where   orderno= '" . $refund_order['orderno'] . "'  ";
                $sendelist = pdo_fetchall($sende);
                foreach ($sendelist as $key => $value) {
                    $goodsInfos = pdo_fetch("select id,gnum,salenum,openid from" . tablename('tg_goods') . " where id=" . $value['sid']);
                    pdo_update('tg_goods', array('gnum' => $goodsInfos['gnum'] + $value['num'], 'salenum' => $goodsInfos['salenum'] - $value['num']), array('id' => $value['sid']));

                    /*更改规格库存*/
                    if (!empty($value['item'])) {
                        $stocks = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $value['sid'], ':title' => $value['item']));
                        pdo_update('tg_goods_option', array('stock' => $stocks['stock'] + $value['num']), array('goodsid' => $value['sid'], 'title' => $value['item']));

                    }
                }
            }else{
                //团商品单买
                pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+{$refund_order['gnum']} where id = '{$refund_order['g_id']}'");
                $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id', array(':id' => $refund_order['g_id']));
                /*更改规格库存*/
                if (!empty($refund_order['optionname'])) {
                    $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $refund_order['g_id'], ':title' => $refund_order['optionname']));
                    pdo_update('tg_goods_option', array('stock' => $stock['stock'] + $refund_order['gnum']), array('goodsid' => $refund_order['g_id'], 'title' => $refund_order['optionname']));
                }
                //更新门店库存
                if($goodsInfo['has_store_stock'] == 1){

                    if (!empty($refund_order['optionname'])) {
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':optionid'=>$refund_order['optionid'],':uniacid'=>$refund_order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                    }else{
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':uniacid'=>$refund_order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                    }
                }
            }
        }
        pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
        return 'success';
    } else {

        $input->SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
        $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);
        if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {
            $data = array('merchantid' => $refund_order['merchantid'],
                'transid' => $refund_order['transid'],
                'refund_id' => $result['refund_id'],
                'createtime' => TIMESTAMP,
                'status' => 0,
                'type' => $type,
                'goodsid' => $refund_order['g_id'],
                'orderid' => $refund_order['id'],
                'orderno' => $refund_order['orderno'],
                'payfee' => $refund_order['pay_price'],
                'refundfee' => $fee * 0.01,
                'refundername' => $refund_order['addname'],
                'refundermobile' => $refund_order['mobile'],
                'goodsname' => $goods['gname'],
                'uniacid' => $_W['uniacid']);
            $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}'");
            if (empty($refund_check)) {
                pdo_insert('tg_refund_record', $data);
            }

            if ($type == 3) {
                pdo_update('tg_order', array('status' => 4, 'is_tuan' => 2), array('id' => $refund_order['id']));
            } else {
                pdo_update('tg_order', array('status' => 7), array('id' => $refund_order['id']));
            }
            //
            if ($refund_order['tuan_id'] > 0 ) {
                //message(1);
                $groups = pdo_fetch("SELECT * FROM " . tableName('tg_group') . " WHERE groupnumber=" . $refund_order['tuan_id']);
                $gnum = $groups['neednum'] - $groups['lacknum'];
                if ($gnum == 1) {
                    pdo_update('tg_group', array('groupstatus' => 1), array('groupnumber' => $refund_order['tuan_id']));
                }
                $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id', array(':id' => $refund_order['g_id']));

                if ($groups['groupstatus'] == 2) {
                    pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+{$refund_order['gnum']} where id = '{$refund_order['g_id']}'");
                    /*更改规格库存*/
                    if (!empty($refund_order['optionname'])) {
                        $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $refund_order['g_id'], ':title' => $refund_order['optionname']));
                        pdo_update('tg_goods_option', array('stock' => $stock['stock'] + $refund_order['gnum']), array('goodsid' => $refund_order['g_id'], 'title' => $refund_order['optionname']));
                    }
                    //更新门店库存
                    if($goodsInfo['has_store_stock'] == 1){

                        if (!empty($refund_order['optionname'])) {
                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':optionid'=>$refund_order['optionid'],':uniacid'=>$refund_order['uniacid']));
                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                        }else{
                            $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':uniacid'=>$refund_order['uniacid']));
                            pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                        }


                    }
                }
                //极限单品减库存加销量
                if ($goodsInfo['supply_goodsid'] > 0) {
                    $go = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $goodsInfo['supply_goodsid']);
                    if ($go['stock'] >= $refund_order['gnum']) {
                        pdo_update('tg_supply_goods', array('stock' => $go['stock'] + $refund_order['gnum'], 'salenum' => $go['salenum'] - $refund_order['gnum']), array('id' => $goodsInfo['supply_goodsid']));
                    }
                    if (!empty($go['optionname'])) {
                        $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $goodsInfo['supply_goodsid'], ':title' => $refund_order['optionname']));
                        pdo_update('tg_goods_supply_option', array('stock' => $option['stock'] + $refund_order['gnum']), array('goodsid' => $goodsInfo['supply_goodsid'], 'title' => $refund_order['optionname']));
                    }
                }


            }
            //
            pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
            if ($refund_order['tuan_id'] == 0) {
                pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+{$refund_order['gnum']} where id = '{$refund_order['g_id']}'");
                $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id', array(':id' => $refund_order['g_id']));
                /*更改规格库存*/
                if (!empty($refund_order['optionname'])) {
                    $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $refund_order['g_id'], ':title' => $refund_order['optionname']));
                    pdo_update('tg_goods_option', array('stock' => $stock['stock'] + $refund_order['gnum']), array('goodsid' => $refund_order['g_id'], 'title' => $refund_order['optionname']));
                }
                //更新门店库存
                if($goodsInfo['has_store_stock'] == 1){

                    if (!empty($refund_order['optionname'])) {
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':optionid'=>$refund_order['optionid'],':uniacid'=>$refund_order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                    }else{
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $refund_order['g_id'], ':storeid' => $refund_order['comadd'],':uniacid'=>$refund_order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $refund_order['gnum']),array('id'=>$store_stock['id']));
                    }
                }
            }
            pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
            return 'success';
        } else {
            $logdata = array(
                'orderno' => $orderno,
                'log' => json_encode($result),
                'from' => $refund_order['uniacid'] . $result['err_code_des']
            );
            pdo_insert('tg_log', $logdata);
            if ($result['err_code_des'] == '订单已全额退款' || $result['err_code_des'] == '累计退款金额大于支付金额' || $result['err_code_des'] == '订单状态错误') {
                pdo_update('tg_order', array('status' => 7), array('id' => $refund_order['id']));
            } elseif ($result['err_code_des'] == '用户账户异常或已注销，不能原路退回，请使用其他方式进行退款。') {
                pdo_update('tg_order', array('status' => 11, 'adminremark' => '用户账户异常或已注销，不能原路退回，请使用其他方式进行退款。'), array('id' => $refund_order['id']));
                return '用户账户异常或已注销，不能原路退回，请使用其他方式进行退款。';
            } elseif ($result['err_code_des'] == '基本账户余额不足，请充值后重新发起') {
                return '微信支付商户后台账户余额不足，请充值后重新发起';
            } else {
                if ($type == 3) {
                    pdo_update('tg_order', array('status' => 10, 'is_tuan' => 2), array('id' => $refund_order['id']));
                } else {
                    pdo_update('tg_order', array('status' => 10), array('id' => $refund_order['id']));
                }
                if ($result['err_code'] != 'SYSTEMERROR') {
                    pdo_update('account_wechats', array('refund_type' => 1, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));

                }
            }
            //return 'fail';
        }
        //
    }
}

function refunds($orderno, $type)
{
    global $_W;
    include_once TG_CERT . 'WxPay.Api.php';
    //load() -> model('account');
    load()->func('communication');
    //wl_load()->model('setting');
    $refund_order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderno}'");
    $WxPayApi = new WxPayApi();
    $input = new WxPayRefund();
    //$accounts = uni_accounts();
    $acid = $refund_order['uniacid'];
    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/' . $refund_order['uniacid'] . '/apiclient_cert.pem';
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/' . $refund_order['uniacid'] . '/apiclient_key.pem';
    $account_info = pdo_fetch("select key from" . tablename('account_wechats') . "where uniacid={$refund_order['uniacid']}");

    $goods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id='{$refund_order['g_id']}'");
    $setting = pdo_fetch("select * from" . tablename('tg_setting') . " where `key`  = 'refund' and uniacid={$refund_order['uniacid']}");
    $settings = unserialize($setting['value']);
    $level_refund = pdo_fetch("select * from " . tableName('tg_order_level_refund') . " where orderno='{$orderno}' and status=2");
    $fee = $refund_order['price'] * 100;
    if (!empty($level_refund)) {
        $fee = ($refund_order['price'] - $level_refund['refundprice']) * 100;
    }
    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = TG_CERT . $refund_order['uniacid'] . '/apiclient_cert.pem';
        $path_key = TG_CERT . $refund_order['uniacid'] . '/apiclient_key.pem';
    }
    $refund_no = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    $key = $settings['apikey'];
    $mchid = $settings['mchid'];
    $appid = $account_info['key'];

    $refundid = $refund_order['transid'];
    $input->SetAppid($appid);
    $input->SetMch_id($mchid);
    $input->SetOp_user_id($mchid);
    $input->SetRefund_fee($fee);
    $input->SetTotal_fee($refund_order['price'] * 100);
    $input->SetTransaction_id($refundid);
    $input->SetOut_refund_no($refund_no);
    $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);


    if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {
        $data = array('merchantid' => $refund_order['merchantid'],
            'transid' => $refund_order['transid'],
            'refund_id' => $result['refund_id'],
            'createtime' => TIMESTAMP,
            'status' => 0,
            'type' => $type,
            'goodsid' => $refund_order['g_id'],
            'orderid' => $refund_order['id'],
            'orderno' => $refund_order['orderno'],
            'payfee' => $refund_order['price'],
            'refundfee' => $fee * 0.01,
            'refundername' => $refund_order['addname'],
            'refundermobile' => $refund_order['mobile'],
            'goodsname' => $goods['gname'],
            'uniacid' => $refund_order['uniacid']);
        $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}'");
        if (empty($refund_check)) {
            pdo_insert('tg_refund_record', $data);
        }
        if ($type == 3) {
            pdo_update('tg_order', array('status' => 4, 'is_tuan' => 2), array('id' => $refund_order['id']));
        } else {
            pdo_update('tg_order', array('status' => 7), array('id' => $refund_order['id']));
        }
        //
        if ($refund_order['tuan_id'] > 0 && $refund_order['tuan_first'] == 1) {
            //message(1);
            $groups = pdo_fetch("select * from " . tableName('tg_group') . " where groupnumber=" . $refund_order['tuan_id']);
            $gnum = $groups['neednum'] - $groups['lacknum'];
            if ($gnum == 1) {
                pdo_update('tg_group', array('groupstatus' => 1), array('groupnumber' => $refund_order['tuan_id']));
            }
            if ($groups['groupstatus'] == 2) {
                pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
            }
        }
        //
        pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
        if ($refund_order['tuan_id'] == 0) {
            pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
        }
        pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
        return 'success';
    } else {

        $input->SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
        $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);
        if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {
            $data = array('merchantid' => $refund_order['merchantid'],
                'transid' => $refund_order['transid'],
                'refund_id' => $result['refund_id'],
                'createtime' => TIMESTAMP,
                'status' => 0,
                'type' => $type,
                'goodsid' => $refund_order['g_id'],
                'orderid' => $refund_order['id'],
                'orderno' => $refund_order['orderno'],
                'payfee' => $refund_order['pay_price'],
                'refundfee' => $fee * 0.01,
                'refundername' => $refund_order['addname'],
                'refundermobile' => $refund_order['mobile'],
                'goodsname' => $goods['gname'],
                'uniacid' => $_W['uniacid']);
            $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}'");
            if (empty($refund_check)) {
                pdo_insert('tg_refund_record', $data);
            }
            if ($type == 3) {
                pdo_update('tg_order', array('status' => 4, 'is_tuan' => 2), array('id' => $refund_order['id']));
            } else {
                pdo_update('tg_order', array('status' => 7), array('id' => $refund_order['id']));
            }
            //
            if ($refund_order['tuan_id'] > 0 && $refund_order['tuan_first'] == 1) {
                //message(1);
                $groups = pdo_fetch("select * from " . tableName('tg_group') . " where groupnumber=" . $refund_order['tuan_id']);
                $gnum = $groups['neednum'] - $groups['lacknum'];
                if ($gnum == 1) {
                    pdo_update('tg_group', array('groupstatus' => 1), array('groupnumber' => $refund_order['tuan_id']));
                }
                if ($groups['groupstatus'] == 2) {
                    pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
                }
            }
            //
            pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
            if ($refund_order['tuan_id'] == 0) {
                pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
            }
            pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
            return 'success';
        } else {
            if ($result['err_code_des'] == '订单已全额退款' || $result['err_code_des'] == '累计退款金额大于支付金额' || $result['err_code_des'] == '订单状态错误' || $result['err_code'] == 'INVALID_REQ_TOO_MUCH') {
                pdo_update('tg_order', array('status' => 7), array('id' => $refund_order['id']));
            } else {
                if ($type == 3) {
                    pdo_update('tg_order', array('status' => 10, 'is_tuan' => 2), array('id' => $refund_order['id']));
                } else {
                    pdo_update('tg_order', array('status' => 10), array('id' => $refund_order['id']));
                }
            }
            if ($result['err_code'] != 'SYSTEMERROR') {
                pdo_update('account_wechats', array('refund_type' => 1, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));

            }
            //return 'fail';
        }
        //
    }
}

function partrefund($orderno, $type, $money)
{
    global $_W;
    include_once TG_CERT . 'WxPay.Api.php';
    load()->model('account');
    load()->func('communication');
    wl_load()->model('setting');
    $WxPayApi = new WxPayApi();
    $input = new WxPayRefund();
    $accounts = uni_accounts();
    $acid = $_W['uniacid'];
    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_cert.pem';
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/' . $_W['uniacid'] . '/apiclient_key.pem';
    $account_info = pdo_fetch("select * from" . tablename('account_wechats') . "where uniacid={$_W['uniacid']}");
    $refund_order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderno}'");
    $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$refund_order['g_id']}'");
    $settings = setting_get_by_name('refund');

    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = TG_CERT . $_W['uniacid'] . '/apiclient_cert.pem';
        $path_key = TG_CERT . $_W['uniacid'] . '/apiclient_key.pem';
    }
    $refund_no = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    //$paylog=pdo_fetch("select uniontid from" . tablename('core_paylog') . "where tid ='{$orderno}'");
    $key = $settings['apikey'];
    $mchid = $settings['mchid'];
    $appid = $account_info['key'];
    $fee = $money * 100;
    $refundid = $refund_order['transid'];
    $input->SetAppid($appid);
    $input->SetMch_id($mchid);
    $input->SetOp_user_id($mchid);
    $input->SetRefund_fee($fee);
    $input->SetTotal_fee($refund_order['price'] * 100);
    $input->SetTransaction_id($refundid);
    //$input -> SetOut_trade_no($paylog['uniontid']);
    $input->SetOut_refund_no($refund_no);
    $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);

    $data = array('merchantid' => $refund_order['merchantid'], 'transid' => $refund_order['transid'], 'orderno' => $refund_order['orderno'], 'refund_id' => $result['refund_id'], 'createtime' => TIMESTAMP, 'status' => 0, 'type' => $type, 'goodsid' => $refund_order['g_id'], 'orderid' => $refund_order['id'], 'payfee' => $refund_order['price'], 'refundfee' => $fee * 0.01, 'refundername' => $refund_order['addname'], 'refundermobile' => $refund_order['mobile'], 'goodsname' => $goods['gname'], 'uniacid' => $_W['uniacid']);
    $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}'");
    if (empty($refund_check)) {
        pdo_insert('tg_refund_record', $data);
    }
    if ($result['return_code'] == 'SUCCESS' && !empty($result['out_refund_no'])) {

        pdo_update('tg_order', array('status' => 6), array('id' => $refund_order['id']));

        pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
        //pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
        pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
        return 'success';
    } else {
        $input->SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
        $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);
        if ($result['return_code'] == 'SUCCESS' && !empty($result['refund_id'])) {
            pdo_update('tg_order', array('status' => 6), array('id' => $refund_order['id']));

            pdo_update('tg_refund_record', array('status' => 1), array('transid' => $refund_order['transid']));
            //pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+1 where id = '{$refund_order['g_id']}'");
            pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
            return 'success';
        } else {
            $logdata = array(
                'orderno' => $orderno,
                'log' => json_encode($result),
                'from' => $refund_order['uniacid'] . $result['err_code_des']
            );
            pdo_insert('tg_log', $logdata);
            if ($result['err_code'] != 'SYSTEMERROR') {
                pdo_update('account_wechats', array('refund_type' => 1, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));

            }
            return 'fail';
        }

    }
}

function partrefundlevel($orderno, $money)
{
    global $_W;
    include_once TG_CERT . 'WxPay.Api.php';
    load()->model('account');
    load()->func('communication');
    wl_load()->model('setting');
    $WxPayApi = new WxPayApi();
    $input = new WxPayRefund();
    //$accounts = uni_accounts();
    $refund_order = pdo_fetch("select * from" . tablename('tg_order') . "where orderno ='{$orderno}'");
    $acid = $refund_order['uniacid'];
    $path_cert = IA_ROOT . '/attachment/lexiangpingou/cert/' . $refund_order['uniacid'] . '/apiclient_cert.pem';
    $path_key = IA_ROOT . '/attachment/lexiangpingou/cert/' . $refund_order['uniacid'] . '/apiclient_key.pem';
    $account_info = pdo_fetch("select * from" . tablename('account_wechats') . "where uniacid={$refund_order['uniacid']}");

    $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$refund_order['g_id']}'");
    $setting = pdo_fetch("select * from" . tablename('tg_setting') . " where `key`  = 'refund' and uniacid={$refund_order['uniacid']}");
    $settings = unserialize($setting['value']);
    //$settings = setting_get_by_name('refund');

    if (!file_exists($path_cert) || !file_exists($path_key)) {
        $path_cert = TG_CERT . $refund_order['uniacid'] . '/apiclient_cert.pem';
        $path_key = TG_CERT . $refund_order['uniacid'] . '/apiclient_key.pem';
    }
    //$refund_no=date('Ymd').substr(time(), -5).substr(microtime(), 2, 5).sprintf('%02d', rand(0, 99));
    $key = $settings['apikey'];
    $mchid = $settings['mchid'];
    $appid = $account_info['key'];
    $fee = $money * 100;
    $refundid = $refund_order['transid'];
    $input->SetAppid($appid);
    $input->SetMch_id($mchid);
    $input->SetOp_user_id($mchid);
    $input->SetRefund_fee($fee);
    $input->SetTotal_fee($refund_order['price'] * 100);
    $input->SetTransaction_id($refundid);
    $input->SetOut_refund_no($orderno);

    $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);


    if ($result['return_code'] == 'SUCCESS' && !empty($result['refund_id'])) {

        pdo_update('tg_order_level_refund', array('status' => 2), array('orderno' => $orderno));
        $data = array('merchantid' => $refund_order['merchantid'],
            'transid' => $refund_order['transid'],
            'refund_id' => $result['refund_id'],
            'createtime' => TIMESTAMP,
            'status' => 1,
            'type' => 2,
            'goodsid' => $refund_order['g_id'],
            'orderid' => $refund_order['id'],
            'orderno' => $refund_order['orderno'],
            'payfee' => $refund_order['price'],
            'refundfee' => $fee * 0.01,
            'refundername' => $refund_order['addname'],
            'refundermobile' => $refund_order['mobile'],
            'goodsname' => $goods['gname'],
            'uniacid' => $refund_order['uniacid']);
        $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}'");
        if (empty($refund_check)) {
            pdo_insert('tg_refund_record', $data);
        }
        pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP));
        return 'success';
    } else {
        $input->SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
        $result = $WxPayApi->refund($input, 6, $path_cert, $path_key, $key);
        if ($result['return_code'] == 'SUCCESS' && !empty($result['refund_id'])) {
            pdo_update('tg_order_level_refund', array('status' => 2), array('orderno' => $orderno));
            $data = array('merchantid' => $refund_order['merchantid'],
                'transid' => $refund_order['transid'],
                'refund_id' => $result['refund_id'],
                'createtime' => TIMESTAMP,
                'status' => 1,
                'type' => 2,
                'goodsid' => $refund_order['g_id'],
                'orderid' => $refund_order['id'],
                'orderno' => $refund_order['orderno'],
                'payfee' => $refund_order['price'],
                'refundfee' => $fee * 0.01,
                'refundername' => $refund_order['addname'],
                'refundermobile' => $refund_order['mobile'],
                'goodsname' => $goods['gname'],
                'uniacid' => $refund_order['uniacid']);
            $refund_check = pdo_fetch('select id from ' . tablename('tg_refund_record') . " where refund_id='{$result['refund_id']}'");
            if (empty($refund_check)) {
                pdo_insert('tg_refund_record', $data);
            }
            pdo_update('account_wechats', array('refund_type' => 0, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));
            return 'success';
        } else {
            $logdata = array(
                'orderno' => $orderno,
                'log' => json_encode($result),
                'from' => $refund_order['uniacid'] . $result['err_code_des']
            );
            pdo_insert('tg_log', $logdata);
            if ($result['err_code'] != 'SYSTEMERROR') {
                pdo_update('account_wechats', array('refund_type' => 1, 'last_refund_time' => TIMESTAMP), array('uniacid' => $account_info['uniacid']));

            }
            return 'fail';
        }

    }
}

//$input - stuff to decrypt
//$key - the secret key to use

function do_mdecrypt($input, $key)
{
    $input = str_replace("n", "", $input);
    $input = str_replace("t", "", $input);
    $input = str_replace("r", "", $input);
    $input = trim(chop(base64_decode($input)));
    $td = mcrypt_module_open('tripledes', '', 'ecb', '');
    $key = substr(md5($key), 0, 24);
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    mcrypt_generic_init($td, $key, $iv);
    $decrypted_data = mdecrypt_generic($td, $input);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return trim(chop($decrypted_data));

}

function check_levelrefund()
{
    global $_W;
    //$sql="select * from ". tablename('tg_order_level_refund') ." where  status=1  limit 2";
    $sql = "SELECT a.orderno AS orderno,a.id AS id,a.refundprice AS refundprice FROM " . tablename('tg_order_level_refund') . " AS a INNER JOIN " . tablename('tg_order') . " AS b ON a.orderno=b.orderno WHERE  a.status=1 AND b.uniacid='" . $_W['uniacid'] . "' LIMIT 1 ";
    $orders = pdo_fetchall($sql);
    foreach ($orders as $key => $value) {
        $order = pdo_fetch("select id,openid,uniacid,selltype from " . tablename('tg_order') . " where orderno='{$value['orderno']}'");

        $refund = pdo_fetch("select * from " . tablename('tg_order_level_refund') . " where orderno = '{$value['orderno']}' and status = 2 ");
        if (empty($refund)) {
            $r = partrefundlevel($value['orderno'], $value['refundprice']);
            if ($r == 'success') {

                $goods = pdo_fetch("select gname from" . tablename('tg_goods') . "where id='{$order['g_id']}'");
                /*退款成功消息提醒*/
                $url = app_url('order/order/detail', array('id' => $order['id']));
                refund_success($value['orderno'], $goods['gname'], $order['openid'], $value['refundprice'], time(), $url);

            }
        } elseif ($order['selltype'] == 4) {
            pdo_update('tg_order_level_refund', array('status' => -1), array('id' => $value['id']));
        }
    }

}

function check_refund()
{
    global $_W;
    $sql = "SELECT * FROM " . tablename('tg_order') . " WHERE mobile<>'虚拟' AND status=:status AND transid<>''   AND uniacid=:uniacid LIMIT 3";
    $params[':status'] = 10;
    $params[':uniacid'] = $_W['uniacid'];
    $orders = pdo_fetchall($sql, $params);

    if (empty($orders)) {
        pdo_update('account_wechats', array('refund_type' => 1, 'last_refund_time' => TIMESTAMP), array('uniacid' => $_W['uniacid']));
    }
    foreach ($orders as $key => $value) {
        if ($value['pay_type'] == 9) {
            balance_payment_refund($value['transid'], 1, $value, '组团失败');
        } else {
            $r = refund($value['orderno'], 1);
            if ($r == 'success') {
                $goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$value['g_id']}'");
                /*退款成功消息提醒*/
                $url = app_url('order/order/detail', array('id' => $value['id']));
                refund_success($value['orderno'], $goods['gname'], $value['openid'], $value['price'], time(), $url);

            }
        }
    }

}

function check_membercash()
{
    //自动签收
    global $_W;

    wl_load()->model('setting');
    $set = setting_get_list();
    $settings = array();
    foreach ($set as $key => $val) {
        $settingarray = $val['value'];
        foreach ($settingarray as $ke => $v) {
            $settings[$ke] = $v;
        }
    }

    $gettime = $settings['gettime'];//自动签收时间
    if (empty($gettime)) {
        $gettime = 5;
    }

    $nowtime = TIMESTAMP - $gettime * 24 * 3600;

    //自动签收
    //pdo_update('tg_order',array('gettime'=>TIMESTAMP,'status'=>3),array('uniacid'=>$_W['uniacid'],'status'=>2,));
    $list = pdo_fetchall("SELECT * FROM " . tablename("tg_order") . " WHERE uniacid = " . $_W['uniacid'] . " AND supply_goodsid=0 AND STATUS = 2 AND delivery_time > 0 AND delivery_time < " . $nowtime . " LIMIT 5 ");
    foreach ($list as $value) {
        $openid = $value["openid"];
        if ($value["selltype"] == 2) {
            if ($value["is_tuan"]) {
                if ($value["tuan_first"] && in_array($value["status"], [1, 2, 8])) {
                    $dataxc["status"] = 3;
                    $dataxc["over_time"] = TIMESTAMP;
                    $tuan_list = pdo_fetchall("select *from " . tablename('tg_order') . " where uniacid = '{$value['uniacid']}' and tuan_id = '{$value['tuan_id']}' and status in (1,8)  AND supply_goodsid=0");
                    foreach ($tuan_list as $it) {
                        pdo_update("tg_order", $dataxc, array("id" => $it["id"]));

                    }
                }
            }
        } else {
//          pdo_query('update cm_tg_order set gettime='.TIMESTAMP.',status=3 where uniacid= '.$_W['uniacid'].' and status=2 and delivery_time<'.$nowtime.' limit 1');
            $recevid_order = pdo_fetchall("select id,delivery_time,openid,ptime,uniacid from " . tablename('tg_order') . " where uniacid = {$_W['uniacid']} AND supply_goodsid=0 and status = 2 and delivery_time > 0 and delivery_time < {$nowtime} limit 3");
            foreach ($recevid_order as $key => $vue) {
                if ($vue["g_id"] == 0) {
                    $is_sends = pdo_fetchall("SELECT * FROM " . tablename("tg_collect") . " WHERE orderno = :id", array(":id" => $vue['orderno']));
                    for ($k = 0; $k < count($is_sends); $k++) {
                        $is_send[$k] = pdo_get("tg_goods", array("id" => $is_sends[$k]["sid"]));
                    }
                } else {
                    $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id", array(":id" => $value["g_id"]));
                }
                if ($vue['supply_goodsid'] > 0) {
                    pdo_update('tg_supply_collect', array('supply_status' => 2, 'receive_time' => TIMESTAMP), array('orderid' => $vue['id']));
                }
                for ($i = 0; $i < count($is_send); $i++) {
//                $is_send = pdo_fetch("select * from ".tablename("tg_goods")." where 	id = :id",array(":id"=>$is_send[$i]["g_id"]));
                    if ($is_send[$i]["is_sendcoupon"] == 1) {
                        $coupon_id = $is_send[$i]["coupon_id"];
                        //查询优惠券详情
                        $coupon_info = pdo_get("tg_coupon_template", array("id" => $coupon_id));
                        $data_xc["name"] = $coupon_info["name"];
                        $data_xc["coupon_template_id"] = $coupon_info["id"];
                        $data_xc["openid"] = $vue["openid"];
                        $data_xc["description"] = $coupon_info["description"];
                        $data_xc["start_time"] = time();
                        $data_xc["end_time"] = time() + 1 * 24 * 60 * 60;
                        $data_xc["at_least"] = $coupon_info["at_least"];
                        $data_xc["uniacid"] = $coupon_info["uniacid"];
                        $data_xc["cash"] = $coupon_info["value"];
                        $data_xc["createtime"] = time();
                        pdo_insert("tg_coupon", $data_xc);
                    }
                }
                $postData['topcolor'] = "blue";
                $postData["data"]["first"] = "您的订单已经签收";
                $postData["data"]["orderno"] = $vue["id"];
                //查询商品名称
                $postData["data"]["keyword2"] = "您在" . date("Y-m-d H:i:s", $vue["ptime"]) . " 支付成功的订单,订单号为 " . $vue["id"] . " 已经签收了!";
//                $postData["data"]["keyword2"] = "您的订单已经签收,订单号为".$value["orderno"];
                $postData["data"]["keyword1"] = "点击查看详情";
                $postData["data"]["remark"] = "点击查看详情";
                $postData_res = serialize($postData);
                $xc["content"] = $postData_res;
                $xc["uniacid"] = $vue['uniacid'];
                $xc["openid"] = $vue['openid'];
                $xc["mess_tpl"] = "result_type";
                pdo_insert("tg_message_log", $xc);
                pdo_update('tg_order', array('gettime' => TIMESTAMP, 'over_time' => TIMESTAMP, 'status' => 3), array('id' => $vue['id']));
            }
        }

        internal_log('check_membercash',
            array(
                'ip' => CLIENT_IP,
                'op' => "自动签收",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'uniacid' => $_W['uniacid'],
                'openid' => $value['openid'],
                'orderno' => $value['orderno'],
                'gettime' => $gettime,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
    }

//	pdo_query('update cm_tg_order set gettime='.TIMESTAMP.',status=3 where uniacid= '.$_W['uniacid'].' and status=2 and delivery_time<'.$nowtime.' limit 1');
    /*
    $recevid_order=pdo_fetchall("select id,delivery_time from " .tablename('tg_order')." where uniacid = {$_W['uniacid']} and status = 2 and delivery_time < {$nowtime} limit 3");

    foreach($recevid_order as $key =>$value){
        pdo_update('tg_order',array('gettime'=>TIMESTAMP,'status'=>3),array('id'=>$value['id']));
    }
    */
    //end
}
//极限单品自动签收
function check_jpmembercash()
{
    //自动签收
    global $_W;

    wl_load()->model('setting');
    $set = setting_get_list();
    $settings = array();
    foreach ($set as $key => $val) {
        $settingarray = $val['value'];
        foreach ($settingarray as $ke => $v) {
            $settings[$ke] = $v;
        }
    }

    $gettime = $settings['jpgettime'];//自动签收时间
    if (empty($gettime)) {
        $gettime = 5;
    }

    $nowtime = TIMESTAMP - $gettime * 24 * 3600;

    //自动签收
    //pdo_update('tg_order',array('gettime'=>TIMESTAMP,'status'=>3),array('uniacid'=>$_W['uniacid'],'status'=>2,));
    $list = pdo_fetchall("SELECT * FROM " . tablename("tg_order") . " WHERE uniacid = " . $_W['uniacid'] . " AND supply_goodsid>0 AND STATUS = 2 AND delivery_time > 0 AND delivery_time < " . $nowtime . " LIMIT 5 ");
    foreach ($list as $value) {
        $openid = $value["openid"];
        if ($value["selltype"] == 2) {
            if ($value["is_tuan"]) {
                if ($value["tuan_first"] && in_array($value["status"], [1, 2, 8])) {
                    $dataxc["status"] = 3;
                    $dataxc["over_time"] = TIMESTAMP;
                    $tuan_list = pdo_fetchall("select *from " . tablename('tg_order') . " where uniacid = '{$value['uniacid']}' and tuan_id = '{$value['tuan_id']}' and status in (1,8) AND supply_goodsid>0");
                    foreach ($tuan_list as $it) {
                        pdo_update("tg_order", $dataxc, array("id" => $it["id"]));

                    }
                }
            }
        } else {
//          pdo_query('update cm_tg_order set gettime='.TIMESTAMP.',status=3 where uniacid= '.$_W['uniacid'].' and status=2 and delivery_time<'.$nowtime.' limit 1');
            $recevid_order = pdo_fetchall("select id,delivery_time,openid,ptime,uniacid from " . tablename('tg_order') . " where uniacid = {$_W['uniacid']}  AND supply_goodsid>0 and status = 2 and delivery_time > 0 and delivery_time < {$nowtime} limit 3");
            foreach ($recevid_order as $key => $vue) {
                if ($vue["g_id"] == 0) {
                    $is_sends = pdo_fetchall("SELECT * FROM " . tablename("tg_collect") . " WHERE orderno = :id", array(":id" => $vue['orderno']));
                    for ($k = 0; $k < count($is_sends); $k++) {
                        $is_send[$k] = pdo_get("tg_goods", array("id" => $is_sends[$k]["sid"]));
                    }
                } else {
                    $is_send = pdo_fetchall("SELECT * FROM " . tablename("tg_goods") . " WHERE id = :id", array(":id" => $value["g_id"]));
                }
                if ($vue['supply_goodsid'] > 0) {
                    pdo_update('tg_supply_collect', array('supply_status' => 2, 'receive_time' => TIMESTAMP), array('orderid' => $vue['id']));
                }
                for ($i = 0; $i < count($is_send); $i++) {
//                $is_send = pdo_fetch("select * from ".tablename("tg_goods")." where 	id = :id",array(":id"=>$is_send[$i]["g_id"]));
                    if ($is_send[$i]["is_sendcoupon"] == 1) {
                        $coupon_id = $is_send[$i]["coupon_id"];
                        //查询优惠券详情
                        $coupon_info = pdo_get("tg_coupon_template", array("id" => $coupon_id));
                        $data_xc["name"] = $coupon_info["name"];
                        $data_xc["coupon_template_id"] = $coupon_info["id"];
                        $data_xc["openid"] = $vue["openid"];
                        $data_xc["description"] = $coupon_info["description"];
                        $data_xc["start_time"] = time();
                        $data_xc["end_time"] = time() + 1 * 24 * 60 * 60;
                        $data_xc["at_least"] = $coupon_info["at_least"];
                        $data_xc["uniacid"] = $coupon_info["uniacid"];
                        $data_xc["cash"] = $coupon_info["value"];
                        $data_xc["createtime"] = time();
                        pdo_insert("tg_coupon", $data_xc);
                    }
                }
                $postData['topcolor'] = "blue";
                $postData["data"]["first"] = "您的订单已经签收";
                $postData["data"]["orderno"] = $vue["id"];
                //查询商品名称
                $postData["data"]["keyword2"] = "您在" . date("Y-m-d H:i:s", $vue["ptime"]) . " 支付成功的订单,订单号为 " . $vue["id"] . " 已经签收了!";
//                $postData["data"]["keyword2"] = "您的订单已经签收,订单号为".$value["orderno"];
                $postData["data"]["keyword1"] = "点击查看详情";
                $postData["data"]["remark"] = "点击查看详情";
                $postData_res = serialize($postData);
                $xc["content"] = $postData_res;
                $xc["uniacid"] = $vue['uniacid'];
                $xc["openid"] = $vue['openid'];
                $xc["mess_tpl"] = "result_type";
                pdo_insert("tg_message_log", $xc);
                pdo_update('tg_order', array('gettime' => TIMESTAMP, 'over_time' => TIMESTAMP, 'status' => 3), array('id' => $vue['id']));
            }
        }

        internal_log('check_jpmembercash',
            array(
                'ip' => CLIENT_IP,
                'op' => "极限单品自动签收",
                'filepath' => __FILE__,
                'line' => __LINE__,
                'uniacid' => $_W['uniacid'],
                'openid' => $value['openid'],
                'orderno' => $value['orderno'],
                'gettime' => $gettime,
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ));
    }

//	pdo_query('update cm_tg_order set gettime='.TIMESTAMP.',status=3 where uniacid= '.$_W['uniacid'].' and status=2 and delivery_time<'.$nowtime.' limit 1');
    /*
    $recevid_order=pdo_fetchall("select id,delivery_time from " .tablename('tg_order')." where uniacid = {$_W['uniacid']} and status = 2 and delivery_time < {$nowtime} limit 3");

    foreach($recevid_order as $key =>$value){
        pdo_update('tg_order',array('gettime'=>TIMESTAMP,'status'=>3),array('id'=>$value['id']));
    }
    */
    //end
}
//多商户订单自动结算
function check_settle($uniacid)
{
    $time = TIMESTAMP;
    $acc = pdo_fetch("select merchant_account_time from " . tablename('account_wechats') . " where uniacid = " . $uniacid);
    $create = $time - intval($acc['merchant_account_time']) * 24 * 60 * 60;
    $order = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE uniacid = {$uniacid} and status = 3 and m_status = 0 and over_time < " . $create . " limit 0,10 ");
    unset($acc);
    foreach ($order as &$value) {
        $data = array();
        $merchant = pdo_fetch("SELECT percent FROM " . tablename('tg_merchant') . " WHERE uniacid = '{$uniacid}' and id = '{$value['merchantid']}' ");
        if ($merchant) {
            $data['merchantid'] = $value['merchantid'];
            $data['money'] = $value['price'];
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $uniacid;
            $data['orderno'] = $value['orderno'];
            $data['percent'] = $merchant['percent'];
            $data['status'] = 0;
            $data['point'] = round(($value['price'] * $merchant['percent'] * 0.01), 2);
            $data['get_money'] = $data['money'] - $data['point'];
            $data['gname'] = $value['goodsname'];
            if (empty($data['gname'])) {
                $goods = pdo_fetch("SELECT goodsname FROM " . tablename('tg_collect') . " WHERE uniacid = " . $uniacid . " AND orderno = " . $value['orderno']);
                $data['gname'] = $goods['goodsname'];
            }
            pdo_update('tg_order', array('m_status' => 1), array('orderno' => $value['orderno']));
            pdo_insert('tg_merchant_record', $data);
        }
    }
}

function checkpaygroup($groupnumber)
{
    global $_GPC, $_W;
    $content = "and uniacid='{$_W['uniacid']}' and tuan_id='{$groupnumber}' and status in (0,9)";
    $orders = pdo_fetchall("select orderno,status,id,ptime,pay_price,tuan_id from " . tablename('tg_order') . " where uniacid='{$_W['uniacid']}' and tuan_id = '{$groupnumber}' and checkpay = '9' and status in (0,9) order by createtime desc ");
    foreach ($orders as $key => $value) {
        $log = pdo_fetch('SELECT tag,type,status FROM ' . tablename('core_paylog') . "WHERE tid = '{$value['orderno']}' AND uniacid = '{$_W['uniacid']}'");
        $tag = iunserializer($log['tag']);
        $params['type'] = $log['type'];
        $params['tag'] = $tag;
        $paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 2, 'delivery' => 3);
        $data['pay_type'] = $paytype[$params['type']];
        $data['price'] = $value['pay_price'];
        if ($log['status'] == 1 && !empty($params['tag']['transaction_id'])) {
            if ($value['status'] == 0) {
                $data['status'] = 1;
            }
            if (empty($value['ptime'])) {
                $data['status'] = 1;
                $data['ptime'] = TIMESTAMP;
            }
            if ($value['tuan_id'] == 0) {
                $data['status'] = 8;
                $data['ptime'] = TIMESTAMP;
            }
            $data['checkpay'] = 0;
            $data['transid'] = $params['tag']['transaction_id'];

            pdo_update('tg_order', $data, array('id' => $value['id']));
            if ($value['tuan_id'] > 0) {
                $allorders = pdo_fetchall("select id from" . tablename('tg_order') . "where status in (1,2,3,8) and tuan_id=:tuan_id", array(':tuan_id' => $value['tuan_id']));
                $group = pdo_fetch("select * from " . tableName('tg_group') . " where groupnumber=:groupnumber", array(':groupnumber' => $value['tuan_id']));

                if (count($allorders) >= $group['neednum']) {
                    pdo_update('tg_group', array('groupstatus' => 2, 'lacknum' => 0,'successtime' => TIMESTAMP), array('groupnumber' => $value['tuan_id']));
                    pdo_update('tg_order', array('status' => 8), array('tuan_id' => $value['tuan_id'], 'status' => 1));
                    /*团成功通知*/
                    $url = app_url('order/group', array('tuan_id' => $value['tuan_id']));
                    if ($value['dispatchtype'] == 3) {
                        $url = app_url('order/order/detail', array('id' => $value['id'], 'b' => 1));
                    }
                    group_success($value['tuan_id'], $url);
                }
            }

        }
    }
}

function checkpay($openid)
{
    global $_GPC, $_W;
    $content = "and uniacid='{$_W['uniacid']}' and openid='{$openid}' and status in (0,9)";
    $orders = pdo_fetchall("select orderno,status,id,ptime,pay_price,tuan_id,pay_type from " . tablename('tg_order') . " where uniacid = '{$_W['uniacid']}' and openid = '{$openid}' and checkpay = '9' and status in (0,9) order by createtime desc limit 3");
    foreach ($orders as $key => $value) {
        if ($value['pay_type'] == 9) {
            break;
        }
        $log = pdo_fetch('SELECT tag,type,status FROM ' . tablename('core_paylog') . " WHERE tid = '{$value['orderno']} ' AND uniacid = '{$_W['uniacid']}'");
        $tag = iunserializer($log['tag']);
        $params['type'] = $log['type'];
        $params['tag'] = $tag;
        $paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 2, 'delivery' => 3);
        $data['pay_type'] = $paytype[$params['type']];
        $data['price'] = $value['pay_price'];
        if ($log['status'] == 1 && !empty($params['tag']['transaction_id'])) {
            if ($value['status'] == 0) {
                $data['status'] = 1;
            }
            if (empty($value['ptime'])) {
                $data['status'] = 1;
                $data['ptime'] = TIMESTAMP;
            }
            if ($value['tuan_id'] == 0) {
                $data['status'] = 8;
                $data['ptime'] = TIMESTAMP;
            }
            $data['checkpay'] = 0;
            $data['transid'] = $params['tag']['transaction_id'];

            pdo_update('tg_order', $data, array('id' => $value['id']));
            if ($value['tuan_id'] > 0) {
                $allorders = pdo_fetchall("select id from" . tablename('tg_order') . "where status in (1,2,3,8) and tuan_id=:tuan_id", array(':tuan_id' => $value['tuan_id']));
                $group = pdo_fetch("select * from " . tableName('tg_group') . " where groupnumber=:groupnumber", array(':groupnumber' => $value['tuan_id']));

                if (count($allorders) >= $group['neednum']) {
                    pdo_update('tg_group', array('groupstatus' => 2, 'lacknum' => 0, 'successtime' => TIMESTAMP), array('groupnumber' => $value['tuan_id']));
                    pdo_update('tg_order', array('status' => 8), array('tuan_id' => $value['tuan_id'], 'status' => 1));
                }
            }

        }
    }

}

function check_member_pay(){

    $member_pay = pdo_fetchall('SELECT plid,tag,type,status,tid,fee,card_id,card_fee,openid,uniacid FROM ' . tablename('core_paylog') . " WHERE openid <> '' and tag <> '' AND createtime > 0 AND module = 'lexiangpingou' AND is_usecard = 0 LIMIT 3 ");
    if ($member_pay) {
        foreach ($member_pay as $item) {
            $record = pdo_get('tg_member_recharge_record', array('uniacid' => $item['uniacid'], 'orderno' => $item['tid']));
            if (!$record && substr($item['tid'], 0, 2) == 'CZ') {
                $card = explode("+", $item['card_id']);
                $card_id = $card[0];
                $card_fee = $card[1];
                pdo_update('core_paylog', array('is_usecard' => 2), array('plid' => $item['plid']));
                pdo_insert('tg_member_recharge_record', array(
                    'uniacid' => $item['uniacid'],
                    'openid' => $item['openid'],
                    'orderno' => $item['tid'],
                    'recharge_id' => intval($card_id),
                    'member_amount' => floatval($card_fee),
                    'member_selling' => floatval($item['fee']),
                    'createtime' => TIMESTAMP
                ));
                $member = pdo_get('tg_member', array('uniacid' => $item['uniacid'], 'from_user' => $item['openid']));
                pdo_update('tg_member', array(
                    'member_amount' => floatval($member['member_amount']) + floatval($card_fee),
                    'member_balance' => floatval($member['member_balance']) + floatval($card_fee)
                ), array('id' => $member['id']));
            } else {
                pdo_update('core_paylog', array('is_usecard' => 1), array('plid' => $item['plid']));
            }
        }
    }
}

function checkpaytransid()
{
    global $_GPC, $_W;
    $content = "    mobile<>'虚拟' and transid=''   and `ptime` >1497283200 ";
    $orders = pdo_fetchall("select orderno,status,id,ptime,pay_price,tuan_id,uniacid from" . tablename('tg_order') . " where  $content  order by createtime desc limit 3");
    foreach ($orders as $key => $value) {
        $log = pdo_fetch('SELECT tag,type,status FROM ' . tablename('core_paylog') . "WHERE tid = '{$value['orderno']}' and uniacid={$value['uniacid']} ");
        $tag = iunserializer($log['tag']);
        $params['type'] = $log['type'];
        $params['tag'] = $tag;
        $paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 2, 'delivery' => 3);
        $data['pay_type'] = 2;
        $data['price'] = $value['pay_price'];
        if ($log['status'] == 1 && !empty($params['tag']['transaction_id'])) {
            if (empty($value['ptime'])) {

                $data['ptime'] = TIMESTAMP;
            }
            $data['transid'] = $params['tag']['transaction_id'];

            pdo_update('tg_order', $data, array('id' => $value['id']));


        }
    }
}

/*
 * 判断$str字符串中是否包含字符串$needle
 */
function checkstr($str, $needle)
{
    $tmparray = explode($needle, $str);
    if (count($tmparray) > 1 || empty($str)) {
        return true;
    } else {
        return false;
    }
}

function checkpaytransid_orderno($orderno)
{
    global $_GPC, $_W;

    $log = pdo_fetch('SELECT tag,type,status,fee FROM ' . tablename('core_paylog') . "WHERE tid = '{$orderno}' AND uniacid = '{$_W['uniacid']}'");
    $tag = iunserializer($log['tag']);
    $params['type'] = $log['type'];
    $params['tag'] = $tag;
    $data['pay_type'] = 2;
    $data['price'] = $log['fee'];
    if ($log['status'] == 1 && !empty($params['tag']['transaction_id'])) {
        if (empty($value['ptime'])) {

            $data['ptime'] = TIMESTAMP;
        }
        $data['transid'] = $params['tag']['transaction_id'];

        pdo_update('tg_order', $data, array('id' => $value['id']));


    }

}

/*openid=>uid*/
function mc_openidTouid($openid = '')
{
    global $_W;
    if (is_numeric($openid)) {
        return $openid;
    }
    if (is_string($openid)) {
        $sql = 'SELECT uid FROM ' . tablename('mc_mapping_fans') . ' WHERE `uniacid`=:uniacid AND `openid`=:openid';
        $pars = array();
        $pars[':uniacid'] = $_W['uniacid'];
        $pars[':openid'] = $openid;
        $uid = pdo_fetchcolumn($sql, $pars);
        return $uid;
    }
    if (is_array($openid)) {
        $uids = array();
        foreach ($openid as $k => $v) {
            if (is_numeric($v)) {
                $uids[] = $v;
            } elseif (is_string($v)) {
                $fans[] = $v;
            }
        }
        if (!empty($fans)) {
            $sql = 'SELECT uid, openid FROM ' . tablename('mc_mapping_fans') . " WHERE `uniacid`=:uniacid AND `openid` IN ('" . implode("','", $fans) . "')";
            $pars = array(':uniacid' => $_W['uniacid']);
            $fans = pdo_fetchall($sql, $pars, 'uid');
            $fans = array_keys($fans);
            $uids = array_merge((array)$uids, $fans);
        }
        return $uids;
    }
    return false;
}

/*模板消息*/
function sendTplNotice($touser, $template_id, $postdata, $url = '', $account = null)
{
    global $_W;
    load()->model('account');
    if (!$account) {
        if (!empty($_W['acid'])) {
            $account = WeAccount:: create($_W['acid']);
        } else {
            $acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE `uniacid`=:uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
            $account = WeAccount:: create($acid);
        }
    }
    /*
    $funclist=pdo_fetch("select * from ".tablename('tg_function_detail') ." where functionid=8166 and uniacid={$_W['uniacid']}");
    if (!$account||$funclist['endtime']<time()) {
        return;
    }
    */
    return $account->sendTplNotice($touser, $template_id, $postdata, $url);
}

/*自动成团*/
function get_head_img($url, $num)
{
    $imgs_array = array();
    $random_array = array();
    $files = array();
    if ($handle = opendir($url)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (substr($file, -3) == 'gif' || substr($file, -3) == 'jpg') $files[count($files)] = $file;
            }
        }
    }
    closedir($handle);
    for ($i = 0; $i < $num; $i++) {
        $random = rand(0, count($files) - 1);
        while (in_array($random, $random_array)) {
            $random = rand(0, count($files) - 1);
        }
        $random_array[$i] = $random;
        $imgs_url = $url . "/" . $files[$random];
        $imgs_array[$i] = $imgs_url;

    }
    return $imgs_array;
}

function get_nickname($filename, $num)
{
    $nickname_array = array();
    $random_array = array();
    $file_new = fopen($filename, "r");
    $file_read = fread($file_new, filesize($filename));
    fclose($file_new);
    $arr_new = unserialize($file_read);
    for ($i = 0; $i < $num; $i++) {
        $random = rand(0, count($arr_new) - 1);
        while (in_array($random, $random_array)) {
            $random = rand(0, count($arr_new) - 1);
        }
        $random_array[$i] = $random;
        $nickname = $arr_new[$random];
        $nickname_array[$i] = $nickname;

    }
    return $nickname_array;
}

function get_randtime($inittime = 0, $now = 0, $num)
{
    $randtime_array = array();
    $random_array = array();
    $max = $now - $inittime;
    for ($i = 0; $i < $num; $i++) {
        $random = intval($max / $num);
        $max = $max - $random;
        $randtime_array[$i] = $inittime + $random;

    }
    return $randtime_array;
}

function permissions($do, $ac, $op, $v1, $v2, $v3, $n1, $n2, $n3)
{
    global $_W;
    $role_do = pdo_fetch_one('tg_user_node', array($do => $v1));
    $role_ac = pdo_fetch_one('tg_user_node', array($do => $v1, $ac => $v2));
    $role_op = pdo_fetch_one('tg_user_node', array($do => $v1, $ac => $v2, $op => $v3));
    if (empty($role_do)) {
        $user_do = array('do' => $v1, 'ac' => '', 'op' => '', 'do_id' => 0, 'ac_id' => 0, 'level' => 1, 'status' => 2, 'uniacid' => $_W['uniacid'], 'name' => $n1);
        pdo_insert('tg_user_node', $user_do);
        $do_id = pdo_insertid();
    } else {
        $do_id = $role_do['id'];
    }
    if (empty($role_ac)) {
        $user_ac = array('do' => $v1, 'ac' => $v2, 'op' => '', 'do_id' => $do_id, 'ac_id' => 0, 'level' => 2, 'status' => 2, 'uniacid' => $_W['uniacid'], 'name' => $n2);
        pdo_insert('tg_user_node', $user_ac);
        $ac_id = pdo_insertid();
    } else {
        $ac_id = $role_ac['id'];
    }
    if (empty($role_op)) {
        $user_op = array('do' => $v1, 'ac' => $v2, 'op' => $v3, 'do_id' => $do_id, 'ac_id' => $ac_id, 'level' => 3, 'status' => 2, 'uniacid' => $_W['uniacid'], 'name' => $n3);
        pdo_insert('tg_user_node', $user_op);
    }
}

function allow($do, $ac, $op, $merchantid)
{
    global $_W;
    $role_op = pdo_fetch_one('tg_user_node', array('do' => $do, 'ac' => $ac, 'op' => $op));
    $role = pdo_fetch("select * from" . tablename('tg_user_role') . "where uniacid={$_W['uniacid']} and merchantid={$merchantid}");
    $nodes = unserialize($role['nodes']);
    if (!empty($role_op['id']) && !empty($nodes)) {
        if (in_array($role_op['id'], $nodes)) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }

}

function merchant()
{
    global $_W;
    $uid = $_W['uid'];
    $merchant = pdo_fetch("select * from" . tablename('tg_merchant') . "where uid=:uid ", array(':uid' => $uid));
    if (empty($merchant)) {
        message("未分配帐号，无权查看！您可以去拼团主程序添加商家并分配帐号，用分配的帐号登录多商家后台！");
        exit;
    } else {
        return $merchant['id'];
    }
}

function oplog($user, $describe, $view_url, $data)
{
    global $_W;
    $data = array(
        'user' => $user,
        'uniacid' => $_W['uniacid'],
        'describe' => $describe,
        'view_url' => $view_url,
        'data' => $data,
        'ip' => CLIENT_IP,
        'createtime' => TIMESTAMP
    );
    pdo_insert("tg_oplog", $data);
    return pdo_insertid();
}

/*订单支付升级*/
function autogrades()
{
    global $_W, $_GPC;
    //获取当前会员的openid
    $openid = $_W["openid"];
    //获取当前会员的等级
    $user_info = pdo_fetch("select * from " .
        tablename("tg_member") .
        " where from_user = :openid and uniacid = :uniacid",
        array(":openid" => $openid, ":uniacid" => $_W["uniacid"])
    );
    //查出来会员的总订单金额是多少
    $order_money = pdo_fetch("select sum(price) from " .
        tablename("tg_order") .
        " where openid = :openid and uniacid = :uniacid and status = 3",
        array(":openid" => $openid, ":uniacid" => $_W["uniacid"])
    );
    $order_price = $order_money["price"];
    //计算出总金额之后然后查看赋予会员的等级是多少
    $auto_level = pdo_fetch("selcet * from " .
        tablename("tg_member_leave") .
        " where :order_price >= money and uniacid = :uniacid order by money desc",
        array(":uniacid" => $_W["uniacid"], ":order_price" => $order_price));
    if ($auto_level["id"] == intval($user_info["level"])) {
        //不做变化
    } elseif ($auto_level["id"] < intval($user_info["level"])) {
        //不做变化
    } else {
        //升级会员等级
        $data["level"] = $auto_level["id"];
        $res = pdo_update("tg_member",
            $data,
            array("from_user" => $openid, "uniacid" => $_W["uniacid"])
        );
    }

}

//微擎内部可用日志接口
function internal_log($path, $args, $line, $file, $op, $url, $uniacid)
{

    global $_W;

    if (!$uniacid) {
        $uniacid = $_W['uniacid'];
    }
    //TODO 创建日志
    $path = IA_ROOT . "/addons/lexiangpingou/data/log/" . $uniacid . "/" . $path . "/";
    //首先判断目录存在否
    if (!is_dir($path)) {
        //第3个参数“true”意思是能创建多级目录，iconv防止中文目录乱码
        $res = mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
    }
    $date = date('Y-m-d', TIMESTAMP);
    file_put_contents($path . $date . ".log", var_export(
            array(
                'ip' => CLIENT_IP,
                'filepath' => $file,
                'line' => $line,
                'url' => $url, //文件路径
                'op' => $op,  //方法名
                'uniacid' => $uniacid,
                'args' => $args, //传入值
                'time' => date('Y-m-d H:i:s', TIMESTAMP)
            ),
            true) . PHP_EOL, FILE_APPEND);

}

//会员余额支付
function balance_payment($uniacid, $params)
{
    $member = pdo_fetch("select * from " . tablename('tg_member') . " where uniacid = {$uniacid} and openid = '{$params['user']}' ");
    $wOpt = array();
    if ($member) {
        if ($member['member_balance'] < $params['fee']) {
            $message = "非常抱歉，余额不足！";
            $wOpt['status'] = 0;
            $wOpt['message'] = $message;
        } else {
            if ($params['type'] == 1) {
                $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid';
                $pars = array();
                $pars[':uniacid'] = $uniacid;
                $pars[':module'] = $params['module'];
                $pars[':tid'] = $params['tid'];
                $log = pdo_fetch($sql, $pars);
            }

            pdo_update('tg_member', array('member_balance' => $member['member_balance'] - $params['fee']), array('uniacid' => $uniacid, 'from_user' => $params['user']));
            $data['openid'] = $params['user'];
            $data['uniacid'] = $uniacid;
            $data['orderno'] = $params['ordersn'];
            $data['price'] = $params['fee'];
            $data['type'] = $params['type'];
            $data['storeid'] = intval($params['storeid']);
            $data['createtime'] = TIMESTAMP;
            $res = pdo_insert('tg_member_billrecord', $data);
            if ($params['type'] == 1) {
                $billerecordid = pdo_insertid();
                $billerecordid = array('billerecord_id' => $billerecordid, 'transaction_id' => $billerecordid);
                $billerecordid = serialize($billerecordid);
                $res = pdo_update('core_paylog', array('tag' => $billerecordid, 'status' => 1), array('plid' => $log['plid']));
                if ($res) {
                    pdo_update('tg_order', array('pay_type' => 9), array('orderno' => $params['tid']));
                }
            }
            $message = '支付成功！';
            $wOpt['status'] = $res;
            $wOpt['message'] = $message;
        }
    } else {
        $message = '会员信息错误！';
        $wOpt['status'] = 0;
        $wOpt['message'] = $message;
    }
    return $wOpt;
}

//会员退款
function balance_payment_refund($id, $type, $order, $reason = '', $price = 0)
{

    global $_W, $_GPC;
    $bill = pdo_get('tg_member_billrecord', array('id' => $id));
    $member = pdo_get('tg_member', array('uniacid' => $bill['uniacid'], 'from_user' => $bill['openid']));
    if ($type == 1) {
        $price = floatval($bill['price']) - floatval($bill['refund']);
    } else {
        $price = floatval($price);
    }
    if ($bill['status'] == 1) {
        return array('status' => -1, 'message' => '非常抱歉！此订单已全额退款');
    } elseif (floatval($bill['price']) - floatval($bill['refund']) <= 0) {
        return array('status' => -1, 'message' => '非常抱歉！此订单已全额退款');
    } elseif (floatval($bill['price']) - floatval($bill['refund']) < $price) {
        return array('status' => -1, 'message' => '非常抱歉！此订单可退款金额不足');
    }
    if ($bill['type'] == 2) {
        $goodsname = '线下交易';
        $refundername = $member['name'];
        $refundermobile = $member['addmobile'];
        $uniacid = $member['uniacid'];
        $orderno = $bill['orderno'];
    } else {
        $goodsname = $order['goodsname'];
        $goodsid = $order['g_id'];
        if ($order['g_id'] == 0) {
            $coll = pdo_get('tg_collect', array('orderno' => $order['orderno']));
            $goodsname = $coll['goodsname'];
            $goodsid = $coll['sid'];
        }
        $refundername = $order['cname'] ? $order['cname'] : $order['addname'];
        if (!$refundername) {
            $refundername = $member['name'];
        }
        $refundermobile = $order['tel'] ? $order['tel'] : $order['moblie'];
        if (!$refundermobile) {
            $refundermobile = $member['addmobile'];
        }
        $uniacid = $order['uniacid'];
        $orderid = $order['id'];
        $merchantid = $order['merchantid'];
        $orderno = $order['orderno'];
        if ($type == 1) {
            pdo_update('tg_order', array('status' => 7), array('id' => $order['id']));
        } elseif (floatval($bill['price']) - floatval($bill['refund']) - $price == 0) {
            pdo_update('tg_order', array('status' => 7), array('id' => $order['id']));
            $type = 1;
        } elseif (floatval($bill['price']) - floatval($bill['refund']) - $price > 0) {
            pdo_update('tg_order', array('status' => 6), array('id' => $order['id']));
        }
    }

    pdo_update('tg_member', array('member_balance' => floatval($member['member_balance']) + floatval($price)), array('uniacid' => $bill['uniacid'], 'from_user' => $bill['openid']));
    $res = pdo_update('tg_member_billrecord', array('status' => $type, 'refund' => floatval($bill['refund']) + $price, 'refund_time' => TIMESTAMP), array('id' => $id));
    if ($res) {

        if (intval($_W['uid']) > 0) {
            $refund_id = intval($_W['uid']);
        } elseif ($_W['openid']) {
            $refund_id = $_W['openid'];
        } else {
            $refund_id = -1;
        }
        $data['type'] = 5;
        $data['goodsid'] = $goodsid;
        $data['payfee'] = floatval($bill['price']);
        $data['refundfee'] = $price;
        $data['transid'] = $id;
        $data['refund_id'] = $refund_id;
        $data['refundername'] = $refundername;
        $data['refundermobile'] = $refundermobile;
        $data['goodsname'] = $goodsname;
        $data['createtime'] = TIMESTAMP;
        $data['status'] = 1;
        $data['uniacid'] = $uniacid;
        $data['orderid'] = $orderid;
        $data['merchantid'] = $merchantid;
        $data['orderno'] = $orderno;
        pdo_insert('tg_refund_record', $data);

        $url = app_url('member/member');
        $title = '会员消费退款';
        if ($reason) {
            $reason = '，因' . $reason;
        }
        $content = "尊敬的" . $member["name"] . $reason . "您消费的：￥" . $price . "已退回您的会员账户余额，请注意查收！";
        $remark = "";
        result_type($bill['openid'], $title, $content, $remark, $url);
        $message = '退款成功';
        //库存更新
        $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id', array(':id' => $goodsid));
        if ($order['tuan_id'] > 0 ) {
            //message(1);
            $groups = pdo_fetch("SELECT * FROM " . tableName('tg_group') . " WHERE groupnumber=" . $order['tuan_id']);
            $gnum = $groups['neednum'] - $groups['lacknum'];
            if ($gnum == 1) {
                pdo_update('tg_group', array('groupstatus' => 1), array('groupnumber' => $order['tuan_id']));
            }

            if ($groups['groupstatus'] == 2) {
                pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+{$order['gnum']} where id = '{$order['g_id']}'");
                /*更改规格库存*/
                if (!empty($order['optionname'])) {
                    $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $order['g_id'], ':title' => $order['optionname']));
                    pdo_update('tg_goods_option', array('stock' => $stock['stock'] + $order['gnum']), array('goodsid' => $order['g_id'], 'title' => $order['optionname']));
                }
                //更新门店库存
                if($goodsInfo['has_store_stock'] == 1){

                    if (!empty($order['optionname'])) {
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $order['g_id'], ':storeid' => $order['comadd'],':optionid'=>$order['optionid'],':uniacid'=>$order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $order['gnum']),array('id'=>$store_stock['id']));
                    }else{
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $order['g_id'], ':storeid' => $order['comadd'],':uniacid'=>$order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $order['gnum']),array('id'=>$store_stock['id']));
                    }


                }
            }
            //极限单品减库存加销量
            if ($goodsInfo['supply_goodsid'] > 0) {
                $go = pdo_fetch("SELECT * FROM " . tablename('tg_supply_goods') . " WHERE id = " . $goodsInfo['supply_goodsid']);
                if ($go['stock'] >= $order['gnum']) {
                    pdo_update('tg_supply_goods', array('stock' => $go['stock'] + $order['gnum'], 'salenum' => $go['salenum'] - $order['gnum']), array('id' => $goodsInfo['supply_goodsid']));
                }
                if (!empty($go['optionname'])) {
                    $option = pdo_fetch("SELECT * FROM " . tablename('tg_goods_supply_option') . " WHERE goodsid = :goodsid AND title = :title", array(':goodsid' => $goodsInfo['supply_goodsid'], ':title' => $order['optionname']));
                    pdo_update('tg_goods_supply_option', array('stock' => $option['stock'] + $order['gnum']), array('goodsid' => $goodsInfo['supply_goodsid'], 'title' => $order['optionname']));
                }
            }


        }

        //
        if ($order['tuan_id'] == 0) {
            if($order['g_id'] == 0){
                //单买购物车
                $sende = "select  * from " . tablename('tg_collect') . " where   orderno= '" . $order['orderno'] . "'  ";
                $sendelist = pdo_fetchall($sende);
                foreach ($sendelist as $key => $value) {
                    $goodsInfos = pdo_fetch("select id,gnum,salenum,openid from" . tablename('tg_goods') . " where id=" . $value['sid']);
                    pdo_update('tg_goods', array('gnum' => $goodsInfos['gnum'] + $value['num'], 'salenum' => $goodsInfos['salenum'] - $value['num']), array('id' => $value['sid']));

                    /*更改规格库存*/
                    if (!empty($value['item'])) {
                        $stocks = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $value['sid'], ':title' => $value['item']));

                        pdo_update('tg_goods_option', array('stock' => $stocks['stock'] + $value['num']), array('goodsid' => $value['sid'], 'title' => $value['item']));

                    }
                }
            }else{
                //团商品单买
                pdo_query("update" . tablename('tg_goods') . " set gnum=gnum+{$order['gnum']} where id = '{$order['g_id']}'");
                $goodsInfo = pdo_fetch('SELECT * FROM ' . tablename('tg_goods') . ' WHERE id=:id', array(':id' => $order['g_id']));
                /*更改规格库存*/
                if (!empty($order['optionname'])) {
                    $stock = pdo_fetch("select * from " . tablename('tg_goods_option') . " where goodsid=:goodsid and title=:title", array(':goodsid' => $order['g_id'], ':title' => $order['optionname']));
                    pdo_update('tg_goods_option', array('stock' => $stock['stock'] + $order['gnum']), array('goodsid' => $order['g_id'], 'title' => $order['optionname']));
                }
                //更新门店库存
                if($goodsInfo['has_store_stock'] == 1){

                    if (!empty($order['optionname'])) {
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid and optionid=:optionid and uniacid=:uniacid", array(':goodsid' => $order['g_id'], ':storeid' => $order['comadd'],':optionid'=>$order['optionid'],':uniacid'=>$order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $order['gnum']),array('id'=>$store_stock['id']));
                    }else{
                        $store_stock = pdo_fetch("select * from " . tablename('tg_goods_store_stock') . " where goodsid=:goodsid and storeid=:storeid  and uniacid=:uniacid", array(':goodsid' => $order['g_id'], ':storeid' => $order['comadd'],':uniacid'=>$order['uniacid']));
                        pdo_update('tg_goods_store_stock',array('stock'=>$store_stock['stock'] + $order['gnum']),array('id'=>$store_stock['id']));
                    }
                }
            }
        }



    } else {
        $message = '非常抱歉！退款失败';
    }
    return array('status' => $res, 'message' => $message);

}
