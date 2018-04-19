<?php

$_W['page']['title'] = "极限单品 - 评分管理";

global $_W, $_GPC;
load()->func("tpl");
$uniacid = $_W['uniacid'];

$op = $_GPC["op"];
if (!$op) {
    $op = "display";
}
if ($op == "display") {
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $status = intval($_GPC['status']);
    $mobile = $_GPC['mobile'];
    $realname = $_GPC['realname'];
    $con = '';
    if ($uniacid != 33 && $uniacid != 53) {
        $con .= " and supply_id = '{$uniacid}' ";
    }
    if ($status == 1) {
        $con .= " and refund = 1 ";
    } elseif ($status == 2) {
        $con .= " and refund = 2 ";
    } elseif ($status == 3) {
        $con .= " and supply_status = 1 ";
    } elseif ($status == 4) {
        $con .= " and supply_status = 2 ";
    }
    if (!empty($mobile)) {
        $con .= " and mobile like '%{$mobile}%' ";
    }
    if (!empty($realname)) {
        $con .= " and realname like '%{$realname}%' ";
    }
//    var_dump($con);
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_score') . " WHERE 1 {$con} LIMIT " . ($page - 1) * $size . " , " . $size);
//    foreach ($list as &$item) {
//        $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$item['orderid']}'");
//        $item['orderno'] = $o['orderno'];
//        unset($item);
//    }
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_score') . " WHERE 1 {$con} ");
    foreach ($list as &$item) {
        $item['goods_style'] = $item['goods_style'] == 0 ? "价格低" : "利润高";
        $item['style_weight'] = $item['style_weight'] * 100 . "%";
        $item['quality_weight'] = $item['quality_weight'] * 100 . "%";
        $item['sales_weight'] = $item['sales_weight'] * 100 . "%";
        $cycle = pdo_fetchcolumn("select cycle from " .tablename('tg_supply_rule') ." where score = " .$item['total_score']);
        $item['platform_cycle'] = $cycle;
//        die(json_encode($cycle));
        unset($item);
    }
    $pager = pagination($total, $page, $size);
//    die(json_encode($list));
}

if ($op == "my_score") {
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $status = intval($_GPC['status']);
    $mobile = $_GPC['mobile'];
    $realname = $_GPC['realname'];
    $con = '';
    $con .= " and uniacid = '{$uniacid}' ";
    if ($status == 1) {
        $con .= " and review_status = 1 ";
    } elseif ($status == -1) {
        $con .= " and review_status = -1 ";
    } elseif ($status == 0) {
        $con .= " and review_status = 0 ";
    }
//    var_dump($con);
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_scores') . " WHERE 1 {$con} LIMIT " . ($page - 1) * $size . " , " . $size);
//    foreach ($list as &$item) {
//        $o = pdo_fetch("select orderno from " . tablename('tg_order') . " where id = '{$item['orderid']}'");
//        $item['orderno'] = $o['orderno'];
//        unset($item);
//    }
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_scores') . " WHERE 1 {$con} ");
    foreach ($list as &$item) {
        $item['goods_style'] = $item['goods_style'] == 0 ? "价格低" : "利润高";
        $item['style_weight'] = $item['style_weight'] * 100 . "%";
        $item['quality_weight'] = $item['quality_weight'] * 100 . "%";
        $item['sales_weight'] = $item['sales_weight'] * 100 . "%";
        $cycle = pdo_fetchcolumn("select cycle from " .tablename('tg_supply_rule') ." where score = " .$item['total_score']);
        $item['platform_cycle'] = $cycle;
//        die(json_encode($cycle));
        unset($item);
    }
    $pager = pagination($total, $page, $size);
    die(json_encode($list));
}

if ($op == 'add') {

    $id = $_GPC['id'];
    $score = pdo_fetch("select * from " . tablename('tg_supply_score') . " where goodsid = '{$id}' ");


//    die(json_encode($score));

}

if ($op == 'add_submit') {
    $id = $_GPC['id'];
    $score = pdo_fetch("select * from " . tablename('tg_supply_score') . " where goodsid = '{$id}' ");
    if (!$score) {
        $goods = pdo_fetch('select * from ' .tablename('tg_supply_goods') ." where id = " .$id);
        $dat['supply_id'] = $goods['supply_id'];
        $dat['gname'] = $goods['name'];
        $dat['goodsid'] = $goods['id'];
        $dat['goods_style'] = $goods['goods_style'];
        $dat['total_score'] = 5;
        $dat['platform_cycle'] = 7;
        pdo_insert('tg_supply_score' , $dat);
        $score['id'] = pdo_insertid();
        $score['goods_style'] =  $dat['goods_style'];
    }


    $data = pdo_fetch("select id from " . tablename('tg_supply_scores') . " where uniacid = '{$uniacid}' and goodsid = '{$id}' ");
    if ($data) {
        die(json_encode(array('status' => 0, 'message' => '请勿重复评价！')));
    } else {
        $data = $_GPC['data'];
        $data['imgs'] = serialize($data['imgs']);
        $data['uniacid'] = $uniacid;
        $data['goodsid'] =$id;
        $data['gname'] = $score['gname'];
        $data['goods_style'] = $score['goods_style'];
        $data['weight_mode'] = $score['weight_mode'];
        $data['score_time'] = TIMESTAMP;
        $data['total_score'] = $score['style_weight'] * $data['style_score'] + $score['quality_weight'] * $data['quality_score'] + $score['sales_weight'] * $data['sales_score'];
        if ($data['total_score'] >= 5) {
            $data['review_status'] = 1;
            $re = supply_score($id);
        }
        $re = pdo_insert('tg_supply_scores', $data);
        $resid=$score['id'];
        if ($re) {
            $message = '评价成功！';
        } else {
            $message = '评价失败！';
        }
        die(json_encode(array('status' => $re, 'message' => $message,'resid'=>$resid)));
    }

}

if ($op == 'detail') {
    $id = $_GPC['id'];
    $page = max(1, intval($_GPC['page']));
    $size = 10;
    $con = '';
    if ($id > 0) {
        $con .= " and goodsid = '{$id}' ";
    }
    $list = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_scores') . " WHERE 1 {$con} LIMIT " . ($page - 1) * $size . " , " . $size);
    foreach ($list as &$item) {
        $item['goods_style'] = $item['goods_style'] == 0 ? "价格低" : "利润高";
        unset($item);
    }
    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_scores') . " WHERE 1 {$con} ");
    $pager = pagination($total, $page, $size);
//    die(json_encode($list));

}

//评论详情
if ($op == 'content_detail') {
    $id = $_GPC['id'];
//    $page = max(1, intval($_GPC['page']));
//    $size = 10;
//    $con = '';
//    if ($id > 0) {
//        $con .= " and goodsid = '{$id}' ";
//    }
    $list = pdo_fetch("SELECT * FROM " . tablename('tg_supply_scores') . " WHERE id = " .$id);
//
//    foreach ($list as &$item) {
//        $item['goods_style'] = $item['goods_style'] == 0 ? "价格低" : "利润高";
//        unset($item);
//    }
//
//    $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('tg_supply_scores') . " WHERE 1 " . $con);
//    $pager = pagination($total, $page, $size);
    $img = unserialize($list['imgs']);
    foreach ($img as $key => $item) {
        $list['img'][$key] = tomedia($item);
    }
    die(json_encode(array('status' => 1 , 'data' => $list)));

}

if ($op == 'scores_detail') {
    $id = $_GPC['id'];
    $data = pdo_fetch("select * from " . tablename('tg_supply_scores') . " where id = '{$id}' ");
    $data['imgs'] = unserialize($data['imgs']);
    die(json_encode(array('data' => $data, 'status' => 1)));
}

if ($op == 'scores_review') {
    $id = $_GPC['id'];
    $status = $_GPC['status'];
    $review_reason = $_GPC['review_reason'];
    $goodsid = $_GPC['goodsid'];
    $re = pdo_update('tg_supply_scores', array('review_status' => $status, 'review_reason' => $review_reason, 'review_time' => TIMESTAMP), array('id' => $id));
    internal_log('score1' , $re);
    if ($re) {
        $message = '审核成功！';
        if($status){
            $re = supply_score($goodsid);
        }

    } else {
        $message = '审核失败！';
    }
    die(json_encode(array('status' => $re, 'message' => $message)));
}

//评分规则
if ($op == '') {

}

function supply_score($goodsid)
{
    $scores = pdo_fetchall("SELECT * FROM " . tablename('tg_supply_scores') . " WHERE goodsid = " . $goodsid . " AND review_status = 1 ");
    $score = 0.0;
    foreach ($scores as $value) {
        $score += floatval($value['total_score']);
    }
    $score = $score / count($scores);
    $re = pdo_update('tg_supply_score', array('total_score' => $score, 'score_time' => TIMESTAMP), array('goodsid' => $goodsid));
    $goods=pdo_fetch('select * from '.tablename('tg_supply_goods').' where id=:id',array(':id'=>$goodsid));
//    10（5-？）
    internal_log('score' , $scores);
    pdo_update('tg_platform_supplier',array('play_cycle'=>10*(5-$score)),array('uniacid'=>$goods['supply_id']));
    return $re;
}

include wl_template("platform/score");
exit();