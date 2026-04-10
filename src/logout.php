<?php
require_once 'services/sessions.php';
$session = new SessionManager();
$session->logoutUser();
header('Location: login.php');
exit;
