<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Message_template
{

    /**
     * 推荐用户成功消息
     * @param str $openid      用户openid
     * @param str $flag_user   推荐人
     * @param str $recommended 被推荐人
     * @param str $url         跳转地址
     */
    function flag_user($openid, $flag_user, $recommended, $url = '')
    {
        if (empty($url)) $url = site_url('/web');
        if (!empty($openid) && !empty($flag_user) && !empty($recommended) && !empty($url)) {
            $data = array(
                'first'    => array(
                    'value' => '你已经成功推荐用户注册',
                    'color' => '#173177',
                ),
                'keyword1' => array(
                    'value' => $flag_user,
                    'color' => '#173177',
                ),
                'keyword2' => array(
                    'value' => $recommended,
                    'color' => '#173177',
                ),
            );
            return self::send_message($openid, config_item('wx_flag_sms_tmp_id'), $url, $data);
        }
        return false;
    }

    /**
     * 推荐用户成功消息
     * @param str $openid 用户openid
     * @param str $price  返现金额
     * @param str $url    跳转地址
     */
    function share_amount($openid, $price, $url = '')
    {
        if (empty($url)) $url = site_url('/web');
        if (!empty($openid) && !empty($price) && !empty($url)) {
            $data = array(
                'first'    => array(
                    'value' => '尊敬的用户您好，您的一笔返现已到账。',
                    'color' => '#173177',
                ),
                'keyword1' => array(
                    'value' => $price.'元',
                    'color' => '#173177',
                ),
                'keyword2' => array(
                    'value' => '你推荐的用户已经确认订单',
                    'color' => '#173177',
                ),
            );
            return self::send_message($openid, config_item('wx_cashback_sms_tmp_id'), $url, $data);
        }
        return false;
    }

    /**
     * 发送消息
     * @param str $openid      用户openid
     * @param str $template_id 模板id
     * @param str $url         跳转地址
     * @param str $data        要发送的数据
     */
    function send_message($openid, $template_id, $url, $data)
    {
        if (!empty($openid) && !empty($template_id) && !empty($url) && !empty($data)) {
            $CI = &get_instance();
            $CI->load->helpers('wechat_helper');
            $token     = get_grobal_token();
            $post_data = array(
                'touser'      => $openid,
                'template_id' => $template_id,
                'url'         => $url,
                'data'        => $data,
            );
            $res       = curl_post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token['access_token'], json_encode($post_data));
            return $res;
        }
        return false;
    }
}