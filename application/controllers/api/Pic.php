<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pic extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 图片显示
     */
    public function index()
    {
        $url = $this->input->get('url', true);
        if (!empty($url)) {
            $url = '.'.$url;
            $mtime      = filemtime($url);
            $gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
            $fileExt    = pathinfo($url, PATHINFO_EXTENSION);
            header('Last-Modified: ' . $gmdate_mod);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 30)) . ' GMT');
            header('Content-type: image/' . $fileExt);
            header('Content-Length: ' . filesize($url));
            readfile($url);
        }
    }
}
