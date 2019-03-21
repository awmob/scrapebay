<?php

  namespace Scrapers;
  use \Db\DbFuncs as DbFuncs;

  Class EbayScraper extends Scraper{



    /*

      Scrape each item on cat page first
      save each item in dbase
        allow crawl only once a day
        set last crawl date
        if last crawl date is within 24 hours, do not allow crawl of individual item

        individual crawl items only after category crawl
    */

    //main category scraper - scrape all cats at once
    function scrape_all_cats(){
      //get cats
      $dbfuncs = new DbFuncs();
      $cats = $dbfuncs->get_cats($this->pdo);

      //crawl through the categories
      while ($row = $cats->fetch()){
        $this->scrape_cat($row['id'], $row['name'], $row['url'], $GLOBALS['sites_config']['ebay']['next_page']);
      }
    }


    //need to add time checker to not try to scrape individual if already done recently
    function scrape_cat($cat_id, $catname, $uri, $next_suffix){
      for($i=1; $i<=$this->iterations; $i++){
        //next page link setting
        if($i > 1){
          $uri_next = $uri . $next_suffix . $i;
        }
        else{
          $uri_next = $uri;
        }

        echo "Getting $catname : $uri_next<br>";
        ob_flush();
        flush();

        echo "Page $i of $this->iterations<br>";
        //get the page
        $dom = $this->scrape_page($uri_next);
        //get the items on the page
        $items = $this->get_items($dom);
        //get the individual items
        $items = $this->get_individual_items($items);
        //enter into database
        echo "<hr/>";
        $this->process_into_db($items, $cat_id);
        echo "<hr/>";

      }//end loop

    }

//$this->pdo, $ebay_id, $item_cat,$image_filename,$item_title
    //enter items into database
    private function process_into_db($items_arr, $cat_id){
      $dbfuncs = new DbFuncs();

      foreach($items_arr as $items){


        //get image and save image

        $ebay_uri =$items[2];
        $ebay_id = $this->get_ebay_item_id($ebay_uri);
        echo "<span id=\"$ebay_id\">$ebay_id</span>";

        var_dump($items);

        //check if id exists, if so do not process new item
        if($dbfuncs->check_item_exists($this->pdo, $ebay_id)){
          echo "<span class=\"error\">ITEM EXISTS - Not Added</span><br>";
          ob_flush();
          flush();
        }
        // item doesn't exist
        else{
          $image_file = $this->get_parse_image($items[0]);
          $title = $items[1];
          //enter into db
          $dbfuncs->item_entry($this->pdo, $ebay_id, $ebay_uri, $cat_id,$image_file,$title);

        }

        //NOW add the line entries for price and sold quantity
        //get the id
        $item_id = $dbfuncs->get_item_id($this->pdo, $ebay_id);

        echo "<span>CHECKING DATE...</span><br>";
        ob_flush();
        flush();
        $date_status =  $dbfuncs->check_date($this->pdo, $item_id, "Item Crawled - Trying to add price and quantity.");
        //if the date is false then add
        if(!$date_status){
          echo "Adding Price & Quantity<br>";
          ob_flush();
          flush();
          $dbfuncs->add_time_update($this->pdo, $item_id, $items[3], $items[4]);
        }




      }
    }

    //extract ebay id from the url
    private function get_ebay_item_id($uri){
      $pattern = "/.*\/([0-9]+)\?/";
      preg_match($pattern, $uri, $match);
      return $match[1];
    }



    //goes into individual item
    function get_individual_items($items_array){

      $dbfuncs = new DbFuncs();

      foreach($items_array as &$item){
        $crawl_status = false;

        //check if processed recently
        $ebay_id = $this->get_ebay_item_id($item[2]);
        echo "<hr/>";
        echo "<a href=\"#$ebay_id\">$ebay_id</a> - ";

        if($dbfuncs->check_item_exists($this->pdo, $ebay_id)){
          $item_id = $dbfuncs->get_item_id($this->pdo, $ebay_id);
          $crawl_status = $dbfuncs->check_date($this->pdo, $item_id, "Attempting to crawl item");
        }

        //only crawl if not crawled recently
        if(!$crawl_status){
        //gets the individual item details and adds back to main $items_array via reference
          $this->sleeper();
          $item = $this->process_individual_item($item);

          echo "<hr/>";

          ob_flush();
          flush();
        }


      }//end foreach

      return $items_array;

    }

    private function extract_price($price){
      $pattern = "/[0-9]+\.*[0-9]*/";
      preg_match($pattern, $price, $match);
      if($match){
        return($match[0]);
      }
      else{
        return false;
      }
    }


    private function extract_sold($sold){
      $sold = str_replace(",","",$sold);

      $pattern = "/[0-9]+/";
      preg_match($pattern, $sold, $match);
      if($match){
        return($match[0]);
      }
      else{
        return false;
      }
    }



    private function process_individual_item($single_item_array){
      //visit the item
      $link = $single_item_array[2];
      $dom = $this->scrape_page($link);

      //get item price
      $price = $this->get_element_contents($dom, "prcIsum", "span", "id");
      if(!$price){
        $price = $this->get_element_contents($dom, "mm-saleDscPrc", "span", "id");
      }
      //tidy up price
      $price = $this->extract_price($price);

      //get sold number and tidy up
      $sold_quantity = trim($this->get_element_contents($dom, "vi-qtyS", "span", "class"));
      $sold_quantity = $this->extract_sold($sold_quantity);

      array_push($single_item_array,$price);
      array_push($single_item_array,$sold_quantity);

      return($single_item_array);
    }

    private function get_element_contents($dom, $param, $tag, $attr){
      //item_sell_price
      $span = $dom->getElementsByTagName($tag);
      foreach($span as $d){
        if(strpos($d->getAttribute($attr),$param,0) !== false){
          return $d->textContent;
        }
      }
      //no result found;
      return false;
    }

    //gets array of items from the category pages
    function get_items($dom){

      //set the items array
      $items_array = array();

      $divs = $dom->getElementsByTagName("div");

      foreach($divs as $d){
        //get the individual item
        if(strpos($d->getAttribute("class"),"s-item__wrapper",0) !== false){
          //image and title
          $item_arr = $this->get_image($d);
          //item_link
          if($item_arr){
            $link = $this->get_link($d);
            if($link){
              array_push($item_arr, $link);
              array_push($items_array, $item_arr);
            }
            else{


            } //end else

          } //end if

        }//end if`

      }//end foreach

      return $items_array;

    }//end function

    private function get_link($div){
      $link = $div->getElementsByTagName("a");
      foreach($link as $l){

        if(strpos($l->getAttribute("class"),"s-item__link",0) >= 0){
          return $l->getAttribute("href");
        }
        else{
          return false;
        }
      }

    }

    private function get_image($div){

      $img = $div->getElementsByTagName("img");

      foreach($img as $i){
        if(strpos($i->getAttribute("class"),"s-item__image-img",0) >= 0){

          //get the image url
          $imge = $i->getAttribute("src");

          //if the image is a gif, then it is a placeholder, so use other source
          $pattern = "/^.*\.gif$/";
          if( preg_match($pattern, $imge) ){
            $imge = $i->getAttribute("data-src");
          }

          //now get the item title
          $title = $i->getAttribute("alt");

        }

        if(isset($imge) && isset($title)){
          return [$imge, $title];
        }
        else{
          return false;
        }
      }
    }






  }
