<?php
//Returns all the paths available in an encoded format.

include('../common/common_functions.php');

$tdh_db = "CLEARDB_URL_TDH_SCRIPTS";

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") === "POST") {
		
	//Collect and filter all the post vars.
	$passcode = filter_input(INPUT_POST,'pass' );  //for future use when new paths are created.
        $action = filter_input(INPUT_POST,'action'); //action defines what is to be done { 'add_path','list_all' }
        
	if (!empty($action) && $action == 'list_all'){
            //send all the paths available
            try{
                if($db = connect_db($tdh_db)){
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

                    $query_sel = $db->query("SELECT * FROM cricket_paths");
                    $result_total = $query_sel->rowCount(); 
                    if($result_total > 0){
                        $json_string = '{';
                        foreach ($query_sel as $key => $row){
                            //echo $result_total.":".$key;
                            $json_string .= "{id: \"{$row['id']}\", path: \"{$row['path']}\"}";                            
                            $json_string .= $result_total != ($key+1) ? "," : "";  //add comma for all non-last elements.
                        }
                        $json_string .= "}";
                        echo $json_string;
                        
                    }else{echo "CODE 101: A VALID SERIAL COULD NOT BE FOUND";}
                }else{
                    echo "CODE 125: Environment variable could not be found.";
                }
            }catch(PDOException $ex) {
                echo "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
                echo $ex->getMessage();
            }
	}
	else{	
            echo "CODE 100: data is missing from post.";
	}

}else if(filter_input(INPUT_SERVER, "REQUEST_METHOD") === "GET") {
    
    switch (filter_input(INPUT_GET,'request' )){
        case "listall":
            
            break;
        case "latest":
        default:
            echo "Nothing requested";
            break;
    }
}
?>