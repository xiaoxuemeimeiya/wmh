<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('manager_helper');
        $this->admin_data = manager_login();
        assign('admin_data', $this->admin_data);
    }

    /**
     * 后台首页
     */
    public function index()
    {
        display('/index/index.html');
    }

    /**
     * 后台头部
     */
    public function header()
    {
        assign('admin_data', $this->admin_data);
        display('/index/header.html');
    }

    /**
     * 后台菜单
     */
    public function menu($type = 'system')
    {
        //菜单查询
        $this->load->model('/manager/menu_model');
        $menu_list = array();
        $menu_list = $this->menu_model->get_menu($type);//菜单列表
        assign('menu_list', $menu_list);

        assign('admin_data', $this->admin_data);
        display('/index/menu.html');
    }

    /**
     * 后台右侧首页
     */
    public function main()
    {
        redirect('/manager/system/admin/index');
    }

}
