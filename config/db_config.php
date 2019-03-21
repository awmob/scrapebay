<?php

  /*
    Store all database configuration details here
  */

  global $db_config;

  $db_config = array(

    'db_host' => 'localhost',
    'db_username' => 'root',
    'db_pass' => '',
    'db_database' => 'ebay_crawler',
    'charset' => 'utf8mb4',
    'pdo_options' => [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]

  );
