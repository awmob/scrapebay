<?php

  namespace PageShow;

  Class PageShow{


    private function show_basic_header(){
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

    function show_home_page($pdo, $cats_pdo){

      $this->show_basic_header();

      ?>
          <title><?php echo  $GLOBALS['env_config']['app_name']  ?> | Home Page</title>
        </head>

        <body>

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



      <?php

      $this->show_basic_footer();

    }//end function




    //crawl all categories
    function show_all_cat_crawlers($ebay_crawler){
      $this->show_basic_header();
      ?>

        <title><?php echo  $GLOBALS['env_config']['app_name']  ?> | Ebay All Categories Crawler</title>
      </head>

      <body class="crawling">
        <h2>Crawling ebay.com.au...</h2>

        <?php

          echo "<p>Getting Ebay Items From All Categories on our database</p>";
          ob_flush();
          flush();
          //get the page
          $ebay_crawler->scrape_all_cats();



          $this->show_basic_footer();

    }



    function show_ebay_cat_crawler($ebay_crawler, $category){
      $this->show_basic_header();

      ?>
        <title><?php echo  $GLOBALS['env_config']['app_name']  ?> | Ebay Category Crawler</title>
      </head>

      <body class="crawling">
        <h2>Crawling ebay.com.au...</h2>

        <?php

          echo "<p>Getting Ebay Items From Single Category</p>";


            //get the page
            $ebay_crawler->scrape_cat($category[0]['id'], $category[0]['name'], $category[0]['url'], $GLOBALS['sites_config']['ebay']['next_page']);



          ob_flush();
          flush();

          $this->show_basic_footer();

    }

  }
