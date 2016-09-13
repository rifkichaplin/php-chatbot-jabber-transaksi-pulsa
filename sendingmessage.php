<?php

// activate full error reporting
//error_reporting(E_ALL & E_STRICT);

//include 'XMPPHP/XMPP.php';
include 'connvoulec.php';

#Use XMPPHP_Log::LEVEL_VERBOSE to get more logging for error reports
#If this doesn't work, are you running 64-bit PHP with < 5.2.6?

try {
    $conn->connect();
    $conn->processUntil('session_start');
    $conn->presence();
	//$conn->processTime(3);


	$t=time();
        $query = 'INSERT INTO jabber_out (id_jabber,format_out,id_transaction,last_update) VALUES ("'.$argv[1].'","'.$argv[2].'","'.$argv[3].'","'.date('Y-m-d H:i:s',$t).'")';
                                       
                                        mysqli_query($connect_db, $query);

                                        //if(!  mysql_query($query,$connect_db) ) {
                                        //      die('Gagal tambah data: ' . mysql_error());
                                        //}
	
	$conn->message("$argv[1]","$argv[2]");
	echo "OK|".$query;
    $conn->disconnect();

} catch(XMPPHP_Exception $e) {
    die($e->getMessage());
}
