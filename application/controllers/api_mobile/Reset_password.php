<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reset_password extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 修改密码
     */
    public function reset_password()
    {
        if (is_post()) {
            $m_id = $this->input->get_post('m_id');
            $data_post = $this->input->post(NULL, true);
            if (empty($data_post['old_password'])) {
                error_json('旧密码不能为空');
            }
            if (empty($data_post['password'])) {
                error_json('新密码不能为空');
            }
            if (($data_post['old_password'] == $data_post['password'])) {
                error_json('新密码不能与旧密码相同');
            }
            if (($data_post['password'] != $data_post['password1'])) {
                error_json('两次输入密码不一样');
            }
            $member_data = $this->loop_model->get_id('member', $m_id);
            if (!empty($member_data)) {
                if ($member_data['password'] != md5(md5($data_post['old_password']) . $member_data['salt'])) {
                    error_json('旧密码错误');
                } elseif($member_data['password'] == md5(md5($data_post['password']) . $member_data['salt'])){
                    error_json('旧密码与新密码不能相同');
                }else {
                    $update_member_data['salt']     = get_rand_num();
                    $update_member_data['password'] = md5(md5($data_post['password']) . $update_member_data['salt']);
                    $res                            = $this->loop_model->update_id('member', $update_member_data, $m_id);
                    if (!empty($res)) {
                        error_json('y');
                    } else {
                        error_json('修改失败');
                    }
                }
            }
        } else {
            error_json('提交方式错误');
        }
    }

}
