<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alumni_network";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";

require_once __DIR__ . '/../include/admin_helpers.php';
ensure_admin_schema($conn);
?>
