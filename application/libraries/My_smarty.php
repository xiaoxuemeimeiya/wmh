<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . 'third_party/smarty/Smarty.class.php');

class MY_Smarty extends Smarty
{
    public function __construct()
    {
        parent::__construct();
        $this->template_dir = APPPATH . '../views';
        $this->compile_dir = APPPATH . 'cache/runtime';
        $this->left_delimiter = '<{';
        $this->right_delimiter = '}>';
    }

}