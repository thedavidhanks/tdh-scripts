<?php
//Files receives a post request.  If the data is provided it will be entered to the database.
//The script will respond with success or failure codes.
//
//The following should be supplied via $_POST
//"tempF" => temperature in farienheit
//"pass" => the serial # of the sensor
//"humidity" => the humidity reading
//"rel_humidity" => the relative humidity (calculated by sensor)
//"time" => time formatted as unix time stamp from when the reading was taken.
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
        $tempF = filter_input(INPUT_POST,'tempF',FILTER_VALIDATE_FLOAT);
        $humidity = filter_input(INPUT_POST,'humidity',FILTER_VALIDATE_FLOAT );
        $rel_humidity = filter_input(INPUT_POST,'rel_humidity',FILTER_VALIDATE_FLOAT );
        
        //If the time stamp is not given by the sensor, use the current timestamp.
        $timestamp = !empty(filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT)) ? filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT) : time();
        
//Check that all post data is avaliable
//        foreach($_POST as $key => $value)
//        {
//            echo($key." = ".$value."<br />");
//        }
//        echo("pass = ".$passcode."\n");
//        echo("tempF = ".$tempF."\n");
//        echo("humidity = ".$humidity."\n");
//        echo("rel_humidity = ".$rel_humidity."\n");
	if (!empty($passcode) && !empty($tempF) && !empty($humidity) && !empty($rel_humidity)){
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

                    //UPDATE - change location to sensor_id
                    $query_insert = $db->query("INSERT INTO `readings` (sensor_id, reading_time, tempF, humidity, rel_humidity) VALUES ({$sensor_id}, '{$datetime}', {$tempF}, {$humidity}, {$rel_humidity})");
                    if($query_insert){
                        //echo "<div class=\"w3-panel w3-green\"><h3>Added values</h3><p>Time: $datetime<br /> Temperature: $tempF F<br /> Humitidy: $humidity %<br /> Relative Humitidy: $rel_humidity %</p></div>";                               
                        echo "CODE 001: SUCCESS<br />";
                        echo "Added values - <br />Time: $datetime<br /> Temperature: $tempF F<br /> Humitidy: $humidity %<br /> Relative Humitidy: $rel_humidity %";                               
                    }
                }else{echo "CODE 101: A VALID SERIAL COULD NOT BE FOUND";}	
            }catch(PDOException $ex) {
                echo "CODE 120: Could not connect to mySQL DB"; //user friendly message
                echo $ex->getMessage();

                    //or a better way to log errors
                    //some_logging_function($ex->getMessage());
            }
	}
	//End If authorized
	else{	
		echo "CODE 100: data is missing from post.";
	}
}
else{
    echo "Welcome, please POST data to this page to add a new record.";
}

?>