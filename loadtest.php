<?php

$url = "http://localhost/temb2c/schedule.php";

$concurrentUsers = 100;
$totalRequests   = 5000;
$rounds          = intdiv($totalRequests, $concurrentUsers);

$logFile = __DIR__ . "/loadtest.log";

$success = 0;
$failed  = 0;
$times   = [];

file_put_contents($logFile, "PHP Load Test Started\n");
file_put_contents($logFile, "Concurrent Users: $concurrentUsers\n", FILE_APPEND);
file_put_contents($logFile, "Total Requests: $totalRequests\n\n", FILE_APPEND);

for ($round = 1; $round <= $rounds; $round++) {

    $mh = curl_multi_init();
    $handles = [];
    $startTimes = [];

    // init 100 concurrent users
    for ($i = 0; $i < $concurrentUsers; $i++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['X-Load-Test: 1']
        ]);

        $handles[$i] = $ch;
        $startTimes[$i] = microtime(true);
        curl_multi_add_handle($mh, $ch);
    }

    // execute batch
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    // collect results
    foreach ($handles as $i => $ch) {
        $responseTime = round((microtime(true) - $startTimes[$i]) * 1000, 2);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $times[] = $responseTime;

        if ($httpCode >= 200 && $httpCode < 300) {
            $success++;
        } else {
            $failed++;
        }

        $logLine = "Round $round | User #" . ($i + 1) .
                   " | HTTP: $httpCode" .
                   " | Time: {$responseTime}ms\n";

        file_put_contents($logFile, $logLine, FILE_APPEND);

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
}

// summary
$avg = round(array_sum($times) / count($times), 2);
$min = min($times);
$max = max($times);

$summary = "\n--- SUMMARY ---\n" .
           "Concurrent Users : $concurrentUsers\n" .
           "Total Requests   : $totalRequests\n" .
           "Success Requests : $success\n" .
           "Failed Requests  : $failed\n" .
           "Avg Time         : {$avg}ms\n" .
           "Min Time         : {$min}ms\n" .
           "Max Time         : {$max}ms\n";

file_put_contents($logFile, $summary, FILE_APPEND);

echo "Load test done (100 users, 5000 requests). Check loadtest.log\n";
