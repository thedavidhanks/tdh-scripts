<?php
//by default list all instagram posts saved to sql db
//
//or receives a $_POST 
//and adds it to an sql database.

include('../common/header.php');
$tdh_db = "CLEARDB_URL-TDH_SCRIPTS";


if (filter_input(INPUT_SERVER, "REQUEST_METHOD") === "POST") {
		
	//Collect and filter all the post vars.
	$passcode = filter_input(INPUT_POST,'pass' );

        
}else{
    
}