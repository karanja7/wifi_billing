<?php
// includes/db_config.php
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = 'Likiman12$$';         // change if you have a password
$DB_NAME = 'wifi_billing';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('DB Connect Error: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
