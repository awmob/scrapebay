<?php

/*

  config for scrapers
*/
global $scraper_config;

$scraper_config = array(

  'base_iterations' => 20,
  'scrape_interval' => 20000 //5 hours

);


global $sites_config;

$sites_config = array(
  'ebay' => [

    'index' => 1,
    'core_category_uri' => 'https://www.ebay.com.au/n/all-categories/?_rdc=1',

    'next_page' => '?_pgn=',

    //parameters on the category search page
    'search_node_parameters' => [
      'search_page_item' => ['div','class','s-item__wrapper'],  //get main item
      'search_page_item_img' => ['img','class','s-item__image-img'],  //get item image
      'search_page_item_img_alt' => ['alt'],  //get item title
      'search_page_item_link' => ['a','class','s-item__link']  //get item link  https://www.ebay.com.au/itm/Anti-Fog-Fog-Free-Shower-Mirror-Fogless-Shaving-Shave-Mirror-Bathroom-17X13cm-AU/152270056555?hash=item237400ac6b:g:EQ8AAOSwEzxYeG7n

    ],

    //parameters on the item page
    'item_node_parameters' => [
      'item_sold_amount' => ['span','class','vi-qtyS-hot-red'], //get items sold
      'item_sell_price' => ['span','id','prcIsum'] //get item price
    ]
  ]


);
