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
        $url = "10.101.0.1:10101/?token=renew";

        $uname = "reiniss";
        $pw = "BarkBarkBark";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $uname . ':' . $pw,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            fwrite(STDERR, 'cURL Error: ' . curl_error($ch) . PHP_EOL);
            exit(1);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            fwrite(STDERR, "HTTP Status: {$httpCode}" . PHP_EOL);
            fwrite(STDERR, "Response Body: {$response}" . PHP_EOL);
            exit(1);
        }

        echo "HTTP Status: {$httpCode}" . PHP_EOL;
        echo "Response Body: {$response}"

    ?>
    
    <!-- <form method="get" action="http://10.101.0.1:10101/?token=renew">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="submit">
    </form> -->


</body>
</html>
