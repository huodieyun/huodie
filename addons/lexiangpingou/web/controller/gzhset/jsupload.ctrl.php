<?php
/**
 * Created by PhpStorm.
 * User: 10987
 * Date: 2017/8/12
 * Time: 13:48
 */
load()->func("tpl");
global $_W,$_GPC;
$op = $_GPC["op"];
if (empty($op) || !$op){
    $op = "upload";
}
if ($op == "upload"){
    if (checksubmit("submit")){
        $name = $_GPC["name"];
        $myfile = fopen(IA_ROOT."/MP_verify_".$name.".txt", "w+") or die("Unable to open file!");
        $txt = $name;
        fwrite($myfile, $txt);
    fclose($myfile);
       echo "<script>alert('上传成功,如果通信失败请重试');location.href=location.href;</script>";
        include wl_template('set/jsupload');
       exit();
    }
    include wl_template('set/jsupload');
}
