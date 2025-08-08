<?php
// redis.php
function redisClient(): Redis {
    $r = new Redis();
    // Adjust host/port if needed
    $r->connect('127.0.0.1', 6379);
    return $r;
}

