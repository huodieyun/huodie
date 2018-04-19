<?
       global $_GPC, $_W;
wl_load()->func('global');
wl_load()->model('setting');
$config = tgsetting_load();
$setting = setting_get_by_name("jobsmscode");
if(!empty($config['tginfo']['saler_img']))
{
	$aaurl=tomedia($config['tginfo']['saler_img']);
}
$slogo=tomedia($config['tginfo']['slogo']);

	   $member=pdo_fetch("SELECT id,total,from_user,parentid FROM ".tablename('tg_member')." WHERE from_user='{$_W['openid']}' and uniacid='{$_W['uniacid']}'");	
		$member1=pdo_fetch("SELECT id,total,from_user,parentid FROM ".tablename('tg_member')." WHERE from_user='{$_W['openid']}' and enable=1  and uniacid='{$_W['uniacid']}'");	
		$member2=pdo_fetch("SELECT id,total,from_user,parentid FROM ".tablename('tg_member')." WHERE id='{$_GPC['sharenum']}'  and uniacid='{$_W['uniacid']}'");
		if(empty($member1))
		{
			$sharen=0;
		}else{
			$sharen=$member1['id'];
		}
		$to_url=app_url('order/infojob')."&sharenum=".$sharen;	
		//$to_url=$_W['siteroot']."app/index.php?i=".$_W['uniacid']."&c=entry&do=infojob&m=feng_fightgroups&sharenum=".$sharen;		
		$fans=pdo_fetch("SELECT * FROM ".tablename('mc_mapping_fans')." WHERE openid='{$_W['openid']}' and uniacid='{$_W['uniacid']}' and follow=1");	
		if((empty($member)||$member['parentid']==-1)&&!empty($_GPC['sharenum']))
		{
			//message(intval($_GPC['sharenum']));
			if($member['parentid']==-1&&!empty($fans))
			{
				$anum=0;
			}else{
				$anum=intval($_GPC['sharenum']);
			}
			$data = array(					
					'parentid' => $anum
				);
			pdo_update('tg_member', $data, array('id' =>$member['id']));
			
		}
		$member=pdo_fetch("SELECT id,total,from_user,parentid,type FROM ".tablename('tg_member')." WHERE from_user='{$_W['openid']}' and uniacid='{$_W['uniacid']}'");	
		if($member['type']==1)
		{
			$anumb=$member['id'];
		}else{
			$anumb=$member['parentid'];
		}
		
		if(empty($_GPC['sharenum'])&&$member['parentid']==-1)
		{
			if($member['parentid']==-1&&!empty($fans))
			{
				$anum=0;
			}else{
				$anum=intval($_GPC['sharenum']);
			}
			$data = array(					
					'parentid' => $anum
				);
			pdo_update('tg_member', $data, array('id' =>$member['id']));
		}
       $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        $weid = $_W['uniacid'];
        $openid = $_W['openid'];
		$share_indexname=$_W['uniaccount']['name']."发布兼职招募令,速来!!!";
		$share_indexdesc="呼朋唤友一起干,动动手指来收益!";
		$share_images =tomedia($config['share']['share_image']);
        $mem = pdo_fetch("SELECT * FROM " . tablename('tg_member')."where from_user='{$openid}' and uniacid='{$weid}'");
    	if (checksubmit('submit')) { 

			pdo_update('tg_member',array('name'=>$_GPC['name'],'mobile'=>$_GPC['mobile'],'type'=>1,'addtime'=>TIMESTAMP,'weixinnumber'=>$_GPC['weixinnumber'],'shopname'=>$_GPC['shopname']),array('id'=>$mem['id']));

			$tip='申请成功';
			echo "<script>alert('".$tip."');location.href='".app_url('order/infojob')."';</script>";
			exit;
        }
      
     include wl_template('order/infojob');exit();
 ?>