<?php
// conexao.php
$servername = "localhost";
$username   = "root";     
$password   = "";         
$dbname     = "portal";   

$conn = new mysqli($servername, $username, $password, $dbname);

$result = $conn->query("DESCRIBE menu_links");
while($row = $result->fetch_assoc()) {
    print_r($row);
}
