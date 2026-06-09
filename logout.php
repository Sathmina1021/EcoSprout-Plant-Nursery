<?php
require_once 'includes/config.php';
session_destroy();
flashMessage('info', 'You have been logged out successfully.');
redirect('login.php');
?>
