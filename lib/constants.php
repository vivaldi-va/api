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

/**
 * Database Table Constants - these constants
 * hold the names of all the database tables used
 * in the script.
 */
define("TBL_USERS", "users");
define("TBL_SHOPPING_LISTS", "shoppinglists");
define("TBL_SHOPPING_LIST_PRODUCTS", "shoppinglistproducts");
define("TBL_SHOPPING_LISTS_HISTORY", "shoppingListsHistory");
define("TBL_SHOPPING_LIST_PRODUCTS_HISTORY", "shoppingListProductsHistory");
define("TBL_PRODUCTS", "products");
define("TBL_PRICES", "prices");
define("TBL_CATEGORIES", "categories");
define("TBL_SHOPS", "shops");
define("TBL_CHAINS", "chains");
define("TBL_BRANDS", "brands");
define("TBL_INVITATIONS", "invitations");
define("TBL_LOG", "log");
define("TBL_LANGUAGES", "languages");
define("TBL_TRANSLATION", "translation");