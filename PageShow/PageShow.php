<?php

  namespace PageShow;

  Class PageShow{


    private function show_basic_header($title){
      ?>

      <!doctype html>
      <html lang="en">
        <head>
          <!--<link rel="shortcut icon" href="https://www.advantageholidays.com.au/storage/favicon.ico">
          <script src="https://www.advantageholidays.com.au/js/back_process.js"></script>-->
          <link href="css/main.css" rel="stylesheet">
          <meta charset="utf-8">
          <meta http-equiv="X-UA-Compatible" content="IE=edge">
          <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">


          <title><?php echo  $GLOBALS['env_config']['app_name']  ?> | <?php echo $title ?></title>
        </head>

        <body>

          <div class="home"><a href="http://<?php echo $_SERVER['HTTP_HOST'] . "/ebay_scraper/" ?>">HOME</a></div>

          <?php
    }


    private function show_basic_footer(){
      ?>
            <footer>

            </footer>
          </body>
        </html>

      <?php
    }


    function show_cat_adder(){
      $this->show_basic_header("Adding Categories");

      ?>
      <h2>Adding Categories</h2>

      <?php
    }



    function show_home_page($pdo, $cats_pdo){

      $this->show_basic_header("Home");

      ?>


          <h1>Ebay Web Crawler Home</h1>

          <p>Future implementation: Password protected access only, cron job</p>

          <div>
            <hr/>
            <h2>Crawl All Categories</h2>

              <form method="GET" action="index.php">
                <input type="hidden" name="crawl_status" value="1">
                <input type="hidden" name="allcats" value="1">
                <input type="submit" value="Crawl All Ebay Categories">
              </form>
          </div>


          <div>
            <hr/>
            <h2>Crawl All Ebay Items in Dbase</h2>

              <form method="GET" action="index.php">
                <input type="hidden" name="crawl_status" value="1">
                <input type="hidden" name="allitems" value="1">
                <input type="submit" value="Crawl All Ebay Items">
              </form>
          </div>



          <div>
            <hr/>
            <h2>Crawl a Single Category</h2>

            Select a Category to crawl:
            <form method="GET" action="index.php">
              <select name="category">

                <?php //loop through pdo fetched
                while ($row = $cats_pdo->fetch()){

                  ?>

                    <option value="<?php echo $row['id'] ?>">

                      <?php
                        echo $row['name'];
                      ?>

                    </option>
                  <?php
                }
                ?>


              </select>
              <input type="hidden" name="crawl_status" value="1">
              <input type="submit" value="Start Crawling">
            </form>

          </div>


          <div>
            <hr/>
            <h2>Disable / Enable Ebay Categories</h2>

            <p>Gives a list of categories to disable / enable</p>

              <form method="GET" action="index.php">
                <input type="hidden" name="disablecat" value="1">
                <input type="submit" value="Disable Ebay Categories">
              </form>
          </div>


          <div>
            <hr/>
            <h2>Find New Ebay Categories</h2>

            <p>Crawls ebay sitemap and finds new valid categories to add.</p>

              <form method="GET" action="index.php">
                <input type="hidden" name="crawl_status" value="1">
                <input type="hidden" name="getcats" value="1">
                <input type="submit" value="Find New Ebay Categories">
              </form>
          </div>



          <div>
            <hr/>
            <h2>Add Categories</h2>

            <form method="POST" action="index.php">
              <input type="hidden" name="addcats" value="1">
              <p>Add new Ebay Categories. Category Id -tab- Category Name</p>
              <textarea name="categories" rows="10" cols="40" class="textbox"></textarea><br>
              <input type="submit" value="Add Categories">
            </form>


          </div>


      <?php

      $this->show_basic_footer();

    }//end function




    function show_disable($pdo, $dbfuncs){
      $this->show_basic_header("ebay.com.au Disable / Enable Categories");
      ?>

      <h2>Show categories to disable / enable</h2>

      <p>Tick to disable</p>



      <?php

      $stmt = $dbfuncs->get_cats($pdo);
      $stmt->execute();
      $cats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

      ?>

      <form method="POST" action="index.php?disablecat=1">
        <input type="hidden" name="subbed" value="1">
        <input type="submit" value="Update Active Status">
        <table cellpadding="5">

          <tr><td>Disabled</td><td>Name</td></tr>
          <?php

          echo sizeof($cats) . " categories<br>";

          foreach($cats as $ct){
            echo "<tr>";

            echo "<td>";

              if($ct['active'] == 1){
                echo "<input type=\"checkbox\" name=\"subvar[]\" value=\"".$ct['id']."\">";
              }
              else{
                echo "<input type=\"checkbox\" name=\"subvar[]\"  value=\"".$ct['id']."\"  checked>";
              }

            echo "</td>";

            echo "<td>";

              echo $ct['name'];

            echo "</td>";



            echo "</tr>";
          }

          ?>

        </table>
        <input type="submit" value="Update Active Status">
      </form>

      <?php

    }


    //crawl all categories
    function show_category_finder($ebay_crawler){
      $this->show_basic_header("ebay.com.au Category Finder");
      ?>

        <h2>Crawling ebay.com.au... Category Finder</h2>

        <?php

          echo "<p>Crawling ebay for new categories</p>";

          flush();
          //get the page
           $ebay_crawler->scrape_get_cats($GLOBALS['scraper_config']['cat_levels'],
            $GLOBALS['sites_config']['ebay']['allcats'],  "All Categories", $GLOBALS['scraper_config']['min_items']);
        /*  $ebay_crawler->scrape_get_cats($GLOBALS['scraper_config']['cat_levels'],
              "http://127.0.0.1/ebay_scraper/ebaytemp.html", "All Categories", $GLOBALS['scraper_config']['min_items']); */


          $this->show_basic_footer();

    }


    //crawl all categories
    function show_all_cat_crawlers($ebay_crawler){
      $this->show_basic_header("ebay.com.au Crawler");
      ?>

        <h2>Crawling ebay.com.au...</h2>

        <?php

          echo "<p>Getting Ebay Items From All Categories on our database</p>";
          ob_flush();
          flush();
          //get the page
          $ebay_crawler->scrape_all_cats();



          $this->show_basic_footer();

    }



    //crawl all categories
    function show_all_item_crawlers($ebay_crawler, $dbfuncs){
      $this->show_basic_header("ebay.com.au All Items Crawler");
      ?>

        <h2>Crawling ebay.com.au...</h2>

        <?php

          echo "<p>Getting all Ebay items from our database and updating crawl</p>";
          ob_flush();
          flush();



          //get the page
          $ebay_crawler->scrape_all_items($dbfuncs);



          $this->show_basic_footer();

    }



    function show_ebay_cat_crawler($ebay_crawler, $category, $dbfuncs){
      $this->show_basic_header("Ebay.com.au Cat Crawler");

      ?>

        <h2>Crawling ebay.com.au...</h2>

        <?php

          echo "<p>Getting Ebay Items From Single Category</p>";


            //get the page
            $ebay_crawler->scrape_cat($category[0]['id'], $category[0]['name'], $category[0]['url'], $GLOBALS['sites_config']['ebay']['next_page']);

            //set crawl date
            $dbfuncs->set_cat_crawl_date($ebay_crawler->$pdo, time(), $category[0]['id']);



          ob_flush();
          flush();

          $this->show_basic_footer();

    }

  }
