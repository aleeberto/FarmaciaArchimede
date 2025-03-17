<?php
session_start();

$root = dirname(__DIR__, 2);

require_once($root . "/php/main.php");

$db = Database::getInstance("farmacia_mysql", "root", "root_password", "farmacia_archimede");
$conn = $db->connect();

?>

