<?php
class Statistics
{
    private $today;
    private $sold_today;
    /*private $total_payment_error;*/
    /*private $views;*/
    private $sold_week;
    private $sold_month;
    private $count_exists;
    private $view_list_count_price=array();

    public function __construct()
    {
        $this->get_today();
        $this->get_sold_today();
        $this->get_sold_week();
        $this->get_sold_month();
        $this->get_count_exists();
        $this->get_view_list_count_price();
    }
    /*خروجی ارسالی ماژول*/
    public function get_output()
    {
        return array('class_name'=>'statistics','today'=>$this->today,
            'sold_today'=>$this->sold_today,'sold_week'=>$this->sold_week,
            'sold_month'=>$this->sold_month,'count_exists'=>$this->count_exists,
            'view_list_count_price'=>$this->view_list_count_price);
    }
    public function get_today()
    {
        global $dbh;
        $sql='SELECT COUNT(*) from charge_sold WHERE date_time >= '.time().' - 86400';
        $sth=$dbh->prepare($sql);
        $sth->execute();
        $this->today=$sth->fetchColumn(0);
    }
    public function get_sold_today()
    {
        global $dbh;
        $sql='SELECT SUM(amount) from charge_sold WHERE date_time >= '.time().' - 86400';
        $sth=$dbh->prepare($sql);
        $sth->execute();
        $this->sold_today=$sth->fetchColumn(0);
    }
    public function get_sold_week()
    {
        global $dbh;
        $sql='SELECT SUM(amount) from charge_sold WHERE date_time >= '.time().' - 604800';
        $sth=$dbh->prepare($sql);
        $sth->execute();
        $this->sold_week=$sth->fetchColumn(0);
    }
    public function get_sold_month()
    {
        global $dbh;
        $sql='SELECT SUM(amount) from charge_sold WHERE date_time >= '.time().'-'.(date('t',time())*86400);
        $sth=$dbh->prepare($sql);
        $sth->execute();
        $this->sold_month=$sth->fetchColumn(0);
    }
    public function get_count_exists()
    {
        global $dbh;
        $sql='SELECT COUNT(*) FROM charge_password WHERE is_sold = 0';
        $sth=$dbh->prepare($sql);
        $sth->execute();
        $this->count_exists=$sth->fetchColumn(0);
    }
    public function get_view_list_count_price()
    {
        global $dbh;
        $sql="SELECT count(*),charge_amount FROM charge_password WHERE is_sold=0 GROUP BY charge_amount ";
        $sth=$dbh->prepare($sql);
        $sth->execute();
        $this->view_list_count_price=$sth->fetchAll(PDO::FETCH_ASSOC);
    }

};
