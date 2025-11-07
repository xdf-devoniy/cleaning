<?php
require __DIR__ . '/../bootstrap.php';

use App\Core\Queue;

$queue = new Queue();
while ($job = $queue->pop('default')) {
    echo 'Processing job #' . $job['id'] . PHP_EOL;
    $queue->delete($job['id']);
}
