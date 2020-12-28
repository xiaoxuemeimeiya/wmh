<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Imgcode extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    //验证码
    public function index($width = '80', $height = '30')
    {
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
        $this->session->set_userdata('imgcode', $cap['word']);
        echo file_get_contents($vals['img_path'].$cap['filename']);
    }

}
