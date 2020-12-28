<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Upload extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 上传
     */
    public function index()
    {
        //根据请求之地址判断是否登录
        $user_type = $this->input->get_post('user_type', true);
        if ($user_type == 'manager') {
            $this->load->helpers('manager_helper');
            $member_data = get_manager_data();
        } else if ($user_type == 'shop') {
            $this->load->helpers('shop_helper');
            $member_data = get_shop_data();
        }  else if ($user_type == 'seller') {
            //商户验证
            $this->load->helpers('shop_helper');
            $member_data = get_shop_data();
        } else {
            $this->load->helpers('web_helper');
            $member_data = get_member_data();
        }
        //只有登录用户存在的时候才能上传
        if (!empty($member_data)) {
            $file_name = $this->input->get_post('file_name', true);//上传文件的文本域名称
            if (empty($file_name)) $file_name = 'file';
            $width       = (int)$this->input->get_post('width', true);//裁剪的宽度
            $height      = (int)$this->input->get_post('height', true);//裁剪的宽度
            $crop        = (int)$this->input->get_post('crop', true);//裁剪的宽度
            $orientation = (int)$this->input->get_post('orientation', true);//图片方向
            $this->load->model('upload_model');
            $res = $this->upload_model->upload($file_name, $width, $height, $crop, $orientation);
            echo json_encode($res);
        }
    }

    /**
     * 编辑器上传
     */
    public function editor_upload()
    {
        //根据请求之地址判断是否登录
        $user_type = $this->input->get_post('user_type', true);
        if ($user_type == 'manager') {
            $this->load->helpers('manager_helper');
            $member_data = get_manager_data();
        } else if ($user_type == 'shop') {
            $this->load->helpers('shop_helper');
            $member_data = get_shop_data();
        } else {
            $this->load->helpers('web_helper');
            $member_data = get_member_data();
        }
        //只有登录用户存在的时候才能上传
        if (!empty($member_data)) {
            $file_name = $this->input->get_post('file_name', true);//上传文件的文本域名称
            if (empty($file_name)) $file_name = 'imgFile';
            $this->load->model('upload_model');
            $res = $this->upload_model->upload($file_name);//print_r($res);
            if ($res['status'] == 'success') {
                $res_data['url']   = $res['url'];
                $res_data['error'] = 0;
            } else {
                $res_data['error']   = 1;
                $res_data['message'] = $res['error'];
            }
            echo json_encode($res_data);
        }
    }
}
