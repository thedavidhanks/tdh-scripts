<?php
/*TempSensorChart.php
 * 
 * TempSensorChart.php will pull query the database for the temperature and humidity datapoints
 * then add them to the Chart.js object as scatter objects.
 */

include('../common/common_functions.php');

 try{
 	$conn_sensordb = connect_db(); //TODO wrong db. needs argument.
	$conn_sensordb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$stmt=$conn_sensordb->prepare("SELECT reading_id, DATE_FORMAT(reading_time,'%m/%d/%Y %H:%i') as date, tempF, rel_humidity, humidity FROM readings WHERE (reading_loc = 'test' OR sensor_id = 1) ORDER BY reading_time");
	$stmt->execute();
	
	/* Exercise PDOStatement::fetch styles */
	$result = $stmt->fetchall(PDO::FETCH_ASSOC);
	//print_r($result);
	$temp_graph_data = "";
	$humid_graph_data = "";
	echo "<p>";
	foreach ($result as $key => $value) {
		//echo "humidity = {$value['rel_humidity']} temperature = {$value['tempF']} @ {$value['reading_time']} <br />";
		//$mdy_date = $value['reading_time']
		$temp_graph_data .= "{ x: '{$value['date']}', y: {$value['tempF']} },";
		//echo  "{ x: '{$value['reading_time']}', y: {$value['tempF']} },<br />";
		//{x: '03/01/2017 09:35', y: 90.21},
		$humid_graph_data .= "{ x: '{$value['date']}', y: {$value['rel_humidity']} },";
	}
	echo "</p>";
 }
 catch (PDOException $e) {
 	echo "Error: ".$e->getMessage();
	

 }
 $conn_sensordb = null;
 
 ?>

		<script src="Include/moment-with-locales.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.bundle.js"></script>
		<script src="include/utils.js"></script>

			<div class="w3-container">
				<canvas id="myChart"></canvas>
			</div>
		
		<script>
		var timeFormat = 'MM/DD/YYYY HH:mm';
		//function newDateString(days) {
		//	return moment().add(days, 'd').format(timeFormat);
		//}
		var color = Chart.helpers.color;
		var temp2 = {type: 'line',
					label: 'Temp - Attic',
					backgroundColor: color(window.chartColors.red).alpha(0.5).rgbString(),
					borderColor: window.chartColors.red,
					fill: false,
					data: [
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor()
					]};
		var humid2 = {type: 'line',
					label: 'Humidity - Attic',
					backgroundColor: color(window.chartColors.blue).alpha(0.5).rgbString(),
					borderColor: window.chartColors.blue,
					fill: false,
					data: [
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor(), 
						randomScalingFactor()
					]};
		var scatter = {
						type: 'line',
						fill: false,
						borderColor: window.chartColors.orange,
			            label: 'Scatter Dataset',
			            data: [{
			                x: '03/01/2017 09:35',
			                y: randomScalingFactor()
			            }, {
			                x: '03/01/2017 10:35',
			                y: randomScalingFactor()
			            }, {
			                x: '03/03/2017 14:35',
			                y: randomScalingFactor()
			            }, {
			            	x: '03/04/2017 14:01',
			            	y: randomScalingFactor()
			            }]
			        };
		var realTemp = {
						type: 'line',
						fill: false,
						borderColor: window.chartColors.orange,
			            label: 'Room Temperature (F)',
			            data: [<?php echo $temp_graph_data; ?>]
			        };
		var realHumid = {
						type: 'line',
						fill: false,
						borderColor: window.chartColors.blue,
			            label: 'Room Humidity (%)',
			            data: [<?php echo $humid_graph_data; ?>]
			        };
		var config = {
			type: 'bar',
			data: {
				//labels: [
				//	newDateString(0), 
				//	newDateString(1), 
				//	newDateString(2), 
				//	newDateString(3), 
				//	newDateString(4), 
				//	newDateString(5), 
				//	newDateString(6)
				//],
				datasets: [realTemp, realHumid]  //Add more datasets here like this=> datasets: [temp2, humid2, scatter, realTemp]
			},
			options: {
                title: {
                	display: true,
                    text:"MAD house temperature & humidity"
                },
                legend: {
		            display: true,
		            position: 'right'
		        },
				scales: {
					xAxes: [{
						type: "time",
						display: true,
						time: {
							format: timeFormat,
							// round: 'day'
						}
					}],
				},
			}
		};
		
		var ctx = document.getElementById("myChart").getContext('2d');
		var myChart = new Chart(ctx, config);
		</script>
			