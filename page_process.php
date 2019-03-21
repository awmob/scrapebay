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
        $pages->show_ebay_cat_crawler($ebay_crawler, $category);
      }
    }

  }


  // all category crawl
  if(isset($_GET['crawl_status']) && isset($_GET['allcats'])) {
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


  //show home page if no other page
  if($show_index){
    //get categories
    $cats_pdo = $dbfuncs->get_cats($pdo);

    $pages->show_home_page($pdo, $cats_pdo);

  }
