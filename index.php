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

        $url = "10.101.0.1:10101/?token=renew";

        $uname = "reiniss";
        $pw = "BarkBarkBark";

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

        echo $response . "<br>";
        
    
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        // echo "HTTP Status: {$httpCode}" . PHP_EOL;


    ?>
    
    <!-- <form method="get" action="http://10.101.0.1:10101/?token=renew">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="submit">
    </form> -->


</body>
</html>
