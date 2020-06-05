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

$tdh_db = "CLEARDB_URL_TDH_SCRIPTS";
//computes the arch length in radians between two lat, long points
function great_circle_arc(float $lat1,float $long1, float $lat2, float $long2){
    //this assumes that all points are in the Nort/west hemispheres
    $distance_radians=2*asin(sqrt((sin((abs($lat1)-abs($lat2))/2))**2 + cos(abs($lat1))*cos(abs($lat2))*(sin((abs($long1)-abs($long2))/2))**2));
    //echo $distance_radians;
    return $distance_radians;
}

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") === "POST") {
		
	//Collect and filter all the post vars.
	$passcode = filter_input(INPUT_POST,'pass' );
        $lat = filter_input(INPUT_POST,'lat',FILTER_VALIDATE_FLOAT);
        $long = filter_input(INPUT_POST,'long',FILTER_VALIDATE_FLOAT );
        $timestamp = !empty(filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT)) ? filter_input(INPUT_POST, 'timestamp',FILTER_VALIDATE_INT) : time();
        $speed = !empty(filter_input(INPUT_POST, 'speed',FILTER_VALIDATE_INT)) ? filter_input(INPUT_POST, 'speed',FILTER_VALIDATE_INT) : NULL;
        $trip_name = !empty(filter_input(INPUT_POST,'tripname' ))? filter_input(INPUT_POST,'tripname' ) : "RoadTrip2020";

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
                        $query_lastPoint = $db->query("SELECT * from `heroku_bfbb423415a117e`.`gps_readings` ORDER BY `time` DESC LIMIT 1");
                        if($query_lastPoint->rowCount() > 0){
                            foreach ($query_lastPoint as $row){
                                $last_lat = $row['lat'];
                                $last_long = $row['long'];
                                $last_id = $row['id'];
                            }
                        }
                        
                        //calculate the distance between the last point and the current
                        $dist_nm = ((180*60)/pi())*great_circle_arc($last_lat,$last_long, $lat, $long);
                        $dist_meters = $dist_nm*1852;    
                            //   REFERENCE - http://edwilliams.org/avform.htm#Dist
                            //angle_radians=(pi/180)*angle_degrees
                            //angle_degrees=(180/pi)*angle_radians 
                        
                            //distance_radians=(pi/(180*60))*distance_nm
                            //distance_nm=((180*60)/pi)*distance_radians 
                        
                            //GPS tolerance +/- 2.5 meters
                        
                        //if the distance between the two points > 5 meters add the point
                        if(!is_nan($dist_meters) && ($dist_meters > 5) ){
                            
                            $query_insert = $db->query("INSERT INTO `heroku_bfbb423415a117e`.`gps_readings` (`sensor_id`, `time`, `lat`, `long`, `tripname`) VALUES ('{$sensor_id}', '{$datetime}', '{$lat}', '{$long}', '{$trip_name}');");
                            if($query_insert){
                                echo "CODE 001: SUCCESS<br />";
                                echo "Added values<br />Time: $datetime<br /> Lat: $lat<br /> Long: $long<br /><br /> LastLat: $last_lat <br /> LastLong: $last_long<br />"; 
                                echo "Distance $dist_meters meters <br />";
                            }
                        }else{
                            $query_update = $db->query("UPDATE `heroku_bfbb423415a117e`.`gps_readings` SET `last_seen`='{$datetime}' WHERE `id`='{$last_id}';");
                            if($query_update){
                                echo "CODE 002: SUCCESS<br />";
                                echo "Updated last seen:<br />ID: $last_id<br />Time: $datetime<br /> Lat: $last_lat<br /> Long: $last_long<br />";
                                echo "$lat, $long (current) was $dist_meters meters of $last_lat, $last_long (last)";
                            }
                            //No movement (distance <=5m, point not added.  
                            //Update timestamp?  New column, last seen?
                        }
                    }else{echo "CODE 101: A VALID SERIAL COULD NOT BE FOUND";}
                }else{
                    echo "CODE 125: Environment variable could not be found.";
                }
            }catch(PDOException $ex) {
                echo "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
                echo $ex->getMessage();
            }
            //build a new path.
            include_once('updatePath.php');
            generateFullPath();
	}
	else{	
            echo "CODE 100: data is missing from post.";
	}
        
/*List all the data points in time order*/
}else if(filter_input(INPUT_SERVER, "REQUEST_METHOD") === "GET") {
    //Collect and filter all the post vars.
    switch (filter_input(INPUT_GET,'request' )){
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
                }else{echo "CODE 105: No data returned by queuery<br />";}	
            }catch(PDOException $ex) {
                echo "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
                echo $ex->getMessage();
            }
            break;
        case "getPathJSON":
            echo file_get_contents("cricketTraveledPath.json");
            break;
//        case "test":
//            include_once('updatePath.php');
//            addPointToPath([31.7922398, -106.2160130], 'RoadTrip2020');
//            break;
        case "updateFullPath":
            include_once('updatePath.php');
            generateFullPath();
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