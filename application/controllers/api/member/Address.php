<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Address extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('web_helper');
        $this->member_data = get_member_data();
        $this->load->model('loop_model');
    }

    /**
     * 查询用户收货地址
     */
    public function index()
    {
        $list = $this->loop_model->get_list('member_user_address', array('where' => array('m_id' => $this->member_data['id'])), '', '', 'is_default desc,id asc');
        if (!empty($list)) {
            $this->load->model('areas_model');
            foreach ($list as $key) {
                $area_name       = $this->areas_model->get_name(array($key['prov'], $key['city'], $key['area']));
                $key['prov_str'] = $area_name[$key['prov']];
                $key['city_str'] = $area_name[$key['city']];
                $key['area_str'] = $area_name[$key['area']];
                $address_list[]  = $key;
            }
            error_json($address_list);
        } else {
            error_json('没有数据');
        }
    }

    /**
     * 根据id查询收货地址
     */
    public function get_id()
    {
        $id = $this->input->get_post('id', true);
        if (empty($id)) error_json('id错误');
        $this->load->model('member/user_address_model');
        $address_data = $this->user_address_model->get_address(array('id' => $id, 'm_id' => $this->member_data['id']));

        if (!empty($address_data)) {
            error_json($address_data);
        } else {
            error_json('没有数据');
        }
    }

    /**
     * 添加修改收货地址
     */
    public function edit()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'full_name'  => $data_post['full_name'],
                'prov'       => (int)$data_post['prov'],
                'city'       => (int)$data_post['city'],
                'area'       => (int)$data_post['area'],
                'address'    => $data_post['address'],
                'tel'        => $data_post['tel'],
                'is_default' => (int)$data_post['is_default'],
            );

            if (empty($update_data['full_name'])) error_json('姓名不能为空');
            if (empty($update_data['prov']) || empty($update_data['city']) || empty($update_data['area'])) error_json('请选择省市区');
            if (empty($update_data['address'])) error_json('详细地址不能为空');
            if (empty($update_data['tel'])) error_json('电话号码不能为空');
            $this->load->helpers('form_validation_helper');
            if (!is_mobile($update_data['tel'])) {
                error_json('手机号码格式错误');
            }

            //设为默认地址的时候先设置其他的地址为非默认
            if ($update_data['is_default'] == 1) {
                $this->loop_model->update_where('member_user_address', array('is_default' => 0), array('m_id' => $this->member_data['id']));
            }

            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_where('member_user_address', $update_data, array('id' => $data_post['id'], 'm_id' => $this->member_data['id']));
            } else {
                //增加数据
                $update_data['m_id'] = $this->member_data['id'];
                $res                 = $this->loop_model->insert('member_user_address', $update_data);
            }
            if (!empty($res)) {
                $this->load->model('areas_model');
                $area_name               = $this->areas_model->get_name(array($update_data['prov'], $update_data['city'], $update_data['area']));
                $update_data['prov_str'] = $area_name[$update_data['prov']];
                $update_data['city_str'] = $area_name[$update_data['city']];
                $update_data['area_str'] = $area_name[$update_data['area']];
                error_json(array($update_data));
            } else {
                error_json('保存失败');
            }
        }
    }

    /**
     * 删除用户地址
     */
    public function delete()
    {
        if (is_post()) {
            $id = $this->input->get_post('id', true);
            if (empty($id)) error_json('id错误');
            $res = $this->loop_model->delete_where('member_user_address', array('id' => $id, 'm_id' => $this->member_data['id']));

            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('删除失败');
            }
        }
    }


    /**
     * 设置为默认地址
     */
    public function set_default()
    {
        if (is_post()) {
            $id = $this->input->get_post('id', true);
            if (empty($id)) error_json('id错误');
            //设为默认地址的时候先设置其他的地址为非默认
            $this->loop_model->update_where('member_user_address', array('is_default' => 0), array('m_id' => $this->member_data['id']));

            $res = $this->loop_model->update_where('member_user_address', array('is_default' => 1), array('id' => $id, 'm_id' => $this->member_data['id']));
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('设置失败');
            }
        }
    }

}
