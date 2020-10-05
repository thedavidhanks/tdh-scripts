<?php
/* Utilizes AWS API to start and stop EC2 instances.
 * 
 * expects 2 required parameter
 * 
 * Parameters:
 * service is required.
 * service options are stop|start|status
 * 
 * passhash is required for service=stop & service=start
 * passhash shall be a md5 string of a password
 */
include('../common/header.php');  //TEST only - headers allow request from any origin.  
header('Access-Control-Allow-Methods: GET, POST');

require '../vendor/autoload.php';

use Aws\Ec2\Ec2Client;

$ec2Client = new Aws\Ec2\Ec2Client([
    'region' => 'us-west-2',
    'version' => '2016-11-15',
    'profile' => 'default'
]);

$instanceIds = array('i-00f00b86a54f19814');
$instance_cfg = array('InstanceIds' => $instanceIds);

function checkResult($server_response){
    $result = false;
    if($server_response["@metadata"]["statusCode"] === 200){
        $result = true;
    }
    return $result;
}

if(filter_input(INPUT_SERVER, "REQUEST_METHOD") === "GET") {
    switch (filter_input(INPUT_GET,'service' )){
        //start factorio server
        case "start":
            if( null !==(filter_input(INPUT_GET,'passhash')) && !empty(filter_input(INPUT_GET,'passhash'))) {
                if( filter_input(INPUT_GET,'passhash') == md5(getenv('EC2_START_PHRASE'))){
                    $result = $ec2Client->startInstances($instance_cfg);
                    if(checkResult($result)){
                        $response = "starting...";
                    }else{
                        $response = "error starting!";
                    }
                }else{
                    $response = "incorrect password.";
                }
            }else{
                $response = "no password set.";
            }
            break;
        case "stop":
            //stop factorio server
            if( null !==(filter_input(INPUT_GET,'passhash')) && !empty(filter_input(INPUT_GET,'passhash'))) {
                if( filter_input(INPUT_GET,'passhash') == md5(getenv('EC2_START_PHRASE'))){
                    $result = $ec2Client->stopInstances($instance_cfg);
                    if(checkResult($result)){
                        $response = "stopping...";
                    }else{
                        $response = "error stopping!";
                    }
                }else{
                    $response = "incorrect password.";
                }
            }else{
                $response = "no password set.";
            }
            break;
        case "status":
        default:
            
            $result = $ec2Client->describeInstanceStatus(array(
                'IncludeAllInstances'=> true,
                'InstanceIds' => $instanceIds));
            //print("<pre>"+print_r($result)+"</pre>");
            $response = $result["InstanceStatuses"][0]["InstanceState"]["Name"];
            
            break;
    }
}
echo $response;