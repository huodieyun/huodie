<?php
// 引入商品类
wl_load()->classs('webGoods');
// api参数
$api = $_GET['api'];

// 商品id
$id = $_GET['id'];
// 商品服务id
$serverId = $_GET['server_id'];

$goods = new Goods();
switch ($api) {
    // 某一商品所有的服务
    case 'index':
        $servies = $goods->getServies($id);
        print(json_encode($servies));
        break;
    // 创建商品服务
    case 'create':
        $serve_id = $goods->createServer($id);
        echo $serve_id;
        break;
    // 删除商品服务
    case 'delete':
        $goods->deleteServer($serverId);
        break;

    default:
        print(json_encode(['error'=>'not api']));
        break;
}
