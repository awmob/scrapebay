<?php

namespace Db;

Class DbFuncs{


  function update_last_item_crawl($pdo, $lastcrawl, $item_id){
    $query = "UPDATE items SET lastcrawl = ? WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$lastcrawl, $item_id]);
  }


  function get_items_last_crawl_sort($pdo){
    $query = "SELECT * FROM items ORDER BY lastcrawl ASC";
    $stmt = $pdo->query($query);

    return $stmt;
  }

  //get categories
  function get_cats($pdo){
    $query = 'SELECT * FROM categories';
    $stmt = $pdo->query($query);

    return $stmt;
  }

  function get_cats_active_lastcrawl_as($pdo){
    $query = 'SELECT * FROM categories WHERE active = \'1\' ORDER BY lastcrawl ASC';
    $stmt = $pdo->query($query);

    return $stmt;
  }

  //set latest category crawl date with time()
  function set_cat_crawl_date($pdo, $crawldate, $catid){
    $query = "UPDATE categories SET lastcrawl = ? WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt = $stmt->execute([$crawldate, $catid]);
  }

  function enable_all_cats($pdo){
    $query = "UPDATE categories SET active = 1";
    $stmt = $pdo->query($query);
    $stmt->execute();
  }

  function disable_cat($pdo, $id){
    $query = "UPDATE categories SET active = 0 where id = ?";
    $stmt = $pdo->prepare($query);
    $stmt = $stmt->execute([$id]);
  }

  //chec category exists
  function check_cat_exists($pdo, $url, $ebay_id){
    $query = 'SELECT COUNT(*) as thecount from categories WHERE ebay_id = ? OR url = ?';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ebay_id, $url]);
    $category = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    if($category[0]['thecount'] > 0){
      return true;
    }
    else{
      return false;
    }

  }

  function add_category($pdo, $ebay_id, $catname, $url){
    echo "Adding $catname into dbase...<br>";
    ob_flush();
    flush();
    $query = 'INSERT INTO categories (ebay_id, name, url) VALUES (?,?,?)';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ebay_id, $catname, $url]);
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

  //returns false if okay to crawl / add
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
