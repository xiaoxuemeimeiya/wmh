<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_log extends Manager_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 列表
     */
    public function index()
    {
        $pagesize = $this->pageSize;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page     = ($page <= 1) ? $page = 1 : $page;//当前页数
        
	    $keyword = $this->input->post_get('keyword');
        if (!empty($keyword)) $where_data['like']['note'] = $keyword;
		
        $search_where = array(
            'keyword' => $keyword,
        );
        assign('search_where', $search_where); //搜索条件
        
        //查到数据
        $list = $this->loop_model->get_list('admin_log', $where_data, $pagesize, $pagesize * ($page - 1), 'id desc');//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('admin_log', array());//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        display('/system/admin_log/list.html');
    }
}
