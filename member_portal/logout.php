<?php
session_start();
unset($_SESSION['member_id']);
unset($_SESSION['member_name']);
unset($_SESSION['member_number']);
session_destroy();
header('Location: login.php');
exit();
?>