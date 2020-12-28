<?php

defined('BASEPATH') OR exit('No direct script access allowed');

//开启调试
$CI = &get_instance();
$CI->output->enable_profiler(false);

if (!function_exists('request_verify')) {
    /**
     * api请求有效性验证
     * @param $username
     * @param $token
     * @param $timestamp
     * @return bool
     */
    function request_verify($username, $token, $timestamp)
    {
        return password_verify(config_item('authentication_string') . $username . $timestamp, $token);
    }
}