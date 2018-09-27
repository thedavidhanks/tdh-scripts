<?php
//ini_set('display_errors', 1); //for testing
$missing=false;

include functions.php;

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
