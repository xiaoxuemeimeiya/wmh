<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Menu_model extends CI_Model
{

    /**
     * 添加
     * @param array $data 添加数据
     */
    public function get_menu($type = 'order')
    {
        $menu_array = array(
            'system' => array(
                '<i class="Hui-iconfont">&#xe605;</i> 权限' => array(
                    '管理员' => '/manager/system/admin/index',
                    '修改密码' => '/manager/system/admin/update_password',
                    '角色管理' => '/manager/system/role/index',
                    '管理员权限资源' => '/manager/system/role_right/index?type=manager',
                    '管理员日志' => '/manager/system/admin_log/index',
                ),
            ),
            'goods' => array(
                '<i class="Hui-iconfont">&#xe60d;</i> 产品管理' => array(
                    //'分类管理' => '/manager/goods/brand/index',
                    '分类管理' => '/manager/goods/category/index',
                    '课程管理' => '/manager/goods/goods/index',
                    '课程属性管理' => '/manager/goods/model/index',
                ),
            ),
            'order' => array(
                '<i class="Hui-iconfont">&#xe60d;</i> 订单管理' => array(
                    '订单管理' => '/manager/order/order/index',
                ),
            ),
            'member' => array(
                '<i class="Hui-iconfont">&#xe60d;</i> 用户管理' => array(
                    '用户基本信息管理' => '/manager/member/user/index',
                    '用户数据管理' => '/manager/member/user/data',
                ),
            ),
            'tool' => array(
                '<i class="Hui-iconfont">&#xe60d;</i> 工具管理' => array(
                    '广告管理' => '/manager/tool/adv/index',
                    '广告位管理' => '/manager/tool/adv_position/index',
                    '图片管理' => '/manager/tool/img/index',
                ),
            ),
        );
        return $menu_array[$type];
    }
}
