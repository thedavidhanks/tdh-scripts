<?php

function IsNullOrEmptyString($question){
    return (!isset($question) || trim($question)==='');
}

function connect_db(){
    //Setup Connection with MySQL database
    
    $servername = getenv('dbserver');
    $username= getenv('dbuser');
    $password=getenv('dbpass');
    $dbname=getenv('dbname');
    $connection = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    return $connection;
}
?>