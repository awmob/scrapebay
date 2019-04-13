<?php
  error_reporting( E_ALL );
  set_time_limit(0);

  /*
https://www.ebay.com.au/n/all-categories

  */

  //autoload classes
  spl_autoload_register(function ($class_name) {
    $class_name = str_replace("\\","/",$class_name);
    //echo $class_name . ".php"; //can be removed
    //echo "<br>";  //can be removed
    if (file_exists($class_name . ".php")) {
      require_once $class_name . ".php";
    }
  });

  //load require files
  function misc_loader($file_name){
    require_once $file_name . ".php";
  }

  //load config
  misc_loader("config/db_config");
  misc_loader("config/scraper_config");
  misc_loader("config/env_config");
  misc_loader("config/user_agents_config");





  //load database
  $pdo = new Db\Db($db_config['db_host'], $db_config['db_username'], $db_config['db_pass'], $db_config['db_database'], $db_config['charset'], $db_config['pdo_options']);

  $pdo = $pdo->get_pdo();

  $dbfuncs = new Db\DbFuncs();


  //show the contents
  $pages = new PageShow\PageShow();

  //process pages in response to GET and POST
  require_once("page_process.php");
