<?php

require_once '../../include/session.php';

global $session;

//$created = $session->getUserCreatedDate();

if ($session->logged_in)
{
	echo json_encode(array("id" => $session->userid, "email" => $session->email, "name" => $session->userName, "created" => $session->created ));
}
else
{
	echo json_encode(array("error" => "access denied: not logged in"));
}
