<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pay extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 订单或这个充值支付
     */
    public function do_pay()
    {
        //必须登录
        $this->load->helpers('web_helper');
        $client      = $this->input->get_post('client');//来源客户端
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        var_dump($order_no);
        if (!empty($order_no)) {
            //判断订单状态
            $all_order_no = explode(',', $order_no);
            $order_price  = 0;
            foreach ($all_order_no as $key) {
                $order_data = $this->loop_model->get_where('order', array('order_no' => $key, 'status' => 1));
                if (!empty($order_data)) {
                    $order_no_data[] = $order_data['order_no'];
                    $order_price     = $order_price + $order_data['price_real'];//支付金额
                    //$payment_id      = $order_data['payment_id'];//支付方式

                } else {
                    error_json('订单信息错误,或者订单已支付');

                }
                $payment_id      = 3;//微信支付
            }
            cache('save', $order_no_data[0], $order_no_data, 7200);
            $pay_data = array(
                'order_body'  => '订单支付',
                'order_no'    => $order_no_data[0],//支付单号
                'order_price' => $order_price > 0 ? $order_price : 1 ,//支付金额
                'payment_id'  => $payment_id,//支付方式
            );
        }else{
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo json_encode($this->ResArr);exit;
        }


        $pay_data['client']          = $client;//当前客户端
        $pay_data['callback']        = site_url('/api_mobile/pay/callback/' . $pay_data['payment_id'] . '/' . $client);//客户端回调地址
        $pay_data['server_callback'] = site_url('/api_mobile/pay/server_callback/' . $pay_data['payment_id']);//服务端回调地址

        if (!empty($pay_data)) {
            //获取支付方式
            $payment_data = $this->loop_model->get_where('payment', array('id' => $pay_data['payment_id'], 'status' => 0));
            if (!empty($payment_data)) {
                //开始支付
                $patment_class_name = $payment_data['class_name'];var_dump($patment_class_name);
                //$this->load->library($patment_class_name);var_dump($patment_class_name);
                $this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);

              //  var_dump($this->load->library('payment1/' . $patment_class_name . '/' . $patment_class_name));exit;
                $this->$patment_class_name->do_pay($pay_data);
            } else {
                $this->ResArr['code'] = 1001;
                $this->ResArr['msg'] = '支付方式不存在';
                echo json_encode($this->ResArr);exit;
            }
        }
    }

    /**
     * 支付
     */
    public function index()
    {
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo json_encode($this->ResArr);exit;
        }
        $order_price  = 0;
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no, 'status' => 1));
        if (!empty($order_data)) {
            $order_no_data = $order_no;
            $order_price     = $order_price + $order_data['order_price'];//支付金额
        } else {
            $this->ResArr['code'] = 101;
            $this->ResArr['msg'] = '订单信息错误,或者订单已支付';
            echo json_encode($this->ResArr);exit;
        }
        $payment_id      = 3;//微信支付
        $pay_data = array(
            'order_body'  => '订单支付',
            'order_no'    => $order_no,//支付单号
            'order_price' => $order_price > 0 ? $order_price : 1000 ,//支付金额
            'payment_id'  => $payment_id,//支付方式
        );
        $user = $this->loop_model->get_where('user',array('id'=>$order_data['m_id']));
        $openid = $user['openid'];
        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        //require_once Env::get('ROOT_PATH').'extend/minipay/WxPay.Api.php';
        //require_once Env::get('ROOT_PATH').'extend/minipay/WxPay.JsApiPay.php';
        //require_once Env::get('ROOT_PATH').'extend/minipay/WxPay.Config.php';
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($pay_data["order_body"]);
        $input->SetAttach("");
        $input->SetOut_trade_no($pay_data['order_no']);
        $input->SetTotal_fee($pay_data['order_price']/100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($pay_data["order_body"]);
        //$input->SetNotify_url("https://".$_SERVER["SERVER_NAME"]."/miniapp/notify");
        $input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        //$input->SetNotify_url("https://".$_SERVER["SERVER_NAME"]."/miniapp/notify");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $config = new \WxPayConfig();
        $order = \WxPayApi::unifiedOrder($config, $input);
        $tools = new \JsApiPay();
        $jsApiParameters = $tools->GetJsApiParameters($order);
        if($order["return_code"]=="SUCCESS"){
            //lyLog(var_export($order,true) , "oncourse" , true);
            $this->ResArr['code'] = 200;
            $this->ResArr['data'] = json_decode($jsApiParameters,true);
        }else{
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = "pay data error!";
        }
        echo json_encode($this->ResArr);
    }

    /**
     * 订单或充值支付客户端回调
     */
    public function callback($payment_id, $client = '')
    {
        $payment_data = $this->loop_model->get_where('payment', array('id' => $payment_id, 'status' => 0));

        if (!empty($payment_data)) {
            //开始支付回调处理
            $patment_class_name = $payment_data['class_name'];
			$this->load->library($patment_class_name);
            //$this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);
            $pay_res = $this->$patment_class_name->callback();
            if ($pay_res['status'] == 'y') {
                error_json('支付成功');
            } else {
                error_json($pay_res['info']);
            }
        } else {
            error_json('支付方式错误');
        }
    }

    /**
     * 订单或这个充值支付服务端回调
     */
    public function server_callback($payment_id)
    {
        $payment_data = $this->loop_model->get_where('payment', array('id' => $payment_id, 'status' => 0));

        if (!empty($payment_data)) {
            //开始支付回调处理
            $patment_class_name = $payment_data['class_name'];
			$this->load->library($patment_class_name);
            //$this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);
            $pay_res = $this->$patment_class_name->server_callback();
            if ($pay_res['status'] == 'y') {
                //充值方式
                if (stripos($pay_res['order_no'], 'on') !== false) {
                    $order_no_data = explode('on', $pay_res['order_no']);
                    $recharge_no   = isset($order_no_data[1]) ? $order_no_data[1] : 0;
                    $this->load->model('member/user_online_recharge');
                    $res = $this->user_online_recharge->update_pay_status($recharge_no);
                    if ($res == 'y') {
                        $this->loop_model->update_where('member_user_online_recharge', array('payment_no' => $pay_res['transaction_id']), array('recharge_no' => $recharge_no));//保存交易流水号
                        $this->$patment_class_name->success();
                    } else {
                        $this->$patment_class_name->error();
                    }
                } else {
                    $order_no = cache('get', $pay_res['order_no']);//取的订单缓存
                    //订单付款
                    foreach ($order_no as $key) {
                        $this->load->model('order/order_model');
                        $order_res = $this->order_model->update_pay_status($key);
                        if ($order_res == 'y') {
                            if (!empty($pay_res['transaction_id'])) {
                                $this->loop_model->update_where('order', array('payment_no' => $pay_res['transaction_id']), array('order_no' => $key));//保存交易流水号
                            }
                        }
                    }
                    if ($order_res == 'y') {
                        $this->$patment_class_name->success();
                    } else {
                        $this->$patment_class_name->error();
                    }
                }
            } else {
                $this->$patment_class_name->error();
            }
        } else {
            echo '支付方式错误';
        }
    }
}
