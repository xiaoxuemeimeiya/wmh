<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('manager_helper');
    }

    /**
     * 后台登陆
     */
    public function index()
    {
        $admin_id = $this->session->userdata('admin_id');
        if (!empty($admin_id)) {
            header('location:' . site_url('/manager/welcome'));
        } else {
            $redirect_url = trim($this->input->get('redirect_url', true));//返回链接
            if (empty($redirect_url)) $redirect_url = site_url('/manager/welcome');
            assign('redirect_url', $redirect_url);
            display('/login.html');
        }
    }

    /**
     * 后台登陆验证
     */
    public function login_act()
    {
        $username = trim($this->input->post('username', true));
        $password = trim($this->input->post('password', true));
        if (!empty($username) && !empty($password)) {
            $this->load->model('loop_model');
            $admin_data = $this->loop_model->get_where('my_admin', array('username' => $username));
            if ($admin_data['username'] == '') {
                error_json('用户名不存在');
            } elseif ($admin_data['password'] != md5(md5($password) . $admin_data['salt'])) {
                error_json('密码错误');
            } elseif ($admin_data['status'] != 0) {
                error_json('帐号被管理员锁定');
            } else {
                $this->loop_model->update_where('my_admin', array('lasttime' => time()), array('id' => $admin_data['id']));
                $this->session->set_userdata('admin_id', $admin_data['id']);
				//error_reporting(E_ALL & ~E_NOTICE);
		//ini_set('display_errors', 1);
				
                admin_log_insert($admin_data['username'] . '登录系统');
                error_json('y');
            }
        } else {
            error_json('账号和密码不能为空');
        }
    }

    /**
     * 后台用户退出登陆
     */
    public function loginout()
    {
        $admin_id = $this->session->userdata('admin_id');
        admin_log_insert($admin_id . '退出系统');
        $this->session->unset_userdata('admin_id');
        echo "<script>alert('您已经退出登陆。');parent.window.location.href='" . site_url('/manager/login') . "'</script>";
    }

}
