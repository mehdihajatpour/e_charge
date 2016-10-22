<?php
class Db
{

    private $dbHandel = NULL;

    public function __construct()
    {

        try
        {
            $dsn = "mysql:host=".DATABASE_HOST.";dbname=".DATABASE_NAME.";charset=utf8";
            $this->dbHandel = new PDO($dsn, DATABASE_ADMIN,DATABASE_ADMIN_PASSWORD);
            $this->dbHandel->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); //ست کردن نحوه نمایش ارور
        }
        catch (PDOException $error)
        {
            f_redirect("index.php?db_error_class=1");
        }

    }

    public function getDbHandel()
    {
        if (isset($this->dbHandel))
            return $this->dbHandel;
        else
            return 0;
    }

    public function __destruct()
    {
        if (isset($this->dbHandel))
            $this->dbHandel = NULL;
    }
};

