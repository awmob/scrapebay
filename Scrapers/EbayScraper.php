<?php

  namespace Scrapers;
  use \Db\DbFuncs as DbFuncs;
  use \Helpers\Helpers;

  Class EbayScraper extends Scraper{

    use Helpers;

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
      $cats = $dbfuncs->get_cats_active_lastcrawl_as($this->pdo);

      //crawl through the categories
      while ($row = $cats->fetch()){
        //only scrape if active
        $this->scrape_cat($row['id'], $row['name'], $row['url'], $GLOBALS['sites_config']['ebay']['next_page']);
        //set the scrape date
        $dbfuncs->set_cat_crawl_date($this->pdo, time(), $row['id']);

      }

      unset($dbfuncs);
    }


    //scrapes all items
    function scrape_all_items($dbfuncs){
      $items = $dbfuncs->get_items_last_crawl_sort($this->pdo);

      //$x = 2;

      $counter = 1;
      while ($row = $items->fetch()){
      /*  $x--;
        if($x <= 0){
          break;
        }*/

        //chec if okay to crawl - false means okay to crawl
        $crawl_status = $dbfuncs->check_date($this->pdo, $row['ebay_id'], "<span class=\"working\">Attempting to crawl item #$counter: </span>" . $row['item_title']);

        if(!$crawl_status){
          //sleep
          $this->sleeper();
          $single_item_array = array(2 => $row['ebay_uri']);
          $single_item_array = $this->process_individual_item($single_item_array);
          //uri [2] price [3] quantity [4];

          //insert into db - update crawl date in db, add to itemcrawls
          //check values are valid and add if okay
          var_dump($single_item_array);
          if($this->check_num($single_item_array[4]) && $this->check_is_price($single_item_array[3])){
            echo "Updating item!<hr>";
            flush();
            //add item crawl details
            $dbfuncs->add_time_update($this->pdo, $row['id'], $single_item_array[3], $single_item_array[4]);
            //add latest update time
          }

          $dbfuncs->update_last_item_crawl($this->pdo, time(), $row['id']);

        }//endif

        ++$counter;

      }//end while

    }


    function scrape_get_cats($levels_down, $url, $catname, $min_items){
      $levels_down--;
      $dbfuncs = new DbFuncs();
      /*
        start with seed link

        check for item links - if exist and more than 10, then this is a good cat
          save cat name and cat url to db

        check for other cats down to one level lower

      */
      if($levels_down > 0){
        //sleep first
        $this->sleeper();

        //scrape the page
        $dom = $this->scrape_page($url);
        echo "Crawling $url <br>";

        //get_items
        //check if link contains items. If it does then it is valid and we can add to db
        $items = $this->get_items($dom);

        //link is vald if there are over a certain number of items on the page
        if(sizeof($items) > $min_items){
          //extract details and check if in dbase
          $catnum = trim($this->get_cat_number($url));
          $catnum_okay = $this->check_num($catnum);
          //if the category number is okay then add cat to db
          if($catnum_okay){
            $cat_exists = $dbfuncs->check_cat_exists($this->pdo, $url, $catnum);
            //add the category if it doesn't exist
            if(!$cat_exists){
              $dbfuncs->add_category($this->pdo, $catnum, $catname, $url);
            }
          }

        }

        //gets array of all links with url and title
        $links = $this->get_cat_href_only($dom, "a", "href");

        //only continue if valid links exist
        if($links){
          //check the links in the array
          //temporarily only check 43rd one
          $x = 2000;
          foreach($links as $lnk){
            $x--;
            if($x <= 0){
              break;
            }

            //skip if link exists in db
            $catnum = trim($this->get_cat_number($lnk['url']));
            $catnum_okay = $this->check_num($catnum);
            //if the category number is okay then add cat to db
            if($catnum_okay){
              $cat_exists = $dbfuncs->check_cat_exists($this->pdo, $url, $catnum);
              //crawl the category if it doesn't exist
              if(!$cat_exists){
                //crawl category
                $this->scrape_get_cats($levels_down, $lnk['url'], $lnk['title'],$min_items);
              }

            }
          }
        }
        else{
          echo "No valid links exist!<br>";
        }

      }//end if

      unset($dbfuncs);

    }

    private function get_cat_number($url){
      $pattern = "/^https:\/\/www.ebay.com.au\/b\/.*?([\d]+)\/bn_/";
      $okay = preg_match($pattern, $url, $matches);
      if($okay){
        return $matches[1];
      }
      else{
        return false;
      }
    }




    //gets category ebay hrefs and titles
    private function get_cat_href_only($dom, $tag, $param){

      $span_arr = array();
      $span = $dom->getElementsByTagName($tag);
      //go through all of the tags
      foreach($span as $d){
        //get the desired attribute
        $href = $d->getAttribute($param);
        //check the string is a valid category link
        $valid = $this->check_if_cat_link($href);
        //if valid get the title
        if($valid){
          //only add if the title is valid
          if(strlen(trim($d->textContent)) > 0){
            $tmp_array = array('title'=>trim($d->textContent),'url'=>$href);
            array_push($span_arr, $tmp_array);
          }
        }

      }
      if(sizeof($span_arr) >=1){
        return $span_arr;
      }
      else{
        //no result found;
        return false;
      }
    }

    //checks if the url is a valid category link
    private function check_if_cat_link($url){
      $pattern = "/^(https:\/\/www.ebay.com.au\/b\/).*?(bn_)/";
      $valid = preg_match_all($pattern, $url, $match);

      if($valid){
        return true;
      }
      else{
        return false;
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

      unset($dbfuncs);
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

      unset($dbfuncs);

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

    //extracts the number of items sold from string
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


    //$single_item_array link[2]
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

      if(!$price){
        $price = "0";
      }

      //get sold number and tidy up
      $sold_quantity = trim($this->get_element_contents($dom, "vi-qtyS", "span", "class"));
      $sold_quantity = $this->extract_sold($sold_quantity);

      if(!$sold_quantity){
        $sold_quantity = "0";
      }

      array_push($single_item_array,$price);
      array_push($single_item_array,$sold_quantity);

      return($single_item_array);
    }



    /*
      $dom is the dom element, param is the name of parameter ie. class = "param"
      $tag is type of tag ie "a" "div" etc.
      $attr is type of attribute ie "a" "class" etc.

    */
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

    //gets array of items from the category pages $dom is the dom element
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
