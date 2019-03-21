<?php

namespace Db;

Class DbFuncs{

  //get categories
  function get_cats($pdo){
    $query = 'SELECT * FROM categories';
    $stmt = $pdo->query($query);

    return $stmt;
  }



  //save item
  function item_entry($pdo, $ebay_id, $ebay_uri, $item_cat,$image_filename,$item_title){
    $query = 'INSERT INTO items (ebay_id, ebay_uri, item_category_id, image_filename, item_title, add_date) VALUES (?,?,?,?,?,?)';
    $stmt = $pdo->prepare($query);
    echo "Saving into database";
    ob_flush();
    flush();
    $stmt->execute([$ebay_id,$ebay_uri,$item_cat,$image_filename,$item_title,time()]);
  }



  function db_check_cat($pdo, $id){
    $query = 'SELECT * FROM categories WHERE id = ?';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $category = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    return $category;
  }

  function check_item_exists($pdo, $ebay_id){

    $query = "SELECT COUNT(*) as the_count FROM items WHERE ebay_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ebay_id]);
    $item_count = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if($item_count[0]['the_count'] == 0){
      return false;
    }
    else{
      return true;
    }

  }

  function get_item_id($pdo, $ebay_id){
    $query = "SELECT id FROM items WHERE ebay_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ebay_id]);
    $item_id = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return($item_id[0]['id']);

  }

  function check_date($pdo, $item_id, $status){
    $query = "select MAX(crawl_date) as crawl_date_max from item_crawls where item_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$item_id]);
    $crawl_date = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //return false if no entry is found
    if(!$crawl_date[0]['crawl_date_max']){
      echo "Status: $status - Okay to add new price and quantity, or to crawl item<br>";
      ob_flush();
      flush();
      return false; //okay to add new
    }

    $max_date = $crawl_date[0]['crawl_date_max'] + $GLOBALS['scraper_config']['scrape_interval'];

    if($max_date < time()){
      echo "Status: $status - Okay to add new price and quantity, or to crawl item<br>";
      ob_flush();
      flush();
      return false; //okay to add new
    }
    else{
      $date = date("H:i:s -- d-D, M, Y",$max_date);
      echo "<span class=\"error\">Status: $status - You must wait until $date before item can be crawled or data can be added</span><br>";
      ob_flush();
      flush();
      return true;
    }
  }

  //item id is not ebay item id, it is item id primary key from item table
  function add_time_update($pdo, $item_id, $price, $items_sold){
    if(!$price){
      $price = 0;
    }
    if(!$items_sold){
      $items_sold = 0;
    }
    $query = "INSERT INTO item_crawls (item_id,crawl_date,current_price,units_sold) VALUES(?,?,?,?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$item_id,time(),$price,$items_sold]);


  }

}
