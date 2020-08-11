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
//CODE 002: SUCCESS. Updated point with last seen.
//CODE 100: Data is missing from post.
//CODE 101: A VALID SERIAL COULD NOT BE FOUND
//CODE 105: No data returned by queuery
//CODE 110: POST data missing
//CODE 120: Could not connect to DB
//CODE 125: Environment variable could not be found.

include('../common/common_functions.php');
include_once('updatePath.php');

function deg_to_rad($deg){
    return $deg*(pi()/180);
}
$tdh_db = "CLEARDB_URL_TDH_SCRIPTS";
//computes the arch length in radians between two lat, long points
function great_circle_arc(float $lat1,float $long1, float $lat2, float $long2){
    //this assumes that all points are in the Nort/west hemispheres
    //REFERENCE - http://edwilliams.org/avform.htm#Dist
    $lat1_rad = deg_to_rad(abs($lat1));
    $lat2_rad = deg_to_rad(abs($lat2));
    $long1_rad = deg_to_rad(abs($long1));
    $long2_rad = deg_to_rad(abs($long2));
    
    $distance_radians=2*asin(sqrt((sin(($lat1_rad-$lat2_rad)/2))**2 
            + cos($lat1_rad)*cos($lat2_rad)*(sin(($long1_rad-$long2_rad)/2))**2));
     
    //echo "radians: ".$distance_radians."\n";
    return $distance_radians;
}

//Distance in meters between point A & B
function meters_a_to_b(float $lat1,float $long1, float $lat2, float $long2){
    $distance_radians=great_circle_arc($lat1, $long1, $lat2, $long2);
    $dist_nm = ((180*60)/pi())*$distance_radians; //distance in nautical miles
    //echo "nm: ".$dist_nm."\n";
    $dist_meters = $dist_nm*1852;  
    
    return $dist_meters;
}

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") === "POST") {
		
	//Collect and filter all the post vars.
	$passcode = filter_input(INPUT_POST,'pass' );
        $lat = filter_input(INPUT_POST,'lat',FILTER_VALIDATE_FLOAT);
        $long = filter_input(INPUT_POST,'long',FILTER_VALIDATE_FLOAT );
        $timestamp = !empty(filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT)) ? filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT) : time();
        $speed = !empty(filter_input(INPUT_POST, 'speed',FILTER_VALIDATE_INT)) ? filter_input(INPUT_POST, 'speed',FILTER_VALIDATE_INT) : NULL;
        $trip_name = !empty(filter_input(INPUT_POST,'tripname' ))? filter_input(INPUT_POST,'tripname' ) : "RoadTrip2020";
        $update_full_path = filter_var(filter_input(INPUT_POST,'updatefullpath' ),FILTER_VALIDATE_BOOLEAN);
        
	if (!empty($passcode) && !empty($lat) && !empty($long) && !empty($timestamp)){
            //A passcode has been sent.  Determine if the sensor is authorized.
            try{
                if($db = connect_db($tdh_db)){
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
                        
                        $last_lat = 0;
                        $last_long = 0;
                        $last_id = 0;
                        //Get the last sensor point
                        try{
                            $query_lastPoint = $db->query("SELECT * from `heroku_bfbb423415a117e`.`gps_readings` WHERE `tripname` = '${trip_name}' ORDER BY `time` DESC LIMIT 1");
                            if($query_lastPoint->rowCount() > 0){
                                foreach ($query_lastPoint as $row){
                                    $last_lat = $row['lat'];
                                    $last_long = $row['long'];
                                    $last_id = $row['id'];
                                }
                            }
                        }catch(PDOException $ex){
                            echo 'Caught exception: ',  $e->getMessage(), "\n";
                        }
                        
                        //calculate the distance between the last point and the current
                        $dist_meters = meters_a_to_b($last_lat,$last_long, $lat, $long);                          
                        
                        //if the distance between the two points > 10 meters add the point
                        if(!is_nan($dist_meters) && ($dist_meters > 10) ){
                            
                            $query_insert = $db->query("INSERT INTO `heroku_bfbb423415a117e`.`gps_readings` (`sensor_id`, `time`, `lat`, `long`, `tripname`) VALUES ('{$sensor_id}', '{$datetime}', '{$lat}', '{$long}', '{$trip_name}');");
                            if($query_insert){
                                echo "CODE 001: SUCCESS<br />";
                                echo "Added values<br />Time: $datetime<br /> Lat: $lat<br /> Long: $long<br /><br /> LastLat: $last_lat <br /> LastLong: $last_long<br />"; 
                                echo "Distance $dist_meters meters <br />";
                                
                                //Update path file.
                                //It is preferable to add to the existing path in order to
                                //minimize map quest transactions. When correcting the route by adding 
                                //intermediate points the post var updatefullpath can be set to true
                                //in order to force full path generation.
                                include_once('updatePath.php');
                                if($update_full_path){
                                    generateFullPath();
                                    //TODO replace with a call to http://tdh-nodescripts.herokuapps.com
                                }
                                else{
                                    //add to existing path
                                    addPointToPath([$lat, $long], 'RoadTrip2020');
                                    //TODO replace with a call to http://tdh-nodescripts.herokuapps.com
                                }
                            }
                        }else{
                            //No movement (distance <=5m, point not added.  
                            $query_update = $db->query("UPDATE `heroku_bfbb423415a117e`.`gps_readings` SET `last_seen`='{$datetime}' WHERE `id`='{$last_id}';");
                            if($query_update){
                                echo "CODE 002: SUCCESS<br />";
                                echo "Updated last seen:<br />ID: $last_id<br />Time: $datetime<br /> Lat: $last_lat<br /> Long: $last_long<br />";
                                echo "$lat, $long (current) was $dist_meters meters of $last_lat, $last_long (last)<br />";
                                echo "Path file not updated<br />";
                            }
                        }
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
        /*List all the data points in time order*/
        case "listall":
            try{
            $db = connect_db($tdh_db);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            $query_sel = $db->query("SELECT * FROM `gps_readings` WHERE `sensor_id` = 4 ORDER BY `time` DESC");
                $numRows = $query_sel->rowCount();
                if($numRows > 0){
                    $gps_list_json = "{ \"points\": [";
                    $i = 0;
                    //If the sensor is identified, then get the id.
                    foreach ($query_sel as $row){
                        $gps_list_json .= "{ \"lat\": {$row['lat']}, \"long\": {$row['long']}, \"date\": \"{$row['time']}\"}";
                        //add a comma for all but last row
                        $gps_list_json .= (++$i === $numRows) ? "" : ", ";
                    }
                    $gps_list_json .= "]}";
                    echo $gps_list_json;
                }else{echo "CODE 105: No data returned by query<br />";}	
            }catch(PDOException $ex) {
                echo "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
                echo $ex->getMessage();
            }
            break;
        case "getPathJSON":
            echo file_get_contents("cricketTraveledPath.json");
            break;
        //TODO convert to google roads for a full path
        //https://developers.google.com/maps/documentation/roads/snap
//        case "updateFullPath":
//            generateFullPath();
//            //TODO replace with a call to http://tdh-nodescripts.herokuapps.com
//            break;
        case "checkDistance":
            $lat1 = filter_input(INPUT_GET,'lat1',FILTER_VALIDATE_FLOAT);
            $long1 = filter_input(INPUT_GET,'long1',FILTER_VALIDATE_FLOAT );
            $lat2 = filter_input(INPUT_GET,'lat2',FILTER_VALIDATE_FLOAT);
            $long2 = filter_input(INPUT_GET,'long2',FILTER_VALIDATE_FLOAT );
            echo meters_a_to_b($lat1,$long1, $lat2, $long2);                          
            break;
        case "latest":
        default:
            try{
            $db = connect_db($tdh_db);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            $query_sel = $db->query("SELECT * FROM `gps_readings` WHERE `sensor_id` = 4 ORDER BY `time` DESC LIMIT 1");
                if($query_sel->rowCount() > 0){
                    $gps_list_json = "";
                    
                    foreach ($query_sel as $row){
                        $gps_list_json .= "{ \"lat\": {$row['lat']}, \"long\": {$row['long']}, \"date\": \"{$row['time']}\"}";
                    }
                    echo $gps_list_json;
                }else{echo "CODE 105: No data returned by queuery<br />";}	
            }catch(PDOException $ex) {
                echo "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
                echo $ex->getMessage();
            }
            break;
    }
}
//If no get or post value is available, the user should see the last location of the cricket.
else{
    try{
        $db = connect_db($tdh_db);
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