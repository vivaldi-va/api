<?php

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'www_user');
define('DB_PASS', 'wwwpass');
define('DB_NAME', 'ostosnero');

define('SECRET', '4b3403665fea6');

define("COOKIE_NAME_IDENT", "on_user");	// cookie for user id
define("COOKIE_NAME_TOKEN", "on_tok");	// cookie for user id

define("COOKIE_EXPIRE", 60*60*24*100);  // 100 days by default
define("COOKIE_PATH", "/");  			// Avaible in whole domain