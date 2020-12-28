<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 检测商家后台用户是否登陆
 */
if (!function_exists('shop_login')) {
    function shop_login()
    {
        $CI = &get_instance();
        $shop_id = $CI->session->userdata('shop_id');
        if (empty($shop_id)) {
            header('location:'.site_url('/seller/login?redirect_url=' . urlencode(get_now_url())));
            exit;
        } else {
            $shop_data = array();
            $CI->load->model('loop_model');
            if ($shop_id['type']=='member_admin') {
                //主账号登录
                $shop_data = $CI->loop_model->get_where('member_shop', array('m_id'=>$shop_id['shop_id']), 'm_id,shop_name');
                if (empty($shop_data)) {
                    echo '店铺信息不存在';exit;
                }
                $member_data = $CI->loop_model->get_id('member', $shop_data['m_id'], 'id,username');
                $shop_data['username'] = $member_data['username'];
                $shop_data['id']       = $member_data['id'];
                $shop_data['role_id']  = 0;
            } elseif($shop_id['type']=='member') {
                //子账户登录
                $shop_admin_data = $CI->loop_model->get_id('member_shop_admin', $shop_id['shop_id'], 'username,role_id,shop_id');
                $shop_data = $CI->loop_model->get_where('member_shop', array('m_id'=>$shop_admin_data['shop_id']), 'm_id as id,shop_name');
                $shop_data['username'] = $shop_admin_data['username'];
                $shop_data['role_id']  = $shop_admin_data['role_id'];
            }

            //角色组
            if (!empty($admin_data['role_id'])) {
                $role = $CI->loop_model->get_where('role', array('id'=>$shop_data['role_id'],'shop_id'=>$shop_data['id']));
                $shop_data['role'] = $role['rights'];
            }

            //判断是否有权限进入
            $segment[2] = $CI->uri->segment(2);
            $segment[3] = $CI->uri->segment(3);
            if ($segment[2] != 'welcome') $segment[4] = $CI->uri->segment(4);

            //不是超级管理员的时候开始验证权限
            if ($shop_data['role_id'] != '0') {
                $now_segment = join('/', $segment);
                if (stripos($shop_data['role'], $now_segment) !== false) {
                    //有权限
                } else {
                    error_json('你没有权限操作');
                }
            }
            return $shop_data;
        }
    }
}

/**
 * 获取商家用户登录信息,提示信息,不强制跳转
 */
if (!function_exists('get_shop_data')) {
    function get_shop_data()
    {
        $CI = &get_instance();
        $shop_id = $CI->session->userdata('shop_id');
        if (!empty($shop_id)) {
            $shop_data = array();
            $CI->load->model('loop_model');
            if ($shop_id['type']=='member_admin') {
                //主账号登录
                $shop_data = $CI->loop_model->get_where('member_shop', array('m_id'=>$shop_id['shop_id']), 'm_id,shop_name');
                if (!empty($shop_data)) {
                    $member_data = $CI->loop_model->get_id('member', $shop_data['m_id'], 'id,username');
                    $shop_data['username'] = $member_data['username'];
                    $shop_data['id']       = $member_data['id'];
                    $shop_data['role_id']  = 0;
                }
            } elseif($shop_id['type']=='member') {
                //子账户登录
                $shop_admin_data = $CI->loop_model->get_id('member_shop_admin', $shop_id['shop_id'], 'username,role_id,shop_id');
                $shop_data = $CI->loop_model->get_where('member_shop', array('m_id'=>$shop_admin_data['shop_id']), 'm_id as id,shop_name');
                $shop_data['username'] = $shop_admin_data['username'];
                $shop_data['role_id']  = $shop_admin_data['role_id'];
            }

            //角色组
            if (!empty($admin_data['role_id'])) {
                $role = $CI->loop_model->get_where('role', array('id'=>$shop_data['role_id'],'shop_id'=>$shop_data['id']));
                $shop_data['role'] = $role['rights'];
            }
            return $shop_data;
        }
    }
}

/**
 * 商家管理员日志操作
 */
if (!function_exists('shop_admin_log_insert')) {
    function shop_admin_log_insert($note = '')
    {
        if (!empty($note)) {
            $CI = &get_instance();
            $shop_data = get_shop_data();
            $CI->load->model('loop_model');
            $insert_data = array(
                'admin_user' => $shop_data['username'],
                'note'       => $note,
                'addtime'    => date('Y-m-d H:i:s',time()),
                'shop_id'    => $shop_data['id'],
            );
            $CI->loop_model->insert('member_shop_admin_log', $insert_data);
        }
    }
}


