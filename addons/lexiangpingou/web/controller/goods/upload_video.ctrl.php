<?php
defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
load()->func("tpl");
load()->func("file");
$operation = $_GPC["op"];
if (empty($operation) || !$operation) {
    $uniacid = $_W["uniacid"];
    $merchant_id = $_W["user"]["merchant_id"];
    if (!$uniacid || empty($uniacid)) {
        $ret = format_ret(0, "身份核实失败");
        die(json_encode($ret));
    }
    $list = pdo_fetchall("select * from " . tablename("tg_goods_video") . " where uniacid = :uniacid and merchant_id = :merchant_id ", array(":uniacid" => $uniacid, "merchant_id" => $merchant_id));
    if (empty($list)) {
        include wl_template("goods/upload_video");
        die();
    } else {
        for ($i = 0; $i < count($list); $i++) {
            if ($list[$i]["status"] != 1 || $list[$i]["status"] != '1') {
                $list[$i]["media_url"] = "";
            } else {
            }
        }
        include wl_template("goods/upload_video");
        die();
    }
    for ($i = 0; $i < count($list); $i++) {
        if ($list[$i]["status"] != 1 || $list[$i]["status"] != '1') {
            $list[$i]["media_url"] = "";
        } else {
        }
    }
    include wl_template("goods/upload_video");
    die();

}

if ($operation == "upload") {

    $path = "./uploads/"; //上传文件的储存位置
    $result = require_once(IA_ROOT . '/framework/library/alioss/sdk.class.php');

    //获取文件名字
    $info = $_FILES["file"]["name"];
//    die(json_encode($_FILES));
    //获取文件后缀名
    $infos = explode(".", $info);;

    //文件后缀名为info[1]
    $filearray = array("mp4", 'wav', "rm", "rmvb", "wmv", "avi", "mp4", "3gp", "mkv");
    if (in_array($infos[1], $filearray)) {
        $info = date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)) . "." . $infos[1];
    } else {
        $ret = array("status" => "error", "data" => "类型不对");
        message("类型不对");
        die(json_encode($ret));
    }
    $file_size = $_FILES["size"] / 1024 / 1024;
    $uniacid = $_W["uniacid"];
    $merchant_id = $_W["user"]["merchant_id"];
    $filepath = $_FILES["file"]['tmp_name'] . '/' . $_FILES["file"]['name'];

    $data["size"] = $_FILES["file"]["size"];//文件大小

    load()->model('setting');
    load()->func('file');
    $remote = setting_load('remote');

    $oss_sdk_service = new ALIOSS($remote['remote']['alioss']['key'], $remote['remote']['alioss']['secret']);
    //设置是否打开curl调试模式
    $oss_sdk_service->set_debug_mode(FALSE);

    $path = "videos/" . $_W['uniacid'] . "/" . date("Y") . "/" . date("m") . "/";

    $bucket = $remote['remote']['alioss']['bucket'];
    $object = $path . $info;

    $path = IA_ROOT . "/attachment/" . $path;
    //首先判断目录存在否
    if (!is_dir($path)) {
        //第3个参数“true”意思是能创建多级目录，iconv防止中文目录乱码
        $res = mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
    }

    $file_path = $path . $info;

    $res = move_uploaded_file($_FILES["file"]['tmp_name'], $file_path);

    $response = $oss_sdk_service->upload_file_by_file($bucket, $object, $file_path);

    file_remote_upload($object);

    $data["media_url"] = $remote['remote']['alioss']['url'] . "/" . $object;

    $data["uniacid"] = $_W["uniacid"];
    $data["title"] = $_GPC["title"];
    $data["merchant_id"] = $_W["merchant_id"];
    pdo_insert("tg_goods_video", $data);
    message("上传成功");
}


//参数获取(状态，原因)
function format_ret($status, $data = '')
{
    if ($status) {
        //成功
        return array('status' => 'success', 'data' => $data);
    } else {
        //验证失败
        return array('status' => 'error', 'data' => $data);
    }
}

//格式化返回结果
function _format($response)
{
    echo '<pre>';
    echo '|-----------------------Start---------------------------------------------------------------------------------------------------' . "\n";
    echo '|-Status:' . $response->status . "\n";
    echo '|-Body:' . "\n";
    echo $response->body . "\n";
    echo "|-Header:\n";
    print_r($response->header);
    echo '-----------------------End-----------------------------------------------------------------------------------------------------' . "\n\n";
}
