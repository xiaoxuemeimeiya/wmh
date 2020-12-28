<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Find_password extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 信息验证
     */
    public function find_password_act()
    {
        if (is_post()) {
            $username = trim($this->input->post('username', true));
            $password = trim($this->input->post('password', true));
            $code     = (int)trim($this->input->post('code', true));
            if (!empty($username) && !empty($password)) {
                if (!empty($code)) {
                    $this->load->library('sms_send/sms_send_tmp');
                    $code_res = $this->sms_send_tmp->validation_code($username, $code);//短信验证
                    if ($code_res != 'y') {
                        error_json($code_res);
                    } else {
                        $this->load->model('loop_model');
                        $member_data = $this->loop_model->get_where('member', array('username' => $username));
                        if (!empty($member_data)) {
                            $update_data['salt']     = get_rand_num();
                            $update_data['password'] = md5(md5($password) . $update_data['salt']);
                            $res                     = $this->loop_model->update_id('member', $update_data, $member_data['id']);
                            if (!empty($res)) {
                                cache('del', 'sms_code' . $username);//删除验证码
                                error_json('y');
                            } else {
                                error_json('密码修改失败');
                            }
                        } else {
                            error_json('用户不存在');
                        }
                    }
                } else {
                    error_json('验证码不能为空');
                }
            } else {
                error_json('账号和密码不能为空');
            }
        }
    }


    /**
     * 验证会员名是否存在
     */
    public function repeat_username()
    {
        $username = $this->input->post('param', true);
        if (!empty($username)) {
            $this->load->model('member/user_model');
            $member_data = $this->user_model->repeat_username($username);
            if (!empty($member_data)) {
                error_json('y');
            } else {
                error_json('手机号码不存在');
            }
        }
    }

}
