<?php

  $show_index = true;

  // single category crawl
  if(isset($_GET['crawl_status']) && isset($_GET['category'])) {
    //load and declare the scrapers
    if($_GET['crawl_status'] == 1){
      $_GET['allcats'] = false;
      //get the category info
      $category = $dbfuncs->db_check_cat($pdo, $_GET['category']);

      //only do if category exists
      if($category){
        $show_index = false;

        //load the ebay scraper
        $ebay_crawler = new Scrapers\EbayScraper($scraper_config['base_iterations'], $pdo);

        //show ebay page
        $pages->show_ebay_cat_crawler($ebay_crawler, $category, $dbfuncs);
      }
    }

  }


  // all category crawl
  else if(isset($_GET['crawl_status']) && isset($_GET['allcats'])) {
    //load and declare the scrapers
    if($_GET['crawl_status'] == 1){
      //only do if categories set to all
      if($_GET['allcats'] == 1){
        $show_index = false;
        //load the ebay scraper
        $ebay_crawler = new Scrapers\EbayScraper($scraper_config['base_iterations'], $pdo);
        //show ebay page
        $pages->show_all_cat_crawlers($ebay_crawler);
      }
    }
  }


  // all items crawl
  else if(isset($_GET['crawl_status']) && isset($_GET['allitems'])) {
    //load and declare the scrapers
    if($_GET['crawl_status'] == 1){
      //only do if items crawl set to all
      if($_GET['allitems'] == 1){
        $show_index = false;
        //load the ebay scraper
        $ebay_crawler = new Scrapers\EbayScraper($scraper_config['base_iterations'], $pdo);
        //show ebay page
        echo "ALL ITEMS";
        $pages->show_all_item_crawlers($ebay_crawler, $dbfuncs);
      }
    }
  }

  // find new categories
  else if(isset($_GET['crawl_status']) && isset($_GET['getcats'])) {
    //load and declare the scrapers
    if($_GET['crawl_status'] == 1){
      //only do if categories set to all
      if($_GET['getcats'] == 1){
        $show_index = false;
        //load the ebay scraper
        $ebay_crawler = new Scrapers\EbayScraper($scraper_config['base_iterations'], $pdo);
        //show ebay page
        $pages->show_category_finder($ebay_crawler);
      }
    }
  }


  if(isset($_POST['addcats']) && isset($_POST['categories'])){
    if($_POST['addcats'] == 1 && trim($_POST['categories'])){
      $pages->show_cat_adder();


      $show_index = false;
    }
  }

  if(isset($_GET['disablecat'])){
    if($_GET['disablecat'] == 1){

      //process subs if subbed
      if(isset($_POST['subbed'])){
        if($_POST['subbed'] == 1){
          //first enable all subs, then disable subs
          $dbfuncs->enable_all_cats($pdo);
          //disable cats
          foreach($_POST['subvar'] as $s){
            $dbfuncs->disable_cat($pdo, $s);
          }

        }
      }

      $pages->show_disable($pdo, $dbfuncs);
      $show_index = false;
    }
  }




  //show home page if no other page
  if($show_index){
    //get categories
    $cats_pdo = $dbfuncs->get_cats($pdo);

    $pages->show_home_page($pdo, $cats_pdo);

  }
