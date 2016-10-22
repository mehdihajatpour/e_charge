<?php
class View
{
    private $module;
    private $template_main_file;
    public function __construct($data=array())
    {
        $this->module = $data;
        /*دریافت کد قالب اصلی سایت*/
        $this->template_main_file = file_get_contents(TEMPLATE_ADDRESS.'main.tpl');

        /*تشخیص عملیات*/
        $this->detecting_action();
        /*قرار دادن عنوان صفحه*/
        $this->set_title();
        /*قرار دادن تگ  های قالب اصلی*/
        $this->set_main_tags();
/*قرار دادن آدرس ها در قالب*/
        $this->set_address();


        /*چاپ قالب*/
        echo $this->template_main_file;
    }
    /*تبدیل تگ TITLE*/
    public function set_title()
    {
        $this->template_main_file=str_replace('{TITLE}',TITLE,$this->template_main_file);
    }
    /*تبدیل تگ ADDRESS */
    public function set_address()
    {
        $this->template_main_file=str_replace('{ADDRESS}',TEMPLATE_ADDRESS,$this->template_main_file);
    }
    /*ماژول اتصال به درگاه پرداخت*/

    /*بارگذاری کپچا در سایت*/
    /*public function captcha_tag($tpl)
    {
        $src='<img src="engine/module/captcha.php" class="captcha">';
        $tpl=str_replace("{captcha}",$src,$this->template_main_file);
        return $tpl;
    }*/
    /*تشخیص عملیات*/
    public function detecting_action()
    {
        if(isset($this->module) && $this->module!=='')
        {
            if($this->module['class_name']=='request_send')
            {
                $this->replace_request_send_tags();
            }
            if($this->module['class_name']=='send_bad_request')
            {
                $this->replace_send_bad_request_tags();
            }
            if($this->module['class_name']=='admin')
            {
                $this->replace_admin_tags();
            }
            if($this->module['class_name']=='add_new_charge')
            {
                $this->replace_add_new_charge_tags();
            }
        }
        else
        {
            $tpl=file_get_contents(TEMPLATE_ADDRESS.'buy.tpl');
            $this->template_main_file=str_replace('{CONTENT}',$tpl,$this->template_main_file);
        }
    }
    /*قرار دادن تگ  های قالب اصلی*/
    public function set_main_tags()
    {
        if(isset($_SESSION['login']))
        {
            $sub_tpl=array();
            preg_match("#\\[(ADMIN)\\](.*)\\[/ADMIN\\]#is", $this->template_main_file, $sub_tpl);
            $this->template_main_file=preg_replace("#\\[(ADMIN)\\](.*)\\[/ADMIN\\]#is", $sub_tpl[2], $this->template_main_file);
            $this->template_main_file=preg_replace("#\\[(LOGIN)\\](.*)\\[/LOGIN\\]#is",'', $this->template_main_file);

            $this->template_main_file=str_replace('{LOGIN}','',$this->template_main_file);
        }
        else
        {
            $sub_tpl=array();
            preg_match("#\\[(LOGIN)\\](.*)\\[/LOGIN\\]#is", $this->template_main_file, $sub_tpl);
            $this->template_main_file=preg_replace("#\\[(LOGIN)\\](.*)\\[/LOGIN\\]#is", $sub_tpl[2], $this->template_main_file);
            $this->template_main_file=preg_replace("#\\[(ADMIN)\\](.*)\\[/ADMIN\\]#is",'', $this->template_main_file);

            $tpl = file_get_contents(TEMPLATE_ADDRESS.'login.tpl');
            $this->template_main_file=str_replace('{LOGIN}',$tpl,$this->template_main_file);
        }

    }
    /*send request tags*/
    public function replace_request_send_tags()
    {
        $tpl='';
        if(!$this->module['whether_send_mail'])
        {
            $error_mssage_tpl=file_get_contents(TEMPLATE_ADDRESS.'error_message.tpl');
            $tpl=str_replace('{ERROR_MESSAGE}','به نظر می رسد ایمیل حاوی رمز شارژ ها برای شما ارسال نشده است.',$error_mssage_tpl);
        }
        $tpl.=file_get_contents(TEMPLATE_ADDRESS.'success_view_charges.tpl');
        if (strpos ( $tpl, "[CHARGE_PASSWORD]" ) !== false)
        {
            $tpl = preg_replace_callback ( "#\\[(CHARGE_PASSWORD)\\](.*?)\\[/CHARGE_PASSWORD\\]#is", array( &$this, 'charge_replacer'),$tpl);
        }
        $tpl=str_replace('{TRANS_ID}',$this->module['trans_id'],$tpl);
        $tpl=str_replace('{ID_GET}',$this->module['id_get'],$tpl);
        $this->template_main_file=str_replace('{CONTENT}',$tpl,$this->template_main_file);
    }
    public function charge_replacer($tpl)
    {
        $charges=unserialize($this->module['list_charges']);
        $tpl_text='';
        for($i = 0; $i < count($charges); $i++)
        {
            $help=str_replace('{CHARGE_PASSWORD}',$charges[$i]['charge_password'],$tpl[2]);
            $tpl_text.=str_replace('{TRACKING_CODE}',$charges[$i]['trackingcode'],$help);
        }
        return $tpl_text;
    }
    /*send bad request tags*/
    public function replace_send_bad_request_tags()
    {
        $tpl= file_get_contents(TEMPLATE_ADDRESS . 'error_view_charges.tpl');
        $tpl=str_replace('{ERROR_CODE}',$this->module['error_code'],$tpl);
        $tpl=str_replace('{ERROR_MESSAGE}',$this->module['error_message'],$tpl);
        $this->template_main_file=str_replace('{CONTENT}',$tpl,$this->template_main_file);
    }
    /*admin tags*/
    public function replace_admin_tags()
    {
        $tpl= file_get_contents(TEMPLATE_ADDRESS.'admin.tpl');
        /*Statistics*/
        include_once 'libs/class/statistics_class.php';
        $statistics=new Statistics;
        $output=$statistics->get_output();
        /*echo '<pre>';
        print_r($output);echo '</pre>';;exit;
*/
        $tpl=str_replace('{today}',$output['today'],$tpl);
        $tpl=str_replace('{sold_today}',$output['sold_today'],$tpl);
        $tpl=str_replace('{sold_week}',$output['sold_week'],$tpl);
        $tpl=str_replace('{sold_month}',$output['sold_month'],$tpl);
        $tpl=str_replace('{count_exists}',$output['count_exists'],$tpl);
        $list_amount='';
        for($i=0;$i<count($output['view_list_count_price']);$i++)
        {
            $list_amount.="تعداد ".$output['view_list_count_price'][$i]['count(*)']." عدد ".$output['view_list_count_price'][$i]['charge_amount']."ریالی <br />";
        }
        $tpl=str_replace('{view_list_count_price}',$list_amount,$tpl);
        /*End Statistics*/
        $this->template_main_file=str_replace('{CONTENT}',$tpl,$this->template_main_file);
    }
    /*replace_add_new_charge_tags*/
    public  function replace_add_new_charge_tags()
    {
        $tpl= file_get_contents(TEMPLATE_ADDRESS.'answer_request.tpl');
        if($this->module['situation']>0)
        {
            $tpl=file_get_contents(TEMPLATE_ADDRESS.'error_message.tpl').$tpl;
            if($this->module['situation']==1)
                $tpl = str_replace('{ERROR_MESSAGE}', 'این کارت شارژ قبلا در سیستم ثبت شده است.', $tpl);
            if($this->module['situation']==2)
                $tpl = str_replace('{ERROR_MESSAGE}', 'اطلاعات وارد شده شما صحیح نیستند.', $tpl);
            if($this->module['situation']==3)
                $tpl = str_replace('{ERROR_MESSAGE}', 'لطفا اطلاعات فرم ارسالی را به درستی تکمیل کنید.', $tpl);
            if($this->module['situation']==4)
                $tpl = str_replace('{ERROR_MESSAGE}', 'یک خطای پایگاه داده مانع ثبت شارژ شده است.', $tpl);
            $tpl = str_replace('{ANSWER_REQUEST}', 'خطا حین انجام عملیات ثبت شارژ', $tpl);
        }
        else
        {
            $tpl = str_replace('{ANSWER_REQUEST}', 'عملیات با موفقیت ثبت شد', $tpl);

        }
        $this->template_main_file = str_replace('{CONTENT}', $tpl, $this->template_main_file);

    }

};