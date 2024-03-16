<?php
$db = new mysqli($mysqlHost, $username, $password, $dbname);
if ($db->connect_error) {
    echo "Cannot connect to Mysql";
    die("Connection failed: " . $db->connect_error);
}