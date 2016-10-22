<?php
class Router
{
private $controller;

    public function __construct($query)
    {
        $arrQuery=explode('&',$query);
        $subQuery=explode('=',$arrQuery[0]);
        $id=0;
        $page='';

        if(isset($subQuery[0]))
        {
            $page=preg_replace('#[^a-zA-Z0-9\_]#','',$subQuery[0]);
        }
        if($page=='')
        {
            $page='';
        }
        if(isset($subQuery[1]) && is_int((int)($subQuery[1])))
        {
            $id=$subQuery[1];
            if($id<=0)
            {
                $id=1;
            }
        }
        else
        {
            $id=1;
        }
        require_once 'libs/controller.php';
        $this->controller=new Controller($id,$page);

    }
    /*public function get()
    {
        if(isset($this->controller)&&$this->controller!='')
            return $this->controller;
        else
        {
            header("Location:index.php?send_bad_request=2");
            exit();
        }

    }*/
};