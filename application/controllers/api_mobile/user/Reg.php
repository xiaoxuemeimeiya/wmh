<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reg extends CI_Controller
{
    protected $api_login = "https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code";
    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $this->load->model('loop_model');
        $this->ResArr = [];
        //code 换取 session_key
    }

    public function index(){
        //$postData = $this->input->post();
        try{
            $re = $this->deal();
            $this->ResArr['data'] = $re;
        }catch (\Exception $exception){
            $this->ResArr['msg'] = $exception->getMessage();
            $this->ResArr['code'] = $exception->getCode();
        }
        echo json_encode($this->ResArr);

    }

    //处理授权
    private function deal()
    {
        $code = $this->input->post('code');
        if(empty($code)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo json_encode($this->ResArr);exit;
        }
        //获取appid,appsecrect
        $smallapp_appid  = config_item('miniApp_appid');//appid
        $smallapp_secret = config_item('miniApp_secret');//secret
        if(!empty($smallapp_appid) && !empty($smallapp_secret) ){
            $api_rul = str_replace(array("APPID","SECRET","JSCODE"),array($smallapp_appid,$smallapp_secret,$code),$this->api_login);
            $result = curl_get($api_rul);
            $info = json_decode($result,true);
            if(isset($info["errcode"])){
                $this->ResArr['code'] = $info["errcode"];
                $this->ResArr['msg'] = $info["errmsg"];
                echo json_encode($this->ResArr);exit;
            }
            $info["userin"] = false;
            //会话密钥
            if(isset($info['session_key']) ) {
                $addDAta["openid"] = isset($info["openid"])?$info["openid"]:"";
                $addDAta["unionid"] = isset($info["unionid"])?$info["unionid"]:"";
                $addDAta['scribe_status']       = 0;
                $addDAta['scribe_time']         = time();

                //判断用户信息是否存在
                $checkinWhere["openid"] = isset($info["openid"])?$info["openid"]:"";
                $hasCheckin = $this->loop_model->get_where('user', $checkinWhere);
                if ($hasCheckin) {
                    //已注册
                    $info["userin"] = true;
                    $user_data = $this->loop_model->get_where('user', array('openid' => $info["openid"]));
                    $this->loop_model->update_where('user', array('endtime' => time()), array('id' => $user_data['id']));
                    $salt = substr(uniqid(), -6);
                    $token = md5($user_data['id'] . $user_data['openid']);
                    $is_register = 0;
                    $is_bind = false;
                    if($user_data['is_true'] == 1 || isset($user_data['nickname'])){
                        $is_register = 1;
                    }
                    if($user_data['top_id'] != null && $user_data['top_id'] > 1 ){
                        $is_bind = true;
                    }
                    //手机号是否绑定
                    $phone = $this->loop_model->get_where('phone',['m_id'=>$user_data['id']],'phone');
                    $tokenData = [
                        'm_id' => $user_data['id'],
                        'token' => $token,
                        'salt' => $salt,
                        'phone'=>$phone['phone'],
                        'expire' => time() + 5 * 24 * 3600,
                        'session_key'=>$info['session_key'],
                        'is_register' => $is_register,
                        'is_bind' => $is_bind
                    ];
                    cache('save', 'user_token_' . $user_data['id'], $token, time() + 30 * 24 * 3600);//保存token
                    $this->ResArr['code'] = 200;
                    $this->ResArr['data'] = $tokenData;
                    echo json_encode($this->ResArr);exit;
                } else {
                    //用户不存在
                    $addDAta["is_true"] = 0;
                    $res = $this->loop_model->insert('user',$addDAta);
                    $info["userin"] = true;
                    $user_data = $this->loop_model->get_where('user', array('openid' => $addDAta["openid"]));
                    $salt = substr(uniqid(), -6);
                    $token = md5($user_data['id'] . $user_data['openid']);
                    $tokenData = [
                        'm_id' => $user_data['id'],
                        'token' => $token,
                        'salt' => $salt,
                        'expire' => time() + 5 * 24 * 3600,
                        'session_key'=>$info['session_key'],
                        'phone'=>'',
                        'is_register' => 0,
                        'is_bind' => false
                    ];
                    cache('save', 'user_token_' . $user_data['id'], $token, time() + 30 * 24 * 3600);//保存token
                    $this->ResArr['code'] = 200;
                    $this->ResArr['data'] = $tokenData;
                    echo json_encode($this->ResArr);exit;
                    /*
                    $this->ResArr['code'] = 5;
                    $this->ResArr['data'] = $info;
                    echo json_encode($this->ResArr);exit;
                    */
                }
            }else{
                //拒绝会话密钥
                $this->ResArr['code'] = 4;
                $this->ResArr['msg'] = '用户拒绝授权';
                echo json_encode($this->ResArr);exit;
            }

        }else{
            $this->ResArr['code'] = 1002;
            $this->ResArr['msg'] = '配置异常';
            echo json_encode($this->ResArr);exit;
        }
    }

    /**
    *获取用户信息
     */
    public function userinfo()
    {
        $getData =  $this->input->post();
        if(sizeof($getData)=="0"){
            $this->ResArr["code"] = "3";
            $this->ResArr["msg"] = "参数不能为空！";
            echo json_encode($this->ResArr);exit;
        }

        require(APPPATH . 'third_party/wx/wxBizDataCrypt.php');
        $smallapp_appid  = config_item('miniApp_appid');//appid
        $smallapp_secret = $getData['session_key'];//会话密钥
        $encryptedData = $getData["encryptedData"];
        $iv = $getData["iv"];
        $pc = new \WXBizDataCrypt($smallapp_appid, $smallapp_secret);
        $errCode = $pc->decryptData($encryptedData, $iv, $data );
        if ($errCode == 0) {
            $data = json_decode($data,true);
            if(!isset($data["openId"])||strlen($data["openId"])=="0"){
                $this->ResArr["code"] = "40002";
                $this->ResArr["msg"] = "error,checkout sid!".$data["openId"];
                echo json_encode($this->ResArr);exit;
            }
            $sex  = ($data["gender"]==1)?"1":"0";
            $addData['appid']               = $smallapp_appid;
            $addData['unionid']             = $data['unionId'];
            $addData['nickname']            = $data['nickName'];
            $addData['wechat_province']     = $data['province'];
            $addData['country']             = $data['country'];
            $addData['headimgurl']          = $data['avatarUrl'];
            $addData['scribe_status']       = "0";
            $addData['scribe_time']         = time();
            $addData['wechat_sex']          = $sex;
            $addData['is_true']             = 1;

            /*
            //加入分销功能
            if(isset($getData["top_openid"])||!empty($getData["top_openid"])){

                $userinfo = $this->loop_model->get_where("user",["openid"=>$getData["top_openid"]],'id')->find();
                if($userinfo){
                    $addData['top_openid'] = $userinfo["id"];
                }
            }
            */
            $findopenid = $this->loop_model->get_where('user',["openid"=>$data['openId']]);
            if(isset($getData["top_id"])&&!empty($getData["top_id"])){
                $userinfo = $this->loop_model->get_where("user",["id"=>$getData["top_id"]],'id');

                if($userinfo){
                    //查看是否被绑定
                    $binddata = $this->loop_model->get_where("user_bind",["bind_id"=>$findopenid["id"]]);
                    if($binddata){
                        //未过期
                        if(time() - $binddata['addtime'] < 180*24*3600){
                            //提示该用户已被绑定
                        }else{
                            //已过期
                            $res = $this->loop_model->update_where("user",['top_id'=>''],["openid"=>$data['openId']]);
                            $res = $this->loop_model->update_where("user_bind",['status'=>2],["id"=>$binddata['id']]);
                            //新插入
                            $bind['m_id'] = $userinfo["id"];
                            $bind['bind_id'] = $findopenid['id'];
                            $bind['addtime'] = time();
                            $res = $this->loop_model->insert("user_bind",$bind);

                            $addData['top_id'] = $userinfo["id"];
                        }
                    }else{
                        //新插入
                        $bind['m_id'] = $userinfo["id"];
                        $bind['bind_id'] = $findopenid['id'];
                        $bind['addtime'] = time();
                        $res = $this->loop_model->insert("user_bind",$bind);
                        $addData['top_id'] = $userinfo["id"];
                    }
                }else{

                    $addData['top_id'] = $userinfo["id"];
                }
            }

            if($findopenid){
                $res = $this->loop_model->update_where("user",$addData,["openid"=>$data['openId']]);
            }else{
                $addData['openid']              = $data['openId'];
                 $res = $this->loop_model->insert('user',$addData);
            }
            if($res > 0){
                $is_bind = false;
                if($findopenid['top_id'] != null && $findopenid['top_id'] > 1 ){
                    $is_bind = true;
                }
				if(isset($getData["top_id"])&&!empty($getData["top_id"]) ){
                    $is_bind = true;
                }
                $this->ResArr["code"] = 200;
                $salt = substr(uniqid(), -6);
                $openid = $this->loop_model->get_where('user',["openid"=>$data['openId']]);
                $token = md5($openid['id'] . $openid['openid']);
                $tokenData = [
                    'm_id' => $openid['id'],
                    'token' => $token,
                    'salt' => $salt,
                    'expire' => time() + 5 * 24 * 3600,
                    'is_register' => 1,
                    'is_bind' => $is_bind
                ];
                //cache('save', 'user_token_' . $openid['id'], $token, time() + 30 * 24 * 3600);//保存token
                $this->ResArr['code'] = 200;
				$this->ResArr['data'] = $tokenData;
                //$this->ResArr['data'] = $tokenData;
                $this->ResArr['msg'] = '数据更新成功';
                echo json_encode($this->ResArr);
            }else{
                $this->ResArr['code'] = 1001;
                $this->ResArr['msg'] = '数据更新失败';
                echo json_encode($this->ResArr);
            }

        } else {
            $this->ResArr["code"] = $errCode;
            $this->ResArr["msg"] = '获取用户信息失败';
            echo json_encode($this->ResArr);
        }

    }

     /**
    *获取用户手机
     */
    public function phone()
    {
        $getData =  $this->input->post();
        if(sizeof($getData)=="0"){
            $this->ResArr["code"] = "3";
            $this->ResArr["msg"] = "参数不能为空！";
            echo json_encode($this->ResArr);exit;
        }

        require(APPPATH . 'third_party/wx/wxBizDataCrypt.php');
        $smallapp_appid  = config_item('miniApp_appid');//appid
        $smallapp_secret = $getData['session_key'];//会话密钥
        $encryptedData = $getData["encryptedData"];
        $iv = $getData["iv"];
        $pc = new \WXBizDataCrypt($smallapp_appid, $smallapp_secret);
        $errCode = $pc->decryptData($encryptedData, $iv, $data );
        if ($errCode == 0) {
            $data = json_decode($data,true);
            if(!isset($data["phoneNumber"])||strlen($data["phoneNumber"])=="0"){
                $addData['phone'] = $data['purePhoneNumber'];
            }else{
                $addData['phone'] = $data['phoneNumber'];
            }
            $addData['m_id']          = $getData['m_id'];
            $addData['scribe_time']   = time();

            $findphone = $this->loop_model->get_where('phone',["m_id"=>$getData['m_id']]);
            if($findphone){
                //替换
                $updata['phone'] = $addData['phone'];
                $updata['up_time'] = time();
                $res = $this->loop_model->update_where("phone",$updata,["m_id"=>$getData['m_id']]);
            }else{
                $res = $this->loop_model->insert('phone',$addData);
            }
            if($res > 0){
                $this->ResArr['code'] = 200;
                $this->ResArr['data'] = $addData['phone'];
                $this->ResArr['msg'] = '数据更新成功';
                echo json_encode($this->ResArr);
            }else{
                $this->ResArr['code'] = 1001;
                $this->ResArr['msg'] = '数据更新失败';
                echo json_encode($this->ResArr);
            }

        } else {
            $this->ResArr["code"] = $errCode;
            $this->ResArr["msg"] = '获取信息失败';
            echo json_encode($this->ResArr);
        }

    }

    //图片验证码
    public function verify_code($width = '80', $height = '30')
    {
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        /*
        $mobile = trim($this->input->get('mobile'));
        $this->load->helpers('form_validation_helper');
        if (!is_mobile($mobile)) {
            error_json('手机号码格式错误请不要加0或86');
        }
        */
        $app_path = explode(DIRECTORY_SEPARATOR, APPPATH);
        $this->load->helper('captcha');
        $vals = array(
            'img_path'    => APPPATH . 'cache/captcha/',
            'img_url'     => site_url('/api/pic?url=/' . $app_path[count($app_path) - 2] . '/cache/captcha'),
            'font_path'   => './path/to/fonts/texb.ttf',
            'img_width'   => $width,
            'img_height'  => $height,
            'expiration'  => 90,
            'word_length' => 4,
            'font_size'   => 16,
            'img_id'      => 'Imageid',
            'pool'        => '0123456789abcdefghjklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ',

            'colors' => array(
                'background' => array(255, 255, 255),
                'border'     => array(rand(1, 255), rand(1, 255), rand(1, 255)),
                'text'       => array(0, 0, 0),
                'grid'       => array(255, 40, 40)
            )
        );
        $cap  = create_captcha($vals);
        //$this->session->set_userdata('imgcode', $cap['word']);
        cache('save', 'imgcode', $cap['word'], 7*24*3600, 600);//写入缓存
        echo file_get_contents($vals['img_path'].$cap['filename']);
    }

    /**
     * 发送手机验证码
     */
    public function send()
    {
        $mobile = trim($this->input->post('mobile', true));
        $str     = trim($this->input->post('str'));
        if (!$mobile) {
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = '参数缺失';
            echo json_encode($this->ResArr);exit;
        }
        /*
        $origin = $_SERVER['HTTP_ORIGIN'] ;
        $arr = [
            'http://dev.mjkx.yaokexing.com'
        ];
        if(!in_array($origin,$arr)){
            $this->ResArr["code"] = 4;
            $this->ResArr["msg"] = '请不要恶意刷验证码';
            echo json_encode($this->ResArr);exit;

        }
        */
        $this->load->helpers('form_validation_helper');
        if (is_mobile($mobile)) {
            $temp = 'SMS_151232985';//身份验证码
            $temp1 = 'SMS_146750109';//登入操作
            $temp2 = 'SMS_146750107';//注册操作
            /*
            $member_data = $this->loop_model->get_where('user', array('username' => $mobile));
            if($member_data){
                $tmp = $temp1;
            }else{
                $tmp = $temp2;
            }
            */
            $tmp = $temp;
            $this->load->library('SmsDemo');
            $res  = SmsDemo::sendSms($mobile,$tmp);
            if ($res->Code == 'OK') {
                $this->ResArr["code"] = 200;
                $this->ResArr["msg"] = "发送成功";
                echo json_encode($this->ResArr);exit;
            } else {
                $this->ResArr["code"] = 7;
                $this->ResArr["msg"] = $res->Message;
                echo json_encode($this->ResArr);exit;
            }
        } else {
            $this->ResArr["code"] = 6;
            $this->ResArr["msg"] = '手机号码格式错误请不要加0或86';
            echo json_encode($this->ResArr);exit;
        }
    }

}
