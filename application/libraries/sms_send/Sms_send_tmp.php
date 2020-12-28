<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Sms_send_tmp
{

    /**
     * 验证验证码
     * @param $tel  int 电话
     * @param $code int 接收的验证码
     */
    public function validation_code($tel, $code)
    {
        if (!empty($tel) && !empty($code)) {
            $server_code = cache('get', 'sms_code' . $tel);
            if (time() - $server_code['time'] > 300) {
                return '验证码已经过期';
            } elseif ($server_code['code'] != $code) {
                return '验证码错误';
            } else {
                return 'y';
            }
        } else {
            return '验证码不能为空';
        }
    }

     /**
     * 发送验证码
     * @param $tel int 电话
     */
    public function code($tel = '')
    {
        if (!empty($tel)) {
           
            $cache_name = 'sms_code' . $tel;
            $sms_code   = cache('get', $cache_name);//查询是否已经发送
            if (!empty($sms_code) && time() - $sms_code['time'] < 60) {
                $return['code'] = 7;
                $return['msg'] = '60秒内只能发送一次';
                return $return;
            } else {
                $tmp       = "SMS_151232985";//这里是短信模板,阿里的是模板id
                $send_data = array(
                    'code' => get_rand_num('int', 6),
                    'product' => '234',
                );
                $res       = self::send_to($tel, $tmp, $send_data);
                if ($res == 'success') {
                    cache('save', $cache_name, array('code' => $send_data['code'], 'time' => time()), 600);//写入缓存
                    $retrun['code'] = 200;
                }else{
                    $return['code'] = 7;
                    $return['msg'] = $res;
                }
                return $return;
            }
           
        } else {
            $return['code'] = 7;
            $return['msg'] = '手机号码不存在';
            return $return;
        }
    }

    /**
     * 发送验证码
     * @param $tel int 电话
     */
    /*
    public function code($tel = '')
    {
        if (!empty($tel)) {
            $CI              = &get_instance();
            $code_img        = $CI->input->post('code_img', true);
            $server_code_img = $CI->session->userdata('imgcode');
            if (strtolower($code_img) != strtolower($server_code_img)) {
                return '图形验证码错误';
            } else {
                $cache_name = 'sms_code' . $tel;
                $sms_code   = cache('get', $cache_name);//查询是否已经发送
                if (!empty($sms_code) && time() - $sms_code['time'] < 60) {
                    return '60秒内只能发送一次';
                } else {
                    $tmp       = "SMS_12841881";//这里是短信模板,阿里的是模板id
                    $send_data = array(
                        'code' => get_rand_num('int', 6),
                        'product' => '三把钥匙',
                    );
                    $res       = self::send_to($tel, $tmp, $send_data);
                    if ($res == 'success') {
                        cache('save', $cache_name, array('code' => $send_data['code'], 'time' => time()), 600);//写入缓存
                    }
                    return $res;
                }
            }
        } else {
            return '手机号码不存在';
        }
    }
    */

    /**
     * 发货提醒
     * @param $tel           int 电话
     * @param $delivery_name string 快递公司名称
     * @param $delivery_code string 快递单号
     */
    public function send_order($tel = '', $delivery_name = '', $delivery_code = '')
    {
        if (!empty($tel)) {
            $tmp       = "SMS_13115129";//这里是短信模板,阿里的是模板id
            $send_data = array(
                'delivery_name' => $delivery_name,
                'delivery_code' => $delivery_code
            );
            $res       = self::send_to($tel, $tmp, $send_data);
            return $res;
        }
    }

    /**
     * 发送短信
     * @param $tel           int 电话
     * @param $tmp           string 模板或模板id
     * @param $send_data     array 发送数据
     */
    function send_to($tel, $tmp, $send_data)
    {
        if (!empty($tel) && !empty($tmp) && !empty($send_data)) {
            if (!empty(config_item('sms_appkey')) && !empty(config_item('sms_secret'))) {
                require(APPPATH . 'third_party/taobao_sdk/TopSdk.php');
                $c            = new TopClient;
                $c->appkey    = config_item('sms_appkey');
                $c->secretKey = config_item('sms_secret');
                $c->format    = 'json';
                $req          = new AlibabaAliqinFcSmsNumSendRequest;
                $req->setSmsType("normal");
                $req->setSmsFreeSignName("私学团");
                $req->setSmsParam(json_encode($send_data));
                $req->setRecNum($tel);
                $req->setSmsTemplateCode($tmp);
                $resp = $c->execute($req);
                $res  = json_decode(json_encode($resp), true);
                if ($res['result']['success'] == true) {
                    return 'success';
                } else {
                    $error = '发送失败';
                    if (!empty($res['msg'])) $error = $res['msg'];
                    return $error;
                }
            } else {
                return '请后台设置短信参数';
            }
        } else {
            return false;
        }
    }
}