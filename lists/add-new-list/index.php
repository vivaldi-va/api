<?php

require_once '../../../include/session.php';

global $session;

if (isset($_POST['name'])) {
	$name = $_POST['name'];
} 
else 
{
	$name = "";	
}

exit($result = $session->createList($name));