<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Payment extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 查询所有支付方式
     */
    public function index()
    {
        $this->load->model('system/payment_model');
        $list = $this->payment_model->payment_list();
        if (!empty($list)) {
            error_json($list);
        } else {
            error_json('没有数据');
        }
    }

    /**
     * 查询充值支付方式
     */
    public function online_recharge_list()
    {
        $this->load->model('system/payment_model');
        $list = $this->payment_model->payment_list('online_recharge');
        if (!empty($list)) {
            error_json($list);
        } else {
            error_json('没有数据');
        }
    }

    /**
     * 查询在线支付方式
     */
    public function online_pay_list()
    {
        $this->load->model('system/payment_model');
        $list = $this->payment_model->payment_list('online_pay');
        if (!empty($list)) {
            error_json($list);
        } else {
            error_json('没有数据');
        }
    }
}
