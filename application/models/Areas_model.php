<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Areas_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 根据父栏目id获取列表
     * @param int $parent_id 父栏目id
     * @return array
     */
    public function get_list($parent_id = '0')
    {
        if ($parent_id != '') {
            $this->db->where(array('parent_id' => $parent_id));
            $this->db->order_by('sortnum asc,area_id asc');
            $query = $this->db->get('areas');
            $list = $query->result_array();
            foreach ($list as $key) {
                $area_list[$key['area_id']] = $key['area_name'];
            }
            return $area_list;
        }
        return false;
    }

    /**
     * 根据地区id获取名称
     * @param int array $id 数据id或者数据id数组
     * @return array
     */
    public function get_name($id = FALSE)
    {
        if (!empty($id)) {
            if (is_array($id)) {
                $this->db->where_in('area_id', $id);
                $query = $this->db->get('areas');
                $list = $query->result_array();
                foreach ($list as $k) {
                    $area[$k['area_id']] = $k['area_name'];
                }
            } else {
                $query = $this->db->get_where('areas', array('area_id' => $id));//echo $this->db->last_query()."<br>";
                $row = $query->row_array();
                $area[$row['area_id']] = $row['area_name'];
            }
            return $area;
        }
        return false;
    }

    /**
     * 根据名称获取地区id
     * @param int array $id 数据id或者数据id数组
     * @return array
     */
    public function get_id($name = FALSE)
    {
        if (!empty($name)) {
            if (is_array($name)) {
                $this->db->where_in('area_name', $name);
                $query = $this->db->get('areas');
                $list = $query->result_array();
                foreach ($list as $k) {
                    $area[$k['area_name']] = $k['area_id'];
                }
            } else {
                $query = $this->db->get_where('areas', array('area_name' => $name));//echo $this->db->last_query()."<br>";
                $row = $query->row_array();
                $area[$row['area_name']] = $row['area_id'];
            }
            return $area;
        }
        return false;
    }
}
