<?php
/**
 * [weliam] Copyright (c) 2016/3/23
 * addmanage.ctrl
 * 我的地址控制器
 */
defined('IN_IA') or exit('Access Denied');
wl_load()->model('address');

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

session_start();
$_SESSION['goodsid'] = $_GPC['goodsid'];
$goodsid = $_SESSION['goodsid'];
$tuan_id=$_GPC['tuan_id'];
$groupnum=$_GPC['groupnum'];
$pagetitle = '编辑收货地址';
wl_load()->model('setting');
wl_load()->model('goods');
$setting = setting_get_by_name("kaiguan");
if(empty($setting['is_village'])){
    $is_village = 2;
}else{
    $is_village = $setting['is_village'];
}
    if($goodsid){
        $goods = goods_get_by_params(' id='.$goodsid);

    }

//权限控制
$tid=8154;
//权限控制
if (empty($_W['openid'])) {
    die("<!DOCTYPE html>
            <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'>
                    <title>抱歉，出错了</title><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=0'><link rel='stylesheet' type='text/css' href='https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css'>
                </head>
                <body>
                <div class='page_msg'><div class='inner'><span class='msg_icon_wrp'><i class='icon80_smile'></i></span><div class='msg_content'><h4>请在微信客户端打开链接</h4></div></div></div>
                </body>
            </html>");
}
wl_load()->model('functions');
$isshop=$_GPC['isshop'];
if(!empty($_GPC['isshop']))
{
	$_SESSION['isshop']=$_GPC['isshop'];
}
if($tuan_id){
    $firstorder = order_get_by_params(" tuan_id = {$tuan_id} and tuan_first = 1");
    $addid = $firstorder['addressid'];
    $firstopenid = $firstorder['openid'];
//    $first = member_get_by_params("openid='{$firstopenid}'");
    $adress_fee = pdo_fetch("select * from " . tablename('tg_address') . "where id = '{$addid}'  ");
//    $adress_fee1 = pdo_fetch("select * from " . tablename('tg_address') . "where openid = '$openid' and status = 1");
//    $adress_fee['cname'] = $adress_fee1['cname'];
//    $adress_fee['tel'] = $adress_fee1['tel'];
//    $tuan = group_get_by_params(array('id'=>$tuan_id));
}

$checkfunction=checkfunc(8154);
$setting=setting_get_by_name("autoaddress");
$autoaddr=0;
if($checkfunction['status']&&$setting['autoaddr']==1)
{
	$autoaddr=1;
	$addrfirst = pdo_fetchall("SELECT * FROM " . tablename('tg_provice')."where weid={$_W['uniacid']} and enabled=1 and parentid=0");
}
if(empty($isshop))
{
	if($goodsid){
		$bakurl = app_url('order/orderconfirm',array('id'=>$goodsid,'tuan_id'=>$tuan_id,'groupnum'=>$groupnum,'num'=>$_GPC['num']));
	}else{
		$bakurl = app_url('member/addmanage');
	}
}else{
	$bakurl = app_url('order/shoporderconfirm');
}
$id=$_GPC['id'];
$weid = $_W['uniacid'];
$openid = $_W['openid'];

if ($op == 'display') {
    if($id){
		
        $addres = address_get_by_id($id);
    }
$addrfirst = pdo_fetchall("SELECT * FROM " . tablename('tg_provice')."where weid={$_W['uniacid']} and enabled=1 and parentid=0");
$city = pdo_fetch("SELECT * FROM " . tablename('tg_provice')." where name='{$addres['city']}' ");  
$county = pdo_fetch("SELECT * FROM " . tablename('tg_provice')." where name='{$addres['county']}' ");    	
	include wl_template('address/createadd',array('isshop'=>$isshop,'goodsid'=>$goodsid,'tuan_id'=>$tuan_id,'groupnum'=>$groupnum));
}

if ($op == 'addwechat'){
	
	$shareAddress = shareAddress();
	include wl_template('address/createadd',array('isshop'=>$isshop));
}

if ($op == 'post') {
	// wl_json(0,$_GPC['city']); 
    if(!empty($id)){
        $status = address_get_by_id($id);
		if($autoaddr==1)
		{
		//$city = pdo_fetch("SELECT * FROM " . tablename('tg_provice')." where id={$_GPC['city']} ");  
//$county = pdo_fetch("SELECT * FROM " . tablename('tg_provice')." where id={$_GPC['county']} ");  		
			$data=array(
            'openid'           => $openid,
            'uniacid'          => $weid,
            'cname'            => $_GPC['myname'],
            'tel'              => $_GPC['myphone'],
            'province'         => $_GPC['province'],
            'city'             => $_GPC['city'],
            'county'           => $_GPC['county'],
            'detailed_address' => $_GPC['detailed'],
            'type'             => $_GPC['type'],
			'ctype'             => 1,
			'status'             => 1,
            'addtime'          => time(),
                'village' =>$_GPC['village']
			);
		}else{
			$citys = explode(" ", $_GPC['citys']); 
			$data=array(
            'openid'           => $openid,
            'uniacid'          => $weid,
            'cname'            => $_GPC['myname'],
            'tel'              => $_GPC['myphone'],
            'province'         => $citys[0],
            'city'             => $citys[1],
            'county'           => $citys[2],
            'detailed_address' => $_GPC['detailed'],
            'type'             => $_GPC['type'],
			'status'             => 1,
            'addtime'          => time(),
                'village' =>$_GPC['village']
        );
		}
        //验证data数据完整性
        foreach ($data as $key=>$value){
            if($key == 'cname'||$key=='tel'||$key=='province'||$key=='city'||$key=='detailed_address'){
                if(!$value){
                    wl_json(0,'数据丢失，请重试');
                }
            }
        }
        $moren = address_get_by_params("status = 1 and openid = '$openid'");
    	pdo_update('tg_address',array('status' => 0),array('id' => $moren['id']));
        if(pdo_update('tg_address',$data,array('id' => $id))){ 
        	wl_json(1);
        }else{   
            wl_json(0,'收货地址编辑失败!');
        }
    }else{
        if($autoaddr==1)
		{
//$city = pdo_fetch("SELECT * FROM " . tablename('tg_provice')." where id={$_GPC['city']} ");  
//$county = pdo_fetch("SELECT * FROM " . tablename('tg_provice')." where id={$_GPC['county']} ");  	
			$data=array(
            'openid'           => $openid,
            'uniacid'          => $weid,
            'cname'            => $_GPC['myname'],
            'tel'              => $_GPC['myphone'],
            'province'         => $_GPC['province'],
			'city'             => $_GPC['city'],
            'county'           => $_GPC['county'],
            'detailed_address' => $_GPC['detailed'],
            'type'             => $_GPC['type'],
			'ctype'             => 1,
			'status' => 1,
            'addtime'          => time(),
                'village' =>$_GPC['village']
			);
		}else{
			$citys = explode(" ", $_GPC['citys']); 
			$data=array(
            'openid'           => $openid,
            'uniacid'          => $weid,
            'cname'            => $_GPC['myname'],
            'tel'              => $_GPC['myphone'],
            'province'         => $citys[0],
            'city'             => $citys[1],
            'county'           => $citys[2],
            'detailed_address' => $_GPC['detailed'],
            'type'             => $_GPC['type'],
			'status' => 1,
            'addtime'          => time(),
                'village' =>$_GPC['village']
        );
		}


        //验证data数据完整性
        foreach ($data as $key=>$value){
            if($key == 'cname'||$key=='tel'||$key=='province'||$key=='city'||$key=='detailed_address'){
                if(!$value){
                    wl_json(0,'数据丢失，请重试');
                }
            }
        }

    	$moren = address_get_by_params("status = 1 and openid = '$openid'");
    	pdo_update('tg_address',array('status' => 0),array('id' => $moren['id']));
        if(pdo_insert('tg_address',$data)){
        	wl_json(1);
        }else{                      
            wl_json(0,'收货地址编辑失败!');
        }                 
    }   
}
if($op == 'ck'){
		if (isset($_POST['provice2'])) {
        $id = isset($_POST['provice2']) ? $_POST['provice2'] : '0';
        $str = '';
        if ($id != 0) {
			 $addrsec = pdo_fetchall("SELECT * FROM " . tablename('tg_provice')."where parentid={$id}");
            foreach($addrsec as $val) {
                $str .= "<div data-value=".$val['id']." class='checked'>".$val['name']."</div>";
           }
		 // $str .= '<div value=2>'.$_POST['provice2'].'</div>';
        }
		//message($str);
        echo $str;
        exit;
    }

    // 获取县城
    if (isset($_POST['city2'])) {
        $id = isset($_POST['city2']) ? $_POST['city2'] : '0';
        
        $str = '';
        if ($id != 0) {
            $addr = pdo_fetchall("SELECT * FROM " . tablename('tg_provice')."where parentid={$id}");
            foreach($addr as $val) {
               // $str .= "<div data-value='.$val['id'].'  class='checked1'>'.$val['name'].'</div>";
				 $str .= "<div data-value=".$val['id']." class='checked1'>".$val['name']."</div>";
            }
        }

        echo $str;
        exit;
    }
           
}
if ($op == 'deletes'){
	if($id){
        if(pdo_delete('tg_address',array('id' => $id ))){
            wl_json(1);
        }else{
            wl_json(0,'收货地址删除失败!');
        }        
    }else{
        wl_json(0,'缺少地址id参数');
    }
}
exit();