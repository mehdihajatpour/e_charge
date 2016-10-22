<?php
class Login
{
    private $username;
    private $password;
    private $page;
    public function __construct($p='')
    {
        $this->page=$p;

        if($this->page=='logout')
        {
            $this->logout();
        }
        else if($this->page=='login')
        {
            $this->check_login();
        }
    }

    private function logout()
    {
            if(isset($_SESSION['login']))
            {
                unset($_SESSION['login']);
            }
            session_destroy();
            f_redirect('index.php');
    }

    private function check_login()
    {
        if(isset($_POST['password']) && isset($_POST['username']) && isset($_POST['submit']))
        {
            if((!preg_match("#[^a-zA-Z0-9]#",$_POST['username'])) && (!preg_match("#[^a-zA-Z0-9]#",$_POST['username'])))
            {
                $this->username =$_POST['username'];
                $this->password =$_POST['password'];
                if(($this->username==USERNAME) && ($this->password==PASSWORD))
                {
                    $_SESSION['login']=true;
                    f_redirect('index.php?admin=1');
                }
                else
                {
                    $this->logout();
                }
            }
            else
            {
                $this->logout();
            }
        }
        else if(isset($_SESSION['login']))
        {
            f_redirect('index.php?admin=1');
        }
        else
        {
            $this->logout();
        }
    }

};