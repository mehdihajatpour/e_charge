<?php
session_set_cookie_params(3600);
session_start();
require_once 'config.php';
require_once 'libs/jdf.php';
require_once 'libs/module/function_module.php';
require_once 'libs/db.php';
$database=new Db();
$dbh=$database->getDbHandel();
define('TEMPLATE_ADDRESS','template/'.TEMPLATE_NAME.'/');

require_once 'libs/router.php';
$router=new Router($_SERVER['QUERY_STRING']);


?>