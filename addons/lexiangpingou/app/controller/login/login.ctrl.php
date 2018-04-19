<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * login.ctrl
 * 手机QQ登录
 */
defined('IN_IA') or exit('Access Denied');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if ($op == "display"){

    //渲染页面 登录页面
    include wl_template("login/login");
}

if ($op == "login") {
        $mobile = $_GPC["phone"];
        //检查数据库里面是否同时存在
        $res = pdo_fetch("select * from " . tablename("tg_member") . " where mobile like '%" . $mobile . "%'");
    if ($res) {
            $_W['user']['id'] = $res['id'];
            $_W['openid'] = $res['openid'];
//            if (empty($res['openid']) || !$res['openid']) {
//                unset($_W["openid"]);
//            }
            $ret = format_ret(1,"");
            die(json_encode($ret));
        } else {
        $ret = format_ret(1,"成功");
        die(json_encode($ret));
        }
}
function format_ret ($status, $data='') {
    if ($status){
        //成功
        return array('status'=>'success', 'data'=>$data);
    }else{
        //验证失败
        return array('status'=>'error', 'data'=>$data);
    }
}
//    //短信验证码接口
    if($op == "send_sms"){
        $sendUrl = 'https://v.juhe.cn/sms/send'; //短信接口的URL
        //定义短信验证码
        //获取当前时间
//        $time = date("YndHis");
        $sms_code = mt_rand(100000,999999);
        $_SESSION["sms_code"] = $sms_code;
        $smsConf = array(
            'key'   => '18666e8bbea7da82366fffd2388623e5', //您申请的APPKEY
            'mobile'    => $_GPC['phone'], //接受短信的用户手机号码
            'tpl_id'    => '18930', //您申请的短信模板ID，根据实际情况修改
            'tpl_value' =>'#code#='.$_SESSION['sms_code'] //您设置的模板变量，根据实际情况修改
        );
//        var_dump($smsConf);die();
        $content = juhecurl($sendUrl,$smsConf,1); //请求发送短信
        if($content){
            $result = json_decode($content,true);
            $error_code = $result['error_code'];
            if($error_code == 0){
                //状态为0，说明短信发送成功
                $returnstatus=array('status'=>1,'code'=>"发送成功");
                echo json_encode($returnstatus);
                exit();
            }else{
                $returnstatus=array('status'=>0,'reason'=>$result['reason']);
                //状态非0，说明失败
                $msg = $result['reason'];
                echo json_encode($returnstatus);
                exit();
            }
        }else{
            //返回内容异常，以下可根据业务逻辑自行修改
            show_json(0,"异常状态");
            exit();
        }
    }

if ($op == "check"){
    if($_GPC['num']==$_SESSION['sms_code']){
        //查询数据库是否又这一条数据
        $res = pdo_fetchall("select * from ".tablename("tg_member")." where mobile = :mobile and uniacid = :uniacid",array(":mobile"=>$_GPC['phone'],":uniacid"=>$_GPC["i"]));
        if (!$res || empty($res) || $res == 0){
            //新建一条记录
            $data["uniacid"] = $_GPC["i"];
            $data["from_user"] =00;
            $data["nickname"] = "游客";
            $data["avatar"] = "https://wx.qlogo.cn/mmopen/icXfAy8ZicysVaEUpB9mfqVTFnwPD5IRZNpTic93B5ibOzjn68BiaYJ12sqvjQSA2VrUBVs8qRO3ibNxRuK1TjD8lEenGnM3iaIxGb6/132";
            $data["addtime"] = time();
            $data["total"] = 0;
            $data["parentid"] =99;
            $data["uid"] = 1;
            $data["level"] = 0;
            $data["mobile"] = $_GPC['phone'];
//            $data["password"] = md5("12345678");
            $user_up = pdo_insert("tg_member",$data);
            $user_up_id = pdo_insertid();
            if ($user_up){
                $_W["user"]["id"] = $user_up;
                $_SESSION["id"] = $user_up_id;
                $ret  = format_ret(1,"成功");
                die(json_encode($ret));
            }else{
                $ret  = format_ret(0,"验证码已经过期!请重试");
                die(json_encode($ret));
            }
        }else{
//
            global $_W,$_GPC;
//            var_dump($res);die();
            $_W["user"]["id"] = $res['id'];
            $_W["openid"]  = $res[0]["from_user"];
            $_SESSION["user_info"] =  $res[0];
           $ret = format_ret(1,$_W["openid"]);
           die(json_encode($ret));
        }
    }else{
//       show_json(0,"短信验证码有误");
        $ret = format_ret(0,"短信验证码有误");
        die(json_encode($ret));
    }
}

//短信发送方法
