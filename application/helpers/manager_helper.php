<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 检测后台用户是否登陆
 */
if (!function_exists('manager_login')) {
    function manager_login()
    {
        $CI = &get_instance();
        $admin_id = (int)$CI->session->userdata('admin_id');
        if (empty($admin_id)) {
            header('location:'.site_url('/manager/login?redirect_url=' . urlencode(get_now_url())));
            exit;
        } else {
            $admin_data = array();
            $CI->load->model('loop_model');
            $admin_data = $CI->loop_model->get_id('admin', $admin_id, 'id,username,full_name,tel,role_id,status');
            if (empty($admin_data)) msg('用户信息不存在');

            //角色组
            if (!empty($admin_data['role_id'])) {
                $role = $CI->loop_model->get_where('role', array('id'=>$admin_data['role_id'],'shop_id'=>0));
                $admin_data['role'] = $role['rights'];
            }

            //判断是否有权限进入
            $segment[2] = $CI->uri->segment(2);
            $segment[3] = $CI->uri->segment(3);
            if ($segment[2] != 'welcome') $segment[4] = $CI->uri->segment(4);

            //不是超级管理员的时候开始验证权限
            if ($admin_data['role_id'] != '0') {
                $now_segment = join('/', $segment);
                if (stripos($admin_data['role'], $now_segment) !== false) {
                    //有权限
                } else {
                    error_json('你没有权限操作');
                }
            }
            return $admin_data;
        }
    }
}

/**
 * 获取后台用户登录信息,提示信息,不强制跳转
 */
if (!function_exists('get_manager_data')) {
    function get_manager_data()
    {
        $CI = &get_instance();
        $admin_id = (int)$CI->session->userdata('admin_id');
        if (!empty($admin_id)) {
            $admin_data = array();
            $CI->load->model('loop_model');
            $admin_data = $CI->loop_model->get_id('admin', $admin_id, 'id,username,full_name,tel,role_id,status');

            //角色组
            if (!empty($admin_data['role_id'])) {
                $role = $CI->loop_model->get_where('role', array('id'=>$admin_data['role_id'],'shop_id'=>0));
                $admin_data['role'] = $role['rights'];
            }
            return $admin_data;
        }
    }
}

/**
 * 后台管理员日志操作
 */
if (!function_exists('admin_log_insert')) {
    function admin_log_insert($note = '')
    {
        if (!empty($note)) {
            $CI = &get_instance();
            $admin_data = get_manager_data();
            $CI->load->model('loop_model');
            $insert_data = array(
                'admin_user' => $admin_data['username'],
                'note'       => $note,
                'addtime'    => date('Y-m-d H:i:s',time()),
            );
            $CI->loop_model->insert('admin_log', $insert_data);
        }
    }
}


