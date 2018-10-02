<?php
/*  trackedItems.php
 * 
 * returns json of items tracked in the AH, adds new items, removes items
 *  [{ id: 1, name: the hammer},{id: 2, name: special coin},{id: 435, name: flower}]
 * 
 * 
 * trackedItems.php  returns a json of item ids and names which are tracked
 * trackedItems.php?option=add&itemNo=123456
 * trackedItems.php?option=delete&itemNo=123456
 * trackedItems.php
 */

ini_set('display_errors', 1); //for testing

include 'functions.php';

$itemNo = (!IsNullOrEmptyString(filter_input(INPUT_GET, "itemNo", FILTER_SANITIZE_STRING))?filter_input(INPUT_GET, "itemNo"):"");
$option = (!IsNullOrEmptyString(filter_input(INPUT_GET, "option", FILTER_SANITIZE_STRING))?filter_input(INPUT_GET, "option"):"");

$conn = connect_db();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(!IsNullOrEmptyString($option)){
    //add or remove numbers
    //UPDATE `mydb`.`items` SET `trackPrice`='True' WHERE `id`='158932';
    $trackPrice = $option = delete? 'False' : 'True';
    $actionED = $option = delete? 'deleted' : 'added';
    $query = "UPDATE `mydb`.`items` SET `trackPrice`='$trackPrice' WHERE `id`='$itemNo'";
    try {
        $updateRow = $conn->prepare($query);
        $updateRow->execute();
        $updateCount = $updateRow->rowCount();
        if($updateCount>0){
            //successfully added
            $list = "$itemNo was $actionED to tracked Items";
        }
    } catch (PDOException $ex) {
        echo "Error: " . $ex->getMessage();
        die();
    }
}else{
    //return a json of all items being tracked
    
    $query = "SELECT id, name FROM mydb.items WHERE trackPrice = True";
    try {
        $SelRow = $conn->prepare($query);   
        $SelRow->execute();
        $count = $SelRow->rowCount();
        if($count>0){
            //if we've got some results, create a json file with the results    
            $list="[";
            $rowCount = 0;
            while( $row = $SelRow->fetch(PDO::FETCH_ASSOC)){
                    $rowCount++;
                    $list.=json_encode($row);
                    $list.=($rowCount<$count)?",":"";
            }
            $list.="]";
        }
        else{
            $missing=true;
        }
    }
    catch(PDOException $pw) {
        echo "Error: " . $pw->getMessage();
        die();
    }
    $message = $missing?"0":$list;
    $conn = null;

    echo $message;

}