<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 更新店铺
     * @return array
     */
    public function update($data_post = array())
    {
        $update_data = array(
            'shop_name'        => $data_post['shop_name'],
            'logo'             => $data_post['logo'],
            'tel'              => $data_post['tel'],
            'email'            => $data_post['email'],
            'customer_url'     => $data_post['customer_url'],
            'business_license' => $data_post['business_license'],
            'prov'             => $data_post['prov'],
            'city'             => $data_post['city'],
            'area'             => $data_post['area'],
            'address'          => $data_post['address'],
            'desc'             => $data_post['desc'],
        );

        if (empty($update_data['shop_name'])) return '店铺名称不能为空';
        if (empty($update_data['logo'])) return '店铺LOGO不能为空';
        if (empty($update_data['business_license'])) return '店铺营业执照不能为空';
        $this->load->model('loop_model');
        if (!empty($data_post['m_id'])) {
            //查询是否会员是否注册
            $member_data = $this->loop_model->get_id('member', $data_post['m_id']);
            if (!empty($member_data)) {
                $member_shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $data_post['m_id']));
                if (!empty($member_shop_data)) {
                    //修改
                    $update_data['endtime'] = time();
                    $res                    = $this->loop_model->update_where('member_shop', $update_data, array('m_id' => $data_post['m_id']));
                } else {
                    //增加
                    $update_data['addtime'] = time();
                    $update_data['endtime'] = time();
                    $update_data['m_id']    = $data_post['m_id'];
                    $res                    = $this->loop_model->insert('member_shop', $update_data);
                }
                if (!empty($res)) {
                    return 'y';
                } else {
                    return '保存失败';
                }
            } else {
                return '请先注册成为会员';
            }
        }
    }


    /**
     * 计算店铺等级
     * @param int $goods_comment 好评数
     * @return int
     */
    function shop_level($goods_comment = '')
    {
        if (!empty($goods_comment)) {
            return ceil($goods_comment / 100);
        }
        return 1;
    }
}
