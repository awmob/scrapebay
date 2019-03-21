<?php

/*

  add user agents here to add web crawl obfuscation.
  user agents will be selected at random before each crawl

  User Agents obtained from: http://useragentstring.com/

  to set user agent:

    ini_set('user_agent', $user_agents_config[$x] );
*/
global $user_agents_config;

 $user_agents_config = array(
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586',
  'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A5370a Safari/604.1'
);
