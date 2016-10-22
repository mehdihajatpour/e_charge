<?php
class Admin
{
    private $login=1;
    public function __construct($admin)
    {
        if(!isset($_SESSION['login']))
        {
            f_redirect('index.php');
        }
        else
        {
            $this->login=1;
        }
    }
    /*خروجی ارسالی ماژول*/
    public function get_output()
    {
        return array('class_name'=>'admin','login'=>$this->login);
    }


};