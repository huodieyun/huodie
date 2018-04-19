<?php
defined('IN_IA') or exit('Access Denied');

function _calc_current_frames2(&$frames)
{
    global $_W, $_GPC;
    if (!empty($frames) && is_array($frames)) {
        foreach ($frames as &$frame) {
            foreach ($frame['items'] as &$fr) {
                if (count($fr['actions']) == 2) {
                    if ($fr['actions']['1'] == $_GPC[$fr['actions']['0']]) {
                        $fr['active'] = 'active';
                    }
                } elseif (count($fr['actions']) == 4) {
                    if ($fr['actions']['1'] == $_GPC[$fr['actions']['0']] && $fr['actions']['3'] == $_GPC[$fr['actions']['2']]) {
                        $fr['active'] = 'active';
                    }
                } else {
                    $query = parse_url($fr['url'], PHP_URL_QUERY);
                    parse_str($query, $urls);
                    if (defined('ACTIVE_FRAME_URL')) {
                        $query = parse_url(ACTIVE_FRAME_URL, PHP_URL_QUERY);
                        parse_str($query, $get);
                    } else {
                        $get = $_GET;
                    }
                    if (!empty($_GPC['a'])) {
                        $get['a'] = $_GPC['a'];
                    }
                    if (!empty($_GPC['c'])) {
                        $get['c'] = $_GPC['c'];
                    }
                    if (!empty($_GPC['do'])) {
                        $get['do'] = $_GPC['do'];
                    }
                    if (!empty($_GPC['ac'])) {
                        $get['ac'] = $_GPC['ac'];
                    }
                    if (!empty($_GPC['status'])) {
                        $get['status'] = $_GPC['status'];
                    }
                    if (!empty($_GPC['op'])) {
                        $get['op'] = $_GPC['op'];
                    }
                    if (!empty($_GPC['m'])) {
                        $get['m'] = $_GPC['m'];
                    }
                    $diff = array_diff_assoc($urls, $get);

                    if (empty($diff)) {
                        $fr['active'] = 'active';
                    } else {
                        $fr['active'] = '';
                    }
                }
            }
        }
    }
}

//公众号设置
function getgzhsetFrames()
{
    global $_W;
    $frames = array();
    $frames['notificas']['title'] = '<i class="fa fa-gear"></i>&nbsp;&nbsp; 通知设置';
    $frames['notificas']['items'] = array();
    $shop = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
    if ($_W['user']['merchant_id'] == 0 && $shop['is_merchant'] == 1) {
        $frames['notificas']['items']['notification']['url'] = web_url('gzhset/notification');
        $frames['notificas']['items']['notification']['title'] = '发布通知';
        $frames['notificas']['items']['notification']['actions'] = array();
        $frames['notificas']['items']['notification']['active'] = '';
    }
    if ($_W['uniacid'] == 33||$_W['uniacid'] == 826||$_W['uniacid'] == 921) {
        $frames['notificas']['items']['jsupload']['url'] = web_url('gzhset/jsupload');
        $frames['notificas']['items']['jsupload']['title'] = '上传js文件';
        $frames['notificas']['items']['jsupload']['actions'] = array();
        $frames['notificas']['items']['jsupload']['active'] = '';
    }
    $frames['notificas']['items']['nlist']['url'] = web_url('gzhset/notification-list');
    $frames['notificas']['items']['nlist']['title'] = '通知列表';
    $frames['notificas']['items']['nlist']['actions'] = array();
    $frames['notificas']['items']['nlist']['active'] = '';

    if ((checkstr($_W['user']['perms'], 'gzhset.platform') ||
            checkstr($_W['user']['perms'], 'gzhset.special') ||
            checkstr($_W['user']['perms'], 'gzhset.basic') ||
            checkstr($_W['user']['perms'], 'gzhset.news') ||
            checkstr($_W['user']['perms'], 'gzhset.images') ||
            checkstr($_W['user']['perms'], 'gzhset.custom') ||
            checkstr($_W['user']['perms'], 'gzhset.userapi'))
        && $_W['user']['merchant_id'] == 0
    ) {
        $frames['gzhsethf']['title'] = '<i class="fa fa-gear"></i>&nbsp;&nbsp; 回复设置';
        $frames['gzhsethf']['items'] = array();
        if (checkstr($_W['user']['perms'], 'gzhset.platform')) {
            $frames['gzhsethf']['items']['platform']['url'] = web_url('gzhset/platform/platform');
            $frames['gzhsethf']['items']['platform']['title'] = '公众号概况';
            $frames['gzhsethf']['items']['platform']['actions'] = array();
            $frames['gzhsethf']['items']['platform']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.special')) {
            $frames['gzhsethf']['items']['special']['url'] = web_url('gzhset/special/display');
            $frames['gzhsethf']['items']['special']['title'] = '系统回复';
            $frames['gzhsethf']['items']['special']['actions'] = array();
            $frames['gzhsethf']['items']['special']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.basic')) {
            $frames['gzhsethf']['items']['adv1']['url'] = web_url('gzhset/reply', array('mo' => 'basic'));
            $frames['gzhsethf']['items']['adv1']['title'] = '文字回复';
            $frames['gzhsethf']['items']['adv1']['actions'] = array('ac', 'basic');
            $frames['gzhsethf']['items']['adv1']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.news')) {
            $frames['gzhsethf']['items']['adv2']['url'] = web_url('gzhset/reply', array('mo' => 'news'));
            $frames['gzhsethf']['items']['adv2']['title'] = '图文回复';
            $frames['gzhsethf']['items']['adv2']['actions'] = array();
            $frames['gzhsethf']['items']['adv2']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.images')) {
            $frames['gzhsethf']['items']['adv3']['url'] = web_url('gzhset/reply', array('mo' => 'images'));
            $frames['gzhsethf']['items']['adv3']['title'] = '图片回复';
            $frames['gzhsethf']['items']['adv3']['actions'] = array();
            $frames['gzhsethf']['items']['adv3']['active'] = '';
        }
        /*
        $frames['gzhsethf']['items']['adv4']['url'] = web_url('gzhset/reply',array('mo' => 'music'));
        $frames['gzhsethf']['items']['adv4']['title'] = '音乐回复';
        $frames['gzhsethf']['items']['adv4']['actions'] = array();
        $frames['gzhsethf']['items']['adv4']['active'] = '';

        $frames['gzhsethf']['items']['adv5']['url'] = web_url('gzhset/reply',array('mo' => 'voice'));
        $frames['gzhsethf']['items']['adv5']['title'] = '语音回复';
        $frames['gzhsethf']['items']['adv5']['actions'] = array();
        $frames['gzhsethf']['items']['adv5']['active'] = '';

        $frames['gzhsethf']['items']['adv5']['url'] = web_url('gzhset/reply',array('mo' => 'video'));
        $frames['gzhsethf']['items']['adv5']['title'] = '视频回复';
        $frames['gzhsethf']['items']['adv5']['actions'] = array();
        $frames['gzhsethf']['items']['adv5']['active'] = '';*/

        if (checkstr($_W['user']['perms'], 'gzhset.custom')) {
            $frames['gzhsethf']['items']['adv']['url'] = web_url('gzhset/reply', array('mo' => 'custom'));
            $frames['gzhsethf']['items']['adv']['title'] = '多客服接入';
            $frames['gzhsethf']['items']['adv']['actions'] = array();
            $frames['gzhsethf']['items']['adv']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.userapi')) {
            $frames['gzhsethf']['items']['reply']['url'] = web_url('gzhset/reply', array('mo' => 'userapi'));
            $frames['gzhsethf']['items']['reply']['title'] = '自定义接口回复';
            $frames['gzhsethf']['items']['reply']['actions'] = array();
            $frames['gzhsethf']['items']['reply']['active'] = '';
        }
    }

    if ((checkstr($_W['user']['perms'], 'gzhset.menu') ||
            checkstr($_W['user']['perms'], 'gzhset.url2qr') ||
            checkstr($_W['user']['perms'], 'gzhset.oauth') ||
            checkstr($_W['user']['perms'], 'gzhset.jsauth'))
        && $_W['user']['merchant_id'] == 0
    ) {
        $frames['gzhsetgj']['title'] = '<i class="fa fa-bookmark"></i>&nbsp;&nbsp; 高级功能';
        $frames['gzhsetgj']['items'] = array();
        if (checkstr($_W['user']['perms'], 'gzhset.menu')) {
            $frames['gzhsetgj']['items']['category1']['url'] = web_url('gzhset/menu');
            $frames['gzhsetgj']['items']['category1']['title'] = '自定义菜单设置';
            $frames['gzhsetgj']['items']['category1']['actions'] = array();
            $frames['gzhsetgj']['items']['category1']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.url2qr')) {
            $frames['gzhsetgj']['items']['category2']['url'] = web_url('gzhset/url2qr');
            $frames['gzhsetgj']['items']['category2']['title'] = '长链转短链';
            $frames['gzhsetgj']['items']['category2']['actions'] = array();
            $frames['gzhsetgj']['items']['category2']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.oauth')) {
            $frames['gzhsetgj']['items']['category3']['url'] = web_url('gzhset/passport', array('mo' => 'oauth'));
            $frames['gzhsetgj']['items']['category3']['title'] = 'oAuth设置';
            $frames['gzhsetgj']['items']['category3']['actions'] = array();
            $frames['gzhsetgj']['items']['category3']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.jsauth')) {
            if ($_W['isfounder']) {
                $frames['gzhsetgj']['items']['category4']['url'] = web_url('gzhset/jsauth');
                $frames['gzhsetgj']['items']['category4']['title'] = 'JS分享设置';
                $frames['gzhsetgj']['items']['category4']['actions'] = array();
                $frames['gzhsetgj']['items']['category4']['active'] = '';
            }
        }
    }

    if ((checkstr($_W['user']['perms'], 'gzhset.payment') ||
            checkstr($_W['user']['perms'], 'gzhset.list')) && $_W['user']['merchant_id'] == 0
    ) {
        $frames['gzhset1']['title'] = '<i class="fa fa-bookmark"></i>&nbsp;&nbsp; 支付设置';
        $frames['gzhset1']['items'] = array();

        if (checkstr($_W['user']['perms'], 'gzhset.payment')) {
            $frames['gzhset1']['items']['category1']['url'] = web_url('gzhset/payment');
            $frames['gzhset1']['items']['category1']['title'] = '支付参数';
            $frames['gzhset1']['items']['category1']['actions'] = array();
            $frames['gzhset1']['items']['category1']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'gzhset.list')) {
            $frames['gzhset1']['items']['helpbuy']['url'] = web_url('gzhset/helpbuy/list');
            $frames['gzhset1']['items']['helpbuy']['title'] = '代付设置';
            $frames['gzhset1']['items']['helpbuy']['actions'] = array();
            $frames['gzhset1']['items']['helpbuy']['active'] = '';
        }
    }
    return $frames;
}

//后台管理列表生成
//商城
function getstoreFrames()
{
    global $_W;
    $frames = array();
    $shop = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
    if (checkstr($_W['user']['perms'], 'copyright_all') ||
        checkstr($_W['user']['perms'], 'notice_all') ||
        checkstr($_W['user']['perms'], 'adv_all') ||
        checkstr($_W['user']['perms'], 'print_all')
    ) {
        $frames['store']['title'] = '<i class="fa fa-gear"></i>&nbsp;&nbsp; 商城设置';
        $frames['store']['items'] = array();

        if (checkstr($_W['user']['perms'], 'copyright_all') && $_W['user']['merchant_id'] == 0) {
            $frames['store']['items']['setting']['url'] = web_url('store/setting/copyright');
            $frames['store']['items']['setting']['title'] = '参数设置';
            $frames['store']['items']['setting']['actions'] = array();
            $frames['store']['items']['setting']['active'] = '';
        }


           if (checkstr($_W['user']['perms'], 'copyright_all') && $_W['user']['merchant_id'] == 0&&$shop['vip']>=1) {
               $frames['store']['items']['mofang']['url'] = web_url('store/mofang');
               $frames['store']['items']['mofang']['title'] = '商品魔方';
               $frames['store']['items']['mofang']['actions'] = array();
               $frames['store']['items']['mofang']['active'] = '';
           }




           if (checkstr($_W['user']['perms'], 'copyright_all') && $_W['user']['merchant_id'] == 0&&$shop['vip']>=1) {
               $frames['store']['items']['banner']['url'] = web_url('store/banner');
               $frames['store']['items']['banner']['title'] = '广告';
               $frames['store']['items']['banner']['actions'] = array();
               $frames['store']['items']['banner']['active'] = '';
           }
        if (checkstr($_W['user']['perms'], 'notice_all') && $_W['user']['merchant_id'] == 0  && $shop['vip'] >= 1) {
            $frames['store']['items']['inc_lists']['url'] = web_url('store/inc_lists');
            $frames['store']['items']['inc_lists']['title'] = '列表';
            $frames['store']['items']['inc_lists']['actions'] = array();
            $frames['store']['items']['inc_lists']['active'] = '';
        }


        if (checkstr($_W['user']['perms'], 'notice_all') && $_W['user']['merchant_id'] == 0) {
            $frames['store']['items']['notice']['url'] = web_url('store/notice');
            $frames['store']['items']['notice']['title'] = '模板消息';
            $frames['store']['items']['notice']['actions'] = array();
            $frames['store']['items']['notice']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'adv_all')) {
            $frames['store']['items']['adv']['url'] = web_url('store/adv');
            $frames['store']['items']['adv']['title'] = '首页轮播';
            $frames['store']['items']['adv']['actions'] = array();
            $frames['store']['items']['adv']['active'] = '';
        }
        if (checkstr($_W['user']['perms'], 'notes')) {
            $frames['store']['items']['notes']['url'] = web_url('store/notes');
            $frames['store']['items']['notes']['title'] = '公告设置';
            $frames['store']['items']['notes']['actions'] = array();
            $frames['store']['items']['notes']['active'] = '';
        }
        if (checkstr($_W['user']['perms'], 'print_all')) {
            $frames['store']['items']['print']['url'] = web_url('store/print');
            $frames['store']['items']['print']['title'] = '小票打印设置';
            $frames['store']['items']['print']['actions'] = array();
            $frames['store']['items']['print']['active'] = '';
        }
    }

    if (checkstr($_W['user']['perms'], 'autoaddress_all') ||
        checkstr($_W['user']['perms'], 'deliverylist_all') ||
        checkstr($_W['user']['perms'], 'bdelete_all') ||
        checkstr($_W['user']['perms'], 'selflogistics_all') ||
        checkstr($_W['user']['perms'], 'sendtime_all')
    ) {
        $frames['delivery']['title'] = '<i class="fa fa-truck"></i>&nbsp;&nbsp; 配送设置';
        $frames['delivery']['items'] = array();

        if (checkstr($_W['user']['perms'], 'autoaddress_all') && $_W['user']['merchant_id'] == 0) {
            $frames['delivery']['items']['autoaddress']['url'] = web_url('store/autoaddress');
            $frames['delivery']['items']['autoaddress']['title'] = '自定义区域';
            $frames['delivery']['items']['autoaddress']['actions'] = array();
            $frames['delivery']['items']['autoaddress']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'deliverylist_all')) {
            $frames['delivery']['items']['deliverylist']['url'] = web_url('store/deliverylist');
            $frames['delivery']['items']['deliverylist']['title'] = '运费模板';
            $frames['delivery']['items']['deliverylist']['actions'] = array();
            $frames['delivery']['items']['deliverylist']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'bdelete_all')) {
            $frames['delivery']['items']['pickup']['url'] = web_url('store/bdelete');
            $frames['delivery']['items']['pickup']['title'] = '自提设置';
            $frames['delivery']['items']['pickup']['actions'] = array();
            $frames['delivery']['items']['pickup']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'selflogistics_all')) {
            $frames['delivery']['items']['selflogistics']['url'] = web_url('store/selflogistics');
            $frames['delivery']['items']['selflogistics']['title'] = '送货上门';
            $frames['delivery']['items']['selflogistics']['actions'] = array();
            $frames['delivery']['items']['selflogistics']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'sendtime_all')) {
            $frames['delivery']['items']['sendtime']['url'] = web_url('store/sendtime');
            $frames['delivery']['items']['sendtime']['title'] = '送货时间';
            $frames['delivery']['items']['sendtime']['actions'] = array();
            $frames['delivery']['items']['sendtime']['active'] = '';
        }
    }

    if (checkstr($_W['user']['perms'], 'copyrights_all') && $_W['user']['merchant_id'] == 0) {
        $frames['other']['title'] = '<i class="fa fa-truck"></i>&nbsp;&nbsp; 其他设置';
        $frames['other']['items'] = array();

        $frames['other']['items']['copyright']['url'] = web_url('store/copyright');
        $frames['other']['items']['copyright']['title'] = '满包邮';
        $frames['other']['items']['copyright']['actions'] = array();
        $frames['other']['items']['copyright']['active'] = '';

        $frames['other']['items']['copyright']['url'] = web_url('store/copyright');
        $frames['other']['items']['copyright']['title'] = '去版权';
        $frames['other']['items']['copyright']['actions'] = array();
        $frames['other']['items']['copyright']['active'] = '';
    }

    $shop = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
//    die(json_encode(array('shop' => $shop)));

    if ((checkstr($_W['user']['perms'], 'user.base') ||
            checkstr($_W['user']['perms'], 'user.edit') ||
            checkstr($_W['user']['perms'], 'user.list'))
        && $shop['is_merchant'] == 1
    ) {
        $frames['perm']['title'] = '<i class="fa fa-truck"></i>&nbsp;&nbsp; 多商户管理';
        $frames['perm']['items'] = array();

        if (checkstr($_W['user']['perms'], 'user.base') && $_W['user']['merchant_id'] == 0 && $shop['is_merchant'] == 1) {
            $frames['perm']['items']['merchant_base']['url'] = web_url('store/merchant_base');
            $frames['perm']['items']['merchant_base']['title'] = '多商户基本设置';
            $frames['perm']['items']['merchant_base']['actions'] = array();
            $frames['perm']['items']['merchant_base']['active'] = '';
        }

        $role = pdo_fetch("select role from " . tablename('uni_account_users') . " where uniacid = '{$_W['uniacid']}' and uid = '{$_W['user']['uid']}'");
        if ($_W['isfounder']) {
            $frames['perm']['items']['user']['url'] = web_url('store/user');
            $frames['perm']['items']['user']['title'] = '新增商家';
            $frames['perm']['items']['user']['actions'] = array();
            $frames['perm']['items']['user']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'user.list') && $shop['is_merchant'] == 1) {
            $frames['perm']['items']['userList']['url'] = web_url('store/userList');
            $frames['perm']['items']['userList']['title'] = '商家列表';
            $frames['perm']['items']['userList']['actions'] = array();
            $frames['perm']['items']['userList']['active'] = '';
        }

        if ($_W['user']['merchant_id'] > 0) {
            $frames['perm']['items']['driver']['url'] = web_url('store/driver');
            $frames['perm']['items']['driver']['title'] = '商城配送方式';
            $frames['perm']['items']['driver']['actions'] = array();
            $frames['perm']['items']['driver']['active'] = '';
        }


    }

    $user = pdo_fetch("select role from " . tablename('uni_account_users') . " where uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
    if ((checkstr($_W['user']['perms'], 'user.accountList') ||
            checkstr($_W['user']['perms'], 'user.accountALLList'))
        && $shop['vip'] >= 1
        && $_W['user']['merchant_id'] == 0
        && $user['role'] == 'owner'
    ) {
        $frames['user']['title'] = '<i class="fa fa-truck"></i>&nbsp;&nbsp; 权限管理';
        $frames['user']['items'] = array();

        if (checkstr($_W['user']['perms'], 'user.accountList') && $shop['vip'] >= 1 && $_W['user']['merchant_id'] == 0 && $user['role'] == 'owner') {
            $frames['user']['items']['user_accountList']['url'] = web_url('store/user_accountList');
            $frames['user']['items']['user_accountList']['title'] = '添加操作员';
            $frames['user']['items']['user_accountList']['actions'] = array();
            $frames['user']['items']['user_accountList']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'user.accountALLList') && $shop['vip'] >= 1 && $_W['user']['merchant_id'] == 0 && $user['role'] == 'owner') {
            $frames['user']['items']['user_accountALLList']['url'] = web_url('store/user_accountALLList');
            $frames['user']['items']['user_accountALLList']['title'] = '操作员列表';
            $frames['user']['items']['user_accountALLList']['actions'] = array();
            $frames['user']['items']['user_accountALLList']['active'] = '';
        }
    }

    if ((checkstr($_W['user']['perms'], 'online_set_all') ||
            checkstr($_W['user']['perms'], 'online_tpl_all') ||
            checkstr($_W['user']['perms'], 'online_dis_all') ||
            checkstr($_W['user']['perms'], 'online_kf_all') ||
            checkstr($_W['user']['perms'], 'online_chat_all'))
        && $shop['vip'] >= 1
    ) {
        $frames['online']['title'] = '<i class="fa fa-truck"></i>&nbsp;&nbsp; 在线客服';
        $frames['online']['items'] = array();
        //在线客服

        if (checkstr($_W['user']['perms'], 'online_set_all') && $_W['user']['merchant_id'] == 0) {
            $frames['online']['items']['online_service']['url'] = web_url('store/online_service');
            $frames['online']['items']['online_service']['title'] = '客服设置';
            $frames['online']['items']['online_service']['actions'] = array();
            $frames['online']['items']['online_service']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'online_tpl_all') && $_W['user']['merchant_id'] == 0) {
            //客服模板消息
            $frames['online']['items']['online_service2']['url'] = web_url('store/online_service/doWebTpllist');
            $frames['online']['items']['online_service2']['title'] = '客服消息模板管理';
            $frames['online']['items']['online_service2']['actions'] = array();
            $frames['online']['items']['online_service2']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'online_dis_all') && $_W['user']['merchant_id'] == 0) {
            //客服组管理
            $frames['online']['items']['online_service3']['url'] = web_url('store/online_service/display');
            $frames['online']['items']['online_service3']['title'] = '客服组管理';
            $frames['online']['items']['online_service3']['actions'] = array();
            $frames['online']['items']['online_service3']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'online_kf_all')) {
            //客服管理
            $frames['online']['items']['online_service4']['url'] = web_url('store/online_service/kfdisplay');
            $frames['online']['items']['online_service4']['title'] = '客服管理';
            $frames['online']['items']['online_service4']['actions'] = array();
            $frames['online']['items']['online_service4']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'online_chat_all')) {
            //聊天记录
            $frames['online']['items']['online_service0']['url'] = web_url('store/online_service/doWebChatlist');
            $frames['online']['items']['online_service0']['title'] = '聊天记录';
            $frames['online']['items']['online_service0']['actions'] = array();
            $frames['online']['items']['online_service0']['active'] = '';
        }
    }
    /*
    $frames['store']['items']['setting0']['url'] = web_url('store/delivery/display0');
    $frames['store']['items']['setting0']['title'] = '权限管理';
    $frames['store']['items']['setting0']['actions'] = array();
    $frames['store']['items']['setting0']['active'] = '';


    $frames['merchant']['title'] = '<i class="fa fa-archive"></i>&nbsp;&nbsp; 商家管理';
    $frames['merchant']['items'] = array();

    $frames['merchant']['items']['merchantlist']['url'] = web_url('application/merchant/display',array('status' => '1'));
    $frames['merchant']['items']['merchantlist']['title'] = '商家列表';
    $frames['merchant']['items']['merchantlist']['actions'] = array('op','display');
    $frames['merchant']['items']['merchantlist']['active'] = '';

    $frames['merchant']['items']['merchantcenter']['url'] = web_url('application/merchant/account_display',array('status' => '1'));
    $frames['merchant']['items']['merchantcenter']['title'] = '商家中心';
    $frames['merchant']['items']['merchantcenter']['actions'] = array();
    $frames['merchant']['items']['merchantcenter']['active'] = '';	*/
//	$frames['store']['items']['display2']['url'] = web_url('store/address/display');
//	$frames['store']['items']['display2']['title'] = '地区管理';
//	$frames['store']['items']['display2']['actions'] = array();
//	$frames['store']['items']['display2']['active'] = '';
//
//	$frames['page']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 页面管理';
//	$frames['page']['items'] = array();
//	$frames['page']['items']['home']['url'] = web_url('store/home/display');
//	$frames['page']['items']['home']['title'] = '商城主页';
//	$frames['page']['items']['home']['actions'] = array();
//	$frames['page']['items']['home']['active'] = '';

//	$frames['page']['items']['detail']['url'] = web_url('store/detail/display');
//	$frames['page']['items']['detail']['title'] = '商品详情';
//	$frames['page']['items']['detail']['actions'] = array();
//	$frames['page']['items']['detail']['active'] = '';

    return $frames;
}

//商品
function getgoodsFrames()
{
    global $_W;
    $frames = array();

    if (checkstr($_W['user']['perms'], 'category_all') &&
        checkstr($_W['user']['perms'], 'item_all')
    ) {
        $frames['goods']['title'] = '<i class="fa fa-gift"></i>&nbsp;&nbsp; 商品管理';
        $frames['goods']['items'] = array();

        if (checkstr($_W['user']['perms'], 'category_all') && $_W['user']['merchant_id'] == 0) {
            $frames['goods']['items']['category']['url'] = web_url('goods/category');
            $frames['goods']['items']['category']['title'] = '商品分类';
            $frames['goods']['items']['category']['actions'] = array('ac', 'category');
            $frames['goods']['items']['category']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'item_all')) {
            $frames['goods']['items']['post']['url'] = web_url('goods/goods/post', array('selltype' => '100'));
            $frames['goods']['items']['post']['title'] = '发布商品';
            $frames['goods']['items']['post']['actions'] = array();
            $frames['goods']['items']['post']['active'] = '';

            $frames['goods']['items']['display']['url'] = web_url('goods/goods/display', array('status' => '1'));
            $frames['goods']['items']['display']['title'] = '商品列表';
            $frames['goods']['items']['display']['actions'] = array();
            $frames['goods']['items']['display']['active'] = '';
        }


        if (checkstr($_W['user']['perms'], 'activity_all') && $_W['user']['merchant_id'] == 0) {
            $frames['member2']['title'] = '<i class="fa fa-tags"></i>&nbsp;&nbsp; 卡券管理';
            $frames['member2']['items'] = array();

            $frames['member2']['items']['activity']['url'] = web_url('goods/activity');
            $frames['member2']['items']['activity']['title'] = '优惠券';
            $frames['member2']['items']['activity']['actions'] = array('ac', 'activity');
            $frames['member2']['items']['activity']['active'] = '';

            $frames['member2']['items']['group']['url'] = web_url('goods/coupon');
            $frames['member2']['items']['group']['title'] = '组优惠券';
            $frames['member2']['items']['group']['actions'] = array('ac', 'coupon');
            $frames['member2']['items']['group']['active'] = '';

            /*
                $frames['member2']['items']['display2']['url'] = web_url('goods/member/display');
                $frames['member2']['items']['display2']['title'] = '折扣券';
                $frames['member2']['items']['display2']['actions'] = array();
                $frames['member2']['items']['display2']['active'] = '';

                $frames['member2']['items']['display3']['url'] = web_url('goods/member/display');
                $frames['member2']['items']['display3']['title'] = '微信卡券';
                $frames['member2']['items']['display3']['actions'] = array();
                $frames['member2']['items']['display3']['active'] = '';

                $frames['member2']['items']['display4']['url'] = web_url('goods/member/display');
                $frames['member2']['items']['display4']['title'] = '卡券核销';
                $frames['member2']['items']['display4']['actions'] = array();
                $frames['member2']['items']['display4']['active'] = '';
                */
        }
    }

    $shop = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
    if ($_W['user']['merchant_id'] > 0) {
        $mer = pdo_fetch("select taobao from " . tablename('tg_merchant') . " where uniacid = :uniacid and id = '{$_W['user']['merchant_id']}'", array(':uniacid' => $_W['uniacid']));
    }
    if ($_W['user']['merchant_id'] == 0 && $shop['is_merchant'] == 1) {
        $frames['goods']['items']['goods_check']['url'] = web_url('goods/goods_check');
        $frames['goods']['items']['goods_check']['title'] = '多商户商品管理';
        $frames['goods']['items']['goods_check']['actions'] = array();
        $frames['goods']['items']['goods_check']['active'] = '';
    }


    if ($shop['vip'] >= 1) {
        $frames['goods']['items']['kanjia']['url'] = web_url('goods/kanjia/list');
        $frames['goods']['items']['kanjia']['title'] = '砍价活动';
        $frames['goods']['items']['kanjia']['actions'] = array();
        $frames['goods']['items']['kanjia']['active'] = 'kanjia';
    }


    if ($shop['is_merchant'] == 1) {
        $frames['goods']['items']['goods_activities']['url'] = web_url('goods/goods_activities');
        $frames['goods']['items']['goods_activities']['title'] = '活动报名';
        $frames['goods']['items']['goods_activities']['actions'] = array();
        $frames['goods']['items']['goods_activities']['active'] = '';
    }
    if ($_W['uniacid'] == 33 || $_W['isfounder'] == true) {
        $frames['goods']['items']['video_check']['url'] = web_url('goods/goods_video');
        $frames['goods']['items']['video_check']['title'] = '审核视频';
        $frames['goods']['items']['video_check']['actions'] = array();
        $frames['goods']['items']['video_check']['active'] = '';
    }
    if ($_W['user']['merchant_id'] == 0 ) {
        $frames['goods']['items']['goods_video']['url'] = web_url('goods/upload_video');
        $frames['goods']['items']['goods_video']['title'] = '上传视频';
        $frames['goods']['items']['goods_video']['actions'] = array();
        $frames['goods']['items']['goods_video']['active'] = '';
    }
    $frames['goods']['items']['shop']['url'] = web_url('goods/video_shop');
    $frames['goods']['items']['shop']['title'] = '视频购买';
    $frames['goods']['items']['shop']['actions'] = array();
    $frames['goods']['items']['shop']['active'] = '';

    if (($shop['vip'] >= 1 && $_W['user']['merchant_id'] == 0) || ($_W['user']['merchant_id'] > 0 && $mer['taobao'] == 1)) {
        $frames['goods']['items']['taobao']['url'] = web_url('goods/taobao/display');
        $frames['goods']['items']['taobao']['title'] = '淘宝商品采集';
        $frames['goods']['items']['taobao']['actions'] = array('ac', 'taobao');
        $frames['goods']['items']['taobao']['active'] = '';
    }
    if ( $_W['user']['merchant_id'] == 0) {
        $frames['jxdp_goods']['title'] = '<i class="fa fa-gift"></i>&nbsp;&nbsp; 极限单品商品管理';
        $frames['jxdp_goods']['items'] = array();

        $frames['jxdp_goods']['items']['fetch2']['url'] = web_url('goods/jxdp');
        $frames['jxdp_goods']['items']['fetch2']['title'] = '商品列表';
        $frames['jxdp_goods']['items']['fetch2']['actions'] = array();
        $frames['jxdp_goods']['items']['fetch2']['active'] = '';
    }
//	$frames['other']['items']['evaluate']['url'] = web_url('goods/evaluate/display');
//	$frames['other']['items']['evaluate']['title'] = '商品评价';
//	$frames['other']['items']['evaluate']['actions'] = array();
//	$frames['other']['items']['evaluate']['active'] = '';

    return $frames;
}

//订单
function getorderFrames()
{
    global $_W;
    $frames = array();
    $shop = pdo_fetch("select * from " . tablename('account_wechats') . " where uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
    if (checkstr($_W['user']['perms'], 'received_all')) {
        $frames['order']['title'] = '<i class="fa fa-list"></i>&nbsp;&nbsp; 订单管理';
        $frames['order']['items'] = array();
        $frames['order']['items']['summary']['url'] = web_url('order/order/summary');
        $frames['order']['items']['summary']['title'] = '订单概况';
        $frames['order']['items']['summary']['actions'] = array();
        $frames['order']['items']['summary']['active'] = '';

        $frames['order']['items']['all']['url'] = web_url('order/order/received', array('dispatchtype' => 0));
        $frames['order']['items']['all']['title'] = '所有订单';
        $frames['order']['items']['all']['actions'] = array();
        $frames['order']['items']['all']['active'] = '';

        $frames['order']['items']['received']['url'] = web_url('order/order/received', array('dispatchtype' => 2));
        $frames['order']['items']['received']['title'] = '快递订单';
        $frames['order']['items']['received']['actions'] = array();
        $frames['order']['items']['received']['active'] = '';

        $frames['order']['items']['fetch']['url'] = web_url('order/order/received', array('dispatchtype' => 3));
        $frames['order']['items']['fetch']['title'] = '自提订单';
        $frames['order']['items']['fetch']['actions'] = array();
        $frames['order']['items']['fetch']['active'] = '';

        $frames['order']['items']['fetch1']['url'] = web_url('order/order/received', array('dispatchtype' => 1));
        $frames['order']['items']['fetch1']['title'] = '送货上门';
        $frames['order']['items']['fetch1']['actions'] = array();
        $frames['order']['items']['fetch1']['active'] = '';

        $frames['order']['items']['luck']['url'] = web_url('order/order/received', array('dispatchtype' => -1));
        $frames['order']['items']['luck']['title'] = '抽奖订单';
        $frames['order']['items']['luck']['actions'] = array();
        $frames['order']['items']['luck']['active'] = '';

        $frames['order']['items']['wholesale']['url'] = web_url('order/order/received', array('is_wholesale' => 1));
        $frames['order']['items']['wholesale']['title'] = '批发订单';
        $frames['order']['items']['wholesale']['actions'] = array();
        $frames['order']['items']['wholesale']['active'] = '';
        if ($shop['vip'] >= 1) {
            $frames['order']['items']['once_card']['url'] = web_url('order/order/received', array('is_once_card' => 1));
            $frames['order']['items']['once_card']['title'] = '次卡订单';
            $frames['order']['items']['once_card']['actions'] = array();
            $frames['order']['items']['once_card']['active'] = '';
        }
        $frames['order']['items']['fetch4']['url'] = web_url('order/fetch');
        $frames['order']['items']['fetch4']['title'] = '售后订单';
        $frames['order']['items']['fetch4']['actions'] = array();
        $frames['order']['items']['fetch4']['active'] = '';

        if ($shop['vip'] >= 1
//            && in_array($_W['uniacid'] , $_W['unicid_array'])
            && $_W['user']['merchant_id'] == 0) {
            $frames['order']['items']['fetch2']['url'] = web_url('order/jxdp', array('dispatchtype' => 2));
            $frames['order']['items']['fetch2']['title'] = '极限单品';
            $frames['order']['items']['fetch2']['actions'] = array();
            $frames['order']['items']['fetch2']['active'] = '';
        }

        /*
        $frames['order']['items']['fetch2']['url'] = web_url('order/order/received',array('dispatchtype'=>4));
        $frames['order']['items']['fetch2']['title'] = '邻购订单';
        $frames['order']['items']['fetch2']['actions'] = array();
        $frames['order']['items']['fetch2']['active'] = '';

        $frames['order']['items']['fetch3']['url'] = web_url('order/fetch/display');
        $frames['order']['items']['fetch3']['title'] = '购物车订单';
        $frames['order']['items']['fetch3']['actions'] = array();
        $frames['order']['items']['fetch3']['active'] = '';

        $frames['order']['items']['fetch4']['url'] = web_url('order/fetch/display');
        $frames['order']['items']['fetch4']['title'] = '售后订单';
        $frames['order']['items']['fetch4']['actions'] = array();
        $frames['order']['items']['fetch4']['active'] = '';*/
    }

    if (checkstr($_W['user']['perms'], 'group_all')) {
        $frames['group']['title'] = '<i class="fa fa-users"></i>&nbsp;&nbsp; 团购管理';
        $frames['group']['items'] = array();

        $frames['group']['items']['all1']['url'] = web_url('order/group/summary');
        $frames['group']['items']['all1']['title'] = '团购概况';
        $frames['group']['items']['all1']['actions'] = array();
        $frames['group']['items']['all1']['active'] = '';

        $frames['group']['items']['all']['url'] = web_url('order/group');
        $frames['group']['items']['all']['title'] = '所有团';
        $frames['group']['items']['all']['actions'] = array('groupstatus', '', 'ac', 'group');
        $frames['group']['items']['all']['active'] = '';
        /*
            $frames['group']['items']['ongoing']['url'] = web_url('order/group/all',array('groupstatus' => '3'));
            $frames['group']['items']['ongoing']['title'] = '随意团';
            $frames['group']['items']['ongoing']['actions'] = array('groupstatus','3');
            $frames['group']['items']['ongoing']['active'] = '';

            $frames['group']['items']['success']['url'] = web_url('order/group/all',array('groupstatus' => '2'));
            $frames['group']['items']['success']['title'] = '邻购团';
            $frames['group']['items']['success']['actions'] = array('groupstatus','2');
            $frames['group']['items']['success']['active'] = '';*/

        $frames['group']['items']['fail']['url'] = web_url('order/will_die');
        $frames['group']['items']['fail']['title'] = '手动成团';
        $frames['group']['items']['fail']['actions'] = array();
        $frames['group']['items']['fail']['active'] = '';
        /*
        $frames['delivery']['title'] = '<i class="fa fa-paper-plane"></i>&nbsp;&nbsp; 配送方式';
        $frames['delivery']['items'] = array();
        $frames['delivery']['items']['template']['url'] = web_url('order/delivery/display');
        $frames['delivery']['items']['template']['title'] = '运费模板';
        $frames['delivery']['items']['template']['actions'] = array('ac','delivery');
        $frames['delivery']['items']['template']['active'] = '';
        */
    }

    if (checkstr($_W['user']['perms'], 'judgment_admin_all')) {
        $frames['judgment']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 评价管理';
        $frames['judgment']['items'] = array();

        $frames['judgment']['items']['judgment_admin']['url'] = web_url('order/judgment_admin');
        $frames['judgment']['items']['judgment_admin']['title'] = '评价管理';
        $frames['judgment']['items']['judgment_admin']['actions'] = array();
        $frames['judgment']['items']['judgment_admin']['active'] = '';
    }

    if (checkstr($_W['user']['perms'], 'import_all')) {
        $frames['import']['title'] = '<i class="fa fa-truck"></i>&nbsp;&nbsp; 发货管理';
        $frames['import']['items'] = array();
        $frames['import']['items']['import']['url'] = web_url('order/import/display');
        $frames['import']['items']['import']['title'] = '批量发货';
        $frames['import']['items']['import']['actions'] = array('ac', 'import');
        $frames['import']['items']['import']['active'] = '';

        $frames['import']['items']['partsend']['url'] = web_url('order/partsend');
        $frames['import']['items']['partsend']['title'] = '拆单发货';
        $frames['import']['items']['partsend']['actions'] = array('ac', 'partsend');
        $frames['import']['items']['partsend']['active'] = '';

        $frames['import']['items']['batch_delivery']['url'] = web_url('order/batch_delivery/display');
        $frames['import']['items']['batch_delivery']['title'] = '批量派送';
        $frames['import']['items']['batch_delivery']['actions'] = array('ac', 'batch_delivery');
        $frames['import']['items']['batch_delivery']['active'] = '';
        if ($shop['vip'] == 1){
            $frames['import']['items']['pick_delivery']['url'] = web_url('order/pick_delivery/display');
        $frames['import']['items']['pick_delivery']['title'] = '批量核销';
        $frames['import']['items']['pick_delivery']['actions'] = array('ac', 'pick_delivery');
        $frames['import']['items']['pick_delivery']['active'] = '';
      }
        /*
        $frames['import']['items']['import1']['url'] = web_url('order/import/display');
        $frames['import']['items']['import1']['title'] = '自动发货';
        $frames['import']['items']['import1']['actions'] = array('ac','import');
        $frames['import']['items']['import1']['active'] = '';
        */
    }
    if (checkstr($_W['user']['perms'], 'tchorder') && $shop['vip'] >= 1) {
        $frames['import']['items']['tchorder']['url'] = web_url('order/tchorder');
        $frames['import']['items']['tchorder']['title'] = '电子面单参数设置';
        $frames['import']['items']['tchorder']['actions'] = array();
        $frames['import']['items']['tchorder']['active'] = '';
        $frames['import']['items']['tchorder_mimeograph']['url'] = web_url('order/tchorder/mimeograph');
        $frames['import']['items']['tchorder_mimeograph']['title'] = '打印电子面单';
        $frames['import']['items']['tchorder_mimeograph']['actions'] = array();
        $frames['import']['items']['tchorder_mimeograph']['active'] = '';
    }
    if (checkstr($_W['user']['perms'], 'refund_all') && $_W['user']['merchant_id'] == 0) {
        $frames['refund']['title'] = '<i class="fa fa-money"></i>&nbsp;&nbsp; 批量退款';
        $frames['refund']['items'] = array();

        if (checkstr($_W['user']['perms'], 'refund.mass')) {
            $frames['refund']['items']['refund']['url'] = web_url('order/refund/display');
            $frames['refund']['items']['refund']['title'] = '一键退款';
            $frames['refund']['items']['refund']['actions'] = array();
            $frames['refund']['items']['refund']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'refund.log')) {
            $frames['refund']['items']['refund_log']['url'] = web_url('order/refund_log');
            $frames['refund']['items']['refund_log']['title'] = '退款日志';
            $frames['refund']['items']['refund_log']['actions'] = array();
            $frames['refund']['items']['refund_log']['active'] = '';
            $frames['refund']['items']['refund_log']['append']['url'] = web_url('order/refund_log');
            $frames['refund']['items']['refund_log']['append']['title'] = '';
        }
    }
    return $frames;
}

//粉丝
function getmemberFrames()
{
    global $_W;
    $frames = array();
    $shop = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));

    if ((checkstr($_W['user']['perms'], 'fans_all') ||
            checkstr($_W['user']['perms'], 'fangroup_all'))
        && $_W['user']['merchant_id'] == 0
    ) {
        $frames['fans']['title'] = '<i class="fa fa-user"></i>&nbsp;&nbsp; 粉丝会员';
        $frames['fans']['items'] = array();

        if (checkstr($_W['user']['perms'], 'fangroup_all')) {
            $frames['fans']['items']['fangroup']['url'] = web_url('member/fangroup');
            $frames['fans']['items']['fangroup']['title'] = '粉丝分组';
            $frames['fans']['items']['fangroup']['actions'] = array();
            $frames['fans']['items']['fangroup']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'fans_all')) {
            $frames['fans']['items']['fans']['url'] = web_url('member/fans');
            $frames['fans']['items']['fans']['title'] = '粉丝管理';
            $frames['fans']['items']['fans']['actions'] = array();
            $frames['fans']['items']['fans']['active'] = '';

            if ($shop['vip'] > 0) {
                $frames['fans']['items']['blacklist']['url'] = web_url('member/blacklist');
                $frames['fans']['items']['blacklist']['title'] = '粉丝黑名单';
                $frames['fans']['items']['blacklist']['actions'] = array();
                $frames['fans']['items']['blacklist']['active'] = '';
            }

            $frames['fans']['items']['parms']['url'] = web_url('member/parms/setting');
            $frames['fans']['items']['parms']['title'] = '粉丝权益';
            $frames['fans']['items']['parms']['actions'] = array();
            $frames['fans']['items']['parms']['active'] = '';

        }

    }

    if (checkstr($_W['user']['perms'], 'member_all') && $_W['user']['merchant_id'] == 0) {

        if ($_W['uniacid'] == 2186 || $_W['uniacid'] == 53 || $_W['uniacid'] == 33) {
            $frames['fans']['items']['grade']['url'] = web_url('member/grade/setting');
            $frames['fans']['items']['grade']['title'] = '会员等级设置（易拼宝定制)';
            $frames['fans']['items']['grade']['actions'] = array();
            $frames['fans']['items']['grade']['active'] = '';
        }

        if ($shop['vip'] ==1) {
            $frames['fans']['items']['card']['url'] = web_url('member/card');
            $frames['fans']['items']['card']['title'] = '会员储值';
            $frames['fans']['items']['card']['actions'] = array();
            $frames['fans']['items']['card']['active'] = '';
        }


        $role = pdo_fetch("select role from " . tablename('uni_account_users') . " where uniacid = '{$_W['uniacid']}' and uid = '{$_W['user']['uid']}'");
        if ($_W['isfounder'] || $shop['vip'] >= 1) {
            $frames['member']['title'] = '<i class="fa fa-comments-o"></i>&nbsp;&nbsp; 群发管理';
            $frames['member']['items'] = array();
            //
            $frames['member']['items']['time']['url'] = web_url('member/time/index');
            $frames['member']['items']['time']['title'] = '消息群发';
            $frames['member']['items']['time']['actions'] = array();
            $frames['member']['items']['time']['active'] = '';



            if($shop['vip'] >0 ||$_W['uniacid']==33||$_W['uniacid']==53){
                if (checkstr($_W['user']['perms'], 'fans.sendhello')) {
                    $frames['member']['items']['sendhello']['url'] = web_url('member/sendhello');
                    $frames['member']['items']['sendhello']['title'] = '无限群发';
                    $frames['member']['items']['sendhello']['actions'] = array();
                    $frames['member']['items']['sendhello']['active'] = '';
                }
            }

            if (checkstr($_W['user']['perms'], 'fans.client')) {
                $frames['member']['items']['notice']['url'] = web_url('member/notice');
                $frames['member']['items']['notice']['title'] = '精准群发';
                $frames['member']['items']['notice']['actions'] = array();
                $frames['member']['items']['notice']['active'] = '';
            }

            $frames['member']['items']['times']['url'] = web_url('member/time/upindex');
            $frames['member']['items']['times']['title'] = '发送记录';
            $frames['member']['items']['times']['actions'] = array();
            $frames['member']['items']['times']['active'] = '';

            if (checkstr($_W['user']['perms'], 'smsbuy_all') && $_W['user']['merchant_id'] == 0) {
                $frames['member']['items']['smsbuy']['url'] = web_url('member/smsbuy');
                $frames['member']['items']['smsbuy']['title'] = '短信群发';
                $frames['member']['items']['smsbuy']['actions'] = array();
                $frames['member']['items']['smsbuy']['active'] = '';
            }
        }
    }

    if ($shop['vip'] == 1) {
        $frames['member1']['title'] = '<i class="fa fa-shopping-cart"></i>&nbsp;&nbsp; 积分商城';
        $frames['member1']['items'] = array();

        $frames['member1']['items']['score']['url'] = web_url('member/score');
        $frames['member1']['items']['score']['title'] = '积分商城';
        $frames['member1']['items']['score']['actions'] = array();
        $frames['member1']['items']['score']['active'] = '';
    }

    if ($shop['vip'] == 1) {
        $frames['member9']['title'] = '<i class="fa fa-cubes"></i>&nbsp;&nbsp; 批发管理';
        $frames['member9']['items'] = array();

        $frames['member9']['items']['commander']['url'] = web_url('member/wholesale');
        $frames['member9']['items']['commander']['title'] = '批发会员';
        $frames['member9']['items']['commander']['actions'] = array();
        $frames['member9']['items']['commander']['active'] = '';
    }


    if (checkstr($_W['user']['perms'], 'partjob_all') && $_W['user']['merchant_id'] == 0) {
        $frames['member3']['title'] = '<i class="fa fa-group"></i>&nbsp;&nbsp; 全民兼职';
        $frames['member3']['items'] = array();

        $frames['member3']['items']['partjob']['url'] = web_url('member/partjob');
        $frames['member3']['items']['partjob']['title'] = '兼职管理';
        $frames['member3']['items']['partjob']['actions'] = array();
        $frames['member3']['items']['partjob']['active'] = '';
        //$frames['member3']['items']['setting']['append']['url'] = web_url('member/member/setting');

        if (checkstr($_W['user']['perms'], 'partjob.cash')) {
            wl_load()->model('functions');
            $checkfunction = checkfunc(8167);

            if ($checkfunction['status']) {
                $frames['member3']['items']['cash']['url'] = web_url('member/cash');
                $frames['member3']['items']['cash']['title'] = '提现管理';
                $frames['member3']['items']['cash']['actions'] = array();
                $frames['member3']['items']['cash']['active'] = '';


            }
        }

        if (checkstr($_W['user']['perms'], 'leader.add') ||
            checkstr($_W['user']['perms'], 'leader.leader') ||
            checkstr($_W['user']['perms'], 'leader.delete')
        ) {
            $frames['member3']['items']['group']['url'] = web_url('member/group');
            $frames['member3']['items']['group']['title'] = '分组管理';
            $frames['member3']['items']['group']['actions'] = array();
            $frames['member3']['items']['group']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'leader.check') ||
            checkstr($_W['user']['perms'], 'leader.play') ||
            checkstr($_W['user']['perms'], 'leader.list')
        ) {
            $frames['member3']['items']['commission']['url'] = web_url('member/commission');
            $frames['member3']['items']['commission']['title'] = '提成管理';
            $frames['member3']['items']['commission']['actions'] = array();
            $frames['member3']['items']['commission']['active'] = '';
        }
    }

    if ($shop['vip'] > 0) {
        $frames['member4']['title'] = '<i class="fa fa-trophy"></i>&nbsp;&nbsp; 团长佣金';
        $frames['member4']['items'] = array();

        $frames['member4']['items']['commander']['url'] = web_url('member/commander');
        $frames['member4']['items']['commander']['title'] = '团长管理';
        $frames['member4']['items']['commander']['actions'] = array();
        $frames['member4']['items']['commander']['active'] = '';

        //$frames['member3']['items']['setting']['append']['url'] = web_url('member/member/setting');

        if (checkstr($_W['user']['perms'], 'partjob.cash')) {
            $frames['member4']['items']['withdraw']['url'] = web_url('member/commander/withdraw');
            $frames['member4']['items']['withdraw']['title'] = '提现管理';
            $frames['member4']['items']['withdraw']['actions'] = array();
            $frames['member4']['items']['withdraw']['active'] = '';
        }


    }

    if ($shop['vip'] == 1) {
        $frames['member5']['title'] = '<i class="fa fa-cny"></i>&nbsp;&nbsp; 核销佣金';
        $frames['member5']['items'] = array();

        $frames['member5']['items']['saler']['url'] = web_url('member/saler');
        $frames['member5']['items']['saler']['title'] = '核销员管理';
        $frames['member5']['items']['saler']['actions'] = array();
        $frames['member5']['items']['saler']['active'] = '';

        if (checkstr($_W['user']['perms'], 'partjob.cash')) {
            $frames['member5']['items']['saler_withdraw']['url'] = web_url('member/saler/withdraw');
            $frames['member5']['items']['saler_withdraw']['title'] = '核销员提现管理';
            $frames['member5']['items']['saler_withdraw']['actions'] = array();
            $frames['member5']['items']['saler_withdraw']['active'] = '';
        }

        $frames['member5']['items']['delivery']['url'] = web_url('member/delivery');
        $frames['member5']['items']['delivery']['title'] = '派送员管理';
        $frames['member5']['items']['delivery']['actions'] = array();
        $frames['member5']['items']['delivery']['active'] = '';

        if (checkstr($_W['user']['perms'], 'partjob.cash')) {
            $frames['member5']['items']['delivery_withdraw']['url'] = web_url('member/delivery/withdraw');
            $frames['member5']['items']['delivery_withdraw']['title'] = '派送员提现管理';
            $frames['member5']['items']['delivery_withdraw']['actions'] = array();
            $frames['member5']['items']['delivery_withdraw']['active'] = '';
        }

    }

    return $frames;
}

//管理应用
function getapplicationFrames()
{
    global $_W;
    $frames = array();
    $frames['application']['title'] = '<i class="fa fa-cloud"></i>&nbsp;&nbsp; 管理应用';
    $frames['application']['items'] = array();

    $frames['application']['items']['list']['url'] = web_url('application/plugins/list');
    $frames['application']['items']['list']['title'] = '应用插件';
    $frames['application']['items']['list']['actions'] = array();
    $frames['application']['items']['list']['active'] = '';
    $frames['application']['items']['list']['append']['url'] = web_url('application/plugins/list');
    $frames['application']['items']['list']['append']['title'] = '<i class="fa fa-plus"></i>';

    $frames['base']['title'] = '<i class="fa fa-inbox"></i>&nbsp;&nbsp; 基础应用';
    $frames['base']['items'] = array();

    $frames['base']['items']['bdelete']['url'] = web_url('application/bdelete/hx_entry');
    $frames['base']['items']['bdelete']['title'] = '线下核销';
    $frames['base']['items']['bdelete']['actions'] = array('ac', 'bdelete');
    $frames['base']['items']['bdelete']['active'] = '';

    $frames['base']['items']['ladder']['url'] = web_url('application/ladder/list');
    $frames['base']['items']['ladder']['title'] = '阶梯团';
    $frames['base']['items']['ladder']['actions'] = array('ac', 'ladder');
    $frames['base']['items']['ladder']['active'] = '';


    return $frames;
}

//大数据
function getdataFrames()
{
    global $_W;
    $frames = array();
    /*$frames['data']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 管理数据';
    $frames['data']['items'] = array();

    $frames['data']['items']['home_data']['url'] = web_url('data/home_data');
    $frames['data']['items']['home_data']['title'] = '数据概况';
    $frames['data']['items']['home_data']['actions'] = array();
    $frames['data']['items']['home_data']['active'] = '';
    $frames['data']['items']['home_data']['append']['url'] = web_url('data/home_data');
    $frames['data']['items']['home_data']['append']['title'] = '';

    $frames['data']['items']['goods_data']['url'] = web_url('data/goods_data');
    $frames['data']['items']['goods_data']['title'] = '商品Top10';
    $frames['data']['items']['goods_data']['actions'] = array();
    $frames['data']['items']['goods_data']['active'] = '';
    $frames['data']['items']['goods_data']['append']['url'] = web_url('data/goods_data');
    $frames['data']['items']['goods_data']['append']['title'] = '';

    $frames['data']['items']['order_data']['url'] = web_url('data/order_data');
    $frames['data']['items']['order_data']['title'] = '订单统计';
    $frames['data']['items']['order_data']['actions'] = array();
    $frames['data']['items']['order_data']['active'] = '';
    $frames['data']['items']['order_data']['append']['url'] = web_url('data/order_data');
    $frames['data']['items']['order_data']['append']['title'] = '';

    $frames['data']['items']['sorder_data']['url'] = web_url('data/sorder_data');
    $frames['data']['items']['sorder_data']['title'] = '商品销售统计';
    $frames['data']['items']['sorder_data']['actions'] = array();
    $frames['data']['items']['sorder_data']['active'] = '';
    $frames['data']['items']['sorder_data']['append']['url'] = web_url('data/sorder_data');
    $frames['data']['items']['sorder_data']['append']['title'] = '';

    $frames['log']['title'] = '<i class="fa fa-history"></i>&nbsp;&nbsp; 日志';
    $frames['log']['items'] = array();

    $frames['log']['items']['system_log']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log']['title'] = '系统日志';
    $frames['log']['items']['system_log']['actions'] = array();
    $frames['log']['items']['system_log']['active'] = '';
    $frames['log']['items']['system_log']['append']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log']['append']['title'] = '';

    $frames['log']['title'] = '<i class="fa fa-history"></i>&nbsp;&nbsp; 回复统计';
    $frames['log']['items'] = array();

    $frames['log']['items']['system_log']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log']['title'] = '聊天记录';
    $frames['log']['items']['system_log']['actions'] = array();
    $frames['log']['items']['system_log']['active'] = '';
    $frames['log']['items']['system_log']['append']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log']['append']['title'] = '';

    $frames['log']['items']['system_log1']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log1']['title'] = '回复规则';
    $frames['log']['items']['system_log1']['actions'] = array();
    $frames['log']['items']['system_log1']['active'] = '';
    $frames['log']['items']['system_log1']['append']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log1']['append']['title'] = '';

    $frames['log']['items']['system_log2']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log2']['title'] = '关键字命中';
    $frames['log']['items']['system_log2']['actions'] = array();
    $frames['log']['items']['system_log2']['active'] = '';
    $frames['log']['items']['system_log2']['append']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log2']['append']['title'] = '';

    $frames['log']['items']['system_log3']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log3']['title'] = '参数';
    $frames['log']['items']['system_log3']['actions'] = array();
    $frames['log']['items']['system_log3']['active'] = '';
    $frames['log']['items']['system_log3']['append']['url'] = web_url('data/system_log');
    $frames['log']['items']['system_log3']['append']['title'] = '';
*/
    if (checkstr($_W['user']['perms'], 'data.sjgk_data') ||
        checkstr($_W['user']['perms'], 'data.scgk_data') ||
        checkstr($_W['user']['perms'], 'data.sczhl_count_data') ||
        checkstr($_W['user']['perms'], 'data.order_count_data') ||
        checkstr($_W['user']['perms'], 'data.order_area_data')
    ) {
        $frames['sc']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 商城概况';
        $frames['sc']['items'] = array();

        if (checkstr($_W['user']['perms'], 'data.sjgk_data')) {
            $frames['sc']['items']['sjgk_data']['url'] = web_url('data/sjgk_data');
            $frames['sc']['items']['sjgk_data']['title'] = '数据概况';
            $frames['sc']['items']['sjgk_data']['actions'] = array();
            $frames['sc']['items']['sjgk_data']['active'] = '';
        }

            if (checkstr($_W['user']['perms'], 'data.new_since_data')) {
                $frames['sc']['items']['new_since_data']['url'] = web_url('data/new_since_data');
                $frames['sc']['items']['new_since_data']['title'] = '自提商品日报表';
                $frames['sc']['items']['new_since_data']['actions'] = array();
                $frames['sc']['items']['new_since_data']['active'] = '';
            }
     
        if (checkstr($_W['user']['perms'], 'data.since_data')) {
            $frames['sc']['items']['since_data']['url'] = web_url('data/since_data');
            $frames['sc']['items']['since_data']['title'] = '自提点日报表';
            $frames['sc']['items']['since_data']['actions'] = array();
            $frames['sc']['items']['since_data']['active'] = '';
        }
        if (checkstr($_W['user']['perms'], 'data.since_goods_data')) {
            $frames['sc']['items']['since_goods_data']['url'] = web_url('data/since_goods_data');
            $frames['sc']['items']['since_goods_data']['title'] = '自提点商品分析';
            $frames['sc']['items']['since_goods_data']['actions'] = array();
            $frames['sc']['items']['since_goods_data']['active'] = '';
        }
        if (checkstr($_W['user']['perms'], 'data.scgk_data')) {
            $frames['sc']['items']['scgk_data']['url'] = web_url('data/scgk_data');
            $frames['sc']['items']['scgk_data']['title'] = '商城访问趋势';
            $frames['sc']['items']['scgk_data']['actions'] = array();
            $frames['sc']['items']['scgk_data']['active'] = '';
        }

//	$frames['sc']['items']['sczhl_data']['url'] = web_url('data/sczhl_data');
//	$frames['sc']['items']['sczhl_data']['title'] = '商城转化率趋势';
//	$frames['sc']['items']['sczhl_data']['actions'] = array();
//	$frames['sc']['items']['sczhl_data']['active'] = '';

        if (checkstr($_W['user']['perms'], 'data.sczhl_count_data')) {
            $frames['sc']['items']['sczhl_count_data']['url'] = web_url('data/sczhl_count_data');
            $frames['sc']['items']['sczhl_count_data']['title'] = '商城转化率统计';
            $frames['sc']['items']['sczhl_count_data']['actions'] = array();
            $frames['sc']['items']['sczhl_count_data']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'data.order_count_data')) {
            $frames['sc']['items']['order_count_data']['url'] = web_url('data/order_count_data');
            $frames['sc']['items']['order_count_data']['title'] = '商城指标趋势';
            $frames['sc']['items']['order_count_data']['actions'] = array();
            $frames['sc']['items']['order_count_data']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'data.order_area_data')) {
            $frames['sc']['items']['order_area']['url'] = web_url('data/order_area_data');
            $frames['sc']['items']['order_area']['title'] = '用户区域分布';
            $frames['sc']['items']['order_area']['actions'] = array();
            $frames['sc']['items']['order_area']['active'] = '';
        }
        if (checkstr($_W['user']['perms'], 'data.annual_data')) {
            $frames['sc']['items']['annual_data']['url'] = web_url('data/annual_data');
            $frames['sc']['items']['annual_data']['title'] = '年度数据报表';
            $frames['sc']['items']['annual_data']['actions'] = array();
            $frames['sc']['items']['annual_data']['active'] = '';
        }
        if (checkstr($_W['user']['perms'], 'data.commander_data')) {
            $frames['sc']['items']['commander_data']['url'] = web_url('data/commander_data');
            $frames['sc']['items']['commander_data']['title'] = '团长佣金报表';
            $frames['sc']['items']['commander_data']['actions'] = array();
            $frames['sc']['items']['commander_data']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'data.puv_data')) {
            $frames['sc']['items']['puv_data']['url'] = web_url('data/puv_data');
            $frames['sc']['items']['puv_data']['title'] = '用户访问时段表';
            $frames['sc']['items']['puv_data']['actions'] = array();
            $frames['sc']['items']['puv_data']['active'] = '';

        }
        /*
            $frames['sp']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 商品概况';
            $frames['sp']['items'] = array();

            $frames['sp']['items']['spxstj_data']['url'] = web_url('data/spxstj_data');
            $frames['sp']['items']['spxstj_data']['title'] = '商品销售统计';
            $frames['sp']['items']['spxstj_data']['actions'] = array();
            $frames['sp']['items']['spxstj_data']['active'] = '';


            $frames['sp']['items']['spxstjhz_data']['url'] = web_url('data/spxstjhz_data');
            $frames['sp']['items']['spxstjhz_data']['title'] = '商品销售统计汇总';
            $frames['sp']['items']['spxstjhz_data']['actions'] = array();
            $frames['sp']['items']['spxstjhz_data']['active'] = '';


            $frames['sp']['items']['fans_count_data']['url'] = web_url('data/fans_count_data');
            $frames['sp']['items']['fans_count_data']['title'] = '粉丝购买力及消费统计';
            $frames['sp']['items']['fans_count_data']['actions'] = array();
            $frames['sp']['items']['fans_count_data']['active'] = '';


            $frames['sp']['items']['hexiao_data']['url'] = web_url('data/hexiao_data');
            $frames['sp']['items']['hexiao_data']['title'] = '核销员核销金额排行榜';
            $frames['sp']['items']['hexiao_data']['actions'] = array();
            $frames['sp']['items']['hexiao_data']['active'] = '';

        */
    }

    if (checkstr($_W['user']['perms'], 'data.buy_model_data') ||
        checkstr($_W['user']['perms'], 'data.group_num_data') ||
        checkstr($_W['user']['perms'], 'data.store_check_data') ||
        checkstr($_W['user']['perms'], 'data.dispatch_data') ||
        checkstr($_W['user']['perms'], 'data.store_real_data')
    ) {
        $frames['dd']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 订单概况';
        $frames['dd']['items'] = array();
        if (checkstr($_W['user']['perms'], 'data.buy_model_data')) {
            $frames['dd']['items']['buy_model_data']['url'] = web_url('data/buy_model_data');
            $frames['dd']['items']['buy_model_data']['title'] = '购买模式实时对比图';
            $frames['dd']['items']['buy_model_data']['actions'] = array();
            $frames['dd']['items']['buy_model_data']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'data.group_num_data')) {
            $frames['dd']['items']['group_num_data']['url'] = web_url('data/group_num_data');
            $frames['dd']['items']['group_num_data']['title'] = '成团人数实时对比图';
            $frames['dd']['items']['group_num_data']['actions'] = array();
            $frames['dd']['items']['group_num_data']['active'] = '';
        }
        /*
            $frames['dd']['items']['group_ordernum_data']['url'] = web_url('data/group_ordernum_data');
            $frames['dd']['items']['group_ordernum_data']['title'] = '成团订单量对比';
            $frames['dd']['items']['group_ordernum_data']['actions'] = array();
            $frames['dd']['items']['group_ordernum_data']['active'] = '';
        */
        if (checkstr($_W['user']['perms'], 'data.store_check_data')) {
            $frames['dd']['items']['store_check_data']['url'] = web_url('data/store_check_data');
            $frames['dd']['items']['store_check_data']['title'] = '门店核销实时对比图';
            $frames['dd']['items']['store_check_data']['actions'] = array();
            $frames['dd']['items']['store_check_data']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'data.dispatch_data')) {
            $frames['dd']['items']['dispatch_data']['url'] = web_url('data/dispatch_data');
            $frames['dd']['items']['dispatch_data']['title'] = '配送方式实时对比图';
            $frames['dd']['items']['dispatch_data']['actions'] = array();
            $frames['dd']['items']['dispatch_data']['active'] = '';
        }

        if (checkstr($_W['user']['perms'], 'data.store_real_data')) {
            $frames['dd']['items']['store_real_data']['url'] = web_url('data/store_real_data');
            $frames['dd']['items']['store_real_data']['title'] = '下单门店准确率';
            $frames['dd']['items']['store_real_data']['actions'] = array();
            $frames['dd']['items']['store_real_data']['active'] = '';
        }
    }
    return $frames;
}

//服务
function getserviceFrames()
{
    global $_W;
    $frames = array();

    $role = pdo_fetch("select role from " . tablename('uni_account_users') . " where uniacid = '{$_W['uniacid']}' and uid = '{$_W['user']['uid']}'");
    if ($_W['isfounder'] || $role['role'] == 'owner' || $_W['uniacid'] == 33 || $_W['uniacid'] == 53
//    checkstr($_W['user']['perms'], 'acclist_all')
    ) {
        $frames['service']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 订购中心';
        $frames['service']['items'] = array();

        if ($_W['uniacid'] == 33) {
            $frames['service']['items']['acclist']['url'] = web_url('service/acclist');
            $frames['service']['items']['acclist']['title'] = '购买记录';
            $frames['service']['items']['acclist']['actions'] = array();
            $frames['service']['items']['acclist']['active'] = '';
            $frames['service']['items']['acclist']['append']['url'] = web_url('service/acclist');
            $frames['service']['items']['acclist']['append']['title'] = '';
        }

//        $frames['service']['items']['viplist']['url'] = web_url('service/viplist');
//        $frames['service']['items']['viplist']['title'] = 'VIP用户';
//        $frames['service']['items']['viplist']['actions'] = array();
//        $frames['service']['items']['viplist']['active'] = '';
//        $frames['service']['items']['viplist']['append']['url'] = web_url('service/viplist');
//        $frames['service']['items']['viplist']['append']['title'] = '';

        $frames['service']['items']['vip_service']['url'] = web_url('service/vip_service');
        $frames['service']['items']['vip_service']['title'] = '服务号年度套餐';
        $frames['service']['items']['vip_service']['actions'] = array();
        $frames['service']['items']['vip_service']['active'] = '';
        //$frames['service']['items']['vip_service']['append']['url'] = web_url('service/vip_service');
        //$frames['service']['items']['vip_service']['append']['title'] = '';
        //        }
        /////修改的部分
        $frames['service']['items']['APP']['url'] = web_url('service/APP');
        $frames['service']['items']['APP']['title'] = 'APP年度套餐';
        $frames['service']['items']['APP']['actions'] = array();
        $frames['service']['items']['APP']['active'] = '';

        $frames['service']['items']['meigong']['url'] = web_url('service/meigong');
        $frames['service']['items']['meigong']['title'] = '美工年度套餐';
        $frames['service']['items']['meigong']['actions'] = array();
        $frames['service']['items']['meigong']['active'] = '';

        $frames['service']['items']['xiaochengxu']['url'] = web_url('service/xiaochengxu');
        $frames['service']['items']['xiaochengxu']['title'] = '小程序年度套餐';
        $frames['service']['items']['xiaochengxu']['actions'] = array();
        $frames['service']['items']['xiaochengxu']['active'] = '';


//        $frames['service']['items']['jinxiaochun']['url'] = web_url('service/jinxiaochun');
//        $frames['service']['items']['jinxiaochun']['title'] = '进销存年度套餐';
//        $frames['service']['items']['jinxiaochun']['actions'] = array();
//        $frames['service']['items']['jinxiaochun']['active'] = '';
        //if (checkstr($_W['user']['perms'], 'acclist.order_service')) {


        //        if (checkstr($_W['user']['perms'], 'acclist.order_service')) {
        $frames['service']['items']['order_service']['url'] = web_url('service/order_service');
        $frames['service']['items']['order_service']['title'] = '订单套餐（已下架）';
        $frames['service']['items']['order_service']['actions'] = array();
        $frames['service']['items']['order_service']['active'] = '';
        //$frames['service']['items']['order_service']['append']['url'] = web_url('service/order_service');
        //$frames['service']['items']['order_service']['append']['title'] = '';
    }

    if ($_W['uniacid'] == 33) {
        $frames['service']['items']['shop']['url'] = web_url('service/shop/display');
        $frames['service']['items']['shop']['title'] = '多商户管理';
        $frames['service']['items']['shop']['actions'] = array();
        $frames['service']['items']['shop']['active'] = '';
    }
//    }

    if (checkstr($_W['user']['perms'], 'workform_all') || $_W['uniacid'] == 53 || $_W['uniacid'] == 33) {
        $frames['workform']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 工单';
        $frames['workform']['items'] = array();

        if (checkstr($_W['user']['perms'], 'workform.add')) {
            $frames['workform']['items']['submit_workform']['url'] = web_url('service/workform_submit');
            $frames['workform']['items']['submit_workform']['title'] = '工单提交';
            $frames['workform']['items']['submit_workform']['actions'] = array();
            $frames['workform']['items']['submit_workform']['active'] = '';
            //$frames['workform']['items']['submit_workform']['append']['url'] = web_url('service/workform_submit');
            //$frames['workform']['items']['submit_workform']['append']['title'] = '';
        }

        if (checkstr($_W['user']['perms'], 'workform.list')) {
            $frames['workform']['items']['list_workform']['url'] = web_url('service/workform_list');
            $frames['workform']['items']['list_workform']['title'] = '工单列表';
            $frames['workform']['items']['list_workform']['actions'] = array();
            $frames['workform']['items']['list_workform']['active'] = '';
            //$frames['workform']['items']['list_workform']['append']['url'] = web_url('service/workform_list');
            //$frames['workform']['items']['list_workform']['append']['title'] = '';
        }

        if (checkstr($_W['user']['perms'], 'workform.schedule')) {
            $frames['workform']['items']['function_workform']['url'] = web_url('service/workform_list_newfunction');
            $frames['workform']['items']['function_workform']['title'] = '开发进度';
            $frames['workform']['items']['function_workform']['actions'] = array();
            $frames['workform']['items']['function_workform']['active'] = '';
            //$frames['workform']['items']['function_workform']['append']['url'] = web_url('service/workform_list_newfunction');
            //$frames['workform']['items']['function_workform']['append']['title'] = '';
        }
    }

    if ($_W['uniacid'] == 53 || $_W['isfounder'] || $_W['uniacid'] == 33) {
        $frames['workform']['items']['process_workform']['url'] = web_url('service/workform_list_admin');
        $frames['workform']['items']['process_workform']['title'] = '工单管理';
        $frames['workform']['items']['process_workform']['actions'] = array();
        $frames['workform']['items']['process_workform']['active'] = '';
        //$frames['workform']['items']['process_workform']['append']['url'] = web_url('service/workform_list_admin');
        //$frames['workform']['items']['process_workform']['append']['title'] = '';
    }

    if ($_W['uniacid'] == 53 || $_W['uniacid'] == 33) {
        $frames['workform']['items']['internal']['url'] = web_url('service/internal');
        $frames['workform']['items']['internal']['title'] = '内部工单管理';
        $frames['workform']['items']['internal']['actions'] = array();
        $frames['workform']['items']['internal']['active'] = '';
    }

//    if (checkstr($_W['user']['perms'], 'workbook_submit_all')) {
    if ($_W['uniacid'] == 53 || $_W['uniacid'] == 33) {

        $frames['workbook']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 手册';
        $frames['workbook']['items'] = array();

        $frames['workbook']['items']['workbook_submit']['url'] = web_url('service/workbook_submit');
        $frames['workbook']['items']['workbook_submit']['title'] = '手册发布1';
        $frames['workbook']['items']['workbook_submit']['actions'] = array();
        $frames['workbook']['items']['workbook_submit']['active'] = '';
        //$frames['workbook']['items']['workbook_submit']['append']['url'] = web_url('service/workbook_submit');
        //$frames['workbook']['items']['workbook_submit']['append']['title'] = '';

    }
//    }

    /*
    $frames['service']['items']['workform']['url'] = web_url('service/workform');
    $frames['service']['items']['workform']['title'] = '工单';
    $frames['service']['items']['workform']['actions'] = array();
    $frames['service']['items']['workform']['active'] = '';
    $frames['service']['items']['workform']['append']['url'] = web_url('service/workform');
    $frames['service']['items']['workform']['append']['title'] = '';

    $frames['service']['items']['technical']['url'] = web_url('service/technical');
    $frames['service']['items']['technical']['title'] = '技术服务';
    $frames['service']['items']['technical']['actions'] = array();
    $frames['service']['items']['technical']['active'] = '';
    $frames['service']['items']['technical']['append']['url'] = web_url('service/technical');
    $frames['service']['items']['technical']['append']['title'] = '';

    $frames['service']['items']['adviser']['url'] = web_url('service/adviser');
    $frames['service']['items']['adviser']['title'] = '专属顾问';
    $frames['service']['items']['adviser']['actions'] = array();
    $frames['service']['items']['adviser']['active'] = '';
    $frames['service']['items']['adviser']['append']['url'] = web_url('service/adviser');
    $frames['service']['items']['adviser']['append']['title'] = '';
    */
    return $frames;
}

//推荐有礼
function getagentFrames()
{
    global $_W;
    $frames = array();
    $agent = pdo_get('tg_agents', array('uniacid' => $_W['uniacid']));
    $role = pdo_fetch("select role from " . tablename('uni_account_users') . " where uniacid = '{$_W['uniacid']}' and uid = '{$_W['user']['uid']}'");
    if (($role['role'] == 'owner' || $_W['isfounder'])
        && !$agent
//        checkstr($_W['user']['perms'], 'agent_all') &&
//        checkstr($_W['user']['perms'], 'agent_users_all')&&$_W['user']['merchant_id']==0
    ) {
        $frames['agent']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 推荐有礼';
        $frames['agent']['items'] = array();

//        if (checkstr($_W['user']['perms'], 'agent_all')) {
        $frames['agent']['items']['agent']['url'] = web_url('agent/agent');
        $frames['agent']['items']['agent']['title'] = '推荐有礼';
        $frames['agent']['items']['agent']['actions'] = array();
        $frames['agent']['items']['agent']['active'] = '';
//        }

//        if (checkstr($_W['user']['perms'], 'agent_users_all')) {
        $frames['agent']['items']['agent_user']['url'] = web_url('agent/agent_users');
        $frames['agent']['items']['agent_user']['title'] = '用户管理';
        $frames['agent']['items']['agent_user']['actions'] = array();
        $frames['agent']['items']['agent_user']['active'] = '';
//        }
    }

//    if (checkstr($_W['user']['perms'], 'examine_all') &&
//        checkstr($_W['user']['perms'], 'subsidy_all') &&
//        checkstr($_W['user']['perms'], 'agent_users_admin_all')
//    ) {
    if ($_W['isfounder'] || $_W['uniacid'] == 53 || $_W['uniacid'] == 33 || $_W['uniacid'] == 995
    ) {
        $frames['agent_admin']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 后台管理';
        $frames['agent_admin']['items'] = array();

//            if (checkstr($_W['user']['perms'], 'examine_all')) {
        $frames['agent_admin']['items']['agentss']['url'] = web_url('agent/examine');
        $frames['agent_admin']['items']['agentss']['title'] = '提现管理';
        $frames['agent_admin']['items']['agentss']['actions'] = array();
        $frames['agent_admin']['items']['agentss']['active'] = '';
//            }

//            if (checkstr($_W['user']['perms'], 'subsidy_all')) {
        $frames['agent_admin']['items']['agent_subsidy']['url'] = web_url('agent/subsidy');
        $frames['agent_admin']['items']['agent_subsidy']['title'] = '补贴发放';
        $frames['agent_admin']['items']['agent_subsidy']['actions'] = array();
        $frames['agent_admin']['items']['agent_subsidy']['active'] = '';
//            }

//            if (checkstr($_W['user']['perms'], 'agent_users_admin_all')) {
        $frames['agent_admin']['items']['agent_user_admin']['url'] = web_url('agent/agent_users_admin');
        $frames['agent_admin']['items']['agent_user_admin']['title'] = '用户管理(admin)';
        $frames['agent_admin']['items']['agent_user_admin']['actions'] = array();
        $frames['agent_admin']['items']['agent_user_admin']['active'] = '';
//            }
    }
//    }


    if (($role['role'] == 'owner' || $_W['isfounder']) && $agent) {
        $frames['proxy']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 城市服务商';
        $frames['proxy']['items'] = array();

        $frames['proxy']['items']['proxy_detail']['url'] = web_url('agent/proxy_detail');
        $frames['proxy']['items']['proxy_detail']['title'] = '套餐详情';
        $frames['proxy']['items']['proxy_detail']['actions'] = array();
        $frames['proxy']['items']['proxy_detail']['active'] = '';

        $frames['proxy']['items']['proxy_user']['url'] = web_url('agent/proxy_user');
        $frames['proxy']['items']['proxy_user']['title'] = '用户管理';
        $frames['proxy']['items']['proxy_user']['actions'] = array();
        $frames['proxy']['items']['proxy_user']['active'] = '';
    }

    return $frames;
}

//极限单品
function getplatformFrames()
{
    global $_W;
    $frames = array();
    $shop = pdo_fetch("SELECT * FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));

//    $role = pdo_fetch("select role from " . tablename('uni_account_users') . " where uniacid = '{$_W['uniacid']}' and uid = '{$_W['user']['uid']}'");

    $frames['platform']['title'] = '<i class="fa fa-pie-chart"></i>&nbsp;&nbsp; 极限单品';
    $frames['platform']['items'] = array();

    if ($_W['uniacid'] == 33 || $_W['isfounder']) {
        $frames['platform']['items']['platform_acceptor']['url'] = web_url('platform/platform_acceptor');
        $frames['platform']['items']['platform_acceptor']['title'] = '平台消息接受员';
        $frames['platform']['items']['platform_acceptor']['actions'] = array();
        $frames['platform']['items']['platform_acceptor']['active'] = '';
    }

    $supplier = pdo_fetch("SELECT * FROM " . tablename('tg_platform_supplier') . " WHERE uniacid = " . $_W['uniacid']);
    if ($_W['uniacid'] == 33 || ($shop['vip'] >= 0 && $supplier)) {
        $frames['platform']['items']['platform_list']['url'] = web_url('platform/platform_list');
        $frames['platform']['items']['platform_list']['title'] = '供应商列表';
        $frames['platform']['items']['platform_list']['actions'] = array();
        $frames['platform']['items']['platform_list']['active'] = '';
    }
    
    if ($shop['vip'] >= 0 && ($supplier['status'] == -1 || $supplier['status'] == 2||empty($supplier))) {
        $frames['platform']['items']['platform_apply']['url'] = web_url('platform/platform_apply');
        $frames['platform']['items']['platform_apply']['title'] = '供应商入驻';
        $frames['platform']['items']['platform_apply']['actions'] = array();
        $frames['platform']['items']['platform_apply']['active'] = '';

    }

    $platform_shop = pdo_fetch("SELECT * FROM " . tablename('tg_platform_shop') . " WHERE uniacid = " . $_W['uniacid']);
    if ($_W['uniacid'] == 33 || ($shop['vip'] >= 0 && $platform_shop)) {
        $frames['platform']['items']['platform_shop_list']['url'] = web_url('platform/platform_shop_list');
        $frames['platform']['items']['platform_shop_list']['title'] = '商家资料';
        $frames['platform']['items']['platform_shop_list']['actions'] = array();
        $frames['platform']['items']['platform_shop_list']['active'] = '';
    }

    if ($shop['vip'] >= 0 && ($platform_shop['status'] == -1 || $platform_shop['status'] == 2||empty($platform_shop))) {
        $frames['platform']['items']['platform_shop']['url'] = web_url('platform/platform_shop');
        $frames['platform']['items']['platform_shop']['title'] = '商家入驻';
        $frames['platform']['items']['platform_shop']['actions'] = array();
        $frames['platform']['items']['platform_shop']['active'] = '';
    }
    if ($shop['vip'] >= 0 && $supplier['status'] == 1) {
        $frames['platform']['items']['platform_goods']['url'] = web_url('platform/goods_list/edit');
        $frames['platform']['items']['platform_goods']['title'] = '上传商品';
        $frames['platform']['items']['platform_goods']['actions'] = array();
        $frames['platform']['items']['platform_goods']['active'] = '';
    }

    if ($_W['uniacid'] == 33 || ($shop['vip'] >= 0 && ($platform_shop['status'] == 1 || $supplier['status'] == 1))) {
        $frames['platform']['items']['list']['url'] = web_url('platform/goods_list/buy_goods_list');
        $frames['platform']['items']['list']['title'] = '货源管理';
        $frames['platform']['items']['list']['actions'] = array();
        $frames['platform']['items']['list']['active'] = '';
    }

    if ($shop['vip'] >= 0 && $platform_shop['status'] == 1) {
        $frames['platform']['items']['shop_order']['url'] = web_url('platform/shop_order');
        $frames['platform']['items']['shop_order']['title'] = '商家订单管理';
        $frames['platform']['items']['shop_order']['actions'] = array();
        $frames['platform']['items']['shop_order']['active'] = '';
    }

    if ($shop['vip'] >= 0 && $supplier['status'] == 1) {
        $frames['platform']['items']['supply_order']['url'] = web_url('platform/supply_order');
        $frames['platform']['items']['supply_order']['title'] = '供货商订单管理';
        $frames['platform']['items']['supply_order']['actions'] = array();
        $frames['platform']['items']['supply_order']['active'] = '';
    }

    if ($_W['uniacid'] == 33 ) {
        $frames['platform']['items']['platform_order']['url'] = web_url('platform/platform_order');
        $frames['platform']['items']['platform_order']['title'] = '平台订单管理';
        $frames['platform']['items']['platform_order']['actions'] = array();
        $frames['platform']['items']['platform_order']['active'] = '';

    }

    if (($shop['vip'] >= 0 && $supplier['status'] == 1) || $_W['uniacid'] == 33 ) {

        $frames['platform']['items']['history_record']['url'] = web_url('platform/history_record');
        $frames['platform']['items']['history_record']['title'] = '平台打款记录';
        $frames['platform']['items']['history_record']['actions'] = array();
        $frames['platform']['items']['history_record']['active'] = '';
        
        $frames['platform']['items']['score']['url'] = web_url('platform/score');
        $frames['platform']['items']['score']['title'] = '评分管理';
        $frames['platform']['items']['score']['actions'] = array();
        $frames['platform']['items']['score']['active'] = '';
    }

    unset($supplier);
    return $frames;
}

//顶部导航
function get_top_menus()
{
    global $_W;
    $frames = array();
    if (checkstr($_W['user']['perms'], 'gzhset_all') && $_W['user']['merchant_id'] == 0) {
        $frames['gzhset']['title'] = '<i class="fa fa-cog"></i>&nbsp;&nbsp; 公众号';
        if ($_W['user']['merchant_id'] == 0) {
            $frames['gzhset']['url'] = web_url('gzhset/special/display');
        } else {
            $frames['gzhset']['url'] = web_url('gzhset/notification-list');
        }

        $frames['gzhset']['active'] = 'gzhset';
    }

    if (checkstr($_W['user']['perms'], 'copyright_all') ||
        checkstr($_W['user']['perms'], 'notice_all') ||
        checkstr($_W['user']['perms'], 'adv_all') ||
        checkstr($_W['user']['perms'], 'print_all') ||
        checkstr($_W['user']['perms'], 'autoaddress_all') ||
        checkstr($_W['user']['perms'], 'deliverylist_all') ||
        checkstr($_W['user']['perms'], 'bdelete_all') ||
        checkstr($_W['user']['perms'], 'selflogistics_all') ||
        checkstr($_W['user']['perms'], 'sendtime_all') ||
        checkstr($_W['user']['perms'], 'copyright_all') ||
        checkstr($_W['user']['perms'], 'user_all')
    ) {
        $frames['store']['title'] = '<i class="fa fa-desktop"></i>&nbsp;&nbsp; 商城';
        if ($_W['user']['merchant_id'] == 0) {
            $frames['store']['url'] = web_url('store/setting/copyright');
        } else {
            $frames['store']['url'] = web_url('store/adv');
        }

        $frames['store']['active'] = 'store';
    }

    if (checkstr($_W['user']['perms'], 'category_all') ||
        checkstr($_W['user']['perms'], 'item_all')
    ) {
        $frames['goods']['title'] = '<i class="fa fa-gift"></i>&nbsp;&nbsp; 商品';
        $frames['goods']['url'] = web_url('goods/goods/display', array('status' => '1'));
        $frames['goods']['active'] = 'goods';
    }

    if (checkstr($_W['user']['perms'], 'received_all') ||
        checkstr($_W['user']['perms'], 'group_all') ||
        checkstr($_W['user']['perms'], 'judgment_admin_all') ||
        checkstr($_W['user']['perms'], 'import_all') ||
        checkstr($_W['user']['perms'], 'refund_all')
    ) {
        $frames['order']['title'] = '<i class="fa fa-shopping-cart"></i>&nbsp;&nbsp; 订单';
        $frames['order']['url'] = web_url('order/order/summary');
        $frames['order']['active'] = 'order';
    }

    if ((checkstr($_W['user']['perms'], 'fans_all') ||
        checkstr($_W['user']['perms'], 'fangroup_all') ||
        checkstr($_W['user']['perms'], 'member_all') ||
        checkstr($_W['user']['perms'], 'partjob_all') ||
        checkstr($_W['user']['perms'], 'activity_all') ||
        checkstr($_W['user']['perms'], 'smsbuy_all'))
        && $_W['user']['merchant_id'] == 0
    ) {
        $frames['member']['title'] = '<i class="fa fa-user"></i>&nbsp;&nbsp; 粉丝';
        $frames['member']['url'] = web_url('member/fangroup');
        $frames['member']['active'] = 'member';
        $uni = pdo_fetch("select vip,endtime,ordernum,uniacid from " . tablename('account_wechats') . ' where uniacid=:uniacid ', array(':uniacid' => $_W['uniacid']));
    }

    if ($_W['user']['merchant_id'] == 0
        && checkstr($_W['user']['perms'], 'data_all')
    ) {
        if ($uni['vip'] == 1 || $uni['uniacid'] == 815) {
            $frames['data']['title'] = '<i class="fa fa-area-chart"></i>&nbsp;&nbsp; 大数据';
            $frames['data']['url'] = web_url('data/sjgk_data');
            $frames['data']['active'] = 'data';
        }
    }
    /*
        $frames['application']['title'] = '<i class="fa fa-cubes"></i>&nbsp;&nbsp; 应用和营销';
        $frames['application']['url'] = web_url('application/plugins/list');
        $frames['application']['active'] = 'application';
        */
    /*
    $frames['data']['title'] = '<i class="fa fa-area-chart"></i>&nbsp;&nbsp; 供应链';
    $frames['data']['url'] = web_url('data/home_data');
    $frames['data']['active'] = 'data';

    $frames['data1']['title'] = '<i class="fa fa-area-chart"></i>&nbsp;&nbsp; 增值服务';
    $frames['data1']['url'] = web_url('data/home_data');
    $frames['data1']['active'] = 'data1';

    $frames['data3']['title'] = '<i class="fa fa-area-chart"></i>&nbsp;&nbsp; 专属顾问';
    $frames['data3']['url'] = web_url('data/home_data');
    $frames['data3']['active'] = 'data3';
    */

    if (
        (checkstr($_W['user']['perms'], 'workform.add') ||
            checkstr($_W['user']['perms'], 'workform.list') ||
            checkstr($_W['user']['perms'], 'workform.schedule')) &&
        $_W['user']['merchant_id'] == 0
    ) {
        $frames['service']['title'] = '<i class="fa fa-user-md"></i>&nbsp;&nbsp; 服务';
        $frames['service']['url'] = web_url('service/vip_service');
        $frames['service']['active'] = 'service';
    }
    $role = pdo_fetch("select role from " . tablename('uni_account_users') . " where uniacid = '{$_W['uniacid']}' and uid = '{$_W['user']['uid']}'");
    $agent = pdo_get('tg_agents', array('uniacid' => $_W['uniacid']));
    $wechat = pdo_get('account_wechats', array('uniacid' => $_W['uniacid']));
    if (!is_null($wechat['referral']) || $agent) {
        if ($_W['isfounder'] || $role['role'] == 'owner' || $_W['uniacid'] == 53 || $_W['uniacid'] == 33 || $_W['uniacid'] == 995)
        {
            $frames['agent']['title'] = '<i class="fa fa-gift"></i>&nbsp;&nbsp; 推荐';
            if ($agent) {
                $frames['agent']['url'] = web_url('agent/proxy_detail');
            } else {
                $frames['agent']['url'] = web_url('agent/agent');
            }
            $frames['agent']['active'] = 'agent';
        }
    }


    $plat = pdo_fetch("select status from " . tablename('tg_platform_supplier') . " where uniacid = '{$_W['uniacid']}'");
    $shop = pdo_fetch("select status from " . tablename('tg_platform_shop') . " where uniacid = '{$_W['uniacid']}'");

    if ($uni['vip'] >= 0
//        && in_array($_W['uniacid'] , $_W['unicid_array'])
    ) {

        $frames['platform']['title'] = '<i class="glyphicon glyphicon-shopping-cart"></i>&nbsp;&nbsp; 极限单品';

        if(empty($shop)) {
            $frames['platform']['url'] = web_url('platform/platform_shop');
        }
        if (empty($plat)) {
            $frames['platform']['url'] = web_url('platform/platform_apply');
        }
        if ($shop) {
            $frames['platform']['url'] = web_url('platform/platform_shop_list');
        }
        if ($plat) {
            $frames['platform']['url'] = web_url('platform/platform_list');
        }
        if ($plat['status'] == 1 || $shop['status'] == 1) {
            $frames['platform']['url'] = web_url('platform/goods_list', array('status' => 'supply_goods_list'));
        }

        $frames['platform']['active'] = 'platform';
    }
    unset($plat);
    unset($role);
    return $frames;
}
