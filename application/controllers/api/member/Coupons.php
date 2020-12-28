<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Coupons extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('web_helper');
        $this->member_data = get_member_data();
        $this->load->model('loop_model');
    }

    /**
     * 优惠券兑换处理
     */
    public function exchange_save()
    {
        if (is_post()) {
            $data_post = $this->input->post(NULL, true);
            if (empty($data_post['password'])) error_json('密码不能为空');

            $coupons_detail_data = $this->loop_model->get_where('coupons_detail', array('password' => $data_post['password'], 'is_send' => 1, 'is_close' => 0, 'm_id' => 0, 'status' => 0));
            if (!empty($coupons_detail_data)) {
                //开始绑定用户
                $res = $this->loop_model->update_id('coupons_detail', array('m_id' => $this->member_data['id']), $coupons_detail_data['id']);
                if (!empty($res)) {
                    error_json('y');
                } else {
                    error_json('兑换失败');
                }
            } else {
                error_json('不存在的优惠券');
            }
        } else {
            error_json('提交方式错误');
        }
    }
}
