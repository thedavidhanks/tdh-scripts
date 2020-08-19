<?php

//connect_db will connect to a mysql database and return the connection
//
//ev_url should be a string which matches an environment variable. 
//The string should be a url containing the user password & host
function connect_db($ev_url){
    if(getenv($ev_url)){
        $url = parse_url(getenv($ev_url));

        $servername = $url["host"];
        $username = $url["user"];
        $password = $url["pass"];
        $dbname = substr($url["path"], 1);

        $connection = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        return $connection;
    }else{
        return false;
    }
    
}

function test_input($data) {
  $cleanData = htmlspecialchars(stripslashes(trim($data)));
  return $cleanData;
}

function IsNullOrEmptyString($question){
    return (!isset($question) || trim($question)==='');
}
?>