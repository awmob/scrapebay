<?php

namespace Helpers;


Trait Helpers{

  //returns true if string is a number only
  function check_num($str){
    $pattern = "/^[\d]+$/";
    return preg_match($pattern, $str);
  }

  function check_is_price($str){
    $pattern = "/^[\d\.]+$/";
    return preg_match($pattern, $str);
  }


  public function get_cat_links($seed_page, $depth){
    /*
      Go through each url
      check next url for existence of valid items. Check name of url

    */
    //get the urls and the titles
    $pattern = "/href=\"(.*?)\".*?>(.*?)<\/a>/";

    preg_match_all($pattern, $file, $match);

  }

}
