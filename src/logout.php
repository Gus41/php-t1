<?php
require_once 'services/SessionsService.php';
$session = new SessionManager();
$session->logoutUser();
header('Location: login.php');
exit;
