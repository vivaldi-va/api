<?php
require '../../include/session.php';

global $session;

if ($session->logged_in)
{
	$session->logout();
	echo json_encode(array("logged_in" => $session->logged_in));
}
else
	echo json_encode(array("logged_in" => false, "error" => "you're not logged in anyway"));