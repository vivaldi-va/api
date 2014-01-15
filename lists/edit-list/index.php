<?php
require_once '../../../include/session.php';

global $session;

$listID = $_POST['listid'];

$newName = $_POST['name'];

exit($session->editList($listID, $newName));