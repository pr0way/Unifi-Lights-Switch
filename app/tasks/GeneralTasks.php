<?php

use Crunz\Schedule;

$schedule = new Schedule();

$taskEveryMinute = $schedule
    ->run(PHP_BINARY . ' insertEntry.php')
    ->everyMinute()
    ->description('Add entry to database every minute');

$taskEveryFifteenMinutes = $schedule
    ->run(PHP_BINARY . ' dbCheck.php')
    ->everyFifteenMinutes()
    ->description('Check database every fifteen minutes');

return $schedule;