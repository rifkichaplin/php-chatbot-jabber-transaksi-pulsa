<?php
include 'XMPPHP/XMPP.php';
$conn = new XMPPHP_XMPP('jabber.at', 5222, 'username-jabber', 'password-jabber', 'xmpphp', 'jabber.at', $printlog=true, $loglevel=XMPPHP_Log::LEVEL_INFO);
$user_name = "root";
$password = "";
$database = "db-name";
$host_name = "localhost";
$connect_db=mysqli_connect($host_name, $user_name, $password, $database);

if (!$connect_db) {
    die("Connection failed: " . mysqli_connect_error());
}
 
?>
