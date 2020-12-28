<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 查询管理员是否已经存在
     * @param string $username 用户名
     * @return array
     */
    public function repeat_username($username = '')
    {
        if (!empty($username)) {
            $this->db->limit(1);
            $query = $this->db->get_where('admin', array('username' => $username));//echo $this->db->last_query()."<br>";
            return $query->row_array();
        }
    }

    /**
     * 更新管理员
     * @return array
     */
    public function update($data_post = array())
    {
        $update_data = array(
            'full_name' => $data_post['full_name'],
            'role_id' => $data_post['role_id'],
            'tel' => $data_post['tel'],
        );

        $this->load->model('loop_model');
        if (!empty($data_post['id'])) {
            //修改密码
            if (!empty($data_post['password'])) {
                if ($data_post['password'] != $data_post['repassword']) {
                    return '两次密码不一样';
                } else {
                    $update_data['salt'] = get_rand_num();
                    $update_data['password'] = md5(md5($data_post['password']) . $update_data['salt']);
                }
            }
            $res = $this->loop_model->update_id('admin', $update_data, $data_post['id']);
            //删除缓存
            cache('del', 'admin_' . $data_post['id']);
            admin_log_insert('修改管理员' . $data_post['id']);
        } else {
            //增加数据
            if (empty($data_post['username']) || empty($data_post['password'])) {
                return '用户名和密码不能为空';
            } elseif ($data_post['password'] != $data_post['repassword']) {
                return '两次密码不一样';
            } else {
                //判断用户名是否重复
                $member_data = self::repeat_username($data_post['username']);
                if (!empty($member_data)) {
                    return '用户名已经存在';
                } else {
                    $update_data['username'] = $data_post['username'];
                    $update_data['salt'] = get_rand_num();
                    $update_data['password'] = md5(md5($data_post['password']) . $update_data['salt']);
                    $update_data['addtime'] = time();
                    $update_data['lasttime'] = time();
                    $this->db->insert('admin', $update_data);
                    $res = $this->db->insert_id();
                    admin_log_insert('增加管理员' . $res);
                }
            }
        }
        if (!empty($res)) {
            return 'y';
        } else {
            return '保存失败';
        }
    }
}
