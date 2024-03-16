<?php
$db = new mysqli($mysqlHost, $username, $password, $dbname);
if ($db->connect_error) {
    echo "Error";
    die("Connection failed: " . $db->connect_error);
}
