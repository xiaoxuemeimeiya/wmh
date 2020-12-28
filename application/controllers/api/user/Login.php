<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 开始登录
     */
    public function user_login()
    {
        if (is_post()) {
            $username = trim($this->input->post('username', true));
            $password = trim($this->input->post('password', true));
            if (!empty($username) && !empty($password)) {
                $member_data = $this->loop_model->get_where('member', array('username' => $username));
                if ($member_data['username'] == '') {
                    error_json('用户名不存在');
                } elseif ($member_data['password'] != md5(md5($password) . $member_data['salt'])) {
                    error_json('密码错误');
                } else {
                    $member_user_data = $this->loop_model->get_where('member_user', array('m_id' => $member_data['id']));
                    if ($member_user_data['status'] == 0) {
                        $this->loop_model->update_where('member_user', array('endtime' => time()), array('m_id' => $member_data['id']));
                        //设置登录信息
                        if (config_item('safe_type') == 'cookie') {
                            $this->input->set_cookie('m_id', encrypt($member_data['id']), config_item('safe_time'));
                        } else {
                            $this->session->set_userdata('m_id', $member_data['id']);
                        }
                        error_json('y');
                    } else {
                        error_json('账户已经被锁定或删除');
                    }

                }
            } else {
                error_json('账号和密码不能为空');
            }
        }
    }

}
