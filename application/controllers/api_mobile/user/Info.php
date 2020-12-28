<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Info extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 获取用户的卡
     */
    public function get_card(){
        $getData =  $this->input->post();
        $m_id = $getData['m_id'];
        $card = $this->loop_model->get_where('card',array('m_id'=>$m_id));
        if($card){
            $this->ResArr["code"] = 200;
            $this->ResArr["data"] = $card;
            $this->ResArr["card"] = 1;
        }else{
            $this->ResArr["code"] = 200;
            $this->ResArr["card"] = 0;
        }
        echo json_encode($this->ResArr);exit;
    }

   /**
    * 用户领取试听卡
    */
    public function free_card()
    {
        $getData =  $this->input->post();
        $mobile = $getData['mobile'];
        $name = $getData['name'];
        $position = $getData['position'];
        if(!$mobile || !$name || !$position){
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = '参数缺失';
            echo json_encode($this->ResArr);exit;
        }
        $this->load->helpers('form_validation_helper');
        if(!is_mobile($mobile)){
            $this->ResArr["code"] = 6;
            $this->ResArr["msg"] = '手机号码格式错误请不要加0或86';
            echo json_encode($this->ResArr);exit;
        }
        //判断该手机是否申请过
        $checkinWhere["mobile"] = $mobile;
        $count = $this->loop_model->get_where('card',$checkinWhere);
        if($count){
            $this->ResArr["code"] = 10;
            $this->ResArr["msg"] = '改手机号已领取过';
            echo json_encode($this->ResArr);exit;
        }
        //cache('save', 'sms_code' . $mobile, array('code' => 464835, 'time' => time()), 600);//写入缓存,测试用
                    
        //判断验证码是否过期
        /*
        $server_code = cache('get', 'sms_code' . $mobile);
        if (time() - $server_code['time'] > 300) {
            $this->ResArr["code"] = 8;
            $this->ResArr["msg"] = '验证码已经过期';
            echo json_encode($this->ResArr);exit;
        } elseif ($server_code['code'] != $code) {
            $this->ResArr["code"] = 9;
            $this->ResArr["msg"] = '验证码错误';
            echo json_encode($this->ResArr);exit;
        }
        */
        $addData['m_id'] = $getData['m_id'];
        $addData['name'] = $getData['name'];
        $addData['position'] = $getData['position'];
        $addData['addtime'] = time();
        $res = $this->loop_model->insert('card',$addData);
        if($res > 0){
            $this->ResArr['code'] = 200;
            $this->ResArr['msg'] = '获取成功';
            echo json_encode($this->ResArr);exit;
        }else{
            $this->ResArr['code'] = 1001;
            $this->ResArr['msg'] = '获取失败';
            echo json_encode($this->ResArr);exit;
        } 
    }

}
