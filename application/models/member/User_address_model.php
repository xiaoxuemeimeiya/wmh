<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_address_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 查询一个收货地址
     * @param array $where 条件数组
     * @param string $orderby 排序
     * @return array
     */
    public function get_address($where, $orderby = 'is_default desc')
    {
        if (!empty($where)) {
            if (!empty($orderby)) {
                $this->db->order_by($orderby);
            }
            $query = $this->db->get_where('member_user_address', $where);
            $data = $query->row_array();//echo $this->db->last_query()."<br>";
            return $data;
        }
    }
}
