<?php

function IsNullOrEmptyString($question){
    return (!isset($question) || trim($question)==='');
}

function connect_db(){
    //Setup Connection with MySQL database
    
    $url = parse_url(getenv("CLEARDB_URL_TDH_SCRIPTS"));
    $servername = $url["host"];
    $username = $url["user"];
    $password = $url["pass"];
    $dbname = substr($url["path"], 1);
    $connection = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $connection->prepare("USE $dbname;")->execute();
    return $connection;
}
?>