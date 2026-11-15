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

    // ------------------- Task 3
        echo "Task 3<br>";
        // initializing variables for url, username, and password
        // Getting credentials (username and password) from given txt file, so the values arent hardcoded
        $credfile = file('credentials/userpass.txt');
        $credarr = [];

        // Gets the url from file, so no hardcoding
        $urlfile = file('credentials/ip.txt');

        foreach ($credfile as $line) {
            $credarr[] =  trim($line);
        }

        // Gets the url from file and combines with text, to get correct url needed for task or tasks further
        $url =  $urlfile[0] . "/?token=renew";

        // Gets the credentials (username and password) from array $credarr
        $uname = $credarr[0];
        $pw = $credarr[1];

        $ch = curl_init($url); // initializes a new session with $url

        // Using cURL for GET request to get the token value from the server
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, // if there is a response, returns it into a string
            CURLOPT_USERPWD => $uname . ':' . $pw, // Authentication used for connecting to the service
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC, // Specifies what Authentication was used
        ]);

        $response = curl_exec($ch); // Gets text/plain xml document data
        
        // This if block is for error handling, checks if the response is empty and if it is outputs curl_error
        if ($response === false) { 
            echo "<br>cURL Error: " . curl_error($ch) . "<br>";
            exit(1);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Gets the http code for error handling
        
        // This if block is for error handling, checks if the http code isn't lower than 200 or higher than 300, if it is outputs curl_erorr
        if ($httpCode < 200 || $httpCode >= 300) {
            echo "HTTP Status: {$httpCode}<br>" . curl_error($ch);
            echo "Response Body: {$response}<br>" . curl_error($ch);
            exit(1);
        }

        // echo "HTTP Status: {$httpCode}" . curl_error($ch); ----- Going to keep it, just in case needed for debugging
        echo "Response Body:<br> {$response}" . "<br>";


        // ------------- Task 4
        // ---- Task 4.1
        
        echo "<br>Task 4.1<br>";


        // initializing variables for xml file, token value and task 4 url.
        $xml = simplexml_load_string($response); // used simplexml to parse the xml document and get the token value
        $tokenValue = $xml->Value;
        $urlTask =  $urlfile[0] . "/task"; // Task URL

        // Using cURL POST request to get atleast one reading from .../task and test connection to the url
        $ch = curl_init($urlTask);

        // Using cURL POST request to get one reading from machines
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "command=begin",
            CURLOPT_HTTPHEADER => [
                'Content-type: text/plain; charset=utf-8',
                'Authorization: Bearer ' . $tokenValue, 
            ],
        ]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // Gets the http code for error handling
        
        // This if block is for error handling, checks if the http code isn't lower than 200 or higher than 300, if it is outputs curl_erorr
        // else outputs the HTTP Status code. Needed for task.
        if ($httpCode < 200 || $httpCode >= 300) {
            echo "HTTP Status: {$httpCode}<br>" . curl_error($ch);
            echo "Response Body: {$response}<br>" . curl_error($ch);
            exit(1);
        } else {
            echo "HTTP Status: {$httpCode}<br>";
        }

        // Using regex to get the request time
        if (preg_match('/\d{2}:\d{2}:\d{2}/', $xml->Created, $m)) {
            $requestTime = $m[0];
        } else {
            $requestTime = Date("H:i:s"); // In case there is no time in the xml. H - 24-hour, i - Minutes, s - Seconds
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

        $response = curl_exec($ch); // Gets the Machine reading json file from cURL Post request response

        // Error handling, checks if the response is empty, if it is outputs error
        if ($response === false) { 
            echo "<br>cURL Error: " . curl_error($ch) . "<br>";
            exit(1);
        }   
        
    
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        // echo "HTTP Status: {$httpCode}" . PHP_EOL; // ---- error handling, checking what http code outputs

        // echo "<pre>";
        // print_r($response);

        // ---- Task 4.2

        echo "<br>Task 4.2<br>";

        // decodes the json file from $response and adds it to the new array, specifically 'data'.
        $machineReadings[] = json_decode($response, true)['data'];

        // initializing a new variable to get the "100 readings from any one node". I understood this as getting 100 readings only from one node.
        // This variable will be used for the while loop, to return only data with this node. for example returns only "node: 77" readings.
        $mainNode = $machineReadings[0]['node'];

        // A while loop to get 100 readings
        while ( count($machineReadings) < 100 ) {
            // Using cURL POST request to get the "one node" readings
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

            // Stores the data from the POST request json file for further use later
            $response = curl_exec($ch);

            // The further use later. Decodes the json file and stores only items from array value data and value node, type and temp.
            $node = json_decode($response, true)['data']['node'];
            $type = json_decode($response, true)['data']['type'];
            $temp = json_decode($response, true)['data']['temp'];

            // Checks if the node is same as the $mainNode, the type isn't unknown and the temp is in acceptable range.
            if ($mainNode === $node && $type !== "unknown" && $temp < 200 && $temp >= 0) {
                // Used for checking data.
                // echo "<pre>";
                // print_r(json_decode($response, true)['data']);
                $machineReadings[] = json_decode($response, true)['data'];
            }

        }

        // Creates a file called "machinecsvfile.csv". Used for storing $machineReadings in csv file
        $file = fopen("machinecsvfile.csv", "w") or die("Unable to open file!");

        // Starts putting the headers in the csv file
        fputcsv($file, ['Time', 'Node', 'Type', 'Temperature°C', '∆']);

        // Creates a variable for delta calculation. $prevTemp is the previous temperature for the previous machine.
        $prevTemp = null;

        // A For Each loop used for calculating the delta and putting the needed data in the csv file
        foreach($machineReadings as $mr) {
            // Initializing celsius variable for storing in csv. Since the original temperature is in Fahrenheit, the task specified to store celsius not fahrenheit.
            // Using a Fahrenheit to Celsius formula.
            $celsius = round(((int) $mr['temp'] - 32) * 5 / 9, 2);
            
            // An if checks if the previous temperature is empty, if it is then saves the current machines temperature in the variable.
            if ($prevTemp === null) {
                $prevTemp = $mr['temp'];
            // If it isnt empty then calculates the delta and sets the current machines temperature to the $prevTemp variable
            } else {
                $delta = $mr['temp'] - $prevTemp;
                $prevTemp = $mr['temp'];
            }
            
            // Finally puts the needed data in the csv file.
            fputcsv($file, [
                $requestTime,
                $mr['node'],
                $mr['type'],
                $celsius,
                $delta ?? null
            ]);

        }

        if ($file !== false) {
            echo "File is created and is not empty!";
        } else {
            echo "File is created, but empty!";
        }

        // closes the file
        fclose($file);

        
        // ---- Task 4.3

        echo "<br><br>Task 4.3<br>";

        // Initiates a cURL POST request for finishing the data collection task
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
            echo "HTTP Status: {$httpCode}<br>" . curl_error($ch);
            echo "Response Body: {$response}<br>" . curl_error($ch);
            exit(1);
        }

        echo "HTTP Status: {$httpCode}. Task concluded.";


        // ----------- task 5
        // Task 5.1
        // So I got no idea if this works since it returns code 204 and I can't or rather don't know how to check the result.

        echo "<br><br>Task 5.1";


        // Initializes variables for this task.
        $urlCSV = $urlfile[0] . "/csv";
        $destinationPath = "server_csv.csv"; // destination path

        $fileCSV = fopen($destinationPath, "w");

        // Initializes cURL and uses GET request to get the csv file for checking the results
        $ch = curl_init($urlCSV);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $tokenValue,
            ],
            CURLOPT_FILE => $fileCSV,
        ]);
        curl_exec($ch);
        
        // HTTP code and closing file
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        fclose($fileCSV);

        if ($httpCode == 200) {
            echo "<br>File downloaded successfully";
        } else if ($httpCode == 204) {
            echo "<br>Connection successful, but no content detected, Code {$httpCode}";
        } else {
            echo "<br>Error downloading file, Code {$httpCode}";
        }

        // --- Task 5.2
        echo "<br><br>Task 5.2<br>";

        // First going to get the data for the chart
        $machineCSV = file('machinecsvfile.csv');
        $headers = str_getcsv(array_shift($machineCSV));

        // Initializing array for data collection
        $machinedata = [];

        // counter for readability
        // $counter = 0;

        // Using a for each loop to get the csv file data into an array
        foreach ($machineCSV as $machineLines) {
            // debug: checking if the items are gotten and printed out
            // echo $counter . " : " . $machineLines . "<br>";
            // $counter++;

            
            $data = array_combine($headers, str_getcsv($machineLines));
            $machinedata[] = $data; 
        }

        // Checking if the array $machinedata got the csv data.
        // echo "<br><pre>";
        // print_r($machinedata);

        // Creating a new array that stores only the data needed for the chart
        $dataPoints = [];

        // Using a for each loop to get the data needed for the $dataPoints array
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
 
// Creates a variable in js, which creates a chart graph for display and adds the look to the div element in html
var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: true,
	theme: "light2",
	title:{
		text: "Task 5.2: Temperature Graph (°C)"
	},
	axisY: {
		title: "Temperatures (in celsius)"
	},
    axisX: {
        title: "Indexes"
    },
	data: [{
		type: "column",
		yValueFormatString: "#,##0.## °C",
		dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
	}]
});
// Renders the chart
chart.render();
 
}
</script>

<!-- Div element for the chart container -->
<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

</body>
</html>
