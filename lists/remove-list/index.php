<?php

require_once '../../../include/session.php';

global $session;

$listID = $_POST['listid'];

exit($session->removeList($listID));