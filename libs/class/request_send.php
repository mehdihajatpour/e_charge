<?php
class Send_Request
{
    private $mobile;
    private $email;
    private $amount;
    private $some;
    private $model;
    private $trans_id;
    private $id_get;
    private $user_id;
    private $charge_list=array();
    private $a_charge_price;
    private $whether_send_mail=true;
    public function __construct($id)
    {
        /*اگر کاربر آیدی 1 را درخواست کرد */
        if($id==1)
        {
            /*اعتبار سنجی کاربر*/
            $this->get_and_validate_user_post_date();
            /*چک کردن مدل کارت شارژ*/
            $this->check_charge_model();
            /*چک کردن موجودی انبار */
            $this->checking_warehouse_inventory();
/*ذخیره اطلاعات کاربر و اختصاص آی دی به کاربر*/
            $this->save_user_information_in_db();
            /*محاسبه مبلغ درخواستی*/
            $this->calculate_payments($this->amount,$this->some);
 /*ایجاد آدرس بازگشت از پی لاین*/
            $_SESSION['some']=$this->some;
            $_SESSION['amount']=$this->a_charge_price;
            $_SESSION['email']=$this->email;
            $redirect = urlencode(f_full_url().'?request_send=2&user_id='.$this->user_id.'&model='.$this->model.'&some='.$this->some);
           /*صدا زدن تابع پرداخت آنلاین پی لاین*/
            $result = $this->send_payline_request(SEND_URL,API,$this->amount,$redirect);
/*تفسیر مقدار بازگشتی از سایت پی لاین و در صورت خطا هدایت به صفحه کنترل خطا*/
            if($result>0 && is_numeric($result))
            {
                $go = "http://payline.ir/payment-test/gateway-$result";
                header("Location: $go");
            }
            else
            {
                switch ($result)
                {
                    case '-1':
                        f_redirect('index.php?send_bad_request=1');
//        echo 'API ارسالی با نوع API های پی لاین سازگار نمی باشد .';
                        break;
                    case '-2':
                        f_redirect('index.php?send_bad_request=2');
//        echo 'مقدار وارد شده صحیح نسیت و یا کمتر از 1000 ریال است.';
                        break;
                    case '-3':
                        f_redirect('index.php?send_bad_request=3');
//        echo 'مقدار آدرس بازگشت خالی است .';
                        break;
                    case '-4':
                        f_redirect('index.php?send_bad_request=4');
//        echo 'درگاهی با درخواست ارسالی شما یافت نشد و یا در حالت انتظار است .';
                        break;
                    default:
                        f_redirect('index.php?send_bad_request=5');
                        break;
                }
            }

        }
        else if($id==2)
        {
            $this->user_id=isset($_GET['user_id'])&&(((int)$_GET['user_id'])>0)?$_GET['user_id']:false;
            $this->model=isset($_GET['model'])&&(((int)$_GET['model'])>0)?$_GET['model']:false;
            if(!(isset($_SESSION['amount']) && isset($_SESSION['some'])&& isset($_SESSION['email'])))
            {
                f_redirect('index.php?send_bad_request=6');
            }
            $this->amount=$_SESSION['amount'];
            $this->some=$_SESSION['some'];
            $this->email=$_SESSION['email'];
            if($this->some==false || $this->model==false || $this->user_id==false)
            {
                f_redirect('index.php?send_bad_request=7');
            }
            $this->trans_id = $_POST['trans_id'];
            $this->id_get = $_POST['id_get'];

            $result = $this->get_payline_answer(GET_URL,API,$this->trans_id,$this->id_get);
            switch($result)
            {
                case '-1' :
                    f_redirect('index.php?send_bad_request=8');
                    //echo 'API ارسالی با API موجود در سایت پی لاین همخوانی ندارد';
                    break;
                case '-2' :
                    f_redirect('index.php?send_bad_request=9');
                    //echo 'trans_id ارسالی معتبر نمی باشد';
                    break;
                case '-3' :
                    f_redirect('index.php?send_bad_request=10');
                    //echo 'id_get ارسالی معتبر نمی باشد';
                    break;
                case '-4' :
                    f_redirect('index.php?send_bad_request=11');
                    //echo 'چنین تراکنشی در سیستم موجود نمی باشد و یا موفقیت آمیز نبوده .';
                    break;
                case '1' :
                    $this->fetch_charges_from_db();
                    $this->send_sms($this->model);
                    $this->get_output();
                    unset($_SESSION['email']);
                    unset($_SESSION['amount']);
                    unset($_SESSION['amount']);
                    break;
            }
        }
    }
    /*خروجی ارسالی ماژول*/
    public function get_output()
    {
        return array('class_name'=>'request_send',$this->model,'id_get'=>$this->id_get,'whether_send_mail'=>$this->whether_send_mail,'trans_id'=>$this->trans_id,'list_charges'=>serialize($this->charge_list));
    }
    /*تابع واکشی کارت شارژها از پایگاه داده*/
    public function fetch_charges_from_db()
    {
        global $dbh;//صدا زدن متغیر عمومی پایگاه داده
        //$dbh->beginTransaction();//آغاز یک تراکنش
        $sql='SELECT charge_id,charge_amount,charge_password,trackingcode FROM charge_password WHERE charge_amount=:amount and is_sold=0 LIMIT 0,:some';//واکشی تعدادی کارت شارژ برای کاربر بسته به نیاز کاربر
        $sth=$dbh->prepare($sql);
        $sth->bindParam(':amount',$this->amount,PDO::PARAM_INT);
        $sth->bindParam(':some',$this->some,PDO::PARAM_INT);
        if(!$sth->execute())
        //$dbh->rollBack();
        f_redirect('index.php?db_error='.$sth->errorCode());
        $this->charge_list = $sth->fetchAll(PDO::FETCH_ASSOC);///دریافت لست کارت شارژ ها

        $sql2='UPDATE charge_password SET  is_sold=1 WHERE charge_amount=:amount and is_sold=0 LIMIT :some';//کارت شارژ های دریافتی کاربر مهر فروخته شدن بگیرند
        $sth2=$dbh->prepare($sql2);
        $sth2->bindParam(':amount',$this->amount,PDO::PARAM_INT);
        $sth2->bindParam(':some',$this->some,PDO::PARAM_INT);
        if(!$sth2->execute())
            //$dbh->rollBack();
            f_redirect('index.php?db_error='.$sth2->errorCode());

        $sql3='SELECT COUNT(*) FROM users WHERE user_id=:user_id';//اطمینان از اینکه کاربر همان کاربر درخواست دهنده و خریدار است
        $sth3=$dbh->prepare($sql3);
        $sth3->bindParam(':user_id',$this->user_id,PDO::PARAM_INT);
        if(!$sth3->execute())
            //$dbh->rollBack();
            f_redirect('index.php?db_error='.$sth3->errorCode());

        if($sth3->fetchColumn()!=1)
        {
            return f_redirect('index.php?send_bad_request=12');
        }
        /*ذخیره شارژ فروخته شده در پایگاه داده*/
        $sql4='INSERT INTO charge_sold (amount, trans_id, id_get, charge_id, user_id,date_time) VALUES ';//ثبت کارت شارز ها و شماره خریدار و قیمت در جدول فروش
        for($i=0;$i < $this->some; $i++)
        {
            $sql4.="(".$this->charge_list[$i]['charge_amount'].",".$this->trans_id.",". $this->id_get.",".$this->charge_list[$i]['charge_id'].",".$this->user_id.",".time().")";
            if($i != $this->some-1)/*اگر هنوز به آخر نرسیدی بعد هر عملیات یک کاما قرار بده*/
                $sql4.=',';
        }
        if(!$dbh->exec($sql4))
            //$dbh->rollBack();
            //$dbh->commit();
            f_redirect('index.php?db_error='.$dbh->errorCode());

    }
    /*بررسی صحت داده های ارسالی کاربر*/
    public function get_and_validate_user_post_date()
    {
        /*check email*/
        if(isset($_POST['email']))
        {
            $em=trim($_POST['email']);
            if(!(f_validate_email($em) && strlen($em)<255))
                f_redirect('index.php?send_bad_request=13');
            else
                $this->email=$em;
        }
        else
        {
            f_redirect('index.php?send_bad_request=14');
        }
        /*check amount of money for a charge*/
        if(isset($_POST['amount']))
        {
            $money=(int)trim($_POST['amount']);
            if(!(is_int($money) && $money >= 1 && $money <= 7))
                f_redirect('index.php?send_bad_request=15');
            else
                $this->amount=$money;
        }
        else
        {
            f_redirect('index.php?send_bad_request=16');
        }
        /*check some of charges*/
        if(isset($_POST['some']))
        {
            $som=(int)trim($_POST['some']);
            if(!(is_int($som) && $som>=1 && $som<=20))
                f_redirect('index.php?send_bad_request=17');
            else
                $this->some=$som;
        }
        else
        {
            $this->some=1;
        }
        /*check model send charge*/
        if(isset($_POST['model']))
        {
            $mod=(int)trim($_POST['model']);
            if(!(is_int($mod) && $mod <= 3 && $mod >= 1))
                f_redirect('index.php?send_bad_request=18');
            else
                $this->model=$mod;
        }
        else
        {
            $this->model=1;
        }
        /*check mobile user*/
        if(isset($_POST['mobile']))
        {
            $mob=trim($_POST['mobile']);
            if(!f_validate_mobile($mob))
                f_redirect('index.php?send_bad_request=19');
            else
                $this->mobile=$mob;
        }
        else
        {
            f_redirect('index.php?send_bad_request=20');
        }
        return true;
    }
    /*چک کردن مدل ارسال کارت شارژ*/
    public function check_charge_model()
    {
        if($this->model==3)
        {
            $this->some=1;
        }
    }
    /*چک کردن موجودی انبار کارت شارژ*/
    public function checking_warehouse_inventory()
    {
        global $AMOUNTS;
        global $dbh;
        $sql='SELECT COUNT(*)FROM charge_password WHERE charge_amount=:amount';
        $sth=$dbh->prepare($sql);
        $sth->bindParam(':amount',$AMOUNTS[$this->amount],PDO::PARAM_INT);
        if(!$sth->execute())
        {
           f_redirect('index.php?db_error='.$sth->errorCode());
        }
        $chargs_count =$sth->fetchColumn();
        if((int)$chargs_count >= (int)$this->some)
            return true;
        else
            return f_redirect('index.php?send_bad_request=21');
    }
    /*ذخیره اطلاعات کاربر در پایگاه داد ه وبازگردادندن آی دی کاربر*/
    public function save_user_information_in_db()
    {
        global $dbh;
        $sql="SELECT COUNT(*) FROM users WHERE  user_email=:email OR user_mobile=:mobile;";
        $sth=$dbh->prepare($sql);
        $sth->bindParam(':email',$this->email);
        $sth->bindParam(':mobile',$this->mobile);
        if(!$sth->execute())
        {
            f_redirect('index.php?db_error='.$sth->errorCode());
        }
        $count=(int)$sth->fetchColumn();
        if($count>0)
        {
            $sql="SELECT user_id FROM users WHERE user_email=:email OR user_mobile=:mobile;";
            $sth=$dbh->prepare($sql);
            $sth->bindParam(':email',$this->email);
            $sth->bindParam(':mobile',$this->mobile);
            if(!$sth->execute())
            {
                f_redirect('index.php?db_error='.$sth->errorCode());
            }
            $this->user_id=$sth->fetchColumn();
        }
        else
        {
            $sql='INSERT INTO users(user_email,user_mobile) VALUES (:email,:mobile);';
            $sth=$dbh->prepare($sql);
            $sth->bindParam(':email',$this->email,PDO::PARAM_STR);
            $sth->bindParam(':mobile',$this->mobile,PDO::PARAM_STR);
            if(!$sth->execute())
            {
                f_redirect('index.php?db_error='.$sth->errorCode());
            }
            $this->user_id=$dbh->lastInsertId();
        }
    }
    /*محاسبه مبلغ درخواستی*/
    public function calculate_payments($charge_amount,$some)
    {
        global $AMOUNTS;
        /*محاسبه قیمت تکی محصول*/
        $this->a_charge_price=$AMOUNTS[$charge_amount];
/*محاسبه قسمت کلی محصولات*/
        $total=$AMOUNTS[$charge_amount]*$some;
        if($total<10000)
            f_redirect('index.php?send_bad_request=22');
        else
            $this->amount=$total;
    }
/*ارسال و انتقال کاربر به سایت پی لاین*/
    public function send_payline_request($url,$api,$amount,$redirect)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&amount=$amount&redirect=$redirect");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    /*جواب دریافتی کاربر از پی لاین*/
    public function get_payline_answer($url,$api,$trans_id,$id_get)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&id_get=$id_get&trans_id=$trans_id");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

/*ارسال اس ام اس به کاربر*/
    public function send_sms($mod)
    {
        if ($mod == 2)
        {
            $tpl='';
            for($i = 0; $i < count($this->charge_list); $i++)
            {
                $tpl.='<div style="background-color: darkgoldenrod;color: black; width: 100%; display: block;font-size: 20px;">PASSWORD : '.$this->charge_list[$i]['charge_password'].'<span style="color:green; font-size:13px;">TRACKING CODE:'.$this->charge_list[$i]['trackingcode'].'</span></div>';
            }
            require_once 'libs/class/mailer_class.php';
            $mailer=new Mailer(NAME,SEND_CHARGE_TO_MAIL_SUBJECT,EMAIL,$this->email,$tpl);
            if($mailer->email_error())
                $this->whether_send_mail=true;
            else
                $this->whether_send_mail=false;
        }
        else if($mod == 3)
        {
            echo 'send by sms';
        }
    }
};