<?php
require_once '../../../include/session.php';

global $session;
$return = array();

if ($session->logged_in)
{
	$list = $session->getAllListInfo();
	
	foreach ($list['lists'] as $key => $listArray) {
		unset($listArray['products']['debug']);
		
		$list['lists'][$key] = $listArray;
	}
	
	exit(json_encode($list));
}
/*
else
{
	echo json_encode(array("error" => "failed running getUserList"));
}
*/