<?php
/**
* 商品类
*/
class Goods
{
    /**
     * 获取一商品所有服务
     *
     * @param  int $id 商品id
     * @return array servies 某一商品所有服务
     */
    public function getServies($id)
    {
        $goodsServies = pdo_fetchall("SELECT * FROM" . tablename('tg_goods_services') .  " WHERE `cm_tg_goods_id` = '{$id}' ORDER BY id");
        return $goodsServies;
    }

    /**
     * 新建一商品的服务
     *
     * @param  int $id 商品id
     * @param string $name 服务名称
     * @param string $content  服务简介
     * @return int $server_id 服务id
     */
    public function createServer($id, $name, $content)
    {
        global $_W;
        $ret = pdo_insert('tg_goods_services', [
            'uniacid'               => $_W['uid'],
            'cm_tg_goods_id'        => $id,
            'goods_service_name'    => $name,
            'goods_service_content' => $content
        ]);
        $server_id = pdo_insertid();
        return $server_id;
    }

    /**
     * 更新一条商品服务
     *
     * @param  int $id  商品服务id
     * @param  string $name 服务名称
     * @param  string $content 服务详细介绍
     */
    public function updateServer($id, $name, $content)
    {
        pdo_fetchall('update `cm_tg_goods_services` set goods_service_name = "'.$name.'",  goods_service_content = "'.$content.'" where id = '.$id.'');
    }

    /**
     * 删除一商品服务
     *
     * @param  int $id 商品服务id
     */
    public function deleteServer($id)
    {
        pdo_fetchall('DELETE FROM `cm_tg_goods_services` WHERE `id` = '.$id.' LIMIT 1');
    }
}
