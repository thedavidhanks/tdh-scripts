<?php
//Files receives a post request.  If the data is provided it will be entered to the database.
//The script will respond with success or failure codes.
//
//The following should be supplied via $_POST
//"lat" => 
//"long" => 
//"passcode" => 
//"timestamp" => time formatted as unix time stamp from when the reading was taken.
//
//
//Returned codes
//CODE 001: SUCCESS. Added data to mySQL database
//CODE 100: Data is missing from post.
//CODE 101: A VALID SERIAL COULD NOT BE FOUND
//CODE 110: POST data missing
//CODE 120: Could not connect to DB

include('../common_functions.php');

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") === "POST") {
		
	//Collect and filter all the post vars.
	$passcode = filter_input(INPUT_POST,'pass' );
        $lat = filter_input(INPUT_POST,'lat',FILTER_VALIDATE_FLOAT);
        $long = filter_input(INPUT_POST,'long',FILTER_VALIDATE_FLOAT );
        $timestamp = !empty(filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT)) ? filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT) : time();
        

	if (!empty($passcode) && !empty($lat) && !empty($long) && !empty($timestamp)){
            //A passcode has been sent.  Determine if the sensor is authorized.
            try{
                $db = connect_db();
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

                //find a sensor with the same passcode
                $query_sel = $db->query("SELECT * FROM sensor_list WHERE sensor_passcode = '{$passcode}'");
                if($query_sel->rowCount() > 0){

                    //If the sensor is identified, then get the id.
                    foreach ($query_sel as $row){
                            $sensor_id = $row['id_sensorlist'];
                            $sensor_location = $row['sensor_location'];
                    }
                    //$timestamp converted to datetime for mysql
                    date_default_timezone_set('America/Chicago');

                    $datetime = date('Y-m-d H:i:s', $timestamp); //"2017-02-28 14:00:02";
                    
                    $query_insert = $db->query("INSERT INTO `instruments`.`gps_readings` (`sensor_id`, `time`, `lat`, `long`) VALUES ('{$sensor_id}', '{$datetime}', '{$lat}', '{$long}');");
                    if($query_insert){
                        echo "CODE 001: SUCCESS<br />";
                        echo "Added values - <br />Time: $datetime<br /> Lat: $lat<br /> Long: $long";                               
                    }
                }else{echo "CODE 101: A VALID SERIAL COULD NOT BE FOUND";}	
            }catch(PDOException $ex) {
                echo "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
                echo $ex->getMessage();

                    //or a better way to log errors
                    //some_logging_function($ex->getMessage());
            }
	}
	else{	
		echo "CODE 100: data is missing from post.";
	}
}

//If no post value is available, the user should see the last location of the cricket.
else{
    try{
        $db = connect_db();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        //find the last location
        $query_sel = $db->query("SELECT * FROM instruments.gps_readings where sensor_id = 4 ORDER BY time DESC Limit 1");
        if($query_sel->rowCount() > 0){
            //If the sensor is identified, then get the id.
            foreach ($query_sel as $row){
                $lat = $row['lat'];
                $long = $row['long'];
                $time = $row['time'];
            }
        }  
    }catch(PDOException $ex) {
        echo "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
        echo $ex->getMessage();

            //or a better way to log errors
            //some_logging_function($ex->getMessage());
    }
    
    echo "The cricket was located at Latitude $lat Longditute: $long on $time";
    
}

?>