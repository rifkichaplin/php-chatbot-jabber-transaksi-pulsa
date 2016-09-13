<?php

// activate full error reporting
error_reporting(E_ALL & E_STRICT);

include 'connvoulec.php';
#Use XMPPHP_Log::LEVEL_VERBOSE to get more logging for error reports
#If this doesn't work, are you running 64-bit PHP with < 5.2.6?



$conn->autoSubscribe();
$vcard_request = array();

try {
    //$conn->connect();
	if(!$conn->isDisconnected()){
		$conn->connect();
		echo "Ada Koneksi..\n";
	}
	

    while(!$conn->isDisconnected()) {
    	$payloads = $conn->processUntil(array('message', 'presence', 'end_stream', 'session_start', 'vcard'));

    	foreach($payloads as $event) {
    		$pl = $event[1];

		$query_cek_trx="select count(*) jmltrx from (select id_transaction, kind from transaction_multiple_req where request_date between subdate(current_date, 1) and subdate(current_date, 0) and status = 8 union all select id_transaction, kind from transaction_multiple_cron where request_date between subdate(current_date, 1) and subdate(current_date, 0) and status = 8 union all select id_transaction, kind from transaction_single where request_date between subdate(current_date, 1) and subdate(current_date, 0) and status = 8) A";

		echo "CEK PROSES JOB ANTRIAN\n";
		$result_cek = mysqli_query($connect_db, $query_cek_trx);
		while($row_c = mysqli_fetch_assoc($result_cek)) {
			$idjmltrx=$row_c["jmltrx"];
			if($idjmltrx <= "30"){
	
                                $query_getdata = "select * from (select id_transaction id, concat(kind,id_transaction) id_transaction, request_date, msisdn, product_code_trx, kind, 'transaction_multiple_req' nm_table from transaction_multiple_req where request_date between subdate(current_date, 1) and subdate(current_date, 0) and status in (4,10) and msisdn not in (select msisdn from transaction_multiple_req where status = 8) union select id_transaction id, concat(kind,id_transaction) id_transaction, request_date, msisdn, product_code_trx, kind, 'transaction_multiple_cron' nm_table from transaction_multiple_cron where request_date between subdate(current_date, 1) and subdate(current_date, 0) and status in (4,10) and msisdn not in (select msisdn from transaction_multiple_cron where status = 8) union select id_transaction id,concat(kind,id_transaction) id_transaction, request_date, msisdn,product_code_trx, kind, 'transaction_single' nm_table from transaction_single where request_date between subdate(current_date, 1) and subdate(current_date, 0) and status in (4,10) and msisdn not in (select msisdn from transaction_single where status = 8) ) A  where substr(now(),12,2) not in ('00','01','02','03','04','05') order by id asc limit 1 ";
                        			echo $query_getdata."\n";

				$result_q = mysqli_query($connect_db, $query_getdata);
				
				if (mysqli_num_rows($result_q) > 0) {
    					// output data of each row
    					while($row = mysqli_fetch_assoc($result_q)) {
						$id_trx=$row["id"];
                                                $id_transaction=$row["id_transaction"];
                                                $request_date=$row["request_date"];
                                                $msisdn=$row["msisdn"];
                                                $product_code_trx=$row["product_code_trx"];
                                                $kind=$row["kind"];
                                                $nm_table=$row["nm_table"];

						$query_ven="select A.product_code_trx, A.id_product_vendor, B.product_code, A.denom, A.operator, B.purchase_price, B.selling_price, C.id_ym, C.pin,C.dbl_code_trx,B.id_vendor,C.posisi_dbl,C.vendor_name from product_trx A left join product_vendor B on A.id_product_vendor = B.id_product_vendor  left join vendor C on B.id_vendor = C.id_vendor where A.product_code_trx = '".$product_code_trx."' limit 1";
						echo $query_ven."\n";

						$result_v = mysqli_query($connect_db, $query_ven);
						while($row_v = mysqli_fetch_assoc($result_v)) {
							$product_code_trx_v=$row_v["product_code_trx"];
                                                	$id_product_vendor=$row_v["id_product_vendor"];
                                                	$product_code=$row_v["product_code"];
                                                	$denom=$row_v["denom"];
                                                	$id_ym=$row_v["id_ym"];
                                                	$pin=$row_v["pin"];
                                                	$operator=$row_v["operator"];		
							$id_vendor=$row_v["id_vendor"];

							if($id_vendor == "1" || $operator == "AS" || $operator == "Simpati"){
								$conn->message($id_ym,  $body="SV".$denom.".".$msisdn.".".$pin  , $type="chat");	
								$msgout="SV".$denom.".".$msisdn.".".$pin;
								echo "KIRIM dengan Format:SV".$denom.".".$msisdn.".".$pin;
							}else{
								$conn->message($id_ym,  $body=$product_code.".".$msisdn.".".$pin  , $type="chat");	
								$msgout=$product_code.".".$msisdn.".".$pin;
								echo "KIRIM dengan Format:".$product_code.".".$msisdn.".".$pin;
							}
							
							$t=time();
                					$query_ins_ym_out = 'INSERT INTO ym_out (id_ym,format_out,id_transaction,last_update) VALUES ("'.$id_ym.'","'.$msgout.'","'.$id_transaction.'","'.date('Y-m-d H:i:s',$t).'")';
							mysqli_query($connect_db,$query_ins_ym_out);
					
							$query_upd='UPDATE '.$nm_table.' set status = 17 where id_transaction ='.$id_trx;
							mysqli_query($connect_db,$query_upd);
						
						}
						
						
						
    					}
				} else {
    					echo "0 results| Tidak ada Transaksi saat ini..\n";
				 }
			}
		}	
				sleep (5);
 				//$conn->close();
 				//tahan session agar aktif terus
				$conn->message("username@jabber.at",  $body=date('Y-m-d H:i:s',$t)."|RifkiChaplin Check.."  , $type="chat");
				

	
    		switch($event[0]) {
    			case 'message': 
				$t=time();
				$query_ins = 'INSERT INTO ym_in (id_ym,format_in,last_update,status) VALUES ("'.$pl['from'].'","'.$pl['body'].'","'.date('Y-m-d H:i:s',$t).'",0)';

				if(!empty($pl['body']) && $pl['from'] != "username@jabber.at/xmpphp" ){
 					mysqli_query($connect_db,$query_ins);
				}
 
    				print "---------------------------------------------------------------------------------\n";
    				print "Message from: {$pl['from']}\n";
    				if($pl['subject']) print "Subject: {$pl['subject']}\n";
    				print $pl['body'] . "\n";
    				print "---------------------------------------------------------------------------------\n";
    				//$conn->message($pl['from'], $body="Thanks for sending me \"{$pl['body']}\".", $type=$pl['type']);
				//$conn->message($pl['from'],  $body="DEP.1234"  , $type=$pl['type']);
				
					$cmd = explode(' ', $pl['body']);
    				//if($cmd[0] == 'quit') $conn->disconnect();
    				if($cmd[0] == 'break') $conn->send("</end>");
    				if($cmd[0] == 'vcard') {
						if(!($cmd[1])) $cmd[1] = $conn->user . '@' . $conn->server;
						// take a note which user requested which vcard
						$vcard_request[$pl['from']] = $cmd[1];
						// request the vcard
						$conn->getVCard($cmd[1]);
					}
    			break;
    			case 'presence':
    				print "Presence: {$pl['from']} [{$pl['show']}] {$pl['status']}\n";
    			break;
    			case 'session_start':
    			    print "Session Start\n";
			    	$conn->getRoster();
    				$conn->presence($status="I'm Machine made by RifkiChaplin");
				
    			break;
				case 'vcard':
				// check to see who requested this vcard
					$deliver = array_keys($vcard_request, $pl['from']);
					// work through the array to generate a message
					print_r($pl);
					$msg = '';
					foreach($pl as $key => $item) {
						$msg .= "$key: ";
						if(is_array($item)) {
							$msg .= "\n";
							foreach($item as $subkey => $subitem) {
								$msg .= "  $subkey: $subitem\n";
							}
						} else {
							$msg .= "$item\n";
						}
					}
					// deliver the vcard msg to everyone that requested that vcard
					foreach($deliver as $sendjid) {
						// remove the note on requests as we send out the message
						unset($vcard_request[$sendjid]);
    					$conn->message($sendjid, $msg, 'chat');
					}
				break;
    		}
    	    	
	}
    }
} catch(XMPPHP_Exception $e) {
    die($e->getMessage());
}
