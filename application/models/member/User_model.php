<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{

    public $m_id;

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 查询会员是否已经存在
     * @param string $username 用户名
     * @return array
     */
    public function repeat_username($username = '')
    {
        if (!empty($username)) {
            $this->db->limit(1);
            $query = $this->db->get_where('member', array('username' => $username));//echo $this->db->last_query()."<br>";
            return $query->row_array();
        }
    }

    /**
     * 更新会员
     * @return array
     */
    public function update($data_post = array())
    {
        if (!empty($data_post['full_name'])) $update_data['full_name'] = $data_post['full_name'];
        if (!empty($data_post['group_id'])) $update_data['group_id'] = $data_post['group_id'];
        if (!empty($data_post['tel'])) $update_data['tel'] = $data_post['tel'];
        if (!empty($data_post['email'])) $update_data['email'] = $data_post['email'];
        if (!empty($data_post['sex'])) $update_data['sex'] = $data_post['sex'];
        if (!empty($data_post['prov'])) $update_data['prov'] = $data_post['prov'];
        if (!empty($data_post['city'])) $update_data['city'] = $data_post['city'];
        if (!empty($data_post['area'])) $update_data['area'] = $data_post['area'];
        if (!empty($data_post['address'])) $update_data['address'] = $data_post['address'];
        $update_data['endtime'] = time();

        $this->load->model('loop_model');
        if (!empty($data_post['id'])) {
            //修改数据
            $res = $this->loop_model->update_where('member_user', $update_data, array('m_id' => $data_post['id']));

            //修改密码或头像start
            if (!empty($data_post['headimgurl'])) {
                $password_data['headimgurl'] = $data_post['headimgurl'];
            }
            if (!empty($data_post['password'])) {
                if ($data_post['password'] != $data_post['repassword']) {
                    return '两次密码不一样';
                } else {
                    $password_data['salt']     = get_rand_num();
                    $password_data['password'] = md5(md5($data_post['password']) . $password_data['salt']);
                }
            }
            if (!empty($password_data)) {
                $this->loop_model->update_id('member', $password_data, $data_post['id']);
            }
            //修改密码或头像end
        } else {
            //增加数据
            if (empty($data_post['username']) || empty($data_post['password'])) {
                return '用户名和密码不能为空';
            } else {
                //判断用户名是否重复
                $member_data = self::repeat_username($data_post['username']);
                if (!empty($member_data)) {
                    return '手机号码已经存在';
                } else {
                    //查询推荐用户是否存在
                    if (!empty($data_post['flag_user'])) {
                        $flag_user_query = $this->db->get_where('member', array('id' => $data_post['flag_user']));
                        $flag_user_data  = $flag_user_query->row_array();
                        if (empty($flag_user_data)) {
                            $data_post['flag_user'] = 0;
                        }
                    }
                    $salt        = get_rand_num();
                    $insert_data = array(
                        'username'   => $data_post['username'],
                        'salt'       => $salt,
                        'password'   => md5(md5($data_post['password']) . $salt),
                        'headimgurl' => $data_post['headimgurl'] == '' ? '/public/images/user_header.jpg' : $data_post['headimgurl'],
                        'flag_user'  => $data_post['flag_user'] != '' ? $data_post['flag_user'] : 0,
                    );

                    $this->db->trans_start();
                    $this->db->insert('member', $insert_data);
                    $m_id = $this->db->insert_id();
                    $res  = $this->m_id = $m_id;
                    //增加用户数据
                    $update_data['addtime']  = time();
                    $update_data['m_id']     = $m_id;
                    $update_data['group_id'] = 1;
                    $this->db->insert('member_user', $update_data);
                    $this->db->trans_complete();

                    //发送推荐成功消息
                    if (!empty($flag_user_data)) {
                        $oauth_query = $this->db->get_where('member_oauth', array('m_id' => $flag_user_data['id'], 'oauth_type' => 'wechat'));
                        $oauth_data  = $oauth_query->row_array();
                        if (!empty($oauth_data['oauth_id'])) {
                            $this->load->library('wechat/message_template');
                            $update_data['full_name'] != '' ? $recommended = $update_data['full_name'] : $recommended = $insert_data['username'];
                            $this->message_template->flag_user($oauth_data['oauth_id'], $flag_user_data['username'], $recommended);
                        }
                    }
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
