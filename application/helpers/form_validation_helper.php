<?php

defined('BASEPATH') OR exit('No direct script access allowed');


/**
 * is_username 匹配帐号是否合法(字母开头，默认允许6-16字节【有效位数可自由定制】，允许字母数字下划线)
 * @param string $str 帐号字符串
 * @param int $minlen 最小长度，默认是6。
 * @param int $maxlen 最大长度，默认是16。
 * @return bool 验证通过返回 true 不通过返回 false
 */
if (!function_exists('is_username')) {
    function is_username($str, $minlen = 4, $maxlen = 16)
    {
        return (bool)preg_match('/^[a-zA-Z][a-zA-Z0-9_]{' . $minlen . ',' . $maxlen . '}$/i', $str);
    }
}

/**
 * is_mobile 验证手机号码
 * @param str $mobile 手机号码
 * @return bool 验证通过返回 true 不通过返回 false
 */
if (!function_exists('is_mobile')) {
    function is_mobile($mobile = '')
    {
        if (!is_numeric($mobile)) {
            return false;
        }
        //return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
        return preg_match('#^1[\d]{10}$#', $mobile) ? true : false;
    }
}

/**
 * is_email 验证邮箱
 * @param str $email 邮箱
 * @return bool 验证通过返回 true 不通过返回 false
 */
if (!function_exists('is_email')) {
    function is_email($email = '')
    {
        return (bool)preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)+$/i', $email);
    }
}

/**
 * is_url 验证网址
 * @param str $url 网址
 * @return 验证通过返回 true 不通过返回 false
 */
if (!function_exists('is_url')) {
    function is_url($url = '')
    {
        return preg_match('/^(\w+:\/\/)?\w+(\.\w+)+.*$/', $url) ? true : false;
    }
}

