<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pal Testing Service</title>
</head>
<body>
    <h1>Pal Testing Service</h1>

    <?php

    // task 3
        // initializing variables for url, username, and password
        $credfile = file('credentials/userpass.txt');
        $credarr = [];

        foreach ($credfile as $line) {
            $credarr[] =  trim($line);
        }

        $url = "10.101.0.1:10101/?token=renew";

        $uname = $credarr[0];
        $pw = $credarr[1];

        $ch = curl_init($url);

        // Using cURL for GET request to get the token value from the server
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $uname . ':' . $pw,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        ]);

        $response = curl_exec($ch); // Gets text/plain xml document data
        
        // This if block is for error handling
        if ($response === false) { 
            fwrite(STDERR, 'cURL Error: ' . curl_error($ch) . PHP_EOL);
            exit(1);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Gets the http code for error handling
        
        // This if block is for error handling
        if ($httpCode < 200 || $httpCode >= 300) {
            fwrite(STDERR, "HTTP Status: {$httpCode}<br>" . PHP_EOL);
            fwrite(STDERR, "Response Body: {$response}" . PHP_EOL);
            exit(1);
        }

        // echo "HTTP Status: {$httpCode}" . PHP_EOL; ----- Going to keep it, just in case needed for debugging
        echo "Response Body:<br> {$response}" . PHP_EOL;

        echo "<br>"; // a break in html for readability




        // ------------- task 4

        // initializing variables for xml file, token value and task 4 url.
        $xml = simplexml_load_string($response); // used simplexml to parse the xml document and get the token value
        $tokenValue = $xml->Value;
        $urlTask = "http://10.101.0.1:10101/task";


        // Using regex to get the request time0
        if (preg_match('/\d{2}:\d{2}:\d{2}/', $xml->Created, $m)) {
            $requestTime = $m[0];
        } else {
            $requestTime = Date("H:i:s");
        }

        
        $ch = curl_init($urlTask);

        // Using cURL for POST request to get the machine readings from the server
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "command=getvalue,request={$requestTime}",
            CURLOPT_HTTPHEADER => [
                'Content-type: text/plain; charset=utf-8',
                'Authorization: Bearer ' . $tokenValue, 
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) { 
            fwrite(STDERR, 'cURL Error: ' . curl_error($ch) . PHP_EOL);
            exit(1);
        }   
        
    
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        // echo "HTTP Status: {$httpCode}" . PHP_EOL;

        $machineReadings[] = json_decode($response, true)['data'];

        $mainNode = $machineReadings[0]['node'];

        while ( count($machineReadings) < 100 ) {
            $ch = curl_init($urlTask);

            // Using cURL for POST request to get the machine readings from the server
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => "command=getvalue,request={$requestTime}",
                CURLOPT_HTTPHEADER => [
                    'Content-type: text/plain; charset=utf-8',
                    'Authorization: Bearer ' . $tokenValue, 
                ],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
            ]);

            $response = curl_exec($ch);

            $node = json_decode($response, true)['data']['node'];
            $type = json_decode($response, true)['data']['type'];
            $temp = json_decode($response, true)['data']['temp'];

            if ($mainNode === $node && $type !== "unknown" && $temp >= 0 && $temp < 199) {
                $machineReadings[] = json_decode($response, true)['data'];
            }

        }


        $file = fopen("machinecsvfile.csv", "w") or die("Unable to open file!");

        fputcsv($file, ['Time', 'Node', 'Type', 'Temperature°C', '∆']);

        $prevTemp = null;

        foreach($machineReadings as $mr) {
            $celsius = round(((int) $mr['temp'] - 32) * 5 / 9, 2);
            
            if ($prevTemp === null) {
                $prevTemp = $mr['temp'];
            } else {
                $delta = $mr['temp'] - $prevTemp;
                $prevTemp = $mr['temp'];
            }
            
            fputcsv($file, [
                $requestTime,
                $mr['node'],
                $mr['type'],
                $celsius,
                $delta ?? null
            ]);

        }

        fclose($file);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "command=end",
            CURLOPT_HTTPHEADER => [
                'Content-type: text/plain; charset=utf-8',
                'Authorization: Bearer ' . $tokenValue, 
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15, 
        ]);

        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Gets the http code for error handling
        
        // This if block is for error handling
        if ($httpCode < 200 || $httpCode >= 300) {
            fwrite(STDERR, "HTTP Status: {$httpCode}<br>" . PHP_EOL);
            fwrite(STDERR, "Response Body: {$response}" . PHP_EOL);
            exit(1);
        }

        echo "HTTP {$httpCode}";


        // ----------- task 5
        // Task 5.1
        // So I got no idea if this works since it returns code 204 and I can't or rather don't know how to check the result.

        $urlCSV = "http://10.101.0.1:10101/csv";
        $destinationPath = "server_csv.csv";

        $fileCSV = fopen($destinationPath, "w");


        $ch = curl_init($urlCSV);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $tokenValue,
            ],
            CURLOPT_FILE => $fileCSV,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        fclose($fileCSV);

        if ($httpCode == 200) {
            echo "<br>File downloaded successfully";
        } else if ($httpCode == 204) {
            echo "<br>Connection successful, but no content detected, Code {$httpCode}";
        } else {
            echo "<br>Error downloading file, Code {$httpCode}";
        }

        // Task 5.2
        // First going to get the data for the chart
        $machineCSV = file('machinecsvfile.csv');
        $headers = str_getcsv(array_shift($machineCSV));
        $machinedata = [];

        // $counter = 0;

        foreach ($machineCSV as $machineLines) {
            // echo $counter . " : " . $machineLines . "<br>";
            // $counter++;

            $data = array_combine($headers, str_getcsv($machineLines));
            $machinedata[] = $data; 
        }


        // echo "<br><pre>";
        // print_r($machinedata);

        $dataPoints = [];

        foreach($machinedata as $id => $data) {
            // echo "<br>" . $id + 1 . " " . $data['Temperature°C'];
            $dataPoints[] = ['y' => $data['Temperature°C'], 'label' => $id + 1];
        }

        // echo "<br><pre>";
        // print_r($dataPoints);


    ?>

<!-- This Column chart graph is taken from https://canvasjs.com/php-charts/column-chart/  -->
<script>
window.onload = function() {
 
var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: true,
	theme: "light2",
	title:{
		text: "Temperature Graph (°C)"
	},
	axisY: {
		title: "Temperatures (in celsius)"
	},
	data: [{
		type: "column",
		yValueFormatString: "#,##0.## °C",
		dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
	}]
});
chart.render();
 
}
</script>

<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>
</html>
