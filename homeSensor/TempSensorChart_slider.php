<?php
/*TempSensorChart.php
 * 
 * TempSensorChart.php will pull query the database for the temperature and humidity datapoints
 * then add them to the Chart.js object as scatter objects.
 */

 try{
 	$conn_sensordb = connect_db();
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
		<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.bundle.js"></script>-->
		<script src="include/utils.js"></script>
			<div class="w3-container w3-padding-48">
				<canvas id="myChart"></canvas>
			</div>
		
		<script>
		var timeFormat = 'MM/DD/YYYY HH:mm';
		//function newDateString(days) {
		//	return moment().add(days, 'd').format(timeFormat);
		//}
		var color = Chart.helpers.color;
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
		var iniData = {
			labels: [
			"Mar 3, 2017", "Mar 4, 2017", "Mar 5, 2017", "Mar 6, 2017", "Mar 7, 2017"],
			datasets: [realTemp, realHumid]  //Add more datasets here like this=> datasets: [temp2, humid2, scatter, realTemp]
		};
		var options = {
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
			};
		
			
		var ctx = document.getElementById("myChart").getContext('2d');
		//var myChart = new Chart(ctx, config);
		
		var rs = new RangeSliderChart({

			chartData: iniData,
			chartOpts: options,
			chartType: 'Line',
			chartCTX: ctx,

			class: 'my-chart-ranger',

			initial: [1, 8]
		})
		</script>
			