<?php
class creat_qrcode {
	public function creategroupQrcode($mid) {
		global $_W, $_GPC;
		
		$path = IA_ROOT . "/attachment/lexiangpingou/qrcode/" . $_W['uniacid'] . "/";
		$path2 = IA_ROOT . "/addons/lexiangpingou/data/qrcode/" . $_W['uniacid'] . "/";
		$path3 = IA_ROOT . "/addons/lexiangpingou/data/pcqrcode/" . $_W['uniacid'] . "/";
		if (!is_dir($path)) {
			load() -> func('file');
			mkdirs($path);
		}
		if (!is_dir($path2)) {
			load() -> func('file');
			mkdirs($path2);
		} 
		if (!is_dir($path3)) {
			load() -> func('file');
			mkdirs($path3);
		}
		$roots='w9.huodiesoft.com';
		if($_W['uniacid']!=53)
		{
			$roots='www.lexiangpingou.cn';
		}
		$wechat=pdo_fetch('select * from '.tableName('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$head_http="http://";
		if($wechat['is_https']==1)
		{
			$head_http="https://";
		}
		$url = $head_http.$wechat['key'].".".$roots."/" . 'app/index.php?i='.$_W['uniacid'].'&c=entry&m=lexiangpingou&';
		$uni_settings=pdo_fetch('select oauth from '.tablename('uni_settings').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$oauth=iunserializer($uni_settings['oauth']);
		if(!empty($oauth['host']))
		{
			$url =$oauth['host']. '/app/index.php?i='.$_W['uniacid'].'&c=entry&m=lexiangpingou&';
		}

		$url .= 'do=order&ac=check&mid=' . $mid;
		$file = $mid . '.png';
		//file_put_contents(TG_DATA."file.log", var_export($file, true).PHP_EOL, FILE_APPEND);
		$qrcode_file = $path . $file;
		$qrcode_file2 = $path2 . $file;
		$qrcode_file3 = $path3 . $file;
		if(is_file($qrcode_file)){
			load() -> func('file');
			file_delete($qrcode_file);
		}
		if (!is_file($qrcode_file)) {
			require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode :: png($url, $qrcode_file, QR_ECLEVEL_H, 4);
			QRcode :: png($url, $qrcode_file2, QR_ECLEVEL_H, 4);
			QRcode :: png($mid, $qrcode_file3, QR_ECLEVEL_H, 4);
		} 
		return $roots. 'addons/lexiangpingou/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	
	}
	public function creatshareQrcode($mid = 0) {
		global $_W, $_GPC;
		$path = IA_ROOT . "/addons/lexiangpingou/qrcode/" . $_W['uniacid'];
		if (!is_dir($path)) {
			load() -> func('file');
			mkdirs($path);
		}
		//$roots="http://".$_SERVER['HTTP_HOST']."/";
		//$url=$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&do=index&m=lexiangpingou&mid=".$mid;
		//$url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&do=shop&p=detail&id=' . $mid . '&mid=' . $mid;
		$roots='w9.huodiesoft.com';
		if($_W['uniacid']!=53)
		{
			$roots='www.lexiangpingou.cn';
		}
		$wechat=pdo_fetch('select * from '.tableName('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$head_http="http://";
		if($wechat['is_https']==1)
		{
			$head_http="https://";
		}
		if($_W['uniacid']==33){
			$url = $head_http.$roots."/" . 'app/index.php?i='.$_W['uniacid'].'&c=entry&m=lexiangpingou&do=goods&ac=list&mid='.$mid;

		}else{
			$url = $head_http.$wechat['key'].".".$roots."/" . 'app/index.php?i='.$_W['uniacid'].'&c=entry&m=lexiangpingou&do=goods&ac=list&mid='.$mid;

		}
		$uni_settings=pdo_fetch('select oauth from '.tablename('uni_settings').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$oauth=iunserializer($uni_settings['oauth']);
		if(!empty($oauth['host']))
		{
			$url =$oauth['host']. '/app/index.php?i='.$_W['uniacid'].'&c=entry&m=lexiangpingou&do=goods&ac=list&mid='.$mid;
		}
		$file = 'share_qrcode_new_' .  $mid .'.png';
		$qrcode_file = $path . '/' . $file;
		if(is_file($qrcode_file)){
			load() -> func('file');
			file_delete($qrcode_file);
		}
		if (!is_file($qrcode_file)) {
			require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode :: png($url, $qrcode_file, QR_ECLEVEL_H, 4);
		} 
		return $roots . '/addons/lexiangpingou/qrcode/' . $_W['uniacid'] . '/' . $file;
	}



    public function creatXCXshareQrcode($uniacid,$mid = 0) {
        global $_W, $_GPC;
        $path = IA_ROOT . "/addons/lexiangpingou/qrcode/" . $uniacid;
        if (!is_dir($path)) {
            load() -> func('file');
            mkdirs($path);
        }
        //$roots="http://".$_SERVER['HTTP_HOST']."/";
        //$url=$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&do=index&m=lexiangpingou&mid=".$mid;
        //$url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&do=shop&p=detail&id=' . $mid . '&mid=' . $mid;
        $roots='w9.huodiesoft.com';
        if($uniacid!=53)
        {
            $roots='min.lexiangpingou.cn';
        }
        $wechat=pdo_fetch('select * from '.tableName('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$uniacid));
        $head_http="http://";
        if($wechat['is_https']==1)
        {
            $head_http="https://";
        }
        if($uniacid==33){
            $url = $head_http.$roots."/" . 'app/index.php?i='.$uniacid.'&c=entry&m=lexiangpingou&do=goods&ac=list&mid='.$mid;

        }else{
            $url = $head_http.$wechat['key'].".".$roots."/" . 'app/index.php?i='.$uniacid.'&c=entry&m=lexiangpingou&do=goods&ac=list&mid='.$mid;

        }
        $uni_settings=pdo_fetch('select oauth from '.tablename('uni_settings').' where uniacid=:uniacid',array(':uniacid'=>$uniacid));
        $oauth=iunserializer($uni_settings['oauth']);
        if(!empty($oauth['host']))
        {
            $url =$oauth['host']. '/app/index.php?i='.$uniacid.'&c=entry&m=lexiangpingou&do=goods&ac=list&mid='.$mid;
        }
        $file = 'share_qrcode_new_' .  $mid .'.png';
        $qrcode_file = $path . '/' . $file;
        if(is_file($qrcode_file)){
            load() -> func('file');
            file_delete($qrcode_file);
        }
        if (!is_file($qrcode_file)) {
            require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
            QRcode :: png($url, $qrcode_file, QR_ECLEVEL_H, 4);
        }
        return $roots . '/addons/lexiangpingou/qrcode/' . $uniacid . '/' . $file;
    }

	public function createverQrcode($mid = 0 , $goodsid = 0, $posterid = 0) {
		global $_W, $_GPC;
		$path = IA_ROOT . "/addons/lexiangpingou/qrcode/" . $_W['uniacid'];
		if (!is_dir($path)) {
			load() -> func('file');
			mkdirs($path);
		}
		/*
		$roots='w9.huodiesoft.com';
		if($_W['uniacid']!=53)
		{
			$roots='www.lexiangpingou.cn';
		}

		if($_W['uniacid']==33)
		{
			$roots = "http://".$roots."/";
		}else{
			$roots = "http://".$_W['uniaccount']['key'].".".$roots."/";
		}
		$url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&do=shop&p=detail&id=' . $goodsid . '&mid=' . $mid;
		*/
		$roots='w9.huodiesoft.com';
		if($_W['uniacid']!=53)
		{
			$roots='www.lexiangpingou.cn';
		}
		$wechat=pdo_fetch('select * from '.tableName('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$head_http="http://";
		if($wechat['is_https']==1)
		{
			$head_http="https://";
		}
		$url = $head_http.$wechat['key'].".".$roots."/" . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&do=goods&ac=list&id=' . $goodsid . '&mid=' . $mid;
		$uni_settings=pdo_fetch('select oauth from '.tablename('uni_settings').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
		$oauth=iunserializer($uni_settings['oauth']);
		if(!empty($oauth['host']))
		{
			$url =$oauth['host']. 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=lexiangpingou&do=goods&p=list&id=' . $goodsid . '&mid=' . $mid;
		}

		if (!empty($posterid)) {
			$url .= '&posterid=' . $posterid;
		} 
		$file = 'ver_qrcode_' . $posterid . '_' . $mid . '_' . $goodsid . '.png';
		$qrcode_file = $path . '/' . $file;
		if (!is_file($qrcode_file)) {
			require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode :: png($url, $qrcode_file, QR_ECLEVEL_H, 4);
		} 
		return $roots . 'addons/lexiangpingou/qrcode/' . $_W['uniacid'] . '/' . $file;
	} 
	public function createfunctionpayQrcode($functionid = 0 , $num = 0, $orderno = 0,$uniacid=0) {
		global $_W, $_GPC;
		$uniacid=33;
		$path = IA_ROOT . "/addons/lexiangpingou/qrcode/" . $uniacid;
		$path2 = IA_ROOT . "/addons/lexiangpingou/data/qrcode/" . $uniacid . "/";
		if (!is_dir($path)) {
			load() -> func('file');
			mkdirs($path);
		} 
		if (!is_dir($path2)) {
			load() -> func('file');
			mkdirs($path2);
		}
		$roots='w9.huodiesoft.com';
		if($_W['uniacid']!=53)
		{
			$roots='www.lexiangpingou.cn';
		}
		
		if($_W['uniacid']==33)
		{
			$roots = "http://".$roots."/";
		}else{
			$roots = "http://".$_W['uniaccount']['key'].".".$roots."/";
		}
		$url = $_W['siteroot']. 'app/index.php?i=' . $uniacid . '&c=entry&m=lexiangpingou&do=pay&ac=payfunction&id=' . $functionid;
		$url .= '&num=' . $num;
		$url .= '&buyuniacid=' . $_W['uniacid'];
		$url .= '&orderno=' . $orderno;
		$file =  $orderno . '.png';
		$qrcode_file = $path . $file;
		$qrcode_file2 = $path2 . $file;
		if (!is_file($qrcode_file)) {
			require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
		
			QRcode :: png($url, $qrcode_file2, QR_ECLEVEL_H, 4);
		} 
		
		//return $_W['siteroot'] . 'addons/lexiangpingou/qrcode/' . $uniacid . '/' . $file;
		return $roots . 'addons/lexiangpingou/data/qrcode/' . $uniacid . '/' . $file;
	}
	public function createQrcode($url)
	{
		global $_W;
		global $_GPC;
		$path = IA_ROOT . '/addons/lexiangpingou/data/qrcode/' . $_W['uniacid'] . '/';
		if (!is_dir($path)) {
			load()->func('file');
			mkdirs($path);
		}
		$file = md5(base64_encode($url)) . '.jpg';
		$qrcode_file = $path . $file;
		if (!is_file($qrcode_file)) {
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}
		return $_W['siteroot'] . 'addons/lexiangpingou/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	}
    //商品二维码
    public function createGoodsQrcode($url,$goodsid){
        global $_W;
        global $_GPC;
        $path = IA_ROOT . '/addons/lexiangpingou/data/goodsqrcode/' . $_W['uniacid'] . '/';
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $file = md5(base64_encode($url)) . '.jpg';
        $qrcode_file = $path . $file;
        if (!is_file($qrcode_file)) {
            require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
            QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
        }
        return json_encode(array('errcode'=>1,'msg'=>$_W['siteroot'] . 'addons/lexiangpingou/data/goodsqrcode/' . $_W['uniacid'] . '/' . $file));
    }
    //优惠券二维码
    public function createCouponQrcode($url,$template_id){
        set_time_limit(0);
        //用户把浏览器关掉（断开连接）亦继续执行
        ignore_user_abort(true);
        global $_W;
        global $_GPC;
        $path = IA_ROOT . '/addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/';
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $file = md5(base64_encode($url)) . '.jpg';
        $qrcode_file = $path . $file;
        if (!is_file($qrcode_file)) {
            require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
            QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
        }

        $QR = $qrcode_file; //原始二维码
        $QR = imagecreatefromstring(file_get_contents($QR));
        //背景图
        $bimg  = IA_ROOT . '/addons/lexiangpingou/web/resource/images/couponbg.png';
        $bimg = imagecreatefromstring(file_get_contents($bimg));
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $bimg_width = imagesx($bimg);//商品图片宽度
        $bimg_height = imagesy($bimg);//商品图片高度

        $newgimg=imagecreatetruecolor($bimg_width, $bimg_height);
        //2.上色
        $color=imagecolorallocate($newgimg,255,255,255);
        //3.设置透明
        imagecolortransparent($newgimg,$color);
        imagefill($newgimg,0,0,$color);
        imagecopyresized($newgimg, $bimg,0, 0,0, 0,$bimg_width, $bimg_height, $bimg_width, $bimg_height);


//        $per=200/$gimg_width;
//        $n_gw=$gimg_width*$per;
//        $n_gh=$gimg_height*$per;
//        $newgimg=imagecreatetruecolor($n_gw, $n_gh);
//        imagecopyresized($newgimg, $gimg,0, 0,0, 0,$n_gw, $n_gh, $gimg_width, $gimg_height);
        //二维码等比缩放
        $per=$bimg_width/$QR_width*0.5;
        $n_w=100;
        $n_h=100;
        $newQR=imagecreatetruecolor($n_w, $n_h);
        imagecopyresized($newQR, $QR,0, 0,0, 0,$n_w, $n_h, $QR_width, $QR_height);
        $rs_qr_height = $bimg_height;//最终图片高度
        //重新创建画布
        $backimg = imagecreatetruecolor($bimg_width,$rs_qr_height);
        //2.上色
        $color=imagecolorallocate($backimg,255,255,255);
        //3.设置透明
        imagecolortransparent($backimg,$color);
        imagefill($backimg,0,0,$color);
        //将商品图，二维码重构到画布

        imagecopymerge($backimg, $newgimg, 0, 0, 0, 0, $bimg_width, $bimg_height, 100);
        imagecopymerge($backimg, $newQR, 365, 160, 0, 0, $n_w, $n_h, 100);

        //优惠券内容文本水印
        $coupon = pdo_fetch("select * from" . tablename('tg_coupon_template') . "where id = '{$template_id}' and uniacid='{$_W['uniacid']}'");
        $font = IA_ROOT . '/framework/library/qrcode/myfont.ttf';//字体
        $black = imagecolorallocate($backimg, 255, 255, 255);//字体颜色 RGB
        $red = imagecolorallocate($backimg, 255, 255, 255);//字体颜色 RGB
        $fontSize = 40;   //字体大小
        $fontsize_2 = 14;
        $circleSize = 0; //旋转角度
        $left = 80;      //左边距
        $top = 180;       //顶边距
        $str1 = '¥ '.$coupon['value'].' 元';
        $str2 = $coupon['name'];
        $str3 = '截止时间: '.date("Y-m-d H:i:s",$coupon['end_time']);
        if(mb_strlen($str1,'utf-8')>12){
            $str1_1 = mb_substr($str1,0,10,'utf-8');
            $str1_2 = mb_substr($str1,10,10,'utf-8');
            imagefttext($backimg, $fontSize, $circleSize, $left, $top, $black, $font, $str1_1);
            imagefttext($backimg, $fontSize, $circleSize, $left, $top+30, $black, $font, $str1_2);
            if(strlen($str1_2)>10){
                $str1_3 = mb_substr($str1,20,10,'utf-8');
                imagefttext($backimg, $fontSize, $circleSize, $left, $top+60, $black, $font, $str1_3);
                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+90, $red, $font, $str2);
                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+120, $black, $font, $str3);
            }else{
                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+60, $red, $font, $str2);
                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+90, $black, $font, $str3);
            }
        }else{
            imagefttext($backimg, $fontSize, $circleSize, $left, $top, $black, $font, $str1);
            imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+30, $red, $font, $str2);
            imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+60, $black, $font, $str3);
        }


        if (imagejpeg($backimg, $qrcode_file)){
            return ['errcode'=>1,'url'=>'addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/' . $file,'name'=>$file];
//            return json_encode(array('errcode'=>1,'msg'=>$_W['siteroot'] . 'addons/lexiangpingou/data/goodsqrcode/' . $_W['uniacid'] . '/' . $file));
        }else{
            return ['errcode'=>0,'url'=>'addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/' . $file,'name'=>$file];
//            return json_encode(array('errcode'=>0,'msg'=>'二维码生成失败'));
        }




//        return ['url'=>'addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/' . $file,'name'=>$file];
    }
    //压缩优惠券二维码
    public function zipCouponQrcode($files,$template_id){
        set_time_limit(0);
        //用户把浏览器关掉（断开连接）亦继续执行
        ignore_user_abort(true);
        global $_W;
        global $_GPC;
        $path = 'couponqrcode/' . $_W['uniacid'] . '/'.$template_id;
        $path = IA_ROOT . "/attachment/" . $path;
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        if($files){
            $zip = new ZipArchive;
            $file = rand(10000, 99999) . time() . '.zip';
            $zipurl = $path.'/'.$file;
            if ($zip->open($zipurl, ZIPARCHIVE::CREATE)===TRUE) {
                foreach ($files as $key => $vvv) {
                    $filename = $vvv['name'];
                    $f =$vvv['url'];
                    $mmm = $zip->addFile(IA_ROOT.'/'.$f,$filename);//假设加入的文件名是image.txt，在当前路径下
                }

                $sss = $zip->close();
                foreach ($files as $v){
                    @unlink(IA_ROOT.'/'.$v['url']);
                }
                //压缩成功之后上传到OOS
                require_once(IA_ROOT . '/framework/library/alioss/sdk.class.php');//oos SDK引入

                //获取文件名字
                $info = $file;
                //    die(json_encode($_FILES));
                //获取文件后缀名
                $infos = explode(".", $info);;

                //文件后缀名为info[1]
                $filearray = array("zip");
                if (!in_array($infos[1], $filearray)) {
                    $ret = array("status" => "error", "data" => "类型不对");
                    message("类型不对");
                    die(json_encode($ret));
                }
                load()->model('setting');
                load()->func('file');
                $remote = setting_load('remote');
                $oss_sdk_service = new ALIOSS($remote['remote']['alioss']['key'], $remote['remote']['alioss']['secret']);
                //设置是否打开curl调试模式
                $oss_sdk_service->set_debug_mode(FALSE);
                $path = "couponqrcode/" . $_W['uniacid'] . "/" . $template_id. "/";
                $bucket = $remote['remote']['alioss']['bucket'];
                $object = $path . $info;
                $res = move_uploaded_file($_FILES["file"]['tmp_name'], $zipurl);
                $response = $oss_sdk_service->upload_file_by_file($bucket, $object, $zipurl);
                file_remote_upload($object);
                $zipurl = $remote['remote']['alioss']['url'] . "/" . $object;
                return $zipurl;
                //var_dump($sss);
                //echo '压缩成功';
            } else {
                //echo '打开zip失败';
                message(error(0, '打开zip失败!'));
            }
        }
    }


    //现金券二维码
    public function createCashCodeQrcode($url,$template_id){
        set_time_limit(0);
        //用户把浏览器关掉（断开连接）亦继续执行
        ignore_user_abort(true);
        global $_W;
        global $_GPC;
        $path = IA_ROOT . '/addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/';
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $file = md5(base64_encode($url)) . '.jpg';
        $qrcode_file = $path . $file;
        if (!is_file($qrcode_file)) {
            require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
            QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
        }

//        $QR = $qrcode_file; //原始二维码
//        $QR = imagecreatefromstring(file_get_contents($QR));
//        //背景图
//        $bimg  = IA_ROOT . '/addons/lexiangpingou/web/resource/images/couponbg.png';
//        $bimg = imagecreatefromstring(file_get_contents($bimg));
//        $QR_width = imagesx($QR);//二维码图片宽度
//        $QR_height = imagesy($QR);//二维码图片高度
//        $bimg_width = imagesx($bimg);//商品图片宽度
//        $bimg_height = imagesy($bimg);//商品图片高度
//
//        $newgimg=imagecreatetruecolor($bimg_width, $bimg_height);
//        //2.上色
//        $color=imagecolorallocate($newgimg,255,255,255);
//        //3.设置透明
//        imagecolortransparent($newgimg,$color);
//        imagefill($newgimg,0,0,$color);
//        imagecopyresized($newgimg, $bimg,0, 0,0, 0,$bimg_width, $bimg_height, $bimg_width, $bimg_height);
//
//
////        $per=200/$gimg_width;
////        $n_gw=$gimg_width*$per;
////        $n_gh=$gimg_height*$per;
////        $newgimg=imagecreatetruecolor($n_gw, $n_gh);
////        imagecopyresized($newgimg, $gimg,0, 0,0, 0,$n_gw, $n_gh, $gimg_width, $gimg_height);
//        //二维码等比缩放
//        $per=$bimg_width/$QR_width*0.5;
//        $n_w=100;
//        $n_h=100;
//        $newQR=imagecreatetruecolor($n_w, $n_h);
//        imagecopyresized($newQR, $QR,0, 0,0, 0,$n_w, $n_h, $QR_width, $QR_height);
//        $rs_qr_height = $bimg_height;//最终图片高度
//        //重新创建画布
//        $backimg = imagecreatetruecolor($bimg_width,$rs_qr_height);
//        //2.上色
//        $color=imagecolorallocate($backimg,255,255,255);
//        //3.设置透明
//        imagecolortransparent($backimg,$color);
//        imagefill($backimg,0,0,$color);
//        //将商品图，二维码重构到画布
//
//        imagecopymerge($backimg, $newgimg, 0, 0, 0, 0, $bimg_width, $bimg_height, 100);
//        imagecopymerge($backimg, $newQR, 365, 160, 0, 0, $n_w, $n_h, 100);
//
//        //优惠券内容文本水印
//        $coupon = pdo_fetch("select * from" . tablename('tg_coupon_template') . "where id = '{$template_id}' and uniacid='{$_W['uniacid']}'");
//        $font = IA_ROOT . '/framework/library/qrcode/myfont.ttf';//字体
//        $black = imagecolorallocate($backimg, 255, 255, 255);//字体颜色 RGB
//        $red = imagecolorallocate($backimg, 255, 255, 255);//字体颜色 RGB
//        $fontSize = 40;   //字体大小
//        $fontsize_2 = 14;
//        $circleSize = 0; //旋转角度
//        $left = 80;      //左边距
//        $top = 180;       //顶边距
//        $str1 = '¥ '.$coupon['value'].' 元';
//        $str2 = $coupon['name'];
//        $str3 = '截止时间: '.date("Y-m-d H:i:s",$coupon['end_time']);
//        if(mb_strlen($str1,'utf-8')>12){
//            $str1_1 = mb_substr($str1,0,10,'utf-8');
//            $str1_2 = mb_substr($str1,10,10,'utf-8');
//            imagefttext($backimg, $fontSize, $circleSize, $left, $top, $black, $font, $str1_1);
//            imagefttext($backimg, $fontSize, $circleSize, $left, $top+30, $black, $font, $str1_2);
//            if(strlen($str1_2)>10){
//                $str1_3 = mb_substr($str1,20,10,'utf-8');
//                imagefttext($backimg, $fontSize, $circleSize, $left, $top+60, $black, $font, $str1_3);
//                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+90, $red, $font, $str2);
//                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+120, $black, $font, $str3);
//            }else{
//                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+60, $red, $font, $str2);
//                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+90, $black, $font, $str3);
//            }
//        }else{
//            imagefttext($backimg, $fontSize, $circleSize, $left, $top, $black, $font, $str1);
//            imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+30, $red, $font, $str2);
//            imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+60, $black, $font, $str3);
//        }
//
//
//        if (imagejpeg($backimg, $qrcode_file)){
        return ['errcode'=>1,'url'=>'addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/' . $file,'name'=>$file];
////            return json_encode(array('errcode'=>1,'msg'=>$_W['siteroot'] . 'addons/lexiangpingou/data/goodsqrcode/' . $_W['uniacid'] . '/' . $file));
//        }else{
//            return ['errcode'=>0,'url'=>'addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/' . $file,'name'=>$file];
////            return json_encode(array('errcode'=>0,'msg'=>'二维码生成失败'));
//        }




//        return ['url'=>'addons/lexiangpingou/data/couponqrcode/' . $_W['uniacid'] . '/' . $file,'name'=>$file];
    }
    //压缩现金券二维码
    public function zipCashCodeQrcode($files,$template_id){
        set_time_limit(0);
        //用户把浏览器关掉（断开连接）亦继续执行
        ignore_user_abort(true);
        global $_W;
        global $_GPC;
        $path = 'couponqrcode/' . $_W['uniacid'] . '/'.$template_id;
        $path = IA_ROOT . "/attachment/" . $path;
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        if($files){
            $zip = new ZipArchive;
            $file = rand(10000, 99999) . time() . '.zip';
            $zipurl = $path.'/'.$file;
            if ($zip->open($zipurl, ZIPARCHIVE::CREATE)===TRUE) {
                foreach ($files as $key => $vvv) {
                    $filename = $vvv['name'];
                    $f =$vvv['url'];
                    $mmm = $zip->addFile(IA_ROOT.'/'.$f,$filename);//假设加入的文件名是image.txt，在当前路径下
                }

                $sss = $zip->close();
                foreach ($files as $v){
                    @unlink(IA_ROOT.'/'.$v['url']);
                }
                //压缩成功之后上传到OOS
                require_once(IA_ROOT . '/framework/library/alioss/sdk.class.php');//oos SDK引入

                //获取文件名字
                $info = $file;
                //    die(json_encode($_FILES));
                //获取文件后缀名
                $infos = explode(".", $info);;

                //文件后缀名为info[1]
                $filearray = array("zip");
                if (!in_array($infos[1], $filearray)) {
                    $ret = array("status" => "error", "data" => "类型不对");
                    message("类型不对");
                    die(json_encode($ret));
                }
                load()->model('setting');
                load()->func('file');
                $remote = setting_load('remote');
                $oss_sdk_service = new ALIOSS($remote['remote']['alioss']['key'], $remote['remote']['alioss']['secret']);
                //设置是否打开curl调试模式
                $oss_sdk_service->set_debug_mode(FALSE);
                $path = "couponqrcode/" . $_W['uniacid'] . "/" . $template_id. "/";
                $bucket = $remote['remote']['alioss']['bucket'];
                $object = $path . $info;
                $res = move_uploaded_file($_FILES["file"]['tmp_name'], $zipurl);
                $response = $oss_sdk_service->upload_file_by_file($bucket, $object, $zipurl);
                file_remote_upload($object);
                $zipurl = $remote['remote']['alioss']['url'] . "/" . $object;
                return $zipurl;
                //var_dump($sss);
                //echo '压缩成功';
            } else {
                //echo '打开zip失败';
                message(error(0, '打开zip失败!'));
            }
        }
    }

    //团单二维码
    public function createOrderQrcode($url,$groupnumber){
        global $_W;
        global $_GPC;
        $path = IA_ROOT . '/addons/lexiangpingou/data/goodsqrcode/' . $_W['uniacid'] . '/';
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $groupnumber = intval($groupnumber);
        //指定团的id
        $thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
        $orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' ORDER BY id asc");
        $goods = goods_get_by_params(" id='{$thistuan['goodsid']}' ");
//        print_r($goods);
//        die;
//        $goods = pdo_fetch("select id,goodsdesc,gimg,gname,mprice,gprice,oprice,selltype,preprice from " .tablename('tg_goods') ." where id = " .$goodsid);
        $gimg = $goods['share_image'];
        if(!@file_get_contents($gimg)){
            $gimg = $goods['gimg'];
            if(!@file_get_contents($gimg)){
                return json_encode(array('errcode'=>0,'msg'=>'商品图片不存在'));
            }
        }
        $gimg = imagecreatefromstring(file_get_contents($gimg));
        $url = $goods['a'];
        $file = md5(base64_encode($url)) . '.jpg';
        $qrcode_file = $path . $file;
        if(is_file($qrcode_file)){
            $rs = @unlink($qrcode_file);
        }
        require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
        QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
        //获取商品信息
        $QR = $qrcode_file; //原始二维码
        $QR = imagecreatefromstring(file_get_contents($QR));
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $gimg_width = imagesx($gimg);//商品图片宽度
        $gimg_height = imagesy($gimg);//商品图片高度
        //商品图等比缩放
        $maxwidth = 392;
        $maxheight = 392;
        if($gimg_width>$maxwidth)
        {
            $widthratio = $maxwidth/$gimg_width;
            $resizewidth_tag = true;
        }
        if($gimg_height>$maxheight)
        {
            $heightratio = $maxheight/$gimg_height;
            $resizeheight_tag = true;
        }
        if($resizewidth_tag && $resizeheight_tag)
        {
            if($widthratio<$heightratio)
                $ratio = $widthratio;
            else
                $ratio = $heightratio;
        }
        if($resizewidth_tag && !$resizeheight_tag)
            $ratio = $widthratio;
        if($resizeheight_tag && !$resizewidth_tag)
            $ratio = $heightratio;
        if($ratio){
            $newwidth = $gimg_width * $ratio;
            $newheight = $gimg_height * $ratio;
        }else{
            $newwidth = $maxwidth;
            $newheight = $maxheight;
        }

        $newgimg=imagecreatetruecolor($newwidth, $newheight);
        imagecopyresized($newgimg, $gimg,0, 0,0, 0,$newwidth, $newheight, $gimg_width, $gimg_height);


//        $per=200/$gimg_width;
//        $n_gw=$gimg_width*$per;
//        $n_gh=$gimg_height*$per;
//        $newgimg=imagecreatetruecolor($n_gw, $n_gh);
//        imagecopyresized($newgimg, $gimg,0, 0,0, 0,$n_gw, $n_gh, $gimg_width, $gimg_height);
        //二维码等比缩放
        $per=$newwidth/$QR_width*0.5;
        $n_w=$QR_width*$per;
        $n_h=$QR_height*$per;
        $newQR=imagecreatetruecolor($n_w, $n_h);
        imagecopyresized($newQR, $QR,0, 0,0, 0,$n_w, $n_h, $QR_width, $QR_height);
        $rs_qr_height = $newheight + $n_h;//最终图片高度
        //重新创建画布
        $backimg = imagecreatetruecolor($newwidth,$rs_qr_height);
        //2.上色
        $color=imagecolorallocate($backimg,255,255,255);
        //3.设置透明
        imagecolortransparent($backimg,$color);
        imagefill($backimg,0,0,$color);
        //将商品图，二维码重构到画布

        imagecopymerge($backimg, $newgimg, 0, 0, 0, 0, $newwidth, $newheight, 100);
        imagecopymerge($backimg, $newQR, 0, $newheight, 0, 0, $n_w, $n_h, 100);
        //添加文本水印
        $font = IA_ROOT . '/framework/library/qrcode/myfont.ttf';//字体
        $black = imagecolorallocate($backimg, 0, 0, 0);//字体颜色 RGB
        $red = imagecolorallocate($backimg, 255, 0, 0);//字体颜色 RGB
        $fontSize = 14;   //字体大小
        $fontsize_2 = 17;
        $circleSize = 0; //旋转角度
        $left = $n_w+10;      //左边距
        $top = $newheight+30;       //顶边距
//        $str1 = '商品名称：'.$goods['gname'];
        //按照类型
        //拼团模式 0单买 1 随意团 2 邻购团 2自选团 4阶梯团 5抽奖团 6 新专团 7 定金团
        switch ($goods['selltype']){
            case '0':
                $str2 = '单价：'.$goods['oprice'];
                break;
            case '4':
                $str2 = '拼团价：'.$goods['gprice'];
                break;
            case '7':
                $str2 = '定金：'.$goods['preprice'];
                break;
            default:
                $str2 = '拼团价：'.$goods['gprice'];
                break;
        }
        $str1 = '【差'.$thistuan['lacknum'].'人】我参加了'.$goods['gname'].'的团';
        $str3 = '市场价：'.$goods['mprice'];
        $str5 = '———————';
//        print_r(mb_strlen($str1,'utf-8'));
//        die;
        if(mb_strlen($str1,'utf-8')>12){
            $str1_1 = mb_substr($str1,0,10,'utf-8');
            $str1_2 = mb_substr($str1,10,10,'utf-8');
            imagefttext($backimg, $fontSize, $circleSize, $left, $top, $black, $font, $str1_1);
            imagefttext($backimg, $fontSize, $circleSize, $left, $top+30, $black, $font, $str1_2);
            if(strlen($str1_2)>10){
                $str1_3 = mb_substr($str1,20,10,'utf-8');
                imagefttext($backimg, $fontSize, $circleSize, $left, $top+60, $black, $font, $str1_3);
                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+90, $red, $font, $str2);
                imagefttext($backimg, $fontSize, $circleSize, $left, $top+120, $black, $font, $str3);
                imagefttext($backimg, $fontSize, $circleSize, $left-10, $top+120, $black, $font, $str5);
            }else{
                imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+60, $red, $font, $str2);
                imagefttext($backimg, $fontSize, $circleSize, $left, $top+90, $black, $font, $str3);
                imagefttext($backimg, $fontSize, $circleSize, $left-10, $top+90, $black, $font, $str5);
            }
        }else{
            imagefttext($backimg, $fontSize, $circleSize, $left, $top, $black, $font, $str1);
            imagefttext($backimg, $fontsize_2, $circleSize, $left, $top+30, $red, $font, $str2);
            imagefttext($backimg, $fontSize, $circleSize, $left, $top+60, $black, $font, $str3);
            imagefttext($backimg, $fontSize, $circleSize, $left-10, $top+60, $black, $font, $str5);
        }

        if (imagejpeg($backimg, $qrcode_file)){
            return json_encode(array('errcode'=>1,'msg'=>$_W['siteroot'] . 'addons/lexiangpingou/data/goodsqrcode/' . $_W['uniacid'] . '/' . $file));
        }else{
            return json_encode(array('errcode'=>0,'msg'=>'二维码生成失败'));
        }

    }


    //核销二维码
    public function createhexiaoQrcode($mid) {
        global $_W, $_GPC;

        $path = IA_ROOT . "/addons/lexiangpingou/data/qrcode/" . $_W['uniacid'] . "/";
        if (!is_dir($path)) {
            load() -> func('file');
            mkdirs($path);
        }
        $roots='w9.huodiesoft.com';
        if($_W['uniacid']!=53)
        {
            $roots='www.lexiangpingou.cn';
        }
        $wechat=pdo_fetch('select * from '.tableName('account_wechats').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
        $head_http="http://";
        if($wechat['is_https']==1)
        {
            $head_http="https://";
        }
        $url = $head_http.$wechat['key'].".".$roots."/" . 'app/index.php?i='.$_W['uniacid'].'&c=entry&m=lexiangpingou&';
        $uni_settings=pdo_fetch('select oauth from '.tablename('uni_settings').' where uniacid=:uniacid',array(':uniacid'=>$_W['uniacid']));
        $oauth=iunserializer($uni_settings['oauth']);
        if(!empty($oauth['host']))
        {
            $url =$oauth['host']. '/app/index.php?i='.$_W['uniacid'].'&c=entry&m=lexiangpingou&';
        }

        $url .= 'do=order&ac=check&mid=' . $mid;
        $file = $mid . '.png';
        //file_put_contents(TG_DATA."file.log", var_export($file, true).PHP_EOL, FILE_APPEND);
        $qrcode_file = $path . $file;
        if(is_file($qrcode_file)){
            load() -> func('file');
            file_delete($qrcode_file);
        }
        if (!is_file($qrcode_file)) {
            require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
            QRcode :: png($url, $qrcode_file, QR_ECLEVEL_H, 4);
        }
        return $roots. 'addons/lexiangpingou/data/qrcode/' . $_W['uniacid'] . '/' . $file;

    }
} 
