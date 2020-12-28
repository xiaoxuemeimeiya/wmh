<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Ykx extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
        $this->load->helpers('api_helper');
        $this->load->helpers('shop_helper');

        $formData = array_merge($this->input->get(), $this->input->post());
        $username = isset($formData['username']) ? $formData['username'] : '';
        $token = isset($formData['token']) ? $formData['token'] : '';
        $timestamp = isset($formData['timestamp']) ? $formData['timestamp'] : '';

        $token = urldecode($token);
        if (!request_verify($username, $token, $timestamp)) {
            error_json('非法请求');
        }
    }

    /**
     * 创建用户同时升级该账户为商户
     * $data_post = [
     *      'username' => '13800138000',
     *      'password' => '123456',
     *      'repassword' => '123456',
     *      'full_name' => '马云',
     *      'group_id' => 0,        //默认会员组
     *      'tel' => '020-12345678',
     *      'email' => '13800138000@139.com',
     *      'shop_name' => '马云小店',
     *      'logo' => '/uploads/2018/01/26/b6837f27a959f7c439298e107d3a3ff3.jpg',
     *      'customer_url' => 'www.yaokexing.com',
     *      'business_license' => '/uploads/2018/01/26/b6837f27a959f7c439298e107d3a3ff3.jpg',
     *      'prov' => '110000',
     *      'city' => '110100',
     *      'area' => '110101',
     *      'address' => '中山路1号',
     *      'desc' => '马云小店描述',
     * ];
     */
    public function create_user_shop()
    {
        header('Access-Control-Allow-Origin: *');
        if (!is_post()) {
            error_json('提交方式错误');
        }

        $data_post = $this->input->post(NULL, true);
        unset($data_post['id']);    //确保是新增用户

        $this->load->model('member/user_model');
        $createUserResult = $this->user_model->update($data_post);
        if ($createUserResult === 'y') {
            $data_post['m_id'] = $this->user_model->m_id;

            $this->load->model('member/shop_model');
            $createShopResult = $this->shop_model->update($data_post);
            if ($createShopResult === 'y') {
                error_json($createShopResult);
            } else {
                error_json($createShopResult);
            }
        } else {
            error_json($createUserResult);
        }
    }

    /**
     * 主账号修改密码
     * $data_post = [
     *      'username' => '13800138000',
     *      'old_password' => '123456',
     *      'password' => '888888',
     *      'repassword' => '888888',
     * ]
     */
    public function update_password()
    {
        if (!is_post()) {
            error_json('提交方式错误');
        }

        $data_post = $this->input->post(NULL, true);
        $member = $this->loop_model->get_where('member', ['username' => $data_post['username']]);
        if (!$member) {
            error_json('账号不存在');
        }

        if ($member['password'] != md5(md5($data_post['old_password']) . $member['salt'])) {
            error_json('原密码错误');
        } else {
            if ($data_post['password'] != $data_post['repassword']) {
                error_json('两次密码不一样');
            } else {
                $update_data['salt']     = get_rand_num();
                $update_data['password'] = md5(md5($data_post['password']) . $update_data['salt']);
                $res = $this->loop_model->update_id('member', $update_data, $member['id']);
                if ($res) {
                    error_json('y');
                } else {
                    error_json('修改失败');
                }
            }
        }
    }

    public function seller_login()
    {
        $username = trim($this->input->get('username', true));
        $redirect_url = trim($this->input->get('redirect_url', true));

        $member_data = $this->loop_model->get_where('member', array('username' => $username));
        if (empty($member_data)) {
            error_json('用户名不存在');
        }

        $shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $member_data['id']));
        if (!empty($shop_data)) {
            $shop_data = array(
                'id'       => $member_data['id'],
                'username' => $member_data['username'],
                'password' => $member_data['password'],
                'salt'     => $member_data['salt'],
                'status'   => $shop_data['status'],
                'type'     => 'member_admin',
            );
        }

        if ($shop_data['username'] == '') {
            error_json('用户名不存在');
        } elseif ($shop_data['status'] != 0 || $shop_data['admin_status'] != 0) {
            error_json('帐号被管理员锁定');
        } else {
            $this->session->set_userdata('shop_id', array(
                'shop_id' => $shop_data['id'],
                'type' => $shop_data['type'],
                'username' => $shop_data['username'],
            ));
            shop_admin_log_insert($shop_data['username'] . '登录系统');
            if (empty($redirect_url)) $redirect_url = site_url('/seller');
            redirect($redirect_url);
        }
    }
}
