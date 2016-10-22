<?php
class Mailer
{
    private $sender;
    private $receiver;
    private $subject;
    private $message;
    private $name;
    private $error;
    public function __construct($nam,$sub,$sen,$rec,$mes)
    {
        if(isset($nam) && isset($mes) && isset($rec) && isset($sub) && isset($sen))
        {
            $this->subject=$sub;
            $this->name=$nam;
            $this->sender=$sen;
            $this->receiver=$rec;
            $this->message=$mes;
            if($this->send_email())
                $this->error=true;
            else
                $this->error=false;
        }
        else
            $this->error=false;
    }
    function send_email(){
        $html=  file_get_contents(TEMPLATE_ADDRESS.'mailer.tpl');
        $html=  str_replace('{NAME}',$this->name, $html);
        $html=  str_replace('{EMAIL}',$this->receiver, $html);
        $html=  str_replace('{SUBJECT}',$this->subject, $html);
        $html=  str_replace('{CONTENT}',$this->message,$html);
        $headers  = 'From: no-reply@domain.com'. "\r\n" .
            'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=utf-8' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        return @mail($this->receiver,$this->subject,$html,$headers);
    }

    function email_error()
    {
        return $this->error;
    }


};