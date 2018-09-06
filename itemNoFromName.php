<?php
ini_set('display_errors', 1); //for testing
$missing=false;

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

$itemName = (!IsNullOrEmptyString(filter_input(INPUT_GET, "name", FILTER_SANITIZE_STRING))?filter_input(INPUT_GET, "name"):"");


$conn = connect_db();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT * FROM mydb.items WHERE name = {$conn->quote($itemName)}";
try {
    $SelRow = $conn->prepare($query);   
    $SelRow->execute();
    $count = $SelRow->rowCount();
    if($count>0){
            while( $row = $SelRow->fetch(PDO::FETCH_ASSOC)){
                    $itemID=$row['id'];
            }
    }
    else{
        $missing=true;
    }
}
catch(PDOException $pw) {
    echo "Error: " . $pw->getMessage();
    die();
}
$message = $missing?"0":$itemID;
$conn = null;

echo $message;
