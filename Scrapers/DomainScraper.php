<?php

  namespace Scrapers;

  Class DomainScraper extends Scraper{

    function scrape_auctions($uri){


      ini_set('user_agent', $GLOBALS['user_agents_config'][0]);

      libxml_use_internal_errors(true);

      $doc = new \DOMDocument();
      $doc->loadHTML(file_get_contents($uri));

      //need to save to db, but first need to check if this data exists in db

      return $doc;
    }

    function get_title($doc){
      $title = $doc->getElementsByTagName("title");
      return $title[0]->textContent;
    }

    function get_meta($doc){
      //get clearance rate and show
      $meta = $doc->getElementsByTagName("meta");
      foreach($meta as $m){
        if($m->getAttribute("name") == "description"){
          return $m->getAttribute("content");
        }
      }

      //no results
      return false;
    }

     function parse_script_json($doc){
      $scripts = $doc->getElementsByTagName("script");

      //auction contents
      $auction_data = $scripts[2]->textContent; //save this into db

      $auction_data = str_replace("window['__domain_group/APP_PROPS'] = ", "", $auction_data);
      $auction_data = str_replace(",\"baseUrl\":\"https://www.domain.com.au\"}; window['__domain_group/APP_PAGE'] = 'auction-results'", "", $auction_data);


      //separate the data
      $delim = "},{";

      $auction_bits = explode($delim, $auction_data);

      return $auction_bits;
    }

    //insert values into the database
    //Need add functions to check for duplicate entries
    function auction_insert($suburb, $price, $address, $pdo){
        $sql = "INSERT INTO domain_auctions (suburb, price, address) VALUES (?,?,?)";
        $stmt= $pdo->prepare($sql);
        $stmt->execute([$suburb, $price, $address]);
    }





  }
