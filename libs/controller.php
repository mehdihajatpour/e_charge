<?php
class Controller
{
private $result='';
    public function __construct($id,$page='')
    {
        if($page=='request_send')
        {
            include_once 'libs/class/request_send.php';
            $response=new Send_Request($id);
            $this->result=$response->get_output();
        }
        elseif($page=='send_bad_request')
        {
            include_once 'libs/class/send_bad_request_class.php';
            $response=new Send_Bad_Request($id);
            $this->result=$response->get_output();
        }
        elseif($page=='login' && $id==1)
        {            
            include_once 'libs/class/login_class.php';
            $response=new Login('login');
        }
        elseif($page=='logout' && $id==1)
        {
            include_once 'libs/class/login_class.php';
            $response=new Login('logout');
        }
        elseif($page=='add_new_charge'&& $id==1)
        {
            include_once 'libs/class/add_new_charge_class.php';
            $response=new Add_new_charge();
            $this->result=$response->get_output();
        }
        elseif($page=='add_invalid_charge'&& $id==1)
        {
            include_once 'libs/class/add_new_charge_class.php';
            //$response=new Controller('add_invalid_charge');
        }
        elseif($page=='delete_charge'&& $id==1)
        {
            include_once 'libs/class/delete_charge_class.php';
            //$response=new Controller('delete_charge');
        }
        elseif($page=='statistics'&& $id==1)
        {
            include_once 'libs/class/statistics_class.php';
            //$controller=new Controller('statistics');
        }
        elseif($page=='admin'&& $id==1)
        {
            include_once 'libs/class/admin_class.php';
            $response=new Admin('admin');
            $this->result=$response->get_output();
        }

            include_once 'libs/view.php';
            $view=new View($this->result);

    }

}