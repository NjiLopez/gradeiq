<?php
require_once 'includes/functions.php';
logoutUser();
header('Location: login.php');
exit;
