<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 获取全局token
 */
if( ! function_exists('get_grobal_token'))
{
    function get_grobal_token(){
        $appid  = config_item('wx_appid');//appid
        $secret = config_item('wx_secret');//secret

        $grobal_token = cache('get', 'wx_grobal_token');
        if($grobal_token == ''){
            $json_grobal_token = curl_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret);
            $grobal_token = json_decode($json_grobal_token, true);
            cache('save', 'wx_grobal_token', $grobal_token, 7200);
        }
        return $grobal_token;
    }
}

if( ! function_exists('lyLog')) {
//自定义系统写入
    function lyLog($text, $dir = "", $byday = false)
    {
        $filename = $byday == true ? date("Ymd") : date("Y-m");
        $log_filename = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] . '/runtime/syslog/' . date('Ymd', time()) . '/');
        is_dir($log_filename) OR mkdir($log_filename, 0777, true);
        $log_filename = $log_filename . $dir . $filename . '.log';
        if (!empty ($text)) {
            $fileType = mb_detect_encoding($text, array('UTF-8', 'GBK', 'GB2312', 'LATIN1', 'BIG5'));
            if ($fileType != 'UTF-8') {
                $text = mb_convert_encoding($text, 'UTF-8', $fileType);
            }
        }
        try {
            file_put_contents($log_filename, date("Y-m-d H:i:s") . " \r\n " . $text . "\r\n", FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
        }
    }
}

/**
 * 获取jssdk签名
 */
if( ! function_exists('get_jsapi_ticket')) {
    function get_jsapi_ticket()
    {
        $appid  = config_item('wx_appid');//appid
        $secret = config_item('wx_secret');//secret
        $jsapi_ticket = cache('get', 'wx_jsapi_ticket');//取得缓存
        if ($jsapi_ticket == '') {
            $token = get_grobal_token($appid, $secret);
            $json_jsapi_ticket = curl_get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$token['access_token'].'&type=jsapi');
            $jsapi_ticket = json_decode($json_jsapi_ticket, true);
            cache('save', 'wx_jsapi_ticket', $jsapi_ticket, 7200);//放入缓存
        }
        $string = array(
            'jsapi_ticket' => $jsapi_ticket['ticket'],
            'noncestr'     => get_rand_num('str', 10),
            'timestamp'    => time(),
            'url'          => urldecode(get_now_url())
        );
        //组合字符串
        $connect = $str = '';
        foreach($string as $val=>$key){
            $str .= $connect.$val.'='.$key;
            $connect = '&';
        }
        $signature = sha1($str);
        $string['signature'] = $signature;
        $string['appid']     = $appid;//print_r($string);
        assign('js_sdk', $string);
        return $string;
    }
}

/**
 * 获取微信所有用户信息
 */
if( ! function_exists('get_userinfo')) {
    function get_userinfo()
    {
        $appid  = config_item('wx_appid');//appid
        $secret = config_item('wx_secret');//secret

        $CI = &get_instance();
        $user_agent = $CI->input->user_agent();//print_r($user_agent);exit;
        if(strpos($user_agent, 'MicroMessenger')!== false) {
            $user_data = json_decode(decrypt($CI->input->cookie('wx_userinfo')), true);

            if(empty($user_data)){
                $code = $CI->input->get('code',true);
                if($code == '')
                {
                    $redirect_uri = urlencode(get_now_url());//授权后的跳转连接
                    $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
                    header("location:$url");exit;
                }else{
                    $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
                    $get_token = curl_get($url);
                    $token = json_decode($get_token,true);//通过code换取网页授权access_token
                    curl_get('https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$appid.'&grant_type=refresh_token&refresh_token='.$token['refresh_token']);//刷新access_token
                    $get_user = curl_get('https://api.weixin.qq.com/sns/userinfo?access_token='.$token['access_token'].'&openid='.$token['openid'].'&lang=zh_CN');
                    $user_data = json_decode($get_user,true);
                    if($user_data['openid']==''){
                        msg('用户信息获取失败', 'stop');
                    }else{
                        $CI->input->set_cookie('wx_userinfo', encrypt(json_encode($user_data)), 3600*24);
                    }
                }
            }
            //查询用户信息,没有用户信息就注册新用户
            $weixin_member = reg_weixin_member($user_data);
            return $weixin_member;//启用微信登录
            //return $user_data;//不启用微信登录
        }
        else
        {
            return false;
        }
    }
}

/**
 * 只获取微信用户openid
 */
if( ! function_exists('get_openid')) {
    function get_openid()
    {
        $appid  = config_item('wx_appid');//appid
        $secret = config_item('wx_secret');//secret

        $CI = &get_instance();
        $openid = $CI->input->cookie('wx_openid');
        if(empty($openid)){
            $code = $CI->input->get('code');
            if($code == '')
            {
                $redirect_uri = urlencode(get_now_url());//授权后的跳转连接
                $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
                header("location:$url");exit;
            }else{
                $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
                $get_token = curl_get($url);
                $token = json_decode($get_token,true);//通过code换取网页授权access_token
                if($token['openid']==''){
                    msg('用户信息获取失败', 'stop');
                }else{
                    $openid = $token['openid'];
                    $CI->input->set_cookie('wx_openid', $openid, 3600*24);
                }
            }
        }
        return $openid;
    }
}

/**
 * 注册微信用户信息
 */
if (!function_exists('reg_weixin_member')) {
    function reg_weixin_member($userinfo)
    {
        $CI = &get_instance();
        if(!empty($userinfo)) {
            $member_data = array();
            //获取登录信息
            if(config_item('safe_type')=='cookie') {
                $m_id = (int)decrypt($CI->input->cookie('m_id', true));
            } else {
                $m_id = (int)$CI->session->userdata('m_id');
            }
            if (empty($m_id)) {
                $CI->load->model('loop_model');
                //查新第三方用户是否存在
                $member_oauth = $CI->loop_model->get_where('member_oauth', array('oauth_type'=>'wechat','oauth_id'=>$userinfo['openid']));
                if (!empty($member_oauth)) {
                    //存在用户信息
                    $m_id = $member_oauth['m_id'];
                } else {
                    $flag_user = decrypt($CI->input->cookie('flag_user'));//推荐人id
                    //不存在用户开始注册
                    $member_data = array(
                        'username'   => 'wx_'.date('mdHis', time()).get_rand_num('str', 5),
                        'password'   => get_rand_num('str', 15),
                        'headimgurl' => $userinfo['headimgurl'],
                        'flag_user'  => $flag_user,
                        'full_name'  => $userinfo['nickname'],
                        'sex'        => $userinfo['sex'],
                    );
                    $CI->load->model('member/user_model');
                    $res = $CI->user_model->update($member_data);
                    if (!empty($res)) {
                        //查询用户id
                        $new_member_data = $CI->loop_model->get_where('member', array('username'=>$member_data['username']));
                        if (!empty($new_member_data)) {
                            //绑定第三方用户
                            $member_oauth = array(
                                'oauth_type' => 'wechat',
                                'oauth_id'   => $userinfo['openid'],
                                'm_id'       => $new_member_data['id'],
                                'addtime'    => time(),
                            );
                            $oauth_id = $CI->loop_model->insert('member_oauth', $member_oauth);
                            if (empty($oauth_id)) {
                                msg('用户绑定失败');
                            } else {
                                $m_id = $new_member_data['id'];
                            }
                        } else {
                            msg('用户信息查询失败');
                        }
                    } else {
                        msg('用户注册失败');
                    }
                }
                //设置登录信息
                if (!empty($m_id)) {
                    //设置登录信息
                    if (config_item('safe_type') == 'cookie') {
                        $CI->input->set_cookie('m_id', encrypt($m_id), config_item('safe_time'));
                    } else {
                        $CI->session->set_userdata('m_id', $m_id);
                    }
                } else {
                    msg('用户信息不存在');
                }
            }
            return $m_id;
        } else {
            msg('用户信息获取失败');
        }
    }
}
if(!function_exists('wx_userinfo')){
    require(APPPATH . 'third_party/wx/wxBizDataCrypt.php');
    $appid = 'wx4f4bc4dec97d474b';
    $sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

    $encryptedData="CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                Db/XcxxmK01EpqOyuxINew==";

    $iv = 'r7BXXKkLb8qrSNn05n0qiA==';

    $pc = new WXBizDataCrypt($appid, $sessionKey);
    $errCode = $pc->decryptData($encryptedData, $iv, $data );

    if ($errCode == 0) {
        print($data . "\n");
    } else {
        print($errCode . "\n");
    }
}
