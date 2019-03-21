<?php

  namespace Scrapers;


  Abstract Class Scraper{

    protected $iterations;
    protected $pdo;

    function __construct($iterations, $pdo){
      $this->iterations = $iterations;
      $this->pdo = $pdo;
    }

    public function get_iterations(){
      return $this->iterations;
    }

    protected function get_parse_image($image_uri){
      $folder = "storage/images/";

      $img = file_get_contents($image_uri);

      $rawname = time() . "-" . rand(0,1000);
      $filename = md5($rawname) . ".jpg";
      $file_path = $folder . $filename;
      $fh = fopen($file_path, 'w');
      fwrite($fh, $img);
      fclose($fh);

      return $filename;

    }


    public function scrape_page($uri){

      ini_set('user_agent', $GLOBALS['user_agents_config'][1]);
      libxml_use_internal_errors(true);

      $doc = new \DOMDocument();
      //s-item__wrapper
      //echo file_get_contents('ebaytemp.html');

      $doc->loadHTML(file_get_contents($uri));
      //$doc->loadHTML(file_get_contents('ebaytemp.html'));

      return $doc;
    }


    public function sleeper(){
      $sleepy = rand(10,100) / 15;
      echo "<br><br>Sleep Time: " . $sleepy . " seconds<br>";
      sleep($sleepy);
    }

  }
