<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Sms_send extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 发送手机验证码
     */
    public function send()
    {
        $mobile = trim($this->input->post('mobile', true));
        $this->load->helpers('form_validation_helper');
        if (is_mobile($mobile)) {
            $this->load->library('sms_send/sms_send_tmp');
            $res  = $this->sms_send_tmp->code($mobile);
            if ($res == 'success') {
                error_json('y');
            } else {
                error_json($res);
            }
        } else {
            error_json('手机号码格式错误请不要加0或86');
        }
    }
}
