<?php

require '../../include/session.php';

global $session;

$name = $_REQUEST['name'];
$email = $_REQUEST['email'];
$pass = $_REQUEST['pass'];
//$sex = $_REQUEST['sex'];
header ( "access-control-allow-origin: *" );
if(isset($_REQUEST['callback'])) {
	header ( 'Content-type: application/javascript' );
	exit(
		$_REQUEST['callback'] . '(' . $session->register($email, $pass, $pass, $name) . ')'	
	);
} else {
	
	header ( 'Content-type: application/json' );
	exit($session->register($email, $pass, $pass, $name));
}

