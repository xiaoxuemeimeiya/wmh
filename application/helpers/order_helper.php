<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 根据订单信息获取订单文字状态
 */
if (!function_exists('get_order_status_text')) {
    function get_order_status_text($order_data)
    {
        if (!empty($order_data)) {
            switch ($order_data['status']) {
                case 1:
                    if ($order_data['payment_id'] == 1) {
                        return '等待发货';//货到付款
                    } else {
                        return '等待支付';
                    }
                    break;
                case 2:
                    return '等待发货';
                    break;
                case 3:
                    return '待确认';
                    break;
                case 4:
                    return '待评价';
                    break;
                case 5:
                    return '交易完成';
                    break;
                case 6:
                    return '退款完成';
                    break;
                case 7:
                    return '部分退款';
                    break;
                case 8:
                    return '交易取消';
                    break;
                case 9:
                    return '交易作废';
                    break;
                case 10:
                    return '退款中';
                    break;
            }
        } else {
            return false;
        }
    }
}

/**
 * 根据订单信息获取订单状态
 */
if (!function_exists('get_order_status')) {
    function get_order_status($order_data)
    {
        if (!empty($order_data)) {
            switch ($order_data['status']) {
                case 1:
                    if ($order_data['payment_id'] == 1) {
                        return '2';//货到付款
                    } else {
                        return '1';
                    }
                    break;
                case 2:
                    return '2';
                    break;
                case 3:
                    return '3';
                    break;
                case 4:
                    return '4';
                    break;
                case 5:
                    return '5';
                    break;
                case 6:
                    return '6';
                    break;
                case 7:
                    return '7';
                    break;
                case 8:
                    return '8';
                    break;
                case 9:
                    return '8';//交易取消
                    break;
                case 10:
                    return '10';
                    break;
            }
        } else {
            return false;
        }
    }
}

/**
 * 判断能否支付
 */
if (!function_exists('is_pay')) {
    function is_pay($order_data)
    {
        if (!empty($order_data)) {
            if ($order_data['status'] == 1 && $order_data['payment_id'] != 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

/**
 * 判断能否退款状态
 */
if (!function_exists('is_refund')) {
    function is_refund($order_data)
    {
        if (!empty($order_data)) {
            if ((($order_data['status'] == 2 || $order_data['status'] == 3) && $order_data['payment_status'] == 1) || $order_data['status'] == 7 || $order_data['status'] == 10) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

/**
 * 判断能否删除
 */
if (!function_exists('is_delete')) {
    function is_delete($order_data)
    {
        if (!empty($order_data)) {
            if ($order_data['status'] == 8 || $order_data['status'] == 9) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

/**
 * 判断能否确认
 */
if (!function_exists('is_confirm')) {
    function is_confirm($order_data)
    {
        if (!empty($order_data)) {
            if ($order_data['delivery_status']==1 && ($order_data['status'] == 3 || $order_data['status'] == 7)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

/**
 * 判断能否评论
 */
if (!function_exists('is_comment')) {
    function is_comment($order_data)
    {
        if (!empty($order_data)) {
            if ($order_data['status'] == 4) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}


/**
 * 判断能否发货
 */
if (!function_exists('is_send')) {
    function is_send($order_data)
    {
        if (!empty($order_data)) {
            if (($order_data['delivery_status'] == 0 && ($order_data['status'] == 2 || $order_data['status'] == 7)) || ($order_data['payment_id'] == 1 && $order_data['pay_status'] == 0 && $order_data['status'] == 1)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

