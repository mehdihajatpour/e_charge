<?php
class Add_new_charge
{
    private $pass;
    private $track;
    private $amount;
    private $situation=0;
    public function __construct()
    {
        if(!isset($_SESSION['login']))
        {
            f_redirect('index.php?send_bad_request=23');
        }

        if(isset($_POST['charge_password']) && isset($_POST['tracking_number'])&& isset($_POST['charge_amount']))
        {
            $this->pass=$_POST['charge_password'];
            $this->track=$_POST['tracking_number'];
            $this->amount=$_POST['charge_amount'];

            if($this->validate_charge())
            {
                if($this->compare_charge())
                {
                    if(!$this->save_charge())
                    {
                        $this->situation=4;
                    }
                }
                else
                {
                    $this->situation=1;
                }
            }
            else
            {
                $this->situation=2;
            }
        }
        else
        {
            $this->situation=3;
        }
    }
    /*خروجی ارسالی ماژول*/
    public function get_output()
    {
        return array('class_name'=>'add_new_charge','situation'=>$this->situation);
    }

    public function compare_charge()
    {
            global $dbh;
            $sql = "SELECT COUNT(*) FROM charge_password WHERE  charge_password=:pass OR trackingcode=:track";
            $sth = $dbh->prepare($sql);
            $sth->bindParam(':pass', $this->pass, PDO::PARAM_INT);
            $sth->bindParam(':track', $this->track, PDO::PARAM_INT);
            if (!$sth->execute())
                f_redirect('index.php?db_error=' . $sth->errorCode());

            $count = (int)$sth->fetchColumn(0);
            if ($count > 0)
                return 0;
            else
                return 1;
    }

    public function validate_charge()
    {
        if (preg_match('#^[0-9]{13,17}$#', $this->pass) && preg_match('#^[0-9]{10,12}$#', $this->track) && preg_match('#^[1-7]{1}$#', $this->amount))
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }
    public function save_charge()
    {
        global $dbh;
        $sql="INSERT INTO charge_password (charge_password,charge_amount,trackingcode,is_sold,date_time)VALUES(:pass,:amount,:track,'0',:date_time)";
        $sth=$dbh->prepare($sql);
        $sth->bindParam(':pass',$this->pass,PDO::PARAM_INT);
        $sth->bindParam(':track',$this->track,PDO::PARAM_INT);
        $money=$this->amount*10000;
        $sth->bindParam(':amount',$money,PDO::PARAM_INT);
        $time=time();
        $sth->bindParam(':date_time',$time,PDO::PARAM_INT);

        if($sth->execute())
            return 1;
        else
            return 0;
    }
}