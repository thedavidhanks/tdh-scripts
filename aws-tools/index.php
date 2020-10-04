<?php
/* Utilizes AWS API to start and stop EC2 instances.
 * 
 */

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
        case "start":
            //start factorio server
            $result = $ec2Client->startInstances($instance_cfg);
            if(checkResult($result)){
                $response = "starting...";
            }else{
                $response = "error starting!";
            }
            break;
        case "stop":
            //stop factorio server
            $result = $ec2Client->stopInstances($instance_cfg);
            if(checkResult($result)){
                $response = "stopping...";
            }else{
                $response = "error stopping!";
            }
            break;
        case "status":
        default:
            
            $result = $ec2Client->describeInstanceStatus(array(
                'IncludeAllInstances'=> true,
                'InstanceIds' => $instanceIds));
            $response = $result["InstanceStatuses"][0]["InstanceState"]["Name"];
            
            break;
    }
}
//print("<pre>".print_r($result,true)."</pre>");
//var_dump($result);
//var_dump($apple->name);
//print("<pre>".print_r($result,true)."</pre>");
//echo "<hr />";
//print("<pre>".print_r($apple,true)."</pre>");
//echo $apple->name;
//echo "<hr />";
//print("<pre>".print_r($result["InstanceStatuses"][0]["InstanceState"]["Name"],true)."</pre>");
echo $response;