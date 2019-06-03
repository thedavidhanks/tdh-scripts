<?php

function connect_db(){
    //Setup Connection with MySQL database
        
    $servername = "sql.thedavidhanks.com"; //"localhost"
    $username= getenv ("SENSOR_DB_USER");
    $password=getenv("SENSOR_DB_PASS");
    $dbname=getenv("SENSOR_DB");
    $connection = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    return $connection;
}

function test_input($data) {
  $cleanData = htmlspecialchars(stripslashes(trim($data)));
  return $cleanData;
}

?>