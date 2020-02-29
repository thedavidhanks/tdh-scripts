<?php
//TEST only - headers allow request from any origin.  needs to change to otc-react-app.herokuapp.com or similar.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

include('../functions.php');
//if $_GET[option]=="add" and the user is allowed then
//  do read all the post variables and add the new project
//else just show all the projects
try{
    $db = connect_db();
    $query = <<<SQL
        SELECT 
            *
        FROM
            heroku_bfbb423415a117e.insta_posts
        ORDER BY 
            date DESC
SQL;
    $statement = $db->prepare($query);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    $json = json_encode($results);
    echo $json;
} catch (Exception $ex) {
    print "Error!: ". $ex->getMessage()."<br />";
    die();
}
